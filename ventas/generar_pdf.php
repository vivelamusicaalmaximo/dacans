<?php

session_start();

if (!isset($_SESSION['admin_logueado'])) {

    header("Location: ../login.php");
    exit;
}

require '../vendor/autoload.php';

// 1. INCLUIR TU ARCHIVO DE CONEXIÓN
require_once '../config/conexion.php'; 

// 2. SOLUCIÓN AL ERROR: Mapeamos la variable $pdo que viene de tu conexion.php a $conn
$conn = $pdo; 

use Dompdf\Dompdf;
use Dompdf\Options;

/* =========================================================
   EQUIPOS (DESDE SESIÓN)
========================================================= */

$equipos = $_SESSION['cotizacion'] ?? [];

if (empty($equipos)) {

    die("No hay equipos en la cotización.");
}

/* =========================================================
   CLIENTE Y PARÁMETROS
========================================================= */

$cliente   = $_POST['cliente']   ?? 'Cliente General';
$rnc       = $_POST['rnc']       ?? ''; 
$telefono  = $_POST['telefono']  ?? '809-000-0000';
$correo    = $_POST['correo']    ?? 'cliente@email.com';

$id_cliente     = isset($_POST['id_cliente']) ? (int)$_POST['id_cliente'] : 1; 
$id_metodo_pago = isset($_POST['id_metodo_pago']) ? (int)$_POST['id_metodo_pago'] : 1; 

$aumento   = isset($_POST['aumento']) ? (float)$_POST['aumento'] : 0.0;
$reduccion = isset($_POST['reduccion']) ? (float)$_POST['reduccion'] : 0.0;

/* =========================================================
   FECHA Y CONTROL DEL NÚMERO DE COTIZACIÓN EN SESIÓN
========================================================= */

$fecha = date('d/m/Y');
$fechaBaseDatos = date('Y-m-d H:i:s');

if (!isset($_SESSION['ultimo_numero_cotizacion'])) {
    $_SESSION['ultimo_numero_cotizacion'] = 'COT-' . date('Y') . '-' . rand(1000, 9999);
}

$numeroCotizacion = $_SESSION['ultimo_numero_cotizacion'];

/* =========================================================
   CÁLCULOS FINANCIEROS (EXTRACCIÓN DEL 18% DE ITBIS)
========================================================= */

$subtotalEquipos = 0;
foreach ($equipos as $eq) {
    $subtotalEquipos += (float)$eq['precio'];
}

$precioConReduccion = $subtotalEquipos - $reduccion;
$subtotalSinItbis   = $precioConReduccion / 1.18;
$itbisCalculado     = $precioConReduccion - $subtotalSinItbis;
$totalGeneral       = $subtotalSinItbis + $itbisCalculado + $aumento;

$estadoCotizacion   = 'Pendiente';

/* =========================================================
   VALIDACIÓN Y GUARDADO EN LA BASE DE DATOS
========================================================= */
try {
    // Verificamos si este número de cotización ya fue registrado previamente
    $sqlCheck = "SELECT COUNT(*) FROM [catalogo_equipos].[dbo].[cotizaciones] WHERE [numero_cotizacion] = :numero_cotizacion";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->execute([':numero_cotizacion' => $numeroCotizacion]);
    $existe = $stmtCheck->fetchColumn();

    // Si NO existe, procedemos a insertar los datos
    if ($existe == 0) {
        $sqlInsert = "INSERT INTO [catalogo_equipos].[dbo].[cotizaciones] 
                ([numero_cotizacion], [fecha_cotizacion], [id_cliente], [id_metodo_pago], [subtotal], [itbis_total], [total_neto], [estado]) 
                VALUES 
                (:numero_cotizacion, :fecha_cotizacion, :id_cliente, :id_metodo_pago, :subtotal, :itbis_total, :total_neto, :estado)";
                
        $stmtInsert = $conn->prepare($sqlInsert);
        
        $stmtInsert->execute([
            ':numero_cotizacion' => $numeroCotizacion,
            ':fecha_cotizacion'  => $fechaBaseDatos,
            ':id_cliente'        => $id_cliente,
            ':id_metodo_pago'    => $id_metodo_pago,
            ':subtotal'          => $subtotalSinItbis,
            ':itbis_total'       => $itbisCalculado,
            ':total_neto'        => $totalGeneral,
            ':estado'            => $estadoCotizacion
        ]);
    }

} catch (PDOException $e) {
    die("Error al procesar la base de datos: " . $e->getMessage());
}

/* =========================================================
   IMÁGENES EN BASE64
========================================================= */
$garantiaData = base64_encode(file_get_contents('../img/garantia.png'));
$logoSrcGarantia = 'data:image/png;base64,' . $garantiaData;

$logoData = base64_encode(file_get_contents('https://dacansdr.com/img/logo.png'));
$logoSrc = 'data:image/png;base64,' . $logoData;

/* =========================================================
   HTML DEL PDF
========================================================= */

