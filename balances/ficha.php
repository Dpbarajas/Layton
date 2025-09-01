<?php
global $db;
require_once __DIR__ . '/../includes/config.php';

if (isset($_GET['origen'])) $_SESSION['origen']['balances'] = $_GET['origen'];
elseif (isset($_POST['origen'])) $_SESSION['origen']['balances'] = $_POST['origen'];
else $_SESSION['origen']['balances'] = 'index.php';

$origen = $_SESSION['origen']['balances'];

// Obtener el ID del contrato desde la URL
$idBalance = isset($_GET['idBalance']) ? (int)$_GET['idBalance'] : 0;
$tipoBalance = isset($_GET['tipoBalance']) ? $_GET['tipoBalance'] : null;

if (is_null($tipoBalance)) {
    die("Error tipo balance");
}

$nombreId = "id" . ucfirst(strtolower($tipoBalance));
$modoEdicion = $idBalance !== 0;

// Variables iniciales
$balance = [
    'idIngreso' => '',
    'idGasto' => '',
    'fecha' => '',
    'empresa' => '',
    'baseImponible' => 0.00,
    'iva' => 21.00,
    'irpf' => $tipoBalance === "ingreso" ? 15.00 : 0.00,
    'baja' => ''
];

// Si se está editando, obtener los datos existentes
if ($modoEdicion) {
    $stmt = $db->prepare("
        SELECT * FROM $tipoBalance
        WHERE $nombreId = ?");
    $stmt->execute([$idBalance]);
    $balance = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$balance) {
        die("Balance no encontrado");
    }
}

