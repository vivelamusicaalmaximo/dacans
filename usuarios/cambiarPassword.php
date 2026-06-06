<?php

session_start();

if (!isset($_SESSION['admin_logueado'])) {

    header("Location: ../login.php");
    exit;
}

/* =========================================================
   CONEXION SQL SERVER
========================================================= */

require_once '../config/conexion.php';

$mensaje = "";
$error = "";

/* =========================================================
   CAMBIAR PASSWORD
========================================================= */

if (isset($_POST['cambiar_password'])) {

    $password_actual = trim($_POST['password_actual']);
    $password_nueva = trim($_POST['password_nueva']);
    $confirmar_password = trim($_POST['confirmar_password']);

    if (
        empty($password_actual) ||
        empty($password_nueva) ||
        empty($confirmar_password)
    ) {

        $error = "Todos los campos son obligatorios";

    } elseif ($password_nueva !== $confirmar_password) {

        $error = "Las nuevas contraseñas no coinciden";

    } elseif (strlen($password_nueva) < 6) {

        $error = "La nueva contraseña debe tener mínimo 6 caracteres";

    } else {

        $stmt = $pdo->prepare("
            SELECT TOP 1 *
            FROM usuarios
            WHERE usuario = ?
        ");

        $stmt->execute([$_SESSION['usuario']]);

        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {

            $error = "Usuario no encontrado";

        } elseif (!password_verify($password_actual, $usuario['password'])) {

            $error = "La contraseña actual es incorrecta";

        } else {

            $nuevoHash = password_hash(
                $password_nueva,
                PASSWORD_DEFAULT
            );

            $update = $pdo->prepare("
                UPDATE usuarios
                SET password = ?
                WHERE id = ?
            ");

            $update->execute([

                $nuevoHash,
                $usuario['id']

            ]);

            $mensaje = "Contraseña actualizada correctamente";

            header("Location: ../mantenimiento");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Cambiar contraseña</title>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="shortcut icon" href="/img/favicon.ico">
<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-md bg-white rounded-3xl shadow-2xl p-6">

    <div class="text-center mb-6">

        <div class="w-20 h-20 bg-blue-600 rounded-full
        flex items-center justify-center mx-auto">

            <i class="fa-solid fa-lock text-white text-3xl"></i>

        </div>

        <h1 class="text-2xl font-black text-slate-800 mt-4">
            Cambiar contraseña
        </h1>

        <p class="text-slate-500 text-sm mt-1">
            Actualiza tu contraseña de acceso
        </p>

    </div>

    <?php if ($mensaje): ?>

        <div class="bg-green-100 border border-green-300
        text-green-700 p-3 rounded-2xl mb-4 text-sm">

            <?= htmlspecialchars($mensaje) ?>

        </div>

    <?php endif; ?>

    <?php if ($error): ?>

        <div class="bg-red-100 border border-red-300
        text-red-700 p-3 rounded-2xl mb-4 text-sm">

            <?= htmlspecialchars($error) ?>

        </div>

    <?php endif; ?>

    <form method="POST">

        <!-- PASSWORD ACTUAL -->
        <div class="mb-4">

            <label class="block text-sm font-bold text-slate-700 mb-2">
                Contraseña actual
            </label>

            <input
                type="password"
                name="password_actual"
                class="w-full p-4 rounded-2xl border
                border-slate-300 focus:outline-none
                focus:ring-2 focus:ring-blue-500"
                required>

        </div>

        <!-- NUEVA PASSWORD -->
        <div class="mb-4">

            <label class="block text-sm font-bold text-slate-700 mb-2">
                Nueva contraseña
            </label>

            <input
                type="password"
                name="password_nueva"
                class="w-full p-4 rounded-2xl border
                border-slate-300 focus:outline-none
                focus:ring-2 focus:ring-blue-500"
                required>

        </div>

        <!-- CONFIRMAR -->
        <div class="mb-5">

            <label class="block text-sm font-bold text-slate-700 mb-2">
                Confirmar nueva contraseña
            </label>

            <input
                type="password"
                name="confirmar_password"
                class="w-full p-4 rounded-2xl border
                border-slate-300 focus:outline-none
                focus:ring-2 focus:ring-blue-500"
                required>

        </div>

        <!-- BOTON -->
        <button
            type="submit"
            name="cambiar_password"
            class="w-full bg-blue-600 hover:bg-blue-700
            text-white font-black py-4 rounded-2xl
            transition-all duration-300">

            <i class="fa-solid fa-key mr-2"></i>
            Cambiar contraseña

        </button>

    </form>

</div>

</body>
</html>