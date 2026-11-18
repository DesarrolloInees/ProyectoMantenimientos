package co.inees.plataforma.comun;

import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.List;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.ObjectProvider;
import org.springframework.http.MediaType;
import org.springframework.mail.javamail.JavaMailSender;
import org.springframework.mail.javamail.MimeMessageHelper;
import org.springframework.stereotype.Component;
import org.springframework.util.LinkedMultiValueMap;
import org.springframework.web.client.RestClient;

import co.inees.plataforma.config.AppProperties;
import co.inees.plataforma.config.NegocioException;

/** Envía correos (SMTP) y mensajes de Telegram. Si el canal no está configurado, lo dice en vez de fallar en silencio. */
@Component
public class Notificador {

	private static final Logger log = LoggerFactory.getLogger(Notificador.class);

	private final ObjectProvider<JavaMailSender> correo;
	private final AppProperties props;
	private final RestClient http = RestClient.create();

	public Notificador(ObjectProvider<JavaMailSender> correo, AppProperties props) {
		this.correo = correo;
		this.props = props;
	}

	public boolean correoConfigurado() {
		return correo.getIfAvailable() != null && props.correo() != null && props.correo().remitente() != null
			&& !props.correo().remitente().isBlank();
	}

	/** Envía un correo HTML. Lanza NegocioException si no hay SMTP configurado o falla el envío. */
	public void correo(List<String> destinatarios, String asunto, String html) {
		JavaMailSender sender = correo.getIfAvailable();
		if (sender == null || !correoConfigurado()) {
			throw new NegocioException("El correo no está configurado en el servidor (SMTP_HOST, SMTP_USER, SMTP_PASS).");
		}
		try {
			var mensaje = sender.createMimeMessage();
			var helper = new MimeMessageHelper(mensaje, "UTF-8");
			helper.setFrom(props.correo().remitente(), props.correo().nombreRemitente());
			helper.setTo(destinatarios.stream().map(String::trim).filter(s -> !s.isEmpty()).toArray(String[]::new));
			helper.setSubject(asunto);
			helper.setText(html + "<br/><br/><hr/><small>Mensaje automático generado el "
				+ LocalDateTime.now().format(DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm")) + "</small>", true);
			sender.send(mensaje);
		}
		catch (Exception e) {
			log.error("No se pudo enviar el correo '{}'", asunto, e);
			throw new NegocioException("No se pudo enviar el correo: " + e.getMessage());
		}
	}

	public boolean telegramConfigurado() {
		return props.telegram() != null && props.telegram().configurado();
	}

	/** Envía un mensaje HTML al grupo de Telegram configurado. Devuelve false si no está configurado o falla. */
	public boolean telegram(String mensajeHtml) {
		if (!telegramConfigurado()) {
			return false;
		}
		try {
			var form = new LinkedMultiValueMap<String, String>();
			form.add("chat_id", props.telegram().chatId());
			form.add("text", mensajeHtml);
			form.add("parse_mode", "HTML");
			http.post().uri("https://api.telegram.org/bot{token}/sendMessage", props.telegram().token())
				.contentType(MediaType.APPLICATION_FORM_URLENCODED).body(form).retrieve().toBodilessEntity();
			return true;
		}
		catch (Exception e) {
			log.warn("No se pudo enviar el mensaje de Telegram: {}", e.getMessage());
			return false;
		}
	}
}
