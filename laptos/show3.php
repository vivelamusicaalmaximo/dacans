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

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>
        <?= htmlspecialchars($e['equipo_marca'] ?? '') ?>
        <?= htmlspecialchars($e['equipo_modelo'] ?? '') ?>
    </title>

    <!-- TAILWIND -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="shortcut icon" href="/img/favicon.ico">
    <!-- ICONOS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <!-- FUENTES -->
    <link
        href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Inter:wght@400;600;700;900&display=swap"
        rel="stylesheet">

    <style>
    /* ===== BODY ===== */

    body {

        margin: 0;
        padding: 15px;

        background:

            linear-gradient(180deg,
                #030712,
                #000000);

        font-family: 'Inter', sans-serif;

        overflow-x: hidden;

    }

    /* ===== TARJETA ===== */

    .poster {

        width: 100%;
        max-width: 430px;

        margin: auto;

        position: relative;

        overflow: hidden;

        border-radius: 32px;

        background:

            linear-gradient(135deg,
                #050816,
                #0f172a);

        border: 1px solid rgba(59, 130, 246, .30);

        box-shadow:

            0 0 20px rgba(37, 99, 235, .25),

            0 0 60px rgba(37, 99, 235, .15),

            0 40px 100px rgba(0, 0, 0, .60);

    }

    /* NEON */

    .poster::before {

        content: '';

        position: absolute;

        inset: 0;

        background:

            linear-gradient(135deg,
                transparent,
                rgba(37, 99, 235, .08),
                transparent);

    }

    /* ESQUINAS */

    .poster::after {

        content: '';

        position: absolute;

        inset: 0;

        border-radius: 32px;

        box-shadow:

            inset 0 0 40px rgba(37, 99, 235, .15);

    }

    /* SPECS */

    .spec {

        background:

            linear-gradient(180deg,
                rgba(37, 99, 235, .12),
                rgba(0, 0, 0, .20));

        border: 1px solid rgba(37, 99, 235, .25);

        border-radius: 18px;

    }

    /* COMBO */

    .combo {

        background:

            linear-gradient(180deg,
                rgba(37, 99, 235, .10),
                rgba(0, 0, 0, .20));

        border: 1px solid rgba(37, 99, 235, .25);

        border-radius: 22px;

    }

    /* PRECIO */

    .price {

        background:

            linear-gradient(135deg,
                #2563eb,
                #3b82f6,
                #06b6d4);

        border-radius: 18px;

        box-shadow:

            0 0 30px rgba(37, 99, 235, .50);

    }

    /* TITULOS */

    .font-tech {

        font-family: 'Orbitron', sans-serif;

        text-transform: uppercase;

        letter-spacing: 1px;

    }

    /* TEXTOS */

    .main-text {

        color: white;

    }

    .soft-text {

        color: #93c5fd;

    }

    .icon-color {

        color: #60a5fa;

    }

    /* BOTONES */

    .btn-orange {
        background: #f97316;
    }

    .btn-purple {
        background: #9333ea;
    }

    .btn-yellow {
        background: #facc15;
    }

    .btn-grey {
        background: #6b7280;
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

            <button class="btn-theme btn-blue" onclick="changeTheme('theme-blue')">
                Azul
            </button>

            <button class="btn-theme btn-red" onclick="changeTheme('theme-red')">
                Rojo
            </button>

            <button class="btn-theme btn-green" onclick="changeTheme('theme-green')">
                Verde
            </button>

            <button class="btn-theme btn-white" onclick="changeTheme('theme-white')">
                Blanco
            </button>

            <button class="btn-theme btn-orange" onclick="changeTheme('theme-orange')">
                Naranja
            </button>

            <button class="btn-theme btn-purple" onclick="changeTheme('theme-purple')">
                Morado
            </button>

            <button class="btn-theme btn-grey" onclick="changeTheme('theme-grey')">
                Gris
            </button>

            <button class="btn-theme btn-yellow" onclick="changeTheme('theme-yellow')">
                Amarillo
            </button>

        </div>

        <button class="btn-toggle" onclick="toggleAccesorios()">

            Mostrar / Ocultar accesorios

        </button>

        <button class="btn-toggle" onclick="descargarImagen()">

            Descargar imagen

        </button>

    </div>

    <!-- NAVEGACION -->
    <div class="flex justify-center gap-3 mb-3">

        <?php if ($prev): ?>
        <a href="?id=<?= $prev ?>" class="w-12 h-12
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
        <a href="?id=<?= $next ?>" class="w-12 h-12
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

            <img src="../img/logo.webp" class="h-10 mx-auto">

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

            <img src="<?= !empty($e['imagen_url']) ? $e['imagen_url'] : '../img/default.png' ?>"
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
        <div class="combo mt-1 p-1.5" id="accesoriosSection">

            <div class="text-center mb-1">

                <span class="text-[9px]
        px-2 py-[2px]
        rounded-full
        uppercase
        font-bold tracking-wide" style="
        background:var(--primary);
        color:white;
        ">

                    Accesorios incluidos

                </span>

            </div>

            <div class="grid grid-cols-3 gap-1 text-center">

                <div>

                    <img src="../img/mochila.png" class="h-14 mx-auto object-contain">

                    <p class="main-text text-[9px] mt-[2px] uppercase leading-tight">
                        Mochila
                    </p>

                </div>

                <div>

                    <img src="../img/audifonos.png" class="h-14 mx-auto object-contain">

                    <p class="main-text text-[9px] mt-[2px] uppercase leading-tight">
                        Audífonos
                    </p>

                </div>

                <div>

                    <img src="../img/teclado_mouse.png" class="h-14 mx-auto object-contain">

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
        flex justify-center items-center" id="precioFinal">

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
    function changeTheme(theme) {

        document.body.className = theme;

    }

    /* FORMATO PRECIO */
    function formatearPrecio(valor) {

        return valor.toLocaleString('en-US');

    }

    /* MOSTRAR / OCULTAR ACCESORIOS */
    function toggleAccesorios() {

        const sec = document.getElementById('accesoriosSection');
        const precio = document.getElementById('precioFinal');

        accesoriosVisibles = !accesoriosVisibles;

        if (accesoriosVisibles) {

            sec.style.display = 'block';

            precio.innerHTML = `
            RD$ ${formatearPrecio(precioConAccesorios)}
        `;

        } else {

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
    async function convertirImagenBase64(img) {

        try {

            const response = await fetch(img.src);

            const blob = await response.blob();

            return await new Promise((resolve) => {

                const reader = new FileReader();

                reader.onloadend = () => resolve(reader.result);

                reader.readAsDataURL(blob);

            });

        } catch (e) {

            return img.src;

        }

    }

    /* DESCARGAR */
    async function descargarImagen() {

        const tarjeta = document.querySelector('.poster');

        tarjeta.classList.add('export-mode');

        const images = tarjeta.querySelectorAll('img');

        const originales = [];

        for (const img of images) {

            originales.push(img.src);

            try {

                const base64 = await convertirImagenBase64(img);

                img.src = base64;

            } catch (e) {}

        }

        await new Promise(r => setTimeout(r, 500));

        const canvas = await html2canvas(tarjeta, {

            scale: 4,
            useCORS: true,
            backgroundColor: null,
            logging: false

        });

        // Restaurar imágenes originales
        images.forEach((img, i) => {

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