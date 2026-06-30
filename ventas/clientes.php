<?php
session_start();

// Validar sesión
if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

require_once '../config/conexion.php'; 

$mensaje_success = "";
$mensaje_error = "";
$rolSesion = $_SESSION['rol'] ?? 'user';

// ID del usuario que registra, tomado de la sesión
$id_usuario_activo = $_SESSION['id_usuario'] ?? 1;

/* =========================================================
   1. REGISTRAR NUEVO CLIENTE (POST)
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_cliente'])) {
    try {
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $rnc_cedula = trim($_POST['rnc_cedula']);
        $telefono = trim($_POST['telefono']);
        $email = trim($_POST['email']);
        $direccion = trim($_POST['direccion']);

        if (empty($nombre) || empty($apellido)) {
            throw new Exception("El nombre y el apellido son obligatorios.");
        }

        // Validar si el RNC o Cédula ya existe para evitar duplicados
        if (!empty($rnc_cedula)) {
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM clientes WHERE rnc_cedula = ?");
            $stmtCheck->execute([$rnc_cedula]);
            if ($stmtCheck->fetchColumn() > 0) {
                throw new Exception("Ya existe un cliente registrado con este RNC o Cédula.");
            }
        }

        $sqlInsert = "INSERT INTO clientes (nombre, apellido, rnc_cedula, telefono, email, direccion, estado, id_usuario) 
                      VALUES (?, ?, ?, ?, ?, ?, 1, ?)";
        
        $stmt = $pdo->prepare($sqlInsert);
        $stmt->execute([$nombre, $apellido, $rnc_cedula, $telefono, $email, $direccion, $id_usuario_activo]);

        $mensaje_success = "Cliente <strong>$nombre $apellido</strong> registrado correctamente.";
    } catch (Exception $e) {
        $mensaje_error = "Error al registrar cliente: " . $e->getMessage();
    }
}

/* =========================================================
   1.1 EDITAR CLIENTE
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_cliente'])) {

    try {

        $id_cliente = (int)$_POST['id_cliente'];

        $nombre      = trim($_POST['nombre']);
        $apellido    = trim($_POST['apellido']);
        $rnc_cedula  = trim($_POST['rnc_cedula']);
        $telefono    = trim($_POST['telefono']);
        $email       = trim($_POST['email']);
        $direccion   = trim($_POST['direccion']);

        if (empty($nombre) || empty($apellido)) {
            throw new Exception("El nombre y apellido son obligatorios.");
        }

        // Verificar duplicado de RNC/Cédula
        if (!empty($rnc_cedula)) {

            $stmt = $pdo->prepare("
                SELECT COUNT(*)
                FROM clientes
                WHERE rnc_cedula=?
                AND id_cliente<>?
            ");

            $stmt->execute([$rnc_cedula,$id_cliente]);

            if($stmt->fetchColumn()>0){
                throw new Exception("Ya existe otro cliente con ese RNC/Cédula.");
            }

        }

        $sql="UPDATE clientes SET

            nombre=?,
            apellido=?,
            rnc_cedula=?,
            telefono=?,
            email=?,
            direccion=?

            WHERE id_cliente=?";

        $stmt=$pdo->prepare($sql);

        $stmt->execute([
            $nombre,
            $apellido,
            $rnc_cedula,
            $telefono,
            $email,
            $direccion,
            $id_cliente
        ]);

        $mensaje_success="Cliente actualizado correctamente.";

    } catch(Exception $e){

        $mensaje_error=$e->getMessage();

    }

}

/* =========================================================
   2. CAMBIAR ESTADO DEL CLIENTE (ACTIVAR/DESACTIVAR)
========================================================= */
if (isset($_GET['toggle_id']) && isset($_GET['estado_actual'])) {
    try {
        $id_c = (int)$_GET['toggle_id'];
        $nuevo_estado = ($_GET['estado_actual'] == '1') ? 0 : 1;

        $stmtUpdate = $pdo->prepare("UPDATE clientes SET estado = ? WHERE id_cliente = ?");
        $stmtUpdate->execute([$nuevo_estado, $id_c]);

        header("Location: clientes.php");
        exit;
    } catch (Exception $e) {
        $mensaje_error = "Error al cambiar el estado: " . $e->getMessage();
    }
}

