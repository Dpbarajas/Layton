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

if (!isset($_SESSION['productos']['orderBy'])) $_SESSION['productos']['orderBy'] = "idProducto";
if (!isset($_SESSION['productos']['order'])) $_SESSION['productos']['order'] = true;

$orderDirection = true;
if (isset($_GET['orderBy'])):
    if ($_GET['orderBy'] !== $_SESSION['productos']['orderBy']):
        $_SESSION['productos']['orderBy'] = $_GET['orderBy'];
        $_SESSION['productos']['order'] = true;
    else:
        $_SESSION['productos']['order'] = !$_SESSION['productos']['order'];
    endif;
endif;

$orderColumn = $_SESSION['productos']['orderBy'];
$orderDirection = $_SESSION['productos']['order'] ? "ASC" : "DESC";


// Cuántos productos por página
if (!isset($_SESSION['productos']['productosPorPagina'])) $_SESSION['productos']['productosPorPagina'] = 10;
$productosPorPagina = isset($_GET['productosPorPagina']) ? $_GET['productosPorPagina'] : $_SESSION['productos']['productosPorPagina'];
$_SESSION['productos']['productosPorPagina'] = $productosPorPagina;

// Página actual (por defecto 1)
if (!isset($_SESSION['productos']['pagina'])) $_SESSION['productos']['pagina'] = 1;
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : $_SESSION['productos']['pagina'];
$_SESSION['productos']['pagina'] = $paginaActual;
$offset = ($paginaActual - 1) * $productosPorPagina;


// Obtener total de productos
$totalProductos = $db->query("SELECT COUNT(*) as total FROM producto WHERE baja = 0")->fetch(PDO::FETCH_ASSOC)['total'];
$totalPaginas = ceil($totalProductos / $productosPorPagina);

// Informacion sobre la paginacion
$pagInfo = "Del " . ($offset + 1) . " al " . min($offset + $productosPorPagina, $totalProductos) . " de " . $totalProductos . " productos.";


if (!isset($_SESSION['productos']['filtros'])) $_SESSION['productos']['filtros'] = [];
$where = "WHERE p.baja = 0";


// Nombre Producto
if (!empty($_POST['nomProd'])) {
    $where .= " AND UPPER(p.nomProd) LIKE :nomProd";

    $nomProducto = $_POST['nomProd'];
    $_SESSION['productos']['filtros'][':nomProd'] = "%" . strtoupper($nomProducto) . "%";
} else {
    $nomProducto = '';
    unset($_SESSION['productos']['filtros'][':nomProd']);
}

// Proveedor
if (!empty($_POST['proveedor'])) {
    $where .= " AND UPPER(pv.nomProv) LIKE :proveedor";

    $proveedor = $_POST['proveedor'];
    $_SESSION['productos']['filtros'][':proveedor'] = "%" . strtoupper($proveedor) . "%";
} else {
    $proveedor = '';
    unset($_SESSION['productos']['filtros'][':proveedor']);
}

if (!empty($_POST['retrocomision'])) {
    if ($_POST['retrocomision'] != 'null') {
        $where .= " AND p.retrocomision = :retrocomision";

        $retrocomision = $_POST['retrocomision'];
        $_SESSION['productos']['filtros'][':retrocomision'] = $retrocomision;
    } else {
        $where .= " AND p.retrocomision IS NULL";

        $retrocomision = "null";
        unset($_SESSION['productos']['filtros'][':retrocomision']);
    }
} else {
    $retrocomision = '';
    unset($_SESSION['productos']['filtros'][':retrocomision']);
}

// Notas
if (!empty($_POST['notasProd'])) {
    $where .= " AND UPPER(p.proveedor) LIKE :notasProd";

    $notasProd = $_POST['notasProd'];
    $_SESSION['productos']['filtros'][':notasProd'] = "%" . strtoupper($notasProd) . "%";
} else {
    $notasProd = '';
    unset($_SESSION['productos']['filtros'][':notasProd']);
}


// Obtener clientes para la página actual
$sql = "SELECT p.*, pv.nomProv 
        FROM producto p 
            INNER JOIN proveedor pv on p.proveedor = pv.idProveedor
        $where ORDER BY $orderColumn $orderDirection 
        LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);

foreach ($_SESSION['productos']['filtros'] as $clave => $valor) {
    $stmt->bindValue($clave, $valor);
}

$stmt->bindValue(':limit', $productosPorPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);


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
    <h2>Productos</h2>
    <a href="/productos/ficha.php?idProducto=0" class="btn btn-success text-nowrap">
        Nuevo producto</a>
</div>

