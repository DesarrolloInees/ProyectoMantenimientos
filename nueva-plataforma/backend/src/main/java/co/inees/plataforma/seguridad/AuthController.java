package co.inees.plataforma.seguridad;

import java.security.SecureRandom;
import java.time.Instant;
import java.time.LocalDateTime;
import java.time.temporal.ChronoUnit;
import java.util.List;
import java.util.regex.Pattern;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.http.HttpStatus;
import org.springframework.jdbc.core.simple.JdbcClient;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.security.oauth2.jose.jws.MacAlgorithm;
import org.springframework.security.oauth2.jwt.JwsHeader;
import org.springframework.security.oauth2.jwt.JwtClaimsSet;
import org.springframework.security.oauth2.jwt.JwtEncoder;
import org.springframework.security.oauth2.jwt.JwtEncoderParameters;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import co.inees.plataforma.comun.Notificador;
import co.inees.plataforma.config.AppProperties;
import co.inees.plataforma.config.NegocioException;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.validation.Valid;
import jakarta.validation.constraints.Email;
import jakarta.validation.constraints.NotBlank;

@RestController
@RequestMapping("/api/auth")
public class AuthController {

	public record LoginPeticion(@NotBlank String usuario, @NotBlank String clave) {
	}

	public record LoginRespuesta(String token, PerfilRespuesta perfil) {
	}

	public record PerfilRespuesta(int usuarioId, String nombre, String cargo, int rol, String nombreRol,
		Integer idTecnico, List<String> permisos, boolean debeCambiarClave, String ultimoAcceso) {
	}

	public record CambioClave(@NotBlank String claveActual, @NotBlank String claveNueva) {
	}

	public record SolicitudCodigo(@NotBlank @Email String email) {
	}

	public record Restablecer(@NotBlank @Email String email, @NotBlank String codigo, @NotBlank String claveNueva) {
	}

	record FilaUsuario(int usuarioId, String nombre, String cargo, String passwordHash, boolean forzarCambioPwd,
		int nivelAcceso, String nombreRol, boolean rolActivo, String estado, LocalDateTime pwdUltimoCambio,
		Integer idTecnico) {
	}

	/** Misma regla de la aplicación PHP: 8+ caracteres, mayúscula, minúscula, número y símbolo. */
	static final Pattern CLAVE_SEGURA = Pattern.compile("^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^A-Za-z\\d]).{8,}$");

	private static final Logger log = LoggerFactory.getLogger(AuthController.class);
	private static final SecureRandom AZAR = new SecureRandom();

	private final JdbcClient jdbc;
	private final PasswordEncoder encoder;
	private final JwtEncoder jwtEncoder;
	private final AppProperties props;
	private final Notificador notificador;

	public AuthController(JdbcClient jdbc, PasswordEncoder encoder, JwtEncoder jwtEncoder, AppProperties props,
		Notificador notificador) {
		this.jdbc = jdbc;
		this.encoder = encoder;
		this.jwtEncoder = jwtEncoder;
		this.props = props;
		this.notificador = notificador;
	}

	@PostMapping("/login")
	@Transactional
	public LoginRespuesta login(@Valid @RequestBody LoginPeticion p, HttpServletRequest request) {
		FilaUsuario u = buscar("u.usuario = :valor", p.usuario().trim());
		if (u == null || u.passwordHash() == null || !encoder.matches(p.clave(), u.passwordHash())) {
			throw new NegocioException(HttpStatus.UNAUTHORIZED, "Usuario o contraseña incorrectos.");
		}
		if (!"activo".equalsIgnoreCase(u.estado()) || !u.rolActivo()) {
			throw new NegocioException(HttpStatus.FORBIDDEN, "El usuario no tiene acceso a la plataforma.");
		}
		String anterior = jdbc.sql("""
			SELECT CONCAT(DATE_FORMAT(fecha, '%d/%m/%Y'), ' a las ', DATE_FORMAT(hora, '%H:%i')) FROM login
			WHERE usuario_id = :id ORDER BY fecha DESC, hora DESC LIMIT 1""")
			.param("id", u.usuarioId()).query(String.class).optional().orElse(null);
		jdbc.sql("UPDATE usuarios SET ultimo_acceso = NOW() WHERE usuario_id = :id").param("id", u.usuarioId()).update();
		jdbc.sql("INSERT INTO login (usuario_id, fecha, hora, usuario, ip) VALUES (:id, CURDATE(), CURTIME(), :nombre, :ip)")
			.param("id", u.usuarioId()).param("nombre", u.nombre()).param("ip", request.getRemoteAddr()).update();

		PerfilRespuesta perfil = perfil(u, anterior);
		Instant ahora = Instant.now();
		JwtClaimsSet claims = JwtClaimsSet.builder()
			.issuer("plataforma-inees")
			.subject(String.valueOf(u.usuarioId()))
			.issuedAt(ahora)
			.expiresAt(ahora.plus(props.jwt().horasValidez(), ChronoUnit.HOURS))
			.claim("nombre", u.nombre())
			.claim("rol", u.nivelAcceso())
			.claim("permisos", perfil.permisos())
			.claims(c -> {
				if (u.idTecnico() != null) {
					c.put("idTecnico", u.idTecnico());
				}
			})
			.build();
		String token = jwtEncoder.encode(JwtEncoderParameters.from(JwsHeader.with(MacAlgorithm.HS256).build(), claims))
			.getTokenValue();
		return new LoginRespuesta(token, perfil);
	}

	@GetMapping("/perfil")
	public PerfilRespuesta perfil() {
		FilaUsuario u = buscar("u.usuario_id = :valor", Sesion.actual().usuarioId());
		if (u == null) {
			throw NegocioException.noEncontrado("El usuario");
		}
		return perfil(u, null);
	}

