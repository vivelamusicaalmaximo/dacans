<?php

session_start();

if (!isset($_SESSION['admin_logueado'])) {

    header("Location: ../login.php");
    exit;
}

/* =========================================================
   CONEXION SQL SERVER
========================================================= */

require_once '../config/conexion.php';

/* =========================================================
   USUARIO LOGUEADO
========================================================= */

$usuarioSesion = $_SESSION['usuario'] ?? '';

/* =========================================================
   BUSCAR PORCENTAJE DEL USUARIO
========================================================= */

$stmtUsuario = $pdo->prepare("
    SELECT TOP 1
        usuario,
        rol,
        porcent_ganancias
    FROM usuarios
    WHERE usuario = ?
");

$stmtUsuario->execute([$usuarioSesion]);

$usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {

    die("Usuario no encontrado");
}

$porcentajeUsuario = (float)($usuario['porcent_ganancias'] ?? 0);

/* =========================================================
   PERIODO ACTUAL
========================================================= */

$diaActual = date('d');

$anioActual = date('Y');
$mesActual  = date('m');

if ($diaActual <= 15) {

    $nombrePeriodo = "Primera Quincena";

    $fechaInicio = "$anioActual-$mesActual-01";
    $fechaFin    = "$anioActual-$mesActual-15";

} else {

    $nombrePeriodo = "Segunda Quincena";

    $fechaInicio = "$anioActual-$mesActual-16";
    $fechaFin    = date('Y-m-d');
}

/* =========================================================
   TOTAL VENDIDO EN LA QUINCENA
========================================================= */

$stmtPeriodo = $pdo->prepare("
    SELECT ISNULL(SUM(precio),0)
    FROM productos_informatica
    WHERE estado = 'Vendida'
    AND vendida_at IS NOT NULL
    AND CONVERT(date, vendida_at)
        BETWEEN ? AND ?
");

$stmtPeriodo->execute([

    $fechaInicio,
    $fechaFin

]);

$totalPeriodo = (float)($stmtPeriodo->fetchColumn() ?? 0);

/* =========================================================
   CALCULO GANANCIA USUARIO
========================================================= */

$gananciaUsuario = $totalPeriodo * ($porcentajeUsuario / 100);

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Mi Porcentaje de Ganancias</title>

<script src="https://cdn.tailwindcss.com"></script>

<link rel="shortcut icon" href="/img/favicon.ico">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

body{
    background:
    linear-gradient(
        135deg,
        #020617,
        #0f172a,
        #111827
    );
}

.glass{
    background:rgba(255,255,255,.08);
    backdrop-filter:blur(20px);
    border:1px solid rgba(255,255,255,.1);
}

</style>

</head>

<body class="min-h-screen p-6 text-white">

<div class="max-w-5xl mx-auto">

    <!-- HEADER -->

    <div class="flex flex-col lg:flex-row
    justify-between items-center gap-5 mb-10">

        <div>

            <h1 class="text-4xl md:text-5xl font-black">

                Mi Ganancia Quincenal

            </h1>

            <p class="text-slate-300 mt-3">

                Resumen personal de ganancias del periodo actual.

            </p>

        </div>

        <a href="../mantenimiento/"
        class="bg-blue-600 hover:bg-blue-700
        px-6 py-4 rounded-2xl font-black transition">

            <i class="fa-solid fa-arrow-left mr-2"></i>
            Volver

        </a>

    </div>

    <!-- CARD PRINCIPAL -->

    <div class="glass rounded-[2rem] p-8 shadow-2xl">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

            <!-- USUARIO -->

            <div class="bg-black/20 rounded-3xl p-8">

                <div class="w-20 h-20 rounded-3xl
                bg-blue-500/20 text-blue-400
                flex items-center justify-center text-4xl mb-6">

                    <i class="fa-solid fa-user"></i>

                </div>

                <p class="text-slate-400 uppercase text-sm font-bold">
                    Usuario
                </p>

                <h2 class="text-4xl font-black mt-3">

                    <?= htmlspecialchars($usuario['usuario']) ?>

                </h2>

                <p class="mt-4 text-slate-300">

                    Rol:
                    <span class="font-black text-cyan-400">

                        <?= htmlspecialchars($usuario['rol']) ?>

                    </span>

                </p>

            </div>

            <!-- GANANCIA -->

            <div class="bg-black/20 rounded-3xl p-8">

                <div class="w-20 h-20 rounded-3xl
                bg-emerald-500/20 text-emerald-400
                flex items-center justify-center text-4xl mb-6">

                    <i class="fa-solid fa-money-bill-trend-up"></i>

                </div>

                <p class="text-slate-400 uppercase text-sm font-bold">
                    Mi Ganancia
                </p>

                <h2 class="text-5xl font-black text-emerald-400 mt-4">

                    RD$
                    <?= number_format($gananciaUsuario, 2) ?>

                </h2>

                <p class="mt-5 text-slate-300">

                    <?= $nombrePeriodo ?>

                </p>

            </div>

        </div>

    </div>

    <!-- DETALLES -->

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-8">

        <!-- TOTAL PERIODO -->

        <div class="glass rounded-3xl p-7">

            <p class="text-slate-400 uppercase text-sm font-bold">
                Total Vendido
            </p>

            <h2 class="text-4xl font-black text-white mt-4">

                RD$
                <?= number_format($totalPeriodo, 0) ?>

            </h2>

        </div>

        <!-- PORCENTAJE -->

        <div class="glass rounded-3xl p-7">

            <p class="text-slate-400 uppercase text-sm font-bold">
                Mi %
            </p>

            <h2 class="text-4xl font-black text-cyan-400 mt-4">

                <?= number_format($porcentajeUsuario, 2) ?>%

            </h2>

        </div>

        <!-- FECHAS -->

        <div class="glass rounded-3xl p-7">

            <p class="text-slate-400 uppercase text-sm font-bold">
                Periodo
            </p>

            <h2 class="text-xl font-black text-white mt-4">

                <?= $fechaInicio ?>

            </h2>

            <p class="text-slate-400 mt-2">
                hasta
            </p>

            <h2 class="text-xl font-black text-white mt-2">

                <?= $fechaFin ?>

            </h2>

        </div>

    </div>

</div>

</body>
</html>