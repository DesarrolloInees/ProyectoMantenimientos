package co.inees.mantenimientos.core;

import java.util.List;
import java.util.Map;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.core.io.ByteArrayResource;
import org.springframework.mail.javamail.JavaMailSender;
import org.springframework.mail.javamail.MimeMessageHelper;
import org.springframework.stereotype.Component;

import jakarta.mail.internet.MimeMessage;

/**
 * Reemplazo de PHPMailer + getConfiguracionSmtp() (app/config/conexion.php).
 * Lee SMTP_HOST, SMTP_USER, SMTP_PASS, SMTP_PORT desde variables de entorno.
 */
@Component
public class Correo {

    private final JavaMailSender sender;
    private final String usuario;
    private final String password;
    private final String nombreRemitente;

    public Correo(JavaMailSender sender,
                  @Value("${spring.mail.username:}") String usuario,
                  @Value("${spring.mail.password:}") String password,
                  @Value("${inees.mail-from-name:Sistema I-Nexis}") String nombreRemitente) {
        this.sender = sender;
        this.usuario = usuario;
        this.password = password;
        this.nombreRemitente = nombreRemitente;
    }

    public void verificarConfiguracion() {
        if (password == null || password.isEmpty()) {
            throw new IllegalStateException(
                    "Falta SMTP_PASS en variables de entorno/.env. Configúralo en el archivo .env (ver .env.example) sin commitear secretos.");
        }
    }

    public String remitente() {
        return usuario;
    }

    /** Envía un correo HTML. adjuntos: nombre -> bytes. */
    public void enviar(List<String> para, List<String> cc, String asunto, String html, String textoAlterno,
                       Map<String, byte[]> adjuntos) throws Exception {
        verificarConfiguracion();
        MimeMessage msg = sender.createMimeMessage();
        boolean multipart = adjuntos != null && !adjuntos.isEmpty() || textoAlterno != null;
        MimeMessageHelper h = new MimeMessageHelper(msg, multipart, "UTF-8");
        h.setFrom(usuario, nombreRemitente);
        h.setTo(para.toArray(new String[0]));
        if (cc != null && !cc.isEmpty()) h.setCc(cc.toArray(new String[0]));
        h.setSubject(asunto);
        if (textoAlterno != null) h.setText(textoAlterno, html);
        else h.setText(html, true);
        if (adjuntos != null) {
            for (Map.Entry<String, byte[]> a : adjuntos.entrySet()) {
                h.addAttachment(a.getKey(), new ByteArrayResource(a.getValue()));
            }
        }
        sender.send(msg);
    }

    public void enviar(String para, String asunto, String html) throws Exception {
        enviar(List.of(para), null, asunto, html, null, null);
    }
}
