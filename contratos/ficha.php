<?php
global $db;
require_once __DIR__ . '/../includes/config.php';

if (isset($_GET['origen'])) $_SESSION['origen']['contratos'] = $_GET['origen'];
elseif (isset($_POST['origen'])) $_SESSION['origen']['contratos'] = $_POST['origen'];
else $_SESSION['origen']['contratos'] = 'index.php';

$origen = $_SESSION['origen']['contratos'];

// Obtener el ID del contrato desde la URL
$idContrato = isset($_GET['idContrato']) ? (int)$_GET['idContrato'] : 0;

$modoEdicion = $idContrato !== 0;
$accion = $modoEdicion ? 'editar' : 'crear';

// Variables iniciales
$contrato = [
    'idCliente' => '',
    'idProducto' => '',
    'idProveedor' => '',
    'fechaVenta' => date('Y-m-d'),
    'fechaActivacion' => '',
    'fechaFacturacion' => '',
    'comision' => '0.00',
    'estado' => 'Vendido',

    'cliente' => '',
    'proveedor' => '',
    'producto' => '',
    'notasContrato' => ''
];

// Si se está editando, obtener los datos existentes
if ($modoEdicion) {
    $stmt = $db->prepare("
        SELECT * FROM contrato c 
            INNER JOIN producto pd ON c.producto = pd.idProducto
            INNER JOIN proveedor pv ON pd.proveedor = pv.idProveedor
        WHERE idContrato = ?");
    $stmt->execute([$idContrato]);
    $contrato = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$contrato) {
        die("Contrato no encontrado");
    }
}

// Obtener listas de clientes, productos y proveedores para los selects
$clientes = $db->query("SELECT idCliente, nombreCliente FROM cliente ORDER BY nombreCliente")->fetchAll(PDO::FETCH_ASSOC);
$productos = $db->query("SELECT idProducto, nombreProveedor FROM producto ORDER BY nombreProveedor")->fetchAll(PDO::FETCH_ASSOC);
$proveedores = $db->query("SELECT idProveedor, nombreProveedor FROM proveedor ORDER BY nombreProveedor")->fetchAll(PDO::FETCH_ASSOC);

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

<?php include '../includes/header.php'; ?>

