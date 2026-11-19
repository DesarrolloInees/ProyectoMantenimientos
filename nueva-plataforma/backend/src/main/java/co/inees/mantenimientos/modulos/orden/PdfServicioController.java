package co.inees.mantenimientos.modulos.orden;

import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Descarga;
import co.inees.mantenimientos.core.Req;

/** Port de pdfServicioControlador.php (PDF individual de un servicio, en línea) */
@RestController
@RequestMapping("/api/pdfServicio")
public class PdfServicioController {

    private final ServicioPdfGenerator gen;

    public PdfServicioController(ServicioPdfGenerator gen) {
        this.gen = gen;
    }

    @GetMapping(params = { "accion" })
    public ResponseEntity<?> generar(Req req) {
        return generarPdf(req);
    }

    @GetMapping
    public ResponseEntity<?> generarSinAccion(Req req) {
        return generarPdf(req);
    }

    private ResponseEntity<?> generarPdf(Req req) {
        if (Conv.empty(req.get("id"))) return Descarga.html("Error: ID de orden no proporcionado.");
        int idOrden = req.intv("id");
        int rol = req.sesion().nivel();
        if (rol == 4) {
            String v = gen.obtenerParametro("meses_restriccion_prosegur");
            int meses = !Conv.empty(v) ? Conv.toInt(v) : 1;
            if (!gen.puedeVerOrden(idOrden, rol, meses)) {
                return Descarga.html("<div style='text-align:center; margin-top:50px; font-family:sans-serif;'>"
                        + "<h2 style='color:red;'>Acceso Denegado</h2>"
                        + "<p>No tienes los permisos suficientes para ver esta orden de servicio porque aún está en periodo de restricción.</p></div>");
            }
        }
        var r = gen.generarPdf(idOrden, null);
        if (!r.ok()) return Descarga.html("<h1>Error generando PDF del Servicio</h1><p>" + Conv.h(r.error()) + "</p>");
        return Descarga.pdf(r.contenido(), r.nombreArchivo(), true);
    }
}
