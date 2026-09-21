<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

/**
 * Helper compartido: clasifica los servicios por tipo de mantenimiento
 * (Preventivo Básico / Preventivo Profundo / Correctivo / Fallido / Otros + Total).
 *
 * Se usa en:
 *  - reporteTecnico (respeta filtros de técnico + fechas del formulario)
 *  - inicio rol técnico (mes actual del técnico logueado)
 *
 * La clasificación es por NOMBRE (normalizado: mayúsculas, sin tildes)
 * para no depender de los IDs numéricos de tipo_mantenimiento.
 * Orden de evaluación: fallido > profundo > correctivo > básico.
 */
class ResumenTiposServicio
{
    public static function normalizar($texto)
    {
        $t = mb_strtoupper(trim((string)$texto), 'UTF-8');
        $t = strtr($t, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'Ü' => 'U', 'Ñ' => 'N'
        ]);
        return $t;
    }

    public static function clasificar($nombreTipo)
    {
        $t = self::normalizar($nombreTipo);

        if ($t === '' || $t === 'SIN ESPECIFICAR' || $t === 'N/A') {
            return 'otros';
        }
        // 1. Fallidos primero (ej: "Visita Fallida", "Mantenimiento Fallido",
        //    "Instalación Fallida"). También atrapa remisiones sueltas fallidas.
        if (strpos($t, 'FALLID') !== false) {
            return 'fallido';
        }
        // 2. Preventivo profundo (ej: "Preventivo Profundo / Completo")
        if (strpos($t, 'PROFUNDO') !== false || strpos($t, 'COMPLETO') !== false) {
            return 'profundo';
        }
        // 3. Correctivo (ej: "Mantenimiento Correctivo", "Reparación")
        if (strpos($t, 'CORRECTIVO') !== false || strpos($t, 'REPARACION') !== false) {
            return 'correctivo';
        }
        // 4. Preventivo básico (ej: "Preventivo Básico"; un "Preventivo" a secas
        //    se asume básico para no perderlo)
        if (strpos($t, 'PREVENTIVO') !== false || strpos($t, 'BASICO') !== false) {
            return 'basico';
        }
        // 5. Resto (Garantía, Kisan, instalaciones, desinstalaciones, etc.)
        return 'otros';
    }

    /**
     * Resume un listado de filas que tengan la clave 'tipo_mantenimiento'.
     * Retorna: ['basico'=>n, 'profundo'=>n, 'correctivo'=>n,
     *           'fallido'=>n, 'otros'=>n, 'total'=>n]
     * Invariante: basico+profundo+correctivo+fallido+otros === total
     */
    public static function resumir($filas)
    {
        $r = ['basico' => 0, 'profundo' => 0, 'correctivo' => 0, 'fallido' => 0, 'otros' => 0, 'total' => 0];
        if (!is_array($filas)) {
            return $r;
        }
        foreach ($filas as $f) {
            $cat = self::clasificar(isset($f['tipo_mantenimiento']) ? $f['tipo_mantenimiento'] : '');
            if (!isset($r[$cat])) {
                $cat = 'otros';
            }
            $r[$cat]++;
            $r['total']++;
        }
        return $r;
    }

    public static function vacio()
    {
        return ['basico' => 0, 'profundo' => 0, 'correctivo' => 0, 'fallido' => 0, 'otros' => 0, 'total' => 0];
    }
}