<?php if (!empty($tipoMensaje)): ?>
    <div class="alert alert-<?= $tipoMensaje ?> alert-dismissible fade show" role="alert">
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<form id="formContratos" method="POST" action="accion.php" class="mt-0">
    <input type="hidden" id="accion" name="accion" value="<?= $accion ?>">
    <input type="hidden" id="origen" name="origen" value="<?= $origen ?>">

    <div class="container">
        <div class="table-box mb-2 row">
            <h2 class="mb-0"><?= $modoEdicion ? 'Editar Contrato' : 'Nuevo Contrato' ?></h2>
        </div>

        <input type="hidden" readonly name="idContrato" id="idContrato" value="<?= $idContrato ?>">

        <div class="table-box row">
            <!-- Main form fields -->
            <div class="col-md-8">
                <div class="mb-2">
                    <label for="idCliente" class="form-label requerido">Cliente</label>
                    <select name="idCliente" id="idCliente" class="form-select" required>
                        <option value="-1">-- Selecciona un cliente --</option>
                        <option value="" disabled></option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?= $cliente['idCliente'] ?>" <?= $cliente['idCliente'] === $contrato['cliente'] ? 'selected' : '' ?>>
                                <?= $cliente['nombreCliente'] ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="" disabled></option>
                        <option value="0">-- Crear nuevo --</option>
                    </select>
                </div>

                <div class="subFormBox mb-4" id="creadorCliente">
                    <div class="row d-flex justify-content-between align-items-center mb-2">
                        <h4 style="width: 50%">Crear Cliente</h4>

                        <button type="button"
                                class="btn btn-outline-success mt-2 me-4"
                                data-accion="crearCliente">
                            Crear
                        </button>

                        <button id="crearCliente" type="submit"
                                onclick="cambiarAccion('formContratos', '/clientes/accion.php', 'crear', '<?= $_SERVER['REQUEST_URI'] ?>', 'requerido_cliente', 'requerido')"
                                style="width:10%" class="btn btn-outline-success mt-2 me-4">Crear
                        </button>
                    </div>

                    <label for="nombreCliente" class="form-label requerido_cliente">Cliente</label>
                    <input type="text" name="nombreCliente" id="nombreCliente" class="form-control">

                    <label for="personaContacto" class="form-label mt-1 requerido_cliente">Persona
                        de Contacto</label>
                    <input type="text" name="personaContacto" id="personaContacto" class="form-control">

                    <label for="telefonoCliente" class="form-label mt-1">Teléfono de Contacto</label>
                    <input type="number" name="telefonoCliente" id="telefonoCliente" class="form-control">

                    <label for="correoCliente" class="form-label mt-1">Correo de Contacto</label>
                    <input type="text" name="correoCliente" id="correoCliente" class="form-control">
                </div>

                <div class="mb-2">
                    <label for="idProveedor" class="form-label requerido">Proveedor</label>
                    <select name="idProveedor" id="idProveedor" class="form-select" required>
                        <option value="-1">-- Selecciona un proveedor --</option>
                        <option value="" disabled></option>
                        <?php foreach ($proveedores as $proveedor): ?>
                            <option value="<?= $proveedor['idProveedor'] ?>" <?= $proveedor['idProveedor'] === $contrato['proveedor'] ? 'selected' : '' ?>>
                                <?= $proveedor['nombreProveedor'] ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="" disabled></option>
                        <option value="0">-- Crear nuevo --</option>
                    </select>
                </div>

                <div class="subFormBox mb-4" id="creadorProveedor">
                    <div class="row d-flex justify-content-between align-items-center mb-2">
                        <h4 style="width: 50%">Crear Proveedor</h4>

                        <button type="button"
                                class="btn btn-outline-success mt-2 me-4"
                                data-accion="crearProveedor">
                            Crear
                        </button>

                        <button id="crearProveedor" type="submit"
                                onclick="cambiarAccion('formProveedor', '/proveedores/accion.php', 'crear', '<?= $_SERVER['REQUEST_URI'] ?>', 'requerido_proveedor', 'requerido')"
                                style="width:10%" class="btn btn-outline-success mt-2 me-4">Crear
                        </button>
                    </div>

                    <label for="nombreProveedor" class="form-label requerido_proveedor">Nombre Proveedor</label>
                    <input type="text" id="nombreProveedor" name="nombreProveedor" class="form-control">

                    <label for="telefonoProveedor" class="form-label mt-1">Teléfono Proveedor</label>
                    <input type="number" id="telefonoProveedor" name="telefonoProveedor" class="form-control">

                    <label for="web" class="form-label mt-1">Página web</label>
                    <input type="number" id="web" name="web" class="form-control">
                </div>


                <div class="mb-2">
                    <label for="idProducto" class="form-label requerido">Producto</label>
                    <select name="idProducto" id="idProducto" class="form-select" required>
                        <option value="-1">-- Selecciona un producto --</option>
                        <option value="" disabled></option>
                        <?php foreach ($productos as $producto): ?>
                            <option value="<?= $producto['idProducto'] ?>" <?= $producto['idProducto'] === $contrato['producto'] ? 'selected' : '' ?>>
                                <?= $producto['nombreProveedor'] ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="" disabled></option>
                        <option value="0">-- Crear nuevo --</option>
                    </select>
                </div>

                <div class="subFormBox mb-4" id="creadorProducto">
                    <div class="row d-flex justify-content-between align-items-center mb-2">
                        <h4 style="width: 50%">Crear Producto</h4>


                        <?php // TODO: añadir links a la pantalla correcta ?>
                        <button type="button"
                                class="btn btn-outline-success mt-2 me-4"
                                data-accion="crearProducto">
                            Crear
                        </button>
                        <a href="index.php" style="width: 10%;" class="btn btn-outline-success mt-2 me-4">Crear</a>
                    </div>

                    <label for="nombreProducto" class="form-label">Nombre Producto</label>
                    <input type="text" id="nombreProducto" name="nombreProducto" class="form-control">

                    <label for="proveedor" class="form-label mt-1">Proveedor</label>
                    <select name="proveedor" id="proveedor" class="form-select" required>
                        <option value="">-- Selecciona un proveedor --</option>
                        <?php foreach ($proveedores as $proveedor): ?>
                            <option value="<?= $proveedor['idProveedor'] ?>">
                                <?= $proveedor['nombreProveedor'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="tipoRetrocomision" class="form-label requerido_producto mt-1">Retrocomision</label>
                    <select name="tipoRetrocomision" id="tipoRetrocomision" class="form-select">
                        <option value="dia">Por día</option>
                        <option value="mes">Por mes</option>
                        <option value="">Sin R.C. asignada</option>
                    </select>

                    <label for="notasProducto" class="form-label mt-1">Notas</label>
                    <textarea id="notasProducto" name="notasProducto" class="form-control"></textarea>
                </div>

                <div class="mb-2">
                    <label for="comision" class="form-label requerido">Comision</label>
                    <input type="number" step="0.01" name="comision" id="comision"
                           class="form-control dinero"
                           value="<?= $contrato['comision'] ?>" required>
                </div>

                <div class="mb-2">
                    <label for="fechaVenta" class="form-label requerido">Fecha Venta</label>
                    <input type="date" name="fechaVenta" id="fechaVenta" class="form-control"
                           value="<?= $contrato['fechaVenta'] ?>" required>
                </div>

                <div class="mb-2">
                    <label for="fechaActivacion" class="form-label">Fecha Activación</label>
                    <input type="date" name="fechaActivacion" id="fechaActivacion" class="form-control"
                           value="<?= $contrato['fechaActivacion'] ?>">
                </div>

                <div class="mb-2 row align-items-right">
                    <div class="col-md-8">
                        <label for="fechaFacturacion" class="form-label">Fecha Facturación</label>
                        <input type="date" name="fechaFacturacion" id="fechaFacturacion" class="form-control"
                               value="<?= $contrato['fechaFacturacion'] ?>" disabled>
                    </div>

                    <div class="col-md-1">
                        <label class="form-label" for="chFacturado">Facturado</label>
                        <br>
                        <input class="form-check-input m-2" type="checkbox"
                               style="transform: scale(1.5);"
                               name="chFacturado" id="chFacturado"
                               value="1" <?= $contrato['estado'] === 'Facturado' ? 'checked' : '' ?>>
                    </div>

                    <div class="col-md-1"></div>

                    <div class="col-md-1">
                        <label class="form-label" for="chCancelado">Cancelado</label>
                        <br>
                        <input class="form-check-input m-2" type="checkbox"
                               style="transform: scale(1.5);"
                               name="chCancelado" id="chCancelado"
                               value="1" <?= $contrato['estado'] === 'Cancelado' ? 'checked' : '' ?>>
                    </div>
                </div>


                <div class="mb-2">
                    <label for="cobrado" class="form-label">A cobrar</label>
                    <input disabled type="number" step="0.05" name="cobrado" id="cobrado" class="form-control dinero"
                           value="<?= is_null($contrato['fechaActivacion']) ? "0.00" : $contrato['comision'] ?>">
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="/contratos/index.php" class="btn btn-secondary">Volver</a>
                    <button type="submit"
                            class="btn btn-primary"
                            data-accion="crearContrato">
                        <?= $modoEdicion ? 'Guardar Cambios' : 'Crear Contrato' ?> NUEVO
                    </button>

                    <button type="submit"
                            onclick="cambiarAccion('formContratos', '/contratos/accion.php', '<?= $accion ?>', '<?= $_SERVER['REQUEST_URI'] ?>', 'requerido', '')"
                            class="btn btn-primary"><?= $modoEdicion ? 'Guardar Cambios' : 'Crear Contrato' ?></button>
                </div>
            </div>

            <div class="col-md-4">
                <div class="mb-2">
                    <label for="estado" class="form-label">Estado</label>
                    <input type="text" readonly id="estado" name="estado" class="form-control"
                           style="box-shadow: inset 0 0 10px <?= $colores[$contrato['estado']] ?>;"
                           value="<?= trim($contrato['estado']) ?>">
                </div>

                <div class="mb-2">
                    <label for="notasContrato" class="form-label">Notas</label>
                    <textarea name="notasContrato" id="notasContrato"
                              rows="<?= $contrato['estado'] === 'Cancelado' ? '10' : '20' ?>"
                              class="form-control"><?= trim($contrato['notasContrato'] ?? '') ?></textarea>

                    <?php if ($contrato['estado'] === 'Cancelado'): ?>
                        <label for="notasCancelacion" class="form-label mt-2">Notas Cancelación</label>
                        <textarea name="notasCancelacion" id="notasCancelacion" rows="8"
                                  class="form-control"><?= trim($contrato['notasCancelacion'] ?? '') ?></textarea>
                    <?php endif; ?>
                </div>

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
                cobrado.value = comision.value;
            }
        });
    });
