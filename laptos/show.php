<?php
session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

/* BASE DE DATOS SQL SERVER */
require '../config/conexion.php';

/* ID */
$id = $_GET['id'] ?? '';

if (empty($id)) {
    die("ID no especificado");
}

/* CONSULTA */
$stmt = $pdo->prepare("
    SELECT TOP 1 *
    FROM productos_informatica
    WHERE id_local = ?
    AND estado = 'Lista'
");

$stmt->execute([$id]);

$e = $stmt->fetch(PDO::FETCH_ASSOC);

/* VALIDAR */
if (!$e) {
    die('Equipo no encontrado o con el estado "No Lista"');
}

/* PRODUCTO ANTERIOR */
$stmtPrev = $pdo->prepare("
    SELECT TOP 1 id_local
    FROM productos_informatica
    WHERE id_local < ?
    AND estado = 'Lista'
    ORDER BY id_local DESC
");

$stmtPrev->execute([$id]);

$prev = $stmtPrev->fetchColumn();

/* PRODUCTO SIGUIENTE */
$stmtNext = $pdo->prepare("
    SELECT TOP 1 id_local
    FROM productos_informatica
    WHERE id_local > ?
    AND estado = 'Lista'
    ORDER BY id_local ASC
");

$stmtNext->execute([$id]);

$next = $stmtNext->fetchColumn();
?>
<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>
<?= htmlspecialchars($e['equipo_marca'] ?? '') ?>
<?= htmlspecialchars($e['equipo_modelo'] ?? '') ?>
</title>

<!-- TAILWIND -->
<script src="https://cdn.tailwindcss.com"></script>
<link rel="shortcut icon" href="/img/favicon.ico">
<!-- ICONOS -->
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<!-- FUENTES -->
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@400;600;700;900&display=swap"
rel="stylesheet">

<style>

:root{

    --bg:#020617;
    --card1:#050a19;
    --card2:#02060f;

    --primary:#2563eb;
    --secondary:#1d4ed8;

    --text:#ffffff;
    --soft:#93c5fd;

    --spec1:#081428;
    --spec2:#050a14;

    --border:rgba(59,130,246,.45);

}

/* ===== TEMAS ===== */

.theme-blue{

    --bg:#020617;
    --card1:#050a19;
    --card2:#02060f;
    --primary:#2563eb;
    --secondary:#1d4ed8;
    --text:#ffffff;
    --soft:#93c5fd;
    --spec1:#081428;
    --spec2:#050a14;
    --border:rgba(59,130,246,.45);

}

.theme-red{

    --bg:#120202;
    --card1:#250505;
    --card2:#120202;
    --primary:#ef4444;
    --secondary:#b91c1c;
    --text:#ffffff;
    --soft:#fecaca;
    --spec1:#2b0808;
    --spec2:#160505;
    --border:rgba(239,68,68,.45);

}

.theme-green{

    --bg:#02120b;
    --card1:#042014;
    --card2:#02110a;
    --primary:#10b981;
    --secondary:#047857;
    --text:#ffffff;
    --soft:#a7f3d0;
    --spec1:#052418;
    --spec2:#03140d;
    --border:rgba(16,185,129,.45);

}

.theme-white{

    --bg:#eef4ff;

    --card1:#ffffff;
    --card2:#f1f7ff;

    --primary:#2563eb;
    --secondary:#1d4ed8;

    --text:#0f172a;
    --soft:#2563eb;

    --spec1:#ffffff;
    --spec2:#edf4ff;

    --border:rgba(37,99,235,.18);

}

/* ===== NARANJA ===== */

.theme-orange{

    --bg:#1a0d02;

    --card1:#2a1405;
    --card2:#140802;

    --primary:#f97316;
    --secondary:#ea580c;

    --text:#ffffff;
    --soft:#fdba74;

    --spec1:#2b1305;
    --spec2:#180902;

    --border:rgba(249,115,22,.35);

}

/* ===== MORADO ===== */

.theme-purple{

    --bg:#12051f;

    --card1:#1e0833;
    --card2:#11041d;

    --primary:#a855f7;
    --secondary:#7e22ce;

    --text:#ffffff;
    --soft:#d8b4fe;

    --spec1:#22093b;
    --spec2:#140624;

    --border:rgba(168,85,247,.35);

}

.theme-grey{

    --bg:#1f2937;

    --card1:#374151;
    --card2:#1f2937;

    --primary:#6b7280;
    --secondary:#4b5563;

    --text:#ffffff;
    --soft:#d1d5db;

    --spec1:#374151;
    --spec2:#1f2937;

    --border:rgba(107,114,128,.35);

}
.theme-yellow{

    --bg:#1a1200;

    --card1:#2b1e05;
    --card2:#140f02;

    --primary:#facc15;
    --secondary:#eab308;

    --text:#ffffff;
    --soft:#fde68a;

    --spec1:#2c1f05;
    --spec2:#180f03;

    --border:rgba(250,204,21,.35);

}
/* ===== BODY ===== */

body{

    margin:0;
    padding:10px;

    background:var(--bg);

    font-family:'Inter',sans-serif;

    transition:.3s;

    overflow-x:hidden;
}

/* FUENTE */
.font-tech{
    font-family:'Orbitron',sans-serif;
}

/* PANEL BOTONES */

.controls{

    width:100%;
    max-width:430px;

    margin:auto auto 12px auto;

    background:rgba(255,255,255,.05);

    backdrop-filter:blur(10px);

    border-radius:22px;

    padding:12px;

    border:1px solid rgba(255,255,255,.08);

}

.control-title{

    color:white;
    font-size:12px;
    text-transform:uppercase;
    margin-bottom:10px;
    font-weight:900;
    letter-spacing:1px;

}

.theme-white .control-title{
    color:#111827;
}

.theme-buttons{

    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:10px;

}

.btn-theme{

    border:none;
    border-radius:14px;
    padding:12px;
    color:white;
    font-weight:800;
    cursor:pointer;
    transition:.2s;
    text-transform:uppercase;

}

.btn-theme:hover{
    transform:scale(.97);
}

.btn-blue{
    background:#2563eb;
}

.btn-red{
    background:#dc2626;
}

.btn-green{
    background:#059669;
}

.btn-white{
    background:#ffffff;
    color:#111827;
    border:1px solid #d1d5db;
}

.btn-toggle{

    width:100%;
    margin-top:12px;

    background:#111827;
    color:white;

    border:none;
    border-radius:14px;

    padding:12px;

    font-weight:800;
    cursor:pointer;

}

/* TARJETA */
.poster{

    width:100%;
    max-width:430px;
 aspect: ratio 4px / 6px;;
 padding-bottom:8px;
    margin:auto;

    background:
    linear-gradient(180deg,
    var(--card1),
    var(--card2));

    border:1.5px solid var(--border);

    border-radius:28px;

    overflow:hidden;

    position:relative;

    transition:.3s;

    box-shadow:
    0 0 30px rgba(0,0,0,.25);

}

/* GRID */
.poster::before{

    content:'';

    position:absolute;
    inset:0;

    background-image:
    linear-gradient(rgba(255,255,255,.03) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,.03) 1px, transparent 1px);

    background-size:22px 22px;

    opacity:.15;

    pointer-events:none;
}

/* CAJAS */
.spec{

    background:
    linear-gradient(180deg,
    var(--spec1),
    var(--spec2));

    border:1px solid var(--border);

    border-radius:16px;

    transition:.3s;

}

/* COMBO */
.combo{

    background:
    linear-gradient(180deg,
    var(--spec1),
    var(--spec2));

    border:1px solid var(--border);

    border-radius:20px;

}

/* PRECIO */
.price{

    background:
    linear-gradient(180deg,
    var(--primary) 0%,
    var(--secondary) 100%);

}

/* TEXTOS */

.main-text{
    color:var(--text);
}

.soft-text{
    color:var(--soft);
}

.icon-color{
    color:var(--primary);
}

.theme-white .poster::before{
    opacity:.05;
}

.export-mode .spec{

    display:flex !important;
    align-items:center !important;

}

.export-mode .spec i{

    transform:translateY(0px);

}
.btn-orange{
    background:#f97316;
}

.btn-purple{
    background:#9333ea;
}

.btn-yellow{
    background:#facc15;
}
.btn-grey{
    background:#6b7280;
}

</style>

</head>

<body class="theme-white" id="bodyTheme">

<!-- CONTROLES -->
<div class="controls">

    <div class="control-title">
        Cambiar diseño
    </div>

    <div class="theme-buttons">

        <button class="btn-theme btn-blue"
        onclick="changeTheme('theme-blue')">
            Azul
        </button>

        <button class="btn-theme btn-red"
        onclick="changeTheme('theme-red')">
            Rojo
        </button>

        <button class="btn-theme btn-green"
        onclick="changeTheme('theme-green')">
            Verde
        </button>

        <button class="btn-theme btn-white"
        onclick="changeTheme('theme-white')">
            Blanco
        </button>

        <button class="btn-theme btn-orange"
        onclick="changeTheme('theme-orange')">
            Naranja
        </button>

        <button class="btn-theme btn-purple"
        onclick="changeTheme('theme-purple')">
            Morado
        </button>

        <button class="btn-theme btn-grey"
        onclick="changeTheme('theme-grey')">
            Gris    
        </button>

        <button class="btn-theme btn-yellow"
        onclick="changeTheme('theme-yellow')">
            Amarillo
        </button>

    </div>

    <button class="btn-toggle"
    onclick="toggleAccesorios()">

        Mostrar / Ocultar accesorios

    </button>

    <button class="btn-toggle"
onclick="descargarImagen()">

    Descargar imagen

</button>

</div>

<!-- NAVEGACION -->
<div class="flex justify-center gap-3 mb-3">

    <?php if ($prev): ?>
        <a href="?id=<?= $prev ?>"
        class="w-12 h-12
        rounded-full
        flex items-center justify-center
        bg-white
        shadow-lg
        text-black
        text-xl
        hover:scale-95 transition">

            <i class="fa-solid fa-chevron-left"></i>

        </a>
    <?php endif; ?>

    <?php if ($next): ?>
        <a href="?id=<?= $next ?>"
        class="w-12 h-12
        rounded-full
        flex items-center justify-center
        bg-white
        shadow-lg
        text-black
        text-xl
        hover:scale-95 transition">

            <i class="fa-solid fa-chevron-right"></i>

        </a>
    <?php endif; ?>


</div>

<div class="poster max-w-[360px] mx-auto p-1.5">

    <!-- LOGO -->
    <div class="text-center">

        <img src="../img/logo.webp"
        class="h-10 mx-auto">

        <h1 class="font-tech
        main-text
        text-[13px]
        uppercase
        leading-none
        font-black mt-2">

            <?= htmlspecialchars($e['equipo_marca'] ?? '') ?>

        </h1>

        <p class="soft-text
        text-[13px]
        font-bold
        uppercase
        tracking-wide
        mt-1 leading-tight">

            <?= htmlspecialchars($e['equipo_modelo'] ?? '') ?>

        </p>

    </div>

    <!-- IMAGEN -->
    <div class="mt-2">

        <img
        src="<?= !empty($e['imagen_url']) ? $e['imagen_url'] : '../img/default.png' ?>"
        class="w-[52%] h-40 mx-auto object-contain drop-shadow-[0_12px_20px_rgba(0,0,0,.45)]">

    </div>

    <!-- SPECS -->
    <div class="grid grid-cols-2 gap-1 mt-2">

        <!-- PROCESADOR -->
        <div class="spec flex items-center gap-1 p-1">

            <i class="fa-solid fa-microchip icon-color text-lg"></i>

            <div class="flex-1 text-center">

                <p class="text-[8px] soft-text uppercase">
                    Procesador
                </p>

                <p class="main-text text-[9px] font-bold leading-tight">

                    <?= htmlspecialchars(
                        ($e['proc_marca'] ?? '') . ' ' .
                        ($e['proc_familia'] ?? '') . ' ' .
                        ($e['proc_modelo'] ?? '')
                    ) ?>

                </p>

            </div>

        </div>

        <!-- RAM -->
        <div class="spec flex items-center gap-1 p-1">

            <i class="fa-solid fa-memory icon-color text-sm"></i>

            <div class="flex-1 text-center">

                <p class="text-[8px] soft-text uppercase">
                    RAM
                </p>

                <p class="main-text text-[9px] font-bold">
                    <?= htmlspecialchars($e['memoria'] ?? '') ?>
                </p>

            </div>

        </div>

        <!-- SSD -->
        <div class="spec flex items-center gap-1 p-1">

            <i class="fa-solid fa-hard-drive icon-color text-sm"></i>

            <div class="flex-1 text-center">

                <p class="text-[9px] soft-text uppercase">
                    SSD
                </p>

                <p class="main-text text-[9px] font-bold">
                    <?= htmlspecialchars($e['disco'] ?? '') ?>
                </p>

            </div>

        </div>

        <!-- PANTALLA -->
        <div class="spec flex items-center gap-1 p-1">

            <i class="fa-solid fa-display icon-color text-sm"></i>

            <div class="flex-1 text-center">

                <p class="text-[9px] soft-text uppercase">
                    Pantalla
                </p>

                <p class="main-text text-[9px] font-bold">
                    <?= htmlspecialchars($e['pantalla'] ?? '') ?>
                </p>

            </div>

        </div>

        <!-- GRAFICOS -->
        <div class="spec col-span-2 flex items-center gap-1 p-1">

            <i class="fa-solid fa-gauge-high icon-color text-sm"></i>

            <div class="flex-1 text-center">

                <p class="text-[9px] soft-text uppercase">
                    Gráficos
                </p>

                <p class="main-text text-[9px] font-bold">
                    <?= htmlspecialchars($e['graficos'] ?: 'Integrado') ?>
                </p>

            </div>

        </div>

    </div>
<!-- ACCESORIOS -->
<div class="combo mt-1 p-1.5"
id="accesoriosSection">

    <div class="text-center mb-1">

        <span class="text-[9px]
        px-2 py-[2px]
        rounded-full
        uppercase
        font-bold tracking-wide"

        style="
        background:var(--primary);
        color:white;
        ">

            Accesorios incluidos

        </span>

    </div>

    <div class="grid grid-cols-3 gap-1 text-center">

        <div>

            <img src="../img/mochila.png"
            class="h-14 mx-auto object-contain">

            <p class="main-text text-[9px] mt-[2px] uppercase leading-tight">
                Mochila
            </p>

        </div>

        <div>

            <img src="../img/audifonos.png"
            class="h-14 mx-auto object-contain">

            <p class="main-text text-[9px] mt-[2px] uppercase leading-tight">
                Audífonos
            </p>

        </div>

        <div>

            <img src="../img/teclado_mouse.png"
            class="h-14 mx-auto object-contain">

            <p class="main-text text-[9px] mt-[2px] uppercase leading-tight">
                Teclado + Mouse
            </p>

        </div>

    </div>

</div>

<!-- FOOTER -->
<div class="grid grid-cols-3 gap-3px mt-2">

   <!-- PRECIO -->
   <div class="price col-span-2 p-3 rounded-[13px]">

        <p class="text-white
        flex justify-center items-center
        text-[11px]
        uppercase">

            Precio especial

        </p>

        <h2 class="font-tech
        text-white
        text-[22px]
        leading-none
        font-black italic mt-1
        flex justify-center items-center"
        id="precioFinal">

        </h2>

   </div>

   <!-- GARANTÍA -->
   <div class="spec
   flex flex-col
   items-center
   justify-center
   text-center
   p-2">

        <div class="w-8 h-8
        rounded-full
        flex items-center justify-center
        bg-blue-500/15
        border border-blue-400/30">

            <i class="fa-solid fa-shield-halved
            text-blue-400 text-[13px]"></i>

        </div>

        <!-- NUEVO -->
        <p class="main-text
        text-[8px]
        font-black
        mt-1
        uppercase
        leading-none">

            Equipo Usado

        </p>

        <p class="main-text
        text-[11px]
        font-black
        mt-1
        uppercase
        leading-none
        text-center">

            1 Año Garantía

        </p>

   </div>

</div>

<!-- FOOTER CONTACTO -->
<div class="mt-2 pt-2 border-t border-white/10">

    <div class="flex items-center justify-center gap-3 text-center flex-wrap">

        <!-- INSTAGRAM -->
        <div class="flex items-center gap-3px">

            <i class="fa-brands fa-instagram text-pink-400 text-[10px]"></i>

            <span class="main-text text-[9px] font-bold">
                @dacanscomputers
            </span>

        </div>

        <!-- WEB -->
        <div class="flex items-center gap-3px">

            <i class="fa-solid fa-globe text-blue-400 text-[10px]"></i>

            <span class="main-text text-[9px] font-bold">
                www.dacansdr.com
            </span>

        </div>

        <!-- WHATSAPP -->
        <div class="flex items-center gap-3px">

            <i class="fa-brands fa-whatsapp text-green-400 text-[10px]"></i>

            <span class="main-text text-[9px] font-bold">
                849-588-6436
            </span>

        </div>

    </div>

</div>

</div>



<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>

const precioBase = <?= (float)($e['precio'] ?? 0) ?>;
const precioConAccesorios = precioBase + 3495;

let accesoriosVisibles = true;

/* CAMBIAR TEMA */
function changeTheme(theme){

    document.body.className = theme;

}

/* FORMATO PRECIO */
function formatearPrecio(valor){

    return valor.toLocaleString('en-US');

}

/* MOSTRAR / OCULTAR ACCESORIOS */
function toggleAccesorios(){

    const sec = document.getElementById('accesoriosSection');
    const precio = document.getElementById('precioFinal');

    accesoriosVisibles = !accesoriosVisibles;

    if(accesoriosVisibles){

        sec.style.display = 'block';

        precio.innerHTML = `
            RD$ ${formatearPrecio(precioConAccesorios)}
        `;

    }else{

        sec.style.display = 'none';

        precio.innerHTML = `
            RD$ ${formatearPrecio(precioBase)}
        `;
    }

}

/* PRECIO INICIAL */
document.getElementById('precioFinal').innerHTML = `
    RD$ ${formatearPrecio(precioConAccesorios)}
`;

/* CONVERTIR IMG A BASE64 */
async function convertirImagenBase64(img){

    try{

        const response = await fetch(img.src);

        const blob = await response.blob();

        return await new Promise((resolve)=>{

            const reader = new FileReader();

            reader.onloadend = ()=> resolve(reader.result);

            reader.readAsDataURL(blob);

        });

    }catch(e){

        return img.src;

    }

}

/* DESCARGAR */
async function descargarImagen(){

    const tarjeta = document.querySelector('.poster');

    tarjeta.classList.add('export-mode');

    const images = tarjeta.querySelectorAll('img');

    const originales = [];

    for(const img of images){

        originales.push(img.src);

        try{

            const base64 = await convertirImagenBase64(img);

            img.src = base64;

        }catch(e){}

    }

    await new Promise(r => setTimeout(r, 500));

    const canvas = await html2canvas(tarjeta, {

        scale: 4,
        useCORS: true,
        backgroundColor: null,
        logging: false

    });

    // Restaurar imágenes originales
    images.forEach((img, i)=>{

        img.src = originales[i];

    });

    tarjeta.classList.remove('export-mode');

    const link = document.createElement('a');

    link.download = 'producto-dacans.png';

    link.href = canvas.toDataURL('image/png');

    link.click();

}

</script>

</body>
</html>