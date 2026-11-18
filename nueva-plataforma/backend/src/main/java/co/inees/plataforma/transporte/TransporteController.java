package co.inees.plataforma.transporte;

import static co.inees.plataforma.comun.Html.esc;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.List;

import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.jdbc.support.GeneratedKeyHolder;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.bind.annotation.DeleteMapping;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RequestPart;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.multipart.MultipartFile;

import co.inees.plataforma.comun.Almacenamiento;
import co.inees.plataforma.comun.Descarga;
import co.inees.plataforma.comun.Excel;
import co.inees.plataforma.comun.Filtro;
import co.inees.plataforma.comun.Html;
import co.inees.plataforma.comun.Pdf;
import co.inees.plataforma.config.NegocioException;
import co.inees.plataforma.remision.Talonario;

/** Instalaciones, desinstalaciones y traslados de máquinas con su remisión y fotos (permiso TRANSPORTE). */
@RestController
@RequestMapping("/api/transporte")
public class TransporteController {

	/** Servicios de cada categoría (los mismos del formulario PHP). */
	public static final java.util.Map<String, List<String>> SERVICIOS = java.util.Map.of(
		"Prosegur_Cobro", List.of("Desmonte Definitivo", "Desmonte Provicional", "Cambio - Instalación", "Cambio - Desinstalación",
			"Instalación Punto Nuevo", "Traslados Prosegur"),
		"Prosegur_NoCobro", List.of("Maquinas Para Bodegaje", "Máquinas Para Recuperar"),
		"Inees", List.of("Malaver", "Internos Don Antonio Osorio", "Internos Inees"));

	public record Transporte(int id, LocalDate fecha, String categoria, String tipoServicio, String notas,
		String descripcionInees, String lugarRecogida, LocalDate fechaRecogida, boolean esMaquina, Integer idTipoMaquina,
		String tipoMaquina, String serialMaquina, String productoOtro, Integer idClienteOrigen, String clienteOrigen,
		Integer idPuntoOrigen, String puntoOrigen, Integer idClienteDestino, String clienteDestino, Integer idPuntoDestino,
		String puntoDestino, int idTecnico, String tecnico, BigDecimal valor, Integer idControlRemision, String remision,
		String fotoRemision, String fotoMaquina, String fotoChazos, LocalDateTime creado) {
	}

	private static final String SELECT = """
		SELECT i.id_instalacion AS id, i.fecha_instalacion AS fecha, i.categoria_servicio AS categoria,
		       i.tipo_servicio_nombre AS tipo_servicio, i.notas, i.descripcion_inees, i.lugar_recogida, i.fecha_recogida,
		       i.es_maquina, i.id_tipo_maquina, tm.nombre_tipo_maquina AS tipo_maquina, i.serial_maquina, i.producto_otro,
		       i.id_cliente_origen, COALESCE(co.nombre_cliente, i.cliente_origen_texto) AS cliente_origen,
		       i.id_punto_origen, COALESCE(po.nombre_punto, i.punto_origen_texto) AS punto_origen,
		       i.id_cliente_destino, COALESCE(cd.nombre_cliente, i.cliente_destino_texto) AS cliente_destino,
		       i.id_punto_destino, COALESCE(pd.nombre_punto, i.punto_destino_texto) AS punto_destino,
		       i.id_tecnico, t.nombre_tecnico AS tecnico, i.valor_servicio AS valor, i.id_control_remision,
		       cr.numero_remision AS remision, i.foto_remision, i.foto_maquina, i.foto_chazos, i.created_at AS creado
		FROM instalaciones_desinstalaciones i
		JOIN tecnico t ON t.id_tecnico = i.id_tecnico
		LEFT JOIN tipo_maquina tm ON tm.id_tipo_maquina = i.id_tipo_maquina
		LEFT JOIN cliente co ON co.id_cliente = i.id_cliente_origen LEFT JOIN punto po ON po.id_punto = i.id_punto_origen
		LEFT JOIN cliente cd ON cd.id_cliente = i.id_cliente_destino LEFT JOIN punto pd ON pd.id_punto = i.id_punto_destino
		LEFT JOIN control_remisiones cr ON cr.id_control = i.id_control_remision
		""";

	private final JdbcClient jdbc;
	private final Almacenamiento almacenamiento;
	private final Talonario talonario;

