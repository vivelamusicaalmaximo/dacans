<?php
session_start();
$dbFile = 'catalogo_equipos.sqlite';
$pdo = new PDO("sqlite:" . $dbFile);

/* ======================================================
   FUNCIONES
====================================================== */

function getOptions($pdo, $column)
{
    try {
        $stmt = $pdo->query("
            SELECT DISTINCT $column 
            FROM productos_informatica 
            WHERE $column != '' 
            AND $column IS NOT NULL 
            ORDER BY $column ASC
        ");

        return $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
    } catch (Exception $e) {
        return [];
    }
}

/* ======================================================
   FILTROS
====================================================== */

$marcas_equipo_db = getOptions($pdo, 'equipo_marca');
$marcas_db        = getOptions($pdo, 'proc_marca');
$rams_db          = getOptions($pdo, 'memoria');
$discos_db        = getOptions($pdo, 'disco');
$gens_db          = getOptions($pdo, 'proc_generacion');

$touch_db        = getOptions($pdo, 'touch');
$graficos_db     = getOptions($pdo, 'graficos');
$expandible_db   = getOptions($pdo, 'g_expandible');

$touch_sel       = (isset($_GET['touch']) && is_array($_GET['touch'])) ? $_GET['touch'] : [];
$graficos_sel    = (isset($_GET['graficos']) && is_array($_GET['graficos'])) ? $_GET['graficos'] : [];
$expandible_sel  = (isset($_GET['g_expandible']) && is_array($_GET['g_expandible'])) ? $_GET['g_expandible'] : [];

$equipo_sel = (isset($_GET['equipo_marcas']) && is_array($_GET['equipo_marcas'])) ? $_GET['equipo_marcas'] : [];
$proc_sel   = (isset($_GET['proc_marcas']) && is_array($_GET['proc_marcas'])) ? $_GET['proc_marcas'] : [];
$ram_sel    = (isset($_GET['ram']) && is_array($_GET['ram'])) ? $_GET['ram'] : [];
$disco_sel  = (isset($_GET['disco']) && is_array($_GET['disco'])) ? $_GET['disco'] : [];
$gens_sel   = (isset($_GET['gens']) && is_array($_GET['gens'])) ? $_GET['gens'] : [];

/* ======================================================
   QUERY
====================================================== */

$query = "SELECT * FROM productos_informatica 
          WHERE estado = 'Lista'";

$params = [];
if (!empty($touch_sel)) {
    $query .= " AND touch IN (" . implode(',', array_fill(0, count($touch_sel), '?')) . ")";
    $params = array_merge($params, $touch_sel);
}

if (!empty($graficos_sel)) {
    $query .= " AND graficos IN (" . implode(',', array_fill(0, count($graficos_sel), '?')) . ")";
    $params = array_merge($params, $graficos_sel);
}

if (!empty($expandible_sel)) {
    $query .= " AND g_expandible IN (" . implode(',', array_fill(0, count($expandible_sel), '?')) . ")";
    $params = array_merge($params, $expandible_sel);
}

if (!empty($equipo_sel)) {
    $query .= " AND equipo_marca IN (" . implode(',', array_fill(0, count($equipo_sel), '?')) . ")";
    $params = array_merge($params, $equipo_sel);
}

if (!empty($proc_sel)) {
    $query .= " AND proc_marca IN (" . implode(',', array_fill(0, count($proc_sel), '?')) . ")";
    $params = array_merge($params, $proc_sel);
}

if (!empty($ram_sel)) {
    $query .= " AND memoria IN (" . implode(',', array_fill(0, count($ram_sel), '?')) . ")";
    $params = array_merge($params, $ram_sel);
}

if (!empty($disco_sel)) {
    $query .= " AND disco IN (" . implode(',', array_fill(0, count($disco_sel), '?')) . ")";
    $params = array_merge($params, $disco_sel);
}

if (!empty($gens_sel)) {
    $query .= " AND proc_generacion IN (" . implode(',', array_fill(0, count($gens_sel), '?')) . ")";
    $params = array_merge($params, $gens_sel);
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);

$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<style>
    .product-card::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(
        135deg,
        rgba(37, 99, 235, .05),
        transparent,
        rgba(6, 182, 212, .05)
    );
    opacity: 0;
    transition: .3s;
    pointer-events: none;
}

.product-card:hover::before {
    opacity: 1;
}

