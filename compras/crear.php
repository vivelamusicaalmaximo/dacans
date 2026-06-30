<?php
session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

// 1. INCLUIR TU CONEXIÓN DE SQL SERVER
require_once '../config/conexion.php'; 

/* =========================================================
   GUARDAR
========================================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $cantidad = (float)($_POST['cantidad_articulos'] ?? 1);

    $costoDop       = (float)($_POST['costo_dop'] ?? 0);
    $impuestos      = (float)($_POST['costo_impuestos'] ?? 0);
    $envio          = (float)($_POST['costo_envio'] ?? 0);
    $incremento     = (float)($_POST['porcentaje_incremento'] ?? 0);

    /* COSTO TOTAL */
    $costoTotal = $costoDop + $impuestos + $envio;

    /* COSTO UNITARIO */
    $costoUnitario = $cantidad > 0 ? $costoTotal / $cantidad : 0;

    /* PRECIO SUGERIDO */
    $precioSugerido = $costoUnitario + ($costoUnitario * ($incremento / 100));

    /* GANANCIA */
    $gananciaItem = $precioSugerido - $costoUnitario;
    $gananciaLote = $gananciaItem * $cantidad;

    try {
        // 2. ADAPTAR LA CONSULTA CON PARÁMETROS NOMBRADOS PARA SQL SERVER
        $stmt = $pdo->prepare("
            INSERT INTO compras_articulos (
                item_id, nombre_articulo, cantidad_articulos, costo_usd, costo_dop,
                costo_impuestos, costo_envio, numero_rastreo_us, status_compra,
                direccion_usada, id_courier, costo_unitario, porcentaje_incremento,
                precio_sugerido, ganancia_por_item, ganancia_por_lote
            ) VALUES (
                :item_id, :nombre_articulo, :cantidad_articulos, :costo_usd, :costo_dop,
                :costo_impuestos, :costo_envio, :numero_rastreo_us, :status_compra,
                :direccion_usada, :id_courier, :costo_unitario, :porcentaje_incremento,
                :precio_sugerido, :ganancia_por_item, :ganancia_por_lote
            )
        ");

        // Ejecutar pasando un array asociativo
        $stmt->execute([
            ':item_id'               => $_POST['item_id'] ?? null,
            ':nombre_articulo'       => $_POST['nombre_articulo'],
            ':cantidad_articulos'    => $cantidad,
            ':costo_usd'             => (float)($_POST['costo_usd'] ?? 0),
            ':costo_dop'             => $costoDop,
            ':costo_impuestos'       => $impuestos,
            ':costo_envio'           => $envio,
            ':numero_rastreo_us'     => $_POST['numero_rastreo_us'] ?? null,
            ':status_compra'         => $_POST['status_compra'] ?? null,
            ':direccion_usada'       => $_POST['direccion_usada'] ?? null,
            ':id_courier'            => $_POST['id_courier'] ?? null,
            ':costo_unitario'        => $costoUnitario,
            ':porcentaje_incremento' => $incremento,
            ':precio_sugerido'       => $precioSugerido,
            ':ganancia_por_item'     => $gananciaItem,
            ':ganancia_por_lote'     => $gananciaLote
        ]);

        header("Location: index.php");
        exit;

    } catch (PDOException $e) {
        die("Error al guardar en SQL Server: " . $e->getMessage());
    }
} // Cierre del IF del POST
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Compra</title>
    <link rel="shortcut icon" href="/img/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
    body {
        background:
            radial-gradient(circle at top left, rgba(37, 99, 235, .08), transparent 30%),
            radial-gradient(circle at bottom right, rgba(14, 165, 233, .08), transparent 30%),
            #f8fafc;
        font-family: Arial, sans-serif;
    }

    .card {
        background: white;
        border-radius: 32px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 30px rgba(15, 23, 42, .05), 0 2px 10px rgba(15, 23, 42, .03);
    }

    .input {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 18px;
        padding: 14px 16px;
        font-size: 14px;
        outline: none;
        transition: .2s;
    }

    .input:focus {
        border-color: #2563eb;
        box-shadow: 0 0 0 4px rgba(37, 99, 235, .10);
    }

    .label {
        font-size: 13px;
        font-weight: 700;
        color: #475569;
        margin-bottom: 8px;
        display: block;
    }
    </style>
</head>

