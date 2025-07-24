<?php
global $db;
require_once __DIR__ . '/../includes/config.php';

// Decidir la accion a realizar,
$action = $_POST['accion'] ?? ($_GET['accion'] ?? '');
$origen = $_POST['origen'] ?? ($_GET['origen'] ?? 'index.php');

$success = false;

try {
    switch ($action) {
        case 'crear':
            $stmt = $db->prepare("INSERT INTO proveedor (nomProv, telefono, web) 
                              VALUES (:nomProv, :telefono, :web)");

            $success = $stmt->execute([
                ':nomProv' => $_POST['nomProv'],
                ':telefono' => $_POST['telefono'],
                ':web' => $_POST['web']
            ]);

            if ($success) {
               $idProveedor = $db->lastInsertId();

               $_SESSION['productos']['idProveedorCreado'] = $idProveedor;
            }

            $infoMessage = "Proveedor creado correctamente";
            $errorMessage = "Hubo un problema al crear el proveedor";

            break;

        case 'editar':

            $stmt = $db->prepare("UPDATE proveedor 
                              SET nomProv = :nomProv,
                                  telefono = :telefono,
                                  web = :web
                              WHERE idProveedor = :id");

            $success = $stmt->execute([
                ':nomProv' => $_POST['nomProv'],
                ':telefono' => $_POST['telefono'],
                ':web' => $_POST['web'],
                ':id' => $_POST['idProveedor']
            ]);

            $infoMessage = "Proveedor modificado correctamente";
            $errorMessage = "Hubo un problema al modificar el proveedor";

            break;

        case 'eliminar':
            $stmt = $db->prepare("UPDATE proveedor SET baja = 1 WHERE idProveedor = :id");

            $success = $stmt->execute([
                ':id' => $_GET['idProveedor']
            ]);

            $infoMessage = "Proveedor eliminado correctamente";
            $errorMessage = "Hubo un problema al eliminar el proveedor";

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
