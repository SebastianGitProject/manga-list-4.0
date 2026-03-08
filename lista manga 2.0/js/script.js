document.addEventListener("DOMContentLoaded", () => {
  //window.scrollTo(0, 0);
  initRaritaSelectors();

  const links = document.querySelectorAll("nav a");
  const sections = document.querySelectorAll(".section");
  const modal = document.getElementById("serieModal");
  const editModal = document.getElementById("editModal");
  const volumeModal = document.getElementById("volumeModal");

  let currentSection = window.location.hash.substring(1) || 'collezione';
  
  //url.searchParams.set('scroll', 'top');
  

  // Navigation //parte dove modifica anche lo scrollTo quando la pagina viene caricata
  links.forEach(link => {
    link.addEventListener("click", e => {
      //window.scrollTo(0, 0);
      e.preventDefault();
      const targetId = link.getAttribute("href").substring(1);
      //window.scrollTo(0, 0);
      sections.forEach(sec => sec.classList.remove("active"));
      document.getElementById(targetId).classList.add("active");
      //window.scrollTo(0, 0);
      links.forEach(l => l.classList.remove("active"));
      link.classList.add("active");
      //window.scrollTo(0, 0);
      // Aggiorna hash nell'URL
      window.location.hash = targetId;
      currentSection = targetId;
      window.scrollTo(0, 0);  //questo è quando cambio sezione e mi mette subito in alto
    });
  });

  // Imposta sezione attiva all'avvio
  if (currentSection) {
    sections.forEach(sec => sec.classList.remove("active"));
    const targetSection = document.getElementById(currentSection);
    if (targetSection) {
      targetSection.classList.add("active");
    }
    
    links.forEach(l => l.classList.remove("active"));
    const activeLink = document.querySelector(`nav a[href="#${currentSection}"]`);
    if (activeLink) {
      activeLink.classList.add("active");
    }
  }

  // Form handling per tipo
  const tipoSelect = document.getElementById("tipo");
  if (tipoSelect) {
    tipoSelect.addEventListener("change", handleTipoChange);
  }

  // Search functionality
  const searchInput = document.getElementById("searchInput");
  if (searchInput) {
    let searchTimeout;
    searchInput.addEventListener("input", function() {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        performSearch(this.value);
      }, 200);
    });
  }

  // Filter functionality
  const filterSelects = document.querySelectorAll(".filter-select");
  filterSelects.forEach(select => {
    select.addEventListener("change", function() {
      const sectionId = this.dataset.section;
      applyFilter(sectionId, this.value);
    });
  });


  const imageUpload = document.getElementById('image_upload');
    if (imageUpload) {
        imageUpload.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('upload-preview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `
                        <div class="preview-image-container">
                            <img src="${e.target.result}" alt="Preview" class="preview-image">
                            <button type="button" class="btn-remove-preview" onclick="removeImagePreview()">
                                ✕ Rimuovi
                            </button>
                        </div>
                    `;
                };
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '';
            }
        });
    }

  // Card click handlers per modifica
  document.addEventListener("click", function(e) {
    const card = e.target.closest(".card");
    if (card && !e.target.classList.contains("btn") && !e.target.closest(".card-links")) {
        const serieId = card.getAttribute('data-serie-id');
        const variantId = card.getAttribute('data-variant-id');
        const mangaStranieroId = card.getAttribute('data-manga-straniero-id');
        const itemType = card.getAttribute('data-item-type');
        const itemId = card.getAttribute('data-item-id');
        
        if (serieId) {
            openSerieEditModal(serieId);
        } else if (variantId) {
            openVariantEditModal(variantId);
        } else if (mangaStranieroId) {
            openMangaStranieroEditModal(mangaStranieroId);
        } else if (itemType && itemId) {
            openItemEditModal(itemType, itemId);
        }
    }
});

  // Modal functionality
  const closeButtons = document.querySelectorAll(".close");
  closeButtons.forEach(close => {
    close.addEventListener("click", function() {
      this.closest(".modal").style.display = "none";
    });
  });

  window.addEventListener("click", function(e) {
    if (e.target.classList.contains("modal")) {
      e.target.style.display = "none";
    }
  });

  // Links management for gameboys and pokemon_game
  setupLinksManagement();
});

function removeImagePreview() {
    const imageUpload = document.getElementById('image_upload');
    const preview = document.getElementById('upload-preview');
    
    if (imageUpload) imageUpload.value = '';
    if (preview) preview.innerHTML = '';
}

function handleTipoChange() {
  const tipoSelect = document.getElementById("tipo");
  const form = tipoSelect.closest('form');
  
  const tipoValue = tipoSelect.value;
  form.reset();
  tipoSelect.value = tipoValue;

  const costoGroup = document.getElementById("costo-group");
  const possedutoGroup = document.getElementById("posseduto-group");
  const prezzoGroup = document.getElementById("prezzo-group");
  const codiceGroup = document.getElementById("codice-group");
  const boxGroup = document.getElementById("box-group");
  const linksGroup = document.getElementById("links-group");
  const statoGroup = document.getElementById("stato-group");
  const categorieGroup = document.getElementById("categorie-group");
  const daPrendereGroup = document.getElementById("da-prendere-group");
  const autoreGroup = document.getElementById("autore-group");
  const tipoMediaGroup = document.getElementById("tipo-media-group");
  const costoViniliGroup = document.getElementById("costo-vinili-group");
  const volumiTotaliGroup = document.getElementById('volumi_totali')?.closest('.form-group');
  const volumiPossedutilGroup = document.getElementById('volumi_posseduti')?.closest('.form-group');
  const prezzoInput = document.getElementById('prezzo');
  const mangaStranieroGroup = document.getElementById("manga-straniero-group");
  const numeroMangaGroup = document.getElementById("numero-manga-group");
  const noteGroup = document.getElementById("note-group");
  const uploadImageGroup = document.getElementById("upload-image-group");
  const immagineUrlGroup = document.getElementById("immagine_url")?.closest('.form-group');
  const raritaGroup = document.getElementById("rarita-group");
  
  const volumiTotaliInput = document.getElementById('volumi_totali');
  const volumiPossedutilInput = document.getElementById('volumi_posseduti');
  const nomeInput = document.getElementById('nome');

  // Reset all groups
  [costoGroup, possedutoGroup, prezzoGroup, codiceGroup, boxGroup, linksGroup, 
     statoGroup, categorieGroup, daPrendereGroup, autoreGroup, tipoMediaGroup, 
     costoViniliGroup, mangaStranieroGroup, numeroMangaGroup, noteGroup, raritaGroup, uploadImageGroup].forEach(group => {
        if (group) group.classList.add("hidden");
    });

  // Clear links
  const linksContainer = document.getElementById('linksContainer');
  if (linksContainer) linksContainer.innerHTML = '';

  const tipo = tipoSelect.value;

  switch(tipo) {
    case "variant":
      costoGroup?.classList.remove("hidden");
      possedutoGroup?.classList.remove("hidden");
      statoGroup?.classList.remove("hidden");
      categorieGroup?.classList.remove("hidden");
      daPrendereGroup?.classList.remove("hidden");
      if (volumiTotaliInput) volumiTotaliInput.value = "1";
      if (volumiTotaliInput) volumiTotaliInput.setAttribute('readonly', 'readonly');
      if (nomeInput) nomeInput.setAttribute('name', 'titolo');
      showVolumiGroups();
      break;
      
    case "serie":
      prezzoGroup?.classList.remove("hidden");
      statoGroup?.classList.remove("hidden");
      categorieGroup?.classList.remove("hidden");
      daPrendereGroup?.classList.remove("hidden");
      mangaStranieroGroup?.classList.remove("hidden");
      raritaGroup?.classList.remove("hidden");
      if (volumiTotaliInput) volumiTotaliInput.removeAttribute('readonly');
      if (nomeInput) nomeInput.setAttribute('name', 'titolo');
      if (prezzoInput) prezzoInput.setAttribute('name', 'prezzo_medio');
      showVolumiGroups();
      break;
    
    case "libreria_update":
      // NASCONDERE URL normale, MOSTRARE upload
      if (immagineUrlGroup) immagineUrlGroup.classList.add("hidden");
      uploadImageGroup?.classList.remove("hidden");
      numeroMangaGroup?.classList.remove("hidden");
      noteGroup?.classList.remove("hidden");
      if (nomeInput) nomeInput.setAttribute('name', 'titolo');
      hideVolumiGroups();
      break;
            
    case "libro_lovecraft":
      costoViniliGroup?.classList.remove("hidden");
      possedutoGroup?.classList.remove("hidden");
      if (nomeInput) nomeInput.setAttribute('name', 'titolo');
      hideVolumiGroups();
      break;
            
    case "libro_giapponese":
      costoViniliGroup?.classList.remove("hidden");
      possedutoGroup?.classList.remove("hidden");
      autoreGroup?.classList.remove("hidden");
      if (nomeInput) nomeInput.setAttribute('name', 'titolo');
      hideVolumiGroups();
      break;

    case "libro_normale":
      prezzoGroup?.classList.remove("hidden");
      possedutoGroup?.classList.remove("hidden");
      autoreGroup?.classList.remove("hidden");
      if (nomeInput) nomeInput.setAttribute('name', 'titolo');
      hideVolumiGroups();
      break;
      
    case "vinile_cd":
      costoViniliGroup?.classList.remove("hidden");
      possedutoGroup?.classList.remove("hidden");
      autoreGroup?.classList.remove("hidden");
      tipoMediaGroup?.classList.remove("hidden");
      if (nomeInput) nomeInput.setAttribute('name', 'titolo');
      hideVolumiGroups();
      break;
      
    case "funko_pop":
    case "monster":
    case "artbooks_anime":
      prezzoGroup?.classList.remove("hidden");
      possedutoGroup?.classList.remove("hidden");
      if (nomeInput) nomeInput.setAttribute('name', 'nome');
      hideVolumiGroups();
      break;
      
    case "gameboys":
    case "pokemon_game":
      prezzoGroup?.classList.remove("hidden");
      possedutoGroup?.classList.remove("hidden");
      linksGroup?.classList.remove("hidden");
      if (nomeInput) nomeInput.setAttribute('name', 'nome');
      hideVolumiGroups();
      break;
      
    case "numeri_yugioh":
      prezzoGroup?.classList.remove("hidden");
      possedutoGroup?.classList.remove("hidden");
      codiceGroup?.classList.remove("hidden");
      if (nomeInput) nomeInput.setAttribute('name', 'nome');
      hideVolumiGroups();
      break;
      
    case "duel_masters":
      prezzoGroup?.classList.remove("hidden");
      possedutoGroup?.classList.remove("hidden");
      boxGroup?.classList.remove("hidden");
      if (nomeInput) nomeInput.setAttribute('name', 'nome');
      hideVolumiGroups();
      break;
      
    default:
      if (immagineUrlGroup) immagineUrlGroup.classList.remove("hidden");
      if (volumiTotaliInput) volumiTotaliInput.removeAttribute('readonly');
      if (nomeInput) nomeInput.setAttribute('name', 'nome');
      showVolumiGroups();
  }
}

function hideVolumiGroups() {
  const volumiTotaliGroup = document.getElementById('volumi_totali')?.closest('.form-group');
  const volumiPossedutilGroup = document.getElementById('volumi_posseduti')?.closest('.form-group');
  
  if (volumiTotaliGroup) volumiTotaliGroup.classList.add('hidden');
  if (volumiPossedutilGroup) volumiPossedutilGroup.classList.add('hidden');
  
  // Remove required when hidden
  const volumiTotaliInput = document.getElementById('volumi_totali');
  const volumiPossedutilInput = document.getElementById('volumi_posseduti');
  if (volumiTotaliInput) volumiTotaliInput.removeAttribute('required');
  if (volumiPossedutilInput) volumiPossedutilInput.removeAttribute('required');
}

function showVolumiGroups() {
  const volumiTotaliGroup = document.getElementById('volumi_totali')?.closest('.form-group');
  const volumiPossedutilGroup = document.getElementById('volumi_posseduti')?.closest('.form-group');
  
  if (volumiTotaliGroup) volumiTotaliGroup.classList.remove('hidden');
  if (volumiPossedutilGroup) volumiPossedutilGroup.classList.remove('hidden');
}

function setupLinksManagement() {
  const addLinkBtn = document.getElementById('addLinkBtn');
  const linksContainer = document.getElementById('linksContainer');
  
  if (addLinkBtn && linksContainer) {
    addLinkBtn.addEventListener('click', function() {
      const linkDiv = document.createElement('div');
      linkDiv.className = 'link-input-group';
      linkDiv.innerHTML = `
        <input type="url" name="links[]" placeholder="Inserisci URL..." class="form-control" style="margin-bottom: 0.5rem;">
        <button type="button" class="btn btn-danger btn-sm" onclick="removeLink(this)">Rimuovi</button>
      `;
      linksContainer.appendChild(linkDiv);
    });
  }
}

function removeLink(button) {
  button.closest('.link-input-group').remove();
}

// Get base path for ajax calls
function getAjaxPath() {
  const currentPath = window.location.pathname;
  if (currentPath.includes('/php/')) {
    return '../db/ajax.php';
  } else {
    return './db/ajax.php';
  }
}

// Funzione per ottenere la sezione corrente
function getCurrentSection() {
  //window.scrollTo(0, 0);
  return window.location.hash.substring(1) || 'collezione';
  
}



// Ruota della Fortuna
function openSpinWheel() {
  document.getElementById('spinWheelModal').style.display = 'block';
}

function spinWheel() {
  const wheel = document.getElementById('wheel');
  const result = document.getElementById('spinResult');
  const spinBtn = document.getElementById('spinBtn');
  
  spinBtn.disabled = true;
  result.innerHTML = 'Girando...';
  
  // Animazione della ruota
  let rotation = 0;
  const finalRotation = Math.random() * 3600 + 1800; // Minimo 5 giri
  const duration = 3000; // 3 secondi
  const startTime = Date.now();
  
  const animate = () => {
    const elapsed = Date.now() - startTime;
    const progress = Math.min(elapsed / duration, 1);
    
    // Easing function per rallentare alla fine
    const easeOut = 1 - Math.pow(1 - progress, 3);
    rotation = finalRotation * easeOut;
    
    wheel.style.transform = `rotate(${rotation}deg)`;
    
    if (progress < 1) {
      requestAnimationFrame(animate);
    } else {
      // Animazione completata, ottieni serie casuale
      fetch(`${getAjaxPath()}?action=getRandomSerie`)
        .then(response => response.json())
        .then(data => {
          if (data.success && data.serie) {
            result.innerHTML = `
              <div class="spin-result-card">
                <img src="${data.serie.immagine_url || 'https://via.placeholder.com/100x150?text=No+Image'}" 
                     alt="${data.serie.titolo}" 
                     style="width: 100px; height: 150px; object-fit: cover; margin-bottom: 1rem;">
                <h3>${data.serie.titolo}</h3>
                <p>Volumi totali: ${data.serie.volumi_totali}</p>
                ${data.serie.prezzo_medio > 0 ? `<p>Prezzo medio: ${formatPrice(data.serie.prezzo_medio)}</p>` : ''}
              </div>
            `;
          } else {
            result.innerHTML = '<p>Nessuna serie mancante trovata!</p>';
          }
          spinBtn.disabled = false;
        })
        .catch(error => {
          console.error('Error:', error);
          result.innerHTML = '<p>Errore nel caricamento della serie!</p>';
          spinBtn.disabled = false;
        });
    }
  };
  
  animate();
}

// Search function
function performSearch(query) {
  if (query.length < 2) {
    location.reload();
    return;
  }

  fetch(`${getAjaxPath()}?action=search&query=${encodeURIComponent(query)}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        displaySearchResults(data.results);
      } else {
        console.error("Search error:", data.message);
      }
    })
    .catch(error => {
      console.error("Search error:", error);
    });
}

