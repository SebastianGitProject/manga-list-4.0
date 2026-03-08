<?php
require_once 'config.php';

// Funzioni per le serie manga con nuove funzionalità
function getSerieCollezione($orderBy = 'titolo', $search = '', $categoria = '', $daPrendere = null, $stato = '') {
    global $pdo;
    
    $whereClause = "WHERE sm.volumi_posseduti > 0";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND sm.titolo LIKE ?";
        $params[] = "%$search%";
    }
    
    if (!empty($categoria)) {
        $whereClause .= " AND JSON_CONTAINS(sm.categorie, ?)";
        $params[] = json_encode($categoria);
    }
    
    if ($daPrendere !== null) {
        $whereClause .= " AND sm.da_prendere_subito = ?";
        $params[] = $daPrendere;
    }
    
    if (!empty($stato)) {
        $whereClause .= " AND sm.stato = ?";
        $params[] = $stato;
    }
    
    $orderClause = match($orderBy) {
        'prezzo_asc' => "ORDER BY sm.prezzo_medio ASC, sm.titolo ASC",
        'prezzo_desc' => "ORDER BY sm.prezzo_medio DESC, sm.titolo ASC",
        'volumi_asc' => "ORDER BY sm.volumi_totali ASC, sm.titolo ASC",
        'volumi_desc' => "ORDER BY sm.volumi_totali DESC, sm.titolo ASC",
        'da_prendere' => "ORDER BY sm.da_prendere_subito DESC, sm.titolo ASC",
        'data_recente' => "ORDER BY sm.created_at DESC, sm.titolo ASC",
        'data_vecchia' => "ORDER BY sm.created_at ASC, sm.titolo ASC",
        'rarita_alta' => "ORDER BY CASE WHEN sm.rarita IS NULL THEN 1 ELSE 0 END, sm.rarita DESC, sm.titolo ASC",
        'rarita_bassa' => "ORDER BY CASE WHEN sm.rarita IS NULL THEN 1 ELSE 0 END, sm.rarita ASC, sm.titolo ASC",
        'stato_in_corso' => "ORDER BY CASE WHEN sm.stato = 'in_corso' THEN 0 ELSE 1 END, sm.titolo ASC",
        'stato_completo' => "ORDER BY CASE WHEN sm.stato = 'completo' THEN 0 ELSE 1 END, sm.titolo ASC",
        'stato_interrotta' => "ORDER BY CASE WHEN sm.stato = 'interrotta' THEN 0 ELSE 1 END, sm.titolo ASC",
        default => "ORDER BY sm.titolo ASC"
    };
    
    $sql = "SELECT sm.*, 
                   COALESCE(vp.volumi_posseduti_count, 0) as volumi_posseduti_actual
            FROM serie_manga sm
            LEFT JOIN (
                SELECT serie_id, COUNT(*) as volumi_posseduti_count
                FROM volumi_posseduti 
                WHERE posseduto = TRUE 
                GROUP BY serie_id
            ) vp ON sm.id = vp.serie_id
            $whereClause
            $orderClause";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getSerieMancanti($orderBy = 'titolo', $search = '', $categoria = '', $daPrendere = null, $stato = '') {
    global $pdo;
    
    $whereClause = "WHERE sm.volumi_posseduti = 0";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND sm.titolo LIKE ?";
        $params[] = "%$search%";
    }
    
    if (!empty($categoria)) {
        $whereClause .= " AND JSON_CONTAINS(sm.categorie, ?)";
        $params[] = json_encode($categoria);
    }
    
    if ($daPrendere !== null) {
        $whereClause .= " AND sm.da_prendere_subito = ?";
        $params[] = $daPrendere;
    }
    
    if (!empty($stato)) {
        $whereClause .= " AND sm.stato = ?";
        $params[] = $stato;
    }
    
    $orderClause = match($orderBy) {
        'prezzo_asc' => "ORDER BY sm.prezzo_medio ASC, sm.titolo ASC",
        'prezzo_desc' => "ORDER BY sm.prezzo_medio DESC, sm.titolo ASC",
        'volumi_asc' => "ORDER BY sm.volumi_totali ASC, sm.titolo ASC",
        'volumi_desc' => "ORDER BY sm.volumi_totali DESC, sm.titolo ASC",
        'da_prendere' => "ORDER BY sm.da_prendere_subito DESC, sm.titolo ASC",
        'data_recente' => "ORDER BY sm.created_at DESC, sm.titolo ASC",
        'data_vecchia' => "ORDER BY sm.created_at ASC, sm.titolo ASC",
        'rarita_alta' => "ORDER BY CASE WHEN sm.rarita IS NULL THEN 1 ELSE 0 END, sm.rarita DESC, sm.titolo ASC",
        'rarita_bassa' => "ORDER BY CASE WHEN sm.rarita IS NULL THEN 1 ELSE 0 END, sm.rarita ASC, sm.titolo ASC",
        'stato_in_corso' => "ORDER BY CASE WHEN sm.stato = 'in_corso' THEN 0 ELSE 1 END, sm.titolo ASC",
        'stato_completo' => "ORDER BY CASE WHEN sm.stato = 'completo' THEN 0 ELSE 1 END, sm.titolo ASC",
        'stato_interrotta' => "ORDER BY CASE WHEN sm.stato = 'interrotta' THEN 0 ELSE 1 END, sm.titolo ASC",
        default => "ORDER BY sm.titolo ASC"
    };
    
    $sql = "SELECT * FROM serie_manga sm $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getVariantCollezione($orderBy = 'titolo', $search = '', $categoria = '', $daPrendere = null, $stato = '') {
    global $pdo;
    
    $whereClause = "WHERE posseduto = 1";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND titolo LIKE ?";
        $params[] = "%$search%";
    }
    
    if (!empty($categoria)) {
        $whereClause .= " AND JSON_CONTAINS(categorie, ?)";
        $params[] = json_encode($categoria);
    }
    
    if ($daPrendere !== null) {
        $whereClause .= " AND da_prendere_subito = ?";
        $params[] = $daPrendere;
    }
    
    if (!empty($stato)) {
        $whereClause .= " AND stato = ?";
        $params[] = $stato;
    }
    
    $orderClause = match($orderBy) {
        'prezzo_asc' => "ORDER BY costo_medio ASC, titolo ASC",
        'prezzo_desc' => "ORDER BY costo_medio DESC, titolo ASC",
        'da_prendere' => "ORDER BY da_prendere_subito DESC, titolo ASC",
        'stato_in_corso' => "ORDER BY CASE WHEN stato = 'in_corso' THEN 0 ELSE 1 END, titolo ASC",
        'stato_completo' => "ORDER BY CASE WHEN stato = 'completo' THEN 0 ELSE 1 END, titolo ASC",
        'stato_interrotta' => "ORDER BY CASE WHEN stato = 'interrotta' THEN 0 ELSE 1 END, titolo ASC",
        default => "ORDER BY titolo ASC"
    };
    
    $sql = "SELECT * FROM variant_manga $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getVariantMancanti($orderBy = 'titolo', $search = '', $categoria = '', $daPrendere = null, $stato = '') {
    global $pdo;
    
    $whereClause = "WHERE posseduto = 0";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND titolo LIKE ?";
        $params[] = "%$search%";
    }
    
    if (!empty($categoria)) {
        $whereClause .= " AND JSON_CONTAINS(categorie, ?)";
        $params[] = json_encode($categoria);
    }
    
    if ($daPrendere !== null) {
        $whereClause .= " AND da_prendere_subito = ?";
        $params[] = $daPrendere;
    }
    
    if (!empty($stato)) {
        $whereClause .= " AND stato = ?";
        $params[] = $stato;
    }
    
    $orderClause = match($orderBy) {
        'prezzo_asc' => "ORDER BY costo_medio ASC, titolo ASC",
        'prezzo_desc' => "ORDER BY costo_medio DESC, titolo ASC",
        'da_prendere' => "ORDER BY da_prendere_subito DESC, titolo ASC",
        'stato_in_corso' => "ORDER BY CASE WHEN stato = 'in_corso' THEN 0 ELSE 1 END, titolo ASC",
        'stato_completo' => "ORDER BY CASE WHEN stato = 'completo' THEN 0 ELSE 1 END, titolo ASC",
        'stato_interrotta' => "ORDER BY CASE WHEN stato = 'interrotta' THEN 0 ELSE 1 END, titolo ASC",
        default => "ORDER BY titolo ASC"
    };
    
    $sql = "SELECT * FROM variant_manga $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getMangaStranieriCollezione($orderBy = 'titolo', $search = '', $categoria = '', $daPrendere = null, $stato = '') {
    global $pdo;
    
    $whereClause = "WHERE ms.volumi_posseduti > 0";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND ms.titolo LIKE ?";
        $params[] = "%$search%";
    }
    
    if (!empty($categoria)) {
        $whereClause .= " AND JSON_CONTAINS(ms.categorie, ?)";
        $params[] = json_encode($categoria);
    }
    
    if ($daPrendere !== null) {
        $whereClause .= " AND ms.da_prendere_subito = ?";
        $params[] = $daPrendere;
    }
    
    if (!empty($stato)) {
        $whereClause .= " AND ms.stato = ?";
        $params[] = $stato;
    }
    
    $orderClause = match($orderBy) {
        'prezzo_asc' => "ORDER BY ms.prezzo_medio ASC, ms.titolo ASC",
        'prezzo_desc' => "ORDER BY ms.prezzo_medio DESC, ms.titolo ASC",
        'volumi_asc' => "ORDER BY ms.volumi_totali ASC, ms.titolo ASC",
        'volumi_desc' => "ORDER BY ms.volumi_totali DESC, ms.titolo ASC",
        'da_prendere' => "ORDER BY ms.da_prendere_subito DESC, ms.titolo ASC",
        'stato_in_corso' => "ORDER BY CASE WHEN ms.stato = 'in_corso' THEN 0 ELSE 1 END, ms.titolo ASC",
        'stato_completo' => "ORDER BY CASE WHEN ms.stato = 'completo' THEN 0 ELSE 1 END, ms.titolo ASC",
        'stato_interrotta' => "ORDER BY CASE WHEN ms.stato = 'interrotta' THEN 0 ELSE 1 END, ms.titolo ASC",
        default => "ORDER BY ms.titolo ASC"
    };
    
    $sql = "SELECT ms.*, 
                   COALESCE(vps.volumi_posseduti_count, 0) as volumi_posseduti_actual
            FROM manga_stranieri ms
            LEFT JOIN (
                SELECT serie_id, COUNT(*) as volumi_posseduti_count
                FROM volumi_posseduti_stranieri 
                WHERE posseduto = TRUE 
                GROUP BY serie_id
            ) vps ON ms.id = vps.serie_id
            $whereClause
            $orderClause";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}


