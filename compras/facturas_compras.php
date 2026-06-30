<?php
session_start();
if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config/conexion.php';

$mensaje = '';
$tipoMensaje = '';

// A. PROCESAR ELIMINACIÓN DE FACTURA
if (isset($_POST['eliminar_factura'])) {
    try {
        $id_eliminar = (int)$_POST['id_factura'];
        
        // Buscar la ruta del archivo físico antes de borrar el registro
        $stmtFile = $pdo->prepare("SELECT ruta_imagen FROM dbo.facturas_compras WHERE id_factura = ?");
        $stmtFile->execute([$id_eliminar]);
        $factura_data = $stmtFile->fetch(PDO::FETCH_ASSOC);

        if ($factura_data) {
            $ruta_completa_archivo = '../' . $factura_data['ruta_imagen'];
            
            // Borrar el archivo del servidor si existe físicamente
            if (file_exists($ruta_completa_archivo) && !is_dir($ruta_completa_archivo)) {
                unlink($ruta_completa_archivo);
            }

            // Eliminar el registro en la base de datos
            $stmtDel = $pdo->prepare("DELETE FROM dbo.facturas_compras WHERE id_factura = ?");
            $stmtDel->execute([$id_eliminar]);

            $mensaje = "Factura y archivo físico eliminados correctamente.";
            $tipoMensaje = "success";
        }
    } catch (Exception $e) {
        $mensaje = "Error al intentar eliminar: " . $e->getMessage();
        $tipoMensaje = "error";
    }
}

// B. PROCESAR EDICIÓN DE FACTURA
if (isset($_POST['editar_factura'])) {
    try {
        $id_editar    = (int)$_POST['id_factura'];
        $codigo_ref   = trim($_POST['codigo_referencia'] ?? '');
        $num_factura  = trim($_POST['numero_factura'] ?? '');
        $proveedor    = trim($_POST['proveedor'] ?? '');
        $monto        = (float)($_POST['monto_total'] ?? 0);
        $comentario   = trim($_POST['comentario'] ?? '');

        // Obtener datos actuales por si no se cambia de archivo
        $stmtActual = $pdo->prepare("SELECT ruta_imagen, nombre_original FROM dbo.facturas_compras WHERE id_factura = ?");
        $stmtActual->execute([$id_editar]);
        $factura_actual = $stmtActual->fetch(PDO::FETCH_ASSOC);

        if (!$factura_actual) {
            throw new Exception("La factura a editar no existe.");
        }

        $ruta_db = $factura_actual['ruta_imagen'];
        $name_original = $factura_actual['nombre_original'];

        // Verificar si se subió un nuevo archivo para reemplazar el anterior
        if (!empty($_FILES['archivo_factura']['name'])) {
            $directorio_subida = '../uploads/facturas/';
            $name     = $_FILES['archivo_factura']['name'];
            $tmp_name = $_FILES['archivo_factura']['tmp_name'];
            $error    = $_FILES['archivo_factura']['error'];

            if ($error === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'pdf'];
                
                if (!in_array($ext, $extensiones_permitidas)) {
                    throw new Exception("Formato de archivo no permitido. Solo JPG, PNG y PDF.");
                }

                // Eliminar el archivo físico antiguo del servidor
                $ruta_antigua_fisica = '../' . $factura_actual['ruta_imagen'];
                if (file_exists($ruta_antigua_fisica) && !is_dir($ruta_antigua_fisica)) {
                    unlink($ruta_antigua_fisica);
                }

                // Guardar el nuevo archivo
                $nuevo_nombre = 'fac_' . uniqid() . '.' . $ext;
                $ruta_destino = $directorio_subida . $nuevo_nombre;

                if (move_uploaded_file($tmp_name, $ruta_destino)) {
                    $ruta_db = 'uploads/facturas/' . $nuevo_nombre;
                    $name_original = $name;
                } else {
                    throw new Exception("No se pudo mover el nuevo archivo al servidor.");
                }
            } else {
                throw new Exception("Error al procesar el archivo subido.");
            }
        }

        // Actualizar datos en SQL Server
        $stmtUpd = $pdo->prepare("
            UPDATE dbo.facturas_compras 
            SET codigo_referencia = ?, 
                numero_factura = ?, 
                proveedor = ?, 
                ruta_imagen = ?, 
                nombre_original = ?, 
                monto_total = ?, 
                comentario = ?
            WHERE id_factura = ?
        ");
        $stmtUpd->execute([$codigo_ref, $num_factura, $proveedor, $ruta_db, $name_original, $monto, $comentario, $id_editar]);

        $mensaje = "¡Factura actualizada con éxito!";
        $tipoMensaje = "success";

    } catch (Exception $e) {
        $mensaje = "Error al editar: " . $e->getMessage();
        $tipoMensaje = "error";
    }
}

