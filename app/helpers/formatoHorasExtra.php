<?php
if (!defined('ENTRADA_PRINCIPAL'))
    die("Acceso denegado.");

/**
 * Helper compartido del módulo de Horas Extra.
 *
 * En la base de datos, registro_horas_extra.total_horas se guarda en
 * formato DECIMAL (ej: 2.75 = 2 horas 45 minutos), porque así las sumas
 * y los cálculos de nómina son matemáticamente correctos.
 *
 * Pero para MOSTRAR las horas al usuario se debe usar notación
 * SEXAGESIMAL (horas.minutos), donde la parte fraccionaria son minutos
 * de 00 a 59 (ej: 2.75 decimal -> "2.45" = 2 h 45 min).
 *
 * Uso:
 *   FormatoHorasExtra::decimalASexagesimal($r['total_horas'])  // "2.45"
 */
class FormatoHorasExtra
{
    /**
     * Convierte horas decimales (base 100) a notación sexagesimal "H.MM"
     * (base 60), redondeando al minuto exacto.
     *
     * Ejemplos:
     *   1.50 -> "1.30"   (1 hora 30 minutos)
     *   2.75 -> "2.45"   (2 horas 45 minutos)
     *   4.25 -> "4.15"   (4 horas 15 minutos)
     *
     * @param float|int|string|null $horasDecimal
     * @return string
     */
    public static function decimalASexagesimal($horasDecimal)
    {
        $horasDecimal = (float) $horasDecimal;
        if ($horasDecimal < 0) {
            $horasDecimal = 0;
        }

        $minutosTotales = (int) round($horasDecimal * 60);
        $horas = intdiv($minutosTotales, 60);
        $minutos = $minutosTotales % 60;

        return sprintf('%d.%02d', $horas, $minutos);
    }
}
