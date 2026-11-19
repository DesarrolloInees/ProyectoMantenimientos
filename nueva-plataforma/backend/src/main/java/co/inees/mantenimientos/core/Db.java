package co.inees.mantenimientos.core;

import java.math.BigDecimal;
import java.sql.ResultSet;
import java.sql.ResultSetMetaData;
import java.sql.SQLException;
import java.sql.Types;
import java.util.ArrayList;
import java.util.Collection;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.function.Supplier;

import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.jdbc.core.RowMapper;
import org.springframework.jdbc.core.namedparam.MapSqlParameterSource;
import org.springframework.jdbc.core.namedparam.NamedParameterJdbcTemplate;
import org.springframework.jdbc.support.GeneratedKeyHolder;
import org.springframework.jdbc.support.KeyHolder;
import org.springframework.stereotype.Component;
import org.springframework.transaction.support.TransactionTemplate;

/**
 * Envoltorio sobre JDBC que reproduce el comportamiento de PDO usado en PHP
 * (PDO::FETCH_ASSOC, parámetros nombrados ":param", fetchColumn, lastInsertId).
 *
 * Los tipos se devuelven igual que PDO/mysqlnd:
 *  - DATE/TIME/DATETIME/TIMESTAMP -> String con el formato de MySQL
 *  - DECIMAL -> String (ej. "12500.00")
 *  - enteros -> Long/Integer
 * Así el JSON que recibe el frontend es idéntico al que devolvía json_encode().
 */
@Component
public class Db {

    private final NamedParameterJdbcTemplate jdbc;
    private final TransactionTemplate tx;

    public Db(NamedParameterJdbcTemplate jdbc, TransactionTemplate tx) {
        this.jdbc = jdbc;
        this.tx = tx;
    }

    /** Mapeador fila -> Map con orden de columnas (FETCH_ASSOC). */
    public static final RowMapper<Map<String, Object>> ASSOC = (ResultSet rs, int n) -> {
        ResultSetMetaData md = rs.getMetaData();
        int cols = md.getColumnCount();
        Map<String, Object> fila = new LinkedHashMap<>(cols * 2);
        for (int i = 1; i <= cols; i++) {
            String nombre = md.getColumnLabel(i);
            if (nombre == null || nombre.isEmpty()) nombre = md.getColumnName(i);
            fila.put(nombre, valor(rs, md, i));
        }
        return fila;
    };

    private static Object valor(ResultSet rs, ResultSetMetaData md, int i) throws SQLException {
        int t = md.getColumnType(i);
        switch (t) {
            case Types.DATE, Types.TIME, Types.TIMESTAMP, Types.TIME_WITH_TIMEZONE, Types.TIMESTAMP_WITH_TIMEZONE -> {
                String s = rs.getString(i);
                if (s != null && (t == Types.TIMESTAMP || t == Types.TIMESTAMP_WITH_TIMEZONE) && s.endsWith(".0")) {
                    s = s.substring(0, s.length() - 2);
                }
                return s;
            }
            case Types.DECIMAL, Types.NUMERIC -> {
                BigDecimal bd = rs.getBigDecimal(i);
                return bd == null ? null : bd.toPlainString();
            }
            case Types.TINYINT, Types.SMALLINT, Types.INTEGER, Types.BIT, Types.BOOLEAN -> {
                long v = rs.getLong(i);
                return rs.wasNull() ? null : (Object) (int) v;
            }
            case Types.BIGINT -> {
                long v = rs.getLong(i);
                return rs.wasNull() ? null : (Object) v;
            }
            case Types.BLOB, Types.BINARY, Types.VARBINARY, Types.LONGVARBINARY -> {
                return rs.getString(i);
            }
            default -> {
                Object o = rs.getObject(i);
                if (o instanceof java.time.temporal.Temporal || o instanceof java.util.Date) {
                    return rs.getString(i);
                }
                return o;
            }
        }
    }

    // ------------------------------------------------------------------ params

