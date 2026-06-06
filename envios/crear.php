<?php

session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

require '../config/conexion.php';

/* =========================================
   ELIMINAR
========================================= */

if (isset($_GET['eliminar'])) {

    $id = (int) $_GET['eliminar'];

    $stmtDelete = $pdo->prepare("
        DELETE FROM agencias_envio
        WHERE id = ?
    ");

    $stmtDelete->execute([$id]);

    header("Location: index.php?deleted=1");
    exit;
}

/* =========================================
   GUARDAR
========================================= */

if (isset($_POST['guardar'])) {

    // MEJORA: Validar si se seleccionaron municipios y unirlos mediante comas
    $ciudades_seleccionadas = $_POST['ciudad'] ?? [];
    $ciudades_string = !empty($ciudades_seleccionadas) ? implode(', ', $ciudades_seleccionadas) : '';

    $stmt = $pdo->prepare("
        INSERT INTO agencias_envio (
            provincia,
            ciudad,
            agencia,
            costo,
            telefono,
            direccion,
            comentario,
            tiempo_entrega
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $_POST['provincia'],
        $ciudades_string, // Guardamos la lista de municipios unidos por comas
        $_POST['agencia'],
        $_POST['costo'],
        $_POST['telefono'],
        $_POST['direccion'],
        $_POST['comentario'],
        $_POST['tiempo_entrega']
    ]);

    header("Location: index.php?success=1");
    exit;
}

/* =========================================
   LISTAR
========================================= */

$query = $pdo->query("
    SELECT *
    FROM agencias_envio
    ORDER BY fecha_registro DESC
");

$agencias = $query->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agencias de Envío</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100 min-h-screen">

    <div class="max-w-7xl mx-auto p-6">

        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-800">Agencias de Envío</h1>
                <p class="text-slate-500 mt-1">Administración de agencias</p>
            </div>
            <a href="index.php" class="bg-slate-800 hover:bg-slate-900 text-white px-5 py-3 rounded-xl">Volver</a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 border border-green-300 text-green-700 p-4 rounded-xl mb-5">
                Agencia registrada correctamente.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="bg-red-100 border border-red-300 text-red-700 p-4 rounded-xl mb-5">
                Agencia eliminada correctamente.
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">

            <h2 class="text-xl font-bold mb-5 text-slate-700">Nueva Agencia</h2>

            <form method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

                <div>
                    <label class="block mb-2 font-medium">Provincia</label>
                    <select name="provincia" id="provincia" required class="w-full border rounded-xl px-4 py-3 bg-white">
                        <option value="">Seleccione una provincia...</option>
                    </select>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label class="font-medium">Ciudad / Municipios (Multi-selección)</label>
                        <span class="text-[10px] text-slate-400 font-bold uppercase bg-slate-100 px-2 py-0.5 rounded">Ctrl + Click</span>
                    </div>
                    <select name="ciudad[]" id="ciudad" required multiple size="5" class="w-full border rounded-xl px-4 py-2 bg-white focus:border-blue-500 focus:outline-none disabled:bg-slate-50 disabled:text-slate-400 font-medium text-sm">
                        <option value="">Seleccione primero una provincia...</option>
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1.5"><i class="fa-solid fa-info-circle"></i> Mantén presionada la tecla <strong>Ctrl</strong> (o <strong>Cmd</strong> en Mac) para elegir varios municipios a la vez.</p>
                </div>

                <div>
                    <label class="block mb-2 font-medium">Agencia</label>
                    <input type="text" name="agencia" required class="w-full border rounded-xl px-4 py-3">
                </div>

                <div>
                    <label class="block mb-2 font-medium">Costo</label>
                    <input type="number" step="0.01" name="costo" required class="w-full border rounded-xl px-4 py-3">
                </div>

                <div>
                    <label class="block mb-2 font-medium">Teléfono</label>
                    <input type="text" name="telefono" class="w-full border rounded-xl px-4 py-3">
                </div>

                <div>
                    <label class="block mb-2 font-medium">Tiempo Entrega</label>
                    <input type="text" name="tiempo_entrega" class="w-full border rounded-xl px-4 py-3">
                </div>

                <div class="md:col-span-2 lg:col-span-3">
                    <label class="block mb-2 font-medium">Dirección</label>
                    <input type="text" name="direccion" class="w-full border rounded-xl px-4 py-3">
                </div>

                <div class="md:col-span-2 lg:col-span-3">
                    <label class="block mb-2 font-medium">Comentario</label>
                    <textarea name="comentario" rows="4" class="w-full border rounded-xl px-4 py-3"></textarea>
                </div>

                <div class="md:col-span-2 lg:col-span-3">
                    <button type="submit" name="guardar" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl font-semibold">
                        Guardar Agencia
                    </button>
                </div>

            </form>

        </div>

    </div>

    <script>
        const rdDatos = {
            "Todo el país": ["Todas las ciudades"],
            "Azua": ["Azua de Compostela", "Estebanía", "Guayabal", "Las Charcas", "Las Yayas de Viajama", "Padre Las Casas", "Peralta", "Sabana Yegua", "Tábara Arriba"],
            "Bahoruco": ["Neiba", "Galván", "Los Ríos", "Plaza Cacique", "Tamayo", "Villa Jaragua"],
            "Barahona": ["Barahona", "Cabral", "El Peñón", "Enriquillo", "Jaquimeyes", "La Ciénaga", "Las Salinas", "Paraíso", "Vicente Noble"],
            "Dajabón": ["Dajabón", "El Pino", "Loma de Cabrera", "Partido", "Restauración"],
            "Distrito Nacional": ["Santo Domingo de Guzmán"],
            "Duarte": ["San Francisco de Macorís", "Arenoso", "Castillo", "Las Guáranas", "Pimentel", "Villa Riva"],
            "El Seibo": ["El Seibo", "Miches"],
            "Elías Piña": ["Comendador", "Bánica", "El Llano", "Hondo Valle", "Juan Santiago", "Pedro Santana"],
            "Espaillat": ["Moca", "Cayetano Germosén", "Gaspar Hernández", "San Víctor"],
            "Hato Mayor": ["Hato Mayor del Rey", "El Valle", "Sabana de la Mar"],
            "Hermanas Mirabal": ["Salcedo", "Tenares", "Villa Tapia"],
            "Independencia": ["Jimaní", "Duvergé", "La Descubierta", "Mella", "Postrer Río"],
            "La Altagracia": ["Higüey", "San Rafael del Yuma"],
            "La Romana": ["La Romana", "Guaymate", "Villa Hermosa"],
            "La Vega": ["La Vega", "Constanza", "Jarabacoa", "Jima Abajo"],
            "María Trinidad Sánchez": ["Nagua", "Cabrera", "El Factor", "Río San Juan"],
            "Monseñor Nouel": ["Bonao", "Maimón", "Piedra Blanca"],
            "Monte Cristi": ["Monte Cristi", "Castañuelas", "Guayubín", "Las Matas de Santa Cruz", "Pepillo Salcedo", "Villa Vásquez"],
            "Monte Plata": ["Monte Plata", "Bayaguana", "Peralvillo", "Sabana Grande de Boyá", "Yamasá"],
            "Pedernales": ["Pedernales", "Oviedo"],
            "Peravia": ["Baní", "Matanzas", "Nizao"],
            "Puerto Plata": ["Puerto Plata", "Altamira", "Guananico", "Imbert", "Los Hidalgos", "Luperón", "San Felipe de Puerto Plata", "Sosúa", "Villa Isabela", "Villa Montellano"],
            "Samaná": ["Samaná", "Las Terrenas", "Sánchez"],
            "San Cristóbal": ["San Cristóbal", "Bají de Haina", "Cambita Garabitos", "Los Cacaos", "Sabana Grande de Palenque", "San Gregorio de Nigua", "Yaguate", "Villa Altagracia"],
            "San José de Ocoa": ["San José de Ocoa", "Rancho Arriba", "Sabana Larga"],
            "San Juan": ["San Juan de la Maguana", "Bohechío", "El Cercado", "Juan de Herrera", "Las Matas de Farfán", "Vallejuelo"],
            "San Pedro de Macorís": ["San Pedro de Macorís", "Consuelo", "Guayacanes", "Quisqueya", "Ramón Santana", "San José de los Llanos"],
            "Sánchez Ramírez": ["Cotuí", "Cevicos", "Fantino", "Villa La Mata"],
            "Santiago": ["Santiago de los Caballeros", "Bisonó", "Jánico", "Licey al Medio", "Puñal", "Sabana Iglesia", "San José de las Matas", "Tamboril", "Villa González"],
            "Santiago Rodríguez": ["Sabaneta", "Monción", "Los Almácigos"],
            "Santo Domingo": ["Santo Domingo Este", "Santo Domingo Oeste", "Santo Domingo Norte", "Boca Chica", "Los Alcarrizos", "Pedro Brand", "San Antonio de Guerra"],
            "Valverde": ["Mao", "Esperanza", "Laguna Salada"]
        };

        const provinciaSelect = document.getElementById('provincia');
        const ciudadSelect = document.getElementById('ciudad');

        // Cargar las provincias en el primer select
        for (let provincia in rdDatos) {
            let option = document.createElement('option');
            option.value = provincia;
            option.textContent = provincia;
            provinciaSelect.appendChild(option);
        }

        // MODIFICACIÓN: Escuchar el cambio de provincia adaptado a multi-select
        provinciaSelect.addEventListener('change', function() {
            const provinciaSeleccionada = this.value;
            
            // Limpiar ciudades previas
            ciudadSelect.innerHTML = '';
            
            if (provinciaSeleccionada !== "") {
                ciudadSelect.disabled = false;
                const ciudades = rdDatos[provinciaSeleccionada];
                
                ciudades.forEach(function(ciudad) {
                    let option = document.createElement('option');
                    option.value = ciudad;
                    option.textContent = ciudad;
                    ciudadSelect.appendChild(option);
                });
            } else {
                ciudadSelect.disabled = true;
                let optionDefecto = document.createElement('option');
                optionDefecto.value = "";
                optionDefecto.textContent = "Seleccione primero una provincia...";
                ciudadSelect.appendChild(optionDefecto);
            }
        });
    </script>
</body>
</html>