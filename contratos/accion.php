<?php
global $db;
require_once __DIR__ . '/../includes/config.php';

// Decidir la accion a realizar
$accion = $_GET['accion'] ?? ($_POST['accion'] ?? '');

$origen = isset($_POST['origen']) ? $_POST['origen'] : 'index.php';

try {
    switch ($accion) {
        case 'crear':
            $stmt = $db->prepare("INSERT INTO CONTRATO (cliente, comision, producto, fechaVenta, fechaActivacion, fechaFacturacion, notasContrato) 
                              VALUES (:cliente, :comision, :producto, :fechaVenta, :fechaActivacion, :notasContrato)");

            $success = $stmt->execute([
                ':cliente' => $_POST['idCliente'],
                ':comision' => $_POST['comision'],
                ':producto' => $_POST['idProducto'],
                ':fechaVenta' => $_POST['fechaVenta'],
                ':fechaActivacion' => empty($_POST['fechaActivacion']) ? null : $_POST['fechaActivacion'],
                ':fechaFacturacion' => empty($_POST['fechaFacturacion']) ? null : $_POST['fechaFacturacion'],
                ':notasContrato' => $_POST['notasContrato'],
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
                                  notasContrato = :notasContrato
                              WHERE idContrato = :id");

            $success = $stmt->execute([
                ':cliente' => $_POST['idCliente'],
                ':comision' => $_POST['comision'],
                ':producto' => $_POST['idProducto'],
                ':fechaVenta' => $_POST['fechaVenta'],
                ':fechaActivacion' => empty($_POST['fechaActivacion']) ? null : $_POST['fechaActivacion'],
                ':fechaFacturacion' => empty($_POST['fechaFacturacion']) ? null : $_POST['fechaFacturacion'],
                ':notasContrato' => $_POST['notasContrato'],
                ':id' => $_POST['idContrato']
            ]);

            $infoMessage = "Contrato modificado correctamente";
            $errorMessage = "Hubo un problema al modificar el contrato";

            break;

        case 'eliminar':
            $stmt = $db->prepare("UPDATE contrato SET baja = 1 WHERE idContrato = :id");

            $stmt->execute([
                ':id' => $_GET['idContrato']
            ]);

            $infoMessage = "Contrato eliminado correctamente";
            $errorMessage = "Hubo un problema al eliminar el contrato";

            break;

        case 'activar':
            $stmt = $db->prepare("UPDATE contrato SET fechaActivacion = :fechaActivacion WHERE idContrato = :id");

            $success = $stmt->execute([
                ':fechaActivacion' => date("Y-m-d"),
                ':id' => $_GET['idContrato']
            ]);

            $infoMessage = "Contrato activado correctamente";
            $errorMessage = "Hubo un problema al activar el contrato";


            break;

        case 'desactivar':
            $stmt = $db->prepare("UPDATE CONTRATO SET fechaActivacion = NULL WHERE idContrato = :id");

            $success = $stmt->execute([
                ':id' => $_GET['idContrato']
            ]);
            // TODO: añadir retrocomisiones

            $infoMessage = "Contrato desactivado correctamente";
            $errorMessage = "Hubo un problema al desactivar el contrato";

            break;


        case 'facturar':
            // TODO: facturar pedido

            break;
            
        case 'cancelar':
            // TODO: cancelar pedido

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
