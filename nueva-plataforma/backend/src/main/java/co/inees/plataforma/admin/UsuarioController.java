package co.inees.plataforma.admin;

import java.time.LocalDateTime;
import java.util.List;

import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.jdbc.support.GeneratedKeyHolder;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.bind.annotation.DeleteMapping;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.PutMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RequestPart;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.multipart.MultipartFile;

import co.inees.plataforma.comun.Almacenamiento;
import co.inees.plataforma.comun.Filtro;
import co.inees.plataforma.config.NegocioException;
import co.inees.plataforma.seguridad.Sesion;
import jakarta.validation.Valid;
import jakarta.validation.constraints.Email;
import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import jakarta.validation.constraints.Size;

/**
 * Usuarios, roles y firma de los técnicos (permiso ADMINISTRACION). Los roles son fijos (V7): al crear un usuario solo se
 * elige su rol; si es motorizado, se le crea o actualiza su ficha de técnico con la delegación para que entre a las rutas.
 */
@RestController
@RequestMapping("/api/admin")
public class UsuarioController {

	public record Usuario(int usuarioId, String nombre, String cedula, String cargo, String email, String celular,
		String usuario, int idRol, String rol, String estado, boolean forzarCambioPwd, LocalDateTime ultimoAcceso,
		Integer idTecnico, String tecnico, Integer idDelegacion, String delegacion) {
	}

	public record UsuarioDatos(@NotBlank @Size(max = 60) String nombre, @Size(max = 15) String cedula,
		@Size(max = 50) String cargo, @Email @Size(max = 50) String email, @Size(max = 15) String celular,
		@NotBlank @Size(max = 20) String usuario, String clave, @NotNull Integer idRol, Boolean activo,
		Integer idDelegacion) {
	}

	public record Permiso(String codigo, String descripcion) {
	}

	/** Rol fijo con lo que puede hacer, para elegirlo al crear un usuario y para la pantalla de roles. */
	public record Rol(int idRol, String nombre, String descripcion, boolean motorizado, int usuarios, List<Permiso> permisos) {
	}

	private static final String SELECT = """
		SELECT u.usuario_id, u.nombre, u.cedula, u.cargo, u.email, u.celular, u.usuario, u.id_rol, r.nombre AS rol,
		       u.estado, u.forzar_cambio_pwd, u.ultimo_acceso, t.id_tecnico, t.nombre_tecnico AS tecnico,
		       t.id_delegacion, d.nombre_delegacion AS delegacion
		FROM usuarios u JOIN rol r ON r.id_rol = u.id_rol
		LEFT JOIN tecnico t ON t.usuario_id = u.usuario_id AND t.estado = 1
		LEFT JOIN delegacion d ON d.id_delegacion = t.id_delegacion
		""";

	private final JdbcClient jdbc;
	private final PasswordEncoder encoder;
	private final Almacenamiento almacenamiento;

	public UsuarioController(JdbcClient jdbc, PasswordEncoder encoder, Almacenamiento almacenamiento) {
		this.jdbc = jdbc;
		this.encoder = encoder;
		this.almacenamiento = almacenamiento;
	}

	@GetMapping("/usuarios")
	public List<Usuario> listar(@RequestParam(required = false) String texto,
		@RequestParam(defaultValue = "false") boolean inactivos) {
		Filtro f = new Filtro().texto("t", texto, "u.nombre", "u.usuario", "u.cedula", "u.email");
		if (!inactivos) {
			f.y("u.estado = 'activo'");
		}
		return jdbc.sql(SELECT + " WHERE " + f.where() + " ORDER BY u.nombre").params(f.params()).query(Usuario.class).list();
	}

	@GetMapping("/usuarios/{id}")
	public Usuario obtener(@PathVariable int id) {
		return jdbc.sql(SELECT + " WHERE u.usuario_id = :id").param("id", id).query(Usuario.class).optional()
			.orElseThrow(() -> NegocioException.noEncontrado("El usuario"));
	}

