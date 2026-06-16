<?php
session_start();

// Validar sesión
if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config/conexion.php'; 

$id_cotizacion_get = isset($_GET['id_cotizacion']) ? (int)$_GET['id_cotizacion'] : null;

$mensaje_success = "";
$mensaje_error = "";
$whatsapp_url = ""; // Almacenará el enlace dinámico de WhatsApp

// ID del usuario cajero/vendedor desde la sesión
$id_usuario_activo = $_SESSION['id_usuario'] ?? 1; 

/* =========================================================
   VARIABLES POR DEFECTO PARA EL FORMULARIO (CASO COTIZACIÓN)
========================================================= */
$cliente_precargado = "";
$metodo_precargado = "";
$productos_precargados_json = "[]"; // Se inyectará en tu JavaScript del carrito

if ($id_cotizacion_get) {
    try {
        // 1. Obtener la cotización principal
        $stmtCot = $pdo->prepare("SELECT id_cliente, id_metodo_pago, numero_cotizacion FROM cotizaciones WHERE id_cotizacion = ?");
        $stmtCot->execute([$id_cotizacion_get]);
        $cotizacion_base = $stmtCot->fetch(PDO::FETCH_ASSOC);

        if ($cotizacion_base) {
            $cliente_precargado = $cotizacion_base['id_cliente'];
            $metodo_precargado  = $cotizacion_base['id_metodo_pago'];
            
            // 2. Extraer los productos de la sesión de cotizaciones si existe
            if (isset($_SESSION['cotizacion']) && !empty($_SESSION['cotizacion'])) {
                $carrito_temporal = [];
                foreach ($_SESSION['cotizacion'] as $eq) {
                    $carrito_temporal[] = [
                        'id_producto' => $eq['id_local'], // O el ID real de tu tabla productos_informatica
                        'cantidad'    => 1,
                        'precio'      => $eq['precio']
                    ];
                }
                $productos_precargados_json = json_encode($carrito_temporal);
            }
        }
    } catch (Exception $e) {
        $mensaje_error = "Error al recuperar datos de la cotización: " . $e->getMessage();
    }
}

