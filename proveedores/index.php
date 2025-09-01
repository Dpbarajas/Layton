<?php
global $db;
require_once '../includes/config.php';

// Ordenacion
function iconoOrden($columna, $actual, $direccion)
{
    if ($columna == $actual) :
        return $direccion === "ASC" ? "<i class='bi bi-chevron-up'></i>" : "<i class='bi bi-chevron-down'></i>";
    else:
        return "<i class='bi bi-chevron-expand'></i>";
    endif;
}

if (!isset($_SESSION['proveedores']['orderBy'])) $_SESSION['proveedores']['orderBy'] = "idProveedor";
if (!isset($_SESSION['proveedores']['order'])) $_SESSION['proveedores']['order'] = true;

$orderDirection = true;
if (isset($_GET['orderBy'])):
    if ($_GET['orderBy'] !== $_SESSION['proveedores']['orderBy']):
        $_SESSION['proveedores']['orderBy'] = $_GET['orderBy'];
        $_SESSION['proveedores']['order'] = true;
    else:
        $_SESSION['proveedores']['order'] = !$_SESSION['proveedores']['order'];
    endif;
endif;

$orderColumn = $_SESSION['proveedores']['orderBy'];
$orderDirection = $_SESSION['proveedores']['order'] ? "ASC" : "DESC";


// Cuántos proveedores por página
if (!isset($_SESSION['proveedores']['proveedoresPorPagina'])) $_SESSION['proveedores']['proveedoresPorPagina'] = 10;
$proveedoresPorPagina = isset($_GET['proveedoresPorPagina']) ? $_GET['proveedoresPorPagina'] : $_SESSION['proveedores']['proveedoresPorPagina'];
$_SESSION['proveedores']['proveedoresPorPagina'] = $proveedoresPorPagina;

// Página actual (por defecto 1)
if (!isset($_SESSION['proveedores']['pagina'])) $_SESSION['proveedores']['pagina'] = 1;
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : $_SESSION['proveedores']['pagina'];
$_SESSION['proveedores']['pagina'] = $paginaActual;
$offset = ($paginaActual - 1) * $proveedoresPorPagina;


// Obtener total de proveedores
$totalProveedores = $db->query("SELECT COUNT(*) as total FROM proveedor WHERE baja = 0")->fetch(PDO::FETCH_ASSOC)['total'];
$totalPaginas = ceil($totalProveedores / $proveedoresPorPagina);

// Informacion sobre la paginacion
$pagInfo = "Del " . ($offset + 1) . " al " . min($offset + $proveedoresPorPagina, $totalProveedores) . " de " . $totalProveedores . " proveedores.";


if (!isset($_SESSION['proveedores']['filtros'])) $_SESSION['proveedores']['filtros'] = [];
$where = "WHERE pv.baja = 0";


// Nombre Proveedor
if (!empty($_POST['proveedor'])) {
    $where .= " AND UPPER(pv.nomProv) LIKE :proveedor";

    $proveedor = $_POST['proveedor'];
    $_SESSION['proveedores']['filtros'][':proveedor'] = "%" . strtoupper($proveedor) . "%";
} else {
    $proveedor = '';
    unset($_SESSION['proveedores']['filtros'][':proveedor']);
}


// Obtener clientes para la página actual
$sql = "SELECT *
        FROM proveedor pv
        $where ORDER BY $orderColumn $orderDirection 
        LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);

foreach ($_SESSION['proveedores']['filtros'] as $clave => $valor) {
    $stmt->bindValue($clave, $valor);
}

$stmt->bindValue(':limit', $proveedoresPorPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC);


$tipoMensaje = '';
if (isset($_SESSION['infoMessage'])):
    $tipoMensaje = 'success';
    $message = $_SESSION['infoMessage'];
    unset($_SESSION['infoMessage']);
endif;

if (isset($_SESSION['errorMessage'])):
    $tipoMensaje = 'danger';
    $message = $_SESSION['errorMessage'];
    unset($_SESSION['errorMessage']);
endif;
?>

<?php include __DIR__ . "/../includes/header.php"; ?>

