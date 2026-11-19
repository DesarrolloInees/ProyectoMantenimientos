package co.inees.mantenimientos;

import java.util.TimeZone;

import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;
import org.springframework.scheduling.annotation.EnableScheduling;

/**
 * Punto de entrada (equivalente a index.php).
 * date_default_timezone_set('America/Bogota') -> zona horaria por defecto de la JVM.
 */
@SpringBootApplication
@EnableScheduling
public class InneesApplication {

    public static void main(String[] args) {
        TimeZone.setDefault(TimeZone.getTimeZone("America/Bogota"));
        SpringApplication.run(InneesApplication.class, args);
    }
}
