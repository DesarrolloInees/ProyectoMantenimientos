package co.inees.mantenimientos.core;

import java.math.BigDecimal;
import java.math.RoundingMode;
import java.text.DecimalFormat;
import java.text.DecimalFormatSymbols;
import java.util.ArrayList;
import java.util.Collection;
import java.util.List;
import java.util.Locale;
import java.util.Map;

/**
 * Conversiones "sueltas" al estilo PHP ((int), (float), empty(), trim, number_format...).
 */
public final class Conv {

    private Conv() {}

    public static String str(Object o) {
        return o == null ? "" : String.valueOf(o);
    }

    public static String str(Object o, String def) {
        return o == null ? def : String.valueOf(o);
    }

    /** (int) $x de PHP: toma el prefijo numérico, 0 si no hay. */
    public static int toInt(Object o) {
        return (int) toLong(o);
    }

    public static long toLong(Object o) {
        if (o == null) return 0;
        if (o instanceof Number n) return n.longValue();
        if (o instanceof Boolean b) return b ? 1 : 0;
        String s = String.valueOf(o).trim();
        if (s.isEmpty()) return 0;
        try {
            return Long.parseLong(s);
        } catch (NumberFormatException e) {
            try {
                return (long) Double.parseDouble(prefijoNumerico(s));
            } catch (NumberFormatException e2) {
                return 0;
            }
        }
    }

    public static double toDouble(Object o) {
        if (o == null) return 0;
        if (o instanceof Number n) return n.doubleValue();
        if (o instanceof Boolean b) return b ? 1 : 0;
        String s = String.valueOf(o).trim();
        if (s.isEmpty()) return 0;
        try {
            return Double.parseDouble(s);
        } catch (NumberFormatException e) {
            try {
                return Double.parseDouble(prefijoNumerico(s));
            } catch (NumberFormatException e2) {
                return 0;
            }
        }
    }

    public static BigDecimal toDecimal(Object o) {
        return BigDecimal.valueOf(toDouble(o));
    }

    private static String prefijoNumerico(String s) {
        StringBuilder sb = new StringBuilder();
        boolean punto = false;
        for (int i = 0; i < s.length(); i++) {
            char c = s.charAt(i);
            if (Character.isDigit(c) || (i == 0 && (c == '-' || c == '+'))) {
                sb.append(c);
            } else if (c == '.' && !punto) {
                punto = true;
                sb.append(c);
            } else {
                break;
            }
        }
        String r = sb.toString();
        return r.isEmpty() || r.equals("-") || r.equals("+") || r.equals(".") ? "0" : r;
    }

    /** empty($x) de PHP. */
    public static boolean empty(Object o) {
        if (o == null) return true;
        if (o instanceof String s) return s.isEmpty() || s.equals("0");
        if (o instanceof Number n) return n.doubleValue() == 0;
        if (o instanceof Boolean b) return !b;
        if (o instanceof Collection<?> c) return c.isEmpty();
        if (o instanceof Map<?, ?> m) return m.isEmpty();
        return false;
    }

    /** Devuelve null si está vacío (patrón "!empty($x) ? $x : null"). */
    public static Object nullIfEmpty(Object o) {
        if (o == null) return null;
        if (o instanceof String s && s.trim().isEmpty()) return null;
        return o;
    }

    public static Integer intOrNull(Object o) {
        if (o == null) return null;
        String s = String.valueOf(o).trim();
        if (s.isEmpty() || s.equalsIgnoreCase("null")) return null;
        return toInt(s);
    }

    /** number_format($n, $dec, ',', '.') */
    public static String numberFormat(Object n, int dec, String decSep, String milSep) {
        double v = toDouble(n);
        BigDecimal bd = BigDecimal.valueOf(v).setScale(dec, RoundingMode.HALF_UP);
        DecimalFormatSymbols sym = new DecimalFormatSymbols(Locale.US);
        StringBuilder pat = new StringBuilder("#,##0");
        if (dec > 0) {
            pat.append('.');
            pat.append("0".repeat(dec));
        }
        DecimalFormat df = new DecimalFormat(pat.toString(), sym);
        String s = df.format(bd);
        // reemplazo de separadores
        s = s.replace(",", "\u0001").replace(".", "\u0002");
        s = s.replace("\u0001", milSep).replace("\u0002", decSep);
        return s;
    }

