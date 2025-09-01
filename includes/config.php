<?php
session_start();

$dsn = 'sqlite:' . __DIR__ . '/../db/contracts.db';
try {
    $db = new PDO($dsn);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error al conectar con la base de datos: " . $e->getMessage());
}
$title = "ToniNet";

$colores = [
    'Vendido' => '#3385ff',
    'Activado' => '#e6e600',
    'Facturable' => '#ff8000',
    'Facturado' => '#00b300',
    'Cancelado' => '#cc0000'
];
?>