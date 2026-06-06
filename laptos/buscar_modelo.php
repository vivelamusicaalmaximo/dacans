<?php
session_start();
if (!isset($_SESSION['admin_logueado'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once '../config/conexion.php';

$modelo = $_GET['modelo'] ?? '';

if (!empty($modelo)) {
    // Buscamos el equipo más reciente que coincida con el modelo
    $stmt = $pdo->prepare("
        SELECT TOP 1 
            equipo_marca, proc_marca, proc_familia, proc_generacion, 
            proc_modelo, graficos, g_expandible, memoria, disco, 
            pantalla, p_resolucion, touch, precio, imagen_url
        FROM productos_informatica 
        WHERE equipo_modelo = ? 
        ORDER BY id DESC
    ");
    $stmt->execute([$modelo]);
    $equipo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($equipo) {
        echo json_encode(['success' => true, 'data' => $equipo]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'No se encontraron coincidencias']);