/* =========================================================
   3. OBTENER LISTADO DE CLIENTES
========================================================= */
try {
    $queryClientes = "SELECT c.*, u.usuario AS nombre_usuario 
                      FROM clientes c
                      LEFT JOIN usuarios u ON c.id_usuario = u.id 
                      ORDER BY c.id_cliente DESC";
    $listado_clientes = $pdo->query($queryClientes)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al consultar clientes: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes | DACANS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

    <style>
    body {
        background-color: #f8fafc;
        font-family: system-ui, sans-serif;
    }

    table.dataTable {
        width: 100% !important;
        border-collapse: separate !important;
        border-spacing: 0;
        font-size: 13px;
    }

    table.dataTable thead th {
        background: #0f172a;
        color: white;
        border: none !important;
        padding: 14px !important;
    }

    table.dataTable tbody td {
        padding: 12px 14px !important;
        border-bottom: 1px solid #e2e8f0;
        vertical-align: middle;
    }
    </style>
</head>

<body class="p-4 md:p-8">

    <div class="max-w-7xl mx-auto">

        <?php if (!empty($mensaje_success)): ?>
        <div
            class="mb-6 p-4 bg-emerald-100 border border-emerald-300 text-emerald-800 rounded-2xl flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-circle-check text-xl"></i>
            <div><?= $mensaje_success ?></div>
        </div>
        <?php endif; ?>
        <?php if (!empty($mensaje_error)): ?>
        <div
            class="mb-6 p-4 bg-red-100 border border-red-300 text-red-800 rounded-2xl flex items-center gap-2 shadow-sm">
            <i class="fa-solid fa-circle-xmark text-xl"></i>
            <div><?= $mensaje_error ?></div>
        </div>
        <?php endif; ?>

        <div
            class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm">
            <div class="flex items-center gap-4">
                <div
                    class="bg-blue-600 text-white w-12 h-12 rounded-2xl flex items-center justify-center text-xl shadow-lg shadow-blue-500/20">
                    <i class="fa-solid fa-user-group"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-black text-slate-900 tracking-tight">Directorio de Clientes</h1>
                    <p class="text-xs text-slate-500">Altas, modificaciones corporativas y control de crédito fiscal
                        (RNC)</p>
                </div>
            </div>
            <a href="index.php"
                class="bg-slate-900 hover:bg-black text-white px-5 py-2.5 rounded-xl font-black text-xs transition flex items-center gap-1">
                <i class="fa-solid fa-cash-register"></i> Ir a Ventas
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <div class="bg-white p-6 rounded-3xl border border-slate-200 shadow-sm h-fit">
                <h3
                    class="text-sm font-black text-slate-800 uppercase tracking-wider mb-5 pb-2 border-b border-slate-100">
                    <i class="fa-solid fa-user-plus text-blue-500 mr-1"></i> Nuevo Cliente
                </h3>

                <form method="POST" class="space-y-4 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Nombre</label>
                            <input type="text" name="nombre" required placeholder="Ej: Juan"
                                class="w-full bg-slate-50 border border-slate-200 p-2.5 rounded-xl font-medium text-slate-800 focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Apellido</label>
                            <input type="text" name="apellido" required placeholder="Ej: Pérez"
                                class="w-full bg-slate-50 border border-slate-200 p-2.5 rounded-xl font-medium text-slate-800 focus:outline-none focus:border-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">RNC o Cédula
                            (Identificación)</label>
                        <input type="text" name="rnc_cedula" placeholder="Ej: 101-XXXXX-X o 402-XXXXXXX-X"
                            class="w-full bg-slate-50 border border-slate-200 p-2.5 rounded-xl font-mono font-bold text-slate-800 focus:outline-none focus:border-blue-500">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Teléfono</label>
                            <input type="text" name="telefono" placeholder="809-555-5555"
                                class="w-full bg-slate-50 border border-slate-200 p-2.5 rounded-xl font-medium text-slate-800 focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Email</label>
                            <input type="email" name="email" placeholder="juan@correo.com"
                                class="w-full bg-slate-50 border border-slate-200 p-2.5 rounded-xl font-medium text-slate-800 focus:outline-none focus:border-blue-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold uppercase text-slate-400 mb-1">Dirección Residencial /
                            Fiscal</label>
                        <textarea name="direccion" rows="3" placeholder="Calle, No., Ensanche, Ciudad..."
                            class="w-full bg-slate-50 border border-slate-200 p-2.5 rounded-xl font-medium text-slate-800 focus:outline-none focus:border-blue-500"></textarea>
                    </div>

                    <button type="submit" name="registrar_cliente"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-xl font-black transition shadow-md shadow-blue-500/10">
                        <i class="fa-solid fa-floppy-disk mr-1"></i> Guardar Cliente
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white p-6 rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider mb-4">
                    <i class="fa-solid fa-list-ul text-emerald-500 mr-1"></i> Clientes Registrados
                </h3>

                <div class="overflow-x-auto">
                    <table id="tablaClientes" class="display w-full">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>RNC / Cédula</th>
                                <th>Contacto</th>
                                <th>Registrado Por</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($listado_clientes as $c): 
                            // --- LÓGICA DINÁMICA DE ENLACE DE VALORACIÓN ---
                            $whatsapp_link = "#";
                            $tiene_telefono = !empty($c['telefono']);
                            
                            if ($tiene_telefono) {
                                // 1. Limpiar el formato del número telefónico (quitar guiones y espacios)
                                $tel_limpio = preg_replace('/[^0-9]/', '', $c['telefono']);
                                if (strlen($tel_limpio) === 10) { $tel_limpio = "1" . $tel_limpio; } // Prefijo RD si aplica

                                // 2. Formatear nombre para la URL exclusiva de valorar.php
                                $nombre_completo = $c['nombre'] . ' ' . $c['apellido'];
                                $nombre_url = str_replace(' ', '_', $nombre_completo);

                                // 3. Construir el mensaje comercial personalizado
                                $mensaje_wa = "¡Hola " . htmlspecialchars($c['nombre']) . "! Te saludamos desde DACANS Computers. 💻 Queremos saber qué tal te ha parecido nuestra atención y el rendimiento de tus equipos. Nos ayudarías muchísimo dedicándonos un minuto aquí: https://dacansdr.com/valorar.php?cliente=" . $nombre_url;
                                
                                $whatsapp_link = "https://api.whatsapp.com/send?phone=" . $tel_limpio . "&text=" . urlencode($mensaje_wa);
                            }
                        ?>
                            <tr>
                                <td class="font-bold text-slate-400">#<?= $c['id_cliente'] ?></td>
                                <td class="font-bold text-slate-900">
                                    <?= htmlspecialchars($c['nombre'] . ' ' . $c['apellido']) ?></td>
                                <td class="font-mono font-bold text-slate-600">
                                    <?= !empty($c['rnc_cedula']) ? htmlspecialchars($c['rnc_cedula']) : '<span class="text-slate-300 font-sans font-normal">Ninguno</span>' ?>
                                </td>
                                <td>
                                    <div class="flex flex-col gap-0.5 text-slate-600 text-xs">
                                        <span><i
                                                class="fa-solid fa-phone text-slate-400 mr-1 text-[10px]"></i><?= htmlspecialchars($c['telefono'] ?? 'N/A') ?></span>
                                        <span><i
                                                class="fa-solid fa-envelope text-slate-400 mr-1 text-[10px]"></i><?= htmlspecialchars($c['email'] ?? 'N/A') ?></span>
                                    </div>
                                </td>
                                <td class="font-semibold text-blue-600 text-xs">
                                    <i
                                        class="fa-solid fa-user text-[10px] mr-1 text-slate-400"></i><?= htmlspecialchars($c['nombre_usuario'] ?? 'Sistema') ?>
                                </td>
                                <td>
                                    <?php if ($c['estado'] == 1): ?>
                                    <span
                                        class="px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-100 text-emerald-800">Activo</span>
                                    <?php else: ?>
                                    <span
                                        class="px-2.5 py-1 rounded-full text-[10px] font-black bg-rose-100 text-rose-800">Inactivo</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="flex items-center gap-1.5">
                                        <?php if ($tiene_telefono && $c['estado'] == 1): ?>
                                        <a href="<?= $whatsapp_link ?>" target="_blank"
                                            class="p-2 bg-emerald-50 text-emerald-600 hover:bg-emerald-500 hover:text-white rounded-lg text-xs font-bold transition flex items-center justify-center shadow-sm"
                                            title="Enviar Link de Valoración por WhatsApp">
                                            <i class="fa-brands fa-whatsapp text-sm"></i>
                                        </a>
                                        <?php else: ?>
                                        <button disabled
                                            class="p-2 bg-slate-100 text-slate-300 rounded-lg text-xs cursor-not-allowed flex items-center justify-center"
                                            title="Falta número telefónico o cliente inactivo">
                                            <i class="fa-brands fa-whatsapp text-sm"></i>
                                        </button>
                                        <?php endif; ?>

                                        <?php if ($rolSesion === 'admin' || $rolSesion === 'superadmin'): ?>

                                        <a href="clientes.php?toggle_id=<?= $c['id_cliente'] ?>&estado_actual=<?= $c['estado'] ?>"
                                            class="p-2 rounded-lg text-xs font-bold transition <?= $c['estado'] == 1 ? 'bg-amber-50 text-amber-600 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100' ?>"
                                            title="<?= $c['estado'] == 1 ? 'Desactivar Cliente' : 'Activar Cliente' ?>">
                                            <i
                                                class="fa-solid <?= $c['estado'] == 1 ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button"
                                        class="btnEditar p-2 bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white rounded-lg transition"
                                        title="Editar Cliente" data-id="<?= $c['id_cliente'] ?>"
                                        data-nombre="<?= htmlspecialchars($c['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-apellido="<?= htmlspecialchars($c['apellido'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-rnc="<?= htmlspecialchars($c['rnc_cedula'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-telefono="<?= htmlspecialchars($c['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-email="<?= htmlspecialchars($c['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                        data-direccion="<?= htmlspecialchars($c['direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                                        <i class="fa-solid fa-pen"></i>

                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>


    <div id="modalEditar" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">

        <div class="bg-white rounded-2xl w-full max-w-2xl p-6">

            <h2 class="text-xl font-bold mb-5">
                Editar Cliente
            </h2>

            <form method="POST">

                <input type="hidden" name="editar_cliente">
                <input type="hidden" name="id_cliente" id="edit_id">

                <div class="grid grid-cols-2 gap-4">

                    <div>
                        <label>Nombre</label>
                        <input id="edit_nombre" name="nombre" class="w-full border rounded-xl p-2">
                    </div>

                    <div>
                        <label>Apellido</label>
                        <input id="edit_apellido" name="apellido" class="w-full border rounded-xl p-2">
                    </div>

                    <div>
                        <label>RNC/Cédula</label>
                        <input id="edit_rnc" name="rnc_cedula" class="w-full border rounded-xl p-2">
                    </div>

                    <div>
                        <label>Teléfono</label>
                        <input id="edit_telefono" name="telefono" class="w-full border rounded-xl p-2">
                    </div>

                    <div class="col-span-2">
                        <label>Email</label>
                        <input id="edit_email" name="email" class="w-full border rounded-xl p-2">
                    </div>

                    <div class="col-span-2">
                        <label>Dirección</label>

                        <textarea id="edit_direccion" name="direccion" rows="3"
                            class="w-full border rounded-xl p-2"></textarea>

                    </div>

                </div>

                <div class="flex justify-end gap-3 mt-6">

                    <button type="button" id="cerrarModal" class="px-5 py-2 bg-gray-200 rounded-xl">

                        Cancelar

                    </button>

                    <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-xl">

                        Guardar Cambios

                    </button>

                </div>

            </form>

        </div>

    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script>
    $(document).ready(function() {
        $('#tablaClientes').DataTable({
            responsive: true,
            pageLength: 10,
            order: [
                [0, "desc"]
            ], // Ordenar por ID de forma descendente
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            }
        });
    });

    const modal = document.getElementById("modalEditar");

    document.querySelectorAll(".btnEditar").forEach(btn => {

        btn.addEventListener("click", function() {

            document.getElementById("edit_id").value = this.dataset.id;
            document.getElementById("edit_nombre").value = this.dataset.nombre;
            document.getElementById("edit_apellido").value = this.dataset.apellido;
            document.getElementById("edit_rnc").value = this.dataset.rnc;
            document.getElementById("edit_telefono").value = this.dataset.telefono;
            document.getElementById("edit_email").value = this.dataset.email;
            document.getElementById("edit_direccion").value = this.dataset.direccion;

            modal.classList.remove("hidden");
            modal.classList.add("flex");

        });

    });

    document.getElementById("cerrarModal").onclick = function() {

        modal.classList.remove("flex");
        modal.classList.add("hidden");

    };

    modal.onclick = function(e) {

        if (e.target === modal) {

            modal.classList.remove("flex");
            modal.classList.add("hidden");

        }

    }
    </script>




</body>

</html>