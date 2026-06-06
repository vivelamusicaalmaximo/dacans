<?php

session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 💡 MIGRACIÓN: Reemplazamos la conexión local de SQLite por tu archivo central de SQL Server
require_once dirname(__DIR__) . '/config/conexion.php';
$db_sql = $pdo; 

/*
|--------------------------------------------------------------------------
| OBTENER ACCESORIOS (INICIAL)
|--------------------------------------------------------------------------
*/
try {
    $stmt = $db_sql->query("
        SELECT * FROM dbo.accesorios 
        ORDER BY id DESC
    ");
    $accesorios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener los accesorios: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dacans | Accesorios</title>
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

    .card-hover {
        transition: all .4s ease;
    }

    .card-hover:hover {
        transform: translateY(-10px);
        box-shadow: 0 25px 50px rgba(0, 0, 0, .08);
    }

    .glass {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(15px);
    }

    ::-webkit-scrollbar {
        width: 10px;
    }

    ::-webkit-scrollbar-thumb {
        background: #2563eb;
        border-radius: 20px;
    }

    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }

    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
    </style>
</head>

<body class="text-slate-900">

    <header class="sticky top-0 z-50 glass border-b border-white/30">
        <div class="container mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="bg-white p-3 rounded-2xl shadow-lg border border-slate-200">
                    <img src="../img/logo.webp" class="h-10 object-contain">
                </div>
                <div>
                    <h1 class="text-2xl font-black">DACANS</h1>
                    <p class="text-xs uppercase tracking-[4px] text-slate-500 font-bold">
                        Accesorios Tecnológicos
                    </p>
                </div>
            </div>

            <nav class="hidden md:flex items-center gap-8 font-bold">
                <?php if (isset($_SESSION['admin_logueado'])): ?>
                <a href="../mantenimiento"
                    class="bg-slate-900 hover:bg-black text-white px-5 py-3 rounded-2xl font-black shadow-lg transition">
                    <i class="fa-solid fa-screwdriver-wrench mr-2"></i>
                    Mantenimiento
                </a>
                <?php endif; ?>
                <a href="../" class="hover:text-blue-600 transition">Inicio</a>
                <a href="../catalogo" class="hover:text-blue-600 transition">Laptops</a>
            </nav>
        </div>
    </header>

    <section class="relative overflow-hidden bg-gradient-to-br from-slate-950 via-blue-950 to-slate-900 text-white">
        <div class="absolute top-0 left-0 w-[400px] h-[400px] bg-blue-500/20 rounded-full blur-[120px]"></div>
        <div class="absolute bottom-0 right-0 w-[400px] h-[400px] bg-cyan-500/20 rounded-full blur-[120px]"></div>

        <div class="container mx-auto px-6 py-24 relative z-10">
            <div class="max-w-3xl">
                <div
                    class="inline-flex items-center gap-3 bg-white/10 border border-white/10 px-5 py-3 rounded-full backdrop-blur-xl mb-8">
                    <span class="w-3 h-3 rounded-full bg-green-400 animate-pulse"></span>
                    <span class="uppercase tracking-[3px] text-xs font-black">
                        Accesorios Premium
                    </span>
                </div>

                <h2 class="text-5xl lg:text-7xl font-black leading-tight">
                    ACCESORIOS
                    <span class="block text-transparent bg-clip-text bg-gradient-to-r from-cyan-400 to-blue-500">
                        TECNOLÓGICOS
                    </span>
                </h2>

                <p class="text-slate-300 text-lg leading-relaxed mt-8">
                    Descubre mochilas, teclados, mouse, headsets y accesorios modernos
                    para complementar tu setup gamer o profesional.
                </p>
            </div>
        </div>
    </section>

    <?php
    /*
    |--------------------------------------------------------------------------
    | OBTENER CATEGORIAS
    |--------------------------------------------------------------------------
    */
    try {
        $stmtCategorias = $db_sql->query("
            SELECT DISTINCT categoria
            FROM dbo.accesorios
            WHERE categoria IS NOT NULL
            AND categoria != ''
            ORDER BY categoria ASC
        ");
        $categorias = $stmtCategorias->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        die("Error al procesar categorías en SQL Server: " . $e->getMessage());
    }
    ?>

    <section class="container mx-auto px-4 sm:px-6 py-16">
        <div class="mb-14">
            <h2 class="text-4xl font-black text-slate-900">
                Accesorios Disponibles
            </h2>
            <p class="text-slate-500 mt-3">
                Explora nuestros accesorios organizados por categorías
            </p>
        </div>

        <?php foreach ($categorias as $categoria): ?>
        <?php
        try {
            $stmt = $db_sql->prepare("
                SELECT *
                FROM dbo.accesorios
                WHERE categoria = ?
                ORDER BY id DESC
            ");
            $stmt->execute([$categoria]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die("Error al obtener los ítems de la categoría: " . $e->getMessage());
        }
        ?>

        <div class="mb-20">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-3xl font-black text-slate-900">
                        <?= htmlspecialchars($categoria) ?>
                    </h3>
                    <p class="text-slate-500 text-sm mt-1">
                        <?= count($items) ?> productos
                    </p>
                </div>

                <div class="hidden md:flex gap-2">
                    <button onclick="scrollCarousel('carousel-<?= md5($categoria) ?>', -1)"
                        class="w-11 h-11 rounded-2xl bg-white border border-slate-200 hover:bg-blue-600 hover:text-white transition flex items-center justify-center shadow-sm">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <button onclick="scrollCarousel('carousel-<?= md5($categoria) ?>', 1)"
                        class="w-11 h-11 rounded-2xl bg-white border border-slate-200 hover:bg-blue-600 hover:text-white transition flex items-center justify-center shadow-sm">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </div>

            <div id="carousel-<?= md5($categoria) ?>"
                class="flex gap-6 overflow-x-auto pb-4 snap-x snap-mandatory scrollbar-hide">

                <?php foreach ($items as $a): ?>
                <a href="show2.php?id=<?= $a['id'] ?>"
                    class="min-w-[280px] max-w-[280px] bg-white rounded-[2rem] overflow-hidden border border-slate-100 shadow-lg card-hover snap-start flex-shrink-0">

                    <div class="h-64 bg-slate-100 overflow-hidden">
                        <img src="<?= htmlspecialchars($a['imagen_url']) ?>" alt="<?= htmlspecialchars($a['nombre']) ?>"
                            class="w-full h-full object-cover hover:scale-110 transition duration-700">
                    </div>

                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <span
                                class="bg-blue-50 text-blue-600 text-[10px] font-black uppercase tracking-widest px-3 py-2 rounded-full">
                                <?= htmlspecialchars($a['marca']) ?>
                            </span>
                            <span class="text-xs text-slate-400 font-bold">
                                Stock: <?= $a['stock'] ?>
                            </span>
                        </div>

                        <h4 class="text-xl font-black text-slate-900 leading-tight min-h-[56px]">
                            <?= htmlspecialchars($a['nombre']) ?>
                        </h4>

                        <p class="text-slate-500 text-sm mt-3 line-clamp-3">
                            <?= htmlspecialchars($a['descripcion']) ?>
                        </p>

                        <div class="flex items-center justify-between mt-8">
                            <div>
                                <span class="block text-[10px] uppercase tracking-widest text-slate-400 font-black">
                                    Precio
                                </span>
                                <span class="text-xl font-black text-blue-600">
                                    $<?= number_format(is_numeric($a['precio'] ?? 0) ? $a['precio'] : 0, 2); ?>
                                </span>
                            </div>
                            <div
                                class="w-12 h-12 rounded-2xl bg-blue-600 text-white flex items-center justify-center shadow-lg">
                                <i class="fa-solid fa-arrow-right"></i>
                            </div>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>

            </div>
        </div>
        <?php endforeach; ?>
    </section>

    <?php include '../includes/footer.php'; ?>

    <script>
    function scrollCarousel(id, direction) {
        const carousel = document.getElementById(id);
        carousel.scrollBy({
            left: direction * 350,
            behavior: 'smooth'
        });
    }
    </script>
</body>

</html>