// Display search results
function displaySearchResults(results) {
  const sections = ["collezione", "serie-mancanti", "variant-mancanti", "funko-pop", "monster", "artbooks-anime", "gameboys", "pokemon-game", "numeri-yugioh", "duel-masters"];
  
  sections.forEach(sectionId => {
    const section = document.getElementById(sectionId);
    if (section) {
      const grid = section.querySelector(".cards-grid");
      //window.scrollTo(0, 0);
      if (grid) {
        grid.innerHTML = "";
        //window.scrollTo(0, 0);
        results.forEach(item => {
          if (shouldShowInSection(item, sectionId)) {
            const card = createCardElement(item);
            grid.appendChild(card);
            //window.scrollTo(0, 0);
          }
        });
      }
    }
  });
}

// Check if item should be shown in section
function shouldShowInSection(item, sectionId) {
  const sectionMappings = {
    'collezione': item => (item.tipo === 'serie' && item.volumi_posseduti > 0) || 
                          (item.tipo === 'variant' && item.posseduto) ||
                          (['funko_pop', 'monster', 'artbooks_anime', 'gameboys', 'pokemon_game', 'numeri_yugioh', 'duel_masters'].includes(item.tipo) && item.posseduto),
    'serie-mancanti': item => item.tipo === 'serie' && item.volumi_posseduti === 0,
    'variant-mancanti': item => item.tipo === 'variant' && !item.posseduto,
    'funko-pop': item => item.tipo === 'funko_pop',
    'monster': item => item.tipo === 'monster',
    'artbooks-anime': item => item.tipo === 'artbooks_anime',
    'gameboys': item => item.tipo === 'gameboys',
    'pokemon-game': item => item.tipo === 'pokemon_game',
    'numeri-yugioh': item => item.tipo === 'numeri_yugioh',
    'duel-masters': item => item.tipo === 'duel_masters'
  };
  
  return sectionMappings[sectionId] ? sectionMappings[sectionId](item) : false;
}

