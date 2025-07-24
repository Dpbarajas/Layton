<?php
global $db;
require_once __DIR__ . '/../includes/config.php';

// Decidir la accion a realizar,
$action = $_POST['accion'] ?? ($_GET['accion'] ?? '');
$origen = $_POST['origen'] ?? ($_GET['origen'] ?? 'index.php');

$tipoBalance = $_POST['tipoBalance'] ?? ($_GET['tipoBalance'] ?? '');

$id = "id" . ucfirst($tipoBalance);

$success = false;

$base = str_replace(['€', ',', ' '], ['', '.', ''], $_POST['baseImponible']) ?? 0;
$iva = str_replace(['€', ',', ' '], ['', '.', ''], $_POST['iva-cant']) ?? 0;
$irpf = str_replace(['€', ',', ' '], ['', '.', ''], $_POST['irpf-cant']) ?? 0;

try {
    switch ($action) {
        case 'crear':
            $stmt = $db->prepare("INSERT INTO $tipoBalance (fecha, empresa, baseImponible, iva, irpf) 
                              VALUES (:fecha, :empresa, :baseImponible, :iva, :irpf)");

            $success = $stmt->execute([
                ':fecha' => $_POST['fecha'],
                ':empresa' => $_POST['empresa'],
                ':baseImponible' => $base,
                ':iva' => $iva,
                ':irpf' => $irpf
            ]);

            $infoMessage = "Producto creado correctamente";
            $errorMessage = "Hubo un problema al crear el producto";

            break;

        case 'editar':
            $prodOriginal = $db->query("SELECT * FROM $tipoBalance WHERE $id = " . $_POST[$id])->fetch(PDO::FETCH_ASSOC);

            $stmt = $db->prepare("UPDATE $tipoBalance 
                              SET fecha = :fecha,
                                  empresa = :empresa,
                                  baseImponible = :baseImponible,
                                  iva = :iva,
                                  irpf = :irpf
                              WHERE $id = :id");

            $success = $stmt->execute([
                ':fecha' => $_POST['fecha'],
                ':empresa' => $_POST['empresa'],
                ':baseImponible' => $base,
                ':iva' => $iva,
                ':irpf' => $irpf,
                ':' . $id => $_POST['idBalance']
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
            $stmt = $db->prepare("UPDATE $tipoBalance SET baja = 1 WHERE $id = :id");

            $success = $stmt->execute([
                ':id' => $_GET['idBalance']
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