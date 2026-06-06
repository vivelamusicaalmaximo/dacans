<?php
session_start();

if (!isset($_SESSION['admin_logueado'])) {

    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {

    die("ID no válido.");
}

$id = (int) $_GET['id'];

/* =========================================================
   CONEXION SQL SERVER
========================================================= */

require_once '../config/conexion.php';

/* =========================================================
   OBTENER ARTICULO
========================================================= */

$stmt = $pdo->prepare("
    SELECT *
    FROM compras_articulos
    WHERE id = ?
");

$stmt->execute([$id]);

$articulo = $stmt->fetch(PDO::FETCH_ASSOC);

/* =========================================================
   CALCULOS
========================================================= */

$costoUnitario = (float)($articulo['costo_unitario'] ?? 0);

$porcentaje = (float)($articulo['porcentaje_incremento'] ?? 0);

$cantidad = (int)($articulo['cantidad_articulos'] ?? 0);

/* PRECIO SUGERIDO */

$precioSugerido =
    $costoUnitario +
    ($costoUnitario * ($porcentaje / 100));

/* GANANCIA POR ITEM */

$gananciaPorItem =
    $precioSugerido - $costoUnitario;

/* GANANCIA TOTAL */

$gananciaPorLote =
    $gananciaPorItem * $cantidad;
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Ver Artículo</title>

<link rel="shortcut icon" href="/img/favicon.ico">

<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

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

    border-radius:32px;

    border:1px solid #e2e8f0;

    box-shadow:
        0 10px 30px rgba(15,23,42,.05),
        0 2px 10px rgba(15,23,42,.03);
}

.info-box{

    background:#f8fafc;

    border:1px solid #e2e8f0;

    border-radius:24px;

    padding:20px;
}

.label{

    font-size:12px;

    text-transform:uppercase;

    color:#64748b;

    font-weight:700;

    margin-bottom:8px;
}

.value{

    font-size:18px;

    font-weight:800;

    color:#0f172a;

    word-break:break-word;
}

</style>

</head>

<body class="p-4 md:p-8">

<div class="max-w-7xl mx-auto">

    <!-- HEADER -->

    <div class="flex flex-col lg:flex-row
    justify-between items-center gap-5 mb-8">

        <div class="flex items-center gap-4">

            <img src="../img/logo.webp"
            class="h-20 bg-white p-3 rounded-3xl shadow-lg border border-slate-200">

            <div>

                <h1 class="text-4xl font-black text-slate-900">
                    Detalle del Artículo
                </h1>

                <p class="text-slate-500 mt-1">
                    Información completa de la compra
                </p>

            </div>

        </div>

        <div class="flex flex-wrap gap-3">

            <!-- EDITAR -->

            <a href="edit.php?id=<?= $articulo['id'] ?>"
            class="bg-blue-700 hover:bg-blue-800
            text-white px-6 py-4 rounded-2xl
            font-black shadow-lg transition">

                <i class="fa-solid fa-pen-to-square mr-2"></i>

                Editar

            </a>

            <!-- VOLVER -->

            <a href="index.php"
            class="bg-slate-900 hover:bg-black
            text-white px-6 py-4 rounded-2xl
            font-black shadow-lg transition">

                <i class="fa-solid fa-arrow-left mr-2"></i>

                Volver

            </a>

        </div>

    </div>

    <!-- CARD PRINCIPAL -->

    <div class="card p-8">

        <!-- TITULO -->

        <div class="flex flex-col xl:flex-row
        justify-between gap-5 mb-8">

            <div>

                <p class="text-sm text-slate-500 font-bold uppercase">
                    Artículo
                </p>

                <h2 class="text-4xl font-black text-slate-900 mt-2">

                    <?= htmlspecialchars($articulo['nombre_articulo']) ?>

                </h2>

            </div>

            <div>

                <span class="px-5 py-3 rounded-full text-sm font-black

                <?=
                    $articulo['status_compra'] === 'Disponible'
                    ? 'bg-green-100 text-green-700'
                    : (
                        $articulo['status_compra'] === 'Cancelado'
                        ? 'bg-red-100 text-red-700'
                        : 'bg-blue-100 text-blue-700'
                    )
                ?>">

                    <?= htmlspecialchars($articulo['status_compra']) ?>

                </span>

            </div>

        </div>

        <!-- GRID -->

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-5">

            <div class="info-box">

                <div class="label">
                    ID Registro
                </div>

                <div class="value">
                    #<?= $articulo['id'] ?>
                </div>

            </div>

            <div class="info-box">

                <div class="label">
                    Item ID
                </div>

                <div class="value">
                    <?= htmlspecialchars($articulo['item_id']) ?>
                </div>

            </div>

            <div class="info-box">

                <div class="label">
                    Cantidad
                </div>

                <div class="value">
                    <?= number_format($articulo['cantidad_articulos']) ?>
                </div>

            </div>

            <div class="info-box">

                <div class="label">
                    Costo USD
                </div>

                <div class="value text-green-700">
                    $<?= number_format($articulo['costo_usd'], 2) ?>
                </div>

            </div>

            <div class="info-box">

                <div class="label">
                    Costo DOP
                </div>

                <div class="value">
                    RD$<?= number_format($articulo['costo_dop'], 2) ?>
                </div>

            </div>

            <div class="info-box">

                <div class="label">
                    Impuestos
                </div>

                <div class="value">
                    RD$<?= number_format($articulo['costo_impuestos'], 2) ?>
                </div>

            </div>

            <div class="info-box">

                <div class="label">
                    Envío
                </div>

                <div class="value">
                    RD$<?= number_format($articulo['costo_envio'], 2) ?>
                </div>

            </div>

            <div class="info-box">

                <div class="label">
                    Costo Unitario
                </div>

                <div class="value text-cyan-700">
                    RD$<?= number_format($articulo['costo_unitario'], 2) ?>
                </div>

            </div>

            <div class="info-box">

                <div class="label">
                    % Incremento
                </div>

                <div class="value">
                    <?= number_format($articulo['porcentaje_incremento'], 2) ?>%
                </div>

            </div>

            <div class="info-box">

                <div class="label">
                    Precio Sugerido
                </div>

                <div class="value text-green-700">
                 RD$<?= number_format($precioSugerido, 2) ?>
                </div>

            </div>

            <div class="info-box">

                <div class="label">
                    Ganancia por Item
                </div>

                <div class="value text-purple-700">
                 RD$<?= number_format($gananciaPorItem, 2) ?>
                </div>

            </div>

            <div class="info-box">

                <div class="label">
                    Ganancia por Lote
                </div>

                <div class="value text-emerald-700">
                    RD$<?= number_format($gananciaPorLote, 2) ?>
                </div>

            </div>

            <div class="info-box">

                <div class="label">
                    Rastreo US
                </div>

                <div class="value">
                    <?= htmlspecialchars($articulo['numero_rastreo_us']) ?>
                </div>

            </div>

            <div class="info-box">

                <div class="label">
                    Courier
                </div>

                <div class="value">
                    <?= htmlspecialchars($articulo['id_courier']) ?>
                </div>

            </div>

            <div class="info-box">

                <div class="label">
                    Dirección
                </div>

                <div class="value">
                    <?= htmlspecialchars($articulo['direccion_usada']) ?>
                </div>

            </div>

            <div class="info-box">

                <div class="label">
                    Fecha Registro
                </div>

                <div class="value">
                    <?= htmlspecialchars($articulo['created_at'] ?? 'N/D') ?>
                </div>

            </div>

        </div>

    </div>

</div>

</body>
</html>