$html = '
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
@page{ margin: 20px 20px 110px 20px; }
body{ font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #0f172a; margin: 0; }
.wrapper{ border: 1px solid #e2e8f0; border-radius: 18px; }
.header{ background: #0f172a; padding: 24px 30px; border-bottom: 4px solid #3b82f6; }
.content{ padding: 22px 26px; }
table{ width: 100%; border-collapse: collapse; }
th{ background: #0f172a; color: white; padding: 10px; font-size: 11px; }
td{ border-bottom: 1px solid #e2e8f0; padding: 10px; }
.label{ font-weight: bold; color: #475569; }
.price-container { margin-top: 20px; width: 100%; }
.price-table { width: 280px; margin-left: auto; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; }
.price-table td { padding: 6px 14px; border-bottom: 1px solid #f1f5f9; }
.price-row-total { background: #dcfce7; }
.price-row-total td { border-top: 1px solid #22c55e; padding: 12px 14px; }
.price-final { font-size: 18px; font-weight: bold; color: #166534; }
.footer{ position: fixed; left: 0; right: 0; bottom: 0; height: 60px; font-size: 10px; text-align: center; color: #64748b; border-top: 1px solid #e2e8f0; background: #ffffff; padding-top: 10px; }
</style>
</head>
<body>

<div class="wrapper">
<div class="header">
<table class="header-table" style="border:none;">
<tr>
<td style="width:70%; border:none;">
<img src="'.$logoSrc.'" style="width:70px;">
<div style="color:white;font-size:22px;font-weight:bold;margin-top:10px;">DACANS COMPUTER</div>
<div style="color:#cbd5e1;font-size:11px;">Soluciones Tecnológicas Profesionales</div>
</td>
<td align="right" style="border:none;">
<div style="color:white;font-size:26px;font-weight:bold;">COTIZACIÓN</div>
<div style="color:#93c5fd;font-size:11px;">DOCUMENTO COMERCIAL <br> RNC: 1-33-69672-1</div>
</td>
</tr>
</table>
</div>

<div class="content">
<table>
<tr>
<td class="label" style="width:12%;">No:</td>
<td style="width:38%;">'.$numeroCotizacion.'</td>
<td class="label" style="width:12%;">Fecha:</td>
<td style="width:38%;">'.$fecha.'</td>
</tr>
<tr>
<td class="label">Cliente:</td>
<td>'.htmlspecialchars($cliente).'</td>
<td class="label">RNC / Cédula:</td>
<td>'.(!empty($rnc) ? htmlspecialchars($rnc) : 'N/A').'</td>
</tr>
<tr>
<td class="label">Teléfono:</td>
<td>'.htmlspecialchars($telefono).'</td>
<td class="label">Correo:</td>
<td>'.htmlspecialchars($correo).'</td>
</tr>
</table>

<br>

<table>
<thead>
<tr>
<th>ID</th>
<th>Equipo</th>
<th>RAM</th>
<th>Disco</th>
<th>Condición Física</th>
<th>Precio</th>
</tr>
</thead>
<tbody>
';

foreach ($equipos as $eq) {
    $claseLetra = strtoupper($eq['clase'] ?? 'A'); 
    switch ($claseLetra) {
        case 'A': $condicionFisica = '<strong>Clase A:</strong><br><span style="color:#64748b; font-size:10px;">Excelente estado</span>'; break;
        case 'B': $condicionFisica = '<strong>Clase B:</strong><br><span style="color:#64748b; font-size:10px;">Ligeras Marcas de Uso</span>'; break;
        case 'C': $condicionFisica = '<strong>Clase C:</strong><br><span style="color:#64748b; font-size:10px;">Marcas de Uso Muy Notables</span>'; break;
        default:  $condicionFisica = '<strong>Clase A:</strong><br><span style="color:#64748b; font-size:10px;">Excelente estado</span>'; break;
    }

$html .= '
<tr>
<td>'.$eq['id_local'].'</td>
<td>
'.$eq['equipo_marca'].' '.$eq['equipo_modelo'].'<br>
<small style="color:#64748b;">Graficos: '.$eq['graficos'].'</small>
</td>
<td>'.$eq['memoria'].'</td>
<td>'.$eq['disco'].'</td>
<td>'.$condicionFisica.'</td>
<td>RD$ '.number_format($eq['precio'],2).'</td>
</tr>
';
}

$html .= '
</tbody>
</table>

<div class="price-container">
    <table class="price-table">
        <tr>
            <td style="color: #64748b;">Subtotal (Sin ITBIS):</td>
            <td align="right" style="font-weight: bold;">RD$ '.number_format($subtotalSinItbis, 2).'</td>
        </tr>';

        if ($aumento > 0) {
            $html .= '
            <tr>
                <td style="color: #16a34a;">Aumento (+):</td>
                <td align="right" style="font-weight: bold; color: #16a34a;">RD$ '.number_format($aumento, 2).'</td>
            </tr>';
        }

        $html .= '
        <tr>
            <td style="color: #64748b;">ITBIS (18%):</td>
            <td align="right" style="font-weight: bold;">RD$ '.number_format($itbisCalculado, 2).'</td>
        </tr>
        <tr class="price-row-total">
            <td style="font-weight: bold; color: #166534;">TOTAL NETO:</td>
            <td align="right" class="price-final">RD$ '.number_format($totalGeneral, 2).'</td>
        </tr>
    </table>
</div>

<div class="footer">
    <div style="margin-top:2px; margin-bottom: 5px;">
        <img src="'.$logoSrcGarantia.'" style="width:20px; height:auto; vertical-align: middle; margin-right: 5px;">
        <span style="vertical-align: middle;"><strong>1 Año de garantía</strong> en productos y servicios</span>
    </div>
<strong>DACANS COMPUTER</strong><br>
Calle Olegario Tenares #14 Plaza MTB 2do nivel local 1 Santo Domingo Distrito Nacional<br>
Tel: 809-685-9705 | WhatsApp: 849-588-6436 | contacto@dacansdr.com | www.dacansdr.com
</div>

</div>
</div>

</body>
</html>
';

/* =========================================================
   DOMPDF & DESCARGA
========================================================= */
$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

if (ob_get_length()) {
    ob_end_clean();
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $numeroCotizacion . '.pdf"');
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

echo $dompdf->output();
exit;