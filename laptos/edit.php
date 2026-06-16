<?php

session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

$rolSesion = $_SESSION['rol'] ?? 'empleado';

/* ======================================================
   CONEXION SQL SERVER
====================================================== */
require_once '../config/conexion.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error DB: " . $e->getMessage());
}

/* ======================================================
   FUNCION LOG EDITAR
====================================================== */
function registrarLogEditar($pdo, $equipo_id, $datosAntes, $datosDespues) {
    $usuario = $_SESSION['usuario'] ?? 'Desconocido';
    $rol = $_SESSION['rol'] ?? 'Sin rol';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'IP desconocida';

    /* =========================================
       DETECTAR CAMBIOS
    ========================================= */
    $cambios = [];
    foreach ($datosDespues as $campo => $valorNuevo) {
        if ($campo === 'imagenes_adicionales') continue; 

        $valorAntes = $datosAntes[$campo] ?? '';
        if ($valorAntes != $valorNuevo) {
            $cambios[] = ucfirst($campo) . ": '" . $valorAntes . "' → '" . $valorNuevo . "'";
        }
    }

    /* =========================================
       DESCRIPCION FINAL
    ========================================= */
    if (count($cambios) > 0) {
        $descripcion = "Se modificó el equipo ID {$equipo_id}. Cambios: " . implode(' | ', $cambios);
    } else {
        $descripcion = "Se editó el equipo ID {$equipo_id} sin cambios.";
    }

    /* =========================================
       INSERTAR LOG
    ========================================= */
    $stmt = $pdo->prepare("
        INSERT INTO logs_sistema (
            usuario, rol, accion, equipo_id, descripcion, ip
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([$usuario, $rol, 'EDITAR', $equipo_id, $descripcion, $ip]);
}

$mensaje = "";
$error = "";

/* ======================================================
   VALIDAR ID
====================================================== */
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("ID no válido.");
}

$id_local = $_GET['id'];

/* ======================================================
   LOGICA ADICIONAL: ELIMINAR UNA IMAGEN SELECCIONADA
====================================================== */
if (isset($_POST['eliminar_imagen_galeria'])) {
    try {
        $img_a_eliminar = trim($_POST['imagen_ruta']);
        
        // Obtener el estado actual de las imágenes
        $stmtImg = $pdo->prepare("SELECT imagenes_adicionales FROM productos_informatica WHERE id_local = ?");
        $stmtImg->execute([$id_local]);
        $resImg = $stmtImg->fetch(PDO::FETCH_ASSOC);
        
        if ($resImg && !empty($resImg['imagenes_adicionales'])) {
            $galeria_actual = explode(',', $resImg['imagenes_adicionales']);
            
            // Filtrar el array para remover la imagen seleccionada
            $nueva_galeria = array_filter($galeria_actual, function($item) use ($img_a_eliminar) {
                return trim($item) !== $img_a_eliminar;
            });
            
            $nuevo_string_galeria = !empty($nueva_galeria) ? implode(',', array_map('trim', $nueva_galeria)) : null;
            
            // Actualizar la base de datos inmediatamente
            $stmtUpdateImg = $pdo->prepare("UPDATE productos_informatica SET imagenes_adicionales = ? WHERE id_local = ?");
            $stmtUpdateImg->execute([$nuevo_string_galeria, $id_local]);
            
            // Eliminar el archivo físico de la carpeta uploads si no es una URL externa
            if (strpos($img_a_eliminar, '../uploads/') === 0) {
                $archivo_fisico = __DIR__ . '/../' . str_replace('../', '', $img_a_eliminar);
                if (file_exists($archivo_fisico)) {
                    unlink($archivo_fisico);
                }
            }
            
            $mensaje = "Imagen eliminada correctamente del servidor y la galería.";
        }
    } catch (Exception $e) {
        $error = "Error al eliminar la imagen: " . $e->getMessage();
    }
}

/* ======================================================
   ACTUALIZAR EQUIPO
====================================================== */
if (isset($_POST['actualizar'])) {
    try {
        /* =========================================
            DATOS ANTES DEL UPDATE
        ========================================= */
        $stmtOld = $pdo->prepare("SELECT * FROM productos_informatica WHERE id_local = ?");
        $stmtOld->execute([$_POST['id_local']]);
        $antes = $stmtOld->fetch(PDO::FETCH_ASSOC);

        if (!$antes) {
            throw new Exception("El equipo a actualizar no existe en la base de datos.");
        }

        /* =========================================
            FECHA AUTOMATICA VENDIDA
        ========================================= */
        $vendida_at = null;
        if ($_POST['estado'] === 'Vendida') {
            $vendida_at = date('Y-m-d H:i:s');
        } elseif (!empty($_POST['vendida_at'])) {
            $vendida_at = $_POST['vendida_at'];
        }

        /* ======================================================
           PROCESAMIENTO DE IMÁGENES (SUBIDA LOCAL FÍSICA SEGURA)
        ====================================================== */
        $directorio_subida = __DIR__ . '/../uploads/';

        if (!is_dir($directorio_subida)) {
            mkdir($directorio_subida, 0777, true);
        }

        $fotos_subidas = [];

        if (!empty($_FILES['fotos_propias']['name']) && !empty($_FILES['fotos_propias']['name'][0])) {
            foreach ($_FILES['fotos_propias']['name'] as $key => $name) {
                if (trim($name) === '') continue;

                $tmp_name = $_FILES['fotos_propias']['tmp_name'][$key];
                $err_code = $_FILES['fotos_propias']['error'][$key];

                if ($err_code === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    
                    $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (!in_array($ext, $extensiones_permitidas)) {
                        continue; 
                    }

                    $nuevo_nombre = 'eq_' . uniqid() . '_' . $key . '.' . $ext;
                    $ruta_destino = $directorio_subida . $nuevo_nombre;

                    if (copy($tmp_name, $ruta_destino)) {
                        unlink($tmp_name); 
                        @chmod($ruta_destino, 0666); 
                        
                        $fotos_subidas[] = '../uploads/' . $nuevo_nombre;
                    }
                }
            }
        }
        
        $imagen_principal = !empty($_POST['imagen_url']) ? trim($_POST['imagen_url']) : ($antes['imagen_url'] ?? null);
        
        if (!empty($fotos_subidas)) {
            if (empty($imagen_principal)) {
                $imagen_principal = $fotos_subidas[0];
                array_shift($fotos_subidas); 
            }

            if (!empty($antes['imagenes_adicionales'])) {
                $imagenes_existentes = explode(',', $antes['imagenes_adicionales']);
                $fotos_finales_carrusel = array_merge($imagenes_existentes, $fotos_subidas);
            } else {
                $fotos_finales_carrusel = $fotos_subidas;
            }
            $fotos_finales_carrusel = array_filter(array_map('trim', $fotos_finales_carrusel));
            $imagenes_adicionales_string = implode(',', $fotos_finales_carrusel);
        } else {
            $imagenes_adicionales_string = !empty($antes['imagenes_adicionales']) ? $antes['imagenes_adicionales'] : null;
        }

        /* =========================================
            UPDATE EJECUCIÓN
        ========================================= */
        $sql = "UPDATE productos_informatica SET
                    serie = ?,
                    proc_marca = ?,
                    proc_familia = ?,
                    proc_generacion = ?,
                    proc_modelo = ?,
                    graficos = ?,
                    g_expandible = ?,
                    memoria = ?,
                    disco = ?,
                    pantalla = ?,
                    p_resolucion = ?,
                    touch = ?,
                    precio = ?,
                    estado = ?,
                    comenta = ?,
                    equipo_marca = ?,
                    equipo_modelo = ?,
                    imagen_url = ?,
                    imagenes_adicionales = ?,
                    vendida_at = ?,
                    clase = ?
                WHERE id_local = ?";

        $stmt = $pdo->prepare($sql);
      $precio_limpio = !empty($_POST['precio']) ? (float)str_replace(',', '', $_POST['precio']) : 0;

        $stmt->execute([
            !empty($_POST['serie']) ? trim($_POST['serie']) : null,
            !empty($_POST['proc_marca']) ? trim($_POST['proc_marca']) : null,
            !empty($_POST['proc_familia']) ? trim($_POST['proc_familia']) : null,
            !empty($_POST['proc_generacion']) ? trim($_POST['proc_generacion']) : null,
            !empty($_POST['proc_modelo']) ? trim($_POST['proc_modelo']) : null,
            !empty($_POST['graficos']) ? trim($_POST['graficos']) : null,
            isset($_POST['g_expandible']) ? (int)$_POST['g_expandible'] : 0,
            !empty($_POST['memoria']) ? trim($_POST['memoria']) : null,
            !empty($_POST['disco']) ? trim($_POST['disco']) : null,
            !empty($_POST['pantalla']) ? trim($_POST['pantalla']) : null,
            !empty($_POST['p_resolucion']) ? trim($_POST['p_resolucion']) : null,
            isset($_POST['touch']) ? (int)$_POST['touch'] : 0,
            $precio_limpio,
            !empty($_POST['estado']) ? trim($_POST['estado']) : 'Lista',
            !empty($_POST['comenta']) ? trim($_POST['comenta']) : null,
            !empty($_POST['equipo_marca']) ? trim($_POST['equipo_marca']) : null,
            !empty($_POST['equipo_modelo']) ? trim($_POST['equipo_modelo']) : null,
            $imagen_principal,
            $imagenes_adicionales_string,
            $vendida_at,
            $_POST['clase'] ?? null,
            $_POST['id_local']
        ]);

        $despues = [
            'serie'           => $_POST['serie'] ?? null,
            'proc_marca'      => $_POST['proc_marca'] ?? null,
            'proc_familia'    => $_POST['proc_familia'] ?? null,
            'proc_generacion' => $_POST['proc_generacion'] ?? null,
            'proc_modelo'     => $_POST['proc_modelo'] ?? null,
            'graficos'        => $_POST['graficos'] ?? null,
            'g_expandible'    => $_POST['g_expandible'] ?? 0,
            'memoria'         => $_POST['memoria'] ?? null,
            'disco'           => $_POST['disco'] ?? null,
            'pantalla'        => $_POST['pantalla'] ?? null,
            'p_resolucion'    => $_POST['p_resolucion'] ?? null,
            'touch'           => $_POST['touch'] ?? 0,
            'precio'          => $precio_limpio,
            'estado'          => $_POST['estado'] ?? null,
            'comenta'         => $_POST['comenta'] ?? null,
            'equipo_marca'    => $_POST['equipo_marca'] ?? null,
            'equipo_modelo'   => $_POST['equipo_modelo'] ?? null,
            'imagen_url'      => $imagen_principal,
            'imagenes_adicionales' => $imagenes_adicionales_string,
            'vendida_at'      => $vendida_at,
            'clase'           => $_POST['clase'] ?? null
        ];

        registrarLogEditar($pdo, $_POST['id_local'], $antes, $despues);
        $mensaje = "Equipo actualizado correctamente.";

    } catch (Exception $e) {
        $error = "Error al actualizar: " . $e->getMessage();
    }
}

/* ======================================================
   OBTENER REGISTRO (RECARGA ACTUALIZADA)
====================================================== */
$stmt = $pdo->prepare("SELECT * FROM productos_informatica WHERE id_local = ?");
$stmt->execute([$id_local]);
$e = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$e) {
    die("Equipo no encontrado.");
}

