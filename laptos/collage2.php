<?php
session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

/* =========================================================
   BASE DE DATOS
========================================================= */
$dbFile = '../catalogo_equipos.sqlite';

try {
    $pdo = new PDO("sqlite:" . $dbFile);
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
for($i=1; $i<=4; $i++){
    if(!empty($_GET["id$i"])){
        $ids[] = $_GET["id$i"];
    }
}

/* =========================================================
   AUTOMATICO
========================================================= */
if(empty($ids)){
    $offset = (int)($_GET['offset'] ?? 0);
    $stmt = $pdo->prepare("
        SELECT *
        FROM productos_informatica
        WHERE estado = 'Lista'
        ORDER BY id_local DESC
        LIMIT 4 OFFSET ?
    ");
    $stmt->bindValue(1, $offset, PDO::PARAM_INT);
    $stmt->execute();
}else{
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
   LISTA EQUIPOS
========================================================= */
$listaEquipos = $pdo->query("
    SELECT
        id_local,
        equipo_marca,
        equipo_modelo
    FROM productos_informatica
    WHERE estado = 'Lista'
    ORDER BY id_local DESC
")->fetchAll(PDO::FETCH_ASSOC);
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
body{
    background: radial-gradient(circle at top,#1e3a8a 0%,#020617 60%);
    min-height:100vh;
    font-family:Arial,sans-serif;
    padding:10px;
    overflow-x:hidden;
}

/* =========================================================
   CONTROLES
========================================================= */
.controls{
    width:100%;
    max-width:780px;
    margin:auto auto 12px auto;
    background:rgba(15,23,42,.95);
    border:1px solid rgba(59,130,246,.2);
    border-radius:18px;
    padding:12px;
    backdrop-filter:blur(10px);
}

.controls-grid{
    display:grid;
    grid-template-columns:70px 1fr;
    gap:10px;
    align-items:start;
}

/* BOTONES */
.nav-buttons{
    display:flex;
    gap:6px;
}

.nav-btn{
    width:32px;
    height:32px;
    border-radius:10px;
    background:#2563eb;
    color:white;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:11px;
    transition:.2s;
}

.nav-btn:hover{
    transform:scale(.95);
}

/* FORM */
.form-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:6px;
}

.select{
    background:#0f172a;
    border:1px solid #1e293b;
    color:white;
    border-radius:12px;
    padding:8px 10px;
    font-size:10px;
    font-weight:700;
    outline:none;
}

.select:focus{
    border-color:#3b82f6;
}

.btn-create{
    grid-column:span 4;
    background: linear-gradient(135deg,#2563eb,#06b6d4);
    border:none;
    color:white;
    padding:9px;
    border-radius:14px;
    font-size:11px;
    font-weight:900;
    letter-spacing:1px;
    text-transform:uppercase;
    cursor:pointer;
    transition:.2s;
}

.btn-create:hover{
    opacity:.9;
}

/* =========================================================
   POSTER
========================================================= */
.poster{
    width:450px;
    max-width:780px;
    margin:auto;
    background: linear-gradient(180deg, rgba(15,23,42,.97), rgba(2,6,23,.98));
    border-radius:24px;
    overflow:hidden;
    border:1px solid rgba(59,130,246,.18);
    box-shadow: 0 12px 30px rgba(0,0,0,.45);
}

/* =========================================================
   HEADER
========================================================= */
.hero{
    padding:14px 16px 4px;
}

.logo{
    height:28px;
}

.hero-title{
    margin-top:8px;
    font-size:22px;
    font-weight:900;
    line-height:1;
    text-transform:uppercase;
    background: linear-gradient(90deg, #60a5fa, #ffffff, #22d3ee);
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
}

.hero-sub{
    margin-top:4px;
    color:#94a3b8;
    font-size:7px;
    letter-spacing:2px;
    font-weight:800;
    text-transform:uppercase;
}

/* =========================================================
   GRID
========================================================= */
.grid-cards{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:8px;
    padding:10px;
    width: 450px;
}


/* =========================================================
   CARD
========================================================= */
.card{
    background: linear-gradient(180deg, rgba(15,23,42,.98), rgba(2,6,23,.98));
    border:1px solid rgba(59,130,246,.10);
    border-radius:16px;
    padding:8px;
    overflow:hidden;
    position:relative;
}

/* TOP */
.top{
    display:flex;
    justify-content:flex-start;
    align-items:flex-start;
    gap:4px;
}

.brand{
    color:white;
    font-size:11px;
    font-weight:900;
    text-transform:uppercase;
    line-height:1;
}

.model{
    color:#93c5fd;
    font-size:6px;
    font-weight:700;
    margin-top:2px;
    text-transform:uppercase;
    line-height:1.1;
}

.badge{
    background:#2563eb;
    color:white;
    padding:2px 5px;
    border-radius:999px;
    font-size:5px;
    font-weight:900;
    text-transform:uppercase;
    margin-left:-2px;
    height:14px;
    display:flex;
    align-items:center;
}

/* =========================================================
   CONTENT
========================================================= */
.content{
    display:grid;
    grid-template-columns:86px 52px;
    gap:1px;
    align-items:center;
    margin-top:3px;
    width:auto;
    justify-content:start;
}

/* =========================================================
   SPECS
========================================================= */
.specs{
    display:flex;
    flex-direction:column;
    gap:3px;
}

.spec{
    background:rgba(255,255,255,.03);
    border:1px solid rgba(255,255,255,.04);
    border-radius:6px;
    padding:2px 4px;
    width:82px;
    min-height:18px;
}

.spec-title{
    color:#60a5fa;
    font-size:7px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.6px;
    line-height:1;
}

.spec-value{
    color:white;
    font-size:9px;
    font-weight:700;
    margin-top:1px;
    line-height:1.1;
    text-align:justify;
}

/* =========================================================
   IMAGE
========================================================= */
.product-image{
 width: 75px;
height: 55px;
    object-fit:contain;
    text-align: right;
    display: block;
    margin-left: auto;
    filter: drop-shadow(0 6px 10px rgba(0,0,0,.45));
}

/* =========================================================
   BOTTOM
========================================================= */
.bottom{
    margin-top:2px;
    display:flex;
    justify-content:space-between;
    align-items:flex-end;
    gap:4px;
}

.warranty{
    color:#cbd5e1;
    font-size:6px;
    font-weight:700;
    text-transform:uppercase;
    line-height:1;
}

.price-container {
    text-align: right;
}

.price-label{
    color:#22d3ee;
    font-size:6px;
    font-weight:900;
    text-transform:uppercase;
    text-align:right;
    line-height:1;
}

.price{
    color:white;
    font-size:14px;
    font-weight:900;
    line-height:1;
    text-align:right;
}

/* =========================================================
   FOOTER
========================================================= */
.footer{
    border-top:1px solid rgba(255,255,255,.06);
    padding:10px 12px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:6px;
}

.contacts{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}

.contact{
    color:white;
    font-size:7px;
    font-weight:700;
}

.contact i{
    margin-right:3px;
}

.cta{
    background: linear-gradient(135deg, #2563eb, #06b6d4);
    color:white;
    padding:7px 14px;
    border-radius:10px;
    font-size:8px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:1px;
}


/* =========================================================
   MOBILE
========================================================= */
@media(max-width:700px){
    .controls-grid{
        grid-template-columns:1fr;
    }
    .form-grid{
        grid-template-columns:1fr 1fr;
    }
    .btn-create{
        grid-column:span 2;
    }
    .grid-cards{
        grid-template-columns:1fr;
    }
    .content{
        grid-template-columns:82px 70px;
    }
    .product-image{
        height:70px;
    }
}
</style>
</head>
<body>

<div class="controls">
    <div class="controls-grid">
        <div class="nav-buttons">
            <a href="?offset=<?= max(0, ((int)($_GET['offset'] ?? 0) - 4)) ?>" class="nav-btn">
                <i class="fa-solid fa-chevron-left"></i>
            </a>
            <a href="?offset=<?= ((int)($_GET['offset'] ?? 0) + 4) ?>" class="nav-btn">
                <i class="fa-solid fa-chevron-right"></i>
            </a>
        </div>

        <form method="GET" class="form-grid">
            <?php for($i=1; $i<=4; $i++): ?>
                <select name="id<?= $i ?>" class="select">
                    <option value="">Equipo <?= $i ?></option>
                    <?php foreach($listaEquipos as $item): ?>
                        <option value="<?= $item['id_local'] ?>" <?= (($_GET["id$i"] ?? '') == $item['id_local']) ? 'selected' : '' ?>>
                            #<?= $item['id_local'] ?> - <?= htmlspecialchars($item['equipo_marca']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endfor; ?>

            <button type="submit" class="btn-create">
                <i class="fa-solid fa-wand-magic-sparkles mr-2"></i>Crear Collage
            </button>
        </form>
    </div>
</div>

<div class="poster">
    <div class="hero">
        <img src="../img/logo.webp" class="logo">
        <div class="hero-title">Equipos Premium</div>
        <div class="hero-sub">Laptops • Tecnología • Workstations</div>
    </div>

    <div class="grid-cards">
        <?php foreach($equipos as $e): ?>
        <div class="card">
            <div class="top">
                <div>
                    <div class="brand"><?= htmlspecialchars($e['equipo_marca']) ?></div>
                    <div class="model"><?= htmlspecialchars($e['equipo_modelo']) ?></div>
                </div>
                <div class="badge">Disponible</div>
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
                        <div class="spec-value"><?= htmlspecialchars($e['pantalla'] ?? '') ?></div>
                    </div>
                </div>

                <div class="image-container">
                    <img src="<?= !empty($e['imagen_url']) ? htmlspecialchars($e['imagen_url']) : '../img/default.png' ?>" class="product-image">
                </div>
            </div>

            <div class="bottom">
                <div class="warranty">
                    <i class="fa-solid fa-shield-halved text-blue-400"></i> 1 Año Garantía
                </div>
                <div class="price-container">
                    <div class="price-label">Precio Especial</div>
                    <div class="price">RD$ <?= number_format((float)$e['precio']) ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="footer">
        <div class="contacts">
            <div class="contact">
                <i class="fa-brands fa-whatsapp text-green-400"></i> 849-588-6436
            </div>
            <div class="contact">
                <i class="fa-brands fa-instagram text-pink-400"></i> @dacanscomputers
            </div>
            <div class="contact">
                <i class="fa-solid fa-globe text-cyan-400"></i> dacansdr.com
            </div>
        </div>
        <div class="cta">¡Cotiza Hoy!</div>
    </div>
</div>

</body>
</html>