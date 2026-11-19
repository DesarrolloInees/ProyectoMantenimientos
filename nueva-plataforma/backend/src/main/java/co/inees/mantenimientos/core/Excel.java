package co.inees.mantenimientos.core;

import java.io.ByteArrayOutputStream;
import java.nio.file.Files;
import java.nio.file.Path;
import java.util.HashMap;
import java.util.LinkedHashMap;
import java.util.Map;
import java.util.Objects;

import org.apache.poi.ss.usermodel.BorderStyle;
import org.apache.poi.ss.usermodel.Cell;
import org.apache.poi.ss.usermodel.CellStyle;
import org.apache.poi.ss.usermodel.ClientAnchor;
import org.apache.poi.ss.usermodel.CreationHelper;
import org.apache.poi.ss.usermodel.Drawing;
import org.apache.poi.ss.usermodel.FillPatternType;
import org.apache.poi.ss.usermodel.HorizontalAlignment;
import org.apache.poi.ss.usermodel.Row;
import org.apache.poi.ss.usermodel.Sheet;
import org.apache.poi.ss.usermodel.VerticalAlignment;
import org.apache.poi.ss.usermodel.Workbook;
import org.apache.poi.ss.util.CellRangeAddress;
import org.apache.poi.ss.util.CellReference;
import org.apache.poi.util.Units;
import org.apache.poi.xssf.usermodel.XSSFCellStyle;
import org.apache.poi.xssf.usermodel.XSSFColor;
import org.apache.poi.xssf.usermodel.XSSFFont;
import org.apache.poi.xssf.usermodel.XSSFWorkbook;

/**
 * API pequeña al estilo PhpSpreadsheet sobre Apache POI.
 * Los estilos se acumulan por celda (como applyFromArray) y se materializan al guardar.
 */
public class Excel implements AutoCloseable {

    /** Propiedades de estilo (null = sin cambio). */
    public static class E {
        public Boolean bold, italic, wrap, underline;
        public Double size;
        public String color;      // ARGB fuente, ej FF000000
        public String fill;       // ARGB fondo
        public String borde;      // "thin", "medium", "thick" (todos los lados)
        public String bordeColor; // ARGB
        public String bordeExterior; // borde solo exterior del rango
        public String h;          // left, center, right
        public String v;          // top, center, bottom
        public String formato;    // formato numérico, ej "#,##0"
        public String fuente;     // nombre de la fuente

        public static E of() { return new E(); }
        public E bold() { bold = true; return this; }
        public E italic() { italic = true; return this; }
        public E wrap() { wrap = true; return this; }
        public E size(double s) { size = s; return this; }
        public E color(String c) { color = c; return this; }
        public E fill(String c) { fill = c; return this; }
        public E borde() { borde = "thin"; return this; }
        public E borde(String tipo) { borde = tipo; return this; }
        public E bordeColor(String c) { bordeColor = c; return this; }
        public E bordeExterior(String tipo) { bordeExterior = tipo; return this; }
        public E center() { h = "center"; v = "center"; return this; }
        public E h(String x) { h = x; return this; }
        public E v(String x) { v = x; return this; }
        public E formato(String f) { formato = f; return this; }
        public E fuente(String f) { fuente = f; return this; }

        E merge(E o) {
            E r = copy();
            if (o.bold != null) r.bold = o.bold;
            if (o.italic != null) r.italic = o.italic;
            if (o.wrap != null) r.wrap = o.wrap;
            if (o.underline != null) r.underline = o.underline;
            if (o.size != null) r.size = o.size;
            if (o.color != null) r.color = o.color;
            if (o.fill != null) r.fill = o.fill;
            if (o.borde != null) r.borde = o.borde;
            if (o.bordeColor != null) r.bordeColor = o.bordeColor;
            if (o.h != null) r.h = o.h;
            if (o.v != null) r.v = o.v;
            if (o.formato != null) r.formato = o.formato;
            if (o.fuente != null) r.fuente = o.fuente;
            return r;
        }

        E copy() {
            E r = new E();
            r.bold = bold; r.italic = italic; r.wrap = wrap; r.underline = underline; r.size = size;
            r.color = color; r.fill = fill; r.borde = borde; r.bordeColor = bordeColor; r.h = h; r.v = v;
            r.formato = formato; r.fuente = fuente;
            return r;
        }