$e = $e ?: [];
$e['g_expandible'] = $e['g_expandible'] ?? '';
$e['touch']        = $e['touch'] ?? '';
$e['estado']       = $e['estado'] ?? '';
$e['vendida_at']   = $e['vendida_at'] ?? '';
$e['imagenes_adicionales'] = $e['imagenes_adicionales'] ?? '';

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Editar Equipo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="shortcut icon" href="/img/favicon.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-slate-100 min-h-screen p-4 sm:p-8">

    <div class="max-w-5xl mx-auto">

        <div class="flex flex-col sm:flex-row justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-black text-blue-900 uppercase italic">Editar Equipo</h1>
                <p class="text-slate-500 text-sm mt-1">ID: <?= $e['id_local'] ?></p>
            </div>
            <div class="flex gap-3">
                <a href="index.php"
                    class="bg-slate-200 hover:bg-slate-300 transition px-5 py-3 rounded-2xl font-bold text-sm">
                    <i class="fa-solid fa-arrow-left mr-2"></i> Volver
                </a>
                <a href="eliminar.php?id=<?= urlencode($e['id_local']) ?>"
                    onclick="return confirm('¿Eliminar este equipo?')"
                    class="bg-red-500 hover:bg-red-600 transition text-white px-5 py-3 rounded-2xl font-bold text-sm">
                    <i class="fa-solid fa-trash mr-2"></i> Eliminar
                </a>
            </div>
        </div>

        <?php if ($mensaje): ?>
        <div class="bg-green-600 text-white p-4 rounded-2xl mb-6 font-bold"><?= $mensaje ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="bg-red-500 text-white p-4 rounded-2xl mb-6 font-bold"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data"
            class="bg-white rounded-[2rem] shadow-xl p-5 sm:p-8 border border-slate-200">

            <input type="hidden" id="vendida_at" name="vendida_at" value="<?= $e['vendida_at'] ?? '' ?>">

            <button type="submit" name="actualizar"
                class="w-full bg-blue-900 hover:bg-black transition text-white py-4 rounded-2xl font-black text-lg shadow-xl mb-8">
                <i class="fa-solid fa-floppy-disk mr-2"></i> GUARDAR CAMBIOS
            </button>

            <input type="hidden" name="id_local" value="<?= $e['id_local'] ?>">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="text-xs font-black uppercase text-slate-400">ID Local</label>
                    <input type="text" value="<?= $e['id_local'] ?>" readonly
                        class="w-full p-3 rounded-2xl bg-slate-100 border border-slate-200 font-black text-blue-700">
                </div>

                <div>
                    <label class="text-xs font-black uppercase text-slate-400">Serie</label>
                    <input type="text" name="serie" value="<?= $e['serie'] ?? '' ?>"
                        class="w-full p-3 rounded-2xl border border-slate-200">
                </div>

                <div>
                    <label class="text-xs font-black uppercase text-slate-400">Marca</label>
                    <input type="text" name="equipo_marca" value="<?= $e['equipo_marca'] ?? '' ?>"
                        class="w-full p-3 rounded-2xl border border-slate-200">
                </div>

                <div>
                    <label class="text-xs font-black uppercase text-slate-400">Modelo</label>
                    <input type="text" name="equipo_modelo" value="<?= $e['equipo_modelo'] ?? '' ?>"
                        class="w-full p-3 rounded-2xl border border-slate-200">
                </div>

                <div>
                    <label class="text-xs font-black uppercase text-slate-400">Marca CPU</label>
                    <input type="text" name="proc_marca" value="<?= $e['proc_marca'] ?? '' ?>"
                        class="w-full p-3 rounded-2xl border border-slate-200">
                </div>

                <div>
                    <label class="text-xs font-black uppercase text-slate-400">Familia CPU</label>
                    <input type="text" name="proc_familia" value="<?= $e['proc_familia'] ?? '' ?>"
                        class="w-full p-3 rounded-2xl border border-slate-200">
                </div>

                <div>
                    <label class="text-xs font-black uppercase text-slate-400">Generación</label>
                    <input type="text" name="proc_generacion" value="<?= $e['proc_generacion'] ?? '' ?>"
                        class="w-full p-3 rounded-2xl border border-slate-200">
                </div>

                <div>
                    <label class="text-xs font-black uppercase text-slate-400">Modelo CPU</label>
                    <input type="text" name="proc_modelo" value="<?= $e['proc_modelo'] ?? '' ?>"
                        class="w-full p-3 rounded-2xl border border-slate-200">
                </div>

                <div>
                    <label class="text-xs font-black uppercase text-slate-400">Gráficos</label>
                    <input type="text" name="graficos" value="<?= $e['graficos'] ?? '' ?>"
                        class="w-full p-3 rounded-2xl border border-slate-200">
                </div>

                <div>
                    <label class="text-xs font-black uppercase text-slate-400">Tipo de GPU</label>
                    <select name="g_expandible" class="w-full p-3 rounded-2xl border border-slate-200">
                        <option value="0" <?= (($e['g_expandible'] ?? '') == 0) ? 'selected' : '' ?>>Integrada</option>
                        <option value="1" <?= (($e['g_expandible'] ?? '') == 1) ? 'selected' : '' ?>>APU Ajustable
                        </option>
                        <option value="2" <?= (($e['g_expandible'] ?? '') == 2) ? 'selected' : '' ?>>Dedicada</option>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-black uppercase text-slate-400">RAM</label>
                    <input type="text" name="memoria" value="<?= $e['memoria'] ?? '' ?>"
                        class="w-full p-3 rounded-2xl border border-slate-200">
                </div>

                <div>
                    <label class="text-xs font-black uppercase text-slate-400">Disco</label>
                    <input type="text" name="disco" value="<?= $e['disco'] ?? '' ?>"
                        class="w-full p-3 rounded-2xl border border-slate-200">
                </div>

                <div>
                    <label class="text-xs font-black uppercase text-slate-400">Pantalla</label>
                    <input type="text" name="pantalla" value="<?= $e['pantalla'] ?? '' ?>"
                        class="w-full p-3 rounded-2xl border border-slate-200">
                </div>

                <div>
                    <label class="text-xs font-black uppercase text-slate-400">Resolución</label>
                    <input type="text" name="p_resolucion" value="<?= $e['p_resolucion'] ?? '' ?>"
                        class="w-full p-3 rounded-2xl border border-slate-200">
                </div>

                <div>
                    <label class="text-xs font-black uppercase text-slate-400">Touch</label>
                    <select name="touch" class="w-full p-3 rounded-2xl border border-slate-200">
                        <option value="0" <?= (($e['touch'] ?? '') == 0) ? 'selected' : '' ?>>NO</option>
                        <option value="1" <?= (($e['touch'] ?? '') == 1) ? 'selected' : '' ?>>SI</option>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-black uppercase text-slate-400">Precio</label>
                    <input type="number" step="any" name="precio" value="<?= $e['precio'] ?? '' ?>"
                        class="w-full p-3 rounded-2xl border border-slate-200">
                </div>

                <div>
                    <label class="text-xs font-black uppercase text-slate-400">Estado</label>
                    <select id="estadoSelect" name="estado" class="w-full p-3 rounded-2xl border border-slate-200">
                        <option value="Lista" <?= (($e['estado'] ?? '') == 'Lista') ? 'selected' : '' ?>>Lista</option>
                        <option value="NO Lista" <?= (($e['estado'] ?? '') == 'NO Lista') ? 'selected' : '' ?>>NO Lista
                        </option>
                        <option value="Vendida" <?= (($e['estado'] ?? '') == 'Vendida') ? 'selected' : '' ?>>Vendida
                        </option>
                        <option value="En camino" <?= (($e['estado'] ?? '') == 'En camino') ? 'selected' : '' ?>>En
                            camino</option>
                        <option value="En revision" <?= (($e['estado'] ?? '') == 'En revision') ? 'selected' : '' ?>>En
                            revision</option>
                    </select>
                </div>

                <div>
                    <label class="text-xs font-black uppercase text-slate-400">Clase</label>
                    <select name="clase" class="w-full p-3 rounded-2xl border border-slate-200">
                        <option value="A" <?= (($e['clase'] ?? '') == 'A') ? 'selected' : '' ?>>A</option>
                        <option value="B" <?= (($e['clase'] ?? '') == 'B') ? 'selected' : '' ?>>B</option>
                        <option value="C" <?= (($e['clase'] ?? '') == 'C') ? 'selected' : '' ?>>C</option>

                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="text-xs font-black uppercase text-slate-400">URL Imagen (Web)</label>
                    <textarea name="imagen_url" rows="2"
                        class="w-full p-3 rounded-2xl border border-slate-200"><?= $e['imagen_url'] ?? '' ?></textarea>
                </div>

                <div class="md:col-span-2">
                    <label class="text-xs font-black uppercase text-slate-700 block mb-1">
                        Añadir Fotos Propias (Opcional) - <span class="text-blue-600 lowercase font-normal">Puedes
                            seleccionar varias para agregar al carrusel</span>
                    </label>
                    <input type="file" name="fotos_propias[]" multiple accept="image/*"
                        class="w-full p-3 rounded-2xl bg-slate-50 border border-dashed border-slate-300 text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-black file:uppercase file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                </div>

                <div class="md:col-span-2">
                    <label class="text-xs font-black uppercase text-slate-400">Comentario</label>
                    <textarea name="comenta" rows="5"
                        class="w-full p-3 rounded-2xl border border-slate-200"><?= $e['comenta'] ?? '' ?></textarea>
                </div>
            </div>

            <?php if (!empty($e['imagen_url']) || !empty($e['imagenes_adicionales'])): ?>
            <div class="mt-8 pt-6 border-t border-slate-100">
                <h4 class="text-xs font-black uppercase text-slate-400 mb-3">Imágenes actuales del equipo</h4>
                <div class="flex flex-wrap gap-4">
                    <?php if (!empty($e['imagen_url'])): ?>
                    <div class="relative group">
                        <span
                            class="absolute top-2 left-2 bg-blue-900 text-white text-[9px] font-black uppercase px-2 py-0.5 rounded-md shadow z-10">Portada</span>
                        <img src="<?= $e['imagen_url'] ?>" class="w-40 h-28 object-cover rounded-2xl border shadow-sm">
                    </div>
                    <?php endif; ?>

                    <?php 
                        if (!empty($e['imagenes_adicionales'])) {
                            $galeria = explode(',', $e['imagenes_adicionales']);
                            foreach ($galeria as $indice => $img_extra) {
                                $img_extra_trim = trim($img_extra);
                                if (!empty($img_extra_trim)) {
                                    ?>
                    <div class="relative group w-40 h-28">
                        <span
                            class="absolute top-2 left-2 bg-slate-700 text-white text-[9px] font-black uppercase px-2 py-0.5 rounded-md shadow z-10">
                            Foto <?= $indice + 1 ?>
                        </span>

                        <button type="submit" name="eliminar_imagen_galeria"
                            onclick="return confirm('¿Seguro que deseas eliminar permanentemente esta imagen de la galería?')"
                            class="absolute -top-2 -right-2 bg-red-500 hover:bg-red-600 text-white w-7 h-7 rounded-full flex items-center justify-center shadow-lg transition-transform transform scale-0 group-hover:scale-100 z-20">
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>

                        <input type="hidden" name="imagen_ruta" value="<?= htmlspecialchars($img_extra_trim) ?>">

                        <img src="<?= $img_extra_trim ?>"
                            class="w-full h-full object-cover rounded-2xl border shadow-sm group-hover:opacity-75 transition-opacity">
                    </div>
                    <?php
                                }
                            }
                        }
                        ?>
                </div>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <script>
    const estadoSelect = document.getElementById('estadoSelect');
    const vendidaInput = document.getElementById('vendida_at');

    estadoSelect.addEventListener('change', function() {
        if (this.value === 'Vendida') {
            const ahora = new Date();
            const fecha = ahora.getFullYear() + '-' +
                String(ahora.getMonth() + 1).padStart(2, '0') + '-' +
                String(ahora.getDate()).padStart(2, '0') + ' ' +
                String(ahora.getHours()).padStart(2, '0') + ':' +
                String(ahora.getMinutes()).padStart(2, '0') + ':' +
                String(ahora.getSeconds()).padStart(2, '0');
            vendidaInput.value = fecha;
        } else {
            vendidaInput.value = '';
        }
    });
    </script>
</body>

</html>