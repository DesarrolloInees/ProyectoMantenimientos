<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Cotizaciones Filtrado</title>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --verde: #16a34a;
            --azul: #1d4ed8;
            --azul-oscuro: #1e3a8a;
            --gris-bg: #f8fafc;
            --gris-borde: #e2e8f0;
            --negro: #0f172a;
        }
        body {
            font-family: 'Sora', sans-serif;
            font-size: 10px;
            color: var(--negro);
            margin: 0;
            padding: 0;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .divider {
            height: 4px;
            background: linear-gradient(to right, var(--verde) 40%, var(--azul) 60%, var(--azul-oscuro) 100%);
            border-radius: 2px;
            margin-bottom: 20px;
        }
        .filtros-info {
            background: var(--gris-bg);
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--azul);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: linear-gradient(to right, var(--verde), var(--azul-oscuro));
            color: white;
            text-transform: uppercase;
            padding: 8px;
            font-size: 9px;
            text-align: left;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid var(--gris-borde);
        }
        tr:nth-child(even) { background-color: #f1f5f9; }
        
        .totales {
            margin-top: 15px;
            text-align: right;
            font-size: 14px;
            font-weight: bold;
            color: var(--azul-oscuro);
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h2 style="margin:0; color:var(--azul-oscuro);">REPORTE DINÁMICO DE COTIZACIONES</h2>
            <span style="color:#475569;">Generado el: <?= date('d/m/Y H:i') ?></span>
        </div>
    </div>
    
    <div class="divider"></div>

    <div class="filtros-info">
        <strong>Filtros aplicados:</strong> 
        <?= !empty($filtros['fecha_inicio']) ? "Desde: {$filtros['fecha_inicio']} " : "" ?>
        <?= !empty($filtros['fecha_fin']) ? "Hasta: {$filtros['fecha_fin']} " : "" ?>
        <?= !empty($filtros['categoria']) ? "| Cat: {$filtros['categoria']} " : "" ?>
        <?= !empty($filtros['estado']) ? "| Estado: {$filtros['estado']} " : "" ?>
        <?= (empty(array_filter($filtros))) ? "Mostrando todos los registros." : "" ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>N° Cotización</th>
                <th>N° Remisión</th>
                <th>Fecha Realización</th>
                <th>Categoría</th>
                <th>Items</th>
                <th>Estado</th>
                <th style="text-align:right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $granTotal = 0;
            foreach ($datosReporte as $row): 
                $granTotal += $row['subtotal'];
            ?>
                <tr>
                    <td><strong><?= htmlspecialchars($row['n_cotizacion']) ?></strong></td>
                    <td><?= htmlspecialchars($row['n_remision']) ?></td>
                    <td><?= htmlspecialchars($row['fecha_realizacion']) ?></td>
                    <td><?= htmlspecialchars($row['categoria']) ?></td>
                    <td><?= htmlspecialchars(substr($row['items'], 0, 30)) ?>...</td>
                    <td><?= htmlspecialchars($row['estado']) ?></td>
                    <td style="text-align:right;">$ <?= number_format($row['subtotal'], 2, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totales">
        TOTAL REPORTE: $ <?= number_format($granTotal, 2, ',', '.') ?>
    </div>
</body>
</html>