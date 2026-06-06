<?php
session_start();

// 1. Validar sesión
if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config/conexion.php'; 

/* =========================================================
   2. CONSULTAS PARA TARJETAS DE MÉTRICAS (KPIs)
========================================================= */
try {
    // Total Histórico Vendido (Solo facturas PAGADAS)
    $stmtTotal = $pdo->query("SELECT SUM(total_neto) AS total FROM facturas WHERE estado_factura = 'PAGADA'");
    $total_historico = $stmtTotal->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Ventas del Día de Hoy
    $stmtHoy = $pdo->query("SELECT SUM(total_neto) AS total FROM facturas WHERE estado_factura = 'PAGADA' AND CAST(fecha_factura AS DATE) = CAST(GETDATE() AS DATE)");
    $total_hoy = $stmtHoy->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Cantidad de Facturas Emitidas
    $stmtCant = $pdo->query("SELECT COUNT(*) AS cant FROM facturas");
    $total_facturas = $stmtCant->fetch(PDO::FETCH_ASSOC)['cant'] ?? 0;

    // Cantidad de Facturas Anuladas
    $stmtAnuladas = $pdo->query("SELECT COUNT(*) AS cant FROM facturas WHERE estado_factura = 'ANULADA'");
    $total_anuladas = $stmtAnuladas->fetch(PDO::FETCH_ASSOC)['cant'] ?? 0;

/* =========================================================
   3. OBTENER EL LISTADO DETALLADO DE VENTAS
========================================================= */
    $sqlVentas = "SELECT f.*, 
                         c.nombre + ' ' + c.apellido AS cliente_nombre, 
                         m.nombre_metodo, 
                         u.usuario AS nombre_cajero
                  FROM facturas f
                  INNER JOIN clientes c ON f.id_cliente = c.id_cliente
                  INNER JOIN metodos_pago m ON f.id_metodo_pago = m.id_metodo
                  INNER JOIN usuarios u ON f.id_usuario = u.id
                  ORDER BY f.fecha_factura DESC";
    $listado_ventas = $pdo->query($sqlVentas)->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error al consultar las ventas: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control de Ventas - DACANS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <style>
        body { background-color: #f8fafc; font-family: system-ui, sans-serif; }
        table.dataTable { width: 100% !important; border-collapse: separate !important; border-spacing: 0; font-size: 13px; }
        table.dataTable thead th { background: #0f172a; color: white; border: none !important; padding: 14px !important; }
        table.dataTable tbody td { padding: 12px 14px !important; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
    </style>
</head>
<body class="p-4 md:p-8">

<div class="max-w-7xl mx-auto">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="bg-blue-600 text-white w-12 h-12 rounded-2xl flex items-center justify-center text-xl shadow-lg shadow-blue-500/20">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Monitoreo de Ventas</h1>
                <p class="text-xs text-slate-500">Resumen de ingresos de DACANS Computers, estados de cuenta y KPIs financieros</p>
            </div>
        </div>
        <div class="flex gap-2">

         <a href="index.php" class="bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl font-black text-xs transition flex items-center gap-1 shadow-md">
                <i class="fa-solid fa-plus"></i> Regresar a Ventas
            </a>
            <a href="crear.php" class="bg-slate-900 hover:bg-slate-800 text-white px-5 py-2.5 rounded-xl font-black text-xs transition flex items-center gap-1 shadow-md">
                <i class="fa-solid fa-plus"></i> Nueva Venta
            </a>

            
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ventas de Hoy</p>
                <h3 class="text-xl font-black text-slate-900 mt-1">RD$ <?= number_format($total_hoy, 2) ?></h3>
            </div>
            <div class="bg-emerald-50 text-emerald-600 w-10 h-10 rounded-xl flex items-center justify-center text-lg">
                <i class="fa-solid fa-calendar-day"></i>
            </div>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Ingreso Total</p>
                <h3 class="text-xl font-black text-slate-900 mt-1">RD$ <?= number_format($total_historico, 2) ?></h3>
            </div>
            <div class="bg-blue-50 text-blue-600 w-10 h-10 rounded-xl flex items-center justify-center text-lg">
                <i class="fa-solid fa-money-bill-trend-up"></i>
            </div>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Facturas Emitidas</p>
                <h3 class="text-xl font-black text-slate-900 mt-1"><?= $total_facturas ?> u.</h3>
            </div>
            <div class="bg-slate-100 text-slate-700 w-10 h-10 rounded-xl flex items-center justify-center text-lg">
                <i class="fa-solid fa-file-invoice"></i>
            </div>
        </div>

        <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Facturas Anuladas</p>
                <h3 class="text-xl font-black text-rose-600 mt-1"><?= $total_anuladas ?> u.</h3>
            </div>
            <div class="bg-rose-50 text-rose-600 w-10 h-10 rounded-xl flex items-center justify-center text-lg">
                <i class="fa-solid fa-ban"></i>
            </div>
        </div>
    </div>

    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table id="tablaVentas" class="display w-full">
                <thead>
                    <tr>
                        <th>No. Factura</th>
                        <th>Fecha / Hora</th>
                        <th>Cliente</th>
                        <th>Método Pago</th>
                        <th>Cajero/Usuario</th>
                        <th class="text-right">Total Neto</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                  <?php foreach ($listado_ventas as $v): ?>
    <tr class="<?= $v['estado_factura'] === 'ANULADA' ? 'bg-rose-50/40 text-slate-400' : '' ?>">
        <td class="font-mono font-bold">
            <a href="detalle_factura.php?id=<?= $v['id_factura'] ?>" class="text-blue-600 hover:text-blue-800 hover:underline">
                <?= htmlspecialchars($v['numero_factura']) ?>
            </a>
        </td>
        <td class="text-slate-600"><?= date('d/m/Y h:i A', strtotime($v['fecha_factura'])) ?></td>
        <td class="font-bold text-slate-800"><?= htmlspecialchars($v['cliente_nombre']) ?></td>
        <td class="text-xs font-semibold text-slate-600">
            <span class="bg-slate-100 px-2 py-1 rounded-md border border-slate-200"><?= htmlspecialchars($v['nombre_metodo']) ?></span>
        </td>
        <td class="font-medium text-slate-600">
            <i class="fa-solid fa-user-tie mr-1 text-[10px] text-slate-400"></i><?= htmlspecialchars($v['nombre_cajero']) ?>
        </td>
        <td class="text-right font-black text-slate-900">RD$ <?= number_format($v['total_neto'], 2) ?></td>
        <td class="text-center">
            <?php if ($v['estado_factura'] === 'PAGADA'): ?>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">PAGADA</span>
            <?php else: ?>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800">ANULADA</span>
            <?php endif; ?>
        </td>
        <td class="text-center">
            <div class="flex items-center justify-center gap-1.5">
                <a href="detalle_factura.php?id=<?= $v['id_factura'] ?>" 
                   class="p-2 bg-blue-50 hover:bg-blue-100 text-blue-600 rounded-lg text-xs font-bold transition" title="Ver Detalle de Factura">
                    <i class="fa-solid fa-eye"></i>
                </a>

                <a href="generar_pdf_factura.php?id=<?= $v['id_factura'] ?>" target="_blank" 
                   class="p-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 rounded-lg text-xs font-bold transition" title="Ver Factura PDF">
                    <i class="fa-solid fa-file-pdf"></i>
                </a>
            </div>
        </td>
    </tr>
<?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function () {
    $('#tablaVentas').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[1, "desc"]], // Ordenar por fecha por defecto (más reciente primero)
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' }
    });
});
</script>
</body>
</html>