// Apply filter - MANTENERE LA SEZIONE CORRENTE
function applyFilter(sectionId, filterType) {
  const currentUrl = new URL(window.location);
  //window.scrollTo(0, 0);
  currentUrl.searchParams.set('order', filterType);
  currentUrl.hash = sectionId; // Mantiene l'hash della sezione
  window.location.href = currentUrl.toString();
}

function openMangaStranieroEditModal(mangaId) {
    fetch(`${getAjaxPath()}?action=getMangaStraniero&id=${mangaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateMangaStranieroEditModal(data.manga);
                document.getElementById("editModal").style.display = "block";
            } else {
                alert("Errore: " + data.message);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("Errore nel caricamento");
        });
}

function populateMangaStranieroEditModal(manga) {
    const modal = document.getElementById("editModal");
    const form = modal.querySelector("form");
    
    let categorieStr = '';
    if (manga.categorie) {
        try {
            const categorieArray = JSON.parse(manga.categorie);
            categorieStr = categorieArray.join(', ');
        } catch(e) {
            categorieStr = '';
        }
    }

    let starsHtml = '';
    for (let i = 1; i <= 5; i++) {
        const isActive = manga.rarita && manga.rarita >= i;
        const classes = isActive ? `star active rarita-${manga.rarita}` : 'star';
        starsHtml += `<span class="${classes}" data-value="${i}">★</span>`;
    }
    
    form.innerHTML = `
        <input type="hidden" name="action" value="updateMangaStraniero">
        <input type="hidden" name="id" value="${manga.id}">
        
        <div class="form-group">
            <label for="edit_titolo">Titolo:</label>
            <input type="text" id="edit_titolo" name="titolo" value="${manga.titolo}" required>
        </div>
        
        <div class="form-group">
            <label for="edit_immagine_url">URL Immagine:</label>
            <input type="url" id="edit_immagine_url" name="immagine_url" value="${manga.immagine_url || ''}">
        </div>
        
        <div class="form-group">
            <label for="edit_data_pubblicazione">Data di Pubblicazione:</label>
            <input type="date" id="edit_data_pubblicazione" name="data_pubblicazione" value="${manga.data_pubblicazione || ''}">
        </div>
        
        <div class="form-group">
            <label for="edit_volumi_totali">Volumi Totali:</label>
            <input type="number" id="edit_volumi_totali" name="volumi_totali" value="${manga.volumi_totali}" min="1" required>
        </div>
        
        <div class="form-group">
            <label for="edit_prezzo_medio">Prezzo Medio (€):</label>
            <input type="number" id="edit_prezzo_medio" name="prezzo_medio" value="${manga.prezzo_medio || 0}" step="0.01" min="0">
        </div>
        
        <div class="form-group">
            <label for="edit_stato">Stato:</label>
            <select id="edit_stato" name="stato">
                <option value="completo" ${manga.stato === 'completo' ? 'selected' : ''}>Completo</option>
                <option value="in_corso" ${manga.stato === 'in_corso' ? 'selected' : ''}>In Corso</option>
                <option value="interrotta" ${manga.stato === 'interrotta' ? 'selected' : ''}>Interrotta</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="edit_categorie">Categorie:</label>
            <input type="text" id="edit_categorie" name="categorie" value="${categorieStr}" placeholder="Es: shonen, horror, azione">
            <div class="categoria-input-info">Separate da virgola</div>
        </div>
        
        <div class="form-group">
            <div class="checkbox-group">
                <input type="checkbox" id="edit_da_prendere_subito" name="da_prendere_subito" ${manga.da_prendere_subito ? 'checked' : ''}>
                <label for="edit_da_prendere_subito">🎯 Da Prendere Subito</label>
            </div>
        </div>

        <!-- Campo Rarità -->
        <div class="form-group">
            <label>⭐ Rarità:</label>
            <div class="rarita-selector" id="rarita-selector-edit-straniero" data-input="edit_rarita_straniero">
                ${starsHtml}
            </div>
            <input type="hidden" id="edit_rarita_straniero" name="rarita" value="${manga.rarita || ''}">
            <small class="rarita-current">
                ${manga.rarita ? `Rarità: <strong style="color: ${getRaritaColor(manga.rarita)}">${getRaritaName(manga.rarita)}</strong>` : 'Nessuna rarità impostata'}
            </small>
        </div>

        <div class="form-group transform-group">
            <div class="checkbox-group">
                <input type="checkbox" id="transform_to_normale" name="transform_to_normale">
                <label for="transform_to_normale" class="transform-label">
                    🏠 Trasforma in Manga Normale
                </label>
            </div>
            <small class="transform-info">
                Seleziona questa opzione per spostare questo manga nella sezione normale
            </small>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn">Aggiorna Manga Straniero</button>
            <button type="button" class="btn btn-secondary" onclick="openVolumeModalStranieri(${manga.id})">Gestisci Volumi</button>
        </div>
    `;
    
    const newForm = form.cloneNode(true);
    form.parentNode.replaceChild(newForm, form);
    newForm.addEventListener("submit", handleMangaStranieroUpdate);

    // Inizializza il selettore di rarità nel modal
    const editSelector = newForm.querySelector('#rarita-selector-edit-straniero');
    if (editSelector) {
        initSingleRaritaSelector(editSelector, 'edit_rarita_straniero');
    }
}

function handleMangaStranieroUpdate(e) {
    e.preventDefault();
    const formData = new FormData(e.target);

    // Verifica se c'è la trasformazione
    const transformToNormale = formData.get('transform_to_normale');
    
    if (transformToNormale) {
        // Conferma trasformazione
        if (!confirm('Sei sicuro di voler trasformare questo manga in Manga Normale? Verrà spostato nella sezione serie normale.')) {
            return;
        }
        
        const mangaId = formData.get('id');
        const transformData = new FormData();
        transformData.append('id', mangaId);
        transformData.append('direzione', 'in_normale');
        
        fetch(`${getAjaxPath()}?action=trasformaManga`, {
            method: "POST",
            body: transformData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Manga trasformato in Manga Normale con successo!");
                location.reload();
            } else {
                alert("Errore nella trasformazione: " + (data.message || 'Errore sconosciuto'));
            }
        })
        .catch(error => {
            console.error("Error: ", error);
            alert("Errore nella trasformazione del manga");
        });
    } else {
        // Update normale
        fetch(getAjaxPath(), {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Manga straniero aggiornato!");
                location.reload();
            } else {
                alert("Errore: figa" + data.message);   //questo
            }
        })
        .catch(error => {
            console.error("Error: ", error);
            alert("Errore nell'aggiornamento");
        });
    }
    
    fetch(getAjaxPath(), {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Manga straniero aggiornato!");
            location.reload();
        } else {
            alert("Errore: Sucayy " + data.message);   //questo
        }
    })
    .catch(error => {
        console.error("Error: ", error);
        alert("Errore nell'aggiornamento");
    });
}

function openVolumeModalStranieri(mangaId) {
    fetch(`${getAjaxPath()}?action=getMangaStraniero&id=${mangaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateVolumeModalStranieri(data.manga);
                document.getElementById("editModal").style.display = "none";
                document.getElementById("volumeModal").style.display = "block";
            } else {
                alert("Errore: " + data.message);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("Errore nel caricamento dei volumi");
        });
}

