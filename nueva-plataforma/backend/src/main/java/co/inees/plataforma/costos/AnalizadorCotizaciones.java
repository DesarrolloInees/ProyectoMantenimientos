package co.inees.plataforma.costos;

import static co.inees.plataforma.comun.Html.esc;

import java.io.IOException;
import java.io.InputStream;
import java.math.BigDecimal;
import java.math.RoundingMode;
import java.text.Normalizer;
import java.time.LocalDate;
import java.time.format.DateTimeFormatter;
import java.time.format.DateTimeParseException;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;

import org.apache.poi.ss.usermodel.Cell;
import org.apache.poi.ss.usermodel.CellType;
import org.apache.poi.ss.usermodel.DataFormatter;
import org.apache.poi.ss.usermodel.DateUtil;
import org.apache.poi.ss.usermodel.Row;
import org.apache.poi.ss.usermodel.Sheet;
import org.apache.poi.ss.usermodel.Workbook;
import org.apache.poi.ss.usermodel.WorkbookFactory;

import co.inees.plataforma.comun.Excel;
import co.inees.plataforma.comun.Html;
import co.inees.plataforma.comun.Pdf;
import co.inees.plataforma.config.NegocioException;

/**
 * Analizador de cotizaciones (reporteFacturacion de PHP). Lee el Excel de control de cotizaciones de la empresa: la
 * hoja «CONTROL COT» alimenta la tabla filtrable y las hojas dinámicas «IM FAC», «IM COT» e «IM Q» el reporte gerencial.
 * No guarda nada en la base: cada consulta trabaja sobre el archivo que se sube.
 */
public final class AnalizadorCotizaciones {

	public record Cotizacion(String cotizacion, String remision, LocalDate fecha, String fechaTexto, String categoria,
		String estado, String items, BigDecimal subtotal) {
	}

	public record Filtro(LocalDate desde, LocalDate hasta, String categoria, String estado) {

		boolean cumple(Cotizacion c) {
			if (desde != null && (c.fecha() == null || c.fecha().isBefore(desde))) {
				return false;
			}
			if (hasta != null && (c.fecha() == null || c.fecha().isAfter(hasta))) {
				return false;
			}
			if (categoria != null && !categoria.isBlank() && !categoria.trim().equalsIgnoreCase(c.categoria().trim())) {
				return false;
			}
			return estado == null || estado.isBlank() || estado.trim().equalsIgnoreCase(c.estado().trim());
		}
	}

	/** Filas de una hoja dinámica (encabezado → valor), sin la fila «Total general». */
	public record Hoja(String nombre, List<String> encabezados, List<Map<String, Object>> filas) {
	}

	public record Analisis(List<Cotizacion> cotizaciones, List<String> categorias, List<String> estados, List<String> hojas,
		int facturas, int cotizacionesPendientes, int qPendientes) {
	}

	private static final DataFormatter FORMATO = new DataFormatter(Locale.forLanguageTag("es-CO"));
	private static final List<DateTimeFormatter> FECHAS = List.of(DateTimeFormatter.ISO_LOCAL_DATE,
		DateTimeFormatter.ofPattern("d/M/yyyy"), DateTimeFormatter.ofPattern("d/M/yy"), DateTimeFormatter.ofPattern("d-M-yyyy"),
		DateTimeFormatter.ofPattern("d-MMM-yy", Locale.forLanguageTag("es")), DateTimeFormatter.ofPattern("d-MMM-yyyy", Locale.forLanguageTag("es")),
		DateTimeFormatter.ofPattern("M/d/yy"));

	private final List<Cotizacion> cotizaciones;
	private final Hoja facturas;
	private final Hoja cotPendientes;
	private final Hoja qPendientes;
	private final List<String> hojas;

	private AnalizadorCotizaciones(List<Cotizacion> cotizaciones, Hoja facturas, Hoja cotPendientes, Hoja qPendientes, List<String> hojas) {
		this.cotizaciones = cotizaciones;
		this.facturas = facturas;
		this.cotPendientes = cotPendientes;
		this.qPendientes = qPendientes;
		this.hojas = hojas;
	}

