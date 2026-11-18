package co.inees.plataforma.reportes;

import static co.inees.plataforma.comun.Html.esc;

import java.math.BigDecimal;
import java.text.NumberFormat;
import java.time.LocalDate;
import java.time.format.TextStyle;
import java.time.temporal.IsoFields;
import java.util.ArrayList;
import java.util.Comparator;
import java.util.LinkedHashMap;
import java.util.LinkedHashSet;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.Set;
import java.util.TreeMap;

import org.springframework.stereotype.Component;

import co.inees.plataforma.comun.Html;
import co.inees.plataforma.comun.Pdf;

/**
 * PDF del reporte ejecutivo (generarReporte de PHP, versión V-11): A4 horizontal, una sección por página y las mismas
 * 17 secciones que se elegían en «Configurar reporte». Usa los datos del tablero ({@link ReporteEjecutivo}) y se
 * arma con openhtmltopdf, sin navegador en el servidor: las gráficas son barras hechas con tablas.
 */
@Component
public class ReporteEjecutivoPdf {

	public record Seccion(String clave, String titulo, String descripcion) {
	}

	/** Secciones en el orden del documento. */
	public static final List<Seccion> SECCIONES = List.of(
		new Seccion("portada", "Portada", "Resumen ejecutivo con los indicadores principales"),
		new Seccion("delegaciones", "Delegaciones", "Participación de cada delegación y técnicos activos"),
		new Seccion("tendencias", "Tendencias", "Evolución diaria de servicios, semana por semana"),
		new Seccion("mantenimiento", "Tipos de mantenimiento", "Servicios por tipo de mantenimiento y delegación"),
		new Seccion("maquinas", "Tipos de máquina", "Servicios por tipo de máquina y delegación"),
		new Seccion("estados", "Estados finales", "Estado de la máquina al terminar el servicio"),
		new Seccion("puntos_atendidos", "Cobertura de puntos", "Puntos únicos atendidos por tipo de máquina"),
		new Seccion("puntos_frecuentes", "Puntos más visitados", "Puntos con más servicios en el período"),
		new Seccion("repuestos", "Repuestos", "Origen de los repuestos y los más usados por delegación"),
		new Seccion("calificaciones", "Calificaciones", "Satisfacción del cliente por delegación"),
		new Seccion("puntos_fallidos", "Puntos fallidos", "Puntos con 2 o más servicios fallidos"),
		new Seccion("novedades", "Principales novedades", "Top 6 por delegación en servicios no fallidos"),
		new Seccion("novedades_fallidas", "Novedades de fallidos", "Top 6 por delegación en servicios fallidos"),
		new Seccion("clientes_novedades", "Clientes con más novedades", "Top 3 de clientes por delegación y sus 3 novedades principales"),
		new Seccion("tecnicos", "Técnicos", "Promedio de servicios por técnico y por tipo"),
		new Seccion("costos", "Costos de operación", "Motorizados, nómina administrativa y gastos administrativos"),
		new Seccion("balance", "Estado de resultados", "Ingresos menos egresos del período"));

	private static final Locale CO = Locale.forLanguageTag("es-CO");

	// Paleta (equivalente a los colores de Tailwind de la versión PHP)
	private static final String VERDE = "#2d7f3e", VERDE_SUAVE = "#e9f4eb", INDIGO = "#0b5591", INDIGO_CLARO = "#368aba",
		MORADO = "#0b5591", MORADO_SUAVE = "#e8f0f7", TEAL = "#0c3554", TEAL_SUAVE = "#e3ebf2", AMBAR = "#f59e0b",
		NARANJA = "#ea580c", ROJO = "#dc2626", ROSA = "#be123c", ESMERALDA = "#4ba844", AZUL = "#0b5591", PIZARRA = "#072942",
		GRIS = "#64748b";

	private static final String ESTILOS = """
		@page {
		  size: A4 landscape; margin: 9mm 10mm 13mm 10mm;
		  @bottom-left { content: "DOCUMENTO CONFIDENCIAL"; font-family: Helvetica, sans-serif; font-size: 6.5pt; font-weight: bold;
		                 color: #94a3b8; letter-spacing: 1.5pt; }
		  @bottom-center { content: "%s"; font-family: Helvetica, sans-serif; font-size: 7.5pt; font-weight: bold; color: #64748b; }
		  @bottom-right { content: "Página " counter(page) " de " counter(pages); font-family: Helvetica, sans-serif; font-size: 7.5pt;
		                  font-weight: bold; color: #475569; }
		}
		body { font-family: Helvetica, Arial, sans-serif; font-size: 8.5pt; color: #1e293b; }
		.pagina { page-break-before: always; }
		.primera { page-break-before: avoid; }
		table { width: 100%%; border-collapse: collapse; }
		td, th { padding: 0; vertical-align: top; }
		.cab { margin-bottom: 9pt; border-bottom: 1pt solid #e2e8f0; }
		.cab td { vertical-align: middle; padding-bottom: 6pt; }
		.cab .num { width: 26pt; height: 26pt; color: #fff; font-weight: bold; font-size: 11pt; text-align: center; border-radius: 6pt; }
		.cab .txt { padding-left: 8pt; }
		.cab .titulo { font-size: 14pt; font-weight: bold; color: #0f172a; }
		.cab .sub { font-size: 8pt; color: #64748b; margin-top: 1pt; }
		.cab .fecha { text-align: right; width: 180pt; }
		.pildora { display: inline-block; padding: 3pt 8pt; border-radius: 9pt; background: #f1f5f9; border: 0.75pt solid #cbd5e1;
		           font-size: 8.5pt; font-weight: bold; color: #334155; }
		.rejilla { border-collapse: separate; border-spacing: 6pt 6pt; width: 100%%; }
		.rejilla tr, .rejilla td { page-break-inside: avoid; }
		.tarjeta { border: 0.75pt solid #e2e8f0; border-radius: 6pt; padding: 7pt 8pt; background: #fff; }
		.t-cab { border-bottom: 0.75pt solid #f1f5f9; padding-bottom: 4pt; margin-bottom: 5pt; }
		.t-titulo { font-weight: bold; font-size: 8.5pt; text-transform: uppercase; color: #1e293b; }
		.insignia { float: right; font-size: 7pt; font-weight: bold; padding: 1pt 5pt; border-radius: 6pt; background: #f1f5f9; color: #475569; }
		.fila td { padding: 2.2pt 0; vertical-align: middle; }
		.etq { font-size: 7.6pt; color: #334155; }
		.val { font-weight: bold; text-align: right; font-size: 8pt; width: 34pt; }
		.pista { background: #f1f5f9; height: 5pt; border-radius: 3pt; }
		.relleno { height: 5pt; border-radius: 3pt; }
		.muted { color: #64748b; }
		.chico { font-size: 7pt; }
		.der { text-align: right; }
		.cen { text-align: center; }
		.neg { font-weight: bold; }
		.matriz { -fs-table-paginate: paginate; border: 0.75pt solid #e2e8f0; }
		.matriz th { background: #f8fafc; color: #475569; font-size: 7pt; text-transform: uppercase; padding: 4pt 3pt;
		             border-bottom: 1pt solid #cbd5e1; text-align: center; vertical-align: bottom; }
		.matriz th.izq, .matriz td.izq { text-align: left; padding-left: 5pt; }
		.matriz td { border-bottom: 0.5pt solid #f1f5f9; padding: 3pt; text-align: center; font-size: 8pt; vertical-align: middle; }
		.matriz tr { page-break-inside: avoid; }
		.matriz .tot td { background: #1e293b; color: #fff; font-weight: bold; border-bottom: none; }
		.matriz td.coltot { background: #f1f5f9; font-weight: bold; }
		.vacio { color: #cbd5e1; }
		.pct { font-size: 6.5pt; color: #64748b; }
		.aviso { border: 0.75pt dashed #cbd5e1; border-radius: 6pt; padding: 18pt; text-align: center; color: #64748b; font-size: 10pt; }
		.kpi { border-radius: 8pt; padding: 10pt 12pt; color: #fff; }
		.kpi .k-et { font-size: 7.5pt; text-transform: uppercase; letter-spacing: 1pt; font-weight: bold; }
		.kpi .k-val { font-size: 26pt; font-weight: bold; margin: 3pt 0; }
		.kpi .k-det { font-size: 8pt; }
		.chip { display: inline-block; padding: 1.5pt 5pt; margin: 1.5pt 2pt 1.5pt 0; border-radius: 5pt; font-size: 7pt;
		        background: #f1f5f9; color: #334155; border: 0.5pt solid #e2e8f0; }
		.barras td { text-align: center; vertical-align: bottom; }
		.barras .col { padding: 0 5pt; }
		.barras .v { font-size: 8pt; font-weight: bold; color: #334155; padding-bottom: 2pt; }
		.barras .n { font-size: 7.3pt; color: #334155; font-weight: bold; padding-top: 3pt; border-top: 0.75pt solid #cbd5e1; }
		.barras .p { font-size: 7pt; color: #64748b; }
		.lista { -fs-table-paginate: paginate; }
		.lista th { background: #f8fafc; color: #475569; font-size: 7pt; text-transform: uppercase; padding: 3.5pt 4pt;
		            border-bottom: 1pt solid #cbd5e1; text-align: left; }
		.lista td { padding: 3pt 4pt; border-bottom: 0.5pt solid #f1f5f9; font-size: 7.8pt; }
		.lista th.der, .lista td.der { text-align: right; }
		.lista th.cen, .lista td.cen { text-align: center; }
		.nowrap { white-space: nowrap; }
		.cuadro { display: inline-block; width: 7pt; height: 7pt; border-radius: 1.5pt; margin-right: 2pt; }
		.lista .sub td { background: #f8fafc; font-weight: bold; border-top: 0.75pt solid #cbd5e1; }
		.semaforo { display: inline-block; min-width: 20pt; padding: 1.5pt 4pt; border-radius: 4pt; color: #fff; font-weight: bold;
		            text-align: center; font-size: 7.6pt; }
		.portada-titulo { font-size: 40pt; font-weight: bold; color: #2d7f3e; line-height: 0.95; letter-spacing: -1pt; }
		.portada-titulo span { color: #072942; }
		.logo { height: 48pt; }
		""";