function populateVolumeModalStranieri(manga) {
    const modal = document.getElementById("volumeModal");
    const title = modal.querySelector(".modal-title");
    const content = modal.querySelector(".volume-content");
    
    title.textContent = `Gestisci Volumi - ${manga.titolo}`;
    
    let volumeGrid = '<div class="volume-grid">';
    const volumiPosseduti = manga.volumi_dettagli || [];
    
    for (let i = 1; i <= manga.volumi_totali; i++) {
        const volumeInfo = volumiPosseduti.find(v => v.numero_volume === i);
        const isOwned = volumeInfo ? volumeInfo.posseduto : false;
        
        volumeGrid += `
            <div class="volume-item ${isOwned ? 'owned' : 'missing'}" 
                 data-volume="${i}" 
                 onclick="toggleVolume(this)">
                ${i}
            </div>
        `;
    }
    
    volumeGrid += '</div>';
    
    content.innerHTML = `
        <p>Clicca sui volumi per cambiare il loro stato (posseduto/non posseduto)</p>
        ${volumeGrid}
        <div class="form-actions" style="margin-top: 2rem;">
            <button type="button" class="btn" onclick="saveVolumesStranieri(${manga.id})">Salva Modifiche</button>
            <button type="button" class="btn btn-secondary" onclick="selectAllVolumes()">Seleziona Tutti</button>
            <button type="button" class="btn btn-secondary" onclick="deselectAllVolumes()">Deseleziona Tutti</button>
        </div>
    `;
}

function saveVolumesStranieri(mangaId) {
    const ownedVolumes = [];
    document.querySelectorAll(".volume-item.owned").forEach(vol => {
        ownedVolumes.push(parseInt(vol.dataset.volume));
    });
    
    const formData = new FormData();
    formData.append("serie_id", mangaId);
    formData.append("volumi", JSON.stringify(ownedVolumes));
    
    fetch(`${getAjaxPath()}?action=updateVolumiStranieri`, {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Volumi aggiornati!");
            location.reload();
        } else {
            alert("Errore: " + data.message);
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("Errore nell'aggiornamento dei volumi");
    });
}

function openPriorityModal(tipo) {
    fetch(`${getAjaxPath()}?action=getElementiConPriorita&tipo=${tipo}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showPriorityModal(tipo, data.elementi);
            } else {
                alert("Errore: " + data.message);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("Errore nel caricamento");
        });
}

function showPriorityModal(tipo, elementi) {
    const modal = document.getElementById("priorityModal") || createPriorityModal();
    const title = modal.querySelector(".modal-title");
    const content = modal.querySelector(".priority-content");
    
    const tipoLabel = {
        'serie': 'Serie',
        'variant': 'Variant',
        'manga_straniero': 'Manga Stranieri'
    }[tipo] || 'Elementi';
    
    title.textContent = `Gestisci Priorità - ${tipoLabel}`;
    
    let cardsHTML = '<div class="priority-cards-grid">';
    
    elementi.forEach(item => {
        const nome = item.titolo || item.nome;
        const priorita = item.priorita || '';
        
        cardsHTML += `
            <div class="priority-card" data-id="${item.id}" data-tipo="${tipo}">
                ${priorita ? `<div class="priority-badge-large">🎀 ${priorita}</div>` : ''}
                <img src="${item.immagine_url || 'https://via.placeholder.com/150x200?text=No+Image'}" 
                     alt="${nome}" 
                     class="priority-card-image">
                <div class="priority-card-content">
                    <h4>${nome}</h4>
                    <div class="priority-input-group">
                        <label>Priorità:</label>
                        <input type="number" 
                               class="priority-input" 
                               value="${priorita}" 
                               min="1" 
                               placeholder="Nessuna"
                               onchange="updatePrioritaCard(${item.id}, '${tipo}', this.value)">
                    </div>
                </div>
            </div>
        `;
    });
    
    cardsHTML += '</div>';
    
    content.innerHTML = cardsHTML;
    modal.style.display = "block";
}

function createPriorityModal() {
    const modal = document.createElement('div');
    modal.id = 'priorityModal';
    modal.className = 'modal';
    modal.innerHTML = `
        <div class="modal-content priority-modal-content">
            <span class="close">&times;</span>
            <h2 class="modal-title">Gestisci Priorità</h2>
            <div class="priority-content"></div>
        </div>
    `;
    document.body.appendChild(modal);
    
    modal.querySelector('.close').addEventListener('click', () => {
        modal.style.display = 'none';
        location.reload(); // Ricarica per vedere le modifiche
    });
    
    return modal;
}

function updatePrioritaCard(id, tipo, priorita) {
    const formData = new FormData();
    formData.append('id', id);
    formData.append('tipo', tipo);
    formData.append('priorita', priorita || null);
    
    fetch(`${getAjaxPath()}?action=updatePriorita`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('Priorità aggiornata');
            // Riordina le card nel modal
            const modal = document.getElementById('priorityModal');
            const tipo_originale = tipo;
            openPriorityModal(tipo_originale); // Ricarica il modal con l'ordine aggiornato
        } else {
            alert('Errore: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function filterStatoWithHash(sectionId, stato) {
    const url = new URL(window.location);
    if (stato) {
        url.searchParams.set('order', stato);
    }
    url.hash = sectionId;
    window.location.href = url.toString();
}

// Open serie edit modal
function openSerieEditModal(serieId) {
  fetch(`${getAjaxPath()}?action=getSerie&id=${serieId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        populateSerieEditModal(data.serie);
        document.getElementById("editModal").style.display = "block";
      } else {
        alert("Errore: " + data.message);
      }
    })
    .catch(error => {
      console.error("Error:", error);
      alert("Errore nel caricamento");
    });
}

// Open variant edit modal
function openVariantEditModal(variantId) {
  fetch(`${getAjaxPath()}?action=getVariant&id=${variantId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        populateVariantEditModal(data.variant);
        document.getElementById("editModal").style.display = "block";
      } else {
        alert("Errore: " + data.message);
      }
    })
    .catch(error => {
      console.error("Error:", error);
      alert("Errore nel caricamento");
    });
}


function openLibroLovecraftEditModal(id) {
    fetch(`${getAjaxPath()}?action=getItem&type=libri_lovecraft&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateLibroLovecraftEditModal(data.item);
                document.getElementById("editModal").style.display = "block";
            } else {
                alert("Errore: " + data.message);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("Errore nel caricamento");
        });
}

function populateLibroLovecraftEditModal(libro) {
    const modal = document.getElementById("editModal");
    const form = modal.querySelector("form");
    
    form.innerHTML = `
        <input type="hidden" name="action" value="updateItem">
        <input type="hidden" name="id" value="${libro.id}">
        <input type="hidden" name="type" value="libri_lovecraft">
        
        <div class="form-group">
            <label>Titolo:</label>
            <input type="text" name="nome" value="${libro.titolo}" required>
        </div>
        
        <div class="form-group">
            <label>URL Immagine:</label>
            <input type="url" name="immagine_url" value="${libro.immagine_url || ''}">
        </div>
        
        <div class="form-group">
            <label>Data Pubblicazione:</label>
            <input type="date" name="data" value="${libro.data_pubblicazione || ''}">
        </div>
        
        <div class="form-group">
            <label>Costo (€):</label>
            <input type="number" name="costo" value="${libro.costo || 0}" step="0.01" min="0">
        </div>
        
        <div class="form-group">
            <div class="checkbox-group">
                <input type="checkbox" name="posseduto" ${libro.posseduto ? 'checked' : ''}>
                <label>Posseduto</label>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn">Aggiorna</button>
        </div>
    `;
    
    const newForm = form.cloneNode(true);
    form.parentNode.replaceChild(newForm, form);
    newForm.addEventListener("submit", handleItemUpdate);
}