	public static AnalizadorCotizaciones leer(InputStream in) {
		try (Workbook libro = WorkbookFactory.create(in)) {
			List<String> nombres = new ArrayList<>();
			libro.forEach(h -> nombres.add(h.getSheetName()));
			Sheet control = hoja(libro, "CONTROL COT");
			if (control == null) {
				control = libro.getSheetAt(0);
			}
			return new AnalizadorCotizaciones(control(control),
				dinamica(hoja(libro, "IM FAC"), "IM FAC", "n factura", "no factura", "nro factura"),
				dinamica(hoja(libro, "IM COT"), "IM COT", "n cotizacion", "no cotizacion", "nro cotizacion"),
				dinamica(hoja(libro, "IM Q"), "IM Q", "n cotizacion", "no cotizacion", "nro cotizacion"), nombres);
		}
		catch (IOException | RuntimeException e) {
			if (e instanceof NegocioException n) {
				throw n;
			}
			throw new NegocioException("No se pudo leer el archivo. Debe ser el Excel de control de cotizaciones (.xlsx o .xls).");
		}
	}

	public Analisis analisis() {
		return new Analisis(cotizaciones, distintos(cotizaciones.stream().map(Cotizacion::categoria).toList()),
			distintos(cotizaciones.stream().map(Cotizacion::estado).toList()), hojas, facturas.filas().size(),
			cotPendientes.filas().size(), qPendientes.filas().size());
	}

	public List<Cotizacion> filtradas(Filtro f) {
		return cotizaciones.stream().filter(f::cumple).toList();
	}

	/** Lista filtrada en Excel (botón «Excel» de la tabla en PHP). */
	public byte[] excel(Filtro f) {
		List<List<Object>> filas = new ArrayList<>();
		for (Cotizacion c : filtradas(f)) {
			filas.add(java.util.Arrays.asList(c.cotizacion(), c.remision(), c.fecha() != null ? c.fecha() : c.fechaTexto(), c.categoria(),
				c.estado(), c.items(), new Excel.Pesos(c.subtotal())));
		}
		return new Excel().hoja("Cotizaciones", titulo(f), List.of("N.º cotización", "Remisión", "Fecha", "Categoría", "Estado", "Ítems",
			"Subtotal"), filas).bytes();
	}

	/** Lista filtrada en PDF (botón «PDF lista» de la tabla en PHP). */
	public byte[] pdfLista(Filtro f) {
		List<Cotizacion> lista = filtradas(f);
		BigDecimal total = lista.stream().map(Cotizacion::subtotal).reduce(BigDecimal.ZERO, BigDecimal::add);
		StringBuilder b = new StringBuilder(Pdf.encabezado("Analizador de cotizaciones", titulo(f)));
		b.append("<table><thead><tr><th>N.º cotización</th><th>Remisión</th><th>Fecha</th><th>Categoría</th><th>Estado</th><th>Ítems</th>")
			.append("<th class=\"der\">Subtotal</th></tr></thead><tbody>");
		for (Cotizacion c : lista) {
			b.append("<tr><td><b>").append(esc(c.cotizacion())).append("</b></td><td>").append(esc(c.remision())).append("</td><td>")
				.append(c.fecha() != null ? Html.fecha(c.fecha()) : esc(c.fechaTexto())).append("</td><td>").append(esc(c.categoria()))
				.append("</td><td>").append(esc(c.estado())).append("</td><td>").append(esc(c.items())).append("</td><td class=\"der\">")
				.append(Html.pesos(c.subtotal())).append("</td></tr>");
		}
		b.append("<tr class=\"total\"><td colspan=\"6\" class=\"der\">").append(lista.size()).append(" registros · Total</td><td class=\"der\">")
			.append(Html.pesos(total)).append("</td></tr></tbody></table>");
		return Pdf.generar(Pdf.documento("Cotizaciones", "@page { size: A4 landscape; } table { -fs-table-paginate: paginate; }", b.toString()));
	}

