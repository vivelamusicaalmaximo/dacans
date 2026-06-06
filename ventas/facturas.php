<?php
session_start();

// Validar sesión
if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config/conexion.php'; 

$mensaje_success = "";
$mensaje_error = "";

/* =========================================================
   1. ACCIÓN: ANULAR FACTURA (BAJA LÓGICA / REVERSIÓN DE STOCK)
========================================================= */
if (isset($_GET['anular_id'])) {
    try {
        $id_factura = (int)$_GET['anular_id'];

        $pdo->beginTransaction();

        // 1.1 Verificar si la factura ya está anulada
        $stmtCheck = $pdo->prepare("SELECT estado_factura FROM facturas WHERE id_factura = ?");
        $stmtCheck->execute([$id_factura]);
        $factura = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$factura) {
            throw new Exception("La factura no existe.");
        }
        if ($factura['estado_factura'] === 'ANULADA') {
            throw new Exception("Esta factura ya se encuentra anulada.");
        }

        // 1.2 Cambiar estado de la factura a ANULADA
        $stmtAnular = $pdo->prepare("UPDATE facturas SET estado_factura = 'ANULADA' WHERE id_factura = ?");
        $stmtAnular->execute([$id_factura]);

        // 1.3 Devolver los productos al inventario disponible
        // Buscamos los productos asociados a esta factura en el detalle
        $stmtDetalle = $pdo->prepare("SELECT id_producto FROM factura_detalle WHERE id_factura = ?");
        $stmtDetalle->execute([$id_factura]);
        $productos_facturados = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);

        $stmtRestaurarProd = $pdo->prepare("UPDATE productos_informatica SET estado = 'Disponible', vendida_at = NULL WHERE id = ?");
        
        foreach ($productos_facturados as $item) {
            $stmtRestaurarProd->execute([$item['id_producto']]);
        }

        $pdo->commit();
        $mensaje_success = "Factura #<strong>$id_factura</strong> anulada correctamente. Los equipos han regresado al inventario.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensaje_error = "Error al anulan la factura: " . $e->getMessage();
    }
}