/* =========================================================
   1. PROCESAR EL GUARDADO DE LA VENTA (POST)
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['procesar_venta'])) {
    try {
        $pdo->beginTransaction();

        $id_cliente = (int)$_POST['id_cliente'];
        $id_metodo_pago = (int)$_POST['id_metodo_pago'];
        $productos_carrito = $_POST['productos_carrito'] ?? []; 
        $comentarios = trim($_POST['comentarios'] ?? '');
        
        // Capturar ID de cotización oculta en el formulario si venía de una
        $id_cotizacion_post = !empty($_POST['id_cotizacion_origen']) ? (int)$_POST['id_cotizacion_origen'] : null;

        // Nuevos campos de entrega
        $tipo_entrega = $_POST['tipo_entrega'] ?? 'Tienda';
        $id_agencia_envio = !empty($_POST['id_agencia_envio']) ? (int)$_POST['id_agencia_envio'] : null;
        $costo_envio = 0.00;

        if (empty($productos_carrito)) {
            throw new Exception("El carrito de compras está vacío.");
        }

        // Si es envío, obtener el costo real desde la base de datos
        if ($tipo_entrega === 'Envio' && $id_agencia_envio !== null) {
            $stmtCosto = $pdo->prepare("SELECT costo, agencia FROM agencias_envio WHERE id = ?");
            $stmtCosto->execute([$id_agencia_envio]);
            $agencia_data = $stmtCosto->fetch(PDO::FETCH_ASSOC);
            if ($agencia_data) {
                $costo_envio = (float)$agencia_data['costo'];
                $comentarios .= " [Envío por: " . $agencia_data['agencia'] . "]";
            }
        }

      /* =========================================================
           LÓGICA ASIGNACIÓN DE NCF (AUTOMÁTICO O MANUAL)
        ========================================================= */
        $ncf_generado = null;
        $id_ncf_usado = null;
        $requiere_ncf = $_POST['requiere_ncf_radio'] ?? 'no';
        $ncf_manual = isset($_POST['ncf_manual']) ? strtoupper(trim($_POST['ncf_manual'])) : '';

        if ($requiere_ncf === 'si') {
            
            if (!empty($ncf_manual)) {
                // --- CASO 1: NCF MANUAL ---
                // Validamos longitud básica de los NCF de la DGII (usualmente 11 caracteres: Ej B0100000001)
                if (strlen($ncf_manual) < 9) { 
                    throw new Exception("El NCF manual introducido parece inválido o muy corto.");
                }
                
                $ncf_generado = $ncf_manual;
                $id_ncf_usado = null; // No proviene de ningún talonario automático interno
                
                $comentarios .= " [NCF Manual: " . $ncf_generado . "]";

            } else if (!empty($_POST['id_comprobante'])) {
                // --- CASO 2: NCF AUTOMÁTICO DESDE BASE DE DATOS ---
                $id_ncf_secuencia = (int)$_POST['id_comprobante'];

                // Buscar el talonario bloqueándolo para evitar duplicados simultáneos (FOR UPDATE)
              // Buscar el talonario bloqueándolo para evitar duplicados simultáneos con UPDLOCK y ROWLOCK
$stmtNCF = $pdo->prepare("SELECT id_ncf, prefijo, secuencia_actual, secuencia_hasta FROM control_ncf WITH (UPDLOCK, ROWLOCK) WHERE id_ncf = ? AND estado = 1");
                $stmtNCF->execute([$id_ncf_secuencia]);
                $talonario = $stmtNCF->fetch(PDO::FETCH_ASSOC);

                if (!$talonario) {
                    throw new Exception("El talonario fiscal seleccionado no es válido o está inactivo.");
                }

                if ($talonario['secuencia_actual'] > $talonario['secuencia_hasta']) {
                    throw new Exception("El talonario fiscal seleccionado se ha agotado. Contacte al administrador.");
                }

                // Armar el número de NCF estándar de la DGII
                $ncf_generado = $talonario['prefijo'] . str_pad($talonario['secuencia_actual'], 8, '0', STR_PAD_LEFT);
                $id_ncf_usado = $talonario['id_ncf'];

                // Actualizar la secuencia en el control de NCF (+1)
                $stmtUpdateNCF = $pdo->prepare("UPDATE control_ncf SET secuencia_actual = secuencia_actual + 1 WHERE id_ncf = ?");
                $stmtUpdateNCF->execute([$id_ncf_usado]);
                
                $comentarios .= " [NCF: " . $ncf_generado . "]";
            } else {
                throw new Exception("Marcaste que requiere comprobante fiscal, pero no seleccionaste uno ni escribiste uno manual.");
            }
        }

        // Generar el número de factura único de control interno
        $prefijo_interno = "FAC-" . date('Ymd');
        $stmtCorrelativo = $pdo->query("SELECT COUNT(*) + 1 as siguiente FROM facturas WHERE numero_factura LIKE '$prefijo_interno%'");
        $resCorrelativo = $stmtCorrelativo->fetch(PDO::FETCH_ASSOC);
        $numero_factura = $prefijo_interno . str_pad($resCorrelativo['siguiente'], 4, '0', STR_PAD_LEFT);

        $subtotal_factura = 0.00;
        $itbis_total_factura = 0.00;
        $total_neto_factura = 0.00;

        $detalles_a_insertar = [];

        foreach ($productos_carrito as $item) {
            $id_prod = (int)$item['id_producto'];
            $cantidad = (int)$item['cantidad'];

            $stmtProd = $pdo->prepare("SELECT id, precio, estado FROM productos_informatica WHERE id = ?");
            $stmtProd->execute([$id_prod]);
            $productoDB = $stmtProd->fetch(PDO::FETCH_ASSOC);

            if (!$productoDB) {
                throw new Exception("Uno de los productos seleccionados no existe.");
            }
            if ($productoDB['estado'] === 'Vendida') {
                throw new Exception("El producto con ID #$id_prod ya ha sido vendido.");
            }

            $precio_unitario = (float)$productoDB['precio'];
            
            $subtotal_unidad = $precio_unitario / 1.18;
            $itbis_unidad = $precio_unitario - $subtotal_unidad;

            $subtotal_linea = $subtotal_unidad * $cantidad;
            $itbis_linea = $itbis_unidad * $cantidad;
            $total_linea = $precio_unitario * $cantidad;

            $subtotal_factura += $subtotal_linea;
            $itbis_total_factura += $itbis_linea;
            $total_neto_factura += $total_linea;

            $detalles_a_insertar[] = [
                'id_producto' => $id_prod,
                'cantidad' => $cantidad,
                'precio_unitario' => $precio_unitario,
                'itbis_aplicado' => $itbis_linea,
                'subtotal_linea' => $total_linea
            ];
        }

        // Sumar el costo de envío al total neto de la factura
        $total_neto_factura += $costo_envio;

        // Se añaden los campos ncf y id_ncf en el INSERT de la factura (Asegúrate de que existan en tu tabla 'facturas')
     $sqlInsertFactura = "INSERT INTO facturas (
    numero_factura, id_cliente, id_metodo_pago, id_usuario, 
    subtotal, itbis_total, descuento_total, total_neto, 
    monto_pagado, devuelta, comentarios, estado_factura, fecha_factura,
    ncf, id_ncf  -- <--- ESTO DEBE COINCIDIR CON LOS NOMBRES REALES DE LA TABLA
) VALUES (?, ?, ?, ?, ?, ?, 0.00, ?, ?, ?, ?, 'PAGADA', GETDATE(), ?, ?)";

        $monto_pagado = (float)($_POST['monto_pagado'] ?? $total_neto_factura);
        $devuelta = $monto_pagado - $total_neto_factura;

        $stmtFactura = $pdo->prepare($sqlInsertFactura);
        $stmtFactura->execute([
            $numero_factura, $id_cliente, $id_metodo_pago, $id_usuario_activo,
            $subtotal_factura, $itbis_total_factura, $total_neto_factura,
            $monto_pagado, $devuelta, $comentarios, $ncf_generado, $id_ncf_usado
        ]);

        $id_factura_generada = $pdo->lastInsertId();

        $sqlInsertDetalle = "INSERT INTO factura_detalle (id_factura, id_producto, cantidad, precio_unitario, itbis_aplicado, descuento_aplicado, subtotal_linea) VALUES (?, ?, ?, ?, ?, 0.00, ?)";
        $stmtDetalle = $pdo->prepare($sqlInsertDetalle);


        $sqlUpdateProducto = "UPDATE productos_informatica SET estado = 'Vendida', vendida_at = GETDATE() WHERE id = ?";
        $stmtUpdateProd = $pdo->prepare($sqlUpdateProducto);

        foreach ($detalles_a_insertar as $det) {
            $stmtDetalle->execute([
                $id_factura_generada, $det['id_producto'], $det['cantidad'], 
                $det['precio_unitario'], $det['itbis_aplicado'], $det['subtotal_linea']
            ]);
            $stmtUpdateProd->execute([$det['id_producto']]);
        }

        // --- EXTRACCIÓN DE DATOS DE CLIENTE PARA WHATSAPP Y CORREO ---
        $stmtInfoCliente = $pdo->prepare("SELECT nombre, telefono, email FROM clientes WHERE id_cliente = ?");
        $stmtInfoCliente->execute([$id_cliente]);
        $infoCliente = $stmtInfoCliente->fetch(PDO::FETCH_ASSOC);

        $pdo->commit();

        /* =========================================================
            ENVÍO AUTOMÁTICO DE FACTURA POR EMAIL
        ========================================================= */
        if ($infoCliente && !empty($infoCliente['email'])) {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            try {
                $mail->isSMTP();
                $mail->Host       = 'mail.dacansdr.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'facturas@dacansdr.com';
                $mail->Password   = 'TuContraseñaSeguraAqui'; 
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;
                $mail->CharSet    = 'UTF-8';

                $mail->setFrom('facturas@dacansdr.com', 'DACANS Computers');
                $mail->addAddress($infoCliente['email'], $infoCliente['nombre']);

                $url_pdf = "https://dacansdr.com/admin/generar_pdf.php?id=" . $id_factura_generada;
                $pdf_content = file_get_contents($url_pdf);
                
                if ($pdf_content !== false) {
                    $mail->addStringAttachment($pdf_content, "Factura_" . $numero_factura . ".pdf");
                }

                $mail->isHTML(true);
                $mail->Subject = 'Tu comprobante de compra ' . $numero_factura . ' - DACANS Computers';
                
                // Texto informativo extra si tiene comprobante fiscal asignado
                $texto_ncf_email = !empty($ncf_generado) ? "<tr><td style='padding: 8px; font-weight: bold;'>Comprobante Fiscal (NCF):</td><td style='padding: 8px; font-weight: bold; color: #1d4ed8;'>".$ncf_generado."</td></tr>" : "";

                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px;'>
                        <h2 style='color: #2563eb;'>¡Gracias por tu compra, " . htmlspecialchars($infoCliente['nombre']) . "!</h2>
                        <p>Nos complace adjuntar a este correo la factura oficial correspondiente a tu reciente adquisición en <strong>DACANS Computers</strong>.</p>
                        <table style='width: 100%; border-collapse: collapse; margin: 20px 0;'>
                            <tr>
                                <td style='padding: 8px; font-weight: bold;'>Número de Factura:</td>
                                <td style='padding: 8px;'>" . $numero_factura . "</td>
                            </tr>
                            " . $texto_ncf_email . "
                            <tr>
                                <td style='padding: 8px; font-weight: bold;'>Monto Total:</td>
                                <td style='padding: 8px; color: #059669; font-weight: bold;'>RD$ " . number_format($total_neto_factura, 2) . "</td>
                            </tr>
                        </table>
                        <p style='font-size: 12px; color: #64748b;'>Por favor, descarga el archivo PDF adjunto para visualizar los detalles de la garantía de 1 año y especificaciones técnicas de tus equipos.</p>
                        <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
                        <p style='font-size: 11px; color: #94a3b8; text-align: center;'>DACANS Computers | Santo Domingo, República Dominicana.</p>
                    </div>";

                $mail->send();
                $mensaje_success = "¡Venta procesada con éxito! Factura creada: <strong>$numero_factura</strong>" . (!empty($ncf_generado) ? " (NCF: $ncf_generado)" : "") . " y enviada automáticamente al cliente.";
            } catch (Exception $e) {
                $mensaje_success = "¡Venta procesada con éxito! Factura creada: <strong>$numero_factura</strong>. <span style='color:#b91c1c;'>(Pero no se pudo enviar el correo: {$mail->ErrorInfo})</span>";
            }
        } else {
            $mensaje_success = "¡Venta procesada con éxito! Factura creada: <strong>$numero_factura</strong>." . (!empty($ncf_generado) ? " (NCF: $ncf_generado)" : "") . " (El cliente no tiene un correo electrónico registrado).";
        }

        // Construcción automática del link de WhatsApp
        if ($infoCliente && !empty($infoCliente['telefono'])) {
            $telefono_limpio = preg_replace('/[^0-9]/', '', $infoCliente['telefono']);
            if (strlen($telefono_limpio) === 10) {
                $telefono_limpio = "1" . $telefono_limpio; 
            }
            $nombre_url = str_replace(' ', '_', $infoCliente['nombre']);
            $texto_mensaje = "¡Hola " . htmlspecialchars($infoCliente['nombre']) . "! Gracias por elegir a DACANS Computers. 💻 Nos encantaría conocer tu experiencia con nosotros y el rendimiento de tu nuevo equipo. Nos ayudas muchísimo dejando tu breve calificación aquí: https://dacansdr.com/valorar.php?cliente=" . $nombre_url . " ¡Disfruta tu compra!";
            $whatsapp_url = "https://api.whatsapp.com/send?phone=" . $telefono_limpio . "&text=" . urlencode($texto_mensaje);
        }

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $mensaje_error = "Error al procesar la venta: " . $e->getMessage();
    }
} 