// 1. PROCESAR SUBIDA DE FACTURA
if (isset($_POST['subir_factura'])) {
    try {
        $codigo_ref   = trim($_POST['codigo_referencia'] ?? '');
        $num_factura  = trim($_POST['numero_factura'] ?? '');
        $proveedor    = trim($_POST['proveedor'] ?? '');
        $monto        = (float)($_POST['monto_total'] ?? 0);
        $comentario   = trim($_POST['comentario'] ?? '');
        $creado_por   = $_SESSION['usuario'] ?? 'Admin';

        $directorio_subida = '../uploads/facturas/';
        if (!is_dir($directorio_subida)) {
            mkdir($directorio_subida, 0755, true);
        }

        if (!empty($_FILES['archivo_factura']['name'])) {
            $name     = $_FILES['archivo_factura']['name'];
            $tmp_name = $_FILES['archivo_factura']['tmp_name'];
            $error    = $_FILES['archivo_factura']['error'];

            if ($error === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'pdf'];
                
                if (!in_array($ext, $extensiones_permitidas)) {
                    throw new Exception("Formato de archivo no permitido. Solo JPG, PNG y PDF.");
                }

                $nuevo_nombre = 'fac_' . uniqid() . '.' . $ext;
                $ruta_destino = $directorio_subida . $nuevo_nombre;

                if (move_uploaded_file($tmp_name, $ruta_destino)) {
                    $ruta_db = 'uploads/facturas/' . $nuevo_nombre;

                    $stmt = $pdo->prepare("
                        INSERT INTO dbo.facturas_compras (
                            codigo_referencia, numero_factura, proveedor, ruta_imagen, 
                            nombre_original, monto_total, comentario, creado_por, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, GETDATE())
                    ");
                    $stmt->execute([$codigo_ref, $num_factura, $proveedor, $ruta_db, $name, $monto, $comentario, $creado_por]);

                    $mensaje = "¡Factura registrada y guardada con éxito!";
                    $tipoMensaje = "success";
                } else {
                    throw new Exception("No se pudo mover el archivo al servidor.");
                }
            } else {
                throw new Exception("Error en el archivo subido.");
            }
        } else {
            throw new Exception("Debe seleccionar un archivo de factura válido.");
        }
    } catch (Exception $e) {
        $mensaje = $e->getMessage();
        $tipoMensaje = "error";
    }
}

// 2. FILTRADO POR FECHAS PARA LA TABLA
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin    = $_GET['fecha_fin'] ?? date('Y-m-d');