        String clave(String bt, String bb, String bl, String br) {
            return String.join("|", Objects.toString(bold), Objects.toString(italic), Objects.toString(wrap),
                    Objects.toString(underline), Objects.toString(size), Objects.toString(color), Objects.toString(fill),
                    Objects.toString(bordeColor), Objects.toString(h), Objects.toString(v), Objects.toString(formato),
                    Objects.toString(fuente), Objects.toString(bt), Objects.toString(bb), Objects.toString(bl), Objects.toString(br));
        }
    }

    private static class Bordes {
        String t, b, l, r;
    }

    private final XSSFWorkbook wb = new XSSFWorkbook();
    private Sheet hoja;
    private final Map<Sheet, Map<String, E>> estilos = new HashMap<>();
    private final Map<Sheet, Map<String, Bordes>> bordes = new HashMap<>();
    private final Map<String, CellStyle> cache = new HashMap<>();
    private final Map<String, XSSFFont> cacheFuentes = new HashMap<>();

    public Excel() {
        hoja = wb.createSheet("Hoja1");
    }

    public Excel(String titulo) {
        hoja = wb.createSheet(titulo(titulo));
    }

    private static String titulo(String t) {
        String s = t.replaceAll("[\\\\/?*\\[\\]:]", " ");
        return s.length() > 31 ? s.substring(0, 31) : s;
    }

    public Workbook wb() { return wb; }
    public Sheet hoja() { return hoja; }

    public Excel tituloHoja(String t) {
        wb.setSheetName(wb.getSheetIndex(hoja), titulo(t));
        return this;
    }

    public Excel nuevaHoja(String t) {
        hoja = wb.createSheet(titulo(t));
        return this;
    }

    public Excel usarHoja(int i) {
        hoja = wb.getSheetAt(i);
        return this;
    }

    // ---------------------------------------------------------------- celdas

    private Cell celda(int fila, int col) {
        Row r = hoja.getRow(fila);
        if (r == null) r = hoja.createRow(fila);
        Cell c = r.getCell(col);
        if (c == null) c = r.createCell(col);
        return c;
    }

    private Cell celda(String ref) {
        CellReference cr = new CellReference(ref);
        return celda(cr.getRow(), cr.getCol());
    }

    /** setCellValue */
    public Excel set(String ref, Object valor) {
        Cell c = celda(ref);
        if (valor == null) {
            c.setBlank();
        } else if (valor instanceof Number n) {
            c.setCellValue(n.doubleValue());
        } else if (valor instanceof Boolean b) {
            c.setCellValue(b);
        } else {
            String s = String.valueOf(valor);
            if (s.startsWith("=")) c.setCellFormula(s.substring(1));
            else c.setCellValue(s);
        }
        return this;
    }

    /** setCellValueExplicit(..., TYPE_STRING) */
    public Excel setTexto(String ref, Object valor) {
        celda(ref).setCellValue(valor == null ? "" : String.valueOf(valor));
        return this;
    }

    public Excel set(int col, int fila, Object valor) {
        return set(ref(col, fila), valor);
    }

    /** Letra de columna (1 = A) */
    public static String col(int n) {
        return CellReference.convertNumToColString(n - 1);
    }

    public static String ref(int col, int fila) {
        return col(col) + fila;
    }

    public Excel merge(String rango) {
        hoja.addMergedRegion(CellRangeAddress.valueOf(rango));
        return this;
    }

    public Excel ancho(String col, double ancho) {
        int i = CellReference.convertColStringToIndex(col);
        hoja.setColumnWidth(i, (int) Math.round(ancho * 256 + 180));
        return this;
    }

    public Excel autoAncho(String desde, String hasta) {
        int a = CellReference.convertColStringToIndex(desde);
        int b = CellReference.convertColStringToIndex(hasta);
        for (int i = a; i <= b; i++) {
            try {
                hoja.autoSizeColumn(i);
                hoja.setColumnWidth(i, Math.min(hoja.getColumnWidth(i) + 512, 255 * 256));
            } catch (Exception ignored) {
            }
        }
        return this;
    }

