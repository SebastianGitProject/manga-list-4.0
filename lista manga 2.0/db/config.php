<?php
// Configurazione database
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'manga_collection');

// Connessione al database
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Errore di connessione: " . $e->getMessage());
}

// Funzioni utili
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function formatDate($date) {
    if (!$date) return '';
    return date('d/m/Y', strtotime($date));
}

function formatPrice($price) {
    return number_format($price, 2, ',', '.') . ' €';
}
?>