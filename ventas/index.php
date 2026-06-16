<?php
session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

$rolSesion = $_SESSION['rol'] ?? 'empleado';
?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Módulo de Ventas</title>

    <link rel="shortcut icon" href="/img/favicon.ico">

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
    body {

        background:
            radial-gradient(circle at top left, rgba(59, 130, 246, .08), transparent 30%),
            radial-gradient(circle at bottom right, rgba(16, 185, 129, .08), transparent 30%),
            linear-gradient(to bottom, #f8fafc, #eef2ff);

        font-family: Arial, sans-serif;
    }

    /* GLASS */

    .glass {

        background: rgba(255, 255, 255, .72);

        backdrop-filter: blur(16px);

        border: 1px solid rgba(255, 255, 255, .5);

        box-shadow:
            0 20px 45px rgba(15, 23, 42, .06),
            0 10px 20px rgba(15, 23, 42, .03);
    }

    /* HOVER */

    .card-hover {

        transition: .25s ease;
    }

    .card-hover:hover {

        transform: translateY(-6px);

        box-shadow:
            0 25px 50px rgba(15, 23, 42, .08),
            0 15px 25px rgba(15, 23, 42, .05);
    }
    </style>

</head>

<body class="min-h-screen p-6 md:p-10">

    <div class="max-w-[1800px] mx-auto">

        <!-- HEADER -->

        <div class="flex flex-col lg:flex-row
    justify-between items-center gap-6 mb-10">

            <div class="flex items-center gap-5">

                <!-- LOGO -->

                <div class="bg-white p-5 rounded-[2rem]
            shadow-xl border border-slate-200">

                    <img src="../img/logo.webp" class="h-16 object-contain">

                </div>

                <!-- TITULO -->

                <div>

                    <h1 class="text-5xl font-black tracking-tight text-slate-900">
                        Módulo de Ventas
                    </h1>

                    <p class="text-slate-500 mt-2 text-lg">
                        Gestión de ventas, clientes, facturas y cotizaciones
                    </p>

                </div>

            </div>

            <!-- BOTON -->

            <a href="../mantenimiento" class="bg-slate-900 hover:bg-black
        text-white px-7 py-4 rounded-2xl
        font-black shadow-xl transition">

                <i class="fa-solid fa-arrow-left mr-2"></i>

                Volver

            </a>

        </div>

        <!-- GRID -->

        <div class="grid grid-cols-1
    md:grid-cols-2
    xl:grid-cols-3
    gap-8">

            <!-- VENTAS -->

            <div class="glass rounded-[2.5rem]
        p-8 card-hover">

                <div class="w-20 h-20 rounded-3xl
            bg-blue-100 text-blue-700
            flex items-center justify-center
            text-4xl mb-6">

                    <i class="fa-solid fa-cart-shopping"></i>

                </div>

                <h2 class="text-3xl font-black text-slate-900">
                    Ventas
                </h2>

                <p class="text-slate-500 mt-4 leading-relaxed">
                    Registra ventas de equipos, accesorios y artículos.
                </p>

                <div class="flex flex-col gap-4 mt-8">

                    <a href="ventas.php" class="bg-blue-600 hover:bg-blue-700
                text-white py-4 rounded-2xl
                text-center font-black transition">

                        Ver Ventas

                    </a>

                    <a href="crear.php" class="bg-slate-900 hover:bg-black
                text-white py-4 rounded-2xl
                text-center font-black transition">

                        Nueva Venta

                    </a>

                </div>

            </div>

            <!-- COTIZACIONES -->

            <div class="glass rounded-[2.5rem]
        p-8 card-hover">

                <div class="w-20 h-20 rounded-3xl
            bg-purple-100 text-purple-700
            flex items-center justify-center
            text-4xl mb-6">

                    <i class="fa-solid fa-file-signature"></i>

                </div>

                <h2 class="text-3xl font-black text-slate-900">
                    Cotizaciones
                </h2>

                <p class="text-slate-500 mt-4 leading-relaxed">
                    Genera cotizaciones modernas para clientes.
                </p>

                <div class="flex flex-col gap-4 mt-8">

                    <a href="cotizaciones.php" class="bg-purple-600 hover:bg-purple-700
                text-white py-4 rounded-2xl
                text-center font-black transition">

                        Ver Cotizaciones

                    </a>

                    <a href="generar_cotizacion.php" class="bg-slate-900 hover:bg-black
                text-white py-4 rounded-2xl
                text-center font-black transition">

                        Nueva Cotización

                    </a>

                </div>

            </div>




            <!-- CLIENTES -->

            <div class="glass rounded-[2.5rem]
        p-8 card-hover">

                <div class="w-20 h-20 rounded-3xl
            bg-orange-100 text-orange-700
            flex items-center justify-center
            text-4xl mb-6">

                    <i class="fa-solid fa-users"></i>

                </div>

                <h2 class="text-3xl font-black text-slate-900">
                    Clientes
                </h2>

                <p class="text-slate-500 mt-4 leading-relaxed">
                    Base de datos y gestión de clientes.
                </p>

                <div class="flex flex-col gap-4 mt-8">

                    <a href="clientes.php" class="bg-orange-600 hover:bg-orange-700
                text-white py-4 rounded-2xl
                text-center font-black transition">

                        Ver Clientes

                    </a>



                </div>

            </div>

            <!-- REPORTES -->

            <div class="glass rounded-[2.5rem]
        p-8 card-hover">

                <div class="w-20 h-20 rounded-3xl
            bg-cyan-100 text-cyan-700
            flex items-center justify-center
            text-4xl mb-6">

                    <i class="fa-solid fa-chart-line"></i>

                </div>

                <h2 class="text-3xl font-black text-slate-900">
                    Reportes
                </h2>

                <p class="text-slate-500 mt-4 leading-relaxed">
                    Visualiza ventas, ganancias y estadísticas.
                </p>

                <div class="flex flex-col gap-4 mt-8">

                    <a href="#" class="bg-cyan-600 hover:bg-cyan-700
                text-white py-4 rounded-2xl
                text-center font-black transition">

                        Ver Reportes

                    </a>

                </div>

            </div>

            <!-- CONFIGURACION -->

            <div class="glass rounded-[2.5rem]
        p-8 card-hover">

                <div class="w-20 h-20 rounded-3xl
            bg-red-100 text-red-700
            flex items-center justify-center
            text-4xl mb-6">

                    <i class="fa-solid fa-gear"></i>

                </div>

                <h2 class="text-3xl font-black text-slate-900">
                    Configuración NCF
                </h2>

                <p class="text-slate-500 mt-4 leading-relaxed">
                    Ajustes generales del módulo de ventas.
                </p>

                <div class="flex flex-col gap-4 mt-8">

                    <a href="ncf.php" class="bg-red-600 hover:bg-red-700
                text-white py-4 rounded-2xl
                text-center font-black transition">

                        NCF

                    </a>

                </div>

            </div>

        </div>

    </div>

</body>

</html>