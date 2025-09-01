
<?php
global $db;
require_once __DIR__ . '/../includes/config.php';

if (isset($_GET['origen'])) $_SESSION['origen']['productos'] = $_GET['origen'];
elseif (isset($_POST['origen'])) $_SESSION['origen']['productos'] = $_POST['origen'];
else $_SESSION['origen']['productos'] = 'index.php';

$origen = $_SESSION['origen']['productos'];

// Obtener el ID del producto desde la URL
$idProducto = isset($_GET['idProducto']) ? (int)$_GET['idProducto'] : 0;
$modoEdicion = $idProducto !== 0;

// Variables iniciales
$producto = [
    'nomProd' => '',
    'retrocomision' => '',
    'proveedor' => $_SESSION['productos']['idProveedorCreado'] ?? '',
    'notasProd' => ''
];
unset($_SESSION['productos']['idProveedorCreado']);


// Si se está editando, obtener los datos existentes
if ($modoEdicion) {
    $stmt = $db->prepare("
        SELECT * FROM producto pd
            INNER JOIN proveedor pv ON pd.proveedor = pv.idProveedor
        WHERE idProducto = ?");
    $stmt->execute([$idProducto]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$producto) {
        die("Producto no encontrado");
    }
}

// Obtener listas de clientes, productos y proveedores para los selects
$proveedores = $db->query("SELECT idProveedor, nomProv FROM proveedor ORDER BY nomProv")->fetchAll(PDO::FETCH_ASSOC);

if (isset($_SESSION['errorMessage'])):
    $tipoMensaje = 'danger';
    $message = $_SESSION['errorMessage'];
    unset($_SESSION['errorMessage']);
endif;
?>

<?php include '../includes/header.php'; ?>

<?php if(!empty($tipoMensaje)): ?>
    <div class="alert alert-<?= $tipoMensaje ?> alert-dismissible fade show" role="alert">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<form id="formProductos" method="POST" action="accion.php" class="mt-0">
    <input type="hidden" id="accion" name="accion" value="<?= $modoEdicion ? "editar" : "crear" ?>">
    <input type="hidden" id="accionOriginal" name="accionOriginal" value="<?= $modoEdicion ? "editar" : "crear" ?>">

    <input type="hidden" id="origen" name="origen" value="<?= $origen ?>">
    <input type="hidden" id="origenOriginal" name="origenOriginal" value="<?= $origen ?>">

    <div class="container">
        <div class="table-box mb-2 row">
            <h2 class="mb-0"><?= $modoEdicion ? 'Editar Producto' : 'Nuevo Producto' ?></h2>
        </div>

        <input type="hidden" readonly name="idProducto" id="idProducto" value="<?= $idProducto ?>">

        <div class="table-box row">
            <!-- Main form fields -->
            <div class="col-md-8">
                <div class="mb-4">
                    <label for="nomProd" class="form-label requerido">Nombre Producto</label>
                    <input type="text" id="nomProd" name="nomProd" class="form-control" value="<?= $producto['nomProd'] ?>" required>
                </div>

                <div class="mb-4">
                    <label for="proveedor" class="form-label requerido">Proveedor</label>
                    <select name="proveedor" id="proveedor" class="form-select" required>
                        <option value="">-- Selecciona un proveedor --</option>
                        <option value="" disabled></option>
                        <?php foreach ($proveedores as $proveedor): ?>
                            <option value="<?= $proveedor['idProveedor'] ?>" <?= $proveedor['idProveedor'] == $producto['proveedor'] ? 'selected' : '' ?>>
                                <?= $proveedor['nomProv'] ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="" disabled></option>
                        <option value="0">-- Crear nuevo --</option>
                    </select>
                </div>

                <div class="subFormBox mb-4" id="creadorProveedor">

                    <div class="row d-flex justify-content-between align-items-center">
                        <h4 class="mb-0" style="width: 50%">Crear Proveedor</h4>

                        <button id="crearProveedor" type="submit" onclick="changeAction('formProductos', '/proveedores/accion.php', 'crear', '<?= $_SERVER['REQUEST_URI'] ?>', ['nomProd'], ['nomProd', 'comision'])" style="width:10%" class="btn btn-outline-success mt-2 me-4">Crear</button>
                    </div>

                    <label for="nomProv" class="form-label">Nombre Proveedor</label>
                    <input type="text" id="nomProv" name="nomProv" class="form-control proveedorInputs">

                    <label for="telefono" class="form-label mt-2">Teléfono</label>
                    <input type="number" id="telefono" name="telefono" class="form-control proveedorInputs">

                    <label for="web" class="form-label mt-2">Página Web</label>
                    <input type="text" id="web" name="web" class="form-control proveedorInputs">
                </div>

                <div class="mb-4">
                    <label for="retrocomision" class="form-label requerido">Tipo de Retrocomision</label>

                    <select name="retrocomision" id="retrocomision" class="form-select mb-2" required>
                        <option value="dia" <?= $producto['retrocomision'] === "dia" ? 'selected' : '' ?>>Por día</option>
                        <option value="mes" <?= $producto['retrocomision'] === "mes" ? 'selected' : '' ?>>Por mes</option>
                        <option value="" <?= empty($producto['retrocomision']) ? 'selected' : '' ?>>Sin R.C. asignada</option>
                    </select>
                </div>

                <div class="d-flex justify-content-between mt-5">
                    <a href="<?= $origen ?>" class="btn btn-secondary">Volver</a>
                    <button id="submitButton" type="submit" onclick="changeAction('formProductos', '/productos/accion.php', null, null, ['nomProd', 'comision', 'proveedor'], [])" class="btn btn-primary"><?= $modoEdicion ? 'Guardar Cambios' : 'Crear Producto' ?></button>
                </div>
            </div>

            <!-- Columna de notas  -->
            <div class="col-md-4">
                <label for="notasProd" class="form-label">Notas</label>
                <textarea name="notasProd" id="notasProd" rows="12" class="form-control"><?= trim($producto['notasProd'] ?? ' ') ?></textarea>
            </div>
        </div>
    </div>
</form>

<script>
    // Enseñar div para añadir proveedor
    document.addEventListener('DOMContentLoaded', function () {
        const selectProveedor = document.getElementById('proveedor');

        const divCreador = document.getElementById('creadorProveedor');

        selectProveedor.addEventListener('change', function () {
            if (selectProveedor.value === '0') {
                divCreador.style.display = 'block'; // Show it when 'Crear nuevo' selected
            } else {
                divCreador.style.display = 'none'; // Hide it otherwise
            }
        });
    });

    // Distinguir entre los dos botones
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById("formProductos").addEventListener("keydown", function (e) {
            if (e.key === "Enter") {
                e.preventDefault(); // Prevent default form submission

                if (e.target.className.includes("proveedorInputs")) {
                    document.getElementById("crearProveedor").click(); // Trigger your preferred button
                } else {
                    document.getElementById("submitButton").click(); // Trigger your preferred button

                }
            }
        });
    });
</script>

<?php include '../includes/footer.php'; ?>