// Obtener el total de documentos asociados con este balance
$stmt = $db->prepare("
    SELECT COUNT(*) as total
    FROM documento
    WHERE (idBalance = ? AND tipoBalance = ?)
");
$stmt->execute([$idBalance, $tipoBalance]);
$documentos = $stmt->fetch(PDO::FETCH_ASSOC);


?>


<?php include '../includes/header.php'; ?>

<form id="formBalance" method="POST" enctype="multipart/form-data" action="accion.php" class="mt-0">
    <input type="hidden" id="accion" name="accion" value="<?= $modoEdicion ? "editar" : "crear" ?>">

    <input type="hidden" id="origen" name="origen" value="<?= $origen ?>">

    <input type="hidden" readonly name="idBalance" id="idBalance" value="<?= $idBalance ?>">
    <input type="hidden" readonly name="tipoBalance" id="tipoBalance" value="<?= $tipoBalance ?>">

    <div class="container table-box">
        <div class="table-box <?= $tipoBalance ?> d-flex justify-content-between align-items-center mb-3">
            <h2><?= $modoEdicion ? 'Editar ' . ucfirst($tipoBalance) : 'Nuevo ' . ucfirst($tipoBalance) ?></h2>

            <?php if (!$modoEdicion): ?>
                <a class="fondo-blanco <?= $tipoBalance === "ingreso" ? 'btn btn-outline-danger' : 'btn btn-outline-success' ?>"
                   href="ficha.php?tipoBalance=<?= $tipoBalance === "ingreso" ? 'gasto' : 'ingreso' ?>">
                    Cambiar a <?= $tipoBalance === "ingreso" ? 'gasto' : 'ingreso' ?>
                </a>
            <?php endif; ?>
        </div>

        <div class="row ">
            <!-- Main form fields -->
            <div class="col-md-6">
                <div class="mb-2">
                    <label for="empresa" class="form-label requerido">Empresa</label>
                    <input type="text" required id="empresa" name="empresa" class="form-control"
                           value="<?= trim($balance['empresa']) ?>">
                </div>

                <div class="mb-2">
                    <label for="fecha" class="form-label requerido">Fecha de realización</label>
                    <input type="date" name="fecha" id="fecha" class="form-control"
                           value="<?= $balance['fecha'] ?>" required>
                </div>


                <div class="mb-2">
                    <label for="baseImponible" class="form-label requerido">Base Imponible</label>
                    <input type="text" step="0.5" name="baseImponible" id="baseImponible"
                           class="form-control dinero"
                           value="<?= sprintf("%.2f", $balance['baseImponible']) ?>€" required>
                </div>

                <div class="row mb-4">
                    <div class="col-md-2">
                        <label for="iva-perc" class="form-label requerido">IVA (%)</label>
                        <input type="number" step=0.01 name="iva-perc" id="iva-perc" class="form-control"
                               value="<?= $balance['iva'] ?? 21.00 ?>" required>
                    </div>

                    <div class="col-md-2">
                        <label for="iva-cant" class="form-label">IVA (€)</label>
                        <input type="text" name="iva-cant" id="iva-cant" class="form-control"
                               value="<?= sprintf("%.2f",$balance['iva'] * $balance['baseImponible'] / 100) ?>€" readonly>
                    </div>

                    <div class="col-md-2"></div>

                    <div class="col-md-2">
                        <label for="irpf-perc" class="form-label requerido">IRPF (%)</label>
                        <input type="number" step="0.01" name="irpf-perc" id="irpf-perc" class="form-control"
                               value="<?= $balance['irpf'] ?>" required>
                    </div>

                    <div class="col-md-2">
                        <label for="irpf-cant" class="form-label">IRPF (€)</label>
                        <input type="text" name="irpf-cant" id="irpf-cant" class="form-control"
                               value="<?= sprintf("%.2f",$balance['irpf'] * $balance['baseImponible'] / 100) ?>€" readonly>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="total" class="form-label requerido">Total</label>
                    <input type="text" step="0.01" name="total" id="total"
                           class="form-control readonly dinero"
                           value="<?= sprintf("%.2f", $balance['baseImponible'] +
                               $balance['iva'] * $balance['baseImponible'] / 100 -
                               $balance['irpf'] * $balance['baseImponible'] / 100) ?>€"
                           required>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="/balances/index.php" class="btn btn-secondary">Volver</a>
                    <button type="submit" id="submitButton"
                            class="btn btn-primary"><?= $modoEdicion ? 'Guardar Cambios' : 'Crear ' . ucfirst($tipoBalance) ?></button>
                </div>
            </div>

            <div class="col-md-1"></div>

            <!-- Columna para subir documentos -->
            <div class="col-md-5">
                <div class="d-flex justify-content-between align-items-center">
                    <label class="form-label">Adjuntar Documentos (<?= $documentos['total'] ?>)</label>

                    <button type="button" <?= $documentos['total'] > 0 ? '' : 'disabled' ?> class="btn btn-outline-primary p-0 border-0" id="btnVerArchivos" data-bs-toggle="modal" data-bs-target="#visorDocumentosModal">
                        <img src=<?= $documentos['total'] > 0 ? '/assets/img/attachments.svg' : '/assets/img/no-attachments.svg' ?>>
                    </button>
                </div>
                <div>
                    <div class="drop-zone" id="drop-zone">
                        <img src="/assets/img/plus-circle.svg" alt="Plus Circle" style="pointer-events: none;">
                        <br>
                        Arrastra y suelta archivos aquí o haz clic para seleccionarlos.
                        <input type="file"
                               id="documentos"
                               name="documentos[]"
                               accept=".pdf,.png,.jpg,.jpeg,.gif"
                               multiple
                               hidden>
                    </div>
                    <div id="lista-archivos"></div>

                </div>

            </div>
        </div>
</form>

<!-- Modal para documentos -->
<div class="modal fade" id="visorDocumentosModal" tabindex="-1" aria-labelledby="visorDocumentosModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-md-down">
        <!-- XL en desktop, fullscreen en pantallas medianas y menores -->
        <div class="modal-content d-flex flex-column" style="height: 90vh;"> <!-- Limita altura -->
            <div class="modal-header">
                <h5 class="modal-title" id="visorDocumentosModalLabel">Archivos Adjuntos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body p-0 flex-grow-1 d-flex"> <!-- Sin padding, visor ocupa todo -->
                <iframe id="iframeVisor"
                        src=""
                        class="w-100 h-100 border-0"></iframe>
            </div>
        </div>
    </div>
</div>


<?php include '../includes/footer.php'; ?>