.product-card * {
    position: relative;
    z-index: 2;
}
</style>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dacans Computers | Catálogo</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body {
            background:
                radial-gradient(circle at top left, rgba(59, 130, 246, 0.08), transparent 30%),
                radial-gradient(circle at bottom right, rgba(14, 165, 233, 0.08), transparent 30%),
                #f8fafc;
        }

        ::-webkit-scrollbar {
            width: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(to bottom, #2563eb, #0f172a);
            border-radius: 20px;
        }

        .glass {
            background: rgba(255, 255, 255, 0.78);
            backdrop-filter: blur(14px);
        }

        .product-card {
            transition: .35s ease;
            position: relative;
            overflow: hidden;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, .08);
        }

        .product-card::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg,
                    rgba(37, 99, 235, .05),
                    transparent,
                    rgba(6, 182, 212, .05));
            opacity: 0;
            transition: .3s;
        }

        .product-card:hover::before {
            opacity: 1;
        }
    </style>

</head>

<body class="min-h-screen text-slate-900 overflow-x-hidden">

    <!-- ======================================================
         HEADER
    ======================================================= -->

    <!-- ======================================================
     HEADER MODERNO
====================================================== -->

<header class="sticky top-0 z-50 glass border-b border-white/40">

    <div class="container mx-auto px-4 lg:px-6 h-[90px] flex items-center justify-between">

        <!-- LOGO -->
        <div class="flex items-center gap-4">

            <a href="home.php"
                class="bg-white p-3 rounded-3xl shadow-lg border border-slate-200 hover:scale-105 transition">

                <img src="img/logo.webp" class="h-12 object-contain">

            </a>

            <div>

                <h1 class="text-xl lg:text-3xl font-black text-slate-900 leading-none">
                    DACANS COMPUTERS
                </h1>

                <p class="text-xs uppercase tracking-[4px] text-slate-500 font-bold mt-1">
                    Tecnología Premium
                </p>

            </div>

        </div>

        <!-- MENU DESKTOP -->
        <nav class="hidden lg:flex items-center gap-8 font-bold text-sm">

            <a href="home.php"
                class="hover:text-blue-600 transition">
                Inicio
            </a>

            <a href="index.php"
                class="text-blue-600">
                Catálogo
            </a>

            <a href="accesorios/index.php"
                class="hover:text-blue-600 transition">
                Accesorios
            </a>


        </nav>

   <!-- DERECHA -->
<div class="hidden lg:flex items-center gap-4">

    <!-- BADGE -->
    <div class="bg-gradient-to-r from-blue-600 to-cyan-500 text-white px-5 py-3 rounded-2xl shadow-lg">

        <span class="block text-[10px] uppercase tracking-widest opacity-70 font-black">
            Premium Tech
        </span>

        <span class="text-sm font-bold">
            Gaming • Business • Workstation
        </span>

    </div>

    <?php if (isset($_SESSION['admin_logueado'])): ?>

        <a href="admin.php"
            class="bg-slate-900 hover:bg-black text-white px-5 py-3 rounded-2xl font-black shadow-lg transition">

            <i class="fa-solid fa-screwdriver-wrench mr-2"></i>

            Mantenimiento

        </a>

    <?php endif; ?>

</div>

        <!-- BOTON MOBILE -->
        <button
            onclick="toggleMobileMenu()"
            class="lg:hidden bg-white border border-slate-200 shadow p-3 rounded-2xl">

            <i class="fa-solid fa-bars text-slate-700"></i>

        </button>

    </div>

    <!-- MOBILE MENU -->

    <div id="mobileMenu"
        class="hidden lg:hidden border-t border-slate-200 bg-white">

        <div class="flex flex-col p-5 gap-4 font-bold text-sm">

            <a href="home.php" class="hover:text-blue-600">
                Inicio
            </a>

            <a href="index.php" class="text-blue-600">
                Catálogo
            </a>

            <a href="accesorios/index.php" class="hover:text-blue-600">
                Accesorios
            </a>

            <a href="home.php#nosotros" class="hover:text-blue-600">
                Nosotros
            </a>

            <a href="home.php#contacto" class="hover:text-blue-600">
                Contacto
            </a>
            <?php if (isset($_SESSION['admin_logueado'])): ?>

    <a href="admin.php"
        class="bg-slate-900 text-white px-4 py-3 rounded-2xl text-center">

        <i class="fa-solid fa-screwdriver-wrench mr-2"></i>

        Mantenimiento

    </a>

<?php endif; ?>

        </div>

    </div>

