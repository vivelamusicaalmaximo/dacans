<?php
session_start();

/* =========================================================
   CONEXION SQL SERVER
========================================================= */
require '../config/conexion.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error DB: " . $e->getMessage());
}

/* =========================================================
   SISTEMA AUTOMÁTICO DE VISITAS (SIN LOGIN) 🚀
========================================================= */
try {
    $ip_usuario = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
    $pagina_visitada = $_SERVER['REQUEST_URI'] ?? 'Catalogo';

    $sqlVisita = "INSERT INTO registro_visitas (ip_usuario, pagina_visitada) VALUES (?, ?)";
    $stmtVisita = $pdo->prepare($sqlVisita);
    $stmtVisita->execute([$ip_usuario, $pagina_visitada]);
} catch (Exception $e) {
    // Silencioso
}

/* ======================================================
   FUNCIONES CORREGIDAS
========================================================= */
function limpiarValor($valor = '')
{
    $texto = $valor ?? '';
    if (!is_string($texto)) {
        $texto = (string)$texto;
    }
    return strtoupper(trim(preg_replace('/[^A-Za-z0-9]/', '', $texto)));
}

// Modificada para recibir de forma dinámica el array de estados seleccionados
function getOptions($pdo, $column, $estados = ['Lista'])
{
    try {
        if (empty($estados)) { $estados = ['Lista']; }
        $estado_placeholders = implode(',', array_fill(0, count($estados), '?'));
        
        $stmt = $pdo->prepare("
            SELECT DISTINCT $column
            FROM productos_informatica
            WHERE estado IN ($estado_placeholders)
              AND $column IS NOT NULL
              AND LTRIM(RTRIM($column)) != ''
        ");

        $stmt->execute($estados);
        $datos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $resultado = [];
        $usados = [];

        foreach ($datos as $d) {
            $normalizado = strtoupper(trim(preg_replace('/\s+/', ' ', $d)));
            $normalizado = preg_replace('/(\d+)([A-Z]+)/', '$1 $2', $normalizado);

            if (!in_array($normalizado, $usados)) {
                $usados[] = $normalizado;
                $resultado[] = $normalizado;
            }
        }

        natcasesort($resultado);
        return array_values($resultado);

    } catch (Exception $e) {
        return [];
    }
}

// Capturamos el filtro de estado dinámico enviado por la tabla/filtro
$estado_sel = $_GET['estado'] ?? ['Lista']; 
if (!is_array($estado_sel)) {
    $estado_sel = [$estado_sel];
}

$categorias_sel  = $_GET['categorias'] ?? [];
$equipo_sel      = $_GET['equipo_marcas'] ?? [];
$proc_sel        = $_GET['proc_marcas'] ?? [];
$ram_sel         = $_GET['ram'] ?? [];
$disco_sel       = $_GET['disco'] ?? [];
$gens_sel        = $_GET['gens'] ?? [];
$pantalla_sel    = $_GET['pantalla'] ?? [];
$touch_sel       = $_GET['touch'] ?? [];
$expandible_sel  = $_GET['g_expandible'] ?? [];

/* ======================================================
   FILTROS INDEPENDIENTES DESDE LA DB (ESTADO DINÁMICO)
========================================================= */
$marcas_equipo_db = getOptions($pdo, 'equipo_marca', $estado_sel);
$marcas_db         = getOptions($pdo, 'proc_marca', $estado_sel);
$rams_db           = getOptions($pdo, 'memoria', $estado_sel);
$discos_db         = getOptions($pdo, 'disco', $estado_sel);
$gens_db           = getOptions($pdo, 'proc_generacion', $estado_sel);
$pantallas_db      = getOptions($pdo, 'pantalla', $estado_sel);
$touch_db          = getOptions($pdo, 'touch', $estado_sel);

/* ======================================================
   CATEGORÍAS ACTIVAS (ESTADO DINÁMICO)
========================================================= */
try {
    $estado_cat_placeholders = implode(',', array_fill(0, count($estado_sel), '?'));
    $stmtCatList = $pdo->prepare("
        SELECT c.id_categoria, c.nombre_serie, c.prefijo, c.rango_uso, c.regaleria_promocion
        FROM categoria c
        WHERE EXISTS (
            SELECT 1 
            FROM productos_informatica p 
            WHERE p.id_categoria = c.id_categoria 
              AND p.estado IN ($estado_cat_placeholders)
        )
        ORDER BY 
            CASE UPPER(LTRIM(RTRIM(c.prefijo)))
                WHEN 'PIC' THEN 1
                WHEN 'KIL' THEN 2
                WHEN 'MEG' THEN 3
                WHEN 'TER' THEN 4
                ELSE 999
            END,
            c.nombre_serie ASC
    ");
    $stmtCatList->execute($estado_sel);
    $categorias_db = $stmtCatList->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $categorias_db = [];
}



/* ======================================================
   PRECIO MAXIMO DE LA DB (ESTADO DINÁMICO - CORREGIDO)
========================================================= */
$estado_precio_placeholders = implode(',', array_fill(0, count($estado_sel), '?'));
$stmtPrecio = $pdo->prepare("
    SELECT MAX(TRY_CAST(precio AS INT)) as max_precio
    FROM productos_informatica
    WHERE LTRIM(RTRIM(estado)) IN ($estado_precio_placeholders)
      AND precio IS NOT NULL
      AND LTRIM(RTRIM(precio)) != ''
      AND TRY_CAST(precio AS INT) IS NOT NULL
");
$stmtPrecio->execute($estado_sel);

$precioDB = $stmtPrecio->fetch(PDO::FETCH_ASSOC);
$precio_max_db = !empty($precioDB['max_precio']) ? (int)$precioDB['max_precio'] : 250000;
$precio_max = isset($_GET['precio_max']) ? (int)$_GET['precio_max'] : $precio_max_db;


/* ======================================================
   VALORES SELECCIONADOS (REQUEST) - CONTROL ESTRICTO
========================================================= */
$estado_crudo = $_GET['estado'] ?? ['Lista']; 

if (!is_array($estado_crudo)) {
    // Si viene separado por comas desde JS, lo rompe en array
    $estado_sel = explode(',', $estado_crudo); 
} else {
    $estado_sel = $estado_crudo;
}

// 1. Limpiamos espacios alrededor de las palabras que envía el formulario
$estado_sel = array_map('trim', $estado_sel);
// 2. Eliminamos elementos vacíos del array
$estado_sel = array_filter($estado_sel);
// 3. Eliminamos duplicados por si se envió dos veces el mismo estado
$estado_sel = array_unique($estado_sel);

// Si quedó completamente vacío tras la limpieza, regresamos al valor por defecto
if (empty($estado_sel)) {
    $estado_sel = ['Lista'];
}
/* ======================================================
   CONSTRUCCIÓN DE CONDICIONES DINÁMICAS (CORREGIDO SQL)
========================================================= */
$condiciones_dinamicas = "";
$params = [];

/* FILTRO POR CATEGORIAS */
if (!empty($categorias_sel)) {
    $placeholders = implode(',', array_fill(0, count($categorias_sel), '?'));
    $condiciones_dinamicas .= " AND p.id_categoria IN ($placeholders)";
    $params = array_merge($params, array_map('intval', $categorias_sel));
}

$limpiar_sql = "REPLACE(REPLACE(REPLACE(UPPER({col}), ' ', ''), CHAR(13), ''), CHAR(10), '')";

/* MARCA EQUIPO */
if (!empty($equipo_sel)) {
    $condiciones = [];
    foreach ($equipo_sel as $v) {
        $condiciones[] = str_replace('{col}', 'p.equipo_marca', $limpiar_sql) . " LIKE ?";
        $params[] = '%' . limpiarValor($v) . '%';
    }
    $condiciones_dinamicas .= " AND (" . implode(' OR ', $condiciones) . ")";
}

/* PROCESADOR */
if (!empty($proc_sel)) {
    $condiciones = [];
    foreach ($proc_sel as $v) {
        $condiciones[] = str_replace('{col}', 'p.proc_marca', $limpiar_sql) . " LIKE ?";
        $params[] = '%' . limpiarValor($v) . '%';
    }
    $condiciones_dinamicas .= " AND (" . implode(' OR ', $condiciones) . ")";
}

/* RAM */
if (!empty($ram_sel)) {
    $condiciones = [];
    foreach ($ram_sel as $v) {
        $condiciones[] = str_replace('{col}', 'p.memoria', $limpiar_sql) . " LIKE ?";
        $params[] = '%' . limpiarValor($v) . '%';
    }
    $condiciones_dinamicas .= " AND (" . implode(' OR ', $condiciones) . ")";
}

/* DISCO */
if (!empty($disco_sel)) {
    $condiciones = [];
    foreach ($disco_sel as $v) {
        $condiciones[] = str_replace('{col}', 'p.disco', $limpiar_sql) . " LIKE ?";
        $params[] = '%' . limpiarValor($v) . '%';
    }
    $condiciones_dinamicas .= " AND (" . implode(' OR ', $condiciones) . ")";
}

/* GENERACION */
if (!empty($gens_sel)) {
    $condiciones = [];
    foreach ($gens_sel as $v) {
        $condiciones[] = str_replace('{col}', 'p.proc_generacion', $limpiar_sql) . " LIKE ?";
        $params[] = '%' . limpiarValor($v) . '%';
    }
    $condiciones_dinamicas .= " AND (" . implode(' OR ', $condiciones) . ")";
}

/* PANTALLA */
if (!empty($pantalla_sel)) {
    $condiciones = [];
    foreach ($pantalla_sel as $v) {
        $condiciones[] = str_replace('{col}', 'p.pantalla', $limpiar_sql) . " LIKE ?";
        $params[] = '%' . limpiarValor($v) . '%';
    }
    $condiciones_dinamicas .= " AND (" . implode(' OR ', $condiciones) . ")";
}

/* TOUCH */
if (!empty($touch_sel)) {
    $placeholders = implode(',', array_fill(0, count($touch_sel), '?'));
    $condiciones_dinamicas .= " AND p.touch IN ($placeholders)";
    $params = array_merge($params, $touch_sel);
}

/* GPU */
if (!empty($expandible_sel)) {
    $placeholders = implode(',', array_fill(0, count($expandible_sel), '?'));
    $condiciones_dinamicas .= " AND p.g_expandible IN ($placeholders)";
    $params = array_merge($params, $expandible_sel);
}

/* FILTRO PRECIO */
$condiciones_dinamicas .= "
    AND TRY_CAST(p.precio AS INT) IS NOT NULL
    AND TRY_CAST(p.precio AS INT) <= ?
";
$params[] = $precio_max;

/* ======================================================
   ENSAMBLAJE DE LA QUERY FINAL CON CTE (CORREGIDO)
====================================================== */
$estado_placeholders = implode(',', array_fill(0, count($estado_sel), '?'));


$query_final = "
    WITH CTE_Productos AS (
        SELECT p.*, 
               c.nombre_serie, 
               c.prefijo,
               c.rango_uso AS descripcion_serie,
               c.regaleria_promocion AS regalo_serie,
               ROW_NUMBER() OVER (PARTITION BY p.id_categoria, p.equipo_modelo, p.clase ORDER BY p.created_at DESC) as rn
        FROM productos_informatica p
        LEFT JOIN categoria c ON p.id_categoria = c.id_categoria
        -- Aplicamos LTRIM y RTRIM para ignorar espacios basura en la columna estado
        WHERE LTRIM(RTRIM(p.estado)) IN ($estado_placeholders)
        $condiciones_dinamicas
    )
    SELECT * FROM CTE_Productos 
    WHERE rn = 1
    ORDER BY created_at DESC
";

/* ======================================================
   EJECUTAR QUERY UNIFICADA CON MERGE CORRECTO
====================================================== */
$stmt = $pdo->prepare($query_final);
$params_finales = array_merge($estado_sel, $params);
$stmt->execute($params_finales);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<style>
.product-card {
    position: relative;
    overflow: hidden;
    transition: all .25s ease;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 1.5rem;
    box-shadow:
        0 1px 3px rgba(0, 0, 0, .04),
        0 8px 20px rgba(15, 23, 42, .04);
}

.product-card:hover {
    transform: translateY(-4px);
    border-color: #93c5fd;
    box-shadow:
        0 15px 35px rgba(37, 99, 235, .10),
        0 5px 15px rgba(0, 0, 0, .05);
}

.product-card::before {
    content: "";
    position: absolute;
    inset: 0;
    background:
        linear-gradient(135deg,
            rgba(37, 99, 235, .04),
            transparent 40%,
            rgba(6, 182, 212, .04));
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

.market-scroll::-webkit-scrollbar {
    height: 6px;
}

.market-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 999px;
}

.market-scroll::-webkit-scrollbar-track {
    background: transparent;
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

                    <!-- AQUI VA FILTRO MARCA EQUIPO -->
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
                        <span class="block mb-4 text-[11px] uppercase tracking-[3px] text-slate-400 font-black">Tipo
                            de
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

                    <div>
                        <span class="block mb-4 text-[11px] uppercase tracking-[3px] text-slate-400 font-black">
                            Categorías / Series
                        </span>
                        <div class="space-y-2">
                            <?php foreach ($categorias_db as $cat): ?>
                            <label class="flex items-center gap-3 mb-3 cursor-pointer">
                                <input type="checkbox" name="categorias[]" value="<?= $cat['id_categoria'] ?>"
                                    onchange="this.form.submit()" class="w-5 h-5 rounded text-blue-600"
                                    <?= in_array($cat['id_categoria'], $categorias_sel) ? 'checked' : '' ?>>

                                <div class="flex flex-col">
                                    <span class="font-semibold text-slate-700">
                                        <?= htmlspecialchars($cat['nombre_serie']) ?>
                                        <span
                                            class="text-xs text-blue-500 font-bold">[<?= htmlspecialchars($cat['prefijo']) ?>]</span>
                                    </span>
                                    <?php if(!empty($cat['rango_uso'])): ?>
                                    <span
                                        class="text-xs text-slate-400"><?= htmlspecialchars($cat['rango_uso']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </form>
            </div>
        </aside>





        <main class="flex-1 h-full overflow-y-auto pr-2">

            <div class="bg-white rounded-3xl border border-slate-200 shadow-sm p-4 lg:p-5 mb-6">

                <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-4">

                    <div>
                        <h2 class="text-3xl font-black text-slate-900 flex items-center gap-3">
                            <i class="fa-solid fa-laptop text-blue-600"></i>
                            Equipos Disponibles
                        </h2>

                        <p class="text-slate-500 mt-2">
                            <?= count($productos) ?> modelos únicos encontrados
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">

                        <span
                            class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-blue-50 text-blue-700 border border-blue-200">
                            Clase A: Excelente estado
                        </span>

                        <span
                            class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">
                            Clase B: Ligeras marcas de uso
                        </span>

                        <span
                            class="px-2.5 py-1 text-[11px] font-bold rounded-lg bg-amber-50 text-amber-700 border border-amber-200">
                            Clase C: Marcas de uso muy notables
                        </span>

                    </div>
                </div>




                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-7 pb-10">
                    <?php if ($productos): ?>
                    <?php foreach ($productos as $i => $p): ?>

                    <?php 
       // 1. Procesador
$familia = strtolower(
    trim(
        $p['proc_familia'] . ' ' .
        $p['proc_modelo'] . ' ' .
        $p['proc_marca']
    )
);

$img_procesador = null;

// Intel
if (strpos($familia, 'i3') !== false) {
    $img_procesador = 'https://dacansdr.com/img/i3.jpg';
} elseif (strpos($familia, 'i5') !== false) {
    $img_procesador = 'https://dacansdr.com/img/i5.png';
} elseif (strpos($familia, 'i7') !== false) {
    $img_procesador = 'https://dacansdr.com/img/i7.png';
} elseif (strpos($familia, 'i9') !== false) {
    $img_procesador = 'https://dacansdr.com/img/i9.png';
}

// AMD
elseif (strpos($familia, 'ryzen 3') !== false || strpos($familia, 'r3') !== false) {
    $img_procesador = 'https://dacansdr.com/img/r3.jpg';
} elseif (strpos($familia, 'ryzen 5') !== false || strpos($familia, 'r5') !== false) {
    $img_procesador = 'https://dacansdr.com/img/r5.png';
} elseif (strpos($familia, 'ryzen 7') !== false || strpos($familia, 'r7') !== false) {
    $img_procesador = 'https://dacansdr.com/img/r7.jpg';
} elseif (strpos($familia, 'ryzen 9') !== false || strpos($familia, 'r9') !== false) {
    $img_procesador = 'https://dacansdr.com/img/r9.jpg';
}

            // 2. NVIDIA (URL EXTERNA que funciona)
            $tiene_nvidia = false;
            if (!empty($p['graficos']) && stripos((string)$p['graficos'], 'nvidia') !== false) {
                $tiene_nvidia = true;
            }
            

// EVALUACIÓN DE DESCUENTO EN PORCENTAJE DECIMAL (Ej: 0.10 = 10%)
$precio_base_num = isset($p['precio']) ? (float)trim($p['precio']) : 0.0;
$descuento_porc  = isset($p['descuento']) ? (float)trim($p['descuento']) : 0.0;

$tiene_descuento = ($descuento_porc > 0.0);

// Si tiene descuento, el precio final es el precio base menos el porcentaje (Ej: precio * (1 - 0.10))
$precio_final_num = $tiene_descuento ? ($precio_base_num * (1 - $descuento_porc)) : $precio_base_num;
        ?>



                    <div
                        class="product-card glass rounded-[2rem] p-4 border border-white/50 shadow-lg flex flex-col justify-between">
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex flex-wrap gap-1 items-center">
                                    <span
                                        class="bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-[9px] uppercase tracking-widest font-black">
                                        <?= htmlspecialchars($p['equipo_marca']) ?>
                                    </span>
                                    <?php if (!empty($p['prefijo'])): ?>
                                    <span
                                        class="bg-slate-900 text-white px-2 py-1 rounded-full text-[9px] font-black uppercase tracking-wider">
                                        <?= htmlspecialchars($p['prefijo']) ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-2 h-2 rounded-full <?= $p['estado'] == 'Vendida' ? 'bg-red-500' : 'bg-green-500' ?>">
                                    </div>
                                    <span class="text-[9px] text-slate-400 font-bold">
                                        <?= !empty($p['serie']) ? htmlspecialchars($p['serie']) : 'Sin Serie' ?>
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
                        $clase_detalle = !empty($p['detalle_clase']) ? $p['detalle_clase'] : 'Clase ' . $p['clase'];
                    ?>
                                <span
                                    class="absolute top-3 right-3 z-30 <?= $bg_color ?> <?= $text_color ?> backdrop-blur-sm text-[9px] font-black uppercase tracking-wider px-2.5 py-1.5 rounded-xl shadow-sm">
                                    <?= htmlspecialchars($clase_detalle) ?>
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
                                    alt="<?= htmlspecialchars($p['equipo_modelo']) ?>"
                                    data-images="<?= $galeria_json ?>" onclick="openModal(this)"
                                    class="w-full h-full object-contain p-3 group-hover:scale-105 transition duration-300">
                                <div
                                    class="absolute inset-0 bg-black/5 opacity-0 group-hover:opacity-100 transition flex items-center justify-center pointer-events-none">
                                    <i
                                        class="fa-solid fa-magnifying-glass-plus text-slate-700 text-lg bg-white/80 p-3 rounded-full shadow"></i>
                                </div>
                                <?php else: ?>
                                <div class="flex flex-col items-center">
                                    <i class="fa-solid fa-laptop text-5xl text-blue-100"></i>
                                    <span
                                        class="mt-2 text-[9px] uppercase tracking-widest text-slate-300 font-black">Foto
                                        Pendiente</span>
                                </div>
                                <?php endif; ?>
                            </div>

                            <h3 class="text-lg font-black text-slate-900 leading-tight line-clamp-2">
                                <?= htmlspecialchars($p['equipo_modelo']) ?>
                            </h3>

                            <?php if (!empty($p['nombre_serie'])): ?>
                            <div class="mt-2 bg-slate-50 border border-slate-100 rounded-xl p-2.5">
                                <div class="flex items-center gap-1.5 mb-0.5">
                                    <i class="fa-solid fa-layer-group text-blue-500 text-xs"></i>
                                    <span class="text-xs font-black text-slate-800 uppercase tracking-wide">
                                        <?= htmlspecialchars($p['nombre_serie']) ?>
                                    </span>
                                </div>
                                <p class="text-[11px] text-slate-600 font-medium leading-normal">
                                    <?= !empty($p['descripcion_serie']) ? htmlspecialchars($p['descripcion_serie']) : '<span class="text-red-400 italic">Falta cargar descripción en SQL</span>' ?>
                                </p>
                            </div>
                            <?php endif; ?>
                            <div class="flex items-center gap-2.5 mt-3">

                                <?php if ($img_procesador): ?>
                                <img src="<?= $img_procesador ?>"
                                    alt="Procesador <?= htmlspecialchars($p['proc_familia']) ?>"
                                    class="h-14 w-14 object-contain flex-shrink-0"
                                    title="<?= htmlspecialchars($p['proc_familia']) ?>">
                                <?php endif; ?>

                                <?php if ($tiene_nvidia): ?>
                                <img src="https://dacansdr.com/img/nvidia.png" alt="Gráficos NVIDIA"
                                    class="h-14 w-14 object-contain flex-shrink-0" title="Incluye Gráficos NVIDIA">
                                <?php endif; ?>

                            </div>
                            <p class="text-blue-600 font-bold mt-2 text-xs">
                                <?= htmlspecialchars($p['proc_marca'] . ' ' . $p['proc_familia'] . ' ' . $p['proc_modelo']) ?>
                            </p>

                            <div class="grid grid-cols-2 gap-2 mt-3 mb-3">
                                <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 text-center">
                                    <span
                                        class="block text-[9px] uppercase tracking-widest text-slate-400 font-black">RAM</span>
                                    <span class="font-black text-sm"><?= htmlspecialchars($p['memoria']) ?></span>
                                </div>
                                <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 text-center">
                                    <span
                                        class="block text-[9px] uppercase tracking-widest text-slate-400 font-black">Disco</span>
                                    <span class="font-black text-sm"><?= htmlspecialchars($p['disco']) ?></span>
                                </div>
                                <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 text-center col-span-2">
                                    <span
                                        class="block text-[9px] uppercase tracking-widest text-slate-400 font-black">Gráficos</span>
                                    <span
                                        class="font-black text-sm text-slate-700"><?= !empty($p['graficos']) ? htmlspecialchars($p['graficos']) : 'No especificado' ?></span>
                                </div>
                                <div class="bg-slate-50 border border-slate-100 rounded-xl p-3 text-center col-span-2">
                                    <span
                                        class="block text-[9px] uppercase tracking-widest text-slate-400 font-black">Pantalla</span>
                                    <span class="font-black text-sm text-slate-700">
                                        <?= !empty($p['pantalla']) ? htmlspecialchars($p['pantalla']) : 'No especificada' ?>
                                        <?php if (!empty($p['touch']) && $p['touch'] == 1): ?> • Touch
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>

                            <?php if (!empty($p['regalo_serie'])): ?>
                            <div
                                class="mb-4 bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200/60 rounded-xl p-2.5 flex items-center gap-2.5 shadow-sm">
                                <div
                                    class="bg-amber-500 text-white w-7 h-7 rounded-lg flex items-center justify-center text-xs shadow-sm flex-shrink-0 animate-bounce">
                                    <i class="fa-solid fa-gift"></i>
                                </div>
                                <div class="leading-tight">
                                    <span
                                        class="block text-[9px] uppercase tracking-wider text-amber-700 font-black">¡Regalo
                                        Incluido!</span>
                                    <span
                                        class="text-[11px] font-bold text-slate-800"><?= htmlspecialchars($p['regalo_serie']) ?></span>
                                </div>
                            </div>
                            <?php elseif (empty($p['regalo_serie']) && !empty($p['nombre_serie'])): ?>
                            <div
                                class="mb-4 bg-red-50 border border-red-200 rounded-xl p-2 text-[10px] text-red-500 italic text-center">
                                Falta enlazar el regalo de la serie en SQL
                            </div>
                            <?php endif; ?>
                        </div>


                        <div class="border-t border-slate-100 pt-3 flex items-center justify-between gap-2 w-full">
                            <div>
                                <?php if ($tiene_descuento): ?>
                                <div class="flex flex-col text-left mb-1">
                                    <span class="text-[18px] text-slate-400 font-bold uppercase tracking-wider">
                                        Antes:&nbsp;&nbsp;<span class="line-through decoration-red-500 decoration-2">RD$
                                            <?= number_format($precio_base_num, 0) ?></span>
                                    </span>
                                    <span class="text-xl font-black text-slate-900 leading-tight mt-0.5">
                                        AHORA:&nbsp;&nbsp;<span class="text-emerald-600"> RD$
                                            <?= number_format($precio_final_num, 0) ?></span>
                                    </span>
                                </div>
                                <?php else: ?>
                                <span class="text-3xl font-black text-slate-900">
                                    RD$ <?= number_format($precio_base_num, 0) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php
                $codigoBase = $p['id_local'] ?? '';
                $codigoFinal = !empty($codigoBase) ? mb_substr((string)$codigoBase, -5, null, 'UTF-8') : '00000';

                $emoji_saludo    = "\xF0\x9F\x91\x8B"; 
                $emoji_medalla   = "\xF0\x9F\x8F\x85"; 
                $emoji_pantalla  = "\xF0\x9F\x92\xBB"; 
                $emoji_engranaje = "\xE2\x9A\x99\xEF\xB8\x8F"; 
                $emoji_disquete  = "\xF0\x9F\x92\xBE"; 
                $emoji_caja      = "\xF0\x9F\x93\xA6"; 
                $emoji_dinero    = "\xF0\x9F\x92\xB0"; 
                $emoji_marcador  = "\xF0\x9F\x94\x96"; 
                $emoji_regalo    = "\xF0\x9F\x8E\x81";

                $clase_explicacion = !empty($p['detalle_clase']) ? $p['detalle_clase'] : "Clase " . $p['clase'];

                // Texto enriquecido para WhatsApp
                $texto = "{$emoji_saludo} Hola, me interesa este equipo:\n\n";
                $texto .= "{$emoji_medalla} Condicion: {$clase_explicacion}\n\n";
                $texto .= "{$emoji_pantalla} {$p['equipo_marca']} {$p['equipo_modelo']}\n";
                if(!empty($p['nombre_serie'])) { 
                    $texto .= "{$emoji_marcador} Linea: {$p['nombre_serie']} ({$p['prefijo']})\n"; 
                    if(!empty($p['descripcion_serie'])) { $texto .= "  ↳ {$p['descripcion_serie']}\n"; }
                }
                $texto .= "{$emoji_engranaje} Procesador: {$p['proc_marca']} {$p['proc_modelo']}\n";
                $texto .= "{$emoji_disquete} RAM: {$p['memoria']}\n";
                $texto .= "{$emoji_caja} Disco: {$p['disco']}\n";
                if(!empty($p['regalo_serie'])) { $texto .= "{$emoji_regalo} Regalo: ¡{$p['regalo_serie']}!\n"; }
                $texto .= "\n{$emoji_dinero} Precio: RD$ " . (!empty($p['precio']) ? number_format((float)$p['precio'], 0) : 'Consultar') . "\n";
                $texto .= "{$emoji_marcador} Codigo: {$codigoFinal}\n";
                ?>

                            <div class="flex items-center gap-1.5 flex-1 justify-end max-w-[175px]">
                                <a href="https://wa.me/18495886436?text=<?= urlencode($texto) ?>" target="_blank"
                                    rel="noopener noreferrer" onclick="event.stopPropagation();"
                                    title="Consultar por WhatsApp"
                                    class="relative z-50 bg-gradient-to-r from-green-500 to-green-600 text-white w-10 h-10 rounded-xl shadow-sm hover:scale-105 transition flex items-center justify-center flex-shrink-0">
                                    <i class="fa-brands fa-whatsapp text-lg"></i>
                                </a>

                                <?php if (!empty($p['precio']) && $p['estado'] !== 'Vendida'): ?>
                                <?php $link_pago = "../precompra.php?id=" . urlencode($p['id_local']); ?>
                                <a href="<?= $link_pago ?>" rel="noopener noreferrer" onclick="event.stopPropagation();"
                                    title="Comprar en Línea"
                                    class="relative z-50 bg-gradient-to-r from-blue-600 to-indigo-700 text-white w-9 h-9 rounded-xl shadow-sm hover:scale-105 active:scale-95 transition flex items-center justify-center">
                                    <i class="fa-solid fa-credit-card text-sm"></i>
                                </a>
                                <?php else: ?>
                                <button disabled title="No disponible"
                                    class="bg-slate-100 text-slate-300 w-9 h-9 rounded-xl transition flex items-center justify-center cursor-not-allowed">
                                    <i class="fa-solid fa-ban text-sm"></i>
                                </button>
                                <?php endif; ?>
                            </div>
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

        <div id="imageModal"
            class="fixed inset-0 bg-black/95 z-[100] hidden flex-col items-center justify-center p-4 md:p-10 transition-all duration-300 opacity-0">
            <button onclick="closeModal()"
                class="absolute top-6 right-6 text-white/80 hover:text-white text-3xl z-[110] bg-white/10 w-12 h-12 rounded-full flex items-center justify-center hover:bg-white/20 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="relative max-w-5xl w-full h-[60vh] md:h-[70vh] flex items-center justify-center">
                <button id="prevBtn" onclick="changeImage(-1)"
                    class="absolute left-2 md:left-6 text-white/80 hover:text-white text-2xl md:text-4xl z-50 bg-black/40 p-3 md:p-4 rounded-full transition hover:bg-black/60">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>

                <img id="modalImage" src="" class="max-w-full max-h-full object-contain select-none rounded-lg">

                <button id="nextBtn" onclick="changeImage(1)"
                    class="absolute right-2 md:right-6 text-white/80 hover:text-white text-2xl md:text-4xl z-50 bg-black/40 p-3 md:p-4 rounded-full transition hover:bg-black/60">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>

            <div id="modalCounter" class="text-white/60 text-sm font-bold mt-4 tracking-widest uppercase"></div>
            <div id="modalThumbnails" class="flex gap-2 mt-4 overflow-x-auto max-w-full p-2"></div>
        </div>
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


    // --- CONTROL DE FILTROS RESPONSIVE (Aislado para evitar errores de duplicidad) ---
    {
        const filtersPanel = document.getElementById('filtersPanel');
        const filtersOverlay = document.getElementById('filtersOverlay');
        const openFilters = document.getElementById('openFilters');
        const closeFilters = document.getElementById('closeFilters');

        if (openFilters && filtersPanel && filtersOverlay) {
            openFilters.addEventListener('click', () => {
                filtersPanel.classList.remove('-translate-x-full');
                filtersOverlay.classList.remove('hidden');
            });
        }

        if (closeFilters && filtersPanel && filtersOverlay) {
            closeFilters.addEventListener('click', () => {
                filtersPanel.classList.add('-translate-x-full');
                filtersOverlay.classList.add('hidden');
            });
        }

        if (filtersOverlay && filtersPanel) {
            filtersOverlay.addEventListener('click', () => {
                filtersPanel.classList.add('-translate-x-full');
                filtersOverlay.classList.add('hidden');
            });
        }
    }

    function toggleMobileMenu() {
        const menu = document.getElementById('mobileMenu');
        if (menu) {
            menu.classList.toggle('hidden');
        }
    }

    // --- SISTEMA DEL MODAL DE IMÁGENES ---
    let currentImages = [];
    let currentIndex = 0;

    function openModal(element) {
        try {
            currentImages = JSON.parse(element.getAttribute('data-images')) || [];
        } catch (e) {
            currentImages = [element.src];
        }

        currentIndex = 0;
        const modal = document.getElementById('imageModal');

        if (!modal) {
            console.error("Error: No se encontró el contenedor HTML con id='imageModal'");
            return;
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');

        // Forzamos un reflow para que la transición de Tailwind funcione correctamente
        void modal.offsetWidth;

        modal.classList.remove('opacity-0');
        modal.classList.add('opacity-100');

        document.body.style.overflow = 'hidden'; // Detiene el scroll de la página de fondo
        updateModalImage();
    }

    function closeModal() {
        const modal = document.getElementById('imageModal');
        if (!modal) return;

        modal.classList.remove('opacity-100');
        modal.classList.add('opacity-0');
        document.body.style.overflow = '';

        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 300);
    }

    function updateModalImage() {
        const modalImg = document.getElementById('modalImage');
        const counter = document.getElementById('modalCounter');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        if (!modalImg || currentImages.length === 0) return;

        modalImg.src = currentImages[currentIndex];

        if (counter) {
            counter.innerText = `Imagen ${currentIndex + 1} de ${currentImages.length}`;
        }

        // Control de visibilidad de las flechas de navegación
        if (prevBtn && nextBtn) {
            if (currentImages.length <= 1) {
                prevBtn.classList.add('hidden');
                nextBtn.classList.add('hidden');
            } else {
                prevBtn.classList.remove('hidden');
                nextBtn.classList.remove('hidden');
            }
        }

        // Renderizar miniaturas dinámicas si hay más de una foto
        const thumbsContainer = document.getElementById('modalThumbnails');
        if (thumbsContainer) {
            thumbsContainer.innerHTML = '';
            if (currentImages.length > 1) {
                currentImages.forEach((img, index) => {
                    const thumb = document.createElement('img');
                    thumb.src = img;
                    thumb.className =
                        `w-12 h-12 object-cover rounded-xl cursor-pointer transition border-2 ${index === currentIndex ? 'border-blue-500 scale-105' : 'border-transparent opacity-50'}`;
                    thumb.onclick = () => {
                        currentIndex = index;
                        updateModalImage();
                    };
                    thumbsContainer.appendChild(thumb);
                });
            }
        }
    }

    function changeImage(direction) {
        if (currentImages.length === 0) return;
        currentIndex += direction;
        if (currentIndex >= currentImages.length) currentIndex = 0;
        if (currentIndex < 0) currentIndex = currentImages.length - 1;
        updateModalImage();
    }

    // Navegación por teclado (Escape para cerrar, Flechas para navegar)
    document.addEventListener('keydown', (e) => {
        const modal = document.getElementById('imageModal');
        if (modal && !modal.classList.contains('hidden')) {
            if (e.key === 'Escape') closeModal();
            if (e.key === 'ArrowRight' && currentImages.length > 1) changeImage(1);
            if (e.key === 'ArrowLeft' && currentImages.length > 1) changeImage(-1);
        }
    });
    </script>
</body>

</html>
</body>

</html>