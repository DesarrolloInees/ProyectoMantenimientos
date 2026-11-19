package co.inees.mantenimientos.modulos.reportes;

import java.time.LocalDate;
import java.util.HashSet;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.Set;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.stereotype.Component;

import co.inees.mantenimientos.core.Conv;
import co.inees.mantenimientos.core.Db;

/** Port de app/models/reportes/reporteEjecutivoModelo.php (compartido por reporteEjecutivo y generarReporte) */
@Component
public class ReporteEjecutivoModelo {

    private static final Logger log = LoggerFactory.getLogger(ReporteEjecutivoModelo.class);
    private final Db db;

    public ReporteEjecutivoModelo(Db db) {
        this.db = db;
    }

    private List<Map<String, Object>> q(String sql, String ini, String fin) {
        try {
            return db.list(sql, "inicio", ini, "fin", fin);
        } catch (Exception e) {
            log.warn("Reporte ejecutivo: {}", e.getMessage());
            return List.of();
        }
    }

    public List<Map<String, Object>> getServiciosPorDia(String ini, String fin) {
        return q("""
                SELECT fecha_visita,
                        COUNT(*) as total,
                        COUNT(DISTINCT id_tecnico) as num_tecnicos
                FROM ordenes_servicio
                WHERE fecha_visita BETWEEN :inicio AND :fin
                GROUP BY fecha_visita ORDER BY fecha_visita ASC""", ini, fin);
    }

    public List<Map<String, Object>> getDelegacionesIntervenidas(String ini, String fin) {
        return q("""
                SELECT d.nombre_delegacion, COUNT(os.id_ordenes_servicio) as total, COUNT(DISTINCT os.id_tecnico) as num_tecnicos
                FROM ordenes_servicio os
                INNER JOIN punto p ON os.id_punto = p.id_punto
                INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                GROUP BY d.nombre_delegacion
                ORDER BY total DESC LIMIT 10""", ini, fin);
    }

    public List<Map<String, Object>> getHorasVsServicios(String ini, String fin) {
        return q("""
                SELECT t.nombre_tecnico,
                       COUNT(os.id_ordenes_servicio) as total_servicios,
                       SUM(TIME_TO_SEC(os.tiempo_servicio))/3600 as total_horas
                FROM ordenes_servicio os
                INNER JOIN tecnico t ON os.id_tecnico = t.id_tecnico
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                GROUP BY t.nombre_tecnico
                ORDER BY total_servicios DESC""", ini, fin);
    }

    public List<Map<String, Object>> getPorTipoMantenimiento(String ini, String fin) {
        return q("""
                SELECT tm.nombre_completo as tipo, COUNT(*) as total
                FROM ordenes_servicio os
                INNER JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                GROUP BY tm.nombre_completo""", ini, fin);
    }

    public Map<String, Object> getDistribucionNovedades(String ini, String fin) {
        List<Map<String, Object>> r = q("""
                SELECT
                    COUNT(DISTINCT osn.id_orden_servicio) as con_novedad,
                    (COUNT(DISTINCT os.id_ordenes_servicio) - COUNT(DISTINCT osn.id_orden_servicio)) as sin_novedad
                FROM ordenes_servicio os
                LEFT JOIN orden_servicio_novedad osn ON os.id_ordenes_servicio = osn.id_orden_servicio
                WHERE os.fecha_visita BETWEEN :inicio AND :fin""", ini, fin);
        return r.isEmpty() ? Map.of() : r.get(0);
    }

    public List<Map<String, Object>> getServiciosFallidos(String ini, String fin) {
        return q("""
                SELECT em.nombre_estado, COUNT(*) as total
                FROM ordenes_servicio os
                INNER JOIN estado_maquina em ON os.id_estado_maquina = em.id_estado
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                GROUP BY em.nombre_estado""", ini, fin);
    }

    public List<Map<String, Object>> getComparativaRepuestos(String ini, String fin) {
        return q("""
                SELECT osr.origen, SUM(osr.cantidad) as total
                FROM orden_servicio_repuesto osr
                INNER JOIN ordenes_servicio os ON osr.id_orden_servicio = os.id_ordenes_servicio
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                GROUP BY osr.origen""", ini, fin);
    }

