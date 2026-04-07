const PLACEHOLDER_AVATAR = 'assets/img/user.png';

// ========================================
// HELPER: ACCESO SEGURO A ELEMENTOS DOM
// ========================================
function SSID(id) {
    const el = document.getElementById(id);
    if (!el) {
        console.warn(`[DOM] Elemento no encontrado: #${id}`);
        // Devolver un proxy que no haga nada si no existe
        return new Proxy({}, {
            get: () => SSID.noop,
            set: () => true
        });
    }
    return el;
}
SSID.noop = { 
    innerHTML: '', innerText: '', textContent: '', value: '', style: {}, 
    addEventListener: () => {}, removeEventListener: () => {},
    appendChild: () => {}, removeChild: () => {},
    onclick: null, onchange: null, oninput: null, onsubmit: null,
    reset: () => {}, submit: () => {}, focus: () => {}, blur: () => {}, click: () => {}
};

// ========================================
// INICIALIZACIÓN: APLICAR OPTIMIZACIONES GUARDADAS
// ========================================
function inicializarOptimizaciones() {
    const nivelOptimizacion = localStorage.getItem('optimizacion_nivel');
    
    if (nivelOptimizacion) {
        console.log('[Optimización] Nivel aplicado al cargar:', nivelOptimizacion);
        
        // Crear estilos de optimización
        let styleID = document.getElementById('optimizacion-estilos-init');
        if (styleID) styleID.remove();

        const animacionesEnabled = localStorage.getItem('animaciones_enabled') === 'true';
        const shadersEnabled = localStorage.getItem('shaders_enabled') === 'true';

        const style = document.createElement('style');
        style.id = 'optimizacion-estilos-init';
        style.textContent = `
            /* Preservar interactividad en todos los niveles */
            input[type="radio"], input[type="checkbox"], button, a, select, textarea, input[type="text"], input[type="email"], input[type="number"] {
                pointer-events: auto !important;
            }

            /* Desactivar transiciones suaves si está optimizado */
            ${!animacionesEnabled ? `
                body, body * {
                    transition: none !important;
                    animation: none !important;
                }
            ` : ''}
            
            /* Desactivar efectos visuales complejos */
            ${!shadersEnabled ? `
                .btn:hover, button:hover { 
                    transform: none !important;
                }
                input:focus, select:focus, textarea:focus { 
                    box-shadow: inset 0 0 0 1px #1976D2 !important;
                }
                .tabla-wrap { 
                    box-shadow: none !important; 
                }
            ` : ''}
            
            /* Reducir sombras en optimización agresiva */
            ${nivelOptimizacion === 'agresivo' ? `
                .sombra, .card, .modal-content {
                    box-shadow: none !important;
                }
                * {
                    filter: none !important;
                }
            ` : ''}
        `;
        document.head.appendChild(style);
    }
}

// Ejecutar al cargar
document.addEventListener('DOMContentLoaded', inicializarOptimizaciones);

// Monitor de conexión - Verificar BD cada 30 segundos
let connectionMonitor = null;
let isConnectionDown = false;
let connectionCheckInProgress = false;

function startConnectionMonitor() {
    clearInterval(connectionMonitor);
    connectionMonitor = setInterval(async () => {
        if (connectionCheckInProgress) return;
        connectionCheckInProgress = true;
        
        try {
            const response = await Promise.race([
                fetch('api/healthcheck.php?t=' + Date.now(), {credentials:'include'}),
                new Promise((_, reject) => setTimeout(() => reject(new Error('timeout')), 5000))
            ]);
            
            const data = await response.json();
            
            if (data.status === 'ok' || data.status === 'degraded') {
                if (isConnectionDown) {
                    isConnectionDown = false;
                    Swal.close();
                    Notificador.success('✅ Conexión recuperada', 'El sistema está nuevamente operativo');
                    // Recargar datos
                    if (window.ventas && window.ventas.loadDebtors) window.ventas.loadDebtors();
                    if (window.inventario && window.inventario.load) window.inventario.load();
                    if (window.registros && window.registros.load) window.registros.load();
                }
            } else {
                if (!isConnectionDown) {
                    isConnectionDown = true;
                    Notificador.connectionError();
                }
            }
        } catch (e) {
            if (!isConnectionDown) {
                isConnectionDown = true;
                Notificador.connectionError();
            }
        } finally {
            connectionCheckInProgress = false;
        }
    }, 30000);
}

const moneyFmt = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });
const debounce = (func, delay) => { let t; return (...args) => { clearTimeout(t); t = setTimeout(() => func.apply(this, args), delay); }; };

// Grupos de la Región 4
const GRUPOS_REGION_4 = [
    'AMALUCAN', 'APIZACO', 'BUENAVISTA', 'GUADALUPE HIDALGO', 'LOMAS DEL SUR',
    'SAN BALTAZAR', 'SAN FELIPE', 'TLAXCALA', 'CHOLULA', 'ZACATELCO',
    'SANTA ANA', 'AMOZOC', 'HUAMANTLA', 'CONTLA'
];

const OTRAS_REGIONES = [
    'Region 1 CDMX', 'Region 2 EDO MEX', 'Region 3', 'Region 5', 'Region 6', 'Region 7', 'Region 8'
];

const TODOS_GRUPOS = [...GRUPOS_REGION_4, ...OTRAS_REGIONES];

// Caché de productos más vendidos en localStorage
const ProductCache = {
    KEY: 'productos_cache',
    TOP_KEY: 'productos_top_vendidos',
    
    save: (productos) => {
        try {
            localStorage.setItem(ProductCache.KEY, JSON.stringify({
                data: productos,
                timestamp: Date.now()
            }));
        } catch (e) { console.error('Error guardando caché:', e); }
    },
    
    get: () => {
        try {
            const cached = localStorage.getItem(ProductCache.KEY);
            if (!cached) return null;
            const parsed = JSON.parse(cached);
            // Caché válido por 5 minutos
            if (Date.now() - parsed.timestamp > 5 * 60 * 1000) return null;
            return parsed.data;
        } catch (e) { return null; }
    },
    
    saveTopVendidos: (productos) => {
        try {
            localStorage.setItem(ProductCache.TOP_KEY, JSON.stringify(productos.slice(0, 20)));
        } catch (e) { console.error('Error guardando top vendidos:', e); }
    },
    
    getTopVendidos: () => {
        try {
            const cached = localStorage.getItem(ProductCache.TOP_KEY);
            return cached ? JSON.parse(cached) : [];
        } catch (e) { return []; }
    }
};




window.addEventListener('load', startConnectionMonitor);



window.addEventListener('error', function(e) {
    console.error('Error global capturado:', e.error);
    logError('JavaScript Error', e.error.message, e.error.stack);
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('Promise rechazada sin manejar:', e.reason);
    logError('Unhandled Promise Rejection', e.reason);
});

function logError(tipo, mensaje, detalles = '') {
    try {
        const errorData = {
            tipo: tipo,
            mensaje: mensaje,
            detalles: detalles,
            url: window.location.href,
            timestamp: new Date().toISOString(),
            userAgent: navigator.userAgent
        };

        if (typeof fetch !== 'undefined') {
            fetch('api/api_admin.php?accion=log_error', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'include',
                body: JSON.stringify(errorData)
            }).catch(err => console.error('No se pudo registrar el error:', err));
        }

        console.error(`[${tipo}]`, mensaje, detalles);
    } catch (err) {
        console.error('Error al registrar error:', err);
    }
}

