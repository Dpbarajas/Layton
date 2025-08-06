<?php
global $db;
require_once __DIR__ . '/../includes/config.php';

// Decidir la accion a realizar,
$action = $_POST['accion'] ?? ($_GET['accion'] ?? '');
$origen = $_POST['origen'] ?? ($_GET['origen'] ?? 'index.php');

$tipoBalance = $_POST['tipoBalance'] ?? ($_GET['tipoBalance'] ?? '');

$id = "id" . ucfirst($tipoBalance);

$success = false;

$base = floatval(str_replace(['€', ',', ' '], ['', '.', ''], $_POST['baseImponible']) ?? 0);

function anyadirDocumentos($tipoBalance, $idBalance) {
    global $db;

    if (empty($_FILES['documentos']['name'][0])) return; // Nada que subir

    $ubicacion = "/uploads/$tipoBalance/$idBalance/";
    $directorio = __DIR__ . "/.." . $ubicacion;
    if (!file_exists($directorio)) {
        mkdir($directorio, 0777, true);
    }

    foreach ($_FILES['documentos']['tmp_name'] as $index => $tmpName) {
        if (!is_uploaded_file($tmpName)) continue;

        $nombreOriginal = basename($_FILES['documentos']['name'][$index]);
        $nombreDepurado = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $nombreOriginal);
        $destino = $ubicacion . $nombreDepurado;


        // Si ya existe, se añade sufijo único
        $i = 1;
        $infoArchivo = pathinfo($destino);
        while (file_exists($destino)) {
            $destino = $infoArchivo['dirname'] . '/' . $infoArchivo['filename'] . "_$i." . $infoArchivo['extension'];
            $i++;
        }

        move_uploaded_file($tmpName, $directorio . $nombreDepurado);

        // Opcional: guardar en tabla documento
        $stmt = $db->prepare("INSERT INTO documento (tipoBalance, idBalance, nombreArchivo, rutaArchivo) VALUES (?, ?, ?, ?)");
        $stmt->execute([$tipoBalance, $idBalance, $nombreDepurado, $destino]);
    }
}

try {
    switch ($action) {
        case 'crear':
            $stmt = $db->prepare("INSERT INTO $tipoBalance (fecha, empresa, baseImponible, iva, irpf) 
                              VALUES (:fecha, :empresa, :baseImponible, :iva, :irpf)");

            $success = $stmt->execute([
                ':fecha' => $_POST['fecha'],
                ':empresa' => $_POST['empresa'],
                ':baseImponible' => $base,
                ':iva' => $_POST['iva-perc'],
                ':irpf' => $_POST['irpf-perc']
            ]);

            if ($success) {
                $idBalance = $db->lastInsertId();
                anyadirDocumentos($tipoBalance, $idBalance);
            }

            $infoMessage = "Producto creado correctamente";
            $errorMessage = "Hubo un problema al crear el producto";

            break;

        case 'editar':
            $idBalance = $_POST['idBalance'];

            $prodOriginal = $db->query("SELECT * FROM $tipoBalance WHERE $id = " . $idBalance)->fetch(PDO::FETCH_ASSOC);

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
                ':iva' => $_POST['iva-perc'],
                ':irpf' => $_POST['irpf-perc'],
                ':id' => $idBalance
            ]);

            if ($success) {
                anyadirDocumentos($tipoBalance, $idBalance);
            }

            $balancModificado = $db->query("SELECT * FROM $tipoBalance WHERE id$tipoBalance = " . $_POST['idProducto'])->fetch(PDO::FETCH_ASSOC);

            if($success) {
                $modificado = false;
                foreach ($balancModificado as $key => $value) {
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