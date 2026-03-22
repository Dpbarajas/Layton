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


if (!isset($_SESSION['contratos']['filtros'])) $_SESSION['contratos']['filtros'] = [];
$where = "WHERE c.baja = 0";

// Cliente
if (!empty($_POST['cliente'])) {
    $where .= " AND cl.idCliente = :idCliente";
    $_SESSION['contratos']['filtros'][':idCliente'] = $_POST['cliente'];
} else if (isset($_SESSION['contratos']['filtros'][':idCliente']) && !isset($_GET['resetear'])) {
    $where .= " AND cl.idCliente = :idCliente";
} else {
    unset($_SESSION['contratos']['filtros'][':idCliente']);
}

// Proveedor
if (!empty($_POST['proveedor'])) {
    $where .= " AND pv.idProveedor = :idProveedor";
    $_SESSION['contratos']['filtros'][':idProveedor'] = $_POST['proveedor'];
} else if (isset($_SESSION['contratos']['filtros'][':idProveedor']) && !isset($_GET['resetear'])) {
    $where .= " AND pv.idProveedor = :idProveedor";
} else {
    unset($_SESSION['contratos']['filtros'][':idProveedor']);
}

// Producto
if (!empty($_POST['producto'])) {
    $where .= " AND pd.idProducto = :idProducto";
    $_SESSION['contratos']['filtros'][':idProducto'] = $_POST['producto'];
} else if (isset($_SESSION['contratos']['filtros'][':idProducto']) && !isset($_GET['resetear'])) {
    $where .= " AND pd.idProducto = :idProducto";
} else {
    unset($_SESSION['contratos']['filtros'][':idProducto']);
}

// Notas
if (!empty($_POST['notas'])) {
    $where .= " AND UPPER(c.notasContrato) LIKE :notas";

    $notasOriginal = $_POST['notas'];
    $_SESSION['contratos']['filtros'][':notas'] = "%" . strtoupper($notasOriginal) . "%";
} else if (isset($_SESSION['contratos']['filtros'][':notas']) && !isset($_GET['resetear'])) {
    $where .= " AND UPPER(c.notasContrato) LIKE :notas";

    $notasOriginal = str_replace("%", "", strtolower($_SESSION['contratos']['filtros'][':notas']));
} else {
    $notasOriginal = '';
    unset($_SESSION['contratos']['filtros'][':notas']);
}


// Leer tipo de fecha seleccionado
$tipoFecha = ($_POST['tipoFecha'] ?? ($_SESSION['contratos']['tipoFecha'] ?? 'fechaVenta'));

// Guardar en sesión
if ($tipoFecha && !isset($_GET['resetear'])) {
    $_SESSION['contratos']['tipoFecha'] = $tipoFecha;
} else {
    unset($_SESSION['contratos']['tipoFecha']);
}

// Fecha Desde
if (!empty($_POST['fechaDesde']) && !isset($_GET['resetear'])) {
    $where .= " AND $tipoFecha >= :fechaDesde";
    $_SESSION['contratos']['filtros'][':fechaDesde'] = $_POST['fechaDesde'];
} else if (isset($_SESSION['contratos']['filtros'][':fechaDesde']) && !isset($_GET['resetear'])) {
    $where .= " AND $tipoFecha >= :fechaDesde";
} else {
    unset($_SESSION['contratos']['filtros'][':fechaDesde']);
}

// Fecha Hasta
if (!empty($_POST['fechaHasta']) && !isset($_GET['resetear'])) {
    $where .= " AND $tipoFecha <= :fechaHasta";
    $_SESSION['contratos']['filtros'][':fechaHasta'] = $_POST['fechaHasta'];
} else if (isset($_SESSION['contratos']['filtros'][':fechaHasta']) && !isset($_GET['resetear'])) {
    $where .= " AND $tipoFecha <= :fechaHasta";
} else {
    unset($_SESSION['contratos']['filtros'][':fechaHasta']);
}

// Estados contrato
// Se resetean todos los filtros para evitar fallos al hacer bindParameters()
for ($i = 0; $i < 6; $i++) {
    unset($_SESSION['contratos']['filtros'][":estado$i"]);
}