	@PostMapping("/cambiar-clave")
	public void cambiarClave(@Valid @RequestBody CambioClave c) {
		FilaUsuario u = buscar("u.usuario_id = :valor", Sesion.actual().usuarioId());
		if (u == null || !encoder.matches(c.claveActual(), u.passwordHash())) {
			throw new NegocioException("La contraseña actual no es correcta.");
		}
		exigirClaveSegura(c.claveNueva());
		if (encoder.matches(c.claveNueva(), u.passwordHash())) {
			throw new NegocioException("La contraseña nueva debe ser distinta de la actual.");
		}
		guardarClave(u.usuarioId(), c.claveNueva());
	}

	/** Envía un código de 6 dígitos al correo del usuario (válido 15 minutos). Responde igual exista o no el correo. */
	@PostMapping("/recuperar")
	@Transactional
	public void solicitarCodigo(@Valid @RequestBody SolicitudCodigo s) {
		String email = s.email().trim();
		boolean existe = jdbc.sql("SELECT COUNT(*) FROM usuarios WHERE email = :e AND estado = 'activo'")
			.param("e", email).query(Integer.class).single() > 0;
		if (!existe) {
			log.info("Solicitud de recuperación para un correo no registrado");
			return;
		}
		String codigo = String.format("%06d", 100000 + AZAR.nextInt(900000));
		jdbc.sql("UPDATE password_reset SET usado = 1 WHERE usuario_email = :e").param("e", email).update();
		jdbc.sql("""
			INSERT INTO password_reset (usuario_email, codigo_hash, expira_en, usado)
			VALUES (:e, :h, DATE_ADD(NOW(), INTERVAL 15 MINUTE), 0)""")
			.param("e", email).param("h", encoder.encode(codigo)).update();
		notificador.correo(List.of(email), "Tu código de recuperación de INEES Mantenimientos",
			"Hola,<br/><br/>Tu código para recuperar la contraseña es: <b style='font-size:18px'>" + codigo
				+ "</b><br/>Vence en 15 minutos. Si no lo pediste, ignora este mensaje.");
	}

	@PostMapping("/restablecer")
	@Transactional
	public void restablecer(@Valid @RequestBody Restablecer r) {
		record Codigo(int id, String codigoHash, boolean usado, boolean vencido) {
		}
		String email = r.email().trim();
		Codigo c = jdbc.sql("""
			SELECT id, codigo_hash, usado = 1 AS usado, expira_en <= NOW() AS vencido FROM password_reset
			WHERE usuario_email = :e ORDER BY id DESC LIMIT 1""").param("e", email).query(Codigo.class).optional()
			.orElseThrow(() -> new NegocioException("No hay una solicitud de recuperación para ese correo."));
		if (c.usado()) {
			throw new NegocioException("Este código ya se usó. Solicite uno nuevo.");
		}
		if (c.vencido()) {
			throw new NegocioException("El código venció. Solicite uno nuevo.");
		}
		if (!encoder.matches(r.codigo().trim(), c.codigoHash())) {
			throw new NegocioException("El código no es correcto.");
		}
		exigirClaveSegura(r.claveNueva());
		Integer idUsuario = jdbc.sql("SELECT usuario_id FROM usuarios WHERE email = :e AND estado = 'activo' LIMIT 1")
			.param("e", email).query(Integer.class).optional()
			.orElseThrow(() -> NegocioException.noEncontrado("El usuario"));
		guardarClave(idUsuario, r.claveNueva());
		jdbc.sql("UPDATE password_reset SET usado = 1 WHERE id = :id").param("id", c.id()).update();
	}

	static void exigirClaveSegura(String clave) {
		if (clave == null || !CLAVE_SEGURA.matcher(clave).matches()) {
			throw new NegocioException(
				"La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula, un número y un símbolo.");
		}
	}

	private void guardarClave(int idUsuario, String clave) {
		jdbc.sql("""
			UPDATE usuarios SET password_hash = :hash, forzar_cambio_pwd = 0, pwd_ultimo_cambio = NOW()
			WHERE usuario_id = :id""")
			.param("hash", encoder.encode(clave)).param("id", idUsuario).update();
	}

	private FilaUsuario buscar(String condicion, Object valor) {
		return jdbc.sql("""
			SELECT u.usuario_id, u.nombre, u.cargo, u.password_hash, u.forzar_cambio_pwd, u.id_rol AS nivel_acceso,
			       r.nombre AS nombre_rol, r.estado = 1 AS rol_activo, u.estado, u.pwd_ultimo_cambio,
			       (SELECT t.id_tecnico FROM tecnico t WHERE t.usuario_id = u.usuario_id AND t.estado = 1 LIMIT 1) AS id_tecnico
			FROM usuarios u
			JOIN rol r ON r.id_rol = u.id_rol
			WHERE """ + " " + condicion)
			.param("valor", valor)
			.query(FilaUsuario.class).optional().orElse(null);
	}

	private PerfilRespuesta perfil(FilaUsuario u, String ultimoAcceso) {
		List<String> permisos = jdbc.sql("SELECT codigo_permiso FROM rol_permiso WHERE id_rol = :rol ORDER BY 1")
			.param("rol", u.nivelAcceso()).query(String.class).list();
		// password_vigencia_dias existe en parametros pero la aplicación PHP nunca lo aplicó; se respeta igual:
		// solo obliga el cambio cuando el administrador marca forzar_cambio_pwd (usuario nuevo o clave reiniciada).
		return new PerfilRespuesta(u.usuarioId(), u.nombre(), u.cargo(), u.nivelAcceso(), u.nombreRol(), u.idTecnico(),
			permisos, u.forzarCambioPwd(), ultimoAcceso);
	}
}
