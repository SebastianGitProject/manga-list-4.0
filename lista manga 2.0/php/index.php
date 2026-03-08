<?php
require_once '../db/functions.php';

$message = '';
$messageType = '';

// Get filter and search parameters
$orderBy = $_GET['order'] ?? 'titolo';
$search = $_GET['search'] ?? '';
$daPrendere = isset($_GET['da_prendere']) ? ($_GET['da_prendere'] === '1' ? 1 : null) : null;
$tipoMedia = $_GET['tipo_media'] ?? ''; // Per vinili e CD
$statoFiltro = $_GET['stato'] ?? '';

// Gestione form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $tipo = sanitizeInput($_POST['tipo']);
                $manga_straniero = isset($_POST['manga_straniero']) ? true : false;
                
                // Gestione nome/titolo in base al tipo
                if ($tipo === 'serie' || $tipo === 'variant') {
                    $nome = sanitizeInput($_POST['titolo'] ?? $_POST['nome']);
                } else {
                    $nome = sanitizeInput($_POST['nome'] ?? $_POST['titolo']);
                }
                
                $immagine_url = sanitizeInput($_POST['immagine_url']);
                $data = sanitizeInput($_POST['data']);
                
                // Gestione prezzo in base al tipo
                if ($tipo === 'serie') {
                    $prezzo = (float)($_POST['prezzo_medio'] ?? 0);
                } else if ($tipo === 'variant') {
                    $prezzo = (float)($_POST['costo_medio'] ?? 0);
                } else {
                    $prezzo = (float)($_POST['prezzo'] ?? 0);
                }
                
                $posseduto = isset($_POST['posseduto']) ? 1 : 0;
                
                $success = false;
                
                switch ($tipo) {
                    case 'serie':
                        $volumi_totali = (int)($_POST['volumi_totali'] ?? 1);
                        $volumi_posseduti = (int)($_POST['volumi_posseduti'] ?? 0);
                        $stato = sanitizeInput($_POST['stato'] ?? 'completo');
                        $da_prendere_subito = isset($_POST['da_prendere_subito']) ? 1 : 0;
                        $rarita = isset($_POST['rarita']) && $_POST['rarita'] !== '' ? (int)$_POST['rarita'] : null;

                        // Gestione categorie
                        $categorie_input = sanitizeInput($_POST['categorie'] ?? '');
                        $categorie = !empty($categorie_input) ? array_map('trim', explode(',', $categorie_input)) : null;

                        error_log("Serie - Prezzo ricevuto: " . $prezzo);
                        error_log("POST prezzo_medio: " . ($_POST['prezzo_medio'] ?? 'NON PRESENTE'));

                        if ($manga_straniero) {
                            $success = addMangaStraniero($nome, $immagine_url, $data, $volumi_totali, $volumi_posseduti, $prezzo, $stato, $da_prendere_subito, $categorie, $rarita);
                        } else {
                            $success = addSerie($nome, $immagine_url, $data, $volumi_totali, $volumi_posseduti, $prezzo, $stato, $da_prendere_subito, $categorie, $rarita);
                        }
                        break;
                        
                    case 'variant':
                        $stato = sanitizeInput($_POST['stato'] ?? 'completo');
                        $da_prendere_subito = isset($_POST['da_prendere_subito']) ? 1 : 0;

                        $categorie_input = sanitizeInput($_POST['categorie'] ?? '');
                        $categorie = !empty($categorie_input) ? array_map('trim', explode(',', $categorie_input)) : null;

                        $success = addVariant($nome, $immagine_url, $data, $prezzo, $posseduto, $stato, $da_prendere_subito, $categorie);
                        break;
                        
                    case 'funko_pop':
                        $success = addFunkoPop($nome, $immagine_url, $data, $prezzo, $posseduto);
                        break;
                        
                    case 'monster':
                        $success = addMonster($nome, $immagine_url, $data, $prezzo, $posseduto);
                        break;
                        
                    case 'artbooks_anime':
                        $success = addArtbooksAnime($nome, $immagine_url, $data, $prezzo, $posseduto);
                        break;
                        
                    case 'gameboys':
                        $links = $_POST['links'] ?? [];
                        $links = array_filter($links);
                        $success = addGameboys($nome, $immagine_url, $data, $prezzo, $links, $posseduto);
                        break;
                        
                    case 'pokemon_game':
                        $links = $_POST['links'] ?? [];
                        $links = array_filter($links);
                        $success = addPokemonGame($nome, $immagine_url, $data, $prezzo, $links, $posseduto);
                        break;
                        
                    case 'numeri_yugioh':
                        $codice = sanitizeInput($_POST['codice']);
                        if (strlen($codice) !== 11) {
                            $message = 'Il codice deve essere di esattamente 11 cifre.';
                            $messageType = 'error';
                            break;
                        }
                        $success = addNumeriYugioh($nome, $codice, $immagine_url, $data, $prezzo, $posseduto);
                        break;
                        
                    case 'duel_masters':
                        $is_box = isset($_POST['is_box']) ? 1 : 0;
                        $success = addDuelMasters($nome, $immagine_url, $data, $prezzo, $is_box, $posseduto);
                        break;

                    case 'libro_normale':
                        $autore = sanitizeInput($_POST['autore']);
                        $success = addLibroNormale($nome, $immagine_url, $data, $prezzo, $autore, $posseduto);
                        break;
                        
                    case 'vinile_cd':
                        $tipo_media = sanitizeInput($_POST['tipo_media']);
                        $autore = sanitizeInput($_POST['autore']);
                        $costo = (float)($_POST['costo'] ?? 0);
                        $success = addVinileCD($tipo_media, $nome, $immagine_url, $data, $costo, $autore, $posseduto);
                        break;

                    case 'libreria_update':
                        // Gestione upload immagine
                        $immagine_url = '';
                        
                        if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === UPLOAD_ERR_OK) {
                            // Upload da PC
                            $upload_dir = '../uploads/';
                            if (!file_exists($upload_dir)) {
                                mkdir($upload_dir, 0755, true);
                            }
                            
                            $file = $_FILES['image_upload'];
                            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                            $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'webp');
                            
                            if (in_array($file_ext, $allowed_types) && $file['size'] <= 5 * 3024 * 4032) {
                                $new_filename = uniqid('libreria_', true) . '.' . $file_ext;
                                $upload_path = $upload_dir . $new_filename;
                                
                                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                                    $immagine_url = '../uploads/' . $new_filename;
                                } else {
                                    $message = 'Errore nel salvataggio dell\'immagine';
                                    $messageType = 'error';
                                    break;
                                }
                            } else {
                                $message = 'Tipo di file non consentito o file troppo grande (max 5MB)';
                                $messageType = 'error';
                                break;
                            }
                        } else {
                            $message = 'Nessuna immagine caricata';
                            $messageType = 'error';
                            break;
                        }
                        
                        $numero_manga = (int)($_POST['numero_manga'] ?? 0);
                        $note = sanitizeInput($_POST['note'] ?? '');
                        $success = addLibreriaUpdate($immagine_url, $data, $numero_manga, $note);
                        break;
                        
                    case 'libro_lovecraft':
                        $costo = (float)($_POST['costo'] ?? 0);
                        $success = addLibroLovecraft($nome, $immagine_url, $data, $costo, $posseduto);
                        break;
                        
                    case 'libro_giapponese':
                        $costo = (float)($_POST['costo'] ?? 0);
                        $autore = sanitizeInput($_POST['autore'] ?? '');
                        $success = addLibroGiapponese($nome, $immagine_url, $costo, $autore, $posseduto);
                        break;     
                }
                
                if ($success) {
                    $message = ucfirst(str_replace('_', ' ', $tipo)) . ' aggiunto con successo!';
                    $messageType = 'success';
                } else if (empty($message)) {
                    echo "<script>console.log(" . json_encode($message) . ");</script>";
                    echo "<script>console.log(" . json_encode($rarita) . ");</script>";
                    $message = 'Errore nell\'aggiunta. Il nome potrebbe già esistere.';
                    $messageType = 'error';
                }
                break;
                
            case 'remove':
                $id = (int)$_POST['id'];
                $tipo = sanitizeInput($_POST['tipo']);
                
                $success = false;
                if ($tipo === 'manga_straniero') {
                    $success = removeMangaStraniero($id);
                } elseif ($tipo === 'variant') {
                    $success = removeVariant($id);
                } else if ($tipo === 'serie') {
                    $success = removeSerie($id);
                } else {
                    $success = removeByTable($tipo, $id);
                }

                if ($success) {
                    $message = ucfirst(str_replace('_', ' ', $tipo)) . ' rimosso con successo!';
                    $messageType = 'success';
                } else {
                    $message = 'Errore nella rimozione.';
                    $messageType = 'error';
                }
                break;
        }
    }
}


// Recupero dati per le diverse sezioni
$serieCollezione = getSerieCollezione($orderBy, $search, '', $daPrendere, $statoFiltro);
$variantCollezione = getVariantCollezione($orderBy, $search, '', $daPrendere, $statoFiltro);
$serieMancanti = getSerieMancanti($orderBy, $search, '', $daPrendere, $statoFiltro);
$variantMancanti = getVariantMancanti($orderBy, $search, '', $daPrendere, $statoFiltro);
$mangaStranieriCollezione = getMangaStranieriCollezione($orderBy, $search, '', $daPrendere, $statoFiltro);
$mangaStranieriMancanti = getMangaStranieriMancanti($orderBy, $search, '', $daPrendere, $statoFiltro);
$libreriaUpdate = getLibreriaUpdate($orderBy, $search);
$libriLovecraft = getLibriLovecraft($orderBy, $search);
$libriGiapponesi = getLibriGiapponesi($orderBy, $search);

