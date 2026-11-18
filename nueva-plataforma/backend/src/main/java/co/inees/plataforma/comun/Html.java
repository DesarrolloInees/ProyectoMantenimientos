package co.inees.plataforma.comun;

import java.math.BigDecimal;
import java.text.NumberFormat;
import java.time.LocalDate;
import java.time.LocalTime;
import java.time.format.DateTimeFormatter;
import java.util.Collection;
import java.util.List;
import java.util.Locale;
import java.util.function.Function;

/** Ayudas para armar HTML de los PDF sin plantillas: escape, formato y tablas. */
public final class Html {

	private static final Locale CO = Locale.forLanguageTag("es-CO");
	private static final DateTimeFormatter FECHA = DateTimeFormatter.ofPattern("dd/MM/yyyy");
	private static final DateTimeFormatter FECHA_LARGA = DateTimeFormatter.ofPattern("EEEE d 'de' MMMM 'de' yyyy", CO);

	private Html() {
	}

	public static String esc(Object v) {
		if (v == null) {
			return "";
		}
		String s = v.toString();
		StringBuilder b = new StringBuilder(s.length() + 16);
		for (char c : s.toCharArray()) {
			switch (c) {
				case '&' -> b.append("&amp;");
				case '<' -> b.append("&lt;");
				case '>' -> b.append("&gt;");
				case '"' -> b.append("&quot;");
				case '\'' -> b.append("&#39;");
				default -> {
					if (c >= 0x20 || c == '\n' || c == '\t') {
						b.append(c);
					}
				}
			}
		}
		return b.toString();
	}

	/** Texto con saltos de línea conservados. */
	public static String parrafo(Object v) {
		return esc(v).replace("\n", "<br/>");
	}

	public static String fecha(LocalDate d) {
		return d == null ? "—" : d.format(FECHA);
	}

	public static String fechaLarga(LocalDate d) {
		if (d == null) {
			return "—";
		}
		String s = d.format(FECHA_LARGA);
		return Character.toUpperCase(s.charAt(0)) + s.substring(1);
	}

	public static String hora(LocalTime t) {
		return t == null ? "—" : t.toString().substring(0, 5);
	}

	public static String pesos(Number n) {
		if (n == null) {
			return "$0";
		}
		NumberFormat f = NumberFormat.getIntegerInstance(CO);
		return "$" + f.format(n instanceof BigDecimal b ? b.longValue() : Math.round(n.doubleValue()));
	}

	public static String minutos(Number min) {
		if (min == null) {
			return "—";
		}
		long m = min.longValue();
		return String.format(Locale.ROOT, "%d:%02d", m / 60, Math.abs(m % 60));
	}

	/** Tabla simple: encabezados y una función que da las celdas de cada fila (ya escapadas). */
	public static <T> String tabla(List<String> encabezados, Collection<T> filas, Function<T, List<String>> celdas) {
		StringBuilder b = new StringBuilder("<table><thead><tr>");
		for (String e : encabezados) {
			b.append("<th>").append(esc(e)).append("</th>");
		}
		b.append("</tr></thead><tbody>");
		if (filas.isEmpty()) {
			b.append("<tr><td colspan=\"").append(encabezados.size()).append("\" class=\"muted cen\">Sin registros</td></tr>");
		}
		for (T f : filas) {
			b.append("<tr>");
			for (String c : celdas.apply(f)) {
				b.append("<td>").append(c == null ? "" : c).append("</td>");
			}
			b.append("</tr>");
		}
		return b.append("</tbody></table>").toString();
	}

	/** Imagen embebida (data URI) para los PDF. */
	public static String imagen(byte[] bytes, String clase) {
		if (bytes == null || bytes.length == 0) {
			return "";
		}
		boolean png = bytes.length > 4 && (bytes[0] & 0xFF) == 0x89 && bytes[1] == 'P' && bytes[2] == 'N' && bytes[3] == 'G';
		return "<img class=\"" + esc(clase) + "\" src=\"data:image/" + (png ? "png" : "jpeg") + ";base64,"
			+ java.util.Base64.getEncoder().encodeToString(bytes) + "\"/>";
	}
}