    /** number_format($n) => separador de miles "," sin decimales (PHP por defecto). */
    public static String numberFormat(Object n) {
        return numberFormat(n, 0, ".", ",");
    }

    /** Formato pesos colombianos "$ 1.234.567" */
    public static String pesos(Object n) {
        return "$ " + numberFormat(n, 0, ",", ".");
    }

    @SuppressWarnings("unchecked")
    public static List<Object> list(Object o) {
        if (o == null) return new ArrayList<>();
        if (o instanceof List<?> l) return (List<Object>) l;
        if (o instanceof Collection<?> c) return new ArrayList<>(c);
        List<Object> r = new ArrayList<>();
        r.add(o);
        return r;
    }

    @SuppressWarnings("unchecked")
    public static Map<String, Object> map(Object o) {
        if (o instanceof Map<?, ?> m) return (Map<String, Object>) m;
        return new java.util.LinkedHashMap<>();
    }

    /** htmlspecialchars() */
    public static String h(Object o) {
        if (o == null) return "";
        String s = String.valueOf(o);
        StringBuilder sb = new StringBuilder(s.length() + 16);
        for (char c : s.toCharArray()) {
            switch (c) {
                case '&' -> sb.append("&amp;");
                case '<' -> sb.append("&lt;");
                case '>' -> sb.append("&gt;");
                case '"' -> sb.append("&quot;");
                case '\'' -> sb.append("&#039;");
                default -> sb.append(c);
            }
        }
        return sb.toString();
    }

    /** Comparación "suelta" de PHP (==): numérica si ambos son numéricos, si no por texto. */
    public static boolean looseEq(Object a, Object b) {
        if (a == null || b == null) {
            String sa = a == null ? "" : String.valueOf(a);
            String sb = b == null ? "" : String.valueOf(b);
            if (a == null && b == null) return true;
            // null == "" / null == 0 en PHP
            return (a == null ? sb : sa).isEmpty() || "0".equals(a == null ? sb : sa);
        }
        String sa = String.valueOf(a).trim();
        String sb = String.valueOf(b).trim();
        if (esNumero(sa) && esNumero(sb)) return Double.parseDouble(sa) == Double.parseDouble(sb);
        return String.valueOf(a).equals(String.valueOf(b));
    }

    public static boolean esNumero(String s) {
        if (s == null || s.isEmpty()) return false;
        try {
            Double.parseDouble(s);
            return true;
        } catch (NumberFormatException e) {
            return false;
        }
    }

    /** mb_convert_case($s, MB_CASE_TITLE, 'UTF-8') */
    public static String titulo(String s) {
        if (s == null) return "";
        StringBuilder sb = new StringBuilder(s.length());
        boolean inicio = true;
        for (int i = 0; i < s.length(); i++) {
            char c = s.charAt(i);
            if (Character.isLetter(c)) {
                sb.append(inicio ? Character.toTitleCase(c) : Character.toLowerCase(c));
                inicio = false;
            } else {
                sb.append(c);
                inicio = !(Character.isDigit(c) || c == '\'');
            }
        }
        return sb.toString();
    }

    public static String ucwordsLower(String s) {
        if (s == null) return "";
        StringBuilder sb = new StringBuilder();
        boolean nueva = true;
        for (char c : s.toLowerCase(Locale.ROOT).toCharArray()) {
            if (Character.isWhitespace(c)) {
                nueva = true;
                sb.append(c);
            } else if (nueva) {
                sb.append(Character.toUpperCase(c));
                nueva = false;
            } else {
                sb.append(c);
            }
        }
        return sb.toString();
    }
}
