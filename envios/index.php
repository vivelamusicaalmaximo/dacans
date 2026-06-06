<?php

session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

require '../config/conexion.php';

/* =========================================
   ELIMINAR
========================================= */

if (isset($_GET['eliminar'])) {

    $id = (int) $_GET['eliminar'];

    $stmtDelete = $pdo->prepare("
        DELETE FROM agencias_envio
        WHERE id = ?
    ");

    $stmtDelete->execute([$id]);

    header("Location: index.php?deleted=1");
    exit;
}

/* =========================================
   GUARDAR
========================================= */

if (isset($_POST['guardar'])) {

    $stmt = $pdo->prepare("
        INSERT INTO agencias_envio (

            provincia,
            ciudad,
            agencia,
            costo,
            telefono,
            direccion,
            comentario,
            tiempo_entrega

        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([

        $_POST['provincia'],
        $_POST['ciudad'],
        $_POST['agencia'],
        $_POST['costo'],
        $_POST['telefono'],
        $_POST['direccion'],
        $_POST['comentario'],
        $_POST['tiempo_entrega']

    ]);

    header("Location: index.php?success=1");
    exit;
}

/* =========================================
   OBTENER DATOS
========================================= */

$query = $pdo->query("
    SELECT *
    FROM agencias_envio
    ORDER BY fecha_registro DESC
");

$agencias = $query->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Agencias de Envío | DACANS</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="shortcut icon" href="../img/favicon.ico">

</head>

<body class="bg-slate-100 min-h-screen">

    <div class="max-w-7xl mx-auto p-6">

        <!-- HEADER -->

        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">

            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-5">

                <div class="flex items-center gap-4">

                    <!-- LOGO -->
                    <img src="../img/logo.png"
                         alt="DACANS"
                         class="w-20 h-20 object-contain">

                    <div>

                        <h1 class="text-3xl font-bold text-slate-800">
                            DACANS COMPUTER
                        </h1>

                        <p class="text-slate-500 mt-1">
                            Administración de Agencias de Envío
                        </p>

                    </div>

                </div>

                <a href="crear.php"
                   class="bg-slate-800 hover:bg-slate-900 text-white px-5 py-3 rounded-xl font-semibold transition">

                    Crear Agencia
                </a>

                <a href="../mantenimiento"
                   class="bg-slate-800 hover:bg-slate-900 text-white px-6 py-3 rounded-xl font-semibold transition">

                    Volver al Panel

                </a>


            </div>

        </div>

        <!-- ALERTAS -->

        <?php if (isset($_GET['success'])): ?>

            <div class="bg-green-100 border border-green-300 text-green-700 p-4 rounded-xl mb-6">

                Agencia registrada correctamente.

            </div>

        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>

            <div class="bg-red-100 border border-red-300 text-red-700 p-4 rounded-xl mb-6">

                Agencia eliminada correctamente.

            </div>

        <?php endif; ?>

    

      

        <!-- TABLA -->

        <div class="bg-white rounded-2xl shadow-lg overflow-hidden">

            <div class="p-6 border-b">

                <h2 class="text-2xl font-bold text-slate-800">
                    Listado de Agencias
                </h2>

            </div>

            <div class="overflow-x-auto">

                <table class="w-full">

                    <thead class="bg-slate-800 text-white">

                        <tr>

                            <th class="p-4 text-left">Provincia</th>
                            <th class="p-4 text-left">Ciudad</th>
                            <th class="p-4 text-left">Agencia</th>
                            <th class="p-4 text-left">Costo</th>
                            <th class="p-4 text-left">Teléfono</th>
                            <th class="p-4 text-left">Tiempo</th>
                            <th class="p-4 text-left">Dirección</th>
                            <th class="p-4 text-left">Fecha</th>
                            <th class="p-4 text-center">Acciones</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php if (count($agencias) > 0): ?>

                            <?php foreach ($agencias as $a): ?>

                                <tr class="border-b hover:bg-slate-50">

                                    <td class="p-4">
                                        <?= htmlspecialchars($a['provincia']) ?>
                                    </td>

                                    <td class="p-4">
                                        <?= htmlspecialchars($a['ciudad']) ?>
                                    </td>

                                    <td class="p-4 font-semibold text-slate-700">
                                        <?= htmlspecialchars($a['agencia']) ?>
                                    </td>

                                    <td class="p-4">
                                        RD$ <?= number_format($a['costo'], 2) ?>
                                    </td>

                                    <td class="p-4">
                                        <?= htmlspecialchars($a['telefono']) ?>
                                    </td>

                                    <td class="p-4">
                                        <?= htmlspecialchars($a['tiempo_entrega']) ?>
                                    </td>

                                    <td class="p-4">
                                        <?= htmlspecialchars($a['direccion']) ?>
                                    </td>

                                    <td class="p-4">
                                        <?= date('d/m/Y', strtotime($a['fecha_registro'])) ?>
                                    </td>

                                    <td class="p-4">

                                        <div class="flex gap-2 justify-center">

                                            <a href="editar.php?id=<?= $a['id'] ?>"
                                               class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">

                                                Editar

                                            </a>

                                            <a href="?eliminar=<?= $a['id'] ?>"
                                               onclick="return confirm('¿Deseas eliminar esta agencia?')"
                                               class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">

                                                Eliminar

                                            </a>

                                        </div>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>

                                <td colspan="9"
                                    class="text-center p-10 text-slate-500">

                                    No hay agencias registradas.

                                </td>

                            </tr>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</body>
</html>