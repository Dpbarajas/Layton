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