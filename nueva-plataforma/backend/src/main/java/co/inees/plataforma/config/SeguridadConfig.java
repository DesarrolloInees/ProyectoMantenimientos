package co.inees.plataforma.config;

import java.nio.charset.StandardCharsets;

import javax.crypto.SecretKey;
import javax.crypto.spec.SecretKeySpec;

import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.http.HttpMethod;
import org.springframework.security.config.Customizer;
import org.springframework.security.config.annotation.web.builders.HttpSecurity;
import org.springframework.security.config.annotation.web.configurers.AbstractHttpConfigurer;
import org.springframework.security.config.http.SessionCreationPolicy;
import org.springframework.security.crypto.bcrypt.BCryptPasswordEncoder;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.security.oauth2.jose.jws.MacAlgorithm;
import org.springframework.security.oauth2.jwt.JwtDecoder;
import org.springframework.security.oauth2.jwt.JwtEncoder;
import org.springframework.security.oauth2.jwt.NimbusJwtDecoder;
import org.springframework.security.oauth2.jwt.NimbusJwtEncoder;
import org.springframework.security.oauth2.server.resource.authentication.JwtAuthenticationConverter;
import org.springframework.security.oauth2.server.resource.authentication.JwtGrantedAuthoritiesConverter;
import org.springframework.security.web.SecurityFilterChain;
import org.springframework.web.cors.CorsConfiguration;
import org.springframework.web.cors.CorsConfigurationSource;
import org.springframework.web.cors.UrlBasedCorsConfigurationSource;

import com.nimbusds.jose.jwk.source.ImmutableSecret;

/**
 * Acceso por permisos de funcionalidad (tabla rol_permiso), no por número de rol escrito en el código.
 * El token JWT lleva los permisos en el claim "permisos".
 */
@Configuration
public class SeguridadConfig {

	@Bean
	SecretKey claveJwt(AppProperties props) {
		byte[] bytes = props.jwt().secreto().getBytes(StandardCharsets.UTF_8);
		if (bytes.length < 32) {
			throw new IllegalStateException("app.jwt.secreto debe tener al menos 32 caracteres");
		}
		return new SecretKeySpec(bytes, "HmacSHA256");
	}

	@Bean
	JwtEncoder jwtEncoder(SecretKey clave) {
		return new NimbusJwtEncoder(new ImmutableSecret<>(clave));
	}

	@Bean
	JwtDecoder jwtDecoder(SecretKey clave) {
		return NimbusJwtDecoder.withSecretKey(clave).macAlgorithm(MacAlgorithm.HS256).build();
	}

	/** Verifica los hash $2y$ que generó PHP (password_hash) y genera hash compatibles con PHP. */
	@Bean
	PasswordEncoder passwordEncoder() {
		return new BCryptPasswordEncoder();
	}

	@Bean
	SecurityFilterChain cadenaSeguridad(HttpSecurity http) throws Exception {
		var permisos = new JwtGrantedAuthoritiesConverter();
		permisos.setAuthoritiesClaimName("permisos");
		permisos.setAuthorityPrefix("");
		var convertidor = new JwtAuthenticationConverter();
		convertidor.setJwtGrantedAuthoritiesConverter(permisos);

		http.csrf(AbstractHttpConfigurer::disable)
			.cors(Customizer.withDefaults())
			.sessionManagement(s -> s.sessionCreationPolicy(SessionCreationPolicy.STATELESS))
			.authorizeHttpRequests(a -> a
				.requestMatchers(HttpMethod.POST, "/api/auth/login", "/api/auth/recuperar", "/api/auth/restablecer").permitAll()
				.requestMatchers("/api/campo/**").hasAuthority("CAMPO")
				.requestMatchers("/api/cargas/**", "/api/planeacion/**").hasAuthority("PLANEACION")
				.requestMatchers(HttpMethod.PUT, "/api/maquinas/*/configuracion").hasAuthority("CONFIGURACION")
				.requestMatchers("/api/maquinas/**").hasAnyAuthority("PLANEACION", "CONFIGURACION")
				.requestMatchers("/api/seguimiento/**", "/api/rastreo/**").hasAnyAuthority("SEGUIMIENTO", "PERSONAL")
				.requestMatchers("/api/ordenes/**", "/api/servicios-pdf/**").hasAuthority("ORDENES")
				.requestMatchers("/api/inventario/**").hasAuthority("INVENTARIO")
				.requestMatchers("/api/remisiones/**").hasAuthority("REMISIONES")
				.requestMatchers("/api/personal/**").hasAuthority("PERSONAL")
				.requestMatchers("/api/transporte/**").hasAuthority("TRANSPORTE")
				.requestMatchers("/api/costos/**").hasAuthority("COSTOS")
				.requestMatchers("/api/reportes/**").hasAnyAuthority("REPORTES")
				.requestMatchers("/api/geo/**").hasAnyAuthority("PLANEACION", "ADMINISTRACION")
				// El catálogo de repuestos también lo administra Logística (el Supervisor lo tenía en PHP)
				.requestMatchers("/api/admin/repuestos", "/api/admin/repuestos/**").hasAnyAuthority("ADMINISTRACION", "INVENTARIO")
				.requestMatchers("/api/admin/**", "/api/importar/**").hasAuthority("ADMINISTRACION")
				// Nombres de usuario y roles: solo Administración (lo usa el catálogo de Técnicos). Sin esto cualquier
				// motorizado podía listar los usuarios de inicio de sesión de los administradores.
				.requestMatchers("/api/listas/usuarios").hasAuthority("ADMINISTRACION")
				.requestMatchers("/api/ia/**").hasAnyAuthority("ORDENES", "CAMPO")
				.requestMatchers("/api/**").authenticated()
				.anyRequest().permitAll())
			.oauth2ResourceServer(o -> o.jwt(j -> j.jwtAuthenticationConverter(convertidor)));
		return http.build();
	}

	@Bean
	CorsConfigurationSource corsConfigurationSource(AppProperties props) {
		var config = new CorsConfiguration();
		config.setAllowedOrigins(props.corsOrigenes());
		config.addAllowedMethod("*");
		config.addAllowedHeader("*");
		var fuente = new UrlBasedCorsConfigurationSource();
		fuente.registerCorsConfiguration("/api/**", config);
		return fuente;
	}
}
