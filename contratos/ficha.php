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
$clientes = $db->query("SELECT idCliente, nomCli FROM cliente ORDER BY nomCli")->fetchAll(PDO::FETCH_ASSOC);
$productos = $db->query("SELECT idProducto, nomProd FROM producto ORDER BY nomProd")->fetchAll(PDO::FETCH_ASSOC);
$proveedores = $db->query("SELECT idProveedor, nomProv FROM proveedor ORDER BY nomProv")->fetchAll(PDO::FETCH_ASSOC);

global $colores;
?>




<?php include '../includes/header.php'; ?>

<form id="formContratos" method="POST" action="accion.php" class="mt-0">
    <input type="hidden" id="accion" name="accion" value="<?= $modoEdicion ? "editar" : "crear" ?>">
    <input type="hidden" id="accionOriginal" name="accionOriginal" value="<?= $modoEdicion ? "editar" : "crear" ?>">

    <input type="hidden" id="origen" name="origen" value="<?= $origen ?>">
    <input type="hidden" id="origenOriginal" name="origenOriginal" value="<?= $origen ?>">


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
                        <option value="">-- Selecciona un cliente --</option>
                        <option value="" disabled></option>
                        <?php foreach ($clientes as $cliente): ?>
                            <option value="<?= $cliente['idCliente'] ?>" <?= $cliente['idCliente'] === $contrato['cliente'] ? 'selected' : '' ?>>
                                <?= $cliente['nomCli'] ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="" disabled></option>
                        <option value="0">-- Crear nuevo --</option>
                    </select>
                </div>

                <!--
                 <div class="subFormBox mb-4" id="creadorCliente">
                    <div class="row d-flex justify-content-between align-items-center">
                        <h4 class="mb-0" style="width: 50%">Crear Cliente</h4>

                        <button id="crearCliente" type="submit" onclick="changeAction('formContratos', '/clientes/accion.php', 'crear', '<?= $_SERVER['REQUEST_URI'] ?>', ['nomProv'], ['nomProd', 'comision'])" style="width:10%" class="btn btn-outline-success mt-2 me-4">Crear</button>
                    </div>

                    <label for="nomNuevoCli" class="form-label">Cliente</label>
                    <input type="text" required name="nomNuevoCli" id="nomCli" class="form-control">

                    <label for="contactoNuevoCli" class="form-label">Persona de Contacto</label>
                    <input type="text" required name="contactoNuevoCli" id="contactoNuevoCli" class="form-control">

                    <label for="telNuevoCli" class="form-label">Teléfono de Contacto</label>
                    <input type="tel" name="telNuevoCli" id="telNuevoCli" class="form-control">

                    <label for="correoNuevoCli" class="form-label">Correo de Contacto</label>
                    <input type="text" name="correoNuevoCli" id="correoNuevoCli" class="form-control">
                </div>
                 -->

                <div class="mb-2">
                    <label for="selProveedor" class="form-label requerido">Proveedor</label>
                    <select name="selProveedor" id="selProveedor" class="form-select" required>
                        <option value="">-- Selecciona un proveedor --</option>
                        <option value="" disabled></option>
                        <?php foreach ($proveedores as $proveedor): ?>
                            <option value="<?= $proveedor['idProveedor'] ?>" <?= $proveedor['idProveedor'] === $contrato['proveedor'] ? 'selected' : '' ?>>
                                <?= $proveedor['nomProv'] ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="" disabled></option>
                        <option value="0">-- Crear nuevo --</option>
                    </select>
                </div>

                <!--
                <div class="subFormBox mb-4" id="creadorProveedor">
                    <div class="row d-flex justify-content-between align-items-center">
                        <h4 class="mb-0" style="width: 50%">Crear Proveedor</h4>

                        <?php // TODO: añadir links a la pantalla correcta ?>
                        <a href="index.php" style="width: 10%;" class="btn btn-outline-success mt-2 me-4">Crear</a>
                    </div>

                    <label for="nomNuevoProv" class="form-label">Nombre Proveedor</label>
                    <input type="text" id="nomNuevoProv" class="form-control">

                    <label for="telNuevoProv" class="form-label mt-2">Teléfono Proveedor</label>
                    <input type="number" id="telNuevoProv" class="form-control">
                </div>
                -->


                <div class="mb-2">
                    <label for="selProducto" class="form-label requerido">Producto</label>
                    <select name="selProducto" id="selProducto" class="form-select" required>
                        <option value="">-- Selecciona un producto --</option>
                        <option value="" disabled></option>
                        <?php foreach ($productos as $producto): ?>
                            <option value="<?= $producto['idProducto'] ?>" <?= $producto['idProducto'] === $contrato['producto'] ? 'selected' : '' ?>>
                                <?= $producto['nomProd'] ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="" disabled></option>
                        <option value="0">-- Crear nuevo --</option>
                    </select>
                </div>

                <!--
                <div class="subFormBox mb-4" id="creadorProducto">
                    <div class="row d-flex justify-content-between align-items-center">
                        <h4 class="mb-0" style="width: 50%">Crear Proveedor</h4>

                        <?php // TODO: añadir links a la pantalla correcta ?>
                        <a href="index.php" style="width: 10%;" class="btn btn-outline-success mt-2 me-4">Crear</a>
                    </div>

                    <label for="nomNuevoProd" class="form-label">Nombre Producto</label>
                    <input type="text" id="nomNuevoProd" class="form-control mb-2">

                    <label for="nuevoProv" class="form-label mt-2">Proveedor</label>
                    <select name="idProveedor" id="idProveedor" class="form-select mb-2" required>
                        <option value="">-- Selecciona un proveedor --</option>
                        <?php foreach ($proveedores as $proveedor): ?>
                            <option value="<?= $proveedor['idProveedor'] ?>">
                                <?= $proveedor['nomProv'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <label for="nuevoComision" class="form-label">Comision</label>
                    <input type="number" step="0.01" name="comision" id="comision" class="form-control mb-2" value="" required>

                    <label for="nuevoRetroCom" class="form-label">Retrocomision</label>
                    <select name="nuevoRetroCom" id="nuevoRetroCom" class="form-select mb-2" required>
                        <option value="dia">Por día</option>
                        <option value="mes">Por mes</option>
                        <option value="">Sin R.C. asignada</option>
                    </select>

                    <label for="nuevoNotas" class="form-label">Notas</label>
                    <input type="text" id="nuevoNotas" name="nuevoNotas" class="form-control">

                </div>
                -->

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
                               value="<?= $contrato['fechaFacturacion'] ?>">
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
                              class="form-control"><?= trim($contrato['notasContrato']) ?></textarea>

                    <?php if ($contrato['estado'] === 'Cancelado'): ?>
                        <label for="notasCancelacion" class="form-label mt-2">Notas Cancelación</label>
                        <textarea name="notasCancelacion" id="notasCancelacion" rows="8"
                                  class="form-control"><?= trim($contrato['notasCancelacion']) ?></textarea>
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

        const fechaVenta = document.getElementById('fechaVenta');
        const fechaActivacion = document.getElementById('fechaActivacion');
        const fechaFacturacion = document.getElementById('fechaFacturacion');

        const cobrado = document.getElementById('cobrado');
        const comision = document.getElementById('comision');

        const colores = {
            'Vendido': '#3385ff',
            'Activado': '#e6e600',
            'Facturable': '#ff8000',
            'Facturado': '#00b300',
            'Cancelado': '#cc0000',
            '-': '#999'
        };

        function habilitarFacturado() {
            if (!fechaFacturacion.value) {
                chFacturado.disabled = true;
                chFacturado.checked = false;
            } else {
                chFacturado.disabled = false;
            }
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
                estado.value = '-';
            } else {
                estado.value = 'Vendido';
            }
            habilitarFacturado();
            habilitarCobrado();
            actualizarColor();
        });

        // Cambiar el estado del contrato al rellenar y borrar la fecha de activacion
        fechaActivacion.addEventListener('input', function () {
            if (!fechaActivacion.value) {
                fechaFacturacion.value = '';
                estado.value = 'Vendido';
            } else {
                fechaVenta.value = fechaActivacion.value;
                estado.value = 'Activado';
            }

            habilitarFacturado();
            habilitarCobrado();
            actualizarColor();
        });

        // Cambiar el estado del contrato al rellenar y borrar la fecha de facturacion
        fechaFacturacion.addEventListener('input', function () {
            if (!fechaFacturacion.value) {
                estado.value = 'Activado';
                chFacturado.checked = false;
            } else {
                fechaVenta.value = fechaFacturacion.value;
                fechaActivacion.value = fechaFacturacion.value;

                estado.value = 'Facturable';
            }

            habilitarFacturado();
            habilitarCobrado();
            actualizarColor();
        });

        chFacturado.addEventListener('input', function () {
            if (chFacturado.checked === false) {
                estado.value = 'Facturable';
            } else {
                estado.value = 'Facturado';
            }

            actualizarColor();
        });

        habilitarFacturado();
        habilitarCobrado();
        actualizarColor();
    });