    public List<Map<String, Object>> getTiposMaquinaActivos(String ini, String fin) {
        return q("""
                SELECT DISTINCT tm.nombre_tipo_maquina
                FROM ordenes_servicio os
                INNER JOIN punto p ON os.id_punto = p.id_punto
                INNER JOIN maquina m ON p.id_punto = m.id_punto
                INNER JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                ORDER BY tm.nombre_tipo_maquina ASC""", ini, fin);
    }

    public List<Map<String, Object>> getDatosMatrizTipoMaquina(String ini, String fin) {
        return q("""
                SELECT d.nombre_delegacion, tm.nombre_tipo_maquina, COUNT(os.id_ordenes_servicio) as total
                FROM ordenes_servicio os
                INNER JOIN punto p ON os.id_punto = p.id_punto
                INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                INNER JOIN maquina m ON m.id_punto = p.id_punto
                INNER JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                AND m.estado = 1
                GROUP BY d.nombre_delegacion, tm.nombre_tipo_maquina""", ini, fin);
    }

    public List<Map<String, Object>> getRepuestosPorDelegacion(String ini, String fin) {
        return q("""
                SELECT d.nombre_delegacion,
                        r.nombre_repuesto as descripcion_repuesto,
                        SUM(osr.cantidad) as cantidad_usada
                FROM orden_servicio_repuesto osr
                INNER JOIN ordenes_servicio os ON osr.id_orden_servicio = os.id_ordenes_servicio
                INNER JOIN punto p ON os.id_punto = p.id_punto
                INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                INNER JOIN repuesto r ON osr.id_repuesto = r.id_repuesto
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                AND (osr.origen LIKE '%INEES%' OR osr.origen = 'INEES')
                GROUP BY d.nombre_delegacion, r.nombre_repuesto
                ORDER BY d.nombre_delegacion ASC, cantidad_usada DESC""", ini, fin);
    }

    public List<Map<String, Object>> getAllTiposMantenimiento() {
        try {
            return db.list("SELECT id_tipo_mantenimiento, nombre_completo FROM tipo_mantenimiento ORDER BY nombre_completo ASC");
        } catch (Exception e) {
            return List.of();
        }
    }

    public List<Map<String, Object>> getDatosMatrizMantenimiento(String ini, String fin) {
        return q("""
                SELECT d.nombre_delegacion, tm.nombre_completo as tipo, COUNT(os.id_ordenes_servicio) as total
                FROM ordenes_servicio os
                INNER JOIN punto p ON os.id_punto = p.id_punto
                INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                INNER JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                GROUP BY d.nombre_delegacion, tm.nombre_completo""", ini, fin);
    }

    public List<Map<String, Object>> getProductividadDetallada(String ini, String fin) {
        return q("""
                SELECT t.nombre_tecnico,
                    SUM(CASE WHEN DAYOFWEEK(os.fecha_visita) BETWEEN 2 AND 6 THEN 1 ELSE 0 END) as servicios_lv,
                    COUNT(DISTINCT CASE WHEN DAYOFWEEK(os.fecha_visita) BETWEEN 2 AND 6 THEN os.fecha_visita END) as dias_trabajados_lv,
                    SUM(CASE WHEN DAYOFWEEK(os.fecha_visita) = 7 THEN 1 ELSE 0 END) as servicios_sab,
                    COUNT(DISTINCT CASE WHEN DAYOFWEEK(os.fecha_visita) = 7 THEN os.fecha_visita END) as dias_trabajados_sab,
                    COUNT(os.id_ordenes_servicio) as total_general
                FROM ordenes_servicio os
                INNER JOIN tecnico t ON os.id_tecnico = t.id_tecnico
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                GROUP BY t.nombre_tecnico ORDER BY total_general DESC""", ini, fin);
    }

    public List<Map<String, Object>> getServiciosPorSemana(String ini, String fin) {
        return q("""
                SELECT YEARWEEK(fecha_visita, 1) as id_semana, MIN(fecha_visita) as fecha_inicio_semana,
                    MAX(fecha_visita) as fecha_fin_semana, COUNT(*) as total
                FROM ordenes_servicio
                WHERE fecha_visita BETWEEN :inicio AND :fin
                GROUP BY YEARWEEK(fecha_visita, 1) ORDER BY fecha_inicio_semana ASC""", ini, fin);
    }

