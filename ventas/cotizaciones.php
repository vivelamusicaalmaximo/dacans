<?php
session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config/conexion.php'; 

// --- BLOQUE DE AUTODIAGNÓSTICO AUTOMÁTICO ---
try {
    $columnas_reales = [];
    $stmtDesc = $pdo->query("SELECT TOP 1 * FROM cotizaciones");
    for ($i = 0; $i < $stmtDesc->columnCount(); $i++) {
        $meta = $stmtDesc->getColumnMeta($i);
        $columnas_reales[] = $meta['name'];
    }
} catch (Exception $e) {
    die("<div style='padding:20px; background:#fef2f2; border:1px solid #f87171; color:#991b1b; font-family:sans-serif; border-radius:8px;'>
            <strong>Error Crítico:</strong> No se pudo leer la estructura de la tabla 'cotizaciones'. ¿Seguro que el objeto existe o tienes permisos?<br><br>
            <em>Detalle técnico: " . $e->getMessage() . "</em>
         </div>");
}

// Mapeo inteligente de columnas
$col_id     = in_array('id_cotizacion', $columnas_reales) ? 'id_cotizacion' : ($columnas_reales[0] ?? 'id');
$col_numero = in_array('numero_cotizacion', $columnas_reales) ? 'numero_cotizacion' : (in_array('numero', $columnas_reales) ? 'numero' : $columnas_reales[1]);
$col_fecha  = in_array('fecha_cotizacion', $columnas_reales) ? 'fecha_cotizacion' : (in_array('fecha', $columnas_reales) ? 'fecha' : $columnas_reales[2]);
$col_total  = in_array('total_neto', $columnas_reales) ? 'total_neto' : (in_array('total', $columnas_reales) ? 'total' : (in_array('monto', $columnas_reales) ? 'monto' : $columnas_reales[3]));
$col_estado = in_array('estado', $columnas_reales) ? 'estado' : null;

$col_cliente = null;
foreach ($columnas_reales as $col) {
    if (stripos($col, 'client') !== false || stripos($col, 'nombre') !== false || stripos($col, 'user') !== false) {
        $col_cliente = $col;
        break;
    }
}
if (!$col_cliente) {
    $col_cliente = $columnas_reales[4] ?? $columnas_reales[0];
}

try {
    // Consultar estadísticas usando las columnas mapeadas
    $estado_query = $col_estado ? "SUM(CASE WHEN $col_estado = 'PENDIENTE' OR $col_estado IS NULL THEN 1 ELSE 0 END)" : "0";
    $aceptadas_query = $col_estado ? "SUM(CASE WHEN $col_estado = 'ACEPTADA' THEN 1 ELSE 0 END)" : "0";
    
    $sqlStats = "SELECT COUNT(*) as total, $estado_query as pendientes, $aceptadas_query as aceptadas FROM cotizaciones";
    $stmtStats = $pdo->query($sqlStats);
    $stats = $stmtStats->fetch(PDO::FETCH_ASSOC);

    // Carga de datos dinámica
    $busqueda = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
    
    $sqlCotizaciones = "SELECT $col_id AS id_cotizacion, 
                               $col_numero AS numero_cotizacion, 
                               $col_fecha AS fecha_cotizacion, 
                               $col_total AS total_neto, 
                               " . ($col_estado ? "$col_estado" : "'PENDIENTE'") . " AS estado,
                               $col_cliente AS cliente_nombre
                        FROM cotizaciones";
    
    if (!empty($busqueda)) {
        $sqlCotizaciones .= " WHERE $col_numero LIKE ? OR $col_cliente LIKE ? ORDER BY $col_id DESC";
        $stmtCot = $pdo->prepare($sqlCotizaciones);
        $stmtCot->execute(["%$busqueda%", "%$busqueda%"]);
    } else {
        $sqlCotizaciones .= " ORDER BY $col_id DESC";
        $stmtCot = $pdo->query($sqlCotizaciones);
    }
    
    $listado_cotizaciones = $stmtCot->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("<div style='padding:25px; background:#fff7ed; border:1px solid #fb923c; color:#c2410c; font-family:monospace; border-radius:12px; max-width:600px; margin:20px auto;'>
            <h3 style='margin-top:0; color:#ea580c;'>⚠️ Error de Columnas en la Consulta</h3>
            <p>Las columnas que pusimos no coinciden. Aquí tienes la lista de columnas <strong>REALES</strong> encontradas en tu tabla <code>cotizaciones</code>:</p>
            <ul style='background:#fef3c7; padding:15px 30px; border-radius:8px;'>
                <li>" . implode("</li><li>", $columnas_reales) . "</li>
            </ul>
            <p style='font-size:12px; color:#7c2d12;'>Detalle del error: " . $e->getMessage() . "</p>
         </div>");
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Cotizaciones - DACANS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-slate-50 text-slate-800 font-sans antialiased min-h-screen">

    <div class="max-w-7xl mx-auto px-4 py-8">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Control de Cotizaciones</h1>
                <p class="text-sm text-slate-500 mt-1">Gestiona y dale seguimiento a los presupuestos del negocio.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs text-slate-400 font-bold uppercase tracking-wider block">Emitidas</span>
                    <span class="text-2xl font-black text-slate-900 block mt-1"><?= $stats['total'] ?? 0; ?></span>
                </div>
                <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-lg">
                    <i class="fa-solid fa-file-invoice"></i>
                </div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs text-slate-400 font-bold uppercase tracking-wider block">Pendientes</span>
                    <span class="text-2xl font-black text-amber-600 block mt-1"><?= $stats['pendientes'] ?? 0; ?></span>
                </div>
                <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-lg">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs text-slate-400 font-bold uppercase tracking-wider block">Aceptadas</span>
                    <span
                        class="text-2xl font-black text-emerald-600 block mt-1"><?= $stats['aceptadas'] ?? 0; ?></span>
                </div>
                <div
                    class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-lg">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>
        </div>

        <div
            class="bg-white p-4 rounded-t-2xl border-t border-x border-slate-200 flex flex-col sm:flex-row gap-4 justify-between items-center">
            <form method="GET" action="" class="w-full sm:w-96 flex gap-2">
                <input type="text" name="buscar" value="<?= htmlspecialchars($busqueda) ?>"
                    placeholder="Buscar por número o cliente..."
                    class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-blue-500 font-medium transition">
                <button type="submit"
                    class="bg-slate-900 text-white px-4 rounded-lg text-sm font-semibold hover:bg-slate-800 transition">Buscar</button>
            </form>
        </div>

        <div class="bg-white rounded-b-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-slate-900 text-slate-200 text-xs font-bold uppercase tracking-wider border-b border-slate-200">
                            <th class="py-4 px-6 w-34">No. Cotización</th>
                            <th class="py-4 px-6 w-44">Fecha</th>
                            <th class="py-4 px-6">Cliente / Info</th>
                            <th class="py-4 px-6 text-right w-40">Monto</th>
                            <th class="py-4 px-6 text-center w-32">Estado</th>
                            <th class="py-4 px-6 text-center w-40">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <?php if (empty($listado_cotizaciones)): ?>
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400 font-medium">No se encontraron
                                cotizaciones registradas.</td>
                        </tr>
                        <?php endif; ?>

                        <?php foreach ($listado_cotizaciones as $c): ?>
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-4 px-6 font-mono font-bold text-blue-600">
                                <?= htmlspecialchars($c['numero_cotizacion']) ?>
                            </td>
                            <td class="py-4 px-6 text-slate-500">
                                <?= !empty($c['fecha_cotizacion']) ? htmlspecialchars($c['fecha_cotizacion']) : 'N/A' ?>
                            </td>
                            <td class="py-4 px-6 font-bold text-slate-800">
                                <?= htmlspecialchars($c['cliente_nombre'] ?? 'Sin Asignar') ?>
                            </td>
                            <td class="py-4 px-6 text-right font-black text-slate-950">
                                RD$ <?= is_numeric($c['total_neto']) ? number_format($c['total_neto'], 2) : '0.00' ?>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <?php if (strtoupper($c['estado']) === 'ACEPTADA'): ?>
                                <span
                                    class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800 uppercase">Aceptada</span>
                                <?php else: ?>
                                <span
                                    class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800 uppercase">Pendiente</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <div class="flex items-center justify-center">
                                    <?php if (strtoupper($c['estado']) === 'ACEPTADA'): ?>
                                    <span
                                        class="text-xs text-slate-400 font-medium bg-slate-100 px-3 py-1.5 rounded-lg flex items-center gap-1.5 cursor-not-allowed">
                                        <i class="fa-solid fa-lock text-slate-400"></i> Facturado
                                    </span>
                                    <?php else: ?>
                                    <a href="crear.php?id_cotizacion=<?= urlencode($c['id_cotizacion']) ?>"
                                        onclick="return confirm('¿Está seguro de que desea aceptar esta cotización e iniciar el proceso de facturación?');"
                                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs px-3 py-1.5 rounded-lg transition-all shadow-sm hover:shadow flex items-center gap-1.5">
                                        <i class="fa-solid fa-cart-shopping"></i> Aceptar y Facturar
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>

</html>