package co.inees.plataforma.orden;

import static co.inees.plataforma.comun.Html.esc;
import static co.inees.plataforma.comun.Html.parrafo;

import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.util.HashSet;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.zip.ZipEntry;
import java.util.zip.ZipOutputStream;

import org.springframework.core.io.ClassPathResource;
import org.springframework.stereotype.Component;

import co.inees.plataforma.comun.Almacenamiento;
import co.inees.plataforma.comun.Descarga;
import co.inees.plataforma.comun.Html;
import co.inees.plataforma.comun.Pdf;

/** PDF del reporte técnico de un servicio (el que se entrega a Prosegur) y descarga de varios en un ZIP. */
@Component
public class PdfServicio {

	private static final Map<String, String> TITULOS_EVIDENCIA = new LinkedHashMap<>();

	static {
		TITULOS_EVIDENCIA.put("llegada", "Llegada al punto");
		TITULOS_EVIDENCIA.put("antes", "Antes");
		TITULOS_EVIDENCIA.put("seriales", "Seriales");
		TITULOS_EVIDENCIA.put("estado_inicial", "Estado inicial");
		TITULOS_EVIDENCIA.put("componentes", "Componentes");
		TITULOS_EVIDENCIA.put("trabajo", "Trabajo realizado");
		TITULOS_EVIDENCIA.put("repuestos", "Repuestos");
		TITULOS_EVIDENCIA.put("despues", "Después");
		TITULOS_EVIDENCIA.put("estado_final", "Estado final");
		TITULOS_EVIDENCIA.put("novedad", "Novedad");
		TITULOS_EVIDENCIA.put("no_ingreso", "Sin ingreso");
		TITULOS_EVIDENCIA.put("remision", "Remisión");
	}

	private static final String ESTILO = """
		.cab { width: 100%; border-bottom: 2pt solid #22313f; margin-bottom: 8pt; }
		.cab td { border: none; vertical-align: middle; }
		.logo { height: 42pt; }
		.rem { text-align: right; font-size: 13pt; font-weight: bold; }
		.rem span { color: #d2493f; }
		.etq { font-size: 7pt; text-transform: uppercase; color: #6f7d8c; letter-spacing: 0.5pt; }
		.val { font-size: 9.5pt; font-weight: bold; }
		.grid td { border: 0.5pt solid #d5dbe1; width: 33%; }
		.pill { background: #d9efe9; color: #17806f; padding: 1pt 6pt; border-radius: 6pt; font-weight: bold; }
		.fotos { width: 100%; }
		.fotos td { border: none; width: 33%; text-align: center; padding: 3pt; }
		.foto { width: 170pt; max-height: 200pt; border: 0.5pt solid #d5dbe1; }
		.firmas { width: 100%; margin-top: 16pt; }
		.firmas td { border: none; text-align: center; width: 50%; }
		.firma { height: 50pt; }
		.linea { border-top: 0.75pt solid #1b2430; margin: 0 30pt; padding-top: 2pt; }
		""";

	private final ServicioConsultas consultas;
	private final OrdenService ordenes;
	private final Almacenamiento almacenamiento;
	private final org.springframework.jdbc.core.simple.JdbcClient jdbc;
	private byte[] logo;

	public PdfServicio(ServicioConsultas consultas, OrdenService ordenes, Almacenamiento almacenamiento,
		org.springframework.jdbc.core.simple.JdbcClient jdbc) {
		this.consultas = consultas;
		this.ordenes = ordenes;
		this.almacenamiento = almacenamiento;
		this.jdbc = jdbc;
	}

	public byte[] pdf(int idOrden) {
		return Pdf.generar(html(consultas.uno(idOrden)));
	}

	/** Nombre igual al de PHP: Servicio_{remisión}_{punto}_{fecha}.pdf */
	public String nombre(ServicioConsultas.Servicio s) {
		return "Servicio_" + Descarga.nombreSeguro(s.numeroRemision() == null ? String.valueOf(s.idOrden()) : s.numeroRemision(),
			"Sin_Remision") + "_" + Descarga.nombreSeguro(s.punto(), "Sin_Punto") + "_" + s.fechaVisita() + ".pdf";
	}