	public TransporteController(JdbcClient jdbc, Almacenamiento almacenamiento, Talonario talonario) {
		this.jdbc = jdbc;
		this.almacenamiento = almacenamiento;
		this.talonario = talonario;
	}

	@GetMapping("/servicios")
	public java.util.Map<String, List<String>> servicios() {
		return SERVICIOS;
	}

	@GetMapping
	public List<Transporte> listar(@RequestParam(required = false) LocalDate desde, @RequestParam(required = false) LocalDate hasta,
		@RequestParam(required = false) Integer idTecnico, @RequestParam(required = false) String categoria,
		@RequestParam(required = false) String texto) {
		Filtro f = new Filtro().y("i.estado = 1").y("i.fecha_instalacion >= :d", "d", desde).y("i.fecha_instalacion <= :h", "h", hasta)
			.y("i.id_tecnico = :t", "t", idTecnico).y("i.categoria_servicio = :c", "c", categoria)
			.texto("q", texto, "i.serial_maquina", "cr.numero_remision", "cd.nombre_cliente", "i.cliente_destino_texto",
				"pd.nombre_punto", "i.punto_destino_texto");
		return jdbc.sql(SELECT + " WHERE " + f.where() + " ORDER BY i.id_instalacion DESC LIMIT 2000").params(f.params())
			.query(Transporte.class).list();
	}

	@GetMapping("/{id}")
	public Transporte obtener(@PathVariable int id) {
		return jdbc.sql(SELECT + " WHERE i.id_instalacion = :id AND i.estado = 1").param("id", id).query(Transporte.class).optional()
			.orElseThrow(() -> NegocioException.noEncontrado("El transporte"));
	}

