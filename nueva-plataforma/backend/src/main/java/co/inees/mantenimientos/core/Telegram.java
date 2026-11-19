package co.inees.mantenimientos.core;

import java.net.URI;
import java.net.URLEncoder;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.nio.charset.StandardCharsets;
import java.time.Duration;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.stereotype.Component;

/**
 * Envío de mensajes a Telegram (equivalente a enviarAlertaTelegram() con curl en PHP).
 * Token y chat se configuran con TELEGRAM_BOT_TOKEN / TELEGRAM_CHAT_ID.
 */
@Component
public class Telegram {

    private static final Logger log = LoggerFactory.getLogger(Telegram.class);
    private final String token;
    private final String chatId;
    private final HttpClient http = HttpClient.newBuilder().connectTimeout(Duration.ofSeconds(5)).build();

    public Telegram(@Value("${inees.telegram-bot-token:}") String token, @Value("${inees.telegram-chat-id:}") String chatId) {
        this.token = token;
        this.chatId = chatId;
    }

    public boolean configurado() {
        return token != null && !token.isBlank() && chatId != null && !chatId.isBlank();
    }

    public String enviar(String mensajeHtml) {
        return enviar(chatId, mensajeHtml);
    }

    public String enviar(String chat, String mensajeHtml) {
        if (token == null || token.isBlank() || chat == null || chat.isBlank()) {
            log.warn("Telegram no configurado (TELEGRAM_BOT_TOKEN / TELEGRAM_CHAT_ID); mensaje no enviado.");
            return null;
        }
        try {
            String cuerpo = "chat_id=" + URLEncoder.encode(chat, StandardCharsets.UTF_8)
                    + "&text=" + URLEncoder.encode(mensajeHtml, StandardCharsets.UTF_8)
                    + "&parse_mode=HTML";
            HttpRequest rq = HttpRequest.newBuilder(URI.create("https://api.telegram.org/bot" + token + "/sendMessage"))
                    .timeout(Duration.ofSeconds(5))
                    .header("Content-Type", "application/x-www-form-urlencoded")
                    .POST(HttpRequest.BodyPublishers.ofString(cuerpo)).build();
            return http.send(rq, HttpResponse.BodyHandlers.ofString()).body();
        } catch (Exception e) {
            log.warn("Error enviando a Telegram: {}", e.getMessage());
            return null;
        }
    }
}