if (!empty($_POST['estadosSeleccionados']) && $_POST['estadosSeleccionados'] !== '[]') {
    $estadosSeleccionados = json_decode($_POST['estadosSeleccionados']);
    $_SESSION['contratos']['estadosSeleccionados'] = $estadosSeleccionados;

    $placeholders = [];
    $numContratos = count($estadosSeleccionados);

    foreach ($estadosSeleccionados as $i => $estado) {
        $placeholders[] = ":estado$i";
        $_SESSION['contratos']['filtros'][":estado$i"] = $estado;
    }

    // Añadir condición al WHERE
    $where .= " AND c.estado IN (" . implode(", ", $placeholders) . ")";
} else if (isset($_SESSION['contratos']['estadosSeleccionados']) && !isset($_GET['resetear'])) {
    // Recuperar de la sesión si no se ha reseteado
    $estadosSeleccionados = $_SESSION['contratos']['estadosSeleccionados'];

    $placeholders = [];
    foreach ($estadosSeleccionados as $i => $estado) {
        $placeholders[] = ":estado$i";
        $_SESSION['contratos']['filtros'][":estado$i"] = $estado;
    }

    $where .= " AND c.estado IN (" . implode(", ", $placeholders) . ")";
} else {
    unset($_SESSION['contratos']['estadosSeleccionados']);
}

// Obtener contratos para la página actual
$sql = "SELECT c.*, pd.nombreProveedor, pv.nombreProveedor, cl.nombreCliente, pv.idProveedor FROM contrato c 
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

$stmtCli = $db->prepare("SELECT idCliente, nombreCliente FROM cliente");
$stmtCli->execute();
$clientes = $stmtCli->fetchAll(PDO::FETCH_ASSOC);

$stmtProv = $db->prepare("SELECT idProveedor, nombreProveedor FROM proveedor");
$stmtProv->execute();
$proveedores = $stmtProv->fetchAll(PDO::FETCH_ASSOC);

$stmtProd = $db->prepare("SELECT idProducto, nombreProveedor FROM producto");
$stmtProd->execute();
$productos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);


// Total de contratos
$sql = "SELECT COUNT(*) as total FROM contrato as c $where";

$stmt = $db->prepare($sql);
foreach ($_SESSION['contratos']['filtros'] as $clave => $valor) {
    $stmt->bindValue($clave, $valor);
}
$stmt->execute();
$totalContratos = $stmt->fetchColumn();

// Informacion sobre la paginacion
$totalPaginas = ceil($totalContratos / $contratosPorPagina);
$pagInfo = "Del " . ($offset + 1) . " al " . min($offset + $contratosPorPagina, $totalContratos) . " de " . $totalContratos . " contratos.";