	private final ReporteEjecutivo ejecutivo;

	public ReporteEjecutivoPdf(ReporteEjecutivo ejecutivo) {
		this.ejecutivo = ejecutivo;
	}

	/**
	 * @param secciones claves de {@link #SECCIONES}; vacío = todas
	 * @param minVisitas mínimo de servicios para que un punto aparezca en «Puntos más visitados»
	 */
	public byte[] generar(LocalDate desde, LocalDate hasta, Set<String> secciones, int minVisitas) {
		return Pdf.generar(html(desde, hasta, secciones, minVisitas));
	}

	String html(LocalDate desde, LocalDate hasta, Set<String> pedidas, int minVisitas) {
		var t = ejecutivo.generar(desde, hasta);
		var c = new Contexto(desde, hasta, t, Math.max(1, minVisitas));
		StringBuilder b = new StringBuilder();
		int numero = 0;
		for (Seccion s : SECCIONES) {
			if (!pedidas.isEmpty() && !pedidas.contains(s.clave())) {
				continue;
			}
			numero++;
			b.append("<div class=\"pagina").append(numero == 1 ? " primera" : "").append("\">");
			switch (s.clave()) {
				case "portada" -> portada(b, c);
				case "delegaciones" -> delegaciones(b, c, numero);
				case "tendencias" -> tendencias(b, c, numero);
				case "mantenimiento" -> mantenimiento(b, c, numero);
				case "maquinas" -> maquinas(b, c, numero);
				case "estados" -> estados(b, c, numero);
				case "puntos_atendidos" -> puntosAtendidos(b, c, numero);
				case "puntos_frecuentes" -> puntosFrecuentes(b, c, numero);
				case "repuestos" -> repuestos(b, c, numero);
				case "calificaciones" -> calificaciones(b, c, numero);
				case "puntos_fallidos" -> puntosFallidos(b, c, numero);
				case "novedades" -> novedades(b, c, numero);
				case "novedades_fallidas" -> novedadesFallidas(b, c, numero);
				case "clientes_novedades" -> clientesNovedades(b, c, numero);
				case "tecnicos" -> tecnicos(b, c, numero);
				case "costos" -> costos(b, c, numero);
				case "balance" -> balance(b, c, numero);
				default -> {
				}
			}
			b.append("</div>");
		}
		if (numero == 0) {
			b.append("<div class=\"aviso\">No se eligió ninguna sección.</div>");
		}
		String rango = Html.fecha(desde) + " - " + Html.fecha(hasta);
		return "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"/><title>Reporte ejecutivo " + esc(rango) + "</title><style>"
			+ ESTILOS.formatted(rango) + "</style></head><body>" + b + "</body></html>";
	}

	/** Datos del tablero y del período, compartidos por todas las secciones. */
	private record Contexto(LocalDate desde, LocalDate hasta, ReporteEjecutivo.Tablero t, int minVisitas) {

		List<Map<String, Object>> s(String clave) {
			return t.secciones().getOrDefault(clave, List.of());
		}

		ReporteEjecutivo.Kpis k() {
			return t.kpis();
		}
	}

	// =========================================================================================== portada
	private void portada(StringBuilder b, Contexto c) {
		var k = c.k();
		var dels = c.s("delegaciones");
		long total = k.totalServicios();
		b.append("<table><tr><td style=\"width:55%\">").append(Html.imagen(logo(), "logo"))
			.append("<div class=\"portada-titulo\" style=\"margin-top:14pt\">REPORTE<br/><span>EJECUTIVO</span></div>")
			.append("<div style=\"margin-top:10pt\"><span class=\"pildora\">").append(Html.fecha(c.desde())).append(" — ")
			.append(Html.fecha(c.hasta())).append("</span></div></td><td style=\"vertical-align:bottom\">")
			.append("<div class=\"muted\" style=\"font-size:9pt\">Servicios de mantenimiento a máquinas Prosegur realizados por INEES en el período.</div>")
			.append("</td></tr></table>");

		// Tres indicadores grandes
		double cumplimiento = k.cumplimientoMeta();
		String colorMeta = cumplimiento >= 100 ? VERDE : cumplimiento >= 85 ? AMBAR : ROJO;
		b.append("<table class=\"rejilla\" style=\"margin-top:12pt\"><tr>")
			.append(kpi("Servicios esperados (meta)", entero(k.meta().esperados()), entero(k.meta().diasLaborables()) + " días laborables · "
				+ entero(k.meta().tecnicosDias()) + " técnico-días", PIZARRA))
			.append(kpi("Servicios realizados", entero(total), entero(k.fallidos()) + " fallidos · " + entero(k.conNovedad()) + " con novedad",
				VERDE))
			.append(kpi("Cumplimiento de meta", decimal(cumplimiento) + "%", "Realizados / esperados del período", colorMeta))
			.append("</tr></table>");

		// Cuatro tarjetas por delegación
		List<String> tarjetas = new ArrayList<>();
		StringBuilder t1 = new StringBuilder(cabTarjeta("Total servicios", entero(total)));
		StringBuilder t2 = new StringBuilder(cabTarjeta("Promedio diario por técnico", null));
		StringBuilder t3 = new StringBuilder(cabTarjeta("Servicios con novedad", entero(k.conNovedad())));
		StringBuilder t4 = new StringBuilder(cabTarjeta("Delegaciones", entero(dels.size())));
		double maxPromedio = dels.stream().mapToDouble(this::promedioDiario).max().orElse(1);
		long maxNov = dels.stream().mapToLong(d -> lng(d.get("novedades"))).max().orElse(1);
		t1.append("<table>");
		t2.append("<table>");
		t3.append("<table>");
		for (var d : dels) {
			long serv = lng(d.get("servicios"));
			String nombre = esc(d.get("delegacion"));
			t1.append("<tr class=\"fila\"><td class=\"etq\">").append(nombre).append("</td><td class=\"val\">").append(entero(serv))
				.append("</td><td class=\"val muted\">").append(decimal(pct(serv, total))).append("%</td></tr>");
			double prom = promedioDiario(d);
			t2.append(filaBarra(nombre, decimal(prom), prom * 100 / Math.max(maxPromedio, 0.01), INDIGO));
			long nov = lng(d.get("novedades"));
			t3.append(filaBarra(nombre, entero(nov), nov * 100.0 / Math.max(maxNov, 1), AMBAR));
			t4.append("<span class=\"chip\">").append(nombre).append("</span>");
		}
		t1.append("</table>");
		t2.append("</table>");
		t3.append("</table>");
		tarjetas.add(t1.toString());
		tarjetas.add(t2.toString());
		tarjetas.add(t3.toString());
		tarjetas.add(t4.toString());
		b.append("<div style=\"margin-top:8pt\">").append(rejilla(tarjetas, 4)).append("</div>");
	}

