<?php
// Iniciamos sesión para leer el estado guardado por el administrador
session_start();

// Si no hay un estado guardado, por defecto asumimos disponible
$estado = $_SESSION['estado_actual'] ?? 'disponible';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="5">
    <title>Estado de Oficina | Vista Pública</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
    /* Animación de pulso gigante para la luz encendida */
    @keyframes pulso-gigante {

        0%,
        100% {
            transform: scale(1);
            box-shadow: 0 0 60px var(--color-brillo);
        }

        50% {
            transform: scale(1.05);
            box-shadow: 0 0 100px var(--color-brillo);
        }
    }

    .luz-gigante {
        animation: pulso-gigante 2.5s infinite ease-in-out;
    }
    </style>
</head>

<body
    class="bg-slate-950 min-h-screen flex flex-col items-center justify-between text-white font-sans p-8 overflow-hidden">

    <div class="text-center mt-6">
        <h1 class="text-xl font-bold tracking-[0.3em] uppercase text-slate-500">
            Oficina de Informática
        </h1>
        <div class="h-1 w-20 bg-slate-800 mx-auto mt-3 rounded-full"></div>
    </div>

    <div class="flex flex-col items-center justify-center my-auto transition-all duration-500">

        <?php if ($estado === 'disponible'): ?>
        <div class="w-64 h-64 rounded-full bg-green-500 luz-gigante flex items-center justify-center"
            style="--color-brillo: rgba(34, 197, 94, 0.7);">
            <div class="w-56 h-56 rounded-full border-4 border-white/20 flex items-center justify-center">
                <span class="text-7xl">🟢</span>
            </div>
        </div>

        <h2
            class="text-5xl md:text-6xl font-black text-center text-green-400 mt-14 tracking-wide uppercase drop-shadow-[0_5px_15px_rgba(34,197,94,0.3)]">
            ¡Estoy disponible!
        </h2>
        <p class="text-slate-400 font-medium text-lg mt-4 tracking-wider">Puedes pasar o llamar con confianza</p>

        <?php else: ?>
        <div class="w-64 h-64 rounded-full bg-red-500 luz-gigante flex items-center justify-center"
            style="--color-brillo: rgba(239, 68, 68, 0.7);">
            <div class="w-56 h-56 rounded-full border-4 border-white/20 flex items-center justify-center">
                <span class="text-7xl">🔴</span>
            </div>
        </div>

        <h2
            class="text-5xl md:text-6xl font-black text-center text-red-400 mt-14 tracking-wide uppercase drop-shadow-[0_5px_15px_rgba(239,68,68,0.3)]">
            Estoy en reunión
        </h2>
        <p class="text-slate-400 font-medium text-lg mt-4 tracking-wider">Por favor, no interrumpir en este momento</p>

        <?php endif; ?>

    </div>

    <div class="text-center mb-4">
        <span class="text-xs text-slate-600 uppercase tracking-widest font-semibold flex items-center gap-2">
            <span class="inline-block w-2 h-2 rounded-full bg-blue-500 animate-ping"></span>
            Actualizado en tiempo real • Dacans Computers
        </span>
    </div>

</body>

</html>