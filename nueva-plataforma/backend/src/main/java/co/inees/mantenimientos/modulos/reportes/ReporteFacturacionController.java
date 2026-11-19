package co.inees.mantenimientos.modulos.reportes;

import java.util.Map;

import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RestController;

/**
 * Port de reporteFacturacionControlador.php: la vista procesa el Excel subido en el navegador.
 * (reporteFacturacionPdfControlador.php no tiene ruta en la tabla `rutas` e incluye una plantilla
 * inexistente, por lo que en PHP no es alcanzable; no se expone.)
 */
@RestController
public class ReporteFacturacionController {

    @GetMapping(value = "/api/reporteFacturacion", params = "accion=index")
    public Map<String, Object> index() {
        return Map.of("titulo", "Analizador de Facturación");
    }
}