<?php if (!empty($tipoMensaje)): ?>
    <div class="alert alert-<?= $tipoMensaje ?> alert-dismissible fade show" role="alert">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="table-box d-flex justify-content-between align-items-center mb-2">
    <h2>Proveedores</h2>
    <a href="/proveedores/ficha.php?idProveedor=0" class="btn btn-success text-nowrap">Nuevo
        proveedor</a>
</div>

<!-- FILTROS -->
<div class="table-box mb-2 pb-0">
    <form method="POST" class="row g-3 mb-4">

        <div class="col-md-3 mb-2">
            <label for="proveedor" class="form-label">Proveedor</label>
            <input type="text" name="proveedor" id="proveedor" class="form-control" value="<?= $proveedor ?>">
        </div>

        <div class="col-md-7"></div>

        <div class="col-md-2 d-flex align-items-end gap-2 mb-2">
            <button type="submit" class="flex-fill btn btn-outline-primary">Filtrar</button>
            <a href="index.php" class="flex-fill btn btn-outline-danger">Reset</a>
        </div>
    </form>
</div>

<!-- LISTADO -->
<table class="table table-bordered table-striped table-hover align-middle">
    <thead class="table-primary">
    <tr>
        <th class="text-nowrap">
            <a href="index.php?orderBy=nomProv"
               class="link-secondary link-offset-2 link-underline-opacity-50 link-underline-opacity-100-hover">
                Nombre Proveedor
            </a>
            <?= iconoOrden("nomProv", $orderColumn, $orderDirection) ?>
        </th>
        <th class="text-nowrap"><span class="opacity-50">Télefono</span></th>
        <th class="text-nowrap"><span class="opacity-50">Página Web</span></th>
        <th class="text-nowrap"></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($proveedores as $proveedor): ?>
        <tr>
            <td style="border-right: none">
                <a href="/proveedores/ficha.php?idProveedor=<?= $proveedor['idProveedor'] ?>"
                   class="link-dark"><?= $proveedor['nomProv'] ?></a>
            </td>
            <td><?= $proveedor['telefono'] ?></td>
            <td><a href="<?= $proveedor['web'] ?>" target="_blank"><?= $proveedor['web'] ?></a></td>
            <td class="text-center">
                <a href="/proveedores/accion.php?accion=eliminar&idProveedor=<?= $proveedor['idProveedor'] ?>"
                   style="display: flex; width: 0; height: 100%; text-align: center; text-decoration: none;"
                >
                    ❌
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<!-- PAGINACION -->
<div class="d-flex justify-content-between align-items-center my-3">

    <!-- Paginación -->
    <nav aria-label="Paginación de proveedores">
        <ul class="pagination mb-0">

            <!-- Botón anterior -->
            <li class="page-item <?= $paginaActual <= 1 ? 'disabled' : '' ?>">
                <a class="page-link"
                   href="?pagina=<?= $paginaActual - 1 ?>&proveedoresPorPagina=<?= $proveedoresPorPagina ?>"
                   aria-label="Anterior">
                    &laquo;
                </a>
            </li>

            <!-- Números de página -->
            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                <li class="page-item <?= $i == $paginaActual ? 'active' : '' ?>">
                    <a class="page-link"
                       href="?pagina=<?= $i ?>&proveedoresPorPagina=<?= $proveedoresPorPagina ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <!-- Botón siguiente -->
            <li class="page-item <?= $paginaActual >= $totalPaginas ? 'disabled' : '' ?>">
                <a class="page-link"
                   href="?pagina=<?= $paginaActual + 1 ?>&proveedoresPorPagina=<?= $proveedoresPorPagina ?>"
                   aria-label="Siguiente">
                    &raquo;
                </a>
            </li>
        </ul>
    </nav>

    <!-- Selector de cantidad -->
    <form method="GET" class="d-flex align-items-center gap-2">
        <label for="proveedoresPorPagina" class="form-label mb-0">Mostrar:</label>
        <select name="proveedoresPorPagina" id="proveedoresPorPagina" class="form-select form-select-sm w-auto"
                onchange="this.form.submit()">
            <?php foreach ([1, 2, 5, 10, 20, 50] as $opcion): ?>
                <option value="<?= $opcion ?>" <?= $proveedoresPorPagina == $opcion ? 'selected' : '' ?>><?= $opcion ?></option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="pagina" value="1">
    </form>
</div>

<div>
    <p style="color: grey"><?= $pagInfo ?> </p>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