	private String kpi(String etiqueta, String valor, String detalle, String color) {
		return "<td style=\"width:33.3%\"><div class=\"kpi\" style=\"background:" + color + "\"><div class=\"k-et\">" + esc(etiqueta)
			+ "</div><div class=\"k-val\">" + valor + "</div><div class=\"k-det\">" + esc(detalle) + "</div></div></td>";
	}

	private double promedioDiario(Map<String, Object> d) {
		long dias = Math.max(1, lng(d.get("dias_efectivos")));
		return lng(d.get("servicios")) / (double) dias;
	}

	// =========================================================================================== delegaciones
	private void delegaciones(StringBuilder b, Contexto c, int n) {
		cabecera(b, n, INDIGO, "Participación por delegación", "Volumen de servicios, porcentaje del total y técnicos activos", c);
		var dels = c.s("delegaciones");
		long total = c.k().totalServicios();
		if (dels.isEmpty()) {
			sinDatos(b, "No hay servicios en el período.");
			return;
		}
		long max = dels.stream().mapToLong(d -> lng(d.get("servicios"))).max().orElse(1);
		List<String[]> barras = new ArrayList<>();
		for (var d : dels) {
			long serv = lng(d.get("servicios"));
			double p = pct(serv, total);
			barras.add(new String[] { entero(serv), esc(d.get("delegacion")),
				decimal(p) + "% · " + entero(lng(d.get("tecnicos"))) + " téc.", String.valueOf(serv * 100.0 / max),
				p > 20 ? INDIGO : INDIGO_CLARO });
		}
		b.append("<div class=\"tarjeta\" style=\"padding:12pt\">").append(barrasVerticales(barras, 165))
			.append("<table style=\"margin-top:8pt\"><tr><td class=\"chico muted\"><span style=\"background:").append(INDIGO)
			.append("\" class=\"cuadro\"></span> Más del 20% de participación · «téc.» = técnicos activos en la delegación</td>")
			.append("<td class=\"der\"><span class=\"pildora\">Total global: ").append(entero(total)).append("</span></td></tr></table></div>");

		// Detalle en tabla
		b.append("<table class=\"lista\" style=\"margin-top:10pt\"><thead><tr><th>Delegación</th><th class=\"der\">Servicios</th>")
			.append("<th class=\"der\">% del total</th><th class=\"der\">Técnicos</th><th class=\"der\">Días efectivos</th>")
			.append("<th class=\"der\">Promedio diario por técnico</th><th class=\"der\">Con novedad</th></tr></thead><tbody>");
		for (var d : dels) {
			long serv = lng(d.get("servicios"));
			b.append("<tr><td class=\"neg\">").append(esc(d.get("delegacion"))).append("</td><td class=\"der\">").append(entero(serv))
				.append("</td><td class=\"der\">").append(decimal(pct(serv, total))).append("%</td><td class=\"der\">")
				.append(entero(lng(d.get("tecnicos")))).append("</td><td class=\"der\">").append(entero(lng(d.get("dias_efectivos"))))
				.append("</td><td class=\"der\">").append(decimal(promedioDiario(d))).append("</td><td class=\"der\">")
				.append(entero(lng(d.get("novedades")))).append("</td></tr>");
		}
		b.append("</tbody></table>");
	}

	// =========================================================================================== tendencias
	private void tendencias(StringBuilder b, Contexto c, int n) {
		cabecera(b, n, AZUL, "Evolución diaria de servicios", "Detalle por semanas operativas del período", c);
		var dias = c.s("serviciosPorDia");
		if (dias.isEmpty()) {
			sinDatos(b, "No hay servicios en el período.");
			return;
		}
		long max = dias.stream().mapToLong(d -> lng(d.get("total"))).max().orElse(1);
		Map<String, List<Map<String, Object>>> semanas = new LinkedHashMap<>();
		for (var d : dias) {
			LocalDate f = fecha(d.get("fecha"));
			semanas.computeIfAbsent("Semana " + f.get(IsoFields.WEEK_OF_WEEK_BASED_YEAR), x -> new ArrayList<>()).add(d);
		}
		List<String> tarjetas = new ArrayList<>();
		semanas.forEach((semana, lista) -> {
			long totalSemana = lista.stream().mapToLong(d -> lng(d.get("total"))).sum();
			StringBuilder t = new StringBuilder(cabTarjeta(semana, entero(totalSemana) + " serv.")).append("<table>");
			for (var d : lista) {
				LocalDate f = fecha(d.get("fecha"));
				long tot = lng(d.get("total"));
				String dia = f.getDayOfWeek().getDisplayName(TextStyle.SHORT, CO);
				t.append("<tr class=\"fila\"><td class=\"etq\" style=\"width:40pt\">").append(esc(capital(dia.replace(".", ""))))
					.append(" ").append(String.format("%02d", f.getDayOfMonth())).append("</td><td style=\"width:55%\">")
					.append(barra(tot * 100.0 / max, f.getDayOfWeek().getValue() == 6 ? INDIGO_CLARO : AZUL))
					.append("</td><td class=\"val\">").append(entero(tot)).append("</td><td class=\"val muted chico\" style=\"width:38pt\">")
					.append(entero(lng(d.get("tecnicos")))).append(" téc.</td></tr>");
			}
			tarjetas.add(t.append("</table>").toString());
		});
		b.append(rejilla(tarjetas, 3));
		b.append("<p class=\"chico muted\" style=\"margin-top:8pt\">«téc.» = técnicos que trabajaron ese día. Los sábados se muestran en tono claro.</p>");
	}

	// =========================================================================================== mantenimiento
	private void mantenimiento(StringBuilder b, Contexto c, int n) {
		cabecera(b, n, MORADO, "Servicios por tipo de mantenimiento", "Cantidades y porcentaje de cada tipo dentro de la delegación", c);
		var datos = c.s("delegacionPorTipo");
		if (datos.isEmpty()) {
			sinDatos(b, "No hay servicios en el período.");
			return;
		}
		List<String> columnas = c.s("tipos").stream().map(r -> String.valueOf(r.get("tipo"))).toList();
		b.append(matriz("Delegación", columnas, datos, true, MORADO_SUAVE, MORADO));
	}

	// =========================================================================================== máquinas
	private void maquinas(StringBuilder b, Contexto c, int n) {
		cabecera(b, n, TEAL, "Servicios por tipo de máquina", "Servicios realizados en cada delegación según el tipo de máquina", c);
		var datos = ejecutivo.filas("""
			SELECT d.nombre_delegacion AS fila, tmq.nombre_tipo_maquina AS columna, COUNT(*) AS valor
			FROM ordenes_servicio o JOIN punto p ON p.id_punto = o.id_punto JOIN delegacion d ON d.id_delegacion = p.id_delegacion
			JOIN maquina m ON m.id_maquina = o.id_maquina JOIN tipo_maquina tmq ON tmq.id_tipo_maquina = m.id_tipo_maquina
			WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h GROUP BY 1, 2""", c.desde(), c.hasta());
		if (datos.isEmpty()) {
			sinDatos(b, "No hay servicios en el período.");
			return;
		}
		b.append(matriz("Delegación", columnas(datos), datos, false, TEAL_SUAVE, TEAL));
	}

