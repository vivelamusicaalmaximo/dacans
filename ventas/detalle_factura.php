<?php
session_start();

// 1. Validar sesión
if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

// 2. Validar que llegue el ID de la factura
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: ID de factura no especificado.");
}

$id_factura = (int)$_GET['id'];

// 3. Requerir conexión a la base de datos
require_once '../config/conexion.php'; 

try {
    // 4. Obtener datos de la cabecera
    $sqlFactura = "SELECT f.*, 
                           c.nombre + ' ' + c.apellido AS cliente_nombre, 
                           m.nombre_metodo, 
                           ae.agencia AS agencia_nombre,
                           ae.costo AS costo_envio
                    FROM facturas f
                    INNER JOIN clientes c ON f.id_cliente = c.id_cliente
                    INNER JOIN metodos_pago m ON f.id_metodo_pago = m.id_metodo
                    LEFT JOIN agencias_envio ae ON f.id_envio = ae.id
                    WHERE f.id_factura = ?";
                    
    $stmtF = $pdo->prepare($sqlFactura);
    $stmtF->execute([$id_factura]);
    $factura = $stmtF->fetch(PDO::FETCH_ASSOC);

    if (!$factura) {
        die("La factura solicitada no existe o no tiene permisos para verla.");
    }

    // 5. Obtener los artículos del detalle
    $sqlDetalle = "SELECT fd.cantidad, fd.precio_unitario, fd.subtotal_linea, 
                           p.id_local, p.equipo_marca, p.equipo_modelo 
                    FROM factura_detalle fd
                    INNER JOIN productos_informatica p ON fd.id_producto = p.id
                    WHERE fd.id_factura = ?";
    $stmtD = $pdo->prepare($sqlDetalle);
    $stmtD->execute([$id_factura]);
    $items = $stmtD->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error en la base de datos: " . $e->getMessage());
}

