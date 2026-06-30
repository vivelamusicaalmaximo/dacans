<?php
session_start();

// --- SISTEMA AUTOMÁTICO DE GUARDADO DE COMENTARIOS ---
$json_file = 'comentarios.json';

// Si no existe el archivo de comentarios, creamos uno con datos de ejemplo premium
if (!file_exists($json_file)) {
    $comentarios_iniciales = [
        ["nombre" => "Carlos Mendoza", "estrellas" => 5, "comentario" => "Excelente servicio. Compré una ThinkPad T14 Clase A y está literalmente como nueva. Súper recomendados.", "fecha" => "02 Jun 2026"],
        ["nombre" => "Sofía Rodríguez", "estrellas" => 5, "comentario" => "La atención por WhatsApp fue muy rápida y profesional. El monitor LG MyView es una joya para mi espacio de trabajo.", "fecha" => "28 May 2026"]
    ];
    file_put_contents($json_file, json_encode($comentarios_iniciales, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/* =========================================================
   1. INCLUIR CONEXIÓN A LA BASE DE DATOS (Faltaba esto 🔑)
========================================================= */
require 'config/conexion.php'; 

/* =========================================================
   2. REGISTRO AUTOMÁTICO DE VISITAS
========================================================= */
try {
    $ip_usuario = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
    
    // Esto detecta automáticamente la ruta de esta nueva página
    $pagina_visitada = $_SERVER['REQUEST_URI'] ?? 'Otra Pagina';

    $sqlVisita = "INSERT INTO registro_visitas (ip_usuario, pagina_visitada) VALUES (?, ?)";
    $stmtVisita = $pdo->prepare($sqlVisita);
    $stmtVisita->execute([$ip_usuario, $pagina_visitada]);
} catch (Exception $e) {
    // Silencioso: si falla la DB por las visitas, los comentarios siguen funcionando
}

// Procesar el formulario cuando el usuario envía un comentario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'nuevo_comentario') {
    $nombre = strip_tags(trim($_POST['nombre'] ?? 'Anónimo'));
    $estrellas = min(5, max(1, intval($_POST['estrellas'] ?? 5)));
    $comentario = strip_tags(trim($_POST['comentario'] ?? ''));
    $fecha = date('d M Y');

    if (!empty($comentario)) {
        $comentarios_actuales = json_decode(file_get_contents($json_file), true) ?: [];
        // Insertar al inicio para que salgan los más nuevos primero
        array_unshift($comentarios_actuales, [
            "nombre" => $nombre,
            "estrellas" => $estrellas,
            "comentario" => $comentario,
            "fecha" => $fecha
        ]);
        file_put_contents($json_file, json_encode($comentarios_actuales, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    // Redireccionar para evitar el reenvío de formulario al recargar
    header("Location: " . $_SERVER['PHP_SELF'] . "#testimonios");
    exit;
}

// Leer comentarios para mostrarlos abajo
$comentarios = json_decode(file_get_contents($json_file), true) ?: [];
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DACANS Computers | Tecnología Premium</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon.png">
    <link rel="shortcut icon" href="/img/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
    html {
        scroll-behavior: smooth;
    }

    body {
        background: #f8fafc;
    }

    .glass {
        background: rgba(255, 255, 255, .8);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
    }

    .card-hover {
        transition: all .35s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .card-hover:hover {
        transform: translateY(-10px);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
    }

    .carousel::-webkit-scrollbar {
        display: none;
    }

    .carousel {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    section {
        scroll-margin-top: 80px;
    }

    /* Estilos personalizados para las estrellas interactivas */
    .star-rating i {
        transition: transform 0.2s ease, color 0.2s ease;
    }

    .star-rating i:hover {
        transform: scale(1.2);
    }
    </style>
</head>

<body class="text-slate-800">

    <header class="fixed top-0 left-0 w-full z-50 glass border-b border-slate-200/50">
        <div class="container mx-auto px-6 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="bg-white p-1 rounded-xl shadow-sm border border-slate-100">
                    <img src="../img/logo.webp" alt="Logo" class="h-12 w-12 object-contain">
                </div>
                <div>
                    <h1 class="text-xl font-black tracking-tight text-blue-900 leading-none">DACANS</h1>
                    <p class="text-[10px] uppercase tracking-[2px] text-slate-500 font-bold">Computers Store</p>
                </div>
            </div>

            <nav class="hidden md:flex items-center gap-8 font-bold text-sm">
                <a href="#inicio" class="hover:text-blue-600 transition">Inicio</a>
                <a href="../catalogo" class="hover:text-blue-600 transition">Catalogo</a>
                <a href="#accesorios" class="hover:text-blue-600 transition">Accesorios</a>
                <a href="#testimonios" class="hover:text-blue-600 transition">Opiniones</a>
                <a href="#nosotros" class="hover:text-blue-600 transition">Nosotros</a>
                <a href="#contacto" class="hover:text-blue-600 transition">Contacto</a>
            </nav>

            <?php if (isset($_SESSION['admin_logueado'])): ?>
            <a href="../mantenimiento"
                class="bg-slate-900 hover:bg-black text-white px-5 py-3 rounded-2xl font-black shadow-lg transition">
                <i class="fa-solid fa-screwdriver-wrench mr-2"></i> Mantenimiento
            </a>
            <?php endif; ?>

            <a href="https://wa.me/18495886436" target="_blank"
                class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-bold shadow-lg transition text-sm">
                WhatsApp
            </a>
        </div>
    </header>

    <section id="inicio" class="relative overflow-hidden min-h-screen flex items-center bg-slate-950 text-white">
        <div
            class="fixed bottom-10 right-10 z-[60] flex items-center gap-3 bg-slate-900/80 backdrop-blur-xl p-3 rounded-2xl border border-white/20 shadow-2xl">
            <button onclick="toggleMute()" id="muteBtn" class="text-white hover:text-cyan-400 transition">
                <i id="muteIcon" class="fa-solid fa-volume-high text-xl"></i>
            </button>
            <input type="range" id="volRange" min="0" max="1" step="0.01" value="0.08"
                class="w-24 h-1 bg-white/20 rounded-lg appearance-none cursor-pointer accent-cyan-400">
        </div>

        <audio id="bgMusic" loop>
            <source src="assets/audio/Dacan’s Computer.mp3" type="audio/mpeg">
        </audio>

        <div class="absolute inset-0 z-0">
            <video autoplay muted loop playsinline class="w-full h-full object-cover opacity-60 blur-sm scale-110">
                <source src="assets/videos/hero-tech.mp4" type="video/mp4">
            </video>
            <div class="absolute inset-0 bg-gradient-to-r from-slate-950/90 via-slate-900/80 to-blue-950/40"></div>
        </div>

        <div
            class="absolute top-0 left-0 w-[500px] h-[500px] bg-blue-500/20 rounded-full blur-[120px] pointer-events-none">
        </div>
        <div
            class="absolute bottom-0 right-0 w-[500px] h-[500px] bg-cyan-500/20 rounded-full blur-[120px] pointer-events-none">
        </div>

        <div class="container mx-auto px-6 relative z-10 py-32">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div>
                    <div
                        class="inline-flex items-center gap-3 bg-white/10 border border-white/10 backdrop-blur-xl px-5 py-3 rounded-full mb-8">
                        <span class="w-3 h-3 rounded-full bg-green-400 animate-pulse"></span>
                        <span class="uppercase tracking-[3px] text-xs font-black">Tecnología Premium Disponible</span>
                    </div>
                    <h1 class="text-5xl md:text-7xl font-black leading-tight">
                        EQUIPOS <span
                            class="block text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500">MODERNOS</span>
                    </h1>
                    <p class="mt-8 text-slate-300 text-lg leading-relaxed max-w-2xl">
                        Potencia tus proyectos con la tecnología de alto rendimiento de DACANS Computers. Encuentra
                        laptops profesionales, estaciones de trabajo y equipos gaming configurados por expertos para
                        superar tus expectativas.
                    </p>
                    <div class="flex flex-wrap gap-4 mt-10">
                        <a href="catalogo"
                            class="bg-blue-600 hover:bg-blue-700 transition px-8 py-4 rounded-2xl font-black shadow-2xl">Ver
                            Catálogo</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="laptops" class="py-24 bg-white">
        <div class="container mx-auto px-6">
            <div class="flex items-end justify-between mb-12">
                <div>
                    <h2 class="text-4xl font-black text-slate-900">Más Vendidos</h2>
                    <p class="text-slate-500 mt-2">Nuestras recomendaciones para ti.</p>
                </div>
                <div class="flex gap-2">
                    <button onclick="scrollCarousel(-1)"
                        class="w-12 h-12 rounded-full border border-slate-200 hover:bg-blue-600 hover:text-white transition flex items-center justify-center">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <button onclick="scrollCarousel(1)"
                        class="w-12 h-12 rounded-full border border-slate-200 hover:bg-blue-600 hover:text-white transition flex items-center justify-center">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <div id="carousel" class="carousel flex gap-6 overflow-x-auto pb-8 snap-x snap-mandatory">
                <div
                    class="min-w-[300px] md:min-w-[350px] bg-slate-50 rounded-[2.5rem] p-6 border border-slate-100 snap-start card-hover">
                    <div class="h-48 bg-white rounded-3xl flex items-center justify-center mb-6 overflow-hidden">
                        <img src="https://p1-ofp.static.pub/medias/bWFzdGVyfHJvb3R8Mjc5MTI5fGltYWdlL3BuZ3xoZjYvaDYxLzE0MTA2OTMyODM4NDMwLnBuZ3xhMzM3NzUxNDIxZGMwNWY0MzY0MGUxMDU3N2VmNThmNjA2MTlhZTU5Yjk2N2EwZjE1MTJlZDVjZThmM2U4MTNl/22tpt14t4a2.png"
                            alt="Lenovo ThinkPad T14 Gen 1" class="h-full object-contain">
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-blue-600">Business</span>
                    <h3 class="text-xl font-black mt-2">Lenovo ThinkPad T14 Gen 1</h3>
                    <div class="mt-4 space-y-2 text-sm text-slate-600">
                        <p>• Intel Core i7 10th Gen</p>
                        <p>• 16GB DDR4 RAM</p>
                        <p>• SSD NVMe 512GB</p>
                        <p>• Pantalla 14" Full HD IPS</p>
                        <p>• Intel UHD Graphics</p>
                    </div>
                </div>

                <div
                    class="min-w-[300px] md:min-w-[350px] bg-slate-50 rounded-[2.5rem] p-6 border border-slate-100 snap-start card-hover">
                    <div class="h-48 bg-white rounded-3xl flex items-center justify-center mb-6 overflow-hidden">
                        <img src="https://p2-ofp.static.pub//fes/cms/2024/10/07/6hn1u97fo9x53q4ge4y4h66snzg01c527290.png"
                            alt="Lenovo ThinkBook 14" class="h-full object-contain">
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-cyan-600">Business</span>
                    <h3 class="text-xl font-black mt-2">Lenovo ThinkBook 14</h3>
                    <div class="mt-4 space-y-2 text-sm text-slate-600">
                        <p>• Intel Core i5 10th Gen</p>
                        <p>• 16GB DDR4 RAM</p>
                        <p>• SSD NVMe 256 GB</p>
                        <p>• Pantalla 14" Full HD</p>
                        <p>• Intel HD Graphics</p>
                    </div>
                </div>

                <div
                    class="min-w-[300px] md:min-w-[350px] bg-slate-50 rounded-[2.5rem] p-6 border border-slate-100 snap-start card-hover">
                    <div class="h-48 bg-white rounded-3xl flex items-center justify-center mb-6 overflow-hidden">
                        <img src="https://p1-ofp.static.pub/medias/bWFzdGVyfHJvb3R8Mjk0MjQyfGltYWdlL3BuZ3xoZWYvaDY0LzE0MTExNzE4NDczNzU4LnBuZ3w3NzUyNzZlMGRiMDNhMjRiMGE3Mzc4MDFmMGZmMDU0ZTA5NzM2OTQyOWM5YmVkNzMwZDc5NmI3NDYyNTIxZWQz/22tpe14e4n1.png"
                            alt="Lenovo ThinkPad E14" class="h-full object-contain">
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-cyan-600">Business</span>
                    <h3 class="text-xl font-black mt-2">Lenovo ThinkPad E14 G2</h3>
                    <div class="mt-4 space-y-2 text-sm text-slate-600">
                        <p>• AMD Ryzen 7 4th Gen</p>
                        <p>• 16GB DDR4 RAM</p>
                        <p>• SSD NVMe 256GB</p>
                        <p>• Pantalla 14" Full HD IPS</p>
                        <p>• Intel Iris Xe Graphics</p>
                    </div>
                </div>

                <div
                    class="min-w-[300px] md:min-w-[350px] bg-slate-50 rounded-[2.5rem] p-6 border border-slate-100 snap-start card-hover">
                    <div class="h-48 bg-white rounded-3xl flex items-center justify-center mb-6 overflow-hidden">
                        <img src="https://media.us.lg.com/transform/ecomm-PDPGallery-1100x730/fad345b7-3e6b-4ece-9a0d-c21cf9924edb/Monitor-34SR60QC-B-digital-trends-gallery-1_5000x5000?io=transform:fill,width:1536"
                            alt="LG MyView Smart Monitor 34" class="h-full object-contain">
                    </div>
                    <span class="text-[10px] font-black uppercase tracking-widest text-violet-600">Smart Monitor</span>
                    <h3 class="text-xl font-black mt-2">LG MyView 34" WQHD Curvo</h3>
                    <div class="mt-4 space-y-2 text-sm text-slate-600">
                        <p>• Pantalla Curva 34" UltraWide</p>
                        <p>• Resolución WQHD 3440x1440</p>
                        <p>• Panel VA 100Hz</p>
                        <p>• 99% sRGB Color</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="accesorios" class="py-24 bg-slate-900 text-white">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-black">Accesorios</h2>
                <div class="w-20 h-1 bg-blue-500 mx-auto mt-4 rounded-full"></div>
            </div>
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-6 text-center">
                <a href="accesorios/index.php">
                    <div class="bg-white/5 p-8 rounded-[2rem] border border-white/5 card-hover">
                        <i class="fa-solid fa-keyboard text-4xl text-cyan-400 mb-4"></i>
                        <h3 class="font-bold">Teclados</h3>
                    </div>
                </a>
                <a href="accesorios/index.php">
                    <div class="bg-white/5 p-8 rounded-[2rem] border border-white/5 card-hover">
                        <i class="fa-solid fa-headphones text-4xl text-cyan-400 mb-4"></i>
                        <h3 class="font-bold">Auriculares</h3>
                    </div>
                </a>
                <a href="accesorios/index.php">
                    <div class="bg-white/5 p-8 rounded-[2rem] border border-white/5 card-hover">
                        <i class="fa-solid fa-computer-mouse text-4xl text-cyan-400 mb-4"></i>
                        <h3 class="font-bold">Mouses</h3>
                    </div>
                </a>
                <a href="accesorios/index.php">
                    <div class="bg-white/5 p-8 rounded-[2rem] border border-white/5 card-hover">
                        <i class="fa-solid fa-hard-drive text-4xl text-cyan-400 mb-4"></i>
                        <h3 class="font-bold">Almacenamiento</h3>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- === SECCIÓN: TESTIMONIOS (SÓLO LECTURA) === -->
    <section id="testimonios" class="py-24 bg-slate-50">
        <div class="container mx-auto px-6">
            <div class="text-center mb-16">
                <span class="text-xs font-black uppercase tracking-[3px] text-blue-600 block mb-2">Comunidad
                    DACANS</span>
                <h2 class="text-4xl font-black text-slate-900">Opiniones de Clientes</h2>
                <p class="text-slate-500 mt-2">Lo que dicen quienes ya confían en la experiencia DACANS</p>
                <div class="w-20 h-1 bg-blue-600 mx-auto mt-4 rounded-full"></div>
            </div>

            <!-- Feed de Comentarios en Grid Completo -->
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php 
                $json_file = 'comentarios.json';
                $comentarios = file_exists($json_file) ? json_decode(file_get_contents($json_file), true) : [];
                
                if (!empty($comentarios)): 
                    foreach ($comentarios as $c): 
                ?>
                <div
                    class="bg-white p-8 rounded-[2rem] border border-slate-100 shadow-sm flex flex-col justify-between h-full card-hover">
                    <div>
                        <div class="flex items-center justify-between mb-5">
                            <div class="flex gap-1 text-amber-400 text-sm">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="<?= $i <= $c['estrellas'] ? 'fa-solid' : 'fa-regular' ?> fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <span
                                class="text-[11px] font-bold text-slate-400"><?= htmlspecialchars($c['fecha']) ?></span>
                        </div>
                        <p class="text-slate-600 text-sm leading-relaxed font-medium mb-6 italic">
                            "<?= htmlspecialchars($c['comentario']) ?>"
                        </p>
                    </div>
                    <div class="flex items-center gap-3 pt-4 border-t border-slate-50">
                        <div
                            class="w-9 h-9 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 font-black text-xs uppercase">
                            <?= substr($c['nombre'], 0, 2) ?>
                        </div>
                        <h4 class="font-black text-xs text-slate-900 tracking-wide">
                            <?= htmlspecialchars($c['nombre']) ?></h4>
                    </div>
                </div>
                <?php 
                    endforeach; 
                else: 
                ?>
                <div
                    class="col-span-full bg-white rounded-[2.5rem] p-12 text-center border border-slate-200/60 max-w-md mx-auto">
                    <i class="fa-regular fa-comments text-5xl text-slate-300 mb-4"></i>
                    <p class="text-slate-500 font-medium">Próximamente opiniones de nuestros clientes premium.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section id="nosotros" class="py-24">
        <div class="container mx-auto px-6 grid lg:grid-cols-2 gap-8">
            <div class="bg-white p-10 rounded-[2.5rem] border border-slate-100 shadow-sm">
                <i class="fa-solid fa-bullseye text-blue-600 text-3xl mb-6 block"></i>
                <h2 class="text-3xl font-black mb-4">Nuestra Misión</h2>
                <p class="text-slate-600">En DACANS, impulsamos el éxito de profesionales, empresas y gamers
                    proporcionando tecnología de alto rendimiento...</p>
            </div>
            <div class="bg-gradient-to-br from-blue-600 to-blue-800 p-10 rounded-[2.5rem] text-white shadow-xl">
                <i class="fa-solid fa-eye text-3xl mb-6 block"></i>
                <h2 class="text-3xl font-black mb-4">Nuestra Visión</h2>
                <p class="text-blue-50/80">Ser reconocidos como el aliado tecnológico líder y de mayor confianza en el
                    mercado...</p>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script>
    const audio = document.getElementById('bgMusic');
    const muteIcon = document.getElementById('muteIcon');
    const volRange = document.getElementById('volRange');

    audio.volume = 0.08;

    function toggleMute() {
        if (audio.paused) {
            audio.play();
        } else {
            audio.muted = !audio.muted;
            volRange.value = audio.muted ? 0 : audio.volume;
            updateIcon(audio.muted);
        }
    }

    volRange.addEventListener('input', (e) => {
        const val = parseFloat(e.target.value);
        audio.volume = val;
        audio.muted = (val === 0);
        updateIcon(audio.muted);
        if (audio.paused && val > 0) audio.play();
    });

    function updateIcon(isMuted) {
        muteIcon.className = isMuted ? 'fa-solid fa-volume-xmark text-xl' : 'fa-solid fa-volume-high text-xl';
    }

    document.addEventListener('click', () => {
        if (audio.paused) audio.play();
    }, {
        once: true
    });

    function scrollCarousel(direction) {
        const carousel = document.getElementById('carousel');
        carousel.scrollBy({
            left: direction * 350,
            behavior: 'smooth'
        });
    }

    // --- INTERACTIVIDAD PARA LA SECCIÓN DE ESTRELLAS ---
    const stars = document.querySelectorAll('.star-rating i');
    const ratingInput = document.getElementById('ratingInput');

    stars.forEach(star => {
        star.addEventListener('click', function() {
            const value = parseInt(this.getAttribute('data-value'));
            ratingInput.value = value;

            // Actualizar clases de las estrellas según la selección
            stars.forEach(s => {
                const sValue = parseInt(s.getAttribute('data-value'));
                if (sValue <= value) {
                    s.className = 'fa-solid fa-star cursor-pointer text-amber-400';
                } else {
                    s.className = 'fa-regular fa-star cursor-pointer text-slate-300';
                }
            });
        });
    });
    </script>
</body>

</html>