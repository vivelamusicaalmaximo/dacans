<?php

session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

$rolSesion = $_SESSION['rol'] ?? 'empleado';

/* ======================================================
   CONEXION SQL SERVER
====================================================== */
require_once '../config/conexion.php';

$error_msg = "";

/* ======================================================
   FUNCION LOG CREAR
====================================================== */
function registrarLogCrear($pdo, $equipo_id, $datos) {
    $usuario = $_SESSION['usuario'] ?? 'Desconocido';
    $rol = $_SESSION['rol'] ?? 'Sin rol';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'IP desconocida';

    $descripcion =
        "Nuevo equipo registrado | "
        . "Marca: " . ($datos['equipo_marca'] ?? '-') . " | "
        . "Modelo: " . ($datos['equipo_modelo'] ?? '-') . " | "
        . "CPU: " . ($datos['proc_marca'] ?? '-') . " "
        . ($datos['proc_familia'] ?? '-') . " "
        . ($datos['proc_modelo'] ?? '-') . " | "
        . "RAM: " . ($datos['memoria'] ?? '-') . " | "
        . "Disco: " . ($datos['disco'] ?? '-') . " | "
        . "Precio: RD$ " . ($datos['precio'] ?? '0');

    $stmt = $pdo->prepare("
        INSERT INTO logs_sistema (
            usuario,
            rol,
            accion,
            equipo_id,
            descripcion,
            ip
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $usuario,
        $rol,
        'CREAR',
        $equipo_id,
        $descripcion,
        $ip
    ]);
}

/* ======================================================
   MODO EDICION
====================================================== */
$modo_edicion = false;
$equipoEditar = null;