</header>
    <!-- ======================================================
         CONTENIDO
    ======================================================= -->

    <div class="container mx-auto px-6 py-6 h-[calc(100vh-90px)] flex gap-6 overflow-hidden">

        <!-- ======================================================
             FILTROS
        ======================================================= -->

     <!-- ======================================================
     BOTON FILTROS MOBILE
====================================================== -->

<button
    id="openFilters"
    class="lg:hidden fixed bottom-6 right-6 z-50 bg-blue-600 text-white w-16 h-16 rounded-full shadow-2xl flex items-center justify-center">

    <i class="fa-solid fa-sliders text-2xl"></i>

</button>

<!-- OVERLAY -->
<div
    id="filtersOverlay"
    class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden">
</div>

<!-- ======================================================
     FILTROS
====================================================== -->

<aside
    id="filtersPanel"
    class="
    fixed lg:relative
    top-0 left-0
    h-full
    w-[320px]
    bg-white
    z-50
    transform -translate-x-full lg:translate-x-0
    transition-transform duration-300
    flex-shrink-0
    lg:block
    ">

    <div class="glass h-full rounded-none lg:rounded-[2rem] p-7 border border-white/40 shadow-xl overflow-y-auto">

        <!-- HEADER FILTROS -->
        <div class="flex items-center justify-between mb-8">

            <h2 class="text-xl font-black flex items-center gap-3">
                <i class="fa-solid fa-sliders text-blue-600"></i>
                Filtros
            </h2>

            <div class="flex items-center gap-4">

                <a href="index.php"
                    class="text-xs uppercase tracking-widest font-black text-red-500">
                    Reiniciar
                </a>

                <!-- BOTON CERRAR MOBILE -->
                <button
                    type="button"
                    id="closeFilters"
                    class="lg:hidden text-slate-700 text-2xl">

                    <i class="fa-solid fa-xmark"></i>

                </button>

            </div>

        </div>

        <form method="GET" class="space-y-8">
            

            <!-- MARCAS -->
            <div>

                <span class="block mb-4 text-[11px] uppercase tracking-[3px] text-slate-400 font-black">
                    Marca Laptop
                </span>

                <div class="grid grid-cols-2 gap-3">

                    <?php foreach ($marcas_equipo_db as $me): ?>

                        <label class="cursor-pointer">

                            <input
                                type="checkbox"
                                name="equipo_marcas[]"
                                value="<?= $me ?>"
                                onchange="this.form.submit()"
                                class="hidden peer"
                                <?= in_array($me, $equipo_sel) ? 'checked' : '' ?>>

                            <div class="text-center py-3 rounded-2xl bg-white border-2 border-slate-100 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition font-bold text-sm">
                                <?= $me ?>
                            </div>

                        </label>

                    <?php endforeach; ?>

                </div>

            </div>

            <!-- PROCESADOR -->
            <div>

                <span class="block mb-4 text-[11px] uppercase tracking-[3px] text-slate-400 font-black">
                    Procesador
                </span>

                <?php foreach ($marcas_db as $m): ?>

                    <label class="flex items-center gap-3 mb-3 cursor-pointer">

                        <input
                            type="checkbox"
                            name="proc_marcas[]"
                            value="<?= $m ?>"
                            onchange="this.form.submit()"
                            class="w-5 h-5 rounded text-blue-600"
                            <?= in_array($m, $proc_sel) ? 'checked' : '' ?>>

                        <span class="font-semibold text-slate-700">
                            <?= $m ?>
                        </span>

                    </label>

                <?php endforeach; ?>

            </div>

            <!-- RAM -->
            <div>

                <span class="block mb-4 text-[11px] uppercase tracking-[3px] text-slate-400 font-black">
                    Memoria RAM
                </span>

                <?php foreach ($rams_db as $r): ?>

                    <label class="flex items-center gap-3 mb-3 cursor-pointer">

                        <input
                            type="checkbox"
                            name="ram[]"
                            value="<?= $r ?>"
                            onchange="this.form.submit()"
                            class="w-5 h-5 rounded text-blue-600"
                            <?= in_array($r, $ram_sel) ? 'checked' : '' ?>>

                        <span class="font-semibold text-slate-700">
                            <?= $r ?>
                        </span>

                    </label>

                <?php endforeach; ?>

            </div>

            <!-- DISCO -->
            <div>

                <span class="block mb-4 text-[11px] uppercase tracking-[3px] text-slate-400 font-black">
                    Almacenamiento
                </span>

                <?php foreach ($discos_db as $d): ?>

                    <label class="flex items-center gap-3 mb-3 cursor-pointer">

                        <input
                            type="checkbox"
                            name="disco[]"
                            value="<?= $d ?>"
                            onchange="this.form.submit()"
                            class="w-5 h-5 rounded text-blue-600"
                            <?= in_array($d, $disco_sel) ? 'checked' : '' ?>>

                        <span class="font-semibold text-slate-700">
                            <?= $d ?>
                        </span>

                    </label>

                <?php endforeach; ?>

            </div>

            <!-- GRAFICOS -->
