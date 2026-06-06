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

    die("Error DB: " . $e->getMessage());
}

$estado = $_GET['estado'] ?? '';

if (empty($estado)) {

    die("Estado no especificado");
}

/* CONSULTA */

if ($estado === 'TODOS') {

    $stmt = $pdo->prepare("
        SELECT *
        FROM productos_informatica
        ORDER BY created_at DESC
    ");

    $stmt->execute();

} else {

    $stmt = $pdo->prepare("
        SELECT *
        FROM productos_informatica
        WHERE estado = ?
        ORDER BY created_at DESC
    ");

    $stmt->execute([$estado]);
}

$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* HEADERS EXCEL */

$filename = "inventario_" . str_replace(' ', '_', $estado) . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF";

?>

<table border="1">

    <tr style="background:#1e3a8a;color:white;font-weight:bold;">

        <th>ID</th>
        <th>Serie</th>
        <th>Marca</th>
        <th>Modelo</th>
        <th>CPU Marca</th>
        <th>CPU Familia</th>
        <th>CPU Modelo</th>
        <th>Generación</th>
        <th>GPU</th>
        <th>RAM</th>
        <th>Disco</th>
        <th>Pantalla</th>
        <th>Resolución</th>
        <th>Touch</th>
        <th>Precio</th>
        <th>Estado</th>
        <th>Fecha Registro</th>
        <th>Fecha Venta</th>
        <th> Comentario</th>

    </tr>

    <?php foreach($equipos as $e): ?>

<tr>

    <td><?= htmlspecialchars($e['id_local'] ?? '') ?></td>

    <td><?= htmlspecialchars($e['serie'] ?? '') ?></td>

    <td><?= htmlspecialchars($e['equipo_marca'] ?? '') ?></td>

    <td><?= htmlspecialchars($e['equipo_modelo'] ?? '') ?></td>

    <td><?= htmlspecialchars($e['proc_marca'] ?? '') ?></td>

    <td><?= htmlspecialchars($e['proc_familia'] ?? '') ?></td>

    <td><?= htmlspecialchars($e['proc_modelo'] ?? '') ?></td>

    <td><?= htmlspecialchars($e['proc_generacion'] ?? '') ?></td>

    <td><?= htmlspecialchars($e['graficos'] ?? '') ?></td>

    <td><?= htmlspecialchars($e['memoria'] ?? '') ?></td>

    <td><?= htmlspecialchars($e['disco'] ?? '') ?></td>

    <td><?= htmlspecialchars($e['pantalla'] ?? '') ?></td>

    <td><?= htmlspecialchars($e['p_resolucion'] ?? '') ?></td>

    <td>
        <?= ($e['touch'] ?? 0) == 1 ? 'SI' : 'NO' ?>
    </td>

    <td>
        <?= htmlspecialchars($e['precio'] ?? '') ?>
    </td>

    <td>
        <?= htmlspecialchars($e['estado'] ?? '') ?>
    </td>

    <td>
        <?= htmlspecialchars($e['created_at'] ?? '') ?>
    </td>

    <td>
        <?= htmlspecialchars($e['vendida_at'] ?? '') ?>
    </td>

    <td>
        <?= htmlspecialchars($e['comenta'] ?? '') ?>
    </td>

</tr>

<?php endforeach; ?>

</table>