    /** Construye parámetros a partir de pares clave/valor: p("id", 1, "nombre", "x"). */
    public static Map<String, Object> p(Object... kv) {
        Map<String, Object> m = new LinkedHashMap<>();
        for (int i = 0; i + 1 < kv.length; i += 2) {
            m.put(String.valueOf(kv[i]), kv[i + 1]);
        }
        return m;
    }

    private static MapSqlParameterSource src(Map<String, ?> params) {
        MapSqlParameterSource s = new MapSqlParameterSource();
        if (params != null) {
            params.forEach((k, v) -> s.addValue(k.startsWith(":") ? k.substring(1) : k, v));
        }
        return s;
    }

    // ------------------------------------------------------------------ consultas

    /** fetchAll(PDO::FETCH_ASSOC) */
    public List<Map<String, Object>> list(String sql, Map<String, ?> params) {
        return jdbc.query(sql, src(params), ASSOC);
    }

    public List<Map<String, Object>> list(String sql, Object... kv) {
        return list(sql, p(kv));
    }

    /** fetch(PDO::FETCH_ASSOC) -> null si no hay filas (equivalente a false en PHP). */
    public Map<String, Object> one(String sql, Map<String, ?> params) {
        List<Map<String, Object>> l = list(sql, params);
        return l.isEmpty() ? null : l.get(0);
    }

    public Map<String, Object> one(String sql, Object... kv) {
        return one(sql, p(kv));
    }

    /** fetchColumn() -> primer valor de la primera fila o null. */
    public Object scalar(String sql, Map<String, ?> params) {
        Map<String, Object> f = one(sql, params);
        if (f == null || f.isEmpty()) return null;
        return f.values().iterator().next();
    }

    public Object scalar(String sql, Object... kv) {
        return scalar(sql, p(kv));
    }

    public long scalarLong(String sql, Object... kv) {
        return Conv.toLong(scalar(sql, p(kv)));
    }

    public long scalarLong(String sql, Map<String, ?> params) {
        return Conv.toLong(scalar(sql, params));
    }

    /** fetchAll(PDO::FETCH_COLUMN) */
    public List<Object> column(String sql, Map<String, ?> params) {
        List<Object> r = new ArrayList<>();
        for (Map<String, Object> f : list(sql, params)) {
            r.add(f.isEmpty() ? null : f.values().iterator().next());
        }
        return r;
    }

    public List<Object> column(String sql, Object... kv) {
        return column(sql, p(kv));
    }

    /** execute() para INSERT/UPDATE/DELETE -> filas afectadas (rowCount). */
    public int update(String sql, Map<String, ?> params) {
        return jdbc.update(sql, src(params));
    }

    public int update(String sql, Object... kv) {
        return update(sql, p(kv));
    }

    /** INSERT devolviendo lastInsertId(). */
    public long insert(String sql, Map<String, ?> params) {
        KeyHolder kh = new GeneratedKeyHolder();
        jdbc.update(sql, src(params), kh);
        List<Map<String, Object>> keys = kh.getKeyList();
        if (keys.isEmpty()) return 0;
        Object v = keys.get(0).values().stream().findFirst().orElse(null);
        return Conv.toLong(v);
    }

    public long insert(String sql, Object... kv) {
        return insert(sql, p(kv));
    }

    /** Ejecuta un bloque dentro de una transacción (beginTransaction/commit/rollBack). */
    public <T> T tx(Supplier<T> bloque) {
        return tx.execute(status -> bloque.get());
    }

    public void txRun(Runnable bloque) {
        tx.executeWithoutResult(status -> bloque.run());
    }

    public JdbcTemplate raw() {
        return jdbc.getJdbcTemplate();
    }

    public NamedParameterJdbcTemplate named() {
        return jdbc;
    }

    /** Genera ":p0,:p1,..." y agrega los valores a params (para IN (...)). */
    public static String in(String prefijo, Collection<?> valores, Map<String, Object> params) {
        StringBuilder sb = new StringBuilder();
        int i = 0;
        for (Object v : valores) {
            if (i > 0) sb.append(',');
            String k = prefijo + i;
            sb.append(':').append(k);
            params.put(k, v);
            i++;
        }
        if (i == 0) sb.append("NULL");
        return sb.toString();
    }
}