	// =========================================================================================== estados
	private void estados(StringBuilder b, Contexto c, int n) {
		cabecera(b, n, ESMERALDA, "Estado final de la máquina", "Resultado operativo tras la intervención técnica", c);
		var estados = c.s("estadosMaquina");
		if (estados.isEmpty()) {
			sinDatos(b, "No hay estados registrados en el período.");
			return;
		}
		long total = estados.stream().mapToLong(e -> lng(e.get("total"))).sum();
		b.append("<div class=\"tarjeta\" style=\"padding:14pt 18pt\"><table>");
		for (var e : estados) {
			String nombre = String.valueOf(e.get("estado"));
			String m = nombre.toLowerCase(CO);
			String color = m.contains("operativ") || m.contains("funcional") || m.contains("buen") ? ESMERALDA
				: m.contains("limit") || m.contains("parcial") || m.contains("observaci") ? AMBAR : ROJO;
			long cant = lng(e.get("total"));
			double p = pct(cant, total);
			b.append("<tr><td style=\"padding:6pt 0 2pt 0\"><span class=\"neg\" style=\"font-size:10pt\">").append(esc(nombre))
				.append("</span></td><td class=\"der\" style=\"padding:6pt 0 2pt 0\"><span class=\"neg\" style=\"color:").append(color)
				.append(";font-size:10pt\">").append(entero(cant)).append(" serv. · ").append(decimal(p)).append("%</span></td></tr>")
				.append("<tr><td colspan=\"2\"><div class=\"pista\" style=\"height:10pt;border-radius:5pt\"><div class=\"relleno\" style=\"height:10pt;border-radius:5pt;width:")
				.append(ancho(p)).append("%;background:").append(color).append("\"></div></div></td></tr>");
		}
		b.append("</table><div class=\"der\" style=\"margin-top:10pt\"><span class=\"pildora\">Total: ").append(entero(total))
			.append(" servicios</span></div></div>");
	}

	// =========================================================================================== puntos atendidos
	private void puntosAtendidos(StringBuilder b, Contexto c, int n) {
		cabecera(b, n, VERDE, "Puntos atendidos", "Cobertura: puntos únicos atendidos por delegación y tipo de máquina", c);
		var datos = c.s("delegacionPorTipoMaquina");
		long puntos = datos.stream().mapToLong(r -> lng(r.get("valor"))).sum();
		b.append("<table class=\"rejilla\" style=\"margin-bottom:6pt\"><tr>")
			.append("<td style=\"width:50%\"><div class=\"tarjeta\"><span class=\"muted chico neg\">SERVICIOS ATENDIDOS</span><div style=\"font-size:20pt;font-weight:bold;color:")
			.append(PIZARRA).append("\">").append(entero(c.k().totalServicios())).append("</div></div></td>")
			.append("<td style=\"width:50%\"><div class=\"tarjeta\"><span class=\"muted chico neg\">PUNTOS ATENDIDOS</span><div style=\"font-size:20pt;font-weight:bold;color:")
			.append(VERDE).append("\">").append(entero(puntos)).append("</div></div></td></tr></table>");
		if (datos.isEmpty()) {
			sinDatos(b, "No hay servicios en el período.");
			return;
		}
		b.append(matriz("Delegación", columnas(datos), datos, false, VERDE_SUAVE, VERDE));
	}

	// =========================================================================================== puntos más visitados
	private void puntosFrecuentes(StringBuilder b, Contexto c, int n) {
		cabecera(b, n, AMBAR, "Puntos más visitados", "Puntos con " + c.minVisitas() + " o más servicios · top 5 por delegación", c);
		var filas = ejecutivo.filas("""
			SELECT COALESCE(d.nombre_delegacion, 'SIN DELEGACIÓN') AS delegacion, p.nombre_punto AS punto,
			       (SELECT GROUP_CONCAT(DISTINCT tm.nombre_tipo_maquina SEPARATOR ', ') FROM maquina m
			          JOIN tipo_maquina tm ON tm.id_tipo_maquina = m.id_tipo_maquina WHERE m.id_punto = p.id_punto AND m.estado = 1) AS tipos,
			       COUNT(*) AS visitas
			FROM ordenes_servicio o JOIN punto p ON p.id_punto = o.id_punto LEFT JOIN delegacion d ON d.id_delegacion = p.id_delegacion
			WHERE o.estado = 1 AND o.fecha_visita BETWEEN :d AND :h
			GROUP BY d.nombre_delegacion, p.id_punto, p.nombre_punto HAVING visitas >= """ + c.minVisitas() + """

			ORDER BY delegacion, visitas DESC, p.nombre_punto""", c.desde(), c.hasta());
		if (filas.isEmpty()) {
			sinDatos(b, "Ningún punto tuvo " + c.minVisitas() + " o más servicios en el período.");
			return;
		}
		List<String> tarjetas = new ArrayList<>();
		agrupar(filas, "delegacion").forEach((del, puntos) -> {
			long max = Math.max(1, lng(puntos.get(0).get("visitas")));
			StringBuilder t = new StringBuilder(cabTarjeta(String.valueOf(del), entero(puntos.size()) + " pts")).append("<table>");
			for (var p : puntos.subList(0, Math.min(5, puntos.size()))) {
				long v = lng(p.get("visitas"));
				t.append("<tr><td class=\"etq neg\" style=\"padding-top:3pt\">").append(esc(p.get("punto")))
					.append("</td><td class=\"val\" rowspan=\"2\" style=\"vertical-align:middle\">").append(entero(v)).append("</td></tr>")
					.append("<tr><td style=\"padding-bottom:3pt\"><div class=\"chico muted\">").append(esc(p.get("tipos") == null ? "S/D" : p.get("tipos")))
					.append("</div>").append(barra(v * 100.0 / max, AMBAR)).append("</td></tr>");
			}
			if (puntos.size() > 5) {
				t.append("<tr><td colspan=\"2\" class=\"chico muted cen\" style=\"padding-top:3pt\">+").append(puntos.size() - 5)
					.append(" puntos más</td></tr>");
			}
			tarjetas.add(t.append("</table>").toString());
		});
		b.append(rejilla(tarjetas, 3));
	}

	// =========================================================================================== repuestos
	private void repuestos(StringBuilder b, Contexto c, int n) {
		cabecera(b, n, NARANJA, "Repuestos usados en servicios", "Origen de los repuestos y los más usados por delegación", c);
		long inees = 0, prosegur = 0;
		for (var r : c.s("repuestosOrigen")) {
			if (String.valueOf(r.get("origen")).toUpperCase(CO).contains("INEES")) {
				inees += lng(r.get("total"));
			}
			else {
				prosegur += lng(r.get("total"));
			}
		}
		long total = inees + prosegur;
		double pInees = pct(inees, total);
		b.append("<div class=\"tarjeta\" style=\"margin-bottom:8pt\"><table><tr><td style=\"width:120pt;vertical-align:middle\">")
			.append("<div class=\"t-titulo\" style=\"color:").append(NARANJA).append("\">Origen de repuestos</div>")
			.append("<div style=\"font-size:18pt;font-weight:bold\">").append(entero(total)).append("</div><div class=\"chico muted\">unidades usadas</div></td>")
			.append("<td style=\"vertical-align:middle\">");
		if (total > 0) {
			b.append("<table style=\"height:16pt\"><tr>");
			if (inees > 0) {
				b.append("<td style=\"width:").append(ancho(pInees)).append("%;background:").append(NARANJA)
					.append(";color:#fff;font-weight:bold;text-align:center;vertical-align:middle;font-size:8pt\">")
					.append(pInees >= 8 ? decimal(pInees) + "%" : "").append("</td>");
			}
			if (prosegur > 0) {
				b.append("<td style=\"background:").append(PIZARRA).append(";color:#fff;font-weight:bold;text-align:center;vertical-align:middle;font-size:8pt\">")
					.append(100 - pInees >= 8 ? decimal(100 - pInees) + "%" : "").append("</td>");
			}
			b.append("</tr></table>");
		}
		b.append("<div class=\"chico\" style=\"margin-top:4pt\"><span class=\"cuadro\" style=\"background:").append(NARANJA).append("\"></span>INEES ")
			.append(decimal(pInees)).append("% (").append(entero(inees)).append(")&#160;&#160;&#160;<span class=\"cuadro\" style=\"background:").append(PIZARRA)
			.append("\"></span>Prosegur ").append(decimal(total == 0 ? 0 : 100 - pInees)).append("% (").append(entero(prosegur))
			.append(")</div></td></tr></table></div>");
		var porDel = c.s("repuestosPorDelegacion");
		if (porDel.isEmpty()) {
			sinDatos(b, "No se usaron repuestos de INEES en el período.");
			return;
		}
		List<String> tarjetas = new ArrayList<>();
		agrupar(porDel, "delegacion").forEach((del, items) -> {
			long totalDel = items.stream().mapToLong(r -> lng(r.get("cantidad"))).sum();
			StringBuilder t = new StringBuilder(cabTarjeta(String.valueOf(del), "Total: " + entero(totalDel))).append("<table>");
			for (var r : items.subList(0, Math.min(5, items.size()))) {
				t.append("<tr class=\"fila\"><td class=\"etq\">").append(esc(r.get("repuesto"))).append("</td><td class=\"val\">")
					.append(entero(lng(r.get("cantidad")))).append("</td></tr>");
			}
			tarjetas.add(t.append("</table>").toString());
		});
		b.append("<div class=\"t-titulo\" style=\"margin:4pt 0 2pt 0\">Repuestos INEES más usados por delegación</div>").append(rejilla(tarjetas, 4));
	}