	/** Crea el usuario con una contraseña temporal; en su primer ingreso debe cambiarla (igual que en PHP). */
	@PostMapping("/usuarios")
	@Transactional
	public Usuario crear(@Valid @RequestBody UsuarioDatos d) {
		if (d.clave() == null || d.clave().length() < 6) {
			throw new NegocioException("La contraseña temporal debe tener al menos 6 caracteres.");
		}
		boolean motorizado = validarRol(d);
		var llave = new GeneratedKeyHolder();
		jdbc.sql("""
			INSERT INTO usuarios (nombre, cedula, cargo, email, celular, usuario, password_hash, id_rol, forzar_cambio_pwd,
			                      pwd_ultimo_cambio, estado)
			VALUES (:nombre, :cedula, :cargo, :email, :celular, :usuario, :hash, :rol, 1, NOW(), 'activo')""")
			.param("nombre", d.nombre().trim()).param("cedula", vacio(d.cedula())).param("cargo", vacio(d.cargo()))
			.param("email", vacio(d.email())).param("celular", vacio(d.celular())).param("usuario", d.usuario().trim())
			.param("hash", encoder.encode(d.clave())).param("rol", d.idRol()).update(llave);
		int id = llave.getKey().intValue();
		if (motorizado) {
			fichaTecnico(id, d);
		}
		return obtener(id);
	}

	@PutMapping("/usuarios/{id}")
	@Transactional
	public Usuario actualizar(@PathVariable int id, @Valid @RequestBody UsuarioDatos d) {
		if (id == Sesion.actual().usuarioId() && Boolean.FALSE.equals(d.activo())) {
			throw new NegocioException("No puede desactivar su propio usuario.");
		}
		if (id == Sesion.actual().usuarioId() && d.idRol() != 1) {
			throw new NegocioException("No puede quitarse a sí mismo el rol de Superusuario.");
		}
		boolean motorizado = validarRol(d);
		jdbc.sql("""
			UPDATE usuarios SET nombre = :nombre, cedula = :cedula, cargo = :cargo, email = :email, celular = :celular,
			       usuario = :usuario, id_rol = :rol, estado = :estado WHERE usuario_id = :id""")
			.param("nombre", d.nombre().trim()).param("cedula", vacio(d.cedula())).param("cargo", vacio(d.cargo()))
			.param("email", vacio(d.email())).param("celular", vacio(d.celular())).param("usuario", d.usuario().trim())
			.param("rol", d.idRol()).param("estado", Boolean.FALSE.equals(d.activo()) ? "inactivo" : "activo")
			.param("id", id).update();
		if (d.clave() != null && !d.clave().isBlank()) {
			if (d.clave().length() < 6) {
				throw new NegocioException("La contraseña temporal debe tener al menos 6 caracteres.");
			}
			// Contraseña reiniciada por el administrador: el usuario la cambia en su siguiente ingreso.
			jdbc.sql("UPDATE usuarios SET password_hash = :h, forzar_cambio_pwd = 1, pwd_ultimo_cambio = NOW() WHERE usuario_id = :id")
				.param("h", encoder.encode(d.clave())).param("id", id).update();
		}
		if (motorizado) {
			fichaTecnico(id, d);
		}
		return obtener(id);
	}

	/** El rol debe ser uno de los cuatro activos. Devuelve si es el del motorizado (el que usa el celular). */
	private boolean validarRol(UsuarioDatos d) {
		Integer motorizado = jdbc.sql("""
			SELECT EXISTS (SELECT 1 FROM rol_permiso rp WHERE rp.id_rol = r.id_rol AND rp.codigo_permiso = 'CAMPO')
			FROM rol r WHERE r.id_rol = :r AND r.estado = 1""").param("r", d.idRol()).query(Integer.class).optional()
			.orElseThrow(() -> new NegocioException("Elija uno de los roles de la lista."));
		return motorizado == 1;
	}