</script>

<script>
    // Enseñar div para añadir proveedor
    document.addEventListener('DOMContentLoaded', function () {
        const selectProveedor = document.getElementById('selProveedor');
        const selectProducto = document.getElementById('selProducto');

        const divCreador = document.getElementById('creadorProveedor');

        selectProveedor.addEventListener('change', function () {
            if (selectProveedor.value === '0') {
                // Enseñar creador de proveedor si se selecciona "-- Crear nuevo--"
                divCreador.style.display = 'block';
            } else {
                // Actualizar productos que solo pertenezcan a un proveedor asociado
                if(selectProveedor.value !== '') {
                    // TODO: get list of productos asociados a ese proveedor through ajax and update selectProducto accordingly
                }

                divCreador.style.display = 'none'; // Hide it otherwise
            }
        });
    });

    // Enseñar div para añadir cliente
    document.addEventListener('DOMContentLoaded', function () {
        const selectCliente = document.getElementById('idCliente');
        const divCreador = document.getElementById('creadorCliente');

        selectCliente.addEventListener('change', function () {
            if (selectCliente.value === '0') {
                divCreador.style.display = 'block';
            } else {
                divCreador.style.display = 'none';
            }
        });
    });

    // Enseñar div para añadir producto
    document.addEventListener('DOMContentLoaded', function () {
        const selectCliente = document.getElementById('idProducto');
        const divCreador = document.getElementById('creadorProducto');

        selectCliente.addEventListener('change', function () {
            if (selectCliente.value === '0') {
                divCreador.style.display = 'block';
            } else {
                divCreador.style.display = 'none';
            }
        });
    });
</script>

<?php include '../includes/footer.php'; ?>
