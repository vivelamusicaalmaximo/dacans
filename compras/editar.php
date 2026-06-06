<?php
session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config/conexion.php'; 

/* =========================================================
   VALIDAR ID
========================================================= */
$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID no valido");
}

/* =========================================================
   OBTENER ARTICULO
========================================================= */
$stmt = $pdo->prepare("
    SELECT *
    FROM compras_articulos
    WHERE id = ?
");
$stmt->execute([$id]);
$articulo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$articulo) {
    die("Articulo no encontrado");
}

/* =========================================================
   ACTUALIZAR
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Se organizaron los campos para que coincidan exactamente con el execute
    $stmt = $pdo->prepare("
        UPDATE compras_articulos SET
            nombre_articulo = ?,
            cantidad_articulos = ?,
            costo_usd = ?,
            costo_dop = ?,
            costo_impuestos = ?,
            costo_envio = ?,
            costo_unitario = ?,
            porcentaje_incremento = ?,
            numero_rastreo_us = ?,
            status_compra = ?,
            direccion_usada = ?,
            id_courier = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $_POST['nombre_articulo'],
        $_POST['cantidad_articulos'],
        $_POST['costo_usd'],
        $_POST['costo_dop'],
        $_POST['costo_impuestos'],
        $_POST['costo_envio'],
        $_POST['costo_unitario'],
        $_POST['porcentaje_incremento'],
        $_POST['numero_rastreo_us'],
        $_POST['status_compra'],
        $_POST['direccion_usada'], // Sincronizado en la posición correcta
        $_POST['id_courier'],      // Sincronizado en la posición correcta
        $id
    ]);

    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Artículo</title>
    <link rel="shortcut icon" href="/img/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
    body {
        background:
            radial-gradient(circle at top left, rgba(37, 99, 235, .08), transparent 30%),
            radial-gradient(circle at bottom right, rgba(14, 165, 233, .08), transparent 30%),
            #f8fafc;
        font-family: Arial, sans-serif;
    }

    .card {
        background: white;
        border-radius: 32px;
        border: 1px solid #e2e8f0;
        box-shadow:
            0 10px 30px rgba(15, 23, 42, .05),
            0 2px 10px rgba(15, 23, 42, .03);
    }

    .input {
        width: 100%;
        border: 1px solid #dbeafe;
        background: #f8fafc;
        border-radius: 16px;
        padding: 14px;
        outline: none;
        transition: .2s;
    }

    .input:focus {
        border-color: #2563eb;
        background: white;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .10);
    }

    .label {
        display: block;
        margin-bottom: 8px;
        font-size: 13px;
        font-weight: 800;
        color: #334155;
        text-transform: uppercase;
    }
    </style>
</head>

<body class="p-4 md:p-8">

    <div class="max-w-5xl mx-auto">

        <div class="flex flex-col lg:flex-row justify-between items-center gap-5 mb-8">
            <div class="flex items-center gap-4">
                <img src="../img/logo.webp" class="h-20 bg-white p-3 rounded-3xl shadow-lg border border-slate-200">
                <div>
                    <h1 class="text-4xl font-black text-slate-900">Editar Artículo</h1>
                    <p class="text-slate-500 mt-1">Modifica la información del artículo</p>
                </div>
            </div>
            <a href="index.php"
                class="bg-slate-900 hover:bg-black text-white px-6 py-4 rounded-2xl font-black shadow-lg transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Volver
            </a>
        </div>

        <div class="card p-8">
            <form method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div class="md:col-span-2">
                        <label class="label">Nombre Artículo</label>
                        <input type="text" name="nombre_articulo" class="input" required
                            value="<?= htmlspecialchars($articulo['nombre_articulo'] ?? '') ?>">
                    </div>

                    <div>
                        <label class="label">Cantidad</label>
                        <input type="number" name="cantidad_articulos" class="input"
                            value="<?= htmlspecialchars($articulo['cantidad_articulos'] ?? '') ?>">
                    </div>

                    <div>
                        <label class="label">Costo USD</label>
                        <input type="number" step="0.01" name="costo_usd" class="input"
                            value="<?= htmlspecialchars($articulo['costo_usd'] ?? '') ?>">
                    </div>

                    <div>
                        <label class="label">Costo DOP</label>
                        <input type="number" step="0.01" name="costo_dop" class="input"
                            value="<?= htmlspecialchars($articulo['costo_dop'] ?? '') ?>">
                    </div>

                    <div>
                        <label class="label">Impuestos</label>
                        <input type="number" step="0.01" name="costo_impuestos" class="input"
                            value="<?= htmlspecialchars($articulo['costo_impuestos'] ?? '') ?>">
                    </div>

                    <div>
                        <label class="label">Envío</label>
                        <input type="number" step="0.01" name="costo_envio" class="input"
                            value="<?= htmlspecialchars($articulo['costo_envio'] ?? '') ?>">
                    </div>

                    <div>
                        <label class="label">Costo Unitario</label>
                        <input type="number" step="0.01" name="costo_unitario" class="input"
                            value="<?= htmlspecialchars($articulo['costo_unitario'] ?? '') ?>">
                    </div>

                    <div>
                        <label class="label">% Incremento</label>
                        <input type="number" step="0.01" name="porcentaje_incremento" class="input"
                            value="<?= htmlspecialchars($articulo['porcentaje_incremento'] ?? '') ?>">
                    </div>

                    <div>
                        <label class="label">Rastreo US</label>
                        <input type="text" name="numero_rastreo_us" class="input"
                            value="<?= htmlspecialchars($articulo['numero_rastreo_us'] ?? '') ?>">
                    </div>

                    <div>
                        <label class="label">ID Courier</label>
                        <input type="text" name="id_courier" class="input"
                            value="<?= htmlspecialchars($articulo['id_courier'] ?? '') ?>">
                    </div>

                    <div>
                        <label class="label">Dirección</label>
                        <select name="direccion_usada" class="input">
                            <?php 
                            $direcciones = [
                                "Pendiente asignar",
                                "D01-050381 Daniel Candelario",
                              
                                "D01-064428 Sandra Solano",
                                "D01-117254 Silvia Guigni",
                                "D01-117374 Yovanny Marquez",
                                "D01-097795 Sandra P Candelario Solano",
                                "D01-061860 Ramon S Medina",
                                "D01-321309 Yensi Alexander Pena",
                                "D01-320879 Aurelyna Marys Collado",
                                "D01-320863 Yunely Alexandra Pena",
                                "D01-321311 Angela Melo",
                                "D01-102832 Ronald Taveras",
                                "D01-097791 Luis Rivera Perez",
                                "D01-083401 Santa Amador Pineda",
                                "D01-064429 Daniel Candelario Pena",
                                "D01-570395 Pablo Fernandez"
                            ];
                            foreach ($direcciones as $dir): 
                                $selDir = ($articulo['direccion_usada'] == $dir) ? 'selected' : '';
                            ?>
                            <option value="<?= $dir ?>" <?= $selDir ?>><?= $dir ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="label">Estado</label>
                        <select name="status_compra" class="input">
                            <?php
                            $estados = [
                                'Ganado',
                                'Pagado',
                                'Enviado',
                                'Cancelado',
                                'Entregado',
                                'Aduanas',
                                'Listo para Recogida',
                                'Disponible'
                            ];
                            foreach ($estados as $estado):
                                $selEstado = ($articulo['status_compra'] == $estado) ? 'selected' : '';
                            ?>
                            <option value="<?= $estado ?>" <?= $selEstado ?>><?= $estado ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                </div>

                <div class="flex flex-wrap gap-4 mt-8">
                    <button type="submit"
                        class="bg-blue-700 hover:bg-blue-800 text-white px-8 py-4 rounded-2xl font-black shadow-lg transition">
                        <i class="fa-solid fa-floppy-disk mr-2"></i> Guardar Cambios
                    </button>
                    <a href="index.php"
                        class="bg-slate-200 hover:bg-slate-300 text-slate-800 px-8 py-4 rounded-2xl font-black transition">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

</body>

</html>