    public List<Map<String, Object>> getPuntosMasVisitados(String ini, String fin, int minVisitas) {
        try {
            return db.list("""
                    SELECT
                        d.nombre_delegacion,
                        p.nombre_punto,
                        tm.nombre_tipo_maquina,
                        COUNT(os.id_ordenes_servicio) as total_visitas
                    FROM ordenes_servicio os
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    LEFT JOIN maquina m ON p.id_punto = m.id_punto AND m.estado = 1
                    LEFT JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY d.nombre_delegacion, p.id_punto, p.nombre_punto, tm.nombre_tipo_maquina
                    HAVING total_visitas >= :min_visitas
                    ORDER BY d.nombre_delegacion ASC, total_visitas DESC""", "inicio", ini, "fin", fin, "min_visitas", minVisitas);
        } catch (Exception e) {
            return List.of();
        }
    }

    public List<Map<String, Object>> getPuntosConMasFallidos(String ini, String fin) {
        return q("""
                SELECT
                    p.nombre_punto,
                    d.nombre_delegacion,
                    COUNT(os.id_ordenes_servicio) as total_fallidos
                FROM ordenes_servicio os
                INNER JOIN punto p ON os.id_punto = p.id_punto
                INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                INNER JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                AND tm.nombre_completo LIKE '%Fallid%'
                GROUP BY p.id_punto, p.nombre_punto, d.nombre_delegacion
                HAVING total_fallidos >= 2
                ORDER BY d.nombre_delegacion ASC, total_fallidos DESC""", ini, fin);
    }

    public int getTotalServiciosFallidos(String ini, String fin) {
        List<Map<String, Object>> r = q("""
                SELECT COUNT(DISTINCT os.id_ordenes_servicio) as total
                FROM ordenes_servicio os
                INNER JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                AND UPPER(tm.nombre_completo) LIKE '%FALLID%'""", ini, fin);
        return r.isEmpty() ? 0 : Conv.toInt(r.get(0).get("total"));
    }

    public List<Map<String, Object>> getCalificacionesServicio(String ini, String fin) {
        return q("""
                SELECT d.nombre_delegacion, cs.nombre_calificacion, COUNT(os.id_ordenes_servicio) as total
                FROM ordenes_servicio os
                INNER JOIN calificacion_servicio cs ON os.id_calificacion = cs.id_calificacion
                INNER JOIN punto p ON os.id_punto = p.id_punto
                INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                GROUP BY d.nombre_delegacion, cs.nombre_calificacion, cs.id_calificacion
                ORDER BY d.nombre_delegacion ASC, cs.id_calificacion DESC""", ini, fin);
    }

    public List<Map<String, Object>> getKpisPorDelegacion(String ini, String fin) {
        return q("""
                SELECT d.nombre_delegacion,
                        COUNT(DISTINCT os.id_ordenes_servicio) as total_servicios,
                        COUNT(DISTINCT os.id_tecnico) as total_tecnicos,
                        COUNT(DISTINCT osn.id_orden_servicio) as total_novedades,
                        COUNT(DISTINCT CONCAT(os.id_tecnico, '_', DATE(os.fecha_visita))) as dias_efectivos
                FROM ordenes_servicio os
                INNER JOIN punto p ON os.id_punto = p.id_punto
                INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                LEFT JOIN orden_servicio_novedad osn ON os.id_ordenes_servicio = osn.id_orden_servicio
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                GROUP BY d.nombre_delegacion ORDER BY total_servicios DESC""", ini, fin);
    }

    public List<Map<String, Object>> getAllTiposMaquina() {
        try {
            return db.list("SELECT nombre_tipo_maquina FROM tipo_maquina ORDER BY nombre_tipo_maquina ASC");
        } catch (Exception e) {
            return List.of();
        }
    }

    public List<Map<String, Object>> getDatosMatrizPuntosPorTipo(String ini, String fin) {
        return q("""
                SELECT d.nombre_delegacion, tm.nombre_tipo_maquina, COUNT(DISTINCT os.id_punto) as total
                FROM ordenes_servicio os
                INNER JOIN punto p ON os.id_punto = p.id_punto
                INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                INNER JOIN maquina m ON m.id_punto = p.id_punto
                INNER JOIN tipo_maquina tm ON m.id_tipo_maquina = tm.id_tipo_maquina
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                GROUP BY d.nombre_delegacion, tm.nombre_tipo_maquina""", ini, fin);
    }

