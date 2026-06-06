<?php
session_start();
header('Content-Type: application/json');

// 1. Validar sesión y que venga la petición correcta
if (!isset($_SESSION['admin_logueado']) || !isset($_POST['registrar_cliente_ajax'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
    exit;
}

require_once '../config/conexion.php';

try {
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $rnc_cedula = trim($_POST['rnc_cedula'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');

    // 2. Capturar el ID del usuario/cajero activo desde la sesión (Usa 1 por defecto si no existe)
    $id_usuario_activo = $_SESSION['id_usuario'] ?? 1; 

    if (empty($nombre) || empty($apellido)) {
        echo json_encode(['success' => false, 'message' => 'El nombre y apellido son obligatorios.']);
        exit;
    }

    // 3. CONSULTA CORREGIDA: Incluimos 'id_usuario' en los campos a insertar
    $sql = "INSERT INTO clientes (nombre, apellido, rnc_cedula, telefono, estado, creado_at, id_usuario) 
            VALUES (?, ?, ?, ?, 1, GETDATE(), ?)";
            
    $stmt = $pdo->prepare($sql);
    // Pasamos la variable $id_usuario_activo al final del array de ejecución
    $stmt->execute([$nombre, $apellido, $rnc_cedula, $telefono, $id_usuario_activo]);
    
    // Capturar el ID asignado al nuevo cliente por SQL Server
    $id_nuevo = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'id_cliente' => $id_nuevo
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}