<?php

session_start();

/* =========================================================
   CONEXION SQL SERVER
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

/* ======================================================
   FUNCIONES
====================================================== */

function limpiarValor($valor)
{
    return strtoupper(trim(preg_replace('/\s+/', '', $valor)));
}

function getOptions($pdo, $column)
{
    try {

        $stmt = $pdo->query("
            SELECT DISTINCT $column
            FROM productos_informatica
            WHERE estado = 'Lista'
            AND $column IS NOT NULL
            AND LTRIM(RTRIM($column)) != ''
        ");

        $datos = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $resultado = [];
        $usados = [];

        foreach ($datos as $d) {

            // Normalizamos: pasamos a mayúsculas, quitamos espacios extras 
            // y dejamos un espacio limpio uniforme antes de la unidad (ej: 512 GB)
            $normalizado = strtoupper(trim(preg_replace('/\s+/', ' ', $d)));
            
            // Si el valor tiene unidades pegadas (ej: 512GB), le ponemos un espacio estándar (512 GB)
            // Esto evita que "512GB" y "512 GB" se traten como filtros diferentes
            $normalizado = preg_replace('/(\d+)([A-Z]+)/', '$1 $2', $normalizado);

            if (!in_array($normalizado, $usados)) {

                $usados[] = $normalizado;

                // Guardamos el valor estandarizado y estético para el usuario final
                $resultado[] = $normalizado;
            }
        }

        // Ordenación natural (así 128 GB irá antes que 1 TB de forma correcta)
        natcasesort($resultado);

        return array_values($resultado);

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
$pantallas_db     = getOptions($pdo, 'pantalla');
$touch_db         = getOptions($pdo, 'touch');

/* ======================================================
   VALORES SELECCIONADOS
====================================================== */

$equipo_sel      = $_GET['equipo_marcas'] ?? [];
$proc_sel        = $_GET['proc_marcas'] ?? [];
$ram_sel         = $_GET['ram'] ?? [];
$disco_sel       = $_GET['disco'] ?? [];
$gens_sel        = $_GET['gens'] ?? [];
$pantalla_sel    = $_GET['pantalla'] ?? [];
$touch_sel       = $_GET['touch'] ?? [];
$expandible_sel  = $_GET['g_expandible'] ?? [];

/* ======================================================
   PRECIO MAXIMO DE LA DB
====================================================== */

$stmtPrecio = $pdo->query("
    SELECT
        MAX(TRY_CAST(precio AS INT)) as max_precio
    FROM productos_informatica
    WHERE estado = 'Lista'
    AND precio IS NOT NULL
    AND LTRIM(RTRIM(precio)) != ''
    AND TRY_CAST(precio AS INT) IS NOT NULL
");

$precioDB = $stmtPrecio->fetch(PDO::FETCH_ASSOC);

$precio_max_db = !empty($precioDB['max_precio'])
    ? (int)$precioDB['max_precio']
    : 250000;

$precio_max = isset($_GET['precio_max'])
    ? (int)$_GET['precio_max']
    : $precio_max_db;


/* ======================================================
   CONSTRUCCIÓN DE CONDICIONES DINÁMICAS
====================================================== */

$condiciones_dinamicas = "";
$params = [];

/* MARCA EQUIPO */
if (!empty($equipo_sel)) {
    $condiciones = [];
    foreach ($equipo_sel as $v) {
        $condiciones[] = "REPLACE(UPPER(equipo_marca),' ','') LIKE ?";
        $params[] = '%' . limpiarValor($v) . '%';
    }
    $condiciones_dinamicas .= " AND (" . implode(' OR ', $condiciones) . ")";
}

/* PROCESADOR */
if (!empty($proc_sel)) {
    $condiciones = [];
    foreach ($proc_sel as $v) {
        $condiciones[] = "REPLACE(UPPER(proc_marca),' ','') LIKE ?";
        $params[] = '%' . limpiarValor($v) . '%';
    }
    $condiciones_dinamicas .= " AND (" . implode(' OR ', $condiciones) . ")";
}

/* RAM */
if (!empty($ram_sel)) {
    $condiciones = [];
    foreach ($ram_sel as $v) {
        $condiciones[] = "REPLACE(UPPER(memoria),' ','') LIKE ?";
        $params[] = '%' . limpiarValor($v) . '%';
    }
    $condiciones_dinamicas .= " AND (" . implode(' OR ', $condiciones) . ")";
}

/* DISCO */
if (!empty($disco_sel)) {
    $condiciones = [];
    foreach ($disco_sel as $v) {
        $condiciones[] = "REPLACE(UPPER(disco),' ','') LIKE ?";
        $params[] = '%' . limpiarValor($v) . '%';
    }
    $condiciones_dinamicas .= " AND (" . implode(' OR ', $condiciones) . ")";
}

/* GENERACION */
if (!empty($gens_sel)) {
    $condiciones = [];
    foreach ($gens_sel as $v) {
        $condiciones[] = "REPLACE(UPPER(proc_generacion),' ','') LIKE ?";
        $params[] = '%' . limpiarValor($v) . '%';
    }
    $condiciones_dinamicas .= " AND (" . implode(' OR ', $condiciones) . ")";
}

/* PANTALLA */
if (!empty($pantalla_sel)) {
    $condiciones = [];
    foreach ($pantalla_sel as $v) {
        $condiciones[] = "REPLACE(UPPER(pantalla),' ','') LIKE ?";
        $params[] = '%' . limpiarValor($v) . '%';
    }
    $condiciones_dinamicas .= " AND (" . implode(' OR ', $condiciones) . ")";
}

/* TOUCH */
if (!empty($touch_sel)) {
    $placeholders = implode(',', array_fill(0, count($touch_sel), '?'));
    $condiciones_dinamicas .= " AND touch IN ($placeholders)";
    $params = array_merge($params, $touch_sel);
}

/* GPU */
if (!empty($expandible_sel)) {
    $placeholders = implode(',', array_fill(0, count($expandible_sel), '?'));
    $condiciones_dinamicas .= " AND g_expandible IN ($placeholders)";
    $params = array_merge($params, $expandible_sel);
}

/* FILTRO PRECIO */
$condiciones_dinamicas .= "
    AND TRY_CAST(precio AS INT) IS NOT NULL
    AND TRY_CAST(precio AS INT) <= ?
";
$params[] = $precio_max;


/* ======================================================
   ENSAMBLAJE DE LA QUERY FINAL CON CTE (CORREGIDO)
====================================================== */

$query_final = "
    WITH CTE_Productos AS (
        SELECT *,
               ROW_NUMBER() OVER (PARTITION BY equipo_modelo, clase ORDER BY created_at DESC) as rn
        FROM productos_informatica
        WHERE estado = 'Lista'
        $condiciones_dinamicas
    )
    SELECT * FROM CTE_Productos 
    WHERE rn = 1
    ORDER BY created_at DESC
";

/* ======================================================
   EJECUTAR
====================================================== */

$stmt = $pdo->prepare($query_final);
$stmt->execute($params);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<style>
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
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon.png">
    <link rel="shortcut icon" href="/img/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

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

    <header class="sticky top-0 z-50 glass border-b border-white/40">
        <div class="container mx-auto px-4 lg:px-6 h-[90px] flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="../"
                    class="bg-white p-3 rounded-3xl shadow-lg border border-slate-200 hover:scale-105 transition">
                    <img src="../img/logo.webp" class="h-12 object-contain">
                </a>
                <div>
                    <h1 class="text-xl lg:text-3xl font-black text-slate-900 leading-none">DACANS COMPUTERS</h1>
                    <p class="text-xs uppercase tracking-[4px] text-slate-500 font-bold mt-1">Tecnología Premium</p>
                </div>
            </div>

            <nav class="hidden lg:flex items-center gap-8 font-bold text-sm">
                <a href="../" class="text-blue-600">Catálogo</a>
                <a href="../accesorios/index.php" class="hover:text-blue-600 transition">Accesorios</a>
            </nav>

            <div class="hidden lg:flex items-center gap-4">
                <div class="bg-gradient-to-r from-blue-600 to-cyan-500 text-white px-5 py-3 rounded-2xl shadow-lg">
                    <span class="block text-[10px] uppercase tracking-widest opacity-70 font-black">Premium Tech</span>
                    <span class="text-sm font-bold">Gaming • Business • Workstation</span>
                </div>
                <?php if (isset($_SESSION['admin_logueado'])): ?>
                <a href="../mantenimiento"
                    class="bg-slate-900 hover:bg-black text-white px-5 py-3 rounded-2xl font-black shadow-lg transition">
                    <i class="fa-solid fa-screwdriver-wrench mr-2"></i> Mantenimiento
                </a>
                <?php endif; ?>
            </div>

            <button onclick="toggleMobileMenu()"
                class="lg:hidden bg-white border border-slate-200 shadow p-3 rounded-2xl">
                <i class="fa-solid fa-bars text-slate-700"></i>
            </button>
        </div>

        <div id="mobileMenu" class="hidden lg:hidden border-t border-slate-200 bg-white">
            <div class="flex flex-col p-5 gap-4 font-bold text-sm">
                <a href="../" class="text-blue-600">Página principal</a>
                <a href="../accesorios/index.php" class="hover:text-blue-600">Accesorios</a>
                <?php if (isset($_SESSION['admin_logueado'])): ?>
                <a href="../mantenimiento" class="bg-slate-900 text-white px-4 py-3 rounded-2xl text-center">
                    <i class="fa-solid fa-screwdriver-wrench mr-2"></i> Mantenimiento
                </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="container mx-auto px-6 py-6 h-[calc(100vh-90px)] flex gap-6 overflow-hidden">

        <button id="openFilters"
            class="lg:hidden fixed bottom-6 right-6 z-50 bg-blue-600 text-white w-16 h-16 rounded-full shadow-2xl flex items-center justify-center">
            <i class="fa-solid fa-sliders text-2xl"></i>
        </button>

        <div id="filtersOverlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

        <aside id="filtersPanel"
            class="fixed lg:relative top-0 left-0 h-full w-[320px] bg-white z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 flex-shrink-0 lg:block">
            <div
                class="glass h-full rounded-none lg:rounded-[2rem] p-7 border border-white/40 shadow-xl overflow-y-auto">
                <div class="flex items-center justify-between mb-8">
                    <h2 class="text-xl font-black flex items-center gap-3">
                        <i class="fa-solid fa-sliders text-blue-600"></i> Filtros
                    </h2>
                    <div class="flex items-center gap-4">
                        <a href="index.php"
                            class="text-xs uppercase tracking-widest font-black text-red-500">Reiniciar</a>
                        <button type="button" id="closeFilters" class="lg:hidden text-slate-700 text-2xl">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                <form method="GET" class="space-y-8">
                    <div>
                        <span class="block mb-5 text-[11px] uppercase tracking-[3px] text-slate-400 font-black">Precio
                            Máximo</span>
                        <div class="bg-white border border-slate-200 rounded-3xl p-5">
                            <div class="text-center mb-5">
                                <span
                                    class="block text-[11px] uppercase tracking-widest text-slate-400 font-black">Hasta</span>
                                <span id="precioTexto"
                                    class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-cyan-500">
                                    RD$ <?= number_format($precio_max) ?>
                                </span>
                            </div>
                            <input type="range" id="precioSlider" name="precio_max" min="0" max="<?= $precio_max_db ?>"
                                step="<?= $precio_max_db / 10 ?>" value="<?= $precio_max ?>"
                                oninput="document.getElementById('precioTexto').innerText = 'RD$ ' + new Intl.NumberFormat().format(this.value)"
                                onchange="this.form.submit()" class="w-full accent-blue-600 cursor-pointer">
                            <div class="flex justify-between mt-3 text-[11px] text-slate-400 font-bold">
                                <span>RD$ 0</span>
                                <span>RD$ <?= number_format($precio_max_db) ?></span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <span class="block mb-4 text-[11px] uppercase tracking-[3px] text-slate-400 font-black">Marca
                            Laptop</span>
                        <div class="grid grid-cols-2 gap-3">
                            <?php foreach ($marcas_equipo_db as $me): ?>
                            <label class="cursor-pointer">
                                <input type="checkbox" name="equipo_marcas[]" value="<?= $me ?>"
                                    onchange="this.form.submit()" class="hidden peer"
                                    <?= in_array($me, $equipo_sel) ? 'checked' : '' ?>>
                                <div
                                    class="text-center py-3 rounded-2xl bg-white border-2 border-slate-100 peer-checked:border-blue-600 peer-checked:bg-blue-50 transition font-bold text-sm">
                                    <?= $me ?>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div>
                        <span
                            class="block mb-4 text-[11px] uppercase tracking-[3px] text-slate-400 font-black">Procesador</span>
                        <?php foreach ($marcas_db as $m): ?>
                        <label class="flex items-center gap-3 mb-3 cursor-pointer">
                            <input type="checkbox" name="proc_marcas[]" value="<?= $m ?>" onchange="this.form.submit()"
                                class="w-5 h-5 rounded text-blue-600" <?= in_array($m, $proc_sel) ? 'checked' : '' ?>>
                            <span class="font-semibold text-slate-700"><?= $m ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <div>
                        <span class="block mb-4 text-[11px] uppercase tracking-[3px] text-slate-400 font-black">Memoria
                            RAM</span>
                        <?php foreach ($rams_db as $r): ?>
                        <label class="flex items-center gap-3 mb-3 cursor-pointer">
                            <input type="checkbox" name="ram[]" value="<?= $r ?>" onchange="this.form.submit()"
                                class="w-5 h-5 rounded text-blue-600" <?= in_array($r, $ram_sel) ? 'checked' : '' ?>>
                            <span class="font-semibold text-slate-700"><?= $r . 'B' ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <div>
                        <span
                            class="block mb-4 text-[11px] uppercase tracking-[3px] text-slate-400 font-black">Almacenamiento</span>
                        <?php
                        if (!function_exists('convertirGB')) {
                            function convertirGB($valor) {
                                $valor = strtoupper(trim($valor));
                                if (strpos($valor, 'T') !== false) {
                                    preg_match('/(\d+)/', $valor, $m);
                                    return isset($m[1]) ? ((int)$m[1]) * 1024 : 0;
                                }
                                if (strpos($valor, 'G') !== false) {
                                    preg_match('/(\d+)/', $valor, $m);
                                    return isset($m[1]) ? (int)$m[1] : 0;
                                }
                                return 0;
                            }
                        }

                        if (!function_exists('formatearEtiqueta')) {
                            function formatearEtiqueta($valor) {
                                $valor = strtoupper(trim($valor));
                                $valor = preg_replace('/(\d+)\s*G$/', '$1 GB', $valor);
                                $valor = preg_replace('/(\d+)\s*T$/', '$1 TB', $valor);
                                return $valor;
                            }
                        }

                        usort($discos_db, function($a, $b) {
                            return convertirGB($a) <=> convertirGB($b);
                        });
                        ?>

                        <?php foreach ($discos_db as $d): ?>
                        <label class="flex items-center gap-3 mb-3 cursor-pointer">
                            <input type="checkbox" name="disco[]" value="<?= htmlspecialchars($d) ?>"
                                onchange="this.form.submit()" class="w-5 h-5 rounded text-blue-600"
                                <?= in_array($d, $disco_sel) ? 'checked' : '' ?>>

                            <span
                                class="font-semibold text-slate-700"><?= htmlspecialchars(formatearEtiqueta($d)) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <div>
                        <span class="block mb-4 text-[11px] normal-case tracking-[3px] text-slate-400 font-black">Tamaño
                            Pantalla</span>

                        <?php foreach ($pantallas_db as $pa): ?>
                        <label class="flex items-center gap-3 mb-3 cursor-pointer">
                            <input type="checkbox" name="pantalla[]" value="<?= $pa ?>" onchange="this.form.submit()"
                                class="w-5 h-5 rounded text-blue-600"
                                <?= in_array($pa, $pantalla_sel) ? 'checked' : '' ?>>

                            <span class="font-semibold text-slate-700">
                                <?= ucfirst(mb_strtolower($pa, 'UTF-8')) ?>
                            </span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <div>
                        <span class="block mb-4 text-[11px] uppercase tracking-[3px] text-slate-400 font-black">Tipo de
                            GPU</span>
                        <?php $tipos_gpu = [0 => 'Integrada', 1 => 'APU Ajustable', 2 => 'Dedicada']; ?>
                        <?php foreach ($tipos_gpu as $valor => $texto): ?>
                        <label class="flex items-center gap-3 mb-3 cursor-pointer">
                            <input type="checkbox" name="g_expandible[]" value="<?= $valor ?>"
                                onchange="this.form.submit()" class="w-5 h-5 rounded text-blue-600"
                                <?= in_array((string)$valor, $expandible_sel) ? 'checked' : '' ?>>
                            <span class="font-semibold text-slate-700"><?= $texto ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>

                    <div>
                        <span class="block mb-4 text-[11px] uppercase tracking-[3px] text-slate-400 font-black">Pantalla
                            Touch</span>
                        <?php foreach ($touch_db as $t): ?>
                        <label class="flex items-center gap-3 mb-3 cursor-pointer">
                            <input type="checkbox" name="touch[]" value="<?= $t ?>" onchange="this.form.submit()"
                                class="w-5 h-5 rounded text-blue-600" <?= in_array($t, $touch_sel) ? 'checked' : '' ?>>
                            <span class="font-semibold text-slate-700"><?= $t == 1 ? 'Sí' : 'No' ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </form>
            </div>
        </aside>

        <main class="flex-1 h-full overflow-y-auto pr-2">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-3xl font-black text-slate-900">Equipos Disponibles</h2>
                    <p class="text-slate-500 mt-1"><?= count($productos) ?> modelos únicos encontrados</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-7 pb-10">
                <?php if ($productos): ?>
                <?php foreach ($productos as $i => $p): ?>
                <div class="product-card glass rounded-[2rem] p-4 border border-white/50 shadow-lg">
                    <div class="flex items-center justify-between mb-3">
                        <span
                            class="bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-[9px] uppercase tracking-widest font-black">
                            <?= $p['equipo_marca'] ?>
                        </span>
                        <div class="flex items-center gap-2">
                            <div
                                class="w-2 h-2 rounded-full <?= $p['estado'] == 'Vendida' ? 'bg-red-500' : 'bg-green-500' ?>">
                            </div>
                            <span class="text-[9px] text-slate-400 font-bold">
                                <?= !empty($p['serie']) ? $p['serie'] : 'Sin Serie' ?>
                            </span>
                        </div>
                    </div>

                    <div
                        class="h-40 rounded-[1.5rem] bg-gradient-to-br from-slate-50 to-white border border-slate-100 flex items-center justify-center overflow-hidden relative mb-4 cursor-zoom-in group">

                        <?php if (!empty($p['clase'])): ?>
                        <?php 
                            $clase_valor = strtoupper(trim($p['clase']));
                            
                            $bg_color = match($clase_valor) {
                                'A' => 'bg-blue-600/90',
                                'B' => 'bg-emerald-600/90',
                                'C' => 'bg-amber-500/90 text-slate-900',
                                default => 'bg-slate-600/90',
                            };

                            $text_color = ($clase_valor === 'C') ? 'text-slate-900' : 'text-white';
                        ?>
                        <span
                            class="absolute top-3 right-3 z-30 <?= $bg_color ?> <?= $text_color ?> backdrop-blur-sm text-[9px] font-black uppercase tracking-wider px-2.5 py-1.5 rounded-xl shadow-sm">
                            <?= htmlspecialchars('Clase ' . $p['clase']) ?>
                        </span>
                        <?php endif; ?>

                        <?php if (!empty($p['imagen_url'])): ?>
                        <?php 
                            $galeria = [$p['imagen_url']];
                            if (!empty($p['imagenes_adicionales'])) {
                                $extras = explode(',', $p['imagenes_adicionales']);
                                $galeria = array_merge($galeria, array_map('trim', $extras));
                            }
                            $galeria_json = htmlspecialchars(json_encode($galeria), ENT_QUOTES, 'UTF-8');
                        ?>
                        <img src="<?= htmlspecialchars($p['imagen_url']) ?>"
                            alt="<?= htmlspecialchars($p['equipo_modelo']) ?>" data-images="<?= $galeria_json ?>"
                            onclick="openModal(this)"
                            class="w-full h-full object-contain p-3 group-hover:scale-105 transition duration-300">
                        <div
                            class="absolute inset-0 bg-black/5 opacity-0 group-hover:opacity-100 transition flex items-center justify-center pointer-events-none">
                            <i
                                class="fa-solid fa-magnifying-glass-plus text-slate-700 text-lg bg-white/80 p-3 rounded-full shadow"></i>
                        </div>
                        <?php else: ?>
                        <div class="flex flex-col items-center">
                            <i class="fa-solid fa-laptop text-5xl text-blue-100"></i>
                            <span class="mt-2 text-[9px] uppercase tracking-widest text-slate-300 font-black">Foto
                                Pendiente</span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <h3 class="text-lg font-black text-slate-900 leading-tight line-clamp-2"><?= $p['equipo_modelo'] ?>
                    </h3>
                    <p class="text-blue-600 font-bold mt-1 text-xs"><?= $p['proc_marca'] ?> <?= $p['proc_familia'] ?>
                        <?= $p['proc_modelo'] ?></p>

                    <div class="grid grid-cols-2 gap-2 mt-4 mb-4">
                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 text-center">
                            <span
                                class="block text-[9px] uppercase tracking-widest text-slate-400 font-black">RAM</span>
                            <span class="font-black text-sm"><?= $p['memoria'] ?></span>
                        </div>
                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 text-center">
                            <span
                                class="block text-[9px] uppercase tracking-widest text-slate-400 font-black">Disco</span>
                            <span class="font-black text-sm"><?= $p['disco'] ?></span>
                        </div>
                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 text-center col-span-2">
                            <span
                                class="block text-[9px] uppercase tracking-widest text-slate-400 font-black">Gráficos</span>
                            <span
                                class="font-black text-sm text-slate-700"><?= !empty($p['graficos']) ? $p['graficos'] : 'No especificado' ?></span>
                        </div>
                        <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 text-center col-span-2">
                            <span
                                class="block text-[9px] uppercase tracking-widest text-slate-400 font-black">Pantalla</span>
                            <span class="font-black text-sm text-slate-700">
                                <?= !empty($p['pantalla']) ? $p['pantalla'] : 'No especificada' ?>
                                <?php if (!empty($p['touch']) && $p['touch'] == 1): ?> • Touch <?php endif; ?>
                            </span>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 pt-4 flex items-center justify-between gap-3">
                        <div>
                            <span
                                class="block text-[9px] uppercase tracking-widest text-slate-400 font-black">Precio</span>
                            <span
                                class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-cyan-500 leading-none">
                                <?= !empty($p['precio']) ? 'RD$ ' . number_format((float)$p['precio'], 0) : 'Consultar' ?>
                            </span>
                        </div>

                        <?php
                        $codigoBase = $p['id_local'] ?? '';
                    $codigoFinal = !empty($codigoBase) ? mb_substr((string)$codigoBase, -5, null, 'UTF-8') : '00000';

                        // Emojis en código binario compatible
                        $emoji_saludo    = "\xF0\x9F\x91\x8B"; // 👋
                        $emoji_medalla   = "\xF0\x9F\x8F\x85"; // 🏅
                        $emoji_pantalla  = "\xF0\x9F\x92\xBB"; // 🖥️
                        $emoji_engranaje = "\xE2\x9A\x99\xEF\xB8\x8F"; // ⚙️
                        $emoji_disquete  = "\xF0\x9F\x92\xBE"; // 💾
                        $emoji_caja      = "\xF0\x9F\x93\xA6"; // 📦
                        $emoji_dinero    = "\xF0\x9F\x92\xB0"; // 💰
                        $emoji_marcador  = "\xF0\x9F\x94\x96"; // 🔖

                        // Formatear la explicación de la condición sin acentos
                        $clase_letra = !empty($p['clase']) ? strtoupper(trim($p['clase'])) : 'N/A';
                        $clase_explicacion = match($clase_letra) {
                            'A' => "Clase A (Condicion excelente, como nueva)",
                            'B' => "Clase B (Buen estado, marcas de uso ligeras)",
                            'C' => "Clase C (Signos de uso notables, gran precio)",
                            default => "Clase " . $clase_letra
                        };

                        // Construcción limpia y completa de la cadena de texto
                        $texto = "{$emoji_saludo} Hola, me interesa este equipo:\n\n";
                        $texto .= "{$emoji_medalla} Condicion: {$clase_explicacion}\n\n";
                        $texto .= "{$emoji_pantalla} {$p['equipo_marca']} {$p['equipo_modelo']}\n";
                        $texto .= "{$emoji_engranaje} Procesador: {$p['proc_marca']} {$p['proc_modelo']}\n";
                        $texto .= "{$emoji_disquete} RAM: {$p['memoria']}\n";
                        $texto .= "{$emoji_caja} Disco: {$p['disco']}\n";
                        $texto .= "{$emoji_dinero} Precio: RD$ " . (!empty($p['precio']) ? number_format((float)$p['precio'], 0) : 'Consultar') . "\n";
                        $texto .= "{$emoji_marcador} Codigo: {$codigoFinal}\n";
                        ?>

                        <a href="https://wa.me/18495886436?text=<?= urlencode($texto) ?>" target="_blank"
                            rel="noopener noreferrer" onclick="event.stopPropagation();"
                            class="relative z-50 bg-gradient-to-r from-green-500 to-green-700 text-white w-14 h-14 rounded-2xl shadow-xl hover:scale-105 transition flex items-center justify-center flex-shrink-0">
                            <i class="fa-brands fa-whatsapp text-xl"></i>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="col-span-full py-20 text-center text-slate-400">
                    <i class="fa-solid fa-laptop-slash text-6xl mb-4"></i>
                    <p class="text-xl font-bold">No se encontraron laptops con los filtros seleccionados.</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
    // Lógica del Menú Móvil (Header)
    function toggleMobileMenu() {
        document.getElementById('mobileMenu').classList.toggle('hidden');
    }

    // Lógica del Panel de Filtros
    const openFilters = document.getElementById('openFilters');
    const closeFilters = document.getElementById('closeFilters');
    const filtersPanel = document.getElementById('filtersPanel');
    const overlay = document.getElementById('filtersOverlay');

    function toggleFilters() {
        const isHidden = filtersPanel.classList.contains('-translate-x-full');
        if (isHidden) {
            filtersPanel.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        } else {
            filtersPanel.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }
    }

    openFilters.addEventListener('click', toggleFilters);
    closeFilters.addEventListener('click', toggleFilters);
    overlay.addEventListener('click', toggleFilters);
    </script>
</body>

</html>