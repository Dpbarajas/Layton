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

if (!isset($_SESSION['clientes']['orderBy'])) $_SESSION['clientes']['orderBy'] = "idCliente";
if (!isset($_SESSION['clientes']['order'])) $_SESSION['clientes']['order'] = true;

$orderDirection = true;
if (isset($_GET['orderBy'])):
    if ($_GET['orderBy'] !== $_SESSION['clientes']['orderBy']):
        $_SESSION['clientes']['orderBy'] = $_GET['orderBy'];
        $_SESSION['clientes']['order'] = true;
    else:
        $_SESSION['clientes']['order'] = !$_SESSION['clientes']['order'];
    endif;
endif;

$orderColumn = $_SESSION['clientes']['orderBy'];
$orderDirection = $_SESSION['clientes']['order'] ? "ASC" : "DESC";


// Cuántos clientes por página
if (!isset($_SESSION['clientes']['clientesPorPagina'])) $_SESSION['clientes']['clientesPorPagina'] = 10;
$clientesPorPagina = isset($_GET['clientesPorPagina']) ? $_GET['clientesPorPagina'] : $_SESSION['clientes']['clientesPorPagina'];
$_SESSION['clientes']['clientesPorPagina'] = $clientesPorPagina;

// Página actual (por defecto 1)
if (!isset($_SESSION['clientes']['pagina'])) $_SESSION['clientes']['pagina'] = 1;
$paginaActual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : $_SESSION['clientes']['pagina'];
$_SESSION['clientes']['pagina'] = $paginaActual;
$offset = ($paginaActual - 1) * $clientesPorPagina;


// Obtener total de clientes
$totalClientes = $db->query("SELECT COUNT(*) as total FROM cliente WHERE baja = 0")->fetch(PDO::FETCH_ASSOC)['total'];
$totalPaginas = ceil($totalClientes / $clientesPorPagina);

// Informacion sobre la paginacion
$pagInfo = "Del " . ($offset + 1) . " al " . min($offset + $clientesPorPagina, $totalClientes) . " de " . $totalClientes . " clientes.";


if (!isset($_SESSION['clientes']['filtros'])) $_SESSION['clientes']['filtros'] = [];
$where = "WHERE c.baja = 0";


// Nombre Cliente
if (!empty($_POST['nomCli'])) {
    $where .= " AND UPPER(c.nomCli) LIKE :nomCli";

    $nomCliente = $_POST['nomCli'];
    $_SESSION['clientes']['filtros'][':nomCli'] = "%" . strtoupper($nomCliente) . "%";
} else {
    $nomCliente = '';
    unset($_SESSION['clientes']['filtros'][':nomCli']);
}

// Nombre Cliente
if (!empty($_POST['persona_contacto'])) {
    $where .= " AND UPPER(c.persona_contacto) LIKE :persona_contacto";

    $persContacto = $_POST['persona_contacto'];
    $_SESSION['clientes']['filtros'][':persona_contacto'] = "%" . strtoupper($persContacto) . "%";
} else {
    $persContacto = '';
    unset($_SESSION['clientes']['filtros'][':persona_contacto']);
}


// Obtener clientes para la página actual
$sql = "SELECT c.* FROM cliente c 
        $where ORDER BY $orderColumn $orderDirection 
        LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);

foreach ($_SESSION['clientes']['filtros'] as $clave => $valor) {
    $stmt->bindValue($clave, $valor);
}

$stmt->bindValue(':limit', $clientesPorPagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);


$stmt->execute();
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);


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


<script>
    function activarCliente(activar, idCliente) {
        if (activar) {
            window.location.href = "accion.php?accion=activar&idCliente=" + idCliente;
        } else {
            window.location.href = "accion.php?accion=desactivar&idCliente=" + idCliente;
        }
    }
</script>


<?php include __DIR__ . "/../includes/header.php"; ?>

<?php if (!empty($tipoMensaje)): ?>
    <div class="alert alert-<?= $tipoMensaje ?> alert-dismissible fade show" role="alert">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="table-box d-flex justify-content-between align-items-center mb-2">
    <h2>Clientes</h2>
    <a href="/clientes/ficha.php?idCliente=0" class="btn btn-success text-nowrap">Nuevo cliente</a>