function openLibroGiapponeseEditModal(id) {
    fetch(`${getAjaxPath()}?action=getItem&type=libri_giapponesi&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateLibroGiapponeseEditModal(data.item);
                document.getElementById("editModal").style.display = "block";
            } else {
                alert("Errore: " + data.message);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("Errore nel caricamento");
        });
}

function populateLibroGiapponeseEditModal(libro) {
    const modal = document.getElementById("editModal");
    const form = modal.querySelector("form");
    
    form.innerHTML = `
        <input type="hidden" name="action" value="updateItem">
        <input type="hidden" name="id" value="${libro.id}">
        <input type="hidden" name="type" value="libri_giapponesi">
        
        <div class="form-group">
            <label>Titolo:</label>
            <input type="text" name="nome" value="${libro.titolo}" required>
        </div>
        
        <div class="form-group">
            <label>URL Immagine:</label>
            <input type="url" name="immagine_url" value="${libro.immagine_url || ''}">
        </div>
        
        <div class="form-group">
            <label>Autore:</label>
            <input type="text" name="autore" value="${libro.autore || ''}">
        </div>
        
        <div class="form-group">
            <label>Costo (€):</label>
            <input type="number" name="costo" value="${libro.costo || 0}" step="0.01" min="0">
        </div>
        
        <div class="form-group">
            <div class="checkbox-group">
                <input type="checkbox" name="posseduto" ${libro.posseduto ? 'checked' : ''}>
                <label>Posseduto</label>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn">Aggiorna</button>
        </div>
    `;
    
    const newForm = form.cloneNode(true);
    form.parentNode.replaceChild(newForm, form);
    newForm.addEventListener("submit", handleItemUpdate);
}



function openLibreriaUpdateEditModal(id) {
    console.log('Opening Libreria Update modal for ID:', id);
    
    fetch(`${getAjaxPath()}?action=getLibreriaUpdate&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateLibreriaUpdateEditModal(data.item);
                document.getElementById("editModal").style.display = "block";
            } else {
                alert("Errore: " + data.message);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("Errore nel caricamento dei dati");
        });
}

function populateLibreriaUpdateEditModal(item) {
    const modal = document.getElementById("editModal");
    const form = modal.querySelector("form");
    const modalTitle = modal.querySelector(".modal-title");
    
    // Cambia il titolo del modal
    modalTitle.textContent = "Modifica Libreria Update";
    
    form.innerHTML = `
        <input type="hidden" name="action" value="updateLibreriaUpdate">
        <input type="hidden" name="id" value="${item.id}">
        
        <div class="form-group">
            <label>Immagine Attuale:</label>
            <div class="current-image-container">
                <img src="${item.immagine_url}" 
                     alt="Libreria" 
                     class="current-image-preview"
                     onclick="openImageFullscreen('${item.immagine_url}')">
                <small class="text-muted">Clicca sull'immagine per vederla a schermo intero</small>
            </div>
        </div>
        
        <div class="form-group">
            <label for="edit_image_upload">Cambia Immagine (opzionale):</label>
            <input type="file" 
                   id="edit_image_upload" 
                   name="image_upload_edit" 
                   accept="image/*"
                   onchange="previewEditImage(this)">
            <small>Lascia vuoto per mantenere l'immagine attuale. Max 5MB - JPG, PNG, GIF, WEBP</small>
            <div id="edit-image-preview" class="upload-preview"></div>
        </div>
        
        <div class="form-group">
            <label for="edit_data_aggiunta">Data Aggiunta:</label>
            <input type="date" 
                   id="edit_data_aggiunta" 
                   name="data_aggiunta" 
                   value="${item.data_aggiunta}" 
                   required>
        </div>
        
        <div class="form-group">
            <label for="edit_numero_manga">Numero Manga nella foto:</label>
            <input type="number" 
                   id="edit_numero_manga" 
                   name="numero_manga" 
                   value="${item.numero_manga}" 
                   min="1" 
                   required>
        </div>
        
        <div class="form-group">
            <label for="edit_note">Note:</label>
            <textarea id="edit_note" 
                      name="note" 
                      rows="4" 
                      placeholder="Aggiungi note o descrizione...">${item.note || ''}</textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn">💾 Salva Modifiche</button>
            <button type="button" class="btn btn-danger" onclick="confirmDeleteLibreriaUpdate(${item.id})">
                🗑️ Elimina
            </button>
            <button type="button" class="btn btn-secondary" onclick="closeEditModal()">
                ✕ Annulla
            </button>
        </div>
    `;
    
    // Attach event listener al form
    const newForm = form.cloneNode(true);
    form.parentNode.replaceChild(newForm, form);
    newForm.addEventListener("submit", handleLibreriaUpdateUpdate);
}

function confirmDeleteLibreriaUpdate(id) {
    if (!confirm('⚠️ Sei sicuro di voler eliminare questo aggiornamento della libreria?\n\nQuesta azione è irreversibile e l\'immagine verrà eliminata dal server.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('id', id);
    
    fetch(`${getAjaxPath()}?action=deleteLibreriaUpdate`, {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("✅ Libreria Update eliminata con successo!");
            location.reload();
        } else {
            alert("❌ Errore: " + data.message);
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("❌ Errore nell'eliminazione");
    });
}

function previewEditImage(input) {
    const preview = document.getElementById('edit-image-preview');
    const file = input.files[0];
    
    if (file) {
        // Verifica dimensione
        if (file.size > 5 * 1024 * 1024) {
            alert('⚠️ File troppo grande! Massimo 5MB');
            input.value = '';
            preview.innerHTML = '';
            return;
        }
        
        // Verifica tipo
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            alert('⚠️ Tipo di file non valido! Usa JPG, PNG, GIF o WEBP');
            input.value = '';
            preview.innerHTML = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.innerHTML = `
                <div class="preview-image-container">
                    <img src="${e.target.result}" alt="Nuova immagine" class="preview-image">
                    <button type="button" class="btn-remove-preview" onclick="removeEditPreview()">
                        ✕ Rimuovi
                    </button>
                    <p class="preview-label">📸 Nuova immagine selezionata</p>
                </div>
            `;
        };
        reader.readAsDataURL(file);
    } else {
        preview.innerHTML = '';
    }
}

function removeEditPreview() {
    const input = document.getElementById('edit_image_upload');
    const preview = document.getElementById('edit-image-preview');
    
    if (input) input.value = '';
    if (preview) preview.innerHTML = '';
}

function openImageFullscreen(imageUrl) {
    // Crea overlay fullscreen
    const overlay = document.createElement('div');
    overlay.className = 'image-fullscreen-overlay';
    overlay.innerHTML = `
        <div class="fullscreen-container">
            <button class="fullscreen-close" onclick="closeImageFullscreen()">✕</button>
            <img src="${imageUrl}" alt="Libreria" class="fullscreen-image">
            <div class="fullscreen-controls">
                <button class="btn btn-secondary" onclick="downloadImage('${imageUrl}')">
                    💾 Scarica Immagine
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(overlay);
    document.body.style.overflow = 'hidden';
    
    // Chiudi cliccando fuori dall'immagine
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) {
            closeImageFullscreen();
        }
    });
}

function closeImageFullscreen() {
    const overlay = document.querySelector('.image-fullscreen-overlay');
    if (overlay) {
        overlay.remove();
        document.body.style.overflow = '';
    }
}

function downloadImage(imageUrl) {
    const link = document.createElement('a');
    link.href = imageUrl;
    link.download = 'libreria_' + new Date().getTime() + '.jpg';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function closeEditModal() {
    const modal = document.getElementById("editModal");
    if (modal) {
        modal.style.display = "none";
    }
}


function handleLibreriaUpdateUpdate(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const submitBtn = e.target.querySelector('button[type="submit"]');
    
    // Disabilita bottone durante upload
    submitBtn.disabled = true;
    submitBtn.textContent = '⏳ Salvando...';
    
    fetch(getAjaxPath(), {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("✅ Libreria Update aggiornata con successo!");
            location.reload();
        } else {
            alert("❌ Errore: " + data.message);
            submitBtn.disabled = false;
            submitBtn.textContent = '💾 Salva Modifiche';
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("❌ Errore nell'aggiornamento");
        submitBtn.disabled = false;
        submitBtn.textContent = '💾 Salva Modifiche';
    });
}


// Open item edit modal for new sections
function openItemEditModal(itemType, itemId) {

  console.log('Opening modal for:', itemType, itemId);
    

    
    // Routing verso modal specifici
    if (itemType === 'libri_lovecraft') {
        openLibroLovecraftEditModal(itemId);
        return;
    }
    
    if (itemType === 'libri_giapponesi') {
        openLibroGiapponeseEditModal(itemId);
        return;
    }
    
    if (itemType === 'libreria_update') {
        openLibreriaUpdateEditModal(itemId);
        return;
    }

  fetch(`${getAjaxPath()}?action=getItem&type=${itemType}&id=${itemId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateItemEditModal(data.item, itemType);
                document.getElementById("editModal").style.display = "block";
            } else {
                alert("Errore: " + data.message);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("Errore nel caricamento");
        });
}

