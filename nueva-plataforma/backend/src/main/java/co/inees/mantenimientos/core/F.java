package co.inees.mantenimientos.core;

import java.time.DayOfWeek;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.time.LocalTime;
import java.time.YearMonth;
import java.time.format.DateTimeFormatter;
import java.time.temporal.ChronoUnit;
import java.util.regex.Pattern;

/** Utilidades de fecha equivalentes a date()/strtotime() de PHP (zona America/Bogota). */
public final class F {

    public static final DateTimeFormatter YMD = DateTimeFormatter.ofPattern("yyyy-MM-dd");
    public static final DateTimeFormatter DMY = DateTimeFormatter.ofPattern("dd/MM/yyyy");
    public static final DateTimeFormatter YMDHMS = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss");
    private static final Pattern P_YMD = Pattern.compile("^\\d{4}-\\d{2}-\\d{2}$");

    public static final String[] MESES = {"", "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio",
            "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"};
    public static final String[] DIAS = {"", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"};

    private F() {}

    /** date('Y-m-d') */
    public static String hoy() {
        return LocalDate.now().format(YMD);
    }

    /** date('Y-m-d H:i:s') */
    public static String ahora() {
        return LocalDateTime.now().format(YMDHMS);
    }

    /** date('Y-m-01') */
    public static String primeroMes() {
        return LocalDate.now().withDayOfMonth(1).format(YMD);
    }

    /** date('Y-m-t') */
    public static String ultimoMes() {
        return YearMonth.now().atEndOfMonth().format(YMD);
    }

    public static boolean esYmd(String s) {
        if (s == null || !P_YMD.matcher(s).matches()) return false;
        try {
            LocalDate.parse(s, YMD);
            return true;
        } catch (Exception e) {
            return false;
        }
    }

    /** Valor fecha válido o por defecto. */
    public static String fechaValida(String valor, String porDefecto) {
        return esYmd(valor) ? valor : porDefecto;
    }

    public static LocalDate fecha(Object s) {
        if (s == null) return null;
        String t = String.valueOf(s).trim();
        if (t.length() >= 10) t = t.substring(0, 10);
        try {
            return LocalDate.parse(t, YMD);
        } catch (Exception e) {
            return null;
        }
    }

    public static LocalDateTime fechaHora(Object s) {
        if (s == null) return null;
        String t = String.valueOf(s).trim().replace('T', ' ');
        try {
            if (t.length() == 10) return LocalDate.parse(t, YMD).atStartOfDay();
            if (t.length() > 19) t = t.substring(0, 19);
            if (t.length() == 16) t = t + ":00";
            return LocalDateTime.parse(t, YMDHMS);
        } catch (Exception e) {
            return null;
        }
    }

    public static LocalTime hora(Object s) {
        if (s == null) return null;
        String t = String.valueOf(s).trim();
        try {
            if (t.length() == 5) t = t + ":00";
            if (t.length() > 8) t = t.substring(0, 8);
            String[] p = t.split(":");
            return LocalTime.of(Integer.parseInt(p[0]) % 24, Integer.parseInt(p[1]), p.length > 2 ? Integer.parseInt(p[2]) : 0);
        } catch (Exception e) {
            return null;
        }
    }

    /** date('d/m/Y', strtotime($f)) */
    public static String dmy(Object s) {
        LocalDate d = fecha(s);
        return d == null ? "" : d.format(DMY);
    }

    /** date($formato, strtotime($f)) con los formatos comunes. */
    public static String fmt(Object s, String patronJava) {
        LocalDateTime d = fechaHora(s);
        if (d == null) {
            LocalTime t = hora(s);
            if (t != null) return t.format(DateTimeFormatter.ofPattern(patronJava));
            return "";
        }
        return d.format(DateTimeFormatter.ofPattern(patronJava));
    }

    /** date('H:i', strtotime($h)) */
    public static String hm(Object s) {
        LocalTime t = hora(s);
        return t == null ? "" : String.format("%02d:%02d", t.getHour(), t.getMinute());
    }

    public static long diasEntre(String a, String b) {
        return ChronoUnit.DAYS.between(fecha(a), fecha(b));
    }

    public static String mesNombre(int m) {
        return m >= 1 && m <= 12 ? MESES[m] : "";
    }

    public static String diaNombre(LocalDate d) {
        return DIAS[d.getDayOfWeek().getValue()];
    }

    public static boolean esDomingo(LocalDate d) {
        return d.getDayOfWeek() == DayOfWeek.SUNDAY;
    }
}