/* =========================================================
   2. RECOPILAR INFORMACIÓN INICIAL (GET)
========================================================= */
try {
    $clientes = $pdo->query("SELECT id_cliente, CONCAT(nombre, ' ', apellido) AS cliente_nombre, rnc_cedula FROM clientes WHERE estado = 1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
    
    $metodos_pago = $pdo->query("SELECT id_metodo, nombre_metodo FROM metodos_pago WHERE estado = 1")->fetchAll(PDO::FETCH_ASSOC);
    
    $productos = $pdo->query("SELECT id, id_local, equipo_marca, equipo_modelo, precio FROM productos_informatica WHERE estado != 'Vendida' ORDER BY id_local DESC")->fetchAll(PDO::FETCH_ASSOC);
    
    $agencias = $pdo->query("SELECT id, provincia, ciudad, agencia, costo FROM agencias_envio ORDER BY agencia ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Traer los talonarios NCF autorizados y activos
    $comprobantes_disponibles = $pdo->query("SELECT id_ncf, tipo_comprobante, prefijo FROM control_ncf WHERE estado = 1 AND secuencia_actual <= secuencia_hasta")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Error crítico al cargar componentes de la interfaz: " . $e->getMessage());
}
?>
<?php if (!empty($mensaje_success)): ?>
<div class="bg-emerald-50 border border-emerald-200 p-6 rounded-2xl my-4 shadow-sm">
    <div class="flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div class="bg-emerald-500 text-white w-10 h-10 rounded-xl flex items-center justify-center text-lg">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <p class="text-emerald-900 font-medium"><?= $mensaje_success ?></p>
                <p class="text-xs text-emerald-600">El inventario se ha actualizado correctamente.</p>
            </div>
        </div>

        <?php if (!empty($whatsapp_url)): ?>
        <a href="<?= $whatsapp_url ?>" target="_blank"
            class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-3 rounded-xl font-bold shadow-md transition text-sm">
            <i class="fa-brands fa-whatsapp text-lg"></i>
            Enviar Link de Valoración
        </a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($mensaje_error)): ?>
<div class="bg-rose-50 border border-rose-200 p-4 rounded-2xl my-4 text-rose-900 font-medium flex items-center gap-3">
    <i class="fa-solid fa-circle-xmark text-rose-500 text-lg"></i>
    <?= $mensaje_error ?>
</div>
<?php endif; ?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Módulo de Facturación y Ventas - DACANS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
    body {
        background-color: #f8fafc;
        font-family: system-ui, sans-serif;
    }
    </style>
</head>

<body class="p-4 md:p-8">

    <div class="max-w-7xl mx-auto">

        <?php if (!empty($mensaje_success)): ?>
        <div
            class="mb-6 p-4 bg-emerald-100 border border-emerald-300 text-emerald-800 rounded-2xl flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-circle-check text-xl"></i>
            <div><?= $mensaje_success ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($mensaje_error)): ?>
        <div
            class="mb-6 p-4 bg-red-100 border border-red-300 text-red-800 rounded-2xl flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-circle-xmark text-xl"></i>
            <div><?= $mensaje_error ?></div>
        </div>
        <?php endif; ?>

        <div class="flex justify-between items-center mb-8 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
            <div class="flex items-center gap-4">
                <div
                    class="bg-blue-600 text-white w-12 h-12 rounded-2xl flex items-center justify-center text-xl shadow-lg shadow-blue-500/20">
                    <i class="fa-solid fa-cash-register"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Nueva Venta / Facturación</h1>
                    <p class="text-xs text-slate-500">Cajero Activo ID: #<?= $id_usuario_activo ?> — Gestión en tiempo
                        real</p>
                </div>
            </div>
            <a href="ventas.php"
                class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2.5 rounded-xl font-bold text-xs transition">
                <i class="fa-solid fa-arrow-left mr-1"></i> Ver Ventas
            </a>
        </div>

        <form method="POST" id="formVenta" class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="lg:col-span-2 space-y-6">

                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-4"><i
                            class="fa-solid fa-laptop text-blue-500 mr-1"></i> Buscar e Introducir Artículo</h3>

                    <div
                        class="mb-4 bg-blue-50/50 p-3 rounded-2xl border border-blue-100 flex flex-col md:flex-row gap-3 items-center">
                        <div class="w-full md:w-1/3">
                            <span class="block text-[10px] font-black uppercase text-blue-600 mb-1"><i
                                    class="fa-solid fa-barcode mr-1"></i> Filtro Rápido (Últimos 4 dígitos)</span>
                            <input type="text" id="txtBuscarDigitos" maxlength="4" placeholder="Ej: 4521"
                                class="w-full bg-white border border-blue-200 p-2 rounded-xl text-center font-mono font-bold text-sm text-blue-700 focus:outline-none focus:border-blue-500">
                        </div>
                        <p class="text-xs text-slate-500 flex-1 leading-normal">Escribe los últimos 4 números del
                            <strong>ID Local</strong> del equipo. El sistema lo buscará y seleccionará de manera
                            automática en la lista de abajo.
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <div class="md:col-span-3">
                            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Selecciona un
                                Equipo Disponible</label>
                            <select id="selectProducto"
                                class="w-full bg-slate-50 border border-slate-200 p-3 rounded-xl font-medium text-slate-800 text-sm focus:outline-none focus:border-blue-500">
                                <option value="">-- Elige un artículo de la lista --</option>
                                <?php foreach ($productos as $p): ?>
                                <option value="<?= $p['id'] ?>" data-marca="<?= htmlspecialchars($p['equipo_marca']) ?>"
                                    data-modelo="<?= htmlspecialchars($p['equipo_modelo']) ?>"
                                    data-precio="<?= $p['precio'] ?>" data-idlocal="<?= $p['id_local'] ?>">
                                    [<?= $p['id_local'] ?>] <?= htmlspecialchars($p['equipo_marca']) ?>
                                    <?= htmlspecialchars($p['equipo_modelo']) ?> —
                                    RD$<?= number_format($p['precio'], 2) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <button type="button" id="btnAgregarCarrito"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white p-3 rounded-xl font-black text-sm shadow-md transition flex items-center justify-center gap-1">
                                <i class="fa-solid fa-plus"></i> Agregar
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                        <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider"><i
                                class="fa-solid fa-cart-shopping text-emerald-500 mr-1"></i> Detalle de Artículos a
                            Facturar</h3>
                        <span id="contadorItems"
                            class="bg-slate-200 text-slate-700 text-xs px-2.5 py-1 rounded-full font-black">0
                            Artículos</span>
                    </div>

                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr
                                class="bg-slate-100 border-b border-slate-200 text-slate-500 uppercase font-bold text-[10px]">
                                <th class="p-4">ID Local</th>
                                <th class="p-4">Descripción Equipo</th>
                                <th class="p-4 text-center">Cantidad</th>
                                <th class="p-4 text-right">Precio Unitario</th>
                                <th class="p-4 text-right">Subtotal</th>
                                <th class="p-4 text-center">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyCarrito" class="divide-y divide-slate-100">
                            <tr id="filaVacia">
                                <td colspan="6" class="p-8 text-center text-slate-400 font-medium">No hay productos
                                    añadidos a la factura aún.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-6">

                <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm space-y-4">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider"><i
                            class="fa-solid fa-user-tie text-amber-500 mr-1"></i> Datos de la Transacción</h3>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Filtrar/Buscar
                            Cliente</label>
                        <div class="relative mb-2">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400 text-xs">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>
                            <input type="text" id="txtBuscarCliente" placeholder="Escribe nombre o cédula..."
                                class="w-full bg-slate-50 border border-slate-200 pl-9 pr-3 py-2 rounded-xl text-xs font-medium text-slate-700 focus:outline-none focus:border-amber-500">
                        </div>

                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Cliente
                            Seleccionado</label>
                        <div class="flex gap-2">
                            <select name="id_cliente" id="selectCliente"
                                class="flex-1 bg-slate-50 border border-slate-200 p-2.5 rounded-xl font-medium text-slate-800 text-xs focus:outline-none"
                                required>
                                <?php foreach ($clientes as $c): ?>
                                <option value="<?= $c['id_cliente'] ?>"
                                    data-search="<?= strtolower(htmlspecialchars($c['cliente_nombre'] . ' ' . $c['rnc_cedula'])) ?>">
                                    <?= htmlspecialchars($c['cliente_nombre']) ?>
                                    <?= !empty($c['rnc_cedula']) ? '('.$c['rnc_cedula'].')' : '' ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" id="btnAbrirModalCliente"
                                class="bg-amber-500 hover:bg-amber-600 text-white px-3.5 rounded-xl text-sm transition font-black shadow-md shadow-amber-500/20"
                                title="Registrar nuevo cliente">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Método de Pago</label>
                        <select name="id_metodo_pago" id="selectMetodoPago"
                            class="w-full bg-slate-50 border border-slate-200 p-2.5 rounded-xl font-medium text-slate-800 text-xs focus:outline-none"
                            required>
                            <?php foreach ($metodos_pago as $m): ?>
                            <option value="<?= $m['id_metodo'] ?>"><?= htmlspecialchars($m['nombre_metodo']) ?></option>
                            <?php endforeach; ?>
                        </select>

                    </div>



                    <div class="border-t border-slate-100 pt-3 space-y-3">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Tipo de
                                Entrega</label>
                            <select name="tipo_entrega" id="selectTipoEntrega"
                                class="w-full bg-slate-50 border border-slate-200 p-2.5 rounded-xl font-medium text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                <option value="Tienda" selected>Retiro en Tienda</option>
                                <option value="Envio">Envío a Domicilio / Agencia</option>
                            </select>
                        </div>

                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/60 space-y-2">
                            <div class="flex items-center gap-3">
                                <div
                                    class="bg-blue-100 text-blue-600 w-8 h-8 rounded-xl flex items-center justify-center text-sm shrink-0">
                                    <i class="fa-solid fa-file-invoice-dollar"></i>
                                </div>
                                <div>
                                    <span class="text-xs font-bold text-slate-700 block">¿Requiere Comprobante
                                        Fiscal?</span>
                                    <span class="text-[10px] text-slate-400 block leading-tight">Selecciona una opción
                                        para continuar</span>
                                </div>
                            </div>

                            <div class="flex gap-4 pt-1 pl-11">
                                <label
                                    class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                                    <input type="radio" name="requiere_ncf_radio" value="no" checked
                                        class="w-4 h-4 accent-blue-600"> No
                                </label>
                                <label
                                    class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                                    <input type="radio" name="requiere_ncf_radio" id="rdRequiereNCF_si" value="si"
                                        class="w-4 h-4 accent-blue-600"> Sí, registrar NCF
                                </label>
                            </div>
                        </div>

                        <div id="contenedorComprobante"
                            class="hidden bg-slate-50 p-4 rounded-2xl border border-slate-200/60 space-y-3">

                            <div>
                                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Tipo de
                                    Comprobante Fiscal (Automático)</label>
                                <select name="id_comprobante" id="selectTipoComprobante"
                                    class="w-full bg-white border border-slate-200 p-2.5 rounded-xl font-medium text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                    <option value="">-- Seleccione de la Base de Datos --</option>
                                    <?php foreach ($comprobantes_disponibles as $comp): ?>
                                    <option value="<?= $comp['id_ncf'] ?>">
                                        <?= htmlspecialchars($comp['tipo_comprobante']) ?> (Prefijo:
                                        <?= htmlspecialchars($comp['prefijo']) ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="flex items-center my-2 text-[10px] font-bold uppercase text-slate-400">
                                <hr class="flex-1 border-slate-200">
                                <span class="px-2">O introduce uno manual</span>
                                <hr class="flex-1 border-slate-200">
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">NCF Manual
                                    (Escribe el código completo)</label>
                                <input type="text" name="ncf_manual" id="txtNcfManual" placeholder="Ej: B0100000005"
                                    maxlength="11"
                                    class="w-full bg-white border border-slate-200 p-2.5 rounded-xl font-mono font-bold text-xs uppercase text-slate-700 placeholder-slate-400 focus:outline-none focus:border-blue-500">
                                <span class="text-[9px] text-slate-400 block mt-1 leading-tight">Si escribes un NCF
                                    aquí, el sistema ignorará la selección automática de arriba.</span>
                            </div>

                        </div>
                        <div id="contenedorAgencia" class="hidden">
                            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Agencia / Destino
                                de Envío</label>
                            <select name="id_agencia_envio" id="selectAgenciaEnvio"
                                class="w-full bg-slate-50 border border-slate-200 p-2.5 rounded-xl font-medium text-slate-800 text-xs focus:outline-none focus:border-blue-500">
                                <option value="" data-costo="0">-- Seleccione Agencia --</option>
                                <?php foreach ($agencias as $ag): ?>
                                <option value="<?= $ag['id'] ?>" data-costo="<?= $ag['costo'] ?>">
                                    <?= htmlspecialchars($ag['agencia']) ?> (<?= htmlspecialchars($ag['provincia']) ?> -
                                    <?= htmlspecialchars($ag['ciudad']) ?>) — RD$<?= number_format($ag['costo'], 2) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Comentarios o
                            Notas</label>
                        <textarea name="comentarios" placeholder="Opcional: detalles del estado de entrega, garantía..."
                            rows="2"
                            class="w-full bg-slate-50 border border-slate-200 p-2.5 rounded-xl font-medium text-slate-800 text-xs focus:outline-none"></textarea>
                    </div>
                </div>

                <div class="bg-slate-900 text-white p-6 rounded-3xl border border-slate-800 shadow-xl space-y-4">
                    <h3 class="text-xs font-black text-slate-400 uppercase tracking-wider">Cómputo Total de Caja</h3>

                    <div class="space-y-2 text-xs border-b border-slate-800 pb-4">
                        <div class="flex justify-between text-slate-400">
                            <span>Subtotal Artículos:</span>
                            <span>RD$ <span id="lblSubtotal">0.00</span></span>
                        </div>
                        <div class="flex justify-between text-slate-400">
                            <span>ITBIS (18% Incl.):</span>
                            <span>RD$ <span id="lblItbis">0.00</span></span>
                        </div>
                        <div class="flex justify-between text-blue-400 font-bold" id="contenedorCostoEnvioLbl">
                            <span>Costo de Envío:</span>
                            <span>RD$ <span id="lblCostoEnvio">0.00</span></span>
                        </div>
                    </div>

                    <div class="flex justify-between items-center py-2">
                        <span class="text-sm font-bold text-slate-300">TOTAL NETO:</span>
                        <span class="text-2xl font-black text-emerald-400">RD$ <span
                                id="lblTotalNeto">0.00</span></span>
                    </div>

                    <div class="space-y-3 pt-2 border-t border-slate-800">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Monto Recibido
                                (Efectivo)</label>
                            <input type="number" step="0.01" name="monto_pagado" id="txtMontoPagado" value="0.00"
                                class="w-full bg-slate-800 border border-slate-700 p-2.5 rounded-xl font-black text-white text-sm focus:outline-none focus:border-emerald-500">
                        </div>
                        <div class="flex justify-between items-center text-xs text-slate-400">
                            <span>Cambio/Devuelta:</span>
                            <span>RD$ <span id="lblDevuelta">0.00</span></span>
                        </div>
                    </div>

                    <button type="submit" name="procesar_venta" id="btnProcesarVenta"
                        class="w-full bg-emerald-600 hover:bg-emerald-700 text-white py-4 rounded-2xl font-black text-sm tracking-wide shadow-lg shadow-emerald-900/30 transition mt-4 flex items-center justify-center gap-2"
                        disabled>
                        <i class="fa-solid fa-print"></i> Guardar y Procesar Factura
                    </button>
                </div>

            </div>
        </form>
    </div>

    <div id="modalCliente"
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center hidden z-50 p-4">
        <div
            class="bg-white w-full max-w-md rounded-3xl shadow-2xl border border-slate-100 overflow-hidden transform transition-all">
            <div class="p-6 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <div
                        class="bg-amber-100 text-amber-700 w-8 h-8 rounded-xl flex items-center justify-center text-sm">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <h3 class="font-black text-slate-800 text-sm uppercase tracking-wide">Registrar Cliente Nuevo</h3>
                </div>
                <button type="button" id="btnCerrarModal" class="text-slate-400 hover:text-slate-600 transition"><i
                        class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form id="formModalCliente" class="p-6 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Nombre *</label>
                        <input type="text" id="modal_nombre" required
                            class="w-full bg-slate-50 border border-slate-200 p-2.5 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:border-amber-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Apellido *</label>
                        <input type="text" id="modal_apellido" required
                            class="w-full bg-slate-50 border border-slate-200 p-2.5 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:border-amber-500">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">RNC o Cédula</label>
                    <input type="text" id="modal_rnc" placeholder="Ej: 402-XXXXXXX-X"
                        class="w-full bg-slate-50 border border-slate-200 p-2.5 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:border-amber-500">
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Teléfono</label>
                    <input type="text" id="modal_telefono" placeholder="Ej: 809-555-5555"
                        class="w-full bg-slate-50 border border-slate-200 p-2.5 rounded-xl text-xs font-semibold text-slate-800 focus:outline-none focus:border-amber-500">
                </div>

                <div class="pt-4 border-t border-slate-100 flex justify-end gap-2">
                    <button type="button" id="btnCancelarModal"
                        class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2.5 rounded-xl text-xs font-bold transition">Cancelar</button>
                    <button type="submit"
                        class="bg-amber-500 hover:bg-amber-600 text-white px-5 py-2.5 rounded-xl text-xs font-black transition shadow-md shadow-amber-500/10">Guardar
                        Cliente</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script>
    let indiceCarrito = 0;

    $(document).ready(function() {

        /* =========================================================
           NUEVO: INTERRUPTOR TIPO DE ENTREGA / AGENCIA
        ========================================================= */
        $('#selectTipoEntrega').on('change', function() {
            if ($(this).val() === 'Envio') {
                $('#contenedorAgencia').removeClass('hidden');
                $('#selectAgenciaEnvio').attr('required', 'required');
            } else {
                $('#contenedorAgencia').addClass('hidden');
                $('#selectAgenciaEnvio').removeAttr('required').val('');
            }
            recalcularTotales();
        });

        $('#selectAgenciaEnvio').on('change', function() {
            recalcularTotales();
        });

        /* =========================================================
           A. FILTRO DE PRODUCTOS POR LOS ÚLTIMOS 4 DÍGITOS
        ========================================================= */
        $('#txtBuscarDigitos').on('input', function() {
            let digitos = $(this).val().trim();
            if (digitos.length === 4) {
                let encontrado = false;
                $('#selectProducto option').each(function() {
                    let idLocal = $(this).data('idlocal')?.toString() || '';
                    if (idLocal.endsWith(digitos)) {
                        $('#selectProducto').val($(this).val());
                        encontrado = true;
                        return false;
                    }
                });
            }
        });

        /* =========================================================
           B. FILTRO DINÁMICO DE CLIENTES
        ========================================================= */
        $('#txtBuscarCliente').on('input', function() {
            let busqueda = $(this).val().toLowerCase().trim();
            let primerMatch = null;

            $('#selectCliente option').each(function() {
                let textoFiltro = $(this).data('search') || '';
                if (textoFiltro.includes(busqueda)) {
                    $(this).show();
                    if (!primerMatch) primerMatch = $(this).val();
                } else {
                    $(this).hide();
                }
            });

            if (primerMatch && busqueda !== '') {
                $('#selectCliente').val(primerMatch);
            }
        });

        /* =========================================================
           C. CONTROL INTERACTIVO DEL MODAL DE CLIENTES
        ========================================================= */
        $('#btnAbrirModalCliente').on('click', function() {
            $('#modalCliente').removeClass('hidden');
        });

        function cerrarModal() {
            $('#modalCliente').addClass('hidden');
            $('#formModalCliente')[0].reset();
        }

        $('#btnCerrarModal, #btnCancelarModal').on('click', function() {
            cerrarModal();
        });

        $('#formModalCliente').on('submit', function(e) {
            e.preventDefault();
            const datosCliente = {
                registrar_cliente_ajax: true,
                nombre: $('#modal_nombre').val().trim(),
                apellido: $('#modal_apellido').val().trim(),
                rnc_cedula: $('#modal_rnc').val().trim(),
                telefono: $('#modal_telefono').val().trim()
            };

            $.ajax({
                url: 'registrar_cliente_ajax.php',
                type: 'POST',
                data: datosCliente,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        const nuevoOption =
                            `<option value="${response.id_cliente}" data-search="${(datosCliente.nombre + ' ' + datosCliente.apellido + ' ' + datosCliente.rnc_cedula).toLowerCase()}">${datosCliente.nombre} ${datosCliente.apellido} (${datosCliente.rnc_cedula || 'Sin RNC'})</option>`;
                        $('#selectCliente').append(nuevoOption).val(response.id_cliente);
                        cerrarModal();
                        alert("Cliente registrado e ingresado con éxito.");
                    } else {
                        alert("Error: " + response.message);
                    }
                },
                error: function() {
                    alert("Hubo un error de conexión con el servidor.");
                }
            });
        });

        /* =========================================================
           D. LÓGICA DEL CARRITO DE COMPRAS
        ========================================================= */
        $('#btnAgregarCarrito').on('click', function() {
            const option = $('#selectProducto option:selected');
            const idProd = option.val();

            if (!idProd) {
                alert("Por favor, selecciona un artículo disponible.");
                return;
            }

            const idLocal = option.data('idlocal');
            const marca = option.data('marca');
            const modelo = option.data('modelo');
            const precio = parseFloat(option.data('precio')) || 0;

            if ($(`.prod-id-clase[value="${idProd}"]`).length > 0) {
                alert("Este artículo único ya está añadido en la factura actual.");
                return;
            }

            $('#filaVacia').hide();

            const filaHtml = `
        <tr class="fila-producto" id="fila_${indiceCarrito}">
            <td class="p-4 font-mono font-bold text-blue-600">
                ${idLocal}
                <input type="hidden" class="prod-id-clase" name="productos_carrito[${indiceCarrito}][id_producto]" value="${idProd}" />
            </td>
            <td class="p-4 font-semibold text-slate-800">${marca} ${modelo}</td>
            <td class="p-4 text-center">
                <input type="number" name="productos_carrito[${indiceCarrito}][cantidad]" value="1" min="1" readonly class="w-12 bg-slate-50 text-center font-bold text-xs p-1 rounded border border-slate-200 focus:outline-none" />
            </td>
            <td class="p-4 text-right font-bold text-slate-700">RD$ ${precio.toFixed(2)}</td>
            <td class="p-4 text-right font-black text-slate-900 line-subtotal" data-valor="${precio}">RD$ ${precio.toFixed(2)}</td>
            <td class="p-4 text-center">
                <button type="button" onclick="eliminarFila(${indiceCarrito})" class="text-rose-500 hover:text-rose-700 text-sm transition"><i class="fa-solid fa-trash-can"></i></button>
            </td>
        </tr>
    `;

            $('#tbodyCarrito').append(filaHtml);
            indiceCarrito++;

            $('#selectProducto').val('');
            $('#txtBuscarDigitos').val('');

            recalcularTotales();
        });

        $('#txtMontoPagado').on('input', function() {
            calcularCambio();
        });

        /* =========================================================
           NUEVO: CARGA AUTOMÁTICA DESDE LA COTIZACIÓN DE ORIGEN
        ========================================================= */
        function cargarProductosCotizados() {
            // Leemos la variable JSON que genera PHP de forma segura
            const productosCotizados = <?= $productos_precargados_json ?? '[]' ?>;

            if (productosCotizados.length > 0) {
                productosCotizados.forEach(function(item) {
                    // Buscamos la opción correspondiente en el select de productos
                    let optionProducto = $(
                        `#selectProducto option[data-idlocal="${item.id_producto}"]`);

                    // Si no se encuentra por id_local, probamos buscar por el value primario (id)
                    if (optionProducto.length === 0) {
                        optionProducto = $(`#selectProducto option[value="${item.id_producto}"]`);
                    }

                    if (optionProducto.length > 0) {
                        // Marcamos la opción encontrada y forzamos el click del botón de añadir
                        $('#selectProducto').val(optionProducto.val());
                        $('#btnAgregarCarrito').click();
                    }
                });
            }
        }

        // Ejecutar la precarga al finalizar de montar el DOM
        cargarProductosCotizados();
    });

    function eliminarFila(idFila) {
        $(`#fila_${idFila}`).remove();
        if ($('.fila-producto').length === 0) {
            $('#filaVacia').show();
        }
        recalcularTotales();
    }

    function recalcularTotales() {
        let totalProductos = 0;
        let totalItems = 0;

        $('.line-subtotal').each(function() {
            totalProductos += parseFloat($(this).data('valor')) || 0;
            totalItems++;
        });

        // Obtener el costo de envío seleccionado si aplica
        let costoEnvio = 0;
        if ($('#selectTipoEntrega').val() === 'Envio') {
            const agenciaSeleccionada = $('#selectAgenciaEnvio option:selected');
            costoEnvio = parseFloat(agenciaSeleccionada.data('costo')) || 0;
        }

        // Los productos ya tienen el ITBIS incluido
        let subtotalProductos = totalProductos / 1.18;
        let itbis = totalProductos - subtotalProductos;

        // El Total Neto final es la suma de los productos más el envío
        let totalNetoFactura = totalProductos + costoEnvio;

        // Actualizar elementos visuales
        $('#lblSubtotal').text(subtotalProductos.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }));
        $('#lblItbis').text(itbis.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }));
        $('#lblCostoEnvio').text(costoEnvio.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }));
        $('#lblTotalNeto').text(totalNetoFactura.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }));
        $('#contadorItems').text(`${totalItems} Artículos`);

        if ($('#txtMontoPagado').val() == 0 || parseFloat($('#txtMontoPagado').val()) < totalNetoFactura) {
            $('#txtMontoPagado').val(totalNetoFactura.toFixed(2));
        }

        if (totalItems > 0) {
            $('#btnProcesarVenta').removeAttr('disabled');
        } else {
            $('#btnProcesarVenta').attr('disabled', 'disabled');
            $('#txtMontoPagado').val('0.00');
        }

        calcularCambio();
    }

    function calcularCambio() {

        const totalNetoTexto = $('#lblTotalNeto').text().replace(/,/g, '');
        const totalNeto = parseFloat(totalNetoTexto) || 0;
        const montoPagado = parseFloat($('#txtMontoPagado').val()) || 0;

        let devuelta = montoPagado - totalNeto;
        if (devuelta < 0) devuelta = 0;

        $('#lblDevuelta').text(devuelta.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }));
    }
    </script>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const radiosNCF = document.querySelectorAll('input[name="requiere_ncf_radio"]');
        const contenedorComprobante = document.getElementById("contenedorComprobante");
        const selectTipoComprobante = document.getElementById("selectTipoComprobante");
        const txtNcfManual = document.getElementById("txtNcfManual");
        const formVenta = document.getElementById("formVenta");

        // Forzar mayúsculas en el NCF manual mientras se escribe
        txtNcfManual.addEventListener("input", function() {
            this.value = this.value.toUpperCase();
        });

        radiosNCF.forEach(radio => {
            radio.addEventListener("change", function() {
                if (this.value === "si") {
                    contenedorComprobante.classList.remove("hidden");
                } else {
                    contenedorComprobante.classList.add("hidden");
                    // Limpiar valores si se marca "No"
                    selectTipoComprobante.value = "";
                    txtNcfManual.value = "";
                }
            });
        });

        // Validación antes de enviar el formulario
        formVenta.addEventListener("submit", function(e) {
            const requiereNCF = document.querySelector('input[name="requiere_ncf_radio"]:checked')
                .value;

            if (requiereNCF === "si") {
                // Validar que al menos uno de los dos esté lleno
                if (selectTipoComprobante.value === "" && txtNcfManual.value.trim() === "") {
                    e.preventDefault(); // Detener el envío
                    alert(
                        "Por favor, seleccione un tipo de NCF automático o introduzca un NCF manual."
                    );
                    selectTipoComprobante.focus();
                }
            }
        });
    });
    </script>
</body>

</html>