if (isset($_GET['editar']) && !empty($_GET['editar'])) {
    $modo_edicion = true;
    $stmtEdit = $pdo->prepare("
        SELECT TOP 1 *
        FROM productos_informatica
        WHERE id_local = ?
    ");
    $stmtEdit->execute([$_GET['editar']]);
    $equipoEditar = $stmtEdit->fetch(PDO::FETCH_ASSOC);

    if (!$equipoEditar) {
        die("Equipo no encontrado");
    }
}

/* ======================================================
   GENERAR NUEVO ID LOCAL
====================================================== */
$ultimo = $pdo->query("
    SELECT TOP 1 id_local
    FROM productos_informatica
    ORDER BY id DESC
")->fetch(PDO::FETCH_ASSOC);

if ($ultimo && !empty($ultimo['id_local'])) {
    preg_match('/(\d+)$/', $ultimo['id_local'], $match);
    $numero = isset($match[1]) ? (int)$match[1] : 1000;
    $nuevoNumero = $numero + 1;
} else {
    $nuevoNumero = 1001;
}

$nuevo_id_local = "DC-2026-" . $nuevoNumero;

/* ======================================================
   GUARDAR NUEVO EQUIPO
====================================================== */
if (isset($_POST['guardar_nuevo'])) {

    try {
        // Iniciamos transacción para asegurar consistencia estricta
        $pdo->beginTransaction();

        $id_a_registrar = trim($_POST['id_local'] ?? $nuevo_id_local);

        // 🔍 [CONTROL DE DUPLICADOS] Validar si el ID Local ya existe en la BD
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM productos_informatica WHERE id_local = ?");
        $stmtCheck->execute([$id_a_registrar]);
        if ($stmtCheck->fetchColumn() > 0) {
            throw new Exception("El ID Local <strong>{$id_a_registrar}</strong> ya fue registrado por otro usuario. Por favor, cierra el modal, refresca la página e inténtalo de nuevo.");
        }

        // --------------------------------------------------
        // PROCESAMIENTO DE IMÁGENES LOCALES (SUBIDA FÍSICA)
        // --------------------------------------------------
        $directorio_subida = '../uploads/';
        
        if (!is_dir($directorio_subida)) {
            mkdir($directorio_subida, 0755, true);
        }

        $fotos_subidas = [];

        if (!empty($_FILES['fotos_propias']['name'][0])) {
            foreach ($_FILES['fotos_propias']['name'] as $key => $name) {
                
                $tmp_name = $_FILES['fotos_propias']['tmp_name'][$key];
                $error    = $_FILES['fotos_propias']['error'][$key];

                if ($error === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    $nuevo_nombre = 'eq_' . uniqid() . '_' . $key . '.' . $ext;
                    $ruta_destino = $directorio_subida . $nuevo_nombre;

                    if (move_uploaded_file($tmp_name, $ruta_destino)) {
                        $fotos_subidas[] = 'uploads/' . $nuevo_nombre;
                    }
                }
            }
        }

        $imagen_principal  = $_POST['imagen_url'] ?: null;
        $fotos_adicionales = [];

        if (!empty($fotos_subidas)) {
            if (empty($imagen_principal)) {
                $imagen_principal = '../' . $fotos_subidas[0]; 
                array_shift($fotos_subidas); 
            }
            foreach ($fotos_subidas as $f) {
                $fotos_adicionales[] = '../' . $f;
            }
        }

        $imagenes_adicionales_string = !empty($fotos_adicionales) ? implode(',', $fotos_adicionales) : null;

        // --------------------------------------------------
        // INSERCIÓN EN LA BASE DE DATOS
        // --------------------------------------------------
        $sql = "INSERT INTO productos_informatica (
            id_local, serie, proc_marca, proc_familia, proc_generacion, proc_modelo,
            graficos, g_expandible, memoria, disco, pantalla, p_resolucion, touch,
            precio, estado, comenta, equipo_marca, equipo_modelo, imagen_url,
            imagenes_adicionales, created_at, vendida_at,clase
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            $id_a_registrar,
            $_POST['serie'] ?: null,
            $_POST['proc_marca'] ?: null,
            $_POST['proc_familia'] ?: null,
            $_POST['proc_generacion'] ?: null,
            $_POST['proc_modelo'] ?: null,
            $_POST['graficos'] ?: null,
            $_POST['g_expandible'] ?: 0,
            $_POST['memoria'] ?: null,
            $_POST['disco'] ?: null,
            $_POST['pantalla'] ?: null,
            $_POST['p_resolucion'] ?: null,
            $_POST['touch'] ?: 0,
            $_POST['precio'] ?: 0,
            $_POST['estado'] ?: null,
            $_POST['comenta'] ?: null,
            $_POST['equipo_marca'] ?: null,
            $_POST['equipo_modelo'] ?: null,
            $imagen_principal,
            $imagenes_adicionales_string, 
            date('Y-m-d H:i:s'),
            !empty($_POST['vendida_at']) ? $_POST['vendida_at'] : null,
            $_POST['clase'] ?: null
        ]);

        // --------------------------------------------------
        // REGISTRO DE AUDITORÍA (LOGS)
        // --------------------------------------------------
        $datosNuevoEquipo = [
            'equipo_marca'  => $_POST['equipo_marca'],
            'equipo_modelo' => $_POST['equipo_modelo'],
            'proc_marca'    => $_POST['proc_marca'],
            'proc_familia'  => $_POST['proc_familia'],
            'proc_modelo'   => $_POST['proc_modelo'],
            'memoria'       => $_POST['memoria'],
            'disco'         => $_POST['disco'],
            'precio'        => $_POST['precio'],
        ];

        registrarLogCrear($pdo, $id_a_registrar, $datosNuevoEquipo);

        // Confirmamos los datos en la base de datos
        $pdo->commit();

        header("Location: index.php?success=1");
        exit();

    } catch (Exception $e) {
        // Si algo falla o el ID está duplicado, revertimos los cambios en la BD
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error_msg = $e->getMessage();
    }
}