// Populate item edit modal (per altre sezioni)
function populateItemEditModal(item, itemType) {
    const modal = document.getElementById("editModal");
    const form = modal.querySelector("form");
    
    let formHTML = `
        <input type="hidden" name="action" value="updateItem">
        <input type="hidden" name="id" value="${item.id}">
        <input type="hidden" name="type" value="${itemType}">
        
        <div class="form-group">
            <label>Titolo/Nome:</label>
            <input type="text" name="nome" value="${item.nome || item.titolo}" required>
        </div>
        
        <div class="form-group">
            <label>URL Immagine:</label>
            <input type="url" name="immagine_url" value="${item.immagine_url || ''}">
        </div>
        
        <div class="form-group">
            <label>Data:</label>
            <input type="date" name="data" value="${item.data_pubblicazione || ''}">
        </div>
    `;
    
    // Campi specifici per tipo
    if (itemType === 'libri_normali' || itemType === 'vinili_cd') {
        formHTML += `
            <div class="form-group">
                <label>${itemType === 'libri_normali' ? 'Autore' : 'Artista'}:</label>
                <input type="text" name="autore" value="${item.autore || ''}">
            </div>
        `;
    }
    
    if (itemType === 'vinili_cd') {
        formHTML += `
            <div class="form-group">
                <label>Tipo:</label>
                <select name="tipo_media">
                    <option value="vinile" ${item.tipo === 'vinile' ? 'selected' : ''}>Vinile</option>
                    <option value="cd" ${item.tipo === 'cd' ? 'selected' : ''}>CD</option>
                </select>
            </div>
            <div class="form-group">
                <label>Costo (€):</label>
                <input type="number" name="costo" value="${item.costo || 0}" step="0.01" min="0">
            </div>
        `;
    } else {
        formHTML += `
            <div class="form-group">
                <label>Prezzo (€):</label>
                <input type="number" name="prezzo" value="${item.prezzo || 0}" step="0.01" min="0">
            </div>
        `;
    }
    
    // Codice per Yu-Gi-Oh
    if (itemType === 'numeri_yugioh') {
        formHTML += `
            <div class="form-group">
                <label>Codice (11 cifre):</label>
                <input type="text" name="codice" value="${item.codice || ''}" maxlength="11" pattern="[0-9]{11}">
            </div>
        `;
    }
    
    // Links per gameboys e pokemon
    if (itemType === 'gameboys' || itemType === 'pokemon_game') {
        let links = [];
        try {
            links = item.links ? JSON.parse(item.links) : [];
        } catch(e) {
            links = [];
        }
        
        formHTML += `
            <div class="form-group">
                <label>Links:</label>
                <div id="editLinksContainer">
        `;
        
        links.forEach((link, index) => {
            formHTML += `
                <div class="link-input-group">
                    <input type="url" name="links[]" value="${link}" class="form-control" style="margin-bottom: 0.5rem;">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeLink(this)">Rimuovi</button>
                </div>
            `;
        });
        
        formHTML += `
                </div>
                <button type="button" class="btn btn-secondary" onclick="addEditLink()">Aggiungi Link</button>
            </div>
        `;
    }
    
    // Checkbox posseduto
    formHTML += `
        <div class="form-group">
            <div class="checkbox-group">
                <input type="checkbox" name="posseduto" ${item.posseduto ? 'checked' : ''}>
                <label>Posseduto</label>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn">Aggiorna</button>
        </div>
    `;
    
    form.innerHTML = formHTML;
    
    const newForm = form.cloneNode(true);
    form.parentNode.replaceChild(newForm, form);
    newForm.addEventListener("submit", handleItemUpdate);
}

function handleItemUpdate(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    fetch(getAjaxPath(), {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Elemento aggiornato!");
            location.reload();
        } else {
            alert("Errore: " + data.message);
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("Errore nell'aggiornamento");
    });
}

function filterTipoMediaWithHash(sectionId, tipoMedia) {
    const url = new URL(window.location);
    if (tipoMedia) {
        url.searchParams.set('tipo_media', tipoMedia);
    } else {
        url.searchParams.delete('tipo_media');
    }
    url.hash = sectionId;
    window.location.href = url.toString();
}

function addEditLink() {
    const container = document.getElementById('editLinksContainer');
    const linkDiv = document.createElement('div');
    linkDiv.className = 'link-input-group';
    linkDiv.innerHTML = `
        <input type="url" name="links[]" placeholder="Inserisci URL..." class="form-control" style="margin-bottom: 0.5rem;">
        <button type="button" class="btn btn-danger btn-sm" onclick="removeLink(this)">Rimuovi</button>
    `;
    container.appendChild(linkDiv);
}

// Populate serie edit modal
function populateSerieEditModal(serie) {
  const modal = document.getElementById("editModal");
  const form = modal.querySelector("form");
  
  // Prepara categorie per il campo input
  let categorieStr = '';
  if (serie.categorie) {
    try {
      const categorieArray = JSON.parse(serie.categorie);
      categorieStr = categorieArray.join(', ');
    } catch(e) {
      categorieStr = '';
    }
  }

  let starsHtml = '';
    for (let i = 1; i <= 5; i++) {
        const isActive = serie.rarita && serie.rarita >= i;
        const classes = isActive ? `star active rarita-${serie.rarita}` : 'star';
        starsHtml += `<span class="${classes}" data-value="${i}">★</span>`;
    }
  
  form.innerHTML = `
    <input type="hidden" name="action" value="updateSerie">
    <input type="hidden" name="id" value="${serie.id}">
    
    <div class="form-group">
      <label for="edit_titolo">Titolo:</label>
      <input type="text" id="edit_titolo" name="titolo" value="${serie.titolo}" required>
    </div>
    
    <div class="form-group">
      <label for="edit_immagine_url">URL Immagine:</label>
      <input type="url" id="edit_immagine_url" name="immagine_url" value="${serie.immagine_url || ''}">
    </div>
    
    <div class="form-group">
      <label for="edit_data_pubblicazione">Data di Pubblicazione:</label>
      <input type="date" id="edit_data_pubblicazione" name="data_pubblicazione" value="${serie.data_pubblicazione || ''}">
    </div>
    
    <div class="form-group">
      <label for="edit_volumi_totali">Volumi Totali:</label>
      <input type="number" id="edit_volumi_totali" name="volumi_totali" value="${serie.volumi_totali}" min="1" required>
    </div>
    
    <div class="form-group">
      <label for="edit_prezzo_medio">Prezzo Medio (€):</label>
      <input type="number" id="edit_prezzo_medio" name="prezzo_medio" value="${serie.prezzo_medio || 0}" step="0.01" min="0">
    </div>
    
    <div class="form-group">
      <label for="edit_stato">Stato:</label>
      <select id="edit_stato" name="stato">
        <option value="completo" ${serie.stato === 'completo' ? 'selected' : ''}>Completo</option>
        <option value="in_corso" ${serie.stato === 'in_corso' ? 'selected' : ''}>In Corso</option>
        <option value="interrotta" ${serie.stato === 'interrotta' ? 'selected' : ''}>Interrotta</option>
      </select>
    </div>
    
    <div class="form-group">
      <label for="edit_categorie">Categorie:</label>
      <input type="text" id="edit_categorie" name="categorie" value="${categorieStr}" placeholder="Es: shonen, horror, azione">
      <div class="categoria-input-info">Separate da virgola</div>
    </div>
    
    <div class="form-group">
      <div class="checkbox-group">
        <input type="checkbox" id="edit_da_prendere_subito" name="da_prendere_subito" ${serie.da_prendere_subito ? 'checked' : ''}>
        <label for="edit_da_prendere_subito">🎯 Da Prendere Subito</label>
      </div>
    </div>

    <!-- Campo Rarità -->
    <div class="form-group">
        <label>⭐ Rarità:</label>
        <div class="rarita-selector" id="rarita-selector-edit" data-input="edit_rarita">
            ${starsHtml}
        </div>
        <input type="hidden" id="edit_rarita" name="rarita" value="${serie.rarita || ''}">
        <small class="rarita-current">
            ${serie.rarita ? `Rarità: <strong style="color: ${getRaritaColor(serie.rarita)}">${getRaritaName(serie.rarita)}</strong>` : 'Nessuna rarità impostata'}
        </small>
    </div>

    <div class="form-group transform-group">
        <div class="checkbox-group">
            <input type="checkbox" id="transform_to_straniero" name="transform_to_straniero">
            <label for="transform_to_straniero" class="transform-label">
                🌍 Trasforma in Manga Straniero
            </label>
        </div>
        <small class="transform-info">
            Seleziona questa opzione per spostare questo manga nella sezione "Manga Stranieri"
        </small>
    </div>
    
    <div class="form-actions">
      <button type="submit" class="btn">Aggiorna Serie</button>
      <button type="button" class="btn btn-secondary" onclick="openVolumeModal(${serie.id})">Gestisci Volumi</button>
    </div>
  `;
  
  const newForm = form.cloneNode(true);
  form.parentNode.replaceChild(newForm, form);
  newForm.addEventListener("submit", handleSerieUpdate);

  const editSelector = newForm.querySelector('#rarita-selector-edit');
    if (editSelector) {
        initSingleRaritaSelector(editSelector, 'edit_rarita');
    }
}

