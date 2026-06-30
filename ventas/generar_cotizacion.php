<?php

session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

require '../config/conexion.php';

try {
    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

/* =========================================================
   INICIALIZAR COTIZACION
========================================================= */
if (!isset($_SESSION['cotizacion'])) {
    $_SESSION['cotizacion'] = [];
}

/* =========================================================
   AGREGAR EQUIPO
========================================================= */
if (isset($_GET['agregar'])) {
    $buscar = trim($_GET['agregar']);
    if (!empty($buscar)) {
        $stmt = $pdo->prepare("
            SELECT TOP 1 *
            FROM productos_informatica
            WHERE RIGHT(id_local, 4) = ?
        ");
        $stmt->execute([$buscar]);
        $equipo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($equipo) {
            $idLocal = $equipo['id_local'];
            if (!isset($_SESSION['cotizacion'][$idLocal])) {
                $_SESSION['cotizacion'][$idLocal] = $equipo;
            }
        }
    }
    header("Location: generar_cotizacion.php");
    exit;
}

/* =========================================================
   ELIMINAR EQUIPO
========================================================= */
if (isset($_GET['eliminar'])) {
    $idEliminar = $_GET['eliminar'];
    unset($_SESSION['cotizacion'][$idEliminar]);
    header("Location: generar_cotizacion.php");
    exit;
}

/* =========================================================
   LIMPIAR COTIZACION
========================================================= */
if (isset($_GET['limpiar'])) {
    $_SESSION['cotizacion'] = [];
    header("Location: generar_cotizacion.php");
    exit;
}

/* =========================================================
   EQUIPOS
========================================================= */
$equipos = $_SESSION['cotizacion'];

/* =========================================================
   CLIENTE Y MODIFICADORES DE COSTO
========================================================= */
$cliente   = $_POST['cliente']   ?? '';
$rnc       = $_POST['rnc']       ?? ''; 
$telefono  = $_POST['telefono']  ?? '';
$correo    = $_POST['correo']    ?? '';

$aumento          = isset($_POST['aumento']) ? (float)$_POST['aumento'] : 0.0;
$porcentajeDesc   = isset($_POST['reduccion']) ? (float)$_POST['reduccion'] : 0.0; // Ahora se maneja como porcentaje

/* =========================================================
   FECHA
========================================================= */
$fecha = date('d/m/Y');
$numeroCotizacion = 'COT-' . date('Y') . '-' . rand(1000, 9999);

/* =========================================================
   CÁLCULO DE TOTALES
========================================================= */
$subtotal = 0;
foreach ($equipos as $equipo) {
    $subtotal += (float)$equipo['precio'];
}

// El porcentaje se calcula en base al Subtotal + Aumento
$baseCalculo     = $subtotal + $aumento;
$dineroDescuento = $baseCalculo * ($porcentajeDesc / 100);

// Total neto final
$totalNeto = $baseCalculo - $dineroDescuento;

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cotización</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
    body {
        background: #eef2ff;
        font-family: Arial, sans-serif;
    }

    @media print {
        .no-print {
            display: none !important;
        }

        body {
            background: white;
        }

        .print-area {
            box-shadow: none !important;
            border: none !important;
        }
    }

    table {
        border-collapse: collapse;
        width: 100%;
    }

    th,
    td {
        padding: 16px;
    }
    </style>
</head>

<body class="p-4 md:p-8">

    <div class="max-w-7xl mx-auto">

        <div class="flex flex-wrap gap-3 mb-6 no-print">
            <a href="index.php"
                class="bg-slate-900 hover:bg-black text-white px-6 py-3 rounded-2xl font-black transition">
                <i class="fa-solid fa-arrow-left mr-2"></i> Volver
            </a>
            <a href="?limpiar=1"
                class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-2xl font-black transition">
                <i class="fa-solid fa-trash mr-2"></i> Vaciar
            </a>
        </div>

        <form method="POST" action="generar_pdf.php" target="_blank">

            <div class="bg-white rounded-[2rem] shadow-xl p-6 mb-6 no-print">

                <div class="grid grid-cols-1 md:grid-cols-4 gap-5 mb-5">
                    <div>
                        <label class="text-xs font-black uppercase text-slate-400">Cliente</label>
                        <input type="text" name="cliente" value="<?= htmlspecialchars($cliente) ?>"
                            class="w-full border border-slate-300 rounded-2xl p-4">
                    </div>
                    <div>
                        <label class="text-xs font-black uppercase text-slate-400">RNC / Cédula</label>
                        <input type="text" name="rnc" value="<?= htmlspecialchars($rnc) ?>"
                            class="w-full border border-slate-300 rounded-2xl p-4" placeholder="001-0000000-0">
                    </div>
                    <div>
                        <label class="text-xs font-black uppercase text-slate-400">Teléfono</label>
                        <input type="text" name="telefono" value="<?= htmlspecialchars($telefono) ?>"
                            class="w-full border border-slate-300 rounded-2xl p-4">
                    </div>
                    <div>
                        <label class="text-xs font-black uppercase text-slate-400">Correo</label>
                        <input type="email" name="correo" value="<?= htmlspecialchars($correo) ?>"
                            class="w-full border border-slate-300 rounded-2xl p-4">
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-4 grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="text-xs font-black uppercase text-emerald-600">Aumento (Suma al total RD$)</label>
                        <input type="number" step="0.01" min="0" name="aumento" value="<?= $aumento ?>"
                            class="w-full border border-emerald-200 bg-emerald-50/30 rounded-2xl p-4 font-bold text-emerald-700"
                            placeholder="0.00">
                    </div>
                    <div>
                        <label class="text-xs font-black uppercase text-rose-600">Reducción / Descuento (Porcentaje
                            %)</label>
                        <input type="number" step="0.1" min="0" max="100" name="reduccion"
                            value="<?= $porcentajeDesc ?>"
                            class="w-full border border-rose-200 bg-rose-50/30 rounded-2xl p-4 font-bold text-rose-700"
                            placeholder="Ej. 10 para 10%">
                    </div>
                </div>

                <div class="flex gap-3 mt-5">
                    <button type="submit" name="calcular" formaction=""
                        class="bg-slate-700 hover:bg-slate-800 text-white px-6 py-3 rounded-2xl font-black transition">
                        <i class="fa-solid fa-calculator mr-2"></i> Calcular Totales
                    </button>
                    <button type="submit"
                        class="bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-3 rounded-2xl font-black transition">
                        <i class="fa-solid fa-file-pdf mr-2"></i> Generar PDF
                    </button>
                </div>

            </div>

        </form>

        <div class="bg-white rounded-[2rem] shadow-xl p-6 mb-6 no-print">
            <h2 class="text-2xl font-black text-slate-800 mb-4">Agregar Equipo</h2>
            <form method="GET">
                <div class="flex flex-col md:flex-row gap-4">
                    <input type="text" name="agregar" maxlength="4" placeholder="Últimos 4 dígitos del ID"
                        class="flex-1 border border-slate-300 rounded-2xl p-4">
                    <button type="submit"
                        class="bg-green-600 hover:bg-green-700 text-white px-8 py-4 rounded-2xl font-black">
                        <i class="fa-solid fa-plus mr-2"></i> Agregar
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-[2rem] shadow-2xl overflow-hidden border border-slate-200 print-area">

            <div class="bg-gradient-to-r from-slate-900 to-slate-800 text-white p-10">
                <div class="flex justify-between">
                    <div>
                        <h1 class="text-4xl font-black">TU EMPRESA</h1>
                        <p class="text-slate-300">Soluciones Tecnológicas</p>

                        <?php if (!empty($cliente)): ?>
                        <div
                            class="mt-4 text-sm text-slate-300 bg-slate-800/50 p-3 rounded-xl border border-slate-700 inline-block">
                            <p class="font-bold text-white uppercase">Cliente: <?= htmlspecialchars($cliente) ?></p>
                            <?php if (!empty($rnc)): ?>
                            <p>RNC/Cédula: <?= htmlspecialchars($rnc) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="text-right">
                        <h2 class="text-5xl font-black">COTIZACIÓN</h2>
                        <p class="text-slate-300">
                            <?= $numeroCotizacion ?> <br>
                            <?= $fecha ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="p-10">
                <?php if (!empty($equipos)): ?>
                <table class="w-full">
                    <thead>
                        <tr class="bg-slate-900 text-white">
                            <th>ID</th>
                            <th>Equipo</th>
                            <th>RAM</th>
                            <th>Disco</th>
                            <th>Graficos</th>
                            <th>Precio</th>
                            <th class="no-print">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($equipos as $equipo): ?>
                        <tr class="border-b">
                            <td><?= $equipo['id_local'] ?></td>
                            <td>
                                <strong><?= $equipo['equipo_marca'] ?> <?= $equipo['equipo_modelo'] ?></strong>
                            </td>
                            <td><?= $equipo['memoria'] ?></td>
                            <td><?= $equipo['disco'] ?></td>
                            <td><?= $equipo['graficos'] ?></td>
                            <td class="font-bold text-green-700">RD$ <?= number_format($equipo['precio'],2) ?></td>
                            <td class="no-print">
                                <a href="?eliminar=<?= $equipo['id_local'] ?>" class="text-red-600 font-bold">X</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No hay equipos agregados</p>
                <?php endif; ?>
            </div>

            <div class="p-10 bg-slate-50 border-t border-slate-100 flex flex-col items-end">
                <div class="w-full md:w-80 space-y-2 text-right text-slate-600">
                    <div class="flex justify-between">
                        <span>Subtotal:</span>
                        <span class="font-bold">RD$ <?= number_format($subtotal, 2) ?></span>
                    </div>

                    <?php if ($aumento > 0): ?>
                    <div class="flex justify-between text-emerald-600">
                        <span>Aumento (+):</span>
                        <span class="font-bold">RD$ <?= number_format($aumento, 2) ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($porcentajeDesc > 0): ?>
                    <div class="flex justify-between text-rose-600">
                        <span>Descuento (<?= $porcentajeDesc ?>%):</span>
                        <span class="font-bold">- RD$ <?= number_format($dineroDescuento, 2) ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="flex justify-between border-t border-slate-300 pt-3 text-slate-900">
                        <span class="text-xl font-bold">Total Neto:</span>
                        <span class="text-3xl font-black text-green-700">RD$ <?= number_format($totalNeto, 2) ?></span>
                    </div>
                </div>
            </div>

        </div>

    </div>

</body>

</html>