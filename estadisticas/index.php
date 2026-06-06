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
   ESTADISTICAS GENERALES
========================================================= */

$totalEquipos = $pdo->query("
    SELECT COUNT(*) 
    FROM productos_informatica
    WHERE estado != 'Vendida'
")->fetchColumn();

$valorTotalEquipos = $pdo->query("
    SELECT ISNULL(SUM(precio),0)
    FROM productos_informatica
    WHERE estado != 'Vendida'
")->fetchColumn();

$totalVendidas = $pdo->query("
    SELECT COUNT(*)
    FROM productos_informatica
    WHERE estado = 'Vendida'
")->fetchColumn();

$totalDisponibles = $pdo->query("
    SELECT COUNT(*)
    FROM productos_informatica
    WHERE estado = 'Lista'
")->fetchColumn();

$totalRevision = $pdo->query("
    SELECT COUNT(*)
    FROM productos_informatica
    WHERE estado = 'En revision'
")->fetchColumn();

$totalCamino = $pdo->query("
    SELECT COUNT(*)
    FROM productos_informatica
    WHERE estado = 'En camino'
")->fetchColumn();

$totalNoLista = $pdo->query("
    SELECT COUNT(*)
    FROM productos_informatica
    WHERE estado = 'NO Lista'
")->fetchColumn();

$valorInventario = $pdo->query("
    SELECT ISNULL(SUM(precio),0)
    FROM productos_informatica
    WHERE estado != 'Vendida'
")->fetchColumn();

$valorVendido = $pdo->query("
    SELECT ISNULL(SUM(precio),0)
    FROM productos_informatica
    WHERE estado = 'Vendida'
")->fetchColumn();

/* =========================================================
   VALORES POR ESTADO
========================================================= */

$valorDisponibles = $pdo->query("
    SELECT ISNULL(SUM(precio),0)
    FROM productos_informatica
    WHERE estado = 'Lista'
")->fetchColumn();

$valorVendidas = $pdo->query("
    SELECT ISNULL(SUM(precio),0)
    FROM productos_informatica
    WHERE estado = 'Vendida'
")->fetchColumn();

$valorRevision = $pdo->query("
    SELECT ISNULL(SUM(precio),0)
    FROM productos_informatica
    WHERE estado = 'En revision'
")->fetchColumn();

$valorCamino = $pdo->query("
    SELECT ISNULL(SUM(precio),0)
    FROM productos_informatica
    WHERE estado = 'En camino'
")->fetchColumn();

$valorNoLista = $pdo->query("
    SELECT ISNULL(SUM(precio),0)
    FROM productos_informatica
    WHERE estado = 'NO Lista'
")->fetchColumn();

/* =========================================================
   PERIODOS QUINCENALES
========================================================= */

$periodo = $_GET['periodo'] ?? '1';

$anioActual = date('Y');
$mesActual  = date('m');

switch ($periodo) {

    /* PRIMERA QUINCENA */
    case '1':

        $fechaInicio = "$anioActual-$mesActual-01";
        $fechaFin    = "$anioActual-$mesActual-15";

    break;

    /* SEGUNDA QUINCENA */
    case '2':

        $fechaInicio = "$anioActual-$mesActual-16";
        $fechaFin    = date('Y-m-d');

    break;

    default:

        $fechaInicio = "$anioActual-$mesActual-01";
        $fechaFin    = "$anioActual-$mesActual-15";
}

/* =========================================================
   TOTAL VENDIDO DEL PERIODO
========================================================= */

$stmtPeriodo = $pdo->prepare("
    SELECT ISNULL(SUM(precio),0)
    FROM productos_informatica
    WHERE estado = 'Vendida'
    AND vendida_at IS NOT NULL
    AND LTRIM(RTRIM(vendida_at)) != ''
    AND CONVERT(date, vendida_at)
        BETWEEN ? AND ?
");

$stmtPeriodo->execute([

    $fechaInicio,
    $fechaFin

]);

$totalPeriodo = (float)($stmtPeriodo->fetchColumn() ?? 0);

/* =========================================================
   BONOS USUARIOS
========================================================= */

$bonosUsuarios = $pdo->query("
    SELECT
        id,
        usuario,
        rol,
        porcent_ganancias
    FROM usuarios
    WHERE porcent_ganancias IS NOT NULL
    AND porcent_ganancias > 0
    ORDER BY usuario ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   MARCAS
========================================================= */

$marcas = $pdo->query("
    SELECT 
        equipo_marca,
        COUNT(*) total
    FROM productos_informatica
    GROUP BY equipo_marca
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   PROCESADORES
========================================================= */

$procesadores = $pdo->query("
    SELECT 
        proc_marca,
        COUNT(*) total
    FROM productos_informatica
    GROUP BY proc_marca
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   MEMORIAS
========================================================= */

$memorias = $pdo->query("
    SELECT 
        memoria,
        COUNT(*) total
    FROM productos_informatica
    GROUP BY memoria
    ORDER BY total DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   ULTIMOS EQUIPOS
========================================================= */

$ultimos = $pdo->query("
    SELECT TOP 5 *
    FROM productos_informatica
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ... Tu código anterior (donde se definen los valores de $e) ...
$e['imagenes_adicionales'] = $e['imagenes_adicionales'] ?? '';

$maxPrecio = (float)($pdo->query("SELECT ISNULL(MAX(precio), 50000) FROM productos_informatica")->fetchColumn());
if($maxPrecio <= 0) $maxPrecio = 50000;

/* ======================================================
   OBTENER LOS ÚLTIMOS 5 EQUIPOS VENDIDOS
====================================================== */
$ultimos_vendidos = [];
try {
    // Ordenamos por 'vendida_at' de forma descendente para tener los más recientes primero
    $stmtVendidos = $pdo->prepare("
        SELECT TOP 5 id_local, equipo_marca, equipo_modelo, precio, vendida_at 
        FROM productos_informatica 
        WHERE estado = 'Vendida' 
        ORDER BY vendida_at DESC
    ");
    // Nota: Si usas MySQL en vez de SQL Server, cambia "SELECT TOP 5 ..." por "SELECT ... LIMIT 5"
    
    $stmtVendidos->execute();
    $ultimos_vendidos = $stmtVendidos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $ex) {
    // Si hay un error en la consulta, guardamos el error para no romper la página por completo
    $error_vendidos = "No se pudieron cargar los últimos vendidos: " . $ex->getMessage();
}
?>



<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Estadísticas Inventario</title>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="shortcut icon" href="/img/favicon.ico">
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

body{
   background:
linear-gradient(
    to bottom,
    #f8fafc,
    #eef2ff
);
    font-family:Arial,sans-serif;
}

.card{
    background:white;
    border-radius:32px;
    padding:28px;
    border:1px solid #e2e8f0;
    box-shadow:
        0 10px 30px rgba(15,23,42,.04),
        0 2px 10px rgba(15,23,42,.03);

    transition:
        transform .25s ease,
        box-shadow .25s ease,
        border-color .25s ease;
}

.card:hover{
    transform:translateY(-4px);

    box-shadow:
        0 20px 40px rgba(15,23,42,.08),
        0 8px 20px rgba(15,23,42,.05);

    border-color:#cbd5e1;
}

</style>

</head>

<body class="p-4 md:p-8">

<div class="max-w-[1700px] mx-auto">

    <!-- HEADER -->

    <div class="flex flex-col lg:flex-row
    justify-between items-center gap-4 mb-8">

        <div class="flex items-center gap-5">

          <img src="../img/logo.webp"
class="h-16 drop-shadow-lg">

            <div>

                <h1 class="text-4xl font-black text-slate-900">
                    Estadísticas Generales
                </h1>

                <p class="text-slate-500 mt-1">
                    Resumen completo del inventario
                </p>

            </div>

        </div>

        <a href="../mantenimiento"
        class="bg-blue-700 hover:bg-blue-800
        text-white px-6 py-4 rounded-2xl font-black">

            <i class="fa-solid fa-arrow-left mr-2"></i>
            Volver Inventario

        </a>

    </div>

   <!-- KPIs -->

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6 gap-5 mb-8">

    <!-- TOTAL -->
<a href="equipos_estado.php?estado=TODOS" class="card block w-full min-w-0 hover:scale-[1.02] transition">

     <div class="flex items-center justify-between gap-4 min-w-0">

     <div class="min-w-0 flex-1">

                <p class="text-slate-400 text-sm uppercase font-bold">
                    Total Equipos
                </p>

               <h2 class="text-5xl font-black text-slate-900 mt-3">
    <?= number_format($totalEquipos) ?>
</h2>

<p class="mt-3 text-blue-700 font-black text-sm">
    RD$ <?= number_format((float)$valorTotalEquipos, 0) ?>
</p>
            </div>

            <div class="w-20 h-20 rounded-3xl
            bg-blue-100 text-blue-700
            flex items-center justify-center">

                <i class="fa-solid fa-laptop text-4xl"></i>

            </div>

        </div>

    </a>

    <!-- DISPONIBLES -->
    <a href="equipos_estado.php?estado=Lista"
    class="card block hover:scale-[1.02] transition">

        <div class="flex items-center justify-between">

            <div>

                <p class="text-slate-400 text-sm uppercase font-bold">
                    Disponibles
                </p>

                <h2 class="text-5xl font-black text-green-600 mt-3">
    <?= number_format($totalDisponibles) ?>
</h2>

<p class="mt-3 text-green-700 font-black text-sm">
    RD$ <?= number_format((float)$valorDisponibles, 0) ?>
</p>






            </div>

            <div class="w-20 h-20 rounded-3xl
            bg-green-100 text-green-700
            flex items-center justify-center">

                <i class="fa-solid fa-circle-check text-4xl"></i>

            </div>

        </div>

    </a>

    <!-- VENDIDAS -->
    <a href="equipos_estado.php?estado=Vendida"
    class="card block hover:scale-[1.02] transition">

        <div class="flex items-center justify-between">

            <div>

                <p class="text-slate-400 text-sm uppercase font-bold">
                    Vendidas
                </p>

               <p class="mt-3 text-red-700 font-black text-sm">
    RD$ <?= number_format((float)$valorVendidas, 0) ?>
</p>

            </div>

            <div class="w-20 h-20 rounded-3xl
            bg-red-100 text-red-700
            flex items-center justify-center">

                <i class="fa-solid fa-cart-shopping text-4xl"></i>

            </div>

        </div>

    </a>

    <!-- EN CAMINO -->
    <a href="equipos_estado.php?estado=En%20camino"
    class="card block hover:scale-[1.02] transition">

        <div class="flex items-center justify-between">

            <div>

                <p class="text-slate-400 text-sm uppercase font-bold">
                    En Camino
                </p>

              <p class="mt-3 text-cyan-700 font-black text-sm">
    RD$ <?= number_format((float)$valorCamino, 0) ?>
</p>

            </div>

            <div class="w-20 h-20 rounded-3xl
            bg-cyan-100 text-cyan-700
            flex items-center justify-center">

                <i class="fa-solid fa-truck text-4xl"></i>

            </div>

        </div>

    </a>

    <!-- NO LISTA -->
    <a href="equipos_estado.php?estado=NO%20Lista"
    class="card block hover:scale-[1.02] transition">

        <div class="flex items-center justify-between">

            <div>

                <p class="text-slate-400 text-sm uppercase font-bold">
                    NO Lista
                </p>

              <p class="mt-3 text-yellow-700 font-black text-sm">
    RD$ <?= number_format((float)$valorNoLista, 0) ?>
</p>

            </div>

            <div class="w-20 h-20 rounded-3xl
            bg-yellow-100 text-yellow-700
            flex items-center justify-center">

                <i class="fa-solid fa-triangle-exclamation text-4xl"></i>

            </div>

        </div>

    </a>

    <!-- EN REVISION -->
    <a href="equipos_estado.php?estado=En%20revision"
    class="card block hover:scale-[1.02] transition">

        <div class="flex items-center justify-between">

            <div>

                <p class="text-slate-400 text-sm uppercase font-bold">
                    En Revisión
                </p>

             <p class="mt-3 text-purple-700 font-black text-sm">
    RD$ <?= number_format((float)$valorRevision, 0) ?>
</p>

            </div>

            <div class="w-20 h-20 rounded-3xl
            bg-purple-100 text-purple-700
            flex items-center justify-center">

                <i class="fa-solid fa-screwdriver-wrench text-4xl"></i>

            </div>

        </div>

    </a>

</div>
  <!-- DINERO -->

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-8">

    <!-- INVENTARIO -->

    <div class="card">

        <p class="text-slate-400 uppercase text-sm font-bold">
            Valor Inventario Disponible
        </p>

        <h2 class="text-5xl font-black text-blue-700 mt-4">

            RD$
            <?= number_format((float)$valorInventario, 0) ?>

        </h2>

    </div>

    <!-- TOTAL VENDIDO -->

    <div class="card">

        <p class="text-slate-400 uppercase text-sm font-bold">
            Valor Total Vendido
        </p>

        <h2 class="text-5xl font-black text-green-700 mt-4">

            RD$
            <?= number_format((float)$valorVendido, 0) ?>

        </h2>

    </div>

    <!-- TOTAL PERIODO -->

    <div class="card">

        <p class="text-slate-400 uppercase text-sm font-bold">

            Total <?= $periodo == '1'
                ? 'Primera Quincena'
                : 'Segunda Quincena' ?>

        </p>

        <h2 class="text-5xl font-black text-purple-700 mt-4">

            RD$
            <?= number_format((float)$totalPeriodo, 0) ?>

        </h2>

        <p class="text-slate-500 text-sm mt-3">

            Desde
            <span class="font-bold">
                <?= $fechaInicio ?>
            </span>

            hasta
            <span class="font-bold">
                <?= $fechaFin ?>
            </span>

        </p>

    </div>

</div>


<div class="flex flex-wrap gap-4 mb-8">

    <a href="?periodo=1"
    class="group px-7 py-5 rounded-3xl border transition-all duration-300

    <?= $periodo == '1'
    ? 'bg-blue-700 border-blue-700 text-white shadow-xl'
    : 'bg-white border-slate-200 text-slate-700 hover:border-blue-300 hover:shadow-lg' ?>">

        <div class="font-black text-base">
            Primera Quincena
        </div>

        <div class="text-xs mt-1 opacity-70">
            Día 1 al 15
        </div>

    </a>

    <a href="?periodo=2"
    class="group px-7 py-5 rounded-3xl border transition-all duration-300

    <?= $periodo == '2'
    ? 'bg-blue-700 border-blue-700 text-white shadow-xl'
    : 'bg-white border-slate-200 text-slate-700 hover:border-blue-300 hover:shadow-lg' ?>">

        <div class="font-black text-base">
            Segunda Quincena
        </div>

        <div class="text-xs mt-1 opacity-70">
            Día 16 al Final
        </div>

    </a>

</div>
<div class="card mt-8">

    <div class="flex items-center justify-between mb-6">

        <div>

          <h3 class="text-2xl font-black text-slate-900">
    Bonos Quincenales
</h3>

            <p class="text-slate-500 text-sm mt-1">

                Total vendido en el periodo:

                <span class="font-black text-green-700">

                    RD$
                  <?= number_format($totalPeriodo, 0) ?>

                </span>

            </p>

        </div>

    </div>

    <div class="overflow-x-auto">

      <table class="w-full overflow-hidden rounded-3xl">
            <thead>

                <tr class="border-b border-slate-100 hover:bg-blue-50/40 transition">

                    <th class="text-left p-4">
                        Usuario
                    </th>

                    <th class="text-left p-4">
                        Rol
                    </th>

                    <th class="text-left p-4">
                        %
                    </th>
<th class="text-left p-4">
    Bono Quincenal
</th>

                    <th class="text-left p-4">
                        Bono Mensual Aproximado
                    </th>

                </tr>

            </thead>

            <tbody>

                <?php foreach($bonosUsuarios as $b): ?>

                    <?php

                    $porcentaje = (float)($b['porcent_ganancias'] ?? 0);

                  $bonoQuincenal = $totalPeriodo * ($porcentaje / 100);

$bonoMensual = $bonoQuincenal * 2;
                    ?>

                  <tr class="border-b border-slate-100 hover:bg-blue-50/40 transition">

                        <td class="p-4 font-black text-slate-800">

                            <?= htmlspecialchars($b['usuario']) ?>

                        </td>

                        <td class="p-4">

                            <?= htmlspecialchars($b['rol']) ?>

                        </td>

                       <td class="p-4">

    <span class="bg-blue-100
    text-blue-700
    px-4 py-2
    rounded-full
    text-sm
    font-black">

        <?= number_format($porcentaje, 2) ?>%

    </span>

</td>

                        <td class="p-4 font-black text-green-700">

                            RD$
                          <?= number_format($bonoQuincenal, 2) ?>

                        </td>

                        <td class="p-4 font-black text-cyan-700">

                            RD$
                            <?= number_format($bonoMensual, 2) ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>

    </div>

    <!-- TABLAS -->

    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

        <!-- MARCAS -->
        <div class="card">

            <h3 class="text-2xl font-black text-slate-900 mb-6">
                Marcas
            </h3>

            <div class="space-y-4">

                <?php foreach($marcas as $m): ?>

                    <div class="flex justify-between items-center">

                        <span class="font-bold text-slate-700">
                            <?= $m['equipo_marca'] ?>
                        </span>

                        <span class="bg-blue-100 text-blue-700
                        px-3 py-1 rounded-full text-sm font-black">

                            <?= $m['total'] ?>

                        </span>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

        <!-- CPU -->
        <div class="card">

            <h3 class="text-2xl font-black text-slate-900 mb-6">
                Procesadores
            </h3>

            <div class="space-y-4">

                <?php foreach($procesadores as $p): ?>

                    <div class="flex justify-between items-center">

                        <span class="font-bold text-slate-700">
                            <?= $p['proc_marca'] ?>
                        </span>

                        <span class="bg-cyan-100 text-cyan-700
                        px-3 py-1 rounded-full text-sm font-black">

                            <?= $p['total'] ?>

                        </span>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

        <!-- RAM -->
        <div class="card">

            <h3 class="text-2xl font-black text-slate-900 mb-6">
                Memorias RAM
            </h3>

            <div class="space-y-4">

                <?php foreach($memorias as $m): ?>

                    <div class="flex justify-between items-center">

                        <span class="font-bold text-slate-700">
                            <?= $m['memoria'] ?>
                        </span>

                        <span class="bg-purple-100 text-purple-700
                        px-3 py-1 rounded-full text-sm font-black">

                            <?= $m['total'] ?>

                        </span>

                    </div>

                <?php endforeach; ?>

            </div>

        </div>

    </div>

    <!-- ULTIMOS -->

    <div class="card mt-8">

        <div class="flex items-center justify-between mb-6">

            <h3 class="text-2xl font-black text-slate-900">
                Últimos Equipos Registrados
            </h3>

        </div>

        <div class="overflow-x-auto">

            <table class="w-full">

                <thead>

                    <tr class="border-b border-slate-200">

                        <th class="text-left p-3">ID</th>
                        <th class="text-left p-3">Marca</th>
                        <th class="text-left p-3">Modelo</th>
                        <th class="text-left p-3">RAM</th>
                        <th class="text-left p-3">Disco</th>
                        <th class="text-left p-3">Estado</th>
                        <th class="text-left p-3">Precio</th>

                    </tr>

                </thead>

                <tbody>

                    <?php foreach($ultimos as $u): ?>

                        <tr class="border-b border-slate-100">

                            <td class="p-3 font-black text-blue-700">
                                <?= $u['id_local'] ?>
                            </td>

                            <td class="p-3">
                                <?= $u['equipo_marca'] ?>
                            </td>

                            <td class="p-3">
                                <?= $u['equipo_modelo'] ?>
                            </td>

                            <td class="p-3">
                                <?= $u['memoria'] ?>
                            </td>

                            <td class="p-3">
                                <?= $u['disco'] ?>
                            </td>

                            <td class="p-3">
                                <?= $u['estado'] ?>
                            </td>

                            <td class="p-3 font-black">

                                RD$
                                <?= number_format((float)$u['precio'], 0) ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

    <div class="mt-12 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
            📊 Últimos 5 Equipos Vendidos
        </h3>
        <span class="text-xs bg-emerald-50 text-emerald-700 px-2.5 py-1 rounded-full font-medium">
            Historial reciente
        </span>
    </div>

    <?php if (isset($error_vendidos)): ?>
        <div class="p-3 bg-red-50 text-red-700 text-sm rounded-xl border border-red-100">
            <?= htmlspecialchars($error_vendidos) ?>
        </div>
    <?php elseif (empty($ultimos_vendidos)): ?>
        <p class="text-sm text-slate-500 text-center py-6 bg-slate-50 rounded-xl border border-dashed border-slate-200">
            Aún no hay equipos registrados como "Vendida".
        </p>
    <?php else: ?>
        <div class="overflow-x-auto rounded-xl border border-slate-100">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-100">
                        <th class="p-3">ID</th>
                        <th class="p-3">Marca</th>
                        <th class="p-3">Modelo</th>
                        <th class="p-3">Precio</th>
                        <th class="p-3">Fecha de Venta</th>
                        <th class="p-3 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    <?php foreach ($ultimos_vendidos as $vendido): ?>
                        <tr class="hover:bg-slate-50/50 transition-colors">
                            <td class="p-3 font-medium text-slate-500">
                                #<?= htmlspecialchars($vendido['id_local']) ?>
                            </td>
                            <td class="p-3 font-semibold text-slate-900">
                                <?= htmlspecialchars($vendido['equipo_marca'] ?? 'N/A') ?>
                            </td>
                            <td class="p-3">
                                <?= htmlspecialchars($vendido['equipo_modelo'] ?? 'N/A') ?>
                            </td>
                            <td class="p-3 text-emerald-600 font-semibold">
                                $<?= number_format($vendido['precio'], 2) ?>
                            </td>
                            <td class="p-3 text-slate-500 text-xs">
                                <?php 
                                if (!empty($vendido['vendida_at'])) {
                                    echo date('d/m/Y h:i A', strtotime($vendido['vendida_at']));
                                } else {
                                    echo 'No especificada';
                                }
                                ?>
                            </td>
                            <td class="p-3 text-center">
                                <a href="editar.php?id=<?= $vendido['id_local'] ?>" 
                                   class="inline-flex items-center justify-center text-xs font-medium bg-slate-100 hover:bg-blue-50 hover:text-blue-600 text-slate-600 px-3 py-1.5 rounded-lg transition-all">
                                    Ver / Editar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

</div>

</body>
</html>