<?php
session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config/conexion.php'; 

// 1. Importar las clases de PHPMailer al espacio de nombres global
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 2. Cargador automático de Composer (Ya no necesitas los requires manuales)
require '../vendor/autoload.php'; 

$mensaje_success = "";
$mensaje_error = "";


if (!function_exists('registrarLogCrear')) {
    function registrarLogCrear($pdo, $id_local, $datos) {
        // Función temporal para evitar el error hasta encontrar la original
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

        // Variable para armar el cuerpo del correo con la lista de equipos
        $htmlEquiposMail = "";

        foreach ($equipos as $eq) {
            $id_local_evaluar = trim($eq['id_local'] ?? '');

            $stmtCheck->execute([$id_local_evaluar]);
            $existe = $stmtCheck->fetchColumn();

            if ($existe > 0) {
                throw new Exception("El ID Local <strong>{$id_local_evaluar}</strong> ya se encuentra registrado.");
            }

            $serie = trim($eq['serie'] ?? '') ?: 'PENDIENTE';

            $stmtInsert->execute([
                $id_local_evaluar, $serie,
                trim($eq['proc_marca'] ?? ''), trim($eq['proc_familia'] ?? ''),
                trim($eq['proc_generacion'] ?? ''), trim($eq['proc_modelo'] ?? ''),
                trim($eq['numero_rastreo'] ?? ''),
                trim($eq['graficos'] ?? ''), trim($eq['g_expandible'] ?? ''),
                trim($eq['memoria'] ?? ''), trim($eq['disco'] ?? ''),
                trim($eq['pantalla'] ?? ''), trim($eq['p_resolucion'] ?? ''),
                trim($eq['touch'] ?? ''), (float)($eq['precio'] ?? 0),
                'En camino', trim($eq['comenta'] ?? ''),
                trim($eq['equipo_marca'] ?? ''), trim($eq['equipo_modelo'] ?? ''),
                trim($eq['imagen_url'] ?? ''), trim($eq['imagenes_adicionales'] ?? ''),
                $fecha_actual
            ]);

            if (isset($eq['id_original_compra'])) {
                $stmtUpdateOrig->execute([$eq['id_original_compra']]);
            }

            // Auditoría (LOGS)
            $datosNuevoEquipo = [
                'equipo_marca'  => $eq['equipo_marca'] ?? '',
                'equipo_modelo' => $eq['equipo_modelo'] ?? '',
                'proc_marca'    => $eq['proc_marca'] ?? '',
                'proc_familia'  => $eq['proc_familia'] ?? '',
                'proc_modelo'   => $eq['proc_modelo'] ?? '',
                'memoria'       => $eq['memoria'] ?? '',
                'disco'         => $eq['disco'] ?? '',
                'precio'        => $eq['precio'] ?? 0
            ];
            registrarLogCrear($pdo, $id_local_evaluar, $datosNuevoEquipo);

            // Agregar fila al reporte del correo
            $htmlEquiposMail .= "
                <tr>
                    <td style='padding:8px; border:1px solid #ddd;'><strong>{$id_local_evaluar}</strong></td>
                    <td style='padding:8px; border:1px solid #ddd;'>{$eq['equipo_marca']} {$eq['equipo_modelo']}</td>
                    <td style='padding:8px; border:1px solid #ddd;'>{$serie}</td>
                    <td style='padding:8px; border:1px solid #ddd;'>{$eq['memoria']} / {$eq['disco']}</td>
                    <td style='padding:8px; border:1px solid #ddd; color:#16a34a;'>RD$ " . number_format((float)$eq['precio'], 2) . "</td>
                </tr>";
        }

        // =========================================================
        // ENVÍO DE NOTIFICACIÓN POR CORREO (GMAIL SMTP)
        // =========================================================
        $mail = new PHPMailer(true);

        // Configuración del Servidor SMTP de Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'pfernandez@dacansdr.com';          
        $mail->Password   = 'qbvi hhmq hrcb pmew'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Destinatarios
        $mail->setFrom('pfernandez@dacansdr.com', 'daniel@dacansdr.com', 'Sistema de Inventario');
        
        // 📥 REEMPLAZA AQUÍ EL CORREO QUE DEBE RECIBIR LAS ALERTAS
        $mail->addAddress('pfernandez@dacansdr.com'); 

        // Contenido del Correo
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

        $mail->send(); // Se envía el correo

        // Si todo sale bien (Base de datos + Logs + Email), guardamos los cambios de verdad
        $pdo->commit();
        $mensaje_success = "¡Éxito! Se han registrado " . count($equipos) . " equipos, se crearon los logs y se envió la notificación por correo.";
        
        header("Location: index.php?success=" . urlencode($mensaje_success));
        exit;

    } catch (Exception $e) {
        // Si falla la base de datos O falla el envío del correo, se cancela TODO (no se guardará nada roto)
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
    // Consulta compatible tanto con SQL Server (LEN / TOP 1) como con MySQL (LENGTH / LIMIT 1)
    // Intentamos primero la sintaxis estándar de SQL Server por si usas Transact-SQL
    $query_ultimo = $pdo->query("SELECT TOP 1 id_local FROM productos_informatica ORDER BY LEN(id_local) DESC, id_local DESC");
    
    if (!$query_ultimo) {
        // Fallback inmediato por si la conexión es una base de datos MySQL tradicional
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
    // Fallback secundario en caso de error en funciones de longitud de cadenas de texto
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

/* =========================================================
   3. LISTAR ARTICULOS PENDIENTES DESDE LA BASE DE DATOS
========================================================= */
try {
    $articulos = $pdo->query("SELECT * FROM compras_articulos WHERE migrada = 0 OR migrada IS NULL ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al consultar los datos: " . $e->getMessage());
}

/* =========================================================
   4. OBTENER HISTORIAL DE ARTÍCULOS AGREGADOS
========================================================= */
try {
    // Si usas SQL Server y la tabla es muy grande, limita con TOP 100 si lo requieres
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
        background: radial-gradient(circle at top left, rgba(37, 99, 235, .08), transparent 30%), radial-gradient(circle at bottom right, rgba(14, 165, 233, .08), transparent 30%), linear-gradient(to bottom, #f8fafc, #eef2ff);
        font-family: Arial, sans-serif;
    }

    .card {
        background: rgba(255, 255, 255, .88);
        backdrop-filter: blur(14px);
        border-radius: 36px;
        border: 1px solid rgba(226, 232, 240, .9);
        box-shadow: 0 20px 45px rgba(15, 23, 42, .05);
        overflow: hidden;
    }

    table.dataTable {
        width: 100% !important;
        border-collapse: separate !important;
        border-spacing: 0;
        font-size: 13px;
    }

    table.dataTable thead th {
        background: linear-gradient(135deg, #0f172a, #1e293b);
        color: white;
        border: none !important;
        padding: 18px 14px !important;
        font-size: 12px;
        text-transform: uppercase;
    }

    table.dataTable tbody td {
        padding: 16px 14px !important;
        vertical-align: middle;
        border-bottom: 1px solid #edf2f7;
    }

    .status {
        padding: 7px 14px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
    }

    .action-btn {
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        transition: .2s ease;
    }

    .action-btn:hover {
        transform: translateY(-2px);
    }
    </style>
</head>

<body class="p-4 md:p-8">

    <div class="w-full px-2 space-y-12">

        <?php if (!empty($mensaje_success)): ?>
        <div
            class="p-4 bg-emerald-100 border border-emerald-300 text-emerald-800 rounded-2xl font-bold flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-xl"></i> <?= htmlspecialchars($mensaje_success) ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($mensaje_error)): ?>
        <div class="p-4 bg-red-100 border border-red-300 text-red-800 rounded-2xl font-bold flex items-center gap-2">
            <i class="fa-solid fa-circle-xmark text-xl"></i> <?= htmlspecialchars($mensaje_error) ?>
        </div>
        <?php endif; ?>

        <div class="flex flex-col lg:flex-row justify-between items-center gap-5">
            <div class="flex items-center gap-5">
                <div class="bg-white p-4 rounded-[28px] shadow-xl border border-slate-200">
                    <img src="../img/logo.webp" class="h-14 object-contain">
                </div>
                <div>
                    <h1 class="text-4xl md:text-5xl font-black tracking-tight text-slate-900">Compras de Artículos</h1>
                    <p class="text-slate-500 mt-2 text-sm md:text-base">Gestión avanzada de compras, costos y ganancias
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="crear.php"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-4 rounded-2xl font-black shadow-lg transition"><i
                        class="fa-solid fa-plus mr-2"></i> Nueva Compra</a>
                <a href="importar_excel.php"
                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-4 rounded-2xl font-black shadow-lg transition"><i
                        class="fa-solid fa-file-excel mr-2"></i> Cargar Excel</a>
                <a href="../mantenimiento"
                    class="bg-slate-900 hover:bg-black text-white px-6 py-4 rounded-2xl font-black shadow-lg transition"><i
                        class="fa-solid fa-arrow-left mr-2"></i> Volver</a>
            </div>
        </div>

        <div class="card p-6">
            <div class="mb-4 flex items-center gap-2">
                <div class="w-2 h-6 bg-blue-600 rounded-full"></div>
                <h2 class="text-xl font-black text-slate-800">Lotes de Compras Registrados</h2>
            </div>
            <div class="w-full">
                <table id="tablaCompras" class="display w-full">
                    <thead>
                        <tr>
                            <th>Acciones</th>
                            <th>Item ID</th>
                            <th>ID Artículo</th>
                            <th>Dirección Usada</th>
                            <th>Numero Rastreo</th>
                            <th>Descripcion</th>
                            <th>Cantidad</th>
                            <th>USD</th>
                            <th>DOP (Lote)</th>
                            <th>Costo Unitario</th>
                            <th>Precio Sugerido</th>
                            <th>Status</th>

                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($articulos) && is_array($articulos)): ?>
                        <?php foreach ($articulos as $articulo): ?>
                        <?php
                                $id_art        = (int)($articulo['id'] ?? 0);
                                $item_id       = trim($articulo['item_id'] ?? '');
                                $direccion     = htmlspecialchars($articulo['direccion_usada'] ?? 'N/A');
                                $numero_rastreo_us = htmlspecialchars($articulo['numero_rastreo_us'] ?? 'N/A');
                                $nombre_art    = $articulo['nombre_articulo'] ?? 'Artículo sin nombre';
                                $cantidad      = (int)($articulo['cantidad_articulos'] ?? 0);
                                $costoDOP      = (float)($articulo['costo_dop'] ?? 0);
                                $impuestos     = (float)($articulo['costo_impuestos'] ?? 0);
                                $envio         = (float)($articulo['costo_envio'] ?? 0);
                                $costoUSD      = (float)($articulo['costo_usd'] ?? 0);
                                
                                $porcentaje    = (float)($articulo['porcentaje_incremento'] ?? 80);
                                $totalLote     = $costoDOP + $impuestos + $envio;
                                $costoUnitario = $cantidad > 0 ? ($totalLote / $cantidad) : 0;
                                $costoSugerido = $costoUnitario + ($costoUnitario * $porcentaje / 100);
                                
                                $estado        = htmlspecialchars($articulo['status_compra'] ?? 'N/A');
                            ?>
                        <tr>
                            <td>
                                <div class="flex items-center gap-1.5">
                                    <a href="editar.php?id=<?= $id_art ?>"
                                        class="action-btn bg-amber-500 text-white hover:bg-amber-600 shadow-md shadow-amber-500/10"
                                        title="Editar Compra"><i class="fa-solid fa-pen"></i></a>
                                    <a href="eliminar.php?id=<?= $id_art ?>"
                                        onclick="return confirm('¿Seguro que deseas eliminar esta compra?')"
                                        class="action-btn bg-rose-500 text-white hover:bg-rose-600 shadow-md shadow-rose-500/10"
                                        title="Eliminar"><i class="fa-solid fa-trash"></i></a>

                                    <button type="button" data-id="<?= $id_art ?>"
                                        data-nombre="<?= htmlspecialchars($nombre_art, ENT_QUOTES, 'UTF-8') ?>"
                                        data-cantidad="<?= $cantidad ?>" data-sugerido="<?= $costoSugerido ?>"
                                        class="btn-abrir-modal action-btn bg-emerald-600 text-white hover:bg-emerald-700 shadow-md shadow-emerald-600/10"
                                        title="Personalizar y enviar a inventario">
                                        <i class="fa-solid fa-arrow-right-to-bracket"></i>
                                    </button>
                                </div>
                            </td>

                            <td class="font-black text-blue-700">#<?= $id_art ?></td>
                            <td class="font-mono text-slate-600 font-bold">
                                <?php if (!empty($item_id)): ?>
                                <a href="https://www.ebay.com/itm/<?= urlencode($item_id) ?>" target="_blank"
                                    class="text-blue-600 hover:text-blue-800 hover:underline flex items-center gap-1"
                                    title="Ver artículo en eBay">
                                    <?= htmlspecialchars($item_id) ?>
                                    <i class="fa-solid fa-arrow-up-right-from-square text-[10px] text-slate-400"></i>
                                </a>
                                <?php else: ?>
                                <span class="text-slate-400 font-normal">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-slate-700 font-medium"><?= $direccion ?></td>
                            <td class="font-bold text-slate-800"><?= htmlspecialchars($numero_rastreo_us) ?></td>
                            <td class="font-bold text-slate-800"><?= htmlspecialchars($nombre_art) ?></td>
                            <td class="font-bold text-center bg-slate-50 rounded-lg"><?= $cantidad ?></td>
                            <td>$<?= number_format($costoUSD, 2) ?></td>
                            <td>RD$<?= number_format($costoDOP, 2) ?></td>
                            <td class="font-black text-cyan-700">RD$<?= number_format($costoUnitario, 2) ?></td>
                            <td class="font-black text-emerald-600">RD$<?= number_format($costoSugerido, 2) ?></td>
                            <td><span class="status bg-blue-100 text-blue-700"><?= $estado ?></span></td>

                        </tr>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <tr>
                            <td colspan="11" class="text-center text-slate-400 py-6">No hay registros de compras
                                disponibles.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card p-6 bg-white/90">
            <div class="mb-4 flex items-center gap-2">
                <div class="w-2 h-6 bg-emerald-600 rounded-full"></div>
                <h2 class="text-xl font-black text-slate-800">Historial de Artículos Guardados en Inventario</h2>
            </div>
            <div class="w-full">
                <table id="tablaHistorial" class="display w-full">
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
                            <td class="font-mono font-black text-blue-600"><?= htmlspecialchars($item['id_local']) ?>
                            </td>
                            <td class="font-bold text-slate-800">
                                <?= htmlspecialchars(($item['equipo_marca'] ?? '') . ' ' . ($item['equipo_modelo'] ?? '')) ?>
                            </td>
                            <td class="font-mono text-slate-600"><?= htmlspecialchars($item['serie'] ?? 'PENDIENTE') ?>
                            </td>
                            <td><?= htmlspecialchars(($item['proc_marca'] ?? '') . ' ' . ($item['proc_modelo'] ?? '')) ?>
                            </td>
                            <td class="font-semibold"><?= htmlspecialchars($item['memoria'] ?? '') ?></td>
                            <td class="font-semibold"><?= htmlspecialchars($item['disco'] ?? '') ?></td>
                            <td class="font-black text-emerald-600">RD$<?= number_format((float)$item['precio'], 2) ?>
                            </td>
                            <td>
                                <span class="status bg-amber-100 text-amber-800">
                                    <?= htmlspecialchars($item['estado'] ?? 'En camino') ?>
                                </span>
                            </td>
                            <td class="text-slate-400 select-none"><?= htmlspecialchars($item['created_at'] ?? '') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="modalMigrarDetallado"
        class="fixed inset-0 z-50 hidden bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div
            class="bg-white rounded-[32px] shadow-2xl border border-slate-100 max-w-6xl w-full max-h-[92vh] flex flex-col overflow-hidden">

            <div class="p-5 bg-slate-900 text-white flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center text-lg">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-black tracking-tight">Personalización con Espejo de Datos</h3>
                        <p class="text-xs text-slate-400">💡 Modifica la <strong>Unidad #1 (Maestra)</strong> para
                            replicar el contenido en cascada.</p>
                    </div>
                </div>
                <button type="button" onclick="cerrarModal()"
                    class="text-slate-400 hover:text-white transition text-xl"><i
                        class="fa-solid fa-xmark"></i></button>
            </div>

            <form method="POST" class="flex flex-col flex-1 overflow-hidden">
                <div id="contenedorEquiposDinamicos" class="p-5 space-y-6 overflow-y-auto flex-1 bg-slate-100">
                </div>

                <div class="p-4 bg-white border-t border-slate-200 flex items-center justify-between shadow-lg">
                    <p class="text-xs font-bold text-blue-600 flex items-center gap-1">
                        <i class="fa-solid fa-truck-ramp-box"></i> Estatus por defecto: "En camino"
                    </p>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="cerrarModal()"
                            class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-5 py-2.5 rounded-xl text-xs font-bold transition">Cancelar</button>
                        <button type="submit" name="migrar_producto_detallado"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2.5 rounded-xl text-xs font-black shadow-md transition">
                            <i class="fa-solid fa-cloud-arrow-up mr-1"></i> Guardar en Inventario
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

        // Delegación de eventos DataTables corregido para capturar fila actual
        $('#tablaCompras tbody').on('click', '.btn-abrir-modal', function() {
            const idCompra = $(this).attr('data-id');
            const nombreArticulo = $(this).attr('data-nombre');
            const cantidad = parseInt($(this).attr('data-cantidad')) || 0;
            const precioSugerido = parseFloat($(this).attr('data-sugerido')) || 0;

            prepararMigracionDetallada(idCompra, nombreArticulo, cantidad, precioSugerido);
        });

        // EVENTO ESPEJO: Copia los valores modificados de la Unidad #1 al resto de elementos
        $(document).on('input change', '.clase-origen', function() {
            const campoDestino = $(this).attr('data-campo');
            const nuevoValor = $(this).val();

            $(`.clase-espejo[data-campo="${campoDestino}"]`).val(nuevoValor);
        });
    });

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
            const inputClass = (i === 0) ? 'clase-origen' : 'clase-espejo';

            const cardHtml = `
            <div class="bg-white rounded-2xl border border-slate-200 shadow-md overflow-hidden border-l-4 ${i === 0 ? 'border-l-amber-500' : 'border-l-blue-600'} mb-4">
                
                <input type="hidden" name="equipo[${i}][id_original_compra]" value="${idCompra}">

                <div class="bg-slate-950 text-slate-200 px-4 py-3 border-b border-slate-800 text-xs flex flex-col md:flex-row justify-between items-start md:items-center gap-2">
                    <div>
                        <span class="bg-blue-600 text-white font-extrabold px-2 py-0.5 rounded text-[10px] mr-2">REFERENCIA</span>
                        <span class="font-mono text-white select-all font-bold tracking-wide text-sm bg-slate-900 px-2 py-1 rounded border border-slate-800">${nombreArticulo}</span>
                    </div>
                    <span class="bg-slate-800 text-slate-300 font-black px-3 py-1 rounded-full text-[11px] whitespace-nowrap">
                        ${i === 0 ? '🔥 UNIDAD #1 (MAESTRA)' : 'Unidad #' + (i + 1)}
                    </span>
                </div>

                <div class="p-4 space-y-4 text-xs">
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200/60">
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">ID Local</label>
                                <input type="text" name="equipo[${i}][id_local]" value="${idLocalAsignado}" class="w-full bg-slate-200 border border-slate-300 p-2 rounded-xl font-black text-blue-700 focus:outline-none" readonly />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Número de Serie</label>
                                <input type="text" name="equipo[${i}][serie]" placeholder="Escribe o escanea" class="w-full bg-white border border-slate-300 focus:border-blue-500 p-2 rounded-xl font-bold text-slate-800" >
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Equipo Marca</label>
                                <input type="text" data-campo="equipo_marca" name="equipo[${i}][equipo_marca]" value="${marcaSugerida}" class="${inputClass} w-full bg-white border border-slate-300 p-2 rounded-xl text-slate-800 font-semibold" required>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Equipo Modelo</label>
                                <input type="text" data-campo="equipo_modelo" name="equipo[${i}][equipo_modelo]" value="${modeloSugerido}" class="${inputClass} w-full bg-white border border-slate-300 p-2 rounded-xl text-slate-800 font-semibold" required>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Precio Individual Venta</label>
                                <input type="number" step="0.01" data-campo="precio" name="equipo[${i}][precio]" value="${precioSugerido.toFixed(2)}" class="${inputClass} w-full bg-white border border-slate-300 p-2 rounded-xl text-emerald-600 font-black" required>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200/60">
                        <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Proc Marca</label>
                                <input type="text" data-campo="proc_marca" name="equipo[${i}][proc_marca]" placeholder="Ej: Intel / AMD" class="${inputClass} w-full bg-white border border-slate-300 p-2 rounded-xl text-slate-800">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Proc Familia</label>
                                <input type="text" data-campo="proc_familia" name="equipo[${i}][proc_familia]" placeholder="Ej: Core i7" class="${inputClass} w-full bg-white border border-slate-300 p-2 rounded-xl text-slate-800">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Proc Gen</label>
                                <input type="text" data-campo="proc_generacion" name="equipo[${i}][proc_generacion]" placeholder="Ej: 11va" class="${inputClass} w-full bg-white border border-slate-300 p-2 rounded-xl text-slate-800">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Proc Modelo</label>
                                <input type="text" data-campo="proc_modelo" name="equipo[${i}][proc_modelo]" placeholder="Ej: 1165G7" class="${inputClass} w-full bg-white border border-slate-300 p-2 rounded-xl text-slate-800">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Memoria (RAM)</label>
                                <input type="text" data-campo="memoria" name="equipo[${i}][memoria]" placeholder="Ej: 16GB" class="${inputClass} w-full bg-white border border-slate-300 p-2 rounded-xl text-slate-800 font-bold">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Disco</label>
                                <input type="text" data-campo="disco" name="equipo[${i}][disco]" placeholder="Ej: 512GB SSD" class="${inputClass} w-full bg-white border border-slate-300 p-2 rounded-xl text-slate-800 font-bold">
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-200/60">
                        <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Gráficos</label>
                                <select data-campo="graficos" name="equipo[${i}][graficos]" class="${inputClass} w-full bg-white border border-slate-300 p-2 rounded-xl text-slate-800">
                                    <option value="">Seleccione</option>
                                    <option value="0">Integrada</option>
                                    <option value="1">APU Ajustable</option>
                                    <option value="2">Dedicada</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">G Expandible</label>
                                <input type="text" data-campo="g_expandible" name="equipo[${i}][g_expandible]" placeholder="Ej: No" class="${inputClass} w-full bg-white border border-slate-300 p-2 rounded-xl text-slate-800">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Pantalla</label>
                                <input type="text" data-campo="pantalla" name="equipo[${i}][pantalla]" placeholder="Ej: 15.6&quot;" class="${inputClass} w-full bg-white border border-slate-300 p-2 rounded-xl text-slate-800">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">P Resolución</label>
                                <input type="text" data-campo="p_resolucion" name="equipo[${i}][p_resolucion]" placeholder="Ej: 1920x1080" class="${inputClass} w-full bg-white border border-slate-300 p-2 rounded-xl text-slate-800">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Touch</label>
                                <select data-campo="touch" name="equipo[${i}][touch]" class="${inputClass} w-full bg-white border border-slate-300 p-2 rounded-xl text-slate-800">
                                    <option value="">Seleccione</option>
                                    <option value="1">Sí</option>
                                    <option value="0">No</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Comentario Interno</label>
                                <input type="text" data-campo="comenta" name="equipo[${i}][comenta]" placeholder="Notas adicionales" class="${inputClass} w-full bg-white border border-slate-300 p-2 rounded-xl text-slate-800">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
            contenedor.insertAdjacentHTML('beforeend', cardHtml);
        }
    }

    function cerrarModal() {
        document.getElementById('modalMigrarDetallado').classList.add('hidden');
    }
    </script>
</body>

</html>