	/** Reporte gerencial de las tres hojas dinámicas (botón «Exportar PDF (IM FAC, COT, Q)» de PHP). */
	public byte[] pdfGerencial() {
		if (facturas.filas().isEmpty() && cotPendientes.filas().isEmpty() && qPendientes.filas().isEmpty()) {
			throw new NegocioException("El archivo no tiene las hojas «IM FAC», «IM COT» ni «IM Q» con datos.");
		}
		StringBuilder b = new StringBuilder(Pdf.encabezado("Reportes gerenciales de facturación", "Generado el " + Html.fecha(LocalDate.now())));
		seccion(b, "Reporte de facturas", facturas, List.of("N° FACTURA", "N° COTIZACION", "N° OC", "FECHA DE FACTURA", "DATAICO"),
			"Suma de TOTAL COT");
		seccion(b, "Cotizaciones pendientes", cotPendientes, List.of("N° COTIZACION", "N° REMISION", "CATEGORIA", "FECHA REALIZACION",
			"FECHA ENVIADO"), "Suma de SUBTOTAL");
		seccion(b, "Cotizaciones Q pendientes", qPendientes, List.of("N° COTIZACION", "N° REMISION", "FECHA DE REALIZACION"),
			"Suma de SUBTOTAL");
		return Pdf.generar(Pdf.documento("Reportes gerenciales", """
			h2 { text-align: center; color: #1e3a8a; border-bottom: none; margin-top: 16pt; }
			th { background: #1e3a8a; text-align: center; }
			table { -fs-table-paginate: paginate; }
			""", b.toString()));
	}

	private static void seccion(StringBuilder b, String titulo, Hoja hoja, List<String> columnas, String columnaValor) {
		b.append("<h2>").append(esc(titulo.toUpperCase(Locale.ROOT))).append("</h2>");
		if (hoja.filas().isEmpty()) {
			b.append("<p class=\"muted cen\">La hoja «").append(esc(hoja.nombre())).append("» no está en el archivo o no tiene datos.</p>");
			return;
		}
		b.append("<table><thead><tr>");
		columnas.forEach(c -> b.append("<th>").append(esc(c)).append("</th>"));
		b.append("<th>").append(esc(columnaValor.replace("Suma de ", ""))).append("</th></tr></thead><tbody>");
		BigDecimal total = BigDecimal.ZERO;
		for (var f : hoja.filas()) {
			b.append("<tr>");
			for (int i = 0; i < columnas.size(); i++) {
				Object v = valor(f, columnas.get(i));
				String texto = v instanceof LocalDate d ? Html.fecha(d) : esc(v);
				b.append("<td").append(i == 0 ? " style=\"font-weight:bold\"" : "").append(">").append(texto).append("</td>");
			}
			BigDecimal monto = numero(valor(f, columnaValor));
			total = total.add(monto);
			b.append("<td class=\"der\">").append(Html.pesos(monto)).append("</td></tr>");
		}
		b.append("<tr class=\"total\"><td colspan=\"").append(columnas.size()).append("\" class=\"der\">Total general</td><td class=\"der\">")
			.append(Html.pesos(total)).append("</td></tr></tbody></table>");
	}

	// ------------------------------------------------------------------------------------------- lectura
	private static List<Cotizacion> control(Sheet hoja) {
		int filaEnc = buscarEncabezado(hoja, "n cotizacion", "no cotizacion", "nro cotizacion", "cotizacion");
		if (filaEnc < 0) {
			throw new NegocioException("No se encontró la columna «N° COTIZACION» en la hoja «" + hoja.getSheetName() + "».");
		}
		Map<String, Integer> col = columnas(hoja.getRow(filaEnc));
		Integer cCot = primera(col, "n cotizacion", "no cotizacion", "nro cotizacion", "cotizacion"),
			cRem = primera(col, "n remision", "no remision", "nro remision", "remision"),
			cCat = primera(col, "categoria"), cFecha = primera(col, "fecha de realizacion", "fecha realizacion"),
			cEstado = primera(col, "estado"), cItems = primera(col, "items"), cSub = primera(col, "subtotal", "total cot");
		List<Cotizacion> r = new ArrayList<>();
		for (int i = filaEnc + 1; i <= hoja.getLastRowNum(); i++) {
			Row fila = hoja.getRow(i);
			String cot = texto(celda(fila, cCot));
			if (cot.isEmpty() || cot.toUpperCase(Locale.ROOT).contains("TOTAL")) {
				continue;
			}
			Cell cf = celda(fila, cFecha);
			r.add(new Cotizacion(cot, texto(celda(fila, cRem)), fecha(cf), texto(cf), texto(celda(fila, cCat)), texto(celda(fila, cEstado)),
				texto(celda(fila, cItems)), numero(valorCelda(celda(fila, cSub)))));
		}
		return r;
	}

