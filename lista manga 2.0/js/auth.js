/**
 * Sistema di autenticazione semplice
 * Usa localStorage per mantenere lo stato di login
 * La password è verificata lato client (per semplicità)
 */



/*
Per reset, scrivere nella console: 
localStorage.removeItem('manga_auth')
location.reload()
*/

// CONFIGURAZIONE
const AUTH_CONFIG = {
    password: 'suca',  // ← CAMBIA QUESTA PASSWORD
    storageKey: 'manga_auth',
    sessionDuration: 24 * 60 * 60 * 1000  // 24 ore in millisecondi
};

// Verifica autenticazione all'avvio
document.addEventListener('DOMContentLoaded', function() {
    checkAuth();
});

/**
 * Verifica se l'utente è autenticato
 */
function checkAuth() {
    const authData = localStorage.getItem(AUTH_CONFIG.storageKey);
    
    if (!authData) {
        showAuthModal();
        return;
    }
    
    try {
        const data = JSON.parse(authData);
        const now = new Date().getTime();
        
        // Verifica se la sessione è scaduta (24 ore)
        if (now - data.timestamp > AUTH_CONFIG.sessionDuration) {
            localStorage.removeItem(AUTH_CONFIG.storageKey);
            showAuthModal();
            return;
        }
        
        // Autenticazione valida
        hideAuthModal();
        
    } catch (e) {
        localStorage.removeItem(AUTH_CONFIG.storageKey);
        showAuthModal();
    }
}

/**
 * Mostra il modal di autenticazione
 */
function showAuthModal() {
    // Blocca lo scroll della pagina
    document.body.style.overflow = 'hidden';
    
    // Crea il modal se non esiste
    let modal = document.getElementById('authModal');
    if (!modal) {
        modal = createAuthModal();
        document.body.appendChild(modal);
    }
    
    modal.style.display = 'flex';
    
    // Focus sul campo password
    setTimeout(() => {
        const passwordInput = document.getElementById('authPassword');
        if (passwordInput) passwordInput.focus();
    }, 100);
}

/**
 * Nasconde il modal di autenticazione
 */
function hideAuthModal() {
    const modal = document.getElementById('authModal');
    if (modal) {
        modal.style.display = 'none';
    }
    document.body.style.overflow = '';
}

/**
 * Crea l'HTML del modal di autenticazione
 */
function createAuthModal() {
    const modal = document.createElement('div');
    modal.id = 'authModal';
    modal.className = 'auth-modal';
    
    modal.innerHTML = `
        <div class="auth-modal-content">
            <div class="auth-header">
                <div class="auth-icon">🔒</div>
                <h2>Accesso Richiesto</h2>
                <p>Inserisci la password per accedere alla collezione</p>
            </div>
            
            <form id="authForm" class="auth-form">
                <div class="auth-input-group">
                    <input 
                        type="password" 
                        id="authPassword" 
                        placeholder="Inserisci password..."
                        autocomplete="off"
                        required>
                    <button type="submit" class="auth-btn">
                        Accedi
                    </button>
                </div>
                <div id="authError" class="auth-error"></div>
            </form>
            
            <div class="auth-footer">
                <small>La sessione durerà 24 ore</small>
            </div>
        </div>
    `;
    
    // Gestione submit del form
    modal.querySelector('#authForm').addEventListener('submit', handleAuth);
    
    // Previeni chiusura cliccando fuori
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            e.preventDefault();
            shakeModal();
        }
    });
    
    // Previeni ESC per chiudere
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const authModal = document.getElementById('authModal');
            if (authModal && authModal.style.display === 'flex') {
                e.preventDefault();
                shakeModal();
            }
        }
    });
    
    return modal;
}

/**
 * Gestisce il tentativo di login
 */
function handleAuth(e) {
    e.preventDefault();
    
    const passwordInput = document.getElementById('authPassword');
    const errorDiv = document.getElementById('authError');
    const password = passwordInput.value;
    
    // Rimuovi errore precedente
    errorDiv.textContent = '';
    errorDiv.style.display = 'none';
    
    // Verifica password
    if (password === AUTH_CONFIG.password) {
        // Password corretta - salva in localStorage
        const authData = {
            authenticated: true,
            timestamp: new Date().getTime()
        };
        localStorage.setItem(AUTH_CONFIG.storageKey, JSON.stringify(authData));
        
        // Animazione di successo
        showSuccessAnimation();
        
        // Nascondi modal dopo animazione
        setTimeout(() => {
            hideAuthModal();
        }, 1000);
        
    } else {
        // Password errata
        showError('❌ Password errata. Riprova.');
        passwordInput.value = '';
        passwordInput.focus();
        shakeModal();
    }
}

/**
 * Mostra messaggio di errore
 */
function showError(message) {
    const errorDiv = document.getElementById('authError');
    if (errorDiv) {
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
    }
}

/**
 * Animazione shake per errore
 */
function shakeModal() {
    const content = document.querySelector('.auth-modal-content');
    if (content) {
        content.classList.add('shake');
        setTimeout(() => {
            content.classList.remove('shake');
        }, 500);
    }
}

/**
 * Animazione di successo
 */
function showSuccessAnimation() {
    const content = document.querySelector('.auth-modal-content');
    const authIcon = document.querySelector('.auth-icon');
    
    if (authIcon) {
        authIcon.textContent = '✅';
        authIcon.style.transform = 'scale(1.5)';
    }
    
    if (content) {
        content.style.borderColor = '#27ae60';
    }
    
    const errorDiv = document.getElementById('authError');
    if (errorDiv) {
        errorDiv.textContent = '✅ Accesso consentito!';
        errorDiv.style.display = 'block';
        errorDiv.style.color = '#27ae60';
    }
}

// Verifica periodicamente se la sessione è scaduta (ogni 5 minuti)
setInterval(() => {
    checkAuth();
}, 5 * 60 * 1000);