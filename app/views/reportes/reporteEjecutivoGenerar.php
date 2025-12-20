<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Ejecutivo</title>
    <style>
        @page { 
            margin: 0.5cm;
            size: A4 landscape;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #2c3e50;
            font-size: 11px;
        }

        .slide {
            width: 100%;
            height: 19.5cm;
            page-break-after: always;
            position: relative;
            background: white;
            overflow: hidden;
        }
        .slide:last-child {
            page-break-after: avoid;
        }

        /* PÁGINA DE PORTADA */
        .cover-slide {
            background: #667eea;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            position: relative;
        }
        .cover-content {
            padding: 40px;
            width: 100%;
        }
        .cover-icon {
            font-size: 80px;
            margin-bottom: 20px;
        }
        .cover-title {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 15px;
            letter-spacing: -1px;
        }
        .cover-subtitle {
            font-size: 24px;
            margin-bottom: 40px;
            font-weight: 300;
        }
        .cover-period {
            background: rgba(255,255,255,0.15);
            padding: 15px 30px;
            border-radius: 8px;
            font-size: 18px;
            margin: 0 auto 20px auto;
            display: inline-block;
            border: 2px solid rgba(255,255,255,0.3);
        }
        .cover-kpis {
            margin: 40px auto;
            max-width: 800px;
            display: table;
            width: 100%;
        }
        .cover-kpi {
            display: table-cell;
            padding: 20px;
            text-align: center;
            border-right: 1px solid rgba(255,255,255,0.2);
        }
        .cover-kpi:last-child {
            border-right: none;
        }
        .cover-kpi-value {
            font-size: 40px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .cover-kpi-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .cover-date {
            font-size: 14px;
            margin-top: 40px;
        }
        .cover-footer {
            position: absolute;
            bottom: 30px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 12px;
            border-top: 1px solid rgba(255,255,255,0.2);
            padding-top: 20px;
        }

        /* PÁGINAS INTERNAS */
        .content-slide {
            padding: 15px;
            display: flex;
            flex-direction: column;
        }

        /* ENCABEZADO COMPACTO */
        .header {
            background: #667eea;
            padding: 10px 20px;
            margin: -15px -15px 10px -15px;
            border-radius: 0;
            color: white;
            position: relative;
        }
        .header-title {
            font-size: 18px;
            font-weight: 700;
            margin: 0;
            position: relative;
        }
        .header-meta {
            margin-top: 3px;
            font-size: 10px;
            position: relative;
        }

        /* GRID DE KPIS COMPACTO */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin: 8px 0;
        }
        .kpi-card {
            background: white;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            position: relative;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--kpi-color, #667eea);
        }
        .kpi-icon {
            font-size: 22px;
            margin-bottom: 4px;
            opacity: 0.8;
        }
        .kpi-value {
            font-size: 26px;
            font-weight: 700;
            color: var(--kpi-color, #667eea);
            display: block;
            margin: 4px 0;
            line-height: 1;
        }
        .kpi-label {
            font-size: 9px;
            text-transform: uppercase;
            color: #7f8c8d;
            font-weight: 600;
            letter-spacing: 0.3px;
            margin-top: 4px;
        }
        .kpi-sub {
            font-size: 8px;
            color: #95a5a6;
            margin-top: 2px;
            display: block;
        }

        /* SECCIÓN DE GRÁFICAS COMPACTA */
        .section-title {
            font-size: 13px;
            font-weight: 700;
            color: #2c3e50;
            margin: 8px 0 6px 0;
            padding-bottom: 4px;
            border-bottom: 2px solid #667eea;
            display: inline-block;
        }

        .chart-container {
            background: white;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        .chart-title {
            color: #34495e;
            font-size: 11px;
            margin-bottom: 6px;
            font-weight: 600;
            text-align: center;
        }
        .chart-img {
            width: 100%;
            max-width: 100%;
            height: 100%;
            max-height: 100%;
            border-radius: 6px;
            object-fit: contain;
        }

        .chart-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 8px;
            flex: 1;
            min-height: 0;
        }

        /* CONTENEDOR PARA GRÁFICA GRANDE */
        .chart-full {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        /* PIE DE PÁGINA */
        .footer {
            position: absolute;
            bottom: 8px;
            left: 50%;
            transform: translateX(-50%);
            text-align: center;
            font-size: 9px;
            color: #95a5a6;
            padding: 5px 12px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            white-space: nowrap;
        }

        /* BADGE DE PÁGINA */
        .page-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #667eea;
            color: white;
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 600;
            z-index: 10;
        }

        /* ESPACIADO FLEXIBLE */
        .content-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
            overflow: hidden;
        }
    </style>