const Notificador = {
    escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    },
    getConfig() {
        const esDark = document.body.getAttribute('data-theme') === 'dark';
        return { bg: esDark ? '#1e1e1e' : '#fff', color: esDark ? '#e0e0e0' : '#333' };
    },
    // Reproducir sonidos usando Web Audio API
    playSound(type = 'success') {
        try {
            // Primero intentar con Web Audio API
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gain = audioContext.createGain();
            
            oscillator.connect(gain);
            gain.connect(audioContext.destination);
            
            const frecuencias = {
                'success': 800,
                'error': 400,
                'warning': 600,
                'delete': 300
            };
            
            const duraciones = {
                'success': 100,
                'error': 150,
                'warning': 200,
                'delete': 300
            };
            
            const freq = frecuencias[type] || 800;
            const duration = duraciones[type] || 100;
            
            oscillator.frequency.value = freq;
            gain.gain.setValueAtTime(0.3, audioContext.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + duration / 1000);
            
            oscillator.start(audioContext.currentTime);
            oscillator.stop(audioContext.currentTime + duration / 1000);
        } catch (e) {
            // Fallback: intentar con elemento audio HTML
            try {
                const audioEl = document.getElementById('notif-sound');
                if (audioEl) {
                    audioEl.currentTime = 0;
                    audioEl.play().catch(err => console.log('No se pudo reproducir audio:', err));
                }
            } catch (err) {
                console.log('Audio no disponible');
            }
        }
    },
    mostrarToast(titulo, mensaje, tipo = 'info', duracion = 2500) {
        let toastContainer = document.getElementById('toast-container');
        const esDark = document.body.getAttribute('data-theme') === 'dark';
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'toast-container';
            toastContainer.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                display: flex;
                flex-direction: column;
                gap: 10px;
                max-width: 400px;
            `;
            document.body.appendChild(toastContainer);
        }

        while (toastContainer.children.length >= 5) {
            toastContainer.removeChild(toastContainer.firstElementChild);
        }

        const toast = document.createElement('div');
        const colores = {
            'success': '#4caf50',
            'error': '#f44336',
            'warning': '#ff9800',
            'info': '#2196f3'
        };
        const iconos = {
            'success': 'fa-circle-check',
            'error': 'fa-circle-xmark',
            'warning': 'fa-triangle-exclamation',
            'info': 'fa-circle-info'
        };
        const color = colores[tipo] || colores.info;
        const icono = iconos[tipo] || iconos.info;
        const bg = esDark ? '#111827' : '#ffffff';
        const textColor = esDark ? '#e5e7eb' : '#111827';
        const subText = esDark ? '#cbd5e1' : '#4b5563';
        
        toast.style.cssText = `
            background: ${bg};
            border-left: 4px solid ${color};
            padding: 16px;
            border-radius: 12px;
            box-shadow: 0 10px 24px rgba(0,0,0,0.22);
            animation: slideInRight 0.3s ease-out;
            display: flex;
            justify-content: space-between;
            align-items: center;
            min-width: 320px;
            border: 1px solid ${esDark ? 'rgba(148,163,184,0.18)' : 'rgba(17,24,39,0.08)'};
        `;
        toast.setAttribute('role', 'status');
        toast.setAttribute('aria-live', 'polite');

        const contenido = document.createElement('div');
        contenido.style.cssText = `flex: 1;`;
        const safeTitle = this.escapeHtml(titulo || 'Notificacion');
        const safeMsg = this.escapeHtml(mensaje || '');
        contenido.innerHTML = `
            <div style="font-weight: 800; color: ${textColor}; font-size: 0.94rem; display:flex; align-items:center; gap:8px;">
                <i class="fas ${icono}" style="color:${color};"></i>
                <span>${safeTitle}</span>
            </div>
            ${safeMsg ? `<div style="color: ${subText}; font-size: 0.84rem; margin-top: 5px;">${safeMsg}</div>` : ''}
        `;

        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '×';
        closeBtn.style.cssText = `
            background: none;
            border: none;
            font-size: 22px;
            color: ${subText};
            cursor: pointer;
            padding: 0 0 0 16px;
            line-height: 1;
        `;
        closeBtn.onclick = () => {
            toast.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => toast.remove(), 300);
        };

        toast.appendChild(contenido);
        toast.appendChild(closeBtn);
        toastContainer.appendChild(toast);

        // Auto-cerrar después de la duración
        const autoCloseTimer = setTimeout(() => {
            if (toast.parentElement) {
                toast.style.animation = 'slideOutRight 0.3s ease-out';
                setTimeout(() => {
                    if (toast.parentElement) toast.remove();
                }, 300);
            }
        }, duracion);

        // Cancelar auto-close si el usuario hace hover
        toast.onmouseenter = () => clearTimeout(autoCloseTimer);

        return toast;
    },
    success(t, tx, sonido = true) {
        if (sonido) this.playSound('success');
        this.mostrarToast(t, tx, 'success', 2000);
    },
    warning(t, tx) {
        this.playSound('warning');
        this.mostrarToast(t, tx, 'warning', 2500);
    },
    info(t, tx = '') {
        if (typeof Swal !== 'undefined' && Swal.fire) {
            const c = this.getConfig();
            return Swal.fire({
                title: t || 'Información',
                html: tx || '',
                icon: 'info',
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#0d47a1',
                background: c.bg,
                color: c.color
            });
        }
        this.mostrarToast(t, tx, 'info', 3000);
    },
    error(t, tx) {
        this.playSound('error');
        logError('User Notification', t, tx);
        this.mostrarToast(t, tx, 'error', 3500);
    },
    connectionError() {
        this.playSound('error');
        this.mostrarToast('⚠️ Sin conexión', 'Reintentando conexión con el servidor...', 'error', 5000);
    },
    async confirm(t, tx='') {
        if (typeof Swal !== 'undefined' && Swal.fire) {
            const c = this.getConfig();
            return (await Swal.fire({
                title:t, 
                text:tx, 
                icon:'warning', 
                showCancelButton:true, 
                confirmButtonColor:'#0d47a1', 
                cancelButtonColor:'#d33', 
                background:c.bg, 
                color:c.color,
                allowOutsideClick: false,
                willClose: () => {
                    // Asegurar que se cierre
                }
            })).isConfirmed;
        } else {
            return confirm((t || '') + '\n' + (tx || ''));
        }
    },
    // Nueva función para confirmación de eliminación con detalles
    async confirmDelete(nombre, detalle = '', extra = '') {
        this.playSound('warning');
        if (typeof Swal !== 'undefined' && Swal.fire) {
            const c = this.getConfig();
            const html = `
                <div style="text-align:left; padding:20px; background:rgba(244,67,54,0.1); border-radius:8px;">
                    <p><strong>¿Está seguro que desea eliminar?</strong></p>
                    <p style="margin:10px 0; font-weight:bold; color:var(--color-primario);">📌 ${nombre}</p>
                    ${detalle ? `<p style="margin:5px 0; color:#666; font-size:0.9rem;">${detalle}</p>` : ''}
                    ${extra ? `<p style="margin:5px 0; color:#f44336; font-size:0.9rem;"><i class="fas fa-exclamation-triangle"></i> ${extra}</p>` : ''}
                    <p style="margin-top:15px; color:#999; font-size:0.85rem;">Esta acción no se puede deshacer.</p>
                </div>
            `;
            return (await Swal.fire({
                title:'⚠️ Confirmar Eliminación',
                html:html,
                icon:'warning',
                showCancelButton:true,
                confirmButtonColor:'#f44336',
                cancelButtonColor:'#999',
                confirmButtonText:'Sí, eliminar',
                cancelButtonText:'Cancelar',
                background:c.bg,
                color:c.color,
                allowOutsideClick: false,
                willClose: () => {
                    // Asegurar que se cierre
                }
            })).isConfirmed;
        } else {
            return confirm(`¿Eliminar: ${nombre}?\n${detalle}`);
        }
    }
};

// Sistema de validación en tiempo real
const Validador = {
    // Validar un campo individual
    validarCampo(campo) {
        if (!campo) return true;
        
        const esRequerido = campo.hasAttribute('required');
        const valor = campo.value.trim();
        const tipo = campo.type;
        
        // Remover clases previas
        campo.classList.remove('campo-error', 'campo-valido', 'shake');
        
        // Eliminar mensaje de error anterior
        const msgError = campo.parentElement.querySelector('.input-error-msg');
        if (msgError) msgError.remove();
        
        // Si el campo no tiene valor y es requerido
        if (esRequerido && !valor) {
            campo.classList.add('campo-error', 'shake');
            this.mostrarErrorCampo(campo, 'Este campo es obligatorio');
            return false;
        }
        
        // Validaciones específicas por tipo
        if (valor) {
            if (tipo === 'number' && isNaN(valor)) {
                campo.classList.add('campo-error');
                this.mostrarErrorCampo(campo, 'Debe ser un número válido');
                return false;
            }
            
            if (tipo === 'email' && !this.esEmailValido(valor)) {
                campo.classList.add('campo-error');
                this.mostrarErrorCampo(campo, 'Email no válido');
                return false;
            }
            
            campo.classList.add('campo-valido');
        }
        
        return true;
    },
    
    // Mostrar mensaje de error bajo el campo
    mostrarErrorCampo(campo, mensaje) {
        const div = document.createElement('div');
        div.className = 'input-error-msg show';
        div.textContent = '⚠️ ' + mensaje;
        campo.parentElement.appendChild(div);
    },
    
    // Validar email
    esEmailValido(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },
    
    // Validar formulario completo
    validarFormulario(formulario) {
        if (!formulario) return true;
        
        const campos = formulario.querySelectorAll('[required]');
        let todosValidos = true;
        
        campos.forEach(campo => {
            if (!this.validarCampo(campo)) {
                todosValidos = false;
            }
        });
        
        return todosValidos;
    },
    
    // Agregar listeners a un formulario para validación en tiempo real
    agregarValidacionTiempoReal(formulario) {
        if (!formulario) return;
        
        const campos = formulario.querySelectorAll('input, select, textarea');
        
        campos.forEach(campo => {
            // Validar al perder foco
            campo.addEventListener('blur', () => {
                this.validarCampo(campo);
            });
            
            // Limpiar error mientras el usuario escribe
            campo.addEventListener('input', () => {
                if (campo.classList.contains('campo-error')) {
                    campo.classList.remove('campo-error', 'shake');
                    const msgError = campo.parentElement.querySelector('.input-error-msg');
                    if (msgError) msgError.remove();
                }
            });
        });
    }
};


function fetchWithCSRF(url, opts = {}, retries = 3) {
    opts.headers = opts.headers || {};
    opts.credentials = 'include';
    const token = sessionStorage.getItem('csrf_token');
    if (token) {
        opts.headers['X-CSRF-Token'] = token;
    }

    const attemptFetch = (attemptsLeft) => {
        return fetch(url, opts)
            .then(async response => {
                if (!response.ok) {
                    let bodyText = '';
                    try { bodyText = await response.clone().text(); } catch (e) { bodyText = ''; }
                    const detalle = bodyText ? `${url} | ${bodyText.substring(0, 500)}` : url;
                    logError('HTTP Error', `${response.status} ${response.statusText}`, detalle);
                    if (response.status >= 500) {
                        if (attemptsLeft > 0) {
                            const delay = Math.pow(2, retries - attemptsLeft) * 1000;
                            console.warn(`Server error ${response.status}, reintentando en ${delay}ms...`);
                            return new Promise(resolve => setTimeout(resolve, delay))
                                .then(() => attemptFetch(attemptsLeft - 1));
                        }
                    }
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response;
            })
            .catch(error => {
                const isNetworkError = error.message.includes('Failed to fetch') || 
                                     error.message.includes('NetworkError') ||
                                     error.message.includes('TypeError');
                
                if (attemptsLeft <= 0) {
                    logError('Fetch Error (sin reintentos)', error.message, url);
                    throw error;
                }

                const baseDelay = isNetworkError ? 2000 : 1000;
                const delay = Math.pow(2, retries - attemptsLeft) * baseDelay;
                console.warn(`Fetch falló (${error.message}), reintentando en ${delay}ms... (${attemptsLeft} intentos restantes)`);
                
                if (isNetworkError && attemptsLeft === retries) {
                    Notificador.connectionError();
                }
                
                return new Promise(resolve => setTimeout(resolve, delay))
                    .then(() => attemptFetch(attemptsLeft - 1));
            });
    };
    
    return attemptFetch(retries);
}

function appendCsrfToFormData(fd) {
    const token = sessionStorage.getItem('csrf_token');
    if (token) fd.append('csrf_token', token);
}

function initNav() {
    const items = document.querySelectorAll('.nav-item');
    const tabs = document.querySelectorAll('.tab-content');
    items.forEach(i => {
        i.addEventListener('click', (e) => {
            const targetId = i.getAttribute('data-tab');
            if (!targetId) return; 
            e.preventDefault();
            items.forEach(n => n.classList.remove('active'));
            tabs.forEach(t => { t.classList.remove('active'); t.style.setProperty('display', 'none', 'important'); });
            i.classList.add('active');
            const activeTab = document.getElementById(targetId);
            if(activeTab) { activeTab.classList.add('active'); activeTab.style.removeProperty('display'); }
            
            if(targetId==='ventas') ventas.init();
            if(targetId==='inventario') inventario.load();
            if(targetId==='registros') registros.load();
            if(targetId==='septimas') septimas.load();
            if(targetId==='config') { 
                usuarios.load();
                migracion.verificarEstado();
            }
            if(targetId==='errores') admin.load();
            if(targetId==='cuentas') cuentas.load();
            if(targetId==='cortes') { cortes.load(); cortes.actualizarEstado(); }
        });
    });
}





const modTema = {
    init: () => {
        const btn = document.getElementById('btn-theme-toggle');
        const body = document.body;
        const icon = btn ? btn.querySelector('i') : null;
        
        const set = (dark) => {
            if(dark) { 
                body.setAttribute('data-theme', 'dark'); 
                localStorage.setItem('theme', 'dark'); 
                if(icon) icon.className='fas fa-sun'; 
            } else { 
                body.removeAttribute('data-theme'); 
                localStorage.setItem('theme', 'light'); 
                if(icon) icon.className='fas fa-moon'; 
            }
        };
        
        // Modo automático si no hay preferencia guardada
        const savedTheme = localStorage.getItem('theme');
        if (!savedTheme || savedTheme === 'auto') {
            const hora = new Date().getHours();
            const esNoche = hora >= 19 || hora < 7; // 7pm a 7am = oscuro
            set(esNoche);
            localStorage.setItem('theme', 'auto');
        } else if(savedTheme === 'dark') {
            set(true);
        }
        
        if(btn) btn.onclick = () => {
            const isDark = !body.hasAttribute('data-theme');
            set(isDark);
        };
    }
};






const modColor = {
    init: () => {
        const colorInput = document.getElementById('color-picker-input');
        
        if (!colorInput) return;

        const savedColor = localStorage.getItem('primary_color') || '#0d47a1';
        colorInput.value = savedColor;
        modColor.aplicarColor(savedColor);

        colorInput.addEventListener('input', (e) => {
            const color = e.target.value;
            modColor.aplicarColor(color);
            localStorage.setItem('primary_color', color);

            if (typeof Notificador !== 'undefined') {
                Notificador.success('Color aplicado', 'Tu tema ha sido personalizado');
            }
        });
    },
    
    aplicarColor: (color) => {

        document.documentElement.style.setProperty('--color-primario', color);

        const rgb = modColor.hexToRgb(color);
        const darker = modColor.darken(rgb, 20);
        const lighter = modColor.lighten(rgb, 20);
        
        document.documentElement.style.setProperty('--color-secundario', modColor.rgbToHex(darker));
        document.documentElement.style.setProperty('--color-acento', modColor.rgbToHex(lighter));

        document.documentElement.style.setProperty('--color-primario-translucido', `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.1)`);
    },

    hexToRgb: (hex) => {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : { r: 13, g: 71, b: 161 };
    },

    rgbToHex: (rgb) => {
        return "#" + ((1 << 24) + (rgb.r << 16) + (rgb.g << 8) + rgb.b).toString(16).slice(1);
    },

    darken: (rgb, percent) => {
        return {
            r: Math.max(0, Math.floor(rgb.r * (1 - percent / 100))),
            g: Math.max(0, Math.floor(rgb.g * (1 - percent / 100))),
            b: Math.max(0, Math.floor(rgb.b * (1 - percent / 100)))
        };
    },

    lighten: (rgb, percent) => {
        return {
            r: Math.min(255, Math.floor(rgb.r + (255 - rgb.r) * (percent / 100))),
            g: Math.min(255, Math.floor(rgb.g + (255 - rgb.g) * (percent / 100))),
            b: Math.min(255, Math.floor(rgb.b + (255 - rgb.b) * (percent / 100)))
        };
    }
};




const ventas = {
    db: [], cart: [], sel: null,
    cuentasCatalogo: [],
    deudoresCatalogo: [],
    cuentaSel: null,
    barcodeBuffer: '',
    barcodeTimeout: null,
    cuentaSearchTimeout: null,

    normalizarCuenta(v) {
        return String(v || '')
            .trim()
            .toLowerCase()
            .replace(/[\.\,]+/g, '')
            .replace(/\s+/g, ' ');
    },

    obtenerTipoProducto(tipoRaw) {
        return String(tipoRaw || '').toLowerCase() === 'preparado' ? 'Preparado' : 'Producto';
    },

    obtenerTipoClase(tipoRaw) {
        return ventas.obtenerTipoProducto(tipoRaw) === 'Preparado' ? 'tipo-preparado' : 'tipo-producto';
    },

    obtenerBadgeTipo(tipoRaw) {
        const tipo = ventas.obtenerTipoProducto(tipoRaw);
        const clase = ventas.obtenerTipoClase(tipoRaw);
        return `<span class="tipo-badge ${clase}">${tipo}</span>`;
    },

    extraerRegionCuenta(cuenta) {
        if (!cuenta) return '';
        if (cuenta.region) return cuenta.region;
        const notas = String(cuenta.notas || '');
        const m = notas.match(/(?:REGION|REGI?ON|GRUPO)\s*:\s*([^\n|;]+)/i);
        return m ? m[1].trim() : '';
    },

    obtenerAdeudoCuenta(nombreCuenta = '') {
        const key = ventas.normalizarCuenta(nombreCuenta);
        if (!key) return 0;
        const item = (ventas.deudoresCatalogo || []).find(d => ventas.normalizarCuenta(d.nombre_fiado) === key);
        return item ? Number(item.total_deuda || 0) : 0;
    },

    actualizarEstadoCuentaUI(cuenta) {
        const info = document.getElementById('cuenta-seleccionada-info');
        if (!info) return;

        if (!cuenta) {
            info.style.display = 'none';
            info.innerHTML = '';
            return;
        }

        const nombre = cuenta.nombre_cuenta || cuenta.nombre || '';
        const region = ventas.extraerRegionCuenta(cuenta) || '-';
        const numero = cuenta.celular || '-';
        const adeudo = ventas.obtenerAdeudoCuenta(nombre);
        const estadoTxt = adeudo > 0 ? `Adeudo pendiente: ${moneyFmt.format(adeudo)}` : 'Sin adeudo';
        const estadoColor = adeudo > 0 ? 'var(--color-danger)' : 'var(--color-success)';

        info.style.display = 'block';
        info.innerHTML = `
            <div><strong>${nombre}</strong> | ${region} | ${numero}</div>
            <div style="margin-top:4px; font-weight:700; color:${estadoColor};">${estadoTxt}</div>
        `;
    },

    cargarCuentasCatalogo: async () => {
        try {
            const resp = await fetch('api/api_ventas.php?accion=listar_cuentas&estado=activo', {credentials:'include'});
            const data = await resp.json();
            if (data.success) {
                ventas.cuentasCatalogo = data.cuentas || [];
            }
        } catch (error) {
            console.error('[Cuentas Catálogo] Error:', error);
        }
    },

    renderCuentaSugerencias: (lista) => {
        const cont = document.getElementById('cuenta-sugerencias');
        if (!cont) return;
        cont.innerHTML = '';
        if (!lista.length) {
            cont.style.display = 'none';
            return;
        }
        lista.slice(0, 8).forEach((c) => {
            const nombre = c.nombre_cuenta || '';
            const numero = c.celular || '-';
            const region = ventas.extraerRegionCuenta(c) || '-';
            const item = document.createElement('div');
            item.className = 'resultado-item';
            item.style.padding = '10px 12px';
            item.style.cursor = 'pointer';
            item.style.borderBottom = '1px solid var(--color-borde)';
            item.innerHTML = `<b>${nombre}</b><div style="font-size:0.8rem; opacity:0.8; margin-top:4px;">${region} | ${numero}</div>`;
            item.onclick = () => ventas.seleccionarCuenta(c);
            cont.appendChild(item);
        });
        cont.style.display = 'block';
    },

    seleccionarCuenta: (cuenta) => {
        ventas.cuentaSel = cuenta;
        const nombreInput = document.getElementById('cuenta-nombre');
        const grupoInput = document.getElementById('cuenta-grupo');
        const numeroInput = document.getElementById('cuenta-numero');
        const cont = document.getElementById('cuenta-sugerencias');

        if (nombreInput) nombreInput.value = cuenta.nombre_cuenta || '';
        if (grupoInput) grupoInput.value = ventas.extraerRegionCuenta(cuenta) || '';
        if (numeroInput) numeroInput.value = cuenta.celular || '';
        if (cont) cont.style.display = 'none';
        ventas.actualizarEstadoCuentaUI(cuenta);
    },

    limpiarCuentaSeleccion: () => {
        ventas.cuentaSel = null;
        const grupoInput = document.getElementById('cuenta-grupo');
        const numeroInput = document.getElementById('cuenta-numero');
        if (grupoInput) grupoInput.value = '';
        if (numeroInput) numeroInput.value = '';
        ventas.actualizarEstadoCuentaUI(null);
    },

    abrirCuentaEnVentas: async (cuenta) => {
        const payload = {
            nombre_cuenta: cuenta.nombre_cuenta || cuenta.nombre || '',
            celular: cuenta.celular || '',
            region: ventas.extraerRegionCuenta(cuenta),
            id: cuenta.id || null
        };
        sessionStorage.setItem('venta_cuenta_preseleccionada', JSON.stringify(payload));
        const tabVentas = document.querySelector('[data-tab="ventas"]');
        if (tabVentas) tabVentas.click();
    },

    aplicarCuentaPreseleccionada: () => {
        const raw = sessionStorage.getItem('venta_cuenta_preseleccionada');
        if (!raw) return;
        try {
            const cuenta = JSON.parse(raw);
            ventas.cuentaSel = {
                id: cuenta.id,
                nombre_cuenta: cuenta.nombre_cuenta,
                celular: cuenta.celular,
                region: cuenta.region,
                notas: cuenta.region ? `REGION: ${cuenta.region}` : ''
            };
            const tipo = document.getElementById('tipo-pago');
            if (tipo) tipo.value = 'cuenta';
            const nombreInput = document.getElementById('cuenta-nombre');
            if (nombreInput) nombreInput.value = cuenta.nombre_cuenta || '';
            ventas.sugerirCuenta(cuenta.nombre_cuenta || '');
            ventas.actualizarCamposCuenta();
            ventas.seleccionarCuenta(ventas.cuentaSel);
            sessionStorage.removeItem('venta_cuenta_preseleccionada');
        } catch (e) {
            console.error('[Cuenta preseleccionada] Error:', e);
            sessionStorage.removeItem('venta_cuenta_preseleccionada');
        }
    },

    sugerirCuenta: (texto) => {
        const q = ventas.normalizarCuenta(texto);
        const lista = ventas.cuentasCatalogo.filter(c => {
            const nombre = ventas.normalizarCuenta(c.nombre_cuenta);
            const num = ventas.normalizarCuenta(c.celular);
            const region = ventas.normalizarCuenta(ventas.extraerRegionCuenta(c));
            return nombre.includes(q) || num.includes(q) || region.includes(q);
        }).sort((a, b) => {
            const aExacta = ventas.normalizarCuenta(a.nombre_cuenta) === q;
            const bExacta = ventas.normalizarCuenta(b.nombre_cuenta) === q;
            if (aExacta !== bExacta) return aExacta ? -1 : 1;
            return (a.nombre_cuenta || '').localeCompare(b.nombre_cuenta || '');
        });

        const exacta = lista.find(c => ventas.normalizarCuenta(c.nombre_cuenta) === q);
        if (exacta && q) {
            ventas.seleccionarCuenta(exacta);
            return;
        }

        ventas.renderCuentaSugerencias(lista);
    },

    actualizarCamposCuenta: () => {
        const tipo = document.getElementById('tipo-pago');
        const esCuenta = tipo && tipo.value === 'cuenta';
        const groupName = document.getElementById('cuenta-nombre-group');
        const groupReg = document.getElementById('cuenta-grupo-group');
        const groupNum = document.getElementById('cuenta-numero-group');
        if (groupName) groupName.style.display = esCuenta ? 'block' : 'none';
        if (groupReg) groupReg.style.display = esCuenta ? 'block' : 'none';
        if (groupNum) groupNum.style.display = esCuenta ? 'block' : 'none';
    },
    
    init: () => {
        try {
            ventas.loadDebtors();
            ventas.cargarCuentasCatalogo();
            const input = document.getElementById('buscar-producto');
            const resContainer = document.getElementById('resultados-busqueda');
            const cuentaInput = document.getElementById('cuenta-nombre');
            const cuentaSug = document.getElementById('cuenta-sugerencias');
            
            if (!input || !resContainer) {
                console.warn('Elementos de búsqueda no encontrados');
                return;
            }

            if (cuentaInput) {
                cuentaInput.oninput = () => {
                    ventas.cuentaSel = null;
                    ventas.actualizarEstadoCuentaUI(null);
                    const q = cuentaInput.value.trim();
                    if (ventas.cuentaSearchTimeout) clearTimeout(ventas.cuentaSearchTimeout);
                    ventas.cuentaSearchTimeout = setTimeout(() => ventas.sugerirCuenta(q), 150);
                };

                cuentaInput.onfocus = () => ventas.sugerirCuenta(cuentaInput.value.trim());
                cuentaInput.onblur = () => setTimeout(() => {
                    if (cuentaSug) cuentaSug.style.display = 'none';
                }, 200);
            }
            
            // Detectar escaneo rápido de código de barras
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const q = input.value.trim();
                    if (q.length >= 3) {
                        ventas.buscarPorCodigo(q);
                    }
                    return;
                }
                
                // Detectar patrón de escáner (teclas rápidas)
                clearTimeout(ventas.barcodeTimeout);
                ventas.barcodeBuffer += e.key;
                
                ventas.barcodeTimeout = setTimeout(() => {
                    ventas.barcodeBuffer = '';
                }, 100); // 100ms entre teclas = modo escáner
                
                // Si hay más de 8 caracteres en < 100ms, es escáner
                if (ventas.barcodeBuffer.length > 8) {
                    input.style.borderColor = '#4caf50';
                    input.style.borderWidth = '3px';
                }
            });
            
            input.addEventListener('blur', () => {
                input.style.borderColor = '';
                input.style.borderWidth = '';
            });
            
            input.onkeyup = debounce(() => {
                const q = input.value.trim();
                resContainer.innerHTML = '';
                if(q.length < 2){ resContainer.style.display='none'; return; }

                fetch(`api/api_inventario.php?accion=buscar&q=${encodeURIComponent(q)}&limit=12`)
                    .then(r => {
                        if (!r.ok) throw new Error(`HTTP ${r.status}`);
                        return r.json();
                    })
                    .then(d=>{
                        if(d.success && d.productos && d.productos.length){
                            resContainer.style.display='block';
                    d.productos.forEach(p => {
                        const dEl = document.createElement('div'); dEl.className = 'resultado-item';
                        const badgeTipo = ventas.obtenerBadgeTipo(p.tipo_producto);
                        const tipoClase = ventas.obtenerTipoClase(p.tipo_producto);
                        dEl.innerHTML = `
                            <div class="resultado-topline">
                                <b>${p.nombre}</b>
                                ${badgeTipo}
                            </div>
                            <div class="resultado-subline ${tipoClase}">${moneyFmt.format(p.precio_venta)}</div>
                        `;
                        dEl.onclick = () => { 
                            ventas.sel = p;
                            const tipo = ventas.obtenerTipoProducto(p.tipo_producto);
                            const colorBorde = tipo === 'Preparado' ? '#ff9800' : '#4caf50';
                            const badgeSel = ventas.obtenerBadgeTipo(p.tipo_producto);
                            document.getElementById('producto-seleccionado').innerHTML = `<div style="background:var(--color-input-bg); padding:10px; border-radius:8px; border-left:4px solid ${colorBorde};"><b>${p.nombre}</b> ${badgeSel} | Stock: ${p.stock}</div>`;
                            resContainer.style.display = 'none'; input.value = '';
                            document.getElementById('cantidad-venta').focus();
                        };
                        resContainer.appendChild(dEl);
                    });
                } else resContainer.style.display='none';
            }).catch(e=>{ 
                console.error('Búsqueda error:', e); 
                logError('Búsqueda Producto', e.message);
                resContainer.style.display='none'; 
            });
        }, 300);

        document.getElementById('agregar-carrito').onclick = () => {
            if(!ventas.sel) return Notificador.error('Selecciona producto');
            const qty = parseInt(document.getElementById('cantidad-venta').value);
            if(qty > ventas.sel.stock) return Notificador.error('Stock insuficiente');
            const existe = ventas.cart.find(x => x.id === ventas.sel.id);
            if(existe) {
                if(existe.cantidad + qty > ventas.sel.stock) return Notificador.error('Stock insuficiente');
                existe.cantidad += qty;
            } else ventas.cart.push({...ventas.sel, cantidad: qty});
            
            // Animación al agregar producto
            const btnAgregar = document.getElementById('agregar-carrito');
            btnAgregar.classList.add('animate-bounce');
            setTimeout(() => btnAgregar.classList.remove('animate-bounce'), 500);
            
            ventas.render(); 
            ventas.sel = null; 
            document.getElementById('producto-seleccionado').innerHTML = ''; 
            document.getElementById('cantidad-venta').value = 1;
            document.getElementById('buscar-producto').focus();
        };

        document.getElementById('finalizar-venta').onclick = async () => {
            if(!ventas.cart.length) return Notificador.error('Carrito vacío');
            const tipo = document.getElementById('tipo-pago').value;
            const cuentaNombre = document.getElementById('cuenta-nombre').value.trim();
            const total = ventas.cart.reduce((a,b) => a + (b.precio_venta * b.cantidad), 0);

            if (tipo === 'pagado') {
                const cfg = Notificador.getConfig();
                const { value: pago } = await Swal.fire({
                    title: `Total: ${moneyFmt.format(total)}`, input: 'number', inputLabel: 'Pago con:',
                    showCancelButton: true, background: cfg.bg, color: cfg.color
                });
                if (!pago) return;
                if (parseFloat(pago) < total) return Notificador.error('Pago insuficiente');
                await Swal.fire({ title: '¡Cobrado!', html: `<h2>Cambio: <b style="color:green">${moneyFmt.format(pago - total)}</b></h2>`, icon: 'success', timer: 3000, background: cfg.bg, color: cfg.color });
            } else if (tipo === 'transferencia') {
                if (!(await Notificador.confirm('Revisar transferencia', 'Confirma que la transferencia fue recibida antes de cobrar.'))) return;
            } else {
                if(!cuentaNombre) return Notificador.error('Falta cuenta');

                if (tipo === 'cuenta') {
                    if (!ventas.cuentaSel || ventas.normalizarCuenta(ventas.cuentaSel.nombre_cuenta) !== ventas.normalizarCuenta(cuentaNombre)) {
                        return Notificador.error('Selecciona una cuenta de la lista');
                    }
                    if(!(await Notificador.confirm(`¿Cobrar a cuenta de ${cuentaNombre}?`))) return;
                }
            }
            
            const grupo = document.getElementById('cuenta-grupo').value;
            const numero = document.getElementById('cuenta-numero').value;
            const tipoBackend = tipo === 'cuenta' ? 'fiado' : tipo;
            if (tipo === 'cuenta' && !grupo) {
                return Notificador.error('Selecciona una cuenta válida');
            }
            
            fetchWithCSRF('api/api_ventas.php', { 
                method: 'POST', 
                headers: {'Content-Type':'application/json'}, 
                body: JSON.stringify({
                    carrito: ventas.cart, 
                    tipo_pago: tipoBackend, 
                    nombre_fiado: cuentaNombre,
                    grupo_fiado: grupo,
                    celular_fiado: numero,
                    comprobante_tarjeta: '',
                    referencia_transferencia: '',
                    csrf_token: sessionStorage.getItem('csrf_token')
                }) 
            }).then(r => r.json()).then(d => {
                if(d.success) { 
                    if(tipo !== 'pagado') Notificador.success('✅ Venta a cuenta registrada'); 
                    else {
                        // Sonido de éxito al cobrar
                        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                        const oscillator = audioContext.createOscillator();
                        const gainNode = audioContext.createGain();
                        oscillator.connect(gainNode);
                        gainNode.connect(audioContext.destination);
                        oscillator.frequency.value = 800;
                        oscillator.type = 'sine';
                        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);
                        oscillator.start(audioContext.currentTime);
                        oscillator.stop(audioContext.currentTime + 0.2);
                    }
                    ventas.cart = []; 
                    ventas.render(); 
                    ventas.loadDebtors(); 
                    ventas.init(); 
                    if(registros && registros.load) registros.load(); 
                    if(estadisticas && estadisticas.cargarAdmin) estadisticas.cargarAdmin();
                } else Notificador.error('Error', d.message);
            });
        };
        document.getElementById('tipo-pago').onchange = (e) => {
            const tipo = e.target.value;
            const esCuenta = tipo === 'cuenta';

            const cont = document.getElementById('cuenta-sugerencias');
            if (cont) cont.style.display = 'none';
            ventas.actualizarCamposCuenta();
            if (!esCuenta) ventas.limpiarCuentaSeleccion();
            const compTarjeta = document.getElementById('comprobante-tarjeta-group');
            const refTransfer = document.getElementById('referencia-transferencia-group');
            if (compTarjeta) compTarjeta.style.display = 'none';
            if (refTransfer) refTransfer.style.display = 'none';
        };
        } catch (error) {
            console.error('Error inicializando ventas:', error);
            logError('Ventas Init Error', error.message, error.stack);
            Notificador.error('Error', 'No se pudo inicializar el módulo de ventas');
        }
    },
    
    // Búsqueda optimizada por código de barras
    buscarPorCodigo: async (codigo) => {
        try {
            const resp = await fetch(`api/api_inventario.php?accion=buscar&q=${encodeURIComponent(codigo)}&limit=1`, {credentials:'include'});
            const data = await resp.json();
            
            if (data.success && data.productos && data.productos.length > 0) {
                const producto = data.productos[0];
                ventas.sel = producto;
                const tipo = ventas.obtenerTipoProducto(producto.tipo_producto);
                const colorBorde = tipo === 'Preparado' ? '#ff9800' : '#4caf50';
                const badgeSel = ventas.obtenerBadgeTipo(producto.tipo_producto);
                
                document.getElementById('producto-seleccionado').innerHTML = 
                    `<div style="background:var(--color-input-bg); padding:10px; border-radius:8px; border-left:4px solid ${colorBorde};">
                        <b>${producto.nombre}</b> ${badgeSel} | Stock: ${producto.stock} | ${moneyFmt.format(producto.precio_venta)}
                    </div>`;
                
                document.getElementById('buscar-producto').value = '';
                document.getElementById('resultados-busqueda').style.display = 'none';
                document.getElementById('cantidad-venta').focus();
                document.getElementById('cantidad-venta').select();
                
                Notificador.success('Producto encontrado');
            } else {
                Notificador.error('Producto no encontrado');
            }
        } catch (error) {
            console.error('[Barcode] Error:', error);
            Notificador.error('Error al buscar producto');
        }
    },
    
    render: () => {
        const l = document.getElementById('carrito-lista'); l.innerHTML = ''; let t = 0;
        ventas.cart.forEach((p, i) => {
            t += p.precio_venta * p.cantidad;
            const badgeTipo = ventas.obtenerBadgeTipo(p.tipo_producto);
            l.innerHTML += `<li>
                <div class="ticket-item-main">
                    <span>${p.cantidad}x ${p.nombre}</span>
                    ${badgeTipo}
                </div>
                <div class="ticket-item-actions">
                    <b>${moneyFmt.format(p.precio_venta * p.cantidad)}</b>
                    <button class="btn-icon btn-delete" style="width:30px; height:30px; padding:0;" onclick="ventas.del(${i})"><i class="fas fa-times"></i></button>
                </div>
            </li>`;
        });
        document.getElementById('carrito-total').innerText = `Total: ${moneyFmt.format(t)}`;
    },
    del: (i) => { ventas.cart.splice(i,1); ventas.render(); },
    loadDebtors: () => {
        fetch('api/api_ventas.php?accion=listar_fiados', {credentials:'include'}).then(r=>r.json()).then(d=>{
            ventas.deudoresCatalogo = (d && d.success && d.deudores) ? d.deudores : [];
            if (ventas.cuentaSel) ventas.actualizarEstadoCuentaUI(ventas.cuentaSel);

            const t = document.getElementById('cuerpo-tabla-deudores');
            if (!t) return;
            t.innerHTML = '';
            if(d.success && d.deudores) d.deudores.forEach(x => { 
                t.innerHTML += `<tr>
                    <td>${x.nombre_fiado}${x.grupo_fiado ? ` <span style="font-size:0.75rem; opacity:0.7;">(${x.grupo_fiado})</span>` : ''}</td>
                    <td>${moneyFmt.format(x.total_deuda)}</td>
                    <td>
                        <div style="display:flex; gap:5px;">
                            <button class="btn-icon btn-pay" onclick="ventas.pay('${x.nombre_fiado}',${x.total_deuda})" title="Cobrar a cuenta">
                                <i class="fas fa-dollar-sign"></i>
                            </button>
                            <button class="btn-icon btn-info" onclick="ventas.verHistorial('${x.nombre_fiado.replace(/'/g, "\\'")}')" title="Ver historial" style="background:#2196f3;">
                                <i class="fas fa-history"></i>
                            </button>
                        </div>
                    </td>
                </tr>`; 
            });
        });
    },
    pay: async (n, m) => { 
        if(await Notificador.confirm(`¿Cobrar a cuenta ${moneyFmt.format(m)}?`)) {
            const fd = new FormData(); fd.append('accion','pagar_fiado'); fd.append('nombre_fiado',n); fd.append('monto_pagado',m);
            appendCsrfToFormData(fd);
            fetchWithCSRF('api/api_ventas.php', {method:'POST', body:fd}).then(()=>{ ventas.loadDebtors(); registros.load(); });
        }
    },
    verHistorial: async (nombre) => {
        try {
            const resp = await fetch(`api/api_ventas.php?accion=historial_cliente&nombre=${encodeURIComponent(nombre)}`);
            const data = await resp.json();
            
            if (!data.success) {
                Notificador.error('Error', data.message || 'No se pudo cargar el historial');
                return;
            }
            
            const ventas = data.ventas || [];
            const totalDeuda = ventas.filter(v => !v.fiado_pagado).reduce((sum, v) => sum + parseFloat(v.total), 0);
            const totalPagado = ventas.filter(v => v.fiado_pagado).reduce((sum, v) => sum + parseFloat(v.total), 0);
            
            let html = `<div style="text-align:left;">
                <h3 style="margin:0 0 15px 0; color:var(--color-primario);">📊 Historial de ${nombre}</h3>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom:20px;">
                    <div style="background:#ffebee; padding:10px; border-radius:6px;">
                        <small style="opacity:0.7;">Deuda Pendiente</small>
                        <h3 style="margin:5px 0; color:#c62828;">${moneyFmt.format(totalDeuda)}</h3>
                    </div>
                    <div style="background:#e8f5e9; padding:10px; border-radius:6px;">
                        <small style="opacity:0.7;">Total Pagado</small>
                        <h3 style="margin:5px 0; color:#2e7d32;">${moneyFmt.format(totalPagado)}</h3>
                    </div>
                </div>
                <table style="width:100%; border-collapse:collapse;">
                    <thead>
                        <tr style="background:var(--color-primario); color:white;">
                            <th style="padding:8px;">Fecha</th>
                            <th style="padding:8px;">Producto</th>
                            <th style="padding:8px;">Monto</th>
                            <th style="padding:8px;">Estado</th>
                        </tr>
                    </thead>
                    <tbody>`;
            
            ventas.forEach(v => {
                const estado = v.fiado_pagado ? '<span style="color:#2e7d32;font-weight:bold;">✓ Pagado</span>' : '<span style="color:#c62828;font-weight:bold;">⏳ Pendiente</span>';
                html += `<tr style="border-bottom:1px solid #e0e0e0;">
                    <td style="padding:8px;">${v.fecha}</td>
                    <td style="padding:8px;">${v.producto_nombre || 'N/A'}</td>
                    <td style="padding:8px;font-weight:bold;">${moneyFmt.format(v.total)}</td>
                    <td style="padding:8px;">${estado}</td>
                </tr>`;
            });
            
            html += `</tbody></table></div>`;
            
            const cfg = Notificador.getConfig();
            Swal.fire({
                html: html,
                width: '700px',
                background: cfg.bg,
                color: cfg.color,
                showCloseButton: true,
                showConfirmButton: false
            });
        } catch (error) {
            console.error('[verHistorial] Error:', error);
            Notificador.error('Error', 'No se pudo cargar el historial');
        }
    }
};

const inventario = {
    listData: [],
    stockBajoCount: 0,
    
    load: () => {
        try {
            const role = sessionStorage.getItem('user_role');
            if (role === 'admin' || role === 'superadmin') {
                estadisticas.cargarAdmin();
            }
            
            // Cargar notificación de stock bajo
            inventario.verificarStockBajo();
            
            const t = document.getElementById('cuerpo-tabla-inventario');
            const btnNew = document.getElementById('btn-mostrar-form-producto');
            const modal = document.getElementById('modal-producto');
            const form = document.getElementById('form-producto');
            
            if(btnNew) {
                btnNew.onclick = () => { 
                    document.getElementById('modal-titulo-producto').textContent = 'Nuevo Producto';
                    form.reset(); 
                    document.getElementById('producto-id').value=''; 
                    modal.style.display = 'flex';
                };
            }

            const btnAplicarFiltrosInv = document.getElementById('btn-aplicar-filtros-inventario');
            const btnLimpiarFiltrosInv = document.getElementById('btn-limpiar-filtros-inventario');
            if (btnAplicarFiltrosInv) btnAplicarFiltrosInv.onclick = () => inventario.aplicarFiltros(true);
            if (btnLimpiarFiltrosInv) btnLimpiarFiltrosInv.onclick = () => inventario.limpiarFiltros();

            const filtroBuscarInv = document.getElementById('filtro-buscar');
            const filtroStockInv = document.getElementById('filtro-stock');
            const filtroOrdenInv = document.getElementById('filtro-ordenar');
            const filtroTipoInv = document.getElementById('filtro-tipo');
            if (filtroBuscarInv) filtroBuscarInv.oninput = debounce(() => inventario.aplicarFiltros(false), 220);
            if (filtroStockInv) filtroStockInv.onchange = () => inventario.aplicarFiltros(false);
            if (filtroOrdenInv) filtroOrdenInv.onchange = () => inventario.aplicarFiltros(false);
            if (filtroTipoInv) filtroTipoInv.onchange = () => inventario.aplicarFiltros(false);
            
            // Cerrar modal al hacer clic fuera del contenido
            if(modal) {
                modal.addEventListener('click', (e) => {
                    if(e.target === modal) {
                        modal.style.display = 'none';
                    }
                });
                
                // Cerrar modal al presionar ESC
                document.addEventListener('keydown', (e) => {
                    if(e.key === 'Escape' && modal.style.display === 'flex') {
                        modal.style.display = 'none';
                    }
                });
            }
            
            if(t) {
                // Mostrar loading
                t.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin"></i> Cargando productos...</td></tr>';
                
                fetch('api/api_inventario.php?accion=listar', {credentials:'include'})
                    .then(r=>{
                        if (!r.ok) throw new Error(`Error HTTP ${r.status}`);
                        return r.json();
                    })
                    .then(d=>{
                        if(d.success){
                            inventario.listData = d.productos || [];
                            inventario.aplicarFiltros(false);
                            } else {
                                console.error('API error:', d);
                                t.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:20px; color:#f44336;"><i class="fas fa-exclamation-triangle"></i> Error: ' + (d.message || 'Error desconocido') + '</td></tr>';
                            Notificador.error('Error al cargar inventario', d.message || 'Error desconocido');
                        }
                    })
                    .catch(e=>{
                        console.error('Fetch error:', e);
                        t.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:20px; color:#f44336;"><i class="fas fa-wifi-slash"></i> Error de conexión</td></tr>';
                        Notificador.error('Error al conectar', 'Verifica tu conexión a internet');
                    });
            }
            
            if(form) {
                // Agregar validación en tiempo real
                Validador.agregarValidacionTiempoReal(form);
                
                form.onsubmit = (e) => { 
                    e.preventDefault();
                    
                    // Validar formulario antes de enviar
                    if (!Validador.validarFormulario(form)) {
                        Notificador.error('Validación', 'Por favor completa todos los campos requeridos');
                        return;
                    }
                    
                    const fd = new FormData(form);
                    const id = document.getElementById('producto-id').value;
                    fd.append('accion', id ? 'editar' : 'crear');
                    appendCsrfToFormData(fd);
                    
                    fetch('api/api_inventario.php', {method:'POST', body:fd, credentials:'include'})
                        .then(r=>r.json())
                        .then(d=>{ 
                            if(d.success) { 
                                Notificador.success(id?'✅ Producto actualizado':'✅ Producto creado', 'Los cambios se guardaron correctamente');
                                form.reset();
                                document.getElementById('modal-producto').style.display='none';
                                inventario.load();
                                ventas.init();
                            } else {
                                Notificador.error('Error', d.message || 'No se pudo guardar el producto');
                            }
                        })
                        .catch(e=>{
                            Notificador.error('Error', 'No se pudo guardar: ' + e.message);
                        });
                };
            }
        } catch (error) {
            console.error('[Inventario] Error en load:', error);
            Notificador.error('Error', 'Error al cargar inventario: ' + error.message);
        }
    },
    
    verificarStockBajo: async () => {
        try {
            const resp = await fetch('api/api_inventario.php?accion=stock_bajo', {credentials:'include'});
            if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
            const data = await resp.json();
            
            if (data.success && data.productos) {
                inventario.stockBajoCount = data.productos.length;
                
                // Banner flotante
                const banner = document.getElementById('banner-stock-alerta');
                const bannerText = document.getElementById('banner-stock-text');
                if (banner && bannerText) {
                    if (inventario.stockBajoCount > 0) {
                        bannerText.innerText = `⚠️ ${inventario.stockBajoCount} producto${inventario.stockBajoCount > 1 ? 's' : ''} con stock bajo`;
                        banner.style.display = 'flex';
                    } else {
                        banner.style.display = 'none';
                    }
                }
                
                // Actualizar badge en navbar
                const badge = document.getElementById('badge-stock-bajo');
                if (badge) {
                    if (inventario.stockBajoCount > 0) {
                        badge.innerText = inventario.stockBajoCount;
                        badge.style.display = 'inline-block';
                        
                        // Mostrar notificación solo la primera vez
                        if (!sessionStorage.getItem('stock_bajo_notificado')) {
                            setTimeout(() => {
                                Notificador.warning(`⚠️ ${inventario.stockBajoCount} productos con stock bajo`);
                                sessionStorage.setItem('stock_bajo_notificado', 'true');
                            }, 2000);
                        }
                    } else {
                        badge.style.display = 'none';
                    }
                }
                
                // Actualizar contador en dashboard si existe
                const elemDashboard = document.getElementById('stock-bajo-count');
                if (elemDashboard && inventario.stockBajoCount !== parseInt(elemDashboard.innerText)) {
                    console.log('[Stock Bajo] Actualizando dashboard:', inventario.stockBajoCount);
                    elemDashboard.innerText = inventario.stockBajoCount;
                    
                    // Aplicar animación
                    const tarjeta = elemDashboard.closest('.stat-card-pink');
                    if (inventario.stockBajoCount > 0) {
                        elemDashboard.style.animation = 'pulse 2s infinite';
                        if (tarjeta) tarjeta.style.boxShadow = '0 0 20px rgba(244, 67, 54, 0.4)';
                    } else {
                        elemDashboard.style.animation = 'none';
                        if (tarjeta) tarjeta.style.boxShadow = '';
                    }
                }
            }
        } catch (error) {
            console.error('[Stock Bajo] Error al verificar:', error);
            // No mostrar error al usuario para verificación en background
            // Solo loguear para debugging del administrador
        }
    },
    
    preEdit: (id) => {
        try {
            const p = inventario.listData.find(x => x.id == id);
            if(!p) {
                Notificador.error('Error', 'Producto no encontrado');
                return;
            }
            
            const form = document.getElementById('form-producto');
            const modal = document.getElementById('modal-producto');
            if(!form || !modal) {
                Notificador.error('Error', 'Formulario o modal no encontrado');
                return;
            }
            
            document.getElementById('modal-titulo-producto').textContent = 'Editar Producto';
            document.getElementById('producto-id').value = p.id;
            document.getElementById('producto-nombre').value = p.nombre;
            document.getElementById('producto-tipo').value = String(p.tipo_producto || 'producto').toLowerCase();
            document.getElementById('producto-codigo').value = p.codigo_barras || '';
            document.getElementById('producto-precio').value = p.precio_venta || 0;
            document.getElementById('producto-stock').value = p.stock || 0;
            
            modal.style.display = 'flex';
            
            // Destacar el campo de nombre
            setTimeout(() => { 
                document.getElementById('producto-nombre').focus();
            }, 100);
        } catch (error) {
            console.error('[preEdit] Error:', error);
            Notificador.error('Error', 'Error al cargar el producto: ' + error.message);
        }
    },
    
    verHistorial: async (id, nombre) => {
        try {
            const resp = await fetch(`api/api_inventario.php?accion=historial&producto_id=${id}`, {credentials:'include'});
            const data = await resp.json();
            
            if (!data.success) {
                Notificador.error(data.message || 'Error al cargar historial');
                return;
            }
            
            const historial = data.historial || [];
            
            let html = `
                <div style="max-height:500px; overflow-y:auto;">
                    <h3 style="margin-top:0; color:var(--color-primario);">
                        <i class="fas fa-history"></i> Historial de ${nombre}
                    </h3>
            `;
            
            if (historial.length === 0) {
                html += '<p style="text-align:center; padding:40px; opacity:0.6;">No hay cambios registrados</p>';
            } else {
                html += '<table style="width:100%; border-collapse:collapse;">';
                html += '<thead><tr style="background:var(--color-primario); color:white;"><th style="padding:10px; text-align:left;">Fecha</th><th>Campo</th><th>Anterior</th><th>Nuevo</th><th>Usuario</th></tr></thead>';
                html += '<tbody>';
                
                historial.forEach(h => {
                    try {
                        // Parsear los datos JSON
                        let datosAnteriores = {};
                        let datosNuevos = {};
                        
                        if (typeof h.datos_anteriores === 'string') {
                            datosAnteriores = JSON.parse(h.datos_anteriores || '{}');
                        } else {
                            datosAnteriores = h.datos_anteriores || {};
                        }
                        
                        if (typeof h.datos_nuevos === 'string') {
                            datosNuevos = JSON.parse(h.datos_nuevos || '{}');
                        } else {
                            datosNuevos = h.datos_nuevos || {};
                        }
                        
                        // Obtener el campo (clave del objeto JSON)
                        const campo = Object.keys(datosNuevos)[0] || Object.keys(datosAnteriores)[0] || 'desconocido';
                        let valorAnterior = datosAnteriores[campo] || '-';
                        let valorNuevo = datosNuevos[campo] || '-';
                        
                        // Formatear valores según el campo
                        if (campo === 'precio_venta' || campo === 'precio') {
                            valorAnterior = moneyFmt.format(parseFloat(valorAnterior || 0));
                            valorNuevo = moneyFmt.format(parseFloat(valorNuevo || 0));
                        }
                        
                        if (campo === 'stock' || campo === 'stock_minimo') {
                            valorAnterior = parseInt(valorAnterior) || 0;
                            valorNuevo = parseInt(valorNuevo) || 0;
                        }
                        
                        html += `
                            <tr style="border-bottom:1px solid var(--color-borde);">
                                <td style="padding:10px; font-size:0.85rem;">${new Date(h.fecha).toLocaleString('es-MX')}</td>
                                <td style="padding:10px;"><strong>${campo}</strong></td>
                                <td style="padding:10px; color:#f44336;">${valorAnterior}</td>
                                <td style="padding:10px; color:#4caf50;">${valorNuevo}</td>
                                <td style="padding:10px;">${h.usuario || 'desconocido'}</td>
                            </tr>
                        `;
                    } catch (e) {
                        console.error('[verHistorial] Error parseando fila:', e, h);
                    }
                });
                
                html += '</tbody></table>';
            }
            
            html += '</div>';
            
            await Swal.fire({
                html: html,
                width: '900px',
                showConfirmButton: true,
                confirmButtonText: 'Cerrar',
                background: 'var(--color-blanco)',
                customClass: {
                    container: 'swal-dark-theme'
                }
            });
            
        } catch (error) {
            console.error('[Historial] Error:', error);
            logError('Historial Producto', error.message);
            Notificador.error('Error al cargar historial');
        }
    },
    del: async (id) => {
        const producto = inventario.listData.find(p => p.id == id);
        if (!producto) return;
        
        const detalle = `💰 Precio: $${producto.precio_venta} | 📦 Stock: ${producto.stock}`;
        const confirmado = await Notificador.confirmDelete(producto.nombre, detalle, 'Se eliminará del inventario permanentemente.');
        
        if(confirmado) {
            try {
                const params = new URLSearchParams({accion:'eliminar', id:id});
                const token = sessionStorage.getItem('csrf_token');
                if(token) params.append('csrf_token', token);
                
                const res = await fetch('api/api_inventario.php', {credentials:'include', 
                    method: 'POST',
                    body: params
                });
                
                const data = await res.json();
                
                if(data.success) {
                    Notificador.success('✅ Eliminado', 'Producto eliminado correctamente');
                    inventario.load();
                } else {
                    Notificador.error('Error', data.message || 'No se pudo eliminar el producto');
                }
            } catch (error) {
                console.error('[Eliminar] Error:', error);
                Notificador.error('Error', 'Error al eliminar: ' + error.message);
            }
        }
    },

    renderTabla: (productos) => {
        const t = document.getElementById('cuerpo-tabla-inventario');
        if (!t) return;
        t.innerHTML = '';
        const isDark = document.body.getAttribute('data-theme') === 'dark';

        if (productos.length > 0) {
            productos.forEach((p, idx) => {
                const stockMin = p.stock_minimo || 10;
                const isStockBajo = Number(p.stock || 0) <= Number(stockMin);
                const rowClass = isStockBajo
                    ? 'inventario-row-stock-bajo'
                    : (idx % 2 === 0 ? 'inventario-row-alt' : '');
                const codigoClass = isDark ? 'inventario-codigo-dark' : 'inventario-codigo-light';
                const precioClass = isStockBajo ? 'inventario-precio-stock-bajo' : 'inventario-precio-normal';

                const stockDisplay = `<div style="display:flex; align-items:center; gap:8px; justify-content:center;">
                    ${p.stock}
                    ${isStockBajo ? `<span style="background:#f44336; color:white; padding:3px 8px; border-radius:12px; font-size:0.75rem; font-weight:bold; white-space:nowrap;">⚠️ BAJO</span>` : ''}
                </div>`;

                let btnAcciones = '';
                if (document.body.getAttribute('data-role') !== 'vendedor') {
                    btnAcciones = `<div style="display:flex; gap:5px; flex-wrap:wrap; justify-content:center;">
                        <button class="btn-icon btn-edit" onclick="inventario.preEdit(${p.id})" title="Editar"><i class="fas fa-pen"></i></button>
                        <button class="btn-icon btn-info" onclick="inventario.verHistorial(${p.id}, '${String(p.nombre || '').replace(/'/g, "\\'")}')" title="Historial" style="background:#2196f3;"><i class="fas fa-history"></i></button>
                        <button class="btn-icon btn-delete" onclick="inventario.del(${p.id})" title="Eliminar"><i class="fas fa-trash"></i></button>
                    </div>`;
                }

                const tipoProducto = String(p.tipo_producto || 'producto').toLowerCase() === 'preparado' ? 'Preparado' : 'Producto';
                t.innerHTML += `<tr class="${rowClass}">
                    <td style="font-weight:500;">${p.nombre}</td>
                    <td style="text-align:center;"><span style="background:${tipoProducto === 'Preparado' ? '#ff9800' : '#4caf50'}; color:white; padding:4px 12px; border-radius:12px; font-size:0.8rem; font-weight:bold;">${tipoProducto}</span></td>
                    <td class="${codigoClass}" style="font-family:monospace;">${p.codigo_barras || '-'}</td>
                    <td class="${precioClass}" style="text-align:right; font-weight:500;">${moneyFmt.format(p.precio_venta || 0)}</td>
                    <td style="text-align:center;">${stockDisplay}</td>
                    <td style="text-align:center;">${btnAcciones}</td>
                </tr>`;
            });
        } else {
            t.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:30px; opacity:0.5;"><i class="fas fa-search"></i> No se encontraron productos con esos filtros</td></tr>';
        }
    },

    actualizarResumenUI: (cantidad) => {
        const el = document.getElementById('inventario-count');
        if (el) el.textContent = `${cantidad} producto${cantidad === 1 ? '' : 's'}`;
    },

    limpiarFiltros: () => {
        const filtroBuscar = document.getElementById('filtro-buscar');
        const filtroOrdenar = document.getElementById('filtro-ordenar');
        const filtroStock = document.getElementById('filtro-stock');
        const filtroTipo = document.getElementById('filtro-tipo');
        if (filtroBuscar) filtroBuscar.value = '';
        if (filtroOrdenar) filtroOrdenar.value = 'nombre';
        if (filtroStock) filtroStock.value = 'todos';
        if (filtroTipo) filtroTipo.value = 'todos';
        inventario.aplicarFiltros(false);
    },

    aplicarFiltros: (mostrarToast = true) => {
        try {
            const buscar = (document.getElementById('filtro-buscar')?.value || '').toLowerCase().trim();
            const ordenar = document.getElementById('filtro-ordenar')?.value || 'nombre';
            const stockFiltro = document.getElementById('filtro-stock')?.value || 'todos';
            const tipoFiltro = document.getElementById('filtro-tipo')?.value || 'todos';
            
            let productos = [...inventario.listData];

            if (buscar) {
                productos = productos.filter(p => 
                    p.nombre.toLowerCase().includes(buscar) || 
                    (p.codigo_barras && p.codigo_barras.toLowerCase().includes(buscar))
                );
            }

            if (stockFiltro === 'bajo') productos = productos.filter(p => p.stock < 10);
            if (stockFiltro === 'medio') productos = productos.filter(p => p.stock >= 10 && p.stock <= 50);
            if (stockFiltro === 'alto') productos = productos.filter(p => p.stock > 50);
            if (tipoFiltro === 'producto' || tipoFiltro === 'preparado') {
                productos = productos.filter(p => String(p.tipo_producto || 'producto').toLowerCase() === tipoFiltro);
            }

            if (ordenar === 'nombre') productos.sort((a, b) => a.nombre.localeCompare(b.nombre));
            if (ordenar === 'nombre_desc') productos.sort((a, b) => b.nombre.localeCompare(a.nombre));
            if (ordenar === 'stock_asc') productos.sort((a, b) => a.stock - b.stock);
            if (ordenar === 'stock_desc') productos.sort((a, b) => b.stock - a.stock);
            if (ordenar === 'precio_asc') productos.sort((a, b) => a.precio_venta - b.precio_venta);
            if (ordenar === 'precio_desc') productos.sort((a, b) => b.precio_venta - a.precio_venta);

            inventario.renderTabla(productos);
            inventario.actualizarResumenUI(productos.length);
            if (mostrarToast) {
                Notificador.success('✓ Filtros aplicados', `${productos.length} producto(s) encontrado(s)`);
            }
        } catch (error) {
            console.error('[Filtros] Error:', error);
            Notificador.error('Error', 'Error al aplicar filtros: ' + error.message);
        }
    },
    mostrarStockBajo: () => {
        // Función sincrónica que inicia la carga asincrónica
        inventario._mostrarStockBajoAsync().catch(error => {
            console.error('[Stock Bajo] Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'No se pudo cargar el stock bajo',
                confirmButtonText: 'Cerrar'
            });
        });
    },
    
    _mostrarStockBajoAsync: async () => {
        try {
            console.log('[Stock Bajo] Solicitando datos...');
            const res = await fetch('api/api_inventario.php?accion=stock_bajo', {credentials:'include'});
            
            // Verificar que la respuesta sea exitosa
            if (!res.ok) {
                throw new Error(`HTTP ${res.status}: ${res.statusText}`);
            }
            
            // Intentar parsear JSON
            let data;
            try {
                data = await res.json();
            } catch (jsonError) {
                console.error('[Stock Bajo] Error parseando JSON:', jsonError);
                const text = await res.text();
                console.error('[Stock Bajo] Respuesta recibida:', text);
                throw new Error('El servidor no devolvió un JSON válido. Revisa la consola para más detalles.');
            }
            
            console.log('[Stock Bajo] Datos recibidos:', data);
            
            if (!data.success) {
                throw new Error(data.message || 'Error desconocido al obtener productos');
            }
            
            if (!data.productos || !Array.isArray(data.productos)) {
                throw new Error('Formato de datos inválido recibido del servidor');
            }
            
            if (data.productos.length === 0) {
                Swal.fire({
                    icon: 'success',
                    title: '✅ Todo en orden',
                    text: 'No hay productos con stock bajo en este momento. Todos los productos tienen suficiente inventario.',
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#4caf50'
                });
                return;
            }
            
            // Crear tabla con mejor diseño
            let html = '<div style="max-height:450px; overflow-y:auto; margin-top:15px;">';
            html += '<table style="width:100%; border-collapse:collapse; font-size:0.95rem;">';
            html += '<thead style="position:sticky; top:0; background:#f44336; color:white; z-index:10;">';
            html += '<tr><th style="padding:12px; text-align:left; border-bottom:2px solid #c62828;"><i class="fas fa-box" style="margin-right:8px;"></i>Producto</th>';
            html += '<th style="padding:12px; text-align:center; border-bottom:2px solid #c62828; width:120px;"><i class="fas fa-layer-group" style="margin-right:8px;"></i>Stock Actual</th>';
            html += '<th style="padding:12px; text-align:center; border-bottom:2px solid #c62828; width:120px;"><i class="fas fa-exclamation-circle" style="margin-right:8px;"></i>Mínimo</th></tr></thead>';
            html += '<tbody>';
            
            data.productos.forEach((p, index) => {
                const bgColor = index % 2 === 0 ? 'rgba(0,0,0,0.03)' : 'transparent';
                const stockMinimo = p.stock_minimo || 10;
                const nivelCritico = p.stock <= stockMinimo / 2;
                const stockColor = nivelCritico ? '#d32f2f' : '#f57c00';
                const icon = nivelCritico ? '🔴' : '⚠️';
                
                html += `<tr style="background:${bgColor}; transition: background 0.2s;" onmouseover="this.style.background='rgba(244,67,54,0.1)'" onmouseout="this.style.background='${bgColor}'">`;
                html += `<td style="padding:12px; border-bottom:1px solid #e0e0e0;">`;
                html += `<div style="display:flex; align-items:center; gap:8px;">`;
                html += `<span style="font-size:1.2rem;">${icon}</span>`;
                html += `<div><strong>${p.nombre}</strong>`;
                if (p.codigo_barras) html += `<br><small style="opacity:0.6;">Código: ${p.codigo_barras}</small>`;
                html += `</div></div></td>`;
                html += `<td style="padding:12px; text-align:center; border-bottom:1px solid #e0e0e0;">`;
                html += `<span style="color:${stockColor}; font-weight:bold; font-size:1.2rem;">${p.stock}</span>`;
                html += `<br><small style="opacity:0.6;">unidades</small></td>`;
                html += `<td style="padding:12px; text-align:center; border-bottom:1px solid #e0e0e0;">`;
                html += `<span style="opacity:0.7; font-weight:500;">${stockMinimo}</span>`;
                html += `<br><small style="opacity:0.5;">unidades</small></td>`;
                html += `</tr>`;
            });
            
            html += '</tbody></table></div>';
            
            // Agregar resumen al final
            html += '<div style="margin-top:20px; padding:15px; background:rgba(244,67,54,0.1); border-radius:8px; border-left:4px solid #f44336;">';
            html += `<p style="margin:0; font-weight:600; color:#d32f2f;"><i class="fas fa-info-circle" style="margin-right:8px;"></i>Resumen</p>`;
            html += `<p style="margin:8px 0 0 0; font-size:0.9rem; opacity:0.8;">`;
            html += `Se encontraron <strong>${data.productos.length}</strong> producto(s) que requieren reabastecimiento.`;
            html += `<br>Por favor, revisa y actualiza el inventario lo antes posible.</p>`;
            html += '</div>';
            
            const cfg = Notificador.getConfig();
            await Swal.fire({
                title: `<span style="color:#f44336;"><i class="fas fa-exclamation-triangle" style="margin-right:10px;"></i>${data.productos.length} Producto${data.productos.length > 1 ? 's' : ''} con Stock Bajo</span>`,
                html: html,
                icon: 'warning',
                confirmButtonText: '<i class="fas fa-check" style="margin-right:8px;"></i>Entendido',
                confirmButtonColor: '#f44336',
                showCancelButton: true,
                cancelButtonText: '<i class="fas fa-print" style="margin-right:8px;"></i>Imprimir Lista',
                cancelButtonColor: '#757575',
                background: cfg.bg,
                color: cfg.color,
                width: '700px',
                customClass: {
                    popup: 'stock-bajo-popup'
                }
            }).then((result) => {
                if (result.dismiss === Swal.DismissReason.cancel) {
                    // Imprimir lista
                    window.print();
                }
            });
        } catch (error) {
            console.error('[Stock Bajo] Error completo:', error);
            Swal.fire({
                icon: 'error',
                title: 'No se pudo cargar el stock bajo',
                html: `
                    <p>Hubo un problema al obtener la lista de productos con stock bajo.</p>
                    <p style="margin-top:15px;"><strong>Posibles soluciones:</strong></p>
                    <ul style="text-align:left; margin-left:20px; margin-right:20px;">
                        <li>Verifica tu conexión a internet</li>
                        <li>Recarga la página (F5)</li>
                        <li>Cierra sesión e inicia sesión nuevamente</li>
                        <li>Contacta al administrador si el problema persiste</li>
                    </ul>
                    <p style="margin-top:15px; font-size:0.85em; color:#666;">Error técnico: ${error.message || 'Error desconocido'}</p>
                `,
                confirmButtonText: 'Recargar Página',
                confirmButtonColor: '#f44336',
                showCancelButton: true,
                cancelButtonText: 'Cerrar'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.reload();
                }
            });
        }
    }
};

const registros = {
    cache: [],
    load: () => {
        const t = document.getElementById('cuerpo-tabla-registros');
        const tbodyVentas = document.getElementById('cuerpo-tabla-ventas');

        if(t) fetch('api/api_registros.php?accion=listar', {credentials:'include'}).then(r=>r.json()).then(d=>{ 
            if(d.success){ 
                registros.cache = d.registros || [];
                registros.aplicarFiltros(false);
            } 
        });

        if(tbodyVentas) fetch('api/api_ventas.php?accion=listar_ventas', {credentials:'include'}).then(r=>r.json()).then(d=>{ 
            if(d.success){ 
                tbodyVentas.innerHTML = ''; 
                d.ventas.forEach(v => { 
                    const btnEliminar = `<button class="btn-icon btn-undo" onclick="registros.undoWithAuth(${v.id})" title="Cancelar venta"><i class="fas fa-ban"></i></button>`;
                    tbodyVentas.innerHTML += `<tr><td>${v.fecha}</td><td>${v.vendedor}</td><td>${v.producto_nombre||'Borrado'}</td><td>${v.cantidad}</td><td>${moneyFmt.format(v.total)}</td><td>${v.tipo_pago}</td><td><div style="display:flex; justify-content:center;">${btnEliminar}</div></td></tr>`; 
                }); 
            } 
        });

        fetch('api/api_registros.php?accion=corte_dia', {credentials:'include'}).then(r=>r.json()).then(d=>{
            if(d.success) {
                const c = d.corte;
                const gastosTotales = parseFloat(c.gastos) + parseFloat(c.retiros);
                
                document.getElementById('corte-ventas').innerText = moneyFmt.format(c.ventas_efectivo);
                document.getElementById('corte-ingresos').innerText = moneyFmt.format(c.ingresos_extra);
                document.getElementById('corte-gastos').innerText = '-' + moneyFmt.format(gastosTotales);
                document.getElementById('corte-total').innerText = moneyFmt.format(c.total_caja);

                const fiadosEl = document.getElementById('corte-fiados');
                if (fiadosEl && c.fiados_pendientes !== undefined) {
                    fiadosEl.innerText = moneyFmt.format(c.fiados_pendientes);
                }
            }
        });

        const f = document.getElementById('form-registro'); 
        const modal = document.getElementById('modal-registro');
        const btnAgregar = document.getElementById('btn-agregar-registro');
        const servicioSel = document.getElementById('registro-servicio');
        const categoriaSel = document.getElementById('registro-categoria');
        const tipoSel = document.getElementById('registro-tipo');
        
        // Botón Agregar Movimiento
        if(btnAgregar) {
            btnAgregar.onclick = () => {
                document.getElementById('modal-titulo-registro').textContent = 'Nuevo Movimiento';
                f.reset();
                document.getElementById('registro-id').value = '';
                if(tipoSel) tipoSel.onchange && tipoSel.onchange();
                modal.style.display = 'flex';
            };
        }

        const btnAplicarFiltrosReg = document.getElementById('btn-aplicar-filtros-registros');
        const btnLimpiarFiltrosReg = document.getElementById('btn-limpiar-filtros-registros');
        const btnEliminarTodosReg = document.getElementById('btn-eliminar-todos-registros');
        if (btnAplicarFiltrosReg) btnAplicarFiltrosReg.onclick = () => registros.aplicarFiltros(true);
        if (btnLimpiarFiltrosReg) btnLimpiarFiltrosReg.onclick = () => registros.limpiarFiltros();
        if (btnEliminarTodosReg) btnEliminarTodosReg.onclick = () => registros.eliminarTodos();

        const regInputs = ['reg-filtro-buscar', 'reg-filtro-usuario', 'reg-filtro-desde', 'reg-filtro-hasta'];
        regInputs.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.oninput = debounce(() => registros.aplicarFiltros(false), 220);
        });
        ['reg-filtro-tipo', 'reg-filtro-categoria'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.onchange = () => registros.aplicarFiltros(false);
        });
        
        // Cerrar modal al hacer clic fuera
        if(modal) {
            modal.addEventListener('click', (e) => {
                if(e.target === modal) {
                    modal.style.display = 'none';
                }
            });
            
            // Cerrar modal al presionar ESC
            document.addEventListener('keydown', (e) => {
                if(e.key === 'Escape' && modal.style.display === 'flex') {
                    modal.style.display = 'none';
                }
            });
        }
        
        if(categoriaSel && servicioSel) {
            categoriaSel.onchange = () => {
                const isArca = categoriaSel.value === 'arca';
                servicioSel.parentElement.style.display = isArca ? 'block' : 'none';
                if (!isArca) servicioSel.value = '';
            };
            categoriaSel.onchange();
        }
        if(tipoSel && categoriaSel) {
            tipoSel.onchange = () => {
                const tval = tipoSel.value;
                if (tval.startsWith('arca_')) categoriaSel.value = 'arca';
                else if (tval === 'septima' || tval === 'septima_especial') categoriaSel.value = tval;
                else if (tval === 'merma') categoriaSel.value = 'merma';
                else categoriaSel.value = 'caja';
                categoriaSel.onchange && categoriaSel.onchange();
            };
        }

        if(f) {
            Validador.agregarValidacionTiempoReal(f);
            f.onsubmit = (e) => { 
                e.preventDefault(); 
                if (!Validador.validarFormulario(f)) {
                    Notificador.error('Validación', 'Por favor completa todos los campos requeridos');
                    return;
                }
                const fd=new FormData(f); 
                const id = document.getElementById('registro-id').value;
                fd.append('accion', id ? 'editar' : 'crear'); 
                appendCsrfToFormData(fd);
                fetchWithCSRF('api/api_registros.php',{method:'POST',body:fd}).then(()=>{ 
                    const tipo = document.getElementById('registro-tipo').value;
                    const monto = document.getElementById('registro-monto').value;
                    const detalleMsg = `Tipo: ${tipo}, Monto: $${parseFloat(monto).toFixed(2)}`;
                    Notificador.success(id ? 'Movimiento actualizado correctamente' : 'Movimiento registrado correctamente', detalleMsg); 
                    f.reset();
                    modal.style.display = 'none';
                    registros.load(); // Recarga tablas y corte automáticamente
                }); 
            }; 
        }
    },

    renderTabla: (rows) => {
        const t = document.getElementById('cuerpo-tabla-registros');
        if (!t) return;
        t.innerHTML = '';

        const colores = {
            'efectivo':'#1b5e20', 'ingreso':'#2e7d32', 'fiado':'#1565c0', 'gasto':'#b71c1c', 'egreso':'#d84315',
            'merma':'#e67e22', 'septima':'#6a1b9a', 'septima_especial':'#ab47bc',
            'arca_ingreso':'#1b5e20','arca_egreso':'#b71c1c','arca_gasto':'#c62828','arca_merma':'#ef6c00'
        };

        if (!rows.length) {
            t.innerHTML = '<tr><td colspan="8" style="text-align:center; padding:28px; opacity:0.65;"><i class="fas fa-search"></i> No hay movimientos con esos filtros</td></tr>';
            return;
        }

        rows.forEach(r => {
            const color = colores[r.tipo] || 'gray';
            const servicio = r.servicio ? r.servicio : '—';
            const categoria = r.categoria ? r.categoria : '—';
            t.innerHTML += `<tr>
                <td>${r.fecha}</td>
                <td><span style="font-weight:bold; color:${color}">${String(r.tipo || '').toUpperCase()}</span></td>
                <td>${categoria}</td>
                <td>${servicio}</td>
                <td>${r.concepto}</td>
                <td>${moneyFmt.format(r.monto)}</td>
                <td>${r.usuario}</td>
                <td class="admin-only">
                    <div style="display:flex; gap:6px; justify-content:center;">
                        <button class="btn-icon btn-info" title="Historial" onclick="registros.verHistorial(${r.id}, '${String(r.concepto || '').replace(/'/g, "\\'")}')" style="background:#2196f3;"><i class="fas fa-history"></i></button>
                        <button class="btn-icon btn-edit" title="Editar" onclick="registros.edit(${r.id})"><i class="fas fa-pen"></i></button>
                        <button class="btn-icon btn-delete" style="width:30px;height:30px;padding:0;" onclick="registros.del(${r.id})"><i class="fas fa-times"></i></button>
                    </div>
                </td>
            </tr>`;
        });
    },

    actualizarResumenUI: (rows) => {
        const countEl = document.getElementById('registros-count');
        const totalEl = document.getElementById('registros-total-visibles');
        const total = rows.reduce((acc, r) => acc + Number(r.monto || 0), 0);
        if (countEl) countEl.textContent = `${rows.length} movimiento${rows.length === 1 ? '' : 's'}`;
        if (totalEl) totalEl.textContent = moneyFmt.format(total);
    },

    limpiarFiltros: () => {
        const ids = ['reg-filtro-buscar', 'reg-filtro-usuario', 'reg-filtro-desde', 'reg-filtro-hasta'];
        ids.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        const tipo = document.getElementById('reg-filtro-tipo');
        const categoria = document.getElementById('reg-filtro-categoria');
        if (tipo) tipo.value = 'todos';
        if (categoria) categoria.value = 'todas';
        registros.aplicarFiltros(false);
    },

    aplicarFiltros: (mostrarToast = true) => {
        let rows = [...registros.cache];
        const q = (document.getElementById('reg-filtro-buscar')?.value || '').toLowerCase().trim();
        const tipo = document.getElementById('reg-filtro-tipo')?.value || 'todos';
        const categoria = document.getElementById('reg-filtro-categoria')?.value || 'todas';
        const usuario = (document.getElementById('reg-filtro-usuario')?.value || '').toLowerCase().trim();
        const desde = document.getElementById('reg-filtro-desde')?.value || '';
        const hasta = document.getElementById('reg-filtro-hasta')?.value || '';

        if (q) {
            rows = rows.filter(r =>
                String(r.concepto || '').toLowerCase().includes(q) ||
                String(r.tipo || '').toLowerCase().includes(q) ||
                String(r.categoria || '').toLowerCase().includes(q) ||
                String(r.usuario || '').toLowerCase().includes(q)
            );
        }
        if (tipo !== 'todos') rows = rows.filter(r => String(r.tipo || '').toLowerCase() === tipo);
        if (categoria !== 'todas') rows = rows.filter(r => String(r.categoria || '').toLowerCase() === categoria);
        if (usuario) rows = rows.filter(r => String(r.usuario || '').toLowerCase().includes(usuario));
        if (desde) rows = rows.filter(r => String(r.fecha || '').slice(0, 10) >= desde);
        if (hasta) rows = rows.filter(r => String(r.fecha || '').slice(0, 10) <= hasta);

        rows.sort((a, b) => String(b.fecha || '').localeCompare(String(a.fecha || '')));
        registros.renderTabla(rows);
        registros.actualizarResumenUI(rows);
        if (mostrarToast) Notificador.success('✓ Filtros aplicados', `${rows.length} movimiento(s) visible(s)`);
    },

    eliminarTodos: async () => {
        const ok = await Notificador.confirmDelete('Todos los movimientos', 'Se eliminarán todos los registros de caja', 'Esta acción no se puede deshacer');
        if (!ok) return;

        try {
            const params = new URLSearchParams({accion: 'eliminar_todos'});
            const token = sessionStorage.getItem('csrf_token');
            if (token) params.append('csrf_token', token);

            const resp = await fetchWithCSRF('api/api_registros.php', { method: 'POST', body: params });
            const data = await resp.json();
            if (!data.success) throw new Error(data.message || 'No se pudo eliminar todo');

            Notificador.success('✅ Registros limpiados', `Eliminados: ${data.deleted ?? 0}`);
            registros.load();
        } catch (error) {
            Notificador.error('Error', error.message || 'No se pudo limpiar registros');
        }
    },

    clearForm: () => {
        const f = document.getElementById('form-registro');
        const resetBtn = document.getElementById('registro-reset');
        if(f) { f.reset(); document.getElementById('registro-id').value=''; }
        if(resetBtn) resetBtn.style.display='none';
        const categoriaSel = document.getElementById('registro-categoria');
        const servicioSel = document.getElementById('registro-servicio');
        if(categoriaSel && servicioSel) {
            categoriaSel.value = 'caja';
            servicioSel.parentElement.style.display = 'none';
            servicioSel.value = '';
        }
    },
    edit: (id) => {
        const r = registros.cache.find(x => x.id == id);
        if(!r) return;
        
        document.getElementById('modal-titulo-registro').textContent = 'Editar Movimiento';
        document.getElementById('registro-id').value = r.id;
        document.getElementById('registro-tipo').value = r.tipo;
        document.getElementById('registro-concepto').value = r.concepto;
        document.getElementById('registro-monto').value = r.monto;
        
        const catEl = document.getElementById('registro-categoria');
        const servEl = document.getElementById('registro-servicio');
        const tipoEl = document.getElementById('registro-tipo');
        
        if(catEl) catEl.value = r.categoria || 'caja';
        if(servEl) servEl.value = r.servicio || '';
        if(tipoEl) tipoEl.onchange && tipoEl.onchange();
        
        const modal = document.getElementById('modal-registro');
        if(modal) modal.style.display = 'flex';
    },
    verHistorial: async (id, concepto) => {
        try {
            const resp = await fetch(`api/api_registros.php?accion=historial&registro_id=${id}`);
            const data = await resp.json();
            
            if (!data.success) {
                Notificador.error(data.message || 'Error al cargar historial');
                return;
            }
            
            const historial = data.historial || [];
            
            let html = `
                <div style="max-height:500px; overflow-y:auto;">
                    <h3 style="margin-top:0; color:var(--color-primario);">
                        <i class="fas fa-history"></i> Historial de "${concepto}"
                    </h3>
            `;
            
            if (historial.length === 0) {
                html += '<p style="text-align:center; padding:40px; opacity:0.6;">No hay cambios registrados</p>';
            } else {
                html += '<table style="width:100%; border-collapse:collapse;">';
                html += '<thead><tr style="background:var(--color-primario); color:white;"><th style="padding:10px; text-align:left;">Fecha</th><th>Campo</th><th>Anterior</th><th>Nuevo</th><th>Usuario</th></tr></thead>';
                html += '<tbody>';
                
                historial.forEach(h => {
                    try {
                        let datosAnteriores = {};
                        let datosNuevos = {};
                        
                        if (typeof h.datos_anteriores === 'string') {
                            datosAnteriores = JSON.parse(h.datos_anteriores || '{}');
                        } else {
                            datosAnteriores = h.datos_anteriores || {};
                        }
                        
                        if (typeof h.datos_nuevos === 'string') {
                            datosNuevos = JSON.parse(h.datos_nuevos || '{}');
                        } else {
                            datosNuevos = h.datos_nuevos || {};
                        }
                        
                        const campo = Object.keys(datosNuevos)[0] || Object.keys(datosAnteriores)[0] || 'desconocido';
                        let valorAnterior = datosAnteriores[campo] || '-';
                        let valorNuevo = datosNuevos[campo] || '-';
                        
                        if (campo === 'monto') {
                            valorAnterior = moneyFmt.format(parseFloat(valorAnterior || 0));
                            valorNuevo = moneyFmt.format(parseFloat(valorNuevo || 0));
                        }
                        
                        html += `
                            <tr style="border-bottom:1px solid var(--color-borde);">
                                <td style="padding:10px; font-size:0.85rem;">${new Date(h.fecha).toLocaleString('es-MX')}</td>
                                <td style="padding:10px;"><strong>${campo}</strong></td>
                                <td style="padding:10px; color:#f44336;">${valorAnterior}</td>
                                <td style="padding:10px; color:#4caf50;">${valorNuevo}</td>
                                <td style="padding:10px;">${h.usuario || 'desconocido'}</td>
                            </tr>
                        `;
                    } catch (e) {
                        console.error('[verHistorial] Error parseando fila:', e, h);
                    }
                });
                
                html += '</tbody></table>';
            }
            
            html += '</div>';
            
            await Swal.fire({
                html: html,
                width: '900px',
                showConfirmButton: true,
                confirmButtonText: 'Cerrar',
                background: 'var(--color-blanco)',
                customClass: {
                    container: 'swal-dark-theme'
                }
            });
        } catch (error) {
            console.error('[verHistorial] Error:', error);
            Notificador.error('Error', 'Error al cargar el historial: ' + error.message);
        }
    },
    del: async (id) => { 
        const registro = registros.cache.find(r => r.id == id);
        if (!registro) return;
        
        const detalle = `💰 Monto: $${registro.monto} | 📝 ${registro.concepto}`;
        const confirmado = await Notificador.confirmDelete(registro.tipo.toUpperCase(), detalle, 'Se eliminará este movimiento de caja.');
        
        if(confirmado) {
            const params = new URLSearchParams({accion:'eliminar', id:id});
            const token = sessionStorage.getItem('csrf_token'); 
            if(token) params.append('csrf_token', token);
            fetchWithCSRF('api/api_registros.php',{method:'POST',body:params}).then(()=>{
                Notificador.success('✅ Eliminado', 'Registro eliminado correctamente');
                registros.load();
            });
        }
    },
    undo: async (id) => { if(await Notificador.confirm('¿Cancelar esta venta? Solo admin/superadmin.')) {
            const params = new URLSearchParams({accion:'cancelar_venta', id:id});
            const token = sessionStorage.getItem('csrf_token'); if(token) params.append('csrf_token', token);
            fetchWithCSRF('api/api_ventas.php',{method:'POST',body:params}).then(()=>registros.load());
        } },
    undoWithAuth: (id) => {
        // Mostrar modal de autenticación
        const modal = document.getElementById('modal-auth-venta');
        if (!modal) {
            Notificador.error('Error', 'Modal no encontrado');
            return;
        }
        
        // Guardar el ID de venta para usar después
        registros.ventaParaEliminar = id;
        
        // Limpiar campos
        document.getElementById('auth-venta-user').value = '';
        document.getElementById('auth-venta-pass').value = '';
        
        // Mostrar modal
        modal.style.display = 'flex';
        document.getElementById('auth-venta-user').focus();
    },
    confirmarEliminacionVenta: async (usuario, password) => {
        if (!registros.ventaParaEliminar) {
            Notificador.error('Error', 'ID de venta no disponible');
            return;
        }
        
        try {
            // Enviar autenticación
            const authParams = new URLSearchParams({
                accion: 'autenticar_admin',
                usuario: usuario,
                password: password
            });
            
            const authResp = await fetch('api/api_admin.php', {
                method: 'POST',
                body: authParams
            });
            
            const authData = await authResp.json();
            
            if (!authData.success) {
                Notificador.error('Error de Autenticación', authData.message || 'Usuario o contraseña inválido');
                return;
            }
            
            // Si la autenticación fue exitosa, eliminar la venta
            const params = new URLSearchParams({
                accion: 'cancelar_venta',
                id: registros.ventaParaEliminar
            });
            
            const token = sessionStorage.getItem('csrf_token');
            if (token) params.append('csrf_token', token);
            
            const deleteResp = await fetch('api/api_ventas.php', {
                method: 'POST',
                body: params
            });
            
            const deleteData = await deleteResp.json();
            
            if (deleteData.success) {
                Notificador.success('✅ Venta eliminada correctamente');
                registros.load();
                
                // Cerrar modal
                const modal = document.getElementById('modal-auth-venta');
                if (modal) modal.style.display = 'none';
            } else {
                Notificador.error('Error', deleteData.message || 'No se pudo eliminar la venta');
            }
        } catch (error) {
            console.error('[eliminarVenta] Error:', error);
            Notificador.error('Error', 'Error al eliminar venta: ' + error.message);
        }
    }
};

// Event listeners para modal de autenticación
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('modal-auth-venta');
    if (!modal) return;
    
    const btnCancel = document.getElementById('btn-auth-venta-cancel');
    const btnConfirm = document.getElementById('btn-auth-venta-confirm');
    const inputUser = document.getElementById('auth-venta-user');
    const inputPass = document.getElementById('auth-venta-pass');
    
    if (btnCancel) {
        btnCancel.onclick = () => {
            modal.style.display = 'none';
            inputUser.value = '';
            inputPass.value = '';
        };
    }
    
    if (btnConfirm) {
        btnConfirm.onclick = async () => {
            const usuario = inputUser.value.trim();
            const password = inputPass.value;
            
            if (!usuario || !password) {
                Notificador.warning('⚠️ Completa todos los campos');
                return;
            }
            
            btnConfirm.disabled = true;
            btnConfirm.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
            
            try {
                await registros.confirmarEliminacionVenta(usuario, password);
            } finally {
                btnConfirm.disabled = false;
                btnConfirm.innerHTML = '<i class="fas fa-trash"></i> Eliminar Venta';
            }
        };
    }
    
    if (inputPass) {
        inputPass.onkeypress = (e) => {
            if (e.key === 'Enter') btnConfirm.click();
        };
    }
    
    // Cerrar modal con Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && modal.style.display === 'flex') {
            modal.style.display = 'none';
        }
    });
    
    // Cerrar modal al hacer clic afuera
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
});

const septimas = {
    cache: [],
    initOnce: false,
    init: () => {
        if (septimas.initOnce) return;
        septimas.initOnce = true;
        const form = document.getElementById('form-septima');
        if(form) {
            Validador.agregarValidacionTiempoReal(form);
            form.onsubmit = (e) => {
                e.preventDefault();
                if (!Validador.validarFormulario(form)) {
                    Notificador.error('Validación', 'Por favor completa todos los campos requeridos');
                    return;
                }
                const fd = new FormData(form);
                fd.append('accion','crear');
                appendCsrfToFormData(fd);
                fetchWithCSRF('api/api_septimas.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
                    if(d.success){ 
                        const monto = document.getElementById('septima-monto').value;
                        Notificador.success('Séptima registrada correctamente', `Monto: $${parseFloat(monto).toFixed(2)}`); 
                        form.reset(); 
                        septimas.load(); 
                    }
                    else Notificador.error('Error', d.message);
                }).catch(err=>Notificador.error('Error', err.message));
            };
        }
    },
    load: () => {
        septimas.init();
        const t = document.getElementById('cuerpo-tabla-septimas');
        if(!t) return;
        fetch('api/api_septimas.php?accion=listar', {credentials:'include'}).then(r=>r.json()).then(d=>{
            if(d.success){
                septimas.cache = d.septimas || [];
                t.innerHTML = '';
                septimas.cache.forEach(s => {
                    const estado = s.pagado ? '<span style="color:green; font-weight:bold;">Pagada</span>' : '<span style="color:#e53935; font-weight:bold;">Pendiente</span>';
                    const tipo = s.tipo ? s.tipo : 'normal';
                    t.innerHTML += `<tr><td>${s.fecha}</td><td>${s.nombre_padrino}</td><td>${moneyFmt.format(s.monto)}</td><td>${tipo}</td><td>${s.servicio||'—'}</td><td>${estado}</td><td class="admin-only"><div style="display:flex; gap:6px;">`+
                        `${s.pagado ? '' : `<button class="btn-icon btn-pay" title="Marcar pagada" onclick="septimas.pay(${s.id})"><i class="fas fa-check"></i></button>`}`+
                        `<button class="btn-icon btn-delete" onclick="septimas.del(${s.id})"><i class="fas fa-times"></i></button>`+
                        `</div></td></tr>`;
                });
            }
        });
    },
    pay: async (id) => {
        if(!await Notificador.confirm('¿Marcar séptima como pagada?')) return;
        const params = new URLSearchParams({accion:'pagar', id:id});
        const token = sessionStorage.getItem('csrf_token'); if(token) params.append('csrf_token', token);
        fetchWithCSRF('api/api_septimas.php',{method:'POST', body:params}).then(()=>septimas.load());
    },
    del: async (id) => {
        if(!await Notificador.confirmDelete('Séptima', 'Esta acción eliminará el registro de séptima permanentemente')) return;
        const params = new URLSearchParams({accion:'eliminar', id:id});
        const token = sessionStorage.getItem('csrf_token'); if(token) params.append('csrf_token', token);
        fetchWithCSRF('api/api_septimas.php',{method:'POST', body:params}).then(()=>{ 
            Notificador.success('Séptima eliminada'); 
            septimas.load(); 
        });
    }
};

const usuarios = {
    load: () => {
        const t = document.getElementById('cuerpo-tabla-usuarios');
        if(t) fetch('api/api_usuarios.php?accion=listar', {credentials:'include'}).then(r=>r.json()).then(d=>{ if(d.success){ t.innerHTML=''; d.usuarios.forEach(u => t.innerHTML+=`<tr><td>${u.username}</td><td>${u.role}</td><td><button class="btn-icon btn-delete" onclick="usuarios.del(${u.id})"><i class="fas fa-times"></i></button></td></tr>`); } });
        const f = document.getElementById('form-crear-usuario'); 
        if(f) {
            Validador.agregarValidacionTiempoReal(f);
            f.onsubmit = (e) => { 
                e.preventDefault(); 
                if (!Validador.validarFormulario(f)) {
                    Notificador.error('Validación', 'Por favor completa todos los campos requeridos');
                    return;
                }
                const fd=new FormData(f); 
                fd.append('accion','crear'); 
                appendCsrfToFormData(fd); 
                fetchWithCSRF('api/api_usuarios.php',{method:'POST',body:fd}).then(r=>r.json()).then(d=>{
                    if(d.success){
                        const username = document.getElementById('nuevo-usuario-user').value;
                        Notificador.success('Usuario creado correctamente', `Usuario: ${username}`); 
                        f.reset(); 
                        usuarios.load();
                    } else {
                        Notificador.error('Error', d.message || 'No se pudo crear el usuario');
                    }
                }).catch(err=>{
                    Notificador.error('Error', 'Error al crear usuario: ' + err.message);
                });
            };
        }
    },
    del: async (id) => { 
        const usuarioNombre = `Usuario ID: ${id}`;
        if(!await Notificador.confirmDelete(usuarioNombre, 'Esta acción eliminará el usuario permanentemente')) return;
        const params = new URLSearchParams({accion:'eliminar', id:id});
        const token = sessionStorage.getItem('csrf_token'); if(token) params.append('csrf_token', token);
        fetchWithCSRF('api/api_usuarios.php',{method:'POST',body:params}).then(()=>{ 
            Notificador.success('Usuario eliminado'); 
            usuarios.load(); 
        }); 
    }
};

const admin = {
    load: () => {
        const t = document.getElementById('cuerpo-tabla-errores');
        const filtroNivel = document.getElementById('log-filter-level');
        const filtroTexto = document.getElementById('log-filter-text');
        const btnRefrescar = document.getElementById('btn-refrescar-log');
        let erroresCache = [];

        const getLevel = (msg = '') => {
            const m = String(msg).toLowerCase();
            if (m.includes('fatal')) return 'error';
            if (m.startsWith('error |') || m.includes(' php exception ') || m.includes('unhandled promise rejection')) return 'error';
            if (m.startsWith('warning |') || m.includes(' php [2] ')) return 'warning';
            if (m.startsWith('notice |') || m.includes(' php [8] ')) return 'notice';
            return 'error';
        };

        const renderErrores = () => {
            if (!t) return;
            const nivel = filtroNivel ? filtroNivel.value : 'all';
            const term = (filtroTexto?.value || '').toLowerCase().trim();

            let rows = [...erroresCache];

            if (nivel !== 'all') {
                rows = rows.filter(r => getLevel(r.msg) === nivel);
            }

            if (term) {
                rows = rows.filter(r => r.msg.toLowerCase().includes(term) || String(r.fecha || '').toLowerCase().includes(term));
            }

            if (!rows.length) {
                t.innerHTML = '<tr><td colspan="2" style="text-align:center; padding:18px; opacity:0.7;"><i class="fas fa-filter"></i> Sin resultados con esos filtros</td></tr>';
                return;
            }

            t.innerHTML = '';
            rows.forEach(r => {
                t.innerHTML += `<tr><td>${r.fecha || '-'}</td><td>${r.msg}</td></tr>`;
            });
        };

        const cargarErrores = () => {
            if (!t) return;
            t.innerHTML = '<tr><td colspan="2" style="text-align:center; padding:18px;"><i class="fas fa-spinner fa-spin"></i> Cargando log...</td></tr>';
            fetchWithCSRF('api/api_admin.php?accion=ver_errores')
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        erroresCache = (d.errores || []).map(e => ({
                            fecha: e.fecha,
                            msg: e.error || e.mensaje || e.detalles || e.linea || ''
                        }));
                        renderErrores();
                    } else {
                        t.innerHTML = '<tr><td colspan="2" style="text-align:center; padding:18px; color:#c62828;">No se pudo cargar el log</td></tr>';
                    }
                })
                .catch(err => {
                    logError('Log Load Error', err.message || err, 'ver_errores');
                    t.innerHTML = '<tr><td colspan="2" style="text-align:center; padding:18px; color:#c62828;">Error cargando log</td></tr>';
                });
        };

        if (t) cargarErrores();
        if (filtroNivel) filtroNivel.onchange = renderErrores;
        if (filtroTexto) filtroTexto.oninput = renderErrores;
        if (btnRefrescar) btnRefrescar.onclick = () => cargarErrores();

        const btnLimpiar = document.getElementById('btn-limpiar-log');
        if (btnLimpiar) {
            btnLimpiar.onclick = async () => {
                if (!await Notificador.confirmDelete('Log de errores', 'Se eliminarán todos los registros del log')) return;
                try {
                    const fd = new FormData();
                    fd.append('accion', 'limpiar_errores');
                    appendCsrfToFormData(fd);
                    const resp = await fetchWithCSRF('api/api_admin.php', { method: 'POST', body: fd });
                    const data = await resp.json();
                    if (data.success) {
                        erroresCache = [];
                        if (t) t.innerHTML = '<tr><td colspan="2" style="text-align:center; padding:18px; opacity:0.7;"><i class="fas fa-broom"></i> Log limpio</td></tr>';
                        Notificador.success('Log limpiado');
                    } else {
                        Notificador.error(data.message || 'No se pudo limpiar el log');
                    }
                } catch (err) {
                    logError('Log Clear Error', err.message || err, 'limpiar_errores');
                    Notificador.error('Error al limpiar el log');
                }
            };
        }
        
        const pk = document.getElementById('color-picker');
        if(pk) {

            fetch('api/api_admin.php?accion=get_color', {credentials:'include'}).then(r=>r.json()).then(d=>{ 
                if(d.success) { 
                    document.documentElement.style.setProperty('--color-primario', d.color); 
                    pk.value=d.color; 
                } 
            });

            pk.onchange=(e)=>{ 
                const c=e.target.value; 
                document.documentElement.style.setProperty('--color-primario',c); 
                const fd=new FormData(); fd.append('accion','save_color'); fd.append('color',c);
                appendCsrfToFormData(fd);
                fetchWithCSRF('api/api_admin.php',{method:'POST',body:fd}); 
            };
        }
    },

};

const diagnostico = {
    verificarIntegridad: async () => {
        try {
            const response = await fetchWithCSRF('api/api_inventario.php?accion=verificar_integridad');
            const data = await response.json();
            
            if (data.success) {
                if (data.problemas && data.problemas.length > 0) {
                    const problemasList = data.problemas.map(p => `• ${p}`).join('<br>');
                    await Swal.fire({
                        icon: 'warning',
                        title: 'Problemas Encontrados',
                        html: `<div style="text-align:left">${problemasList}</div>`,
                        confirmButtonText: 'Entendido'
                    });
                } else {
                    Notificador.success('✅ Base de datos íntegra', 'No se encontraron problemas');
                }
            } else {
                Notificador.error('Error', data.message);
            }
        } catch (error) {
            Notificador.error('Error de Conexión', 'No se pudo verificar la integridad');
            logError('Verificar Integridad', error.message);
        }
    }
};

const estadisticas = {
    cargarAdmin: async (reintentar = true) => {
        try {
            console.log('[Stats] Cargando estadísticas admin...');

            // DESHABILITADO: Cargar top producto del mes (causaba lag en inventario)
            // Comentado porque no se utiliza (solo se usa 3 días)
            /*
            try {
                const resTop = await fetch('api/api_admin.php?accion=top_producto_mes', {credentials:'include'});
                if (!resTop.ok) throw new Error(`HTTP ${resTop.status}`);
                const dataTop = await resTop.json();
                console.log('[Stats] Top producto:', dataTop);
                if (dataTop.success && dataTop.producto) {
                    const elemNombre = document.getElementById('top-producto');
                    const elemVentas = document.getElementById('top-producto-ventas');
                    if (elemNombre) elemNombre.innerText = dataTop.producto.nombre || 'Sin datos';
                    if (elemVentas) elemVentas.innerText = `${dataTop.producto.ventas || 0} ventas`;
                }
            } catch (error) {
                console.error('[Stats] Error cargando top producto:', error);
            }
            */

            // Cargar stock bajo (crítico para seguridad)
            try {
                const resStock = await fetch('api/api_inventario.php?accion=stock_bajo', {credentials:'include'});
                if (!resStock.ok) throw new Error(`HTTP ${resStock.status}`);
                const dataStock = await resStock.json();
                console.log('[Stats] Stock bajo:', dataStock);
                if (dataStock.success) {
                    const count = dataStock.productos ? dataStock.productos.length : 0;
                    const elem = document.getElementById('stock-bajo-count');
                    if (elem) {
                        // Actualizar el número sin animación
                        elem.innerText = count;
                        
                        // Añadir efecto glow a la tarjeta si hay productos con stock bajo
                        const tarjeta = elem.closest('.stat-card-pink');
                        if (count > 0) {
                            if (tarjeta) tarjeta.style.boxShadow = '0 0 20px rgba(244, 67, 54, 0.4)';
                        } else {
                            if (tarjeta) tarjeta.style.boxShadow = '';
                        }
                        
                        // Guardar en inventario para sincronización
                        inventario.stockBajoCount = count;
                    }
                }
            } catch (error) {
                console.error('[Stats] Error cargando stock bajo:', error);
            }

            // DESHABILITADO: Cargar ventas del mes (causaba lag, no se usa)
            // Comentado porque el usuario indicó que no es útil
            /*
            try {
                const resVentas = await fetch('api/api_admin.php?accion=ventas_mes', {credentials:'include'});
                if (!resVentas.ok) throw new Error(`HTTP ${resVentas.status}`);
                const dataVentas = await resVentas.json();
                console.log('[Stats] Ventas mes:', dataVentas);
                if (dataVentas.success) {
                    const elemTotal = document.getElementById('ventas-mes-total');
                    const elemComp = document.getElementById('ventas-mes-comparativa');
                    if (elemTotal) elemTotal.innerText = moneyFmt.format(dataVentas.total || 0);
                    if (elemComp) {
                        const comparativa = dataVentas.comparativa || 0;
                        const compText = comparativa > 0 ? `+${comparativa}%` : `${comparativa}%`;
                        const compColor = comparativa > 0 ? '#4caf50' : '#f44336';
                        elemComp.innerHTML = `<span style="color:${compColor}">${compText}</span> vs mes anterior`;
                    }
                }
            } catch (error) {
                console.error('[Stats] Error cargando ventas del mes:', error);
            }
            */
            
            console.log('[Stats] Estadísticas cargadas exitosamente');
        } catch (error) {
            console.error('[Stats] Error general:', error);
            if (reintentar) {
                console.log('[Stats] Reintentando en 3 segundos...');
                setTimeout(() => estadisticas.cargarAdmin(false), 3000);
            } else {
                // Ya se reintentó, mostrar mensaje al usuario
                const elemStockBajo = document.getElementById('stock-bajo-count');
                if (elemStockBajo && elemStockBajo.innerText === '0') {
                    elemStockBajo.innerText = '--';
                    elemStockBajo.title = 'No se pudieron cargar las estadísticas. Recarga la página.';
                }
            }
        }
    },
    
    cargarSuperAdmin: async () => {
        try {
            console.log('[Stats] Cargando estadísticas superadmin...');

            const res = await fetch('api/api_admin.php?accion=stats_globales', {credentials:'include'});
            const data = await res.json();
            console.log('[Stats] Stats globales:', data);
            
            if (data.success) {
                const dbSize = document.getElementById('stat-db-size');
                const ventasTotal = document.getElementById('stat-ventas-total');
                const usuariosTotal = document.getElementById('stat-usuarios-total');
                const ultimaVenta = document.getElementById('stat-ultima-venta');
                
                if (dbSize) dbSize.innerText = data.db_size || '--';
                if (ventasTotal) ventasTotal.innerText = moneyFmt.format(data.ventas_total || 0);
                if (usuariosTotal) usuariosTotal.innerText = data.usuarios_total || 0;
                if (ultimaVenta) ultimaVenta.innerText = data.ultima_venta || 'Nunca';
            }
        } catch (error) {
            console.error('[Stats] Error cargando stats superadmin:', error);
        }
    }
};

const mantenimiento = {
    // Sistema de optimización de rendimiento para equipos lentos
    optimizarRendimiento: async () => {
        try {
            // Mostrar modal con opciones
            const result = await Swal.fire({
                title: '⚙️ Optimizar Rendimiento',
                html: `
                    <div style="text-align: left; padding: 20px;">
                        <p style="margin-bottom: 15px;"><strong>Selecciona el nivel de optimización:</strong></p>
                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <label style="display: flex; align-items: center; cursor: pointer;">
                                <input type="radio" name="optimizacion" value="normal" style="margin-right: 10px;">
                                <span><strong>⚪ Normal</strong> - Sin optimizaciones (por defecto)</span>
                            </label>
                            <label style="display: flex; align-items: center; cursor: pointer;">
                                <input type="radio" name="optimizacion" value="ligero" style="margin-right: 10px;">
                                <span><strong>🟢 Ligero</strong> - Desactiva algunas animaciones</span>
                            </label>
                            <label style="display: flex; align-items: center; cursor: pointer;">
                                <input type="radio" name="optimizacion" value="moderado" style="margin-right: 10px;">
                                <span><strong>🟡 Moderado</strong> - Reduce datos en pantalla + menos animaciones</span>
                            </label>
                            <label style="display: flex; align-items: center; cursor: pointer;">
                                <input type="radio" name="optimizacion" value="agresivo" style="margin-right: 10px;">
                                <span><strong>🔴 Agresivo</strong> - Máxima optimización para equipos muy lentos</span>
                            </label>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Aplicar',
                didOpen: () => {
                    // Obtener nivel actual y seleccionar ese radio
                    const nivelActual = localStorage.getItem('optimizacion_nivel') || 'normal';
                    const radioActual = document.querySelector(`input[name="optimizacion"][value="${nivelActual}"]`);
                    if (radioActual) {
                        radioActual.checked = true;
                    } else {
                        // Si no encuentra el actual, selecciona "normal" por defecto
                        const radioNormal = document.querySelector('input[name="optimizacion"][value="normal"]');
                        if (radioNormal) radioNormal.checked = true;
                    }
                }
            });

            if (!result.isConfirmed) return;

            // Obtener nivel seleccionado con validación
            const radioSeleccionado = document.querySelector('input[name="optimizacion"]:checked');
            if (!radioSeleccionado) {
                Notificador.error('Error', 'Por favor selecciona un nivel de optimización');
                return;
            }

            const nivel = radioSeleccionado.value;
            console.log('[Optimización] Nivel seleccionado:', nivel);
            
            // Aplicar optimizaciones
            await mantenimiento.aplicarOptimizaciones(nivel);
            
        } catch (error) {
            Notificador.error('Error', 'Error al optimizar: ' + error.message);
            console.error('[Optimización] Error:', error);
        }
    },

    aplicarOptimizaciones: async (nivel) => {
        try {
            const optimizaciones = {
                normal: {
                    animacionesEnabled: true,
                    shadersEnabled: true,
                    filasTabla: 100,
                    descripcion: '⚪ Optimización desactivada - Modo normal'
                },
                ligero: {
                    animacionesEnabled: false,
                    shadersEnabled: true,
                    filasTabla: 100,
                    descripcion: '🟢 Optimización ligera aplicada'
                },
                moderado: {
                    animacionesEnabled: false,
                    shadersEnabled: false,
                    filasTabla: 50,
                    descripcion: '🟡 Optimización moderada aplicada'
                },
                agresivo: {
                    animacionesEnabled: false,
                    shadersEnabled: false,
                    filasTabla: 25,
                    efectosHover: false,
                    cadenasLargas: true,
                    descripcion: '🔴 Optimización agresiva aplicada'
                }
            };

            const config = optimizaciones[nivel] || optimizaciones.normal;

            // 1. Guardar configuración en localStorage
            localStorage.setItem('optimizacion_nivel', nivel);
            localStorage.setItem('animaciones_enabled', config.animacionesEnabled.toString());
            localStorage.setItem('shaders_enabled', config.shadersEnabled.toString());
            localStorage.setItem('filas_tabla_max', config.filasTabla.toString());

            // 2. Aplicar cambios CSS dinámicamente
            let styleID = document.getElementById('optimizacion-estilos');
            if (styleID) styleID.remove();

            const style = document.createElement('style');
            style.id = 'optimizacion-estilos';
            style.innerHTML = `
                /* Preservar interactividad en todos los niveles */
                input[type="radio"], input[type="checkbox"], button, a, select, textarea, input[type="text"], input[type="email"], input[type="number"] {
                    pointer-events: auto !important;
                }

                /* Desactivar transiciones suaves si está optimizado */
                ${!config.animacionesEnabled ? `
                    body, body * {
                        transition: none !important;
                        animation: none !important;
                    }
                ` : `
                    body * {
                        transition: all 0.3s ease;
                    }
                `}
                
                /* Desactivar efectos visuales complejos */
                ${!config.shadersEnabled ? `
                    .btn:hover, button:hover { 
                        transform: none !important;
                    }
                    input:focus, select:focus, textarea:focus { 
                        box-shadow: inset 0 0 0 1px #1976D2 !important;
                    }
                    .tabla-wrap { 
                        box-shadow: none !important; 
                    }
                ` : `
                    .btn:hover, button:hover { 
                        transform: translateY(-2px); 
                    }
                    input:focus, select:focus, textarea:focus { 
                        box-shadow: 0 0 0 3px rgba(13, 71, 161, 0.1); 
                    }
                `}
                
                /* Reducir sombras en optimización agresiva */
                ${nivel === 'agresivo' ? `
                    .sombra, .card, .modal-content {
                        box-shadow: none !important;
                    }
                    * {
                        filter: none !important;
                    }
                ` : ''}
            `;
            document.head.appendChild(style);

            // 3. Limpiar cachés y objetos globales
            await mantenimiento.limpiarMemoria();

            // 4. Guardar en servidor
            try {
                const res = await fetchWithCSRF('api/api_admin.php?accion=guardar_optimizacion', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ nivel: nivel })
                });

                const data = await res.json();
                console.log('[Optimización] Respuesta del servidor:', data);
                
                if (data.success) {
                    Notificador.success('✅ Optimización Aplicada', config.descripcion);
                    console.log('[Optimización] Cambios guardados. Recargando en 2 segundos...');
                    
                    // Recargar página después de 2 segundos
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    Notificador.error('⚠️ Error en Servidor', data.message || 'No se pudo guardar la configuración');
                    console.error('[Optimización] Error del servidor:', data);
                }
            } catch (serverError) {
                console.error('[Optimización] Error de servidor:', serverError);
                Notificador.error('⚠️ Error de Conexión', 'No se pudo contactar el servidor, pero la optimización se aplicó localmente');
            }

        } catch (error) {
            Notificador.error('❌ Error', 'Error en optimización: ' + error.message);
            console.error('[Optimización] Error general:', error);
            logError('Optimizar Rendimiento', error.message);
        }
    },

    limpiarMemoria: async () => {
        try {
            // 1. Limpiar localStorage de datos temporales
            const keysToKeep = ['optimizacion_nivel', 'animaciones_enabled', 'shaders_enabled', 'csrf_token', 'user_role', 'usuario'];
            Object.keys(localStorage).forEach(key => {
                if (!keysToKeep.includes(key) && !key.startsWith('log_')) {
                    localStorage.removeItem(key);
                }
            });

            // 2. Limpiar sessionStorage innecesario
            const sessionKeysToKeep = ['csrf_token', 'user_role', 'usuario'];
            Object.keys(sessionStorage).forEach(key => {
                if (!sessionKeysToKeep.includes(key)) {
                    sessionStorage.removeItem(key);
                }
            });

            // 3. Limpiar arrays de caché globales (si existen)
            if (typeof inventario !== 'undefined' && inventario.cache) {
                inventario.cache = [];
            }
            if (typeof registros !== 'undefined' && registros.cache) {
                registros.cache = [];
            }
            if (typeof septimas !== 'undefined' && septimas.cache) {
                septimas.cache = [];
            }

            // 4. Forzar garbage collection (en navegadores que lo soportan)
            if (window.gc) {
                window.gc();
            }

            console.log('[Optimización] Memoria limpiada');
        } catch (error) {
            console.error('[Optimización] Error limpiando memoria:', error);
        }
    },

    obtenerDiagnostico: async () => {
        try {
            // Función auxiliar para medir FPS (simplificada)
            const medirFPS = () => {
                return new Promise(resolve => {
                    let frameCount = 0;
                    let lastTime = performance.now();
                    let timeout = false;
                    
                    // Timeout después de 2 segundos para no quedarse colgado
                    const timeoutId = setTimeout(() => {
                        timeout = true;
                        resolve(frameCount > 0 ? frameCount : 60);
                    }, 2000);
                    
                    const countFrames = () => {
                        if (timeout) {
                            clearTimeout(timeoutId);
                            return;
                        }
                        
                        frameCount++;
                        const currentTime = performance.now();
                        if (currentTime >= lastTime + 1000) {
                            clearTimeout(timeoutId);
                            resolve(frameCount);
                            return;
                        }
                        requestAnimationFrame(countFrames);
                    };
                    
                    requestAnimationFrame(countFrames);
                });
            };

            // Detectar capacidades del navegador/equipo
            const diagnostico = {
                navegador: {
                    nombre: navigator.userAgent.includes('Chrome') ? 'Chrome' : 
                            navigator.userAgent.includes('Firefox') ? 'Firefox' :
                            navigator.userAgent.includes('Safari') ? 'Safari' : 'Otro',
                    memoria: navigator.deviceMemory || 'Desconocida',
                    nucleos: navigator.hardwareConcurrency || 'Desconocidos',
                    conexion: navigator.connection?.effectiveType || 'Desconocida'
                },
                pantalla: {
                    ancho: window.innerWidth,
                    alto: window.innerHeight,
                    pixelRatio: window.devicePixelRatio
                },
                rendimiento: {
                    fps: await medirFPS(),
                    memoriaUsada: performance.memory ? Math.round(performance.memory.usedJSHeapSize / 1048576) : 'N/A',
                    memoriaTotal: performance.memory ? Math.round(performance.memory.jsHeapSizeLimit / 1048576) : 'N/A'
                },
                almacenamiento: {
                    localStorageUsado: Math.round(new Blob(Object.values(localStorage)).size / 1024),
                    sessionStorageUsado: Math.round(new Blob(Object.values(sessionStorage)).size / 1024)
                }
            };

            return diagnostico;
        } catch (error) {
            console.error('Error obteniendo diagnóstico:', error);
            return null;
        }
    },

    medirFPS: async () => {
        return new Promise(resolve => {
            let frameCount = 0;
            let lastTime = performance.now();
            
            const countFrames = () => {
                frameCount++;
                const currentTime = performance.now();
                if (currentTime >= lastTime + 1000) {
                    resolve(frameCount);
                    return;
                }
                requestAnimationFrame(countFrames);
            };
            
            requestAnimationFrame(countFrames);
        });
    },

    mostrarDiagnostico: async () => {
        try {
            console.log('[Diagnóstico] Iniciando...');
            const diagnostico = await mantenimiento.obtenerDiagnostico();
            const nivelActual = localStorage.getItem('optimizacion_nivel') || 'Ninguno';

            console.log('[Diagnóstico] Datos obtenidos:', diagnostico);

            if (diagnostico) {
                const html = `
                    <div style="text-align: left; font-size: 0.9rem; line-height: 1.8;">
                        <h4 style="margin-top: 0; color: var(--color-primario);">📊 Diagnóstico de Rendimiento</h4>
                        
                        <p><strong>Nivel Optimización Actual:</strong> ${nivelActual}</p>
                        
                        <h5>🖥️ Navegador</h5>
                        <ul style="margin: 5px 0; padding-left: 20px;">
                            <li><strong>Nombre:</strong> ${diagnostico.navegador.nombre}</li>
                            <li><strong>Memoria RAM:</strong> ${diagnostico.navegador.memoria} GB</li>
                            <li><strong>Núcleos CPU:</strong> ${diagnostico.navegador.nucleos}</li>
                            <li><strong>Conexión:</strong> ${diagnostico.navegador.conexion}</li>
                        </ul>

                        <h5>📱 Pantalla</h5>
                        <ul style="margin: 5px 0; padding-left: 20px;">
                            <li><strong>Resolución:</strong> ${diagnostico.pantalla.ancho}x${diagnostico.pantalla.alto}</li>
                            <li><strong>Pixel Ratio:</strong> ${diagnostico.pantalla.pixelRatio}</li>
                        </ul>

                        <h5>⚡ Rendimiento</h5>
                        <ul style="margin: 5px 0; padding-left: 20px;">
                            <li><strong>FPS:</strong> ${diagnostico.rendimiento.fps}</li>
                            <li><strong>Memoria JS Usada:</strong> ${diagnostico.rendimiento.memoriaUsada} MB / ${diagnostico.rendimiento.memoriaTotal} MB</li>
                        </ul>

                        <h5>💾 Almacenamiento</h5>
                        <ul style="margin: 5px 0; padding-left: 20px;">
                            <li><strong>LocalStorage:</strong> ${diagnostico.almacenamiento.localStorageUsado} KB</li>
                            <li><strong>SessionStorage:</strong> ${diagnostico.almacenamiento.sessionStorageUsado} KB</li>
                        </ul>

                        <p style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd; color: #666; font-size: 0.85rem;">
                            ${diagnostico.rendimiento.fps < 30 ? '⚠️ <strong>ALERTA:</strong> Bajo FPS detectado (' + diagnostico.rendimiento.fps + ' FPS). Considera usar optimización moderada o agresiva.' : '✅ Rendimiento normal detectado (' + diagnostico.rendimiento.fps + ' FPS)'}
                        </p>
                    </div>
                `;

                const c = Notificador.getConfig();
                await Swal.fire({
                    title: '🔍 Diagnóstico del Sistema',
                    html: html,
                    icon: 'info',
                    confirmButtonText: 'Entendido',
                    width: '600px',
                    background: c.bg,
                    color: c.color
                });
            } else {
                Notificador.error('Error', 'No se pudieron obtener los datos del diagnóstico');
            }
        } catch (error) {
            console.error('[Diagnóstico] Error:', error);
            Notificador.error('Error', 'Error obteniendo diagnóstico: ' + error.message);
        }
    },

    ejecutarPruebasSistema: async () => {
        const output = document.getElementById('pruebas-sistema-resumen');
        if (output) {
            output.className = 'config-test-result';
            output.textContent = 'Ejecutando pruebas...';
        }

        const resultados = [];
        const add = (nombre, estado, detalle) => resultados.push({ nombre, estado, detalle });

        try {
            // 1) Conectividad base
            try {
                const resp = await fetch(`ping.php?t=${Date.now()}`, { cache: 'no-store' });
                const data = await resp.json();
                if (resp.ok && data.ok) add('Ping sistema', 'ok', 'Conectividad lista');
                else add('Ping sistema', 'fail', data.error || `HTTP ${resp.status}`);
            } catch (e) {
                add('Ping sistema', 'fail', e.message);
            }

            // 2) Salud sistema
            try {
                const resp = await fetchWithCSRF(`api/api_admin.php?accion=salud_sistema&t=${Date.now()}`);
                const data = await resp.json();
                if (resp.ok && data.success) {
                    add('Salud de sistema', 'ok', `BD ${data.db_size_mb} MB | Usuarios ${data.usuarios_total}`);
                } else {
                    add('Salud de sistema', 'fail', data.message || `HTTP ${resp.status}`);
                }
            } catch (e) {
                add('Salud de sistema', 'fail', e.message);
            }

            // 3) Integridad inventario
            try {
                const resp = await fetchWithCSRF(`api/api_inventario.php?accion=verificar_integridad&t=${Date.now()}`);
                const data = await resp.json();
                if (resp.ok && data.success) {
                    if (Array.isArray(data.problemas) && data.problemas.length > 0) {
                        add('Integridad de inventario', 'warn', `${data.problemas.length} posible(s) inconsistencia(s)`);
                    } else {
                        add('Integridad de inventario', 'ok', 'Sin inconsistencias');
                    }
                } else {
                    add('Integridad de inventario', 'fail', data.message || `HTTP ${resp.status}`);
                }
            } catch (e) {
                add('Integridad de inventario', 'fail', e.message);
            }

            // 4) Seguridad sesión
            const token = sessionStorage.getItem('csrf_token');
            if (token && token.length > 12) add('Token CSRF', 'ok', 'Token presente');
            else add('Token CSRF', 'warn', 'Token no detectado (recomendado relogin)');

            // 5) Almacenamiento local
            try {
                const key = `tg_test_${Date.now()}`;
                localStorage.setItem(key, 'ok');
                const val = localStorage.getItem(key);
                localStorage.removeItem(key);
                if (val === 'ok') add('Almacenamiento local', 'ok', 'Lectura/escritura correcta');
                else add('Almacenamiento local', 'warn', 'Validacion parcial');
            } catch (e) {
                add('Almacenamiento local', 'fail', e.message);
            }

            // 6) Componentes UI críticos
            const criticos = ['btn-optimizar-bd', 'btn-respaldo-db', 'btn-diagnostico-rendimiento', 'btn-optimizar-app'];
            const faltantes = criticos.filter(id => !document.getElementById(id));
            if (faltantes.length === 0) add('Componentes UI', 'ok', 'Todos los controles críticos están activos');
            else add('Componentes UI', 'warn', `Faltan: ${faltantes.join(', ')}`);

            const fails = resultados.filter(r => r.estado === 'fail').length;
            const warns = resultados.filter(r => r.estado === 'warn').length;
            const oks = resultados.filter(r => r.estado === 'ok').length;

            if (output) {
                output.className = `config-test-result ${fails > 0 ? 'fail' : warns > 0 ? 'warn' : 'ok'}`;
                output.textContent = `OK: ${oks} | Alertas: ${warns} | Fallas: ${fails}`;
            }

            const rows = resultados.map(r => {
                const icon = r.estado === 'ok' ? '✅' : r.estado === 'warn' ? '⚠️' : '❌';
                return `<tr>
                    <td style="padding:8px 6px; border-bottom:1px solid rgba(148,163,184,0.2);">${icon} ${r.nombre}</td>
                    <td style="padding:8px 6px; border-bottom:1px solid rgba(148,163,184,0.2);">${r.detalle}</td>
                </tr>`;
            }).join('');

            const c = Notificador.getConfig();
            await Swal.fire({
                title: 'Resultados de Pruebas de Sistema',
                html: `
                    <div style="text-align:left;">
                        <p style="margin:0 0 10px 0;"><strong>Resumen:</strong> OK ${oks} | Alertas ${warns} | Fallas ${fails}</p>
                        <div style="max-height:320px; overflow:auto; border:1px solid rgba(148,163,184,0.3); border-radius:8px;">
                            <table style="width:100%; border-collapse:collapse; margin:0; box-shadow:none; border:none;">
                                <thead>
                                    <tr>
                                        <th style="text-align:left;">Prueba</th>
                                        <th style="text-align:left;">Resultado</th>
                                    </tr>
                                </thead>
                                <tbody>${rows}</tbody>
                            </table>
                        </div>
                    </div>
                `,
                icon: fails > 0 ? 'error' : warns > 0 ? 'warning' : 'success',
                confirmButtonText: 'Entendido',
                background: c.bg,
                color: c.color,
                width: '760px'
            });

            if (fails === 0 && warns === 0) {
                Notificador.success('Pruebas completadas', 'Todo funcionando correctamente');
            } else if (fails === 0) {
                Notificador.warning('Pruebas completadas', 'Hay alertas por revisar');
            } else {
                Notificador.error('Pruebas completadas con fallas', 'Revisa el detalle y corrige antes de continuar');
            }
        } catch (error) {
            if (output) {
                output.className = 'config-test-result fail';
                output.textContent = 'Error ejecutando pruebas';
            }
            Notificador.error('Error en pruebas de sistema', error.message || 'Error desconocido');
            logError('Pruebas Sistema', error.message || error);
        }
    },
    
    optimizarBD: async () => {
        if (!await Notificador.confirm('⚙️ Optimizar BD', 'Esto puede tardar unos segundos pero mejorará el rendimiento')) return;
        
        try {
            const res = await fetchWithCSRF('api/api_admin.php?accion=optimizar_bd', { method: 'POST' });
            const data = await res.json();
            
            if (data.success) {
                Notificador.success('✅ Optimización Completa', `Ahorro: ${data.ahorro || '0 KB'}`);
                // Recargar salud del sistema
                const cargarSalud = async () => {
                    const resp = await fetchWithCSRF('api/api_admin.php?accion=salud_sistema');
                    const saludData = await resp.json();
                    if (saludData.success) {
                        document.getElementById('salud-db-size').textContent = saludData.db_size_mb + ' MB';
                        document.getElementById('salud-productos-activos').textContent = saludData.productos_activos;
                        document.getElementById('salud-ventas-total').textContent = '$' + parseFloat(saludData.ventas_total).toFixed(2);
                    }
                };
                setTimeout(cargarSalud, 1000);
            } else {
                Notificador.error('❌ Error', data.message || 'No se pudo optimizar');
            }
        } catch (error) {
            Notificador.error('❌ Error', 'No se pudo optimizar la BD: ' + error.message);
            console.error('Error optimizando BD:', error);
            logError('Optimizar BD', error.message);
        }
    },
    
    resetearDemo: async () => {
        const paso1 = await Swal.fire({
            title: '☠️ ADVERTENCIA CRÍTICA',
            html: 'Esto eliminará <b>TODOS los datos</b> excepto el usuario admin principal.<br><br>¿Realmente quieres continuar?',
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Sí, estoy seguro'
        });
        
        if (!paso1.isConfirmed) return;
        
        const paso2 = await Swal.fire({
            title: 'Confirmación Final',
            input: 'text',
            inputLabel: 'Escribe "RESETEAR" para confirmar',
            inputPlaceholder: 'RESETEAR',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            inputValidator: (value) => {
                if (value !== 'RESETEAR') {
                    return 'Debes escribir exactamente "RESETEAR"';
                }
            }
        });
        
        if (!paso2.isConfirmed) return;
        
        try {
            const res = await fetchWithCSRF('api/api_admin.php?accion=resetear_sistema', { method: 'POST' });
            const data = await res.json();
            
            if (data.success) {
                await Swal.fire({
                    icon: 'success',
                    title: 'Sistema Reseteado',
                    text: 'Se eliminaron todos los datos. Recargando...',
                    timer: 2000,
                    showConfirmButton: false
                });
                window.location.reload();
            } else {
                Notificador.error('Error', data.message);
            }
        } catch (error) {
            Notificador.error('Error', 'No se pudo resetear');
            logError('Resetear Sistema', error.message);
        }
    }
};