</script>

<script>
    // Cambiar el estado del contrato en base a los campos rellenados
    // y habilitar el check de facturacion
    document.addEventListener('DOMContentLoaded', function () {
        const estado = document.getElementById('estado');
        const chFacturado = document.getElementById('chFacturado');
        const chCancelado = document.getElementById('chCancelado');

        const fechaVenta = document.getElementById('fechaVenta');
        const fechaActivacion = document.getElementById('fechaActivacion');
        const fechaFacturacion = document.getElementById('fechaFacturacion');

        const cobrado = document.getElementById('cobrado');
        const comision = document.getElementById('comision');

        const colores = {
            'Vendido': '#3385ff',
            'Activado': '#e6e600',
            'Retrocomision': '#9D23FB',
            'Facturado': '#00b300',
            'Cancelado': '#cc0000',
            '-': '#999'
        };

        function determinarEstado() {
            let estado = '-';
            if (fechaVenta.value) {
                estado = 'Vendido';
            }
            if (fechaActivacion.value) {
                estado = 'Activado';
            }
            if (fechaFacturacion.value) {
                estado = 'Facturado';
            }
            // TODO: Cancelar

            return estado;
        }

        function habilitarCobrado() {
            if (!fechaActivacion.value) {
                cobrado.value = '0.00';
            } else {
                cobrado.value = comision.value;
            }
        }

        function actualizarColor() {
            const color = colores[estado.value.trim()] || '#999';
            estado.style.boxShadow = `inset 0 0 10px ${color}`;
        }

        // Cambiar el estado del contrato al rellenar y borrar la fecha de activacion
        fechaVenta.addEventListener('input', function () {
            if (!fechaVenta.value) {
                fechaActivacion.value = '';
                fechaFacturacion.value = '';
                chFacturado.checked = false;

                estado.value = determinarEstado();
            } else {
                estado.value = 'Vendido';
            }

            habilitarCobrado();
            actualizarColor();
        });

        // Cambiar el estado del contrato al rellenar y borrar la fecha de activacion
        fechaActivacion.addEventListener('input', function () {
            if (!fechaActivacion.value) {
                fechaFacturacion.value = '';
                chFacturado.checked = false;

                estado.value = determinarEstado();
            } else {
                fechaVenta.value = fechaActivacion.value;
                estado.value = 'Activado';
            }

            habilitarCobrado();
            actualizarColor();
        });

        // Cambiar el estado del contrato al rellenar y borrar la fecha de facturacion
        fechaFacturacion.addEventListener('input', function () {
            if (!fechaFacturacion.value) {
                estado.value = determinarEstado();
                chFacturado.checked = false;
            } else {
                if (!fechaVenta.value) fechaVenta.value = fechaFacturacion.value;
                if (!fechaActivacion.value) fechaActivacion.value = fechaFacturacion.value;

                estado.value = 'Facturado';
                chFacturado.checked = true;
            }

            habilitarCobrado();
            actualizarColor();
        });

        chFacturado.addEventListener('input', function () {
            if (chFacturado.checked === false) {
                fechaFacturacion.value = '';
                fechaFacturacion.disabled = true;

                estado.value = determinarEstado();
            } else {
                fechaFacturacion.disabled = false;
                fechaFacturacion.value = new Date().toISOString().split('T')[0];

                if (!fechaVenta.value) fechaVenta.value = fechaFacturacion.value;
                if (!fechaActivacion.value) fechaActivacion.value = fechaFacturacion.value;

                estado.value = 'Facturado';
            }

            actualizarColor();
        });

        // TODO: añadir retrocomisiones y enseñar notasCancelacion
        chCancelado.addEventListener('input', function () {
            if (chCancelado.checked === false) {
                estado.value = determinarEstado();
            } else {
                estado.value = 'Cancelado';
            }

            actualizarColor();
        })

        habilitarCobrado();
        actualizarColor();
    });
