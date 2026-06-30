<?php

session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

/* ======================================================
   VALIDAR ROL
====================================================== */
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
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

/* ======================================================
   VALIDAR ID
====================================================== */
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID no válido");
}

$id = trim($_GET['id']);

/* ======================================================
   VERIFICAR SI EXISTE
====================================================== */
$stmt = $pdo->prepare("
    SELECT TOP 1 id_local, comenta
    FROM productos_informatica
    WHERE id_local = ?
");
$stmt->execute([$id]);
$equipo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipo) {
    die("Equipo no encontrado");
}

/* ======================================================
   ELIMINAR (BORRADO LÓGICO VIA UPDATE)
====================================================== */
try {
    $usuario = $_SESSION['usuario'] ?? 'Admin';
    
    // En lugar de DELETE, hacemos un UPDATE para proteger la integridad referencial de las facturas
    $delete = $pdo->prepare("
        UPDATE productos_informatica
        SET estado = 'Eliminado',
            comenta = CONCAT(comenta, ' | Enviado a papelera por ', ?, ' el ', CONVERT(varchar, GETDATE(), 103))
        WHERE id_local = ?
    ");

    $delete->execute([$usuario, $id]);

    // Opcional: Si manejas la tabla logs_sistema en tu base de datos, puedes insertar el log aquí de forma idéntica.

    header("Location: index.php?deleted=1");
    exit;

} catch (PDOException $e) {
    die("Error al eliminar (Borrado Lógico): " . $e->getMessage());
}
?>