</div>

<!-- FILTROS -->
<div class="table-box mb-2 pb-0">
    <form method="POST" class="row g-3 mb-4">
        <div class="col-md-3">
            <label for="nomCli" class="form-label">Cliente</label>
            <input type="text" name="nomCli" id="nomCli" class="form-control" value="<?= $nomCliente ?>">
        </div>

        <div class="col-md-3">
            <label for="persona_contacto" class="form-label">Persona Contacto</label>
            <input type="text" name="persona_contacto" id="persona_contacto" class="form-control"
                   value="<?= $persContacto ?>">
        </div>

        <div class="col-md-4"></div>

        <div class="col-md-2 d-flex align-items-end gap-2">
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
            <a href="index.php?orderBy=nomCli"
               class="link-secondary link-offset-2 link-underline-opacity-50 link-underline-opacity-100-hover">
                Nombre Cliente
            </a>
            <?= iconoOrden("nomCli", $orderColumn, $orderDirection) ?>
        </th>
        <th class="text-nowrap">
            <a href="index.php?orderBy=persona_contacto"
               class="link-secondary link-offset-2 link-underline-opacity-50 link-underline-opacity-100-hover">
                Persona de Contacto
            </a>
            <?= iconoOrden("persona_contacto", $orderColumn, $orderDirection) ?>
        </th>
        <th class="text-nowrap">
            <a href="index.php?orderBy=telefono"
               class="link-secondary link-offset-2 link-underline-opacity-50 link-underline-opacity-100-hover">
                Teléfono
            </a>
            <?= iconoOrden("telefono", $orderColumn, $orderDirection) ?>
        </th>
        <th class="text-nowrap"><span class="opacity-50">Correo</span></th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($clientes as $cliente): ?>
        <tr>
            <td style="border-right: none">
                <a href="/clientes/ficha.php?idCliente=<?= $cliente['idCliente'] ?>"
                   class="link-dark"><?= $cliente['nomCli'] ?></a>
            </td>
            <td><?= $cliente['persona_contacto'] ?></td>
            <td><?= $cliente['telefono'] ?></td>
            <td><?= $cliente['correo'] ?></td>
            <td class="text-center eliminar">
                <a href="/clientes/accion.php?accion=eliminar&idContrato=<?= $cliente['idCliente'] ?>"
                   style="text-decoration: none;"
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
    <nav aria-label="Paginación de clientes">
        <ul class="pagination mb-0">

            <!-- Botón anterior -->
            <li class="page-item <?= $paginaActual <= 1 ? 'disabled' : '' ?>">
                <a class="page-link"
                   href="?pagina=<?= $paginaActual - 1 ?>&clientesPorPagina=<?= $clientesPorPagina ?>"
                   aria-label="Anterior">
                    &laquo;
                </a>
            </li>

            <!-- Números de página -->
            <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                <li class="page-item <?= $i == $paginaActual ? 'active' : '' ?>">
                    <a class="page-link"
                       href="?pagina=<?= $i ?>&clientesPorPagina=<?= $clientesPorPagina ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <!-- Botón siguiente -->
            <li class="page-item <?= $paginaActual >= $totalPaginas ? 'disabled' : '' ?>">
                <a class="page-link"
                   href="?pagina=<?= $paginaActual + 1 ?>&clientesPorPagina=<?= $clientesPorPagina ?>"
                   aria-label="Siguiente">
                    &raquo;
                </a>
            </li>
        </ul>
    </nav>

    <!-- Selector de cantidad -->
    <form method="GET" class="d-flex align-items-center gap-2">
        <label for="clientesPorPagina" class="form-label mb-0">Mostrar:</label>
        <select name="clientesPorPagina" id="clientesPorPagina" class="form-select form-select-sm w-auto"
                onchange="this.form.submit()">
            <?php foreach ([1, 2, 5, 10, 20, 50] as $opcion): ?>
                <option value="<?= $opcion ?>" <?= $clientesPorPagina == $opcion ? 'selected' : '' ?>><?= $opcion ?></option>
            <?php endforeach; ?>
        </select>
        <input type="hidden" name="pagina" value="1">
    </form>
</div>

<div>
    <p style="color: grey"><?= $pagInfo ?> </p>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