	// =========================================================================================== calificaciones
	private void calificaciones(StringBuilder b, Contexto c, int n) {
		cabecera(b, n, AMBAR, "Calidad de atención", "Satisfacción del cliente por delegación", c);
		var datos = c.s("calificaciones");
		if (datos.isEmpty()) {
			sinDatos(b, "No hay calificaciones registradas en el período.");
			return;
		}
		List<String> tarjetas = new ArrayList<>();
		agrupar(datos, "fila").forEach((del, items) -> {
			long total = items.stream().mapToLong(r -> lng(r.get("valor"))).sum();
			List<Map<String, Object>> orden = new ArrayList<>(items);
			orden.sort(Comparator.comparingLong((Map<String, Object> r) -> lng(r.get("valor"))).reversed());
			StringBuilder t = new StringBuilder(cabTarjeta(String.valueOf(del), "Total: " + entero(total))).append("<table>");
			for (var r : orden) {
				String nombre = String.valueOf(r.get("columna"));
				String m = nombre.toLowerCase(CO);
				String color = m.contains("mal") || m.contains("pésim") || m.contains("pesim") ? ROJO : m.contains("excelente") ? ESMERALDA : AMBAR;
				long v = lng(r.get("valor"));
				double p = pct(v, total);
				t.append("<tr><td class=\"etq\" style=\"padding-top:3pt\">").append(esc(nombre)).append(" <span class=\"muted\">(")
					.append(entero(v)).append(")</span></td><td class=\"val\" style=\"padding-top:3pt;color:").append(color).append("\">")
					.append(decimal(p)).append("%</td></tr><tr><td colspan=\"2\" style=\"padding-bottom:2pt\">").append(barra(p, color))
					.append("</td></tr>");
			}
			tarjetas.add(t.append("</table>").toString());
		});
		b.append(rejilla(tarjetas, 3));
	}

	// =========================================================================================== puntos fallidos
	private void puntosFallidos(StringBuilder b, Contexto c, int n) {
		cabecera(b, n, ROSA, "Puntos fallidos", "Puntos con 2 o más visitas fallidas · top 5 por delegación", c);
		var datos = c.s("puntosConFallidos");
		if (datos.isEmpty()) {
			b.append("<div class=\"aviso\" style=\"color:").append(VERDE).append("\">Excelente gestión: ningún punto tiene 2 o más servicios fallidos en este período.</div>");
			return;
		}
		List<String> tarjetas = new ArrayList<>();
		new TreeMap<>(agrupar(datos, "delegacion")).forEach((del, items) -> {
			long total = items.stream().mapToLong(r -> lng(r.get("fallidos"))).sum();
			StringBuilder t = new StringBuilder(cabTarjeta(String.valueOf(del), "Tot: " + entero(total))).append("<table>");
			for (var r : items.subList(0, Math.min(5, items.size()))) {
				long v = lng(r.get("fallidos"));
				t.append("<tr><td class=\"etq neg\" style=\"padding-top:3pt\">").append(esc(r.get("punto"))).append("</td><td class=\"val\" style=\"color:")
					.append(ROSA).append(";padding-top:3pt\">").append(entero(v)).append("</td></tr><tr><td colspan=\"2\" style=\"padding-bottom:2pt\">")
					.append(barra(Math.min(v / 5.0 * 100, 100), ROSA)).append("</td></tr>");
			}
			tarjetas.add(t.append("</table>").toString());
		});
		b.append(rejilla(tarjetas, 3));
	}

	// =========================================================================================== novedades
	private void novedades(StringBuilder b, Contexto c, int n) {
		cabecera(b, n, INDIGO, "Principales novedades por delegación", "Top 6 de novedades en servicios no fallidos", c);
		var datos = ReporteEjecutivo.top(ejecutivo.filas(ReporteEjecutivo.novedades(false), c.desde(), c.hasta()), "delegacion", 6);
		if (datos.isEmpty()) {
			sinDatos(b, "No se registraron novedades en servicios no fallidos durante el período.");
			return;
		}
		List<String> tarjetas = new ArrayList<>();
		new TreeMap<>(agrupar(datos, "delegacion")).forEach((del, items) -> {
			long total = items.stream().mapToLong(r -> lng(r.get("total"))).sum();
			long max = items.stream().mapToLong(r -> lng(r.get("total"))).max().orElse(1);
			StringBuilder t = new StringBuilder(cabTarjeta(String.valueOf(del), "Tot: " + entero(total))).append("<table>");
			for (var r : items) {
				long v = lng(r.get("total"));
				t.append("<tr><td class=\"etq\" style=\"padding-top:3pt\">").append(esc(r.get("novedad"))).append("</td><td class=\"val\" style=\"padding-top:3pt;color:")
					.append(INDIGO).append("\">").append(entero(v)).append("</td></tr><tr><td colspan=\"2\" style=\"padding-bottom:2pt\">")
					.append(barra(v * 100.0 / Math.max(1, max), INDIGO_CLARO)).append("</td></tr>");
			}
			tarjetas.add(t.append("</table>").toString());
		});
		b.append(rejilla(tarjetas, 3));
	}

	private void novedadesFallidas(StringBuilder b, Contexto c, int n) {
		cabecera(b, n, ROSA, "Novedades de servicios fallidos", "Top 6 de novedades por delegación en servicios fallidos", c);
		var datos = c.s("fallidosPorDelegacion");
		if (datos.isEmpty()) {
			sinDatos(b, "No se registraron novedades en servicios fallidos durante el período.");
			return;
		}
		b.append("<table class=\"lista\"><thead><tr><th style=\"width:110pt\">Delegación</th><th>Principales novedades (top 6)</th>")
			.append("<th class=\"der\" style=\"width:50pt\">Total</th></tr></thead><tbody>");
		new TreeMap<>(agrupar(datos, "delegacion")).forEach((del, items) -> {
			long total = items.stream().mapToLong(r -> lng(r.get("total"))).sum();
			b.append("<tr style=\"page-break-inside:avoid\"><td class=\"neg\" style=\"vertical-align:middle\">").append(esc(del)).append("</td><td>");
			for (var r : items) {
				b.append("<span class=\"chip\">").append(esc(r.get("novedad"))).append(" <b style=\"color:").append(ROSA).append("\">")
					.append(entero(lng(r.get("total")))).append("</b></span>");
			}
			b.append("</td><td class=\"der neg\" style=\"vertical-align:middle;color:").append(ROSA).append("\">").append(entero(total)).append("</td></tr>");
		});
		b.append("</tbody></table>");
	}

	// =========================================================================================== clientes con novedades
	private void clientesNovedades(StringBuilder b, Contexto c, int n) {
		cabecera(b, n, AMBAR, "Clientes con más novedades por delegación", "Top 3 de clientes por delegación y sus 3 novedades más frecuentes", c);
		var datos = c.s("clientesConNovedades");
		if (datos.isEmpty()) {
			sinDatos(b, "No se registraron novedades por cliente durante el período.");
			return;
		}
		List<String> tarjetas = new ArrayList<>();
		new TreeMap<>(agrupar(datos, "delegacion")).forEach((del, clientes) -> {
			long total = clientes.stream().mapToLong(r -> lng(r.get("total"))).sum();
			StringBuilder t = new StringBuilder(cabTarjeta(String.valueOf(del), "Tot: " + entero(total)));
			int i = 0;
			for (var cl : clientes) {
				i++;
				t.append("<div style=\"padding:3pt 0;").append(i > 1 ? "border-top:0.5pt solid #f1f5f9;" : "").append("\"><table><tr><td class=\"etq neg\">")
					.append(i).append(". ").append(esc(cl.get("cliente"))).append("</td><td class=\"val\" style=\"color:").append(NARANJA).append("\">")
					.append(entero(lng(cl.get("total")))).append("</td></tr></table><div>");
				String nov = cl.get("novedades") == null ? "" : cl.get("novedades").toString();
				int k = 0;
				for (String chip : nov.split("; ")) {
					if (chip.isBlank() || ++k > 3) {
						continue;
					}
					t.append("<span class=\"chip\">").append(esc(chip)).append("</span>");
				}
				t.append("</div></div>");
			}
			tarjetas.add(t.toString());
		});
		b.append(rejilla(tarjetas, 3));
	}

