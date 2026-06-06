<?php
session_start();
$json_file = 'comentarios.json';

$enviado = false;
$acceso_denegado = false;
$nombre_cliente = "";

// 1. VALIDAR SI EL LINK TRAE EL NOMBRE DEL CLIENTE EXCLUSIVO
if (isset($_GET['cliente']) && !empty(trim($_GET['cliente']))) {
    // Decodificar el nombre (reemplaza guiones bajos o %20 por espacios limpios)
    $nombre_cliente = strip_tags(trim(str_replace('_', ' ', $_GET['cliente'])));
} else {
    // Si no viene el parámetro "cliente" en la URL, se bloquea el formulario
    $acceso_denegado = true;
}

// 2. PROCESAR EL FORMULARIO
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$acceso_denegado) {
    // Tomamos el nombre validado del GET para evitar alteraciones por el inspector de elementos
    $nombre = $nombre_cliente; 
    $estrellas = min(5, max(1, intval($_POST['estrellas'] ?? 5)));
    $comentario = strip_tags(trim($_POST['comentario'] ?? ''));
    $fecha = date('d M Y');

    if (!empty($comentario) && !empty($nombre)) {
        $comentarios_actuales = file_exists($json_file) ? json_decode(file_get_contents($json_file), true) : [];
        array_unshift($comentarios_actuales, [
            "nombre" => $nombre,
            "estrellas" => $estrellas,
            "comentario" => $comentario,
            "fecha" => $fecha
        ]);
        file_put_contents($json_file, json_encode($comentarios_actuales, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $enviado = true;
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Califica tu compra | DACANS Computers</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
    body {
        background: #0f172a;
    }

    .star-rating i {
        transition: transform 0.2s ease;
    }

    .star-rating i:hover {
        transform: scale(1.25);
    }
    </style>
</head>

<body class="text-slate-200 min-h-screen flex items-center justify-center p-4">

    <div
        class="w-full max-w-md bg-slate-900/60 backdrop-blur-xl border border-white/10 p-8 rounded-[2.5rem] shadow-2xl relative overflow-hidden">

        <div class="absolute -top-10 -right-10 w-32 h-32 bg-blue-500/10 rounded-full blur-2xl pointer-events-none">
        </div>
        <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-cyan-500/10 rounded-full blur-2xl pointer-events-none">
        </div>

        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-6">
                <img src="../img/logo.webp" alt="DACANS" class="h-10 w-10 object-contain bg-white/10 p-1 rounded-lg">
                <div>
                    <h1 class="font-black text-lg text-white leading-none tracking-tight">DACANS COMPUTERS</h1>
                    <p class="text-[10px] uppercase text-cyan-400 font-bold tracking-widest mt-1">Garantía y Calidad</p>
                </div>
            </div>

            <?php if ($acceso_denegado): ?>
            <div class="text-center py-6 animate-fade-in">
                <div
                    class="w-16 h-16 bg-rose-500/10 text-rose-400 border border-rose-500/20 rounded-2xl flex items-center justify-center mx-auto text-2xl mb-5 shadow-xl">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <h2 class="text-xl font-black text-white mb-2">Acceso Exclusivo</h2>
                <p class="text-slate-400 text-xs leading-relaxed mb-6">
                    Este espacio de valoraciones está reservado únicamente para clientes que han completado una compra
                    legítima mediante una invitación de WhatsApp.
                </p>
                <a href="https://dacansdr.com"
                    class="inline-block bg-white/5 hover:bg-white/10 text-white font-bold px-6 py-2.5 rounded-xl border border-white/10 transition text-xs">
                    Ir a la tienda principal
                </a>
            </div>

            <?php elseif ($enviado): ?>
            <div class="text-center py-8 animate-fade-in">
                <div
                    class="w-16 h-16 bg-green-500/20 text-green-400 border border-green-500/30 rounded-2xl flex items-center justify-center mx-auto text-3xl mb-6 shadow-xl">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <h2 class="text-2xl font-black text-white mb-3">¡Muchas gracias,
                    <?= explode(' ', $nombre_cliente)[0]; ?>!</h2>
                <p class="text-slate-400 text-sm leading-relaxed mb-8">Tu reseña ha sido procesada con autenticidad y ya
                    se muestra en nuestra página oficial.</p>
                <a href="index.php"
                    class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-black px-8 py-3.5 rounded-xl transition shadow-lg text-sm">
                    Ir al Inicio
                </a>
            </div>

            <?php else: ?>
            <h2 class="text-xl font-bold text-white mb-1">¿Cómo fue tu experiencia?</h2>
            <p class="text-xs text-slate-400 mb-6">Hola <span
                    class="text-cyan-400 font-bold"><?= htmlspecialchars($nombre_cliente) ?></span>, tu opinión es muy
                importante para nosotros.</p>

            <form action="" method="POST" class="space-y-5">
                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Tu Nombre
                        registrado</label>
                    <div class="relative">
                        <input type="text" readonly value="<?= htmlspecialchars($nombre_cliente) ?>"
                            class="w-full bg-slate-950/40 border border-white/5 rounded-xl px-4 py-3.5 text-sm text-slate-400 font-bold focus:outline-none cursor-not-allowed select-none">
                        <i class="fa-solid fa-lock absolute right-4 top-4 text-xs text-slate-600"></i>
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">Tu
                        Calificación</label>
                    <div class="flex items-center gap-3 text-3xl text-slate-600 star-rating py-2">
                        <i class="fa-solid fa-star cursor-pointer text-amber-400" data-value="1"></i>
                        <i class="fa-solid fa-star cursor-pointer text-amber-400" data-value="2"></i>
                        <i class="fa-solid fa-star cursor-pointer text-amber-400" data-value="3"></i>
                        <i class="fa-solid fa-star cursor-pointer text-amber-400" data-value="4"></i>
                        <i class="fa-solid fa-star cursor-pointer text-amber-400" data-value="5"></i>
                    </div>
                    <input type="hidden" name="estrellas" id="ratingInput" value="5">
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase tracking-widest text-slate-400 mb-2">Comentario
                        sobre el equipo / servicio</label>
                    <textarea name="comentario" rows="4" required
                        placeholder="Cuéntanos qué tal el rendimiento de la PC o la atención recibida..."
                        class="w-full bg-slate-950/50 border border-white/10 rounded-xl px-4 py-3.5 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 transition font-medium resize-none"></textarea>
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-black py-4 rounded-2xl shadow-xl transition-all duration-300 text-sm tracking-wide mt-2">
                    Publicar Testimonio
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
    const stars = document.querySelectorAll('.star-rating i');
    const ratingInput = document.getElementById('ratingInput');

    stars.forEach(star => {
        star.addEventListener('click', function() {
            const value = parseInt(this.getAttribute('data-value'));
            ratingInput.value = value;

            stars.forEach(s => {
                const sValue = parseInt(s.getAttribute('data-value'));
                if (sValue <= value) {
                    s.className = 'fa-solid fa-star cursor-pointer text-amber-400';
                } else {
                    s.className = 'fa-regular fa-star cursor-pointer text-slate-600';
                }
            });
        });
    });
    </script>
</body>

</html>