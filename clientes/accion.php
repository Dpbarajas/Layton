<?php
global $db;
require_once __DIR__ . '/../includes/config.php';

// Decidir la accion a realizar,
$accion = $_POST['accion'] ?? ($_GET['accion'] ?? '');

$origen = $_POST['origen'] ?? ($_GET['origen'] ?? 'index.php');

$success = false;

try {
    switch ($accion) {
        case 'crear':
            $stmt = $db->prepare("INSERT INTO cliente (nombreCliente, personaContacto, telefonoCliente, correoCliente) 
                              VALUES (:nombreCliente, :personaContacto, :telefonoCliente, :correoCliente)");

            $success = $stmt->execute([
                ':nombreCliente' => $_POST['nombreCliente'],
                ':personaContacto' => $_POST['personaContacto'],
                ':telefonoCliente' => $_POST['telefonoCliente'],
                ':correoCliente' => $_POST['correoCliente']
            ]);

            $infoMessage = "Cliente creado correctamente";
            $errorMessage = "Hubo un problema al crear el cliente";

            break;

        case 'editar':

            $stmt = $db->prepare("UPDATE cliente 
                              SET nombreCliente = :nombreCliente,
                                  personaContacto = :personaContacto,
                                  telefonoCliente = :telefonoCliente,
                                  correoCliente = :correoCliente
                              WHERE idCliente = :id");

            $success = $stmt->execute([
                ':nombreCliente' => $_POST['nombreCliente'],
                ':personaContacto' => $_POST['personaContacto'],
                ':telefonoCliente' => $_POST['telefonoCliente'],
                ':correoCliente' => $_POST['correoCliente'],
                ':id' => $_POST['idCliente']
            ]);

            $infoMessage = "Cliente modificado correctamente";
            $errorMessage = "Hubo un problema al modificar el cliente";

            break;

        case 'eliminar':
            $stmt = $db->prepare("UPDATE cliente SET baja = 1 WHERE idCliente = :id");

            $success = $stmt->execute([
                ':id' => $_GET['idContrato']
            ]);

            $infoMessage = "Cliente eliminado correctamente";
            $errorMessage = "Hubo un problema al eliminar el cliente";

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
