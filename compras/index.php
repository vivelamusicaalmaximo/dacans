<?php
session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config/conexion.php'; 

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; 

$mensaje_success = "";
$mensaje_error = "";

if (!function_exists('registrarLogCrear')) {
    function registrarLogCrear($pdo, $id_local, $datos) {
        return true;
    }
}

/* =========================================================
   1. PROCESAR EL ENVÍO AL INVENTARIO Y ACTUALIZAR ESTADO
========================================================= */
if (isset($_POST['migrar_producto_detallado'])) {
    try {
        $pdo->beginTransaction();

        $equipos = $_POST['equipo'] ?? [];
        $fecha_actual = date('Y-m-d H:i:s');

        if (empty($equipos)) {
            throw new Exception("No se enviaron datos de equipos para registrar.");
        }

        $sqlCheck = "SELECT COUNT(*) FROM productos_informatica WHERE id_local = ?";
        $stmtCheck = $pdo->prepare($sqlCheck);

        $sqlInsert = "INSERT INTO productos_informatica (
            id_local, serie, proc_marca, proc_familia, proc_generacion, proc_modelo,
            graficos, g_expandible, memoria, disco, pantalla, p_resolucion, touch,
            precio, estado, comenta, equipo_marca, equipo_modelo, imagen_url, 
            imagenes_adicionales, created_at, vendida_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)";
        
        $stmtInsert = $pdo->prepare($sqlInsert);

        $sqlUpdateOrig = "UPDATE compras_articulos SET migrada = 1 WHERE id = ?";
        $stmtUpdateOrig = $pdo->prepare($sqlUpdateOrig);

        $htmlEquiposMail = "";

        foreach ($equipos as $eq) {
            $id_local_evaluar = trim($eq['id_local'] ?? '');

            $stmtCheck->execute([$id_local_evaluar]);
            $existe = $stmtCheck->fetchColumn();

            if ($existe > 0) {
                throw new Exception("El ID Local <strong>{$id_local_evaluar}</strong> ya se encuentra registrado.");
            }

            $serie = trim($eq['serie'] ?? '') ?: 'PENDIENTE';

            $touch_texto = strtolower(trim($eq['touch'] ?? ''));
            $touch_int = ($touch_texto === 'no' || empty($touch_texto)) ? 0 : 1;

            $g_expandible_texto = strtolower(trim($eq['g_expandible'] ?? ''));
            $g_expandible_int = ($g_expandible_texto === 'si') ? 1 : 0;

            $stmtInsert->execute([
                $id_local_evaluar,
                $serie,
                trim($eq['proc_marca'] ?? ''),
                trim($eq['proc_familia'] ?? ''),
                trim($eq['proc_generacion'] ?? ''),
                trim($eq['proc_modelo'] ?? ''),
                trim($eq['graficos'] ?? ''),
                $g_expandible_int,
                trim($eq['memoria'] ?? ''),
                trim($eq['disco'] ?? ''),
                trim($eq['pantalla'] ?? ''),
                trim($eq['p_resolucion'] ?? ''),
                $touch_int,
                (float)($eq['precio'] ?? 0),
                'En camino',
                trim($eq['comenta'] ?? ''),
                trim($eq['equipo_marca'] ?? ''),
                trim($eq['equipo_modelo'] ?? ''),
                trim($eq['imagen_url'] ?? ''),
                trim($eq['imagenes_adicionales'] ?? ''),
                $fecha_actual
            ]);

            if (isset($eq['id_original_compra'])) {
                $stmtUpdateOrig->execute([$eq['id_original_compra']]);
            }

            $datosNuevoEquipo = [
                'equipo_marca'  => $eq['equipo_marca'] ?? '',
                'equipo_modelo' => $eq['equipo_modelo'] ?? '',
                'proc_marca'    => $eq['proc_marca'] ?? '',
                'proc_familia'  => $eq['proc_familia'] ?? '',
                'proc_modelo'   => $eq['proc_modelo'] ?? '',
                'memoria'       => $eq['memoria'] ?? '',
                'disco'         => $eq['disco'] ?? '',
                'g_expandible'  => $g_expandible_int,
                'precio'        => $eq['precio'] ?? 0
            ];
            registrarLogCrear($pdo, $id_local_evaluar, $datosNuevoEquipo);

            $htmlEquiposMail .= "
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'><strong>{$id_local_evaluar}</strong></td>
                    <td style='padding:8px; border:1px solid #ddd;'>{$eq['equipo_marca']} {$eq['equipo_modelo']}</td>
                    <td style='padding:8px; border:1px solid #ddd;'>{$serie}</td>
                    <td style='padding:8px; border:1px solid #ddd;'>{$eq['memoria']} / {$eq['disco']}</td>
                    <td style='padding:8px; border:1px solid #ddd; color:#16a34a;'>RD$ " . number_format((float)$eq['precio'], 2) . "</td>
                </tr>";
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'pfernandez@dacansdr.com';          
        $mail->Password   = 'qbvi hhmq hrcb pmew'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('pfernandez@dacansdr.com', 'Sistema de Inventario');
        $mail->addAddress('pfernandez@dacansdr.com'); 

        $mail->isHTML(true);
        $mail->Subject = '🔔 Notificación: Nuevos equipos migrados al Inventario';
        
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; color: #333; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #2563eb;'>Resumen de Migración de Lote</h2>
                <p>Se ha completado con éxito la migración de <strong>" . count($equipos) . "</strong> equipos al inventario el <strong>{$fecha_actual}</strong>.</p>
                <table style='width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px;'>
                    <thead>
                        <tr style='background-color: #f3f4f6; text-align: left;'>
                            <th style='padding:8px; border:1px solid #ddd;'>ID Local</th>
                            <th style='padding:8px; border:1px solid #ddd;'>Equipo</th>
                            <th style='padding:8px; border:1px solid #ddd;'>No. Serie</th>
                            <th style='padding:8px; border:1px solid #ddd;'>Specs</th>
                            <th style='padding:8px; border:1px solid #ddd;'>Precio</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$htmlEquiposMail}
                    </tbody>
                </table>
                <br>
                <p style='font-size: 11px; color: #666;'>Este es un correo automático generado por el sistema.</p>
            </div>";

        $mail->send();
        $pdo->commit();
        $mensaje_success = "¡Éxito! Se han registrado " . count($equipos) . " equipos correctamente.";
        header("Location: index.php?success=" . urlencode($mensaje_success));
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $mensaje_error = "Error en el proceso: " . $e->getMessage();
    }
}