function getMangaStranieriMancanti($orderBy = 'titolo', $search = '', $categoria = '', $daPrendere = null, $stato = '') {
    global $pdo;
    
    $whereClause = "WHERE ms.volumi_posseduti = 0";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND ms.titolo LIKE ?";
        $params[] = "%$search%";
    }
    
    if (!empty($categoria)) {
        $whereClause .= " AND JSON_CONTAINS(ms.categorie, ?)";
        $params[] = json_encode($categoria);
    }
    
    if ($daPrendere !== null) {
        $whereClause .= " AND ms.da_prendere_subito = ?";
        $params[] = $daPrendere;
    }
    
    if (!empty($stato)) {
        $whereClause .= " AND ms.stato = ?";
        $params[] = $stato;
    }
    
    $orderClause = match($orderBy) {
        'prezzo_asc' => "ORDER BY ms.prezzo_medio ASC, ms.titolo ASC",
        'prezzo_desc' => "ORDER BY ms.prezzo_medio DESC, ms.titolo ASC",
        'volumi_asc' => "ORDER BY ms.volumi_totali ASC, ms.titolo ASC",
        'volumi_desc' => "ORDER BY ms.volumi_totali DESC, ms.titolo ASC",
        'da_prendere' => "ORDER BY ms.da_prendere_subito DESC, ms.titolo ASC",
        'stato_in_corso' => "ORDER BY CASE WHEN ms.stato = 'in_corso' THEN 0 ELSE 1 END, ms.titolo ASC",
        'stato_completo' => "ORDER BY CASE WHEN ms.stato = 'completo' THEN 0 ELSE 1 END, ms.titolo ASC",
        'stato_interrotta' => "ORDER BY CASE WHEN ms.stato = 'interrotta' THEN 0 ELSE 1 END, ms.titolo ASC",
        default => "ORDER BY ms.titolo ASC"
    };
    
    $sql = "SELECT * FROM manga_stranieri ms $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function addMangaStraniero($titolo, $immagine_url, $data_pubblicazione, $volumi_totali, $volumi_posseduti, $prezzo_medio = 0.00, $stato = 'completo', $da_prendere_subito = false, $categorie = null, $rarita = null) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        $categorie_json = $categorie ? json_encode($categorie) : null;
        
        $stmt = $pdo->prepare("INSERT INTO manga_stranieri (titolo, immagine_url, data_pubblicazione, volumi_totali, volumi_posseduti, prezzo_medio, stato, da_prendere_subito, categorie, rarita) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$titolo, $immagine_url, $data_pubblicazione, $volumi_totali, $volumi_posseduti, $prezzo_medio, $stato, $da_prendere_subito, $categorie_json, $rarita]);
        
        $serie_id = $pdo->lastInsertId();
        
        for ($i = 1; $i <= $volumi_totali; $i++) {
            $posseduto = ($i <= $volumi_posseduti) ? 1 : 0;
            $stmt = $pdo->prepare("INSERT INTO volumi_posseduti_stranieri (serie_id, numero_volume, posseduto) VALUES (?, ?, ?)");
            $stmt->execute([$serie_id, $i, $posseduto]);
        }
        
        $pdo->commit();
        return true;
    } catch(PDOException $e) {
        $pdo->rollback();
        return false;
    }
}

