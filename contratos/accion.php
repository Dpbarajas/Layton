<?php
global $db;
require_once __DIR__ . '/../includes/config.php';

// Decidir la accion a realizar
$accion = $_GET['accion'] ?? ($_POST['accion'] ?? '');

$origen = $_GET['origen'] ?? ($_POST['origen'] ?? 'index.php');

$idContrato = $_GET['idContrato'] ?? ($_POST['idContrato'] ?? 0);

if ($idContrato != 0) {
    $stmt = $db->prepare("SELECT * FROM contrato WHERE idContrato = ?");
    $stmt->execute([$idContrato]);

    $contrato = $stmt->fetch(PDO::FETCH_ASSOC);
}

function comprobarEstado($contrato, $estados)
{
    if (!is_array($estados)) {
        $estados = [$estados];
    }

    return in_array($contrato['estado'], $estados);
}

$success = false;
try {
    switch ($accion) {
        case 'crear':
            $stmt = $db->prepare("INSERT INTO CONTRATO (cliente, comision, producto, fechaVenta, fechaActivacion, fechaFacturacion, estado, estadoPrevio, notasContrato, notasCancelacion) 
                              VALUES (:cliente, :comision, :producto, :fechaVenta, :fechaActivacion, :fechaFacturacion, :estado, :estadoPrevio, :notasContrato, :notasCancelacion)");

            $success = $stmt->execute([
                ':cliente' => $_POST['idCliente'],
                ':comision' => trim(str_replace('€', '', $_POST['comision'])),
                ':producto' => $_POST['idProducto'],
                ':fechaVenta' => $_POST['fechaVenta'],
                ':fechaActivacion' => empty($_POST['fechaActivacion']) ? null : $_POST['fechaActivacion'],
                ':fechaFacturacion' => empty($_POST['fechaFacturacion']) ? null : $_POST['fechaFacturacion'],
                ':estado' => $_POST['estado'],
                ':estadoPrevio' => $_POST['estado'],
                ':notasContrato' => $_POST['notasContrato'],
                ':notasCancelacion' => $_POST['notasCancelacion'] ?? null,
            ]);

            $infoMessage = "Contrato creado correctamente";
            $errorMessage = "Hubo un problema al crear el contrato";

            break;

        case 'editar':
            $stmt = $db->prepare("UPDATE CONTRATO 
                              SET producto = :producto,
                                  comision = :comision,
                                  cliente = :cliente,
                                  fechaVenta = :fechaVenta,
                                  fechaActivacion = :fechaActivacion,
                                  fechaFacturacion = :fechaFacturacion,
                                  estado = :estado,
                                  estadoPrevio = estado,
                                  notasContrato = :notasContrato,
                                  notasCancelacion = :notasCancelacion
                              WHERE idContrato = :id");

            $success = $stmt->execute([
                ':cliente' => $_POST['idCliente'],
                ':comision' => trim(str_replace('€', '', $_POST['comision'])),
                ':producto' => $_POST['idProducto'],
                ':fechaVenta' => $_POST['fechaVenta'],
                ':fechaActivacion' => empty($_POST['fechaActivacion']) ? null : $_POST['fechaActivacion'],
                ':fechaFacturacion' => empty($_POST['fechaFacturacion']) ? null : $_POST['fechaFacturacion'],
                ':estado' => $_POST['estado'],
                ':notasContrato' => $_POST['notasContrato'],
                ':notasCancelacion' => $_POST['notasCancelacion'],
                ':id' => $_POST['idContrato']
            ]);

            $infoMessage = "Contrato modificado correctamente";
            $errorMessage = "Hubo un problema al modificar el contrato";

            break;

        case 'eliminar':
            $stmt = $db->prepare("UPDATE contrato SET baja = 1 WHERE idContrato = :id");

            $success = $stmt->execute([
                ':id' => $_GET['idContrato']
            ]);

            $infoMessage = "Contrato eliminado correctamente";
            $errorMessage = "Hubo un problema al eliminar el contrato";

            break;

        case 'activar':
            if (!comprobarEstado($contrato, ['Vendido'])) {
                $errorMessage = "El contrato no se puede activar si está en estado " . $contrato['estado'];

                break;
            }

            $stmt = $db->prepare("UPDATE contrato SET fechaActivacion = :fechaActivacion, estado = 'Activado', estadoPrevio = estado WHERE idContrato = :id");

            $success = $stmt->execute([
                ':fechaActivacion' => date("Y-m-d"),
                ':id' => $_GET['idContrato']
            ]);

            $infoMessage = "Contrato activado correctamente";
            $errorMessage = "Hubo un problema al activar el contrato";

            break;

        case 'desactivar':
            if (!comprobarEstado($contrato, ['Activado'])) {
                $errorMessage = "El contrato no se puede desactivar si está en estado " . $contrato['estado'];

                break;
            }

            $stmt = $db->prepare("UPDATE CONTRATO SET fechaActivacion = NULL, estado = 'Vendido', estadoPrevio = estado WHERE idContrato = :id");

            $success = $stmt->execute([
                ':id' => $_GET['idContrato']
            ]);

            $infoMessage = "Contrato desactivado correctamente";
            $errorMessage = "Hubo un problema al desactivar el contrato";

            break;

        case 'facturar':
            if (!comprobarEstado($contrato, ['Activado'])) {
                $errorMessage = "El contrato no se puede facturar si está en estado " . $contrato['estado'];

                break;
            }

            $stmt = $db->prepare("UPDATE contrato SET fechaFacturacion = :fechaFacturacion, estado = 'Facturado', estadoPrevio = estado WHERE idContrato = :id");

            $success = $stmt->execute([
                ':fechaFacturacion' => date("Y-m-d"),
                ':id' => $_GET['idContrato']
            ]);

            $infoMessage = "Contrato facturado correctamente";
            $errorMessage = "Hubo un problema al guardar la fecha de facturación del contrato";

            break;

        case 'desfacturar':
            if (!comprobarEstado($contrato, ['Facturado'])) {
                $errorMessage = "El contrato no se puede desfacturar si está en estado " . $contrato['estado'];

                break;
            }

            $stmt = $db->prepare("UPDATE CONTRATO SET fechaFacturacion = NULL, estado = 'Activado', estadoPrevio = estado WHERE idContrato = :id");

            $success = $stmt->execute([
                ':id' => $_GET['idContrato']
            ]);

            $infoMessage = "Contrato desfacturado correctamente";
            $errorMessage = "Hubo un problema al desfacturar el contrato";

            break;

        case 'cancelar':
            if (empty($_GET['notaCancelacion'])) {
                $_GET['notaCancelacion'] = null;
            }

            $stmt = $db->prepare("UPDATE contrato SET estado = 'Cancelado', notasCancelacion = :notasCancelacion, estadoPrevio = estado WHERE idContrato = :id");

            $success = $stmt->execute([
                ':id' => $_GET['idContrato'],
                ':notasCancelacion' => $_GET['notaCancelacion']
            ]);

            $infoMessage = "Contrato cancelado correctamente";
            $errorMessage = "Hubo un problema al cancelar el contrato";

            break;

        case 'descancelar':
            // TODO: CAMBIAR CODIGO
            if (!is_null($contrato['fechaFacturacion'])) {
            }


            $stmt = $db->prepare("UPDATE contrato SET estado = estadoPrevio, notasCancelacion = null, retrocomision = 0 WHERE idContrato = :id");

            $success = $stmt->execute([
                ':id' => $_GET['idContrato']
            ]);

            $infoMessage = "Contrato reactivado correctamente";
            $errorMessage = "Hubo un problema al reactivar el contrato";

            break;

        case 'retrocomision':
            $stmt = $db->prepare("UPDATE contrato SET estado = 'Retrocomision', retrocomision = :retrocomision, estadoPrevio = estado WHERE idContrato = :id");

            $success = $stmt->execute([
                ':retrocomision' => $_GET['retrocomision'],
                ':id' => $_GET['idContrato']
            ]);

            $infoMessage = "Contrato retrocomisionado correctamente";
            $errorMessage = "Hubo un problema al retrocomisionar el contrato";

            break;

        default:
            throw new Exception("Accion incorrecta");
    }

    if ($success):
        $_SESSION['infoMessage'] = $infoMessage;
    else:
        $_SESSION['errorMessage'] = $errorMessage;
    endif;
} catch (Exception $e) {
    $_SESSION['errorMessage'] = $e->getMessage();
}


header("Location: $origen");
