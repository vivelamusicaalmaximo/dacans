<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('max_execution_time', 300); // Damos 5 minutos al script por si las descargas van lento

if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config/conexion.php';

echo "<h2>🌐 Reorganización y Descarga Automática de Imágenes (Estado: Lista)</h2>";
echo "<p>Descargando imágenes externas de internet y organizando carpetas locales...</p>";
echo "<hr>";

// Función auxiliar para descargar archivos por HTTP/HTTPS de forma segura
function descargarImagenExterna($url, $ruta_destino) {
    // Limpiar posibles residuos de rutas relativas como "../https://"
    if (strpos($url, '../http') === 0) {
        $url = substr($url, 3);
    }
    
    // Configurar contexto para simular un navegador (evita bloqueos de servidores como Dell/Lenovo)
    $options = [
        "http" => [
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n"
        ]
    ];
    $context = stream_context_create($options);
    
    $contenido = @file_get_contents($url, false, $context);
    if ($contenido !== false) {
        return file_put_contents($ruta_destino, $contenido) !== false;
    }
    return false;
}

try {
    $query = $pdo->query("
        SELECT p.id_local, p.imagen_url, p.imagenes_adicionales, p.estado
        FROM productos_informatica p
        WHERE p.estado = 'Lista'
    ");
    $equipos = $query->fetchAll(PDO::FETCH_ASSOC);

    $total_equipos = count($equipos);
    $total_modificados = 0;
    $total_saltados = 0;

    echo "<p><strong>Total de equipos 'Lista' a procesar:</strong> $total_equipos</p>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%; font-family: sans-serif; font-size: 14px;'>";
    echo "<tr style='background: #f2f2f2;'><th>ID Local</th><th>Resultado del Proceso</th></tr>";

    foreach ($equipos as $equipo) {
        $id_local = trim($equipo['id_local']);
        $nueva_carpeta = '../uploads/' . $id_local . '/';
        
        $nueva_imagen_principal = $equipo['imagen_url'];
        $nuevas_fotos_adicionales = [];
        $hubo_cambios = false;
        
        if (empty($equipo['imagen_url']) && empty($equipo['imagenes_adicionales'])) {
            echo "<tr><td><strong>{$id_local}</strong></td><td style='color: gray;'>Sin imágenes registradas.</td></tr>";
            $total_saltados++;
            continue;
        }

        // --- 1. PROCESAR IMAGEN PRINCIPAL ---
        if (!empty($equipo['imagen_url'])) {
            $url_principal = trim($equipo['imagen_url']);

            // ¿Es una URL de Internet (contiene http)?
            if (strpos($url_principal, 'http') !== false) {
                if (!is_dir($nueva_carpeta)) { mkdir($nueva_carpeta, 0755, true); }
                
                // Extraer el nombre original del archivo eliminando parámetros raros de URL (?v=..., ?width=...)
                $url_limpia = explode('?', basename($url_principal))[0];
                $nombre_archivo = !empty($url_limpia) ? $url_limpia : 'principal.png';
                $ruta_destino = $nueva_carpeta . $nombre_archivo;

                if (descargarImagenExterna($url_principal, $ruta_destino)) {
                    $nueva_imagen_principal = '../uploads/' . $id_local . '/' . $nombre_archivo;
                    $hubo_cambios = true;
                } else {
                    echo "<tr><td><strong>{$id_local}</strong></td><td style='color: red;'>❌ Error al descargar imagen de internet: <code>{$url_principal}</code></td></tr>";
                    $total_saltados++;
                    continue; // Saltamos este equipo si falla la imagen principal
                }
            } 
            // Si no es internet, evaluar si es un archivo local ya existente en la raíz vieja
            else {
                $ruta_origen = '../' . str_replace(['\\', '../'], ['/', ''], $url_principal);
                if (strpos($url_principal, $id_local) === false && file_exists($ruta_origen) && is_file($ruta_origen)) {
                    if (!is_dir($nueva_carpeta)) { mkdir($nueva_carpeta, 0755, true); }
                    $nombre_archivo = basename($ruta_origen);
                    $ruta_destino = $nueva_carpeta . $nombre_archivo;

                    if (copy($ruta_origen, $ruta_destino)) {
                        $nueva_imagen_principal = '../uploads/' . $id_local . '/' . $nombre_archivo;
                        $hubo_cambios = true;
                    }
                }
            }
        }

        // --- 2. PROCESAR IMÁGENES ADICIONALES ---
        if (!empty($equipo['imagenes_adicionales'])) {
            $fotos_adicionales = explode(',', $equipo['imagenes_adicionales']);

            foreach ($fotos_adicionales as $foto_adicional) {
                $url_adicional = trim($foto_adicional);

                if (strpos($url_adicional, 'http') !== false) {
                    if (!is_dir($nueva_carpeta)) { mkdir($nueva_carpeta, 0755, true); }
                    
                    $url_limpia_ad = explode('?', basename($url_adicional))[0];
                    $nombre_archivo_ad = !empty($url_limpia_ad) ? $url_limpia_ad : 'adicional_' . uniqid() . '.png';
                    $ruta_destino_ad = $nueva_carpeta . $nombre_archivo_ad;

                    if (descargarImagenExterna($url_adicional, $ruta_destino_ad)) {
                        $nuevas_fotos_adicionales[] = '../uploads/' . $id_local . '/' . $nombre_archivo_ad;
                        $hubo_cambios = true;
                    } else {
                        $nuevas_fotos_adicionales[] = $foto_adicional; // Mantener original si falla
                    }
                } else {
                    // Lógica local para adicionales
                    $ruta_origen_ad = '../' . str_replace(['\\', '../'], ['/', ''], $url_adicional);
                    if (strpos($url_adicional, $id_local) === false && file_exists($ruta_origen_ad) && is_file($ruta_origen_ad)) {
                        if (!is_dir($nueva_carpeta)) { mkdir($nueva_carpeta, 0755, true); }
                        $nombre_archivo_ad = basename($ruta_origen_ad);
                        $ruta_destino_ad = $nueva_carpeta . $nombre_archivo_ad;

                        if (copy($ruta_origen_ad, $ruta_destino_ad)) {
                            $nuevas_fotos_adicionales[] = '../uploads/' . $id_local . '/' . $nombre_archivo_ad;
                            $hubo_cambios = true;
                        } else {
                            $nuevas_fotos_adicionales[] = $foto_adicional;
                        }
                    } else {
                        $nuevas_fotos_adicionales[] = $foto_adicional;
                    }
                }
            }
        }

        // --- 3. GUARDAR CAMBIOS EN BASE DE DATOS ---
        if ($hubo_cambios) {
            $imagenes_adicionales_string = !empty($nuevas_fotos_adicionales) ? implode(',', $nuevas_fotos_adicionales) : null;

            $stmtUpdate = $pdo->prepare("
                UPDATE productos_informatica 
                SET imagen_url = ?, imagenes_adicionales = ? 
                WHERE id_local = ?
            ");
            $stmtUpdate->execute([$nueva_imagen_principal, $imagenes_adicionales_string, $id_local]);

            echo "<tr><td><strong>{$id_local}</strong></td><td style='color: green;'>📥 Descargada, carpeta creada y base de datos actualizada.</td></tr>";
            $total_modificados++;
        } else {
            echo "<tr><td><strong>{$id_local}</strong></td><td style='color: blue;'>ℹ️ Ya se encontraba almacenada localmente.</td></tr>";
            $total_saltados++;
        }
    }

    echo "</table>";
    echo "<hr>";
    echo "<h3>📊 Resumen de Descargas:</h3>";
    echo "<ul>";
    echo "<li><strong>Total analizados:</strong> $total_equipos</li>";
    echo "<li><strong>Imágenes descargadas e introducidas a sus carpetas:</strong> $total_modificados</li>";
    echo "<li><strong>Omitidos (No requirieron cambios):</strong> $total_saltados</li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "</table>";
    echo "<br><span style='color:red;'>❌ Error en el proceso:</span> " . $e->getMessage();
}
?>