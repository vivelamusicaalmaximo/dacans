<?php

session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

require 'config/conexion.php';

try {

    /*==========================================
    KPIs
    ==========================================*/

    $sql = "

    SELECT

    COUNT(*) total,

    SUM(CASE WHEN CAST(fecha_visita AS DATE)=CAST(GETDATE() AS DATE)
    THEN 1 ELSE 0 END) hoy,

    SUM(CASE WHEN CAST(fecha_visita AS DATE)=CAST(DATEADD(DAY,-1,GETDATE()) AS DATE)
    THEN 1 ELSE 0 END) ayer,

    SUM(CASE WHEN fecha_visita>=DATEADD(DAY,-7,GETDATE())
    THEN 1 ELSE 0 END) semana,

    SUM(CASE WHEN fecha_visita>=DATEADD(DAY,-30,GETDATE())
    THEN 1 ELSE 0 END) mes

    FROM registro_visitas

    ";

    $kpi = $pdo->query($sql)->fetch(PDO::FETCH_ASSOC);

    $total = $kpi['total'];
    $hoy = $kpi['hoy'];
    $ayer = $kpi['ayer'];
    $semana = $kpi['semana'];
    $mes = $kpi['mes'];

    $usuarios = $pdo->query("
    SELECT COUNT(DISTINCT ip_usuario)
    FROM registro_visitas
    ")->fetchColumn();

    $online = $pdo->query("
    SELECT COUNT(DISTINCT ip_usuario)
    FROM registro_visitas
    WHERE fecha_visita>=DATEADD(MINUTE,-5,GETDATE())
    ")->fetchColumn();

    $crecimiento = 0;

    if ($ayer > 0) {

        $crecimiento = round((($hoy - $ayer) / $ayer) * 100, 1);

    }

    /*==========================================
    DISPOSITIVOS
    ==========================================*/

    $desktop = $pdo->query("
    SELECT COUNT(*)
    FROM registro_visitas
    WHERE dispositivo='Desktop'
    ")->fetchColumn();

    $mobile = $pdo->query("
    SELECT COUNT(*)
    FROM registro_visitas
    WHERE dispositivo='Mobile'
    ")->fetchColumn();

    $tablet = $pdo->query("
    SELECT COUNT(*)
    FROM registro_visitas
    WHERE dispositivo='Tablet'
    ")->fetchColumn();

    /*==========================================
    GRAFICA
    ==========================================*/

    $grafica = $pdo->query("

    SELECT

    CONVERT(VARCHAR(10),fecha_visita,120) fecha,

    COUNT(*) total

    FROM registro_visitas

    WHERE fecha_visita>=DATEADD(DAY,-30,GETDATE())

    GROUP BY CONVERT(VARCHAR(10),fecha_visita,120)

    ORDER BY fecha

    ")->fetchAll(PDO::FETCH_ASSOC);

    $labels = array_column($grafica,'fecha');

    $data = array_column($grafica,'total');

    /*==========================================
    TOP PAGINAS
    ==========================================*/

    $topPaginas = $pdo->query("

    SELECT TOP 10

    pagina_visitada,

    COUNT(*) total

    FROM registro_visitas

    GROUP BY pagina_visitada

    ORDER BY total DESC

    ")->fetchAll(PDO::FETCH_ASSOC);

    /*==========================================
    TOP IPS
    ==========================================*/

    $topIps = $pdo->query("

    SELECT TOP 10

    ip_usuario,

    COUNT(*) total

    FROM registro_visitas

    GROUP BY ip_usuario

    ORDER BY total DESC

    ")->fetchAll(PDO::FETCH_ASSOC);

    /*==========================================
    NAVEGADORES
    ==========================================*/

    $navegadores = $pdo->query("

    SELECT

    navegador,

    COUNT(*) total

    FROM registro_visitas

    GROUP BY navegador

    ORDER BY total DESC

    ")->fetchAll(PDO::FETCH_ASSOC);

    /*==========================================
    SISTEMAS OPERATIVOS
    ==========================================*/

    $so = $pdo->query("

    SELECT

    sistema_operativo,

    COUNT(*) total

    FROM registro_visitas

    GROUP BY sistema_operativo

    ORDER BY total DESC

    ")->fetchAll(PDO::FETCH_ASSOC);

    /*==========================================
    PAISES
    ==========================================*/

    $paises = $pdo->query("

    SELECT TOP 10

    pais,

    COUNT(*) total

    FROM registro_visitas

    GROUP BY pais

    ORDER BY total DESC

    ")->fetchAll(PDO::FETCH_ASSOC);

    /*==========================================
    ULTIMAS VISITAS
    ==========================================*/

    $ultimas = $pdo->query("

    SELECT TOP 50

    ip_usuario,

    fecha_visita,

    pagina_visitada,

    navegador,

    dispositivo,

    sistema_operativo,

    pais,

    ciudad,

    referencia

    FROM registro_visitas

    ORDER BY id DESC

    ")->fetchAll(PDO::FETCH_ASSOC);

} catch(Exception $e){

    die($e->getMessage());

}

?>

<!-- ===================================================== -->
<!-- KPI PREMIUM -->
<!-- ===================================================== -->

<div class="grid xl:grid-cols-4 lg:grid-cols-2 gap-6 mt-8">

    <div class="card p-6">

        <div class="flex justify-between items-center">

            <div>

                <div class="label">VISITAS AYER</div>

                <div class="kpi"><?=number_format($ayer)?></div>

                <div class="text-red-400 text-sm mt-2">
                    <i class="fa-solid fa-calendar-day"></i>
                    Día anterior
                </div>

            </div>

            <div class="icon purple">

                <i class="fa-solid fa-clock-rotate-left"></i>

            </div>

        </div>

    </div>

    <div class="card p-6">

        <div class="flex justify-between items-center">

            <div>

                <div class="label">USUARIOS ÚNICOS</div>

                <div class="kpi"><?=number_format($usuarios)?></div>

                <div class="text-green-400 text-sm mt-2">

                    <i class="fa-solid fa-users"></i>

                    Clientes distintos

                </div>

            </div>

            <div class="icon green">

                <i class="fa-solid fa-users"></i>

            </div>

        </div>

    </div>

    <div class="card p-6">

        <div class="flex justify-between items-center">

            <div>

                <div class="label">ÚLTIMOS 7 DÍAS</div>

                <div class="kpi"><?=number_format($semana)?></div>

                <div class="text-cyan-400 text-sm mt-2">

                    <i class="fa-solid fa-calendar-week"></i>

                    Última semana

                </div>

            </div>

            <div class="icon cyan">

                <i class="fa-solid fa-chart-column"></i>

            </div>

        </div>

    </div>

    <div class="card p-6">

        <div class="flex justify-between items-center">

            <div>

                <div class="label">ÚLTIMOS 30 DÍAS</div>

                <div class="kpi"><?=number_format($mes)?></div>

                <div class="text-orange-400 text-sm mt-2">

                    <i class="fa-solid fa-calendar-days"></i>

                    Último mes

                </div>

            </div>

            <div class="icon orange">

                <i class="fa-solid fa-chart-line"></i>

            </div>

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- DISPOSITIVOS -->
<!-- ===================================================== -->

<div class="grid xl:grid-cols-3 gap-6 mt-8">

    <div class="card p-6">

        <div class="flex items-center justify-between">

            <div>

                <div class="label">

                    DESKTOP

                </div>

                <div class="kpi">

                    <?=number_format($desktop)?>

                </div>

            </div>

            <div class="icon blue">

                <i class="fa-solid fa-desktop"></i>

            </div>

        </div>

        <div class="w-full bg-slate-800 rounded-full h-2 mt-5">

            <div class="bg-blue-500 h-2 rounded-full" style="width:<?=($total>0)?round(($desktop/$total)*100):0?>%">

            </div>

        </div>

    </div>

    <div class="card p-6">

        <div class="flex items-center justify-between">

            <div>

                <div class="label">

                    MOBILE

                </div>

                <div class="kpi">

                    <?=number_format($mobile)?>

                </div>

            </div>

            <div class="icon green">

                <i class="fa-solid fa-mobile-screen-button"></i>

            </div>

        </div>

        <div class="w-full bg-slate-800 rounded-full h-2 mt-5">

            <div class="bg-green-500 h-2 rounded-full" style="width:<?=($total>0)?round(($mobile/$total)*100):0?>%">

            </div>

        </div>

    </div>

    <div class="card p-6">

        <div class="flex items-center justify-between">

            <div>

                <div class="label">

                    TABLET

                </div>

                <div class="kpi">

                    <?=number_format($tablet)?>

                </div>

            </div>

            <div class="icon purple">

                <i class="fa-solid fa-tablet-screen-button"></i>

            </div>

        </div>

        <div class="w-full bg-slate-800 rounded-full h-2 mt-5">

            <div class="bg-purple-500 h-2 rounded-full" style="width:<?=($total>0)?round(($tablet/$total)*100):0?>%">

            </div>

        </div>

    </div>

</div>

<!-- ===================================================== -->
<!-- GRAFICOS -->
<!-- ===================================================== -->

<div class="grid xl:grid-cols-2 gap-8 mt-8">

    <div class="card p-6">

        <h2 class="text-white font-black text-xl mb-5">

            <i class="fa-solid fa-chart-pie text-blue-400 mr-2"></i>

            Dispositivos

        </h2>

        <canvas id="deviceChart"></canvas>

    </div>

    <div class="card p-6">

        <h2 class="text-white font-black text-xl mb-5">

            <i class="fa-solid fa-globe text-green-400 mr-2"></i>

            Navegadores

        </h2>

        <canvas id="browserChart"></canvas>
<div class="grid xl:grid-cols-2 gap-8 mt-8">

    <div class="card p-6">

        <h2 class="text-xl font-black text-white mb-5">
            <i class="fa-solid fa-file-lines text-cyan-400 mr-2"></i>
            Top páginas
        </h2>

        <?php foreach($topPaginas as $row): ?>

        <div class="mb-4">

            <div class="flex justify-between text-sm mb-1">

                <span class="text-slate-300 truncate">
                    <?=htmlspecialchars($row['pagina_visitada'])?>
                </span>

                <span class="text-cyan-400 font-bold">
                    <?=$row['total']?>
                </span>

            </div>

            <div class="bg-slate-800 rounded-full h-2">

                <div
                    class="bg-cyan-500 h-2 rounded-full"
                    style="width:<?=($total>0)?round(($row['total']/$total)*100):0?>%">
                </div>

            </div>

        </div>

        <?php endforeach; ?>

    </div>

    <div class="card p-6">

        <h2 class="text-xl font-black text-white mb-5">
            <i class="fa-solid fa-network-wired text-orange-400 mr-2"></i>
            Top IPs
        </h2>

        <div class="space-y-3">

            <?php foreach($topIps as $row): ?>

            <div class="flex justify-between border-b border-slate-800 pb-2">

                <span class="text-slate-300">
                    <?=$row['ip_usuario']?>
                </span>

                <span class="text-orange-400 font-bold">
                    <?=$row['total']?>
                </span>

            </div>

            <?php endforeach; ?>

        </div>

    </div>

</div>

<div class="grid xl:grid-cols-2 gap-8 mt-8">

    <div class="card p-6">

        <h2 class="text-xl font-black text-white mb-5">
            <i class="fa-brands fa-windows text-blue-400 mr-2"></i>
            Sistemas operativos
        </h2>

        <canvas id="osChart"></canvas>

    </div>

    <div class="card p-6">

        <h2 class="text-xl font-black text-white mb-5">
            <i class="fa-solid fa-earth-americas text-green-400 mr-2"></i>
            Países
        </h2>

        <canvas id="countryChart"></canvas>

    </div>

</div>



<div class="card p-6 mt-8">

    <div class="flex justify-between items-center mb-5">

        <h2 class="text-xl font-black text-white">

            <i class="fa-solid fa-bolt text-yellow-400 mr-2"></i>

            Últimas visitas en tiempo real

        </h2>

        <span class="text-green-400 text-sm">

            ● Actualizando cada 10 segundos

        </span>

    </div>

    <div class="overflow-auto">

        <table class="w-full text-sm">

            <thead>

                <tr class="border-b border-slate-800">

                    <th class="text-left p-3">Fecha</th>
                    <th class="text-left p-3">IP</th>
                    <th class="text-left p-3">Página</th>
                    <th class="text-left p-3">País</th>
                    <th class="text-left p-3">SO</th>
                    <th class="text-left p-3">Dispositivo</th>

                </tr>

            </thead>

            <tbody id="tablaTiempoReal">

                <?php foreach($ultimas as $v): ?>

                <tr class="border-b border-slate-900">

                    <td class="p-3">
                        <?=$v['fecha_visita']?>
                    </td>

                    <td class="p-3">
                        <?=$v['ip_usuario']?>
                    </td>

                    <td class="p-3">
                        <?=$v['pagina_visitada']?>
                    </td>

                    <td class="p-3">
                        <?=$v['pais']?>
                    </td>

                    <td class="p-3">
                        <?=$v['sistema_operativo']?>
                    </td>

                    <td class="p-3">
                        <?=$v['dispositivo']?>
                    </td>

                </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>

    </div>



</div>

<script>

setInterval(()=>{

    fetch('ajax_visitas.php')

    .then(r=>r.text())

    .then(html=>{

        document.getElementById('tablaTiempoReal').innerHTML = html;

    });

},10000);

</script>