// Populate variant edit modal
function populateVariantEditModal(variant) {
  const modal = document.getElementById("editModal");
  const form = modal.querySelector("form");
  
  let categorieStr = '';
  if (variant.categorie) {
    try {
      const categorieArray = JSON.parse(variant.categorie);
      categorieStr = categorieArray.join(', ');
    } catch(e) {
      categorieStr = '';
    }
  }
  
  form.innerHTML = `
    <input type="hidden" name="action" value="updateVariant">
    <input type="hidden" name="id" value="${variant.id}">
    
    <div class="form-group">
      <label for="edit_titolo">Titolo:</label>
      <input type="text" id="edit_titolo" name="titolo" value="${variant.titolo}" required>
    </div>
    
    <div class="form-group">
      <label for="edit_immagine_url">URL Immagine:</label>
      <input type="url" id="edit_immagine_url" name="immagine_url" value="${variant.immagine_url || ''}">
    </div>
    
    <div class="form-group">
      <label for="edit_data_rilascio">Data di Rilascio:</label>
      <input type="date" id="edit_data_rilascio" name="data_rilascio" value="${variant.data_rilascio || ''}">
    </div>
    
    <div class="form-group">
      <label for="edit_costo_medio">Costo Medio (€):</label>
      <input type="number" id="edit_costo_medio" name="costo_medio" value="${variant.costo_medio || 0}" step="0.01" min="0" required>
    </div>
    
    <div class="form-group">
      <label for="edit_stato">Stato:</label>
      <select id="edit_stato" name="stato">
        <option value="completo" ${variant.stato === 'completo' ? 'selected' : ''}>Completo</option>
        <option value="in_corso" ${variant.stato === 'in_corso' ? 'selected' : ''}>In Corso</option>
        <option value="interrotta" ${variant.stato === 'interrotta' ? 'selected' : ''}>Interrotta</option>
      </select>
    </div>
    
    <div class="form-group">
      <label for="edit_categorie">Categorie:</label>
      <input type="text" id="edit_categorie" name="categorie" value="${categorieStr}" placeholder="Es: shonen, horror, azione">
      <div class="categoria-input-info">Separate da virgola</div>
    </div>
    
    <div class="form-group">
      <div class="checkbox-group">
        <input type="checkbox" id="edit_da_prendere_subito" name="da_prendere_subito" ${variant.da_prendere_subito ? 'checked' : ''}>
        <label for="edit_da_prendere_subito">🎯 Da Prendere Subito</label>
      </div>
    </div>
    
    <div class="form-group">
      <div class="checkbox-group">
        <input type="checkbox" id="edit_posseduto" name="posseduto" ${variant.posseduto ? 'checked' : ''}>
        <label for="edit_posseduto">Posseduto</label>
      </div>
    </div>
    
    <div class="form-actions">
      <button type="submit" class="btn">Aggiorna Variant</button>
    </div>
  `;
  
  const newForm = form.cloneNode(true);
  form.parentNode.replaceChild(newForm, form);
  newForm.addEventListener("submit", handleVariantUpdate);
}

// Handle serie update
function handleSerieUpdate(e) {
  e.preventDefault();
  const formData = new FormData(e.target);

  // Verifica se c'è la trasformazione
  const transformToStraniero = formData.get('transform_to_straniero');

  if (transformToStraniero) {
        // Conferma trasformazione
        if (!confirm('Sei sicuro di voler trasformare questo manga in Manga Straniero? Verrà spostato nella sezione dedicata.')) {
            return;
        }
        
        const serieId = formData.get('id');
        const transformData = new FormData();
        transformData.append('id', serieId);
        transformData.append('direzione', 'in_straniero');
        
        fetch(`${getAjaxPath()}?action=trasformaManga`, {
            method: "POST",
            body: transformData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Manga trasformato in Manga Straniero con successo!");
                location.reload();
            } else {
                alert("Errore nella trasformazione: " + (data.message || 'Errore sconosciuto'));
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("Errore nella trasformazione del manga");
        });
    } else {
        // Update normale
        fetch(getAjaxPath(), {
            method: "POST",
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Serie aggiornata!");
                location.reload();
            } else {
                alert("Errore: " + data.message);
            }
        })
        .catch(error => {
            console.error("Error:", error);
            alert("Errore nell'aggiornamento della serie");
        });
    }
  
  fetch(getAjaxPath(), {
    method: "POST",
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert("Serie aggiornata!");
      location.reload();
    } else {
      alert("Errore: " + data.message);
    }
  })
  .catch(error => {
    console.error("Error:", error);
    alert("Errore nell'aggiornamento della serie");
  });
}

// Handle variant update
function handleVariantUpdate(e) {
  e.preventDefault();
  const formData = new FormData(e.target);
  
  fetch(getAjaxPath(), {
    method: "POST",
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert("Variant aggiornata!");
      location.reload();
    } else {
      alert("Errore: " + data.message);
    }
  })
  .catch(error => {
    console.error("Error:", error);
    alert("Errore nell'aggiornamento della variant");
  });
}

