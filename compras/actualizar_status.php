<?php

session_start();

if (!isset($_SESSION['admin_logueado'])) {
    http_response_code(403);
    exit('Acceso denegado');
}

require_once '../config/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido');
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = trim($_POST['status'] ?? '');

if ($id <= 0 || empty($status)) {
    http_response_code(400);
    exit('Datos inválidos');
}

try {

    $sql = "UPDATE compras_articulos 
            SET status_compra = ? 
            WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$status, $id]);

    echo 'OK';

} catch (PDOException $e) {

    http_response_code(500);
    echo 'Error: ' . $e->getMessage();

}