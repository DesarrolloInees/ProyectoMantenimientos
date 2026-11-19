package co.inees.mantenimientos.core;

import java.io.InputStream;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.apache.poi.ss.usermodel.Cell;
import org.apache.poi.ss.usermodel.DataFormatter;
import org.apache.poi.ss.usermodel.FormulaEvaluator;
import org.apache.poi.ss.usermodel.Row;
import org.apache.poi.ss.usermodel.Sheet;
import org.apache.poi.ss.usermodel.Workbook;
import org.apache.poi.ss.usermodel.WorkbookFactory;
import org.apache.poi.ss.util.CellReference;

/**
 * Equivalente a PhpSpreadsheet $hoja->toArray(null, true, true, true):
 * devuelve las filas (índice 0 = fila 1) como mapas columna-letra → valor formateado.
 */
public final class ExcelLector {

    private ExcelLector() {
    }

    /** @param hojaPreferida nombre de hoja a usar si existe (null = hoja activa) */
    public static List<Map<String, String>> leer(InputStream in, String hojaPreferida) throws Exception {
        try (Workbook wb = WorkbookFactory.create(in)) {
            Sheet hoja = hojaPreferida != null ? wb.getSheet(hojaPreferida) : null;
            if (hoja == null) hoja = wb.getSheetAt(wb.getActiveSheetIndex());
            FormulaEvaluator ev = wb.getCreationHelper().createFormulaEvaluator();
            DataFormatter fmt = new DataFormatter();
            List<Map<String, String>> filas = new ArrayList<>();
            int ultima = hoja.getLastRowNum();
            for (int r = 0; r <= ultima; r++) {
                Row row = hoja.getRow(r);
                Map<String, String> m = new LinkedHashMap<>();
                if (row != null) {
                    for (Cell c : row) {
                        String v;
                        try {
                            v = fmt.formatCellValue(c, ev);
                        } catch (Exception e) {
                            v = fmt.formatCellValue(c);
                        }
                        if (v != null && !v.isEmpty()) m.put(CellReference.convertNumToColString(c.getColumnIndex()), v);
                    }
                }
                filas.add(m);
            }
            return filas;
        }
    }
}
