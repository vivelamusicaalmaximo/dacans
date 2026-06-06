<?php

session_start();

if (!isset($_SESSION['admin_logueado'])) {

    header("Location: ../login.php");
    exit;
}

/* SOLO ADMIN Y SUPERADMIN */

if (
    $_SESSION['rol'] !== 'superadmin'
    && $_SESSION['rol'] !== 'admin'
) {

    die("Acceso denegado.");
}

require_once '../config/conexion.php';

$mensaje = "";

/* ======================================================
   CREAR USUARIO
====================================================== */

if (isset($_POST['crear'])) {

    $usuario = trim($_POST['usuario']);

    $correo = trim($_POST['correo']);

    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $rol = $_POST['rol'];

    try {

        $stmt = $pdo->prepare("
            INSERT INTO usuarios (

                usuario,
                correo,
                password,
                rol

            ) VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([

            $usuario,
            $correo,
            $password,
            $rol

        ]);

        $mensaje = "Usuario creado correctamente.";

    } catch (Exception $e) {

        $mensaje = "Error: usuario o correo ya existen.";
    }
}

/* ======================================================
   EDITAR USUARIO
====================================================== */

/* ======================================================
   EDITAR USUARIO
====================================================== */

if (isset($_POST['editar'])) {

    $id = $_POST['id'];

    $usuario = trim($_POST['usuario']);

    $correo = trim($_POST['correo']);

    $rol = $_POST['rol'];

    $porcent_ganancias = $_POST['porcent_ganancias'] ?? 0;

    if (!empty($_POST['password'])) {

        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            UPDATE usuarios SET

                usuario = ?,
                correo = ?,
                password = ?,
                rol = ?,
                porcent_ganancias = ?

            WHERE id = ?
        ");

        $stmt->execute([

            $usuario,
            $correo,
            $password,
            $rol,
            $porcent_ganancias,
            $id

        ]);

    } else {

        $stmt = $pdo->prepare("
            UPDATE usuarios SET

                usuario = ?,
                correo = ?,
                rol = ?,
                porcent_ganancias = ?

            WHERE id = ?
        ");

        $stmt->execute([

            $usuario,
            $correo,
            $rol,
            $porcent_ganancias,
            $id

        ]);
    }

    $mensaje = "Usuario actualizado.";
}
/* ======================================================
   ELIMINAR USUARIO
====================================================== */

if (isset($_GET['eliminar'])) {

    $id = $_GET['eliminar'];

    $stmt = $pdo->prepare("
        DELETE FROM usuarios
        WHERE id = ?
    ");

    $stmt->execute([$id]);

    $mensaje = "Usuario eliminado.";
}

/* ======================================================
   LISTAR USUARIOS
====================================================== */

$usuarios = $pdo->query("
    SELECT *
    FROM usuarios
    ORDER BY id DESC
")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<title>Módulo de Usuarios</title>

<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" href="/img/favicon.ico">
<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>

<body class="bg-slate-100 p-4 sm:p-6">

<div class="max-w-7xl mx-auto">

    <!-- HEADER -->

    <div class="flex flex-col sm:flex-row justify-between gap-4 items-center mb-8">

        <div>

            <h1 class="text-3xl font-black text-blue-900 uppercase italic">
                Usuarios
            </h1>

            <p class="text-slate-500 text-sm">
                Gestión de accesos del sistema
            </p>

        </div>

        <a
            href="../mantenimiento"
            class="bg-slate-200 hover:bg-slate-300 transition px-5 py-3 rounded-2xl font-bold">

            <i class="fa-solid fa-arrow-left mr-2"></i>
            Volver

        </a>

    </div>

    <!-- MENSAJE -->

    <?php if ($mensaje): ?>

        <div class="bg-blue-900 text-white p-4 rounded-2xl mb-6 font-bold">
            <?= $mensaje ?>
        </div>

    <?php endif; ?>

    <!-- FORMULARIO CREAR -->

    <form
        method="POST"
        class="bg-white rounded-[2rem] shadow-xl p-6 mb-8 border border-slate-200">

        <h2 class="text-xl font-black text-slate-700 mb-6">
            Crear Usuario
        </h2>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">

            <input
                type="text"
                name="usuario"
                placeholder="Usuario"
                required
                class="p-4 rounded-2xl border border-slate-200">

            <input
                type="email"
                name="correo"
                placeholder="Correo"
                required
                class="p-4 rounded-2xl border border-slate-200">

            <input
                type="password"
                name="password"
                placeholder="Contraseña"
                required
                class="p-4 rounded-2xl border border-slate-200">

            <select
                name="rol"
                class="p-4 rounded-2xl border border-slate-200">

                <option value="empleado">
                    Empleado
                </option>

                 <option value="invitado">
                    Invitado
                </option>

                <option value="admin">
                    Administrador
                </option>

                <option value="superadmin">
                    Super Admin
                </option>

            </select>

        </div>

        <button
            type="submit"
            name="crear"
            class="mt-6 bg-blue-900 hover:bg-black transition text-white px-8 py-4 rounded-2xl font-black">

            <i class="fa-solid fa-user-plus mr-2"></i>
            CREAR USUARIO

        </button>

    </form>

    <!-- TABLA -->

    <div class="bg-white rounded-[2rem] shadow-xl overflow-hidden border border-slate-200 overflow-x-auto">

        <table class="w-full min-w-[1000px]">

            <thead class="bg-slate-100">

                <tr>

                    <th class="p-4 text-left">ID</th>
                    <th class="p-4 text-left">Usuario</th>
                    <th class="p-4 text-left">Correo</th>
                    <th class="p-4 text-left">Password</th>
                    <th class="p-4 text-left">Rol</th>

                    <th class="p-4 text-left">Porcentaje</th>
                    <th class="p-4 text-left">Fecha</th>
                    <th class="p-4 text-center">Acciones</th>

                </tr>

            </thead>

            <tbody>

                <?php foreach ($usuarios as $u): ?>

                    <tr class="border-t border-slate-100 hover:bg-slate-50">

                        <form method="POST">

                            <input
                                type="hidden"
                                name="id"
                                value="<?= $u['id'] ?>">

                            <td class="p-4 font-bold">
                                #<?= $u['id'] ?>
                            </td>

                            <td class="p-4">

                                <input
                                    type="text"
                                    name="usuario"
                                    value="<?= $u['usuario'] ?>"
                                    class="w-full p-3 rounded-xl border border-slate-200">

                            </td>

                            <td class="p-4">

                                <input
                                    type="email"
                                    name="correo"
                                    value="<?= $u['correo'] ?>"
                                    class="w-full p-3 rounded-xl border border-slate-200">

                            </td>

                            <td class="p-4">

                                <input
                                    type="password"
                                    name="password"
                                    placeholder="Nueva contraseña"
                                    class="w-full p-3 rounded-xl border border-slate-200">

                            </td>

                            <td class="p-4">

                                <select
                                    name="rol"
                                    class="w-full p-3 rounded-xl border border-slate-200">

                                    <option value="empleado"
                                        <?= $u['rol'] == 'empleado' ? 'selected' : '' ?>>
                                        Empleado
                                    </option>
                                        <option value="invitado"
                                            <?= $u['rol'] == 'invitado' ? 'selected' : '' ?>>
                                            Invitado
                                        </option>
                                    <option value="admin"
                                        <?= $u['rol'] == 'admin' ? 'selected' : '' ?>>
                                        Admin
                                    </option>

                                    <option value="superadmin"
                                        <?= $u['rol'] == 'superadmin' ? 'selected' : '' ?>>
                                        Super Admin
                                    </option>

                                </select>

                            </td>
<td class="p-4">

    <div class="relative">

        <input
            type="number"
            step="0.01"
            min="0"
            max="100"
            name="porcent_ganancias"
            value="<?= htmlspecialchars($u['porcent_ganancias'] ?? '0') ?>"
            class="w-full p-3 pr-8 rounded-xl border border-slate-200">

        <span class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 font-bold">
            %
        </span>

    </div>

</td>

                            <td class="p-4 text-sm text-slate-500">
                                <?= $u['fecha_creacion'] ?>
                            </td>

                            <td class="p-4">

                                <div class="flex gap-2 justify-center">

                                    <!-- EDITAR -->

                                    <button
                                        type="submit"
                                        name="editar"
                                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-xl font-black text-sm transition">

                                        <i class="fa-solid fa-pen"></i>

                                    </button>

                                    <!-- ELIMINAR -->

                                    <a
                                        href="?eliminar=<?= $u['id'] ?>"
                                        onclick="return confirm('¿Eliminar usuario?')"
                                        class="bg-red-500 hover:bg-red-600 text-white px-4 py-3 rounded-xl font-black text-sm transition">

                                        <i class="fa-solid fa-trash"></i>

                                    </a>

                                </div>

                            </td>

                        </form>

                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

</div>

</body>

</html>