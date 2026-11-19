package co.inees.mantenimientos.core;

import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.GetMapping;

/**
 * Las URLs amigables de la versión PHP (/inicio, /ordenDetalle/2025-12-10, ...)
 * se entregan al router de Angular (index.html).
 */
@Controller
public class SpaController {

    @GetMapping({"/", "/{a:(?!api$)[^\\.]*}", "/{a:(?!api$)[^\\.]*}/{b:[^\\.]*}", "/{a:(?!api$)[^\\.]*}/{b:[^\\.]*}/{c:[^\\.]*}"})
    public String spa() {
        return "forward:/index.html";
    }
}
