<?php

session_start();

if (!isset($_SESSION['admin_logueado'])) {

    header("Location: ../login.php");
    exit;
}

/* SOLO ADMIN Y SUPERADMIN */

$rolSesion = $_SESSION['rol'] ?? 'invitado';

if (

    $rolSesion !== 'admin' &&
    $rolSesion !== 'superadmin'

) {

    die("No tienes permisos para eliminar artículos.");
}

/* CONEXION SQL SERVER */

require '../config/conexion.php';

/* VALIDAR ID */

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {

    die("ID inválido.");
}

/* VERIFICAR EXISTENCIA */

$stmt = $pdo->prepare("

    SELECT id
    FROM compras_articulos
    WHERE id = ?

");

$stmt->execute([$id]);

$articulo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$articulo) {

    die("Artículo no encontrado.");
}

/* ELIMINAR */

$delete = $pdo->prepare("

    DELETE FROM compras_articulos
    WHERE id = ?

");

$delete->execute([$id]);

/* REDIRECT */

header("Location: index.php?eliminado=1");
exit;

?>