// Ver notas
if (!empty($_POST['verNotas'])) {
    $_SESSION['contratos']['verNotas'] = $_POST['verNotas'] == 1;
} else if (isset($_SESSION['contratos']['verNotas']) && !isset($_GET['resetear'])) {

} else {
    $_SESSION['contratos']['verNotas'] = false;
}

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
    <form method="POST" action="index.php" class="row g-3 mb-4">
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
                        <?= $cli['nombreCliente'] ?>
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
                        <?= $prov['nombreProveedor'] ?>
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
                        <?= $prod['nombreProveedor'] ?>
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
            <input type="checkbox" id="verNotas" name="verNotas" class="autosubmit form-check-input"
                   style="transform: scale(1.5);"
                   value="1" <?= $_SESSION['contratos']['verNotas'] ? 'checked' : '' ?>>
        </div>

        <div class="w-100"></div> <!-- Salto de fila -->

        <div class="col-md-2">
            <label for="tipoFecha" class="form-label">Tipo de fecha</label>
            <!-- Selector del tipo de fecha -->
            <select name="tipoFecha" id="tipoFecha" class="form-select">
                <option value="fechaVenta" <?= $tipoFecha === 'fechaVenta' ? 'selected' : '' ?>>Fecha Venta</option>
                <option value="fechaActivacion" <?= $tipoFecha === 'fechaActivacion' ? 'selected' : '' ?>>Fecha
                    Activación
                </option>
                <option value="fechaFacturacion" <?= $tipoFecha === 'fechaFacturacion' ? 'selected' : '' ?>>Fecha
                    Facturación
                </option>
            </select>
        </div>

        <div class="col-md-4 d-flex">
            <!-- Desde -->
            <div class="flex-fill me-2">
                <label for="fechaDesde" class="form-label">Desde</label>
                <input type="date" name="fechaDesde" id="fechaDesde" class="form-control"
                       value="<?= $_SESSION['contratos']['filtros'][':fechaDesde'] ?? '' ?>">
            </div>

            <!-- Hasta -->
            <div class="flex-fill">
                <label for="fechaHasta" class="form-label">Hasta</label>
                <input type="date" name="fechaHasta" id="fechaHasta" class="form-control"
                       value="<?= $_SESSION['contratos']['filtros'][':fechaHasta'] ?? '' ?>">
            </div>
        </div>

        <div class="col-md-1"></div>

        <div id="estadoSelector" class="estado-selector col-md-3">
            <label for="estadosSeleccionados" class="form-label">Estados</label>

            <div class="estado-seleccionados form-control" id="inputSeleccionados"></div>

            <div class="dropdown-menu w-100 p-2" id="listaEstados">
                <?php
                $seleccionados = $_SESSION['contratos']['estadosSeleccionados'] ?? [];

                foreach ($colores as $estado => $color):

                    $selected = in_array($estado, $seleccionados) ? 'selected' : '';
                    ?>
                    <div class="estado-opcion <?= $selected ?>"
                         data-estado="<?= $estado ?>"
                         data-color="<?= $color ?>">
                        <span class="color-dot" style="background-color: <?= $color ?>"></span>
                        <?= $estado ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Campo oculto para enviar al backend -->
            <input type="hidden" name="estadosSeleccionados" id="estadosSeleccionados" value=<?= json_encode($seleccionados) ?>>
        </div>

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
            <a href="index.php?orderBy=nombreCliente"
               class="link-secondary link-offset-2 link-underline-opacity-50 link-underline-opacity-100-hover">
                Cliente
            </a>
            <?= iconoOrden("nombreCliente", $orderColumn, $orderDirection) ?>
        </th>
        <th class="text-nowrap">
            <a href="index.php?orderBy=nombreProveedor"
               class="link-secondary link-offset-2 link-underline-opacity-50 link-underline-opacity-100-hover">
                Producto
            </a>
            <?= iconoOrden("nombreProveedor", $orderColumn, $orderDirection) ?>
        </th>
        <th class="text-nowrap">
            <a href="index.php?orderBy=nombreProveedor"
               class="link-secondary link-offset-2 link-underline-opacity-50 link-underline-opacity-100-hover">
                Proveedor
            </a>
            <?= iconoOrden("nombreProveedor", $orderColumn, $orderDirection) ?>
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
    if(count($contratos) > 0):
        foreach ($contratos as $contrato):
            $rowClass = $i % 2 === 0 ? 'table-light' : 'table-secondary';
            $i++;
            ?>
            <tr class="<?= $rowClass ?> divisor">
                <td>
                    <div style="cursor: pointer"
                         onclick="facturarContrato(<?= ($contrato['estado'] !== 'Facturado') ? 'true' : 'false' ?>, <?= $contrato['idContrato'] ?>)">
                        <img src="/assets/img/<?= ($contrato['estado'] !== 'Facturado') ? 'check-empty.svg' : 'check-full.svg' ?>"
                    </div>
                </td>
                <td style="box-shadow: inset 0 0 15px <?= $colores[$contrato['estado']] ?>">
                    <?= $contrato['estado']; ?>
                </td>
                <td>
                    <a href="/clientes/ficha.php?origen=/contratos/index.php&idCliente=<?= $contrato['cliente'] ?>"
                       class="link-dark"><?= $contrato['nombreCliente'] ?></a>
                </td>
                <td>
                    <a href="/productos/ficha.php?origen=/contratos/index.php&idProducto=<?= $contrato['producto'] ?>"
                       class="link-dark"><?= $contrato['nombreProveedor'] ?></a>
                </td>
                <td>
                    <a href="/proveedores/ficha.php?origen=/contratos/index.php&idProveedor=<?= $contrato['idProveedor'] ?>"
                       class="link-dark"><?= $contrato['nombreProveedor'] ?></a>
                </td>
                <td><?= $contrato['comision'] . ' €' ?></td>
                <td><?= date("d/m/Y", strtotime($contrato['fechaVenta'])); ?></td>
                <td>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <?= is_null($contrato['fechaActivacion']) ? " - " : date("d/m/Y", strtotime($contrato['fechaActivacion'])) ?>
                        </div>
                        <div style="cursor: pointer"
                             onclick="activarContrato(<?= is_null($contrato['fechaActivacion']) ? 'true' : 'false' ?>, <?= $contrato['idContrato'] ?>)">
                            <img src="/assets/img/<?= is_null($contrato['fechaActivacion']) ? 'check-empty.svg' : 'check-full.svg' ?>"
                        </div>
                    </div>
                </td>
                <td><?= is_null($contrato['fechaFacturacion']) ? " - " : date("d/m/Y", strtotime($contrato['fechaFacturacion'])) ?></td>
                <td><?= is_null($contrato['fechaActivacion']) || $contrato['estado'] === 'Cancelado' ?
                        "-" :
                        floatval($contrato['comision']) - floatval($contrato['retrocomision']) . ' €'
                    ?>
                </td>
                <td class="text-center">
                    <a href="/contratos/ficha.php?idContrato=<?= $contrato['idContrato'] ?>"
                       class="btn btn-outline-primary">Editar</a>
                </td>
                <td class="text-center">
                    <?php
                    $accion = $contrato['estado'] === 'Facturado' ? 'retrocomision' :
                        ($contrato['estado'] === 'Cancelado' || $contrato['estado'] === 'Retrocomision' ? 'descancelar' : 'cancelar');

                    $estilo = $accion === 'retrocomision' ? 'btn btn-outline-rcomision' :
                        ($accion === 'cancelar' ? 'btn-outline-danger' : 'btn-outline-success px-2 py-1');
                    ?>
                    <a href="/contratos/accion.php?accion=<?= $accion ?>&idContrato=<?= $contrato['idContrato'] ?>"
                       class="btn <?= $estilo ?>"
                       id="btnCancelar"
                       data-accion="<?= $accion ?>"
                       data-id="<?= $contrato['idContrato'] ?>"
                       data-comision="<?= $contrato['comision'] ?>"
                    >
                        <?= ucfirst(
                            str_replace('retrocomision', 'r.Comision',
                                str_replace('descancelar', 'reactivar', $accion))); ?></a>
                </td>
                <td class="text-center p-0">
                    <a class="px-2" href="/contratos/accion.php?accion=eliminar&idContrato=<?= $contrato['idContrato'] ?>"
                       onclick="return confirm('Seguro que quieres borrar el contrato?')"
                    >
                        <img src="/assets/img/cross.svg">
                    </a>
                </td>
            </tr>

            <?php if ($_SESSION['contratos']['verNotas'] && (!empty($contrato['notasContrato']) || !empty($contrato['notasCancelacion']))): ?>
                <tr class="<?= $rowClass ?>">
                    <td colspan="<?= $contrato['estado'] === 'Cancelado' ? '7' : '14' ?>" style="border-top: none;">
                        <div style="max-height: 25px; overflow-y: auto; padding: 0">
                            <?= !empty($contrato['notasContrato']) ? $contrato['notasContrato'] : '-' ?>
                        </div>
                    </td>
                    <?php if ($contrato['estado'] === 'Cancelado'): ?>
                        <td colspan="6" style="border-top: none; box-shadow: inset 0 0 10px <?= $colores['Cancelado'] ?>">
                            <div style="max-height: 25px; overflow-y: auto; padding: 0">
                                <?= !empty($contrato['notasCancelacion']) ? $contrato['notasCancelacion'] : '-' ?>
                            </div>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endif;
        endforeach;
    else: ?>
        <tr class="table-secondary">
            <td class="text-center text-secondary" colspan="13">No hay contratos disponibles</td>
        </tr>
    <?php endif; ?>
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

