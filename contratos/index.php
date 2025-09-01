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

if (!isset($_SESSION['contratos']['orderBy'])) $_SESSION['contratos']['orderBy'] = "idContrato";
if (!isset($_SESSION['contratos']['order'])) $_SESSION['contratos']['order'] = true;

$orderDirection = true;
if (isset($_GET['orderBy'])):
    if ($_GET['orderBy'] !== $_SESSION['contratos']['orderBy']):
        $_SESSION['contratos']['orderBy'] = $_GET['orderBy'];
        $_SESSION['contratos']['order'] = true;
    else:
        $_SESSION['contratos']['order'] = !$_SESSION['contratos']['order'];
    endif;

    header("Location: index.php");
endif;

$orderColumn = $_SESSION['contratos']['orderBy'];
$orderDirection = $_SESSION['contratos']['order'] ? "ASC" : "DESC";


// Cuántos contratos por página
if (!isset($_SESSION['contratos']['contratosPorPagina'])) $_SESSION['contratos']['contratosPorPagina'] = 10;
$contratosPorPagina = isset($_GET['contratosPorPagina']) ? $_GET['contratosPorPagina'] : $_SESSION['contratos']['contratosPorPagina'];
$_SESSION['contratos']['contratosPorPagina'] = $contratosPorPagina;

// Página actual (por defecto 1)
if (!isset($_SESSION['contratos']['pagina'])) $_SESSION['contratos']['pagina'] = 1;
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : $_SESSION['contratos']['pagina'];
$_SESSION['contratos']['pagina'] = $paginaActual;
$offset = ($paginaActual - 1) * $contratosPorPagina;


// Obtener total de contratos
$totalContratos = $db->query("SELECT COUNT(*) as total FROM contrato WHERE baja = 0")->fetch(PDO::FETCH_ASSOC)['total'];
$totalPaginas = ceil($totalContratos / $contratosPorPagina);

// Informacion sobre la paginacion
$pagInfo = "Del " . ($offset + 1) . " al " . min($offset + $contratosPorPagina, $totalContratos) . " de " . $totalContratos . " contratos.";


if (!isset($_SESSION['contratos']['filtros'])) $_SESSION['contratos']['filtros'] = [];
$where = "WHERE c.baja = 0";

// Cliente
if (!empty($_POST['cliente'])) {
    $where .= " AND cl.idCliente = :idCliente";
    $_SESSION['contratos']['filtros'][':idCliente'] = $_POST['cliente'];
} else if (isset($_SESSION['contratos']['filtros'][':idCliente']) && !isset($_GET['resetear']) && !isset($_POST['resetear'])) {
    $where .= " AND cl.idCliente = :idCliente";
} else {
    unset($_SESSION['contratos']['filtros'][':idCliente']);
}

// Proveedor
if (!empty($_POST['proveedor'])) {
    $where .= " AND pv.idProveedor = :idProveedor";
    $_SESSION['contratos']['filtros'][':idProveedor'] = $_POST['proveedor'];
} else if (isset($_SESSION['contratos']['filtros'][':idProveedor']) && !isset($_GET['resetear']) && !isset($_POST['resetear'])) {
    $where .= " AND pv.idProveedor = :idProveedor";
} else {
    unset($_SESSION['contratos']['filtros'][':idProveedor']);
}

// Producto
if (!empty($_POST['producto'])) {
    $where .= " AND pd.idProducto = :idProducto";
    $_SESSION['contratos']['filtros'][':idProducto'] = $_POST['producto'];
} else if (isset($_SESSION['contratos']['filtros'][':idProducto']) && !isset($_GET['resetear']) && !isset($_POST['resetear'])) {
    $where .= " AND pd.idProducto = :idProducto";
} else {
    unset($_SESSION['contratos']['filtros'][':idProducto']);
}

