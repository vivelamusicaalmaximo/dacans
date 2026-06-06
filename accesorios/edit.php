<?php

session_start();

if (!isset($_SESSION['admin_logueado'])) {

    header("Location: ../login.php");
    exit;
}

if (
    ($_SESSION['rol'] === 'invitado') ||
    ($_SESSION['rol'] === 'empleado')
) {

    echo "No tienes permisos para editar accesorios.";
    exit;
}

/* ======================================================
   CONEXION SQL SERVER
====================================================== */

require '../config/conexion.php';

try {

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {

    die("Error DB: " . $e->getMessage());
}

$error = "";
$success = "";

/* ======================================================
   OBTENER ID
====================================================== */

if (!isset($_GET['id']) || empty($_GET['id'])) {

    die("ID no válido");
}

$id = (int) $_GET['id'];

/* ======================================================
   OBTENER ACCESORIO
====================================================== */

$stmt = $pdo->prepare("
    SELECT TOP 1 *
    FROM accesorios
    WHERE id = ?
");

$stmt->execute([$id]);

$accesorio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$accesorio) {

    die("Accesorio no encontrado");
}

/* ======================================================
   ACTUALIZAR
====================================================== */

if (isset($_POST['guardar'])) {

    try {

        $sql = "
            UPDATE accesorios SET

                nombre = ?,
                categoria = ?,
                marca = ?,
                descripcion = ?,
                precio = ?,
                stock = ?,
                imagen_url = ?,
                estado = ?

            WHERE id = ?
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([

            $_POST['nombre'],
            $_POST['categoria'],
            $_POST['marca'],
            $_POST['descripcion'],
            $_POST['precio'],
            $_POST['stock'],
            $_POST['imagen_url'],
            $_POST['estado'],

            $id

        ]);

        $success = "Accesorio actualizado correctamente";

        /* ======================================================
           RECARGAR DATOS
        ====================================================== */

        $stmt = $pdo->prepare("
            SELECT TOP 1 *
            FROM accesorios
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        $accesorio = $stmt->fetch(PDO::FETCH_ASSOC);

    } catch (Exception $e) {

        $error = $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Editar Accesorio | DACANS</title>

    <script src="https://cdn.tailwindcss.com"></script>

    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>

        body {

            background:
                radial-gradient(circle at top left, rgba(37,99,235,.08), transparent 30%),
                radial-gradient(circle at bottom right, rgba(14,165,233,.08), transparent 30%),
                #f8fafc;
        }

    </style>

</head>

<body class="min-h-screen p-4 md:p-8">

    <div class="max-w-4xl mx-auto">

        <!-- HEADER -->

        <div class="flex flex-col md:flex-row gap-4 justify-between items-center mb-8">

            <div class="flex items-center gap-4">

                <div class="bg-white p-3 rounded-2xl shadow-lg border border-slate-200">

                    <img src="../img/logo.webp"
                        class="h-12 object-contain">

                </div>

                <div>

                    <h1 class="text-3xl font-black text-slate-900">
                        EDITAR ACCESORIO
                    </h1>

                    <p class="text-slate-500 text-sm">
                        Modifica la información del accesorio
                    </p>

                </div>

            </div>

            <a href="view.php"
                class="bg-slate-900 hover:bg-black text-white px-6 py-3 rounded-2xl font-bold transition">

                <i class="fa-solid fa-arrow-left mr-2"></i>

                Volver

            </a>

        </div>

        <!-- MENSAJES -->

        <?php if ($success): ?>

            <div class="bg-green-500 text-white p-4 rounded-2xl mb-6 font-bold shadow-lg">

                <?= $success ?>

            </div>

        <?php endif; ?>

        <?php if ($error): ?>

            <div class="bg-red-500 text-white p-4 rounded-2xl mb-6 font-bold shadow-lg">

                <?= $error ?>

            </div>

        <?php endif; ?>

        <!-- FORM -->

        <div class="bg-white rounded-[2rem] shadow-2xl border border-slate-200 overflow-hidden">

            <div class="bg-gradient-to-r from-blue-700 to-cyan-500 px-8 py-6 text-white">

                <h2 class="text-2xl font-black">
                    Editar Producto
                </h2>

                <p class="text-sm text-white/80 mt-1">
                    Actualiza los datos del accesorio
                </p>

            </div>

            <form method="POST"
                class="p-6 md:p-8">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                    <!-- NOMBRE -->

                    <div class="md:col-span-2">

                        <label class="text-xs uppercase font-black text-slate-400 ml-2">
                            Nombre
                        </label>

                        <input
                            type="text"
                            name="nombre"
                            required
                            value="<?= htmlspecialchars($accesorio['nombre']) ?>"
                            class="w-full mt-1 p-4 rounded-2xl border border-slate-200 bg-slate-50">

                    </div>

                    <!-- CATEGORIA -->

                    <div>

                        <label class="text-xs uppercase font-black text-slate-400 ml-2">
                            Categoría
                        </label>

                        <select
                            name="categoria"
                            class="w-full mt-1 p-4 rounded-2xl border border-slate-200 bg-slate-50">

                            <?php

                            $categorias = [

                                'Teclados',
                                'Mouse',
                                'Auriculares',
                                'Mochilas',
                                'Monitores',
                                'Almacenamiento',
                                'Adaptadores',
                                'Cargadores',
                                'Otros'
                            ];

                            foreach ($categorias as $cat):

                            ?>

                                <option
                                    value="<?= $cat ?>"
                                    <?= $accesorio['categoria'] == $cat ? 'selected' : '' ?>>

                                    <?= $cat ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <!-- MARCA -->

                    <div>

                        <label class="text-xs uppercase font-black text-slate-400 ml-2">
                            Marca
                        </label>

                        <input
                            type="text"
                            name="marca"
                            value="<?= htmlspecialchars($accesorio['marca']) ?>"
                            class="w-full mt-1 p-4 rounded-2xl border border-slate-200 bg-slate-50">

                    </div>

                    <!-- PRECIO -->

                    <div>

                        <label class="text-xs uppercase font-black text-slate-400 ml-2">
                            Precio
                        </label>

                        <input
                            type="number"
                            step="0.01"
                            name="precio"
                            value="<?= $accesorio['precio'] ?>"
                            class="w-full mt-1 p-4 rounded-2xl border border-slate-200 bg-slate-50">

                    </div>

                    <!-- STOCK -->

                    <div>

                        <label class="text-xs uppercase font-black text-slate-400 ml-2">
                            Stock
                        </label>

                        <input
                            type="number"
                            name="stock"
                            value="<?= $accesorio['stock'] ?>"
                            class="w-full mt-1 p-4 rounded-2xl border border-slate-200 bg-slate-50">

                    </div>

                    <!-- ESTADO -->

                    <div>

                        <label class="text-xs uppercase font-black text-slate-400 ml-2">
                            Estado
                        </label>

                        <select
                            name="estado"
                            class="w-full mt-1 p-4 rounded-2xl border border-slate-200 bg-slate-50">

                            <option value="Activo"
                                <?= $accesorio['estado'] == 'Activo' ? 'selected' : '' ?>>
                                Activo
                            </option>

                            <option value="Agotado"
                                <?= $accesorio['estado'] == 'Agotado' ? 'selected' : '' ?>>
                                Agotado
                            </option>

                            <option value="Oculto"
                                <?= $accesorio['estado'] == 'Oculto' ? 'selected' : '' ?>>
                                Oculto
                            </option>

                        </select>

                    </div>

                    <!-- IMAGEN -->

                    <div>

                        <label class="text-xs uppercase font-black text-slate-400 ml-2">
                            URL Imagen
                        </label>

                        <input
                            type="text"
                            name="imagen_url"
                            value="<?= htmlspecialchars($accesorio['imagen_url']) ?>"
                            class="w-full mt-1 p-4 rounded-2xl border border-slate-200 bg-slate-50">

                    </div>

                    <!-- DESCRIPCIÓN -->

                    <div class="md:col-span-2">

                        <label class="text-xs uppercase font-black text-slate-400 ml-2">
                            Descripción
                        </label>

                        <textarea
                            name="descripcion"
                            rows="5"
                            class="w-full mt-1 p-4 rounded-2xl border border-slate-200 bg-slate-50 resize-none"><?= htmlspecialchars($accesorio['descripcion']) ?></textarea>

                    </div>

                </div>

                <!-- PREVIEW -->

                <?php if (!empty($accesorio['imagen_url'])): ?>

                    <div class="mt-8">

                        <p class="text-xs uppercase font-black text-slate-400 mb-3">
                            Vista previa
                        </p>

                        <div class="w-48 h-48 rounded-[2rem] overflow-hidden border border-slate-200 bg-slate-100">

                            <img
                                src="<?= $accesorio['imagen_url'] ?>"
                                class="w-full h-full object-cover">

                        </div>

                    </div>

                <?php endif; ?>

                <!-- BOTONES -->

                <div class="flex flex-col md:flex-row gap-4 mt-10">

                    <button
                        type="submit"
                        name="guardar"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-2xl font-black text-lg shadow-xl transition">

                        <i class="fa-solid fa-floppy-disk mr-2"></i>

                        GUARDAR CAMBIOS

                    </button>

                    <a href="view.php"
                        class="flex-1 md:flex-none text-center bg-slate-100 hover:bg-slate-200 text-slate-700 px-8 py-4 rounded-2xl font-black transition">

                        CANCELAR

                    </a>

                </div>

            </form>

        </div>

    </div>

</body>

</html>