    public List<Map<String, Object>> getDistribucionEstados(String ini, String fin) {
        return q("""
                SELECT em.nombre_estado, COUNT(os.id_ordenes_servicio) as total
                FROM ordenes_servicio os
                INNER JOIN estado_maquina em ON os.id_estado_maquina = em.id_estado
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                GROUP BY em.nombre_estado
                ORDER BY total DESC""", ini, fin);
    }

    public List<Map<String, Object>> getOrigenRepuestos(String ini, String fin) {
        return q("""
                SELECT origen, SUM(cantidad) as total
                FROM orden_servicio_repuesto osr
                INNER JOIN ordenes_servicio os ON osr.id_orden_servicio = os.id_ordenes_servicio
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                GROUP BY origen""", ini, fin);
    }

    public List<Map<String, Object>> getDesgloseMantenimientoPorTecnico(String ini, String fin) {
        return q("""
                SELECT t.nombre_tecnico, tm.nombre_completo as tipo, COUNT(os.id_ordenes_servicio) as cantidad
                FROM ordenes_servicio os
                INNER JOIN tecnico t ON os.id_tecnico = t.id_tecnico
                INNER JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                GROUP BY t.nombre_tecnico, tm.nombre_completo""", ini, fin);
    }

    private static String mes(String f) {
        return f.substring(0, 7) + "-01";
    }

    public List<Map<String, Object>> getListadoCostosOperativos(String ini, String fin) {
        try {
            return db.list("""
                    SELECT
                        COALESCE(t.nombre_tecnico, u.nombre) as beneficiario,
                        COALESCE(u.cargo, 'Técnico Motorizado') as rol,
                        c.salario, c.horas_extra, c.bono_meta,
                        c.gasolina, c.auxilio_comunicacion, c.auxilio_rodamiento,
                        (c.salario + c.horas_extra + c.bono_meta + c.gasolina + c.auxilio_comunicacion + c.auxilio_rodamiento) as subtotal
                    FROM costos_operativos c
                    LEFT JOIN tecnico t ON c.id_tecnico = t.id_tecnico
                    LEFT JOIN usuarios u ON c.id_usuario = u.usuario_id
                    WHERE c.mes_reporte BETWEEN :mes_inicio AND :mes_fin
                    ORDER BY subtotal DESC""", "mes_inicio", mes(ini), "mes_fin", mes(fin));
        } catch (Exception e) {
            return List.of();
        }
    }

    public List<Map<String, Object>> getListadoGastosGenerales(String ini, String fin) {
        try {
            return db.list("""
                    SELECT categoria, concepto, valor, mes_reporte
                    FROM gastos_administrativos
                    WHERE mes_reporte BETWEEN :mes_inicio AND :mes_fin
                    ORDER BY valor DESC""", "mes_inicio", mes(ini), "mes_fin", mes(fin));
        } catch (Exception e) {
            return List.of();
        }
    }

    public List<Map<String, Object>> getNovedadesPorDelegacion(String ini, String fin, boolean soloFallidos) {
        String filtro = soloFallidos ? "AND UPPER(tm.nombre_completo) LIKE '%FALLID%'" : "AND UPPER(tm.nombre_completo) NOT LIKE '%FALLID%'";
        return q("""
                SELECT
                    d.nombre_delegacion,
                    tn.nombre_novedad,
                    COUNT(DISTINCT os.id_ordenes_servicio) as total
                FROM ordenes_servicio os
                INNER JOIN punto p ON os.id_punto = p.id_punto
                INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                INNER JOIN orden_servicio_novedad osn ON os.id_ordenes_servicio = osn.id_orden_servicio
                INNER JOIN tipo_novedad tn ON osn.id_tipo_novedad = tn.id_tipo_novedad
                INNER JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                """ + filtro + """

                GROUP BY d.nombre_delegacion, tn.id_tipo_novedad, tn.nombre_novedad
                ORDER BY d.nombre_delegacion ASC, total DESC, tn.nombre_novedad ASC""", ini, fin);
    }

