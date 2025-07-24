
<?php
global $db;
require_once __DIR__ . '/../includes/config.php';


if (isset($_GET['origen'])) $_SESSION['origen']['proveedores'] = $_GET['origen'];
elseif (isset($_POST['origen'])) $_SESSION['origen']['proveedores'] = $_POST['origen'];
else $_SESSION['origen']['proveedores'] = 'index.php';

$origen = $_SESSION['origen']['proveedores'];

// Obtener el ID del proveedor desde la URL
$idProveedor = isset($_GET['idProveedor']) ? (int)$_GET['idProveedor'] : 0;
$modoEdicion = $idProveedor !== 0;

// Variables iniciales
$proveedor = [
    'nomProv' => '',
    'telefono' => '',
    'web' => ''
];

// Si se está editando, obtener los datos existentes
if ($modoEdicion) {
    $stmt = $db->prepare("
        SELECT * FROM proveedor pv
        WHERE idProveedor = ?");
    $stmt->execute([$idProveedor]);
    $proveedor = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$proveedor) {
        die("Proveedor no encontrado");
    }
}
?>




<?php include '../includes/header.php'; ?>

<form method="POST" action="accion.php" class="mt-3">
    <input type="hidden" id="accion" name="accion" value="<?= $modoEdicion ? "editar" : "crear" ?>">
    <input type="hidden" name="origen" value="<?= $origen ?>">

    <div class="container">
        <h2><?= $modoEdicion ? 'Editar Proveedor' : 'Nuevo Proveedor' ?></h2>

        <input type="hidden" readonly name="idProveedor" id="idProveedor" value="<?= $idProveedor ?>">

        <div class="row">
            <!-- Main form fields -->
            <div class="col-md-8">
                <div class="mb-2">
                    <label for="nomProv" class="form-label">Nombre Proveedor</label>
                    <input type="text" id="nomProv" name="nomProv" class="form-control" value="<?= $proveedor['nomProv'] ?>" required>
                </div>

                <div class="mb-2">
                    <label for="telefono" class="form-label">Telefono</label>
                    <input type="text" name="telefono" id="telefono" class="form-control" value="<?= $proveedor['telefono'] ?>">
                </div>

                <div class="mb-2">
                    <label for="web" class="form-label">Página Web</label>

                    <?php if(!empty($proveedor['web'])): ?>
                        <div class="col-md-12 d-flex align-items-end gap-2">
                            <input type="text" name="web" id="web" class="form-control" value="<?= $proveedor['web'] ?>">
                            <a href="<?= $proveedor['web'] ?>" target="_blank"><img src="/assets/img/web.svg" alt="web"></a>
                        </div>
                    <?php else: ?>
                        <input type="text" name="web" id="web" class="form-control" value="<?= $proveedor['web'] ?>">
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="<?= $origen ?>" class="btn btn-secondary">Volver</a>
                    <button type="submit" class="btn btn-primary"><?= $modoEdicion ? 'Guardar Cambios' : 'Crear Proveedor' ?></button>
                </div>
            </div>

        </div>
    </div>
</form>

<?php include '../includes/footer.php'; ?>
