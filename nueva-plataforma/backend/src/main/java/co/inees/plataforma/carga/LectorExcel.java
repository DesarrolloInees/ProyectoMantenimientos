package co.inees.plataforma.carga;

import java.io.IOException;
import java.io.InputStream;
import java.text.Normalizer;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;

import org.apache.poi.ss.usermodel.Cell;
import org.apache.poi.ss.usermodel.DataFormatter;
import org.apache.poi.ss.usermodel.Row;
import org.apache.poi.ss.usermodel.Sheet;
import org.apache.poi.ss.usermodel.Workbook;
import org.apache.poi.ss.usermodel.WorkbookFactory;

import co.inees.plataforma.config.NegocioException;
import tools.jackson.databind.json.JsonMapper;
import tools.jackson.core.type.TypeReference;

/**
 * Lee el Excel de Prosegur sin depender de un formato fijo: busca la fila de encabezados y reconoce las columnas
 * por sus nombres (lista configurable en el parámetro excel_prosegur_columnas).
 */
public class LectorExcel {

	public record Fila(int numero, String deviceId, String referencia, String tipo, String descripcion,
		Map<String, String> original) {
	}

	public record Resultado(List<Fila> filas, Map<String, String> columnasReconocidas) {
	}

	private static final JsonMapper JSON = JsonMapper.builder().build();

	private final Map<String, List<String>> sinonimos;

	public LectorExcel(String configuracionJson) {
		Map<String, List<String>> conf;
		try {
			conf = JSON.readValue(configuracionJson, new TypeReference<Map<String, List<String>>>() {
			});
		}
		catch (RuntimeException e) {
			conf = Map.of("device_id", List.of("device", "device id", "device_id", "serial", "maquina"));
		}
		this.sinonimos = new LinkedHashMap<>();
		conf.forEach((campo, lista) -> sinonimos.put(campo, lista.stream().map(LectorExcel::normalizar).toList()));
	}

	public Resultado leer(InputStream in) {
		try (Workbook libro = WorkbookFactory.create(in)) {
			Sheet hoja = libro.getSheetAt(0);
			DataFormatter formato = new DataFormatter(Locale.ROOT);

			int filaEncabezado = -1;
			Map<String, Integer> columnas = Map.of();
			List<String> encabezados = List.of();
			for (int r = hoja.getFirstRowNum(); r <= Math.min(hoja.getLastRowNum(), hoja.getFirstRowNum() + 15); r++) {
				List<String> textos = textos(hoja.getRow(r), formato);
				Map<String, Integer> encontradas = reconocer(textos);
				if (encontradas.containsKey("device_id") && encontradas.size() > columnas.size()) {
					filaEncabezado = r;
					columnas = encontradas;
					encabezados = textos;
				}
			}
			if (filaEncabezado < 0) {
				throw new NegocioException("No se encontró la columna con el ID de la máquina. Revise que el Excel tenga "
					+ "un encabezado como «Device ID», «Serial» o «Máquina», o ajuste el parámetro excel_prosegur_columnas.");
			}

			Map<String, String> reconocidas = new LinkedHashMap<>();
			for (var e : columnas.entrySet()) {
				reconocidas.put(e.getKey(), encabezados.get(e.getValue()));
			}

			List<Fila> filas = new ArrayList<>();
			for (int r = filaEncabezado + 1; r <= hoja.getLastRowNum(); r++) {
				List<String> valores = textos(hoja.getRow(r), formato);
				if (valores.stream().allMatch(String::isBlank)) {
					continue;
				}
				Map<String, String> original = new LinkedHashMap<>();
				for (int c = 0; c < encabezados.size(); c++) {
					String clave = encabezados.get(c).isBlank() ? "Columna " + (c + 1) : encabezados.get(c);
					original.put(clave, c < valores.size() ? valores.get(c) : "");
				}
				filas.add(new Fila(r + 1, valor(valores, columnas.get("device_id")), valor(valores, columnas.get("referencia")),
					valor(valores, columnas.get("tipo")), valor(valores, columnas.get("descripcion")), original));
			}
			return new Resultado(filas, reconocidas);
		}
		catch (IOException | RuntimeException e) {
			if (e instanceof NegocioException ne) {
				throw ne;
			}
			throw new NegocioException("No se pudo leer el archivo. Debe ser un Excel (.xlsx o .xls).");
		}
	}

	public static String json(Map<String, String> fila) {
		return JSON.writeValueAsString(fila);
	}

	/** Reconoce cada campo en la primera columna cuyo encabezado coincide con alguno de sus sinónimos. */
	private Map<String, Integer> reconocer(List<String> textos) {
		Map<String, Integer> encontradas = new LinkedHashMap<>();
		for (var campo : sinonimos.entrySet()) {
			for (int c = 0; c < textos.size(); c++) {
				String t = normalizar(textos.get(c));
				if (!t.isEmpty() && !encontradas.containsValue(c) && campo.getValue().contains(t)) {
					encontradas.put(campo.getKey(), c);
					break;
				}
			}
		}
		return encontradas;
	}

	private static List<String> textos(Row fila, DataFormatter formato) {
		List<String> lista = new ArrayList<>();
		if (fila == null) {
			return lista;
		}
		for (int c = 0; c < fila.getLastCellNum(); c++) {
			Cell celda = fila.getCell(c);
			lista.add(celda == null ? "" : formato.formatCellValue(celda).trim());
		}
		return lista;
	}

	private static String valor(List<String> valores, Integer columna) {
		if (columna == null || columna >= valores.size()) {
			return null;
		}
		String v = valores.get(columna).trim();
		return v.isEmpty() ? null : v;
	}

	static String normalizar(String s) {
		String sinTildes = Normalizer.normalize(s == null ? "" : s, Normalizer.Form.NFD).replaceAll("\\p{M}", "");
		return sinTildes.toLowerCase(Locale.ROOT).replaceAll("[^a-z0-9]+", " ").trim();
	}
}
