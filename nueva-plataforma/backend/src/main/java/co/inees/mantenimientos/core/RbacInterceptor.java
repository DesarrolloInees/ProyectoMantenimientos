package co.inees.mantenimientos.core;

import java.io.IOException;
import java.util.LinkedHashMap;
import java.util.Map;

import org.springframework.stereotype.Component;
import org.springframework.web.servlet.HandlerInterceptor;

import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;

/**
 * Aplica a /api/{pagina}/** las mismas reglas de index.php:
 *  A. páginas públicas
 *  B. logout
 *  C. no logueado y no pública -> login (401)
 *  D. logueado e intenta login -> inicio
 *  RBAC: rutas / rol_permisos
 */
@Component
public class RbacInterceptor implements HandlerInterceptor {

    private final Rbac rbac;

    public RbacInterceptor(Rbac rbac) {
        this.rbac = rbac;
    }

    @Override
    public boolean preHandle(HttpServletRequest request, HttpServletResponse response, Object handler) throws IOException {
        String uri = request.getRequestURI().substring(request.getContextPath().length());
        if (!uri.startsWith("/api/")) return true;
        String resto = uri.substring(5);
        String pagina = resto.contains("/") ? resto.substring(0, resto.indexOf('/')) : resto;

        // Endpoints de infraestructura del frontend (no existen como página en PHP)
        if (pagina.equals("sesion") || pagina.equals("logout")) return true;

        Sesion sesion = new Sesion(request.getSession(true));
        boolean logueado = sesion.logueado();

        if (!logueado && !Rbac.esPublica(pagina)) {
            escribir(response, 401, Map.of("status", "error", "message", "Sesión no iniciada", "redirect", "login"));
            return false;
        }
        if (logueado && pagina.equals("login")) {
            escribir(response, 200, Map.of("status", "success", "redirect", "inicio"));
            return false;
        }
        String ctrl;
        try {
            ctrl = rbac.controladorPara(pagina, sesion);
        } catch (Exception e) {
            escribir(response, 500, Map.of("status", "error", "message", "Error crítico en el Router RBAC: " + e.getMessage()));
            return false;
        }
        if (ctrl == null) {
            Map<String, Object> r = new LinkedHashMap<>();
            r.put("status", "error");
            r.put("success", false);
            r.put("message", "Página no encontrada o acceso denegado.");
            escribir(response, 404, r);
            return false;
        }
        request.setAttribute("inees.pagina", pagina);
        return true;
    }

    private void escribir(HttpServletResponse response, int code, Map<String, ?> body) throws IOException {
        response.setStatus(code);
        response.setContentType("application/json;charset=UTF-8");
        response.getWriter().write(Req.jsonEncode(body));
    }
}