/* =========================================================
   2. OBTENER EL ÚLTIMO ID_LOCAL ADAPTADO A SQL SERVER O MYSQL
========================================================= */
$ultimo_numero_base = 1000; 

try {
    $query_ultimo = $pdo->query("SELECT TOP 1 id_local FROM productos_informatica ORDER BY LEN(id_local) DESC, id_local DESC");
    if (!$query_ultimo) {
        $query_ultimo = $pdo->query("SELECT id_local FROM productos_informatica ORDER BY LENGTH(id_local) DESC, id_local DESC LIMIT 1");
    }
    if ($query_ultimo) {
        $ultimo = $query_ultimo->fetch(PDO::FETCH_ASSOC);
        if ($ultimo && !empty($ultimo['id_local'])) {
            $id_local_texto = trim($ultimo['id_local']); 
            if (preg_match('/-(\d+)$/', $id_local_texto, $match)) {
                $ultimo_numero_base = (int)$match[1]; 
            } 
        }
    }
} catch (Exception $e) {
    try {
        $query_ultimo = $pdo->query("SELECT id_local FROM productos_informatica ORDER BY id_local DESC LIMIT 1");
        if ($query_ultimo) {
            $ultimo = $query_ultimo->fetch(PDO::FETCH_ASSOC);
            if ($ultimo && preg_match('/-(\d+)$/', trim($ultimo['id_local']), $match)) {
                $ultimo_numero_base = (int)$match[1];
            }
        }
    } catch(Exception $ex) {
        $ultimo_numero_base = 1000; 
    }
}