	/** Escribe un ZIP con un PDF por servicio, opcionalmente en carpetas por delegación y técnico. */
	public void zip(List<Integer> ids, boolean porDelegacion, boolean porTecnico, OutputStream salida) throws IOException {
		Set<String> usados = new HashSet<>();
		try (ZipOutputStream zip = new ZipOutputStream(salida)) {
			for (ServicioConsultas.Servicio s : consultas.porIds(ids)) {
				StringBuilder ruta = new StringBuilder();
				if (porDelegacion) {
					ruta.append(Descarga.nombreSeguro(s.delegacion(), "SIN_DELEGACION")).append('/');
				}
				if (porTecnico) {
					ruta.append(Descarga.nombreSeguro(s.tecnico(), "SIN_TECNICO")).append('/');
				}
				String nombre = nombre(s);
				String completa = ruta + nombre;
				if (!usados.add(completa)) {
					completa = ruta + nombre.replace(".pdf", "_" + s.idOrden() + ".pdf");
					usados.add(completa);
				}
				byte[] pdf;
				try {
					pdf = Pdf.generar(html(s));
				}
				catch (RuntimeException e) {
					zip.putNextEntry(new ZipEntry(completa.replace(".pdf", "_ERROR.txt")));
					zip.write(("No se pudo generar el PDF del servicio " + s.idOrden() + ": " + e.getMessage()).getBytes());
					zip.closeEntry();
					continue;
				}
				zip.putNextEntry(new ZipEntry(completa));
				zip.write(pdf);
				zip.closeEntry();
			}
		}
	}