	private static Hoja dinamica(Sheet hoja, String nombre, String... columnaClave) {
		if (hoja == null) {
			return new Hoja(nombre, List.of(), List.of());
		}
		int filaEnc = buscarEncabezado(hoja, columnaClave);
		if (filaEnc < 0) {
			return new Hoja(hoja.getSheetName(), List.of(), List.of());
		}
		Row enc = hoja.getRow(filaEnc);
		List<String> encabezados = new ArrayList<>();
		for (int c = 0; c < enc.getLastCellNum(); c++) {
			encabezados.add(texto(enc.getCell(c)));
		}
		List<Map<String, Object>> filas = new ArrayList<>();
		for (int i = filaEnc + 1; i <= hoja.getLastRowNum(); i++) {
			Row fila = hoja.getRow(i);
			String primero = texto(celda(fila, 0)).toUpperCase(Locale.ROOT);
			if (primero.isEmpty() || primero.contains("TOTAL GENERAL")) {
				continue;
			}
			Map<String, Object> m = new LinkedHashMap<>();
			for (int c = 0; c < encabezados.size(); c++) {
				if (!encabezados.get(c).isEmpty()) {
					m.put(encabezados.get(c), valorCelda(celda(fila, c)));
				}
			}
			filas.add(m);
		}
		return new Hoja(hoja.getSheetName(), encabezados, filas);
	}

	private static Sheet hoja(Workbook libro, String contiene) {
		for (Sheet h : libro) {
			if (h.getSheetName().toUpperCase(Locale.ROOT).contains(contiene)) {
				return h;
			}
		}
		return null;
	}

	private static int buscarEncabezado(Sheet hoja, String... claves) {
		for (int i = hoja.getFirstRowNum(); i <= Math.min(hoja.getLastRowNum(), 40); i++) {
			Row fila = hoja.getRow(i);
			if (fila == null) {
				continue;
			}
			for (Cell c : fila) {
				String n = normalizar(texto(c));
				for (String clave : claves) {
					if (n.equals(clave)) {
						return i;
					}
				}
			}
		}
		return -1;
	}

	private static Map<String, Integer> columnas(Row enc) {
		Map<String, Integer> m = new LinkedHashMap<>();
		for (Cell c : enc) {
			m.putIfAbsent(normalizar(texto(c)), c.getColumnIndex());
		}
		return m;
	}

	private static Integer primera(Map<String, Integer> col, String... nombres) {
		for (String n : nombres) {
			if (col.containsKey(n)) {
				return col.get(n);
			}
		}
		return null;
	}

	private static Cell celda(Row fila, Integer c) {
		return fila == null || c == null ? null : fila.getCell(c);
	}

	private static String texto(Cell c) {
		if (c == null) {
			return "";
		}
		if (c.getCellType() == CellType.FORMULA) {
			try {
				return c.getCachedFormulaResultType() == CellType.NUMERIC ? FORMATO.formatRawCellContents(c.getNumericCellValue(),
					c.getCellStyle().getDataFormat(), c.getCellStyle().getDataFormatString()).trim() : c.getStringCellValue().trim();
			}
			catch (RuntimeException e) {
				return "";
			}
		}
		return FORMATO.formatCellValue(c).trim();
	}