try {
    $articulos = $pdo->query("SELECT * FROM compras_articulos WHERE migrada = 0 OR migrada IS NULL ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al consultar los datos: " . $e->getMessage());
}

try {
    $historial_agregados = $pdo->query("SELECT * FROM productos_informatica ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $historial_agregados = [];
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compras Artículos</title>
    <link rel="shortcut icon" href="/img/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <style>
    body {
        background-color: #f8fafc;
        font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    }

    .card {
        background: #ffffff;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05);
    }

    table.dataTable {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 13px;
    }

    table.dataTable thead th {
        background-color: #0f172a !important;
        color: #ffffff !important;
        padding: 12px 10px !important;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border: none !important;
    }

    table.dataTable tbody td {
        padding: 12px 10px !important;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9 !important;
    }

    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #2563eb !important;
        color: white !important;
        border: 1px solid #2563eb !important;
        border-radius: 6px;
    }
    </style>
</head>

<body class="p-4 md:p-6 bg-slate-50 text-slate-800">

    <div class="max-w-[1600px] mx-auto space-y-6">

        <?php if (!empty($mensaje_success)): ?>
        <div
            class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl font-medium flex items-center gap-2 shadow-sm animate-fade-in">
            <i class="fa-solid fa-circle-check text-emerald-500 text-lg"></i> <?= htmlspecialchars($mensaje_success) ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($mensaje_error)): ?>
        <div
            class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl font-medium flex items-center gap-2 shadow-sm animate-fade-in">
            <i class="fa-solid fa-circle-xmark text-rose-500 text-lg"></i> <?= htmlspecialchars($mensaje_error) ?>
        </div>
        <?php endif; ?>

        <div
            class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex items-center gap-4">
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200 hidden sm:block">
                    <img src="../img/logo.webp" class="h-12 w-auto object-contain" alt="Logo">
                </div>
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold tracking-tight text-slate-900">Compras de Artículos</h1>
                    <p class="text-slate-500 text-sm mt-0.5">Gestión avanzada de lotes de compras, análisis de costos y
                        migración</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 w-full md:w-auto">
                <a href="crear.php"
                    class="flex-1 md:flex-none text-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-semibold shadow-sm transition">
                    <i class="fa-solid fa-plus mr-1.5"></i> Nueva Compra
                </a>
                <a href="importar_excel.php"
                    class="flex-1 md:flex-none text-center bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2.5 rounded-lg text-sm font-semibold shadow-sm transition">
                    <i class="fa-solid fa-file-excel mr-1.5"></i> Excel
                </a>
                <a href="../mantenimiento"
                    class="flex-1 md:flex-none text-center bg-slate-800 hover:bg-slate-900 text-white px-4 py-2.5 rounded-lg text-sm font-semibold shadow-sm transition">
                    <i class="fa-solid fa-arrow-left mr-1.5"></i> Volver
                </a>
            </div>
        </div>

        <div class="card p-5">
            <div class="mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
                <div class="w-1 h-5 bg-blue-600 rounded-full"></div>
                <h2 class="text-lg font-bold text-slate-900">Lotes de Compras Activos</h2>
            </div>
            <div class="overflow-x-auto">
                <table id="tablaCompras" class="display cell-border compact hover">
                    <thead>
                        <tr>
                            <th>Acciones</th>
                            <th>ID</th>
                            <th>ID Artículo (eBay)</th>
                            <th>Descripción Artículo</th>
                            <th>Status Compra</th>
                            <th>ID Courier</th>
                            <th>Dirección</th>
                            <th>Tracking US</th>
                            <th>Cant.</th>
                            <th>Costo USD</th>
                            <th>Precio Sugerido</th>
                            <th>Total DOP</th>
                            <th>Costo Unitario</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($articulos) && is_array($articulos)): ?>
                        <?php foreach ($articulos as $articulo): 
                            $id_art = (int)($articulo['id'] ?? 0);
                            $item_id = trim($articulo['item_id'] ?? '');
                            $id_currier = trim($articulo['id_courier'] ?? '');
                            $direccion = htmlspecialchars($articulo['direccion_usada'] ?? 'N/A');
                            $numero_rastreo_us = htmlspecialchars($articulo['numero_rastreo_us'] ?? 'N/A');
                            $nombre_art = $articulo['nombre_articulo'] ?? 'Artículo sin nombre';
                            $amount = (int)($articulo['cantidad_articulos'] ?? 0);
                            $costoDOP = (float)($articulo['costo_dop'] ?? 0);
                            $impuestos = (float)($articulo['costo_impuestos'] ?? 0);
                            $envio = (float)($articulo['costo_envio'] ?? 0);
                            $costoUSD = (float)($articulo['costo_usd'] ?? 0);
                            
                            $porcentaje = (float)($articulo['porcentaje_incremento'] ?? 80);
                            $totalLote = $costoDOP + $impuestos + $envio;
                            $costoUnitario = $amount > 0 ? ($totalLote / $amount) : 0;
                            $costoSugerido = $costoUnitario + ($costoUnitario * $porcentaje / 100);
                            
                            $estado = htmlspecialchars($articulo['status_compra'] ?? 'N/A');
                        ?>
                        <tr>
                            <td>
                                <div class="flex items-center gap-1">
                                    <a href="editar.php?id=<?= $id_art ?>"
                                        class="p-1.5 bg-amber-500 text-white rounded hover:bg-amber-600 transition text-xs"
                                        title="Editar"><i class="fa-solid fa-pen"></i></a>
                                    <a href="eliminar.php?id=<?= $id_art ?>"
                                        onclick="return confirm('¿Seguro que deseas eliminar esta compra?')"
                                        class="p-1.5 bg-rose-500 text-white rounded hover:bg-rose-600 transition text-xs"
                                        title="Eliminar"><i class="fa-solid fa-trash"></i></a>
                                    <button type="button" data-id="<?= $id_art ?>"
                                        data-nombre="<?= htmlspecialchars($nombre_art, ENT_QUOTES, 'UTF-8') ?>"
                                        data-cantidad="<?= $amount ?>" data-sugerido="<?= $costoSugerido ?>"
                                        class="btn-abrir-modal p-1.5 bg-emerald-600 text-white rounded hover:bg-emerald-700 transition text-xs"
                                        title="Migrar a Inventario">
                                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="font-bold text-slate-900"><?= $id_art ?></td>
                            <td>
                                <?php if (!empty($item_id)): ?>
                                <a href="https://www.ebay.com/itm/<?= urlencode($item_id) ?>" target="_blank"
                                    class="text-blue-600 hover:underline flex items-center gap-1 font-mono">
                                    <?= htmlspecialchars($item_id) ?> <i
                                        class="fa-solid fa-arrow-up-right-from-square text-[9px]"></i>
                                </a>
                                <?php else: ?>
                                <span class="text-slate-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="font-medium text-slate-700"><?= htmlspecialchars($nombre_art) ?></td>
                            <td>
                                <select
                                    class="bg-slate-50 border border-slate-300 text-slate-700 font-medium rounded text-xs px-2 py-1 focus:ring-1 focus:ring-blue-500"
                                    data-id="<?= $id_art ?>" onchange="actualizarStatus(this)">
                                    <?php
                                    $opciones_status = ['Ganado', 'Pagado', 'Enviado', 'Cancelado', 'Entregado', 'Aduanas', 'Listo para Recogida', 'Disponible'];
                                    foreach ($opciones_status as $opcion):
                                    ?>
                                    <option value="<?= $opcion ?>" <?= ($estado == $opcion) ? 'selected' : '' ?>>
                                        <?= $opcion ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="font-semibold text-slate-600"><?= $id_currier ?></td>
                            <td class="max-w-[150px] truncate" title="<?= $direccion ?>"><?= $direccion ?></td>
                            <td class="font-mono text-xs"><?= $numero_rastreo_us ?></td>
                            <td class="font-bold text-center bg-slate-50"><?= $amount ?></td>
                            <td class="text-right">$<?= number_format($costoUSD, 2) ?></td>
                            <td class="font-bold text-emerald-600 text-right">RD$<?= number_format($costoSugerido, 2) ?>
                            </td>
                            <td class="text-right text-slate-600">RD$<?= number_format($costoDOP, 2) ?></td>
                            <td class="font-bold text-blue-700 text-right">RD$<?= number_format($costoUnitario, 2) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card p-5">
            <div class="mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
                <div class="w-1 h-5 bg-emerald-600 rounded-full"></div>
                <h2 class="text-lg font-bold text-slate-900">Historial de Artículos en Inventario</h2>
            </div>
            <div class="overflow-x-auto">
                <table id="tablaHistorial" class="display cell-border compact hover">
                    <thead>
                        <tr>
                            <th>ID Local</th>
                            <th>Marca / Modelo</th>
                            <th>No. Serie</th>
                            <th>Procesador</th>
                            <th>Memoria (RAM)</th>
                            <th>Disco</th>
                            <th>Precio Venta</th>
                            <th>Estado</th>
                            <th>Fecha Registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial_agregados as $item): ?>
                        <tr>
                            <td class="font-mono font-bold text-blue-600"><?= htmlspecialchars($item['id_local']) ?>
                            </td>
                            <td class="font-medium text-slate-800">
                                <?= htmlspecialchars(($item['equipo_marca'] ?? '') . ' ' . ($item['equipo_modelo'] ?? '')) ?>
                            </td>
                            <td class="font-mono text-slate-600 text-xs">
                                <?= htmlspecialchars($item['serie'] ?? 'PENDIENTE') ?></td>
                            <td><?= htmlspecialchars(($item['proc_marca'] ?? '') . ' ' . ($item['proc_modelo'] ?? '')) ?>
                            </td>
                            <td><?= htmlspecialchars($item['memoria'] ?? '') ?></td>
                            <td><?= htmlspecialchars($item['disco'] ?? '') ?></td>
                            <td class="font-bold text-emerald-600">RD$<?= number_format((float)$item['precio'], 2) ?>
                            </td>
                            <td>
                                <span
                                    class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">
                                    <?= htmlspecialchars($item['estado'] ?? 'En camino') ?>
                                </span>
                            </td>
                            <td class="text-slate-400 text-xs"><?= htmlspecialchars($item['created_at'] ?? '') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="modalMigrarDetallado"
        class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
        <div
            class="bg-white rounded-xl shadow-xl border border-slate-200 max-w-5xl w-full max-h-[90vh] flex flex-col overflow-hidden">
            <div class="p-4 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center text-sm"><i
                            class="fa-solid fa-bolt"></i></div>
                    <div>
                        <h3 class="text-sm font-bold">Personalización con Espejo de Datos</h3>
                        <p class="text-xs text-slate-400">Modifica la <strong>Unidad #1 (Maestra)</strong> para replicar
                            el contenido hacia abajo.</p>
                    </div>
                </div>
                <button type="button" onclick="cerrarModal()" class="text-slate-400 hover:text-white transition"><i
                        class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form method="POST" class="flex flex-col flex-1 overflow-hidden">
                <div id="contenedorEquiposDinamicos" class="p-4 space-y-4 overflow-y-auto flex-1 bg-slate-50"></div>

                <div class="p-4 bg-white border-t border-slate-200 flex items-center justify-between shadow-inner">
                    <p class="text-xs font-semibold text-blue-600 flex items-center gap-1">
                        <i class="fa-solid fa-truck-ramp-box"></i> Estatus asignado de fábrica: "En camino"
                    </p>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="cerrarModal()"
                            class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2 rounded-lg text-xs font-medium transition">Cancelar</button>
                        <button type="submit" name="migrar_producto_detallado"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded-lg text-xs font-bold shadow-sm transition">
                            <i class="fa-solid fa-cloud-arrow-up mr-1"></i> Confirmar Guardado
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>
    let ultimoNumeroBaseGlobal = parseInt("<?= $ultimo_numero_base ?>") || 1000;

    $(document).ready(function() {
        $('#tablaCompras').DataTable({
            responsive: true,
            pageLength: 10,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            }
        });

        $('#tablaHistorial').DataTable({
            responsive: true,
            pageLength: 5,
            order: [
                [0, 'desc']
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            }
        });

        $('#tablaCompras tbody').on('click', '.btn-abrir-modal', function() {
            const idCompra = $(this).attr('data-id');
            const nombreArticulo = $(this).attr('data-nombre');
            const cantidad = parseInt($(this).attr('data-cantidad')) || 0;
            const precioSugerido = parseFloat($(this).attr('data-sugerido')) || 0;

            prepararMigracionDetallada(idCompra, nombreArticulo, cantidad, precioSugerido);
        });

        $(document).on('input change', '.clase-origen', function() {
            const campoDestino = $(this).attr('data-campo');
            const nuevoValor = $(this).val();
            $(`.clase-espejo[data-campo="${campoDestino}"]`).val(nuevoValor);
        });
    });

    function cerrarModal() {
        document.getElementById('modalMigrarDetallado').classList.add('hidden');
    }

    function actualizarStatus(selectElement) {
        const id = $(selectElement).attr('data-id');
        const nuevoStatus = $(selectElement).val();
        console.log("Actualizando item " + id + " a estado: " + nuevoStatus);
        // Aquí puedes inyectar tu llamada AJAX tradicional si la requieres.
    }

    function prepararMigracionDetallada(idCompra, nombreArticulo, cantidad, precioSugerido) {
        const contenedor = document.getElementById('contenedorEquiposDinamicos');
        if (!contenedor) return;

        contenedor.innerHTML = '';
        document.getElementById('modalMigrarDetallado').classList.remove('hidden');

        const palabras = nombreArticulo.trim().split(' ');
        const marcaSugerida = palabras[0] || '';
        const modeloSugerido = palabras.slice(1).join(' ') || 'Genérico';

        let correlativoTemporal = ultimoNumeroBaseGlobal;

        for (let i = 0; i < cantidad; i++) {
            correlativoTemporal++;
            const idLocalAsignado = "DC-2026-" + correlativoTemporal;
            const isMaestra = (i === 0);
            const inputClass = isMaestra ? 'clase-origen' : 'clase-espejo';

            // Renderizado corregido y estilizado del Grid de Inputs del Formulario
            const cardHtml = `
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden border-l-4 ${isMaestra ? 'border-l-amber-500' : 'border-l-blue-500'}">
                <input type="hidden" name="equipo[${i}][id_original_compra]" value="${idCompra}">

                <div class="bg-slate-900 text-slate-100 px-4 py-2 text-xs flex justify-between items-center">
                    <span class="font-mono text-slate-300 truncate max-w-md">Ref: ${nombreArticulo}</span>
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold ${isMaestra ? 'bg-amber-500 text-slate-950' : 'bg-slate-700 text-slate-300'}">
                        ${isMaestra ? '🔥 UNIDAD #1 (MAESTRA)' : 'Unidad #' + (i + 1)}
                    </span>
                </div>

                <div class="p-4 grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">ID Local</label>
                        <input type="text" name="equipo[${i}][id_local]" value="${idLocalAsignado}" readonly class="w-full bg-slate-100 border border-slate-300 rounded px-2 py-1.5 font-mono font-bold text-slate-700 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">No. Serie</label>
                        <input type="text" name="equipo[${i}][serie]" placeholder="Número de Serie" class="w-full bg-white border border-slate-300 rounded px-2 py-1.5 font-mono focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Marca Equipo</label>
                        <input type="text" name="equipo[${i}][equipo_marca]" value="${marcaSugerida}" data-campo="equipo_marca" class="${inputClass} w-full bg-white border border-slate-300 rounded px-2 py-1.5 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Modelo Equipo</label>
                        <input type="text" name="equipo[${i}][equipo_modelo]" value="${modeloSugerido}" data-campo="equipo_modelo" class="${inputClass} w-full bg-white border border-slate-300 rounded px-2 py-1.5 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Marca Proc.</label>
                        <input type="text" name="equipo[${i}][proc_marca]" placeholder="Ej: Intel" data-campo="proc_marca" class="${inputClass} w-full bg-white border border-slate-300 rounded px-2 py-1.5 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Familia Proc.</label>
                        <input type="text" name="equipo[${i}][proc_familia]" placeholder="Ej: Core i5" data-campo="proc_familia" class="${inputClass} w-full bg-white border border-slate-300 rounded px-2 py-1.5 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Gen. Proc.</label>
                        <input type="text" name="equipo[${i}][proc_generacion]" placeholder="Ej: 11va" data-campo="proc_generacion" class="${inputClass} w-full bg-white border border-slate-300 rounded px-2 py-1.5 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Modelo Proc.</label>
                        <input type="text" name="equipo[${i}][proc_modelo]" placeholder="Ej: 1135G7" data-campo="proc_modelo" class="${inputClass} w-full bg-white border border-slate-300 rounded px-2 py-1.5 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Memoria (RAM)</label>
                        <input type="text" name="equipo[${i}][memoria]" placeholder="Ej: 16GB DDR4" data-campo="memoria" class="${inputClass} w-full bg-white border border-slate-300 rounded px-2 py-1.5 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Disco Almac.</label>
                        <input type="text" name="equipo[${i}][disco]" placeholder="Ej: 512GB NVMe" data-campo="disco" class="${inputClass} w-full bg-white border border-slate-300 rounded px-2 py-1.5 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Gráficos</label>
                        <input type="text" name="equipo[${i}][graficos]" placeholder="Ej: Intel Iris Xe" data-campo="graficos" class="${inputClass} w-full bg-white border border-slate-300 rounded px-2 py-1.5 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Gráfica Expandible?</label>
                        <select name="equipo[${i}][g_expandible]" data-campo="g_expandible" class="${inputClass} w-full bg-white border border-slate-300 rounded px-2 py-1.5 focus:border-blue-500 focus:outline-none">
                            <option value="no">No</option>
                            <option value="si">Sí</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Pantalla</label>
                        <input type="text" name="equipo[${i}][pantalla]" placeholder="Ej: 15.6" data-campo="pantalla" class="${inputClass} w-full bg-white border border-slate-300 rounded px-2 py-1.5 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Resolución</label>
                        <input type="text" name="equipo[${i}][p_resolucion]" placeholder="Ej: 1920x1080" data-campo="p_resolucion" class="${inputClass} w-full bg-white border border-slate-300 rounded px-2 py-1.5 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Touchscreen?</label>
                        <select name="equipo[${i}][touch]" data-campo="touch" class="${inputClass} w-full bg-white border border-slate-300 rounded px-2 py-1.5 focus:border-blue-500 focus:outline-none">
                            <option value="no">No</option>
                            <option value="si">Sí</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-600 mb-1">Precio Final (DOP)</label>
                        <input type="number" step="0.01" name="equipo[${i}][precio]" value="${precioSugerido.toFixed(2)}" data-campo="precio" class="${inputClass} w-full bg-slate-50 font-bold text-emerald-600 border border-slate-300 rounded px-2 py-1.5 focus:border-emerald-500 focus:outline-none">
                    </div>
                    <div class="col-span-2">
                        <label class="block font-semibold text-slate-600 mb-1">Imagen URL</label>
                        <input type="text" name="equipo[${i}][imagen_url]" data-campo="imagen_url" class="${inputClass} w-full bg-white border border-slate-300 rounded px-2 py-1.5 focus:border-blue-500 focus:outline-none">
                    </div>
                    <div class="col-span-2">
                        <label class="block font-semibold text-slate-600 mb-1 font-mono">Comentarios / Notas</label>
                        <input type="text" name="equipo[${i}][comenta]" data-campo="comenta" class="${inputClass} w-full bg-white border border-slate-300 rounded px-2 py-1.5 focus:border-blue-500 focus:outline-none">
                    </div>
                </div>
            </div>`;
            contenedor.insertAdjacentHTML('beforeend', cardHtml);
        }
    }
    </script>
</body>

</html>