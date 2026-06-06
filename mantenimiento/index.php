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

/* ======================================================
   EQUIPOS
====================================================== */

try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM productos_informatica
    ");

    $totalEquipos = $stmt->fetchColumn();

} catch (Exception $e) {}

/* ======================================================
   ACCESORIOS
====================================================== */

try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM accesorios
    ");

    $totalAccesorios = $stmt->fetchColumn();

} catch (Exception $e) {}

/* ======================================================
   DISPONIBLES
====================================================== */

try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM accesorios
        WHERE estado = 'Activo'
    ");

    $totalDisponibles = $stmt->fetchColumn();

} catch (Exception $e) {}

/* ======================================================
   AGOTADOS
====================================================== */

try {

    $stmt = $pdo->query("
        SELECT COUNT(*)
        FROM accesorios
        WHERE estado = 'Agotado'
    ");

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
            radial-gradient(circle at top left, rgba(37, 99, 235, .08), transparent 30%),
            radial-gradient(circle at bottom right, rgba(14, 165, 233, .08), transparent 30%),
            #f8fafc;
    }

    .glass {
        background: rgba(255, 255, 255, .7);
        backdrop-filter: blur(14px);
    }

    .card-hover {
        transition: .35s ease;
    }

    @media (min-width: 768px) {
        .card-hover:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, .08);
        }
    }
    </style>
</head>

