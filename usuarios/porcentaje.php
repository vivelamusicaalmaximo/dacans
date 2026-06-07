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
   BUSCAR PORCENTAJE Y REDUCCIÓN DEL USUARIO
========================================================= */
$stmtUsuario = $pdo->prepare("
    SELECT TOP 1
        usuario,
        rol,
        porcent_ganancias,
        reduccion_porcentaje 
    FROM usuarios
    WHERE usuario = ?
");

$stmtUsuario->execute([$usuarioSesion]);
$usuario = $stmtUsuario->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    die("Usuario no encontrado");
}

$porcentajeUsuario  = (float)($usuario['porcent_ganancias'] ?? 0);
$reduccionUsuario   = (float)($usuario['reduccion_porcentaje'] ?? 0); 

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
    $fechaFin    = date('Y-m-t'); // Calcula automáticamente el último día del mes (30 o 31)
}

/* =========================================================
   TOTAL VENDIDO Y CÁLCULO DE GANANCIA PRODUCTO POR PRODUCTO (CORREGIDO CAST)
========================================================= */
$stmtPeriodo = $pdo->prepare("
    SELECT 
        ISNULL(SUM(precio), 0) AS SumaTotal,
        ISNULL(
            SUM(
                CASE 
                    WHEN precio >= 40000 THEN precio * ((CAST(? AS DECIMAL(5,2)) - CAST(? AS DECIMAL(5,2))) / 100.0)
                    ELSE precio * (CAST(? AS DECIMAL(5,2)) / 100.0)
                END
            ), 0
        ) AS GananciaCalculada,
        SUM(CASE WHEN precio >= 40000 THEN 1 ELSE 0 END) AS CantidadReducidos
    FROM productos_informatica
    WHERE estado = 'Vendida'
    AND vendida_at IS NOT NULL
    AND CONVERT(date, vendida_at) BETWEEN ? AND ?
");

$stmtPeriodo->execute([
    $porcentajeUsuario,   
    $reduccionUsuario,    
    $porcentajeUsuario,   
    $fechaInicio,
    $fechaFin
]);

$resultadoVentas = $stmtPeriodo->fetch(PDO::FETCH_ASSOC);

$totalPeriodo      = (float)($resultadoVentas['SumaTotal'] ?? 0);
$gananciaUsuario   = (float)($resultadoVentas['GananciaCalculada'] ?? 0);
$cantidadReducidos = (int)($resultadoVentas['CantidadReducidos'] ?? 0);

// Determinamos si se aplicó alguna deducción en el periodo para avisar en la interfaz
$huboReduccion = ($cantidadReducidos > 0);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Porcentaje de Ganancias</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="shortcut icon" href="/img/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
    body {
        background: linear-gradient(135deg, #020617, #0f172a, #111827);
    }

    .glass {
        background: rgba(255, 255, 255, .08);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, .1);
    }
    </style>
</head>

<body class="min-h-screen p-6 text-white">

    <div class="max-w-5xl mx-auto">

        <div class="flex flex-col lg:flex-row justify-between items-center gap-5 mb-10">
            <div>
                <h1 class="text-4xl md:text-5xl font-black">Mi Ganancia Quincenal</h1>
                <p class="text-slate-300 mt-3">Resumen personal de ganancias calculadas producto por producto.</p>
            </div>
            <a href="../mantenimiento/"
                class="bg-blue-600 hover:bg-blue-700 px-6 py-4 rounded-2xl font-black transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Volver
            </a>
        </div>

        <?php if ($huboReduccion): ?>
        <div
            class="bg-amber-500/10 border border-amber-500/20 text-amber-400 p-4 rounded-2xl mb-6 flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-xl"></i>
            <p class="text-sm">
                <strong>Deducción aplicada:</strong> En esta quincena se registraron
                <strong><?= $cantidadReducidos ?></strong> producto(s) con valor igual o mayor a RD$ 40,000. A esos
                artículos específicos se les aplicó la deducción de
                <strong>-<?= number_format($reduccionUsuario, 2) ?>%</strong> en tu comisión.
            </p>
        </div>
        <?php else: ?>
        <div
            class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 p-4 rounded-2xl mb-6 flex items-center gap-3">
            <i class="fa-solid fa-circle-check text-xl"></i>
            <p class="text-sm">
                <strong>Comisiones completas:</strong> Todos los productos vendidos en este periodo son menores a RD$
                40,000. Conservas el 100% de tu porcentaje base en cada uno.
            </p>
        </div>
        <?php endif; ?>

        <div class="glass rounded-[2rem] p-8 shadow-2xl">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                <div class="bg-black/20 rounded-3xl p-8">
                    <div
                        class="w-20 h-20 rounded-3xl bg-blue-500/20 text-blue-400 flex items-center justify-center text-4xl mb-6">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <p class="text-slate-400 uppercase text-sm font-bold">Usuario</p>
                    <h2 class="text-4xl font-black mt-3"><?= htmlspecialchars($usuario['usuario']) ?></h2>
                    <p class="mt-4 text-slate-300">Rol: <span
                            class="font-black text-cyan-400"><?= htmlspecialchars($usuario['rol']) ?></span></p>
                </div>

                <div class="bg-black/20 rounded-3xl p-8">
                    <div
                        class="w-20 h-20 rounded-3xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center text-4xl mb-6">
                        <i class="fa-solid fa-money-bill-trend-up"></i>
                    </div>
                    <p class="text-slate-400 uppercase text-sm font-bold">Mi Ganancia Personal Acumulada</p>
                    <h2 class="text-5xl font-black text-emerald-400 mt-4">
                        RD$ <?= number_format($gananciaUsuario, 2) ?>
                    </h2>
                    <p class="mt-5 text-slate-300"><?= $nombrePeriodo ?></p>
                </div>

            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mt-8">

            <div class="glass rounded-3xl p-7">
                <p class="text-slate-400 uppercase text-sm font-bold">Total Vendido General</p>
                <h2 class="text-4xl font-black text-white mt-4">
                    RD$ <?= number_format($totalPeriodo, 0) ?>
                </h2>
            </div>

            <div class="glass rounded-3xl p-7">
                <p class="text-slate-400 uppercase text-sm font-bold">Mis Ajustes de Comisión</p>
                <h2 class="text-2xl font-black text-cyan-400 mt-4">
                    <?= number_format($porcentajeUsuario, 2) ?>% <span
                        class="text-xs text-slate-400 font-normal">Base</span>
                </h2>
                <?php if ($reduccionUsuario > 0): ?>
                <p class="text-xs text-amber-400 font-bold mt-1">
                    -<?= number_format($reduccionUsuario, 2) ?>% <span class="text-slate-400 font-normal">en productos
                        de RD$ 40k+</span>
                </p>
                <?php endif; ?>
            </div>

            <div class="glass rounded-3xl p-7">
                <p class="text-slate-400 uppercase text-sm font-bold">Periodo</p>
                <h2 class="text-xl font-black text-white mt-4"><?= $fechaInicio ?></h2>
                <p class="text-slate-400 mt-2">hasta</p>
                <h2 class="text-xl font-black text-white mt-2"><?= $fechaFin ?></h2>
            </div>

        </div>

    </div>

</body>

</html>