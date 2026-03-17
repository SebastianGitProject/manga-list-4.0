<?php
// upload.php - Gestione upload immagini

// Configurazione
$upload_dir = '../uploads/';
$allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'webp');
$max_size = 5 * 3024 * 4032; // 5MB

// Verifica che esista la cartella uploads
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Impossibile creare la cartella uploads'
        ]);
        exit;
    }
}

header('Content-Type: application/json');

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $error_message = 'Errore nel caricamento del file';
    
    if (isset($_FILES['image']['error'])) {
        switch ($_FILES['image']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = 'File troppo grande';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = 'Upload parziale';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = 'Nessun file caricato';
                break;
            default:
                $error_message = 'Errore sconosciuto nell\'upload';
        }
    }
    
    echo json_encode([
        'success' => false,
        'message' => $error_message
    ]);
    exit;
}

$file = $_FILES['image'];
$file_size = $file['size'];
$file_tmp = $file['tmp_name'];
$file_name = $file['name'];
$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

// Verifica estensione
if (!in_array($file_ext, $allowed_types)) {
    echo json_encode([
        'success' => false,
        'message' => 'Tipo di file non consentito. Usa: ' . implode(', ', $allowed_types)
    ]);
    exit;
}

// Verifica dimensione
if ($file_size > $max_size) {
    echo json_encode([
        'success' => false,
        'message' => 'File troppo grande. Massimo 5MB'
    ]);
    exit;
}

// Verifica che sia effettivamente un'immagine
$check = getimagesize($file_tmp);
if ($check === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Il file non è un\'immagine valida'
    ]);
    exit;
}

// Genera nome unico
$new_filename = uniqid('libreria_', true) . '.' . $file_ext;
$upload_path = $upload_dir . $new_filename;

// Sposta il file
if (move_uploaded_file($file_tmp, $upload_path)) {
    // Ritorna il path relativo per il database
    $relative_path = '../uploads/' . $new_filename;
    
    // Log successo (opzionale)
    error_log("File uploaded successfully: " . $new_filename);
    
    echo json_encode([
        'success' => true,
        'filename' => $new_filename,
        'path' => $relative_path,
        'message' => 'File caricato con successo'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Errore nel salvataggio del file. Verifica i permessi della cartella uploads'
    ]);
}
?>