    public Excel alto(int fila, double puntos) {
        Row r = hoja.getRow(fila - 1);
        if (r == null) r = hoja.createRow(fila - 1);
        r.setHeightInPoints((float) puntos);
        return this;
    }

    public Excel congelar(String ref) {
        CellReference cr = new CellReference(ref);
        hoja.createFreezePane(cr.getCol(), cr.getRow());
        return this;
    }

    public Excel autoFiltro(String rango) {
        hoja.setAutoFilter(CellRangeAddress.valueOf(rango));
        return this;
    }

    // ---------------------------------------------------------------- estilos

    /** applyFromArray sobre un rango (A1 o A1:G9). */
    public Excel estilo(String rango, E e) {
        CellRangeAddress ra = rango.contains(":") ? CellRangeAddress.valueOf(rango) : CellRangeAddress.valueOf(rango + ":" + rango);
        Map<String, E> m = estilos.computeIfAbsent(hoja, k -> new HashMap<>());
        Map<String, Bordes> mb = bordes.computeIfAbsent(hoja, k -> new HashMap<>());
        for (int f = ra.getFirstRow(); f <= ra.getLastRow(); f++) {
            for (int c = ra.getFirstColumn(); c <= ra.getLastColumn(); c++) {
                String k = f + ":" + c;
                E actual = m.getOrDefault(k, new E());
                m.put(k, actual.merge(e));
                celda(f, c);
                if (e.borde != null) {
                    Bordes b = mb.computeIfAbsent(k, x -> new Bordes());
                    b.t = b.b = b.l = b.r = e.borde;
                }
                if (e.bordeExterior != null) {
                    Bordes b = mb.computeIfAbsent(k, x -> new Bordes());
                    if (f == ra.getFirstRow()) b.t = e.bordeExterior;
                    if (f == ra.getLastRow()) b.b = e.bordeExterior;
                    if (c == ra.getFirstColumn()) b.l = e.bordeExterior;
                    if (c == ra.getLastColumn()) b.r = e.bordeExterior;
                }
            }
        }
        return this;
    }

    private static BorderStyle bs(String t) {
        if (t == null) return BorderStyle.NONE;
        return switch (t) {
            case "medium" -> BorderStyle.MEDIUM;
            case "thick" -> BorderStyle.THICK;
            case "dashed" -> BorderStyle.DASHED;
            case "dotted" -> BorderStyle.DOTTED;
            case "none" -> BorderStyle.NONE;
            default -> BorderStyle.THIN;
        };
    }

    private static XSSFColor color(String argb) {
        String s = argb.startsWith("#") ? argb.substring(1) : argb;
        if (s.length() == 6) s = "FF" + s;
        byte[] b = new byte[4];
        for (int i = 0; i < 4; i++) b[i] = (byte) Integer.parseInt(s.substring(i * 2, i * 2 + 2), 16);
        return new XSSFColor(b, null);
    }