<script>
    document.addEventListener("DOMContentLoaded", () => {
        document.getElementById("btnCancelar")
            .addEventListener("click", function (e) {
                const accion = this.dataset.accion;
                const idContrato = this.dataset.id;
                const comision = Number(this.dataset.comision);

                if (accion === "cancelar") {
                    e.preventDefault(); // no seguir el link directamente
                    const nota = prompt("Escribe el motivo de cancelación:");
                    if (nota !== null) {
                        // redirigir incluyendo la nota en la URL (URL-encoded)
                        window.location.href = `/contratos/accion.php?accion=cancelar&idContrato=${idContrato}&notaCancelacion=${encodeURIComponent(nota)}`;
                    }
                } else if (accion === "retrocomision") {
                    e.preventDefault();
                    const nota = prompt("Escribe la cantidad a sustraer [Comision = " + comision + "]:");
                    let rComision = Number(nota)

                    while (Number.isNaN(rComision) || rComision > comision) {
                        alert("No es un numero válido!");
                        rComision = Number(prompt("Escribe la cantidad a sustraer [Comision = " + comision + "]:"));
                    }

                    window.location.href = `/contratos/accion.php?accion=retrocomision&idContrato=${idContrato}&retrocomision=${rComision}`;
                }
                // si es "descancelar", sigue normal (no hacemos nada)
            });
    });