function getMangaStranieroById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM manga_stranieri WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getVolumiPossedatiStranieri($serie_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM volumi_posseduti_stranieri WHERE serie_id = ? ORDER BY numero_volume");
    $stmt->execute([$serie_id]);
    return $stmt->fetchAll();
}

function updateMangaStraniero($id, $titolo, $immagine_url, $data_pubblicazione, $volumi_totali, $prezzo_medio, $stato = null, $da_prendere_subito = null, $categorie = null, $rarita = null) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        $categorie_json = $categorie ? json_encode($categorie) : null;
        
        $stmt = $pdo->prepare("UPDATE manga_stranieri SET titolo = ?, immagine_url = ?, data_pubblicazione = ?, volumi_totali = ?, prezzo_medio = ?, stato = ?, da_prendere_subito = ?, categorie = ?, rarita = ? WHERE id = ?");
        $stmt->execute([$titolo, $immagine_url, $data_pubblicazione, $volumi_totali, $prezzo_medio, $stato, $da_prendere_subito, $categorie_json, $rarita, $id]);
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM volumi_posseduti_stranieri WHERE serie_id = ?");
        $stmt->execute([$id]);
        $current_count = $stmt->fetch()['count'];
        
        if ($current_count != $volumi_totali) {
            $stmt = $pdo->prepare("DELETE FROM volumi_posseduti_stranieri WHERE serie_id = ?");
            $stmt->execute([$id]);
            
            for ($i = 1; $i <= $volumi_totali; $i++) {
                $stmt = $pdo->prepare("INSERT INTO volumi_posseduti_stranieri (serie_id, numero_volume, posseduto) VALUES (?, ?, ?)");
                $stmt->execute([$id, $i, 0]);
            }
            
            $stmt = $pdo->prepare("UPDATE manga_stranieri SET volumi_posseduti = 0 WHERE id = ?");
            $stmt->execute([$id]);
        }
        
        $pdo->commit();
        return true;
    } catch(PDOException $e) {
        $pdo->rollback();
        return false;
    }
}

function updateVolumiPossedatiStranieri($serie_id, $volumi_array) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT volumi_totali FROM manga_stranieri WHERE id = ?");
        $stmt->execute([$serie_id]);
        $serie = $stmt->fetch();
        
        if (!$serie) {
            throw new Exception("Serie non trovata");
        }
        
        $stmt = $pdo->prepare("DELETE FROM volumi_posseduti_stranieri WHERE serie_id = ?");
        $stmt->execute([$serie_id]);
        
        for ($i = 1; $i <= $serie['volumi_totali']; $i++) {
            $posseduto = in_array($i, $volumi_array) ? 1 : 0;
            $stmt = $pdo->prepare("INSERT INTO volumi_posseduti_stranieri (serie_id, numero_volume, posseduto) VALUES (?, ?, ?)");
            $stmt->execute([$serie_id, $i, $posseduto]);
        }
        
        $volumi_posseduti_count = count($volumi_array);
        $stmt = $pdo->prepare("UPDATE manga_stranieri SET volumi_posseduti = ? WHERE id = ?");
        $stmt->execute([$volumi_posseduti_count, $serie_id]);
        
        $pdo->commit();
        return true;
    } catch(Exception $e) {
        $pdo->rollback();
        return false;
    }
}