	/** Crea o (con id) actualiza un transporte. Cliente y punto aceptan uno del catálogo o un texto libre. */
	@PostMapping(consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
	@Transactional
	public Transporte crear(@RequestParam int idTecnico, @RequestParam LocalDate fecha, @RequestParam String categoria,
		@RequestParam(required = false) String tipoServicio, @RequestParam(required = false) String notas,
		@RequestParam(required = false) String descripcionInees, @RequestParam(required = false) String lugarRecogida,
		@RequestParam(required = false) LocalDate fechaRecogida, @RequestParam(defaultValue = "true") boolean esMaquina,
		@RequestParam(required = false) Integer idTipoMaquina, @RequestParam(required = false) String serialMaquina,
		@RequestParam(required = false) String productoOtro, @RequestParam(required = false) Integer idClienteOrigen,
		@RequestParam(required = false) String clienteOrigenTexto, @RequestParam(required = false) Integer idPuntoOrigen,
		@RequestParam(required = false) String puntoOrigenTexto, @RequestParam(required = false) Integer idClienteDestino,
		@RequestParam(required = false) String clienteDestinoTexto, @RequestParam(required = false) Integer idPuntoDestino,
		@RequestParam(required = false) String puntoDestinoTexto, @RequestParam(defaultValue = "0") BigDecimal valor,
		@RequestParam(required = false) Integer idControlRemision,
		@RequestPart(name = "fotoRemision", required = false) MultipartFile fotoRemision,
		@RequestPart(name = "fotoMaquina", required = false) MultipartFile fotoMaquina,
		@RequestPart(name = "fotoChazos", required = false) MultipartFile fotoChazos) {
		validar(categoria, tipoServicio, esMaquina, idTipoMaquina);
		var llave = new GeneratedKeyHolder();
		jdbc.sql("""
			INSERT INTO instalaciones_desinstalaciones (id_tecnico, fecha_instalacion, categoria_servicio, tipo_servicio_nombre,
			       notas, descripcion_inees, lugar_recogida, fecha_recogida, es_maquina, id_tipo_maquina, serial_maquina,
			       producto_otro, id_cliente_origen, cliente_origen_texto, id_punto_origen, punto_origen_texto,
			       id_cliente_destino, cliente_destino_texto, id_punto_destino, punto_destino_texto, valor_servicio, estado, created_at)
			VALUES (:tec, :f, :cat, :tipo, :notas, :desc, :lugar, :frec, :esm, :tm, :serial, :otro, :co, :cot, :po, :pot,
			        :cd, :cdt, :pd, :pdt, :valor, 1, NOW())""")
			.param("tec", idTecnico).param("f", fecha).param("cat", categoria).param("tipo", vacio(tipoServicio))
			.param("notas", vacio(notas)).param("desc", vacio(descripcionInees)).param("lugar", vacio(lugarRecogida))
			.param("frec", fechaRecogida).param("esm", esMaquina).param("tm", esMaquina ? idTipoMaquina : null)
			.param("serial", vacio(serialMaquina)).param("otro", esMaquina ? null : vacio(productoOtro))
			.param("co", idClienteOrigen).param("cot", idClienteOrigen == null ? vacio(clienteOrigenTexto) : null)
			.param("po", idPuntoOrigen).param("pot", idPuntoOrigen == null ? vacio(puntoOrigenTexto) : null)
			.param("cd", idClienteDestino).param("cdt", idClienteDestino == null ? vacio(clienteDestinoTexto) : null)
			.param("pd", idPuntoDestino).param("pdt", idPuntoDestino == null ? vacio(puntoDestinoTexto) : null)
			.param("valor", valor).update(llave);
		int id = llave.getKey().intValue();
		if (idControlRemision != null) {
			talonario.ocuparEnTransporte(idControlRemision, idTecnico, id);
			jdbc.sql("UPDATE instalaciones_desinstalaciones SET id_control_remision = :c WHERE id_instalacion = :id")
				.param("c", idControlRemision).param("id", id).update();
		}
		guardarFotos(id, fotoRemision, fotoMaquina, fotoChazos);
		return obtener(id);
	}

	@PostMapping(value = "/{id}", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
	@Transactional
	public Transporte actualizar(@PathVariable int id, @RequestParam int idTecnico, @RequestParam LocalDate fecha,
		@RequestParam String categoria, @RequestParam(required = false) String tipoServicio,
		@RequestParam(required = false) String notas, @RequestParam(required = false) String descripcionInees,
		@RequestParam(required = false) String lugarRecogida, @RequestParam(required = false) LocalDate fechaRecogida,
		@RequestParam(defaultValue = "true") boolean esMaquina, @RequestParam(required = false) Integer idTipoMaquina,
		@RequestParam(required = false) String serialMaquina, @RequestParam(required = false) String productoOtro,
		@RequestParam(required = false) Integer idClienteOrigen, @RequestParam(required = false) String clienteOrigenTexto,
		@RequestParam(required = false) Integer idPuntoOrigen, @RequestParam(required = false) String puntoOrigenTexto,
		@RequestParam(required = false) Integer idClienteDestino, @RequestParam(required = false) String clienteDestinoTexto,
		@RequestParam(required = false) Integer idPuntoDestino, @RequestParam(required = false) String puntoDestinoTexto,
		@RequestParam(defaultValue = "0") BigDecimal valor, @RequestParam(required = false) Integer idControlRemision,
		@RequestPart(name = "fotoRemision", required = false) MultipartFile fotoRemision,
		@RequestPart(name = "fotoMaquina", required = false) MultipartFile fotoMaquina,
		@RequestPart(name = "fotoChazos", required = false) MultipartFile fotoChazos) {
		Transporte actual = obtener(id);
		validar(categoria, tipoServicio, esMaquina, idTipoMaquina);
		jdbc.sql("""
			UPDATE instalaciones_desinstalaciones SET id_tecnico = :tec, fecha_instalacion = :f, categoria_servicio = :cat,
			       tipo_servicio_nombre = :tipo, notas = :notas, descripcion_inees = :desc, lugar_recogida = :lugar,
			       fecha_recogida = :frec, es_maquina = :esm, id_tipo_maquina = :tm, serial_maquina = :serial, producto_otro = :otro,
			       id_cliente_origen = :co, cliente_origen_texto = :cot, id_punto_origen = :po, punto_origen_texto = :pot,
			       id_cliente_destino = :cd, cliente_destino_texto = :cdt, id_punto_destino = :pd, punto_destino_texto = :pdt,
			       valor_servicio = :valor, updated_at = NOW()
			WHERE id_instalacion = :id""")
			.param("tec", idTecnico).param("f", fecha).param("cat", categoria).param("tipo", vacio(tipoServicio))
			.param("notas", vacio(notas)).param("desc", vacio(descripcionInees)).param("lugar", vacio(lugarRecogida))
			.param("frec", fechaRecogida).param("esm", esMaquina).param("tm", esMaquina ? idTipoMaquina : null)
			.param("serial", vacio(serialMaquina)).param("otro", esMaquina ? null : vacio(productoOtro))
			.param("co", idClienteOrigen).param("cot", idClienteOrigen == null ? vacio(clienteOrigenTexto) : null)
			.param("po", idPuntoOrigen).param("pot", idPuntoOrigen == null ? vacio(puntoOrigenTexto) : null)
			.param("cd", idClienteDestino).param("cdt", idClienteDestino == null ? vacio(clienteDestinoTexto) : null)
			.param("pd", idPuntoDestino).param("pdt", idPuntoDestino == null ? vacio(puntoDestinoTexto) : null)
			.param("valor", valor).param("id", id).update();
		if (!java.util.Objects.equals(idControlRemision, actual.idControlRemision()) || idTecnico != actual.idTecnico()) {
			talonario.liberarDeTransporte(id);
			jdbc.sql("UPDATE instalaciones_desinstalaciones SET id_control_remision = NULL WHERE id_instalacion = :id").param("id", id).update();
			if (idControlRemision != null) {
				talonario.ocuparEnTransporte(idControlRemision, idTecnico, id);
				jdbc.sql("UPDATE instalaciones_desinstalaciones SET id_control_remision = :c WHERE id_instalacion = :id")
					.param("c", idControlRemision).param("id", id).update();
			}
		}
		guardarFotos(id, fotoRemision, fotoMaquina, fotoChazos);
		return obtener(id);
	}

	@DeleteMapping("/{id}")
	@Transactional
	public void eliminar(@PathVariable int id) {
		obtener(id);
		talonario.liberarDeTransporte(id);
		jdbc.sql("UPDATE instalaciones_desinstalaciones SET estado = 0, id_control_remision = NULL WHERE id_instalacion = :id")
			.param("id", id).update();
	}

	/** Remisiones disponibles del técnico con su id de control (para el selector del formulario). */
	@GetMapping("/remisiones")
	public List<co.inees.plataforma.catalogo.ListasController.Opcion> remisiones(@RequestParam int idTecnico) {
		return jdbc.sql("""
			SELECT cr.id_control, cr.numero_remision FROM control_remisiones cr
			WHERE cr.id_tecnico = :t AND cr.id_estado = (SELECT id_estado FROM estados_remision WHERE nombre_estado = 'DISPONIBLE')
			ORDER BY CAST(cr.numero_remision AS UNSIGNED)""")
			.param("t", idTecnico)
			.query((rs, i) -> new co.inees.plataforma.catalogo.ListasController.Opcion(rs.getInt(1), rs.getString(2), null)).list();
	}

	@GetMapping("/excel")
	public ResponseEntity<byte[]> excel(@RequestParam(required = false) LocalDate desde, @RequestParam(required = false) LocalDate hasta,
		@RequestParam(required = false) Integer idTecnico, @RequestParam(required = false) String categoria) {
		var filas = listar(desde, hasta, idTecnico, categoria, null).stream().map(t -> (List<?>) java.util.Arrays.asList(t.id(), t.fecha(),
			t.categoria(), t.tipoServicio(), t.remision(), t.tecnico(), t.esMaquina() ? t.tipoMaquina() : t.productoOtro(),
			t.serialMaquina(), t.clienteOrigen(), t.puntoOrigen(), t.clienteDestino(), t.puntoDestino(), t.lugarRecogida(),
			t.fechaRecogida(), new Excel.Pesos(t.valor()), t.notas())).toList();
		return Descarga.excel("Transportes_" + LocalDate.now(), new Excel().hoja("Transportes", List.of("ID", "Fecha", "Categoría",
			"Servicio", "Remisión", "Técnico", "Máquina / producto", "Serial", "Cliente origen", "Punto origen", "Cliente destino",
			"Punto destino", "Lugar de recogida", "Fecha de recogida", "Valor", "Notas"), filas).bytes());
	}

	@GetMapping("/{id}/pdf")
	public ResponseEntity<byte[]> pdf(@PathVariable int id) {
		Transporte t = obtener(id);
		String firma = jdbc.sql("SELECT ruta_firma FROM tecnico WHERE id_tecnico = :t").param("t", t.idTecnico())
			.query((rs, i) -> rs.getString(1)).list().stream().filter(java.util.Objects::nonNull).findFirst().orElse(null);
		StringBuilder b = new StringBuilder(Pdf.encabezado("Acta de transporte #" + t.id(),
			categoria(t.categoria()) + " · " + (t.tipoServicio() == null ? "" : t.tipoServicio())));
		b.append("<table><tr><th colspan=\"2\">Datos generales</th></tr>")
			.append(fila("Fecha", Html.fecha(t.fecha()))).append(fila("Remisión", esc(t.remision())))
			.append(fila("Técnico", esc(t.tecnico()))).append(fila("Valor", Html.pesos(t.valor())))
			.append(fila(t.esMaquina() ? "Máquina" : "Producto", esc(t.esMaquina() ? t.tipoMaquina() : t.productoOtro())))
			.append(fila("Serial", esc(t.serialMaquina())))
			.append(fila("Origen", esc(t.clienteOrigen()) + " · " + esc(t.puntoOrigen())))
			.append(fila("Destino", esc(t.clienteDestino()) + " · " + esc(t.puntoDestino())))
			.append(fila("Lugar y fecha de recogida", esc(t.lugarRecogida()) + " " + (t.fechaRecogida() == null ? "" : Html.fecha(t.fechaRecogida()))))
			.append(fila("Notas", Html.parrafo(t.notas()))).append(fila("Descripción INEES", Html.parrafo(t.descripcionInees())))
			.append("</table><h2>Evidencias</h2><table><tr>");
		for (String[] f : new String[][] { { "Remisión", t.fotoRemision() }, { "Máquina", t.fotoMaquina() }, { "Chazos", t.fotoChazos() } }) {
			b.append("<td class=\"cen\">").append(Html.imagen(almacenamiento.bytes(f[1]), "foto")).append("<div class=\"muted\">")
				.append(f[0]).append("</div></td>");
		}
		b.append("</tr></table><div style=\"margin-top:30pt;width:200pt\">").append(Html.imagen(almacenamiento.bytes(firma), "firma"))
			.append("<div style=\"border-top:0.75pt solid #000\">").append(esc(t.tecnico())).append("<br/>Técnico</div></div>");
		String estilo = ".foto { width: 160pt; max-height: 200pt; } .firma { height: 45pt; } td { width: 33%; }";
		return Descarga.pdf("Transporte_" + t.id() + "_" + Descarga.nombreSeguro(t.remision(), "sin_remision"),
			Pdf.generar(Pdf.documento("Transporte " + t.id(), estilo, b.toString())));
	}

	private void guardarFotos(int id, MultipartFile remision, MultipartFile maquina, MultipartFile chazos) {
		String[][] fotos = { { "foto_remision", "remision" }, { "foto_maquina", "maquina" }, { "foto_chazos", "chazos" } };
		MultipartFile[] archivos = { remision, maquina, chazos };
		for (int i = 0; i < 3; i++) {
			if (archivos[i] != null && !archivos[i].isEmpty()) {
				String ruta = almacenamiento.guardarImagenEn("transporte", "t" + id + "_" + fotos[i][1], archivos[i]);
				jdbc.sql("UPDATE instalaciones_desinstalaciones SET " + fotos[i][0] + " = :r WHERE id_instalacion = :id")
					.param("r", ruta).param("id", id).update();
			}
		}
	}

	private static void validar(String categoria, String tipo, boolean esMaquina, Integer idTipoMaquina) {
		if (!SERVICIOS.containsKey(categoria)) {
			throw new NegocioException("Seleccione la categoría del transporte.");
		}
		if (tipo != null && !tipo.isBlank() && !SERVICIOS.get(categoria).contains(tipo)) {
			throw new NegocioException("El servicio no corresponde a la categoría.");
		}
		if (esMaquina && idTipoMaquina == null) {
			throw new NegocioException("Seleccione el tipo de máquina.");
		}
	}

	private static String categoria(String c) {
		return switch (c == null ? "" : c) {
			case "Prosegur_Cobro" -> "Prosegur - con cobro";
			case "Prosegur_NoCobro" -> "Prosegur - sin cobro";
			case "Inees" -> "Transportes INEES (internos)";
			default -> "";
		};
	}

	private static String fila(String etiqueta, String valor) {
		return "<tr><td style=\"width:30%\"><b>" + esc(etiqueta) + "</b></td><td>" + (valor == null || valor.isBlank() ? "—" : valor) + "</td></tr>";
	}

	private static String vacio(String s) {
		return s == null || s.isBlank() ? null : s.trim();
	}
}