<div>

    <span class="block mb-4 text-[11px] uppercase tracking-[3px] text-slate-400 font-black">
        Gráficos
    </span>

    <?php foreach ($graficos_db as $g): ?>

        <label class="flex items-center gap-3 mb-3 cursor-pointer">

            <input
                type="checkbox"
                name="graficos[]"
                value="<?= $g ?>"
                onchange="this.form.submit()"
                class="w-5 h-5 rounded text-blue-600"
                <?= in_array($g, $graficos_sel) ? 'checked' : '' ?>>

            <span class="font-semibold text-slate-700">
                <?= $g ?>
            </span>

        </label>

    <?php endforeach; ?>

</div>
<!-- GPU EXPANDIBLE -->
<div>

    <span class="block mb-4 text-[11px] uppercase tracking-[3px] text-slate-400 font-black">
        GPU Expandible
    </span>

    <?php foreach ($expandible_db as $e): ?>

        <label class="flex items-center gap-3 mb-3 cursor-pointer">

            <input
                type="checkbox"
                name="g_expandible[]"
                value="<?= $e ?>"
                onchange="this.form.submit()"
                class="w-5 h-5 rounded text-blue-600"
                <?= in_array($e, $expandible_sel) ? 'checked' : '' ?>>

            <span class="font-semibold text-slate-700">
                <?= $e == 1 ? 'Sí' : 'No' ?>
            </span>

        </label>

    <?php endforeach; ?>

</div>
            <!-- TOUCH -->
<div>

    <span class="block mb-4 text-[11px] uppercase tracking-[3px] text-slate-400 font-black">
        Pantalla Touch
    </span>

    <?php foreach ($touch_db as $t): ?>

        <label class="flex items-center gap-3 mb-3 cursor-pointer">

            <input
                type="checkbox"
                name="touch[]"
                value="<?= $t ?>"
                onchange="this.form.submit()"
                class="w-5 h-5 rounded text-blue-600"
                <?= in_array($t, $touch_sel) ? 'checked' : '' ?>>

            <span class="font-semibold text-slate-700">
                <?= $t == 1 ? 'Sí' : 'No' ?>
            </span>

        </label>

    <?php endforeach; ?>

</div>

        </form>

    </div>

</aside>

        <!-- ======================================================
             PRODUCTOS
        ======================================================= -->

        <main class="flex-1 h-full overflow-y-auto pr-2">

            <div class="flex items-center justify-between mb-8">

                <div>

                    <h2 class="text-3xl font-black text-slate-900">
                        Equipos Disponibles
                    </h2>

                    <p class="text-slate-500 mt-1">
                        <?= count($productos) ?> productos encontrados
                    </p>

                </div>

            </div>

            <!-- GRID -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-7 pb-10">

                <?php if ($productos): ?>

                    <?php foreach ($productos as $i => $p): ?>

                       <div class="product-card glass rounded-[2rem] p-4 border border-white/50 shadow-lg">

    <!-- TOP -->
    <div class="flex items-center justify-between mb-3">

        <span class="bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-[9px] uppercase tracking-widest font-black">
            <?= $p['equipo_marca'] ?>
        </span>

        <div class="flex items-center gap-2">

            <div class="w-2 h-2 rounded-full <?= $p['estado'] == 'Vendida' ? 'bg-red-500' : 'bg-green-500' ?>"></div>

            <span class="text-[9px] text-slate-400 font-bold">
                <?= !empty($p['serie']) ? $p['serie'] : 'Sin Serie' ?>
            </span>

        </div>

    </div>

    <!-- IMAGEN -->
    <div class="h-40 rounded-[1.5rem] bg-gradient-to-br from-slate-50 to-white border border-slate-100 flex items-center justify-center overflow-hidden relative mb-4">

        <?php if (!empty($p['imagen_url'])): ?>

            <img
                src="<?= htmlspecialchars($p['imagen_url']) ?>"
                alt="<?= htmlspecialchars($p['equipo_modelo']) ?>"
                class="w-full h-full object-contain p-3 hover:scale-105 transition duration-300">

        <?php else: ?>

            <div class="flex flex-col items-center">

                <i class="fa-solid fa-laptop text-5xl text-blue-100"></i>

                <span class="mt-2 text-[9px] uppercase tracking-widest text-slate-300 font-black">
                    Foto Pendiente
                </span>

            </div>

        <?php endif; ?>

    </div>

    <!-- INFO -->
    <h3 class="text-lg font-black text-slate-900 leading-tight line-clamp-2">
        <?= $p['equipo_modelo'] ?>
    </h3>

    <p class="text-blue-600 font-bold mt-1 text-xs">

        <?= $p['proc_marca'] ?>
        <?= $p['proc_familia'] ?>
        <?= $p['proc_modelo'] ?>

    </p>

    <!-- SPECS -->
    <div class="grid grid-cols-2 gap-2 mt-4 mb-4">

        <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 text-center">

            <span class="block text-[9px] uppercase tracking-widest text-slate-400 font-black">
                RAM
            </span>

            <span class="font-black text-sm">
                <?= $p['memoria'] ?>
            </span>

        </div>

        <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 text-center">

            <span class="block text-[9px] uppercase tracking-widest text-slate-400 font-black">
                Disco
            </span>

            <span class="font-black text-sm">
                <?= $p['disco'] ?>
            </span>

        </div>

    </div>

    <!-- FOOTER -->
    <div class="border-t border-slate-100 pt-4 flex items-center justify-between gap-3">

        <!-- PRECIO -->
        <div>

            <span class="block text-[9px] uppercase tracking-widest text-slate-400 font-black">
                Precio
            </span>

          <span class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-cyan-500 leading-none">

                <?= !empty($p['precio'])
                    ? 'RD$ ' . number_format((float)$p['precio'], 0)
                    : 'Consultar' ?>

            </span>

        </div>

        <!-- WHATSAPP -->
        <?php