// Nuove sezioni
$funkoPop = getFunkoPop($orderBy, $search);
$monster = getMonster($orderBy, $search);
$artbooksAnime = getArtbooksAnime($orderBy, $search);
$gameboys = getGameboys($orderBy, $search);
$pokemonGame = getPokemonGame($orderBy, $search);
$numeriYugioh = getNumeriYugioh($orderBy, $search);
$duelMasters = getDuelMasters($orderBy, $search);
$libriNormali = getLibriNormali($orderBy, $search);
$viniliCD = getViniliCD($orderBy, $search, $tipoMedia);

$tuttiGliElementi = getAllItems();

// Contatori
$serieMancantiFIltered = getSerieMancanti('', $search);
$countSerieMancanti = count($serieMancantiFIltered);
$countYugiohMancanti = countYugiohMancanti();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Collezione Manga & More</title>
    <link rel="stylesheet" href="../css/styles.css?version=24">
    <link rel="stylesheet" href="../css/auth.css">
    <script src="../js/auth.js"></script>
    <style>
        /* Additional styles for new features */
        .spin-wheel-container {
            text-align: center;
            margin: 2rem 0;
        }
        
        .counter-badge {
            background-color: #e74c3c;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .wheel-container {
            position: relative;
            width: 200px;
            height: 200px;
            margin: 0 auto 2rem;
        }
        
        .wheel {
            width: 200px;
            height: 200px;
            border: 8px solid #3498db;
            border-radius: 50%;
            background: conic-gradient(
                #ff6b6b 0deg 60deg,
                #4ecdc4 60deg 120deg,
                #45b7d1 120deg 180deg,
                #f9ca24 180deg 240deg,
                #f0932b 240deg 300deg,
                #eb4d4b 300deg 360deg
            );
            transition: transform 3s cubic-bezier(0.25, 0.1, 0.25, 1);
            position: relative;
        }
        
        .wheel::after {
            content: '';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 10px solid transparent;
            border-right: 10px solid transparent;
            border-top: 20px solid #2c3e50;
        }
        
        .spin-result-card {
            text-align: center;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .spin-result-card img {
            border-radius: 4px;
        }
        
        .link-input-group {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .link-input-group input {
            flex: 1;
        }
        
        .card-links {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }
        
        .card-link {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.8rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            transition: background-color 0.3s ease;
        }
        
        .card-link:hover {
            background-color: #2980b9;
        }
        
        .card-code {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #bdc3c7;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
        .da-prendere-badge {
            background-color: #e74c3c;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            display: inline-block;
            margin-top: 0.5rem;
        }
        
        .stato-badge {
            background-color: #db9834ff;
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: bold;
            display: inline-block;
            margin-right: 0.5rem;
            margin-bottom: 0.6rem;
        }
        
        .stato-badge.in-corso {
            background-color: #db9834ff;
        }
        
        .stato-badge.completo {
            background-color: #db9834ff;
        }
        
        .stato-badge.interrotta {
            background-color: #db9834ff;
        }
        
        .categorie-tags {
            margin-top: 0.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 0.3rem;
        }
        
        .categoria-tag {
            background-color: #ecf0f1;
            color: #2c3e50;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.75rem;
            display: inline-block;
        }
        
        .categorie-input-container {
            margin-top: 0.5rem;
        }
        
        .categoria-input-info {
            font-size: 0.85rem;
            color: #7f8c8d;
            font-style: italic;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">📚 Collezione Manga & More</div>
                <nav>
                    <ul>
                        <li><a href="#collezione" class="active">Collezione Completa</a></li>
                        <li><a href="#serie-mancanti">Serie Mancanti</a></li>
                        <li><a href="#variant-mancanti">Variant Mancanti</a></li>
                        <li><a href="#manga-stranieri">Serie Straniere</a></li>
                        <li><a href="#funko-pop">Funko Pop</a></li>
                        <li><a href="#monster">Monster</a></li>
                        <li><a href="#artbooks-anime">Artbooks</a></li>
                        <li><a href="#gameboys">Gameboys AD-SP</a></li>
                        <li><a href="#pokemon-game">Games</a></li>
                        <li><a href="#numeri-yugioh">Yu-Gi-Oh</a></li>
                        <li><a href="#duel-masters">Duel Masters</a></li>
                        <li><a href="#libri-normali">Libri</a></li>
                        <li><a href="#vinili-cd">Vinili & CD</a></li>
                        <li><a href="#libreria-update">Libreria update</a></li>
                        <li><a href="#libri-giapponesi">libri jap</a></li>
                        <li><a href="#libri-lovecraft">lovecraft</a></li>
                        <li><a href="#aggiungi">Aggiungi</a></li>
                        <li><a href="#rimuovi">Rimuovi</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Sezione Collezione Completa -->
            <section id="collezione" class="section active">
                <h1 class="section-title">Collezione Completa</h1>
                
                <div class="search-filter-bar">
                    <input type="text" id="searchInput" class="search-input" placeholder="Cerca..." value="<?php echo htmlspecialchars($search); ?>">
                    
                    <select class="filter-select" onchange="filterWithHash('collezione', this.value)">
                        <option value="titolo" <?php echo $orderBy === 'titolo' ? 'selected' : ''; ?>>Alfabetico</option>
                        <option value="prezzo_asc" <?php echo $orderBy === 'prezzo_asc' ? 'selected' : ''; ?>>Prezzo ↑</option>
                        <option value="prezzo_desc" <?php echo $orderBy === 'prezzo_desc' ? 'selected' : ''; ?>>Prezzo ↓</option>
                        <option value="volumi_asc" <?php echo $orderBy === 'volumi_asc' ? 'selected' : ''; ?>>Volumi ↑</option>
                        <option value="volumi_desc" <?php echo $orderBy === 'volumi_desc' ? 'selected' : ''; ?>>Volumi ↓</option>
                        <option value="da_prendere" <?php echo $orderBy === 'da_prendere' ? 'selected' : ''; ?>>Da Prendere</option>
                        <option value="data_recente" <?php echo $orderBy === 'data_recente' ? 'selected' : ''; ?>>📅 Più Recenti</option>
                        <option value="data_vecchia" <?php echo $orderBy === 'data_vecchia' ? 'selected' : ''; ?>>📅 Meno Recenti</option>
                        <option value="rarita_alta" <?php echo $orderBy === 'rarita_alta' ? 'selected' : ''; ?>>⭐ Rarità Alta</option>
                        <option value="rarita_bassa" <?php echo $orderBy === 'rarita_bassa' ? 'selected' : ''; ?>>⭐ Rarità Bassa</option>
                    </select>

                    <select class="filter-select" onchange="filterStatoWithHash('collezione', this.value)">
                        <option value="">Tutti gli stati</option>
                        <option value="stato_completo" <?php echo $orderBy === 'stato_completo' ? 'selected' : ''; ?>>Completo</option>
                        <option value="stato_in_corso" <?php echo $orderBy === 'stato_in_corso' ? 'selected' : ''; ?>>In Corso</option>
                        <option value="stato_interrotta" <?php echo $orderBy === 'stato_interrotta' ? 'selected' : ''; ?>>Interrotta</option>
                    </select>
                </div>

                <div class="priority-button-container">
                    <button class="btn btn-priority" onclick="openPriorityModal('serie')">
                        🎯 Gestisci Priorità
                    </button>
                </div>
                
                <div class="cards-grid">
                    <?php 
                    $allCollezione = array_merge($serieCollezione, $variantCollezione);
                    foreach ($allCollezione as $item): 
                        $isCard = isset($item['titolo']);
                        $nome = $isCard ? $item['titolo'] : $item['nome'];
                        $data = $isCard ? ($item['data_pubblicazione'] ?? $item['data_rilascio']) : $item['data_pubblicazione'];
                    ?>
                        <div class="card" 
                            <?php if ($isCard): ?>
                                <?php if (isset($item['volumi_totali'])): ?>
                                    data-serie-id="<?php echo $item['id']; ?>"
                                <?php else: ?>
                                    data-variant-id="<?php echo $item['id']; ?>"
                                <?php endif; ?>
                            <?php endif; ?>>
                            
                            <img src="<?php echo htmlspecialchars($item['immagine_url']); ?>" 
                                alt="<?php echo htmlspecialchars($nome); ?>" 
                                class="card-image"
                                onerror="this.src='https://via.placeholder.com/250x300?text=No+Image'">
                            <div class="card-content">
                                <h3 class="card-title"><?php echo htmlspecialchars($nome); ?></h3>
                                <p class="card-date"><?php echo formatDate($data); ?></p>
                                
                                <?php if (isset($item['stato'])): ?>
                                    <span class="stato-badge <?php echo $item['stato']; ?>">
                                        <?php echo "Stato: " . ucfirst(str_replace('_', ' ', $item['stato'])); ?>
                                    </span>
                                <?php endif; ?>

                                <!-- Badge Rarità -->
                                <?php if (isset($item['rarita']) && $item['rarita'] > 0): ?>
                                    <span class="rarita-badge rarita-<?php echo $item['rarita']; ?>">
                                        <?php 
                                        for ($i = 0; $i < $item['rarita']; $i++) {
                                            echo '★';
                                        }
                                        ?>
                                    </span>
                                <?php endif; ?>
                                
                                <div class="card-progress">
                                    <?php if ($isCard): ?>
                                        <?php if (isset($item['volumi_totali'])): ?>
                                            <?php 
                                            $volumi_actual = $item['volumi_posseduti_actual'] ?? $item['volumi_posseduti'];
                                            if ($volumi_actual == $item['volumi_totali']): ?>
                                                <span class="complete-badge">Serie Completa</span>
                                            <?php else: ?>
                                                <span class="card-volumes">
                                                    Volumi: <?php echo $volumi_actual; ?>/<?php echo $item['volumi_totali']; ?> - 
                                                    Mancanti: <?php echo $item['volumi_totali'] - $volumi_actual; ?>
                                                </span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="complete-badge">Variant Posseduta</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (isset($item['da_prendere_subito']) && $item['da_prendere_subito']): ?>
                                    <span class="da-prendere-badge">🎯 DA PRENDERE SUBITO!</span>
                                <?php endif; ?>
                                
                                <?php if (isset($item['categorie']) && !empty($item['categorie'])): ?>
                                    <?php
                                    // Mostra categorie SOLO se ha volumi posseduti > 0 (per serie) o se è variant posseduta
                                    $mostraCategorie = false;
                                    if (isset($item['volumi_totali'])) {
                                        // È una serie - mostra solo se volumi_posseduti > 0
                                        $volumi_actual = $item['volumi_posseduti_actual'] ?? $item['volumi_posseduti'];
                                        $mostraCategorie = ($volumi_actual > 0);
                                    } else {
                                        // È una variant - è sempre posseduta in questa sezione
                                        $mostraCategorie = true;
                                    }
                                    
                                    if ($mostraCategorie):
                                    ?>
                                        <div class="categorie-tags">
                                            <?php 
                                            $categorie = json_decode($item['categorie'], true);
                                            if ($categorie):
                                                foreach ($categorie as $cat): ?>
                                                    <span class="categoria-tag"><?php echo htmlspecialchars($cat); ?></span>
                                                <?php endforeach;
                                            endif;
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php 
                                $prezzo = 0;
                                if ($isCard) {
                                    $prezzo = $item['prezzo_medio'] ?? $item['costo_medio'] ?? 0;
                                } else {
                                    $prezzo = $item['prezzo'] ?? 0;
                                }
                                if ($prezzo > 0): 
                                ?>
                                    <div class="card-price"><?php echo formatPrice($prezzo); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Sezione Serie Mancanti -->
            <section id="serie-mancanti" class="section">
                <h1 class="section-title">Serie Mancanti</h1>
                
                 <div class="spin-wheel-container">
                    <div class="counter-badge">Serie Mancanti: <?php echo $countSerieMancanti; ?></div>
                    <button class="btn" onclick="openSpinWheel()">🎯 Ruota della Fortuna</button>
                </div>
                
                <div class="search-filter-bar">
                    <input type="text" class="search-input" placeholder="Cerca..." value="<?php echo htmlspecialchars($search); ?>">
                    
                    <select class="filter-select" onchange="filterWithHash('serie-mancanti', this.value)">
                        <option value="titolo" <?php echo $orderBy === 'titolo' ? 'selected' : ''; ?>>Alfabetico</option>
                        <option value="prezzo_asc" <?php echo $orderBy === 'prezzo_asc' ? 'selected' : ''; ?>>Prezzo ↑</option>
                        <option value="prezzo_desc" <?php echo $orderBy === 'prezzo_desc' ? 'selected' : ''; ?>>Prezzo ↓</option>
                        <option value="volumi_asc" <?php echo $orderBy === 'volumi_asc' ? 'selected' : ''; ?>>Volumi ↑</option>
                        <option value="volumi_desc" <?php echo $orderBy === 'volumi_desc' ? 'selected' : ''; ?>>Volumi ↓</option>
                        <option value="da_prendere" <?php echo $orderBy === 'da_prendere' ? 'selected' : ''; ?>>Da Prendere</option>
                        <option value="data_recente" <?php echo $orderBy === 'data_recente' ? 'selected' : ''; ?>>📅 Più Recenti</option>
                        <option value="data_vecchia" <?php echo $orderBy === 'data_vecchia' ? 'selected' : ''; ?>>📅 Meno Recenti</option>
                        <option value="rarita_alta" <?php echo $orderBy === 'rarita_alta' ? 'selected' : ''; ?>>⭐ Rarità Alta</option>
                        <option value="rarita_bassa" <?php echo $orderBy === 'rarita_bassa' ? 'selected' : ''; ?>>⭐ Rarità Bassa</option>
                    </select>

                    <select class="filter-select" onchange="filterStatoWithHash('serie-mancanti', this.value)">
                        <option value="">Tutti gli stati</option>
                        <option value="stato_completo" <?php echo $orderBy === 'stato_completo' ? 'selected' : ''; ?>>Completo</option>
                        <option value="stato_in_corso" <?php echo $orderBy === 'stato_in_corso' ? 'selected' : ''; ?>>In Corso</option>
                        <option value="stato_interrotta" <?php echo $orderBy === 'stato_interrotta' ? 'selected' : ''; ?>>Interrotta</option>
                    </select>
                </div>

                <div class="priority-button-container">
                    <button class="btn btn-priority" onclick="openPriorityModal('serie')">
                        🎯 Gestisci Priorità
                    </button>
                </div>
                
                <div class="cards-grid">
                    <?php foreach ($serieMancanti as $serie): ?>
                        <div class="card missing" data-serie-id="<?php echo $serie['id']; ?>">
                            <img src="<?php echo htmlspecialchars($serie['immagine_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($serie['titolo']); ?>" 
                                 class="card-image"
                                 onerror="this.src='https://via.placeholder.com/250x300?text=No+Image'">
                            <div class="card-content">
                                <h3 class="card-title"><?php echo htmlspecialchars($serie['titolo']); ?></h3>
                                <p class="card-date"><?php echo formatDate($serie['data_pubblicazione']); ?></p>
                                
                                <?php if (isset($serie['stato'])): ?>
                                    <span class="stato-badge <?php echo $serie['stato']; ?>">
                                        <?php echo "Stato: " . ucfirst(str_replace('_', ' ', $serie['stato'])); ?>
                                    </span>
                                <?php endif; ?>

                                <!-- Badge Rarità -->
                                <?php if (isset($serie['rarita']) && $serie['rarita'] > 0): ?>
                                    <span class="rarita-badge rarita-<?php echo $serie['rarita']; ?>">
                                        <?php 
                                        for ($i = 0; $i < $serie['rarita']; $i++) {
                                            echo '★';
                                        }
                                        ?>
                                    </span>
                                <?php endif; ?>
                                
                                <div class="card-progress">
                                    <span class="card-volumes">
                                        Volumi totali: <?php echo $serie['volumi_totali']; ?> - Nessun volume posseduto
                                    </span>
                                </div>
                                
                                <?php if ($serie['da_prendere_subito']): ?>
                                    <span class="da-prendere-badge">🎯 DA PRENDERE SUBITO!</span>
                                <?php endif; ?>
                                
                                <?php if (!empty($serie['categorie'])): ?>
                                    <?php
                                    // Mostra categorie SOLO se volumi_posseduti = 0
                                    if ($serie['volumi_posseduti'] == 0):
                                    ?>
                                        <div class="categorie-tags">
                                            <?php 
                                            $categorie = json_decode($serie['categorie'], true);
                                            if ($categorie):
                                                foreach ($categorie as $cat): ?>
                                                    <span class="categoria-tag"><?php echo htmlspecialchars($cat); ?></span>
                                                <?php endforeach;
                                            endif;
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php if ($serie['prezzo_medio'] > 0): ?>
                                    <div class="card-price"><?php echo formatPrice($serie['prezzo_medio']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Sezione Variant Mancanti -->
            <section id="variant-mancanti" class="section">
                <h1 class="section-title">Variant Mancanti</h1>
                
                <div class="search-filter-bar">
                    <input type="text" class="search-input" placeholder="Cerca..." value="<?php echo htmlspecialchars($search); ?>">
                    
                    <select class="filter-select" onchange="filterWithHash('variant-mancanti', this.value)">
                        <option value="titolo" <?php echo $orderBy === 'titolo' ? 'selected' : ''; ?>>Alfabetico</option>
                        <option value="prezzo_asc" <?php echo $orderBy === 'prezzo_asc' ? 'selected' : ''; ?>>Prezzo ↑</option>
                        <option value="prezzo_desc" <?php echo $orderBy === 'prezzo_desc' ? 'selected' : ''; ?>>Prezzo ↓</option>
                        <option value="da_prendere" <?php echo $orderBy === 'da_prendere' ? 'selected' : ''; ?>>Da Prendere</option>
                    </select>

                    <select class="filter-select" onchange="filterStatoWithHash('variant-mancanti', this.value)">
                        <option value="">Tutti gli stati</option>
                        <option value="stato_completo" <?php echo $orderBy === 'stato_completo' ? 'selected' : ''; ?>>Completo</option>
                        <option value="stato_in_corso" <?php echo $orderBy === 'stato_in_corso' ? 'selected' : ''; ?>>In Corso</option>
                        <option value="stato_interrotta" <?php echo $orderBy === 'stato_interrotta' ? 'selected' : ''; ?>>Interrotta</option>
                    </select>
                </div>

                <div class="priority-button-container">
                    <button class="btn btn-priority" onclick="openPriorityModal('variant')">
                        🎯 Gestisci Priorità
                    </button>
                </div>
                
                <div class="cards-grid">
                <?php foreach ($variantMancanti as $variant): ?>
                    <div class="card missing" data-variant-id="<?php echo $variant['id']; ?>">
                        <img src="<?php echo htmlspecialchars($variant['immagine_url']); ?>" 
                            alt="<?php echo htmlspecialchars($variant['titolo']); ?>" 
                            class="card-image"
                            onerror="this.src='https://via.placeholder.com/250x300?text=No+Image'">
                        <div class="card-content">
                            <h3 class="card-title"><?php echo htmlspecialchars($variant['titolo']); ?></h3>
                            <p class="card-date"><?php echo formatDate($variant['data_rilascio']); ?></p>
                            
                            <?php if (isset($variant['stato'])): ?>
                                <span class="stato-badge <?php echo $variant['stato']; ?>">
                                    <?php echo "Stato: " . ucfirst(str_replace('_', ' ', $variant['stato'])); ?>
                                </span>
                            <?php endif; ?>
                            
                            <div class="card-progress">
                                <span class="card-volumes">Variant non posseduta</span>
                            </div>
                            
                            <?php if ($variant['da_prendere_subito']): ?>
                                <span class="da-prendere-badge">🎯 DA PRENDERE SUBITO!</span>
                            <?php endif; ?>
                            
                            <?php if (!empty($variant['categorie'])): ?>
                                <?php
                                // Mostra categorie SOLO se posseduto = 0
                                if ($variant['posseduto'] == 0):
                                ?>
                                    <div class="categorie-tags">
                                        <?php 
                                        $categorie = json_decode($variant['categorie'], true);
                                        if ($categorie):
                                            foreach ($categorie as $cat): ?>
                                                <span class="categoria-tag"><?php echo htmlspecialchars($cat); ?></span>
                                            <?php endforeach;
                                        endif;
                                        ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <div class="card-price"><?php echo formatPrice($variant['costo_medio']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>




        <section id="manga-stranieri" class="section">
            <h1 class="section-title">Serie Straniere</h1>
            
            <div class="search-filter-bar">
                <input type="text" class="search-input" placeholder="Cerca..." value="<?php echo htmlspecialchars($search); ?>">
                
                <select class="filter-select" onchange="filterWithHash('manga-stranieri', this.value)">
                    <option value="titolo" <?php echo $orderBy === 'titolo' ? 'selected' : ''; ?>>Alfabetico</option>
                    <option value="prezzo_asc" <?php echo $orderBy === 'prezzo_asc' ? 'selected' : ''; ?>>Prezzo ↑</option>
                    <option value="prezzo_desc" <?php echo $orderBy === 'prezzo_desc' ? 'selected' : ''; ?>>Prezzo ↓</option>
                    <option value="volumi_asc" <?php echo $orderBy === 'volumi_asc' ? 'selected' : ''; ?>>Volumi ↑</option>
                    <option value="volumi_desc" <?php echo $orderBy === 'volumi_desc' ? 'selected' : ''; ?>>Volumi ↓</option>
                    <option value="da_prendere" <?php echo $orderBy === 'da_prendere' ? 'selected' : ''; ?>>Da Prendere</option>
                </select>
                
                <select class="filter-select" onchange="filterStatoWithHash('manga-stranieri', this.value)">
                    <option value="">Tutti gli stati</option>
                    <option value="stato_completo" <?php echo $orderBy === 'stato_completo' ? 'selected' : ''; ?>>Completo</option>
                    <option value="stato_in_corso" <?php echo $orderBy === 'stato_in_corso' ? 'selected' : ''; ?>>In Corso</option>
                    <option value="stato_interrotta" <?php echo $orderBy === 'stato_interrotta' ? 'selected' : ''; ?>>Interrotta</option>
                </select>

            </div>

            <div class="priority-button-container">
                <button class="btn btn-priority" onclick="openPriorityModal('manga_straniero')">
                    🎯 Gestisci Priorità
                </button>
            </div>
            
            <div class="cards-grid">
                <?php 
                $allMangaStranieri = array_merge($mangaStranieriCollezione, $mangaStranieriMancanti);
                foreach ($allMangaStranieri as $manga): 
                    $volumi_actual = $manga['volumi_posseduti_actual'] ?? $manga['volumi_posseduti'];
                ?>
                    <div class="card <?php echo $volumi_actual == 0 ? 'missing' : ''; ?>" 
                        data-manga-straniero-id="<?php echo $manga['id']; ?>">
                        
                        <img src="<?php echo htmlspecialchars($manga['immagine_url']); ?>" 
                            alt="<?php echo htmlspecialchars($manga['titolo']); ?>" 
                            class="card-image"
                            onerror="this.src='https://via.placeholder.com/250x300?text=No+Image'">
                        
                        <?php if ($manga['priorita']): ?>
                            <div class="priority-badge">
                                🎀 <?php echo $manga['priorita']; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-content">
                            <h3 class="card-title"><?php echo htmlspecialchars($manga['titolo']); ?></h3>
                            <p class="card-date"><?php echo formatDate($manga['data_pubblicazione']); ?></p>
                            
                            <?php if (isset($manga['stato'])): ?>
                                <span class="stato-badge <?php echo $manga['stato']; ?>">
                                    <?php echo "Stato: " . ucfirst(str_replace('_', ' ', $manga['stato'])); ?>
                                </span>
                            <?php endif; ?>

                            <?php if (isset($manga['rarita']) && $manga['rarita'] > 0): ?>
                                    <span class="rarita-badge rarita-<?php echo $manga['rarita']; ?>">
                                        <?php 
                                        for ($i = 0; $i < $manga['rarita']; $i++) {
                                            echo '★';
                                        }
                                        ?>
                                    </span>
                                <?php endif; ?>
                            
                            <div class="card-progress">
                                <?php if ($volumi_actual == $manga['volumi_totali']): ?>
                                    <span class="complete-badge">Serie Completa</span>
                                <?php elseif ($volumi_actual > 0): ?>
                                    <span class="card-volumes">
                                        Volumi: <?php echo $volumi_actual; ?>/<?php echo $manga['volumi_totali']; ?> - 
                                        Mancanti: <?php echo $manga['volumi_totali'] - $volumi_actual; ?>
                                    </span>
                                <?php else: ?>
                                    <span class="card-volumes">
                                        Volumi totali: <?php echo $manga['volumi_totali']; ?> - Nessun volume posseduto
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($manga['da_prendere_subito']): ?>
                                <span class="da-prendere-badge">🎯 DA PRENDERE SUBITO!</span>
                            <?php endif; ?>
                            
                            <?php if (!empty($manga['categorie'])): ?>
                                <div class="categorie-tags">
                                    <?php 
                                    $categorie = json_decode($manga['categorie'], true);
                                    if ($categorie):
                                        foreach ($categorie as $cat): ?>
                                            <span class="categoria-tag"><?php echo htmlspecialchars($cat); ?></span>
                                        <?php endforeach;
                                    endif;
                                    ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($manga['prezzo_medio'] > 0): ?>
                                <div class="card-price"><?php echo formatPrice($manga['prezzo_medio']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>                                    

        <section id="libreria-update" class="section">
            <h1 class="section-title">Libreria Update</h1>
            
            <div class="search-filter-bar">
                <input type="text" class="search-input" placeholder="Cerca nelle note..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="cards-grid">
                <?php foreach ($libreriaUpdate as $item): ?>
                    <div class="card" data-item-type="libreria_update" data-item-id="<?php echo $item['id']; ?>" style="cursor: pointer;">
                        <img src="<?php echo htmlspecialchars($item['immagine_url']); ?>" 
                            alt="Libreria Update" 
                            class="card-image"
                            onerror="this.src='https://via.placeholder.com/250x300?text=No+Image'">
                        <div class="card-content">
                            <h3 class="card-title">Aggiornamento del <?php echo formatDate($item['data_aggiunta']); ?></h3>
                            <p class="card-volumes">Numero Manga: <?php echo $item['numero_manga']; ?></p>
                            <?php if (!empty($item['note'])): ?>
                                <p class="card-notes"><?php echo htmlspecialchars($item['note']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>                            

        <section id="libri-lovecraft" class="section">
            <h1 class="section-title">Libri di H.P. Lovecraft</h1>
            
            <div class="search-filter-bar">
                <input type="text" class="search-input" placeholder="Cerca libri Lovecraft..." value="<?php echo htmlspecialchars($search); ?>">
                
                <select class="filter-select" onchange="filterWithHash('libri-lovecraft', this.value)">
                    <option value="titolo" <?php echo $orderBy === 'titolo' ? 'selected' : ''; ?>>Alfabetico</option>
                    <option value="costo_asc" <?php echo $orderBy === 'costo_asc' ? 'selected' : ''; ?>>Costo ↑</option>
                    <option value="costo_desc" <?php echo $orderBy === 'costo_desc' ? 'selected' : ''; ?>>Costo ↓</option>
                </select>
            </div>
            
            <div class="cards-grid">
                <?php foreach ($libriLovecraft as $libro): ?>
                    <div class="card <?php echo $libro['posseduto'] ? '' : 'missing'; ?>" 
                        data-item-type="libri_lovecraft" 
                        data-item-id="<?php echo $libro['id']; ?>">
                        
                        <img src="<?php echo htmlspecialchars($libro['immagine_url']); ?>" 
                            alt="<?php echo htmlspecialchars($libro['titolo']); ?>" 
                            class="card-image"
                            onerror="this.src='https://via.placeholder.com/250x300?text=No+Image'">
                        <div class="card-content">
                            <h3 class="card-title"><?php echo htmlspecialchars($libro['titolo']); ?></h3>
                            <p class="card-date"><?php echo formatDate($libro['data_pubblicazione']); ?></p>
                            
                            <div class="card-progress">
                                <?php if ($libro['posseduto']): ?>
                                    <span class="complete-badge">Posseduto</span>
                                <?php else: ?>
                                    <span class="card-volumes">Non posseduto</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($libro['costo'] > 0): ?>
                                <div class="card-price"><?php echo formatPrice($libro['costo']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>                            

        <section id="libri-giapponesi" class="section">
            <h1 class="section-title">Libri Giapponesi</h1>
            
            <div class="search-filter-bar">
                <input type="text" class="search-input" placeholder="Cerca libri giapponesi..." value="<?php echo htmlspecialchars($search); ?>">
                
                <select class="filter-select" onchange="filterWithHash('libri-giapponesi', this.value)">
                    <option value="titolo" <?php echo $orderBy === 'titolo' ? 'selected' : ''; ?>>Alfabetico</option>
                    <option value="costo_asc" <?php echo $orderBy === 'costo_asc' ? 'selected' : ''; ?>>Costo ↑</option>
                    <option value="costo_desc" <?php echo $orderBy === 'costo_desc' ? 'selected' : ''; ?>>Costo ↓</option>
                    <option value="autore" <?php echo $orderBy === 'autore' ? 'selected' : ''; ?>>Autore</option>
                </select>
            </div>
            
            <div class="cards-grid">
                <?php foreach ($libriGiapponesi as $libro): ?>
                    <div class="card <?php echo $libro['posseduto'] ? '' : 'missing'; ?>" 
                        data-item-type="libri_giapponesi" 
                        data-item-id="<?php echo $libro['id']; ?>">
                        
                        <img src="<?php echo htmlspecialchars($libro['immagine_url']); ?>" 
                            alt="<?php echo htmlspecialchars($libro['titolo']); ?>" 
                            class="card-image"
                            onerror="this.src='https://via.placeholder.com/250x300?text=No+Image'">
                        <div class="card-content">
                            <h3 class="card-title"><?php echo htmlspecialchars($libro['titolo']); ?></h3>
                            
                            <?php if (!empty($libro['autore'])): ?>
                                <p class="card-author">
                                    <strong>Autore:</strong> <?php echo htmlspecialchars($libro['autore']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <div class="card-progress">
                                <?php if ($libro['posseduto']): ?>
                                    <span class="complete-badge">Posseduto</span>
                                <?php else: ?>
                                    <span class="card-volumes">Non posseduto</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($libro['costo'] > 0): ?>
                                <div class="card-price"><?php echo formatPrice($libro['costo']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>                            

        <!-- Sezione Libri Normali -->
        <section id="libri-normali" class="section">
            <h1 class="section-title">Libri Normali</h1>
            
            <div class="search-filter-bar">
                <input type="text" class="search-input" placeholder="Cerca libri..." value="<?php echo htmlspecialchars($search); ?>">
                
                <select class="filter-select" onchange="filterWithHash('libri-normali', this.value)">
                    <option value="titolo" <?php echo $orderBy === 'titolo' ? 'selected' : ''; ?>>Alfabetico</option>
                    <option value="prezzo_asc" <?php echo $orderBy === 'prezzo_asc' ? 'selected' : ''; ?>>Prezzo ↑</option>
                    <option value="prezzo_desc" <?php echo $orderBy === 'prezzo_desc' ? 'selected' : ''; ?>>Prezzo ↓</option>
                    <option value="autore" <?php echo $orderBy === 'autore' ? 'selected' : ''; ?>>Autore</option>
                </select>
            </div>
            
            <div class="cards-grid">
                <?php foreach ($libriNormali as $libro): ?>
                    <!-- IMPORTANTE: Aggiungere data-item-type e data-item-id -->
                    <div class="card <?php echo $libro['posseduto'] ? '' : 'missing'; ?>" 
                        data-item-type="libri_normali" 
                        data-item-id="<?php echo $libro['id']; ?>">
                        
                        <img src="<?php echo htmlspecialchars($libro['immagine_url']); ?>" 
                            alt="<?php echo htmlspecialchars($libro['titolo']); ?>" 
                            class="card-image"
                            onerror="this.src='https://via.placeholder.com/250x300?text=No+Image'">
                        <div class="card-content">
                            <h3 class="card-title"><?php echo htmlspecialchars($libro['titolo']); ?></h3>
                            
                            <?php if (!empty($libro['autore'])): ?>
                                <p class="card-author">
                                    <strong>Autore:</strong> <?php echo htmlspecialchars($libro['autore']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <p class="card-date"><?php echo formatDate($libro['data_pubblicazione']); ?></p>
                            
                            <div class="card-progress">
                                <?php if ($libro['posseduto']): ?>
                                    <span class="complete-badge">Posseduto</span>
                                <?php else: ?>
                                    <span class="card-volumes">Non posseduto</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($libro['prezzo'] > 0): ?>
                                <div class="card-price"><?php echo formatPrice($libro['prezzo']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="funko-pop" class="section">
            <h1 class="section-title">Funko Pop</h1>
            
            <div class="search-filter-bar">
                <input type="text" class="search-input" placeholder="Cerca..." value="<?php echo htmlspecialchars($search); ?>">
                
                <select class="filter-select" onchange="filterWithHash('funko-pop', this.value)">
                    <option value="nome" <?php echo $orderBy === 'nome' ? 'selected' : ''; ?>>Alfabetico</option>
                    <option value="prezzo_asc" <?php echo $orderBy === 'prezzo_asc' ? 'selected' : ''; ?>>Prezzo ↑</option>
                    <option value="prezzo_desc" <?php echo $orderBy === 'prezzo_desc' ? 'selected' : ''; ?>>Prezzo ↓</option>
                </select>
            </div>
            
            <div class="cards-grid">
                <?php foreach ($funkoPop as $item): ?>
                    <!-- IMPORTANTE: data-item-type DEVE essere "funko_pop" (con underscore) -->
                    <div class="card <?php echo $item['posseduto'] ? '' : 'missing'; ?>" 
                        data-item-type="funko_pop" 
                        data-item-id="<?php echo $item['id']; ?>">
                        
                        <img src="<?php echo htmlspecialchars($item['immagine_url']); ?>" 
                            alt="<?php echo htmlspecialchars($item['nome']); ?>" 
                            class="card-image"
                            onerror="this.src='https://via.placeholder.com/250x300?text=No+Image'">
                        <div class="card-content">
                            <h3 class="card-title"><?php echo htmlspecialchars($item['nome']); ?></h3>
                            <p class="card-date"><?php echo formatDate($item['data_pubblicazione']); ?></p>
                            
                            <div class="card-progress">
                                <?php if ($item['posseduto']): ?>
                                    <span class="complete-badge">Posseduto</span>
                                <?php else: ?>
                                    <span class="card-volumes">Non posseduto</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($item['prezzo'] > 0): ?>
                                <div class="card-price"><?php echo formatPrice($item['prezzo']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="monster" class="section">
            <h1 class="section-title">Monster Energy</h1>
            
            <div class="search-filter-bar">
                <input type="text" class="search-input" placeholder="Cerca..." value="<?php echo htmlspecialchars($search); ?>">
                
                <select class="filter-select" onchange="filterWithHash('monster', this.value)">
                    <option value="nome" <?php echo $orderBy === 'nome' ? 'selected' : ''; ?>>Alfabetico</option>
                    <option value="prezzo_asc" <?php echo $orderBy === 'prezzo_asc' ? 'selected' : ''; ?>>Prezzo ↑</option>
                    <option value="prezzo_desc" <?php echo $orderBy === 'prezzo_desc' ? 'selected' : ''; ?>>Prezzo ↓</option>
                </select>
            </div>
            
            <div class="cards-grid">
                <?php foreach ($monster as $item): ?>
                    <div class="card <?php echo $item['posseduto'] ? '' : 'missing'; ?>" 
                        data-item-type="monster" 
                        data-item-id="<?php echo $item['id']; ?>">
                        
                        <img src="<?php echo htmlspecialchars($item['immagine_url']); ?>" 
                            alt="<?php echo htmlspecialchars($item['nome']); ?>" 
                            class="card-image"
                            onerror="this.src='https://via.placeholder.com/250x300?text=No+Image'">
                        <div class="card-content">
                            <h3 class="card-title"><?php echo htmlspecialchars($item['nome']); ?></h3>
                            <p class="card-date"><?php echo formatDate($item['data_pubblicazione']); ?></p>
                            
                            <div class="card-progress">
                                <?php if ($item['posseduto']): ?>
                                    <span class="complete-badge">Posseduto</span>
                                <?php else: ?>
                                    <span class="card-volumes">Non posseduto</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($item['prezzo'] > 0): ?>
                                <div class="card-price"><?php echo formatPrice($item['prezzo']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="artbooks-anime" class="section">
            <h1 class="section-title">Artbooks Anime</h1>
            
            <div class="search-filter-bar">
                <input type="text" class="search-input" placeholder="Cerca..." value="<?php echo htmlspecialchars($search); ?>">
                
                <select class="filter-select" onchange="filterWithHash('artbooks-anime', this.value)">
                    <option value="nome" <?php echo $orderBy === 'nome' ? 'selected' : ''; ?>>Alfabetico</option>
                    <option value="prezzo_asc" <?php echo $orderBy === 'prezzo_asc' ? 'selected' : ''; ?>>Prezzo ↑</option>
                    <option value="prezzo_desc" <?php echo $orderBy === 'prezzo_desc' ? 'selected' : ''; ?>>Prezzo ↓</option>
                </select>
            </div>
            
            <div class="cards-grid">
                <?php foreach ($artbooksAnime as $item): ?>
                    <div class="card <?php echo $item['posseduto'] ? '' : 'missing'; ?>" 
                        data-item-type="artbooks_anime" 
                        data-item-id="<?php echo $item['id']; ?>">
                        
                        <img src="<?php echo htmlspecialchars($item['immagine_url']); ?>" 
                            alt="<?php echo htmlspecialchars($item['nome']); ?>" 
                            class="card-image"
                            onerror="this.src='https://via.placeholder.com/250x300?text=No+Image'">
                        <div class="card-content">
                            <h3 class="card-title"><?php echo htmlspecialchars($item['nome']); ?></h3>
                            <p class="card-date"><?php echo formatDate($item['data_pubblicazione']); ?></p>
                            
                            <div class="card-progress">
                                <?php if ($item['posseduto']): ?>
                                    <span class="complete-badge">Posseduto</span>
                                <?php else: ?>
                                    <span class="card-volumes">Non posseduto</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($item['prezzo'] > 0): ?>
                                <div class="card-price"><?php echo formatPrice($item['prezzo']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="gameboys" class="section">
            <h1 class="section-title">Gameboys AD-SP</h1>
            
            <div class="search-filter-bar">
                <input type="text" class="search-input" placeholder="Cerca..." value="<?php echo htmlspecialchars($search); ?>">
                
                <select class="filter-select" onchange="filterWithHash('gameboys', this.value)">
                    <option value="nome" <?php echo $orderBy === 'nome' ? 'selected' : ''; ?>>Alfabetico</option>
                    <option value="prezzo_asc" <?php echo $orderBy === 'prezzo_asc' ? 'selected' : ''; ?>>Prezzo ↑</option>
                    <option value="prezzo_desc" <?php echo $orderBy === 'prezzo_desc' ? 'selected' : ''; ?>>Prezzo ↓</option>
                </select>
            </div>
            
            <div class="cards-grid">
                <?php foreach ($gameboys as $item): ?>
                    <div class="card <?php echo $item['posseduto'] ? '' : 'missing'; ?>" 
                        data-item-type="gameboys" 
                        data-item-id="<?php echo $item['id']; ?>">
                        
                        <img src="<?php echo htmlspecialchars($item['immagine_url']); ?>" 
                            alt="<?php echo htmlspecialchars($item['nome']); ?>" 
                            class="card-image"
                            onerror="this.src='https://via.placeholder.com/250x300?text=No+Image'">
                        <div class="card-content">
                            <h3 class="card-title"><?php echo htmlspecialchars($item['nome']); ?></h3>
                            <p class="card-date"><?php echo formatDate($item['data_pubblicazione']); ?></p>
                            
                            <div class="card-progress">
                                <?php if ($item['posseduto']): ?>
                                    <span class="complete-badge">Posseduto</span>
                                <?php else: ?>
                                    <span class="card-volumes">Non posseduto</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($item['prezzo'] > 0): ?>
                                <div class="card-price"><?php echo formatPrice($item['prezzo']); ?></div>
                            <?php endif; ?>
                            
                            <?php if (!empty($item['links'])): ?>
                                <?php $links = json_decode($item['links'], true); ?>
                                <?php if ($links && count($links) > 0): ?>
                                    <div class="card-links">
                                        <?php foreach ($links as $index => $link): ?>
                                            <a href="<?php echo htmlspecialchars($link); ?>" target="_blank" class="card-link">
                                                Link <?php echo $index + 1; ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="pokemon-game" class="section">
            <h1 class="section-title">Pokemon - Mario Party - Final Fantasy - Fire Emblem - Resident Evil</h1>
            
            <div class="search-filter-bar">
                <input type="text" class="search-input" placeholder="Cerca..." value="<?php echo htmlspecialchars($search); ?>">
                
                <select class="filter-select" onchange="filterWithHash('pokemon-game', this.value)">
                    <option value="nome" <?php echo $orderBy === 'nome' ? 'selected' : ''; ?>>Alfabetico</option>
                    <option value="prezzo_asc" <?php echo $orderBy === 'prezzo_asc' ? 'selected' : ''; ?>>Prezzo ↑</option>
                    <option value="prezzo_desc" <?php echo $orderBy === 'prezzo_desc' ? 'selected' : ''; ?>>Prezzo ↓</option>
                </select>
            </div>
            
            <div class="cards-grid">
                <?php foreach ($pokemonGame as $item): ?>
                    <div class="card <?php echo $item['posseduto'] ? '' : 'missing'; ?>" 
                        data-item-type="pokemon_game" 
                        data-item-id="<?php echo $item['id']; ?>">
                        
                        <img src="<?php echo htmlspecialchars($item['immagine_url']); ?>" 
                            alt="<?php echo htmlspecialchars($item['nome']); ?>" 
                            class="card-image"
                            onerror="this.src='https://via.placeholder.com/250x300?text=No+Image'">
                        <div class="card-content">
                            <h3 class="card-title"><?php echo htmlspecialchars($item['nome']); ?></h3>
                            <p class="card-date"><?php echo formatDate($item['data_pubblicazione']); ?></p>
                            
                            <div class="card-progress">
                                <?php if ($item['posseduto']): ?>
                                    <span class="complete-badge">Posseduto</span>
                                <?php else: ?>
                                    <span class="card-volumes">Non posseduto</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($item['prezzo'] > 0): ?>
                                <div class="card-price"><?php echo formatPrice($item['prezzo']); ?></div>
                            <?php endif; ?>
                            
                            <?php if (!empty($item['links'])): ?>
                                <?php $links = json_decode($item['links'], true); ?>
                                <?php if ($links && count($links) > 0): ?>
                                    <div class="card-links">
                                        <?php foreach ($links as $index => $link): ?>
                                            <a href="<?php echo htmlspecialchars($link); ?>" target="_blank" class="card-link">
                                                Link <?php echo $index + 1; ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="numeri-yugioh" class="section">
            <h1 class="section-title">Numeri Yu-Gi-Oh</h1>

            <div class="spin-wheel-container">
                    <div class="counter-badge">Numeri Mancanti: <?php echo $countYugiohMancanti; ?></div>
                    <!--<button class="btn" onclick="openSpinWheel()">🎯 Ruota della Fortuna</button>-->
            </div>
            
            <div class="search-filter-bar">
                <input type="text" class="search-input" placeholder="Cerca..." value="<?php echo htmlspecialchars($search); ?>">
                
                <select class="filter-select" onchange="filterWithHash('numeri-yugioh', this.value)">
                    <option value="nome" <?php echo $orderBy === 'nome' ? 'selected' : ''; ?>>Alfabetico</option>
                    <option value="prezzo_asc" <?php echo $orderBy === 'prezzo_asc' ? 'selected' : ''; ?>>Prezzo ↑</option>
                    <option value="prezzo_desc" <?php echo $orderBy === 'prezzo_desc' ? 'selected' : ''; ?>>Prezzo ↓</option>
                </select>
            </div>
            
            <div class="cards-grid">
                <?php foreach ($numeriYugioh as $item): ?>
                    <div class="card <?php echo $item['posseduto'] ? '' : 'missing'; ?>" 
                        data-item-type="numeri_yugioh" 
                        data-item-id="<?php echo $item['id']; ?>">
                        
                        <img src="<?php echo htmlspecialchars($item['immagine_url']); ?>" 
                            alt="<?php echo htmlspecialchars($item['nome']); ?>" 
                            class="card-image"
                            onerror="this.src='https://via.placeholder.com/250x300?text=No+Image'">
                        <div class="card-content">
                            <h3 class="card-title"><?php echo htmlspecialchars($item['nome']); ?></h3>
                            
                            <?php if (!empty($item['codice'])): ?>
                                <p class="card-code"><strong>Codice:</strong> <?php echo htmlspecialchars($item['codice']); ?></p>
                            <?php endif; ?>
                            
                            <p class="card-date"><?php echo formatDate($item['data_pubblicazione']); ?></p>
                            
                            <div class="card-progress">
                                <?php if ($item['posseduto']): ?>
                                    <span class="complete-badge">Posseduto</span>
                                <?php else: ?>
                                    <span class="card-volumes">Non posseduto</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($item['prezzo'] > 0): ?>
                                <div class="card-price"><?php echo formatPrice($item['prezzo']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section id="duel-masters" class="section">
            <h1 class="section-title">Duel Masters</h1>
            
            <div class="search-filter-bar">
                <input type="text" class="search-input" placeholder="Cerca..." value="<?php echo htmlspecialchars($search); ?>">
                
                <select class="filter-select" onchange="filterWithHash('duel-masters', this.value)">
                    <option value="nome" <?php echo $orderBy === 'nome' ? 'selected' : ''; ?>>Alfabetico</option>
                    <option value="prezzo_asc" <?php echo $orderBy === 'prezzo_asc' ? 'selected' : ''; ?>>Prezzo ↑</option>
                    <option value="prezzo_desc" <?php echo $orderBy === 'prezzo_desc' ? 'selected' : ''; ?>>Prezzo ↓</option>
                </select>
            </div>
            
            <div class="cards-grid">
                <?php foreach ($duelMasters as $item): ?>
                    <div class="card <?php echo $item['posseduto'] ? '' : 'missing'; ?>" 
                        data-item-type="duel_masters" 
                        data-item-id="<?php echo $item['id']; ?>">
                        
                        <img src="<?php echo htmlspecialchars($item['immagine_url']); ?>" 
                            alt="<?php echo htmlspecialchars($item['nome']); ?>" 
                            class="card-image"
                            onerror="this.src='https://via.placeholder.com/250x300?text=No+Image'">
                        <div class="card-content">
                            <?php if ($item['is_box']): ?>
                                <span class="stato-badge in-corso">BOX</span>
                            <?php endif; ?>
                            
                            <h3 class="card-title"><?php echo htmlspecialchars($item['nome']); ?></h3>
                            <p class="card-date"><?php echo formatDate($item['data_pubblicazione']); ?></p>
                            
                            <div class="card-progress">
                                <?php if ($item['posseduto']): ?>
                                    <span class="complete-badge">Posseduto</span>
                                <?php else: ?>
                                    <span class="card-volumes">Non posseduto</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($item['prezzo'] > 0): ?>
                                <div class="card-price"><?php echo formatPrice($item['prezzo']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Sezione Vinili e CD -->
        <section id="vinili-cd" class="section">
            <h1 class="section-title">Vinili e CD</h1>
            
            <div class="search-filter-bar">
                <input type="text" class="search-input" placeholder="Cerca vinili o CD..." value="<?php echo htmlspecialchars($search); ?>">
                
                <select class="filter-select" onchange="filterWithHash('vinili-cd', this.value)">
                    <option value="titolo" <?php echo $orderBy === 'titolo' ? 'selected' : ''; ?>>Alfabetico</option>
                    <option value="costo_asc" <?php echo $orderBy === 'costo_asc' ? 'selected' : ''; ?>>Costo ↑</option>
                    <option value="costo_desc" <?php echo $orderBy === 'costo_desc' ? 'selected' : ''; ?>>Costo ↓</option>
                    <option value="autore" <?php echo $orderBy === 'autore' ? 'selected' : ''; ?>>Artista</option>
                    <option value="tipo" <?php echo $orderBy === 'tipo' ? 'selected' : ''; ?>>Tipo</option>
                </select>
                
            </div>
            
            <div class="cards-grid">
                <?php foreach ($viniliCD as $item): ?>
                    <!-- IMPORTANTE: Aggiungere data-item-type e data-item-id -->
                    <div class="card <?php echo $item['posseduto'] ? '' : 'missing'; ?>" 
                        data-item-type="vinili_cd" 
                        data-item-id="<?php echo $item['id']; ?>">
                        
                        <img src="<?php echo htmlspecialchars($item['immagine_url']); ?>" 
                            alt="<?php echo htmlspecialchars($item['titolo']); ?>" 
                            class="card-image"
                            onerror="this.src='https://via.placeholder.com/250x300?text=No+Image'">
                        <div class="card-content">
                            <span class="stato-badge <?php echo $item['tipo'] === 'vinile' ? 'in-corso' : 'completo'; ?>">
                                <?php echo strtoupper($item['tipo']); ?>
                            </span>
                            
                            <h3 class="card-title"><?php echo htmlspecialchars($item['titolo']); ?></h3>
                            
                            <?php if (!empty($item['autore'])): ?>
                                <p class="card-author">
                                    <strong>Artista:</strong> <?php echo htmlspecialchars($item['autore']); ?>
                                </p>
                            <?php endif; ?>
                            
                            <p class="card-date"><?php echo formatDate($item['data_pubblicazione']); ?></p>
                            
                            <div class="card-progress">
                                <?php if ($item['posseduto']): ?>
                                    <span class="complete-badge">Posseduto</span>
                                <?php else: ?>
                                    <span class="card-volumes">Non posseduto</span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($item['costo'] > 0): ?>
                                <div class="card-price"><?php echo formatPrice($item['costo']); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <style>
        .card-author {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        </style>

            <?php
            // Funzione per creare sezioni generiche
            function createGenericSection($id, $title, $items, $counter = null) {
                echo "<section id=\"$id\" class=\"section\">";
                echo "<h1 class=\"section-title\">$title</h1>";
                
                if ($counter !== null) {
                    echo "<div class=\"spin-wheel-container\">";
                    echo "<div class=\"counter-badge\">$counter</div>";
                    echo "</div>";
                }
                
                echo '<div class="search-filter-bar">
                        <input type="text" class="search-input" placeholder="Cerca..." onkeyup="performSearch(this.value)">
                        <select class="filter-select" data-section="' . $id . '" onchange="applyFilter(\'' . $id . '\', this.value)">
                            <option value="nome">Ordine Alfabetico</option>
                            <option value="prezzo_asc">Prezzo: Dal più basso</option>
                            <option value="prezzo_desc">Prezzo: Dal più alto</option>
                        </select>
                      </div>';
                
                echo '<div class="cards-grid">';
                foreach ($items as $item) {
                    $cardClass = $item['posseduto'] ? 'card' : 'card missing';
                    $itemType = getItemType($item);
                    
                    echo "<div class=\"$cardClass\" data-item-type=\"$itemType\" data-item-id=\"{$item['id']}\">";
                    echo '<img src="' . htmlspecialchars($item['immagine_url']) . '" alt="' . htmlspecialchars($item['nome']) . '" class="card-image" onerror="this.src=\'https://via.placeholder.com/250x300?text=No+Image\'">';
                    echo '<div class="card-content">';
                    echo '<h3 class="card-title">' . htmlspecialchars($item['nome']) . '</h3>';
                    echo '<p class="card-date">' . formatDate($item['data_pubblicazione']) . '</p>';
                    
                    if (isset($item['codice'])) {
                        echo '<p class="card-code"><strong>Codice:</strong> ' . htmlspecialchars($item['codice']) . '</p>';
                    }
                    
                    echo '<div class="card-progress">';
                    echo $item['posseduto'] ? '<span class="complete-badge">Posseduto</span>' : '<span class="card-volumes">Non posseduto</span>';
                    echo '</div>';
                    
                    if ($item['prezzo'] > 0) {
                        echo '<div class="card-price">' . formatPrice($item['prezzo']) . '</div>';
                    }
                    
                    // Links per gameboys e pokemon_game
                    if (isset($item['links']) && !empty($item['links'])) {
                        $links = json_decode($item['links'], true);
                        if ($links && count($links) > 0) {
                            echo '<div class="card-links">';
                            foreach ($links as $index => $link) {
                                echo '<a href="' . htmlspecialchars($link) . '" target="_blank" class="card-link">Link ' . ($index + 1) . '</a>';
                            }
                            echo '</div>';
                        }
                    }
                    
                    echo '</div>';
                    echo '</div>';
                }
                echo '</div>';
                echo '</section>';
            }
            
            // Crea le sezioni per le nuove categorie
            createGenericSection('funko-pop', 'Funko Pop', $funkoPop);
            createGenericSection('monster', 'Monster Energy', $monster);
            createGenericSection('artbooks-anime', 'Artbooks Anime', $artbooksAnime);
            createGenericSection('gameboys', 'Gameboys AD-SP', $gameboys);
            createGenericSection('pokemon-game', 'Pokemon - Mario Party - Final fantasy - Fire Emblem - Resident evil', $pokemonGame);
            createGenericSection('numeri-yugioh', 'Numeri Yu-Gi-Oh', $numeriYugioh, "Numeri Mancanti: $countYugiohMancanti");
            createGenericSection('duel-masters', 'Duel Masters', $duelMasters);
            ?>

            <!-- Sezione Aggiungi -->
            <section id="aggiungi" class="section">
                <h1 class="section-title">Aggiungi Elemento</h1>
                <div class="form-container">
                    <form id="addForm" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="form-group">
                            <label for="tipo">Tipo:</label>
                            <select id="tipo" name="tipo" required>
                                <option value="">Seleziona tipo...</option>
                                <option value="serie">Serie Manga</option>
                                <option value="variant">Variant</option>
                                <option value="funko_pop">Funko Pop</option>
                                <option value="monster">Monster</option>
                                <option value="artbooks_anime">Artbooks Anime</option>
                                <option value="gameboys">Gameboys</option>
                                <option value="pokemon_game">Pokemon Game</option>
                                <option value="numeri_yugioh">Numeri Yu-Gi-Oh</option>
                                <option value="duel_masters">Duel Masters</option>
                                <option value="libro_normale">Libro Normale</option>
                                <option value="vinile_cd">Vinile/CD</option>
                                <option value="libreria_update">Libreria Update</option>
                                <option value="libro_lovecraft">Libro Lovecraft</option>
                                <option value="libro_giapponese">Libro Giapponese</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="nome">Nome/Titolo:</label>
                            <input type="text" id="nome" name="nome" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="immagine_url">URL Immagine:</label>
                            <input type="url" id="immagine_url" name="immagine_url">
                        </div>

                        <!-- Campo per upload file (per Libreria Update) -->
                        <div class="form-group hidden" id="upload-image-group">
                            <label for="image_upload">Carica Immagine dal PC:</label>
                            <input type="file" id="image_upload" name="image_upload" accept="image/*">
                            <div class="upload-preview" id="upload-preview"></div>
                            <small>Formati supportati: JPG, PNG, GIF, WEBP (max 5MB)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="data">Data di Pubblicazione/Rilascio:</label>
                            <input type="date" id="data" name="data">
                        </div>
                        
                        <!-- Campi specifici per Serie e Variant -->
                        <div class="form-group hidden" id="stato-group">
                            <label for="stato">Stato:</label>
                            <select id="stato" name="stato">
                                <option value="completo">Completo</option>
                                <option value="in_corso">In Corso</option>
                                <option value="interrotta">Interrotta</option>
                            </select>
                        </div>

                        <div class="form-group hidden" id="manga-straniero-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="manga_straniero" name="manga_straniero">
                                <label for="manga_straniero">Manga Straniero</label>
                            </div>
                        </div>

                        <div class="form-group hidden" id="numero-manga-group">
                            <label for="numero_manga">Numero Manga nella foto:</label>
                            <input type="number" id="numero_manga" name="numero_manga" min="1">
                        </div>

                        <div class="form-group hidden" id="note-group">
                            <label for="note">Note:</label>
                            <textarea id="note" name="note" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group hidden" id="categorie-group">
                            <label for="categorie">Categorie:</label>
                            <input type="text" id="categorie" name="categorie" placeholder="Es: shonen, horror, azione">
                            <div class="categoria-input-info">
                                Inserisci le categorie separate da virgola (es: shonen, horror, azione)
                            </div>
                        </div>
                        
                        <div class="form-group hidden" id="da-prendere-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="da_prendere_subito" name="da_prendere_subito">
                                <label for="da_prendere_subito">🎯 Da Prendere Subito</label>
                            </div>
                        </div>

                        <!-- Campo Rarità - Solo per serie manga normali -->
                        <div class="form-group hidden" id="rarita-group">
                            <label>⭐ Rarità:</label>
                            <div class="rarita-selector" id="rarita-selector-add">
                                <span class="star" data-value="1">★</span>
                                <span class="star" data-value="2">★</span>
                                <span class="star" data-value="3">★</span>
                                <span class="star" data-value="4">★</span>
                                <span class="star" data-value="5">★</span>
                            </div>
                            <input type="hidden" id="rarita_value" name="rarita" value="">
                        </div>
                                                
                        <!-- Autore per Libri e Vinili/CD -->
                        <div class="form-group hidden" id="autore-group">
                            <label for="autore">Autore/Artista:</label>
                            <input type="text" id="autore" name="autore">
                        </div>
                        
                        <!-- Tipo media per Vinili/CD -->
                        <div class="form-group hidden" id="tipo-media-group">
                            <label for="tipo_media">Tipo:</label>
                            <select id="tipo_media" name="tipo_media">
                                <option value="vinile">Vinile</option>
                                <option value="cd">CD</option>
                            </select>
                        </div>
                        
                        <!-- Codice per Yu-Gi-Oh -->
                        <div class="form-group hidden" id="codice-group">
                            <label for="codice">Codice (11 cifre):</label>
                            <input type="text" id="codice" name="codice" maxlength="11" pattern="[0-9]{11}">
                        </div>
                        
                        <!-- Box per Duel Masters -->
                        <div class="form-group hidden" id="box-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_box" name="is_box">
                                <label for="is_box">È un Box</label>
                            </div>
                        </div>
                        
                        <!-- Links per Gameboys e Pokemon Game -->
                        <div class="form-group hidden" id="links-group">
                            <label>Links:</label>
                            <div id="linksContainer"></div>
                            <button type="button" id="addLinkBtn" class="btn btn-secondary">Aggiungi Link</button>
                        </div>
                        
                        <div class="form-group hidden" id="costo-group">
                            <label for="costo_medio">Costo Medio (€):</label>
                            <input type="number" id="costo_medio" name="costo_medio" step="0.01" min="0">
                        </div>
                        
                        <div class="form-group hidden" id="costo-vinili-group">
                            <label for="costo">Costo (€):</label>
                            <input type="number" id="costo" name="costo" step="0.01" min="0">
                        </div>
                        
                        <div class="form-group" id="prezzo-group">
                            <label for="prezzo">Prezzo (€):</label>
                            <input type="number" id="prezzo" name="prezzo" step="0.01" min="0" value="0">
                        </div>
                        
                        <div class="form-group">
                            <label for="volumi_totali">Volumi Totali:</label>
                            <input type="number" id="volumi_totali" name="volumi_totali" min="1" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="volumi_posseduti">Volumi Posseduti:</label>
                            <input type="number" id="volumi_posseduti" name="volumi_posseduti" min="0" value="0" required>
                        </div>
                        
                        <div class="form-group hidden" id="posseduto-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="posseduto" name="posseduto">
                                <label for="posseduto">Posseduto</label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn">Aggiungi</button>
                    </form>
                </div>
            </section>

            <!-- Sezione Rimuovi -->
            <section id="rimuovi" class="section">
                <h1 class="section-title">Rimuovi Elemento</h1>
                <div class="remove-list">
                    <?php foreach ($tuttiGliElementi as $elemento): ?>
                        <div class="remove-item">
                            <div class="remove-item-info">
                                <div class="remove-item-title"><?php echo htmlspecialchars($elemento['nome']); ?></div>
                                <div class="remove-item-type"><?php echo ucfirst(str_replace('_', ' ', $elemento['tipo'])); ?></div>
                            </div>
                            <button class="btn btn-danger" 
                                    onclick="confirmRemove(<?php echo $elemento['id']; ?>, '<?php echo $elemento['tipo']; ?>', '<?php echo htmlspecialchars($elemento['nome']); ?>')">
                                Rimuovi
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
    </main>

    <!-- Modal per la Ruota della Fortuna -->
    <div id="spinWheelModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 class="modal-title">🎯 Ruota della Fortuna - Serie Mancanti</h2>
            <div class="wheel-container">
                <div id="wheel" class="wheel"></div>
            </div>
            <button id="spinBtn" class="btn" onclick="spinWheel()">Gira la Ruota!</button>
            <div id="spinResult"></div>
        </div>
    </div>

    <!-- Modal per modifica elementi -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 class="modal-title">Modifica Elemento</h2>
            <div class="edit-form-container">
                <form id="editForm" method="POST">
                    <!-- Il contenuto verrà popolato dinamicamente da JavaScript -->
                </form>
            </div>
        </div>
    </div>

    <!-- Modal per gestione volumi -->
    <div id="volumeModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 class="modal-title">Gestisci Volumi</h2>
            <div class="volume-content">
                <!-- Il contenuto verrà popolato dinamicamente da JavaScript -->
            </div>
        </div>
    </div>

    <script src="../js/script.js?version=28"></script>
    <script>
        // Enhanced form handling
        document.addEventListener("DOMContentLoaded", function() {
            const tipoSelect = document.getElementById("tipo");
            
            if (tipoSelect) {
                tipoSelect.addEventListener("change", handleTipoChange);
            }
        });

        // FUNZIONI FILTRO AGGIORNATE
        function filterWithHash(sectionId, orderBy) {
            const url = new URL(window.location);
            url.searchParams.set('order', orderBy);
            // Mantieni la categoria se esiste
            url.hash = sectionId;
            window.location.href = url.toString();
        }

        function filterTipoMediaWithHash(sectionId, tipoMedia) {
            const url = new URL(window.location);
            if (tipoMedia) {
                url.searchParams.set('tipo_media', tipoMedia);
            } else {
                url.searchParams.delete('tipo_media');
            }
            // Mantieni order
            const currentOrder = new URLSearchParams(window.location.search).get('order');
            if (currentOrder) {
                url.searchParams.set('order', currentOrder);
            }
            url.hash = sectionId;
            window.location.href = url.toString();
        }
        
        // Apply filter function
        function applyFilter(section, orderBy) {
            const url = new URL(window.location);
            url.searchParams.set('order', orderBy);
            window.location.href = url.toString();
        }

        function checkCardAttributes() {
            const sections = [
                'funko-pop', 'monster', 'artbooks-anime', 
                'gameboys', 'pokemon-game', 'numeri-yugioh', 'duel-masters'
            ];
            
            sections.forEach(sectionId => {
                const section = document.getElementById(sectionId);
                if (section) {
                    const cards = section.querySelectorAll('.card');
                    console.log(`Sezione ${sectionId}:`, cards.length, 'cards');
                    
                    cards.forEach((card, index) => {
                        const type = card.getAttribute('data-item-type');
                        const id = card.getAttribute('data-item-id');
                        console.log(`  Card ${index}:`, { type, id });
                        
                        if (!type || !id) {
                            console.error(`    ERRORE: Card senza attributi!`, card);
                        }
                    });
                }
            });
        }
        
        // Search function with debouncing
        let searchTimeout;
        function performSearch(query) {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                if (query.length >= 2) {
                    const url = new URL(window.location);
                    url.searchParams.set('search', query);
                    window.location.href = url.toString();
                } else if (query.length === 0) {
                    const url = new URL(window.location);
                    url.searchParams.delete('search');
                    window.location.href = url.toString();
                }
            }, 500);
        }
        
        // Set search input value on page load
        document.addEventListener("DOMContentLoaded", function() {
            const searchInputs = document.querySelectorAll('.search-input');
            const urlParams = new URLSearchParams(window.location.search);
            const searchQuery = urlParams.get('search');

            if (searchQuery) {
                searchInputs.forEach(input => {
                    input.value = searchQuery;
                });
            }
            
            // Add search event listeners
            searchInputs.forEach(input => {
                input.addEventListener('input', function() {
                    performSearch(this.value);
                });
            });
        });
    </script>
</body>
</html>

<?php
// Funzione helper per determinare il tipo di item
function getItemType($item) {
    // Controlla se l'array contiene chiavi specifiche per determinare il tipo
    if (isset($item['volumi_totali'])) return 'serie';
    if (isset($item['costo_medio'])) return 'variant';
    if (isset($item['codice'])) return 'numeri_yugioh';
    if (isset($item['is_box'])) return 'duel_masters';
    
    // Controlla dalla tabella di origine se disponibile
    global $pdo;
    $tables = ['funko_pop', 'monster', 'artbooks_anime', 'gameboys', 'pokemon_game'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SELECT id FROM $table WHERE id = ? LIMIT 1");
        $stmt->execute([$item['id']]);
        if ($stmt->fetch()) {
            return $table;
        }
    }
    
    return 'unknown';
}
?>


<!--lo scroll non è problema dell'index-->