// 6. Preparar variables calculadas para la interfaz
$costo_envio = isset($factura['costo_envio']) ? (float)$factura['costo_envio'] : 0.00;
$agencia_envio = !empty($factura['agencia_nombre']) ? htmlspecialchars($factura['agencia_nombre']) : 'Retiro en Tienda';
$total_final = (float)$factura['total_neto'] + $costo_envio;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Factura #<?php echo htmlspecialchars($factura['numero_factura']); ?> - DACANS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-slate-50 text-slate-800 font-sans antialiased min-h-screen">

    <div class="max-w-5xl mx-auto px-4 py-8">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
                <a href="facturas.php"
                    class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800 transition">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Volver al listado
                </a>
                <h1 class="text-2xl font-bold text-slate-900 mt-1">Detalle de Factura</h1>
            </div>

            <div class="flex items-center gap-3">
                <a href="generar_pdf_factura.php?id=<?php echo $id_factura; ?>" target="_blank"
                    class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2.5 rounded-lg shadow-sm text-sm transition">
                    <i class="fa-solid fa-file-pdf mr-2"></i> Descargar PDF / Imprimir
                </a>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

            <div
                class="p-6 sm:p-8 border-b border-slate-100 bg-slate-900 text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div
                        class="bg-white p-2 rounded-xl border border-slate-700 w-16 h-16 flex items-center justify-center">
                        <img src="https://dacansdr.com/img/logo.png" alt="DACANS Logo"
                            class="max-h-12 w-auto object-contain">
                    </div>
                    <div>
                        <h2 class="text-xl font-extrabold tracking-tight">DACANS COMPUTERS</h2>
                        <p class="text-xs text-slate-400">Especialistas en Tecnología Premium</p>
                    </div>
                </div>

                <div class="sm:text-right">
                    <div class="text-xs text-slate-400 uppercase tracking-wider font-semibold">Factura No.</div>
                    <div class="text-2xl font-mono font-bold text-blue-400">
                        <?php echo htmlspecialchars($factura['numero_factura']); ?></div>
                    <div class="mt-2 flex sm:justify-end">
                        <?php if ($factura['estado_factura'] === 'PAGADA'): ?>
                        <span
                            class="inline-flex items-center bg-emerald-500/10 text-emerald-400 text-xs font-bold px-3 py-1 rounded-full border border-emerald-500/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mr-1.5 animate-pulse"></span> PAGADA
                        </span>
                        <?php else: ?>
                        <span
                            class="inline-flex items-center bg-rose-500/10 text-rose-400 text-xs font-bold px-3 py-1 rounded-full border border-rose-500/20">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400 mr-1.5"></span> ANULADA
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="p-6 sm:p-8 border-b border-slate-100 bg-slate-50 grid grid-cols-1 sm:grid-cols-3 gap-6 text-sm">
                <div>
                    <span class="text-xs text-slate-400 font-bold uppercase tracking-wider block mb-1">Información del
                        Cliente</span>
                    <div class="font-bold text-slate-900 text-base">
                        <?php echo htmlspecialchars($factura['cliente_nombre']); ?></div>
                    <div class="text-slate-500 mt-1"><span class="font-medium text-slate-600">Condición:</span> Contado
                    </div>
                </div>

                <div>
                    <span class="text-xs text-slate-400 font-bold uppercase tracking-wider block mb-1">Detalles del
                        Pago</span>
                    <div class="text-slate-700 mt-0.5"><span class="font-semibold text-slate-600">Método:</span>
                        <?php echo htmlspecialchars($factura['nombre_metodo']); ?></div>
                    <div class="text-slate-700 mt-0.5"><span class="font-semibold text-slate-600">Envío:</span>
                        <?php echo $agencia_envio; ?></div>
                </div>

                <div class="sm:text-right">
                    <span class="text-xs text-slate-400 font-bold uppercase tracking-wider block mb-1">Fecha de
                        Emisión</span>
                    <div class="text-slate-800 font-semibold text-base">
                        <?php echo date('d/m/Y', strtotime($factura['fecha_factura'])); ?></div>
                    <div class="text-xs text-slate-500 mt-0.5">
                        <?php echo date('h:i A', strtotime($factura['fecha_factura'])); ?></div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr
                            class="bg-slate-900 text-slate-200 text-xs font-bold uppercase tracking-wider border-b border-slate-200">
                            <th class="py-4 px-6 w-24">ID Local</th>
                            <th class="py-4 px-6">Descripción del Artículo</th>
                            <th class="py-4 px-6 text-center w-20">Cant.</th>
                            <th class="py-4 px-6 text-right w-36">Precio Unitario</th>
                            <th class="py-4 px-6 text-right w-40">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <?php foreach ($items as $item): ?>
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="py-4 px-6 font-mono font-bold text-blue-600">
                                <?php echo htmlspecialchars($item['id_local']); ?>
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-semibold text-slate-900">
                                    <?php echo htmlspecialchars($item['equipo_marca'] . ' ' . $item['equipo_modelo']); ?>
                                </div>
                            </td>
                            <td class="py-4 px-6 text-center font-medium text-slate-600">
                                <?php echo $item['cantidad']; ?>
                            </td>
                            <td class="py-4 px-6 text-right text-slate-600 font-medium">
                                RD$ <?php echo number_format($item['precio_unitario'], 2); ?>
                            </td>
                            <td class="py-4 px-6 text-right font-bold text-slate-950">
                                RD$ <?php echo number_format($item['subtotal_linea'], 2); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div
                class="p-6 sm:p-8 bg-slate-50/50 border-t border-slate-100 flex flex-col md:flex-row gap-8 justify-between items-start">

                <div
                    class="w-full md:w-auto max-w-sm bg-white p-5 rounded-2xl border-2 border-dashed border-slate-200 shadow-sm flex items-center gap-4">
                    <div class="p-3 bg-blue-50 text-blue-600 rounded-xl flex-shrink-0">
                        <img src="../img/garantia.png" alt="Sello de Garantía" class="w-10 h-10 object-contain"
                            onerror="this.src='https://cdn-icons-png.flaticon.com/512/9722/9722912.png';">
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900 uppercase tracking-wide">1 Año de Garantía</h4>
                        <p class="text-xs text-slate-500 mt-0.5 leading-relaxed">Este documento valida la cobertura
                            oficial de hardware exclusiva de DACANS COMPUTERS.</p>
                    </div>
                </div>

                <div class="w-full md:w-80 space-y-3 text-sm">
                    <div class="flex justify-between text-slate-500 font-medium">
                        <span>Subtotal Neto:</span>
                        <span class="text-slate-900">RD$ <?php echo number_format($factura['subtotal'], 2); ?></span>
                    </div>
                    <div class="flex justify-between text-slate-500 font-medium">
                        <span>ITBIS (18%):</span>
                        <span class="text-slate-900">RD$ <?php echo number_format($factura['itbis_total'], 2); ?></span>
                    </div>

                    <?php if ($costo_envio > 0): ?>
                    <div class="flex justify-between text-slate-500 font-medium">
                        <span>Costo de Envío:</span>
                        <span class="text-slate-900">RD$ <?php echo number_format($costo_envio, 2); ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="pt-3 border-t-2 border-slate-200 flex justify-between items-baseline">
                        <span class="text-base font-bold text-slate-900">Total General:</span>
                        <span class="text-xl font-black text-emerald-600">RD$
                            <?php echo number_format($total_final, 2); ?></span>
                    </div>
                </div>

            </div>
        </div>

        <div class="text-center text-xs text-slate-400 mt-6 font-medium">
            Soporte Técnico Especializado: contacto@dacansdr.com | DACANS Computers © 2026
        </div>

    </div>

</body>

</html>