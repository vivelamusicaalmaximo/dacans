<?php

session_start();

if (!isset($_SESSION['admin_logueado'])) {
    header("Location: ../login.php");
    exit;
}

require '../config/conexion.php';

// Validar que venga un ID válido por la URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = (int) $_GET['id'];

/* =========================================
   OBTENER DATOS ACTUALES DE LA AGENCIA
========================================= */
$stmtQuery = $pdo->prepare("SELECT * FROM agencias_envio WHERE id = ?");
$stmtQuery->execute([$id]);
$agencia_actual = $stmtQuery->fetch(PDO::FETCH_ASSOC);

if (!$agencia_actual) {
    // Si no existe la agencia con ese ID
    header("Location: index.php");
    exit;
}

// Convertir el string de ciudades guardado ("Municipio1, Municipio2") en un Array de PHP
// Usamos array_map y trim para limpiar espacios en blanco innecesarios
$ciudades_guardadas = !empty($agencia_actual['ciudad']) 
    ? array_map('trim', explode(',', $agencia_actual['ciudad'])) 
    : [];


/* =========================================
   ACTUALIZAR / GUARDAR CAMBIOS
========================================= */
if (isset($_POST['actualizar'])) {

    $ciudades_seleccionadas = $_POST['ciudad'] ?? [];
    $ciudades_string = !empty($ciudades_seleccionadas) ? implode(', ', $ciudades_seleccionadas) : '';

    $stmtUpdate = $pdo->prepare("
        UPDATE agencias_envio SET 
            provincia = ?,
            ciudad = ?,
            agencia = ?,
            costo = ?,
            telefono = ?,
            direccion = ?,
            comentario = ?,
            tiempo_entrega = ?
        WHERE id = ?
    ");

    $stmtUpdate->execute([
        $_POST['provincia'],
        $ciudades_string,
        $_POST['agencia'],
        $_POST['costo'],
        $_POST['telefono'],
        $_POST['direccion'],
        $_POST['comentario'],
        $_POST['tiempo_entrega'],
        $id
    ]);

    header("Location: index.php?success_edit=1");
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Agencia de Envío</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-100 min-h-screen">

    <div class="max-w-7xl mx-auto p-6">

        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-800">Editar Agencia de Envío</h1>
                <p class="text-slate-500 mt-1">Modificando registro: <?php echo htmlspecialchars($agencia_actual['agencia']); ?></p>
            </div>
            <a href="index.php" class="bg-slate-800 hover:bg-slate-900 text-white px-5 py-3 rounded-xl">Volver</a>
        </div>

        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">

            <h2 class="text-xl font-bold mb-5 text-slate-700">Datos de la Agencia</h2>

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
                        </select>
                    <p class="text-[11px] text-slate-400 mt-1.5">Mantén presionada la tecla <strong>Ctrl</strong> para añadir o quitar municipios.</p>
                </div>

                <div>
                    <label class="block mb-2 font-medium">Agencia</label>
                    <input type="text" name="agencia" required value="<?php echo htmlspecialchars($agencia_actual['agencia']); ?>" class="w-full border rounded-xl px-4 py-3">
                </div>

                <div>
                    <label class="block mb-2 font-medium">Costo</label>
                    <input type="number" step="0.01" name="costo" required value="<?php echo htmlspecialchars($agencia_actual['costo']); ?>" class="w-full border rounded-xl px-4 py-3">
                </div>

                <div>
                    <label class="block mb-2 font-medium">Teléfono</label>
                    <input type="text" name="telefono" value="<?php echo htmlspecialchars($agencia_actual['telefono']); ?>" class="w-full border rounded-xl px-4 py-3">
                </div>

                <div>
                    <label class="block mb-2 font-medium">Tiempo Entrega</label>
                    <input type="text" name="tiempo_entrega" value="<?php echo htmlspecialchars($agencia_actual['tiempo_entrega']); ?>" class="w-full border rounded-xl px-4 py-3">
                </div>

                <div class="md:col-span-2 lg:col-span-3">
                    <label class="block mb-2 font-medium">Dirección</label>
                    <input type="text" name="direccion" value="<?php echo htmlspecialchars($agencia_actual['direccion']); ?>" class="w-full border rounded-xl px-4 py-3">
                </div>

                <div class="md:col-span-2 lg:col-span-3">
                    <label class="block mb-2 font-medium">Comentario</label>
                    <textarea name="comentario" rows="4" class="w-full border rounded-xl px-4 py-3"><?php echo htmlspecialchars($agencia_actual['comentario']); ?></textarea>
                </div>

                <div class="md:col-span-2 lg:col-span-3">
                    <button type="submit" name="actualizar" class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-xl font-semibold">
                        Actualizar Cambios
                    </button>
                </div>

            </form>

        </div>

    </div>

    <script>
        const rdDatos = {
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

        // Pasar las ciudades guardadas desde PHP a una variable nativa de JavaScript
        const ciudadesGuardadas = <?php echo json_encode($ciudades_guardadas); ?>;
        const provinciaGuardada = "<?php echo $agencia_actual['provincia']; ?>";

        const provinciaSelect = document.getElementById('provincia');
        const ciudadSelect = document.getElementById('ciudad');

        // 1. Rellenar provincias y seleccionar la correcta
        for (let provincia in rdDatos) {
            let option = document.createElement('option');
            option.value = provincia;
            option.textContent = provincia;
            if (provincia === provinciaGuardada) {
                option.selected = true;
            }
            provinciaSelect.appendChild(option);
        }

        // 2. Función para cargar municipios marcando los que ya estaban elegidos
        function cargarMunicipios(provinciaSeleccionada) {
            ciudadSelect.innerHTML = '';
            
            if (provinciaSeleccionada !== "") {
                ciudadSelect.disabled = false;
                const ciudades = rdDatos[provinciaSeleccionada];
                
                ciudades.forEach(function(ciudad) {
                    let option = document.createElement('option');
                    option.value = ciudad;
                    option.textContent = ciudad;
                    
                    // Comprobar si este municipio está en la lista de guardados para seleccionarlo
                    if (ciudadesGuardadas.includes(ciudad)) {
                        option.selected = true;
                    }
                    
                    ciudadSelect.appendChild(option);
                });
            } else {
                ciudadSelect.disabled = true;
                let optionDefecto = document.createElement('option');
                optionDefecto.value = "";
                optionDefecto.textContent = "Seleccione primero una provincia...";
                ciudadSelect.appendChild(optionDefecto);
            }
        }

        // Ejecutar carga inicial con los datos existentes
        cargarMunicipios(provinciaGuardada);

        // Escuchar cambios manuales de provincia
        provinciaSelect.addEventListener('change', function() {
            cargarMunicipios(this.value);
        });
    </script>
</body>
</html>