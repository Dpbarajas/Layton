<?php
global $db;
require_once __DIR__ . '/../includes/config.php';


// Obtener el ID del cliente desde la URL
$idCliente = isset($_GET['idCliente']) ? (int)$_GET['idCliente'] : 0;
$modoEdicion = $idCliente !== 0;

if (isset($_GET['origen'])) $_SESSION['origen']['clientes'] = $_GET['origen'];
elseif (isset($_POST['origen'])) $_SESSION['origen']['clientes'] = $_POST['origen'];
else $_SESSION['origen']['clientes'] = 'index.php';

$origen = $_SESSION['origen']['clientes'];

// Variables iniciales
$cliente = [
    'idCliente' => '',
    'nombreCliente' => '',
    'personaContacto' => '',
    'telefonoCliente' => '',
    'correoCliente' => '',
    'baja' => ''
];

// Si se está editando, obtener los datos existentes
if ($modoEdicion) {
    $stmt = $db->prepare("
        SELECT * FROM cliente c 
        WHERE idCliente = ?");
    $stmt->execute([$idCliente]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        die("Cliente no encontrado");
    }
}
?>


<?php include '../includes/header.php'; ?>

<form method="POST" action="accion.php" class="mt-0">
    <input type="hidden" id="accion" name="accion" value="<?= $modoEdicion ? "editar" : "crear" ?>">
    <input type="hidden" id="origen" name="origen" value="<?= $origen ?>">

    <div class="container">
        <div class="table-box mb-2 row">
            <h2 class="mb-0"><?= $modoEdicion ? 'Editar Cliente' : 'Nuevo Cliente' ?></h2>
        </div>

        <input type="hidden" readonly name="idCliente" id="idCliente" value="<?= $idCliente ?>">

        <div class="table-box row">
            <!-- Main form fields -->
            <div class="mb-2">
                <label for="nombreCliente" class="form-label requerido">Cliente</label>
                <input type="text" required name="nombreCliente" id="nombreCliente" class="form-control"
                       value="<?= $cliente['nombreCliente'] ?>">
            </div>

            <div class="mb-2">
                <label for="personaContacto requerido" class="form-label">Persona de Contacto</label>
                <input type="text" required name="personaContacto" id="personaContacto" class="form-control"
                       value="<?= $cliente['personaContacto'] ?>">
            </div>

            <div class="mb-2">
                <label for="telefonoCliente" class="form-label">Teléfono de Contacto</label>
                <input type="tel" name="telefonoCliente" id="telefonoCliente" class="form-control"
                       value="<?= $cliente['telefonoCliente'] ?>">
            </div>

            <div class="mb-2">
                <label for="correoCliente" class="form-label">Correo de Contacto</label>
                <input type="text" name="correoCliente" id="correoCliente" class="form-control"
                       value="<?= $cliente['correoCliente'] ?>">
            </div>

            <div class="d-flex justify-content-between mt-4">
                <a href="<?= $origen ?>" class="btn btn-secondary">Volver</a>
                <button type="submit"
                        class="btn btn-primary"><?= $modoEdicion ? 'Guardar Cambios' : 'Crear Cliente' ?></button>
            </div>
        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const fechaActivacion = document.getElementById('fechaActivacion');
        const cobrado = document.getElementById('cobrado');
        const comision = document.getElementById('comision');

        fechaActivacion.addEventListener('input', function () {
            if (!fechaActivacion.value) {
                cobrado.value = '0.00';
            } else {
                cobrado.value = comision.value; // example fallback
            }
        });
    });
</script>

<?php include '../includes/footer.php'; ?>