<!-- FILTROS -->
<div class="table-box mb-2 pb-0">
    <form method="POST" class="row g-3 mb-4">
        <div class="col-md-2">
            <label for="nomProd" class="form-label">Producto</label>
            <input type="text" name="nomProd" id="nomProd" class="form-control" value="<?= $nomProducto ?>">
        </div>

        <div class="col-md-2">
            <label for="proveedor" class="form-label">Proveedor</label>
            <input type="text" name="proveedor" id="proveedor" class="form-control" value="<?= $proveedor ?>">
        </div>

        <div class="col-md-1"></div>

        <div class="col-md-2">
            <label for="retrocomision" class="form-label">Retrocomision</label>
            <select name="retrocomision" id="retrocomision" class="form-select mb-2">
                <option value="" <?= $retrocomision === "" ? "selected" : "" ?> >Todas</option>
                <option value="dia" <?= $retrocomision === "dia" ? "selected" : "" ?> >Día</option>
                <option value="mes" <?= $retrocomision === "mes" ? "selected" : "" ?> >Mes</option>
                <option value="null" <?= $retrocomision === "null" ? "selected" : "" ?> >Sin R.C. asignada</option>
            </select>
        </div>


        <div class="col-md-2">
            <label for="notasProd" class="form-label">Notas</label>
            <input type="text" name="notasProd" id="notasProd" class="form-control" value="<?= $notasProd ?>"
            step="0.5">
        </div>

        <div class="col-md-1"></div>

        <div class="col-md-2 d-flex align-items-end gap-2 mb-2">
            <button type="submit" class="btn btn-outline-primary flex-fill">Filtrar</button>
            <a href="index.php" class="btn btn-outline-danger flex-fill">Reset</a>
        </div>
    </form>
</div>

<!-- LISTADO -->
<table class="table table-bordered table-striped table-hover align-middle">
    <thead class="table-primary">
    <tr>
        <th class="text-nowrap">
            <a href="index.php?orderBy=nomProd"
               class="link-secondary link-offset-2 link-underline-opacity-50 link-underline-opacity-100-hover">
                Nombre Producto
            </a>
            <?= iconoOrden("nomProd", $orderColumn, $orderDirection) ?>
        </th>
        <th class="text-nowrap">
            <a href="index.php?orderBy=nomProv"
               class="link-secondary link-offset-2 link-underline-opacity-50 link-underline-opacity-100-hover">
                Proveedor
            </a>
            <?= iconoOrden("nomProv", $orderColumn, $orderDirection) ?>
        </th>
        <th class="text-nowrap"><span class="opacity-50">Retrocomision</span></th>
        <th class="text-nowrap"><span class="opacity-50">Notas</span></th>
        <th class="text-nowrap"></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($productos as $producto): ?>
        <tr>
            <td style="border-right: none">
                <a href="/productos/ficha.php?idProducto=<?= $producto['idProducto'] ?>"
                   class="link-dark"><?= $producto['nomProd'] ?></a>
            </td>
            <td>
                <a href="/proveedores/ficha.php?origen=/productos/index.php&idProveedor=<?= $producto['proveedor'] ?>"
                   class="link-dark"><?= $producto['nomProv'] ?></a></td>
            <td><?= $producto['retrocomision'] ?? 'Sin R.C asignada' ?></td>
            <td><?= $producto['notasProd'] ?></td>
            <td class="text-center delete">
                <a href="/productos/accion.php?accion=eliminar&idProducto=<?= $producto['idProducto'] ?>">
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
    <nav aria-label="Paginación de productos">
        <ul class="pagination mb-0">

            <!-- Botón anterior -->
            <li class="page-item <?= $paginaActual <= 1 ? 'disabled' : '' ?>">
                <a class="page-link"
                   href="?pagina=<?= $paginaActual - 1 ?>&productosPorPagina=<?= $productosPorPagina ?>"
                   aria-label="Anterior">
                    &laquo;
                </a>
            </li>

            <!-- Números de página -->
            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                <li class="page-item <?= $i == $paginaActual ? 'active' : '' ?>">
                    <a class="page-link"
                       href="?pagina=<?= $i ?>&productosPorPagina=<?= $productosPorPagina ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <!-- Botón siguiente -->
            <li class="page-item <?= $paginaActual >= $totalPaginas ? 'disabled' : '' ?>">
                <a class="page-link"
                   href="?pagina=<?= $paginaActual + 1 ?>&productosPorPagina=<?= $productosPorPagina ?>"
                   aria-label="Siguiente">
                    &raquo;
                </a>
            </li>
        </ul>
    </nav>

    <!-- Selector de cantidad -->
    <form method="GET" class="d-flex align-items-center gap-2">
        <label for="productosPorPagina" class="form-label mb-0">Mostrar:</label>
        <select name="productosPorPagina" id="productosPorPagina" class="form-select form-select-sm w-auto"
                onchange="this.form.submit()">
            <?php foreach ([1, 2, 5, 10, 20, 50] as $opcion): ?>
                <option value="<?= $opcion ?>" <?= $productosPorPagina == $opcion ? 'selected' : '' ?>><?= $opcion ?></option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="pagina" value="1">
    </form>
</div>

<div>
    <p style="color: grey"><?= $pagInfo ?> </p>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
