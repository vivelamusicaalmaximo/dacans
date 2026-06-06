<?php
session_start();

// 1. Validar sesión
if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

// 2. Validar que llegue el ID de la factura
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: ID de factura no especificado.");
}

$id_factura = (int)$_GET['id'];

// 3. Requerir conexión e imports de Dompdf
require_once '../config/conexion.php'; 
require_once '../vendor/autoload.php'; 

use Dompdf\Dompdf;
use Dompdf\Options;

try {
    // 4. Obtener datos de la cabecera + Información de agencias_envio (Se mantiene u.usuario por si acaso lo usas en otra lógica, pero se quitó del diseño)
    $sqlFactura = "SELECT f.*, 
                           c.nombre + ' ' + c.apellido AS cliente_nombre, 
                           m.nombre_metodo, 
                           u.usuario AS nombre_cajero,
                           ae.agencia AS agencia_nombre,
                           ae.costo AS costo_envio
                    FROM facturas f
                    INNER JOIN clientes c ON f.id_cliente = c.id_cliente
                    INNER JOIN metodos_pago m ON f.id_metodo_pago = m.id_metodo
                    INNER JOIN usuarios u ON f.id_usuario = u.id
                    LEFT JOIN agencias_envio ae ON f.id_envio = ae.id
                    WHERE f.id_factura = ?";
                    
    $stmtF = $pdo->prepare($sqlFactura);
    $stmtF->execute([$id_factura]);
    $factura = $stmtF->fetch(PDO::FETCH_ASSOC);

    if (!$factura) {
        die("La factura solicitada no existe.");
    }

    // 5. Obtener los artículos del detalle
    $sqlDetalle = "SELECT fd.cantidad, fd.precio_unitario, fd.subtotal_linea, 
                           p.id_local, p.equipo_marca, p.equipo_modelo 
                    FROM factura_detalle fd
                    INNER JOIN productos_informatica p ON fd.id_producto = p.id
                    WHERE fd.id_factura = ?";
    $stmtD = $pdo->prepare($sqlDetalle);
    $stmtD->execute([$id_factura]);
    $items = $stmtD->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("Error en la base de datos: " . $e->getMessage());
}

// --- PROCESAMIENTO DE IMÁGENES EN BASE64 ---
$logoData = base64_encode(file_get_contents('https://dacansdr.com/img/logo.png'));
$ruta_logo = 'data:image/png;base64,' . $logoData;

// Sello de Garantía PNG cargado desde la carpeta local 'img'
$garantiaData = base64_encode(file_get_contents('../img/garantia.png')); 
$ruta_garantia = 'data:image/png;base64,' . $garantiaData;

// Validar si la factura incluye envío o si es retiro físico
$costo_envio = isset($factura['costo_envio']) ? (float)$factura['costo_envio'] : 0.00;
$agencia_envio = !empty($factura['agencia_nombre']) ? htmlspecialchars($factura['agencia_nombre']) : 'Retiro en Tienda';

// Sumar total neto más envío
$total_final = (float)$factura['total_neto'] + $costo_envio;

