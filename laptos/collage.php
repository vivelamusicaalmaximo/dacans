<?php
session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

/* =========================================================
   BASE DE DATOS SQL SERVER
========================================================= */
require '../config/conexion.php';

try {

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {

    die("Error DB: " . $e->getMessage());
}

/* =========================================================
   EQUIPOS SELECCIONADOS
========================================================= */
$ids = [];

for ($i = 1; $i <= 6; $i++) {

    if (!empty($_GET["id$i"])) {
        $ids[] = $_GET["id$i"];
    }
}

/* =========================================================
   AUTOMATICO Y SELECCIONADOS
========================================================= */
if (empty($ids)) {

    $offset = (int)($_GET['offset'] ?? 0);

    // CORRECCIÓN: Eliminado el ROW_NUMBER() y PARTITION BY para traer todos los registros reales sin omitir repetidos
    $stmt = $pdo->prepare("
        SELECT *
        FROM productos_informatica
        WHERE estado = 'Lista'
        ORDER BY id_local DESC
        OFFSET ? ROWS
        FETCH NEXT 6 ROWS ONLY
    ");

    $stmt->bindValue(1, $offset, PDO::PARAM_INT);
    $stmt->execute();

} else {

    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("
        SELECT *
        FROM productos_informatica
        WHERE id_local IN ($placeholders)
    ");

    $stmt->execute($ids);
}

$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   LISTA EQUIPOS PARA SELECTS
========================================================= */

$listaStmt = $pdo->query("
    SELECT * FROM productos_informatica 
    WHERE estado = 'Lista' 
    ORDER BY id_local DESC
");

$listaEquipos = $listaStmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collage Publicitario</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
    body {
        background: radial-gradient(circle at top, #1e3a8a 0%, #020617 60%);
        min-height: 100vh;
        font-family: Arial, sans-serif;
        padding: 10px;
        overflow-x: hidden;
    }

    /* =========================================================
       CONTROLES
    ========================================================= */
    .controls {
        width: 100%;
        max-width: 780px;
        margin: auto auto 12px auto;
        background: rgba(15, 23, 42, .95);
        border: 1px solid rgba(59, 130, 246, .2);
        border-radius: 18px;
        padding: 12px;
        backdrop-filter: blur(10px);
    }

    .controls-grid {
        display: grid;
        grid-template-columns: 70px 1fr;
        gap: 10px;
        align-items: start;
    }

    /* BOTONES NAVEGACIÓN */
    .nav-buttons {
        display: flex;
        gap: 6px;
    }

    .nav-btn {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        background: #2563eb;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        transition: .2s;
    }

    .nav-btn:hover {
        transform: scale(.95);
    }

    /* FORMULARIO */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 6px;
    }

    .select {
        background: #0f172a;
        border: 1px solid #1e293b;
        color: white;
        border-radius: 12px;
        padding: 8px 10px;
        font-size: 10px;
        font-weight: 700;
        outline: none;
    }

    .select:focus {
        border-color: #3b82f6;
    }

    .btn-create {
        grid-column: span 3;
        background: linear-gradient(135deg, #2563eb, #06b6d4);
        border: none;
        color: white;
        padding: 9px;
        border-radius: 14px;
        font-size: 11px;
        font-weight: 900;
        letter-spacing: 1px;
        text-transform: uppercase;
        cursor: pointer;
        transition: .2s;
    }

    .btn-create:hover {
        opacity: .9;
    }

    /* =========================================================
       POSTER
    ========================================================= */
    .poster {
        width: 420px;
        max-width: 780px;
        margin: auto;
        background: linear-gradient(180deg, #f8fafc, #e2e8f0);
        border-radius: 24px;
        overflow: hidden;
        border: 1px solid #cbd5e1;
        box-shadow: 0 12px 30px rgba(0, 0, 0, .15);
        transition: all 0.3s ease;
    }

    .hero {
        display: block;
        text-align: center;
        padding: 14px 16px 4px;
    }

    .hero img {
        display: block;
        margin: 0 auto;
    }

    .logo {
        height: 50px;
    }

    .hero-title {
        margin-top: 8px;
        font-size: 22px;
        font-weight: 900;
        line-height: 1;
        text-transform: uppercase;
        background: linear-gradient(90deg, #1e40af, #3b82f6, #1d4ed8);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .hero-sub {
        margin-top: 4px;
        color: #64748b;
        font-size: 7px;
        letter-spacing: 2px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .grid-cards {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
        padding: 10px;
        width: 420px;
    }

    .card {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 16px;
        padding: 8px;
        overflow: hidden;
        position: relative;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }

    .top {
        margin-bottom: 8px;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 4px;
    }

    .brand {
        color: #0f172a;
        font-size: 9px;
        font-weight: 900;
        text-transform: uppercase;
        line-height: 1;
    }

    .content {
        display: flex;
        flex-direction: row;
        gap: 10px;
        align-items: center;
        margin-top: 3px;
        width: auto;
        justify-content: start;
    }

    .specs {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .spec {
        background: transparent;
        border: none;
        padding: 0;
        width: auto;
        min-height: auto;
        margin-bottom: 4px;
    }

    .spec-title {
        color: #2563eb;
        font-size: 10px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: .10px;
        line-height: 1;
    }

    .spec-value {
        color: #334155;
        font-size: 9px;
        font-weight: 700;
        margin-top: 1px;
        line-height: 1;
    }

    .product-image {
        width: 100%;
        height: 62px;
        object-fit: contain;
        transform: translateX(-8px);
        filter: drop-shadow(0 4px 6px rgba(0, 0, 0, .15));
    }

    .bottom {
        margin-top: 2px;
        display: flex;
        justify-content: flex-start;
        align-items: flex-end;
        gap: 4px;
    }

    .warranty {
        color: #475569;
        font-size: 9px;
        font-weight: 700;
        text-transform: uppercase;
        line-height: 1;
    }

    .price-label {
        color: #0284c7;
        font-size: 8px;
        margin-left: 24px;
        font-weight: 900;
        text-transform: uppercase;
        text-align: left;
        line-height: 1;
    }

    .price {
        color: #1e3a8a;
        font-size: 15px;
        margin-left: 20px;
        font-weight: 900;
        line-height: 1;
        text-align: left;
    }

    .footer {
        border-top: 1px solid #cbd5e1;
        padding: 10px 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
    }

    .contacts {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .contact {
        color: #334155;
        font-size: 10px;
        font-weight: 700;
    }

    .contact i {
        margin-right: 3px;
    }

    .cta {
        background: linear-gradient(135deg, #2563eb, #06b6d4);
        color: white;
        padding: 2px 7px;
        border-radius: 9px;
        font-size: 6px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    /* =========================================================
       TEMAS ALTERNATIVOS
    ========================================================= */
    .poster.theme-dark {
        background: linear-gradient(180deg, rgba(15, 23, 42, .97), rgba(2, 6, 23, .98));
        border: 1px solid rgba(59, 130, 246, .18);
        box-shadow: 0 12px 30px rgba(0, 0, 0, .45);
    }

    .poster.theme-dark .hero-title {
        background: linear-gradient(90deg, #60a5fa, #ffffff, #22d3ee);
        -webkit-background-clip: text;
    }

    .poster.theme-dark .hero-sub {
        color: #94a3b8;
    }

    .poster.theme-dark .card {
        background: linear-gradient(180deg, rgba(15, 23, 42, .98), rgba(2, 6, 23, .98));
        border: 1px solid rgba(59, 130, 246, .10);
        box-shadow: none;
    }

    .poster.theme-dark .brand {
        color: white;
    }

    .poster.theme-dark .spec-title {
        color: #60a5fa;
    }

    .poster.theme-dark .spec-value {
        color: white;
    }

    .poster.theme-dark .warranty {
        color: #cbd5e1;
    }

    .poster.theme-dark .price-label {
        color: #22d3ee;
    }

    .poster.theme-dark .price {
        color: white;
    }

    .poster.theme-dark .footer {
        border-top: 1px solid rgba(255, 255, 255, .06);
    }

    .poster.theme-dark .contact {
        color: white;
    }

    .poster.theme-cyber {
        background: linear-gradient(180deg, #1e1b4b, #090514);
        border: 1px solid #d946ef;
        box-shadow: 0 12px 30px rgba(0, 0, 0, .45);
    }

    .poster.theme-cyber .hero-title {
        background: linear-gradient(90deg, #ff007f, #ffffff, #d946ef);
        -webkit-background-clip: text;
    }

    .poster.theme-cyber .hero-sub {
        color: #d946ef;
    }

    .poster.theme-cyber .card {
        background: #130f26;
        border: 1px solid rgba(217, 70, 239, .2);
        box-shadow: none;
    }

    .poster.theme-cyber .brand {
        color: white;
    }

    .poster.theme-cyber .spec-title {
        color: #d946ef;
    }

    .poster.theme-cyber .spec-value {
        color: white;
    }

    .poster.theme-cyber .warranty {
        color: #cbd5e1;
    }

    .poster.theme-cyber .price-label {
        color: #ff007f;
    }

    .poster.theme-cyber .price {
        color: white;
    }

    .poster.theme-cyber .footer {
        border-top: 1px solid rgba(217, 70, 239, .2);
    }

    .poster.theme-cyber .contact {
        color: white;
    }

    .poster.theme-green {
        background: linear-gradient(180deg, #064e3b, #022c22);
        border: 1px solid #10b981;
        box-shadow: 0 12px 30px rgba(0, 0, 0, .45);
    }

    .poster.theme-green .hero-title {
        background: linear-gradient(90deg, #34d399, #ffffff, #059669);
        -webkit-background-clip: text;
    }

    .poster.theme-green .hero-sub {
        color: #34d399;
    }

    .poster.theme-green .card {
        background: #043327;
        border: 1px solid rgba(16, 185, 129, .15);
        box-shadow: none;
    }

    .poster.theme-green .brand {
        color: white;
    }

    .poster.theme-green .spec-title {
        color: #34d399;
    }

    .poster.theme-green .spec-value {
        color: white;
    }

    .poster.theme-green .warranty {
        color: #cbd5e1;
    }

    .poster.theme-green .price-label {
        color: #10b981;
    }

    .poster.theme-green .price {
        color: white;
    }

    .poster.theme-green .footer {
        border-top: 1px solid rgba(16, 185, 129, .15);
    }

    .poster.theme-green .contact {
        color: white;
    }

    /* =========================================================
       MOBILE
    ========================================================= */
    @media(max-width:700px) {
        .controls-grid {
            grid-template-columns: 1fr;
        }

        .form-grid {
            grid-template-columns: 1fr 1fr;
        }

        .btn-create {
            grid-column: span 2;
        }

        .grid-cards {
            grid-template-columns: 1fr;
        }

        .content {
            grid-template-columns: 82px 70px;
        }

        .product-image {
            height: 70px;
        }
    }
    </style>
</head>

<body>

    <a class="px-4 py-2 rounded-lg bg-blue-500 hover:bg-blue-600 text-white font-semibold transition duration-200 inline-block"
        href="../mantenimiento">
        Salir
    </a>

    <div class="controls">
        <div
            class="mb-3 pb-3 border-b border-slate-700/50 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <span class="text-white text-xs font-bold uppercase tracking-wider">
                <i class="fa-solid fa-palette text-blue-400 mr-1"></i> Color del Diseño:
            </span>
            <div class="flex gap-2 overflow-x-auto pb-1 sm:pb-0 snap-x">
                <button type="button" onclick="changeTheme('white-blue')"
                    class="snap-mini px-2.5 py-1 bg-white border border-blue-600 text-blue-900 text-[10px] font-black rounded-lg uppercase tracking-tight hover:bg-slate-100 transition whitespace-nowrap">⚪
                    Blanco con Azul</button>
                <button type="button" onclick="changeTheme('dark')"
                    class="snap-mini px-2.5 py-1 bg-slate-800 border border-blue-500 text-white text-[10px] font-black rounded-lg uppercase tracking-tight hover:bg-slate-700 transition whitespace-nowrap">🔵
                    Oscuro Original</button>
                <button type="button" onclick="changeTheme('cyber')"
                    class="snap-mini px-2.5 py-1 bg-fuchsia-950 border border-fuchsia-500 text-fuchsia-300 text-[10px] font-black rounded-lg uppercase tracking-tight hover:bg-fuchsia-900 transition whitespace-nowrap">🔮
                    Cyberpunk</button>
                <button type="button" onclick="changeTheme('green')"
                    class="snap-mini px-2.5 py-1 bg-emerald-950 border border-emerald-500 text-emerald-300 text-[10px] font-black rounded-lg uppercase tracking-tight hover:bg-emerald-900 transition whitespace-nowrap">🟢
                    Verde Premium</button>
            </div>
        </div>

        <div class="controls-grid">
            <div class="nav-buttons">
                <a href="?offset=<?= max(0, ((int)($_GET['offset'] ?? 0) - 6)) ?>" class="nav-btn">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
                <a href="?offset=<?= ((int)($_GET['offset'] ?? 0) + 6) ?>" class="nav-btn">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>

            <form method="GET" class="form-grid">
                <?php for($i=1; $i<=6; $i++): ?>
                <select name="id<?= $i ?>" class="select">
                    <option value="">Equipo <?= $i ?></option>
                    <?php foreach($listaEquipos as $item): ?>
                    <option value="<?= $item['id_local'] ?>"
                        <?= (($_GET["id$i"] ?? '') == $item['id_local']) ? 'selected' : '' ?>>
                        #<?= $item['id_local'] ?> -
                        <?= htmlspecialchars($item['equipo_marca'] . ' ' . $item['equipo_modelo']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php endfor; ?>

                <button type="submit" class="btn-create">
                    <i class="fa-solid fa-wand-magic-sparkles mr-2"></i> Crear Collage
                </button>
            </form>
        </div>
    </div>

    <div class="poster" id="mainPoster">
        <div class="hero">
            <img src="../img/logo.webp" class="logo">
            <div class="hero-title">Equipos Premium</div>
            <div class="hero-sub">Laptops • Tecnología • Workstations</div>
        </div>

        <div class="grid-cards">
            <?php foreach($equipos as $e): ?>
            <div class="card">
                <div class="top">
                    <div class="brand">
                        <?= htmlspecialchars($e['equipo_marca']) . ' ' . htmlspecialchars($e['equipo_modelo']) ?>
                    </div>
                </div>

                <div class="content">
                    <div class="specs">
                        <div class="spec">
                            <div class="spec-title">Procesador</div>
                            <div class="spec-value">
                                <?= htmlspecialchars(($e['proc_familia'] ?? '') . ' ' . ($e['proc_modelo'] ?? '')) ?>
                            </div>
                        </div>
                        <div class="spec">
                            <div class="spec-title">RAM</div>
                            <div class="spec-value"><?= htmlspecialchars($e['memoria'] ?? '') ?></div>
                        </div>
                        <div class="spec">
                            <div class="spec-title">SSD</div>
                            <div class="spec-value"><?= htmlspecialchars($e['disco'] ?? '') ?></div>
                        </div>
                        <div class="spec">
                            <div class="spec-title">Pantalla</div>
                            <div class="spec-value"><?= ucfirst(mb_strtolower($e['pantalla'] ?? '', 'UTF-8')) ?></div>
                        </div>
                    </div>

                    <div>
                        <img src="<?= !empty($e['imagen_url']) ? htmlspecialchars($e['imagen_url']) : '../img/default.png' ?>"
                            class="product-image">
                    </div>
                </div>

                <div class="bottom">
                    <div class="warranty">
                        <i class="fa-solid fa-shield-halved text-blue-500"></i>
                        1 Año <br>Garantía
                    </div>
                    <div>
                        <div class="price-label">Precio Especial</div>
                        <div class="price">RD$ <?= number_format((float)$e['precio']) ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="footer">
            <div class="contacts">
                <div class="contact"><i class="fa-brands fa-whatsapp text-green-600"></i> 849-588-6436</div>
                <div class="contact"><i class="fa-brands fa-instagram text-pink-600"></i> @dacanscomputers</div>
                <div class="contact"><i class="fa-solid fa-globe text-blue-600"></i> www.dacansdr.com</div>
            </div>
            <div class="cta">¡Cotiza Hoy!</div>
        </div>
    </div>

    <script>
    function changeTheme(themeName) {
        const poster = document.getElementById('mainPoster');
        poster.classList.remove('theme-dark', 'theme-cyber', 'theme-green');
        if (themeName !== 'white-blue') {
            poster.classList.add('theme-' + themeName);
        }
    }
    </script>
</body>

</html>