/* ======================================================
   OBTENER EQUIPOS
====================================================== */
$query = $pdo->query("
    SELECT *
    FROM productos_informatica
    WHERE estado != 'Vendida'
    ORDER BY equipo_marca ASC
");

$equipos = $query->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Inventario Maestro | Dacans Computers</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="shortcut icon" href="/img/favicon.ico">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
    body {
        overflow-x: hidden;
        font-family: Arial, sans-serif;
        background: #f1f5f9;
    }

    .page-container {
        width: 100%;
        max-width: 100%;
        margin: auto;
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    table.dataTable {
        border-collapse: collapse !important;
        font-size: 11px !important;
        width: 100% !important;
    }

    table.dataTable thead th {
        background: #f8fafc;
        color: #1e3a8a;
        border-bottom: 1px solid #e2e8f0 !important;
        padding: 8px 6px !important;
        font-size: 10px;
        white-space: normal !important;
        word-break: break-word;
        text-align: center;
        vertical-align: middle;
    }

    table.dataTable tbody td {
        padding: 6px !important;
        vertical-align: middle;
        white-space: normal !important;
        word-break: break-word;
    }

    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #dbe2ea;
        border-radius: 10px;
        padding: 5px 10px;
        font-size: 12px;
        height: 34px;
    }

    .dataTables_wrapper .dataTables_length select {
        border-radius: 10px;
        padding: 4px 8px;
        border: 1px solid #dbe2ea;
        font-size: 12px;
    }

    .dataTables_info,
    .dataTables_paginate,
    .dataTables_length,
    .dataTables_filter {
        font-size: 12px !important;
    }

    .dataTables_paginate .paginate_button {
        padding: 4px 10px !important;
        border-radius: 8px !important;
    }

    .compact-btn {
        width: 30px;
        height: 30px;
        min-width: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        font-size: 11px;
    }

    .status-badge {
        padding: 3px 8px;
        border-radius: 999px;
        font-size: 10px;
        font-weight: bold;
    }

    @media(max-width:768px) {
        body {
            padding: 8px;
        }

        table.dataTable {
            font-size: 10px !important;
        }

        table.dataTable thead th {
            font-size: 9px !important;
            padding: 6px 4px !important;
        }

        table.dataTable tbody td {
            font-size: 10px !important;
            padding: 5px !important;
        }

        .compact-btn {
            width: 26px;
            height: 26px;
            min-width: 26px;
            font-size: 10px;
        }
    }

    .dataTables_wrapper {
        width: 100%;
        overflow-x: auto;
    }

    table {
        width: 100% !important;
    }

    #tablaInventario th,
    #tablaInventario td {
        max-width: 140px;
    }

    #tablaInventario {
        width: 100% !important;
        table-layout: auto !important;
    }

    #tablaInventario thead th {
        white-space: normal !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
        min-width: 70px;
        max-width: 120px;
        text-align: center;
        vertical-align: middle;
        line-height: 1.2;
    }

    #tablaInventario tbody td {
        white-space: normal !important;
        word-break: break-word !important;
        overflow-wrap: break-word !important;
        line-height: 1.2;
    }

    .dataTables_scrollHeadInner,
    .dataTables_scrollHeadInner table {
        width: 100% !important;
    }

    @media(max-width:768px) {
        #tablaInventario thead th {
            min-width: 55px;
            max-width: 90px;
            font-size: 9px !important;
            padding: 4px 3px !important;
        }

        #tablaInventario tbody td {
            font-size: 9px !important;
            padding: 4px 3px !important;
        }
    }
    </style>
</head>