    public Map<String, List<Map<String, Object>>> getClientesConMasNovedades(String ini, String fin) {
        Map<String, List<Map<String, Object>>> r = new LinkedHashMap<>();
        r.put("totales", q("""
                SELECT
                    IFNULL(d.nombre_delegacion, 'SIN DELEGACIÓN') AS nombre_delegacion,
                    c.id_cliente,
                    c.nombre_cliente,
                    COUNT(DISTINCT os.id_ordenes_servicio) AS total
                FROM ordenes_servicio os
                INNER JOIN cliente c ON os.id_cliente = c.id_cliente
                INNER JOIN punto p ON os.id_punto = p.id_punto
                LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                INNER JOIN orden_servicio_novedad osn ON os.id_ordenes_servicio = osn.id_orden_servicio
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                GROUP BY d.nombre_delegacion, c.id_cliente, c.nombre_cliente
                ORDER BY nombre_delegacion ASC, total DESC""", ini, fin));
        r.put("novedades", q("""
                SELECT
                    IFNULL(d.nombre_delegacion, 'SIN DELEGACIÓN') AS nombre_delegacion,
                    c.id_cliente,
                    tn.nombre_novedad,
                    COUNT(DISTINCT os.id_ordenes_servicio) AS total
                FROM ordenes_servicio os
                INNER JOIN cliente c ON os.id_cliente = c.id_cliente
                INNER JOIN punto p ON os.id_punto = p.id_punto
                LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                INNER JOIN orden_servicio_novedad osn ON os.id_ordenes_servicio = osn.id_orden_servicio
                INNER JOIN tipo_novedad tn ON osn.id_tipo_novedad = tn.id_tipo_novedad
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                GROUP BY d.nombre_delegacion, c.id_cliente, tn.id_tipo_novedad, tn.nombre_novedad
                ORDER BY total DESC, tn.nombre_novedad ASC""", ini, fin));
        return r;
    }

    public Map<String, Object> getMetaServiciosEsperados(String ini, String fin) {
        Map<String, Object> r = new LinkedHashMap<>();
        int esperados = 0, tecDias = 0, normal = 0, na = 0;
        Set<String> diasConMeta = new HashSet<>();
        try {
            List<Map<String, Object>> regs = db.list("""
                    SELECT
                        os.fecha_visita,
                        os.id_tecnico,
                        MAX(
                            CASE
                                WHEN os.id_tipo_mantenimiento = 6
                                  OR UPPER(tm.nombre_completo) LIKE '%NUEVA ARQUITECTURA%'
                                THEN 1 ELSE 0
                            END
                        ) as es_nueva_arquitectura
                    FROM ordenes_servicio os
                    LEFT JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    GROUP BY os.fecha_visita, os.id_tecnico""", "inicio", ini, "fin", fin);
            Set<String> festivos = new HashSet<>();
            db.column("SELECT fecha FROM dias_festivos WHERE fecha BETWEEN :inicio AND :fin", "inicio", ini, "fin", fin).forEach(f -> festivos.add(Conv.str(f).substring(0, 10)));
            for (var reg : regs) {
                String fecha = Conv.str(reg.get("fecha_visita")).substring(0, 10);
                int dow = LocalDate.parse(fecha).getDayOfWeek().getValue();
                if (dow == 7 || festivos.contains(fecha)) continue;
                boolean sab = dow == 6;
                int meta;
                if (Conv.toInt(reg.get("es_nueva_arquitectura")) != 0) {
                    meta = sab ? 2 : 3;
                    na += meta;
                } else {
                    meta = sab ? 3 : 6;
                    normal += meta;
                }
                esperados += meta;
                tecDias++;
                diasConMeta.add(fecha);
            }
        } catch (Exception ignored) {
        }
        r.put("esperados", esperados);
        r.put("dias_laborables", diasConMeta.size());
        r.put("tecnicos_dias", tecDias);
        r.put("esperados_normal", normal);
        r.put("esperados_nueva_arquitectura", na);
        return r;
    }

    public Map<String, Map<String, Object>> getDesgloseIngresosPorTipo(String ini, String fin) {
        Map<String, Map<String, Object>> r = new LinkedHashMap<>();
        if (Conv.empty(ini) || Conv.empty(fin)) return r;
        for (var row : q("""
                SELECT
                    tm.id_tipo_mantenimiento,
                    tm.nombre_completo as tipo,
                    COALESCE(SUM(os.valor_servicio), 0) as total_ingresos,
                    COUNT(os.id_ordenes_servicio) as cantidad_servicios
                FROM ordenes_servicio os
                INNER JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                AND os.estado = 1
                GROUP BY tm.id_tipo_mantenimiento, tm.nombre_completo
                ORDER BY tm.id_tipo_mantenimiento""", ini, fin)) {
            Map<String, Object> d = new LinkedHashMap<>();
            d.put("tipo", row.get("tipo"));
            d.put("total", Conv.toDouble(row.get("total_ingresos")));
            d.put("cantidad", Conv.toInt(row.get("cantidad_servicios")));
            r.put(Conv.str(row.get("id_tipo_mantenimiento")), d);
        }
        return r;
    }

