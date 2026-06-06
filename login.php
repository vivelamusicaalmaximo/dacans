<?php
session_start();

/* =========================================================
   CONEXION SQL SERVER
========================================================= */

require_once 'config/conexion.php';

$error = "";

/* =========================================================
   LOGIN
========================================================= */

if (isset($_POST['login'])) {

    $usuario = trim($_POST['usuario']);
    $password = trim($_POST['password']);

    $sql = "
        SELECT TOP 1 *
        FROM usuarios
        WHERE usuario = ?
        OR correo = ?
    ";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        $usuario,
        $usuario
    ]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {

        $_SESSION['admin_logueado'] = true;

        $_SESSION['usuario'] = $user['usuario'];

        $_SESSION['correo'] = $user['correo'];

        $_SESSION['rol'] = $user['rol'];

        $_SESSION['porcent_ganancias'] = $user['porcent_ganancias'] ?? 0;

        header("Location: mantenimiento");
        exit;

    } else {

        $error = "Usuario o contraseña incorrectos";
    }
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="/img/favicon.png">
    <link rel="shortcut icon" href="/img/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        body {
            background: radial-gradient(circle at top, #1e3a8a 0%, #020617 60%);
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4 sm:p-6">

    <div class="bg-slate-900/90 backdrop-blur-xl w-full max-w-sm sm:max-w-md rounded-2xl sm:rounded-[2rem] p-6 sm:p-8 shadow-2xl border border-blue-500/20 text-white">
        
        <div class="text-center sm:text-left mb-6">
            <h1 class="text-2xl sm:text-3xl font-black text-transparent bg-clip-text bg-gradient-to-r from-blue-400 via-white to-cyan-400 uppercase italic tracking-tight mb-1">
                Dacans Admin
            </h1>
            <p class="text-slate-400 text-xs sm:text-sm">
                Panel de control de inventario
            </p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-500/20 border border-red-500/50 text-red-300 p-3 rounded-xl mb-5 text-xs sm:text-sm font-bold flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-red-400"></i>
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">

            <div>
                <label class="text-[10px] sm:text-xs font-black uppercase text-slate-400 block mb-1.5 tracking-wider">
                    Usuario o Correo
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500">
                        <i class="fa-solid fa-user text-xs"></i>
                    </span>
                    <input
                        type="text"
                        name="usuario"
                        required
                        autocorrect="off"
                        autocapitalize="none"
                        placeholder="Ej: dacans"
                        class="w-full bg-slate-950/50 border border-slate-800 text-white rounded-xl pl-10 pr-3 py-2.5 sm:py-3 text-sm outline-none focus:border-blue-500 transition placeholder:text-slate-600">
                </div>
            </div>

            <div>
                <label class="text-[10px] sm:text-xs font-black uppercase text-slate-400 block mb-1.5 tracking-wider">
                    Contraseña
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500">
                        <i class="fa-solid fa-lock text-xs"></i>
                    </span>
                    <input
                        type="password"
                        name="password"
                        required
                        placeholder="••••••••"
                        class="w-full bg-slate-950/50 border border-slate-800 text-white rounded-xl pl-10 pr-3 py-2.5 sm:py-3 text-sm outline-none focus:border-blue-500 transition placeholder:text-slate-600">
                </div>
            </div>

            <button
                type="submit"
                name="login"
                class="w-full bg-gradient-to-r from-blue-600 to-cyan-500 hover:opacity-90 active:scale-[0.98] transition text-white rounded-xl py-3 text-sm font-black uppercase tracking-wider mt-2 shadow-lg shadow-blue-500/20 cursor-pointer">
                Entrar <i class="fa-solid fa-right-to-bracket ml-1 text-xs"></i>
            </button>

        </form>

    </div>

</body>

</html>