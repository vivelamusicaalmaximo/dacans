<?php
echo "Este metodo aun no se ha habilitado.";
exit;
// procesar_precompra.php
// include 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ... (Capturas tus datos del formulario y calculas el $monto_total_pagar)

    try {
        $pdo->beginTransaction();

        // 1. INSERTAR CLIENTE
        // $sqlInsertCliente = "INSERT ...";
        // $id_cliente = $pdo->lastInsertId();

        // 2. INSERTAR LA VENTA COMO PENDIENTE
        // Guardamos el id_local del equipo y el total con el envío incluido
        $sqlVenta = "INSERT INTO ventas (id_cliente, id_producto, total, estado_pago, fecha) 
                     VALUES (?, ?, ?, 'Pendiente', NOW())";
        $stmtVenta = $pdo->prepare($sqlVenta);
        $stmtVenta->execute([$id_cliente, $id_producto, $monto_total_pagar]);
        
        // Obtenemos el ID de esta venta para pasárselo a la pasarela como referencia única
        $id_venta = $pdo->lastInsertId();

        $pdo->commit();

        // 3. REDIRIGIR A LA PASARELA
        // Es VITAL enviar el $id_venta como parámetro de referencia (o 'trackId') 
        // para que la pasarela sepa devolvértelo cuando el pago se complete.
        header("Location: https://tu-pasarela.com/checkout?monto=" . $monto_total_pagar . "&referencia=" . $id_venta);
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}