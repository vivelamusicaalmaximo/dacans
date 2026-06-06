<?php
// conexion.php

$serverName = "DESKTOP-VS1Q3O5\SQLEXPRESS"; 
$database   = "catalogo_equipos";

try {
    $dsn = "sqlsrv:Server=$serverName;Database=$database;TrustServerCertificate=true";
    
    // Creamos la conexión por Windows Authentication
    $pdo = new PDO($dsn, "", ""); 
    
    // Configuración de errores y modo de obtención de datos por defecto (Objetos o Arrays Asociativos)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Si la conexión falla, detenemos la aplicación y mostramos el error
    die("Error crítico de conexión en la Base de Datos: " . $e->getMessage());
}

// Al terminar este archivo, la variable $pdo queda disponible para quien lo llame