    private void materializar() {
        for (Map.Entry<Sheet, Map<String, E>> hs : estilos.entrySet()) {
            Sheet sh = hs.getKey();
            Map<String, Bordes> mb = bordes.getOrDefault(sh, Map.of());
            java.util.Set<String> claves = new java.util.HashSet<>(hs.getValue().keySet());
            claves.addAll(mb.keySet());
            for (String k : claves) {
                E e = hs.getValue().getOrDefault(k, new E());
                Bordes b = mb.getOrDefault(k, new Bordes());
                String[] p = k.split(":");
                int f = Integer.parseInt(p[0]);
                int c = Integer.parseInt(p[1]);
                Row r = sh.getRow(f);
                if (r == null) r = sh.createRow(f);
                Cell cell = r.getCell(c);
                if (cell == null) cell = r.createCell(c);
                String clave = e.clave(b.t, b.b, b.l, b.r);
                CellStyle st = cache.get(clave);
                if (st == null) {
                    XSSFCellStyle s = wb.createCellStyle();
                    String fk = Objects.toString(e.bold) + Objects.toString(e.italic) + Objects.toString(e.size)
                            + Objects.toString(e.color) + Objects.toString(e.fuente) + Objects.toString(e.underline);
                    XSSFFont font = cacheFuentes.get(fk);
                    if (font == null) {
                        font = wb.createFont();
                        if (e.fuente != null) font.setFontName(e.fuente);
                        if (Boolean.TRUE.equals(e.bold)) font.setBold(true);
                        if (Boolean.TRUE.equals(e.italic)) font.setItalic(true);
                        if (Boolean.TRUE.equals(e.underline)) font.setUnderline(XSSFFont.U_SINGLE);
                        if (e.size != null) font.setFontHeightInPoints(e.size.shortValue());
                        if (e.color != null) font.setColor(color(e.color));
                        cacheFuentes.put(fk, font);
                    }
                    s.setFont(font);
                    if (e.fill != null) {
                        s.setFillForegroundColor(color(e.fill));
                        s.setFillPattern(FillPatternType.SOLID_FOREGROUND);
                    }
                    if (Boolean.TRUE.equals(e.wrap)) s.setWrapText(true);
                    if (e.h != null) s.setAlignment(switch (e.h) {
                        case "center" -> HorizontalAlignment.CENTER;
                        case "right" -> HorizontalAlignment.RIGHT;
                        default -> HorizontalAlignment.LEFT;
                    });
                    if (e.v != null) s.setVerticalAlignment(switch (e.v) {
                        case "top" -> VerticalAlignment.TOP;
                        case "bottom" -> VerticalAlignment.BOTTOM;
                        default -> VerticalAlignment.CENTER;
                    });
                    if (e.formato != null) {
                        CreationHelper ch = wb.getCreationHelper();
                        s.setDataFormat(ch.createDataFormat().getFormat(e.formato));
                    }
                    s.setBorderTop(bs(b.t));
                    s.setBorderBottom(bs(b.b));
                    s.setBorderLeft(bs(b.l));
                    s.setBorderRight(bs(b.r));
                    if (e.bordeColor != null) {
                        XSSFColor bc = color(e.bordeColor);
                        s.setTopBorderColor(bc);
                        s.setBottomBorderColor(bc);
                        s.setLeftBorderColor(bc);
                        s.setRightBorderColor(bc);
                    }
                    cache.put(clave, s);
                    st = s;
                }
                cell.setCellStyle(st);
            }
        }
    }

    // ---------------------------------------------------------------- imágenes

    /** Drawing: inserta una imagen anclada a la celda con alto en píxeles. */
    public Excel imagen(Path archivo, String celdaRef, int altoPx, int offX, int offY) {
        try {
            if (archivo == null || !Files.exists(archivo)) return this;
            byte[] datos = Files.readAllBytes(archivo);
            String ext = Archivos.extension(archivo.getFileName().toString());
            int tipo = ext.equals("png") ? Workbook.PICTURE_TYPE_PNG : Workbook.PICTURE_TYPE_JPEG;
            int idx = wb.addPicture(datos, tipo);
            Drawing<?> d = hoja.createDrawingPatriarch();
            ClientAnchor a = wb.getCreationHelper().createClientAnchor();
            CellReference cr = new CellReference(celdaRef);
            a.setCol1(cr.getCol());
            a.setRow1(cr.getRow());
            a.setDx1(Units.pixelToEMU(offX));
            a.setDy1(Units.pixelToEMU(offY));
            a.setAnchorType(ClientAnchor.AnchorType.MOVE_DONT_RESIZE);
            var pic = d.createPicture(a, idx);
            java.awt.Dimension dim = pic.getImageDimension();
            double escala = dim.height > 0 ? (double) altoPx / dim.height : 1;
            pic.resize(escala);
        } catch (Exception ignored) {
        }
        return this;
    }

    // ---------------------------------------------------------------- salida

    public byte[] bytes() {
        try (ByteArrayOutputStream os = new ByteArrayOutputStream()) {
            materializar();
            wb.write(os);
            return os.toByteArray();
        } catch (Exception e) {
            throw new IllegalStateException("Error generando Excel: " + e.getMessage(), e);
        }
    }

    @Override
    public void close() {
        try {
            wb.close();
        } catch (Exception ignored) {
        }
    }

    public static Map<String, Object> fila(Object... kv) {
        Map<String, Object> m = new LinkedHashMap<>();
        for (int i = 0; i + 1 < kv.length; i += 2) m.put(String.valueOf(kv[i]), kv[i + 1]);
        return m;
    }
}