	// =========================================================================================== técnicos
	private void tecnicos(StringBuilder b, Contexto c, int n) {
		cabecera(b, n, VERDE, "Promedio de servicios por técnico", "Servicios por tipo, media diaria de lunes a viernes y de sábado", c);
		var prod = c.s("productividad");
		if (prod.isEmpty()) {
			sinDatos(b, "No hay servicios en el período.");
			return;
		}
		List<String> tipos = c.s("tipos").stream().map(r -> String.valueOf(r.get("tipo"))).toList();
		Map<String, Map<String, Long>> porTipo = new LinkedHashMap<>();
		for (var r : c.s("tecnicoPorTipo")) {
			porTipo.computeIfAbsent(String.valueOf(r.get("fila")), x -> new LinkedHashMap<>()).put(String.valueOf(r.get("columna")), lng(r.get("valor")));
		}
		record Tec(String nombre, double mediaLv, double mediaSab, long total) {
		}
		List<Tec> tecs = new ArrayList<>();
		for (var p : prod) {
			long dLv = lng(p.get("dias_lv")), dSab = lng(p.get("dias_sab"));
			tecs.add(new Tec(String.valueOf(p.get("tecnico")), dLv > 0 ? redondear1(lng(p.get("servicios_lv")) / (double) dLv) : 0,
				dSab > 0 ? redondear1(lng(p.get("servicios_sab")) / (double) dSab) : 0, lng(p.get("total"))));
		}
		tecs.sort(Comparator.comparingDouble(Tec::mediaLv).reversed().thenComparing(Comparator.comparingLong(Tec::total).reversed()));
		b.append("<table class=\"matriz\"><thead><tr><th class=\"izq\">Técnico</th>");
		tipos.forEach(tp -> b.append("<th>").append(esc(tp)).append("</th>"));
		b.append("<th class=\"nowrap\">Media L-V</th><th class=\"nowrap\">Media sáb.</th><th>Total</th></tr></thead><tbody>");
		for (Tec t : tecs) {
			b.append("<tr><td class=\"izq neg\">").append(esc(t.nombre())).append("</td>");
			for (String tp : tipos) {
				long v = porTipo.getOrDefault(t.nombre(), Map.of()).getOrDefault(tp, 0L);
				b.append(v == 0 ? "<td class=\"vacio\">-</td>" : "<td class=\"neg\">" + entero(v) + "</td>");
			}
			String cLv = t.mediaLv() > 6 ? ESMERALDA : t.mediaLv() >= 5 ? "#eab308" : t.mediaLv() >= 4 ? "#fb923c" : ROJO;
			String cSab = t.mediaSab() > 3 ? ESMERALDA : t.mediaSab() >= 2.5 ? "#eab308" : t.mediaSab() >= 2 ? "#fb923c" : ROJO;
			b.append("<td><span class=\"semaforo\" style=\"background:").append(cLv).append("\">").append(decimal(t.mediaLv())).append("</span></td>")
				.append("<td><span class=\"semaforo\" style=\"background:").append(t.mediaSab() == 0 ? "#cbd5e1" : cSab).append("\">")
				.append(decimal(t.mediaSab())).append("</span></td><td class=\"coltot\">").append(entero(t.total())).append("</td></tr>");
		}
		b.append("</tbody></table><p class=\"chico muted\" style=\"margin-top:6pt\">Semáforo L-V: verde más de 6 · amarillo 5 a 6 · naranja 4 a 5 · rojo menos de 4. ")
			.append("Sábado: verde más de 3 · amarillo 2,5 a 3 · naranja 2 a 2,5 · rojo menos de 2.</p>");
	}

	// =========================================================================================== costos
	private record Costos(List<Map<String, Object>> motorizados, List<Map<String, Object>> admin, List<Map<String, Object>> gastos,
		BigDecimal totalMotorizados, BigDecimal totalAdmin, BigDecimal totalGastos) {

		BigDecimal total() {
			return totalMotorizados.add(totalAdmin).add(totalGastos);
		}
	}

	private Costos costos(Contexto c) {
		List<Map<String, Object>> moto = new ArrayList<>(), admin = new ArrayList<>();
		BigDecimal tm = BigDecimal.ZERO, ta = BigDecimal.ZERO, tg = BigDecimal.ZERO;
		for (var r : c.s("costosOperativos")) {
			BigDecimal v = dec(r.get("subtotal"));
			if (String.valueOf(r.get("rol")).toLowerCase(CO).contains("técnico")) {
				moto.add(r);
				tm = tm.add(v);
			}
			else {
				admin.add(r);
				ta = ta.add(v);
			}
		}
		for (var g : c.s("gastosGenerales")) {
			tg = tg.add(dec(g.get("valor")));
		}
		return new Costos(moto, admin, c.s("gastosGenerales"), tm, ta, tg);
	}

	private void costos(StringBuilder b, Contexto c, int n) {
		cabecera(b, n, PIZARRA, "Reporte de costos operativos", "Motorizados, nómina administrativa y gastos administrativos de los meses del período", c);
		Costos k = costos(c);
		BigDecimal total = k.total();
		b.append("<table class=\"rejilla\"><tr>")
			.append(tarjetaValor("Gastos motorizados", Html.pesos(k.totalMotorizados()), AZUL))
			.append(tarjetaValor("Nómina administrativa", Html.pesos(k.totalAdmin()), MORADO))
			.append(tarjetaValor("Gastos administrativos", Html.pesos(k.totalGastos()), NARANJA)).append("</tr></table>");
		if (total.signum() > 0) {
			b.append("<table style=\"height:14pt;margin-top:6pt\"><tr>");
			segmento(b, k.totalMotorizados(), total, AZUL);
			segmento(b, k.totalAdmin(), total, MORADO);
			segmento(b, k.totalGastos(), total, NARANJA);
			b.append("</tr></table>");
		}
		b.append("<div class=\"chico\" style=\"margin:3pt 0 8pt 0\"><span class=\"cuadro\" style=\"background:").append(AZUL).append("\"></span>Motorizados&#160;&#160;<span class=\"cuadro\" style=\"background:")
			.append(MORADO).append("\"></span>Nómina administrativa&#160;&#160;<span class=\"cuadro\" style=\"background:").append(NARANJA)
			.append("\"></span>Gastos administrativos</div>");

		b.append("<div class=\"t-titulo\" style=\"color:").append(AZUL).append(";margin-top:4pt\">1. Gastos motorizados</div>")
			.append("<table class=\"lista\"><thead><tr><th>Técnico</th><th class=\"der\">Salario</th><th class=\"der\">Horas extra</th><th class=\"der\">Bono meta</th>")
			.append("<th class=\"der\">Rodamiento</th><th class=\"der\">Gasolina</th><th class=\"der\">Comunicación</th><th class=\"der\">Total</th></tr></thead><tbody>");
		if (k.motorizados().isEmpty()) {
			b.append("<tr><td colspan=\"8\" class=\"cen muted\">Sin registros</td></tr>");
		}
		for (var m : k.motorizados()) {
			b.append("<tr><td class=\"neg\">").append(esc(m.get("beneficiario"))).append("</td>").append(pesosCelda(m.get("salario")))
				.append(pesosCelda(m.get("horas_extra"))).append(pesosCelda(m.get("bono_meta"))).append(pesosCelda(m.get("auxilio_rodamiento")))
				.append(pesosCelda(m.get("gasolina"))).append(pesosCelda(m.get("auxilio_comunicacion"))).append("<td class=\"der neg\">")
				.append(Html.pesos(dec(m.get("subtotal")))).append("</td></tr>");
		}
		b.append("<tr class=\"sub\"><td colspan=\"7\" class=\"der\">Subtotal</td><td class=\"der\">").append(Html.pesos(k.totalMotorizados()))
			.append("</td></tr></tbody></table>");

		b.append("<table class=\"rejilla\" style=\"margin-top:4pt\"><tr><td style=\"width:55%\"><div class=\"t-titulo\" style=\"color:").append(MORADO)
			.append("\">2. Nómina administrativa</div><table class=\"lista\"><thead><tr><th>Colaborador</th><th>Cargo</th><th class=\"der\">Salario</th>")
			.append("<th class=\"der\">Horas extra</th><th class=\"der\">Bono</th><th class=\"der\">Total</th></tr></thead><tbody>");
		if (k.admin().isEmpty()) {
			b.append("<tr><td colspan=\"6\" class=\"cen muted\">Sin registros</td></tr>");
		}
		for (var a : k.admin()) {
			b.append("<tr><td class=\"neg\">").append(esc(a.get("beneficiario"))).append("</td><td>").append(esc(a.get("rol"))).append("</td>")
				.append(pesosCelda(a.get("salario"))).append(pesosCelda(a.get("horas_extra"))).append(pesosCelda(a.get("bono_meta")))
				.append("<td class=\"der neg\">").append(Html.pesos(dec(a.get("subtotal")))).append("</td></tr>");
		}
		b.append("<tr class=\"sub\"><td colspan=\"5\" class=\"der\">Subtotal</td><td class=\"der\">").append(Html.pesos(k.totalAdmin()))
			.append("</td></tr></tbody></table></td><td style=\"width:45%\"><div class=\"t-titulo\" style=\"color:").append(NARANJA)
			.append("\">3. Gastos administrativos</div><table class=\"lista\"><thead><tr><th>Concepto</th><th>Categoría</th><th class=\"der\">Valor</th></tr></thead><tbody>");
		if (k.gastos().isEmpty()) {
			b.append("<tr><td colspan=\"3\" class=\"cen muted\">Sin registros</td></tr>");
		}
		for (var g : k.gastos()) {
			b.append("<tr><td>").append(esc(g.get("concepto"))).append("</td><td><span class=\"chip\">").append(esc(g.get("categoria")))
				.append("</span></td><td class=\"der neg\">").append(Html.pesos(dec(g.get("valor")))).append("</td></tr>");
		}
		b.append("<tr class=\"sub\"><td colspan=\"2\" class=\"der\">Subtotal</td><td class=\"der\">").append(Html.pesos(k.totalGastos()))
			.append("</td></tr></tbody></table></td></tr></table>");

		b.append("<div class=\"kpi\" style=\"background:").append(PIZARRA).append(";margin-top:8pt;page-break-inside:avoid\"><table><tr><td style=\"vertical-align:middle\">")
			.append("<div class=\"k-et\">Total egresos operativos</div><div class=\"k-det\">Motorizados + nómina administrativa + gastos administrativos</div></td>")
			.append("<td class=\"der\" style=\"vertical-align:middle;font-size:20pt;font-weight:bold\">").append(Html.pesos(total)).append("</td></tr></table></div>");
	}

