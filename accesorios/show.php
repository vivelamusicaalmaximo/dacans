<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['admin_logueado'])) {

    header("Location: ../login.php");
    exit;
}

/* ======================================================
   CONEXION SQL SERVER
====================================================== */

$dbFile = '../catalogo_equipos.sqlite';
$pdo = new PDO("sqlite:" . $dbFile);
/* ======================================================
   VALIDAR ID
====================================================== */

if (!isset($_GET['id']) || empty($_GET['id'])) {

    die("ID no válido");
}

$id = (int) $_GET['id'];

/* ======================================================
   OBTENER ACCESORIO
====================================================== */

$stmt = $pdo->prepare("
    SELECT TOP 1 *
    FROM accesorios
    WHERE id = ?
");

$stmt->execute([$id]);

$accesorio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$accesorio) {

    die("Accesorio no encontrado");
}

/* ======================================================
   PRECIO
====================================================== */

$precio = is_numeric($accesorio['precio'])
    ? (float)$accesorio['precio']
    : 0;

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= htmlspecialchars($accesorio['nombre']) ?> | DACANS</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon.png">
    <link rel="shortcut icon" href="/img/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
    body {

        background:
            radial-gradient(circle at top left, rgba(37, 99, 235, .08), transparent 30%),
            radial-gradient(circle at bottom right, rgba(14, 165, 233, .08), transparent 30%),
            #f8fafc;
    }

    .glass {

        background: rgba(255, 255, 255, .7);
        backdrop-filter: blur(14px);
    }
    </style>

</head>

<body class="min-h-screen p-4 md:p-8 text-slate-800">

    <div class="max-w-6xl mx-auto">

        <!-- HEADER -->

        <div class="flex flex-col lg:flex-row gap-4 justify-between items-center mb-8">

            <div class="flex items-center gap-4">

                <div class="bg-white p-3 rounded-2xl shadow-lg border border-slate-200">

                    <img src="../img/logo.webp" class="h-12 object-contain">

                </div>

                <div>

                    <h1 class="text-3xl font-black text-slate-900">
                        DETALLE ACCESORIO
                    </h1>

                    <p class="text-slate-500 text-sm">
                        Vista completa del producto
                    </p>

                </div>

            </div>

            <div class="flex gap-3 flex-wrap">



                <a href="edit.php?id=<?= $accesorio['id'] ?>"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-3 rounded-2xl font-black shadow-lg transition">

                    <i class="fa-solid fa-pen mr-2"></i>

                    Editar

                </a>

                <a href="view.php"
                    class="bg-slate-900 hover:bg-black text-white px-6 py-3 rounded-2xl font-black shadow-lg transition">

                    <i class="fa-solid fa-arrow-left mr-2"></i>

                    Volver

                </a>

            </div>

        </div>

        <!-- CARD -->

        <div class="grid lg:grid-cols-2 gap-8">

            <!-- IMAGEN -->

            <div class="bg-white rounded-[2.5rem] p-6 shadow-2xl border border-slate-200">

                <div class="aspect-square rounded-[2rem] overflow-hidden bg-slate-100 border border-slate-200">

                    <img src="<?= !empty($accesorio['imagen_url'])
                                    ? htmlspecialchars($accesorio['imagen_url'])
                                    : 'https://via.placeholder.com/700x700?text=NO+IMAGE' ?>"
                        class="w-full h-full object-cover">

                </div>

            </div>

            <!-- INFO -->

            <div class="bg-white rounded-[2.5rem] p-8 shadow-2xl border border-slate-200">

                <!-- CATEGORIA -->

                <div class="flex items-center justify-between gap-4 flex-wrap">

                    <span
                        class="bg-blue-100 text-blue-700 px-4 py-2 rounded-full text-xs uppercase tracking-widest font-black">

                        <?= htmlspecialchars($accesorio['categoria']) ?>

                    </span>

                    <span class="
                        px-4 py-2 rounded-full text-xs uppercase tracking-widest font-black

                        <?= $accesorio['estado'] == 'Activo'
                            ? 'bg-green-100 text-green-700'
                            : ($accesorio['estado'] == 'Agotado'
                                ? 'bg-red-100 text-red-600'
                                : 'bg-yellow-100 text-yellow-700')
                        ?>
                    ">

                        <?= htmlspecialchars($accesorio['estado']) ?>

                    </span>

                </div>

                <!-- NOMBRE -->

                <h2 class="text-4xl lg:text-5xl font-black text-slate-900 mt-6 leading-tight">

                    <?= htmlspecialchars($accesorio['nombre']) ?>

                </h2>

                <!-- MARCA -->

                <div class="mt-5 flex items-center gap-3 text-slate-500">

                    <i class="fa-solid fa-tag text-blue-600"></i>

                    <span class="font-bold">

                        Marca:
                        <?= htmlspecialchars($accesorio['marca']) ?>

                    </span>

                </div>

                <!-- DESCRIPCION -->

                <div class="mt-8">

                    <h3 class="text-lg font-black text-slate-900 mb-3">

                        Descripción

                    </h3>

                    <p class="text-slate-600 leading-relaxed">

                        <?= nl2br(htmlspecialchars($accesorio['descripcion'])) ?>

                    </p>

                </div>

                <!-- INFO GRID -->

                <div class="grid grid-cols-2 gap-5 mt-10">

                    <!-- PRECIO -->

                    <div class="glass border border-slate-200 rounded-3xl p-6">

                        <span class="block text-xs uppercase tracking-widest font-black text-slate-400 mb-2">

                            Precio

                        </span>

                        <h4 class="text-4xl font-black text-blue-700">
                            RD$ <?= number_format($precio, 0) ?>
                        </h4>

                    </div>

                    <!-- STOCK -->

                    <div class="glass border border-slate-200 rounded-3xl p-6">

                        <span class="block text-xs uppercase tracking-widest font-black text-slate-400 mb-2">

                            Stock

                        </span>

                        <h4 class="text-3xl font-black text-slate-900">

                            <?= $accesorio['stock'] ?>

                        </h4>

                    </div>

                </div>

                <!-- FECHA -->

                <div class="mt-8 text-sm text-slate-400 font-bold">

                    <i class="fa-solid fa-clock mr-2"></i>

                    <?php if (!empty($accesorio['fecha_creado'])): ?>

                    Registrado:
                    <?= date('d/m/Y h:i A', strtotime($accesorio['fecha_creado'])) ?>

                    <?php else: ?>

                    Fecha no disponible

                    <?php endif; ?>

                </div>

                <!-- BOTONES -->

                <div class="flex flex-col sm:flex-row gap-4 mt-10">

                    <a href="https://wa.me/18096926631" target="_blank"
                        class="flex-1 bg-green-500 hover:bg-green-600 text-white py-4 rounded-2xl text-center font-black shadow-xl transition">

                        <i class="fa-brands fa-whatsapp mr-2"></i>

                        CONTACTAR

                    </a>

                    <a href="delete.php?id=<?= $accesorio['id'] ?>"
                        onclick="return confirm('¿Eliminar este accesorio?')"
                        class="flex-1 bg-red-500 hover:bg-red-600 text-white py-4 rounded-2xl text-center font-black shadow-xl transition">

                        <i class="fa-solid fa-trash mr-2"></i>

                        ELIMINAR

                    </a>

                </div>

            </div>

        </div>

    </div>

</body>

</html>