	String html(ServicioConsultas.Servicio s) {
		OrdenService.Complemento c = ordenes.complemento(s.idOrden());
		List<OrdenService.Evidencia> evidencias = ordenes.evidencias(s.idOrden());
		String estadoInicial = c.idEstadoInicial() == null ? null : jdbc.sql("SELECT nombre_estado FROM estado_maquina WHERE id_estado = :e")
			.param("e", c.idEstadoInicial()).query(String.class).optional().orElse(null);
		String rutaFirmaTecnico = s.idTecnico() == null ? null : jdbc.sql("SELECT ruta_firma FROM tecnico WHERE id_tecnico = :t")
			.param("t", s.idTecnico()).query((rs, i) -> rs.getString(1)).list().stream().filter(java.util.Objects::nonNull)
			.findFirst().orElse(null);

		StringBuilder b = new StringBuilder();
		b.append("<table class=\"cab\"><tr><td>").append(Html.imagen(logo(), "logo"))
			.append("<div class=\"etq\">Reporte técnico de servicio</div></td><td class=\"rem\">Remisión <span>#")
			.append(esc(s.numeroRemision() == null ? "—" : s.numeroRemision())).append("</span><br/><span class=\"muted\" style=\"font-size:9pt;color:#6f7d8c\">")
			.append(Html.fechaLarga(s.fechaVisita())).append("</span></td></tr></table>");

		b.append("<table class=\"grid\"><tr>")
			.append(celda("Cliente", s.cliente())).append(celda("Punto", s.punto())).append(celda("Delegación", s.delegacion()))
			.append("</tr><tr>")
			.append(celda("Técnico", s.tecnico())).append(celda("Tipo de servicio", s.tipoMantenimiento()))
			.append(celda("Modalidad", s.modalidad())).append("</tr><tr>")
			.append(celda("Hora de entrada", Html.hora(s.horaEntrada()))).append(celda("Hora de salida", Html.hora(s.horaSalida())))
			.append(celda("Duración", Html.minutos(s.minutosServicio()) + " h")).append("</tr><tr>")
			.append(celda("Administrador del punto", c.administrador())).append(celda("Celular", c.celular()))
			.append("<td><div class=\"etq\">Calificación</div><span class=\"pill\">")
			.append(esc(s.calificacion() == null ? "Pendiente" : s.calificacion())).append("</span></td></tr></table>");

		b.append("<h2>Detalles de equipos</h2><table class=\"grid\"><tr>")
			.append(celda("Device ID", s.deviceId())).append(celda("Tipo de máquina", s.tipoMaquina()))
			.append(celda("N° de máquina", c.numeroMaquina())).append("</tr><tr>")
			.append(celda("Serial máquina", c.serialMaquina())).append(celda("Serial router", c.serialRouter()))
			.append(celda("Serial UPS", c.serialUps())).append("</tr><tr>")
			.append(celda("Estado inicial", estadoInicial)).append(celda("Estado final", s.estadoMaquina()))
			.append(celda("Soporte remoto", c.soporteRemoto())).append("</tr></table>");

		b.append("<h2>Diagnóstico y mantenimiento</h2>");
		if (c.detalleEstadoInicial() != null && !c.detalleEstadoInicial().isBlank()) {
			b.append("<div class=\"caja\"><div class=\"etq\">Cómo se encontró la máquina</div>").append(parrafo(c.detalleEstadoInicial())).append("</div>");
		}
		b.append("<div class=\"caja\"><div class=\"etq\">Actividades realizadas</div>")
			.append(s.actividades() == null ? "<span class=\"muted\">Sin observaciones</span>" : parrafo(s.actividades())).append("</div>");
		if (c.pendientes() != null && !c.pendientes().isBlank()) {
			b.append("<div class=\"caja\"><div class=\"etq\">Pendientes</div>").append(parrafo(c.pendientes())).append("</div>");
		}
		if (s.novedades() != null) {
			b.append("<div class=\"caja\"><div class=\"etq\">Novedades</div><b>").append(esc(s.novedades())).append("</b>")
				.append(s.detalleNovedad() == null ? "" : "<br/>" + parrafo(s.detalleNovedad())).append("</div>");
		}

		b.append("<h3>Repuestos utilizados</h3>");
		b.append(Html.tabla(List.of("Repuesto", "Cantidad", "Origen"), s.repuestos(),
			r -> List.of(esc(r.nombre()), "<div class=\"cen\">" + r.cantidad() + "</div>", "<div class=\"cen\">" + esc(r.origen()) + "</div>")));
		if (s.repuestos().isEmpty() && s.repuestosLegado() != null && !s.repuestosLegado().isBlank()) {
			b.append("<div class=\"muted\">Registrado: ").append(esc(s.repuestosLegado())).append("</div>");
		}

		byte[] firmaCliente = evidencias.stream().filter(e -> e.tipo().equals("firma")).findFirst()
			.map(e -> almacenamiento.bytes(e.ruta())).orElse(null);
		b.append("<table class=\"firmas\"><tr><td>").append(Html.imagen(almacenamiento.bytes(rutaFirmaTecnico), "firma"))
			.append("<div class=\"linea\">").append(esc(s.tecnico())).append("<br/><span class=\"muted\">Técnico de servicio</span></div></td><td>")
			.append(Html.imagen(firmaCliente, "firma")).append("<div class=\"linea\">")
			.append(esc(c.administrador() == null ? "Responsable en sitio" : c.administrador()))
			.append("<br/><span class=\"muted\">Recibido y aprobado</span></div></td></tr></table>");

		// Solo las fotos que existen en disco; si no hay ninguna no se agrega la página de evidencias.
		List<byte[]> imagenes = new java.util.ArrayList<>();
		List<String> titulos = new java.util.ArrayList<>();
		for (OrdenService.Evidencia e : evidencias) {
			byte[] img = e.tipo().equals("firma") ? null : almacenamiento.bytes(e.ruta());
			if (img != null) {
				imagenes.add(img);
				titulos.add(TITULOS_EVIDENCIA.getOrDefault(e.tipo(), e.tipo()));
			}
		}
		if (!imagenes.isEmpty()) {
			b.append("<div class=\"salto\"></div><h2>Evidencia fotográfica</h2><table class=\"fotos\"><tr>");
			for (int i = 0; i < imagenes.size(); i++) {
				if (i > 0 && i % 3 == 0) {
					b.append("</tr><tr>");
				}
				b.append("<td>").append(Html.imagen(imagenes.get(i), "foto")).append("<div class=\"etq\">")
					.append(esc(titulos.get(i))).append("</div></td>");
			}
			b.append("</tr></table>");
		}
		return Pdf.documento("Servicio " + s.idOrden(), ESTILO, b.toString());
	}

	private static String celda(String etiqueta, Object valor) {
		return "<td><div class=\"etq\">" + esc(etiqueta) + "</div><div class=\"val\">"
			+ (valor == null || valor.toString().isBlank() ? "N/A" : esc(valor)) + "</div></td>";
	}

	private byte[] logo() {
		if (logo == null) {
			try (InputStream in = new ClassPathResource("pdf/logo-servicio.jpg").getInputStream()) {
				logo = in.readAllBytes();
			}
			catch (IOException e) {
				logo = new byte[0];
			}
		}
		return logo;
	}
}
