<?php
global $db;
require_once __DIR__ . '/../includes/config.php';

// Decidir la accion a realizar,
$action = $_POST['accion'] ?? ($_GET['accion'] ?? '');
$origen = $_POST['origen'] ?? ($_GET['origen'] ?? 'index.php');

$success = false;

if ($action == 'crear' || $action == 'editar') {
    if ($_POST['proveedor'] === "0") {
        if ($_POST['nombreProveedor'] != '') {
            $stmt = $db->prepare("INSERT INTO proveedor (nombreProveedor, telefonoProveedor, web) 
                              VALUES (:nombreProveedor, :telefono, :web)");

            $success = $stmt->execute([
                ':nombreProveedor' => $_POST['nombreProveedor'],
                ':telefonoProveedor' => $_POST['telefonoProveedor'],
                ':web' => $_POST['web']
            ]);

            if ($success) {
                $idProveedor = $db->lastInsertId();
                $_POST['proveedor'] = $idProveedor;
            }
        }
        else {
            $_SESSION['errorMessage'] = "Debes seleccionar o crear un proveedor para crear el producto";
            header('Location: /productos/ficha.php?idProducto=' . $_POST['idProducto']);
            exit;
        }
    }
}


try {
    switch ($action) {
        case 'crear':
            $stmt = $db->prepare("INSERT INTO producto (nombreProveedor, proveedor, tipoRetrocomision, notasProducto) 
                              VALUES (:nombreProveedor, :proveedor, :tipoRetrocomision, :notasProducto)");

            $success = $stmt->execute([
                ':nombreProveedor' => $_POST['nombreProveedor'],
                ':proveedor' => $_POST['proveedor'],
                ':tipoRetrocomision' => empty($_POST['tipoRetrocomision']) ? null : $_POST['tipoRetrocomision'],
                ':notasProducto' => $_POST['notasProducto']
            ]);

            $infoMessage = "Producto creado correctamente";
            $errorMessage = "Hubo un problema al crear el producto";

            break;

        case 'editar':
            $prodOriginal = $db->query("SELECT * FROM producto WHERE idProducto = " .$_POST['idProducto'])->fetch(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("UPDATE producto 
                              SET nombreProveedor = :nombreProveedor,
                                  proveedor = :proveedor,
                                  tipoRetrocomision = :tipoRetrocomision,
                                  notasProducto = :notasProducto
                              WHERE idProducto = :id");

            $success = $stmt->execute([
                ':nombreProveedor' => $_POST['nombreProveedor'],
                ':proveedor' => $_POST['proveedor'],
                ':tipoRetrocomision' => empty($_POST['tipoRetrocomision']) ? null : $_POST['tipoRetrocomision'],
                ':notasProducto' => $_POST['notasProducto'],
                ':id' => $_POST['idProducto']
            ]);

            $prodModificado = $db->query("SELECT * FROM producto WHERE idProducto = " . $_POST['idProducto'])->fetch(PDO::FETCH_ASSOC);

            if($success) {
                $modificado = false;
                foreach ($prodModificado as $key => $value) {
                    if($value !== $prodOriginal[$key]){
                        $modificado = true;
                        break;
                    }
                }

                if(! $modificado) {
                    $success = null;
                }
            }

            $infoMessage = "Producto modificado correctamente";
            $errorMessage = "Hubo un problema al modificar el producto";

            break;

        case 'eliminar':
            $stmt = $db->prepare("UPDATE producto SET baja = 1 WHERE idProducto = :id");

            $success = $stmt->execute([
                ':id' => $_GET['idProducto']
            ]);

            $infoMessage = "Producto eliminado correctamente";
            $errorMessage = "Hubo un problema al eliminar el producto";

            break;

        default:
            throw new Exception("Accion incorrecta");
    }

    if(!is_null($success)) {
        if ($success):
            $_SESSION['infoMessage'] = $infoMessage;
        else:
            $_SESSION['errorMessage'] = $errorMessage;
        endif;
    }

} catch (Exception $e) {
    $_SESSION['errorMessage'] = $e->getMessage();
}

header("Location: $origen");
