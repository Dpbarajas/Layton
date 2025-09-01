<?php
global $db;
require_once __DIR__ . '/../includes/config.php';

$success = null;

if ($_POST['accion'] === 'eliminar' && isset($_POST['idDocumento'])) {
    $idDocumento = intval($_POST['idDocumento']);

    // Obtén la ruta del archivo
    $stmt = $db->prepare("SELECT rutaArchivo FROM documento WHERE idDocumento = ?");
    $stmt->execute([$idDocumento]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $ruta = realpath(__DIR__ . '/..' . $row['rutaArchivo']);

        if (!file_exists($ruta)) {
            echo 'error';
            exit;
        }

        unlink($ruta);

        // Borra de la base de datos
        $stmt = $db->prepare("DELETE FROM documento WHERE idDocumento = ?");
        $success = $stmt->execute([$idDocumento]);
    } else {
        $success = false;
    }
} else if ($_POST['accion'] === 'cambiarNombre' && isset($_POST['idDocumento'], $_POST['nombre'])) {
    $idDocumento = intval($_POST['idDocumento']);
    $nuevoNombre = trim($_POST['nombre']);

    if ($nuevoNombre === '') {
        echo 'error';
        exit;
    }

    // Obtiene info actual
    $stmt = $db->prepare("SELECT rutaArchivo, nombreArchivo FROM documento WHERE idDocumento = ?");
    $stmt->execute([$idDocumento]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);


    if ($row) {
        $rutaBase = realpath(__DIR__ . '/../');

        $nombreActual = $row['nombreArchivo'];

        $rutaRelVieja = $row['rutaArchivo'];
        $rutaRelNueva = str_replace($nombreActual, $nuevoNombre, $rutaRelVieja);


        $rutaAbsVieja = $rutaBase . $rutaRelVieja;
        $rutaAbsNueva = $rutaBase . $rutaRelNueva;

        if (rename($rutaAbsVieja, $rutaAbsNueva)) {
            $stmt = $db->prepare("UPDATE documento SET nombreArchivo = ?, rutaArchivo = ? WHERE idDocumento = ?");
            $success = $stmt->execute([$nuevoNombre, $rutaRelNueva, $idDocumento]);

            if (! $success) {
                rename($rutaAbsNueva, $rutaAbsVieja);
            }
        } else {
            $success = false;
            rename($rutaAbsNueva, $rutaAbsVieja);
        }
    } else {
        $success = false;
    }
}

echo $success ? 'ok' : 'error';
exit;