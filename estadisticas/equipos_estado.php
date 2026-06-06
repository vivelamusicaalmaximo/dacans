<?php

session_start();

if (!isset($_SESSION['admin_logueado'])) {

    header("Location: ../login.php");
    exit;
}

require_once '../config/conexion.php';

$pdo->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION
);

$estado = $_GET['estado'] ?? '';

if (empty($estado)) {

    die("Estado no especificado");
}

if ($estado === 'TODOS') {

    $stmt = $pdo->prepare("
        SELECT *
        FROM productos_informatica
        ORDER BY created_at DESC
    ");

    $stmt->execute();

} else {

    $stmt = $pdo->prepare("
        SELECT *
        FROM productos_informatica
        WHERE estado = ?
        ORDER BY created_at DESC
    ");

    $stmt->execute([$estado]);
}

$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
Equipos <?= htmlspecialchars($estado) ?>
</title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body class="bg-slate-100 p-6">

<div class="max-w-7xl mx-auto">

    <!-- HEADER -->

    <div class="flex justify-between items-center mb-8">

        <div>

            <h1 class="text-4xl font-black text-slate-900">

                Estado:
                <?= htmlspecialchars($estado) ?>

            </h1>

            <p class="text-slate-500 mt-2">

                <?= count($equipos) ?>
                equipos encontrados

            </p>

        </div>

        

       <div class="flex gap-3">

    <a href="exportar_estado_excel.php?estado=<?= urlencode($estado) ?>"
    class="bg-green-600 hover:bg-green-700
    text-white px-5 py-3 rounded-2xl font-black">

        <i class="fa-solid fa-file-excel mr-2"></i>
        Exportar Excel

    </a>

    <a href="index.php"
    class="bg-blue-700 hover:bg-blue-800
    text-white px-5 py-3 rounded-2xl font-black">

        <i class="fa-solid fa-arrow-left mr-2"></i>
        Volver

    </a>

</div>
        

    </div>

    <!-- TABLA -->

    <div class="bg-white rounded-3xl shadow-lg overflow-hidden">

        <div class="overflow-x-auto">

            <table class="w-full">

                <thead class="bg-slate-50">

                    <tr>

                        <th class="p-4 text-left">ID</th>
                        <th class="p-4 text-left">Marca</th>
                        <th class="p-4 text-left">Modelo</th>
                        <th class="p-4 text-left">Comentario</th>
                        <th class="p-4 text-left">Precio</th>
                        <th class="p-4 text-left">Fecha</th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach($equipos as $e): ?>

                        <tr class="border-b border-slate-100">

                            <td class="p-4 font-black text-blue-700">
                                <?= $e['id_local'] ?>
                            </td>

                            <td class="p-4">
                                <?= $e['equipo_marca'] ?>
                            </td>

                            <td class="p-4">
                                <?= $e['equipo_modelo'] ?>
                            </td>

                            <td class="p-4">
                                <?= $e['comenta'] ?>
                            </td>

                            <td class="p-4 font-black">

                                RD$
                                <?= number_format((float)$e['precio'], 0) ?>

                            </td>

                            <td class="p-4 text-sm text-slate-500">
                                <?= $e['created_at'] ?>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>
</html>