	/** Crea o actualiza la ficha de técnico del motorizado con su delegación (sin ella no entra a las rutas). */
	private void fichaTecnico(int usuarioId, UsuarioDatos d) {
		Integer idTecnico = jdbc.sql("SELECT id_tecnico FROM tecnico WHERE usuario_id = :u ORDER BY estado DESC, id_tecnico LIMIT 1")
			.param("u", usuarioId).query(Integer.class).optional().orElse(null);
		if (idTecnico == null) {
			if (d.idDelegacion() == null) {
				throw new NegocioException("Elija la delegación donde trabaja el motorizado.");
			}
			jdbc.sql("INSERT INTO tecnico (nombre_tecnico, usuario_id, estado, id_delegacion) VALUES (:n, :u, 1, :d)")
				.param("n", d.nombre().trim().toUpperCase()).param("u", usuarioId).param("d", d.idDelegacion()).update();
		}
		else {
			jdbc.sql("UPDATE tecnico SET estado = 1, id_delegacion = COALESCE(:d, id_delegacion) WHERE id_tecnico = :t")
				.param("d", d.idDelegacion()).param("t", idTecnico).update();
		}
	}

	@DeleteMapping("/usuarios/{id}")
	public void desactivar(@PathVariable int id) {
		if (id == Sesion.actual().usuarioId()) {
			throw new NegocioException("No puede desactivar su propio usuario.");
		}
		jdbc.sql("UPDATE usuarios SET estado = 'inactivo' WHERE usuario_id = :id").param("id", id).update();
	}

	// ------------------------------------------------------------------ roles fijos
	@GetMapping("/permisos")
	public List<Permiso> permisos() {
		return jdbc.sql("SELECT codigo, descripcion FROM permiso ORDER BY codigo").query(Permiso.class).list();
	}

	/** Los cuatro roles con lo que puede hacer cada uno (no se editan: ver V7). */
	@GetMapping("/roles")
	public List<Rol> roles() {
		record Fila(int idRol, String nombre, String descripcion, int usuarios) {
		}
		List<Fila> filas = jdbc.sql("""
			SELECT r.id_rol, r.nombre, r.descripcion,
			       (SELECT COUNT(*) FROM usuarios u WHERE u.id_rol = r.id_rol AND u.estado = 'activo') AS usuarios
			FROM rol r WHERE r.estado = 1 ORDER BY r.orden, r.id_rol""").query(Fila.class).list();
		return filas.stream().map(f -> {
			List<Permiso> permisos = jdbc.sql("""
				SELECT pe.codigo, pe.descripcion FROM rol_permiso rp JOIN permiso pe ON pe.codigo = rp.codigo_permiso
				WHERE rp.id_rol = :r ORDER BY FIELD(pe.codigo, 'PLANEACION', 'SEGUIMIENTO', 'ORDENES', 'INVENTARIO', 'REMISIONES',
				  'TRANSPORTE', 'REPORTES', 'PERSONAL', 'CONFIGURACION', 'COSTOS', 'ADMINISTRACION', 'CAMPO')""").param("r", f.idRol()).query(Permiso.class).list();
			return new Rol(f.idRol(), f.nombre(), f.descripcion(), permisos.stream().anyMatch(x -> x.codigo().equals("CAMPO")),
				f.usuarios(), permisos);
		}).toList();
	}

	// ------------------------------------------------------------------ firma del técnico (va en el PDF del servicio)
	@PostMapping("/tecnicos/{id}/firma")
	public void subirFirma(@PathVariable int id, @RequestPart("archivo") MultipartFile archivo) {
		List<String> filas = jdbc.sql("SELECT ruta_firma FROM tecnico WHERE id_tecnico = :id").param("id", id)
			.query((rs, i) -> rs.getString(1)).list();
		if (filas.isEmpty()) {
			throw NegocioException.noEncontrado("El técnico");
		}
		String anterior = filas.get(0);
		String ruta = almacenamiento.guardarImagenEn("firmas_tecnicos", "tecnico_" + id, archivo);
		jdbc.sql("UPDATE tecnico SET ruta_firma = :r WHERE id_tecnico = :id").param("r", ruta).param("id", id).update();
		if (anterior != null && anterior.startsWith("uploads/firmas_tecnicos/")) {
			almacenamiento.eliminar(anterior);
		}
	}

	private static String vacio(String s) {
		return s == null || s.isBlank() ? null : s.trim();
	}
}
