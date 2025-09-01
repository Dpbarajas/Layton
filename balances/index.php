<?php
global $db;
require_once '../includes/config.php';

include __DIR__ . "/../includes/header.php";

// FILTROS
if (!isset($_SESSION['balances']['filtros'])) $_SESSION['balances']['filtros'] = [];
$where = "where baja = 0";


// Año seleccionado
if (empty($_GET['anyo']) && empty($_GET['trimestre'])) {
    unset($_SESSION['balances']['filtros'][':anyo']);
}
if (!empty($_GET['anyo'])) {
    $_SESSION['balances']['filtros'][':anyo'] = $_GET['anyo'];
} else if (!isset($_SESSION['balances']['filtros'][':anyo'])) {
    $_SESSION['balances']['filtros'][':anyo'] = date('Y');
}
$where .= " AND strftime('%Y', fecha) = :anyo";

// Trimestre
if (!empty($_GET['trimestre'])) {
    if ($_GET['trimestre'] !== 'G') {
        $_SESSION['balances']['filtros'][':trimestre'] = intval($_GET['trimestre']);
        $where .= " AND CAST(((CAST(strftime('%m', fecha) AS INTEGER) - 1) / 3) + 1 AS INTEGER) = :trimestre";
    } else {
        unset($_SESSION['balances']['filtros'][':trimestre']);
    }
} else {
    $_SESSION['balances']['filtros'][':trimestre'] = intval(((date("n") - 1) / 3) + 1);
    $where .= " AND CAST(((CAST(strftime('%m', fecha) AS INTEGER) - 1) / 3) + 1 AS INTEGER) = :trimestre";
}
$trimestre = $_SESSION['balances']['filtros'][':trimestre'] ?? 0;


