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

/* =========================================================
   1. PROCESAR ACCIONES (POST) - AGREGAR NUEVO BLOQUE NCF
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_ncf'])) {
    try {
        $tipo_comprobante  = trim($_POST['tipo_comprobante']);
        $prefijo           = strtoupper(trim($_POST['prefijo']));
        $secuencia_desde   = (int)$_POST['secuencia_desde'];
        $secuencia_hasta   = (int)$_POST['secuencia_hasta'];
        $fecha_vencimiento = !empty($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : null;
        $secuencia_actual  = $secuencia_desde; 

        if (empty($tipo_comprobante) || empty($prefijo) || $secuencia_desde <= 0 || $secuencia_hasta <= 0) {
            throw new Exception("Todos los campos obligatorios deben ser completados.");
        }
        if ($secuencia_desde > $secuencia_hasta) {
            throw new Exception("La secuencia 'Desde' no puede ser mayor que la secuencia 'Hasta'.");
        }

        $stmtDesactivar = $pdo->prepare("UPDATE control_ncf SET estado = 0 WHERE prefijo = ?");
        $stmtDesactivar->execute([$prefijo]);

        // Se agrega 'fecha_vencimiento' al INSERT
        $sqlInsert = "INSERT INTO control_ncf (tipo_comprobante, prefijo, secuencia_desde, secuencia_hasta, secuencia_actual, fecha_vencimiento, estado) 
                      VALUES (?, ?, ?, ?, ?, ?, 1)";
        $stmtInsert = $pdo->prepare($sqlInsert);
        $stmtInsert->execute([$tipo_comprobante, $prefijo, $secuencia_desde, $secuencia_hasta, $secuencia_actual, $fecha_vencimiento]);

        $mensaje_success = "¡Bloque de NCF registrado y activado correctamente!";
    } catch (Exception $e) {
        $mensaje_error = "Error: " . $e->getMessage();
    }
}

/* =========================================================
   1B. PROCESAR ACCIONES (POST) - EDITAR BLOQUE NCF
========================================================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_ncf'])) {
    try {
        $id_ncf            = (int)$_POST['id_ncf'];
        $tipo_comprobante  = trim($_POST['tipo_comprobante']);
        $prefijo           = strtoupper(trim($_POST['prefijo']));
        $secuencia_desde   = (int)$_POST['secuencia_desde'];
        $secuencia_hasta   = (int)$_POST['secuencia_hasta'];
        $secuencia_actual  = (int)$_POST['secuencia_actual'];
        $fecha_vencimiento = !empty($_POST['fecha_vencimiento']) ? $_POST['fecha_vencimiento'] : null;

        if ($id_ncf <= 0 || empty($tipo_comprobante) || empty($prefijo) || $secuencia_desde <= 0 || $secuencia_hasta <= 0 || $secuencia_actual <= 0) {
            throw new Exception("Todos los campos son obligatorios y válidos.");
        }
        if ($secuencia_desde > $secuencia_hasta) {
            throw new Exception("La secuencia 'Desde' no puede ser mayor que la secuencia 'Hasta'.");
        }
        if ($secuencia_actual < $secuencia_desde || $secuencia_actual > ($secuencia_hasta + 1)) {
            throw new Exception("La secuencia actual debe estar dentro del rango autorizado.");
        }

        // Se agrega 'fecha_vencimiento = ?' al UPDATE
        $sqlUpdate = "UPDATE control_ncf 
                      SET tipo_comprobante = ?, prefijo = ?, secuencia_desde = ?, secuencia_hasta = ?, secuencia_actual = ?, fecha_vencimiento = ? 
                      WHERE id_ncf = ?";
        $stmtUpdate = $pdo->prepare($sqlUpdate);
        $stmtUpdate->execute([$tipo_comprobante, $prefijo, $secuencia_desde, $secuencia_hasta, $secuencia_actual, $fecha_vencimiento, $id_ncf]);

        $mensaje_success = "¡Bloque de NCF actualizado correctamente!";
    } catch (Exception $e) {
        $mensaje_error = "Error al editar: " . $e->getMessage();
    }
}

/* =========================================================
   2. PROCESAR ACCIONES (GET) - CAMBIAR ESTADO / DESACTIVAR
========================================================= */
if (isset($_GET['action']) && $_GET['action'] === 'toggle_estado' && isset($_GET['id'])) {
    try {
        $id_cambiar = (int)$_GET['id'];
        
        $stmtCheck = $pdo->prepare("SELECT estado FROM control_ncf WHERE id_ncf = ?");
        $stmtCheck->execute([$id_cambiar]);
        $ncf_row = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($ncf_row) {
            $nuevo_estado = ($ncf_row['estado'] == 1) ? 0 : 1;
            $stmtUpdate = $pdo->prepare("UPDATE control_ncf SET estado = ? WHERE id_ncf = ?");
            $stmtUpdate->execute([$nuevo_estado, $id_cambiar]);
            $mensaje_success = "Estado del comprobante actualizado.";
        }
    } catch (Exception $e) {
        $mensaje_error = "Error al cambiar estado: " . $e->getMessage();
    }
}