// Notas
if (!empty($_POST['notas'])) {
    $where .= " AND UPPER(c.notasContrato) LIKE :notas";

    $notasOriginal = $_POST['notas'];
    $_SESSION['contratos']['filtros'][':notas'] = "%" . strtoupper($notasOriginal) . "%";
} else if (isset($_SESSION['contratos']['filtros'][':notas']) && !isset($_GET['resetear']) && !isset($_POST['resetear'])) {
    $where .= " AND UPPER(c.notasContrato) LIKE :notas";

    $notasOriginal = str_replace("%", "", strtolower($_SESSION['contratos']['filtros'][':notas']));
} else {
    $notasOriginal = '';
    unset($_SESSION['contratos']['filtros'][':notas']);
}

// Fecha Venta Desde
if (!empty($_POST['fechaVentaDesde'])) {
    $where .= " AND fechaVenta >= :fechaVentaDesde";

    $_SESSION['contratos']['filtros'][':fechaVentaDesde'] = $_POST['fechaVentaDesde'];
} else if (isset($_SESSION['contratos']['filtros'][':fechaVentaDesde']) && !isset($_GET['resetear']) && !isset($_POST['resetear'])) {
    $where .= " AND fechaVenta >= :fechaVentaDesde";
} else {
    unset($_SESSION['contratos']['filtros'][':fechaVentaDesde']);
}

// Fecha Venta Hasta
if (!empty($_POST['fechaVentaHasta'])) {
    $where .= " AND fechaVenta <= :fechaVentaHasta";

    $_SESSION['contratos']['filtros'][':fechaVentaHasta'] = $_POST['fechaVentaHasta'];
} else if (isset($_SESSION['contratos']['filtros'][':fechaVentaHasta']) && !isset($_GET['resetear']) && !isset($_POST['resetear'])) {
    $where .= " AND fechaVenta <= :fechaVentaHasta";
} else {
    unset($_SESSION['contratos']['filtros'][':fechaVentaHasta']);
}

// Fecha Activación Desde
if (!empty($_POST['fechaActivacionDesde'])) {
    $where .= " AND fechaActivacion >= :fechaActivacionDesde";
    $_SESSION['contratos']['filtros'][':fechaActivacionDesde'] = $_POST['fechaActivacionDesde'];
} else if (isset($_SESSION['contratos']['filtros'][':fechaActivacionDesde']) && !isset($_GET['resetear']) && !isset($_POST['resetear'])) {
    $where .= " AND fechaActivacion >= :fechaActivacionDesde";
} else {
    unset($_SESSION['contratos']['filtros'][':fechaActivacionDesde']);
}

// Fecha Activación Hasta
if (!empty($_POST['fechaActivacionHasta'])) {
    $where .= " AND fechaActivacion <= :fechaActivacionHasta";
    $_SESSION['contratos']['filtros'][':fechaActivacionHasta'] = $_POST['fechaActivacionHasta'];
} else if (isset($_SESSION['contratos']['filtros'][':fechaActivacionHasta']) && !isset($_GET['resetear']) && !isset($_POST['resetear'])) {
    $where .= " AND fechaActivacion <= :fechaActivacionHasta";
} else {
    unset($_SESSION['contratos']['filtros'][':fechaActivacionHasta']);
}

// Obtener contratos para la página actual
$sql = "SELECT c.*, pd.nomProd, pv.nomProv, cl.nomCli, pv.idProveedor FROM contrato c 
            INNER JOIN producto pd ON c.producto = pd.idProducto 
            INNER JOIN proveedor pv ON pd.proveedor = pv.idProveedor
            INNER JOIN cliente cl ON c.cliente = cl.idCliente 
            $where ORDER BY $orderColumn $orderDirection LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);

foreach ($_SESSION['contratos']['filtros'] as $clave => $valor) {
    $stmt->bindValue($clave, $valor);
}

$stmt->bindValue(':limit', $contratosPorPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

$stmt->execute();
$contratos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtCli = $db->prepare("SELECT idCliente, nomCli FROM cliente");
$stmtCli->execute();
$clientes = $stmtCli->fetchAll(PDO::FETCH_ASSOC);

$stmtProv = $db->prepare("SELECT idProveedor, nomProv FROM proveedor");
$stmtProv->execute();
$proveedores = $stmtProv->fetchAll(PDO::FETCH_ASSOC);

$stmtProd = $db->prepare("SELECT idProducto, nomProd FROM producto");
$stmtProd->execute();
$productos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);


