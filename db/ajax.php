<?php
require_once 'functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? null;

if (!$action) {
    echo json_encode(['success' => false, 'message' => 'Azione non specificata']);
    exit;
}

switch ($action) {

    case 'updateItem':
        if (!isset($_POST['id']) || !isset($_POST['type'])) {
            echo json_encode(['success' => false, 'message' => 'ID o tipo non specificato']);
            exit;
        }
        
        $id = (int)$_POST['id'];
        $type = sanitizeInput($_POST['type']);
        $nome = sanitizeInput($_POST['nome']);
        $immagine_url = sanitizeInput($_POST['immagine_url']);
        $data = sanitizeInput($_POST['data']);
        $posseduto = isset($_POST['posseduto']) ? 1 : 0;
        
        $success = false;

        if ($type === 'libreria_update' && isset($_FILES['image_upload_edit']) && $_FILES['image_upload_edit']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/';
            $file = $_FILES['image_upload_edit'];
            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'webp');
            
            if (in_array($file_ext, $allowed_types) && $file['size'] <= 5 * 1024 * 1024) {
                $new_filename = uniqid('libreria_', true) . '.' . $file_ext;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    $immagine_url = '../uploads/' . $new_filename;
                    $_POST['immagine_url'] = $immagine_url;
                }
            }
        }
        
        switch($type) {
            case 'libri_normali':
                $prezzo = (float)($_POST['prezzo'] ?? 0);
                $autore = sanitizeInput($_POST['autore'] ?? '');
                
                $stmt = $pdo->prepare("UPDATE libri_normali SET titolo = ?, immagine_url = ?, data_pubblicazione = ?, prezzo = ?, autore = ?, posseduto = ? WHERE id = ?");
                $success = $stmt->execute([$nome, $immagine_url, $data, $prezzo, $autore, $posseduto, $id]);
                break;
                
            case 'vinili_cd':
                $costo = (float)($_POST['costo'] ?? 0);
                $autore = sanitizeInput($_POST['autore'] ?? '');
                $tipo_media = sanitizeInput($_POST['tipo_media'] ?? 'vinile');
                
                $stmt = $pdo->prepare("UPDATE vinili_cd SET tipo = ?, titolo = ?, immagine_url = ?, data_pubblicazione = ?, costo = ?, autore = ?, posseduto = ? WHERE id = ?");
                $success = $stmt->execute([$tipo_media, $nome, $immagine_url, $data, $costo, $autore, $posseduto, $id]);
                break;
                
            case 'funko_pop':
            case 'monster':
            case 'artbooks_anime':
                $prezzo = (float)($_POST['prezzo'] ?? 0);
                
                $stmt = $pdo->prepare("UPDATE $type SET nome = ?, immagine_url = ?, data_pubblicazione = ?, prezzo = ?, posseduto = ? WHERE id = ?");
                $success = $stmt->execute([$nome, $immagine_url, $data, $prezzo, $posseduto, $id]);
                break;
                
            case 'gameboys':
            case 'pokemon_game':
                $prezzo = (float)($_POST['prezzo'] ?? 0);
                $links = $_POST['links'] ?? [];
                $links = array_filter($links);
                $links_json = json_encode($links);
                
                $stmt = $pdo->prepare("UPDATE $type SET nome = ?, immagine_url = ?, data_pubblicazione = ?, prezzo = ?, links = ?, posseduto = ? WHERE id = ?");
                $success = $stmt->execute([$nome, $immagine_url, $data, $prezzo, $links_json, $posseduto, $id]);
                break;
                
            case 'numeri_yugioh':
                $prezzo = (float)($_POST['prezzo'] ?? 0);
                $codice = sanitizeInput($_POST['codice'] ?? '');
                
                $stmt = $pdo->prepare("UPDATE numeri_yugioh SET nome = ?, codice = ?, immagine_url = ?, data_pubblicazione = ?, prezzo = ?, posseduto = ? WHERE id = ?");
                $success = $stmt->execute([$nome, $codice, $immagine_url, $data, $prezzo, $posseduto, $id]);
                break;
                
            case 'duel_masters':
                $prezzo = (float)($_POST['prezzo'] ?? 0);
                $is_box = isset($_POST['is_box']) ? 1 : 0;
                
                $stmt = $pdo->prepare("UPDATE duel_masters SET nome = ?, immagine_url = ?, data_pubblicazione = ?, prezzo = ?, is_box = ?, posseduto = ? WHERE id = ?");
                $success = $stmt->execute([$nome, $immagine_url, $data, $prezzo, $is_box, $posseduto, $id]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Tipo non supportato']);
                exit;
        }
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Elemento aggiornato con successo']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Errore nell\'aggiornamento']);
        }
        break;
    case 'getSerie':
        if (!isset($_GET['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID non specificato']);
            exit;
        }
        
        $serie = getSerieById($_GET['id']);
        if ($serie) {
            $volumi = getVolumiPosseduti($_GET['id']);
            $serie['volumi_dettagli'] = $volumi;
            echo json_encode(['success' => true, 'serie' => $serie]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Serie non trovata']);
        }
        break;
    
    case 'getVariant':
        if (!isset($_GET['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID non specificato']);
            exit;
        }
        
        $variant = getVariantById($_GET['id']);
        if ($variant) {
            echo json_encode(['success' => true, 'variant' => $variant]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Variant non trovata']);
        }
        break;
    
    case 'getItem':
        if (!isset($_GET['type']) || !isset($_GET['id'])) {
            echo json_encode(['success' => false, 'message' => 'Tipo o ID non specificato']);
            exit;
        }
        
        $itemType = sanitizeInput($_GET['type']);
        $itemId = (int)$_GET['id'];
        
        // IMPORTANTE: usa il tipo esatto dalla richiesta
        $item = getItemById($itemType, $itemId);
        
        if ($item) {
            // Aggiungi il tipo all'item per debug
            $item['_debug_type'] = $itemType;
            echo json_encode(['success' => true, 'item' => $item]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Elemento non trovato', 'debug' => ['type' => $itemType, 'id' => $itemId]]);
        }
        break;
    
    case 'updateVolumi':
        if (!isset($_POST['serie_id']) || !isset($_POST['volumi'])) {
            echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
            exit;
        }
        
        $serie_id = (int)$_POST['serie_id'];
        $volumi_posseduti = json_decode($_POST['volumi'], true);
        
        if (updateVolumiPosseduti($serie_id, $volumi_posseduti)) {
            echo json_encode(['success' => true, 'message' => 'Volumi aggiornati con successo']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Errore nell\'aggiornamento dei volumi']);
        }
        break;
    
    case 'updateSerie':
        if (!isset($_POST['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID non specificato']);
            exit;
        }
        
        $id = (int)$_POST['id'];
        $titolo = sanitizeInput($_POST['titolo']);
        $immagine_url = sanitizeInput($_POST['immagine_url']);
        $data_pubblicazione = sanitizeInput($_POST['data_pubblicazione']);
        $volumi_totali = (int)$_POST['volumi_totali'];
        $prezzo_medio = (float)$_POST['prezzo_medio'];
        $stato = sanitizeInput($_POST['stato'] ?? 'completo');
        $da_prendere_subito = isset($_POST['da_prendere_subito']) ? 1 : 0;
        
        // Gestione categorie
        $categorie_input = sanitizeInput($_POST['categorie'] ?? '');
        $categorie = !empty($categorie_input) ? array_map('trim', explode(',', $categorie_input)) : null;
        
        if (updateSerie($id, $titolo, $immagine_url, $data_pubblicazione, $volumi_totali, $prezzo_medio, $stato, $da_prendere_subito, $categorie)) {
            echo json_encode(['success' => true, 'message' => 'Serie aggiornata con successo']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Errore nell\'aggiornamento della serie']);
        }
        break;
    
    case 'updateVariant':
        if (!isset($_POST['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID non specificato']);
            exit;
        }
        
        $id = (int)$_POST['id'];
        $titolo = sanitizeInput($_POST['titolo']);
        $immagine_url = sanitizeInput($_POST['immagine_url']);
        $data_rilascio = sanitizeInput($_POST['data_rilascio']);
        $costo_medio = (float)$_POST['costo_medio'];
        $posseduto = isset($_POST['posseduto']) ? 1 : 0;
        $stato = sanitizeInput($_POST['stato'] ?? 'completo');
        $da_prendere_subito = isset($_POST['da_prendere_subito']) ? 1 : 0;
        
        $categorie_input = sanitizeInput($_POST['categorie'] ?? '');
        $categorie = !empty($categorie_input) ? array_map('trim', explode(',', $categorie_input)) : null;
        
        if (updateVariant($id, $titolo, $immagine_url, $data_rilascio, $costo_medio, $posseduto, $stato, $da_prendere_subito, $categorie)) {
            echo json_encode(['success' => true, 'message' => 'Variant aggiornata con successo']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Errore nell\'aggiornamento della variant']);
        }
        break;
    
    case 'search':
        if (!isset($_GET['query'])) {
            echo json_encode(['success' => false, 'message' => 'Query di ricerca non specificata']);
            exit;
        }
        
        $results = searchItems($_GET['query']);
        echo json_encode(['success' => true, 'results' => $results]);
        break;
    
    case 'getRandomSerie':
        $serie = getRandomSerieMancante();
        if ($serie) {
            echo json_encode(['success' => true, 'serie' => $serie]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Nessuna serie mancante trovata']);
        }
        break;
    
    case 'getMangaStraniero':
    if (!isset($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'ID non specificato']);
        exit;
    }
    
    $manga = getMangaStranieroById($_GET['id']);
    if ($manga) {
        $volumi = getVolumiPossedatiStranieri($_GET['id']);
        $manga['volumi_dettagli'] = $volumi;
        echo json_encode(['success' => true, 'manga' => $manga]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Manga non trovato']);
    }
    break;

    case 'updateMangaStraniero':
        if (!isset($_POST['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID non specificato']);
            exit;
        }
        
        $id = (int)$_POST['id'];
        $titolo = sanitizeInput($_POST['titolo']);
        $immagine_url = sanitizeInput($_POST['immagine_url']);
        $data_pubblicazione = sanitizeInput($_POST['data_pubblicazione']);
        $volumi_totali = (int)$_POST['volumi_totali'];
        $prezzo_medio = (float)$_POST['prezzo_medio'];
        $stato = sanitizeInput($_POST['stato'] ?? 'completo');
        $da_prendere_subito = isset($_POST['da_prendere_subito']) ? 1 : 0;
        
        $categorie_input = sanitizeInput($_POST['categorie'] ?? '');
        $categorie = !empty($categorie_input) ? array_map('trim', explode(',', $categorie_input)) : null;
        
        if (updateMangaStraniero($id, $titolo, $immagine_url, $data_pubblicazione, $volumi_totali, $prezzo_medio, $stato, $da_prendere_subito, $categorie)) {
            echo json_encode(['success' => true, 'message' => 'Manga straniero aggiornato con successo']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Errore nell\'aggiornamento']);
        }
        break;

    case 'updateVolumiStranieri':
        if (!isset($_POST['serie_id']) || !isset($_POST['volumi'])) {
            echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
            exit;
        }
        
        $serie_id = (int)$_POST['serie_id'];
        $volumi_posseduti = json_decode($_POST['volumi'], true);
        
        if (updateVolumiPossedatiStranieri($serie_id, $volumi_posseduti)) {
            echo json_encode(['success' => true, 'message' => 'Volumi aggiornati con successo']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Errore nell\'aggiornamento']);
        }
        break;

    case 'updatePriorita':
        if (!isset($_POST['id']) || !isset($_POST['tipo'])) {
            echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
            exit;
        }
        
        $id = (int)$_POST['id'];
        $tipo = sanitizeInput($_POST['tipo']);
        $priorita = isset($_POST['priorita']) ? (int)$_POST['priorita'] : null;
        
        $success = false;
        if ($tipo === 'serie') {
            $success = updatePrioritaSerie($id, $priorita);
        } elseif ($tipo === 'variant') {
            $success = updatePrioritaVariant($id, $priorita);
        } elseif ($tipo === 'manga_straniero') {
            $success = updatePrioritaMangaStraniero($id, $priorita);
        }
        
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Priorità aggiornata']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Errore aggiornamento priorità']);
        }
        break;

    case 'getElementiConPriorita':
        if (!isset($_GET['tipo'])) {
            echo json_encode(['success' => false, 'message' => 'Tipo non specificato']);
            exit;
        }
        
        $tipo = sanitizeInput($_GET['tipo']);
        $elementi = [];
        
        if ($tipo === 'serie') {
            $elementi = getSerieConPriorita();
        } elseif ($tipo === 'variant') {
            $elementi = getVariantConPriorita();
        } elseif ($tipo === 'manga_straniero') {
            $elementi = getMangaStranieriConPriorita();
        }
        
        echo json_encode(['success' => true, 'elementi' => $elementi]);
        break;

    case 'trasformaManga':
        if (!isset($_POST['id']) || !isset($_POST['direzione'])) {
            echo json_encode(['success' => false, 'message' => 'Dati mancanti']);
            exit;
        }
        
        $id = (int)$_POST['id'];
        $direzione = sanitizeInput($_POST['direzione']);
        
        if ($direzione === 'in_straniero') {
            $result = trasformaMangaInStraniero($id);
        } elseif ($direzione === 'in_normale') {
            $result = trasformaMangaInNormale($id);
        } else {
            $result = ['success' => false, 'message' => 'Direzione non valida'];
        }
        
        echo json_encode($result);
        break;
    
    case 'getLibreriaUpdate':
        if (!isset($_GET['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID non specificato']);
            exit;
        }
        
        $id = (int)$_GET['id'];
        
        // Ottieni item da libreria_update
        $stmt = $pdo->prepare("SELECT * FROM libreria_update WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        
        if ($item) {
            echo json_encode(['success' => true, 'item' => $item]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Elemento non trovato']);
        }
        break;

    case 'updateLibreriaUpdate':
        if (!isset($_POST['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID non specificato']);
            exit;
        }
        
        $id = (int)$_POST['id'];
        $data_aggiunta = sanitizeInput($_POST['data_aggiunta']);
        $numero_manga = (int)($_POST['numero_manga'] ?? 0);
        $note = sanitizeInput($_POST['note'] ?? '');
        $immagine_url = null;
        
        try {
            // Gestione upload nuova immagine (opzionale)
            if (isset($_FILES['image_upload_edit']) && $_FILES['image_upload_edit']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = __DIR__ . '/../uploads/';
                
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file = $_FILES['image_upload_edit'];
                $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'webp');
                
                if (in_array($file_ext, $allowed_types) && $file['size'] <= 5 * 1024 * 1024) {
                    $new_filename = uniqid('libreria_', true) . '.' . $file_ext;
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        $immagine_url = '../uploads/' . $new_filename;
                        
                        // Elimina vecchia immagine
                        $stmt = $pdo->prepare("SELECT immagine_url FROM libreria_update WHERE id = ?");
                        $stmt->execute([$id]);
                        $old_item = $stmt->fetch();
                        
                        if ($old_item && !empty($old_item['immagine_url'])) {
                            $old_path = __DIR__ . '/../' . str_replace('../', '', $old_item['immagine_url']);
                            if (file_exists($old_path)) {
                                @unlink($old_path);
                            }
                        }
                    }
                }
            }
            
            // Update database
            if ($immagine_url) {
                // Update con nuova immagine
                $stmt = $pdo->prepare("UPDATE libreria_update SET immagine_url = ?, data_aggiunta = ?, numero_manga = ?, note = ? WHERE id = ?");
                $success = $stmt->execute([$immagine_url, $data_aggiunta, $numero_manga, $note, $id]);
            } else {
                // Update senza cambiare immagine
                $stmt = $pdo->prepare("UPDATE libreria_update SET data_aggiunta = ?, numero_manga = ?, note = ? WHERE id = ?");
                $success = $stmt->execute([$data_aggiunta, $numero_manga, $note, $id]);
            }
            
            if ($success) {
                echo json_encode(['success' => true, 'message' => 'Libreria Update aggiornata']);
            } else {
                $errorInfo = $stmt->errorInfo();
                echo json_encode(['success' => false, 'message' => 'Errore database: ' . $errorInfo[2]]);
            }
            
        } catch(Exception $e) {
            error_log("Errore updateLibreriaUpdate: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Errore: ' . $e->getMessage()]);
        }
        break;

    case 'deleteLibreriaUpdate':
        if (!isset($_POST['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID non specificato']);
            exit;
        }
        
        $id = (int)$_POST['id'];
        
        // Ottieni path immagine prima di eliminare
        $stmt = $pdo->prepare("SELECT immagine_url FROM libreria_update WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        
        // Elimina dal database
        $stmt = $pdo->prepare("DELETE FROM libreria_update WHERE id = ?");
        $success = $stmt->execute([$id]);
        
        if ($success) {
            // Elimina file immagine
            if ($item && !empty($item['immagine_url'])) {
                $image_path = __DIR__ . '/../' . str_replace('../', '', $item['immagine_url']);
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            echo json_encode(['success' => true, 'message' => 'Libreria Update eliminata']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Errore nell\'eliminazione']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Azione non valida: ' . $action]);
        break;
}
?>