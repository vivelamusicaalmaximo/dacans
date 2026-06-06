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
   CONEXION SQL SERVER
========================================================= */
require '../config/conexion.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

/* =========================================================
   VALIDAR ID
========================================================= */
if (!isset($_GET['id'])) {
    die("ID inválido.");
}

$id = $_GET['id'];

/* =========================================================
   OBTENER EQUIPO
========================================================= */
$stmt = $pdo->prepare("
    SELECT TOP 1 *
    FROM productos_informatica
    WHERE id_local = ?
");
$stmt->execute([$id]);
$equipo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipo) {
    die("Equipo no encontrado.");
}

/* =========================================================
   DATOS CLIENTE Y COTIZACION
========================================================= */
$cliente  = $_GET['cliente']  ?? 'Cliente General';
$telefono = $_GET['telefono'] ?? '809-000-0000';
$correo   = $_GET['correo']   ?? 'cliente@email.com';

$fecha = date('d/m/Y');
$fecha_sql = date('Y-m-d H:i:s'); // Formato para la Base de Datos

// Generar número único
$numeroCotizacion = 'DC-' . date('Y') . '-' . rand(1000, 9999);

$precio_crudo = (float)$equipo['precio'];
$precio = number_format($precio_crudo, 2);

/* =========================================================
   GUARDAR EN LA BASE DE DATOS (NUEVO REGISTRO AUTOMÁTICO)
========================================================= */
try {
    $pdo->beginTransaction();

    // 1. Insertar la cabecera de la cotización
    // Nota: Como no tenemos ID de cliente relacional aquí todavía por usar inputs manuales,
    // guardamos el nombre directo, el teléfono, etc.
    $sqlInsertCabecera = "INSERT INTO cotizaciones 
        (numero_cotizacion, fecha_cotizacion, cliente_nombre, cliente_telefono, cliente_correo, total_neto, estado) 
        VALUES (?, ?, ?, ?, ?, ?, 'PENDIENTE')";
    
    $stmtCabecera = $pdo->prepare($sqlInsertCabecera);
    $stmtCabecera->execute([
        $numeroCotizacion,
        $fecha_sql,
        $cliente,
        $telefono,
        $correo,
        $precio_crudo
    ]);
    
    // Obtener el ID recién creado
    $id_cotizacion_guardada = $pdo->lastInsertId();

    // 2. Insertar el artículo en el detalle
    $sqlInsertDetalle = "INSERT INTO cotizaciones_detalle 
        (id_cotizacion, id_producto_local, descripcion, precio_unitario) 
        VALUES (?, ?, ?, ?)";
    
    $stmtDetalle = $pdo->prepare($sqlInsertDetalle);
    $stmtDetalle->execute([
        $id_cotizacion_guardada,
        $equipo['id_local'],
        $equipo['equipo_marca'] . ' ' . $equipo['equipo_modelo'],
        $precio_crudo
    ]);

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    // Si las tablas no existen en tu BD actual, ignorará el guardado y generará el PDF de todas formas
    // para no romper la experiencia del cajero en lo que creas las tablas.
}

/* =========================================================
   LOGO
========================================================= */
$logoData = base64_encode(file_get_contents('https://dacansdr.com/img/logo.png'));
$logoSrc = 'data:image/png;base64,' . $logoData;