<body class="bg-slate-50 p-3 sm:p-6">
    <div class="page-container px-2 sm:px-4">
        <div class="flex flex-col lg:flex-row gap-4 justify-between items-center mb-8">
            <div class="flex items-center gap-4">
                <img src="../img/logo.webp" class="h-12 sm:h-14">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black text-blue-900 tracking-tighter">INVENTARIO TOTAL</h1>
                    <p class="text-slate-400 text-sm italic">Gestión completa de inventario</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 w-full lg:w-auto">
                <?php if (isset($_SESSION['admin_logueado'])): ?>
                <a href="../mantenimiento"
                    class="bg-slate-900 hover:bg-black text-white px-5 py-3 rounded-2xl font-black shadow-lg transition">
                    <i class="fa-solid fa-screwdriver-wrench mr-2"></i> Mantenimiento
                </a>
                <a href="exportar_excel.php"
                    class="bg-green-700 hover:bg-green-800 text-white px-5 py-3 rounded-2xl font-bold text-sm shadow-lg transition">
                    <i class="fa-solid fa-file-excel mr-2"></i> Exportar Excel
                </a>
                <?php endif; ?>

                <?php if($rolSesion !== 'empleado'): ?>
                <button onclick="openModal()"
                    class="flex-1 lg:flex-none bg-green-600 text-white px-5 py-3 rounded-2xl font-bold text-sm shadow-lg hover:bg-black transition">
                    <i class="fa-solid fa-laptop-medical mr-2"></i> Nuevo Registro
                </button>
                <?php endif; ?>

                <a href="../catalogo"
                    class="flex-1 lg:flex-none bg-white border border-slate-200 px-5 py-3 rounded-2xl font-bold text-sm text-slate-600 hover:bg-slate-50 transition text-center">
                    <i class="fa-solid fa-house mr-2"></i> Catálogo
                </a>
            </div>
        </div>

        <?php if (!empty($error_msg)): ?>
        <div class="bg-red-500 text-white p-4 rounded-2xl mb-6 font-bold">
            <?= $error_msg ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
        <div class="bg-green-600 text-white p-4 rounded-2xl mb-6 font-bold">
            Equipo registrado correctamente
        </div>
        <?php endif; ?>

        <div class="bg-white p-2 sm:p-3 rounded-3xl shadow-lg border border-slate-200">
            <div class="table-responsive">
                <table id="tablaInventario" class="display w-full">
                    <thead>
                        <tr>
                            <th>Acciones</th>
                            <th>ID Local</th>
                            <th>Serie</th>
                            <th>Marca</th>
                            <th>Modelo</th>
                            <th>Procesador</th>
                            <th>Familia</th>
                            <th>Modelo CPU</th>
                            <th>Gen</th>
                            <th>GPU</th>
                            <th>Expandible</th>
                            <th>RAM</th>
                            <th>Disco</th>
                            <th>Pantalla</th>
                            <th>Resolución</th>
                            <th>Touch</th>
                            <th>Precio</th>
                            <th>Comentario</th>
                            <th>Estado</th>
                            <th>Fecha Registro</th>
                            <th>Fecha_Venta</th>
                            <th>Clase</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($equipos as $e): ?>
                        <tr>
                            <td class="min-w-[130px]">
                                <div class="flex items-center gap-1">
                                    <a href="show.php?id=<?= urlencode($e['id_local']) ?>" target="_blank"
                                        class="compact-btn bg-blue-600 hover:bg-blue-700 text-white transition">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?= urlencode($e['id_local']) ?>"
                                        class="compact-btn bg-yellow-500 hover:bg-yellow-600 text-white transition">
                                        <i class="fa-solid fa-pen"></i>
                                    </a>
                                    <?php if($rolSesion === 'admin' || $rolSesion === 'superadmin') : ?>
                                    <a href="delete.php?id=<?= urlencode($e['id_local']) ?>"
                                        onclick="return confirm('¿Eliminar este equipo?')"
                                        class="compact-btn bg-red-500 hover:bg-red-600 text-white transition">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="font-black text-blue-700"><?= $e['id_local'] ?></td>
                            <td><?= $e['serie'] ?: '-' ?></td>
                            <td><?= $e['equipo_marca'] ?></td>
                            <td><?= $e['equipo_modelo'] ?></td>
                            <td><?= $e['proc_marca'] ?></td>
                            <td><?= $e['proc_familia'] ?></td>
                            <td><?= $e['proc_modelo'] ?></td>
                            <td><?= $e['proc_generacion'] ?></td>
                            <td><?= $e['graficos'] ?></td>
                            <td class="font-bold"><?= $e['g_expandible'] == 1 ? 'SI' : 'NO' ?></td>
                            <td><?= $e['memoria'] ?></td>
                            <td><?= $e['disco'] ?></td>
                            <td><?= $e['pantalla'] ?></td>
                            <td><?= $e['p_resolucion'] ?></td>
                            <td class="font-bold"><?= $e['touch'] == 1 ? 'SI' : 'NO' ?></td>
                            <td class="font-black">
                                <?= !empty($e['precio']) ? 'RD$ ' . number_format($e['precio'], 0) : '-' ?></td>
                            <td class="max-w-[180px] text-[11px] leading-tight"><?= $e['comenta'] ?></td>
                            <td>
                                <span class="status-badge <?= 
                                        $e['estado'] == 'Vendida' ? 'bg-red-100 text-red-600' : 
                                        ($e['estado'] == 'NO Lista' ? 'bg-yellow-100 text-yellow-700' : 
                                        ($e['estado'] == 'En camino' ? 'bg-blue-100 text-blue-700' : 
                                        ($e['estado'] == 'En revision' ? 'bg-purple-100 text-purple-700' : 'bg-green-100 text-green-600'))) 
                                    ?>"><?= $e['estado'] ?></span>
                            </td>
                            <td><?= $e['created_at'] ?></td>
                            <td><?= $e['vendida_at'] ?></td>
                            <td><?= $e['clase'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="modalRegistro" class="fixed inset-0 z-[100] hidden">
        <div onclick="closeModal()" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
        <div class="relative min-h-screen flex items-center justify-center p-2 sm:p-5">
            <div class="relative bg-white w-full max-w-3xl rounded-[2rem] shadow-2xl overflow-hidden">
                <div class="flex justify-between items-center px-5 sm:px-8 py-5 border-b border-slate-100">
                    <div>
                        <h2 class="text-xl sm:text-2xl font-black text-blue-900 uppercase italic">Registrar Equipo</h2>
                        <p class="text-xs text-slate-400 mt-1">Formulario rápido de inventario</p>
                    </div>
                    <button onclick="closeModal()"
                        class="w-10 h-10 rounded-full bg-slate-100 hover:bg-red-100 text-slate-500 hover:text-red-500 transition flex items-center justify-center text-xl">&times;</button>
                </div>

                <form method="POST" enctype="multipart/form-data" class="p-5 sm:p-8 overflow-y-auto max-h-[85vh]">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-2">ID Local</label>
                            <input type="text" name="id_local" id="id_local" readonly value="<?= $nuevo_id_local ?>"
                                class="w-full p-3 rounded-2xl bg-slate-100 border border-slate-200 text-blue-700 font-black">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Serie</label>
                            <input type="text" name="serie" id="serie" placeholder="Opcional"
                                class="w-full p-3 rounded-2xl bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Marca</label>
                            <input type="text" required name="equipo_marca" id="equipo_marca"
                                placeholder="Dell / Lenovo"
                                class="w-full p-3 rounded-2xl bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Modelo</label>
                            <input type="text" name="equipo_modelo" id="equipo_modelo" placeholder="ThinkPad T14"
                                class="w-full p-3 rounded-2xl bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Procesador</label>
                            <input type="text" name="proc_marca" id="proc_marca" placeholder="Intel / AMD"
                                class="w-full p-3 rounded-2xl bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Generación</label>
                            <input type="text" name="proc_generacion" id="proc_generacion" placeholder="11 / Ryzen 7"
                                class="w-full p-3 rounded-2xl bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Familia CPU</label>
                            <input type="text" name="proc_familia" id="proc_familia" placeholder="i7 / Ryzen 5"
                                class="w-full p-3 rounded-2xl bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Modelo CPU</label>
                            <input type="text" name="proc_modelo" id="proc_modelo" placeholder="1165G7"
                                class="w-full p-3 rounded-2xl bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Gráficos</label>
                            <input type="text" name="graficos" id="graficos" placeholder="RTX 3050"
                                class="w-full p-3 rounded-2xl bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Tipo de GPU</label>
                            <select name="g_expandible" id="g_expandible"
                                class="w-full p-3 rounded-2xl bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="0">Integrada</option>
                                <option value="1">APU Ajustable</option>
                                <option value="2">Dedicada</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Pantalla</label>
                            <input type="text" name="pantalla" id="pantalla" placeholder='15.6"'
                                class="w-full p-3 rounded-2xl bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Resolución</label>
                            <input type="text" name="p_resolucion" id="p_resolucion" placeholder="1920x1080"
                                class="w-full p-3 rounded-2xl bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Touch</label>
                            <select name="touch" id="touch"
                                class="w-full p-3 rounded-2xl bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="0">NO</option>
                                <option value="1">SI</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Estado</label>
                            <select name="estado" id="estado"
                                class="w-full p-3 rounded-2xl bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="Lista">Lista</option>
                                <option value="NO Lista">NO Lista</option>
                                <option value="Vendida">Vendida</option>
                                <option value="En camino">En camino</option>
                                <option value="En revision">En revision</option>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Comentario</label>
                            <textarea name="comenta" id="comenta" rows="4"
                                class="w-full p-3 rounded-2xl bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none"
                                placeholder="Detalles adicionales..."></textarea>
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-2">RAM</label>
                            <input type="text" name="memoria" id="memoria" placeholder="16 G"
                                class="w-full p-3 rounded-2xl bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Disco</label>
                            <input type="text" name="disco" id="disco" placeholder="512 G / 1 TB"
                                class="w-full p-3 rounded-2xl bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Precio RD$</label>
                            <input type="number" name="precio" id="precio" placeholder="Opcional"
                                class="w-full p-3 rounded-2xl bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none font-bold text-blue-700">
                        </div>
                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-2">URL Imagen (Web)</label>
                            <input type="text" name="imagen_url" id="imagen_url" placeholder="https://..."
                                class="w-full p-3 rounded-2xl bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-[10px] font-black uppercase text-slate-700 ml-2 block mb-1">Subir Fotos
                                Propias (Local/Dispositivo) - <span class="text-blue-600 lowercase font-normal">Puedes
                                    seleccionar varias</span></label>
                            <input type="file" name="fotos_propias[]" multiple accept="image/*"
                                class="w-full p-3 rounded-2xl bg-slate-100 border border-dashed border-slate-300 text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:uppercase file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                        </div>

                        <div>
                            <label class="text-[10px] font-black uppercase text-slate-400 ml-2">Clase</label>
                            <select name="clase" id="clase"
                                class="w-full p-3 rounded-2xl bg-slate-50 border border-slate-200 focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="Laptop">A</option>
                                <option value="Desktop">B</option>
                                <option value="Tablet">C</option>

                            </select>
                        </div>


                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 mt-8">
                        <button type="submit" name="guardar_nuevo"
                            class="flex-1 bg-blue-900 hover:bg-black transition text-white py-4 rounded-2xl font-black shadow-xl">GUARDAR
                            EQUIPO</button>
                        <button type="button" onclick="closeModal()"
                            class="flex-1 sm:flex-none px-8 py-4 rounded-2xl bg-slate-100 hover:bg-slate-200 font-bold text-slate-600 transition">CANCELAR</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#tablaInventario').DataTable({
            pageLength: 15,
            responsive: true,
            autoWidth: false,
            scrollX: false,
            paging: true,
            searching: true,
            ordering: true,
            info: true,
            lengthChange: false,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            }
        });
    });

    function openModal() {
        document.getElementById('modalRegistro').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('modalRegistro').classList.add('hidden');
    }

    // --- SISTEMA DE AUTORELLENADO POR MODELO ---
    document.getElementById('equipo_modelo').addEventListener('blur', function() {
        const modeloVal = this.value.trim();

        // Si tiene menos de 2 caracteres no buscamos nada
        if (modeloVal.length < 2) return;

        // Llamada AJAX nativa con Fetch API al archivo que creamos antes
        fetch(`buscar_modelo.php?modelo=${encodeURIComponent(modeloVal)}`)
            .then(response => response.json())
            .then(res => {
                if (res.success) {
                    const info = res.data;

                    // Rellenamos los campos únicamente si están vacíos actualmente
                    if (!document.getElementById('equipo_marca').value) document.getElementById(
                        'equipo_marca').value = info.equipo_marca || '';
                    if (!document.getElementById('proc_marca').value) document.getElementById('proc_marca')
                        .value = info.proc_marca || '';
                    if (!document.getElementById('proc_familia').value) document.getElementById(
                        'proc_familia').value = info.proc_familia || '';
                    if (!document.getElementById('proc_generacion').value) document.getElementById(
                        'proc_generacion').value = info.proc_generacion || '';
                    if (!document.getElementById('proc_modelo').value) document.getElementById(
                        'proc_modelo').value = info.proc_modelo || '';
                    if (!document.getElementById('graficos').value) document.getElementById('graficos')
                        .value = info.graficos || '';
                    if (!document.getElementById('memoria').value) document.getElementById('memoria')
                        .value = info.memoria || '';
                    if (!document.getElementById('disco').value) document.getElementById('disco').value =
                        info.disco || '';
                    if (!document.getElementById('pantalla').value) document.getElementById('pantalla')
                        .value = info.pantalla || '';
                    if (!document.getElementById('p_resolucion').value) document.getElementById(
                        'p_resolucion').value = info.p_resolucion || '';
                    if (!document.getElementById('precio').value) document.getElementById('precio').value =
                        info.precio || '';
                    if (!document.getElementById('imagen_url').value) document.getElementById('imagen_url')
                        .value = info.imagen_url || '';

                    // Selectores dinámicos
                    document.getElementById('g_expandible').value = info.g_expandible !== null ? info
                        .g_expandible : '0';
                    document.getElementById('touch').value = info.touch !== null ? info.touch : '0';

                    // Animación o feedback visual rápido en verde para avisar al usuario
                    this.classList.add('border-green-500', 'ring-2', 'ring-green-200');
                    setTimeout(() => this.classList.remove('border-green-500', 'ring-2', 'ring-green-200'),
                        1500);
                }
            })
            .catch(err => console.error("Error al obtener el auto-completado:", err));
    });
    </script>
</body>

</html>