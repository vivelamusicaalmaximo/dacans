<?php

session_start();

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
   ESTADISTICAS
====================================================== */

// TOTAL ACCESORIOS
$totalAccesorios = $pdo->query("
    SELECT COUNT(*) 
    FROM accesorios
")->fetchColumn();

// TOTAL STOCK
$totalStock = $pdo->query("
    SELECT ISNULL(SUM(stock),0)
    FROM accesorios
")->fetchColumn();

// VALOR INVENTARIO
$valorInventario = $pdo->query("
    SELECT ISNULL(SUM(precio * stock),0)
    FROM accesorios
")->fetchColumn();

// ACTIVOS
$totalActivos = $pdo->query("
    SELECT COUNT(*)
    FROM accesorios
    WHERE estado = 'Activo'
")->fetchColumn();

// AGOTADOS
$totalAgotados = $pdo->query("
    SELECT COUNT(*)
    FROM accesorios
    WHERE estado = 'Agotado'
")->fetchColumn();

/* ======================================================
   CATEGORIAS
====================================================== */

$stmtCategorias = $pdo->query("
    SELECT 
        categoria,
        COUNT(*) total
    FROM accesorios
    GROUP BY categoria
    ORDER BY total DESC
");

$categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);

/* ======================================================
   MARCAS
====================================================== */

$stmtMarcas = $pdo->query("
    SELECT TOP 10
        marca,
        COUNT(*) total
    FROM accesorios
    GROUP BY marca
    ORDER BY total DESC
");

$marcas = $stmtMarcas->fetchAll(PDO::FETCH_ASSOC);

/* ======================================================
   STOCK BAJO
====================================================== */

$stmtBajo = $pdo->query("
    SELECT *
    FROM accesorios
    WHERE stock <= 3
    ORDER BY stock ASC
");

/* ======================================================
   TOTAL POR PRODUCTO / CATEGORIA
====================================================== */

$stmtTotales = $pdo->query("
    SELECT 
        categoria,
        COUNT(*) as total_productos,
        ISNULL(SUM(stock),0) as total_stock,
        ISNULL(SUM(precio * stock),0) as valor_total
    FROM accesorios
    GROUP BY categoria
    ORDER BY total_productos DESC
");

$totalesProductos = $stmtTotales->fetchAll(PDO::FETCH_ASSOC);

/* ======================================================
   RESULTADOS
====================================================== */

$stockBajo = $stmtBajo->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Estadísticas Accesorios | DACANS</title>

    <link rel="shortcut icon" href="/img/favicon.ico">

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>

        body{

            background:
                radial-gradient(circle at top left, rgba(37,99,235,.08), transparent 30%),
                radial-gradient(circle at bottom right, rgba(14,165,233,.08), transparent 30%),
                #f8fafc;

            font-family: Arial, sans-serif;
        }

        .card{

            background:white;
            border-radius:30px;
            border:1px solid #e2e8f0;
            box-shadow:0 10px 25px rgba(0,0,0,.06);
        }

        .stat-card{

            background:linear-gradient(135deg,#0f172a,#1e3a8a);
            color:white;
            border-radius:28px;
            padding:24px;
            position:relative;
            overflow:hidden;
        }

        .stat-card::after{

            content:'';
            position:absolute;
            width:140px;
            height:140px;
            background:rgba(255,255,255,.06);
            border-radius:999px;
            top:-40px;
            right:-40px;
        }

    </style>

</head>

<body class="p-4 md:p-8">

<div class="max-w-[1700px] mx-auto">

    <!-- HEADER -->

    <div class="flex flex-col lg:flex-row justify-between gap-4 items-center mb-8">

        <div class="flex items-center gap-4">

            <div class="bg-white p-3 rounded-2xl shadow-lg border border-slate-200">

                <img src="../img/logo.webp"
                    class="h-12 object-contain">

            </div>

            <div>

                <h1 class="text-3xl md:text-4xl font-black text-slate-900">
                    ESTADÍSTICAS ACCESORIOS
                </h1>

                <p class="text-slate-500">
                    Panel general del inventario de accesorios
                </p>

            </div>

        </div>

        <div class="flex gap-3 flex-wrap">

            <a href="index.php"
                class="bg-slate-900 hover:bg-black text-white px-6 py-3 rounded-2xl font-black shadow-lg transition">

                <i class="fa-solid fa-arrow-left mr-2"></i>

                Volver

            </a>

        </div>

    </div>

    <!-- CARDS -->

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-5 mb-8">

        <!-- TOTAL -->

        <div class="stat-card">

            <div class="flex justify-between items-start">

                <div>

                    <p class="text-sm text-blue-100 uppercase font-bold">
                        Total Accesorios
                    </p>

                    <h2 class="text-4xl font-black mt-3">
                        <?= number_format($totalAccesorios) ?>
                    </h2>

                </div>

                <i class="fa-solid fa-box text-4xl text-white/80"></i>

            </div>

        </div>

        <!-- STOCK -->

        <div class="stat-card">

            <div class="flex justify-between items-start">

                <div>

                    <p class="text-sm text-blue-100 uppercase font-bold">
                        Stock Total
                    </p>

                    <h2 class="text-4xl font-black mt-3">
                        <?= number_format($totalStock) ?>
                    </h2>

                </div>

                <i class="fa-solid fa-layer-group text-4xl text-white/80"></i>

            </div>

        </div>

        <!-- INVENTARIO -->

        <div class="stat-card">

            <div class="flex justify-between items-start">

                <div>

                    <p class="text-sm text-blue-100 uppercase font-bold">
                        Valor Inventario
                    </p>

                    <h2 class="text-3xl font-black mt-3">
                        RD$ <?= number_format($valorInventario,0) ?>
                    </h2>

                </div>

                <i class="fa-solid fa-money-bill-wave text-4xl text-white/80"></i>

            </div>

        </div>

        <!-- ACTIVOS -->

        <div class="stat-card">

            <div class="flex justify-between items-start">

                <div>

                    <p class="text-sm text-blue-100 uppercase font-bold">
                        Activos
                    </p>

                    <h2 class="text-4xl font-black mt-3">
                        <?= number_format($totalActivos) ?>
                    </h2>

                </div>

                <i class="fa-solid fa-circle-check text-4xl text-white/80"></i>

            </div>

        </div>

        <!-- AGOTADOS -->

        <div class="stat-card">

            <div class="flex justify-between items-start">

                <div>

                    <p class="text-sm text-blue-100 uppercase font-bold">
                        Agotados
                    </p>

                    <h2 class="text-4xl font-black mt-3">
                        <?= number_format($totalAgotados) ?>
                    </h2>

                </div>

                <i class="fa-solid fa-triangle-exclamation text-4xl text-white/80"></i>

            </div>

        </div>

    </div>

    <!-- GRAFICAS -->

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">

        <!-- CATEGORIAS -->

        <div class="card p-6">

            <h2 class="text-2xl font-black text-slate-900 mb-6">
                Categorías
            </h2>

            <canvas id="chartCategorias"></canvas>

        </div>

        <!-- MARCAS -->

        <div class="card p-6">

            <h2 class="text-2xl font-black text-slate-900 mb-6">
                Marcas
            </h2>

            <canvas id="chartMarcas"></canvas>

        </div>

    </div>
    <!-- TOTAL POR PRODUCTO -->

<div class="card p-6 mt-8">

    <div class="flex justify-between items-center mb-6">

        <div>

            <h2 class="text-2xl font-black text-slate-900">
                Totales por Tipo de Producto
            </h2>

            <p class="text-slate-500 text-sm">
                Resumen individual por categoría
            </p>

        </div>

    </div>

    <div class="overflow-x-auto">

        <table class="w-full">

            <thead>

                <tr class="border-b border-slate-200">

                    <th class="text-left p-4 font-black">
                        Categoría
                    </th>

                    <th class="text-left p-4 font-black">
                        Productos
                    </th>

                    <th class="text-left p-4 font-black">
                        Stock Total
                    </th>

                    <th class="text-left p-4 font-black">
                        Valor Inventario
                    </th>

                </tr>

            </thead>

            <tbody>

                <?php foreach($totalesProductos as $t): ?>

                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition">

                        <!-- CATEGORIA -->

                        <td class="p-4 font-bold text-slate-900">

                            <?= $t['categoria'] ?: 'Sin categoría' ?>

                        </td>

                        <!-- PRODUCTOS -->

                        <td class="p-4">

                            <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-black">

                                <?= number_format($t['total_productos']) ?>

                            </span>

                        </td>

                        <!-- STOCK -->

                        <td class="p-4">

                            <?= number_format($t['total_stock']) ?>

                        </td>

                        <!-- VALOR -->

                        <td class="p-4 font-black text-green-700">

                            RD$ <?= number_format($t['valor_total'],0) ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>

    <!-- STOCK BAJO -->

    <div class="card p-6">

        <div class="flex justify-between items-center mb-6">

            <h2 class="text-2xl font-black text-slate-900">
                Stock Bajo
            </h2>

            <div class="bg-red-100 text-red-700 px-4 py-2 rounded-2xl font-bold text-sm">

                <?= count($stockBajo) ?> Productos

            </div>

        </div>

        <div class="overflow-x-auto">

            <table class="w-full">

                <thead>

                    <tr class="border-b border-slate-200">

                        <th class="text-left p-3">Producto</th>
                        <th class="text-left p-3">Categoría</th>
                        <th class="text-left p-3">Marca</th>
                        <th class="text-left p-3">Stock</th>
                        <th class="text-left p-3">Precio</th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach($stockBajo as $s): ?>

                        <tr class="border-b border-slate-100 hover:bg-slate-50 transition">

                            <td class="p-3 font-bold">

                                <?= $s['nombre'] ?>

                            </td>

                            <td class="p-3">

                                <?= $s['categoria'] ?>

                            </td>

                            <td class="p-3">

                                <?= $s['marca'] ?>

                            </td>

                            <td class="p-3">

                                <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs font-black">

                                    <?= $s['stock'] ?>

                                </span>

                            </td>

                            <td class="p-3 font-black text-blue-700">

                                RD$ <?= number_format($s['precio'],0) ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<script>

    // CATEGORIAS

    const categoriasLabels = <?= json_encode(array_column($categorias,'categoria')) ?>;

    const categoriasData = <?= json_encode(array_column($categorias,'total')) ?>;

    new Chart(document.getElementById('chartCategorias'), {

        type: 'doughnut',

        data: {

            labels: categoriasLabels,

            datasets: [{

                data: categoriasData

            }]

        },

        options: {

            responsive:true,

            plugins: {

                legend: {

                    position:'bottom'
                }
            }
        }

    });

    // MARCAS

    const marcasLabels = <?= json_encode(array_column($marcas,'marca')) ?>;

    const marcasData = <?= json_encode(array_column($marcas,'total')) ?>;

    new Chart(document.getElementById('chartMarcas'), {

        type: 'bar',

        data: {

            labels: marcasLabels,

            datasets: [{

                data: marcasData

            }]

        },

        options: {

            responsive:true,

            plugins: {

                legend: {

                    display:false
                }
            },

            scales: {

                y: {

                    beginAtZero:true
                }
            }
        }

    });

</script>

</body>
</html>