<script>
    // Recalcular los valores de IVA e IRPF en tiempo real en base a la base imponible y los respectivos porcentajes
    document.addEventListener('DOMContentLoaded', function () {
        const baseInput = document.getElementById('baseImponible');
        const ivaPercInput = document.getElementById('iva-perc');
        const ivaCantInput = document.getElementById('iva-cant');
        const irpfPercInput = document.getElementById('irpf-perc');
        const irpfCantInput = document.getElementById('irpf-cant');

        const totalInput = document.getElementById('total');

        // Quitar símbolo € y convertir a número
        function getValor(input) {
            return parseFloat(input.value.replace(/[^\d.,-]/g, '').replace(',', '.')) || 0;
        }

        function actualizarCalculosParciales() {
            const base = getValor(baseInput);

            const ivaPerc = parseFloat(ivaPercInput.value) || 0;
            const irpfPerc = parseFloat(irpfPercInput.value) || 0;

            const ivaCant = base * ivaPerc / 100;
            const irpfCant = base * irpfPerc / 100;

            ivaCantInput.value = ivaCant.toFixed(2) + '€';
            irpfCantInput.value = irpfCant.toFixed(2) + '€';
            totalInput.value = (base + ivaCant - irpfCant).toFixed(2) + "€";
        }

        function actualizarCalculosTotal() {
            const total = getValor(totalInput);

            const ivaPerc = parseFloat(ivaPercInput.value) || 0;
            const irpfPerc = parseFloat(irpfPercInput.value) || 0;

            const baseCant = total / (1 + ivaPerc / 100 - irpfPerc/100);
            const ivaCant = baseCant * ivaPerc / 100;
            const irpfCant = baseCant * irpfPerc / 100;

            baseInput.value = baseCant.toFixed(2) + "€";
            ivaCantInput.value = ivaCant.toFixed(2) + '€';
            irpfCantInput.value = irpfCant.toFixed(2) + '€';
        }

        // Listeners: base, iva-perc y irpf-perc
        baseInput.addEventListener('input', actualizarCalculosParciales);
        ivaPercInput.addEventListener('input', actualizarCalculosParciales);
        irpfPercInput.addEventListener('input', actualizarCalculosParciales);

        // Listeners: Balance total
        totalInput.addEventListener('input', actualizarCalculosTotal);

    });
</script>

<script>
    document.getElementById('btnVerArchivos').addEventListener('click', function() {
        const iframe = document.getElementById('iframeVisor');

        const idBalance = <?= (int)$_GET['idBalance'] ?>;
        const tipoBalance = "<?= $tipoBalance ?>";

        iframe.src = `visorDocumentos.php?tipoBalance=${tipoBalance}&idBalance=${idBalance}`;
    });
</script>



<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById("formBalance");

        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('documentos');
        const listaArchivos = document.getElementById('lista-archivos');

        let archivosAcumulados = [];

        dropZone.addEventListener('click', () => fileInput.click());

        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');

            agregarArchivos(e.dataTransfer.files);
        });

        fileInput.addEventListener('change', () => {
            agregarArchivos(fileInput.files);
            // Resetea el input, porque si vuelves a elegir el mismo archivo, `change` no saltará
            fileInput.value = '';
        });

        function agregarArchivos(files) {
            archivosAcumulados = archivosAcumulados.concat(Array.from(files));
            renderLista();
        }

        function renderLista() {
            listaArchivos.innerHTML = '';
            archivosAcumulados.forEach((archivo, index) => {
                const elemento = document.createElement('div');
                elemento.classList.add('archivo');

                elemento.textContent = `${archivo.name} (${Math.round(archivo.size / 1024)} KB)`;

                const removeBtn = document.createElement('span');
                removeBtn.classList.add('circle');
                removeBtn.textContent = '❌';

                removeBtn.onclick = () => {
                    archivosAcumulados.splice(index, 1);
                    renderLista();
                };

                elemento.appendChild(removeBtn);
                listaArchivos.appendChild(elemento);
            });
        }

        form.addEventListener('submit', () => {
            // Antes de enviar, junta todos los archivos en el input real
            const dt = new DataTransfer();
            archivosAcumulados.forEach(file => dt.items.add(file));
            fileInput.files = dt.files;
        });
    });
</script>
