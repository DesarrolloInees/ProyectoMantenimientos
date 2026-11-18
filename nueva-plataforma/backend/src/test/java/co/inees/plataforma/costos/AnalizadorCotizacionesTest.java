package co.inees.plataforma.costos;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertThrows;
import static org.junit.jupiter.api.Assertions.assertTrue;

import java.io.ByteArrayInputStream;
import java.io.ByteArrayOutputStream;
import java.math.BigDecimal;
import java.time.LocalDate;

import org.apache.poi.ss.usermodel.Sheet;
import org.apache.poi.xssf.usermodel.XSSFWorkbook;
import org.junit.jupiter.api.Test;

import co.inees.plataforma.config.NegocioException;

class AnalizadorCotizacionesTest {

	/** Libro con la forma del Excel de control: títulos arriba, fechas de Excel y de texto, y tablas dinámicas. */
	private static byte[] libro() throws Exception {
		try (var wb = new XSSFWorkbook(); var out = new ByteArrayOutputStream()) {
			var formatoFecha = wb.createCellStyle();
			formatoFecha.setDataFormat(wb.getCreationHelper().createDataFormat().getFormat("dd/mm/yyyy"));
			Sheet control = wb.createSheet("CONTROL COT 2026");
			control.createRow(0).createCell(0).setCellValue("CONTROL DE COTIZACIONES");
			var enc = control.createRow(2);
			String[] cab = { "N° COTIZACION", "N° REMISION", "CATEGORIA", "FECHA DE REALIZACION", "ESTADO ", "ITEMS", "SUBTOTAL" };
			for (int i = 0; i < cab.length; i++) {
				enc.createCell(i).setCellValue(cab[i]);
			}
			Object[][] filas = {
				{ "COT-001", "R-10", "MQ", LocalDate.of(2026, 10, 5), "FACTURADO", "Máquina SDM-500", 1500000.0 },
				{ "COT-002", "R-11", "RP", "12/10/2026", "PENDIENTE", "Kit correas", "$ 250.000" },
				{ "COT-003", "", "SE", LocalDate.of(2026, 11, 2), "pendiente", "Servicio", 80000.0 },
				{ "TOTAL", "", "", "", "", "", 1830000.0 } };
			for (int r = 0; r < filas.length; r++) {
				var fila = control.createRow(3 + r);
				for (int c = 0; c < filas[r].length; c++) {
					var celda = fila.createCell(c);
					switch (filas[r][c]) {
						case LocalDate d -> {
							celda.setCellValue(d);
							celda.setCellStyle(formatoFecha);
						}
						case Double n -> celda.setCellValue(n);
						case Object o -> celda.setCellValue(o.toString());
					}
				}
			}
			Sheet fac = wb.createSheet("IM FAC");
			var ef = fac.createRow(3);
			String[] cf = { "N° FACTURA", "N° COTIZACION", "N° OC", "FECHA DE FACTURA", "DATAICO", "Suma de TOTAL COT" };
			for (int i = 0; i < cf.length; i++) {
				ef.createCell(i).setCellValue(cf[i]);
			}
			var f1 = fac.createRow(4);
			f1.createCell(0).setCellValue("FE-100");
			f1.createCell(1).setCellValue("COT-001");
			f1.createCell(5).setCellValue(1500000);
			var ft = fac.createRow(5);
			ft.createCell(0).setCellValue("Total general");
			ft.createCell(5).setCellValue(1500000);
			wb.createSheet("IM Q").createRow(0).createCell(0).setCellValue("sin tabla");
			wb.write(out);
			return out.toByteArray();
		}
	}

	@Test
	void leeLaHojaDeControlYLasDinamicas() throws Exception {
		var a = AnalizadorCotizaciones.leer(new ByteArrayInputStream(libro()));
		var r = a.analisis();
		assertEquals(3, r.cotizaciones().size(), "la fila TOTAL no cuenta");
		assertEquals(LocalDate.of(2026, 10, 5), r.cotizaciones().get(0).fecha());
		assertEquals(LocalDate.of(2026, 10, 12), r.cotizaciones().get(1).fecha(), "fecha escrita como texto");
		assertEquals(0, new BigDecimal("250000").compareTo(r.cotizaciones().get(1).subtotal()), "moneda escrita como texto");
		assertEquals(1, r.facturas(), "la fila Total general no cuenta");
		assertEquals(0, r.cotizacionesPendientes());
		assertEquals(0, r.qPendientes());
		assertEquals(java.util.List.of("MQ", "RP", "SE"), r.categorias());
		assertEquals(java.util.List.of("FACTURADO", "PENDIENTE"), r.estados());
	}

	@Test
	void filtraPorFechaCategoriaYEstado() throws Exception {
		var a = AnalizadorCotizaciones.leer(new ByteArrayInputStream(libro()));
		assertEquals(2, a.filtradas(new AnalizadorCotizaciones.Filtro(null, null, null, "PENDIENTE")).size(), "el estado no distingue mayúsculas");
		assertEquals(2, a.filtradas(new AnalizadorCotizaciones.Filtro(LocalDate.of(2026, 10, 1), LocalDate.of(2026, 10, 31), null, null)).size());
		assertEquals(1, a.filtradas(new AnalizadorCotizaciones.Filtro(null, null, "se", null)).size());
	}

	@Test
	void generaExcelYPdf() throws Exception {
		var a = AnalizadorCotizaciones.leer(new ByteArrayInputStream(libro()));
		var todos = new AnalizadorCotizaciones.Filtro(null, null, null, null);
		assertTrue(a.excel(todos).length > 1000);
		assertTrue(new String(a.pdfLista(todos), 0, 5).startsWith("%PDF"));
		assertTrue(new String(a.pdfGerencial(), 0, 5).startsWith("%PDF"));
	}

	@Test
	void interpretaNumerosEnVariosFormatos() {
		assertEquals(0, new BigDecimal("1234567").compareTo(AnalizadorCotizaciones.numero("$1.234.567")));
		assertEquals(0, new BigDecimal("1234567.5").compareTo(AnalizadorCotizaciones.numero("1.234.567,5")));
		assertEquals(0, new BigDecimal("1234567.5").compareTo(AnalizadorCotizaciones.numero("1,234,567.5")));
		assertEquals(0, new BigDecimal("1234.5").compareTo(AnalizadorCotizaciones.numero("1234.5")));
		assertEquals(0, BigDecimal.ZERO.compareTo(AnalizadorCotizaciones.numero("—")));
	}

	@Test
	void rechazaUnArchivoQueNoEsExcel() {
		assertThrows(NegocioException.class, () -> AnalizadorCotizaciones.leer(new ByteArrayInputStream("hola".getBytes())));
	}
}
