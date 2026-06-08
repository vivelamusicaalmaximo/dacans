<?php
// webhook_pago.php
// include 'conexion.php';

// Nota: Dependiendo de la pasarela, los datos te llegarán por $_POST, $_GET o un JSON crudo (php://input).
// Usaremos un ejemplo estándar por $_POST:
$id_venta_pasarela = $_POST['referencia'] ?? null; // El ID de venta que les mandaste antes
$estado_pasarela   = $_POST['estado_pago'] ?? '';   // Ej: 'APPROVED', 'SUCCESS', '1'

// 1. Verificar que la pasarela nos confirme que el pago fue realmente aprobado
if ($id_venta_pasarela && ($estado_pasarela === 'APPROVED' || $estado_pasarela === 'SUCCESS')) {
    
    try {
        $pdo->beginTransaction();

        // 2. Buscar qué producto (equipo) está amarrado a esta venta
        $sqlBuscarVenta = "SELECT id_producto FROM ventas WHERE id_venta = ? AND estado_pago = 'Pendiente'";
        $stmtBuscar = $pdo->prepare($sqlBuscarVenta);
        $stmtBuscar->execute([$id_venta_pasarela]);
        $venta = $stmtBuscar->fetch();

        if ($venta) {
            $id_producto = $venta['id_producto'];

            // 3. ACTUALIZAR LA VENTA A COMPLETADA
            $sqlUpdateVenta = "UPDATE ventas SET estado_pago = 'Pagado' WHERE id_venta = ?";
            $pdo->prepare($sqlUpdateVenta)->execute([$id_venta_pasarela]);

            // 4. ¡AHORA SÍ! EL EQUIPO QUEDA MARCADO COMO VENDIDO OFICIALMENTE
            $sqlUpdateProd = "UPDATE productos SET estado = 'Vendida' WHERE id_local = ?";
            $pdo->prepare($sqlUpdateProd)->execute([$id_producto]);

            $pdo->commit();
            
            // Le respondemos un código 200 a la pasarela para decirle que procesamos la notificación correctamente
            http_response_code(200);
            echo "Inventario actualizado y pago procesado con éxito.";
        } else {
            // La venta ya estaba pagada o no existe
            $pdo->rollBack();
            http_response_code(400);
            echo "Venta no encontrada o ya procesada.";
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        // Guardar error en un log para revisar qué pasó
        error_log("Error en Webhook: " . $e->getMessage());
        http_response_code(500); 
    }

} else {
    // Si el pago fue rechazado o faltan datos, no tocamos nada
    http_response_code(400);
    echo "Notificación inválida o pago rechazado.";
}