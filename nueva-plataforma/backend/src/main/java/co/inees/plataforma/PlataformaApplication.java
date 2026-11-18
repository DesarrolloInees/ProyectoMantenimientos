package co.inees.plataforma;

import java.util.TimeZone;

import org.springframework.boot.SpringApplication;
import org.springframework.boot.autoconfigure.SpringBootApplication;
import org.springframework.boot.context.properties.ConfigurationPropertiesScan;
import org.springframework.scheduling.annotation.EnableScheduling;

@SpringBootApplication
@ConfigurationPropertiesScan
@EnableScheduling
public class PlataformaApplication {

	public static void main(String[] args) {
		// Las horas automáticas (entrada, salida, espera) se toman en hora de Colombia.
		TimeZone.setDefault(TimeZone.getTimeZone("America/Bogota"));
		SpringApplication.run(PlataformaApplication.class, args);
	}
}