</head>
<body>

    

    <!-- PÁGINA 1: TENDENCIAS -->
    <div class="slide content-slide">
        <div class="page-badge">1/5</div>
        
        <div class="header">
            <div class="header-title"> Tendencias y Evolución</div>
            <div class="header-meta">
                Análisis temporal del período
            </div>
        </div>

        <div class="content-wrapper">
            

            <div class="section-title">Evolución Diaria de Servicios</div>
            <div class="chart-container chart-full">
                <div class="chart-title">Servicios realizados por día en el período analizado</div>
                <img src="<?= $graficas['dias'] ?>" class="chart-img">
            </div>
        </div>

        <div class="footer">Reporte Ejecutivo | Confidencial</div>
    </div>

    <!-- PÁGINA 2: ANÁLISIS DE MANTENIMIENTO -->
    <div class="slide content-slide">
        <div class="page-badge">2/5</div>
        
        <div class="header">
            <div class="header-title"> Análisis de Mantenimiento y Estados</div>
            <div class="header-meta">
                Distribución por tipo y estado final
            </div>
        </div>

        <div class="content-wrapper">
            <div class="section-title"> Distribución y Estados</div>
            <div class="chart-grid">
                <div class="chart-container">
                    <div class="chart-title">Distribución por Tipo de Servicio</div>
                    <img src="<?= $graficas['tipo'] ?>" class="chart-img">
                </div>
                <div class="chart-container">
                    <div class="chart-title">Estado Final de Servicios</div>
                    <img src="<?= $graficas['estados'] ?>" class="chart-img">
                </div>
            </div>

            <div class="section-title"> Top Delegaciones Intervenidas</div>
            <div class="chart-container chart-full">
                <div class="chart-title">Delegaciones con mayor número de intervenciones</div>
                <img src="<?= $graficas['delegaciones'] ?>" class="chart-img">
            </div>
        </div>

        <div class="footer">Reporte Ejecutivo | Confidencial</div>
    </div>

    <!-- PÁGINA 3: PRODUCTIVIDAD -->
    <div class="slide content-slide">
        <div class="page-badge">3/5</div>
        
        <div class="header">
            <div class="header-title"> Productividad Técnica y Recursos</div>
            <div class="header-meta">
                Rendimiento del equipo y uso de repuestos
            </div>
        </div>

        <div class="content-wrapper">
            <div class="section-title"> Rendimiento del Equipo Técnico</div>
            <div class="chart-container" style="flex: 1.5;">
                <div class="chart-title">Top 15 Técnicos por Número de Servicios Realizados</div>
                <img src="<?= $graficas['tecnicos'] ?>" class="chart-img">
            </div>

            <div class="section-title"> Gestión de Repuestos</div>
            <div class="chart-container" style="flex: 1;">
                <div style="max-width: 400px; margin: 0 auto; height: 100%;">
                    <div class="chart-title">Distribución de Repuestos por Origen</div>
                    <img src="<?= $graficas['repuestos'] ?>" class="chart-img">
                </div>
            </div>
        </div>

        <div class="footer">Reporte Ejecutivo | Confidencial</div>
    </div>

    <!-- PÁGINA 4: ANÁLISIS DE PUNTOS -->
    <div class="slide content-slide">
        <div class="page-badge">4/5</div>
        
        <div class="header">
            <div class="header-title"> Análisis de Puntos Críticos</div>
            <div class="header-meta">
                Puntos con mayor actividad y problemas recurrentes
            </div>
        </div>

        <div class="content-wrapper">
            <div class="section-title"> Puntos Más Visitados ( Más de 3 servicios)</div>
            <div class="chart-container" style="flex: 1;">
                <div class="chart-title">Puntos que requirieron 3 o más servicios en el período</div>
                <img src="<?= $graficas['puntos_visitados'] ?>" class="chart-img">
            </div>

            <div class="section-title"> Puntos con Servicios Fallidos ( Más de 2 fallidos)</div>
            <div class="chart-container" style="flex: 1;">
                <div class="chart-title">Puntos con 2 o más servicios fallidos - Requieren atención prioritaria</div>
                <img src="<?= $graficas['puntos_fallidos'] ?>" class="chart-img">
            </div>
        </div>

        <div class="footer">Reporte Ejecutivo | Confidencial</div>
    </div>

    <!-- PÁGINA 5: CALIDAD Y SATISFACCIÓN -->
    <div class="slide content-slide">
        <div class="page-badge">5/5</div>
        
        <div class="header">
            <div class="header-title"> Calidad y Análisis de Fallas</div>
            <div class="header-meta">
                Calificaciones de servicio y delegaciones con más fallas
            </div>
        </div>

        <div class="content-wrapper">
            <div class="section-title"> Calificaciones del Servicio</div>
            <div class="chart-container" style="flex: 1;">
                <div class="chart-title">Distribución de calificaciones otorgadas por los clientes</div>
                <img src="<?= $graficas['calificaciones'] ?>" class="chart-img">
            </div>

            <div class="section-title"> Top Delegaciones con Servicios Fallidos</div>
            <div class="chart-container" style="flex: 1;">
                <div class="chart-title">Delegaciones que registraron mayor cantidad de servicios fallidos</div>
                <img src="<?= $graficas['fallidos_delegacion'] ?>" class="chart-img">
            </div>
        </div>

        <div class="footer">Reporte Ejecutivo | Confidencial</div>
    </div>

    <!-- PÁGINA 0: PORTADA -->
    <div class="slide cover-slide">
        <div class="cover-content">
            <div class="cover-icon"></div>
            <h1 class="cover-title">Reporte Ejecutivo</h1>
            <p class="cover-subtitle">Operaciones y Productividad</p>
            
            <div class="cover-period">
                <?= date('d/m/Y', strtotime($inicio)) ?> - <?= date('d/m/Y', strtotime($fin)) ?>
            </div>
            
            <table class="cover-kpis">
                <tr>
                    <td class="cover-kpi">
                        <div class="cover-kpi-value"><?= number_format($totalServicios) ?></div>
                        <div class="cover-kpi-label">Servicios</div>
                    </td>
                    <td class="cover-kpi">
                        <div class="cover-kpi-value"><?= $mediaDiaria ?></div>
                        <div class="cover-kpi-label">Media Diaria</div>
                    </td>
                    <td class="cover-kpi">
                        <div class="cover-kpi-value"><?= number_format($datosNovedad['con_novedad'] ?? 0) ?></div>
                        <div class="cover-kpi-label">Novedades</div>
                    </td>
                    <td class="cover-kpi">
                        <div class="cover-kpi-value"><?= count($datosDelegacion) ?></div>
                        <div class="cover-kpi-label">Delegaciones</div>
                    </td>
                </tr>
            </table>
            
            <div class="cover-date">
                Generado: <?= date('d/m/Y H:i') ?>
            </div>
        </div>
        
        <div class="cover-footer">
            Documento Confidencial
        </div>
    </div>

</body>
</html>