<body class="min-h-screen text-slate-800">

    <header class="glass border-b border-white/40 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4 flex flex-col md:flex-row items-center justify-between gap-4">

            <div class="flex items-center justify-between w-full md:w-auto gap-4">
                <div class="flex items-center gap-3">
                    <div class="bg-white p-2 sm:p-3 rounded-2xl shadow-md border border-slate-200">
                        <img src="../img/logo.webp" class="h-8 sm:h-10 object-contain">
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-black text-slate-900 leading-tight">PANEL ADMIN</h1>
                        <p class="text-[10px] uppercase tracking-[2px] sm:tracking-[4px] text-slate-500 font-bold">
                            DACANS COMPUTERS</p>
                    </div>
                </div>

                <button id="menu-btn" class="md:hidden text-slate-800 text-2xl focus:outline-none p-2">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>

            <div id="menu-actions"
                class="hidden md:flex flex-col md:flex-row items-stretch md:items-center gap-4 w-full md:w-auto">

                <div class="glass px-4 py-2 sm:px-5 sm:py-3 rounded-2xl border border-white/40 shadow-md">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl sm:rounded-2xl bg-blue-600 text-white flex items-center justify-center text-lg sm:text-xl flex-shrink-0">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <div>
                            <p class="font-black text-slate-900 text-sm sm:text-base leading-none">
                                <?= htmlspecialchars($usuarioSesion) ?>
                            </p>
                            <p class="text-[10px] uppercase tracking-widest text-slate-500 font-bold mt-1">
                                <?= htmlspecialchars($rolSesion) ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-2 md:gap-4">
                    <a href="../index.php"
                        class="bg-slate-900 hover:bg-black text-white px-5 py-3 rounded-2xl font-black shadow-md transition text-center text-sm sm:text-base">
                        <i class="fa-solid fa-house mr-2"></i> Inicio
                    </a>

                    <a href="../logout.php"
                        class="bg-red-500 hover:bg-red-600 text-white px-5 py-3 rounded-2xl font-black shadow-md transition text-center text-sm sm:text-base">
                        <i class="fa-solid fa-right-from-bracket mr-2"></i> Salir
                    </a>
                </div>

            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 py-6 sm:py-10">

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 sm:gap-8">

            <?php if ($rolSesion === 'admin' || $rolSesion === 'superadmin'): ?>
            <div
                class="glass rounded-[2rem] p-6 sm:p-8 border border-white/40 shadow-xl card-hover flex flex-col justify-between">
                <div>
                    <div
                        class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl sm:rounded-3xl bg-green-100 text-green-700 flex items-center justify-center text-3xl sm:text-4xl mb-6">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Compras</h2>
                    <p class="text-slate-500 mt-3 text-sm sm:text-base leading-relaxed">
                        Gestiona compras, costos, lotes, ganancias e importación de artículos desde Excel.
                    </p>
                </div>
                <div class="flex flex-col gap-3 mt-6 sm:mt-8">
                    <a href="../compras"
                        class="bg-green-600 hover:bg-green-700 text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">Ver
                        Compras</a>
                    <a href="../compras/crear.php"
                        class="bg-slate-900 hover:bg-black text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">Crear
                        Compra</a>
                    <a href="../compras/importar_excel.php"
                        class="bg-emerald-700 hover:bg-emerald-800 text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">Importar
                        Excel</a>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($rolSesion === 'admin' || $rolSesion === 'superadmin'): ?>
            <div
                class="glass rounded-[2rem] p-6 sm:p-8 border border-white/40 shadow-xl card-hover flex flex-col justify-between">
                <div>
                    <div
                        class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl sm:rounded-3xl bg-blue-100 text-blue-700 flex items-center justify-center text-3xl sm:text-4xl mb-6">
                        <i class="fa-solid fa-laptop"></i>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Equipos</h2>
                    <p class="text-slate-500 mt-3 text-sm sm:text-base leading-relaxed">
                        Administra laptops, workstations y equipos tecnológicos.
                    </p>
                </div>
                <div class="flex flex-col gap-3 mt-6 sm:mt-8">
                    <a href="../catalogo"
                        class="bg-blue-600 hover:bg-blue-700 text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">Ver
                        Catálogo</a>
                    <a href="../laptos"
                        class="bg-slate-900 hover:bg-black text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">Ver
                        Inventario</a>
                    <a href="../log"
                        class="bg-yellow-900 hover:bg-yellow-800 text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">Log
                        de Inventario</a>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($rolSesion === 'admin' || $rolSesion === 'superadmin'): ?>
            <div
                class="glass rounded-[2rem] p-6 sm:p-8 border border-white/40 shadow-xl card-hover flex flex-col justify-between">
                <div>
                    <div
                        class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl sm:rounded-3xl bg-yellow-100 text-yellow-700 flex items-center justify-center text-3xl sm:text-4xl mb-6">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Estadísticas</h2>
                    <p class="text-slate-500 mt-3 text-sm sm:text-base leading-relaxed">
                        Visualiza estadísticas de ventas, tráfico y rendimiento.
                    </p>
                </div>
                <div class="flex flex-col gap-3 mt-6 sm:mt-8">
                    <a href="../estadisticas/"
                        class="bg-yellow-600 hover:bg-yellow-700 text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">Laptops</a>
                    <a href="../estadisticas/accesorios.php"
                        class="bg-yellow-600 hover:bg-yellow-700 text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">Accesorios</a>
                    <a href="../estadisticas/totales_generales.php"
                        class="bg-yellow-600 hover:bg-yellow-700 text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">Totales
                        Generales</a>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($rolSesion === 'admin' || $rolSesion === 'superadmin'): ?>
            <div
                class="glass rounded-[2rem] p-6 sm:p-8 border border-white/40 shadow-xl card-hover flex flex-col justify-between">
                <div>
                    <div
                        class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl sm:rounded-3xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-3xl sm:text-4xl mb-6">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Ventas</h2>
                    <p class="text-slate-500 mt-3 text-sm sm:text-base leading-relaxed">
                        Gestiona ventas, cotizaciones, facturas, clientes y publicidad de equipos.
                    </p>
                </div>
                <div class="flex flex-col gap-3 mt-6 sm:mt-8">
                    <a href="../ventas/"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">Ventas</a>
                    <a href="../ventas/cotizaciones.php"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">Cotizaciones</a>
                    <a href="../ventas/facturas.php"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">Facturas</a>
                </div>
            </div>
            <?php endif; ?>

            <div
                class="glass rounded-[2rem] p-6 sm:p-8 border border-white/40 shadow-xl card-hover flex flex-col justify-between">
                <div>
                    <div
                        class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl sm:rounded-3xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-3xl sm:text-4xl mb-6">
                        <i class="fa-solid fa-users"></i>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Usuarios</h2>
                    <p class="text-slate-500 mt-3 text-sm sm:text-base leading-relaxed">
                        Administra usuarios, permisos y accesos del sistema.
                    </p>
                </div>
                <div class="flex flex-col gap-3 mt-6 sm:mt-8">
                    <?php if ($rolSesion === 'admin' || $rolSesion === 'superadmin'): ?>
                    <a href="../usuarios/"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">Ver
                        Usuarios</a>
                    <a href="../usuarios/"
                        class="bg-slate-900 hover:bg-black text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">Crear
                        Usuario</a>
                    <?php endif; ?>
                    <!-- NUEVA OPCION -->

                    <a href="../ventas/clientes.php"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">
                        Clientes
                    </a>


                    <a href="../usuarios/porcentaje.php"
                        class="bg-yellow-500 hover:bg-yellow-600 text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">
                        Ver % de Ganancias
                    </a>
                    <a href="../usuarios/cambiarPassword.php"
                        class="bg-red-500 hover:bg-red-600 text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">Reset
                        Password</a>
                </div>
            </div>

            <?php if ($rolSesion === 'admin' || $rolSesion === 'superadmin'): ?>
            <div
                class="glass rounded-[2rem] p-6 sm:p-8 border border-white/40 shadow-xl card-hover flex flex-col justify-between">
                <div>
                    <div
                        class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl sm:rounded-3xl bg-cyan-100 text-cyan-700 flex items-center justify-center text-3xl sm:text-4xl mb-6">
                        <i class="fa-solid fa-keyboard"></i>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Accesorios</h2>
                    <p class="text-slate-500 mt-3 text-sm sm:text-base leading-relaxed">
                        Gestiona teclados, mouse, headsets y accesorios.
                    </p>
                </div>
                <div class="flex flex-col gap-3 mt-6 sm:mt-8">
                    <a href="../accesorios/view.php"
                        class="bg-cyan-600 hover:bg-cyan-700 text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">Ver
                        Accesorios</a>
                    <a href="../accesorios/create.php"
                        class="bg-slate-900 hover:bg-black text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">Crear
                        Accesorio</a>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($rolSesion === 'invitado'): ?>
            <div
                class="glass rounded-[2rem] p-6 sm:p-8 border border-white/40 shadow-xl card-hover flex flex-col justify-between">
                <div>
                    <div
                        class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl sm:rounded-3xl bg-cyan-100 text-cyan-700 flex items-center justify-center text-3xl sm:text-4xl mb-6">
                        <i class="fa-solid fa-keyboard"></i>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Administración</h2>
                    <p class="text-slate-500 mt-3 text-sm sm:text-base leading-relaxed">
                        Crear equipos y manejo de accesorios.
                    </p>
                </div>
                <div class="flex flex-col gap-3 mt-6 sm:mt-8">
                    <a href="../accesorios/view.php"
                        class="bg-cyan-600 hover:bg-cyan-700 text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">Ver
                        Accesorios</a>
                    <a href="../accesorios/create.php"
                        class="bg-slate-900 hover:bg-black text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">Crear
                        Accesorio</a>
                    <a href="../laptos"
                        class="bg-slate-900 hover:bg-black text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">Crear
                        Equipo</a>
                </div>
            </div>
            <?php endif; ?>

            <div
                class="glass rounded-[2rem] p-6 sm:p-8 border border-white/40 shadow-xl card-hover flex flex-col justify-between">
                <div>
                    <div
                        class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl sm:rounded-3xl bg-blue-100 text-blue-700 flex items-center justify-center text-3xl sm:text-4xl mb-6">
                        <i class="fa-solid fa-laptop"></i>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Publicidad</h2>
                    <p class="text-slate-500 mt-3 text-sm sm:text-base leading-relaxed">
                        Crear publicidad, promociones y banners para el sitio web.
                    </p>
                </div>
                <div class="flex flex-col gap-3 mt-6 sm:mt-8">
                    <a href="../laptos"
                        class="bg-blue-600 hover:bg-blue-700 text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">Publicidad</a>
                    <a href="../laptos/collage.php"
                        class="bg-blue-600 hover:bg-blue-700 text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">Collage
                        Publicidad</a>
                    <a href="../envios"
                        class="bg-blue-600 hover:bg-blue-700 text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">Envios</a>
                </div>
            </div>

            <div
                class="glass rounded-[2rem] p-6 sm:p-8 border border-white/40 shadow-xl card-hover flex flex-col justify-between">
                <div>
                    <div
                        class="w-16 h-16 sm:w-20 sm:h-20 rounded-2xl sm:rounded-3xl bg-purple-100 text-purple-700 flex items-center justify-center text-3xl sm:text-4xl mb-6">
                        <i class="fa-solid fa-globe"></i>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-black text-slate-900">Sitio Web</h2>
                    <p class="text-slate-500 mt-3 text-sm sm:text-base leading-relaxed">
                        Accede rápidamente al home y páginas públicas.
                    </p>
                </div>
                <div class="flex flex-col gap-3 mt-6 sm:mt-8">
                    <a href="../"
                        class="bg-purple-600 hover:bg-purple-700 text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">Ir
                        al Home</a>
                    <a href="../accesorios/index.php"
                        class="bg-slate-900 hover:bg-black text-white py-3.5 rounded-xl text-center font-black transition text-sm sm:text-base">Ver
                        Tienda</a>
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