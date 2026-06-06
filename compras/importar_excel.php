<?php

session_start();

require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if (!isset($_SESSION['admin_logueado'])) {

    header("Location: ../login.php");
    exit;
}

/* =========================================================
   CONEXION SQL SERVER
========================================================= */

require '../config/conexion.php';

/* =========================================================
   IMPORTAR EXCEL
========================================================= */

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!empty($_FILES['excel']['tmp_name'])) {

        $archivo = $_FILES['excel']['tmp_name'];

        try {

            $spreadsheet = IOFactory::load($archivo);

            $sheet = $spreadsheet->getActiveSheet();

            $rows = $sheet->toArray();

            $insertados = 0;

            foreach ($rows as $index => $row) {

                /* SALTAR HEADER */
                if ($index === 0) {
                    continue;
                }

                $stmt = $pdo->prepare("

                    INSERT INTO compras_articulos (

                        item_id,
                        nombre_articulo,
                        cantidad_articulos,
                        costo_usd,
                        costo_dop,
                        costo_impuestos,
                        costo_envio,
                        numero_rastreo_us,
                        status_compra,
                        direccion_usada,
                        id_courier,
                        costo_unitario,
                        porcentaje_incremento

                    ) VALUES (

                        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?

                    )

                ");

               $stmt->execute([

    trim($row[0] ?? ''),
    trim($row[1] ?? ''),

    (int) str_replace(',', '', $row[2] ?? 1),

    (float) preg_replace('/[^0-9.-]/', '', $row[3] ?? 0),
    (float) preg_replace('/[^0-9.-]/', '', $row[4] ?? 0),
    (float) preg_replace('/[^0-9.-]/', '', $row[5] ?? 0),
    (float) preg_replace('/[^0-9.-]/', '', $row[6] ?? 0),

    trim($row[7] ?? ''),
    trim($row[8] ?? 'Pagado'),
    trim($row[9] ?? ''),
    trim($row[10] ?? ''),

    (float) preg_replace('/[^0-9.-]/', '', $row[11] ?? 0),
    (float) preg_replace('/[^0-9.-]/', '', $row[12] ?? 0)

]);

                $insertados++;
            }

            $mensaje = "Se importaron {$insertados} artículos correctamente.";

        } catch (Exception $e) {

            $mensaje = "Error importando Excel: " . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">

<head>

<meta charset="UTF-8">

<meta name="viewport"
content="width=device-width, initial-scale=1.0">

<title>Importar Excel</title>

<link rel="shortcut icon" href="/img/favicon.ico">

<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>

body{

    background:
    radial-gradient(circle at top left, rgba(37,99,235,.08), transparent 30%),
    radial-gradient(circle at bottom right, rgba(14,165,233,.08), transparent 30%),
    #f8fafc;
}

.card{

    background:white;

    border-radius:32px;

    border:1px solid #e2e8f0;

    box-shadow:
        0 10px 30px rgba(15,23,42,.05),
        0 2px 10px rgba(15,23,42,.03);
}

</style>

</head>

<body class="min-h-screen flex items-center justify-center p-6">

<div class="w-full max-w-2xl">

    <div class="card p-10">

        <!-- HEADER -->

        <div class="text-center mb-10">

            <div class="w-24 h-24 mx-auto rounded-3xl
            bg-green-100 text-green-700
            flex items-center justify-center mb-5">

                <i class="fa-solid fa-file-excel text-5xl"></i>

            </div>

            <h1 class="text-4xl font-black text-slate-900">
                Importar Excel
            </h1>

            <p class="text-slate-500 mt-3">
                Carga un archivo Excel para importar artículos automáticamente
            </p>

        </div>

        <!-- ALERTA -->

        <?php if (!empty($mensaje)): ?>

            <div class="mb-6 p-5 rounded-2xl
            bg-blue-100 text-blue-800 font-bold">

                <?= $mensaje ?>

            </div>

        <?php endif; ?>

        <!-- FORM -->

        <form method="POST"
        enctype="multipart/form-data">

            <div class="border-2 border-dashed
            border-slate-300 rounded-3xl
            p-10 text-center bg-slate-50">

                <input
                    type="file"
                    name="excel"
                    accept=".xlsx,.xls"
                    required
                    class="block w-full text-sm text-slate-600
                    file:mr-4
                    file:py-4
                    file:px-6
                    file:rounded-2xl
                    file:border-0
                    file:bg-green-600
                    file:text-white
                    file:font-black
                    hover:file:bg-green-700">

                <p class="text-slate-400 text-sm mt-5">

                    Formatos permitidos:
                    XLSX / XLS

                </p>

            </div>

            <!-- BOTONES -->

            <div class="flex flex-wrap gap-4 mt-8">

                <button
                    type="submit"
                    class="flex-1 bg-green-600 hover:bg-green-700
                    text-white py-5 rounded-2xl
                    font-black text-lg shadow-lg transition">

                    <i class="fa-solid fa-upload mr-2"></i>

                    Importar Excel

                </button>

                <a href="index.php"
                class="bg-slate-900 hover:bg-black
                text-white px-8 py-5 rounded-2xl
                font-black shadow-lg transition">

                    <i class="fa-solid fa-arrow-left mr-2"></i>

                    Volver

                </a>

            </div>

        </form>

        <!-- EJEMPLO -->

        <div class="mt-10 bg-slate-50 rounded-3xl p-6 border border-slate-200">

            <h3 class="font-black text-slate-900 mb-4">
                Orden de columnas en Excel
            </h3>

            <div class="text-sm text-slate-600 leading-8">

                1. item_id<br>
                2. nombre_articulo<br>
                3. cantidad_articulos<br>
                4. costo_usd<br>
                5. costo_dop<br>
                6. costo_impuestos<br>
                7. costo_envio<br>
                8. numero_rastreo_us<br>
                9. status_compra<br>
                10. direccion_usada<br>
                11. id_courier<br>
                12. costo_unitario<br>
                13. porcentaje_incremento

            </div>

        </div>

    </div>

</div>

</body>
</html>