	// =========================================================================================== balance
	private void balance(StringBuilder b, Contexto c, int n) {
		cabecera(b, n, ESMERALDA, "Estado de resultados", "Ingresos del período menos los egresos operativos", c);
		var k = c.k();
		Costos costos = costos(c);
		record Linea(String nombre, String detalle, BigDecimal valor) {
		}
		List<Linea> ingresos = new ArrayList<>();
		for (var r : c.s("tipos")) {
			ingresos.add(new Linea(String.valueOf(r.get("tipo")), entero(lng(r.get("total"))) + " serv.", dec(r.get("ingresos"))));
		}
		ingresos.add(new Linea("Viáticos (servicios interurbanos)", "-", nz(k.viaticos())));
		ingresos.add(new Linea("Repuestos INEES", "-", nz(k.ingresosRepuestos())));
		BigDecimal totalIngresos = ingresos.stream().map(Linea::valor).reduce(BigDecimal.ZERO, BigDecimal::add);
		List<Linea> egresos = List.of(new Linea("Personal motorizado", "", costos.totalMotorizados()),
			new Linea("Nómina administrativa", "", costos.totalAdmin()), new Linea("Gastos administrativos", "", costos.totalGastos()));
		BigDecimal totalEgresos = costos.total();
		BigDecimal utilidad = totalIngresos.subtract(totalEgresos);
		double margen = totalIngresos.signum() == 0 ? 0 : utilidad.doubleValue() * 100 / totalIngresos.doubleValue();

		b.append("<div class=\"kpi\" style=\"background:").append(utilidad.signum() < 0 ? ROSA : "#065f46").append(";padding:14pt 16pt\"><table><tr>")
			.append("<td style=\"vertical-align:middle\"><div class=\"k-et\">Resultado del período (ingresos - egresos)</div>")
			.append("<div class=\"k-det\">Margen sobre ingresos: ").append(decimal(margen)).append("%</div></td>")
			.append("<td class=\"der\" style=\"vertical-align:middle;font-size:28pt;font-weight:bold\">").append(Html.pesos(utilidad))
			.append("</td></tr></table></div>");

		b.append("<table class=\"rejilla\" style=\"margin-top:6pt\"><tr><td style=\"width:55%\"><div class=\"tarjeta\">")
			.append(cabTarjeta("Desglose de ingresos", Html.pesos(totalIngresos))).append("<table class=\"lista\"><thead><tr><th>Concepto</th>")
			.append("<th class=\"der\">Servicios</th><th class=\"der\">Valor</th><th class=\"der\">%</th><th style=\"width:70pt\"></th></tr></thead><tbody>");
		for (Linea l : ingresos) {
			double p = totalIngresos.signum() == 0 ? 0 : l.valor().doubleValue() * 100 / totalIngresos.doubleValue();
			b.append("<tr><td class=\"neg\">").append(esc(l.nombre())).append("</td><td class=\"der muted\">").append(esc(l.detalle()))
				.append("</td><td class=\"der neg\">").append(Html.pesos(l.valor())).append("</td><td class=\"der\">").append(decimal(p))
				.append("%</td><td style=\"vertical-align:middle\">").append(barra(p, ESMERALDA)).append("</td></tr>");
		}
		b.append("</tbody></table></div></td><td style=\"width:45%\"><div class=\"tarjeta\">").append(cabTarjeta("Desglose de costos", Html.pesos(totalEgresos)))
			.append("<table class=\"lista\"><thead><tr><th>Concepto</th><th class=\"der\">Valor</th><th class=\"der\">%</th><th style=\"width:70pt\"></th></tr></thead><tbody>");
		for (Linea l : egresos) {
			double p = totalEgresos.signum() == 0 ? 0 : l.valor().doubleValue() * 100 / totalEgresos.doubleValue();
			b.append("<tr><td class=\"neg\">").append(esc(l.nombre())).append("</td><td class=\"der neg\">").append(Html.pesos(l.valor()))
				.append("</td><td class=\"der\">").append(decimal(p)).append("%</td><td style=\"vertical-align:middle\">").append(barra(p, ROSA))
				.append("</td></tr>");
		}
		b.append("</tbody></table>");
		if (totalEgresos.signum() == 0) {
			b.append("<p class=\"chico muted\" style=\"margin-top:6pt\">No hay costos registrados para los meses del período (módulo Costos).</p>");
		}
		b.append("</div></td></tr></table>");
	}

	// =========================================================================================== piezas comunes
	private void cabecera(StringBuilder b, int numero, String color, String titulo, String subtitulo, Contexto c) {
		b.append("<table class=\"cab\"><tr><td class=\"num\" style=\"background:").append(color).append("\">").append(String.format("%02d", numero))
			.append("</td><td class=\"txt\"><div class=\"titulo\">").append(esc(titulo)).append("</div><div class=\"sub\">").append(esc(subtitulo))
			.append("</div></td><td class=\"fecha\"><span class=\"pildora\">").append(Html.fecha(c.desde())).append(" - ").append(Html.fecha(c.hasta()))
			.append("</span></td></tr></table>");
	}

	private static void sinDatos(StringBuilder b, String texto) {
		b.append("<div class=\"aviso\">").append(esc(texto)).append("</div>");
	}

	private static String cabTarjeta(String titulo, String insignia) {
		return "<div class=\"t-cab\">" + (insignia == null ? "" : "<span class=\"insignia\">" + esc(insignia) + "</span>")
			+ "<span class=\"t-titulo\">" + esc(titulo) + "</span></div>";
	}

	private static String tarjetaValor(String etiqueta, String valor, String color) {
		return "<td style=\"width:33.3%\"><div class=\"tarjeta\" style=\"border-left:3pt solid " + color + "\"><div class=\"muted chico neg\">"
			+ esc(etiqueta.toUpperCase(CO)) + "</div><div style=\"font-size:16pt;font-weight:bold;color:" + color + "\">" + valor + "</div></div></td>";
	}

