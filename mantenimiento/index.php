<?php

session_start();

$usuarioSesion = $_SESSION['usuario'] ?? 'Usuario';
$rolSesion     = $_SESSION['rol'] ?? 'Sin rol';
$correoSesion  = $_SESSION['correo'] ?? '';

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
   CONTADORES
====================================================== */
$totalEquipos      = 0;
$totalAccesorios   = 0;
$totalDisponibles  = 0;
$totalAgotados     = 0;

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM productos_informatica");
    $totalEquipos = $stmt->fetchColumn();
} catch (Exception $e) {}

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM accesorios");
    $totalAccesorios = $stmt->fetchColumn();
} catch (Exception $e) {}

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM accesorios WHERE estado = 'Activo'");
    $totalDisponibles = $stmt->fetchColumn();
} catch (Exception $e) {}

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM accesorios WHERE estado = 'Agotado'");
    $totalAgotados = $stmt->fetchColumn();
} catch (Exception $e) {}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo | DACANS</title>
    <link rel="shortcut icon" href="/img/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
    body {
        background:
            radial-gradient(circle at top left, rgba(37, 99, 235, .06), transparent 30%),
            radial-gradient(circle at bottom right, rgba(14, 165, 233, .06), transparent 30%),
            #f8fafc;
    }

    .glass {
        background: rgba(255, 255, 255, .75);
        backdrop-filter: blur(12px);
    }

    .card-hover {
        transition: all .3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    @media (min-width: 768px) {
        .card-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 20px -5px rgba(0, 0, 0, .05), 0 8px 10px -6px rgba(0, 0, 0, .05);
        }
    }
    </style>
</head>

