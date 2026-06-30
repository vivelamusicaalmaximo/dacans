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

// 1. Obtener datos del equipo
$stmt = $pdo->prepare("SELECT * FROM productos_informatica WHERE id_local = ?");
$stmt->execute([$id]);
$equipo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipo) {
    die("El equipo no existe.");
}

// Inicializar variables de control de la deuda
$precioTotal = (float)$equipo['precio'];
$abonosAnteriores = 0.0; 

// NOTA: Si manejas una tabla relacional de abonos (ej: 'cxc_abonos'), aquí harías un SUM(). 
// Como solución directa y auto-contenida, usaremos un campo temporal en la misma tabla si existiera, 
// o asumiremos el abono directamente desde el formulario.

$balancePendiente = $precioTotal - $abonosAnteriores;
$mensaje = '';
$tipoMensaje = '';

// 2. Procesar el formulario de Pago / Abono
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $montoPago = (float)($_POST['monto_pago'] ?? 0);
    $tipoPago = $_POST['tipo_pago'] ?? 'Total'; // 'Abono' o 'Total'

    if ($montoPago <= 0) {
        $mensaje = "El monto ingresado debe ser mayor a cero.";
        $tipoMensaje = "error";
    } elseif ($montoPago > $balancePendiente) {
        $mensaje = "El pago no puede ser mayor al balance pendiente (RD$ " . number_format($balancePendiente, 2) . ").";
        $tipoMensaje = "error";
    } else {
        try {
            $pdo->beginTransaction();

            // Si es pago total o el abono cubre exactamente lo que queda pendiente
            if ($tipoPago === 'Total' || $montoPago == $balancePendiente) {
                // El equipo pasa a estar completamente pagado y cambia a 'Vendida'
                $stmtUpdate = $pdo->prepare("
                    UPDATE productos_informatica 
                    SET estado = 'Vendida', 
                        vendida_at = GETDATE(),
                        comenta = CONCAT(comenta, ' | Crédito Completado el ', CONVERT(varchar, GETDATE(), 103))
                    WHERE id_local = ?
                ");
                $stmtUpdate->execute([$id]);
                $mensaje = "¡Pago completado con éxito! El equipo ahora figura como 'Vendida'.";
            } else {
                // Es un abono parcial (Mantenemos estado CREDITO, guardamos el comentario)
                $stmtUpdate = $pdo->prepare("
                    UPDATE productos_informatica 
                    SET comenta = CONCAT(comenta, ' | Abono de RD$', ?, ' el ', CONVERT(varchar, GETDATE(), 103))
                    WHERE id_local = ?
                ");
                $stmtUpdate->execute([$montoPago, $id]);
                $mensaje = "Abono registrado con éxito en el historial del equipo.";
            }

            $pdo->commit();
            $tipoMensaje = "success";
            
            // Recargar datos actualizados del equipo
            $stmt->execute([$id]);
            $equipo = $stmt->fetch(PDO::FETCH_ASSOC);
            $balancePendiente -= $montoPago;

        } catch (Exception $e) {
            $pdo->rollBack();
            $mensaje = "Error al procesar el pago: " . $e->getMessage();
            $tipoMensaje = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completar Pago - #<?= $id ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="bg-slate-100 p-6 flex items-center justify-center min-h-screen">

<div class="max-w-xl w-full bg-white rounded-3xl shadow-xl p-8 border border-slate-200">
    
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-black text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-money-bill-wave text-amber-500"></i> Gestión de Pago
        </h1>
        <a href="equipos_estado.php?estado=CREDITO" class="text-slate-500 hover:text-slate-800 font-bold text-sm">
            <i class="fa-solid fa-xmark mr-1"></i> Cancelar
        </a>
    </div>

    <?php if (!empty($mensaje)): ?>
        <div class="p-4 mb-6 rounded-2xl border text-sm font-bold flex items-center gap-3 <?= $tipoMensaje === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800' ?>">
            <i class="fa-solid <?= $tipoMensaje === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?> text-lg"></i>
            <p><?= htmlspecialchars($mensaje) ?></p>
        </div>
    <?php endif; ?>

    <div class="bg-slate-50 rounded-2xl p-4 mb-6 border border-slate-100 space-y-2 text-sm">
        <div class="flex justify-between"><span class="text-slate-500">ID Local:</span> <span class="font-black text-blue-700">#<?= $equipo['id_local'] ?></span></div>
        <div class="flex justify-between"><span class="text-slate-500">Equipo:</span> <span class="font-bold text-slate-800"><?= htmlspecialchars($equipo['equipo_marca'] . ' ' . $equipo['equipo_modelo']) ?></span></div>
        <div class="flex justify-between"><span class="text-slate-500">Estado Actual:</span> <span class="px-2 py-0.5 text-xs font-black bg-amber-100 text-amber-800 rounded-full"><?= $equipo['estado'] ?></span></div>
        <hr class="border-slate-200 my-2">
        <div class="flex justify-between text-base"><span class="text-slate-500">Precio de Venta:</span> <span class="font-black text-slate-900">RD$ <?= number_format($precioTotal, 2) ?></span></div>
        <div class="flex justify-between text-lg border-t border-dashed border-slate-300 pt-2"><span class="text-slate-900 font-bold">Balance Pendiente:</span> <span class="font-black text-red-600">RD$ <?= number_format($balancePendiente, 2) ?></span></div>
    </div>

    <?php if ($equipo['estado'] !== 'Vendida'): ?>
    <form action="" method="POST" class="space-y-5">
        <div>
            <label class="block text-xs font-black uppercase text-slate-400 mb-2">Tipo de Transacción</label>
            <div class="grid grid-cols-2 gap-3">
                <label class="border border-slate-200 p-3 rounded-xl flex items-center justify-center gap-2 font-bold text-sm cursor-pointer hover:bg-slate-50 active:scale-95 transition">
                    <input type="radio" name="tipo_pago" value="Total" checked onclick="document.getElementById('monto_input').value = '<?= $balancePendiente ?>'" class="text-blue-600"> Liquidar Total
                </label>
                <label class="border border-slate-200 p-3 rounded-xl flex items-center justify-center gap-2 font-bold text-sm cursor-pointer hover:bg-slate-50 active:scale-95 transition">
                    <input type="radio" name="tipo_pago" value="Abono" class="text-blue-600"> Registrar Abono
                </label>
            </div>
        </div>

        <div>
            <label for="monto_pago" class="block text-xs font-black uppercase text-slate-400 mb-2">Monto a Cobrar (RD$)</label>
            <div class="relative">
                <span class="absolute left-4 top-3.5 font-bold text-slate-400">RD$</span>
                <input type="number" step="0.01" min="1" max="<?= $balancePendiente ?>" name="monto_pago" id="monto_input" value="<?= $balancePendiente ?>" required
                       class="w-full bg-slate-50 border border-slate-200 rounded-2xl py-3 pl-14 pr-4 font-black text-slate-900 text-xl focus:outline-none focus:border-blue-500 focus:bg-white transition-all">
            </div>
        </div>

        <button type="submit" class="w-full bg-blue-700 hover:bg-blue-800 text-white font-black py-4 rounded-2xl shadow-lg transition-all flex items-center justify-center gap-2">
            <i class="fa-solid fa-cash-register"></i> Procesar e Imprimir Recibo
        </button>
    </form>
    <?php else: ?>
        <div class="text-center py-4">
            <a href="equipos_estado.php?estado=TODOS" class="bg-slate-800 text-white font-bold px-6 py-3 rounded-xl inline-block">Ir a Inventario General</a>
        </div>
    <?php endif; ?>

</div>

</body>
</html>