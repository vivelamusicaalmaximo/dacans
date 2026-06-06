<?php
// Nombre del archivo de la base de datos
$dbFile = 'catalogo_equipos.sqlite';

try {
    // 1. Crear conexión (si el archivo no existe, SQLite lo crea automáticamente)
    $pdo = new PDO("sqlite:" . $dbFile);

    // Configurar para que lance excepciones en caso de error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Definir el script SQL de creación
    $sql = "CREATE TABLE IF NOT EXISTS productos_informatica (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        serie TEXT NOT NULL,
        proc_marca TEXT,        -- PROC-MAI
        proc_familia TEXT,      -- PROC-FAM
        proc_generacion TEXT,   -- PROC-G
        proc_modelo TEXT,       -- PROC-MO
        graficos TEXT,          -- GRAFICOS
        g_expandible INTEGER,   -- G-EXPAND (Booleano 0 o 1)
        memoria TEXT,           -- MEMORIA
        disco TEXT,             -- DISCO
        pantalla TEXT,          -- PANTALLA
        p_resolucion TEXT,      -- P-RESOLU
        touch INTEGER,          -- TOUCH (Booleano 0 o 1)
        precio REAL,            -- PRECIO
        estado TEXT,            -- ESTADO
        comenta TEXT            -- COMENTA
    )";

    // 3. Ejecutar el comando
    $pdo->exec($sql);

    echo "Base de datos y tabla creadas exitosamente en: " . $dbFile;
} catch (PDOException $e) {
    echo "Error en la conexión o creación: " . $e->getMessage();
}
