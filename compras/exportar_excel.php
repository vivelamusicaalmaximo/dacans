<?php
session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

$dbFile = '../catalogo_equipos.sqlite';

try {

    $pdo = new PDO("sqlite:" . $dbFile);

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {

    die("Error de conexion: " . $e->getMessage());
}

/* =========================================================
   CONSULTAR DATOS
========================================================= */

$stmt = $pdo->query("
    SELECT *
    FROM compras_articulos
    ORDER BY id DESC
");

$articulos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================================================
   HEADERS EXCEL
========================================================= */

$filename = "compras_articulos_" . date("Y-m-d") . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF"; // UTF-8

?>

<table border="1">

    <tr style="background:#0f172a;color:white;font-weight:bold;">

        <th>ID</th>
        <th>Artículo</th>
        <th>Cantidad</th>
        <th>USD</th>
        <th>DOP</th>
        <th>Impuestos</th>
        <th>Envío</th>
        <th>Costo Unitario</th>
        <th>Porcentaje</th>
        <th>Precio Sugerido</th>
        <th>Ganancia Item</th>
        <th>Ganancia Lote</th>
        <th>Status</th>
        <th>Rastreo</th>
        <th>Courier</th>

    </tr>

<?php foreach ($articulos as $articulo): ?>

<?php

$cantidad = (int)$articulo['cantidad_articulos'];

$costoDOP = (float)$articulo['costo_dop'];

$impuestos = (float)$articulo['costo_impuestos'];

$envio = (float)$articulo['costo_envio'];

$costoUSD = (float)$articulo['costo_usd'];

$totalLote =
    $costoDOP +
    $impuestos +
    $envio;

$costoUnitario =
    $cantidad > 0
    ? ($totalLote / $cantidad)
    : 0;

/* PORCENTAJE */

$porcentaje = 80;

if ($cantidad == 1 && $costoUSD < 200) {

    $porcentaje = 45;

} elseif (
    $cantidad >= 1 &&
    $cantidad <= 5 &&
    $costoUSD < 300
) {

    $porcentaje = 60;
}

/* PRECIO SUGERIDO */

$precioSugerido =
    $costoUnitario +
    ($costoUnitario * $porcentaje / 100);

/* GANANCIAS */

$gananciaItem =
    $precioSugerido - $costoUnitario;

$gananciaLote =
    $gananciaItem * $cantidad;

?>

<tr>

    <td>
        <?= (int)$articulo['id'] ?>
    </td>

    <td>
        <?= htmlspecialchars($articulo['nombre_articulo']) ?>
    </td>

    <td>
        <?= $cantidad ?>
    </td>

    <td>
        <?= number_format($costoUSD, 2) ?>
    </td>

    <td>
        <?= number_format($costoDOP, 2) ?>
    </td>

    <td>
        <?= number_format($impuestos, 2) ?>
    </td>

    <td>
        <?= number_format($envio, 2) ?>
    </td>

    <td>
        <?= number_format($costoUnitario, 2) ?>
    </td>

    <td>
        <?= number_format($porcentaje, 2) ?>%
    </td>

    <td>
        <?= number_format($precioSugerido, 2) ?>
    </td>

    <td>
        <?= number_format($gananciaItem, 2) ?>
    </td>

    <td>
        <?= number_format($gananciaLote, 2) ?>
    </td>

    <td>
        <?= htmlspecialchars($articulo['status_comp']) ?>
    </td>

    <td>
        <?= htmlspecialchars($articulo['numero_rastreo_us']) ?>
    </td>

    <td>
        <?= htmlspecialchars($articulo['id_courier']) ?>
    </td>

</tr>

<?php endforeach; ?>

</table>