$anyosStmt = $db->prepare("
    SELECT strftime('%Y', fecha) as anyo FROM ingreso
    UNION
    SELECT strftime('%Y', fecha) as anyo FROM gasto;
");
$anyosStmt->execute();
$anyos = $anyosStmt->fetchAll(PDO::FETCH_COLUMN);

// Recuperar los ingresos de la base de datos
$sqlIngresos = "SELECT * FROM ingreso $where ORDER BY unixepoch(fecha)";
$stmtIngresos = $db->prepare($sqlIngresos);

foreach ($_SESSION['balances']['filtros'] as $clave => $valor) {
    $stmtIngresos->bindValue($clave, $valor);
}

$stmtIngresos->execute();
$ingresos = $stmtIngresos->fetchAll(PDO::FETCH_ASSOC);


// Recuperar los gastos de la base de datos
$sqlGastos = "SELECT * FROM gasto $where ORDER BY unixepoch(fecha)";
$stmtGastos = $db->prepare($sqlGastos);

foreach ($_SESSION['balances']['filtros'] as $clave => $valor) {
    $stmtGastos->bindValue($clave, $valor);
}

$stmtGastos->execute();
$gastos = $stmtGastos->fetchAll(PDO::FETCH_ASSOC);


$totalIngBase = 0;
$totalGasBase = 0;
$totalIngIva = 0;
$totalGasIva = 0;
$totalIngIrpf = 0;
$totalGasIrpf = 0;
$totalIngresos = 0;
$totalGastos = 0;
?>

    <!-- Selector de años y trimestres -->
    <div class="d-flex justify-content-between align-items-center mb-3 table-box">
        <!-- Año -->
        <h2>Balances</h2>

        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <!-- Trimestres -->
            <div class="btn-group" role="group" aria-label="Trimestres">
                <a href="index.php?trimestre=1"
                   class="btn btn-outline-primary <?= $trimestre === 1 ? 'active' : '' ?>">T1</a>
                <a href="index.php?trimestre=2"
                   class="btn btn-outline-primary <?= $trimestre === 2 ? 'active' : '' ?>">T2</a>
                <a href="index.php?trimestre=3"
                   class="btn btn-outline-primary <?= $trimestre === 3 ? 'active' : '' ?>">T3</a>
                <a href="index.php?trimestre=4"
                   class="btn btn-outline-primary <?= $trimestre === 4 ? 'active' : '' ?>">T4</a>
                <a href="index.php?trimestre=G" class="btn btn-outline-primary <?= $trimestre === 0 ? 'active' : '' ?>">Global</a>
            </div>


            <div class="dropdown hover-dropdown">
                <button class="btn btn-outline-primary dropdown-toggle"
                        id="entitiesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <span><?= $_SESSION['balances']['filtros'][':anyo'] ?></span>
                </button>
                <ul class="dropdown-menu" aria-labelledby="entitiesDropdown">
                    <?php foreach ($anyos as $anyo): ?>
                        <li class="dropdown hover-dropdown">
                            <a class="dropdown-item <?= $anyo === $_SESSION['balances']['filtros'][':anyo'] ? 'active' : '' ?>"
                               href="/balances/index.php?anyo=<?= $anyo ?>"><?= $anyo ?></a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>




    </div>


    <div class="table-container">
        <!-- Ingresos -->
        <div class="table-box">
            <div class="d-flex justify-content-between align-items-center mb-3 table-box ingreso">
                <h4>Ingresos</h4>
                <a href="/balances/ficha.php?tipoBalance=ingreso" class="btn btn-outline-success">Nuevo Ingreso</a>
            </div>
            <div class="scrollable-table">
                <table class="table table-bordered table-sm table-hover table-striped">
                    <thead class="table-success">
                    <tr>
                        <th>Fecha</th>
                        <th>Empresa</th>
                        <th>Base Imponible</th>
                        <th>IRPF</th>
                        <th>IVA</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ingresos as $ingreso):
                        $ingIva = $ingreso['baseImponible'] * $ingreso['iva'] / 100;
                        $ingIrpf = $ingreso['baseImponible'] * $ingreso['irpf'] / 100;
                        $ingTotal = $ingreso['baseImponible'] + $ingIva - $ingIrpf;
                        ?>
                        <tr class="clickable"
                            data-url="/balances/ficha.php?tipoBalance=ingreso&idBalance=<?= $ingreso['idIngreso'] ?>">
                            <td><?= $ingreso['fecha'] ?></td>
                            <td><?= $ingreso['empresa'] ?></td>
                            <td class="text-end"><?= number_format($ingreso['baseImponible'], 2) . " €" ?></td>
                            <td class="text-end"><?= number_format($ingIrpf, 2) . " €" ?></td>
                            <td class="text-end"><?= number_format($ingIva, 2) . " €" ?></td>
                            <td class="text-end"><?= number_format($ingTotal, 2) . " €" ?></td>
                            <td>
                                <a href="accion.php?accion=eliminar&idBalance=<?= $ingreso['idIngreso'] ?>&tipoBalance=ingreso"
                                   style="text-decoration: none;">❌</a>
                            </td>
                        </tr>
                        <?php
                        $totalIngBase += $ingreso['baseImponible'];
                        $totalIngIva += $ingIva;
                        $totalIngIrpf += $ingIrpf;
                        $totalIngresos += $ingTotal;

                    endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Gastos -->
        <div class="table-box">
            <div class="d-flex justify-content-between align-items-center mb-3 table-box gasto">
                <h4>Gastos</h4>
                <a href="/balances/ficha.php?tipoBalance=gasto" class="btn btn-outline-danger">Nuevo Gasto</a>
            </div>
            <div class="scrollable-table">
                <table class="table table-bordered table-sm table-hover table-striped">
                    <thead class="table-danger">
                    <tr>
                        <th>Fecha</th>
                        <th>Empresa</th>
                        <th>Base Imponible</th>
                        <th>IRPF</th>
                        <th>IVA</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($gastos as $gasto):
                        $gasIva = $gasto['baseImponible'] * $gasto['iva'] / 100;
                        $gasIrpf = $gasto['baseImponible'] * $gasto['irpf'] / 100;
                        $gasTotal = $gasto['baseImponible'] + $gasIva - $gasIrpf;
                        ?>
                        <tr class="clickable"
                            data-url="/balances/ficha.php?tipoBalance=gasto&idBalance=<?= $gasto['idGasto'] ?>">
                            <td><?= $gasto['fecha'] ?></td>
                            <td><?= $gasto['empresa'] ?></td>
                            <td class="text-end"><?= number_format($gasto['baseImponible'], 2) . " €" ?></td>
                            <td class="text-end"><?= number_format($gasIrpf, 2) . " €" ?></td>
                            <td class="text-end"><?= number_format($gasIva, 2) . " €" ?></td>
                            <td class="text-end"><?= number_format($gasTotal, 2) . " €" ?></td>
                            <td>
                                <a href="accion.php?accion=eliminar&idBalance=<?= $gasto['idGasto'] ?>&tipoBalance=gasto"
                                   style="text-decoration: none;">❌</a></td>
                        </tr>
                        <?php
                        $totalGasBase += $gasto['baseImponible'];
                        $totalGasIva += $gasIva;
                        $totalGasIrpf += $gasIrpf;
                        $totalGastos += $gasTotal;

                    endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <div class="table-container">
        <!-- Ingresos -->
        <div class="table-box">
            <div class="scrollable-table">
                <table class="table table-bordered table-sm">
                    <thead class="table-success">
                    <tr>
                        <th>TOTAL</th>
                        <th>Base Imponible</th>
                        <th>IRPF</th>
                        <th>IVA</th>
                        <th>Total</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td></td>
                        <td><?= number_format($totalIngBase, 2) . " €" ?></td>
                        <td><?= number_format($totalIngIrpf, 2) . " €" ?></td>
                        <td><?= number_format($totalIngIva, 2) . " €" ?></td>
                        <td><?= number_format($totalIngresos, 2) . " €" ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-box">
            <div class="scrollable-table">
                <table class="table table-bordered table-sm">
                    <thead class="table-danger">
                    <tr>
                        <th>TOTAL</th>
                        <th>Base Imponible</th>
                        <th>IRPF</th>
                        <th>IVA</th>
                        <th>Total</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td></td>
                        <td><?= number_format($totalGasBase, 2) . " €" ?></td>
                        <td><?= number_format($totalGasIrpf, 2) . " €" ?></td>
                        <td><?= number_format($totalGasIva, 2) . " €" ?></td>
                        <td><?= number_format($totalGastos, 2) . " €" ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>


<?php
include __DIR__ . "/../includes/footer.php";