$codigoBase = $p['id_local'] ?? '';
$codigoFinal = !empty($codigoBase)
    ? substr((string)$codigoBase, -5)
    : '00000';

$texto = "Hola, me interesa este equipo:\n\n";

$texto .= "🖥️ {$p['equipo_marca']} {$p['equipo_modelo']}\n";

$texto .= "⚙️ Procesador: {$p['proc_marca']} {$p['proc_modelo']}\n";

$texto .= "💾 RAM: {$p['memoria']}\n";

$texto .= "📦 Disco: {$p['disco']}\n";

$texto .= "💰 Precio: RD$ " . (
    !empty($p['precio'])
    ? number_format((float)$p['precio'], 0)
    : 'Consultar'
) . "\n";

$texto .= "🔖 Código: {$codigoFinal}\n";

?>

<a
    href="https://wa.me/18096926631?text=<?= urlencode($texto) ?>"
    target="_blank"
    rel="noopener noreferrer"
    onclick="event.stopPropagation();"
    class="relative z-50 bg-gradient-to-r from-green-500 to-green-700 text-white w-14 h-14 rounded-2xl shadow-xl hover:scale-105 transition flex items-center justify-center flex-shrink-0">

    <i class="fa-brands fa-whatsapp text-xl"></i>

</a>

    </div>

</div>

                    <?php endforeach; ?>

                <?php else: ?>

                    <div class="col-span-full">

                        <div class="glass rounded-[3rem] border border-white/50 py-24 text-center">

                            <i class="fa-solid fa-box-open text-7xl text-slate-200 mb-6"></i>

                            <h3 class="text-3xl font-black text-slate-700">
                                No se encontraron equipos
                            </h3>

                            <p class="text-slate-400 mt-3">
                                Intenta cambiar la combinación de filtros.
                            </p>

                            <a href="index.php"
                                class="inline-block mt-8 bg-blue-600 text-white px-8 py-4 rounded-2xl font-black hover:bg-blue-700 transition">

                                Ver Todo El Catálogo

                            </a>

                        </div>

                    </div>

                <?php endif; ?>

            </div>

        </main>

    </div>

    <script>

function toggleMobileMenu() {

    document
        .getElementById('mobileMenu')
        .classList
        .toggle('hidden');

}

</script>
    <script>

    const openBtn = document.getElementById('openFilters');
    const closeBtn = document.getElementById('closeFilters');
    const panel = document.getElementById('filtersPanel');
    const overlay = document.getElementById('filtersOverlay');

    function openFilters() {
        panel.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeFilters() {
        panel.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    openBtn.addEventListener('click', openFilters);

    closeBtn.addEventListener('click', closeFilters);

    overlay.addEventListener('click', closeFilters);

</script>

</body>

</html>