function removeMangaStraniero($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM manga_stranieri WHERE id = ?");
        $stmt->execute([$id]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

function getLibreriaUpdate($orderBy = 'data_aggiunta', $search = '') {
    global $pdo;
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND note LIKE ?";
        $params[] = "%$search%";
    }
    
    $orderClause = "ORDER BY data_aggiunta DESC";
    
    $sql = "SELECT * FROM libreria_update $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function addLibreriaUpdate($immagine_url, $data_aggiunta, $numero_manga, $note = '') {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO libreria_update (immagine_url, data_aggiunta, numero_manga, note) VALUES (?, ?, ?, ?)");
        $stmt->execute([$immagine_url, $data_aggiunta, $numero_manga, $note]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// ===== FUNZIONI PER LIBRI LOVECRAFT =====

function getLibriLovecraft($orderBy = 'titolo', $search = '', $posseduto = null) {
    global $pdo;
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND titolo LIKE ?";
        $params[] = "%$search%";
    }
    
    if ($posseduto !== null) {
        $whereClause .= " AND posseduto = ?";
        $params[] = $posseduto;
    }
    
    $orderClause = match($orderBy) {
        'costo_asc' => "ORDER BY costo ASC, titolo ASC",
        'costo_desc' => "ORDER BY costo DESC, titolo ASC",
        default => "ORDER BY titolo ASC"
    };
    
    $sql = "SELECT * FROM libri_lovecraft $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function addLibroLovecraft($titolo, $immagine_url, $data_pubblicazione, $costo, $posseduto) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO libri_lovecraft (titolo, immagine_url, data_pubblicazione, costo, posseduto) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$titolo, $immagine_url, $data_pubblicazione, $costo, $posseduto]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// ===== FUNZIONI PER LIBRI GIAPPONESI =====

function getLibriGiapponesi($orderBy = 'titolo', $search = '', $posseduto = null) {
    global $pdo;
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND (titolo LIKE ? OR autore LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($posseduto !== null) {
        $whereClause .= " AND posseduto = ?";
        $params[] = $posseduto;
    }
    
    $orderClause = match($orderBy) {
        'costo_asc' => "ORDER BY costo ASC, titolo ASC",
        'costo_desc' => "ORDER BY costo DESC, titolo ASC",
        'autore' => "ORDER BY autore ASC, titolo ASC",
        default => "ORDER BY titolo ASC"
    };
    
    $sql = "SELECT * FROM libri_giapponesi $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function addLibroGiapponese($titolo, $immagine_url, $costo, $autore, $posseduto) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO libri_giapponesi (titolo, immagine_url, costo, autore, posseduto) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$titolo, $immagine_url, $costo, $autore, $posseduto]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// ===== AGGIORNAMENTO PRIORITÀ =====

function updatePrioritaSerie($id, $priorita) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE serie_manga SET priorita = ? WHERE id = ?");
        $stmt->execute([$priorita, $id]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

function updatePrioritaVariant($id, $priorita) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE variant_manga SET priorita = ? WHERE id = ?");
        $stmt->execute([$priorita, $id]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

function updatePrioritaMangaStraniero($id, $priorita) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE manga_stranieri SET priorita = ? WHERE id = ?");
        $stmt->execute([$priorita, $id]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Ottieni elementi con da_prendere_subito ordinati per priorità
function getSerieConPriorita() {
    global $pdo;
    $stmt = $pdo->query("SELECT sm.*, COALESCE(vp.volumi_posseduti_count, 0) as volumi_posseduti_actual
            FROM serie_manga sm
            LEFT JOIN (
                SELECT serie_id, COUNT(*) as volumi_posseduti_count
                FROM volumi_posseduti 
                WHERE posseduto = TRUE 
                GROUP BY serie_id
            ) vp ON sm.id = vp.serie_id
            WHERE sm.da_prendere_subito = 1
            ORDER BY 
                CASE WHEN sm.priorita IS NULL THEN 1 ELSE 0 END,
                sm.priorita ASC,
                sm.titolo ASC");
    return $stmt->fetchAll();
}

function getVariantConPriorita() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM variant_manga 
            WHERE da_prendere_subito = 1
            ORDER BY 
                CASE WHEN priorita IS NULL THEN 1 ELSE 0 END,
                priorita ASC,
                titolo ASC");
    return $stmt->fetchAll();
}

function getMangaStranieriConPriorita() {
    global $pdo;
    $stmt = $pdo->query("SELECT ms.*, COALESCE(vps.volumi_posseduti_count, 0) as volumi_posseduti_actual
            FROM manga_stranieri ms
            LEFT JOIN (
                SELECT serie_id, COUNT(*) as volumi_posseduti_count
                FROM volumi_posseduti_stranieri 
                WHERE posseduto = TRUE 
                GROUP BY serie_id
            ) vps ON ms.id = vps.serie_id
            WHERE ms.da_prendere_subito = 1
            ORDER BY 
                CASE WHEN ms.priorita IS NULL THEN 1 ELSE 0 END,
                ms.priorita ASC,
                ms.titolo ASC");
    return $stmt->fetchAll();
}



function getCategorieSerieUniche() {
    global $pdo;
    $stmt = $pdo->query("SELECT DISTINCT categoria FROM categorie_serie WHERE categoria IS NOT NULL ORDER BY categoria");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getCategorieVariantUniche() {
    global $pdo;
    $stmt = $pdo->query("SELECT DISTINCT categoria FROM categorie_variant WHERE categoria IS NOT NULL ORDER BY categoria");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getSerieById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM serie_manga WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getVariantById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM variant_manga WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Funzioni per i volumi individuali
function getVolumiPosseduti($serie_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM volumi_posseduti WHERE serie_id = ? ORDER BY numero_volume");
    $stmt->execute([$serie_id]);
    return $stmt->fetchAll();
}

function updateVolumiPosseduti($serie_id, $volumi_array) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT volumi_totali FROM serie_manga WHERE id = ?");
        $stmt->execute([$serie_id]);
        $serie = $stmt->fetch();
        
        if (!$serie) {
            throw new Exception("Serie non trovata");
        }
        
        $stmt = $pdo->prepare("DELETE FROM volumi_posseduti WHERE serie_id = ?");
        $stmt->execute([$serie_id]);
        
        for ($i = 1; $i <= $serie['volumi_totali']; $i++) {
            $posseduto = in_array($i, $volumi_array) ? 1 : 0;
            $stmt = $pdo->prepare("INSERT INTO volumi_posseduti (serie_id, numero_volume, posseduto) VALUES (?, ?, ?)");
            $stmt->execute([$serie_id, $i, $posseduto]);
        }
        
        $volumi_posseduti_count = count($volumi_array);
        $stmt = $pdo->prepare("UPDATE serie_manga SET volumi_posseduti = ? WHERE id = ?");
        $stmt->execute([$volumi_posseduti_count, $serie_id]);
        
        $pdo->commit();
        return true;
    } catch(Exception $e) {
        $pdo->rollback();
        return false;
    }
}

// NUOVE FUNZIONI PER LE SEZIONI AGGIUNTIVE

function getLibriNormali($orderBy = 'titolo', $search = '', $posseduto = null) {
    global $pdo;
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND (titolo LIKE ? OR autore LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($posseduto !== null) {
        $whereClause .= " AND posseduto = ?";
        $params[] = $posseduto;
    }
    
    $orderClause = match($orderBy) {
        'prezzo_asc' => "ORDER BY prezzo ASC, titolo ASC",
        'prezzo_desc' => "ORDER BY prezzo DESC, titolo ASC",
        'autore' => "ORDER BY autore ASC, titolo ASC",
        default => "ORDER BY titolo ASC"
    };
    
    $sql = "SELECT * FROM libri_normali $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function addLibroNormale($titolo, $immagine_url, $data_pubblicazione, $prezzo, $autore, $posseduto) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO libri_normali (titolo, immagine_url, data_pubblicazione, prezzo, autore, posseduto) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$titolo, $immagine_url, $data_pubblicazione, $prezzo, $autore, $posseduto]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

function getViniliCD($orderBy = 'titolo', $search = '', $tipoMedia = '', $posseduto = null) {
    global $pdo;
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND (titolo LIKE ? OR autore LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($tipoMedia)) {
        $whereClause .= " AND tipo = ?";
        $params[] = $tipoMedia;
    }
    
    if ($posseduto !== null) {
        $whereClause .= " AND posseduto = ?";
        $params[] = $posseduto;
    }
    
    $orderClause = match($orderBy) {
        'costo_asc' => "ORDER BY costo ASC, titolo ASC",
        'costo_desc' => "ORDER BY costo DESC, titolo ASC",
        'autore' => "ORDER BY autore ASC, titolo ASC",
        'tipo' => "ORDER BY tipo ASC, titolo ASC",
        default => "ORDER BY titolo ASC"
    };
    
    $sql = "SELECT * FROM vinili_cd $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}


function addVinileCD($tipo, $titolo, $immagine_url, $data_pubblicazione, $costo, $autore, $posseduto) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO vinili_cd (tipo, titolo, immagine_url, data_pubblicazione, costo, autore, posseduto) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tipo, $titolo, $immagine_url, $data_pubblicazione, $costo, $autore, $posseduto]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Funzioni per Funko Pop
function getFunkoPop($orderBy = 'nome', $search = '', $posseduto = null) {
    global $pdo;
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND nome LIKE ?";
        $params[] = "%$search%";
    }
    
    if ($posseduto !== null) {
        $whereClause .= " AND posseduto = ?";
        $params[] = $posseduto;
    }
    
    $orderClause = match($orderBy) {
        'prezzo_asc' => "ORDER BY prezzo ASC, nome ASC",
        'prezzo_desc' => "ORDER BY prezzo DESC, nome ASC",
        default => "ORDER BY nome ASC"
    };
    
    $sql = "SELECT * FROM funko_pop $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function addFunkoPop($nome, $immagine_url, $data_pubblicazione, $prezzo, $posseduto) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO funko_pop (nome, immagine_url, data_pubblicazione, prezzo, posseduto) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $immagine_url, $data_pubblicazione, $prezzo, $posseduto]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Funzioni per Monster
function getMonster($orderBy = 'nome', $search = '', $posseduto = null) {
    global $pdo;
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND nome LIKE ?";
        $params[] = "%$search%";
    }
    
    if ($posseduto !== null) {
        $whereClause .= " AND posseduto = ?";
        $params[] = $posseduto;
    }
    
    $orderClause = match($orderBy) {
        'prezzo_asc' => "ORDER BY prezzo ASC, nome ASC",
        'prezzo_desc' => "ORDER BY prezzo DESC, nome ASC",
        default => "ORDER BY nome ASC"
    };
    
    $sql = "SELECT * FROM monster $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function addMonster($nome, $immagine_url, $data_pubblicazione, $prezzo, $posseduto) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO monster (nome, immagine_url, data_pubblicazione, prezzo, posseduto) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $immagine_url, $data_pubblicazione, $prezzo, $posseduto]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Funzioni per Artbooks Anime
function getArtbooksAnime($orderBy = 'nome', $search = '', $posseduto = null) {
    global $pdo;
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND nome LIKE ?";
        $params[] = "%$search%";
    }
    
    if ($posseduto !== null) {
        $whereClause .= " AND posseduto = ?";
        $params[] = $posseduto;
    }
    
    $orderClause = match($orderBy) {
        'prezzo_asc' => "ORDER BY prezzo ASC, nome ASC",
        'prezzo_desc' => "ORDER BY prezzo DESC, nome ASC",
        default => "ORDER BY nome ASC"
    };
    
    $sql = "SELECT * FROM artbooks_anime $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function addArtbooksAnime($nome, $immagine_url, $data_pubblicazione, $prezzo, $posseduto) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO artbooks_anime (nome, immagine_url, data_pubblicazione, prezzo, posseduto) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $immagine_url, $data_pubblicazione, $prezzo, $posseduto]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Funzioni per Gameboys
function getGameboys($orderBy = 'nome', $search = '', $posseduto = null) {
    global $pdo;
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND nome LIKE ?";
        $params[] = "%$search%";
    }
    
    if ($posseduto !== null) {
        $whereClause .= " AND posseduto = ?";
        $params[] = $posseduto;
    }
    
    $orderClause = match($orderBy) {
        'prezzo_asc' => "ORDER BY prezzo ASC, nome ASC",
        'prezzo_desc' => "ORDER BY prezzo DESC, nome ASC",
        default => "ORDER BY nome ASC"
    };
    
    $sql = "SELECT * FROM gameboys $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function addGameboys($nome, $immagine_url, $data_pubblicazione, $prezzo, $links, $posseduto) {
    global $pdo;
    try {
        $links_json = json_encode($links);
        $stmt = $pdo->prepare("INSERT INTO gameboys (nome, immagine_url, data_pubblicazione, prezzo, links, posseduto) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $immagine_url, $data_pubblicazione, $prezzo, $links_json, $posseduto]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Funzioni per Pokemon Game
function getPokemonGame($orderBy = 'nome', $search = '', $posseduto = null) {
    global $pdo;
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND nome LIKE ?";
        $params[] = "%$search%";
    }
    
    if ($posseduto !== null) {
        $whereClause .= " AND posseduto = ?";
        $params[] = $posseduto;
    }
    
    $orderClause = match($orderBy) {
        'prezzo_asc' => "ORDER BY prezzo ASC, nome ASC",
        'prezzo_desc' => "ORDER BY prezzo DESC, nome ASC",
        default => "ORDER BY nome ASC"
    };
    
    $sql = "SELECT * FROM pokemon_game $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function addPokemonGame($nome, $immagine_url, $data_pubblicazione, $prezzo, $links, $posseduto) {
    global $pdo;
    try {
        $links_json = json_encode($links);
        $stmt = $pdo->prepare("INSERT INTO pokemon_game (nome, immagine_url, data_pubblicazione, prezzo, links, posseduto) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $immagine_url, $data_pubblicazione, $prezzo, $links_json, $posseduto]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Funzioni per Numeri Yu-Gi-Oh
function getNumeriYugioh($orderBy = 'nome', $search = '', $posseduto = null) {
    global $pdo;
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND (nome LIKE ? OR codice LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($posseduto !== null) {
        $whereClause .= " AND posseduto = ?";
        $params[] = $posseduto;
    }
    
    $orderClause = match($orderBy) {
        'prezzo_asc' => "ORDER BY prezzo ASC, nome ASC",
        'prezzo_desc' => "ORDER BY prezzo DESC, nome ASC",
        default => "ORDER BY nome ASC"
    };
    
    $sql = "SELECT * FROM numeri_yugioh $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function addNumeriYugioh($nome, $codice, $immagine_url, $data_pubblicazione, $prezzo, $posseduto) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO numeri_yugioh (nome, codice, immagine_url, data_pubblicazione, prezzo, posseduto) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $codice, $immagine_url, $data_pubblicazione, $prezzo, $posseduto]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Funzioni per Duel Masters
function getDuelMasters($orderBy = 'nome', $search = '', $posseduto = null) {
    global $pdo;
    
    $whereClause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $whereClause .= " AND nome LIKE ?";
        $params[] = "%$search%";
    }
    
    if ($posseduto !== null) {
        $whereClause .= " AND posseduto = ?";
        $params[] = $posseduto;
    }
    
    $orderClause = match($orderBy) {
        'prezzo_asc' => "ORDER BY prezzo ASC, nome ASC",
        'prezzo_desc' => "ORDER BY prezzo DESC, nome ASC",
        default => "ORDER BY nome ASC"
    };
    
    $sql = "SELECT * FROM duel_masters $whereClause $orderClause";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function addDuelMasters($nome, $immagine_url, $data_pubblicazione, $prezzo, $is_box, $posseduto) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO duel_masters (nome, immagine_url, data_pubblicazione, prezzo, is_box, posseduto) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $immagine_url, $data_pubblicazione, $prezzo, $is_box, $posseduto]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Funzioni per aggiungere elementi aggiornate
function addSerie($titolo, $immagine_url, $data_pubblicazione, $volumi_totali, $volumi_posseduti, $prezzo_medio = 0.00, $stato = 'completo', $da_prendere_subito = false, $categorie = null, $rarita = null) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        $categorie_json = $categorie ? json_encode($categorie) : null;
        
        $stmt = $pdo->prepare("INSERT INTO serie_manga (titolo, immagine_url, data_pubblicazione, volumi_totali, volumi_posseduti, prezzo_medio, stato, da_prendere_subito, categorie, rarita) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$titolo, $immagine_url, $data_pubblicazione, $volumi_totali, $volumi_posseduti, $prezzo_medio, $stato, $da_prendere_subito, $categorie_json, $rarita]);
        
        $serie_id = $pdo->lastInsertId();
        
        for ($i = 1; $i <= $volumi_totali; $i++) {
            $posseduto = ($i <= $volumi_posseduti) ? 1 : 0;
            $stmt = $pdo->prepare("INSERT INTO volumi_posseduti (serie_id, numero_volume, posseduto) VALUES (?, ?, ?)");
            $stmt->execute([$serie_id, $i, $posseduto]);
        }
        
        $pdo->commit();
        return true;
    } catch(PDOException $e) {
        $pdo->rollback();
        return false;
    }
}

function addVariant($titolo, $immagine_url, $data_rilascio, $costo_medio, $posseduto, $stato = 'completo', $da_prendere_subito = false, $categorie = null) {
    global $pdo;
    try {
        $categorie_json = $categorie ? json_encode($categorie) : null;
        
        $stmt = $pdo->prepare("INSERT INTO variant_manga (titolo, immagine_url, data_rilascio, costo_medio, posseduto, stato, da_prendere_subito, categorie) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$titolo, $immagine_url, $data_rilascio, $costo_medio, $posseduto, $stato, $da_prendere_subito, $categorie_json]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Funzioni per rimuovere elementi
function removeSerie($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM serie_manga WHERE id = ?");
        $stmt->execute([$id]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

function removeVariant($id) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM variant_manga WHERE id = ?");
        $stmt->execute([$id]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Funzioni generiche di rimozione per le nuove sezioni
function removeByTable($table, $id) {
    global $pdo;
    try {
        $allowedTables = [
            'funko_pop', 'monster', 'artbooks_anime', 'gameboys', 
            'pokemon_game', 'numeri_yugioh', 'duel_masters',
            'libri_normali', 'vinili_cd', 'libri_lovecraft', 
            'libri_giapponesi', 'libreria_update'
        ];
        
        if (!in_array($table, $allowedTables)) {
            return false;
        }
        
        $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->execute([$id]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}



function trasformaMangaInStraniero($serie_id) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        // 1. Ottieni dati dalla serie normale
        $stmt = $pdo->prepare("SELECT * FROM serie_manga WHERE id = ?");
        $stmt->execute([$serie_id]);
        $serie = $stmt->fetch();
        
        if (!$serie) {
            throw new Exception("Serie non trovata");
        }
        
        // 2. Inserisci in manga_stranieri
        $stmt = $pdo->prepare("INSERT INTO manga_stranieri (titolo, immagine_url, data_pubblicazione, volumi_totali, volumi_posseduti, prezzo_medio, stato, da_prendere_subito, priorita, categorie, rarita) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $serie['titolo'],
            $serie['immagine_url'],
            $serie['data_pubblicazione'],
            $serie['volumi_totali'],
            $serie['volumi_posseduti'],
            $serie['prezzo_medio'],
            $serie['stato'],
            $serie['da_prendere_subito'],
            $serie['priorita'],
            $serie['categorie'],
            $serie['rarita']
        ]);
        
        $new_id = $pdo->lastInsertId();
        
        // 3. Copia volumi posseduti
        $stmt = $pdo->prepare("SELECT * FROM volumi_posseduti WHERE serie_id = ?");
        $stmt->execute([$serie_id]);
        $volumi = $stmt->fetchAll();
        
        foreach ($volumi as $volume) {
            $stmt = $pdo->prepare("INSERT INTO volumi_posseduti_stranieri (serie_id, numero_volume, posseduto, data_acquisto, prezzo_pagato) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $new_id,
                $volume['numero_volume'],
                $volume['posseduto'],
                $volume['data_acquisto'],
                $volume['prezzo_pagato']
            ]);
        }
        
        // 4. Elimina dalla tabella originale
        $stmt = $pdo->prepare("DELETE FROM serie_manga WHERE id = ?");
        $stmt->execute([$serie_id]);
        
        $pdo->commit();
        return ['success' => true, 'new_id' => $new_id];
    } catch(Exception $e) {
        $pdo->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function trasformaMangaInNormale($manga_id) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        // 1. Ottieni dati dal manga straniero
        $stmt = $pdo->prepare("SELECT * FROM manga_stranieri WHERE id = ?");
        $stmt->execute([$manga_id]);
        $manga = $stmt->fetch();
        
        if (!$manga) {
            throw new Exception("Manga non trovato");
        }
        
        // 2. Inserisci in serie_manga
        $stmt = $pdo->prepare("INSERT INTO serie_manga (titolo, immagine_url, data_pubblicazione, volumi_totali, volumi_posseduti, prezzo_medio, stato, da_prendere_subito, priorita, categorie, rarita) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $manga['titolo'],
            $manga['immagine_url'],
            $manga['data_pubblicazione'],
            $manga['volumi_totali'],
            $manga['volumi_posseduti'],
            $manga['prezzo_medio'],
            $manga['stato'],
            $manga['da_prendere_subito'],
            $manga['priorita'],
            $manga['categorie'],
            $manga['rarita']
        ]);
        
        $new_id = $pdo->lastInsertId();
        
        // 3. Copia volumi posseduti
        $stmt = $pdo->prepare("SELECT * FROM volumi_posseduti_stranieri WHERE serie_id = ?");
        $stmt->execute([$manga_id]);
        $volumi = $stmt->fetchAll();
        
        foreach ($volumi as $volume) {
            $stmt = $pdo->prepare("INSERT INTO volumi_posseduti (serie_id, numero_volume, posseduto, data_acquisto, prezzo_pagato) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $new_id,
                $volume['numero_volume'],
                $volume['posseduto'],
                $volume['data_acquisto'],
                $volume['prezzo_pagato']
            ]);
        }
        
        // 4. Elimina dalla tabella originale
        $stmt = $pdo->prepare("DELETE FROM manga_stranieri WHERE id = ?");
        $stmt->execute([$manga_id]);
        
        $pdo->commit();
        return ['success' => true, 'new_id' => $new_id];
    } catch(Exception $e) {
        $pdo->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Funzioni per aggiornare elementi
function updateSerie($id, $titolo, $immagine_url, $data_pubblicazione, $volumi_totali, $prezzo_medio, $stato = null, $da_prendere_subito = null, $categorie = null, $rarita = null) {
    global $pdo;
    try {
        $pdo->beginTransaction();
        
        $categorie_json = $categorie ? json_encode($categorie) : null;
        
        $stmt = $pdo->prepare("UPDATE serie_manga SET titolo = ?, immagine_url = ?, data_pubblicazione = ?, volumi_totali = ?, prezzo_medio = ?, stato = ?, da_prendere_subito = ?, categorie = ?, rarita = ? WHERE id = ?");
        $stmt->execute([$titolo, $immagine_url, $data_pubblicazione, $volumi_totali, $prezzo_medio, $stato, $da_prendere_subito, $categorie_json, $rarita, $id]);
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM volumi_posseduti WHERE serie_id = ?");
        $stmt->execute([$id]);
        $current_count = $stmt->fetch()['count'];
        
        if ($current_count != $volumi_totali) {
            $stmt = $pdo->prepare("DELETE FROM volumi_posseduti WHERE serie_id = ?");
            $stmt->execute([$id]);
            
            for ($i = 1; $i <= $volumi_totali; $i++) {
                $stmt = $pdo->prepare("INSERT INTO volumi_posseduti (serie_id, numero_volume, posseduto) VALUES (?, ?, ?)");
                $stmt->execute([$id, $i, 0]);
            }
            
            $stmt = $pdo->prepare("UPDATE serie_manga SET volumi_posseduti = 0 WHERE id = ?");
            $stmt->execute([$id]);
        }
        
        $pdo->commit();
        return true;
    } catch(PDOException $e) {
        $pdo->rollback();
        return false;
    }
}

function updateVariant($id, $titolo, $immagine_url, $data_rilascio, $costo_medio, $posseduto, $stato = null, $da_prendere_subito = null, $categorie = null) {
    global $pdo;
    try {
        $categorie_json = $categorie ? json_encode($categorie) : null;
        
        $stmt = $pdo->prepare("UPDATE variant_manga SET titolo = ?, immagine_url = ?, data_rilascio = ?, costo_medio = ?, posseduto = ?, stato = ?, da_prendere_subito = ?, categorie = ? WHERE id = ?");
        $stmt->execute([$titolo, $immagine_url, $data_rilascio, $costo_medio, $posseduto, $stato, $da_prendere_subito, $categorie_json, $id]);
        return true;
    } catch(PDOException $e) {
        return false;
    }
}

// Funzione per ottenere tutti gli elementi per la rimozione
function getAllItems() {
    global $pdo;
    $items = [];
    
    // Serie manga
    $stmt = $pdo->prepare("SELECT id, titolo as nome, 'serie' as tipo FROM serie_manga ORDER BY titolo");
    $stmt->execute();
    $items = array_merge($items, $stmt->fetchAll());
    
    // Variant
    $stmt = $pdo->prepare("SELECT id, titolo as nome, 'variant' as tipo FROM variant_manga ORDER BY titolo");
    $stmt->execute();
    $items = array_merge($items, $stmt->fetchAll());
    
    // Altre sezioni
    $tables = [
        'funko_pop' => 'Funko Pop',
        'monster' => 'Monster',
        'artbooks_anime' => 'Artbooks Anime',
        'gameboys' => 'Gameboys',
        'pokemon_game' => 'Pokemon Game',
        'numeri_yugioh' => 'Numeri Yu-Gi-Oh',
        'duel_masters' => 'Duel Masters',
        'libri_normali' => 'Libri Normali'
    ];
    
    foreach ($tables as $table => $tipo) {
        if ($table === 'libri_normali') {
            $stmt = $pdo->prepare("SELECT id, titolo as nome, '$table' as tipo FROM $table ORDER BY titolo");
        } else {
            $stmt = $pdo->prepare("SELECT id, nome, '$table' as tipo FROM $table ORDER BY nome");
        }
        $stmt->execute();
        $results = $stmt->fetchAll();
        foreach ($results as $result) {
            $result['tipo_display'] = $tipo;
            $items[] = $result;
        }
    }
    
    // Vinili e CD
    $stmt = $pdo->prepare("SELECT id, titolo as nome, 'vinili_cd' as tipo FROM vinili_cd ORDER BY titolo");
    $stmt->execute();
    $items = array_merge($items, $stmt->fetchAll());
    
    return $items;
}

// Funzione di ricerca globale
function searchItems($query) {
    global $pdo;
    $items = [];
    $searchTerm = "%$query%";
    
    // Cerca nelle serie manga
    $stmt = $pdo->prepare("SELECT id, titolo as nome, 'serie' as tipo, immagine_url, data_pubblicazione as data FROM serie_manga WHERE titolo LIKE ? ORDER BY titolo");
    $stmt->execute([$searchTerm]);
    $items = array_merge($items, $stmt->fetchAll());
    
    // Cerca nelle variant
    $stmt = $pdo->prepare("SELECT id, titolo as nome, 'variant' as tipo, immagine_url, data_rilascio as data FROM variant_manga WHERE titolo LIKE ? ORDER BY titolo");
    $stmt->execute([$searchTerm]);
    $items = array_merge($items, $stmt->fetchAll());
    
    // Cerca nelle nuove sezioni
    $tables = [
        'funko_pop' => 'funko_pop',
        'monster' => 'monster', 
        'artbooks_anime' => 'artbooks_anime',
        'gameboys' => 'gameboys',
        'pokemon_game' => 'pokemon_game',
        'numeri_yugioh' => 'numeri_yugioh',
        'duel_masters' => 'duel_masters'
    ];
    
    foreach ($tables as $table => $tipo) {
        if ($table === 'numeri_yugioh') {
            $stmt = $pdo->prepare("SELECT id, nome, '$tipo' as tipo, immagine_url, data_pubblicazione as data, codice FROM $table WHERE nome LIKE ? OR codice LIKE ? ORDER BY nome");
            $stmt->execute([$searchTerm, $searchTerm]);
        } else {
            $stmt = $pdo->prepare("SELECT id, nome, '$tipo' as tipo, immagine_url, data_pubblicazione as data FROM $table WHERE nome LIKE ? ORDER BY nome");
            $stmt->execute([$searchTerm]);
        }
        $items = array_merge($items, $stmt->fetchAll());
    }
    
    return $items;
}

// Funzione per ottenere una serie casuale per la ruota della fortuna
function getRandomSerieMancante() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT * FROM serie_manga WHERE volumi_posseduti = 0 ORDER BY RAND() LIMIT 1");
        $stmt->execute();
        return $stmt->fetch();
    } catch(PDOException $e) {
        return false;
    }
}

// Funzione per contare serie mancanti
function countSerieMancanti() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM serie_manga WHERE volumi_posseduti = 0");
    $stmt->execute();
    return $stmt->fetch()['count'];
}

// Funzione per contare numeri Yu-Gi-Oh mancanti
function countYugiohMancanti() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM numeri_yugioh WHERE posseduto = 0");
    $stmt->execute();
    return $stmt->fetch()['count'];
}

// Funzione generica per ottenere elementi da una tabella specifica
function getItemById($table, $id) {
    global $pdo;
    
    $allowedTables = [
        'funko_pop', 'monster', 'artbooks_anime', 'gameboys', 
        'pokemon_game', 'numeri_yugioh', 'duel_masters',
        'libri_normali', 'vinili_cd', 'libri_lovecraft', 'libri_giapponesi',
        'libreria_update'
    ];
    
    if (!in_array($table, $allowedTables)) {
        return false;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function updateRarita($serie_id, $rarita) {
    global $pdo;
    
    // Valida rarità (1-5 o NULL)
    if ($rarita !== null && ($rarita < 1 || $rarita > 5)) {
        return false;
    }
    
    $stmt = $pdo->prepare("UPDATE serie_manga SET rarita = ? WHERE id = ?");
    return $stmt->execute([$rarita, $serie_id]);
}

/**
 * Ottiene il nome descrittivo della rarità
 */
function getRaritaName($rarita) {
    $names = [
        1 => 'Comune',
        2 => 'Non Comune',
        3 => 'Raro',
        4 => 'Epico',
        5 => 'Leggendario'
    ];
    return $names[$rarita] ?? 'Nessuna';
}

/**
 * Ottiene il colore esadecimale della rarità
 */
function getRaritaColor($rarita) {
    $colors = [
        1 => '#27ae60',  // Verde
        2 => '#2ecc71',  // Verde chiaro
        3 => '#f1c40f',  // Giallo
        4 => '#e67e22',  // Arancione
        5 => '#e74c3c'   // Rosso
    ];
    return $colors[$rarita] ?? '#95a5a6';
}
?>