// ========================================
// MÓDULO: EXPORTACIÓN (CSV/JSON)
// ========================================
const exportacion = {
    async descargarCSV() {
        try {
            const resp = await fetchWithCSRF('api/api_reportes.php?accion=exportar&formato=csv');
            
            // Verificar si es JSON (error del servidor)
            const contentType = resp.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                const data = await resp.json();
                if (!data.success) {
                    const cfg = Notificador.getConfig();
                    await Swal.fire({
                        icon: 'info',
                        title: '📊 Sin datos para exportar',
                        html: `<div style="text-align:left; margin:15px 0;">
                            <p><strong>${data.message || 'No hay ventas para exportar'}</strong></p>
                            <p style="opacity:0.7; margin-top:15px;">Necesitas:</p>
                            <ul style="margin:10px 0; padding-left:20px;">
                                <li>✓ Registrar al menos una venta</li>
                                <li>✓ Tener productos en inventario</li>
                            </ul>
                            <p style="opacity:0.7; margin-top:15px; font-size:0.9rem;">
                                💡 Crea una venta primero y luego descarga el reporte.
                            </p>
                        </div>`,
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#0d47a1',
                        background: cfg.bg,
                        color: cfg.color
                    });
                    return;
                }
            }
            
            if (!resp.ok) throw new Error('Error al generar CSV');
            
            const blob = await resp.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `ventas_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
            
            Notificador.success('✓ CSV descargado');
        } catch (error) {
            console.error('[Exportación CSV] Error:', error);
            logError('Exportación CSV', error.message);
            const cfg = Notificador.getConfig();
            Swal.fire({
                icon: 'error',
                title: '❌ Error al descargar',
                text: error.message || 'No se pudo generar el CSV. Intenta de nuevo.',
                confirmButtonText: 'Reintentar',
                confirmButtonColor: '#f44336',
                background: cfg.bg,
                color: cfg.color
            });
        }
    },
    
    async descargarJSON() {
        try {
            const resp = await fetchWithCSRF('api/api_reportes.php?accion=exportar&formato=json');
            
            // Verificar si es JSON (error del servidor)
            const contentType = resp.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                const data = await resp.json();
                if (!data.success) {
                    const cfg = Notificador.getConfig();
                    await Swal.fire({
                        icon: 'info',
                        title: '📊 Sin datos para exportar',
                        html: `<div style="text-align:left; margin:15px 0;">
                            <p><strong>${data.message || 'No hay datos disponibles'}</strong></p>
                            <p style="opacity:0.7; margin-top:15px;">El sistema necesita:</p>
                            <ul style="margin:10px 0; padding-left:20px;">
                                <li>✓ Al menos 1 producto</li>
                                <li>✓ Al menos 1 venta registrada</li>
                                <li>✓ Datos en el período</li>
                            </ul>
                            <p style="opacity:0.7; margin-top:15px; font-size:0.9rem;">
                                💡 Crea algunos datos de prueba e intenta nuevamente.
                            </p>
                        </div>`,
                        confirmButtonText: 'Entendido',
                        confirmButtonColor: '#0d47a1',
                        background: cfg.bg,
                        color: cfg.color
                    });
                    return;
                }
            }
            
            if (!resp.ok) throw new Error('Error al generar JSON');
            
            const blob = await resp.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `datos_${new Date().toISOString().split('T')[0]}.json`;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
            
            Notificador.success('✓ JSON descargado');
        } catch (error) {
            console.error('[Exportación JSON] Error:', error);
            logError('Exportación JSON', error.message);
            const cfg = Notificador.getConfig();
            Swal.fire({
                icon: 'error',
                title: '❌ Error al descargar',
                text: error.message || 'No se pudo generar el JSON. Intenta de nuevo.',
                confirmButtonText: 'Reintentar',
                confirmButtonColor: '#f44336',
                background: cfg.bg,
                color: cfg.color
            });
        }
    }
};

const cuentas = {
    listData: [],

    normalizarNombre(v) {
        return String(v || '')
            .trim()
            .toLowerCase()
            .replace(/[\.\,]+/g, '')
            .replace(/\s+/g, ' ');
    },

    componerNombreCuenta(nombre, apellidoInicial = '') {
        const base = String(nombre || '').trim();
        const inicial = String(apellidoInicial || '').trim().charAt(0).toUpperCase();
        return inicial ? `${base} ${inicial}.` : base;
    },

    descomponerNombreCuenta(nombreCompleto = '') {
        const valor = String(nombreCompleto || '').trim();
        const match = valor.match(/^(.*?)(?:\s+([A-Za-z])\.)?$/);
        return {
            nombre: match ? (match[1] || valor).trim() : valor,
            apellidoInicial: match && match[2] ? match[2].toUpperCase() : ''
        };
    },

    extraerRegionDesdeNotas(notas) {
        const txt = String(notas || '');
        const m = txt.match(/(?:REGION|REGIÓN|GRUPO)\s*:\s*([^\n|;]+)/i);
        return m ? m[1].trim() : '';
    },

    construirNotasConRegion(region, notasPrevias = '') {
        const limpia = String(notasPrevias || '')
            .replace(/(?:REGION|REGIÓN|GRUPO)\s*:\s*([^\n|;]+)\s*[|;]?/ig, '')
            .trim();
        const regTxt = region ? `REGION: ${region}` : '';
        return [regTxt, limpia].filter(Boolean).join(' | ');
    },

    async mostrarFormularioCuenta(titulo, inicial = {}) {
        const opcionesGrupo = ['<option value="">Selecciona una región</option>']
            .concat(TODOS_GRUPOS.map(grupo => {
                const selected = String(inicial.region || '') === grupo ? ' selected' : '';
                return `<option value="${grupo}"${selected}>${grupo}</option>`;
            }))
            .join('');
        const html = `
            <div style="display:grid; gap:10px; text-align:left;">
                <label>Nombre</label>
                <input id="sw-cuenta-nombre" class="swal2-input" style="margin:0; width:100%;" value="${String(inicial.nombre || '').replace(/"/g, '&quot;')}">
                <label>Inicial del apellido</label>
                <input id="sw-cuenta-apellido" class="swal2-input" style="margin:0; width:100%;" value="${String(inicial.apellidoInicial || '').replace(/"/g, '&quot;')}" maxlength="1">
                <label>Grupo o Región</label>
                <select id="sw-cuenta-region" class="swal2-input" style="margin:0; width:100%;">${opcionesGrupo}</select>
                <label>Número</label>
                <input id="sw-cuenta-numero" class="swal2-input" style="margin:0; width:100%;" value="${String(inicial.numero || '').replace(/"/g, '&quot;')}">
            </div>
        `;

        const result = await Swal.fire({
            title: titulo,
            html,
            showCancelButton: true,
            confirmButtonText: 'Guardar',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const nombre = (document.getElementById('sw-cuenta-nombre').value || '').trim();
                const apellidoInicial = (document.getElementById('sw-cuenta-apellido').value || '').trim();
                const region = (document.getElementById('sw-cuenta-region').value || '').trim();
                const numero = (document.getElementById('sw-cuenta-numero').value || '').trim();
                if (!nombre) {
                    Swal.showValidationMessage('Nombre requerido');
                    return false;
                }
                if (!region) {
                    Swal.showValidationMessage('Selecciona una región');
                    return false;
                }
                if (!numero) {
                    Swal.showValidationMessage('Número obligatorio');
                    return false;
                }
                return { nombre, apellidoInicial, region, numero };
            }
        });

        return result.isConfirmed ? result.value : null;
    },

    async load() {
        try {
            const tbody = document.getElementById('cuerpo-tabla-cuentas');
            if (!tbody) return;
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>';

            const [respCuentas, respDeudas] = await Promise.all([
                fetch('api/api_ventas.php?accion=listar_cuentas&estado=activo', {credentials:'include'}),
                fetch('api/api_ventas.php?accion=listar_fiados', {credentials:'include'})
            ]);

            const dataCuentas = await respCuentas.json();
            const dataDeudas = await respDeudas.json();
            if (!respCuentas.ok || !dataCuentas.success) throw new Error(dataCuentas.message || 'Error al cargar cuentas');
            if (!respDeudas.ok || !dataDeudas.success) throw new Error(dataDeudas.message || 'Error al cargar deudas a cuenta');

            const cuentasDb = dataCuentas.cuentas || [];
            const deudas = dataDeudas.deudores || [];
            const merged = [];
            const mapPorNombre = new Map();

            cuentasDb.forEach((c) => {
                const nombre = c.nombre_cuenta || '';
                const row = {
                    id: c.id,
                    nombre,
                    celular: c.celular || '',
                    region: this.extraerRegionDesdeNotas(c.notas || ''),
                    notas: c.notas || '',
                    saldo: parseFloat(c.saldo_total || 0),
                    origen: 'cuenta',
                    fecha_ultimo_compra: c.fecha_ultimo_compra || c.fecha_creacion || ''
                };
                mapPorNombre.set(this.normalizarNombre(nombre), row);
                merged.push(row);
            });

            deudas.forEach((f) => {
                const nombre = f.nombre_fiado || '';
                const key = this.normalizarNombre(nombre);
                const deuda = parseFloat(f.total_deuda || 0);
                const existente = mapPorNombre.get(key);
                if (existente) {
                    existente.saldo = deuda > 0 ? deuda : existente.saldo;
                    existente.origen = 'cuenta';
                } else {
                    const row = {
                        id: null,
                        nombre,
                        celular: '',
                        region: f.grupo_fiado || '',
                        notas: f.grupo_fiado ? `REGION: ${f.grupo_fiado}` : '',
                        saldo: deuda,
                        origen: 'venta_credito',
                        fecha_ultimo_compra: ''
                    };
                    merged.push(row);
                    mapPorNombre.set(key, row);
                }
            });

            this.listData = merged.sort((a, b) => b.saldo - a.saldo || a.nombre.localeCompare(b.nombre));

            tbody.innerHTML = '';
            let saldoTotal = 0;

            if (this.listData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:20px; opacity:0.5;"><i class="fas fa-inbox"></i> Sin cuentas. Crea una nueva.</td></tr>';
            } else {
                this.listData.forEach((c) => {
                    saldoTotal += c.saldo;
                    const nombreSeg = c.nombre.replace(/'/g, "\\'");
                    const mov = c.saldo > 0 ? 'Compra a cuenta' : 'Sin adeudo';
                    const colorSaldo = c.saldo > 0 ? 'var(--color-danger)' : 'var(--color-success)';
                    const bg = c.saldo > 0 ? 'rgba(255,193,7,0.12)' : 'rgba(76,175,80,0.08)';
                    tbody.innerHTML += `<tr style="background:${bg};">
                        <td style="font-weight:600;">${c.nombre}</td>
                        <td>${c.celular || '-'}</td>
                        <td>${c.region || '-'}</td>
                        <td>${mov}</td>
                        <td style="text-align:right; color:${colorSaldo}; font-weight:bold;">${moneyFmt.format(c.saldo)}</td>
                        <td style="text-align:center; white-space:nowrap;">
                            <button class="btn-icon btn-success" onclick="cuentas.pagarCuenta('${nombreSeg}')" title="Pagado"><i class="fas fa-dollar-sign"></i></button>
                                    <button class="btn-icon btn-warning" onclick="cuentas.abrirEnVentas('${nombreSeg}')" title="Agregar"><i class="fas fa-plus"></i></button>
                                    <button class="btn-icon btn-info" onclick="cuentas.verHistorialCuenta('${nombreSeg}')" title="Historial"><i class="fas fa-history"></i></button>
                                    <button class="btn-icon btn-edit" onclick="cuentas.editarCuenta('${nombreSeg}')" title="Editar"><i class="fas fa-pen"></i></button>
                            <button class="btn-icon btn-delete" onclick="cuentas.eliminarCuenta('${nombreSeg}')" title="Eliminar"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>`;
                });
            }

            document.getElementById('cuentas-total').textContent = this.listData.length;
            document.getElementById('cuentas-saldo-total').textContent = moneyFmt.format(saldoTotal);
        } catch (error) {
            console.error('Error cargando cuentas:', error);
            Notificador.error('Error', error.message);
        }
    },

    getCuentaPorNombre(nombre) {
        const key = this.normalizarNombre(nombre);
        return this.listData.find(c => this.normalizarNombre(c.nombre) === key) || null;
    },

    async asegurarCuentaId(nombre, celular = '') {
        const actual = this.getCuentaPorNombre(nombre);
        if (actual && actual.id) return actual.id;

        const descompuesta = this.descomponerNombreCuenta(nombre);
        const params = new URLSearchParams({
            accion: 'crear_cuenta',
            nombre_cuenta: this.componerNombreCuenta(descompuesta.nombre, descompuesta.apellidoInicial),
            celular: celular,
            notas: this.construirNotasConRegion(actual?.region || '', actual?.notas || ''),
            csrf_token: sessionStorage.getItem('csrf_token') || ''
        });

        const resp = await fetchWithCSRF('api/api_ventas.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        });
        const data = await resp.json();
        if (!data.success) throw new Error(data.message || 'No se pudo preparar la cuenta');
        await this.load();
        const creada = this.getCuentaPorNombre(nombre);
        if (!creada || !creada.id) throw new Error('No se pudo obtener la cuenta creada');
        return creada.id;
    },

    async pedirDatosPago(nombre, saldoActual) {
        const html = `
            <div style="text-align:left; display:grid; gap:10px;">
                <label>Monto a cobrar</label>
                <input id="sw-pago-monto" type="number" min="0.01" step="0.01" value="${Math.max(0, Number(saldoActual || 0)).toFixed(2)}" class="swal2-input" style="margin:0; width:100%;">
                <label>Método de pago</label>
                <select id="sw-pago-metodo" class="swal2-input" style="margin:0; width:100%;">
                    <option value="efectivo">Efectivo</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="transferencia">Transferencia</option>
                </select>
                <label>Referencia (opcional)</label>
                <input id="sw-pago-referencia" type="text" class="swal2-input" style="margin:0; width:100%;" placeholder="Últimos dígitos o folio">
            </div>
        `;

        const result = await Swal.fire({
            title: `Pagado: ${nombre}`,
            html,
            focusConfirm: false,
            showCancelButton: true,
            confirmButtonText: 'Registrar pago',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const monto = parseFloat(document.getElementById('sw-pago-monto').value || '0');
                const metodo = document.getElementById('sw-pago-metodo').value;
                const referencia = (document.getElementById('sw-pago-referencia').value || '').trim();
                if (monto <= 0) {
                    Swal.showValidationMessage('Monto inválido');
                    return false;
                }
                return { monto, metodo, referencia };
            }
        });

        return result.isConfirmed ? result.value : null;
    },

    async pagarCuenta(nombre) {
        try {
            const cuenta = this.getCuentaPorNombre(nombre);
            if (!cuenta) return Notificador.error('Cuenta no encontrada');
            if (cuenta.saldo <= 0) return Notificador.info('Sin adeudo', 'La cuenta no tiene saldo pendiente');

            const datosPago = await this.pedirDatosPago(cuenta.nombre, cuenta.saldo);
            if (!datosPago) return;

            if (cuenta.id) {
                const params = new URLSearchParams({
                    accion: 'pagar_cuenta',
                    id_cuenta: cuenta.id,
                    monto_pagado: String(datosPago.monto),
                    metodo_pago: datosPago.metodo,
                    referencia_pago: datosPago.referencia,
                    csrf_token: sessionStorage.getItem('csrf_token') || ''
                });

                const resp = await fetchWithCSRF('api/api_ventas.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: params.toString()
                });
                const data = await resp.json();
                if (!data.success) throw new Error(data.message || 'No se pudo registrar el pago');
            } else {
                const fd = new FormData();
                fd.append('accion', 'pagar_fiado');
                fd.append('nombre_fiado', cuenta.nombre);
                fd.append('monto_pagado', String(datosPago.monto));
                fd.append('metodo_pago', datosPago.metodo);
                fd.append('referencia_pago', datosPago.referencia);
                appendCsrfToFormData(fd);

                const resp = await fetchWithCSRF('api/api_ventas.php', { method: 'POST', body: fd });
                const data = await resp.json();
                if (!data.success) throw new Error(data.message || 'No se pudo registrar el pago');
            }

            Notificador.success('✓ Pago registrado');
            await this.load();
            if (registros && registros.load) registros.load();
        } catch (error) {
            Notificador.error('Error', error.message);
        }
    },

    async agregarCuenta(nombre) {
        return this.abrirEnVentas(nombre);
    },

    async abrirEnVentas(nombre) {
        try {
            const cuenta = this.getCuentaPorNombre(nombre);
            if (!cuenta) return Notificador.error('Cuenta no encontrada');
            await this.agregarProductoACuenta(cuenta);
        } catch (error) {
            Notificador.error('Error', error.message);
        }
    },

    async cargarProductosCatalogo() {
        if (inventario.listData && inventario.listData.length) return inventario.listData;
        const resp = await fetch('api/api_inventario.php?accion=listar', {credentials:'include'});
        const data = await resp.json();
        if (!resp.ok || !data.success) throw new Error(data.message || 'No se pudo cargar el inventario');
        inventario.listData = data.productos || [];
        return inventario.listData;
    },

    async agregarProductoACuenta(cuenta) {
        const cuentaObj = typeof cuenta === 'string' ? this.getCuentaPorNombre(cuenta) : cuenta;
        if (!cuentaObj) return Notificador.error('Cuenta no encontrada');

        try {
            const productos = await this.cargarProductosCatalogo();
            let productoSeleccionado = null;

            const html = `
                <div style="display:grid; gap:10px; text-align:left;">
                    <div>
                        <label style="display:block; margin-bottom:6px; font-weight:600;">Cuenta</label>
                        <input class="swal2-input" style="margin:0; width:100%;" value="${String(cuentaObj.nombre || '').replace(/"/g, '&quot;')}" readonly>
                    </div>
                    <div>
                        <label style="display:block; margin-bottom:6px; font-weight:600;">Buscar producto</label>
                        <input id="sw-cuenta-producto-buscar" class="swal2-input" style="margin:0; width:100%;" placeholder="Escribe nombre, código o tipo">
                    </div>
                    <div id="sw-cuenta-producto-resultados" style="max-height:220px; overflow:auto; border:1px solid var(--color-borde); border-radius:10px; background:var(--color-fondo); text-align:left;"></div>
                    <div>
                        <label style="display:block; margin-bottom:6px; font-weight:600;">Producto seleccionado</label>
                        <input id="sw-cuenta-producto-nombre" class="swal2-input" style="margin:0; width:100%;" placeholder="Selecciona un producto" readonly>
                    </div>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
                        <div>
                            <label style="display:block; margin-bottom:6px; font-weight:600;">Cantidad</label>
                            <input id="sw-cuenta-producto-cantidad" type="number" min="1" step="1" class="swal2-input" style="margin:0; width:100%;" value="1">
                        </div>
                        <div>
                            <label style="display:block; margin-bottom:6px; font-weight:600;">Precio</label>
                            <input id="sw-cuenta-producto-precio" type="number" min="0" step="0.01" class="swal2-input" style="margin:0; width:100%;" value="0.00">
                        </div>
                    </div>
                </div>
            `;

            const renderProductos = (texto = '') => {
                const q = this.normalizarNombre(texto);
                const resultados = productos.filter((p) => {
                    const nombre = this.normalizarNombre(p.nombre);
                    const tipo = this.normalizarNombre(p.tipo_producto);
                    const codigo = this.normalizarNombre(p.codigo || p.sku || '');
                    return !q || nombre.includes(q) || tipo.includes(q) || codigo.includes(q);
                }).slice(0, 12);

                const cont = document.getElementById('sw-cuenta-producto-resultados');
                if (!cont) return;
                if (!resultados.length) {
                    cont.innerHTML = '<div style="padding:12px; opacity:0.7;">Sin resultados</div>';
                    return;
                }

                cont.innerHTML = resultados.map((p) => {
                    const nombre = String(p.nombre || '').replace(/</g, '&lt;');
                    const tipo = String(p.tipo_producto || '').replace(/</g, '&lt;');
                    const precio = Number(p.precio_venta || 0).toFixed(2);
                    return `<button type="button" class="resultado-item" data-producto-id="${p.id}" style="width:100%; text-align:left; border:0; background:transparent; padding:10px 12px; border-bottom:1px solid var(--color-borde); cursor:pointer;">
                        <div style="font-weight:700;">${nombre}</div>
                        <div style="font-size:0.85rem; opacity:0.8;">${tipo || 'Producto'} | $${precio}</div>
                    </button>`;
                }).join('');

                cont.querySelectorAll('[data-producto-id]').forEach((btn) => {
                    btn.onclick = () => {
                        const id = Number(btn.getAttribute('data-producto-id'));
                        const producto = productos.find((p) => Number(p.id) === id);
                        if (!producto) return;
                        productoSeleccionado = producto;
                        const nombreInput = document.getElementById('sw-cuenta-producto-nombre');
                        const precioInput = document.getElementById('sw-cuenta-producto-precio');
                        if (nombreInput) nombreInput.value = `${producto.nombre || ''}${producto.tipo_producto ? ` (${producto.tipo_producto})` : ''}`;
                        if (precioInput) precioInput.value = Number(producto.precio_venta || 0).toFixed(2);
                    };
                });
            };

            const result = await Swal.fire({
                title: `Agregar a ${cuentaObj.nombre || ''}`,
                html,
                width: 820,
                showCancelButton: true,
                confirmButtonText: 'Agregar a cuenta',
                cancelButtonText: 'Cancelar',
                didOpen: () => {
                    const buscar = document.getElementById('sw-cuenta-producto-buscar');
                    if (buscar) {
                        buscar.oninput = () => renderProductos(buscar.value);
                        buscar.focus();
                    }
                    renderProductos('');
                },
                preConfirm: () => {
                    const cantidad = parseInt(document.getElementById('sw-cuenta-producto-cantidad').value || '0', 10);
                    const precio = parseFloat(document.getElementById('sw-cuenta-producto-precio').value || '0');
                    if (!productoSeleccionado) {
                        Swal.showValidationMessage('Selecciona un producto');
                        return false;
                    }
                    if (!cantidad || cantidad <= 0) {
                        Swal.showValidationMessage('Cantidad inválida');
                        return false;
                    }
                    if (Number.isNaN(precio) || precio <= 0) {
                        Swal.showValidationMessage('Precio inválido');
                        return false;
                    }
                    return { cantidad, precio };
                }
            });

            if (!result.isConfirmed) return;

            const nombreBase = this.descomponerNombreCuenta(cuentaObj.nombre || '');
            const payload = {
                carrito: [{
                    id: Number(productoSeleccionado.id),
                    cantidad: result.value.cantidad,
                    precio: result.value.precio,
                    nombre: productoSeleccionado.nombre,
                    precio_venta: result.value.precio
                }],
                tipo_pago: 'fiado',
                nombre_fiado: cuentaObj.nombre || '',
                grupo_fiado: this.extraerRegionCuenta(cuentaObj),
                celular_fiado: cuentaObj.celular || '',
                csrf_token: sessionStorage.getItem('csrf_token') || ''
            };

            const resp = await fetchWithCSRF('api/api_ventas.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await resp.json();
            if (!data.success) throw new Error(data.message || 'No se pudo agregar el producto a la cuenta');

            Notificador.success('✓ Producto agregado a la cuenta');
            await this.load();
            if (window.ventas && window.ventas.loadDebtors) window.ventas.loadDebtors();
        } catch (error) {
            Notificador.error('Error', error.message);
        }
    },

    async verHistorialCuenta(nombre) {
        try {
            const resp = await fetch(`api/api_ventas.php?accion=historial_cliente&nombre=${encodeURIComponent(nombre)}`);
            const data = await resp.json();
            if (!data.success) throw new Error(data.message || 'No se pudo cargar historial');

            const ventas = data.ventas || [];
            if (!ventas.length) {
                await Swal.fire({
                    title: `Historial de ${nombre}`,
                    html: '<div style="padding:10px;">Sin compras a cuenta registradas</div>',
                    icon: 'info',
                    confirmButtonText: 'Cerrar'
                });
                return;
            }

            let html = '<div style="max-height:420px; overflow-y:auto; text-align:left;">';
            ventas.forEach(v => {
                html += `<div style="border-bottom:1px solid #eee; padding:10px 0;">
                    <small style="opacity:0.7;">${v.fecha || '-'}</small>
                    <p style="margin:5px 0;"><strong>${v.producto_nombre || 'Producto sin nombre'}</strong> x ${v.cantidad || 1}</p>
                    <p style="margin:0; color:#e65100; font-weight:700;">Compra a cuenta: ${moneyFmt.format(v.total || 0)}</p>
                    <small style="opacity:0.8;">Estado: ${Number(v.fiado_pagado) === 1 ? 'Pagado' : 'Pendiente'}</small>
                </div>`;
            });
            html += '</div>';

            await Swal.fire({
                title: `Historial de ${nombre}`,
                html,
                width: 760,
                confirmButtonText: 'Cerrar'
            });
        } catch (error) {
            Notificador.error('Error', error.message);
        }
    },

    async editarCuenta(nombre) {
        try {
            const cuenta = this.getCuentaPorNombre(nombre);
            if (!cuenta) return Notificador.error('Cuenta no encontrada');
            const nombreParts = this.descomponerNombreCuenta(cuenta.nombre);

            const datos = await this.mostrarFormularioCuenta('Editar Cuenta', {
                nombre: nombreParts.nombre,
                apellidoInicial: nombreParts.apellidoInicial,
                region: cuenta.region,
                numero: cuenta.celular
            });
            if (!datos) return;

            const notasRegion = this.construirNotasConRegion(datos.region, cuenta.notas || '');
            const nombreCompleto = this.componerNombreCuenta(datos.nombre, datos.apellidoInicial);

            let idCuenta = cuenta.id;
            if (!idCuenta) {
                // Si la fila proviene solo de ventas a cuenta, primero materializamos la cuenta con los datos editados.
                const paramsCrear = new URLSearchParams({
                    accion: 'crear_cuenta',
                    nombre_cuenta: nombreCompleto,
                    celular: datos.numero,
                    notas: notasRegion,
                    csrf_token: sessionStorage.getItem('csrf_token') || ''
                });
                const respCrear = await fetchWithCSRF('api/api_ventas.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: paramsCrear.toString()
                });
                const dataCrear = await respCrear.json();
                if (!dataCrear.success || !dataCrear.cuenta_id) {
                    throw new Error(dataCrear.message || 'No se pudo preparar la cuenta para edición');
                }
                idCuenta = dataCrear.cuenta_id;
            }

            const params = new URLSearchParams({
                accion: 'editar_cuenta',
                id_cuenta: idCuenta,
                nombre_cuenta: nombreCompleto,
                celular: datos.numero,
                notas: notasRegion,
                csrf_token: sessionStorage.getItem('csrf_token') || ''
            });

            const resp = await fetchWithCSRF('api/api_ventas.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: params.toString()
            });
            const data = await resp.json();
            if (!data.success) throw new Error(data.message || 'No se pudo editar la cuenta');

            Notificador.success('✓ Cuenta actualizada');
            await this.load();
        } catch (error) {
            Notificador.error('Error', error.message);
        }
    },

    async eliminarCuenta(nombre) {
        try {
            const cuenta = this.getCuentaPorNombre(nombre);
            if (!cuenta) return Notificador.error('Cuenta no encontrada');
            if (cuenta.saldo > 0) return Notificador.warning('No se puede eliminar', 'Primero liquida el saldo pendiente');

            const idCuenta = await this.asegurarCuentaId(cuenta.nombre, cuenta.celular || '');
            if (!(await Notificador.confirmDelete(`Cuenta de ${cuenta.nombre}`, 'Esta cuenta se desactivará y dejará de aparecer en la lista.'))) return;

            const params = new URLSearchParams({
                accion: 'eliminar_cuenta',
                id_cuenta: idCuenta,
                csrf_token: sessionStorage.getItem('csrf_token') || ''
            });

            const resp = await fetchWithCSRF('api/api_ventas.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: params.toString()
            });
            const data = await resp.json();
            if (!data.success) throw new Error(data.message || 'No se pudo eliminar la cuenta');

            Notificador.success('✓ Cuenta eliminada');
            await this.load();
        } catch (error) {
            Notificador.error('Error', error.message);
        }
    }
};

const cortes = {
    listData: [],

    actualizarBotonesCorte(hayCorteAbierto) {
        const btnAbrir = document.getElementById('btn-abrir-corte');
        const btnCerrar = document.getElementById('btn-cerrar-corte');
        const btnVer = document.getElementById('btn-ver-corte-actual');

        if (btnAbrir) {
            btnAbrir.disabled = !!hayCorteAbierto;
            btnAbrir.style.opacity = hayCorteAbierto ? '0.6' : '1';
            btnAbrir.style.cursor = hayCorteAbierto ? 'not-allowed' : 'pointer';
            btnAbrir.title = hayCorteAbierto ? 'Ya hay un corte abierto' : 'Abrir corte';
        }

        if (btnCerrar) {
            btnCerrar.disabled = !hayCorteAbierto;
            btnCerrar.style.opacity = hayCorteAbierto ? '1' : '0.6';
            btnCerrar.style.cursor = hayCorteAbierto ? 'pointer' : 'not-allowed';
            btnCerrar.title = hayCorteAbierto ? 'Cerrar corte actual' : 'No hay corte abierto';
        }

        if (btnVer) {
            btnVer.disabled = !hayCorteAbierto;
            btnVer.style.opacity = hayCorteAbierto ? '1' : '0.6';
            btnVer.style.cursor = hayCorteAbierto ? 'pointer' : 'not-allowed';
            btnVer.title = hayCorteAbierto ? 'Ver corte actual' : 'No hay corte abierto';
        }
    },

    async load() {
        try {
            const tbody = document.getElementById('cuerpo-tabla-cortes');
            if (!tbody) return;
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px;"><i class="fas fa-spinner fa-spin"></i> Cargando...</td></tr>';

            const resp = await fetch('api/api_caja.php?accion=listar_cortes', {credentials:'include'});
            const data = await resp.json();
            if (!resp.ok || !data.success) throw new Error(data.message || 'Error al cargar cortes');

            this.listData = data.cortes || [];

            if (data.cortes && data.cortes.length > 0) {
                tbody.innerHTML = '';

                data.cortes.forEach((c) => {
                    const totalMovimiento = Number(c.saldo_final || 0) > 0
                        ? Number(c.saldo_final || 0)
                        : Number(c.saldo_inicial || 0) + Number(c.ingresos_efectivo || 0) + Number(c.ingresos_tarjeta || 0) + Number(c.ingresos_transferencia || 0) - Number(c.egresos || 0);
                    const estado = c.estado === 'abierto' ? '<span style="background:#4caf50; color:white; padding:4px 12px; border-radius:12px; font-size:0.8rem;">Abierto</span>' : '<span style="background:#999; color:white; padding:4px 12px; border-radius:12px; font-size:0.8rem;">Cerrado</span>';
                    const tr = `<tr>
                        <td>${c.fecha_apertura || '-'}</td>
                        <td>${c.fecha_cierre || '-'}</td>
                        <td style="text-align:right; font-weight:bold;">${moneyFmt.format(totalMovimiento)}</td>
                        <td style="text-align:center;">${estado}</td>
                        <td style="text-align:center;">
                            <button class="btn-icon btn-info" onclick="cortes.verDetalle(${c.id})" title="Ver"><i class="fas fa-eye"></i></button>
                        </td>
                    </tr>`;
                    tbody.innerHTML += tr;
                });
            } else {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px; opacity:0.5;"><i class="fas fa-inbox"></i> Sin cortes registrados.</td></tr>';
            }
            
            this.actualizarEstado();
        } catch (error) {
            console.error('Error cargando cortes:', error);
            Notificador.error('Error', error.message);
        }
    },
    
    async actualizarEstado() {
        try {
            const resp = await fetch('api/api_caja.php?accion=corte_actual');
            const data = await resp.json();

            if (data.success && data.corte) {
                const corte = data.corte;
                const ingresos = Number(corte.ventas?.efectivo || 0) + Number(corte.ventas?.tarjeta || 0) + Number(corte.ventas?.transferencia || 0);
                const egresos = Number(corte.egresos_total || 0);
                const movimiento = Number(corte.saldo_inicial || 0) + ingresos - egresos;

                document.getElementById('corte-estado').textContent = 'Abierto';
                document.getElementById('corte-estado').style.color = 'var(--color-success)';
                document.getElementById('corte-hora').textContent = data.corte.fecha_apertura || '--';
                document.getElementById('corte-movimiento').textContent = moneyFmt.format(movimiento);
                document.getElementById('corte-ventas').textContent = data.corte.ventas?.total_ventas || 0;
                this.actualizarBotonesCorte(true);
            } else {
                document.getElementById('corte-estado').textContent = 'Cerrado';
                document.getElementById('corte-estado').style.color = 'var(--color-danger)';
                document.getElementById('corte-hora').textContent = '--';
                document.getElementById('corte-movimiento').textContent = '$0.00';
                document.getElementById('corte-ventas').textContent = '0';
                this.actualizarBotonesCorte(false);
            }
        } catch (error) {
            console.error('Error actualizando estado:', error);
        }
    },

    async abrirCorte() {
        const cfg = Notificador.getConfig();
        const result = await Swal.fire({
            title: 'Abrir corte de caja',
            html: `
                <div style="text-align:left; display:grid; gap:10px;">
                    <label>Saldo inicial en caja</label>
                    <input id="sw-corte-saldo-inicial" type="number" min="0" step="0.01" value="0" class="swal2-input" style="margin:0; width:100%;">
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Abrir corte',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#2e7d32',
            background: cfg.bg,
            color: cfg.color,
            preConfirm: () => {
                const saldoInicial = parseFloat(document.getElementById('sw-corte-saldo-inicial').value || '0');
                if (Number.isNaN(saldoInicial) || saldoInicial < 0) {
                    Swal.showValidationMessage('Saldo inicial inválido');
                    return false;
                }
                return { saldoInicial };
            }
        });
        if (!result.isConfirmed) return;
        const saldoInicial = result.value.saldoInicial;

        try {
            const resp = await fetchWithCSRF('api/api_caja.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    accion: 'abrir_corte',
                    saldo_inicial: String(saldoInicial),
                    csrf_token: sessionStorage.getItem('csrf_token')
                }).toString()
            });

            const data = await resp.json();
            if (data.success) {
                Notificador.success('✓ Corte abierto');
                this.load();
            } else {
                Notificador.error('Error', data.message);
            }
        } catch (error) {
            Notificador.error('Error', error.message);
        }
    },
    
    async cerrarCorte() {
        try {
            const actualResp = await fetch('api/api_caja.php?accion=corte_actual', {credentials:'include'});
            const actualData = await actualResp.json();
            if (!actualData.success || !actualData.corte) {
                return Notificador.warning('No hay corte abierto', 'Primero abre un corte');
            }

            const corte = actualData.corte;
            const ingresos = Number(corte.ventas?.efectivo || 0) + Number(corte.ventas?.tarjeta || 0) + Number(corte.ventas?.transferencia || 0);
            const egresos = Number(corte.egresos_total || 0);
            const saldoEsperado = Number(corte.saldo_inicial || 0) + ingresos - egresos;

            const cfg = Notificador.getConfig();
            const result = await Swal.fire({
                title: 'Cerrar corte de caja',
                html: `
                    <div style="text-align:left; display:grid; gap:10px;">
                        <div style="padding:10px; border-radius:8px; background:var(--color-input-bg); border-left:4px solid var(--color-info);">
                            <div><strong>Saldo esperado:</strong> ${moneyFmt.format(saldoEsperado)}</div>
                            <div style="font-size:0.85rem; opacity:0.8; margin-top:4px;">Inicial + ingresos - egresos</div>
                        </div>
                        <label>Saldo final contado</label>
                        <input id="sw-corte-saldo-final" type="number" min="0" step="0.01" value="${String(saldoEsperado.toFixed(2))}" class="swal2-input" style="margin:0; width:100%;">
                        <label>Notas de cierre (opcional)</label>
                        <textarea id="sw-corte-notas" class="swal2-textarea" style="margin:0; width:100%;" placeholder="Observaciones del cierre"></textarea>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Cerrar corte',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: '#0d47a1',
                background: cfg.bg,
                color: cfg.color,
                preConfirm: () => {
                    const saldoFinal = parseFloat(document.getElementById('sw-corte-saldo-final').value || '0');
                    const notas = (document.getElementById('sw-corte-notas').value || '').trim();
                    if (Number.isNaN(saldoFinal) || saldoFinal < 0) {
                        Swal.showValidationMessage('Saldo final inválido');
                        return false;
                    }
                    return { saldoFinal, notas };
                }
            });
            if (!result.isConfirmed) return;
            const saldoFinal = result.value.saldoFinal;
            const notas = result.value.notas || '';

            const resp = await fetchWithCSRF('api/api_caja.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    accion: 'cerrar_corte',
                    id_corte: String(corte.id),
                    saldo_final: String(saldoFinal),
                    notas: notas,
                    csrf_token: sessionStorage.getItem('csrf_token')
                }).toString()
            });

            const data = await resp.json();
            if (data.success) {
                Notificador.success('✓ Corte cerrado', `Diferencia: ${moneyFmt.format(Number(data.resumen?.diferencia || 0))}`);
                this.load();
            } else {
                Notificador.error('Error', data.message);
            }
        } catch (error) {
            Notificador.error('Error', error.message);
        }
    },

    async verDetalle(id) {
        try {
            const resp = await fetch(`api/api_caja.php?accion=detalle_corte&id_corte=${id}`);
            const data = await resp.json();
            if (!data.success) throw new Error(data.message || 'No se pudo obtener el detalle');

            const c = data.corte;
            const resumen = `<div style="text-align:left; line-height:1.6;">
                <p><strong>Apertura:</strong> ${c.fecha_apertura || '-'}</p>
                <p><strong>Cierre:</strong> ${c.fecha_cierre || 'Aún abierto'}</p>
                <p><strong>Saldo inicial:</strong> ${moneyFmt.format(c.saldo_inicial || 0)}</p>
                <p><strong>Ingresos efectivo:</strong> ${moneyFmt.format(c.ingresos_efectivo || 0)}</p>
                <p><strong>Ingresos tarjeta:</strong> ${moneyFmt.format(c.ingresos_tarjeta || 0)}</p>
                <p><strong>Ingresos transferencia:</strong> ${moneyFmt.format(c.ingresos_transferencia || 0)}</p>
                <p><strong>Egresos:</strong> ${moneyFmt.format(c.egresos || 0)}</p>
                <p><strong>Saldo final:</strong> ${moneyFmt.format(c.saldo_final || 0)}</p>
                <p><strong>Diferencia:</strong> <b style="color:${Number(c.diferencia || 0) === 0 ? 'var(--color-success)' : 'var(--color-danger)'};">${moneyFmt.format(c.diferencia || 0)}</b></p>
                <p><strong>Ventas en corte:</strong> ${Array.isArray(data.ventas) ? data.ventas.length : 0}</p>
                <p><strong>Egresos en corte:</strong> ${Array.isArray(data.egresos) ? data.egresos.length : 0}</p>
            </div>`;

            Notificador.info(`Detalle Corte #${id}`, resumen);
        } catch (error) {
            Notificador.error('Error', error.message);
        }
    },

    async verCorteActual() {
        try {
            const resp = await fetch('api/api_caja.php?accion=corte_actual');
            const data = await resp.json();
            if (!data.success || !data.corte) {
                return Notificador.info('No hay corte abierto', 'Abre un nuevo corte para ver sus detalles');
            }
            return this.verDetalle(data.corte.id);
        } catch (error) {
            Notificador.error('Error', error.message);
        }
    },

    async eliminarHistorial() {
        const cfg = Notificador.getConfig();
        const result = await Swal.fire({
            title: 'Eliminar historial de cortes',
            html: `
                <div style="text-align:left; line-height:1.6;">
                    <p>Se eliminaran todos los cortes cerrados del historial.</p>
                    <p style="font-weight:700; color:#d32f2f;">Esta accion no se puede deshacer.</p>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Si, eliminar historial',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#c62828',
            background: cfg.bg,
            color: cfg.color
        });

        if (!result.isConfirmed) return;

        try {
            const resp = await fetchWithCSRF('api/api_caja.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    accion: 'eliminar_historial',
                    csrf_token: sessionStorage.getItem('csrf_token')
                }).toString()
            });

            const data = await resp.json();
            if (!resp.ok || !data.success) throw new Error(data.message || 'No se pudo eliminar el historial');

            Notificador.success('Historial eliminado', `Cortes eliminados: ${Number(data.eliminados || 0)}`);
            this.load();
        } catch (error) {
            Notificador.error('Error', error.message);
        }
    }
};

const migracion = {
    datosDisponibles: null,
    
    async cargarBD() {
        const input = document.getElementById('migracion-archivo');
        if (!input.files.length) return Notificador.error('Selecciona un archivo');
        
        const formData = new FormData();
        formData.append('accion', 'cargar_bd_antigua');
        formData.append('archivo_bd', input.files[0]);
        appendCsrfToFormData(formData);
        
        try {
            const resp = await fetch('api/api_migracion.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await resp.json();
            if (data.success) {
                this.datosDisponibles = data.datos;
                this.mostrarDatos(data.datos);
                document.getElementById('btn-ejecutar-migracion').style.display = 'block';
                Notificador.success('✓ Archivo cargado', 'Se encontraron datos para importar');
            } else {
                Notificador.error('Error', data.message);
            }
        } catch (error) {
            Notificador.error('Error', error.message);
        }
    },
    
    mostrarDatos(datos) {
        const tbody = document.getElementById('cuerpo-tabla-migracion');
        tbody.innerHTML = '';
        
        Object.entries(datos).forEach(([tipo, cantidad]) => {
            const tr = `<tr>
                <td>${tipo}</td>
                <td style="text-align:center;"><b>${cantidad}</b></td>
                <td style="text-align:center;"><i class="fas fa-check" style="color:var(--color-success);"></i></td>
            </tr>`;
            tbody.innerHTML += tr;
        });
    },
    
    async ejecutar() {
        if (!this.datosDisponibles) return Notificador.error('Carga un archivo primero');
        if (!(await Notificador.confirm('¿Ejecutar migración? No podrá repetirse.'))) return;
        
        try {
            const resp = await fetchWithCSRF('api/api_migracion.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: new URLSearchParams({
                    accion: 'ejecutar_migracion',
                    csrf_token: sessionStorage.getItem('csrf_token')
                }).toString()
            });
            
            const data = await resp.json();
            if (data.success) {
                Notificador.success('✓ Migración completada');
                document.getElementById('migracion-resultado').style.display = 'block';
                document.getElementById('migracion-resultado').textContent = `✓ Datos importados: ${JSON.stringify(data.resultado).substring(0, 100)}...`;
                document.getElementById('btn-ejecutar-migracion').style.display = 'none';
            } else {
                Notificador.error('Error', data.message);
            }
        } catch (error) {
            Notificador.error('Error', error.message);
        }
    },
    
    async verificarEstado() {
        try {
            const resp = await fetch('api/api_migracion.php?accion=estado_bd_actual', {credentials:'include'});
            const data = await resp.json();
            
            if (data.success) {
                const estado = document.getElementById('migracion-estado');
                if (data.estado.migrado) {
                    estado.innerHTML = '<p style="color:var(--color-success);"><i class="fas fa-check-circle"></i> Base de datos ya migrada anteriormente</p>';
                    document.getElementById('btn-cargar-bd-antigua').disabled = true;
                } else {
                    estado.innerHTML = '<p style="color:var(--color-warning);"><i class="fas fa-info-circle"></i> Base de datos no migrada. Puedes realizar la migración ahora.</p>';
                }
            }
        } catch (error) {
            console.error('Error verificando estado:', error);
        }
    }
};

window.ventas = ventas; window.inventario = inventario; window.registros = registros; window.usuarios = usuarios; window.admin = admin; window.septimas = septimas; window.diagnostico = diagnostico; window.estadisticas = estadisticas; window.mantenimiento = mantenimiento; window.exportacion = exportacion; window.cuentas = cuentas; window.cortes = cortes; window.migracion = migracion;

document.addEventListener('DOMContentLoaded', () => {
    const roleAttr = document.body.getAttribute('data-role');
    if (roleAttr) sessionStorage.setItem('user_role', roleAttr);
    initNav(); modTema.init(); modColor.init(); ventas.init(); admin.load();
    
    // Cargar estadísticas del dashboard al inicio
    const role = sessionStorage.getItem('user_role');
    if (role === 'admin' || role === 'superadmin') {
        setTimeout(() => {
            console.log('[Init] Cargando estadísticas del dashboard...');
            estadisticas.cargarAdmin();
        }, 500);
    }
    
    // DESACTIVADO: Cargar salud del sistema - Funcionalidad removida por errores
    // setTimeout(() => {
    //     const role = sessionStorage.getItem('user_role');
    //     console.log('[Init] Rol del usuario:', role);
    //     if (role === 'admin' || role === 'superadmin') {
    //         console.log('[Init] Iniciando carga de Salud del Sistema');
    //         saludSistema.cargar();
    //     }
    // }, 500);
    
    // Botón para refrescar salud del sistema
    const btnRefreshSalud = document.getElementById('btn-refresh-salud');
    if (btnRefreshSalud) {
        const cargarSalud = async () => {
            try {
                const resp = await fetchWithCSRF('api/api_admin.php?accion=salud_sistema');
                
                // Validar que sea JSON antes de parsear
                const contentType = resp.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    throw new Error('El servidor no devolvió JSON válido. Respuesta: ' + resp.status);
                }
                
                const data = await resp.json();
                
                if (data && data.success) {
                    document.getElementById('salud-db-size').textContent = data.db_size_mb + ' MB';
                    document.getElementById('salud-usuarios-total').textContent = data.usuarios_total;
                    document.getElementById('salud-productos-activos').textContent = data.productos_activos;
                    document.getElementById('salud-ventas-total').textContent = '$' + parseFloat(data.ventas_total).toFixed(2);
                    document.getElementById('salud-fiados-pendientes').textContent = '$' + parseFloat(data.fiados_pendientes).toFixed(2);
                    document.getElementById('salud-disk-free').textContent = (data.disk_free_gb !== null ? data.disk_free_gb + ' GB' : 'N/A');
                    Notificador.success('Salud del Sistema', 'Datos actualizados correctamente');
                } else {
                    Notificador.error('Error', data?.message || 'No se pudo cargar la salud del sistema');
                }
            } catch (err) {
                console.error('[Salud del Sistema] Error:', err);
                Notificador.error('Error al cargar salud', err.message);
            }
        };
        
        btnRefreshSalud.onclick = cargarSalud;
        // Cargar al iniciar
        setTimeout(cargarSalud, 1000);
    }
    
    // Descargar reportes - VERSIÓN SIMPLE QUE FUNCIONA
    const descargarReporte = async (tipoReporte) => {
        try {
            Swal.fire({
                title: '⏳ Generando reporte...',
                html: 'Creando archivo Excel...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Usar API principal de reportes
            const response = await fetch(`api/api_reportes.php?reporte=${tipoReporte}`);
            const textoRespuesta = await response.text();
            
            console.log('[Reporte] Respuesta:', textoRespuesta);
            
            Swal.close();
            
            // Parsear respuesta
            const limpio = (textoRespuesta || '').trim();
            if (!limpio) {
                throw new Error(`Respuesta vacía (HTTP ${response.status})`);
            }

            if (limpio.startsWith('{')) {
                try {
                    const json = JSON.parse(limpio);
                    const msg = json.message || json.error || 'Error desconocido';
                    throw new Error(msg);
                } catch (jsonErr) {
                    throw new Error('Respuesta inválida del servidor');
                }
            }

            const partes = limpio.split('|');
            
            if (partes[0] === 'SUCCESS') {
                const [, filename, path, size] = partes;
                const sizeKB = (parseInt(size) / 1024).toFixed(2);
                const cfg = Notificador.getConfig();
                
                Swal.fire({
                    icon: 'success',
                    title: '✅ ¡Archivo Guardado!',
                    html: `
                        <div style="text-align:center; padding:20px;">
                            <div style="background: linear-gradient(135deg, #e8f5e9, #c8e6c9); padding:30px; border-radius:15px; margin:20px 0;">
                                <div style="font-size:5rem; margin-bottom:15px;">📊</div>
                                <p style="margin:15px 0; font-weight:bold; color:#1b5e20; font-size:1.3rem;">
                                    ${filename}
                                </p>
                                <p style="font-size:1rem; color:#555; margin:10px 0;">
                                    💾 Tamaño: ${sizeKB} KB
                                </p>
                                <div style="background:white; padding:15px; border-radius:10px; margin-top:20px;">
                                    <p style="font-size:0.9rem; color:#666; margin:5px 0;">
                                        📁 Guardado en:
                                    </p>
                                    <p style="font-family:monospace; font-size:0.85rem; color:#333; margin:10px 0; word-break:break-all;">
                                        ${path}
                                    </p>
                                </div>
                            </div>
                            <p style="font-size:1.1rem; color:#2e7d32; margin-top:20px; font-weight:bold;">
                                ✓ Abre tu carpeta de Descargas para verlo
                            </p>
                        </div>
                    `,
                    confirmButtonText: '👍 Perfecto',
                    confirmButtonColor: '#2e7d32',
                    width: 650,
                    background: cfg.bg,
                    color: cfg.color
                });
            } else {
                const errorMsg = partes[1] || limpio || `Error HTTP ${response.status}`;
                console.error('[Reporte] Error:', errorMsg);
                Notificador.error('Error al generar reporte', errorMsg);
            }
            
        } catch (error) {
            console.error('[Reporte] Excepción:', error);
            Swal.close();
            Notificador.error('Error', 'No se pudo generar el reporte: ' + error.message);
        }
    };
    
    const b1=document.getElementById('btn-reporte-inventario'); 
    if(b1) b1.onclick=()=>descargarReporte('inventario_hoy');
    
    const b2=document.getElementById('btn-reporte-consolidado'); 
    if(b2) b2.onclick=()=>descargarReporte('consolidado');
    
    const b3=document.getElementById('btn-respaldo-db'); if(b3) b3.onclick=()=>window.location.href='api/api_respaldo.php';

    const btnIntegridad = document.getElementById('btn-verificar-integridad');
    if (btnIntegridad) btnIntegridad.onclick = () => diagnostico.verificarIntegridad();

    const btnOptimizar = document.getElementById('btn-optimizar-bd');
    if (btnOptimizar) btnOptimizar.onclick = () => mantenimiento.optimizarBD();
    
    const btnOptimizarApp = document.getElementById('btn-optimizar-app');
    if (btnOptimizarApp) btnOptimizarApp.onclick = () => mantenimiento.optimizarRendimiento();

    const btnDiagnostico = document.getElementById('btn-diagnostico-rendimiento');
    if (btnDiagnostico) btnDiagnostico.onclick = () => mantenimiento.mostrarDiagnostico();

    const btnPruebasSistema = document.getElementById('btn-ejecutar-pruebas-sistema');
    if (btnPruebasSistema) btnPruebasSistema.onclick = () => mantenimiento.ejecutarPruebasSistema();

    const btnNotiSuccess = document.getElementById('btn-noti-demo-success');
    if (btnNotiSuccess) btnNotiSuccess.onclick = () => Notificador.success('Operación exitosa', 'Ejemplo de notificación de éxito');

    const btnNotiWarn = document.getElementById('btn-noti-demo-warning');
    if (btnNotiWarn) btnNotiWarn.onclick = () => Notificador.warning('Atención', 'Ejemplo de alerta preventiva del sistema');

    const btnNotiError = document.getElementById('btn-noti-demo-error');
    if (btnNotiError) btnNotiError.onclick = () => Notificador.error('Error controlado', 'Ejemplo de notificación de error con manejo de casos');

    const btnNotiInfo = document.getElementById('btn-noti-demo-info');
    if (btnNotiInfo) btnNotiInfo.onclick = () => Notificador.info('Información', 'Ejemplo de notificación informativa para usuario');
    
    const btnResetear = document.getElementById('btn-resetear-demo');
    if (btnResetear) btnResetear.onclick = () => mantenimiento.resetearDemo();

    const tabConfig = document.querySelector('[data-tab="config"]');
    if (tabConfig) {
        tabConfig.addEventListener('click', () => {
            const role = sessionStorage.getItem('user_role');
            if (role === 'superadmin') {
                setTimeout(() => estadisticas.cargarSuperAdmin(), 100);
            }
            // DESACTIVADO: Cargar salud del sistema cuando se abre Config
            // setTimeout(() => {
            //     console.log('[Eventos] Recargando salud del sistema al abrir config');
            //     saludSistema.cargar();
            // }, 100);
        });
    }
    
    // Botones de exportación CSV/JSON (si existen)
    const btnExportCSV = document.getElementById('btn-export-csv');
    if (btnExportCSV) btnExportCSV.onclick = () => exportacion.descargarCSV();
    
    const btnExportJSON = document.getElementById('btn-export-json');
    if (btnExportJSON) btnExportJSON.onclick = () => exportacion.descargarJSON();
    
    // Verificar que el click handler está disponible
    const stockBajoBtn = document.querySelector('[onclick*="mostrarStockBajo"]');
    if (stockBajoBtn) {
        console.log('✅ Botón Stock Bajo encontrado y verificado');
        // Asegurar que el onclick está correctamente configurado
        if (!stockBajoBtn.onclick && window.inventario && window.inventario.mostrarStockBajo) {
            stockBajoBtn.onclick = () => inventario.mostrarStockBajo();
            console.log('✅ Evento onclick configurado manualmente para Stock Bajo');
        }
    } else {
        console.warn('⚠️ Botón Stock Bajo no encontrado en el DOM');
    }
    
    // Manejadores para nuevas pestañas
    const btnCrearCuenta = document.getElementById('btn-crear-cuenta');
    if (btnCrearCuenta) {
        btnCrearCuenta.onclick = async () => {
            try {
                const datos = await cuentas.mostrarFormularioCuenta('Nueva Cuenta', {
                    nombre: '',
                    apellidoInicial: '',
                    region: '',
                    numero: ''
                });
                if (!datos) return;

                const nombreCuenta = cuentas.componerNombreCuenta(datos.nombre, datos.apellidoInicial);
                const notasRegion = cuentas.construirNotasConRegion(datos.region, '');
                const params = new URLSearchParams({
                    accion: 'crear_cuenta',
                    nombre_cuenta: nombreCuenta,
                    celular: datos.numero,
                    notas: notasRegion,
                    csrf_token: sessionStorage.getItem('csrf_token') || ''
                });

                const resp = await fetchWithCSRF('api/api_ventas.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/x-www-form-urlencoded'},
                    body: params.toString()
                });
                const d = await resp.json();
                if (!d.success) throw new Error(d.message || 'No se pudo crear la cuenta');

                Notificador.success('✓ ' + (d.message || 'Cuenta creada'));
                await cuentas.load();

                const buscarCuentas = document.getElementById('cuentas-buscar');
                if (buscarCuentas) {
                    buscarCuentas.value = '';
                    buscarCuentas.dispatchEvent(new Event('input'));
                }
            } catch (e) {
                Notificador.error('Error', e.message);
            }
        };
    }
    
    const buscarCuentas = document.getElementById('cuentas-buscar');
    if (buscarCuentas) {
        buscarCuentas.oninput = () => {
            const term = buscarCuentas.value.toLowerCase();
            const filas = document.querySelectorAll('#tabla-cuentas tbody tr');
            filas.forEach(fila => {
                const texto = fila.textContent.toLowerCase();
                fila.style.display = texto.includes(term) ? '' : 'none';
            });
        };
    }
    
    const btnAbrirCorte = document.getElementById('btn-abrir-corte');
    if (btnAbrirCorte) {
        btnAbrirCorte.onclick = () => cortes.abrirCorte();
    }
    
    const btnCerrarCorte = document.getElementById('btn-cerrar-corte');
    if (btnCerrarCorte) {
        btnCerrarCorte.onclick = () => cortes.cerrarCorte();
    }
    
    const btnVerCorteActual = document.getElementById('btn-ver-corte-actual');
    if (btnVerCorteActual) {
        btnVerCorteActual.onclick = () => cortes.verCorteActual();
    }

    const btnEliminarHistorialCortes = document.getElementById('btn-eliminar-historial-cortes');
    if (btnEliminarHistorialCortes) {
        btnEliminarHistorialCortes.onclick = () => cortes.eliminarHistorial();
    }
    
    const btnCargarBDAntiga = document.getElementById('btn-cargar-bd-antigua');
    if (btnCargarBDAntiga) {
        btnCargarBDAntiga.onclick = () => migracion.cargarBD();
    }
    
    const btnEjecutarMigracion = document.getElementById('btn-ejecutar-migracion');
    if (btnEjecutarMigracion) {
        btnEjecutarMigracion.onclick = () => migracion.ejecutar();
    }
    
    setInterval(()=>{ const d=new Date(); document.getElementById('reloj-digital').innerText=d.toLocaleTimeString('es-MX',{hour:'2-digit',minute:'2-digit'}); },1000);
});