<body class="min-h-screen text-slate-800 antialiased">

    <header class="glass border-b border-slate-200/50 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 flex flex-col md:flex-row items-center justify-between gap-4">

            <div class="flex items-center justify-between w-full md:w-auto gap-4">
                <div class="flex items-center gap-3">
                    <div class="bg-white p-2 rounded-xl shadow-sm border border-slate-200">
                        <img src="../img/logo.webp" class="h-8 object-contain">
                    </div>
                    <div>
                        <h1 class="text-lg font-black text-slate-900 leading-none">PANEL ADMIN</h1>
                        <p class="text-[9px] uppercase tracking-[2px] text-slate-500 font-bold mt-1">DACANS COMPUTERS
                        </p>
                    </div>
                </div>

                <button id="menu-btn" class="md:hidden text-slate-800 text-xl focus:outline-none p-2">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>

            <div id="menu-actions"
                class="hidden md:flex flex-col md:flex-row items-stretch md:items-center gap-3 w-full md:w-auto">

                <div class="glass px-3 py-1.5 rounded-xl border border-slate-200/60 shadow-sm">
                    <div class="flex items-center gap-2.5">
                        <div
                            class="w-8 h-8 rounded-lg bg-blue-600 text-white flex items-center justify-center text-sm flex-shrink-0">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div>
                            <p class="font-bold text-slate-900 text-sm leading-none">
                                <?= htmlspecialchars($usuarioSesion) ?></p>
                            <p class="text-[9px] uppercase tracking-wider text-slate-400 font-bold mt-0.5">
                                <?= htmlspecialchars($rolSesion) ?></p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-2">
                    <a href="../index.php"
                        class="bg-slate-900 hover:bg-black text-white px-4 py-2 rounded-xl font-bold shadow-sm transition text-center text-xs sm:text-sm">
                        <i class="fa-solid fa-house mr-1.5"></i> Inicio
                    </a>
                    <a href="../logout.php"
                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-xl font-bold shadow-sm transition text-center text-xs sm:text-sm">
                        <i class="fa-solid fa-right-from-bracket mr-1.5"></i> Salir
                    </a>
                </div>

            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 py-6">

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5">

            <?php if ($rolSesion === 'admin' || $rolSesion === 'superadmin'): ?>
            <div
                class="glass rounded-2xl p-4 border border-slate-200/60 shadow-sm card-hover flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div
                            class="w-11 h-11 rounded-xl bg-green-100 text-green-700 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fa-solid fa-cart-shopping"></i>
                        </div>
                        <h2 class="text-lg font-bold text-slate-900">Compras</h2>
                    </div>
                    <p class="text-slate-500 text-xs leading-normal">
                        Gestiona compras, costos, lotes, ganancias e importación de artículos desde Excel.
                    </p>
                </div>
                <div class="flex flex-col gap-1.5 mt-4">
                    <a href="../compras"
                        class="bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg text-center font-bold transition text-xs">Ver
                        Compras</a>
                    <div class="grid grid-cols-2 gap-1.5">
                        <a href="../compras/crear.php"
                            class="bg-slate-900 hover:bg-black text-white py-2 rounded-lg text-center font-bold transition text-xs">Crear</a>
                        <a href="../compras/facturas_compras.php"
                            class="bg-emerald-700 hover:bg-emerald-800 text-white py-2 rounded-lg text-center font-bold transition text-xs truncate px-1">Facturas</a>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($rolSesion === 'admin' || $rolSesion === 'superadmin'): ?>
            <div
                class="glass rounded-2xl p-4 border border-slate-200/60 shadow-sm card-hover flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div
                            class="w-11 h-11 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fa-solid fa-laptop"></i>
                        </div>
                        <h2 class="text-lg font-bold text-slate-900">Equipos</h2>
                    </div>
                    <p class="text-slate-500 text-xs leading-normal">
                        Administra laptops, workstations y equipos tecnológicos de forma centralizada.
                    </p>
                </div>
                <div class="flex flex-col gap-1.5 mt-4">
                    <div class="grid grid-cols-2 gap-1.5">
                        <a href="../catalogo"
                            class="bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg text-center font-bold transition text-xs">Catálogo</a>
                        <a href="../laptos"
                            class="bg-slate-900 hover:bg-black text-white py-2 rounded-lg text-center font-bold transition text-xs">Inventario</a>
                    </div>
                    <a href="../log"
                        class="bg-amber-800 hover:bg-amber-900 text-white py-2 rounded-lg text-center font-bold transition text-xs">Log
                        de Inventario</a>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($rolSesion === 'admin' || $rolSesion === 'superadmin'): ?>
            <div
                class="glass rounded-2xl p-4 border border-slate-200/60 shadow-sm card-hover flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div
                            class="w-11 h-11 rounded-xl bg-yellow-100 text-yellow-700 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fa-solid fa-chart-line"></i>
                        </div>
                        <h2 class="text-lg font-bold text-slate-900">Estadísticas</h2>
                    </div>
                    <p class="text-slate-500 text-xs leading-normal">
                        Visualiza estadísticas analíticas de ventas, tráfico y rendimiento general.
                    </p>
                </div>
                <div class="flex flex-col gap-1.5 mt-4">
                    <div class="grid grid-cols-2 gap-1.5">
                        <a href="../estadisticas/"
                            class="bg-yellow-600 hover:bg-yellow-700 text-white py-2 rounded-lg text-center font-bold transition text-xs">Laptops</a>
                        <a href="../estadisticas/accesorios.php"
                            class="bg-yellow-600 hover:bg-yellow-700 text-white py-2 rounded-lg text-center font-bold transition text-xs">Accesorios</a>
                    </div>
                    <a href="../estadisticas/totales_generales.php"
                        class="bg-yellow-600 hover:bg-yellow-700 text-white py-2 rounded-lg text-center font-bold transition text-xs">Totales
                        Generales</a>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($rolSesion === 'admin' || $rolSesion === 'superadmin' || $rolSesion === 'empleado'): ?>
            <div
                class="glass rounded-2xl p-4 border border-slate-200/60 shadow-sm card-hover flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div
                            class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fa-solid fa-cart-shopping"></i>
                        </div>
                        <h2 class="text-lg font-bold text-slate-900">Ventas</h2>
                    </div>
                    <p class="text-slate-500 text-xs leading-normal">
                        Gestiona ventas, cotizaciones, facturas emitidas, clientes y publicidad.
                    </p>
                </div>
                <div class="grid grid-cols-3 gap-1 mt-4">
                    <a href="../ventas/"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white py-2 rounded-lg text-center font-bold transition text-[11px]">Ventas</a>
                    <a href="../ventas/cotizaciones.php"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white py-2 rounded-lg text-center font-bold transition text-[11px] truncate px-0.5">Cotiz.</a>
                    <a href="../ventas/facturas.php"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white py-2 rounded-lg text-center font-bold transition text-[11px]">Facturas</a>
                </div>
            </div>
            <?php endif; ?>

            <div
                class="glass rounded-2xl p-4 border border-slate-200/60 shadow-sm card-hover flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div
                            class="w-11 h-11 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <h2 class="text-lg font-bold text-slate-900">Usuarios</h2>
                    </div>
                    <p class="text-slate-500 text-xs leading-normal">
                        Administra usuarios, permisos, clientes y accesos del sistema.
                    </p>
                </div>
                <div class="flex flex-col gap-1.5 mt-4">
                    <?php if ($rolSesion === 'admin' || $rolSesion === 'superadmin'): ?>
                    <div class="grid grid-cols-2 gap-1.5">
                        <a href="../usuarios/"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white py-2 rounded-lg text-center font-bold transition text-xs">Ver
                            Lista</a>
                        <a href="../usuarios/"
                            class="bg-slate-900 hover:bg-black text-white py-2 rounded-lg text-center font-bold transition text-xs">Crear</a>
                    </div>
                    <?php endif; ?>

                    <div class="grid grid-cols-2 gap-1.5">
                        <a href="../ventas/clientes.php"
                            class="bg-yellow-500 hover:bg-yellow-600 text-white py-2 rounded-lg text-center font-bold transition text-xs">Clientes</a>
                        <a href="../usuarios/porcentaje.php"
                            class="bg-yellow-500 hover:bg-yellow-600 text-white py-2 rounded-lg text-center font-bold transition text-xs truncate px-0.5">%
                            Ganancias</a>
                    </div>
                    <a href="../usuarios/cambiarPassword.php"
                        class="bg-red-500 hover:bg-red-600 text-white py-1.5 rounded-lg text-center font-bold transition text-xs">Reset
                        Password</a>
                </div>
            </div>

            <?php if ($rolSesion === 'admin' || $rolSesion === 'superadmin'): ?>
            <div
                class="glass rounded-2xl p-4 border border-slate-200/60 shadow-sm card-hover flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div
                            class="w-11 h-11 rounded-xl bg-cyan-100 text-cyan-700 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fa-solid fa-keyboard"></i>
                        </div>
                        <h2 class="text-lg font-bold text-slate-900">Accesorios</h2>
                    </div>
                    <p class="text-slate-500 text-xs leading-normal">
                        Gestiona periféricos: teclados, mouse, headsets y más.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-1.5 mt-4">
                    <a href="../accesorios/view.php"
                        class="bg-cyan-600 hover:bg-cyan-700 text-white py-2 rounded-lg text-center font-bold transition text-xs">Ver
                        Todos</a>
                    <a href="../accesorios/create.php"
                        class="bg-slate-900 hover:bg-black text-white py-2 rounded-lg text-center font-bold transition text-xs">Crear
                        Nuevo</a>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($rolSesion === 'invitado'): ?>
            <div
                class="glass rounded-2xl p-4 border border-slate-200/60 shadow-sm card-hover flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div
                            class="w-11 h-11 rounded-xl bg-cyan-100 text-cyan-700 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fa-solid fa-keyboard"></i>
                        </div>
                        <h2 class="text-lg font-bold text-slate-900">Administración</h2>
                    </div>
                    <p class="text-slate-500 text-xs leading-normal">
                        Mapeo básico de equipos y manejo operativo de accesorios.
                    </p>
                </div>
                <div class="flex flex-col gap-1.5 mt-4">
                    <div class="grid grid-cols-2 gap-1.5">
                        <a href="../accesorios/view.php"
                            class="bg-cyan-600 hover:bg-cyan-700 text-white py-2 rounded-lg text-center font-bold transition text-xs">Ver
                            Acc.</a>
                        <a href="../accesorios/create.php"
                            class="bg-slate-900 hover:bg-black text-white py-2 rounded-lg text-center font-bold transition text-xs">Crear
                            Acc.</a>
                    </div>
                    <a href="../laptos"
                        class="bg-slate-900 hover:bg-black text-white py-2 rounded-lg text-center font-bold transition text-xs">Crear
                        Equipo</a>
                </div>
            </div>
            <?php endif; ?>

            <div
                class="glass rounded-2xl p-4 border border-slate-200/60 shadow-sm card-hover flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div
                            class="w-11 h-11 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fa-solid fa-laptop"></i>
                        </div>
                        <h2 class="text-lg font-bold text-slate-900">Publicidad</h2>
                    </div>
                    <p class="text-slate-500 text-xs leading-normal">
                        Crear campañas, promociones fijas y banners informativos.
                    </p>
                </div>
                <div class="grid grid-cols-3 gap-1 mt-4">
                    <a href="../laptos"
                        class="bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg text-center font-bold transition text-[11px]">Banners</a>
                    <a href="../laptos/collage.php"
                        class="bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg text-center font-bold transition text-[11px] truncate px-0.5">Collage</a>
                    <a href="../envios"
                        class="bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg text-center font-bold transition text-[11px]">Envíos</a>
                </div>
            </div>

            <div
                class="glass rounded-2xl p-4 border border-slate-200/60 shadow-sm card-hover flex flex-col justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <div
                            class="w-11 h-11 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center text-xl flex-shrink-0">
                            <i class="fa-solid fa-globe"></i>
                        </div>
                        <h2 class="text-lg font-bold text-slate-900">Sitio Web</h2>
                    </div>
                    <p class="text-slate-500 text-xs leading-normal">
                        Enlaces de rápido acceso a la tienda y métricas de visitas.
                    </p>
                </div>
                <div class="flex flex-col gap-1.5 mt-4">
                    <div class="grid grid-cols-2 gap-1.5">
                        <a href="../"
                            class="bg-purple-600 hover:bg-purple-700 text-white py-2 rounded-lg text-center font-bold transition text-xs">Home</a>
                        <a href="../accesorios/index.php"
                            class="bg-slate-900 hover:bg-black text-white py-2 rounded-lg text-center font-bold transition text-xs">Tienda</a>
                    </div>
                    <a href="../visitas.php"
                        class="bg-slate-900 hover:bg-black text-white py-2 rounded-lg text-center font-bold transition text-xs">Contador
                        de Visitas</a>
                </div>
            </div>

        </div>
    </main>

    <script>
    const menuBtn = document.getElementById('menu-btn');
    const menuActions = document.getElementById('menu-actions');

    menuBtn.addEventListener('click', () => {
        menuActions.classList.toggle('hidden');
    });
    </script>
</body>

</html>