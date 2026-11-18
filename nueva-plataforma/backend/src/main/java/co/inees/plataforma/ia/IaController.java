package co.inees.plataforma.ia;

import java.time.Duration;
import java.time.Instant;
import java.util.List;
import java.util.Map;
import java.util.concurrent.ConcurrentHashMap;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.http.client.SimpleClientHttpRequestFactory;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.client.RestClient;

import co.inees.plataforma.config.AppProperties;
import co.inees.plataforma.config.NegocioException;
import jakarta.validation.Valid;
import jakarta.validation.constraints.NotBlank;

/**
 * Mejora la redacción de las actividades de un servicio con IA (Groq), conservando toda la información.
 * Mismo prompt, modelos en cascada y caché por texto que la aplicación PHP.
 */
@RestController
@RequestMapping("/api/ia")
public class IaController {

	public record Peticion(@NotBlank String texto) {
	}

	public record Respuesta(String textoMejorado, boolean cache) {
	}

	private static final Logger log = LoggerFactory.getLogger(IaController.class);
	private static final String SISTEMA = "Eres un editor técnico experto en mantenimiento industrial. Conservas el 100% de la "
		+ "información del reporte, nunca resumes ni recortas, amplías solo lo que ya está escrito y respetas cifras, seriales "
		+ "y códigos tal cual aparecen.";

	private final AppProperties props;
	private final RestClient http;
	private final Map<String, Map.Entry<Instant, String>> cache = new ConcurrentHashMap<>();

	public IaController(AppProperties props) {
		this.props = props;
		var fabrica = new SimpleClientHttpRequestFactory();
		fabrica.setConnectTimeout(Duration.ofSeconds(5));
		fabrica.setReadTimeout(Duration.ofSeconds(15));
		this.http = RestClient.builder().requestFactory(fabrica).build();
	}

	@PostMapping("/mejorar-texto")
	@SuppressWarnings("unchecked")
	public Respuesta mejorar(@Valid @RequestBody Peticion p) {
		String original = p.texto().trim();
		var enCache = cache.get(original);
		if (enCache != null && enCache.getKey().isAfter(Instant.now().minus(Duration.ofHours(24)))) {
			return new Respuesta(enCache.getValue(), true);
		}
		List<String> claves = props.ia() == null || props.ia().claves() == null ? List.of()
			: props.ia().claves().stream().filter(c -> c != null && !c.isBlank()).toList();
		if (claves.isEmpty()) {
			throw new NegocioException("La IA no está configurada en el servidor (GROQ_API_KEY).");
		}
		int total = original.length();
		String prompt = """
			TAREA: Reorganiza y mejora la redacción del siguiente reporte técnico de mantenimiento (español).
			REGLAS OBLIGATORIAS:
			1. CONSERVA EL 100%% DE LA INFORMACIÓN: NO resumas, NO omitas y NO elimines datos, medidas, marcas, modelos, seriales, códigos de error, repuestos, cantidades, tiempos, nombres, puntos, remisiones ni observaciones.
			2. EXTENSIÓN: la respuesta debe tener al menos %d caracteres y como máximo unos %d. Si una idea está abreviada, telegráfica o incompleta, AMPLÍALA con detalle técnico; si ya está completa, reordénala y redáctala mejor sin encogerla de más.
			3. CONSERVA CIFRAS Y CÓDIGOS TAL CUAL: números, cantidades, seriales, modelos, versiones, medidas y códigos de error se escriben en dígitos exactamente como aparecen, nunca con palabras ni aproximaciones.
			4. Corrige ortografía, tildes, puntuación y gramática, y usa lenguaje técnico profesional.
			5. Reordena la información de forma lógica y coherente (por actividad, por equipo/sistema o cronológicamente). Puedes unir o separar ideas y mejorar la redacción, pero NUNCA descartar ninguna.
			6. NO INVENTES INFORMACIÓN: amplía solo lo que ya estaba escrito; NO agregues equipos, fallas, procedimientos, pruebas, repuestos ni datos que no aparezcan en el texto original.
			7. Devuelve SOLO el texto final del reporte: sin títulos, sin listas de reglas, sin comillas y sin explicaciones.
			Texto original (%d caracteres):
			""".formatted((int) Math.floor(total * 0.8), (int) Math.ceil(total * 1.5), total);
		int maxTokens = Math.min(Math.max(900, (int) Math.ceil(total / 2.2) * 2 + 400), 4096);
		Instant limite = Instant.now().plusSeconds(25);
		int i = 0;
		for (String modelo : props.ia().modelos()) {
			if (Instant.now().isAfter(limite)) {
				break;
			}
			String clave = claves.get(i++ % claves.size());
			try {
				Map<String, Object> r = http.post().uri(props.ia().url()).header("Authorization", "Bearer " + clave)
					.body(Map.of("model", modelo, "temperature", 0.1, "max_tokens", maxTokens,
						"messages", List.of(Map.of("role", "system", "content", SISTEMA), Map.of("role", "user", "content", prompt + original))))
					.retrieve().body(Map.class);
				var choices = (List<Map<String, Object>>) r.get("choices");
				String texto = ((String) ((Map<String, Object>) choices.get(0).get("message")).get("content")).trim();
				if (!texto.isEmpty()) {
					cache.put(original, Map.entry(Instant.now(), texto));
					return new Respuesta(texto, false);
				}
			}
			catch (RuntimeException e) {
				log.warn("IA {} falló: {}", modelo, e.getMessage());
			}
		}
		throw new NegocioException("La IA no respondió a tiempo. Intente de nuevo en unos segundos.");
	}
}
