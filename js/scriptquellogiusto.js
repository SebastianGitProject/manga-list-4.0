// Gestione navigazione
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('nav a');
    const sections = document.querySelectorAll('.section');
    
    // Funzione per mostrare una sezione
    function showSection(sectionId) {
        sections.forEach(section => {
            section.classList.remove('active');
        });
        
        navLinks.forEach(link => {
            link.classList.remove('active');
        });
        
        document.getElementById(sectionId).classList.add('active');
        document.querySelector(`nav a[href="#${sectionId}"]`).classList.add('active');
    }
    
    // Event listeners per i link di navigazione
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const sectionId = this.getAttribute('href').substring(1);
            showSection(sectionId);
        });
    });
    
    // Mostra la prima sezione di default
    showSection('collezione');
    
    // Gestione form tipo (serie/variant)
    const tipoSelect = document.getElementById('tipo');
    const costoGroup = document.getElementById('costo-group');
    const possedutoGroup = document.getElementById('posseduto-group');
    const volumiPosseduti = document.getElementById('volumi_posseduti');
    
    if (tipoSelect) {
        tipoSelect.addEventListener('change', function() {
            if (this.value === 'variant') {
                costoGroup.classList.remove('hidden');
                possedutoGroup.classList.remove('hidden');
                volumiPosseduti.max = '1';
                volumiPosseduti.value = Math.min(volumiPosseduti.value, 1);
            } else {
                costoGroup.classList.add('hidden');
                possedutoGroup.classList.add('hidden');
                volumiPosseduti.max = '999';
            }
        });
    }
    
    // Gestione modale per dettagli serie
    const modal = document.getElementById('serieModal');
    const closeBtn = document.querySelector('.close');
    
    if (modal && closeBtn) {
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
        });
        
        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    }
    
    // Gestione click sulle card serie
    document.addEventListener('click', function(e) {
        const card = e.target.closest('.card[data-serie-id]');
        if (card) {
            const serieId = card.getAttribute('data-serie-id');
            showSerieDetails(serieId);
        }
    });
    
    // Gestione conferma rimozione
    window.confirmRemove = function(id, tipo, titolo) {
        if (confirm(`Sei sicuro di voler rimuovere "${titolo}" dalla collezione?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'remove';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = id;
            
            const tipoInput = document.createElement('input');
            tipoInput.type = 'hidden';
            tipoInput.name = 'tipo';
            tipoInput.value = tipo;
            
            form.appendChild(actionInput);
            form.appendChild(idInput);
            form.appendChild(tipoInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    };
    
    // Gestione form di aggiunta
    const addForm = document.getElementById('addForm');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            const tipo = document.getElementById('tipo').value;
            const titolo = document.getElementById('titolo').value.trim();
            const volumiTotali = parseInt(document.getElementById('volumi_totali').value);
            const volumiPosseduti = parseInt(document.getElementById('volumi_posseduti').value);
            
            if (!titolo) {
                alert('Il titolo è obbligatorio');
                e.preventDefault();
                return;
            }
            
            if (volumiPosseduti > volumiTotali) {
                alert('I volumi posseduti non possono essere maggiori dei volumi totali');
                e.preventDefault();
                return;
            }
            
            if (tipo === 'variant' && volumiTotali > 1) {
                alert('Una variant può avere al massimo 1 volume');
                e.preventDefault();
                return;
            }
        });
    }
});

// Funzione per mostrare dettagli serie
function showSerieDetails(serieId) {
    // Correzione del percorso per il file AJAX
    fetch(`../db/ajax.php?action=getSerie&id=${serieId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const serie = data.serie;
                const modal = document.getElementById('serieModal');
                const modalTitle = document.getElementById('modalTitle');
                const modalImage = document.getElementById('modalImage');
                const modalDate = document.getElementById('modalDate');
                const modalProgress = document.getElementById('modalProgress');
                const volumeGrid = document.getElementById('volumeGrid');
                
                modalTitle.textContent = serie.titolo;
                modalImage.src = serie.immagine_url || 'https://via.placeholder.com/200x300?text=No+Image';
                modalImage.alt = serie.titolo;
                modalDate.textContent = formatDate(serie.data_pubblicazione);
                
                const mancanti = serie.volumi_totali - serie.volumi_posseduti;
                if (mancanti === 0) {
                    modalProgress.innerHTML = '<span class="complete-badge">Serie Completa</span>';
                } else {
                    modalProgress.innerHTML = `<span class="card-volumes">Volumi: ${serie.volumi_posseduti}/${serie.volumi_totali} - Mancanti: ${mancanti}</span>`;
                }
                
                // Genera griglia volumi
                volumeGrid.innerHTML = '';
                for (let i = 1; i <= serie.volumi_totali; i++) {
                    const volumeItem = document.createElement('div');
                    volumeItem.className = `volume-item ${i <= serie.volumi_posseduti ? 'owned' : 'missing'}`;
                    volumeItem.textContent = i;
                    volumeGrid.appendChild(volumeItem);
                }
                
                modal.style.display = 'block';
            } else {
                alert('Errore: ' + (data.message || 'Serie non trovata'));
            }
        })
        .catch(error => {
            console.error('Errore dettagliato:', error);
            alert('Errore nel caricamento dei dettagli: ' + error.message);
        });
}

// Funzione per formattare date
function formatDate(dateString) {
    if (!dateString) return 'Non specificata';
    const date = new Date(dateString);
    return date.toLocaleDateString('it-IT');
}

// Funzione per nascondere messaggi dopo qualche secondo
setTimeout(function() {
    const messages = document.querySelectorAll('.message');
    messages.forEach(message => {
        message.style.opacity = '0';
        setTimeout(() => message.remove(), 500);
    });
}, 3000);