/* =========================================================
   3. RECOPILAR LISTADO DE NCF (GET)
========================================================= */
try {
    // Se añade 'fecha_vencimiento' en el SELECT de la tabla
    $stmt = $pdo->query("SELECT id_ncf, tipo_comprobante, prefijo, secuencia_desde, secuencia_hasta, secuencia_actual, fecha_vencimiento, estado FROM control_ncf ORDER BY estado DESC, prefijo ASC");
    $listado_ncf = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error crítico en la base de datos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración de NCF - DACANS Computers</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container mt-5">

        <?php if (!empty($mensaje_success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $mensaje_success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <?php if (!empty($mensaje_error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $mensaje_error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <div class="card shadow">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
                <h4 class="mb-0">Control de Comprobantes Fiscales (NCF)</h4>
                <div class="btn-group">
                    <a href="index.php" class="btn btn-outline-light me-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                            class="bi bi-arrow-left-circle me-1" viewBox="0 0 16 16">
                            <path fill-rule="evenodd"
                                d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8m15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0m-4.5-.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5z" />
                        </svg>
                        Volver al Panel
                    </a>

                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#modalNuevoNCF">
                        + Registrar Autorización DGII
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0 align-middle">
                        <thead class="table-secondary">
                            <tr>
                                <th>Tipo de Comprobante</th>
                                <th class="text-center">Prefijo</th>
                                <th class="text-center">Rango Autorizado</th>
                                <th class="text-center">Siguiente a Emitir</th>
                                <th class="text-center">Progreso / Disponibles</th>
                                <th class="text-center">Vencimiento</th>
                                <th class="text-center">Estado</th>
                                <th class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($listado_ncf)): ?>
                            <?php foreach ($listado_ncf as $ncf): 
                                $desde_f = str_pad($ncf['secuencia_desde'], 8, '0', STR_PAD_LEFT);
                                $hasta_f = str_pad($ncf['secuencia_hasta'], 8, '0', STR_PAD_LEFT);
                                $actual_f = str_pad($ncf['secuencia_actual'], 8, '0', STR_PAD_LEFT);
                                
                                $total_bloque = $ncf['secuencia_hasta'] - $ncf['secuencia_desde'] + 1;
                                $emitidos = $ncf['secuencia_actual'] - $ncf['secuencia_desde'];
                                $disponibles = $ncf['secuencia_hasta'] - $ncf['secuencia_actual'] + 1;
                                
                                $porcentaje_usado = ($emitidos / $total_bloque) * 100;
                                
                                // Formatear fecha para mostrarla limpiamente
                                $vence_f = !empty($ncf['fecha_vencimiento']) ? date('d/m/Y', strtotime($ncf['fecha_vencimiento'])) : 'No aplica';
                            ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($ncf['tipo_comprobante']) ?></div>
                                    <small class="text-muted">ID Sistema: #<?= $ncf['id_ncf'] ?></small>
                                </td>
                                <td class="text-center">
                                    <span
                                        class="badge bg-primary fs-6 font-monospace"><?= htmlspecialchars($ncf['prefijo']) ?></span>
                                </td>
                                <td class="text-center text-muted small">
                                    Desde: <?= $desde_f ?><br>
                                    Hasta: <?= $hasta_f ?>
                                </td>
                                <td class="text-center font-monospace fs-5 text-successfw-bold">
                                    <strong><?= $ncf['prefijo'] . $actual_f ?></strong>
                                </td>
                                <td style="min-width: 180px;">
                                    <div class="d-flex justify-content-between small text-muted mb-1">
                                        <span>Usados: <?= $emitidos ?></span>
                                        <span>Quedan: <strong><?= $disponibles ?></strong></span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-warning" role="progressbar"
                                            style="width: <?= $porcentaje_usado ?>%"
                                            aria-valuenow="<?= $porcentaje_usado ?>" aria-valuemin="0"
                                            aria-valuemax="100"></div>
                                    </div>
                                </td>
                                <td class="text-center small fw-bold text-secondary">
                                    <?= $vence_f ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($ncf['estado'] == 1 && $disponibles > 0): ?>
                                    <span class="badge bg-success">En Uso</span>
                                    <?php else: ?>
                                    <span class="badge bg-danger">Inactivo / Agotado</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-outline-secondary btn-editar"
                                            data-id="<?= $ncf['id_ncf'] ?>"
                                            data-tipo="<?= htmlspecialchars($ncf['tipo_comprobante']) ?>"
                                            data-prefijo="<?= htmlspecialchars($ncf['prefijo']) ?>"
                                            data-desde="<?= $ncf['secuencia_desde'] ?>"
                                            data-hasta="<?= $ncf['secuencia_hasta'] ?>"
                                            data-actual="<?= $ncf['secuencia_actual'] ?>"
                                            data-vence="<?= $ncf['fecha_vencimiento'] ?>" data-bs-toggle="modal"
                                            data-bs-target="#modalEditarNCF">
                                            Editar
                                        </button>

                                        <?php if ($ncf['estado'] == 1): ?>
                                        <a href="ncf.php?action=toggle_estado&id=<?= $ncf['id_ncf'] ?>"
                                            class="btn btn-sm btn-outline-danger">Desactivar</a>
                                        <?php else: ?>
                                        <a href="ncf.php?action=toggle_estado&id=<?= $ncf['id_ncf'] ?>"
                                            class="btn btn-sm btn-outline-success">Activar</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center p-4 text-muted">No hay registros de NCF autorizados
                                    aún.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalNuevoNCF" tabindex="-1" aria-labelledby="modalNuevoNCFLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalNuevoNCFLabel">Nueva Autorización de NCF</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form action="ncf.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tipo de Comprobante</label>
                            <select name="tipo_comprobante" class="form-select" required>
                                <option value="Factura de Crédito Fiscal">Factura de Crédito Fiscal (Llenado obligatorio
                                    de RNC)</option>
                                <option value="Factura de Consumo">Factura de Consumo (Público General)</option>
                                <option value="Comprobante Gubernamental">Comprobante Gubernamental</option>
                                <option value="Regímenes Especiales de Tributación">Regímenes Especiales</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Prefijo NCF (3 caracteres)</label>
                            <select name="prefijo" class="form-select" required>
                                <option value="B01">B01 - Crédito Fiscal</option>
                                <option value="B02">B02 - Consumo</option>
                                <option value="B15">B15 - Gubernamental</option>
                                <option value="B14">B14 - Regímenes Especiales</option>
                            </select>
                            <small class="text-muted">Nota: Activar un prefijo desactivará automáticamente el bloque
                                anterior del mismo tipo.</small>
                        </div>
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">Secuencia Desde</label>
                                <input type="number" name="secuencia_desde" class="form-control" placeholder="Ej: 1"
                                    min="1" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label fw-bold">Secuencia Hasta</label>
                                <input type="number" name="secuencia_hasta" class="form-control" placeholder="Ej: 500"
                                    min="1" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Fecha de Vencimiento (DGII)</label>
                            <input type="date" name="fecha_vencimiento" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="guardar_ncf" class="btn btn-primary">Guardar y Activar
                            bloque</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalEditarNCF" tabindex="-1" aria-labelledby="modalEditarNCFLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title" id="modalEditarNCFLabel">Editar Bloque de NCF</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <form action="ncf.php" method="POST">
                    <input type="hidden" name="id_ncf" id="edit_id_ncf">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tipo de Comprobante</label>
                            <select name="tipo_comprobante" id="edit_tipo_comprobante" class="form-select" required>
                                <option value="Factura de Crédito Fiscal">Factura de Crédito Fiscal (Llenado obligatorio
                                    de RNC)</option>
                                <option value="Factura de Consumo">Factura de Consumo (Público General)</option>
                                <option value="Comprobante Gubernamental">Comprobante Gubernamental</option>
                                <option value="Regímenes Especiales de Tributación">Regímenes Especiales</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Prefijo NCF</label>
                            <select name="prefijo" id="edit_prefijo" class="form-select" required>
                                <option value="B01">B01 - Crédito Fiscal</option>
                                <option value="B02">B02 - Consumo</option>
                                <option value="B15">B15 - Gubernamental</option>
                                <option value="B14">B14 - Regímenes Especiales</option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-4 mb-3">
                                <label class="form-label fw-bold">Desde</label>
                                <input type="number" name="secuencia_desde" id="edit_secuencia_desde"
                                    class="form-control" min="1" required>
                            </div>
                            <div class="col-4 mb-3">
                                <label class="form-label fw-bold">Hasta</label>
                                <input type="number" name="secuencia_hasta" id="edit_secuencia_hasta"
                                    class="form-control" min="1" required>
                            </div>
                            <div class="col-4 mb-3">
                                <label class="form-label fw-bold">Siguiente (Actual)</label>
                                <input type="number" name="secuencia_actual" id="edit_secuencia_actual"
                                    class="form-control" min="1" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Fecha de Vencimiento (DGII)</label>
                            <input type="date" name="fecha_vencimiento" id="edit_fecha_vencimiento"
                                class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="editar_ncf" class="btn btn-success">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const botonesEditar = document.querySelectorAll('.btn-editar');

        botonesEditar.forEach(boton => {
            boton.addEventListener('click', function() {
                // Usamos .dataset para leer correctamente los valores de los atributos data-*
                const id = this.dataset.id;
                const tipo = this.dataset.tipo;
                const prefijo = this.dataset.prefijo;
                const desde = this.dataset.desde;
                const hasta = this.dataset.hasta;
                const actual = this.dataset.actual;
                const vence = this.dataset.vence;

                // Rellenar los inputs del modal de edición
                document.getElementById('edit_id_ncf').value = id;
                document.getElementById('edit_tipo_comprobante').value = tipo;
                document.getElementById('edit_prefijo').value = prefijo;
                document.getElementById('edit_secuencia_desde').value = desde;
                document.getElementById('edit_secuencia_hasta').value = hasta;
                document.getElementById('edit_secuencia_actual').value = actual;
                document.getElementById('edit_fecha_vencimiento').value = vence;
            });
        });
    });
    </script>