package co.inees.plataforma.comun;

import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.LocalTime;
import java.util.ArrayList;
import java.util.List;

import org.apache.poi.ss.usermodel.BorderStyle;
import org.apache.poi.ss.usermodel.Cell;
import org.apache.poi.ss.usermodel.CellStyle;
import org.apache.poi.ss.usermodel.FillPatternType;
import org.apache.poi.ss.usermodel.Font;
import org.apache.poi.ss.usermodel.IndexedColors;
import org.apache.poi.ss.usermodel.Row;
import org.apache.poi.xssf.streaming.SXSSFSheet;
import org.apache.poi.xssf.streaming.SXSSFWorkbook;

/**
 * Genera archivos .xlsx sencillos: una o varias hojas con encabezado fijo, filtros y formato de fecha, hora y moneda.
 *
 * <pre>
 * byte[] xlsx = new Excel().hoja("Servicios", List.of("Fecha", "Valor"), filas).bytes();
 * </pre>
 */
public class Excel {

	/** Celda con formato de pesos. */
	public record Pesos(Number valor) {
	}

	private final SXSSFWorkbook libro = new SXSSFWorkbook(500);
	private final CellStyle encabezado;
	private final CellStyle fecha;
	private final CellStyle fechaHora;
	private final CellStyle hora;
	private final CellStyle pesos;
	private final CellStyle titulo;

	public Excel() {
		var df = libro.createDataFormat();
		Font negrita = libro.createFont();
		negrita.setBold(true);
		negrita.setColor(IndexedColors.WHITE.getIndex());
		encabezado = libro.createCellStyle();
		encabezado.setFont(negrita);
		encabezado.setFillForegroundColor(IndexedColors.GREY_80_PERCENT.getIndex());
		encabezado.setFillPattern(FillPatternType.SOLID_FOREGROUND);
		encabezado.setBorderBottom(BorderStyle.THIN);
		encabezado.setWrapText(true);
		fecha = libro.createCellStyle();
		fecha.setDataFormat(df.getFormat("yyyy-mm-dd"));
		fechaHora = libro.createCellStyle();
		fechaHora.setDataFormat(df.getFormat("yyyy-mm-dd hh:mm"));
		hora = libro.createCellStyle();
		hora.setDataFormat(df.getFormat("hh:mm"));
		pesos = libro.createCellStyle();
		pesos.setDataFormat(df.getFormat("\"$\"#,##0"));
		Font grande = libro.createFont();
		grande.setBold(true);
		grande.setFontHeightInPoints((short) 13);
		titulo = libro.createCellStyle();
		titulo.setFont(grande);
	}

	public Excel hoja(String nombre, List<String> columnas, List<? extends List<?>> filas) {
		return hoja(nombre, null, columnas, filas);
	}

	/** Agrega una hoja; si hay título va en la primera fila y los encabezados en la tercera. */
	public Excel hoja(String nombre, String tituloHoja, List<String> columnas, List<? extends List<?>> filas) {
		SXSSFSheet hoja = libro.createSheet(nombreValido(nombre));
		hoja.trackAllColumnsForAutoSizing();
		int r = 0;
		if (tituloHoja != null) {
			Cell c = hoja.createRow(r).createCell(0);
			c.setCellValue(tituloHoja);
			c.setCellStyle(titulo);
			r = 2;
		}
		Row cab = hoja.createRow(r);
		for (int i = 0; i < columnas.size(); i++) {
			Cell c = cab.createCell(i);
			c.setCellValue(columnas.get(i));
			c.setCellStyle(encabezado);
		}
		int primeraFila = r;
		for (List<?> fila : filas) {
			Row row = hoja.createRow(++r);
			for (int i = 0; i < fila.size(); i++) {
				escribir(row.createCell(i), fila.get(i));
			}
		}
		hoja.createFreezePane(0, primeraFila + 1);
		if (!columnas.isEmpty()) {
			hoja.setAutoFilter(new org.apache.poi.ss.util.CellRangeAddress(primeraFila, Math.max(primeraFila, r), 0,
				columnas.size() - 1));
		}
		for (int i = 0; i < columnas.size(); i++) {
			hoja.autoSizeColumn(i);
			int ancho = Math.min(hoja.getColumnWidth(i) + 512, 60 * 256);
			hoja.setColumnWidth(i, Math.max(ancho, 10 * 256));
		}
		return this;
	}

	public byte[] bytes() {
		try (var out = new ByteArrayOutputStream()) {
			libro.write(out);
			libro.close();
			return out.toByteArray();
		}
		catch (IOException e) {
			throw new IllegalStateException("No se pudo generar el Excel", e);
		}
	}

	/** Atajo: lista de filas a partir de arreglos. */
	public static List<List<Object>> filas(List<Object[]> datos) {
		List<List<Object>> r = new ArrayList<>(datos.size());
		for (Object[] d : datos) {
			r.add(java.util.Arrays.asList(d));
		}
		return r;
	}

	private void escribir(Cell c, Object v) {
		switch (v) {
			case null -> c.setBlank();
			case Pesos p -> {
				if (p.valor() == null) {
					c.setBlank();
				}
				else {
					c.setCellValue(p.valor().doubleValue());
					c.setCellStyle(pesos);
				}
			}
			case BigDecimal b -> c.setCellValue(b.doubleValue());
			case Number n -> c.setCellValue(n.doubleValue());
			case Boolean b -> c.setCellValue(b ? "Sí" : "No");
			case LocalDate d -> {
				c.setCellValue(d);
				c.setCellStyle(fecha);
			}
			case LocalDateTime d -> {
				c.setCellValue(d);
				c.setCellStyle(fechaHora);
			}
			case LocalTime t -> c.setCellValue(t.toString().substring(0, 5));
			case java.sql.Date d -> {
				c.setCellValue(d.toLocalDate());
				c.setCellStyle(fecha);
			}
			case java.sql.Timestamp t -> {
				c.setCellValue(t.toLocalDateTime());
				c.setCellStyle(fechaHora);
			}
			case java.sql.Time t -> c.setCellValue(t.toLocalTime().toString().substring(0, 5));
			default -> {
				String s = v.toString();
				c.setCellValue(s.length() > 32000 ? s.substring(0, 32000) : s);
			}
		}
	}

	private static String nombreValido(String nombre) {
		String n = nombre.replaceAll("[\\\\/?*\\[\\]:]", " ").trim();
		return n.length() > 31 ? n.substring(0, 31) : n;
	}
}
