<?php

session_start();

if (!isset($_SESSION['admin_logueado'])) {

    header("Location: ../login.php");
    exit;
}

if ($_SESSION['rol'] === 'empleado') {

    echo "No tienes permisos para crear accesorios.";
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

$mensaje = "";
$error = "";

/* ======================================================
   CREAR TABLA SI NO EXISTE
====================================================== */

$crearTabla = "
IF NOT EXISTS (
    SELECT *
    FROM sysobjects
    WHERE name='accesorios'
    AND xtype='U'
)
BEGIN

    CREATE TABLE accesorios (

        id INT IDENTITY(1,1) PRIMARY KEY,

        nombre NVARCHAR(255),
        categoria NVARCHAR(255),
        marca NVARCHAR(255),
        descripcion NVARCHAR(MAX),

        precio DECIMAL(18,2),
        stock INT DEFAULT 0,

        imagen_url NVARCHAR(MAX),

        estado NVARCHAR(100) DEFAULT 'Activo',

        fecha_creado DATETIME DEFAULT GETDATE()

    )

END
";

$pdo->exec($crearTabla);

/* ======================================================
   GUARDAR
====================================================== */

if (isset($_POST['guardar'])) {

    try {

        $sql = "
            INSERT INTO accesorios (

                nombre,
                categoria,
                marca,
                descripcion,
                precio,
                stock,
                imagen_url,
                estado

            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
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
            $_POST['estado']

        ]);

        $mensaje = "Accesorio registrado correctamente";

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

    <title>Registrar Accesorio | DACANS</title>

    <script src="https://cdn.tailwindcss.com"></script>
<link rel="shortcut icon" href="/img/favicon.ico">
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
                        NUEVO ACCESORIO
                    </h1>

                    <p class="text-slate-500 text-sm">
                        Panel de registro de accesorios
                    </p>

                </div>

            </div>

            <a href="index.php"
                class="bg-slate-900 hover:bg-black text-white px-6 py-3 rounded-2xl font-bold transition">

                <i class="fa-solid fa-arrow-left mr-2"></i>

                Volver

            </a>

        </div>

        <!-- MENSAJES -->

        <?php if ($mensaje): ?>

            <div class="bg-green-500 text-white p-4 rounded-2xl mb-6 font-bold shadow-lg">

                <?= $mensaje ?>

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
                    Registrar Producto
                </h2>

                <p class="text-sm text-white/80 mt-1">
                    Completa la información del accesorio
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
                            placeholder="Mouse Gamer RGB"
                            class="w-full mt-1 p-4 rounded-2xl border border-slate-200 bg-slate-50 focus:ring-2 focus:ring-blue-500 outline-none">

                    </div>

                    <!-- CATEGORIA -->

                    <div>

                        <label class="text-xs uppercase font-black text-slate-400 ml-2">
                            Categoría
                        </label>

                        <select
                            name="categoria"
                            class="w-full mt-1 p-4 rounded-2xl border border-slate-200 bg-slate-50">

                            <option value="Teclados">Teclados</option>
                            <option value="Mouse">Mouse</option>
                            <option value="Auriculares">Auriculares</option>
                            <option value="Mochilas">Mochilas</option>
                            <option value="Monitores">Monitores</option>
                            <option value="Almacenamiento">Almacenamiento</option>
                            <option value="Adaptadores">Adaptadores</option>
                            <option value="Cargadores">Cargadores</option>
                            <option value="Otros">Otros</option>

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
                            placeholder="Logitech"
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
                            placeholder="3500"
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
                            value="1"
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

                            <option value="Activo">
                                Activo
                            </option>

                            <option value="Agotado">
                                Agotado
                            </option>

                            <option value="Oculto">
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
                            placeholder="https://..."
                            class="w-full mt-1 p-4 rounded-2xl border border-slate-200 bg-slate-50">

                    </div>

                    <!-- DESCRIPCION -->

                    <div class="md:col-span-2">

                        <label class="text-xs uppercase font-black text-slate-400 ml-2">
                            Descripción
                        </label>

                        <textarea
                            name="descripcion"
                            rows="5"
                            placeholder="Descripción del accesorio..."
                            class="w-full mt-1 p-4 rounded-2xl border border-slate-200 bg-slate-50 resize-none"></textarea>

                    </div>

                </div>

                <!-- BOTONES -->

                <div class="flex flex-col md:flex-row gap-4 mt-8">

                    <button
                        type="submit"
                        name="guardar"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-4 rounded-2xl font-black text-lg shadow-xl transition">

                        <i class="fa-solid fa-floppy-disk mr-2"></i>

                        GUARDAR ACCESORIO

                    </button>

                    <a href="index.php"
                        class="flex-1 md:flex-none text-center bg-slate-100 hover:bg-slate-200 text-slate-700 px-8 py-4 rounded-2xl font-black transition">

                        CANCELAR

                    </a>

                </div>

            </form>

        </div>

    </div>

</body>

</html>