/* =========================================================
   HTML (Mantiene intacto tu excelente diseño original)
========================================================= */
$html = '
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
@page{ margin:20px; }
body{ font-family: DejaVu Sans, sans-serif; color:#0f172a; font-size:11px; margin:0; }
.wrapper{ border:1px solid #e2e8f0; border-radius:18px; overflow:hidden; }
.header{ background:#0f172a; padding:24px 30px; border-bottom:4px solid #3b82f6; }
.header-table{ width:100%; border-collapse:collapse; }
.content{ padding:22px 26px; }
.info{ width:100%; border-collapse:collapse; margin-bottom:18px; }
.info td{ padding:6px 0; font-size:11px; }
.label{ width:120px; font-weight:bold; color:#475569; }
.product{ background:#f8fafc; border:1px solid #e2e8f0; border-radius:14px; padding:16px; margin-bottom:18px; }
.product-title{ font-size:20px; font-weight:bold; margin-bottom:4px; }
.product-sub{ font-size:11px; color:#64748b; margin-bottom:14px; }
.specs{ width:100%; border-collapse:collapse; }
.specs td{ padding:7px 0; border-bottom:1px solid #e2e8f0; font-size:11px; }
.spec-name{ width:150px; font-weight:bold; color:#334155; }
.price-box{ margin-top:18px; background:#dcfce7; border:1px solid #22c55e; border-radius:14px; padding:16px; text-align:center; }
.price-label{ font-size:11px; color:#15803d; text-transform:uppercase; font-weight:bold; letter-spacing:.5px; }
.price{ font-size:32px; font-weight:bold; color:#166534; margin-top:6px; }
.terms{ margin-top:16px; padding:14px 18px; background:#f8fafc; border-radius:12px; border:1px solid #e2e8f0; }
.footer{ margin-top:18px; text-align:center; font-size:10px; color:#64748b; line-height:1.6; }
</style>
</head>
<body>
<div class="wrapper">
<div class="header">
<table class="header-table">
<tr>
<td style="width:70%;">
<img src="'.$logoSrc.'" style="width:70px;">
<div style="color:white; font-size:24px; font-weight:bold; margin-top:10px;">DACANS COMPUTER</div>
<div style="color:#cbd5e1; font-size:11px;">Soluciones Tecnológicas Profesionales</div>
</td>
<td align="right">
<div style="color:white; font-size:28px; font-weight:bold;">COTIZACIÓN</div>
<div style="color:#93c5fd; font-size:11px;">DOCUMENTO COMERCIAL</div>
</td>
</tr>
</table>
</div>
<div class="content">
<table class="info">
<tr>
<td class="label">Cotización:</td>
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
<div class="product">
<div class="product-title">
'.htmlspecialchars($equipo['equipo_marca']).' '.htmlspecialchars($equipo['equipo_modelo']).'
</div>
<div class="product-sub">ID LOCAL #'.htmlspecialchars($equipo['id_local']).'</div>
<table class="specs">
<tr><td class="spec-name">Procesador</td><td>'.htmlspecialchars($equipo['proc_familia']).' '.htmlspecialchars($equipo['proc_generacion']).' '.htmlspecialchars($equipo['proc_modelo']).'</td></tr>
<tr><td class="spec-name">Gráficos</td><td>'.htmlspecialchars($equipo['graficos']).'</td></tr>
<tr><td class="spec-name">RAM</td><td>'.htmlspecialchars($equipo['memoria']).'</td></tr>
<tr><td class="spec-name">Disco</td><td>'.htmlspecialchars($equipo['disco']).'</td></tr>
<tr><td class="spec-name">Pantalla</td><td>'.htmlspecialchars($equipo['pantalla']).' | '.htmlspecialchars($equipo['p_resolucion']).'</td></tr>
<tr><td class="spec-name">Touch</td><td>'.(($equipo['touch'] ?? 0) == 1 ? 'SI' : 'NO').'</td></tr>
<tr><td class="spec-name">Estado</td><td>'.htmlspecialchars($equipo['estado']).'</td></tr>
<tr><td class="spec-name">Serie</td><td>'.htmlspecialchars($equipo['serie']).'</td></tr>
</table>
<div class="price-box">
<div class="price-label">PRECIO FINAL</div>
<div class="price">RD$'.$precio.'</div>
</div>
</div>
<div class="terms">
<strong>Términos y Condiciones</strong>
<ul>
<li>Cotización válida por 7 días.</li>
<li>Equipos sujetos a disponibilidad.</li>
<li>Garantía según políticas del producto.</li>
<li>No incluye software adicional.</li>
</ul>
</div>
<div class="footer">
<strong>DACANS COMPUTER</strong><br>
Santo Domingo, República Dominicana<br>
809-596-0868 | dacanscomputer@gmail.com | www.dacansdr.com
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

$dompdf->stream('cotizacion_' . $equipo['id_local'] . '.pdf', ['Attachment' => false]);
exit;