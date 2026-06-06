<?php

session_start();

if (!isset($_SESSION['admin_logueado'])) {

    header("Location: ../login.php");
    exit;
}

if ($_SESSION['rol'] === 'empleado') {

    echo "No tienes permisos para eliminar equipos.";
    exit;
}

/* ======================================================
   CONEXION SQL SERVER
====================================================== */

require '../config/conexion.php';

try {

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (Exception $e) {

    die("Error de conexión: " . $e->getMessage());
}

/*
|--------------------------------------------------------------------------
| VALIDAR ID
|--------------------------------------------------------------------------
*/

if (!isset($_GET['id']) || empty($_GET['id'])) {

    die("ID no válido");
}

$id = (int) $_GET['id'];

/*
|--------------------------------------------------------------------------
| OBTENER ACCESORIO
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT TOP 1 *
    FROM accesorios
    WHERE id = ?
");

$stmt->execute([$id]);

$accesorio = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$accesorio) {

    die("Accesorio no encontrado");
}

/*
|--------------------------------------------------------------------------
| ELIMINAR
|--------------------------------------------------------------------------
*/

try {

    $delete = $pdo->prepare("
        DELETE FROM accesorios
        WHERE id = ?
    ");

    $delete->execute([$id]);

    header("Location: view.php?deleted=1");
    exit;

} catch (Exception $e) {

    die("Error al eliminar: " . $e->getMessage());
}

?>