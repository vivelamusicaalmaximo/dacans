<?php
session_start();
if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config/conexion.php';

$id = $_GET['id'] ?? '';
if (empty($id)) {
    die("ID de equipo no especificado.");
}

$mensaje = '';
$tipoMensaje = '';

// 1. Procesar la actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marca = $_POST['equipo_marca'] ?? '';
    $modelo = $_POST['equipo_modelo'] ?? '';
    $precio = (float)($_POST['precio'] ?? 0);
    $estadoActualizado = $_POST['estado'] ?? '';
    $comentario = $_POST['comenta'] ?? '';

    try {
        $stmtUpdate = $pdo->prepare("
            UPDATE productos_informatica 
            SET equipo_marca = ?, 
                equipo_modelo = ?, 
                precio = ?, 
                estado = ?, 
                comenta = ?
            WHERE id_local = ?
        ");
        $stmtUpdate->execute([$marca, $modelo, $precio, $estadoActualizado, $comentario, $id]);
        
        $mensaje = "¡Equipo actualizado correctamente!";
        $tipoMensaje = "success";
    } catch (Exception $e) {
        $mensaje = "Error al actualizar: " . $e->getMessage();
        $tipoMensaje = "error";
    }
}

// 2. Obtener datos actuales del equipo
$stmt = $pdo->prepare("SELECT * FROM productos_informatica WHERE id_local = ?");
$stmt->execute([$id]);
$equipo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipo) {
    die("El equipo solicitado no existe.");
}

// Lista de estados soportados por el sistema
$estadosDisponibles = ['Lista', 'Vendida', 'CREDITO', 'En camino', 'En revision', 'NO Lista'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Equipo #<?= $id ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="bg-slate-100 p-6 flex items-center justify-center min-h-screen">

<div class="max-w-2xl w-full bg-white rounded-3xl shadow-xl p-8 border border-slate-200">
    
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-black text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-laptop text-blue-700"></i> Editar Equipo #<?= $equipo['id_local'] ?>
        </h1>
        <a href="index.php" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-xl font-bold text-sm transition">
            <i class="fa-solid fa-arrow-left mr-1"></i> Volver
        </a>
    </div>

    <?php if (!empty($mensaje)): ?>
        <div class="p-4 mb-6 rounded-2xl text-sm font-bold <?= $tipoMensaje === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
            <?= htmlspecialchars($mensaje) ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST" class="space-y-5">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-black uppercase text-slate-400 mb-2">Marca</label>
                <input type="text" name="equipo_marca" value="<?= htmlspecialchars($equipo['equipo_marca']) ?>" required
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 font-bold text-slate-800 focus:outline-none focus:border-blue-500 transition">
            </div>
            
            <div>
                <label class="block text-xs font-black uppercase text-slate-400 mb-2">Modelo</label>
                <input type="text" name="equipo_modelo" value="<?= htmlspecialchars($equipo['equipo_modelo']) ?>" required
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 font-bold text-slate-800 focus:outline-none focus:border-blue-500 transition">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-black uppercase text-slate-400 mb-2">Precio de Venta (RD$)</label>
                <input type="number" step="0.01" name="precio" value="<?= (float)$equipo['precio'] ?>" required
                       class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 font-black text-slate-900 focus:outline-none focus:border-blue-500 transition">
            </div>

            <div>
                <label class="block text-xs font-black uppercase text-slate-400 mb-2">Estado del Inventario</label>
                <select name="estado" class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 font-bold text-slate-800 focus:outline-none focus:border-blue-500 transition">
                    <?php foreach ($estadosDisponibles as $est): ?>
                        <option value="<?= $est ?>" <?= $equipo['estado'] === $est ? 'selected' : '' ?>>
                            <?= $est ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-black uppercase text-slate-400 mb-2">Comentarios / Notas Historial</label>
            <textarea name="comenta" rows="4" 
                      class="w-full bg-slate-50 border border-slate-200 rounded-xl py-3 px-4 font-semibold text-slate-700 text-sm focus:outline-none focus:border-blue-500 transition"><?= htmlspecialchars($equipo['comenta'] ?? '') ?></textarea>
        </div>

        <button type="submit" class="w-full bg-blue-700 hover:bg-blue-800 text-white font-black py-4 rounded-xl shadow-md transition-all">
            <i class="fa-solid fa-floppy-disk mr-1"></i> Guardar Cambios
        </button>
    </form>

</div>

</body>
</html>