	private static void segmento(StringBuilder b, BigDecimal valor, BigDecimal total, String color) {
		if (valor.signum() <= 0) {
			return;
		}
		double p = valor.doubleValue() * 100 / total.doubleValue();
		b.append("<td style=\"width:").append(ancho(p)).append("%;background:").append(color)
			.append(";color:#fff;font-weight:bold;text-align:center;vertical-align:middle;font-size:7.5pt\">").append(p > 12 ? decimal(p) + "%" : "")
			.append("</td>");
	}

	/** Tarjetas en filas de {@code columnas}; cada fila no se parte entre páginas. */
	private static String rejilla(List<String> tarjetas, int columnas) {
		StringBuilder b = new StringBuilder("<table class=\"rejilla\" style=\"table-layout:fixed\">");
		String ancho = String.format(Locale.ROOT, "%.2f", 100.0 / columnas);
		for (int i = 0; i < tarjetas.size(); i += columnas) {
			b.append("<tr>");
			for (int j = i; j < i + columnas; j++) {
				b.append("<td style=\"width:").append(ancho).append("%\">");
				if (j < tarjetas.size()) {
					b.append("<div class=\"tarjeta\">").append(tarjetas.get(j)).append("</div>");
				}
				b.append("</td>");
			}
			b.append("</tr>");
		}
		return b.append("</table>").toString();
	}

	private static String barra(double porcentaje, String color) {
		return "<div class=\"pista\"><div class=\"relleno\" style=\"width:" + ancho(porcentaje) + "%;background:" + color + "\"></div></div>";
	}

	private static String filaBarra(String etiquetaEscapada, String valor, double porcentaje, String color) {
		return "<tr class=\"fila\"><td class=\"etq\" style=\"width:38%\">" + etiquetaEscapada + "</td><td>" + barra(porcentaje, color)
			+ "</td><td class=\"val\">" + valor + "</td></tr>";
	}

	/**
	 * Barras verticales: cada elemento es {valor, nombre, detalle, porcentaje de la altura, color}.
	 */
	private static String barrasVerticales(List<String[]> barras, double altoPt) {
		StringBuilder cuerpo = new StringBuilder("<tr>"), nombres = new StringBuilder("<tr>"), detalle = new StringBuilder("<tr>");
		String ancho = String.format(Locale.ROOT, "%.2f", 100.0 / barras.size());
		for (String[] x : barras) {
			double alto = Math.max(2, Double.parseDouble(x[3]) * (altoPt - 16) / 100);
			cuerpo.append("<td class=\"col\" style=\"width:").append(ancho).append("%;height:").append(fmt(altoPt)).append("pt\"><div class=\"v\">").append(x[0])
				.append("</div><div style=\"height:").append(fmt(alto)).append("pt;background:").append(x[4])
				.append(";border-radius:3pt 3pt 0 0\"></div></td>");
			nombres.append("<td class=\"n\">").append(x[1]).append("</td>");
			detalle.append("<td class=\"p\">").append(x[2]).append("</td>");
		}
		return "<table class=\"barras\" style=\"table-layout:fixed\">" + cuerpo.append("</tr>") + nombres.append("</tr>")
			+ detalle.append("</tr>") + "</table>";
	}

	/** Matriz fila × columna con totales; con {@code porcentajes} muestra el % de cada celda dentro de su fila. */
	private static String matriz(String nombreFila, List<String> columnas, List<Map<String, Object>> datos, boolean porcentajes,
		String fondo, String color) {
		Map<String, Map<String, Long>> m = new TreeMap<>();
		Set<String> cols = new LinkedHashSet<>(columnas);
		for (var r : datos) {
			String f = String.valueOf(r.get("fila")), col = String.valueOf(r.get("columna"));
			cols.add(col);
			m.computeIfAbsent(f, x -> new LinkedHashMap<>()).merge(col, lng(r.get("valor")), Long::sum);
		}
		// Solo columnas con datos (un tipo inactivo sin servicios no ocupa espacio)
		cols.removeIf(col -> m.values().stream().noneMatch(f -> f.getOrDefault(col, 0L) > 0));
		Map<String, Long> totCol = new LinkedHashMap<>();
		long gran = 0;
		StringBuilder b = new StringBuilder("<table class=\"matriz\"><thead><tr><th class=\"izq\">").append(esc(nombreFila)).append("</th>");
		cols.forEach(col -> b.append("<th>").append(esc(col)).append("</th>"));
		b.append("<th>Total</th></tr></thead><tbody>");
		for (var e : m.entrySet()) {
			long totFila = e.getValue().values().stream().mapToLong(Long::longValue).sum();
			b.append("<tr><td class=\"izq neg\">").append(esc(e.getKey())).append("</td>");
			for (String col : cols) {
				long v = e.getValue().getOrDefault(col, 0L);
				totCol.merge(col, v, Long::sum);
				if (v == 0) {
					b.append("<td class=\"vacio\">-</td>");
					continue;
				}
				double p = pct(v, totFila);
				b.append("<td style=\"background:").append(fondo).append(";color:").append(color).append(";font-weight:bold\">").append(entero(v));
				if (porcentajes) {
					b.append("<div class=\"pct\">").append(decimal(p)).append("%</div>");
				}
				b.append("</td>");
			}
			gran += totFila;
			b.append("<td class=\"coltot\">").append(entero(totFila)).append("</td></tr>");
		}
		b.append("<tr class=\"tot\"><td class=\"izq\">TOTAL</td>");
		for (String col : cols) {
			long v = totCol.getOrDefault(col, 0L);
			b.append("<td>").append(entero(v));
			if (porcentajes) {
				b.append("<div class=\"pct\" style=\"color:#cbd5e1\">").append(decimal(pct(v, gran))).append("%</div>");
			}
			b.append("</td>");
		}
		return b.append("<td>").append(entero(gran)).append("</td></tr></tbody></table>").toString();
	}

	private static List<String> columnas(List<Map<String, Object>> datos) {
		return datos.stream().map(r -> String.valueOf(r.get("columna"))).distinct().sorted().toList();
	}

	/** Agrupa filas ya ordenadas por {@code clave}, conservando el orden. */
	private static Map<Object, List<Map<String, Object>>> agrupar(List<Map<String, Object>> filas, String clave) {
		Map<Object, List<Map<String, Object>>> g = new LinkedHashMap<>();
		filas.forEach(f -> g.computeIfAbsent(f.get(clave) == null ? "SIN DELEGACIÓN" : f.get(clave), x -> new ArrayList<>()).add(f));
		return g;
	}

	private static String pesosCelda(Object v) {
		BigDecimal d = dec(v);
		return "<td class=\"der\">" + (d.signum() > 0 ? Html.pesos(d) : "<span class=\"vacio\">-</span>") + "</td>";
	}

	private byte[] logo() {
		return Pdf.logo();
	}

	// ------------------------------------------------------------------------------------------- números
	private static long lng(Object v) {
		return v instanceof Number x ? x.longValue() : 0;
	}

	private static BigDecimal dec(Object v) {
		return v instanceof BigDecimal d ? d : v instanceof Number x ? new BigDecimal(x.toString()) : BigDecimal.ZERO;
	}

	private static BigDecimal nz(BigDecimal v) {
		return v == null ? BigDecimal.ZERO : v;
	}

	private static double pct(long parte, long total) {
		return total == 0 ? 0 : redondear1(parte * 100.0 / total);
	}

	private static double redondear1(double v) {
		return Math.round(v * 10) / 10.0;
	}

	private static String entero(long v) {
		return NumberFormat.getIntegerInstance(CO).format(v);
	}

	private static String decimal(double v) {
		return String.format(CO, "%.1f", v);
	}

	private static String ancho(double porcentaje) {
		double p = porcentaje <= 0 ? 0 : Math.min(100, Math.max(1.5, porcentaje));
		return fmt(p);
	}

	private static String fmt(double v) {
		return String.format(Locale.ROOT, "%.2f", v);
	}

	private static LocalDate fecha(Object v) {
		return v instanceof LocalDate d ? d : v instanceof java.sql.Date d ? d.toLocalDate() : LocalDate.parse(String.valueOf(v));
	}

	private static String capital(String s) {
		return s.isEmpty() ? s : Character.toUpperCase(s.charAt(0)) + s.substring(1);
	}
}