    public double getTotalIngresosRepuestos(String ini, String fin) {
        if (Conv.empty(ini) || Conv.empty(fin)) return 0;
        List<Map<String, Object>> r = q("""
                SELECT COALESCE(SUM(osr.cantidad * osr.valor_unitario), 0) as total
                FROM orden_servicio_repuesto osr
                INNER JOIN ordenes_servicio os ON osr.id_orden_servicio = os.id_ordenes_servicio
                WHERE os.fecha_visita BETWEEN :inicio AND :fin
                AND os.estado = 1""", ini, fin);
        return r.isEmpty() ? 0 : Conv.toDouble(r.get(0).get("total"));
    }

    public List<Map<String, Object>> getPuntosMasVisitadosWeb(String ini, String fin, int minVisitas, Integer limite, Map<String, Object> filtros) {
        Map<String, Object> p = new LinkedHashMap<>(Map.of("inicio", ini, "fin", fin, "min_visitas", minVisitas));
        String filtro = "";
        if (filtros != null && !Conv.empty(filtros.get("id_punto"))) {
            filtro += " AND p.id_punto = :id_punto";
            p.put("id_punto", Conv.toInt(filtros.get("id_punto")));
        }
        if (filtros != null && !Conv.empty(filtros.get("delegacion"))) {
            filtro += " AND d.nombre_delegacion = :delegacion";
            p.put("delegacion", filtros.get("delegacion"));
        }
        String lim = limite != null && limite > 0 ? " LIMIT " + limite : "";
        try {
            return db.list("""
                    SELECT
                        p.id_punto,
                        p.nombre_punto,
                        d.nombre_delegacion,
                        (
                            SELECT GROUP_CONCAT(DISTINCT tm2.nombre_tipo_maquina SEPARATOR ', ')
                            FROM maquina m2
                            INNER JOIN tipo_maquina tm2 ON m2.id_tipo_maquina = tm2.id_tipo_maquina
                            WHERE m2.id_punto = p.id_punto AND m2.estado = 1
                        ) AS tipos,
                        COUNT(DISTINCT os.id_ordenes_servicio) AS total_visitas
                    FROM ordenes_servicio os
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    """ + filtro + """

                    GROUP BY p.id_punto, p.nombre_punto, d.nombre_delegacion
                    HAVING total_visitas >= :min_visitas
                    ORDER BY total_visitas DESC""" + lim, p);
        } catch (Exception e) {
            log.error("Error en getPuntosMasVisitadosWeb: {}", e.getMessage());
            return List.of();
        }
    }

    public List<Map<String, Object>> getDetalleServiciosPorPunto(String ini, String fin, Map<String, Object> filtros) {
        Map<String, Object> p = new LinkedHashMap<>(Map.of("inicio", ini, "fin", fin));
        String filtro = "";
        if (filtros != null && !Conv.empty(filtros.get("id_punto"))) {
            filtro += " AND p.id_punto = :id_punto";
            p.put("id_punto", Conv.toInt(filtros.get("id_punto")));
        }
        if (filtros != null && !Conv.empty(filtros.get("delegacion"))) {
            filtro += " AND d.nombre_delegacion = :delegacion";
            p.put("delegacion", filtros.get("delegacion"));
        }
        try {
            return db.list("""
                    SELECT
                        os.id_ordenes_servicio,
                        os.fecha_visita,
                        p.id_punto,
                        p.nombre_punto,
                        d.nombre_delegacion,
                        COALESCE(c.nombre_cliente, 'Sin Cliente') AS nombre_cliente,
                        COALESCE(t.nombre_tecnico, 'SIN TÉCNICO') AS nombre_tecnico,
                        COALESCE(tm.nombre_completo, 'SIN TIPO') AS tipo_servicio,
                        GROUP_CONCAT(DISTINCT tn.nombre_novedad SEPARATOR ', ') AS novedades
                    FROM ordenes_servicio os
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    INNER JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    LEFT JOIN cliente c ON os.id_cliente = c.id_cliente
                    LEFT JOIN tecnico t ON os.id_tecnico = t.id_tecnico
                    LEFT JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                    LEFT JOIN orden_servicio_novedad osn ON os.id_ordenes_servicio = osn.id_orden_servicio
                    LEFT JOIN tipo_novedad tn ON osn.id_tipo_novedad = tn.id_tipo_novedad
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    """ + filtro + """

                    GROUP BY os.id_ordenes_servicio, os.fecha_visita, p.id_punto, p.nombre_punto,
                             d.nombre_delegacion, c.nombre_cliente, t.nombre_tecnico, tm.nombre_completo
                    ORDER BY os.fecha_visita DESC, os.id_ordenes_servicio DESC""", p);
        } catch (Exception e) {
            log.error("Error en getDetalleServiciosPorPunto: {}", e.getMessage());
            return List.of();
        }
    }