// Ver notas
$_SESSION['contratos']['verNotas'] = isset($_POST['verNotas']);

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

global $colores;
?>

<?php include __DIR__ . "/../includes/header.php"; ?>

<?php if (!empty($tipoMensaje)): ?>
    <div class="alert alert-<?= $tipoMensaje ?> alert-dismissible fade show" role="alert">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="table-box d-flex justify-content-between align-items-center mb-2">
    <h2>Contratos</h2>
    <a href="/contratos/ficha.php?idContrato=0" class="btn btn-success text-nowrap">Nuevo
        contrato</a>
</div>

<!-- FILTROS -->
<div class="table-box pb-0 mb-2">
    <form method="POST" class="row g-3 mb-4">
        <div class="col-md-2">
            <label for="cliente" class="form-label">Cliente</label>
            <select name="cliente" id="cliente" class="form-select">
                <option value="" <?= (!isset($_SESSION['contratos']['filtros'][':idProveedor']) ? "selected" : "") ?>>
                    Todos
                </option>
                <?php foreach ($clientes as $cli): ?>
                    <option value="<?= $cli['idCliente'] ?>"
                        <?= (isset($_SESSION['contratos']['filtros'][':idCliente']) && intval($_SESSION['contratos']['filtros'][':idCliente']) === $cli['idCliente'] ? "selected" : "") ?>
                    >
                        <?= $cli['nomCli'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label for="proveedor" class="form-label">Proveedor</label>
            <select name="proveedor" id="proveedor" class="form-select">
                <option value="" <?= (!isset($_SESSION['contratos']['filtros'][':idProveedor']) ? "selected" : "") ?>>
                    Todos
                </option>
                <?php foreach ($proveedores as $prov): ?>
                    <option value="<?= $prov['idProveedor'] ?>"
                        <?= (isset($_SESSION['contratos']['filtros'][':idProveedor']) && intval($_SESSION['contratos']['filtros'][':idProveedor']) === $prov['idProveedor'] ? "selected" : "") ?>
                    >
                        <?= $prov['nomProv'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label for="producto" class="form-label">Producto</label>
            <select name="producto" id="producto" class="form-select">
                <option value="" <?= (!isset($_SESSION['contratos']['filtros'][':idProducto']) ? "selected" : "") ?>>
                    Todos
                </option>
                <?php foreach ($productos as $prod): ?>
                    <option value="<?= $prod['idProducto'] ?>"
                        <?= (isset($_SESSION['contratos']['filtros'][':idProducto']) && intval($_SESSION['contratos']['filtros'][':idProducto']) === $prod['idProducto'] ? "selected" : "") ?>
                    >
                        <?= $prod['nomProd'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-1"></div>

        <div class="col-md-4">
            <label for="notas" class="form-label">Notas</label>
            <input type="text" name="notas" id="notas" class="form-control" value="<?= $notasOriginal ?>">
        </div>

        <div class="col-md-1 d-flex flex-column align-items-center gap-2">
            <label for="verNotas" class="form-label">Ver notas</label>
            <input type="checkbox" id="verNotas" name="verNotas" class="autosubmit form-check-input" style="transform: scale(1.5);"
                   value="1" <?= $_SESSION['contratos']['verNotas'] ? 'checked' : '' ?>>
        </div>

        <div class="w-100"></div> <!-- Salto de fila -->

        <div class="col-md-3">
            <label for="fechaVentaDesde" class="form-label">Fecha Venta</label>

            <div class="d-flex w-100 gap-2">
                <input type="date" name="fechaVentaDesde" id="fechaVentaDesde" class=" w-50 form-control"
                       value="<?= $_SESSION['contratos']['filtros'][':fechaVentaDesde'] ?? '' ?>">
                <p class="mt-2">-</p>
                <input type="date" name="fechaVentaHasta" id="fechaVentaHasta" class="w-50 form-control"
                       value="<?= $_SESSION['contratos']['filtros'][':fechaVentaHasta'] ?? '' ?>">
            </div>
        </div>

        <div class="col-md-1"></div>

        <div class="col-md-3">
            <label for="fechaActivacionDesde" class="form-label">Fecha Activación</label>

            <div class="d-flex w-100 gap-2">
                <input type="date" name="fechaActivacionDesde" id="fechaActivacionDesde" class="form-control"
                       value="<?= $_SESSION['contratos']['filtros'][':fechaActivacionDesde'] ?? '' ?>">
                <p class="mt-2">-</p>
                <input type="date" name="fechaActivacionHasta" id="fechaActivacionHasta" class="form-control"
                       value="<?= $_SESSION['contratos']['filtros'][':fechaActivacionHasta'] ?? '' ?>">
            </div>
        </div>

        <div class="col-md-3"></div>

        <div class="col-md-2 d-flex align-items-end gap-2">
            <button type="submit" id="btn-submit" class="btn btn-outline-primary flex-fill mb-2">Filtrar</button>

            <a href="index.php?resetear=true" class="btn btn-outline-danger flex-fill mb-2">Resetear</a>
        </div>
    </form>
</div>


<!-- LISTADO -->
<table id="tabla-contratos" class="table table-bordered table-hover align-middle">
    <thead class="table-primary">
    <tr>
        <th class="text-nowrap"></th>
        <th class="text-nowrap">
            <a href="index.php?orderBy=estado"
               class="link-secondary link-offset-2 link-underline-opacity-50 link-underline-opacity-100-hover">
                Estado
            </a>
            <?= iconoOrden("estado", $orderColumn, $orderDirection) ?>
        </th>
        <th class="text-nowrap">
            <a href="index.php?orderBy=nomCli"
               class="link-secondary link-offset-2 link-underline-opacity-50 link-underline-opacity-100-hover">
                Cliente
            </a>
            <?= iconoOrden("nomCli", $orderColumn, $orderDirection) ?>
        </th>
        <th class="text-nowrap">
            <a href="index.php?orderBy=nomProd"
               class="link-secondary link-offset-2 link-underline-opacity-50 link-underline-opacity-100-hover">
                Producto
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
        <th class="text-nowrap">
            <a href="index.php?orderBy=comision"
               class="link-secondary link-offset-2 link-underline-opacity-50 link-underline-opacity-100-hover">
                Comision
            </a>
            <?= iconoOrden("comision", $orderColumn, $orderDirection) ?>
        </th>
        <th class="text-nowrap">
            <a href="index.php?orderBy=fechaVenta"
               class="link-secondary link-offset-2 link-underline-opacity-50 link-underline-opacity-100-hover">
                Venta
            </a>
            <?= iconoOrden("fechaVenta", $orderColumn, $orderDirection) ?>
        </th>
        <th class="text-nowrap">
            <a href="index.php?orderBy=fechaActivacion"
               class="link-secondary link-offset-2 link-underline-opacity-50 link-underline-opacity-100-hover">
                Activacion
            </a>
            <?= iconoOrden("fechaActivacion", $orderColumn, $orderDirection) ?>
        </th>
        <th class="text-nowrap">
            <a href="index.php?orderBy=fechaFacturacion"
               class="link-secondary link-offset-2 link-underline-opacity-50 link-underline-opacity-100-hover">
                Facturacion
            </a>
            <?= iconoOrden("fechaFacturacion", $orderColumn, $orderDirection) ?>
        </th>
        <th class="text-nowrap"><span class="opacity-50">Cobrado</span></th>
        <th class="text-nowrap text-center" colspan="2"><span class="opacity-50">Acciones</span></th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    <?php $i = 0;
    foreach ($contratos as $contrato):
        $rowClass = $i % 2 === 0 ? 'table-light' : 'table-secondary';
        $i++;
        ?>
        <tr class="<?= $rowClass ?> divisor">
            <td>
                <?php if ($contrato['estado'] === 'Facturado'): ?>
                    <a href="accion.php?accion=cancelar_pago&idContrato=<?= $contrato['idContrato'] ?>">
                        <img src="/assets/img/check-full.svg">
                    </a>
                <?php else: ?>
                    <a href="accion.php?accion=pagar&idContrato=<?= $contrato['idContrato'] ?>">
                        <img src="/assets/img/check-empty.svg">
                    </a>
                <?php endif; ?>
            </td>
            <td style="box-shadow: inset 0 0 15px <?= $colores[$contrato['estado']] ?>">
                <?= $contrato['estado']; ?>
            </td>
            <td>
                <a href="/clientes/ficha.php?origen=/contratos/index.php&idCliente=<?= $contrato['cliente'] ?>"
                   class="link-dark"><?= $contrato['nomCli'] ?></a>
            </td>
            <td>
                <a href="/productos/ficha.php?origen=/contratos/index.php&idProducto=<?= $contrato['producto'] ?>"
                   class="link-dark"><?= $contrato['nomProd'] ?></a>
            </td>
            <td>
                <a href="/proveedores/ficha.php?origen=/contratos/index.php&idProveedor=<?= $contrato['idProveedor'] ?>"
                   class="link-dark"><?= $contrato['nomProv'] ?></a>
            </td>
            <td><?= $contrato['comision'] . ' €' ?></td>
            <td><?= date("d/m/Y", strtotime($contrato['fechaVenta'])); ?></td>
            <td>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <?= is_null($contrato['fechaActivacion']) ? " - " : date("d/m/Y", strtotime($contrato['fechaActivacion']))?>
                    </div>
                    <div style="cursor: pointer"
                              onclick="activarContrato(<?= is_null($contrato['fechaActivacion']) ? 'true' : 'false' ?>, <?= $contrato['idContrato'] ?>)">
                        <img src="/assets/img/<?= is_null($contrato['fechaActivacion']) ? 'check-empty.svg' : 'check-full.svg' ?>"
                    </div>
                </div>
            </td>
            <td>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <?= is_null($contrato['fechaFacturacion']) ? " - " : date("d/m/Y", strtotime($contrato['fechaFacturacion']))?>
                    </div>
                    <?php if(!is_null($contrato['fechaActivacion'])): ?>
                        <div style="cursor: pointer"
                             onclick="facturarContrato(<?= is_null($contrato['fechaFacturacion']) ? 'true' : 'false' ?>, <?= $contrato['idContrato'] ?>)">
                            <img src="/assets/img/<?= is_null($contrato['fechaFacturacion']) ? 'check-empty.svg' : 'check-full.svg' ?>"
                        </div>
                    <?php endif; ?>
                </div>
            </td>
            <td><?= is_null($contrato['fechaActivacion']) || $contrato['estado'] === 'Cancelado' ? "-" : $contrato['comision'] . ' €' ?></td>
            <td class="text-center">
                <a href="/contratos/ficha.php?idContrato=<?= $contrato['idContrato'] ?>"
                   class="btn btn-outline-primary">Editar</a>
            </td>
            <td class="text-center">
                <a href="/contratos/accion.php?accion=<?= $contrato['estado'] === 'Cancelado' ? 'descancelar' : 'cancelar' ?>&idContrato=<?= $contrato['idContrato'] ?>"
                   class="btn btn-outline-<?= $contrato['estado'] === 'Cancelado' ? 'dark px-2 py-1' : 'danger' ?>" id="btn-<?= $contrato['estado'] === 'Cancelado' ? 'descancelar' : 'cancelar' ?>">
                    <?= $contrato['estado'] === 'Cancelado' ? 'Reactivar' : 'Cancelar' ?></a>
            </td>
            <td class="text-center p-0">
                <a class="px-2" href="/contratos/accion.php?accion=eliminar&idContrato=<?= $contrato['idContrato'] ?>"
                    onclick="return confirm('Seguro que quieres borrar el contrato?')"
                >
                    <img src="/assets/img/cross.svg">
                </a>
            </td>
        </tr>

        <?php if ($_SESSION['contratos']['verNotas']): ?>
        <tr class="<?= $rowClass ?>">
            <td colspan="<?= $contrato['estado'] === 'Cancelado' ? '7' : '14' ?>" style="border-top: none;">
                <div style="max-height: 25px; overflow-y: auto; padding: 0">
                    <?= ! empty($contrato['notasContrato']) ? $contrato['notasContrato'] : '-' ?>
                </div>
            </td>
            <?php if($contrato['estado'] === 'Cancelado'): ?>
                <td colspan="6" style="border-top: none; box-shadow: inset 0 0 10px <?= $colores['Cancelado']?>">
                    <div style="max-height: 25px; overflow-y: auto; padding: 0">
                        <?= ! empty($contrato['notasCancelacion']) ? $contrato['notasCancelacion'] : '-' ?>
                    </div>
                </td>
            <?php endif; ?>
        </tr>
        <?php endif; ?>
    <?php endforeach; ?>
    </tbody>
</table>

<!-- PAGINACION -->
<div class="d-flex justify-content-between align-items-center my-3">

    <!-- Paginación -->
    <nav aria-label="Paginación de contratos">
        <ul class="pagination mb-0">

            <!-- Botón anterior -->
            <li class="page-item <?= $paginaActual <= 1 ? 'disabled' : '' ?>">
                <a class="page-link"
                   href="?pagina=<?= $paginaActual - 1 ?>&contratosPorPagina=<?= $contratosPorPagina ?>"
                   aria-label="Anterior">
                    &laquo;
                </a>
            </li>

            <!-- Números de página -->
            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                <li class="page-item <?= $i == $paginaActual ? 'active' : '' ?>">
                    <a class="page-link"
                       href="?pagina=<?= $i ?>&contratosPorPagina=<?= $contratosPorPagina ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <!-- Botón siguiente -->
            <li class="page-item <?= $paginaActual >= $totalPaginas ? 'disabled' : '' ?>">
                <a class="page-link"
                   href="?pagina=<?= $paginaActual + 1 ?>&contratosPorPagina=<?= $contratosPorPagina ?>"
                   aria-label="Siguiente">
                    &raquo;
                </a>
            </li>
        </ul>
    </nav>

    <!-- Selector de cantidad -->
    <form method="GET" class="d-flex align-items-center gap-2">
        <label for="contratosPorPagina" class="form-label mb-0">Mostrar:</label>
        <select name="contratosPorPagina" id="contratosPorPagina" class="form-select form-select-sm w-auto"
                onchange="this.form.submit()">
            <?php foreach ([1, 2, 5, 10, 20, 50] as $opcion): ?>
                <option value="<?= $opcion ?>" <?= $contratosPorPagina == $opcion ? 'selected' : '' ?>><?= $opcion ?></option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="pagina" value="1">
    </form>
</div>

<div>
    <p style="color: grey"><?= $pagInfo ?> </p>
</div>


<script>
    function showNotes(idContrato = '', cancelacion) {
        const iframe = document.getElementById('notesIframe');
        iframe.src = "/modalContrato.php";
        const modal = new bootstrap.Modal(document.getElementById('notesModal'));
        modal.show();
    }
</script>

<script>
    function activarContrato(activar, idContrato, estado) {
        if (activar) {
            window.location.href = "accion.php?accion=activar&idContrato=" + idContrato;
        } else {
            window.location.href = "accion.php?accion=desactivar&idContrato=" + idContrato;
        }
    }

    function facturarContrato(activar, idContrato) {
        if (activar) {
            window.location.href = "accion.php?accion=facturar&idContrato=" + idContrato;
        } else {
            window.location.href = "accion.php?accion=desfacturar&idContrato=" + idContrato;
        }
    }
</script>


<?php include __DIR__ . "/../includes/footer.php"; ?>
