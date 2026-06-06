<?php

session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

/* =========================
   CONEXION SQL SERVER
========================= */

require '../config/conexion.php';

try {

    $query = $pdo->query("
        SELECT
            id_local,
            serie,
            equipo_marca,
            equipo_modelo,
            proc_marca,
            proc_familia,
            proc_modelo,
            proc_generacion,
            graficos,
            g_expandible,
            memoria,
            disco,
            pantalla,
            p_resolucion,
            touch,
            precio,
            comenta,
            estado,
            created_at,
            vendida_at
        FROM productos_informatica
        ORDER BY equipo_marca ASC
    ");

    $equipos = $query->fetchAll(PDO::FETCH_ASSOC);

    header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
    header("Content-Disposition: attachment; filename=inventario_dacans.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "\xEF\xBB\xBF";

    ?>

    <table border="1">

        <tr style="background:#1e3a8a;color:white;font-weight:bold;">

            <th>ID Local</th>
            <th>Serie</th>
            <th>Marca</th>
            <th>Modelo</th>
            <th>CPU Marca</th>
            <th>CPU Familia</th>
            <th>CPU Modelo</th>
            <th>Generación</th>
            <th>GPU</th>
            <th>Tipo GPU</th>
            <th>RAM</th>
            <th>Disco</th>
            <th>Pantalla</th>
            <th>Resolución</th>
            <th>Touch</th>
            <th>Precio</th>
            <th>Comentario</th>
            <th>Estado</th>
            <th>Fecha Registro</th>
            <th>Fecha Venta</th>

        </tr>

        <?php foreach ($equipos as $e): ?>

            <tr>

                <td><?= htmlspecialchars($e['id_local'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>

                <td><?= htmlspecialchars($e['serie'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>

                <td><?= htmlspecialchars($e['equipo_marca'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>

                <td><?= htmlspecialchars($e['equipo_modelo'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>

                <td><?= htmlspecialchars($e['proc_marca'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>

                <td><?= htmlspecialchars($e['proc_familia'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>

                <td><?= htmlspecialchars($e['proc_modelo'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>

                <td><?= htmlspecialchars($e['proc_generacion'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>

                <td><?= htmlspecialchars($e['graficos'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>

                <td>
                    <?= 
                    $e['g_expandible'] == 0 ? 'Integrada' :
                    ($e['g_expandible'] == 1 ? 'APU Ajustable' : 'Dedicada')
                    ?>
                </td>

                <td><?= htmlspecialchars($e['memoria'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>

                <td><?= htmlspecialchars($e['disco'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>

                <td><?= htmlspecialchars($e['pantalla'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>

                <td><?= htmlspecialchars($e['p_resolucion'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>

                <td>
                    <?= $e['touch'] == 1 ? 'SI' : 'NO' ?>
                </td>

                <td>
                    <?= !empty($e['precio']) ? $e['precio'] : '' ?>
                </td>

                <td><?= htmlspecialchars($e['comenta'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>

                <td><?= htmlspecialchars($e['estado'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>

                <td><?= htmlspecialchars($e['created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>

                <td><?= htmlspecialchars($e['vendida_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>

            </tr>

        <?php endforeach; ?>

    </table>

<?php

} catch (Exception $e) {

    echo "Error: " . $e->getMessage();
}

?>