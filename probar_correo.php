<?php
// Habilitar reporte de errores para depuración rápida
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Declarar los espacios de nombres de PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// 2. Cargador automático de Composer
require 'vendor/autoload.php'; 

$resultado = "";
$tipo_alerta = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_prueba'])) {
    
    $correo_destino = trim($_POST['destino'] ?? '');
    $asunto_correo  = trim($_POST['asunto'] ?? 'Correo de Prueba');
    $mensaje_correo = trim($_POST['mensaje'] ?? 'Este es un correo de prueba enviado desde mi sistema.');

    if (empty($correo_destino)) {
        $resultado = "Por favor, especifica un correo electrónico de destino.";
        $tipo_alerta = "error";
    } else {
        $mail = new PHPMailer(true);

        try {
            // --------------------------------------------------
            // CONFIGURACIÓN DEL SERVIDOR SMTP (GMAIL)
            // --------------------------------------------------
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            
            // Credenciales de Google
            $mail->Username   = 'pfernandez@dacansdr.com';          
            $mail->Password   = 'qbvi hhmq hrcb pmew'; // 🟢 Corregido: Comilla de cierre agregada
            
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            // Habilitar Debug para ver logs detallados en pantalla si falla (0 = apagado, 2 = cliente + servidor)
            $mail->SMTPDebug  = 0; 

            // --------------------------------------------------
            // DESTINATARIOS Y REMITENTE
            // --------------------------------------------------
            $mail->setFrom('pfernandez@dacansdr.com', 'Probador de Envíos');
            $mail->addAddress($correo_destino);

            // --------------------------------------------------
            // CONTENIDO DEL CORREO
            // --------------------------------------------------
            $mail->isHTML(true);
            $mail->Subject = $asunto_correo;
            
            // Cuerpo del correo con un diseño HTML básico
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; padding: 20px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;'>
                    <h2 style='color: #2563eb; margin-bottom: 10px;'>🧪 Prueba de Envío Exitosa</h2>
                    <p style='color: #334155; font-size: 14px;'>Felicidades, el servidor SMTP está respondiendo y autenticando de manera correcta.</p>
                    <hr style='border: 0; border-top: 1px solid #cbd5e1; margin: 15px 0;'>
                    <p style='font-size: 13px; color: #475569;'><strong>Mensaje enviado:</strong></p>
                    <div style='background-color: #ffffff; padding: 12px; border-left: 4px solid #2563eb; font-style: italic; color: #1e293b;'>
                        " . nl2br(htmlspecialchars($mensaje_correo)) . "
                    </div>
                    <p style='font-size: 11px; color: #94a3b8; margin-top: 20px;'>Enviado el: " . date('Y-m-d H:i:s') . "</p>
                </div>";

            // Enviar correo
            $mail->send();
            
            $resultado = "¡El correo de prueba ha sido enviado con éxito a <strong>" . htmlspecialchars($correo_destino) . "</strong>!";
            $tipo_alerta = "success";

        } catch (Exception $e) {
            $resultado = "No se pudo enviar el correo. <br><strong>Detalle del error de PHPMailer:</strong> {$mail->ErrorInfo}";
            $tipo_alerta = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Probador de Correos SMTP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-lg bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">
        
        <div class="bg-slate-900 p-6 text-white flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-paper-plane"></i>
            </div>
            <div>
                <h1 class="text-xl font-black tracking-tight">Probador SMTP</h1>
                <p class="text-xs text-slate-400">Verifica la conexión y tus credenciales de Gmail</p>
            </div>
        </div>

        <div class="p-6 space-y-6">
            
            <?php if (!empty($resultado)): ?>
                <?php if ($tipo_alerta === 'success'): ?>
                    <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl text-xs font-semibold flex items-start gap-2.5">
                        <i class="fa-solid fa-circle-check text-lg text-emerald-500 mt-0.5"></i>
                        <div><?= $resultado ?></div>
                    </div>
                <?php else: ?>
                    <div class="p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl text-xs font-semibold flex items-start gap-2.5">
                        <i class="fa-solid fa-circle-xmark text-lg text-rose-500 mt-0.5"></i>
                        <div class="break-words w-full"><?= $resultado ?></div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                
                <div>
                    <label class="block text-[11px] font-black uppercase text-slate-400 tracking-wider mb-1">Correo de Destino</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <input type="email" name="destino" placeholder="ejemplo@correo.com" required
                               class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 focus:border-blue-500 focus:bg-white rounded-xl text-sm font-semibold text-slate-800 transition focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-black uppercase text-slate-400 tracking-wider mb-1">Asunto</label>
                    <input type="text" name="asunto" value="Correo de Prueba del Sistema" required
                           class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:border-blue-500 focus:bg-white rounded-xl text-sm font-semibold text-slate-800 transition focus:outline-none">
                </div>

                <div>
                    <label class="block text-[11px] font-black uppercase text-slate-400 tracking-wider mb-1">Mensaje de Prueba</label>
                    <textarea name="mensaje" rows="4" 
                              class="w-full px-4 py-3 bg-slate-50 border border-slate-200 focus:border-blue-500 focus:bg-white rounded-xl text-sm font-medium text-slate-700 transition focus:outline-none resize-none">Hola, esta es una prueba para certificar que la integración de PHPMailer y Gmail funciona perfectamente.</textarea>
                </div>

                <button type="submit" name="enviar_prueba" 
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-black py-3.5 rounded-xl text-sm shadow-lg shadow-blue-600/10 transition flex items-center justify-center gap-2">
                    <i class="fa-solid fa-paper-plane"></i> Enviar Correo de Prueba
                </button>

            </form>
        </div>

        <div class="bg-slate-50 p-4 border-t border-slate-100 text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest">
            Entorno de pruebas
        </div>
    </div>

</body>
</html>