<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* ======================================================
   CONEXIÓN CENTRALIZADA (SQL SERVER)
====================================================== */
require_once '../config/conexion.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

/* ======================================================
   VALIDAR ID
====================================================== */
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID no válido");
}

$id = (int) $_GET['id'];

/* ======================================================
   OBTENER ACCESORIO (CON CONTINGENCIA DE NOMBRE DE TABLA)
====================================================== */
$accesorio = false;

try {
    // 1. Intento estándar: buscar en la tabla "accesorios" (Esquema por defecto dbo.accesorios)
    $stmt = $pdo->prepare("SELECT * FROM accesorios WHERE id = ?");
    $stmt->execute([$id]);
    $accesorio = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // 2. Contingencia: Si dice que no existe la tabla, intentamos buscar en singular "accesorio"
    try {
        $stmt = $pdo->prepare("SELECT * FROM accesorio WHERE id = ?");
        $stmt->execute([$id]);
        $accesorio = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e2) {
        // Si ambos fallan, te mostramos un mensaje claro de diagnóstico
        die("Error crítico: No se encontró la tabla 'accesorios' ni 'accesorio' en tu base de datos de SQL Server. Por favor verifica el nombre exacto.");
    }
}

if (!$accesorio) {
    die("Accesorio no encontrado");
}

/* ======================================================
   PRECIO SEGURO
====================================================== */
$precio = is_numeric($accesorio['precio']) ? (float)$accesorio['precio'] : 0;

/* ======================================================
   WHATSAPP
====================================================== */
$mensaje = "Hola, me interesa este accesorio:%0A%0A";
$mensaje .= "🖥️ " . ($accesorio['nombre'] ?? 'Accesorio') . "%0A";
$mensaje .= "🏷️ Marca: " . ($accesorio['marca'] ?? 'N/A') . "%0A";
$mensaje .= "💰 Precio: RD$ " . number_format($precio, 0) . "%0A";
$mensaje .= "📦 Stock: " . ($accesorio['stock'] ?? '0');
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($accesorio['nombre'] ?? 'Accesorio') ?> | DACANS</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon.png">
    <link rel="shortcut icon" href="/img/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
    body {
        background:
            radial-gradient(circle at top left, rgba(37, 99, 235, .08), transparent 30%),
            radial-gradient(circle at bottom right, rgba(14, 165, 233, .08), transparent 30%),
            #f8fafc;
    }

    .glass {
        background: rgba(255, 255, 255, .75);
        backdrop-filter: blur(14px);
    }

    .float {
        animation: float 5s ease-in-out infinite;
    }

    @keyframes float {
        0% {
            transform: translateY(0px);
        }

        50% {
            transform: translateY(-10px);
        }

        100% {
            transform: translateY(0px);
        }
    }
    </style>
</head>

<body class="min-h-screen text-slate-800">

    <header class="glass border-b border-white/40 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="../home.php" class="flex items-center gap-4">
                <div class="bg-white p-3 rounded-2xl shadow-lg border border-slate-200">
                    <img src="../img/logo.webp" class="h-10 object-contain">
                </div>
                <div>
                    <h1 class="text-2xl font-black text-slate-900">DACANS</h1>
                    <p class="text-xs uppercase tracking-[4px] text-slate-500 font-bold">Computers Store</p>
                </div>
            </a>
            <a href="index.php"
                class="bg-slate-900 hover:bg-black text-white px-6 py-3 rounded-2xl font-black shadow-lg transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Volver
            </a>
        </div>
    </header>

    <section class="max-w-7xl mx-auto px-6 py-12">
        <div class="grid lg:grid-cols-2 gap-10 items-start">
            <div class="bg-white rounded-[2.5rem] p-6 shadow-2xl border border-slate-200 float">
                <div class="aspect-square rounded-[2rem] overflow-hidden bg-slate-100 border border-slate-200">
                    <img src="<?= !empty($accesorio['imagen_url']) ? htmlspecialchars($accesorio['imagen_url']) : 'https://via.placeholder.com/700x700?text=NO+IMAGE' ?>"
                        class="w-full h-full object-cover">
                </div>
            </div>

            <div class="bg-white rounded-[2.5rem] p-8 shadow-2xl border border-slate-200">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <span
                        class="bg-blue-100 text-blue-700 px-5 py-2 rounded-full text-xs uppercase tracking-widest font-black">
                        <?= htmlspecialchars($accesorio['categoria'] ?? 'General') ?>
                    </span>
                    <span
                        class="px-5 py-2 rounded-full text-xs uppercase tracking-widest font-black <?= ($accesorio['estado'] ?? '') == 'Activo' ? 'bg-green-100 text-green-700' : (($accesorio['estado'] ?? '') == 'Agotado' ? 'bg-red-100 text-red-600' : 'bg-yellow-100 text-yellow-700') ?>">
                        <?= htmlspecialchars($accesorio['estado'] ?? 'Disponible') ?>
                    </span>
                </div>

                <h2 class="text-4xl lg:text-6xl font-black text-slate-900 mt-8 leading-tight">
                    <?= htmlspecialchars($accesorio['nombre'] ?? 'Sin Nombre') ?>
                </h2>

                <div class="mt-5 flex items-center gap-3 text-slate-500 text-lg">
                    <i class="fa-solid fa-tag text-blue-600"></i>
                    <span class="font-bold"><?= htmlspecialchars($accesorio['marca'] ?? 'Genérico') ?></span>
                </div>

                <div class="mt-10">
                    <h3 class="text-xl font-black text-slate-900 mb-4">Descripción</h3>
                    <p class="text-slate-600 leading-relaxed text-lg">
                        <?= nl2br(htmlspecialchars($accesorio['descripcion'] ?? 'Sin descripción disponible.')) ?>
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-5 mt-10">
                    <div class="glass border border-slate-200 rounded-3xl p-6">
                        <span
                            class="block text-xs uppercase tracking-widest font-black text-slate-400 mb-2">Precio</span>
                        <h4 class="text-4xl font-black text-blue-700">RD$ <?= number_format($precio, 0) ?></h4>
                    </div>

                    <div class="glass border border-slate-200 rounded-3xl p-6">
                        <span
                            class="block text-xs uppercase tracking-widest font-black text-slate-400 mb-2">Stock</span>
                        <h4 class="text-4xl font-black text-slate-900"><?= $accesorio['stock'] ?? 0 ?></h4>
                    </div>
                </div>

                <div class="mt-8 text-sm text-slate-400 font-bold">
                    <i class="fa-solid fa-clock mr-2"></i>
                    <?php if (!empty($accesorio['fecha_creado'])): ?>
                    Registrado: <?= date('d/m/Y h:i A', strtotime($accesorio['fecha_creado'])) ?>
                    <?php else: ?>
                    Fecha no disponible
                    <?php endif; ?>
                </div>

                <div class="mt-10">
                    <a href="https://wa.me/18495886436?text=<?= $mensaje ?>" target="_blank"
                        class="w-full flex items-center justify-center gap-3 bg-green-500 hover:bg-green-600 text-white py-5 rounded-2xl text-center font-black shadow-2xl transition text-lg">
                        <i class="fa-brands fa-whatsapp text-2xl"></i> CONSULTAR POR WHATSAPP
                    </a>
                </div>
            </div>
        </div>
    </section>
</body>

</html>