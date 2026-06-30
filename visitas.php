<?php
session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

require 'config/conexion.php';

try {

    // =========================
    // KPIs PRINCIPALES
    // =========================

    $total = $pdo->query("SELECT COUNT(*) FROM registro_visitas")->fetchColumn();

    $hoy = $pdo->query("
        SELECT COUNT(*) 
        FROM registro_visitas 
        WHERE CAST(fecha_visita AS DATE)=CAST(GETDATE() AS DATE)
    ")->fetchColumn();

    $ayer = $pdo->query("
        SELECT COUNT(*) 
        FROM registro_visitas 
        WHERE CAST(fecha_visita AS DATE)=CAST(DATEADD(DAY,-1,GETDATE()) AS DATE)
    ")->fetchColumn();

    $usuarios = $pdo->query("
        SELECT COUNT(DISTINCT ip_usuario) 
        FROM registro_visitas
    ")->fetchColumn();

    $online = $pdo->query("
        SELECT COUNT(DISTINCT ip_usuario)
        FROM registro_visitas
        WHERE fecha_visita >= DATEADD(MINUTE,-5,GETDATE())
    ")->fetchColumn();

    $semana = $pdo->query("
        SELECT COUNT(*) 
        FROM registro_visitas 
        WHERE fecha_visita >= DATEADD(DAY,-7,GETDATE())
    ")->fetchColumn();

    $mes = $pdo->query("
        SELECT COUNT(*) 
        FROM registro_visitas 
        WHERE fecha_visita >= DATEADD(DAY,-30,GETDATE())
    ")->fetchColumn();

    $crecimiento = ($ayer > 0)
        ? round((($hoy - $ayer) / $ayer) * 100, 2)
        : 100;

    // =========================
    // TOP PAGINAS
    // =========================


$topPaginas = $pdo->query("
    SELECT TOP 10
        pagina_visitada,
        COUNT(*) AS total
    FROM registro_visitas
    GROUP BY pagina_visitada
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

    // =========================
    // TOP IPS
    // =========================

    $topIps = $pdo->query("
        SELECT TOP 10 ip_usuario,
        COUNT(*) total
        FROM registro_visitas
        GROUP BY ip_usuario
        ORDER BY total DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // =========================
    // GRAFICA 30 DIAS
    // =========================

    $grafica = $pdo->query("
        SELECT 
        CONVERT(VARCHAR(10),fecha_visita,120) fecha,
        COUNT(*) total
        FROM registro_visitas
        WHERE fecha_visita >= DATEADD(DAY,-30,GETDATE())
        GROUP BY CONVERT(VARCHAR(10),fecha_visita,120)
        ORDER BY fecha
    ")->fetchAll(PDO::FETCH_ASSOC);

    $labels = array_column($grafica,'fecha');
    $data = array_column($grafica,'total');

    $labels = array_column($grafica,'fecha');
$data = array_column($grafica,'total');

// =========================
// ULTIMAS VISITAS
// =========================

$ultimas = $pdo->query("
    SELECT TOP 50
        ip_usuario,
        fecha_visita,
        pagina_visitada,
        navegador,
        dispositivo,
        pais,
        ciudad,
        referencia
    FROM registro_visitas
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die($e->getMessage());
}


?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Analytics V2 Enterprise</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
    body {
        background:
            radial-gradient(circle at top left, #2563eb22, transparent 30%),
            radial-gradient(circle at bottom right, #9333ea22, transparent 30%),
            #020617;
        font-family: system-ui;
    }

    /* ================= CARD ================= */

    .card {
        background: rgba(15, 23, 42, .75);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, .08);
        border-radius: 24px;
        transition: .3s;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 50px rgba(0, 0, 0, .4);
    }

    /* ================= KPI ================= */

    .kpi {
        font-size: 40px;
        font-weight: 900;
        color: white;
    }

    .label {
        font-size: 11px;
        letter-spacing: 1px;
        color: #94a3b8;
        text-transform: uppercase;
    }

    /* ICONS */

    .icon {
        width: 65px;
        height: 65px;
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
    }

    .blue {
        background: #1e3a8a;
        color: #60a5fa;
    }

    .green {
        background: #064e3b;
        color: #34d399;
    }

    .orange {
        background: #78350f;
        color: #fbbf24;
    }

    .purple {
        background: #4c1d95;
        color: #c084fc;
    }

    .red {
        background: #7f1d1d;
        color: #f87171;
    }

    .cyan {
        background: #155e75;
        color: #67e8f9;
    }

    /* TABLE */

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        background: #0f172a;
        color: #94a3b8;
        font-size: 11px;
        text-transform: uppercase;
        padding: 14px;
    }

    td {
        padding: 14px;
        color: #e2e8f0;
        border-top: 1px solid rgba(255, 255, 255, .05);
    }

    tbody tr:hover {
        background: #0f172a;
    }

    /* LIVE DOT */

    .live {
        width: 10px;
        height: 10px;
        background: #22c55e;
        border-radius: 50%;
        animation: pulse 1.2s infinite;
    }

    @keyframes pulse {
        0% {
            opacity: 1
        }

        50% {
            opacity: .2
        }

        100% {
            opacity: 1
        }
    }
    </style>
</head>

<body>

    <div class="max-w-7xl mx-auto p-8">

        <!-- ================= HEADER ================= -->

        <div class="card p-6 flex justify-between items-center mb-8">

            <div>

                <div class="flex items-center gap-2 text-green-400 font-bold text-sm">
                    <div class="live"></div>
                    LIVE SYSTEM
                </div>

                <h1 class="text-4xl font-black text-white mt-2">
                    DACANS ANALYTICS V2
                </h1>

                <p class="text-slate-400">
                    Dashboard empresarial en tiempo real
                </p>

            </div>

            <div class="text-right">

                <div class="text-slate-400 text-sm">Estado</div>

                <div class="text-green-400 font-bold text-xl">
                    ONLINE
                </div>

            </div>

        </div>

        <!-- ================= KPI GRID ================= -->

        <div class="grid xl:grid-cols-4 md:grid-cols-2 gap-6">

            <div class="card p-5 flex justify-between items-center">
                <div>
                    <div class="label">VISITAS HOY</div>
                    <div class="kpi"><?=number_format($hoy)?></div>
                </div>
                <div class="icon blue"><i class="fa fa-chart-line"></i></div>
            </div>

            <div class="card p-5 flex justify-between items-center">
                <div>
                    <div class="label">USUARIOS ONLINE</div>
                    <div class="kpi"><?=number_format($online)?></div>
                </div>
                <div class="icon green"><i class="fa fa-wifi"></i></div>
            </div>

            <div class="card p-5 flex justify-between items-center">
                <div>
                    <div class="label">CRECIMIENTO</div>
                    <div class="kpi"><?=number_format($crecimiento)?>%</div>
                </div>
                <div class="icon cyan"><i class="fa fa-arrow-trend-up"></i></div>
            </div>

            <div class="card p-5 flex justify-between items-center">
                <div>
                    <div class="label">TOTAL VISITAS</div>
                    <div class="kpi"><?=number_format($total)?></div>
                </div>
                <div class="icon orange"><i class="fa fa-database"></i></div>
            </div>

        </div>

        <!-- ================= CHART ================= -->

        <div class="card p-6 mt-8">

            <h2 class="text-white font-black text-2xl mb-4">
                Visitas últimos 30 días
            </h2>

            <canvas id="chart" height="100"></canvas>

        </div>

        <script>
        new Chart(document.getElementById('chart'), {
            type: 'line',
            data: {
                labels: <?=json_encode($labels)?>,
                datasets: [{
                    label: 'Visitas',
                    data: <?=json_encode($data)?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59,130,246,.2)',
                    fill: true,
                    tension: .4,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#fff'
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: '#94a3b8'
                        }
                    },
                    y: {
                        ticks: {
                            color: '#94a3b8'
                        }
                    }
                }
            }
        });
        </script>

        <!-- ================= SEGUNDA FILA KPIs ================= -->

        <div class="grid xl:grid-cols-4 md:grid-cols-2 gap-6 mt-8">

            <div class="card p-5 flex justify-between items-center">

                <div>

                    <div class="label">VISITAS AYER</div>

                    <div class="kpi"><?=number_format($ayer)?></div>

                </div>

                <div class="icon purple">

                    <i class="fa-solid fa-calendar-day"></i>

                </div>

            </div>

            <div class="card p-5 flex justify-between items-center">

                <div>

                    <div class="label">USUARIOS ÚNICOS</div>

                    <div class="kpi"><?=number_format($usuarios)?></div>

                </div>

                <div class="icon green">

                    <i class="fa-solid fa-users"></i>

                </div>

            </div>

            <div class="card p-5 flex justify-between items-center">

                <div>

                    <div class="label">ÚLTIMOS 7 DÍAS</div>

                    <div class="kpi"><?=number_format($semana)?></div>

                </div>

                <div class="icon cyan">

                    <i class="fa-solid fa-calendar-week"></i>

                </div>

            </div>

            <div class="card p-5 flex justify-between items-center">

                <div>

                    <div class="label">ÚLTIMOS 30 DÍAS</div>

                    <div class="kpi"><?=number_format($mes)?></div>

                </div>

                <div class="icon red">

                    <i class="fa-solid fa-calendar-days"></i>

                </div>

            </div>

        </div>

        <!-- ================= TOP PAGINAS ================= -->

        <div class="grid lg:grid-cols-2 gap-8 mt-8">

            <div class="card overflow-hidden">

                <div class="p-5 border-b border-slate-700">

                    <h2 class="text-white text-xl font-black">

                        🔥 Top Páginas

                    </h2>

                </div>

                <table>

                    <thead>

                        <tr>

                            <th>Página</th>

                            <th>Total</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach($topPaginas as $p): ?>

                        <tr>

                            <td class="text-blue-300">

                                <?=htmlspecialchars($p['pagina_visitada'])?>

                            </td>

                            <td>

                                <span class="bg-blue-900 text-blue-300 px-3 py-1 rounded-full">

                                    <?=number_format($p['total'])?>

                                </span>

                            </td>

                        </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <!-- ================= TOP IPS ================= -->

            <div class="card overflow-hidden">

                <div class="p-5 border-b border-slate-700">

                    <h2 class="text-white text-xl font-black">

                        🌎 Top IPs

                    </h2>

                </div>

                <table>

                    <thead>

                        <tr>

                            <th>IP</th>

                            <th>Visitas</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach($topIps as $ip): ?>

                        <tr>

                            <td class="font-mono text-sky-300">

                                <?=$ip['ip_usuario']?>

                            </td>

                            <td>

                                <span class="bg-green-900 text-green-300 px-3 py-1 rounded-full">

                                    <?=number_format($ip['total'])?>

                                </span>

                            </td>

                        </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

        <!-- ================= ULTIMAS VISITAS ================= -->

        <div class="card overflow-hidden mt-8">

            <div class="p-5 border-b border-slate-700 flex justify-between">

                <h2 class="text-white text-xl font-black">

                    ⚡ Últimas Visitas

                </h2>

                <div class="text-green-400 font-bold">

                    ● LIVE

                </div>

            </div>

            <div class="overflow-x-auto">

                <table>

                    <thead>

                        <tr>

                            <th>IP</th>

                            <th>Fecha</th>

                            <th>Página</th>

                            <th>Navegador</th>

                            <th>Dispositivo</th>

                            <th>País</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach($ultimas as $v): ?>

                        <tr>

                            <td class="font-mono text-cyan-300">

                                <?=$v['ip_usuario']?>

                            </td>

                            <td>

                                <?=$v['fecha_visita']?>

                            </td>

                            <td class="text-blue-300">

                                <?=$v['pagina_visitada']?>

                            </td>

                            <td>

                                <?=$v['navegador']?>

                            </td>

                            <td>

                                <?=$v['dispositivo']?>

                            </td>

                            <td>

                                <?=$v['pais']?>

                            </td>

                        </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

        <script>
        setInterval(function() {

            location.reload();

        }, 30000);
        </script>

    </div>

</body>

</html>