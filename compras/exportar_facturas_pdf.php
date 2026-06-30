<?php
session_start();
if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config/conexion.php';

$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fecha_fin    = $_GET['fecha_fin'] ?? date('Y-m-d');

$stmt = $pdo->prepare("
    SELECT * FROM dbo.facturas_compras 
    WHERE CAST(created_at AS DATE) BETWEEN ? AND ?
    ORDER BY created_at ASC
");
$stmt->execute([$fecha_inicio, $fecha_fin]);
$facturas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPeriodo = 0.0;
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte Detallado con Comprobantes</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
    body {
        font-family: 'Helvetica Neue', Arial, sans-serif;
        color: #1e293b;
        margin: 0;
        padding: 0;
        font-size: 12px;
        bg-color: #ffffff;
    }

    .header {
        width: 100%;
        border-bottom: 2px solid #1e3a8a;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }

    .title {
        font-size: 18px;
        font-weight: bold;
        color: #0f172a;
    }

    /* Estilos de la tabla resumen */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: bold;
        text-align: left;
        padding: 8px;
        border-bottom: 1px solid #cbd5e1;
        font-size: 10px;
        text-transform: uppercase;
    }

    td {
        padding: 8px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: top;
    }

    .monto {
        font-weight: bold;
        text-align: right;
    }

    .total-box {
        margin-top: 15px;
        text-align: right;
        font-size: 13px;
        font-weight: bold;
        padding: 10px;
        background-color: #f1f5f9;
        border-radius: 6px;
    }

    /* CONTROL DE SALTOS DE PÁGINA (Clave del requerimiento) */
    .pagina-factura {
        page-break-after: always;
        /* Fuerza a que la siguiente factura empiece en hoja nueva */
        padding: 10px;
        box-sizing: border-box;
    }

    .pagina-factura:last-child {
        page-break-after: avoid;
        /* Evita dejar una hoja en blanco al final de todo el documento */
    }

    /* Contenedor de la factura a pantalla completa */
    .factura-info-header {
        background-color: #0f172a;
        color: #ffffff;
        padding: 12px;
        border-radius: 8px;
        margin-bottom: 15px;
    }

    .factura-grid {
        width: 100%;
        margin-bottom: 5px;
    }

    .factura-grid td {
        color: #f8fafc;
        border: none;
        padding: 4px;
    }

    /* Ajuste de la imagen para que use el alto máximo disponible de la página */
    .contenedor-imagen-completa {
        width: 100%;
        text-align: center;
        margin-top: 10px;
    }

    .img-full {
        width: auto;
        max-width: 100%;
        height: auto;
        max-height: 230mm;
        /* Deja espacio exacto para el encabezado en una hoja A4 (297mm) */
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    }

    .badge-pdf-grande {
        display: block;
        margin: 50px auto;
        padding: 30px;
        background-color: #fee2e2;
        color: #991b1b;
        border: 2px dashed #f87171;
        border-radius: 12px;
        font-size: 16px;
        font-weight: bold;
        width: 60%;
        text-align: center;
    }

    .loading-btn {
        background-color: #1e3a8a;
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        font-size: 14px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
    }
    </style>
</head>

<body>

    <div style="text-align: center; margin: 30px 0;" id="btn-container">
        <button onclick="generarPDF()" class="loading-btn">
            📥 Generar PDF (1 Página por Factura)
        </button>
    </div>

    <div id="reporte-pdf">

        <div class="pagina-factura">
            <div class="header">
                <table style="width: 100%; border: none;">
                    <tr>
                        <td style="border: none; padding: 0;">
                            <div class="title">REPORTE DETALLADO DE COMPRAS</div>
                            <div style="color: #64748b; font-size: 11px;">Índice de comprobantes registrados en el
                                sistema</div>
                        </td>
                        <td style="border: none; padding: 0; text-align: right; color: #64748b; font-size: 11px;">
                            <strong>Periodo:</strong> <?= date('d/m/Y', strtotime($fecha_inicio)) ?> al
                            <?= date('d/m/Y', strtotime($fecha_fin)) ?><br>
                            <strong>Generado:</strong> <?= date('d/m/Y H:i') ?>
                        </td>
                    </tr>
                </table>
            </div>

            <table>
                <thead>
                    <tr>
                        <th style="width: 15%;">Fecha</th>
                        <th style="width: 25%;">Referencia / NCF</th>
                        <th style="width: 25%;">Proveedor</th>
                        <th style="width: 20%; text-align: right;">Monto</th>
                        <th style="width: 15%; text-align: center;">Anexo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($facturas)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 20px; color: #94a3b8;">
                            No existen registros en las fechas seleccionadas.
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php foreach ($facturas as $f): $totalPeriodo += (float)$f['monto_total']; ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($f['created_at'])) ?></td>
                        <td>
                            <strong
                                style="color: #1d4ed8;"><?= htmlspecialchars($f['codigo_referencia'] ?: 'Gasto Gral.') ?></strong><br>
                            <span style="color: #64748b; font-size: 10px;">Fac:
                                <?= htmlspecialchars($f['numero_factura'] ?: 'S/N') ?></span>
                        </td>
                        <td><?= htmlspecialchars($f['proveedor'] ?: 'N/A') ?></td>
                        <td class="monto">RD$ <?= number_format((float)$f['monto_total'], 2) ?></td>
                        <td style="text-align: center; color: #64748b; font-size: 10px;">
                            <?= strtoupper(pathinfo($f['ruta_imagen'], PATHINFO_EXTENSION)) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="total-box">
                TOTAL GENERAL DEL PERIODO: <span style="color: #1e3a8a;">RD$
                    <?= number_format($totalPeriodo, 2) ?></span>
            </div>
        </div>


        <?php foreach ($facturas as $index => $f): ?>
        <div class="pagina-factura">

            <div class="factura-info-header">
                <table class="factura-grid">
                    <tr>
                        <td><strong>PROVEEDOR:</strong> <?= htmlspecialchars($f['proveedor'] ?: 'N/A') ?></td>
                        <td style="text-align: right;"><strong>FECHA REGISTRO:</strong>
                            <?= date('d/m/Y', strtotime($f['created_at'])) ?></td>
                    </tr>
                    <tr>
                        <td><strong>NÚMERO FACTURA / NCF:</strong>
                            <?= htmlspecialchars($f['numero_factura'] ?: 'S/N') ?></td>
                        <td style="text-align: right;"><strong>REF / EQUIPO:</strong>
                            <?= htmlspecialchars($f['codigo_referencia'] ?: 'N/A') ?></td>
                    </tr>
                    <tr>
                        <td><strong>COMENTARIO:</strong> <?= htmlspecialchars($f['comentario'] ?: '-') ?></td>
                        <td style="text-align: right; font-size: 13px; color: #38bdf8;"><strong>MONTO: RD$
                                <?= number_format((float)$f['monto_total'], 2) ?></strong></td>
                    </tr>
                </table>
            </div>

            <div class="contenedor-imagen-completa">
                <?php 
                $archivo = '../' . $f['ruta_imagen'];
                $extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));

                if (file_exists($archivo) && !is_dir($archivo)) {
                    if ($extension !== 'pdf') {
                        // Carga binaria limpia
                        $imageData = base64_encode(file_get_contents($archivo));
                        $imageSrc = 'data:image/' . $extension . ';base64,' . $imageData;
                        echo '<img src="' . $imageSrc . '" class="img-full" />';
                    } else {
                        echo '<div class="badge-pdf-grande"> El archivo adjunto es un documento PDF nativo.<br><span style="font-size:12px; font-weight:normal; color:#b91c1c;">(Consulte el archivo original en el servidor web: ' . htmlspecialchars($f['nombre_original']) . ')</span></div>';
                    }
                } else {
                    echo '<div style="color:#94a3b8; padding: 100px 0;">Archivo físico no encontrado en el almacén de datos del servidor.</div>';
                }
                ?>
            </div>

        </div>
        <?php endforeach; ?>

    </div>

    <script>
    function generarPDF() {
        // Ocultamos el botón para que no contamine el reporte
        document.getElementById('btn-container').style.display = 'none';

        const elemento = document.getElementById('reporte-pdf');
        const opciones = {
            margin: [10, 10, 10, 10], // Margen uniforme
            filename: 'Reporte_Completo_Facturas.pdf',
            image: {
                type: 'jpeg',
                quality: 0.98
            },
            html2canvas: {
                scale: 2,
                useCORS: true,
                logging: false
            },
            jsPDF: {
                unit: 'mm',
                format: 'a4',
                orientation: 'portrait'
            }
        };

        // Dispara el renderizador e inmediatamente restablece el botón
        html2pdf().set(opciones).from(elemento).save().then(() => {
            document.getElementById('btn-container').style.display = 'block';
        });
    }

    // Auto-ejecución inteligente al terminar de cargar la vista
    window.onload = function() {
        setTimeout(generarPDF, 600);
    };
    </script>
</body>

</html>