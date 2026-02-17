
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
                fetch('api/healthcheck.php?t=' + Date.now()),
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
const PLACEHOLDER_IMG = 'assets/img/placeholder.png';

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
                body: JSON.stringify(errorData)
            }).catch(err => console.error('No se pudo registrar el error:', err));
        }

        console.error(`[${tipo}]`, mensaje, detalles);
    } catch (err) {
        console.error('Error al registrar error:', err);
    }
}

const Notificador = {
    getConfig() {
        const esDark = document.body.getAttribute('data-theme') === 'dark';
        return { bg: esDark ? '#1e1e1e' : '#fff', color: esDark ? '#e0e0e0' : '#333' };
    },
    // Reproducir sonidos simples usando Web Audio API
    playSound(type = 'success') {
        try {
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
        }
    },
    mostrarToast(titulo, mensaje, tipo = 'info', duracion = 2500) {
        let toastContainer = document.getElementById('toast-container');
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

        const toast = document.createElement('div');
        const colores = {
            'success': '#4caf50',
            'error': '#f44336',
            'warning': '#ff9800',
            'info': '#2196f3'
        };
        const color = colores[tipo] || colores.info;
        
        toast.style.cssText = `
            background: white;
            border-left: 4px solid ${color};
            padding: 16px;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            animation: slideInRight 0.3s ease-out;
            display: flex;
            justify-content: space-between;
            align-items: center;
            min-width: 300px;
        `;

        const contenido = document.createElement('div');
        contenido.style.cssText = `flex: 1;`;
        contenido.innerHTML = `
            <div style="font-weight: bold; color: ${color}; font-size: 0.95rem;">${titulo}</div>
            ${mensaje ? `<div style="color: #666; font-size: 0.85rem; margin-top: 4px;">${mensaje}</div>` : ''}
        `;

        const closeBtn = document.createElement('button');
        closeBtn.innerHTML = '×';
        closeBtn.style.cssText = `
            background: none;
            border: none;
            font-size: 24px;
            color: #999;
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
            }
            if(targetId==='errores') admin.load();
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
    barcodeBuffer: '',
    barcodeTimeout: null,
    
    init: () => {
        try {
            ventas.loadDebtors();
            const input = document.getElementById('buscar-producto');
            const resContainer = document.getElementById('resultados-busqueda');
            
            if (!input || !resContainer) {
                console.warn('Elementos de búsqueda no encontrados');
                return;
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
                        dEl.innerHTML = `<b>${p.nombre}</b> - ${moneyFmt.format(p.precio_venta)}`;
                        dEl.onclick = () => { 
                            ventas.sel = p;
                            document.getElementById('producto-seleccionado').innerHTML = `<div style="background:var(--color-input-bg); padding:10px; border-radius:8px; border-left:4px solid var(--color-primario);"><b>${p.nombre}</b> | Stock: ${p.stock}</div>`;
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
            const fiado = document.getElementById('nombre-fiado').value;
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
            } else {
                if(!fiado) return Notificador.error('Falta deudor');
                
                // Validación de deudor duplicado: normalizar y verificar
                const fiadoNormalizado = fiado.toLowerCase().trim();
                const deudoresExistentes = document.querySelectorAll('.deudor-list tr');
                let deudorExistente = null;
                
                deudoresExistentes.forEach(row => {
                    const celda = row.querySelector('td:first-child');
                    if (celda) {
                        const nombreExistente = celda.textContent.toLowerCase().trim();
                        if (nombreExistente === fiadoNormalizado) {
                            deudorExistente = celda.textContent;
                        }
                    }
                });
                
                // Si existe un deudor similar, confirmar o sugerir
                if (deudorExistente) {
                    const cfg = Notificador.getConfig();
                    const { isDismissed } = await Swal.fire({
                        title: '⚠️ Deudor Existente',
                        html: `Se encontró un deudor registrado como: <b>"${deudorExistente}"</b><br><br>¿Desea agregar deuda al mismo deudor o crear un nuevo registro?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Agregar deuda al existente',
                        cancelButtonText: 'Crear nuevo registro',
                        background: cfg.bg,
                        color: cfg.color
                    });
                    
                    if (!isDismissed) {
                        // Usar el nombre del deudor existente
                        document.getElementById('nombre-fiado').value = deudorExistente;
                    }
                }
                
                if(!(await Notificador.confirm(`¿Fiar a ${fiado}?`))) return;
            }
            
            const grupo = document.getElementById('grupo-fiado').value;
            if (tipo === 'fiado' && !grupo) {
                return Notificador.error('Selecciona el grupo del deudor');
            }
            
            fetchWithCSRF('api/api_ventas.php', { 
                method: 'POST', 
                headers: {'Content-Type':'application/json'}, 
                body: JSON.stringify({
                    carrito: ventas.cart, 
                    tipo_pago: tipo, 
                    nombre_fiado: fiado,
                    grupo_fiado: grupo,
                    csrf_token: sessionStorage.getItem('csrf_token')
                }) 
            }).then(r => r.json()).then(d => {
                if(d.success) { 
                    if(tipo !== 'pagado') Notificador.success('✅ Venta fiada registrada'); 
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
            const esFiado = e.target.value === 'fiado';
            document.getElementById('nombre-fiado-group').style.display = esFiado ? 'block' : 'none';
            document.getElementById('grupo-fiado-group').style.display = esFiado ? 'block' : 'none';
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
            const resp = await fetch(`api/api_inventario.php?accion=buscar&q=${encodeURIComponent(codigo)}&limit=1`);
            const data = await resp.json();
            
            if (data.success && data.productos && data.productos.length > 0) {
                const producto = data.productos[0];
                ventas.sel = producto;
                
                document.getElementById('producto-seleccionado').innerHTML = 
                    `<div style="background:var(--color-input-bg); padding:10px; border-radius:8px; border-left:4px solid #4caf50;">
                        <b>${producto.nombre}</b> | Stock: ${producto.stock} | ${moneyFmt.format(producto.precio_venta)}
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
            l.innerHTML += `<li>${p.cantidad}x ${p.nombre} <b>${moneyFmt.format(p.precio_venta * p.cantidad)}</b> <button class="btn-icon btn-delete" style="width:30px; height:30px; padding:0;" onclick="ventas.del(${i})"><i class="fas fa-times"></i></button></li>`;
        });
        document.getElementById('carrito-total').innerText = `Total: ${moneyFmt.format(t)}`;
    },
    del: (i) => { ventas.cart.splice(i,1); ventas.render(); },
    loadDebtors: () => {
        fetch('api/api_ventas.php?accion=listar_fiados').then(r=>r.json()).then(d=>{
            const t = document.getElementById('cuerpo-tabla-deudores'); t.innerHTML = '';
            if(d.success && d.deudores) d.deudores.forEach(x => { 
                t.innerHTML += `<tr>
                    <td>${x.nombre_fiado}${x.grupo ? ` <span style="font-size:0.75rem; opacity:0.7;">(${x.grupo})</span>` : ''}</td>
                    <td>${moneyFmt.format(x.total_deuda)}</td>
                    <td>
                        <div style="display:flex; gap:5px;">
                            <button class="btn-icon btn-pay" onclick="ventas.pay('${x.nombre_fiado}',${x.total_deuda})" title="Cobrar">
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
        if(await Notificador.confirm(`¿Cobrar ${moneyFmt.format(m)}?`)) {
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
                
                fetch('api/api_inventario.php?accion=listar')
                    .then(r=>{
                        if (!r.ok) throw new Error(`Error HTTP ${r.status}`);
                        return r.json();
                    })
                    .then(d=>{
                        if(d.success){
                            inventario.listData = d.productos || []; 
                            t.innerHTML = '';
                            
                            if(d.productos && d.productos.length > 0) {
                                d.productos.forEach((p, idx) => {
                                    const stockMin = p.stock_minimo || 10; // Default a 10 si no está configurado
                                    const isStockBajo = p.stock <= stockMin;
                                    const stockClass = isStockBajo ? 'style="background:#ffcdd2; color:#c62828; font-weight:bold; border-left:4px solid #f44336; padding-left:8px;"' : '';
                                    const rowClass = idx % 2 === 0 ? 'style="background:rgba(0,0,0,0.02);"' : '';
                                    
                                    let stockDisplay = `<div style="display:flex; align-items:center; gap:8px; justify-content:center;">
                                        ${p.stock}
                                        ${isStockBajo ? `<span style="background:#f44336; color:white; padding:3px 8px; border-radius:12px; font-size:0.75rem; font-weight:bold; white-space:nowrap;">⚠️ BAJO</span>` : ''}
                                    </div>`;
                                    
                                    let btnAcciones = '';
                                    if(document.body.getAttribute('data-role') !== 'vendedor') {
                                        btnAcciones = `<div style="display:flex; gap:5px; flex-wrap:wrap;">
                                            <button class="btn-icon btn-edit" onclick="inventario.preEdit(${p.id})" title="Editar" data-id="${p.id}">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <button class="btn-icon btn-info" onclick="inventario.verHistorial(${p.id}, '${p.nombre.replace(/'/g, "\\'")}')" title="Historial" style="background:#2196f3;">
                                                <i class="fas fa-history"></i>
                                            </button>
                                            <button class="btn-icon btn-delete" onclick="if(confirm('¿Eliminar ${p.nombre}?')) inventario.del(${p.id})" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>`;
                                    }
                                    
                                    const tr = `<tr ${isStockBajo ? stockClass : rowClass}>
                                        <td style="width:50px; text-align:center;">
                                            <img src="${p.foto_url||PLACEHOLDER_IMG}" class="foto-preview" style="width:40px; height:40px; border-radius:4px; object-fit:cover;">
                                        </td>
                                        <td style="font-weight:500;">${p.nombre}</td>
                                        <td style="opacity:0.7; font-family:monospace;">${p.codigo_barras||'-'}</td>
                                        <td style="text-align:right; color:var(--color-primario); font-weight:500;">${moneyFmt.format(p.precio_venta||0)}</td>
                                        <td style="text-align:center;">${stockDisplay}</td>
                                        <td style="text-align:center;">${btnAcciones}</td>
                                    </tr>`;
                                    
                                    t.innerHTML += tr;
                                });
                            } else {
                                t.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:30px; opacity:0.5;"><i class="fas fa-inbox"></i> Sin productos. Agrega uno nuevo.</td></tr>';
                            }
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
                    
                    fetch('api/api_inventario.php', {method:'POST', body:fd})
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
            const resp = await fetch('api/api_inventario.php?accion=stock_bajo');
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
            document.getElementById('producto-codigo').value = p.codigo_barras || '';
            document.getElementById('producto-precio').value = p.precio_venta || 0;
            document.getElementById('producto-stock').value = p.stock || 0;
            document.getElementById('producto-foto').value = p.foto_url || '';
            
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
            const resp = await fetch(`api/api_inventario.php?accion=historial&producto_id=${id}`);
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
                
                const res = await fetch('api/api_inventario.php', {
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

    aplicarFiltros: () => {
        try {
            const buscar = document.getElementById('filtro-buscar').value.toLowerCase();
            const ordenar = document.getElementById('filtro-ordenar').value;
            const stockFiltro = document.getElementById('filtro-stock').value;
            
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

            if (ordenar === 'nombre') productos.sort((a, b) => a.nombre.localeCompare(b.nombre));
            if (ordenar === 'nombre_desc') productos.sort((a, b) => b.nombre.localeCompare(a.nombre));
            if (ordenar === 'stock_asc') productos.sort((a, b) => a.stock - b.stock);
            if (ordenar === 'stock_desc') productos.sort((a, b) => b.stock - a.stock);
            if (ordenar === 'precio_asc') productos.sort((a, b) => a.precio_venta - b.precio_venta);
            if (ordenar === 'precio_desc') productos.sort((a, b) => b.precio_venta - a.precio_venta);

            const t = document.getElementById('cuerpo-tabla-inventario');
            t.innerHTML = '';
            
            if (productos.length > 0) {
                productos.forEach((p, idx) => {
                    const stockClass = p.stock <= 5 ? 'style="color:#f44336; font-weight:bold;"' : '';
                    const rowClass = idx % 2 === 0 ? 'style="background:rgba(0,0,0,0.02);"' : '';
                    
                    let btnAcciones = '';
                    if(document.body.getAttribute('data-role') !== 'vendedor') {
                        btnAcciones = `<div style="display:flex; gap:5px; flex-wrap:wrap;">
                            <button class="btn-icon btn-edit" onclick="inventario.preEdit(${p.id})" title="Editar">
                                <i class="fas fa-pen"></i>
                            </button>
                            <button class="btn-icon btn-info" onclick="inventario.verHistorial(${p.id}, '${p.nombre.replace(/'/g, "\\'")}')" title="Historial" style="background:#2196f3;">
                                <i class="fas fa-history"></i>
                            </button>
                            <button class="btn-icon btn-delete" onclick="if(confirm('¿Eliminar ${p.nombre}?')) inventario.del(${p.id})" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>`;
                    }
                    
                    const tr = `<tr ${rowClass}>
                        <td style="width:50px; text-align:center;">
                            <img src="${p.foto_url||PLACEHOLDER_IMG}" class="foto-preview" style="width:40px; height:40px; border-radius:4px; object-fit:cover;">
                        </td>
                        <td style="font-weight:500;">${p.nombre}</td>
                        <td style="opacity:0.7; font-family:monospace;">${p.codigo_barras||'-'}</td>
                        <td style="text-align:right; color:var(--color-primario); font-weight:500;">${moneyFmt.format(p.precio_venta||0)}</td>
                        <td style="text-align:center;" ${stockClass}>${p.stock}</td>
                        <td style="text-align:center;">${btnAcciones}</td>
                    </tr>`;
                    
                    t.innerHTML += tr;
                });
            } else {
                t.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:30px; opacity:0.5;"><i class="fas fa-search"></i> No se encontraron productos con esos filtros</td></tr>';
            }
            
            Notificador.success('✓ Filtros aplicados', `${productos.length} producto(s) encontrado(s)`);
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
            const res = await fetch('api/api_inventario.php?accion=stock_bajo');
            
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

        if(t) fetch('api/api_registros.php?accion=listar').then(r=>r.json()).then(d=>{ 
            if(d.success){ 
                registros.cache = d.registros || [];
                t.innerHTML = ''; 
                registros.cache.forEach(r => {
                    const colores = {
                        'efectivo':'green', 'ingreso':'green', 'fiado':'blue', 'gasto':'red', 'egreso':'red',
                        'merma':'#e67e22', 'septima':'#6a1b9a', 'septima_especial':'#ab47bc',
                        'arca_ingreso':'#1b5e20','arca_egreso':'#b71c1c','arca_gasto':'#c62828','arca_merma':'#ef6c00'
                    };
                    const color = colores[r.tipo] || 'gray';
                    const servicio = r.servicio ? r.servicio : '—';
                    const categoria = r.categoria ? r.categoria : '—';
                    t.innerHTML += `<tr><td>${r.fecha}</td><td><span style="font-weight:bold; color:${color}">${r.tipo.toUpperCase()}</span></td><td>${categoria}</td><td>${servicio}</td><td>${r.concepto}</td><td>${moneyFmt.format(r.monto)}</td><td>${r.usuario}</td><td class="admin-only"><div style="display:flex; gap:6px;">` +
                        `<button class="btn-icon btn-info" title="Historial" onclick="registros.verHistorial(${r.id}, '${r.concepto.replace(/'/g, "\\'")}')" style="background:#2196f3;"><i class="fas fa-history"></i></button>`+
                        `<button class="btn-icon btn-edit" title="Editar" onclick="registros.edit(${r.id})"><i class="fas fa-pen"></i></button>`+
                        `<button class="btn-icon btn-delete" style="width:30px;height:30px;padding:0;" onclick="registros.del(${r.id})"><i class="fas fa-times"></i></button>`+
                        `</div></td></tr>`; 
                }); 
            } 
        });

        if(tbodyVentas) fetch('api/api_ventas.php?accion=listar_ventas').then(r=>r.json()).then(d=>{ 
            if(d.success){ 
                tbodyVentas.innerHTML = ''; 
                d.ventas.forEach(v => { 
                    const btnEliminar = `<button class="btn-icon btn-undo" onclick="registros.undoWithAuth(${v.id})" title="Cancelar venta"><i class="fas fa-ban"></i></button>`;
                    tbodyVentas.innerHTML += `<tr><td>${v.fecha}</td><td>${v.vendedor}</td><td>${v.producto_nombre||'Borrado'}</td><td>${v.cantidad}</td><td>${moneyFmt.format(v.total)}</td><td>${v.tipo_pago}</td><td><div style="display:flex; justify-content:center;">${btnEliminar}</div></td></tr>`; 
                }); 
            } 
        });

        fetch('api/api_registros.php?accion=corte_dia').then(r=>r.json()).then(d=>{
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
        fetch('api/api_septimas.php?accion=listar').then(r=>r.json()).then(d=>{
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
        if(t) fetch('api/api_usuarios.php?accion=listar').then(r=>r.json()).then(d=>{ if(d.success){ t.innerHTML=''; d.usuarios.forEach(u => t.innerHTML+=`<tr><td>${u.username}</td><td>${u.role}</td><td><button class="btn-icon btn-delete" onclick="usuarios.del(${u.id})"><i class="fas fa-times"></i></button></td></tr>`); } });
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
        if (t) {
            fetchWithCSRF('api/api_admin.php?accion=ver_errores')
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        t.innerHTML = '';
                        d.errores.forEach(e => {
                            const msg = e.error || e.mensaje || e.detalles || e.linea || '';
                            t.innerHTML += `<tr><td>${e.fecha}</td><td>${msg}</td></tr>`;
                        });
                    }
                })
                .catch(err => logError('Log Load Error', err.message || err, 'ver_errores'));
        }

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
                        if (t) t.innerHTML = '';
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

            fetch('api/api_admin.php?accion=get_color').then(r=>r.json()).then(d=>{ 
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

            // Cargar top producto del mes
            try {
                const resTop = await fetch('api/api_admin.php?accion=top_producto_mes');
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

            // Cargar stock bajo
            try {
                const resStock = await fetch('api/api_inventario.php?accion=stock_bajo');
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

            // Cargar ventas del mes
            try {
                const resVentas = await fetch('api/api_admin.php?accion=ventas_mes');
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

            const res = await fetch('api/api_admin.php?accion=stats_globales');
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

window.ventas = ventas; window.inventario = inventario; window.registros = registros; window.usuarios = usuarios; window.admin = admin; window.septimas = septimas; window.diagnostico = diagnostico; window.estadisticas = estadisticas; window.mantenimiento = mantenimiento; window.exportacion = exportacion;

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
    
    setInterval(()=>{ const d=new Date(); document.getElementById('reloj-digital').innerText=d.toLocaleTimeString('es-MX',{hour:'2-digit',minute:'2-digit'}); },1000);
});
