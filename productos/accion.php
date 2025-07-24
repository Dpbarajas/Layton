<?php
global $db;
require_once __DIR__ . '/../includes/config.php';

// Decidir la accion a realizar,
$action = $_POST['accion'] ?? ($_GET['accion'] ?? '');
$origen = $_POST['origen'] ?? ($_GET['origen'] ?? 'index.php');

$success = false;

if ($action == 'crear' || $action == 'editar') {
    if ($_POST['proveedor'] === "0") {
        if ($_POST['nomProv'] != '') {
            $stmt = $db->prepare("INSERT INTO proveedor (nomProv, telefono, web) 
                              VALUES (:nomProv, :telefono, :web)");

            $success = $stmt->execute([
                ':nomProv' => $_POST['nomProv'],
                ':telefono' => $_POST['telefono'],
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
            $stmt = $db->prepare("INSERT INTO producto (nomProd, proveedor, retrocomision, notasProd) 
                              VALUES (:nomProd, :proveedor, :retrocomision, :notasProd)");

            $success = $stmt->execute([
                ':nomProd' => $_POST['nomProd'],
                ':proveedor' => $_POST['proveedor'],
                ':retrocomision' => empty($_POST['retrocomision']) ? null : $_POST['retrocomision'],
                ':notasProd' => $_POST['notasProd']
            ]);

            $infoMessage = "Producto creado correctamente";
            $errorMessage = "Hubo un problema al crear el producto";

            break;

        case 'editar':
            $prodOriginal = $db->query("SELECT * FROM producto WHERE idProducto = " .$_POST['idProducto'])->fetch(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("UPDATE producto 
                              SET nomProd = :nomProd,
                                  proveedor = :proveedor,
                                  retrocomision = :retrocomision,
                                  notasProd = :notasProd
                              WHERE idProducto = :id");

            $success = $stmt->execute([
                ':nomProd' => $_POST['nomProd'],
                ':proveedor' => $_POST['proveedor'],
                ':retrocomision' => empty($_POST['retrocomision']) ? null : $_POST['retrocomision'],
                ':notasProd' => $_POST['notasProd'],
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