    /** @param soloFallidos null = todos, true = sólo fallidos, false = sin fallidos */
    public List<Map<String, Object>> getDetalleNovedades(String ini, String fin, Boolean soloFallidos, Map<String, Object> filtros) {
        Map<String, Object> p = new LinkedHashMap<>(Map.of("inicio", ini, "fin", fin));
        String filtro = "";
        if (Boolean.TRUE.equals(soloFallidos)) filtro += " AND UPPER(tm.nombre_completo) LIKE '%FALLID%'";
        else if (Boolean.FALSE.equals(soloFallidos)) filtro += " AND UPPER(tm.nombre_completo) NOT LIKE '%FALLID%'";
        if (filtros != null) {
            if (!Conv.empty(filtros.get("id_cliente"))) {
                filtro += " AND c.id_cliente = :id_cliente";
                p.put("id_cliente", Conv.toInt(filtros.get("id_cliente")));
            }
            if (!Conv.empty(filtros.get("id_punto"))) {
                filtro += " AND p.id_punto = :id_punto";
                p.put("id_punto", Conv.toInt(filtros.get("id_punto")));
            }
            if (!Conv.empty(filtros.get("delegacion"))) {
                filtro += " AND IFNULL(d.nombre_delegacion, 'SIN DELEGACIÓN') = :delegacion";
                p.put("delegacion", filtros.get("delegacion"));
            }
            if (!Conv.empty(filtros.get("id_tipo_novedad"))) {
                filtro += " AND tn.id_tipo_novedad = :id_tipo_novedad";
                p.put("id_tipo_novedad", Conv.toInt(filtros.get("id_tipo_novedad")));
            }
            if (!Conv.empty(filtros.get("novedad"))) {
                filtro += " AND tn.nombre_novedad = :novedad";
                p.put("novedad", filtros.get("novedad"));
            }
        }
        try {
            return db.list("""
                    SELECT
                        os.id_ordenes_servicio,
                        os.fecha_visita,
                        IFNULL(d.nombre_delegacion, 'SIN DELEGACIÓN') AS nombre_delegacion,
                        c.id_cliente,
                        COALESCE(c.nombre_cliente, 'Sin Cliente') AS nombre_cliente,
                        p.id_punto,
                        p.nombre_punto,
                        COALESCE(t.nombre_tecnico, 'SIN TÉCNICO') AS nombre_tecnico,
                        COALESCE(tm.nombre_completo, 'SIN TIPO') AS tipo_servicio,
                        tn.id_tipo_novedad,
                        tn.nombre_novedad
                    FROM ordenes_servicio os
                    INNER JOIN punto p ON os.id_punto = p.id_punto
                    LEFT JOIN delegacion d ON p.id_delegacion = d.id_delegacion
                    LEFT JOIN cliente c ON os.id_cliente = c.id_cliente
                    INNER JOIN orden_servicio_novedad osn ON os.id_ordenes_servicio = osn.id_orden_servicio
                    INNER JOIN tipo_novedad tn ON osn.id_tipo_novedad = tn.id_tipo_novedad
                    INNER JOIN tipo_mantenimiento tm ON os.id_tipo_mantenimiento = tm.id_tipo_mantenimiento
                    LEFT JOIN tecnico t ON os.id_tecnico = t.id_tecnico
                    WHERE os.fecha_visita BETWEEN :inicio AND :fin
                    """ + filtro + """

                    ORDER BY os.fecha_visita DESC, c.nombre_cliente ASC""", p);
        } catch (Exception e) {
            log.error("Error en getDetalleNovedades: {}", e.getMessage());
            return List.of();
        }
    }
}