<body class="p-4 md:p-8">

    <div class="max-w-7xl mx-auto">

        <div class="flex flex-col lg:flex-row justify-between items-center gap-5 mb-8">
            <div class="flex items-center gap-4">
                <img src="../img/logo.webp" class="h-20 bg-white p-3 rounded-3xl shadow-lg border border-slate-200">
                <div>
                    <h1 class="text-4xl font-black text-slate-900">Nueva Compra</h1>
                    <p class="text-slate-500 mt-1">Registro de artículos y costos</p>
                </div>
            </div>
            <a href="index.php"
                class="bg-slate-900 hover:bg-black text-white px-6 py-4 rounded-2xl font-black shadow-lg transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Volver
            </a>
        </div>

        <div class="card p-8">
            <form method="POST">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

                    <div>
                        <label class="label">Item ID</label>
                        <input type="text" name="item_id" class="input">
                    </div>

                    <div class="md:col-span-2">
                        <label class="label">Nombre Artículo</label>
                        <input type="text" name="nombre_articulo" class="input" required>
                    </div>

                    <div>
                        <label class="label">Cantidad</label>
                        <input type="number" id="cantidad_articulos" name="cantidad_articulos" value="1" class="input">
                    </div>

                    <div>
                        <label class="label">Costo USD</label>
                        <input type="number" step="0.01" id="costo_usd" name="costo_usd" value="0" class="input">
                    </div>

                    <div>
                        <label class="label">Costo DOP <span id="tasa_info"
                                class="text-xs text-blue-500 font-normal"></span></label>
                        <input type="number" step="0.01" id="costo_dop" name="costo_dop" value="0" class="input">
                    </div>

                    <div>
                        <label class="label">Impuestos</label>
                        <input type="number" step="0.01" name="costo_impuestos" value="0" class="input">
                    </div>

                    <div>
                        <label class="label">Envío</label>
                        <input type="number" step="0.01" name="costo_envio" value="0" class="input">
                    </div>

                    <div>
                        <label class="label">% Incremento</label>
                        <input type="number" step="0.01" name="porcentaje_incremento" value="0" class="input">
                    </div>

                    <div>
                        <label class="label">Rastreo US</label>
                        <input type="text" name="numero_rastreo_us" class="input">
                    </div>

                    <div>
                        <label class="label">Status</label>
                        <select name="status_compra" class="input">
                            <option value="Ganado">Ganado</option>
                            <option value="Pagado">Pagado</option>
                            <option value="Enviado">Enviado</option>
                            <option value="Cancelado">Cancelado</option>
                            <option value="Entregado">Entregado</option>
                            <option value="Aduanas">Aduanas</option>
                            <option value="Listo para Recogida">Listo para Recogida</option>
                            <option value="Disponible">Disponible</option>
                        </select>
                    </div>

                    <div>
                        <label class="label">Dirección</label>
                        <select name="direccion_usada" class="input">
                            <option value="D01-050381 Daniel Candelario">Sin Dirección</option>
                            <option value="D01-050381 Daniel Candelario"> D01-050381 Daniel Candelario</option>
                            <option value="D01-064428 Sandra Solano"> D01-064428 Sandra Solano</option>
                            <option value="D01-117254 Silvia Guigni"> D01-117254 Silvia Guigni</option>
                            <option value="D01-117374 Yovanny Marquez"> D01-117374 Yovanny Marquez</option>
                            <option value="D01-097795 Sandra P Candelario Solano"> D01-097795 Sandra P Candelario Solano
                            </option>
                            <option value="D01-061860 Ramon S Medina"> D01-061860 Ramon S Medina</option>
                            <option value="D01-321309 Yensi Alexander Pena"> D01-321309 Yensi Alexander Pena</option>
                            <option value="D01-320879 Aurelyna Marys Collado"> D01-320879 Aurelyna Marys Collado
                            </option>
                            <option value="D01-320863 Yunely Alexandra Pena"> D01-320863 Yunely Alexandra Pena</option>
                            <option value="D01-321311 Angela Melo"> D01-321311 Angela Melo</option>
                            <option value="D01-102832 Ronald Taveras"> D01-102832 Ronald Taveras</option>
                            <option value="D01-097791 Luis Rivera Perez"> D01-097791 Luis Rivera Perez</option>
                            <option value="D01-083401 Santa Amador Pineda"> D01-083401 Santa Amador Pineda</option>
                            <option value="D01-064429 Daniel Candelario Pena"> D01-064429 Daniel Candelario Pena
                            </option>
                            <option value="D01-570395 Pablo Fernandez"> D01-570395 Pablo Fernandez</option>
                        </select>
                    </div>

                    <div>
                        <label class="label">ID Courier</label>
                        <input type="text" name="id_courier" class="input">
                    </div>
                </div>

                <div class="flex flex-wrap gap-4 mt-8">
                    <button type="submit"
                        class="bg-blue-700 hover:bg-blue-800 text-white px-8 py-4 rounded-2xl font-black shadow-lg transition">
                        <i class="fa-solid fa-floppy-disk mr-2"></i> Guardar Compra
                    </button>
                    <a href="index.php"
                        class="bg-slate-200 hover:bg-slate-300 text-slate-800 px-8 py-4 rounded-2xl font-black transition">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const inputUsd = document.getElementById('costo_usd');
        const inputDop = document.getElementById('costo_dop');
        const tasaInfo = document.getElementById('tasa_info');

        let tasaCambio = 0;

        // 1. Obtener la tasa de cambio del día al cargar la página
        async function obtenerTasaCambio() {
            try {
                // API pública y gratuita (USD a DOP)
                const respuesta = await fetch('https://open.er-api.com/v6/latest/USD');
                if (!respuesta.ok) throw new Error('Error al conectar con la API');

                const datos = await respuesta.json();
                tasaCambio = datos.rates.DOP;

                // Mostrar la tasa al usuario de forma sutil
                tasaInfo.textContent = `(Tasa: RD$${tasaCambio.toFixed(2)})`;
            } catch (error) {
                console.error('No se pudo obtener la tasa automatizada:', error);
                // Tasa de respaldo por si falla el internet o la API
                tasaCambio = 60.50;
                tasaInfo.textContent = `(Tasa estática: RD$${tasaCambio.toFixed(2)})`;
            }
        }

        // 2. Escuchar lo que escribe el usuario en el campo USD
        inputUsd.addEventListener('input', function() {
            const valorUsd = parseFloat(inputUsd.value) || 0;
            if (tasaCambio > 0) {
                // Calcular y fijar a 2 decimales
                inputDop.value = (valorUsd * tasaCambio).toFixed(2);
            }
        });

        // Inicializar la consulta de la tasa
        obtenerTasaCambio();
    });
    </script>
</body>

</html>