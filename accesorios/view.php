<?php

session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['admin_logueado'])) {

    header("Location: ../login.php");
    exit;
}

/* ======================================================
   CONEXION SQL SERVER
====================================================== */

require '../config/conexion.php';

try {

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {

    die("Error DB: " . $e->getMessage());
}

/* ======================================================
   CREAR TABLA SI NO EXISTE (SQL SERVER)
====================================================== */

$crearTabla = "
IF NOT EXISTS (
    SELECT *
    FROM sysobjects
    WHERE name='accesorios'
    AND xtype='U'
)
BEGIN

    CREATE TABLE accesorios (

        id INT IDENTITY(1,1) PRIMARY KEY,

        nombre NVARCHAR(255),
        categoria NVARCHAR(255),
        marca NVARCHAR(255),
        descripcion NVARCHAR(MAX),

        precio DECIMAL(18,2),
        stock INT DEFAULT 0,

        imagen_url NVARCHAR(MAX),

        estado NVARCHAR(100) DEFAULT 'Activo',

        fecha_creado DATETIME DEFAULT GETDATE()

    )

END
";

$pdo->exec($crearTabla);

/* ======================================================
   OBTENER ACCESORIOS
====================================================== */

$stmt = $pdo->query("
    SELECT *
    FROM accesorios
    ORDER BY id DESC
");

$accesorios = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ======================================================
   ROL
====================================================== */

$rolSesion = $_SESSION['rol'] ?? 'invitado';

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Panel Accesorios | DACANS</title>
<link rel="shortcut icon" href="/img/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <link rel="stylesheet"
        href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <style>

        body {

            background:
                radial-gradient(circle at top left, rgba(37,99,235,.08), transparent 30%),
                radial-gradient(circle at bottom right, rgba(14,165,233,.08), transparent 30%),
                #f8fafc;
        }

        table.dataTable {

            border-collapse: collapse !important;
            font-size: 13px;
        }

        table.dataTable thead th {

            background: #f8fafc;
            color: #0f172a;
            border-bottom: 1px solid #e2e8f0 !important;
            padding: 12px !important;
        }

        table.dataTable tbody td {

            padding: 10px !important;
            vertical-align: middle;
        }

        .compact-btn {

            width: 36px;
            height: 36px;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 12px;
        }

        .status-badge {

            padding: 5px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: bold;
        }

    </style>

</head>

<body class="p-4 md:p-8">

    <div class="max-w-[1600px] mx-auto">

        <!-- HEADER -->

        <div class="flex flex-col lg:flex-row justify-between gap-4 items-center mb-8">

            <div class="flex items-center gap-4">

                <div class="bg-white p-3 rounded-2xl shadow-lg border border-slate-200">

                    <img src="../img/logo.webp"
                        class="h-12 object-contain">

                </div>

                <div>

                    <h1 class="text-3xl font-black text-slate-900">
                        PANEL ACCESORIOS
                    </h1>

                    <p class="text-slate-500 text-sm">
                        Administración completa de accesorios
                    </p>

                </div>

            </div>

            <div class="flex gap-3 flex-wrap">

                <a href="create.php"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-2xl font-black shadow-lg transition">

                    <i class="fa-solid fa-plus mr-2"></i>

                    Nuevo Accesorio

                </a>

                <a href="../mantenimiento"
                    class="bg-slate-900 hover:bg-black text-white px-6 py-3 rounded-2xl font-black shadow-lg transition">

                    <i class="fa-solid fa-house mr-2"></i>

                    Inicio

                </a>

            </div>

        </div>

        <!-- TABLA -->

        <div class="bg-white rounded-[2rem] border border-slate-200 shadow-2xl p-4 overflow-hidden">

            <div class="overflow-x-auto">

                <table id="tablaAccesorios"
                    class="display nowrap w-full">

                    <thead>

                        <tr>

                            <th>ID</th>
                            <th>Imagen</th>
                            <th>Nombre</th>
                            <th>Categoría</th>
                            <th>Marca</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($accesorios as $a): ?>

                            <tr>

                                <!-- ID -->

                                <td class="font-black text-blue-700">

                                    #<?= $a['id'] ?>

                                </td>

                                <!-- IMAGEN -->

                                <td>

                                    <div class="w-16 h-16 rounded-2xl overflow-hidden bg-slate-100 border border-slate-200">

                                        <img
                                            src="<?= !empty($a['imagen_url']) ? $a['imagen_url'] : 'https://via.placeholder.com/100x100?text=IMG' ?>"
                                            class="w-full h-full object-cover">

                                    </div>

                                </td>

                                <!-- NOMBRE -->

                                <td class="font-bold text-slate-900">

                                    <?= $a['nombre'] ?>

                                </td>

                                <!-- CATEGORIA -->

                                <td>

                                    <?= $a['categoria'] ?>

                                </td>

                                <!-- MARCA -->

                                <td>

                                    <?= $a['marca'] ?>

                                </td>

                                <!-- PRECIO -->

                               <?php
$precio = is_numeric($a['precio'])
    ? (float)$a['precio']
    : 0;
?>

<td class="font-black">
    RD$ <?= number_format($precio, 0) ?>
</td>

                                <!-- STOCK -->

                                <td>

                                    <?= $a['stock'] ?>

                                </td>

                                <!-- ESTADO -->

                                <td>

                                    <span class="status-badge
                                        <?= $a['estado'] == 'Activo'
                                            ? 'bg-green-100 text-green-700'
                                            : ($a['estado'] == 'Agotado'
                                                ? 'bg-red-100 text-red-600'
                                                : 'bg-yellow-100 text-yellow-700')
                                        ?>">

                                        <?= $a['estado'] ?>

                                    </span>

                                </td>

                                <!-- FECHA -->

                              <td class="text-xs text-slate-500">

    <?php if (!empty($a['fecha_creado'])): ?>

        <?= date('d/m/Y', strtotime($a['fecha_creado'])) ?>

    <?php else: ?>

        -

    <?php endif; ?>

</td>
                                <!-- ACCIONES -->

                                <td>

                                    <div class="flex gap-2">

                                        <!-- VER -->

                                        <a
                                            href="show.php?id=<?= $a['id'] ?>"
                                            class="compact-btn bg-blue-600 hover:bg-blue-700 text-white transition">

                                            <i class="fa-solid fa-eye"></i>

                                        </a>

                                        <!-- EDITAR -->
                                  

                                        <a
                                            href="edit.php?id=<?= $a['id'] ?>"
                                            class="compact-btn bg-yellow-500 hover:bg-yellow-600 text-white transition">

                                            <i class="fa-solid fa-pen"></i>

                                        </a>

                                        <!-- ELIMINAR -->

                                        <?php if($rolSesion === 'admin' || $rolSesion === 'superadmin') : ?>
                                     
                                        <a
                                            href="delete.php?id=<?= $a['id'] ?>"
                                            onclick="return confirm('¿Eliminar accesorio?')"
                                            class="compact-btn bg-red-500 hover:bg-red-600 text-white transition">

                                            <i class="fa-solid fa-trash"></i>

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

    <!-- SCRIPTS -->

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>

        $(document).ready(function () {

            $('#tablaAccesorios').DataTable({

                responsive: true,
                pageLength: 10,
                scrollX: true,
                autoWidth: false,

                language: {

                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                }

            });

        });

    </script>

</body>

</html>