$stmtList = $pdo->prepare("
    SELECT * FROM dbo.facturas_compras 
    WHERE CAST(created_at AS DATE) BETWEEN ? AND ?
    ORDER BY created_at DESC
");
$stmtList->execute([$fecha_inicio, $fecha_fin]);
$facturas = $stmtList->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archivo de Facturas de Compras</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="bg-slate-100 p-6">

    <div class="max-w-7xl mx-auto space-y-8">

        <div
            class="flex flex-col md:flex-row justify-between items-start md:items-center bg-white p-6 rounded-3xl shadow-sm border border-slate-200 gap-4">
            <div>
                <h1 class="text-3xl font-black text-slate-950 flex items-center gap-2">
                    <i class="fa-solid fa-file-invoice-dollar text-blue-700"></i> Facturas de Compras
                </h1>
                <p class="text-slate-500 mt-1">Gestión independiente de comprobantes, gastos y archivos de almacén.</p>
            </div>
            <a href="../mantenimiento"
                class="bg-slate-950 hover:bg-slate-800 text-white px-5 py-3 rounded-2xl font-black text-sm flex items-center gap-2 transition">
                <i class="fa-solid fa-arrow-left"></i> Volver al Inicio
            </a>
        </div>

        <?php if (!empty($mensaje)): ?>
        <div
            class="p-4 rounded-2xl text-sm font-bold border <?= $tipoMensaje === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' ?>">
            <i
                class="fa-solid <?= $tipoMensaje === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?> mr-1"></i>
            <?= htmlspecialchars($mensaje) ?>
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="bg-white p-6 rounded-3xl shadow-lg border border-slate-200 h-fit">
                <h2 class="text-xl font-black text-slate-900 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-cloud-arrow-up text-blue-600"></i> Registrar Documento
                </h2>

                <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label class="block text-xs font-black uppercase text-slate-400 mb-1">Código Ref / Equipo
                            (Opcional)</label>
                        <input type="text" name="codigo_referencia" placeholder="Ej: DC-2026-1005"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 font-semibold text-sm focus:outline-none focus:border-blue-500 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-black uppercase text-slate-400 mb-1">Número de Factura /
                            NCF</label>
                        <input type="text" name="numero_factura" placeholder="Ej: B1500000123"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 font-semibold text-sm focus:outline-none focus:border-blue-500 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-black uppercase text-slate-400 mb-1">Proveedor /
                            Suplidor</label>
                        <input type="text" name="proveedor" placeholder="Ej: Dell Wholesale Miami"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 font-semibold text-sm focus:outline-none focus:border-blue-500 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-black uppercase text-slate-400 mb-1">Monto Total Venta
                            (RD$)</label>
                        <input type="number" step="0.01" name="monto_total" placeholder="0.00"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 font-black text-sm focus:outline-none focus:border-blue-500 transition">
                    </div>
                    <div>
                        <label class="block text-xs font-black uppercase text-slate-400 mb-1">Comentario
                            descriptivo</label>
                        <textarea name="comentario" rows="2" placeholder="Ej: Lote de 5 Laptops cargadores incluidos..."
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 font-medium text-sm focus:outline-none focus:border-blue-500 transition"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-black uppercase text-slate-400 mb-1">Archivo de Factura (PDF,
                            JPG, PNG)</label>
                        <input type="file" name="archivo_factura" required
                            class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                    </div>

                    <button type="submit" name="subir_factura"
                        class="w-full bg-blue-700 hover:bg-blue-800 text-white font-black py-3 rounded-xl shadow-md transition text-sm">
                        <i class="fa-solid fa-check mr-1"></i> Guardar Factura
                    </button>
                </form>
            </div>

            <div class="lg:grid-cols-1 lg:col-span-2 space-y-4">

                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200">
                    <form method="GET" action="" class="flex flex-col md:flex-row items-end gap-4">
                        <div class="flex-1 w-full">
                            <label class="block text-xs font-black uppercase text-slate-400 mb-1">Desde Fecha</label>
                            <input type="date" name="fecha_inicio" value="<?= htmlspecialchars($fecha_inicio) ?>"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 font-bold text-sm text-slate-700 focus:outline-none">
                        </div>
                        <div class="flex-1 w-full">
                            <label class="block text-xs font-black uppercase text-slate-400 mb-1">Hasta Fecha</label>
                            <input type="date" name="fecha_fin" value="<?= htmlspecialchars($fecha_fin) ?>"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2 px-3 font-bold text-sm text-slate-700 focus:outline-none">
                        </div>
                        <div class="flex gap-2 w-full md:w-auto">
                            <button type="submit"
                                class="flex-1 md:flex-none bg-slate-200 hover:bg-slate-300 text-slate-800 font-black px-4 py-2.5 rounded-xl text-sm transition">
                                <i class="fa-solid fa-filter"></i> Filtrar
                            </button>
                            <a href="exportar_facturas_pdf.php?fecha_inicio=<?= urlencode($fecha_inicio) ?>&fecha_fin=<?= urlencode($fecha_fin) ?>"
                                target="_blank"
                                class="flex-1 md:flex-none bg-red-600 hover:bg-red-700 text-white font-black px-4 py-2.5 rounded-xl text-sm transition text-center flex items-center justify-center gap-1">
                                <i class="fa-solid fa-file-pdf"></i> Descargar PDF
                            </a>
                        </div>
                    </form>
                </div>

                <div class="bg-white rounded-3xl shadow-lg border border-slate-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead
                                class="bg-slate-50 text-slate-500 font-bold text-xs uppercase border-b border-slate-100">
                                <tr>
                                    <th class="p-4">Fecha</th>
                                    <th class="p-4">Referencia</th>
                                    <th class="p-4">Factura/NCF</th>
                                    <th class="p-4">Proveedor</th>
                                    <th class="p-4">Monto</th>
                                    <th class="p-4 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-sm text-slate-700">
                                <?php if (empty($facturas)): ?>
                                <tr>
                                    <td colspan="6" class="p-8 text-center text-slate-400 font-medium">No se encontraron
                                        facturas en el rango de fechas seleccionado.</td>
                                </tr>
                                <?php endif; ?>
                                <?php foreach($facturas as $f): ?>
                                <tr class="hover:bg-slate-50/50 transition">
                                    <td class="p-4 font-medium text-slate-500">
                                        <?= date('d/m/Y', strtotime($f['created_at'])) ?>
                                    </td>
                                    <td class="p-4 font-bold text-blue-700">
                                        <?= htmlspecialchars($f['codigo_referencia'] ?: 'Gasto Gral.') ?>
                                    </td>
                                    <td class="p-4 font-semibold text-slate-900">
                                        <?= htmlspecialchars($f['numero_factura'] ?: 'S/N') ?>
                                    </td>
                                    <td class="p-4 text-slate-600">
                                        <?= htmlspecialchars($f['proveedor'] ?: 'N/A') ?>
                                    </td>
                                    <td class="p-4 font-black text-slate-900">
                                        RD$ <?= number_format((float)$f['monto_total'], 2) ?>
                                    </td>
                                    <td class="p-4 text-center flex items-center justify-center gap-2">
                                        <a href="../<?= htmlspecialchars($f['ruta_imagen']) ?>" target="_blank"
                                            class="inline-flex items-center gap-1 text-xs font-black bg-slate-100 hover:bg-blue-50 hover:text-blue-700 text-slate-700 px-2.5 py-1.5 rounded-lg transition">
                                            <i class="fa-solid fa-eye"></i> Ver
                                        </a>

                                        <button type="button"
                                            onclick='abrirModalEditar(<?= json_encode($f, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                                            class="inline-flex items-center gap-1 text-xs font-black bg-amber-50 hover:bg-amber-100 text-amber-700 px-2.5 py-1.5 rounded-lg transition">
                                            <i class="fa-solid fa-pen-to-square"></i> Editar
                                        </button>

                                        <form action="" method="POST"
                                            onsubmit="return confirm('¿Seguro que deseas eliminar esta factura y borrar permanentemente su archivo físico del servidor?');"
                                            class="inline">
                                            <input type="hidden" name="id_factura" value="<?= $f['id_factura'] ?>">
                                            <button type="submit" name="eliminar_factura"
                                                class="inline-flex items-center gap-1 text-xs font-black bg-red-50 hover:bg-red-100 text-red-600 px-2.5 py-1.5 rounded-lg transition">
                                                <i class="fa-solid fa-trash-can"></i> Borrar
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <div id="modalEditar"
        class="fixed inset-0 z-50 hidden flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm">
        <div
            class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden transform transition-all">
            <div class="bg-slate-950 px-6 py-4 flex justify-between items-center text-white">
                <h3 class="text-lg font-black flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-amber-500"></i> Modificar Factura
                </h3>
                <button onclick="cerrarModalEditar()" class="text-slate-400 hover:text-white transition text-xl">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                <input type="hidden" name="id_factura" id="edit_id_factura">

                <div>
                    <label class="block text-xs font-black uppercase text-slate-400 mb-1">Código Ref / Equipo
                        (Opcional)</label>
                    <input type="text" name="codigo_referencia" id="edit_codigo_referencia"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 font-semibold text-sm focus:outline-none focus:border-blue-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-black uppercase text-slate-400 mb-1">Número de Factura /
                        NCF</label>
                    <input type="text" name="numero_factura" id="edit_numero_factura"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 font-semibold text-sm focus:outline-none focus:border-blue-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-black uppercase text-slate-400 mb-1">Proveedor / Suplidor</label>
                    <input type="text" name="proveedor" id="edit_proveedor"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 font-semibold text-sm focus:outline-none focus:border-blue-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-black uppercase text-slate-400 mb-1">Monto Total Venta
                        (RD$)</label>
                    <input type="number" step="0.01" name="monto_total" id="edit_monto_total"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 font-black text-sm focus:outline-none focus:border-blue-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-black uppercase text-slate-400 mb-1">Comentario descriptivo</label>
                    <textarea name="comentario" id="edit_comentario" rows="2"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl py-2.5 px-4 font-medium text-sm focus:outline-none focus:border-blue-500 transition"></textarea>
                </div>
                <div class="bg-amber-50 p-3 rounded-xl border border-amber-200">
                    <label class="block text-xs font-black uppercase text-amber-800 mb-1">Reemplazar Archivo
                        (Opcional)</label>
                    <input type="file" name="archivo_factura"
                        class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:bg-amber-100 file:text-amber-800 hover:file:bg-amber-200 cursor-pointer">
                    <p class="text-[10px] text-amber-700 mt-1"><i class="fa-solid fa-info-circle"></i> Deje este espacio
                        en blanco si desea conservar el archivo digital actual.</p>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="cerrarModalEditar()"
                        class="w-1/3 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold py-3 rounded-xl transition text-sm">
                        Cancelar
                    </button>
                    <button type="submit" name="editar_factura"
                        class="w-2/3 bg-amber-600 hover:bg-amber-700 text-white font-black py-3 rounded-xl shadow-md transition text-sm">
                        <i class="fa-solid fa-rotate mr-1"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function abrirModalEditar(factura) {
        // Mapeo dinámico de datos al formulario modal
        document.getElementById('edit_id_factura').value = factura.id_factura;
        document.getElementById('edit_codigo_referencia').value = factura.codigo_referencia || '';
        document.getElementById('edit_numero_factura').value = factura.numero_factura || '';
        document.getElementById('edit_proveedor').value = factura.proveedor || '';
        document.getElementById('edit_monto_total').value = factura.monto_total || 0;
        document.getElementById('edit_comentario').value = factura.comentario || '';

        // Desplegar el modal visualmente
        const modal = document.getElementById('modalEditar');
        modal.classList.remove('hidden');
    }

    function cerrarModalEditar() {
        const modal = document.getElementById('modalEditar');
        modal.classList.add('hidden');
    }

    // Cerrar modal si el usuario hace clic fuera de la caja blanca
    window.onclick = function(event) {
        const modal = document.getElementById('modalEditar');
        if (event.target === modal) {
            cerrarModalEditar();
        }
    }
    </script>

</body>

</html>