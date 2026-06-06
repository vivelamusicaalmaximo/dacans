<?php

$dbFile = '../catalogo_equipos.sqlite';

$pdo = new PDO("sqlite:" . $dbFile);

try {

    $pdo->exec("
        ALTER TABLE accesorios
        ADD COLUMN fecha_creado DATETIME DEFAULT CURRENT_TIMESTAMP
    ");

    echo "Columna agregada correctamente";

} catch (Exception $e) {

    echo "La columna ya existe o hubo un error";
}