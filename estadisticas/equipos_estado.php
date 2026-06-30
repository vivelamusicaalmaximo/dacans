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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Equipos <?= htmlspecialchars($estado) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="bg-slate-100 p-6">

    <div class="max-w-7xl mx-auto">

        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-4xl font-black text-slate-900">
                    Estado: <?= htmlspecialchars($estado) ?>
                </h1>
                <p class="text-slate-500 mt-2">
                    <?= count($equipos) ?> equipos encontrados
                </p>
            </div>

            <div class="flex gap-3">
                <a href="exportar_estado_excel.php?estado=<?= urlencode($estado) ?>"
                    class="bg-green-600 hover:bg-green-700 text-white px-5 py-3 rounded-2xl font-black transition-colors">
                    <i class="fa-solid fa-file-excel mr-2"></i> Exportar Excel
                </a>
                <a href="index.php"
                    class="bg-blue-700 hover:bg-blue-800 text-white px-5 py-3 rounded-2xl font-black transition-colors">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Volver
                </a>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-100">
                        <tr>
                            <th class="p-4">ID</th>
                            <th class="p-4">Marca</th>
                            <th class="p-4">Modelo</th>
                            <th class="p-4">Comentario</th>
                            <th class="p-4">Precio</th>
                            <th class="p-4">Fecha</th>
                            <th class="p-4 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        <?php foreach($equipos as $e): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="p-4 font-black text-blue-700">
                                #<?= htmlspecialchars($e['id_local']) ?>
                            </td>
                            <td class="p-4 font-semibold text-slate-900">
                                <?= htmlspecialchars($e['equipo_marca']) ?>
                            </td>
                            <td class="p-4">
                                <?= htmlspecialchars($e['equipo_modelo']) ?>
                            </td>
                            <td class="p-4 text-slate-500 text-sm max-w-xs truncate">
                                <?= htmlspecialchars($e['comenta'] ?? '') ?>
                            </td>
                            <td class="p-4 font-black text-slate-900">
                                RD$ <?= number_format((float)$e['precio'], 0) ?>
                            </td>
                            <td class="p-4 text-sm text-slate-500">
                                <?= !empty($e['created_at']) ? date('d/m/Y', strtotime($e['created_at'])) : 'N/A' ?>
                            </td>
                            <td class="p-4 text-center">
                                <div class="flex items-center justify-center gap-2">

                                    <?php if (strtoupper($e['estado']) === 'CREDITO'): ?>
                                    <a href="../ventas/cxc_pagos.php?id=<?= $e['id_local'] ?>"
                                        class="inline-flex items-center justify-center text-xs font-black bg-amber-100 hover:bg-amber-200 text-amber-800 px-3 py-2 rounded-xl transition-all gap-1"
                                        title="Completar o abonar pago de cuenta por cobrar">
                                        <i class="fa-solid fa-money-bill-transfer"></i> Pago / CXC
                                    </a>
                                    <?php endif; ?>

                                    <a href="../ventas/editar.php?id=<?= $e['id_local'] ?>"
                                        class="inline-flex items-center justify-center text-xs font-medium bg-slate-100 hover:bg-blue-50 hover:text-blue-600 text-slate-600 px-3 py-2 rounded-xl transition-all">
                                        <i class="fa-solid fa-pen-to-square md:mr-1"></i> <span
                                            class="hidden md:inline">Editar</span>
                                    </a>

                                </div>
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