</script>

<script>
    // TODO: no cerrar la lista cuando se hace clic en uno de los estados
    document.addEventListener('DOMContentLoaded', () => {
        const listaEstados = document.getElementById('listaEstados');
        const inputSeleccionados = document.getElementById('inputSeleccionados');

        const estadosSeleccionados = document.getElementById('estadosSeleccionados');

        let seleccionados = JSON.parse(estadosSeleccionados.value);

        inputSeleccionados.addEventListener('click', () => {
            listaEstados.classList.toggle('show');
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('#estadoSelector') && !e.target.closest('#inputSeleccionados')) {
                listaEstados.classList.remove('show');
            }
        });

        // Manejo de selección
        listaEstados.querySelectorAll('.estado-opcion').forEach(opcion => {
            const color = opcion.dataset.color;
            opcion.style.setProperty('--estado-hover', color + '33'); // color translúcido
            opcion.style.setProperty('--estado-color', color);

            opcion.addEventListener('click', () => {
                const estado = opcion.dataset.estado;
                const idx = seleccionados.indexOf(estado);

                if (idx === -1) {
                    seleccionados.push(estado);
                    opcion.classList.add('selected');
                } else {
                    seleccionados.splice(idx, 1);
                    opcion.classList.remove('selected');
                }

                actualizarChips();
                listaEstados.classList.add('show');

                estadosSeleccionados.value = JSON.stringify(seleccionados);
            });
        });

        function actualizarChips() {
            inputSeleccionados.innerHTML = '';
            seleccionados.forEach(estado => {
                const color = listaEstados.querySelector(`[data-estado="${estado}"]`).dataset.color;
                const chip = document.createElement('span');
                chip.className = 'estado-chip';
                chip.style.setProperty('--chip-color', color);
                chip.innerHTML = `${estado} <button type="button" data-estado="${estado}">&times;</button>`;
                inputSeleccionados.appendChild(chip);
            });

            inputSeleccionados.querySelectorAll('button').forEach(btn => {
                btn.addEventListener('click', () => {
                    const estado = btn.dataset.estado;
                    seleccionados = seleccionados.filter(e => e !== estado);
                    listaEstados.querySelector(`[data-estado="${estado}"]`).classList.remove('selected');
                    actualizarChips();
                    estadosSeleccionados.value = JSON.stringify(seleccionados);
                });
            });
        }

        actualizarChips();
    });
</script>


<?php include __DIR__ . "/../includes/footer.php"; ?>
