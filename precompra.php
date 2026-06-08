<?php
// precompra.php

// 1. INCLUIR TU CONEXIÓN REAL A LA BASE DE DATOS
// Descomenta la línea de abajo y asegúrate de que el nombre del archivo sea el correcto
include 'config/conexion.php'; 

// 2. CAPTURAR EL PRODUCTO SELECCIONADO VIA URL
$id_producto = $_GET['id'] ?? null;

if (!$id_producto) {
    die("Error: No se ha seleccionado ningún equipo para la compra.");
}

// 3. CONSULTA REAL A TU BASE DE DATOS
// Preparamos la consulta para traer la laptop exacta que seleccionó el cliente
$stmt = $pdo->prepare("SELECT * FROM productos_informatica WHERE id_local = ?");
$stmt->execute([$id_producto]);
$p = $stmt->fetch();

// Si el ID no existe en la base de datos, detenemos el script con un mensaje limpio
if (!$p) {
    die("Error: El equipo seleccionado no existe o ya no está disponible.");
}

// Guardamos el precio real de la base de datos
$precio_equipo = (float)$p['precio'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Compra de Equipo</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-slate-50 text-slate-800 font-sans antialiased min-h-screen flex items-center justify-center p-4">

    <div
        class="max-w-4xl w-full bg-white rounded-[2rem] shadow-xl border border-slate-100 overflow-hidden grid grid-cols-1 md:grid-cols-2">

        <div class="p-8 border-b md:border-b-0 md:border-r border-slate-100">
            <h2 class="text-2xl font-black text-slate-900 mb-2">Datos de Facturación</h2>
            <p class="text-xs text-slate-400 uppercase tracking-wider mb-6 font-bold">Completa el formulario para
                proceder al pago</p>

            <form action="procesar_precompra.php" method="POST" class="space-y-4">
                <input type="hidden" name="id_producto" value="<?= $p['id_local'] ?>">
                <input type="hidden" id="precio_base" value="<?= $precio_equipo ?>">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label
                            class="block text-[10px] font-black uppercase text-slate-400 tracking-wider mb-1">Nombre</label>
                        <input type="text" name="nombre" required
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-blue-500 transition">
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-black uppercase text-slate-400 tracking-wider mb-1">Apellido</label>
                        <input type="text" name="apellido" required
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-blue-500 transition">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-400 tracking-wider mb-1">RNC o
                        Cédula</label>
                    <input type="text" name="rnc_cedula" required placeholder="001-0000000-0"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-blue-500 transition">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label
                            class="block text-[10px] font-black uppercase text-slate-400 tracking-wider mb-1">Teléfono</label>
                        <input type="tel" name="telefono" required placeholder="809-555-5555"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-blue-500 transition">
                    </div>
                    <div>
                        <label
                            class="block text-[10px] font-black uppercase text-slate-400 tracking-wider mb-1">Email</label>
                        <input type="email" name="email" required placeholder="correo@ejemplo.com"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-blue-500 transition">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-400 tracking-wider mb-1">Dirección
                        de Entrega</label>
                    <input type="text" name="direccion" required placeholder="Calle, No., Ensanche, Ciudad"
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-blue-500 transition">
                </div>

                <div class="pt-2">
                    <label class="block text-[10px] font-black uppercase text-slate-400 tracking-wider mb-2">Método de
                        Entrega</label>
                    <div class="grid grid-cols-2 gap-3">
                        <label
                            class="border-2 border-blue-600 bg-blue-50/30 rounded-xl p-3 flex flex-col items-center justify-center text-center cursor-pointer transition select-none"
                            id="label_tienda">
                            <input type="radio" name="tipo_entrega" value="tienda" checked class="hidden"
                                onchange="calcularTotales()">
                            <i class="fa-solid fa-shop text-blue-600 text-lg mb-1"></i>
                            <span class="text-xs font-black text-slate-800">Retiro en Tienda</span>
                            <span class="text-[10px] text-emerald-600 font-bold mt-0.5">Gratis</span>
                        </label>

                        <label
                            class="border border-slate-200 rounded-xl p-3 flex flex-col items-center justify-center text-center cursor-pointer transition select-none"
                            id="label_envio">
                            <input type="radio" name="tipo_entrega" value="envio" class="hidden"
                                onchange="calcularTotales()">
                            <i class="fa-solid fa-truck-fast text-slate-400 text-lg mb-1" id="icon_envio"></i>
                            <span class="text-xs font-black text-slate-800">Envío a Domicilio</span>
                            <span class="text-[10px] text-slate-500 font-bold mt-0.5">Cálculo dinámico</span>
                        </label>
                    </div>
                </div>


                <div class="flex items-center gap-3 pt-4">
                    <a href="../catalogo/index.php" rel="noopener noreferrer"
                        class="w-[30%] bg-slate-100 hover:bg-slate-200 text-slate-600 font-black text-xs uppercase tracking-wider h-12 rounded-xl transition flex items-center justify-center gap-1.5 active:scale-95">
                        <i class="fa-solid fa-arrow-left text-[10px]"></i>
                        Atrás
                    </a>

                    <button type="submit"
                        class="w-[70%] bg-gradient-to-r from-blue-600 to-indigo-700 text-white font-black text-xs uppercase tracking-wider h-12 rounded-xl shadow-md hover:scale-[1.01] active:scale-95 transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-lock text-[10px]"></i>
                        Proceder al Pago
                    </button>
                </div>
            </form>
        </div>

        <div class="p-8 bg-slate-50/50 flex flex-col justify-between">
            <div>
                <h3 class="text-xl font-black text-slate-900 mb-6">Resumen de la Orden</h3>

                <div class="bg-white border border-slate-100 rounded-2xl p-4 shadow-sm flex items-center gap-4 mb-6">
                    <div
                        class="w-16 h-16 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-center overflow-hidden flex-shrink-0 relative">
                        <?php if (!empty($p['imagen_url'])): ?>
                        <img src="<?= htmlspecialchars($p['imagen_url']) ?>"
                            alt="<?= htmlspecialchars($p['equipo_modelo']) ?>" class="w-full h-full object-contain p-1">
                        <?php else: ?>
                        <i class="fa-solid fa-laptop text-3xl text-slate-300"></i>
                        <?php endif; ?>
                    </div>
                </div>
                <div>
                    <span
                        class="bg-blue-50 text-blue-700 px-2 py-0.5 rounded-full text-[8px] uppercase tracking-widest font-black inline-block mb-1">
                        <?= htmlspecialchars($p['equipo_marca']) ?>
                    </span>
                    <h4 class="text-sm font-black text-slate-900 leading-tight">
                        <?= htmlspecialchars($p['equipo_modelo']) ?></h4>
                    <p class="text-xs text-slate-400 font-bold mt-0.5">ID: <?= htmlspecialchars($p['id_local']) ?>
                    </p>
                </div>
            </div>

            <div class="space-y-3 border-b border-slate-200 pb-4 text-sm font-medium text-slate-600">
                <div class="flex justify-between">
                    <span>Precio del equipo</span>
                    <span class="font-bold text-slate-900">RD$ <?= number_format($precio_equipo, 0) ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span>Costo de envío</span>
                    <span class="font-bold text-slate-900" id="txt_envio">RD$ 0</span>
                </div>
            </div>
        </div>

        <div class="mt-6 md:mt-0 pt-4">
            <div class="flex justify-between items-baseline mb-4">
                <span class="text-xs uppercase tracking-widest text-slate-400 font-black">Total a pagar</span>
                <span
                    class="text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-700 to-cyan-500 tracking-tight"
                    id="txt_total">
                    RD$ <?= number_format($precio_equipo, 0) ?>
                </span>
            </div>
            <div class="bg-blue-50/60 rounded-xl p-3 flex items-start gap-2.5 text-[11px] text-blue-800 leading-normal">
                <i class="fa-solid fa-shield-halved text-sm mt-0.5 text-blue-600"></i>
                <p>Tus datos están protegidos. Al presionar el botón serás redirigido a la pasarela oficial para
                    procesar tu tarjeta de forma segura.</p>
            </div>
        </div>

    </div>
    </div>

    <script>
    function calcularTotales() {
        const precioBase = parseFloat(document.getElementById('precio_base').value);
        const metodo = document.querySelector('input[name="tipo_entrega"]:checked').value;

        const labelTienda = document.getElementById('label_tienda');
        const labelEnvio = document.getElementById('label_envio');
        const iconEnvio = document.getElementById('icon_envio');

        let costoEnvio = 0;

        if (metodo === 'envio') {
            costoEnvio = 500 + (precioBase * 0.03);

            labelEnvio.classList.add('border-blue-600', 'bg-blue-50/30');
            labelEnvio.classList.remove('border-slate-200');
            iconEnvio.classList.add('text-blue-600');

            labelTienda.classList.remove('border-blue-600', 'bg-blue-50/30');
            labelTienda.classList.add('border-slate-200');
        } else {
            labelTienda.classList.add('border-blue-600', 'bg-blue-50/30');
            labelTienda.classList.remove('border-slate-200');

            labelEnvio.classList.remove('border-blue-600', 'bg-blue-50/30');
            labelEnvio.classList.add('border-slate-200');
            iconEnvio.classList.remove('text-blue-600');
        }

        const totalGeneral = precioBase + costoEnvio;

        document.getElementById('txt_envio').innerText = 'RD$ ' + costoEnvio.toLocaleString('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
        document.getElementById('txt_total').innerText = 'RD$ ' + totalGeneral.toLocaleString('en-US', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        });
    }
    </script>
</body>

</html>