	/** Fecha de la celda: fecha de Excel o texto en los formatos usuales. */
	private static LocalDate fecha(Cell c) {
		if (c == null) {
			return null;
		}
		boolean numerica = c.getCellType() == CellType.NUMERIC
			|| (c.getCellType() == CellType.FORMULA && c.getCachedFormulaResultType() == CellType.NUMERIC);
		if (numerica && DateUtil.isCellDateFormatted(c)) {
			return c.getLocalDateTimeCellValue().toLocalDate();
		}
		if (numerica) {
			double v = c.getNumericCellValue();
			return v > 20000 && v < 80000 ? DateUtil.getLocalDateTime(v).toLocalDate() : null;
		}
		String t = texto(c);
		if (t.length() > 10) {
			t = t.substring(0, 10).trim();
		}
		for (DateTimeFormatter f : FECHAS) {
			try {
				return LocalDate.parse(t, f);
			}
			catch (DateTimeParseException e) {
				// siguiente formato
			}
		}
		return null;
	}

	/** Número, fecha o texto según la celda (para las hojas dinámicas). */
	private static Object valorCelda(Cell c) {
		if (c == null) {
			return null;
		}
		CellType tipo = c.getCellType() == CellType.FORMULA ? c.getCachedFormulaResultType() : c.getCellType();
		if (tipo == CellType.NUMERIC) {
			if (DateUtil.isCellDateFormatted(c)) {
				return c.getLocalDateTimeCellValue().toLocalDate();
			}
			return BigDecimal.valueOf(c.getNumericCellValue()).setScale(2, RoundingMode.HALF_UP);
		}
		String t = texto(c);
		return t.isEmpty() ? null : t;
	}

	private static Object valor(Map<String, Object> fila, String columna) {
		String buscada = normalizar(columna);
		for (var e : fila.entrySet()) {
			if (normalizar(e.getKey()).equals(buscada)) {
				return e.getValue();
			}
		}
		return null;
	}

	/** Número a partir de una celda o de un texto con signos de moneda y separadores. */
	static BigDecimal numero(Object v) {
		if (v instanceof BigDecimal b) {
			return b;
		}
		if (v instanceof Number n) {
			return BigDecimal.valueOf(n.doubleValue());
		}
		if (v == null) {
			return BigDecimal.ZERO;
		}
		String s = v.toString().replaceAll("[^0-9,.-]", "");
		if (s.isEmpty() || s.equals("-")) {
			return BigDecimal.ZERO;
		}
		// 1.234.567,89 (es-CO) o 1,234,567.89 (en)
		int coma = s.lastIndexOf(','), punto = s.lastIndexOf('.');
		if (coma > punto) {
			s = s.replace(".", "").replace(',', '.');
		}
		else if (punto > coma && coma >= 0) {
			s = s.replace(",", "");
		}
		else if (punto >= 0 && s.indexOf('.') != punto) {
			s = s.replace(".", "");
		}
		else if (coma >= 0 && s.indexOf(',') != coma) {
			s = s.replace(",", "");
		}
		else if (coma >= 0) {
			s = s.length() - coma - 1 == 3 ? s.replace(",", "") : s.replace(',', '.');
		}
		else if (punto >= 0 && s.length() - punto - 1 == 3) {
			s = s.replace(".", "");
		}
		try {
			return new BigDecimal(s);
		}
		catch (NumberFormatException e) {
			return BigDecimal.ZERO;
		}
	}

	private static String titulo(Filtro f) {
		List<String> partes = new ArrayList<>();
		if (f.desde() != null || f.hasta() != null) {
			partes.add("Del " + (f.desde() == null ? "inicio" : Html.fecha(f.desde())) + " al " + (f.hasta() == null ? "final" : Html.fecha(f.hasta())));
		}
		if (f.categoria() != null && !f.categoria().isBlank()) {
			partes.add("categoría " + f.categoria());
		}
		if (f.estado() != null && !f.estado().isBlank()) {
			partes.add("estado " + f.estado());
		}
		return partes.isEmpty() ? "Todas las cotizaciones" : String.join(" · ", partes);
	}

	private static List<String> distintos(List<String> valores) {
		return valores.stream().map(String::trim).filter(s -> !s.isEmpty()).map(s -> s.toUpperCase(Locale.ROOT)).distinct().sorted().toList();
	}

	static String normalizar(String s) {
		String sinTildes = Normalizer.normalize(s == null ? "" : s, Normalizer.Form.NFD).replaceAll("\\p{M}", "");
		return sinTildes.toLowerCase(Locale.ROOT).replaceAll("[^a-z0-9]+", " ").trim();
	}
}
