<?php

session_start();

if (!isset($_SESSION['admin_logueado'])) {

    header("Location: ../login.php");
    exit;
}

/* =========================================================
   BASE DE DATOS SQL SERVER
========================================================= */

require '../config/conexion.php';

try {

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {

    die("Error DB: " . $e->getMessage());
}

/* =========================================================
   EQUIPOS
========================================================= */

$totalEquipos = $pdo->query("
    SELECT COUNT(*)
    FROM productos_informatica
")->fetchColumn();

$valorEquipos = $pdo->query("
    SELECT ISNULL(SUM(precio),0)
    FROM productos_informatica
")->fetchColumn();

$equiposDisponibles = $pdo->query("
    SELECT COUNT(*)
    FROM productos_informatica
    WHERE estado = 'Lista'
")->fetchColumn();

$equiposVendidos = $pdo->query("
    SELECT COUNT(*)
    FROM productos_informatica
    WHERE estado = 'Vendida'
")->fetchColumn();

/* =========================================================
   ACCESORIOS
========================================================= */

$totalAccesorios = $pdo->query("
    SELECT COUNT(*)
    FROM accesorios
")->fetchColumn();

$stockAccesorios = $pdo->query("
    SELECT ISNULL(SUM(stock),0)
    FROM accesorios
")->fetchColumn();

$valorAccesorios = $pdo->query("
    SELECT ISNULL(SUM(precio * stock),0)
    FROM accesorios
")->fetchColumn();

/* =========================================================
   TOTALES GLOBALES
========================================================= */

$totalProductos =
    (int)$totalEquipos +
    (int)$stockAccesorios;

$valorGlobal =
    (float)$valorEquipos +
    (float)$valorAccesorios;

/* =========================================================
   EQUIPOS POR MARCA
========================================================= */

$marcasEquipos = $pdo->query("
    SELECT
        equipo_marca,
        COUNT(*) total,
        ISNULL(SUM(precio),0) valor
    FROM productos_informatica
    GROUP BY equipo_marca
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   ACCESORIOS POR CATEGORIA
========================================================= */

$categoriasAccesorios = $pdo->query("
    SELECT
        categoria,
        SUM(stock) stock_total,
        ISNULL(SUM(precio * stock),0) valor_total
    FROM accesorios
    GROUP BY categoria
    ORDER BY stock_total DESC
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Dashboard General</title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="shortcut icon" href="/img/favicon.ico">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

body{

    background:
    linear-gradient(
        180deg,
        #eff6ff 0%,
        #f8fafc 40%,
        #ffffff 100%
    );

    font-family:Arial,sans-serif;
}

.card{

    background:white;

    border-radius:32px;

    border:1px solid #e2e8f0;

    padding:28px;

    box-shadow:
    0 10px 30px rgba(15,23,42,.05);

    transition:.25s;
}

.card:hover{

    transform:translateY(-4px);
}

</style>

</head>

<body class="p-4 md:p-8">

<div class="max-w-[1800px] mx-auto">

    <!-- HEADER -->

    <div class="flex flex-col lg:flex-row
    justify-between items-center gap-5 mb-10">

        <div class="flex items-center gap-5">

            <img src="../img/logo.webp"
            class="h-16">

            <div>

                <h1 class="text-5xl font-black text-slate-900">

                    Dashboard Global

                </h1>

                <p class="text-slate-500 mt-2 text-lg">

                    Equipos + Accesorios + Inventario General

                </p>

            </div>

        </div>

        <a href="../mantenimiento"
        class="bg-blue-700 hover:bg-blue-800
        text-white px-7 py-4 rounded-2xl font-black shadow-xl">

            <i class="fa-solid fa-arrow-left mr-2"></i>

            Volver

        </a>

    </div>

    <!-- KPI SUPERIOR -->

    <div class="grid grid-cols-1
    md:grid-cols-2
    xl:grid-cols-4
    gap-6 mb-8">

        <!-- TOTAL GLOBAL -->

        <div class="card">

            <div class="flex justify-between items-center">

                <div>

                    <p class="text-slate-400 uppercase
                    text-sm font-black">

                        Productos Totales

                    </p>

                    <h2 class="text-6xl font-black
                    text-slate-900 mt-4">

                        <?= number_format($totalProductos) ?>

                    </h2>

                </div>

                <div class="w-24 h-24 rounded-3xl
                bg-blue-100 text-blue-700
                flex items-center justify-center">

                    <i class="fa-solid fa-boxes-stacked text-5xl"></i>

                </div>

            </div>

        </div>

        <!-- EQUIPOS -->

        <div class="card">

            <div class="flex justify-between items-center">

                <div>

                    <p class="text-slate-400 uppercase
                    text-sm font-black">

                        Equipos

                    </p>

                    <h2 class="text-6xl font-black
                    text-cyan-700 mt-4">

                        <?= number_format($totalEquipos) ?>

                    </h2>

                    <p class="mt-3 text-cyan-700 font-black">

                        RD$
                        <?= number_format($valorEquipos,0) ?>

                    </p>

                </div>

                <div class="w-24 h-24 rounded-3xl
                bg-cyan-100 text-cyan-700
                flex items-center justify-center">

                    <i class="fa-solid fa-laptop text-5xl"></i>

                </div>

            </div>

        </div>

        <!-- ACCESORIOS -->

        <div class="card">

            <div class="flex justify-between items-center">

                <div>

                    <p class="text-slate-400 uppercase
                    text-sm font-black">

                        Stock Accesorios

                    </p>

                    <h2 class="text-6xl font-black
                    text-purple-700 mt-4">

                        <?= number_format($stockAccesorios) ?>

                    </h2>

                    <p class="mt-3 text-purple-700 font-black">

                        RD$
                        <?= number_format($valorAccesorios,0) ?>

                    </p>

                </div>

                <div class="w-24 h-24 rounded-3xl
                bg-purple-100 text-purple-700
                flex items-center justify-center">

                    <i class="fa-solid fa-keyboard text-5xl"></i>

                </div>

            </div>

        </div>

        <!-- VALOR GLOBAL -->

        <div class="card bg-gradient-to-br
        from-blue-700
        to-indigo-800
        text-white">

            <p class="uppercase text-sm font-black opacity-80">

                Valor Global

            </p>

            <h2 class="text-5xl font-black mt-5">

                RD$
                <?= number_format($valorGlobal,0) ?>

            </h2>

            <p class="mt-4 opacity-70">

                Inventario completo

            </p>

        </div>

    </div>

    <!-- RESUMEN ESTADOS -->

    <div class="grid grid-cols-1
    md:grid-cols-2
    gap-6 mb-8">

        <div class="card">

            <h2 class="text-2xl font-black
            text-slate-900 mb-6">

                Estado Equipos

            </h2>

            <div class="space-y-5">

                <div class="flex justify-between">

                    <span class="font-bold text-slate-700">
                        Disponibles
                    </span>

                    <span class="font-black text-green-600">
                        <?= number_format($equiposDisponibles) ?>
                    </span>

                </div>

                <div class="flex justify-between">

                    <span class="font-bold text-slate-700">
                        Vendidos
                    </span>

                    <span class="font-black text-red-600">
                        <?= number_format($equiposVendidos) ?>
                    </span>

                </div>

            </div>

        </div>

        <div class="card">

            <h2 class="text-2xl font-black
            text-slate-900 mb-6">

                Resumen General

            </h2>

            <div class="space-y-5">

                <div class="flex justify-between">

                    <span class="font-bold text-slate-700">
                        Total Equipos
                    </span>

                    <span class="font-black text-cyan-700">
                        <?= number_format($totalEquipos) ?>
                    </span>

                </div>

                <div class="flex justify-between">

                    <span class="font-bold text-slate-700">
                        Total Stock Accesorios
                    </span>

                    <span class="font-black text-purple-700">
                        <?= number_format($stockAccesorios) ?>
                    </span>

                </div>

                <div class="flex justify-between">

                    <span class="font-bold text-slate-700">
                        Valor Inventario
                    </span>

                    <span class="font-black text-blue-700">
                        RD$ <?= number_format($valorGlobal,0) ?>
                    </span>

                </div>

            </div>

        </div>

    </div>

    <!-- TABLAS -->

    <div class="grid grid-cols-1
    xl:grid-cols-2
    gap-8">

        <!-- EQUIPOS -->

        <div class="card">

            <h2 class="text-3xl font-black
            text-slate-900 mb-6">

                Equipos por Marca

            </h2>

            <div class="overflow-x-auto">

                <table class="w-full">

                    <thead>

                        <tr class="border-b border-slate-200">

                            <th class="text-left p-4">
                                Marca
                            </th>

                            <th class="text-left p-4">
                                Cantidad
                            </th>

                            <th class="text-left p-4">
                                Valor
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach($marcasEquipos as $m): ?>

                            <tr class="border-b border-slate-100">

                                <td class="p-4 font-bold">

                                    <?= $m['equipo_marca'] ?>

                                </td>

                                <td class="p-4">

                                    <?= number_format($m['total']) ?>

                                </td>

                                <td class="p-4 font-black text-cyan-700">

                                    RD$
                                    <?= number_format($m['valor'],0) ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

        <!-- ACCESORIOS -->

        <div class="card">

            <h2 class="text-3xl font-black
            text-slate-900 mb-6">

                Accesorios por Categoría

            </h2>

            <div class="overflow-x-auto">

                <table class="w-full">

                    <thead>

                        <tr class="border-b border-slate-200">

                            <th class="text-left p-4">
                                Categoría
                            </th>

                            <th class="text-left p-4">
                                Stock
                            </th>

                            <th class="text-left p-4">
                                Valor
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach($categoriasAccesorios as $c): ?>

                            <tr class="border-b border-slate-100">

                                <td class="p-4 font-bold">

                                    <?= $c['categoria'] ?>

                                </td>

                                <td class="p-4 font-black">

                                    <?= number_format($c['stock_total']) ?>

                                </td>

                                <td class="p-4 font-black text-purple-700">

                                    RD$
                                    <?= number_format($c['valor_total'],0) ?>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

</body>
</html>