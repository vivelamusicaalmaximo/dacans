<?php

session_start();

if (!isset($_SESSION['admin_logueado'])) {

    header("Location: ../login.php");
    exit;
}

/* =========================================
   CONEXION SQL SERVER
========================================= */

require '../config/conexion.php';

try {

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (Exception $e) {

    die("Error DB: " . $e->getMessage());
}

/* =========================================
   OBTENER LOGS
========================================= */

$query = $pdo->query("

    SELECT *
    FROM logs_sistema
    ORDER BY created_at DESC

");

$logs = $query->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <title>Logs del Sistema</title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet"
        href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>

body{
    background:#f1f5f9;
    font-family:Arial,sans-serif;
}

/* WRAPPER */
.dataTables_wrapper{
    width:100%;
    overflow-x:auto;
}

/* TABLA */
table.dataTable{
    width:100% !important;
    border-collapse:collapse !important;
    font-size:12px !important;
}

/* HEADER */
table.dataTable thead th{

    background:#0f172a;
    color:white;

    padding:10px 8px !important;

    white-space:normal !important;
    word-break:break-word !important;

    text-align:center;
    vertical-align:middle;

    font-size:11px;
}

/* CELDAS */
table.dataTable tbody td{

    padding:8px !important;

    white-space:normal !important;
    word-break:break-word !important;

    vertical-align:middle;

    font-size:12px;
}

/* INPUT BUSCAR */
.dataTables_wrapper .dataTables_filter input{

    border:1px solid #cbd5e1;
    border-radius:12px;

    padding:6px 12px;

    font-size:12px;
}

/* SELECT */
.dataTables_wrapper .dataTables_length select{

    border:1px solid #cbd5e1;
    border-radius:10px;

    padding:5px 8px;
}

/* BADGE */
.badge{

    padding:4px 10px;
    border-radius:999px;

    font-size:11px;
    font-weight:bold;

    display:inline-block;
}

/* DESCRIPCION */
.descripcion-cell{

    min-width:350px;
    max-width:800px;

    line-height:1.4;
}

/* MOBILE */
@media(max-width:768px){

    body{
        padding:8px;
    }

    table.dataTable{
        font-size:10px !important;
    }

    table.dataTable thead th{

        font-size:9px !important;
        padding:6px 4px !important;
    }

    table.dataTable tbody td{

        font-size:10px !important;
        padding:6px 4px !important;
    }

    .descripcion-cell{

        min-width:220px;
        max-width:320px;
    }

}

</style>

</head>

<body class="bg-slate-100 min-h-screen p-2 sm:p-4 lg:p-6 overflow-x-hidden">

<div class="w-full max-w-full mx-auto">

    <!-- HEADER -->

    <div class="flex flex-col lg:flex-row gap-4 justify-between items-center mb-6">

        <div>

            <h1 class="text-3xl font-black text-slate-800">
                LOGS DEL SISTEMA
            </h1>

            <p class="text-slate-500 text-sm">
                Historial de acciones realizadas
            </p>

        </div>

        <div class="flex gap-2">

            <a href="../mantenimiento"
                class="bg-blue-700 hover:bg-blue-800 text-white px-5 py-3 rounded-2xl font-bold transition">

                <i class="fa-solid fa-arrow-left mr-2"></i>

                Regresar

            </a>

        </div>

    </div>

    <!-- TABLA -->

    <div class="bg-white rounded-3xl shadow-lg border border-slate-200 p-2 sm:p-4 w-full overflow-hidden">

        <div class="w-full overflow-x-auto">

            <table id="tablaLogs" class="display w-full">

                <thead>

                    <tr>

                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Rol</th>
                        <th>Acción</th>
                        <th>Equipo</th>
                        <th>Descripción</th>
                        <th>IP</th>
                        <th>Fecha</th>

                    </tr>

                </thead>

                <tbody>

                <?php foreach($logs as $log): ?>

                    <tr>

                        <td>
                            <?= $log['id'] ?>
                        </td>

                        <td class="font-bold text-slate-700">
                            <?= htmlspecialchars($log['usuario']) ?>
                        </td>

                        <td>
                            <?= htmlspecialchars($log['rol']) ?>
                        </td>

                        <td>

<?php

$color = 'bg-slate-100 text-slate-700';

if($log['accion'] == 'CREAR'){
    $color = 'bg-green-100 text-green-700';
}

if($log['accion'] == 'EDITAR'){
    $color = 'bg-yellow-100 text-yellow-700';
}

if($log['accion'] == 'ELIMINAR'){
    $color = 'bg-red-100 text-red-700';
}

if($log['accion'] == 'VENDIDA'){
    $color = 'bg-blue-100 text-blue-700';
}

?>

<span class="badge <?= $color ?>">

    <?= htmlspecialchars($log['accion']) ?>

</span>

                        </td>

                        <td class="font-bold text-blue-700">

                            <?= htmlspecialchars($log['equipo_id']) ?>

                        </td>

                        <td class="descripcion-cell">

                            <?= htmlspecialchars($log['descripcion']) ?>

                        </td>

                        <td>

                            <?= htmlspecialchars($log['ip']) ?>

                        </td>

                        <td>

                            <?= htmlspecialchars($log['created_at']) ?>

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

    $('#tablaLogs').DataTable({

        pageLength: 25,

        responsive: true,

        autoWidth: false,

        scrollX: true,

        order: [[7, 'desc']],

        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
        }

    });

});

</script>

</body>

</html>