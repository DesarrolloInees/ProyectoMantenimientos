<?php
if (!defined('ENTRADA_PRINCIPAL')) die("Acceso denegado.");

use Spatie\Browsershot\Browsershot;

class ServicioPdfGenerator
{
    public function generarPdf($modelo, $idOrden, $datosOrden = null)
    {
        $datosOrden = $datosOrden ?: $modelo->obtenerDatosCompletosOrden($idOrden);

        if (!$datosOrden) {
            return [
                'ok' => false,
                'error' => 'La orden no existe.',
                'nombreArchivo' => null,
                'contenido' => null
            ];
        }

        $evidencias = $modelo->obtenerEvidenciasOrden($idOrden);
        $novedades = $modelo->obtenerNovedadesOrden($idOrden);
        $repuestos = $modelo->obtenerRepuestosOrden($idOrden);

        try {
            $html = $this->renderizar($datosOrden, $evidencias, $novedades, $repuestos);
            $contenido = $this->crearBrowsershot($html)->pdf();

            return [
                'ok' => true,
                'error' => null,
                'nombreArchivo' => $this->construirNombreArchivo($datosOrden, $idOrden),
                'contenido' => $contenido,
                'datos' => $datosOrden
            ];
        } catch (Throwable $e) {
            error_log('Error generando PDF de orden ' . $idOrden . ': ' . $e->getMessage());

            return [
                'ok' => false,
                'error' => $e->getMessage(),
                'nombreArchivo' => null,
                'contenido' => null
            ];
        }
    }

    private function renderizar($datosOrden, $evidencias, $novedades, $repuestos)
    {
        $nivelInicial = ob_get_level();
        ob_start();

        try {
            include __DIR__ . '/../views/orden/plantillaPdfServicio.php';

            return ob_get_clean();
        } catch (Throwable $e) {
            while (ob_get_level() > $nivelInicial) {
                ob_end_clean();
            }

            throw $e;
        }
    }

    private function crearBrowsershot($html)
    {
        $nodePath = getenv('NODE_BINARY') ?: 'C:\\Program Files\\nodejs\\node.exe';
        $npmPath = getenv('NPM_BINARY') ?: 'C:\\Program Files\\nodejs\\npm.cmd';

        $posiblesRutasChrome = array_filter([
            getenv('CHROME_PATH') ?: null,
            'C:\\Users\\User\\.cache\\puppeteer\\chrome\\win64-144.0.7559.96\\chrome-win64\\chrome.exe',
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            getenv('LOCALAPPDATA') ? getenv('LOCALAPPDATA') . '\\Google\\Chrome\\Application\\chrome.exe' : null,
        ]);

        $chromePath = null;
        foreach ($posiblesRutasChrome as $ruta) {
            if ($ruta && file_exists($ruta)) {
                $chromePath = $ruta;
                break;
            }
        }

        $browsershot = Browsershot::html($html)
            ->setNodeBinary($nodePath)
            ->setNpmBinary($npmPath)
            ->setOption('args', ['--no-sandbox'])
            ->format('A4')
            ->margins(10, 10, 10, 10)
            ->timeout(120);

        if ($chromePath) {
            $browsershot->setChromePath($chromePath);
        }

        return $browsershot;
    }

    public function construirNombreArchivo($datosOrden, $idOrden = null)
    {
        $numeroRemision = $datosOrden['numero_remision'] ?? $idOrden;
        $punto = $this->limpiarSegmento($datosOrden['nombre_punto'] ?? '', 'Sin_Punto');
        $fecha = !empty($datosOrden['fecha_visita'])
            ? date('Y-m-d', strtotime($datosOrden['fecha_visita']))
            : date('Y-m-d');

        $partes = [
            'Servicio',
            $this->limpiarSegmento($numeroRemision, 'Sin_Remision'),
            $punto,
            $fecha
        ];

        return implode('_', array_filter($partes)) . '.pdf';
    }

    public function construirRutaRelativa($datosOrden, $nombreArchivo, $incluirDelegacion, $incluirTecnico)
    {
        $partes = [];

        if ($incluirDelegacion) {
            $delegacion = $datosOrden['nombre_delegacion'] ?? $datosOrden['delegacion'] ?? '';
            $partes[] = $this->limpiarSegmento($delegacion, 'SIN_DELEGACION');
        }

        if ($incluirTecnico) {
            $partes[] = $this->limpiarSegmento($datosOrden['nombre_tecnico'] ?? '', 'SIN_TECNICO');
        }

        $partes[] = $nombreArchivo;

        return implode('/', $partes);
    }

    public function limpiarSegmento($texto, $fallback = 'SIN_DATO')
    {
        $texto = trim((string) $texto);

        if ($texto === '') {
            return $fallback;
        }

        $convertido = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        if ($convertido !== false) {
            $texto = $convertido;
        }

        $texto = preg_replace('/[<>:"\/\\\\|?*\x00-\x1F]/u', '_', $texto);
        $texto = preg_replace('/\s+/u', '_', $texto);
        $texto = preg_replace('/_+/u', '_', $texto);
        $texto = trim($texto, '._-');
        $texto = substr($texto, 0, 90);
        $texto = trim($texto, '._-');

        return $texto !== '' ? $texto : $fallback;
    }
}