// Altre funzioni (volume modal, spin wheel, etc.) rimangono uguali
function openVolumeModal(serieId) {
  fetch(`${getAjaxPath()}?action=getSerie&id=${serieId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        populateVolumeModal(data.serie);
        document.getElementById("editModal").style.display = "none";
        document.getElementById("volumeModal").style.display = "block";
      } else {
        alert("Errore: " + data.message);
      }
    })
    .catch(error => {
      console.error("Error:", error);
      alert("Errore nel caricamento dei volumi");
    });
}

// Populate volume modal
function populateVolumeModal(serie) {
  const modal = document.getElementById("volumeModal");
  const title = modal.querySelector(".modal-title");
  const content = modal.querySelector(".volume-content");
  
  title.textContent = `Gestisci Volumi - ${serie.titolo}`;
  
  let volumeGrid = '<div class="volume-grid">';
  const volumiPosseduti = serie.volumi_dettagli || [];
  
  for (let i = 1; i <= serie.volumi_totali; i++) {
    const volumeInfo = volumiPosseduti.find(v => v.numero_volume === i);
    const isOwned = volumeInfo ? volumeInfo.posseduto : false;
    
    volumeGrid += `
      <div class="volume-item ${isOwned ? 'owned' : 'missing'}" 
           data-volume="${i}" 
           onclick="toggleVolume(this)">
        ${i}
      </div>
    `;
  }
  
  volumeGrid += '</div>';
  
  content.innerHTML = `
    <p>Clicca sui volumi per cambiare il loro stato (posseduto/non posseduto)</p>
    ${volumeGrid}
    <div class="form-actions" style="margin-top: 2rem;">
      <button type="button" class="btn" onclick="saveVolumes(${serie.id})">Salva Modifiche</button>
      <button type="button" class="btn btn-secondary" onclick="selectAllVolumes()">Seleziona Tutti</button>
      <button type="button" class="btn btn-secondary" onclick="deselectAllVolumes()">Deseleziona Tutti</button>
    </div>
  `;
}

// Toggle volume ownership
function toggleVolume(element) {
  if (element.classList.contains("owned")) {
    element.classList.remove("owned");
    element.classList.add("missing");
  } else {
    element.classList.remove("missing");
    element.classList.add("owned");
  }
}

// Select all volumes
function selectAllVolumes() {
  document.querySelectorAll(".volume-item").forEach(vol => {
    vol.classList.remove("missing");
    vol.classList.add("owned");
  });
}

// Deselect all volumes
function deselectAllVolumes() {
  document.querySelectorAll(".volume-item").forEach(vol => {
    vol.classList.remove("owned");
    vol.classList.add("missing");
  });
}

// Save volume changes
function saveVolumes(serieId) {
  const ownedVolumes = [];
  document.querySelectorAll(".volume-item.owned").forEach(vol => {
    ownedVolumes.push(parseInt(vol.dataset.volume));
  });
  
  const formData = new FormData();
  formData.append("serie_id", serieId);
  formData.append("volumi", JSON.stringify(ownedVolumes));
  
  fetch(`${getAjaxPath()}?action=updateVolumi`, {
    method: "POST",
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert("Volumi aggiornati!");
      location.reload();
    } else {
      alert("Errore: " + data.message);
    }
  })
  .catch(error => {
    console.error("Error:", error);
    alert("Errore nell'aggiornamento dei volumi");
  });
}

// Create card element for search results
function createCardElement(item) {
  const card = document.createElement("div");
  card.className = "card";
  
  if (item.tipo === "serie") {
    card.dataset.serieId = item.id;
    if (item.volumi_posseduti === 0) {
      card.classList.add("missing");
    }
  } else if (item.tipo === "variant") {
    card.dataset.variantId = item.id;
    if (!item.posseduto) {
      card.classList.add("missing");
    }
  } else {
    card.dataset.itemType = item.tipo;
    card.dataset.itemId = item.id;
    if (!item.posseduto) {
      card.classList.add("missing");
    }
  }
  
  const imageUrl = item.immagine_url || 'https://via.placeholder.com/250x300?text=No+Image';
  const date = formatDate(item.data);
  
  card.innerHTML = `
    <img src="${imageUrl}" 
         alt="${item.nome || item.titolo}" 
         class="card-image"
         onerror="this.src='https://via.placeholder.com/250x300?text=No+Image'">
    <div class="card-content">
      <h3 class="card-title">${item.nome || item.titolo}</h3>
      <p class="card-date">${date}</p>
      ${item.codice ? `<p class="card-code"><strong>Codice:</strong> ${item.codice}</p>` : ''}
      <div class="card-progress">
        ${getCardProgressHTML(item)}
      </div>
      ${getCardPriceHTML(item)}
      ${getCardLinksHTML(item)}
    </div>
  `;
  
  return card;
}

// Get card progress HTML
function getCardProgressHTML(item) {
  if (item.tipo === "variant") {
    return item.posseduto ? 
      '<span class="complete-badge">Variant Posseduta</span>' : 
      '<span class="card-volumes">Variant non posseduta</span>';
  } else if (item.tipo === "serie") {
    if (item.volumi_posseduti === item.volumi_totali) {
      return '<span class="complete-badge">Serie Completa</span>';
    } else if (item.volumi_posseduti > 0) {
      return `<span class="card-volumes">Volumi: ${item.volumi_posseduti}/${item.volumi_totali} - Mancanti: ${item.volumi_totali - item.volumi_posseduti}</span>`;
    } else {
      return `<span class="card-volumes">Volumi totali: ${item.volumi_totali} - Nessun volume posseduto</span>`;
    }
  } else {
    return item.posseduto ? 
      '<span class="complete-badge">Posseduto</span>' : 
      '<span class="card-volumes">Non posseduto</span>';
  }
}

// Get card price HTML
function getCardPriceHTML(item) {
  let price = 0;
  if (item.tipo === "variant") {
    price = item.costo_medio;
  } else if (item.tipo === "serie") {
    price = item.prezzo_medio;
  } else {
    price = item.prezzo;
  }
  return price > 0 ? `<div class="card-price">${formatPrice(price)}</div>` : '';
}

// Get card links HTML
function getCardLinksHTML(item) {
  if ((item.tipo === "gameboys" || item.tipo === "pokemon_game") && item.links) {
    try {
      const links = JSON.parse(item.links);
      if (links && links.length > 0) {
        const linksHtml = links.map((link, index) => 
          `<a href="${link}" target="_blank" class="card-link">Link ${index + 1}</a>`
        ).join('');
        return `<div class="card-links">${linksHtml}</div>`;
      }
    } catch (e) {
      console.error('Error parsing links:', e);
    }
  }
  return '';
}

// Format date helper
function formatDate(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('it-IT');
}

// Format price helper
function formatPrice(price) {
  return new Intl.NumberFormat('it-IT', {
    style: 'currency',
    currency: 'EUR'
  }).format(price);
}

// Confirm remove function
function confirmRemove(id, tipo, nome) {
  if (confirm(`Sei sicuro di voler rimuovere "${nome}"?`)) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
      <input type="hidden" name="action" value="remove">
      <input type="hidden" name="id" value="${id}">
      <input type="hidden" name="tipo" value="${tipo}">
    `;
    document.body.appendChild(form);
    form.submit();
  }
}

function initRaritaSelectors() {
    // Selettore nel form aggiungi
    const addSelector = document.getElementById('rarita-selector-add');
    if (addSelector) {
        initSingleRaritaSelector(addSelector, 'rarita_value');
    }
    
    // Gestione click su stelle (anche per modali dinamici)
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('star')) {
            const selector = e.target.closest('.rarita-selector');
            if (selector) {
                const inputId = selector.getAttribute('data-input');
                handleStarClick(e.target, selector, inputId);
            }
        }
    });
}

/**
 * Inizializza un singolo selettore di rarità
 */
function initSingleRaritaSelector(selector, inputId) {
    selector.setAttribute('data-input', inputId);
    
    const stars = selector.querySelectorAll('.star');
    stars.forEach(star => {
        // Hover effect
        star.addEventListener('mouseenter', function() {
            const value = parseInt(this.getAttribute('data-value'));
            highlightStars(selector, value, true);
        });
    });
    
    selector.addEventListener('mouseleave', function() {
        const input = document.getElementById(inputId);
        const currentValue = input ? parseInt(input.value) || 0 : 0;
        highlightStars(selector, currentValue, false);
    });
}

/**
 * Gestisce il click su una stella
 */
function handleStarClick(star, selector, inputId) {
    const value = parseInt(star.getAttribute('data-value'));
    const input = document.getElementById(inputId);
    
    if (!input) return;
    
    // Se clicco sulla stessa stella già selezionata, resetto
    const currentValue = parseInt(input.value) || 0;
    const newValue = (currentValue === value) ? 0 : value;
    
    input.value = newValue || '';
    highlightStars(selector, newValue, false);
    
    // Aggiorna testo rarità attuale se esiste
    const raritaCurrent = selector.parentElement.querySelector('.rarita-current');
    if (raritaCurrent) {
        if (newValue > 0) {
            raritaCurrent.innerHTML = `Rarità: <strong style="color: ${getRaritaColor(newValue)}">${getRaritaName(newValue)}</strong>`;
        } else {
            raritaCurrent.innerHTML = 'Nessuna rarità impostata';
        }
    }
}

/**
 * Evidenzia le stelle in base al valore
 */
function highlightStars(selector, value, isHover) {
  
    const stars = selector.querySelectorAll('.star');
    
    stars.forEach((star, index) => {
        const starValue = index + 1;
        star.classList.remove('active', 'hover', 'rarita-1', 'rarita-2', 'rarita-3', 'rarita-4', 'rarita-5');
        
        if (starValue <= value) {
            star.classList.add(isHover ? 'hover' : 'active');
            star.classList.add(`rarita-${value}`);
        }
    });
    
}

/**
 * Ottiene il colore in base alla rarità
 */
function getRaritaColor(rarita) {
    const colors = {
        1: '#27ae60',  // Verde
        2: '#2ecc71',  // Verde chiaro
        3: '#f1c40f',  // Giallo
        4: '#e67e22',  // Arancione
        5: '#e74c3c'   // Rosso
    };
    return colors[rarita] || '#95a5a6';
}

/**
 * Ottiene il nome della rarità
 */
function getRaritaName(rarita) {
    const names = {
        1: 'Comune',
        2: 'Non Comune',
        3: 'Raro',
        4: 'Molto Raro',
        5: 'Leggendario'
    };
    return names[rarita] || 'Nessuna';
}

/**
 * Aggiorna la rarità di una serie tramite AJAX
 */
function updateSerieRarita(serieId, rarita) {
    const formData = new FormData();
    formData.append('serie_id', serieId);
    formData.append('rarita', rarita || '');
    
    return fetch(`${getAjaxPath()}?action=updateRarita`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            throw new Error(data.message || 'Errore aggiornamento rarità');
        }
        return data;
    });
}

