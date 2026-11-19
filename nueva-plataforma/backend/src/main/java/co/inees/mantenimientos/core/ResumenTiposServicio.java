package co.inees.mantenimientos.core;

import java.util.LinkedHashMap;
import java.util.List;
import java.util.Locale;
import java.util.Map;

/** Port de app/helpers/resumenTiposServicio.php */
public final class ResumenTiposServicio {

    private ResumenTiposServicio() {}

    public static String normalizar(Object texto) {
        String t = Conv.str(texto).trim().toUpperCase(Locale.ROOT);
        return t.replace('Á', 'A').replace('É', 'E').replace('Í', 'I').replace('Ó', 'O').replace('Ú', 'U')
                .replace('Ü', 'U').replace('Ñ', 'N');
    }

    public static String clasificar(Object nombreTipo) {
        String t = normalizar(nombreTipo);
        if (t.isEmpty() || t.equals("SIN ESPECIFICAR") || t.equals("N/A")) return "otros";
        if (t.contains("FALLID")) return "fallido";
        if (t.contains("NUEVA ARQUITECTURA") || t.contains("NUEVAARQUITECTURA")) return "nueva_arquitectura";
        if (t.contains("PROFUNDO") || t.contains("COMPLETO")) return "profundo";
        if (t.contains("CORRECTIVO") || t.contains("REPARACION")) return "correctivo";
        if (t.contains("PREVENTIVO") || t.contains("BASICO")) return "basico";
        return "otros";
    }

    public static Map<String, Object> vacio() {
        Map<String, Object> r = new LinkedHashMap<>();
        r.put("basico", 0);
        r.put("profundo", 0);
        r.put("correctivo", 0);
        r.put("fallido", 0);
        r.put("nueva_arquitectura", 0);
        r.put("otros", 0);
        r.put("total", 0);
        return r;
    }

    public static Map<String, Object> resumir(List<Map<String, Object>> filas) {
        Map<String, Object> r = vacio();
        if (filas == null) return r;
        for (Map<String, Object> f : filas) {
            String cat = clasificar(f.get("tipo_mantenimiento"));
            if (!r.containsKey(cat)) cat = "otros";
            r.put(cat, Conv.toInt(r.get(cat)) + 1);
            r.put("total", Conv.toInt(r.get("total")) + 1);
        }
        return r;
    }
}
