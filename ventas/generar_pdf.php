<?php

session_start();

if (!isset($_SESSION['admin_logueado'])) {

    header("Location: ../login.php");
    exit;
}

require '../vendor/autoload.php';

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
   CLIENTE
========================================================= */

$cliente  = $_POST['cliente']  ?? 'Cliente General';
$telefono = $_POST['telefono'] ?? '809-000-0000';
$correo   = $_POST['correo']   ?? 'cliente@email.com';

/* =========================================================
   FECHA / COTIZACION
========================================================= */

$fecha = date('d/m/Y');

$numeroCotizacion =
'COT-' . date('Y') . '-' . rand(1000,9999);

/* =========================================================
   TOTAL
========================================================= */

$total = 0;

foreach ($equipos as $eq) {

    $total += (float)$eq['precio'];
}


$garantiaData = base64_encode(
    file_get_contents('../img/garantia.png')
);

$logoSrcGarantia = 'data:image/png;base64,' . $garantiaData;

/* =========================================================
   LOGO
========================================================= */

$logoData = base64_encode(
    file_get_contents('https://dacansdr.com/img/logo.png')
);

$logoSrc = 'data:image/png;base64,' . $logoData;

/* =========================================================
   HTML
========================================================= */

$html = '
<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="UTF-8">

<style>

@page{
    margin: 20px 20px 90px 20px;
}

body{
    font-family: DejaVu Sans, sans-serif;
    font-size: 11px;
    color: #0f172a;
    margin: 0;
}

/* IMPORTANTE: quitar overflow hidden */
.wrapper{
    border: 1px solid #e2e8f0;
    border-radius: 18px;
    /* overflow: hidden;  <-- ❌ QUITAR ESTO */
}

/* HEADER */
.header{
    background: #0f172a;
    padding: 24px 30px;
    border-bottom: 4px solid #3b82f6;
}

/* CONTENT */
.content{
    padding: 22px 26px;
}

/* TABLA */
table{
    width: 100%;
    border-collapse: collapse;
}

th{
    background: #0f172a;
    color: white;
    padding: 10px;
    font-size: 11px;
}

td{
    border-bottom: 1px solid #e2e8f0;
    padding: 10px;
}

/* TOTAL */
.price-box{
    margin-top: 20px;
    background: #dcfce7;
    border: 1px solid #22c55e;
    padding: 18px;
    text-align: center;
    border-radius: 12px;
}

.price{
    font-size: 30px;
    font-weight: bold;
    color: #166534;
}

/* =========================
   FOOTER (VERSIÓN SEGURA DOMPDF)
========================= */
.footer{
    position: fixed;

    left: 0;
    right: 0;

    bottom: 0;

    height: 60px;

    font-size: 10px;
    text-align: center;
    color: #64748b;

    border-top: 1px solid #e2e8f0;
    background: #ffffff;

    padding-top: 10px;
}

</style>

</head>

<body>

<div class="wrapper">

<!-- HEADER -->
<div class="header">

<table class="header-table">

<tr>

<td style="width:70%;">

<img src="'.$logoSrc.'" style="width:70px;">

<div style="color:white;font-size:22px;font-weight:bold;margin-top:10px;">
DACANS COMPUTER
</div>

<div style="color:#cbd5e1;font-size:11px;">
Soluciones Tecnológicas Profesionales
</div>

</td>

<td align="right">

<div style="color:white;font-size:26px;font-weight:bold;">
COTIZACIÓN
</div>

<div style="color:#93c5fd;font-size:11px;">
DOCUMENTO COMERCIAL <br>
RNC: 1-33-69672-1
</div>

</td>

</tr>

</table>

</div>

<!-- CONTENT -->
<div class="content">

<table>

<tr>
<td class="label">No:</td>
<td>'.$numeroCotizacion.'</td>

<td class="label">Fecha:</td>
<td>'.$fecha.'</td>
</tr>

<tr>
<td class="label">Cliente:</td>
<td>'.htmlspecialchars($cliente).'</td>

<td class="label">Teléfono:</td>
<td>'.htmlspecialchars($telefono).'</td>
</tr>

<tr>
<td class="label">Correo:</td>
<td colspan="3">'.htmlspecialchars($correo).'</td>
</tr>

</table>

<br>

<!-- TABLA EQUIPOS -->
<table>

<thead>

<tr>
<th>ID</th>
<th>Equipo</th>
<th>RAM</th>
<th>Disco</th>
<th>Graficos</th>
<th>Precio</th>
</tr>

</thead>

<tbody>
';

foreach ($equipos as $eq) {

$html .= '

<tr>

<td>'.$eq['id_local'].'</td>

<td>
'.$eq['equipo_marca'].' '.$eq['equipo_modelo'].'
</td>

<td>'.$eq['memoria'].'</td>

<td>'.$eq['disco'].'</td>

<td>'.$eq['graficos'].'</td>

<td>RD$ '.number_format($eq['precio'],2).'</td>

</tr>

';

}

$html .= '
</tbody>
</table>

<!-- TOTAL -->
<div class="price-box">

<div>PRECIO TOTAL</div>

<div class="price">
RD$ '.number_format($total,2).'
</div>

</div>

<!-- FOOTER -->
<div class="footer">

    <div style="display:flex; justify-content:center; align-items:center; gap:10px; margin-top:10px;">

        <img src="'.$logoSrcGarantia.'" style="width:35px; height:auto;">

        <div>
            <strong>1 Año de garantía</strong><br>
            en productos y servicios
        </div>

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
   DOMPDF
========================================================= */

$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream(
    'cotizacion.pdf',
    ['Attachment' => false]
);