<?php
// Iniciamos sesión para recordar el estado actual si se recarga la página
session_start();

// Si se envió un estado por POST, lo guardamos en la sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['estado'])) {
    $_SESSION['estado_actual'] = $_POST['estado'];
}

// Estado por defecto al entrar por primera vez: disponible (verde)
$estado = $_SESSION['estado_actual'] ?? 'disponible';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indicador de Estado | DACANS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
    /* Efecto de parpadeo suave para la luz activa */
    @keyframes pulse-soft {

        0%,
        100% {
            opacity: 1;
            transform: scale(1);
        }

        50% {
            opacity: 0.85;
            transform: scale(1.03);
        }
    }

    .luz-activa {
        animation: pulse-soft 2s infinite ease-in-out;
    }
    </style>
</head>

<body class="bg-slate-900 min-h-screen flex flex-col items-center justify-center text-white font-sans">

    <div
        class="w-full max-w-md p-8 bg-slate-800/50 backdrop-blur-md rounded-[2.5rem] border border-slate-700/50 shadow-2xl text-center">

        <h1 class="text-2xl font-black tracking-wider uppercase text-slate-400 mb-2">
            Estado de Oficina
        </h1>
        <p class="text-xs text-slate-500 font-bold uppercase tracking-[3px] mb-8">
            Dacans Computers Tech
        </p>

        <div class="grid grid-cols-2 gap-6 mb-10">

            <div class="flex flex-col items-center p-5 rounded-3xl bg-slate-800 border border-slate-700/30">
                <div id="luzVerde"
                    class="w-16 h-16 rounded-full transition-all duration-500 flex items-center justify-center <?= $estado === 'disponible' ? 'bg-green-500 shadow-[0_0_30px_rgba(34,197,94,0.6)] luz-activa' : 'bg-green-950/40 opacity-30' ?>">
                    <i
                        class="fa-solid fa-circle-check text-2xl <?= $estado === 'disponible' ? 'text-white' : 'text-green-900' ?>"></i>
                </div>
                <span class="mt-3 font-black text-xs tracking-widest uppercase text-slate-400">Disponible</span>
            </div>

            <div class="flex flex-col items-center p-5 rounded-3xl bg-slate-800 border border-slate-700/30">
                <div id="luzRoja"
                    class="w-16 h-16 rounded-full transition-all duration-500 flex items-center justify-center <?= $estado === 'reunion' ? 'bg-red-500 shadow-[0_0_30px_rgba(239,68,68,0.6)] luz-activa' : 'bg-red-950/40 opacity-30' ?>">
                    <i
                        class="fa-solid fa-rectangle-xmark text-2xl <?= $estado === 'reunion' ? 'text-white' : 'text-red-900' ?>"></i>
                </div>
                <span class="mt-3 font-black text-xs tracking-widest uppercase text-slate-400">En Reunión</span>
            </div>

        </div>

        <div class="mb-10 p-5 rounded-2xl bg-slate-950/40 border border-slate-800">
            <span class="block text-[10px] uppercase tracking-widest text-slate-500 font-bold mb-1">Mensaje
                Actual</span>
            <p id="textoMensaje"
                class="text-xl font-black transition-all duration-300 <?= $estado === 'disponible' ? 'text-green-400' : 'text-red-400' ?>">
                <?= $estado === 'disponible' ? '🟢 ¡Estoy disponible!' : '🔴 Estoy en reunión' ?>
            </p>
        </div>

        <form method="POST" id="formEstado" class="grid grid-cols-1 gap-4">

            <button type="button" onclick="cambiarEstado('disponible')"
                class="w-full bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-500 hover:to-emerald-400 text-white font-black py-4 px-6 rounded-2xl shadow-lg hover:scale-[1.02] active:scale-[0.98] transition flex items-center justify-center gap-3">
                <i class="fa-solid fa-door-open"></i>
                Poner "Disponible"
            </button>

            <button type="button" onclick="cambiarEstado('reunion')"
                class="w-full bg-gradient-to-r from-red-600 to-rose-500 hover:from-red-500 hover:to-rose-400 text-white font-black py-4 px-6 rounded-2xl shadow-lg hover:scale-[1.02] active:scale-[0.98] transition flex items-center justify-center gap-3">
                <i class="fa-solid fa-video"></i>
                Poner "En Reunión"
            </button>

            <input type="hidden" name="estado" id="inputEstado" value="<?= $estado ?>">
        </form>

    </div>

    <script>
    function cambiarEstado(nuevoEstado) {
        const luzVerde = document.getElementById('luzVerde');
        const luzRoja = document.getElementById('luzRoja');
        const textoMensaje = document.getElementById('textoMensaje');
        const inputEstado = document.getElementById('inputEstado');
        const formEstado = document.getElementById('formEstado');

        // Actualizar input oculto
        inputEstado.value = nuevoEstado;

        if (nuevoEstado === 'disponible') {
            // Prender Verde, apagar Roja
            luzVerde.className =
                "w-16 h-16 rounded-full transition-all duration-500 flex items-center justify-center bg-green-500 shadow-[0_0_30px_rgba(34,197,94,0.6)] luz-activa";
            luzVerde.innerHTML = '<i class="fa-solid fa-circle-check text-2xl text-white"></i>';

            luzRoja.className =
                "w-16 h-16 rounded-full transition-all duration-500 flex items-center justify-center bg-red-950/40 opacity-30";
            luzRoja.innerHTML = '<i class="fa-solid fa-rectangle-xmark text-2xl text-red-900"></i>';

            textoMensaje.className = "text-xl font-black text-green-400 transition-all duration-300";
            textoMensaje.innerHTML = "🟢 ¡Estoy disponible!";
        } else {
            // Prender Roja, apagar Verde
            luzRoja.className =
                "w-16 h-16 rounded-full transition-all duration-500 flex items-center justify-center bg-red-500 shadow-[0_0_30px_rgba(239,68,68,0.6)] luz-activa";
            luzRoja.innerHTML = '<i class="fa-solid fa-rectangle-xmark text-2xl text-white"></i>';

            luzVerde.className =
                "w-16 h-16 rounded-full transition-all duration-500 flex items-center justify-center bg-green-950/40 opacity-30";
            luzVerde.innerHTML = '<i class="fa-solid fa-circle-check text-2xl text-green-900"></i>';

            textoMensaje.className = "text-xl font-black text-red-400 transition-all duration-300";
            textoMensaje.innerHTML = "🔴 Estoy en reunión";
        }

        // Enviamos el formulario al servidor PHP de forma asíncrona o directa para salvar el estado
        setTimeout(() => {
            formEstado.submit();
        }, 150); // Pequeña pausa para apreciar la animación inicial antes de enviar el POST
    }
    </script>
</body>

</html>