</script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Mostrar / ocultar subformularios
        const secciones = [
            { select: 'idProveedor', div: 'creadorProveedor' },
            { select: 'idCliente', div: 'creadorCliente' },
            { select: 'idProducto', div: 'creadorProducto' }
        ];

        const ocultarTodos = () => {
            secciones.forEach(({ select, div }) => {
                const selEl = document.getElementById(select);
                const divEl = document.getElementById(div);

                if (divEl && selEl && selEl.value === "0") {
                    selEl.value = "-1";
                    divEl.style.display = 'none';
                }
            });
        };

        secciones.forEach(({ select, div }) => {
            const selectEl = document.getElementById(select);
            const divEl = document.getElementById(div);
            if (!selectEl || !divEl) return;

            selectEl.addEventListener('change', () => {
                console.log(selectEl.value);

                if (selectEl.value === '0') {
                    ocultarTodos();

                    selectEl.value = "0";
                    divEl.style.display = 'block';
                } else {
                    divEl.style.display = "none"
                }
            });
        });

        // Captura todos los botones con data-accion
        document.querySelectorAll('[data-accion]').forEach(btn => {
            btn.addEventListener('click', e => {
                const accion = e.target.getAttribute('data-accion');
                manejarAccion(accion);
            });
        });
    });

    function manejarAccion(accion) {
        const form = document.getElementById('formContratos');
        const origen = "<?= $_SERVER['REQUEST_URI'] ?>";

        switch (accion) {
            case 'crearCliente':
                if (validarSubform('requerido_cliente')) {
                    form.action = '/clientes/accion.php';
                    enviar(form, 'crear', origen);
                }
                break;

            case 'crearProveedor':
                if (validarSubform('requerido_proveedor')) {
                    form.action = '/proveedores/accion.php';
                    enviar(form, 'crear', origen);
                }
                break;

            case 'crearProducto':
                if (validarSubform('requerido_producto')) {
                    form.action = '/productos/accion.php';
                    enviar(form, 'crear', origen);
                }
                break;

            case 'crearContrato':
                if (validarContrato()) {
                    form.action = '/contratos/accion.php';
                    enviar(form, '<?= $accion ?>', origen);
                }
                break;
        }
    }

    function enviar(form, accion, origen) {
        document.getElementById('accion').value = accion;
        document.getElementById('origen').value = origen;
        form.submit();
    }

    // Valida inputs por clase requerida
    function validarSubform(clase) {
        const campos = document.querySelectorAll(`.${clase}`);
        for (const label of campos) {
            const input = document.getElementById(label.htmlFor);
            if (input && !input.value.trim()) {
                alert(`El campo "${label.textContent.trim()}" es obligatorio.`);
                input.focus();
                return false;
            }
        }
        return true;
    }

    // Valida contrato principal (no seleccionar 0)
    function validarContrato() {
        const selects = ['idCliente', 'idProveedor', 'idProducto'];
        for (const id of selects) {
            const el = document.getElementById(id);
            if (el && (el.value === '' || el.value === '0')) {
                alert(`Debes seleccionar un valor válido para ${id.replace('id', '').toLowerCase()}.`);
                el.focus();
                return false;
            }
        }
        return true;
    }
</script>

<?php include '../includes/footer.php'; ?>