/* =========================================================
   2. PROCESAMIENTO AJAX: OBTENER DETALLE DE FACTURA
========================================================= */
if (isset($_GET['action']) && $_GET['action'] === 'get_detalle') {
    header('Content-Type: application/json');
    try {
        $id_fac = (int)$_GET['id'];
        
        // Consultar productos del detalle
        $sqlD = "SELECT fd.cantidad, fd.precio_unitario, fd.subtotal_linea, 
                        p.id_local, p.equipo_marca, p.equipo_modelo 
                 FROM factura_detalle fd
                 INNER JOIN productos_informatica p ON fd.id_producto = p.id
                 WHERE fd.id_factura = ?";
        $stmtD = $pdo->prepare($sqlD);
        $stmtD->execute([$id_fac]);
        $items = $stmtD->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['status' => 'success', 'data' => $items]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit; // Detener ejecución para que no renderice el HTML completo
}

/* =========================================================
   3. OBTENER EL HISTORIAL DE FACTURAS
========================================================= */
try {
    $sqlFacturas = "SELECT f.*, 
                           c.nombre + ' ' + c.apellido AS cliente_nombre, 
                           m.nombre_metodo, 
                           u.usuario AS nombre_cajero
                    FROM facturas f
                    INNER JOIN clientes c ON f.id_cliente = c.id_cliente
                    INNER JOIN metodos_pago m ON f.id_metodo_pago = m.id_metodo
                    INNER JOIN usuarios u ON f.id_usuario = u.id
                    ORDER BY f.id_factura DESC";
    $listado_facturas = $pdo->query($sqlFacturas)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al consultar el historial: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Facturas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    
    <style>
        body { background-color: #f8fafc; font-family: system-ui, sans-serif; }
        table.dataTable { width: 100% !important; border-collapse: separate !important; border-spacing: 0; font-size: 13px; }
        table.dataTable thead th { background: #0f172a; color: white; border: none !important; padding: 14px !important; }
        table.dataTable tbody td { padding: 12px 14px !important; border-bottom: 1px solid #e2e8f0; vertical-align: middle; }
        @media print {
            .no-print { display: none !important; }
            .print-area { display: block !important; }
        }
    </style>
</head>
<body class="p-4 md:p-8">

<div class="max-w-7xl mx-auto no-print">

    <?php if (!empty($mensaje_success)): ?>
        <div class="mb-6 p-4 bg-emerald-100 border border-emerald-300 text-emerald-800 rounded-2xl flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-circle-check text-xl"></i> <div><?= $mensaje_success ?></div>
        </div>
    <?php endif; ?>
    <?php if (!empty($mensaje_error)): ?>
        <div class="mb-6 p-4 bg-red-100 border border-red-300 text-red-800 rounded-2xl flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-circle-xmark text-xl"></i> <div><?= $mensaje_error ?></div>
        </div>
    <?php endif; ?>

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="bg-slate-900 text-white w-12 h-12 rounded-2xl flex items-center justify-center text-xl shadow-lg">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Historial de Facturas</h1>
                <p class="text-xs text-slate-500">Auditoría de transacciones, desglose de ITBIS y flujo de caja diario</p>
            </div>
        </div>
        <div class="flex gap-2">
            <a href="clientes.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2.5 rounded-xl font-bold text-xs transition">
                <i class="fa-solid fa-user-group mr-1"></i> Clientes
            </a>
            <a href="ventas.php" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-black text-xs transition flex items-center gap-1 shadow-md shadow-blue-500/10">
                <i class="fa-solid fa-plus"></i> Nueva Venta
            </a>
        </div>
    </div>

    <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table id="tablaFacturas" class="display w-full">
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
                    <?php foreach ($listado_facturas as $f): ?>
                        <tr class="<?= $f['estado_factura'] === 'ANULADA' ? 'bg-rose-50/40 text-slate-400' : '' ?>">
                            <td class="font-mono font-bold text-blue-600"><?= htmlspecialchars($f['numero_factura']) ?></td>
                            <td class="text-slate-600"><?= date('d/m/Y h:i A', strtotime($f['fecha_factura'])) ?></td>
                            <td class="font-bold text-slate-800"><?= htmlspecialchars($f['cliente_nombre']) ?></td>
                            <td class="text-xs font-semibold text-slate-600">
                                <span class="bg-slate-100 px-2 py-1 rounded-md border border-slate-200"><?= htmlspecialchars($f['nombre_metodo']) ?></span>
                            </td>
                            <td class="font-medium text-slate-600">
                                <i class="fa-solid fa-user-tie mr-1 text-[10px] text-slate-400"></i><?= htmlspecialchars($f['nombre_cajero']) ?>
                            </td>
                            <td class="text-right font-black text-slate-900">RD$ <?= number_format($f['total_neto'], 2) ?></td>
                            <td class="text-center">
                                <?php if ($f['estado_factura'] === 'PAGADA'): ?>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">PAGADA</span>
                                <?php else: ?>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-800">ANULADA</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                               <div class="flex items-center justify-center gap-1.5">
    <button type="button" onclick="verDetalleFactura(<?= $f['id_factura'] ?>, '<?= $f['numero_factura'] ?>', '<?= htmlspecialchars($f['cliente_nombre']) ?>', '<?= number_format($f['subtotal'], 2) ?>', '<?= number_format($f['itbis_total'], 2) ?>', '<?= number_format($f['total_neto'], 2) ?>')" 
            class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-bold transition" title="Ver artículos de la factura">
        <i class="fa-solid fa-eye"></i>
    </button>

    <a href="generar_pdf_factura.php?id=<?= $f['id_factura'] ?>" target="_blank"
       class="p-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 rounded-lg text-xs font-bold transition" title="Exportar a PDF">
        <i class="fa-solid fa-file-pdf"></i>
    </a>
    
    <?php if ($f['estado_factura'] === 'PAGADA'): ?>
        <a href="facturas.php?anular_id=<?= $f['id_factura'] ?>" 
           onclick="return confirm('¿Seguro que deseas ANULAR esta factura? Esto regresará de inmediato los equipos vendidos al inventario de forma disponible.');" 
           class="p-2 bg-rose-50 hover:bg-rose-100 text-rose-600 rounded-lg text-xs font-bold transition" title="Anular Factura / Devolver Inventario">
            <i class="fa-solid fa-ban"></i>
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

<div id="modalDetalle" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center hidden z-50 p-4 no-print">
    <div class="bg-white max-w-2xl w-full rounded-3xl border border-slate-200 shadow-2xl overflow-hidden transform scale-95 transition-all duration-300">
        
        <div class="p-6 bg-slate-900 text-white flex justify-between items-center">
            <div>
                <h4 class="text-sm uppercase font-black text-slate-400 tracking-wider">Comprobante de Caja</h4>
                <h3 class="text-xl font-mono font-black text-emerald-400" id="modalNumeroFactura">FAC-0000</h3>
            </div>
            <button type="button" onclick="cerrarModal()" class="text-slate-400 hover:text-white transition text-lg"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="p-6 space-y-4">
            <p class="text-xs text-slate-500 font-medium">Cliente Relacionado: <strong class="text-slate-800 font-bold" id="modalCliente">Cargando...</strong></p>
            
            <div class="border border-slate-200 rounded-2xl overflow-hidden">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-400 font-bold uppercase text-[10px]">
                            <th class="p-3">ID Local</th>
                            <th class="p-3">Descripción Artículo</th>
                            <th class="p-3 text-center">Cant.</th>
                            <th class="p-3 text-right">Precio Unitario</th>
                            <th class="p-3 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody id="modalTbodyItems" class="divide-y divide-slate-100 text-slate-700 font-medium">
                        </tbody>
                </table>
            </div>

            <div class="w-1/2 ml-auto space-y-1.5 text-xs border-t border-slate-100 pt-3">
                <div class="flex justify-between text-slate-500">
                    <span>Subtotal Neto:</span>
                    <span class="font-mono font-bold text-slate-800">RD$ <span id="modalSubtotal">0.00</span></span>
                </div>
                <div class="flex justify-between text-slate-500">
                    <span>ITBIS Desglosado:</span>
                    <span class="font-mono font-bold text-slate-800">RD$ <span id="modalItbis">0.00</span></span>
                </div>
                <div class="flex justify-between items-center pt-2 border-t border-slate-200 text-sm font-black text-slate-900">
                    <span>Total Factura:</span>
                    <span class="font-mono text-emerald-600 text-base">RD$ <span id="modalTotal">0.00</span></span>
                </div>
            </div>
        </div>
<div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-2">
    <button type="button" onclick="cerrarModal()" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-xl text-xs transition">Cerrar</button>
    
    <a id="btnModalPDF" href="#" target="_blank" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-black rounded-xl text-xs transition shadow-md shadow-emerald-500/20"><i class="fa-solid fa-file-pdf mr-1"></i> PDF</a>
    
    <button type="button" onclick="window.print()" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-black rounded-xl text-xs transition shadow-md shadow-blue-500/20"><i class="fa-solid fa-print mr-1"></i> Imprimir Ticket</button>
</div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function () {
    $('#tablaFacturas').DataTable({
        responsive: true,
        pageLength: 10,
        order: [[0, "desc"]], 
        language: { url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' }
    });
});

// Función para invocar el detalle vía AJAX
function verDetalleFactura(id, numFactura, cliente, subtotal, itbis, total) {
    $('#modalNumeroFactura').text(numFactura);
    $('#modalCliente').text(cliente);
    $('#modalSubtotal').text(subtotal);
    $('#modalItbis').text(itbis);
    $('#modalTotal').text(total);
    
    $('#modalTbodyItems').html('<tr><td colspan="5" class="p-4 text-center text-slate-400 font-medium">Buscando artículos en base de datos...</td></tr>');
    $('#modalDetalle').removeClass('hidden');

    $('#btnModalPDF').attr('href', 'generar_pdf.php?id=' + id);

    $.ajax({
        url: 'facturas.php',
        type: 'GET',
        data: { action: 'get_detalle', id: id },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                let htmlRows = '';
                response.data.forEach(function(item) {
                    htmlRows += `
                        <tr>
                            <td class="p-3 font-mono font-bold text-blue-600">${item.id_local}</td>
                            <td class="p-3 font-semibold">${item.equipo_marca} ${item.equipo_modelo}</td>
                            <td class="p-3 text-center font-bold">${item.cantidad}</td>
                            <td class="p-3 text-right">RD$ ${parseFloat(item.precio_unitario).toFixed(2)}</td>
                            <td class="p-3 text-right font-bold text-slate-900">RD$ ${parseFloat(item.subtotal_linea).toFixed(2)}</td>
                        </tr>
                    `;
                });
                $('#modalTbodyItems').html(htmlRows);
            } else {
                $('#modalTbodyItems').html('<tr><td colspan="5" class="p-4 text-center text-rose-500">Error al mapear los artículos.</td></tr>');
            }
        },
        error: function() {
            $('#modalTbodyItems').html('<tr><td colspan="5" class="p-4 text-center text-rose-500">Error de conexión con el servidor.</td></tr>');
        }
    });
}

function cerrarModal() {
    $('#modalDetalle').addClass('hidden');
}
</script>
</body>
</html>