// 6. Estructura HTML para el PDF
$html = '
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura ' . htmlspecialchars($factura['numero_factura']) . '</title>
    <style>
        @page { margin: 40px 40px 140px 40px; }
        body { font-family: "Helvetica", "Arial", sans-serif; color: #333; font-size: 11px; line-height: 1.4; }
        .invoice-box { max-width: 100%; margin: auto; }
        table { width: 100%; border-collapse: collapse; }
        table td { padding: 6px; vertical-align: top; }
        .header-table td { vertical-align: middle; }
        .logo-container { width: 60px; background: #fff; padding: 5px; border-radius: 6px; border: 1px solid #e2e8f0; text-align: center; }
        .logo-img { height: 40px; width: auto; display: block; margin: 0 auto; }
        .company-name { font-size: 20px; font-weight: 900; color: #0f172a; margin: 0; letter-spacing: -0.5px; }
        .company-slogan { font-size: 11px; color: #64748b; font-weight: normal; margin-top: 2px; }
        .info-header { text-align: right; font-size: 11px; }
        .info-block { background: #f8fafc; padding: 12px; border-radius: 8px; margin-top: 15px; margin-bottom: 15px; border: 1px solid #f1f5f9; }
        .details-table th { background: #0f172a; color: #fff; padding: 8px; font-size: 10px; text-transform: uppercase; text-align: left; font-weight: bold; }
        .details-table td { border-bottom: 1px solid #e2e8f0; padding: 8px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        
        /* Contenedor de bloque final */
        .summary-container { margin-top: 20px; width: 100%; }
        .warranty-box { background: #fdfdfd; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 15px; text-align: center; width: 240px; }
        .warranty-img { height: 38px; width: auto; display: block; margin: 0 auto 6px auto; }
        .warranty-title { font-size: 13px; font-weight: bold; color: #1e3a8a; text-transform: uppercase; letter-spacing: 0.5px; }
        .warranty-text { font-size: 9px; color: #64748b; margin-top: 2px; font-weight: 500; }
        
        .totals-box { width: 100%; font-size: 11px; }
        .totals-box td { padding: 4px; }
        .border-top { border-top: 2px solid #0f172a; font-weight: bold; font-size: 13px; color: #059669; }
        .badge { padding: 3px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .badge-pagada { background-color: #d1fae5; color: #065f46; }
        .badge-anulada { background-color: #fee2e2; color: #991b1b; }
        footer { position: fixed; bottom: -100px; left: 0px; right: 0px; height: 110px; border-top: 1px solid #e2e8f0; padding-top: 10px; }
        .footer-table { font-size: 10px; color: #64748b; }
        .footer-title { color: #0f172a; font-weight: bold; margin-bottom: 4px; font-size: 10px; text-transform: uppercase; }
        .copyright { text-align: center; font-size: 9px; color: #94a3b8; border-top: 1px solid #f1f5f9; margin-top: 8px; padding-top: 5px; font-weight: bold; }
    </style>
</head>
<body>

    <footer>
        <table class="footer-table">
            <tr>
                <td style="width: 40%;">
                    <div class="footer-title">DACANS Computers</div>
                    Equipos premium y soporte técnico especializado.<br>
                    ¡Gracias por su preferencia y confianza!
                </td>
                <td style="width: 60%; text-align: right; line-height: 1.3;">
                    <div class="footer-title">Contacto & Ubicación</div>
                    <b>Teléfono:</b> 809-685-9705 | <b>WhatsApp:</b> 849-588-6436 <br> <b>Email:</b> contacto@dacansdr.com<br>
                    Calle Olegario Tenares #14, Plaza MTB 2do nivel local 1,<br>
                    Santo Domingo, Distrito Nacional, República Dominicana.
                </td>
            </tr>
        </table>
        <div class="copyright">
            © 2026 DACANS COMPUTERS - TODOS LOS DERECHOS RESERVADOS.
        </div>
    </footer>

    <div class="invoice-box">
        <table class="header-table">
            <tr>
                <td style="width: 65px;">
                    <div class="logo-container">
                        <img src="' . $ruta_logo . '" class="logo-img" alt="Logo">
                    </div>
                </td>
                <td>
                    <h1 class="company-name">DACANS COMPUTERS</h1>
                    <div class="company-slogan">RNC: 1-33-69672-1 | Especialistas en Tecnología Premium</div>
                </td>
                <td class="info-header">
                    <strong style="font-size: 15px; color: #2563eb;">' . htmlspecialchars($factura['numero_factura']) . '</strong><br>
                    <b>Fecha:</b> ' . date('d/m/Y h:i A', strtotime($factura['fecha_factura'])) . '<br>
                    <b>Estado:</b> ' . ($factura['estado_factura'] === 'PAGADA' ? '<span class="badge badge-pagada">PAGADA</span>' : '<span class="badge badge-anulada">ANULADA</span>') . '
                </td>
            </tr>
        </table>

        <div class="info-block">
            <table>
                <tr>
                    <td style="width: 55%; padding:0;">
                        <b>Cliente:</b> ' . htmlspecialchars($factura['cliente_nombre']) . '<br>
                        <b>Condición:</b> Contado
                    </td>
                    <td style="width: 45%; text-align: right; padding:0;">
                        <b>Método de Pago:</b> ' . htmlspecialchars($factura['nombre_metodo']) . '<br>
                        <b>Tipo de Envío:</b> ' . $agencia_envio . '
                    </td>
                </tr>
            </table>
        </div>

        <table class="details-table">
            <thead>
                <tr>
                    <th style="width: 15%;">ID Local</th>
                    <th style="width: 45%;">Descripción Artículo</th>
                    <th style="width: 10%; text-align: center;">Cant.</th>
                    <th style="width: 15%; text-align: right;">Precio</th>
                    <th style="width: 15%; text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>';
            
            foreach ($items as $item) {
                $html .= '
                <tr>
                    <td style="font-family: monospace; font-weight: bold; color: #2563eb;">' . htmlspecialchars($item['id_local']) . '</td>
                    <td style="font-weight: bold;">' . htmlspecialchars($item['equipo_marca'] . ' ' . $item['equipo_modelo']) . '</td>
                    <td class="text-center">' . $item['cantidad'] . '</td>
                    <td class="text-right">RD$ ' . number_format($item['precio_unitario'], 2) . '</td>
                    <td class="text-right" style="font-weight: bold;">RD$ ' . number_format($item['subtotal_linea'], 2) . '</td>
                </tr>';
            }

$html .= '
            </tbody>
        </table>

        <table class="summary-container">
            <tr>
                <td style="width: 55%; padding: 10px 0 0 0; vertical-align: top;">
                    <div class="warranty-box">
                        <img src="' . $ruta_garantia . '" class="warranty-img" alt="Garantía Oficial">
                        <div class="warranty-title">1 Año de Garantía</div>
                        <div class="warranty-text">Sello de Cobertura Oficial DACANS COMPUTERS</div>
                    </div>
                </td>
                
                <td style="width: 45%; padding: 10px 0 0 0; vertical-align: top;">
                    <table class="totals-box">
                        <tr>
                            <td>Subtotal Neto:</td>
                            <td class="text-right">RD$ ' . number_format($factura['subtotal'], 2) . '</td>
                        </tr>
                        <tr>
                            <td>ITBIS (18%):</td>
                            <td class="text-right">RD$ ' . number_format($factura['itbis_total'], 2) . '</td>
                        </tr>';

                        if ($costo_envio > 0) {
                            $html .= '
                            <tr>
                                <td>Costo Envío:</td>
                                <td class="text-right">RD$ ' . number_format($costo_envio, 2) . '</td>
                            </tr>';
                        }

$html .= '
                        <tr class="border-top">
                            <td style="padding-top: 6px;">Total General:</td>
                            <td class="text-right" style="padding-top: 6px;">RD$ ' . number_format($total_final, 2) . '</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>';

// 7. Configurar Dompdf y Renderizar
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true); 

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);

$dompdf->setPaper('letter', 'portrait');
$dompdf->render();

$dompdf->stream("Factura_" . $factura['numero_factura'] . ".pdf", array("Attachment" => false));
exit;