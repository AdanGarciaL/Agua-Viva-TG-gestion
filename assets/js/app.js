/**
 * ========================================
 * TG Gestión - Sistema de Punto de Venta
 * Versión 5.0 Offline Edition
 * ========================================
 * 
 * Características v5.0:
 * - ✅ 100% Offline (SQLite local)
 * - ✅ Personalización de colores (selector libre)
 * - ✅ Gradientes dinámicos adaptativos
 * - ✅ Modo claro/oscuro con temas personalizados
 * - ✅ Sistema robusto de manejo de errores
 * - ✅ Validación dual (Frontend + Backend)
 * - ✅ Corte de caja automático preciso
 * - ✅ Exportación profesional a Excel
 * - ✅ Sistema de roles (Vendedor/Admin/SuperAdmin)
 * 
 * Fecha: Noviembre 2025
 * Autor: Adán García López
 * GitHub: @AdanGarciaL
 */

const moneyFmt = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });
const debounce = (func, delay) => { let t; return (...args) => { clearTimeout(t); t = setTimeout(() => func.apply(this, args), delay); }; };
const PLACEHOLDER_IMG = 'assets/img/placeholder.png';

// ==========================================
// MANEJO GLOBAL DE ERRORES (v5.0)
// ==========================================
// Captura todos los errores JavaScript y promesas rechazadas
// para evitar crashes y proporcionar diagnóstico

window.addEventListener('error', function(e) {
    console.error('Error global capturado:', e.error);
    logError('JavaScript Error', e.error.message, e.error.stack);
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('Promise rechazada sin manejar:', e.reason);
    logError('Unhandled Promise Rejection', e.reason);
});

// Función para registrar errores en el servidor
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
        
        // Enviar al servidor si hay endpoint disponible
        if (typeof fetch !== 'undefined') {
            fetch('api/api_admin.php?accion=log_error', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(errorData)
            }).catch(err => console.error('No se pudo registrar el error:', err));
        }
        
        // También log local
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
    success(t, tx) {
        const c = this.getConfig();
        if (typeof Swal !== 'undefined' && Swal.fire) {
            Swal.fire({icon:'success', title:t, text:tx, timer:1500, showConfirmButton:false, background:c.bg, color:c.color});
        } else {
            if (tx) alert(t + "\n" + tx); else alert(t);
        }
    },
    error(t, tx) {
        const c = this.getConfig();
        logError('User Notification', t, tx); // Log errores mostrados al usuario
        if (typeof Swal !== 'undefined' && Swal.fire) {
            Swal.fire({icon:'error', title:t, text:tx, background:c.bg, color:c.color});
        } else {
            alert((t ? t + "\n" : '') + (tx || ''));
        }
    },
    async confirm(t, tx='') {
        const c = this.getConfig();
        if (typeof Swal !== 'undefined' && Swal.fire) {
            return (await Swal.fire({title:t, text:tx, icon:'warning', showCancelButton:true, confirmButtonColor:'#0d47a1', cancelButtonColor:'#d33', background:c.bg, color:c.color})).isConfirmed;
        } else {
            return confirm((t || '') + '\n' + (tx || ''));
        }
    }
};

// Helper fetch que añade CSRF token desde sessionStorage automáticamente
// v5.0: Ahora con retry automático y backoff exponencial
function fetchWithCSRF(url, opts = {}, retries = 3) {
    opts.headers = opts.headers || {};
    const token = sessionStorage.getItem('csrf_token');
    if (token) {
        opts.headers['X-CSRF-Token'] = token;
    }
    
    // Función de retry con backoff exponencial
    const attemptFetch = (attemptsLeft) => {
        return fetch(url, opts)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response;
            })
            .catch(error => {
                if (attemptsLeft <= 0) {
                    logError('Fetch Error (sin reintentos)', error.message, url);
                    throw error;
                }
                
                // Calcular delay con backoff exponencial: 1s, 2s, 4s
                const delay = Math.pow(2, retries - attemptsLeft) * 1000;
                console.warn(`Fetch falló, reintentando en ${delay}ms... (${attemptsLeft} intentos restantes)`);
                
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
            if(targetId==='config') usuarios.load();
            if(targetId==='errores') admin.load();
        });
    });
}

// ==========================================
// MÓDULO DE TEMA CLARO/OSCURO (v5.0)
// ==========================================
// Permite cambiar entre modo claro y oscuro
// La preferencia se guarda en localStorage
const modTema = {
    init: () => {
        const btn = document.getElementById('btn-theme-toggle');
        const body = document.body;
        const icon = btn ? btn.querySelector('i') : null;
        const set = (dark) => {
            if(dark) { body.setAttribute('data-theme', 'dark'); localStorage.setItem('theme', 'dark'); if(icon) icon.className='fas fa-sun'; }
            else { body.removeAttribute('data-theme'); localStorage.setItem('theme', 'light'); if(icon) icon.className='fas fa-moon'; }
        };
        if(localStorage.getItem('theme') === 'dark') set(true);
        if(btn) btn.onclick = () => set(!body.hasAttribute('data-theme'));
    }
};

// ==========================================
// MÓDULO DE PERSONALIZACIÓN DE COLOR (v5.0)
// ==========================================
// Permite elegir cualquier color del espectro completo
// Genera automáticamente: primario, secundario (oscuro), acento (claro)
// Los gradientes se adaptan al color elegido manteniendo identidad de botones
const modColor = {
    init: () => {
        const colorInput = document.getElementById('color-picker-input');
        
        if (!colorInput) return;
        
        // Cargar color guardado
        const savedColor = localStorage.getItem('primary_color') || '#0d47a1';
        colorInput.value = savedColor;
        modColor.aplicarColor(savedColor);
        
        // Event listener para cambio de color
        colorInput.addEventListener('input', (e) => {
            const color = e.target.value;
            modColor.aplicarColor(color);
            localStorage.setItem('primary_color', color);
            
            // Notificar al usuario (opcional)
            if (typeof Notificador !== 'undefined') {
                Notificador.success('Color aplicado', 'Tu tema ha sido personalizado');
            }
        });
    },
    
    aplicarColor: (color) => {
        // Aplicar color a variables CSS
        document.documentElement.style.setProperty('--color-primario', color);
        
        // Calcular variaciones del color
        const rgb = modColor.hexToRgb(color);
        const darker = modColor.darken(rgb, 20);
        const lighter = modColor.lighten(rgb, 20);
        
        document.documentElement.style.setProperty('--color-secundario', modColor.rgbToHex(darker));
        document.documentElement.style.setProperty('--color-acento', modColor.rgbToHex(lighter));
        
        // Crear versión translúcida para focus de inputs
        document.documentElement.style.setProperty('--color-primario-translucido', `rgba(${rgb.r}, ${rgb.g}, ${rgb.b}, 0.1)`);
    },
    
    // Convierte color HEX a componentes RGB
    hexToRgb: (hex) => {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result ? {
            r: parseInt(result[1], 16),
            g: parseInt(result[2], 16),
            b: parseInt(result[3], 16)
        } : { r: 13, g: 71, b: 161 };
    },
    
    // Convierte componentes RGB a formato HEX
    rgbToHex: (rgb) => {
        return "#" + ((1 << 24) + (rgb.r << 16) + (rgb.g << 8) + rgb.b).toString(16).slice(1);
    },
    
    // Oscurece un color RGB en un porcentaje dado (para color secundario)
    darken: (rgb, percent) => {
        return {
            r: Math.max(0, Math.floor(rgb.r * (1 - percent / 100))),
            g: Math.max(0, Math.floor(rgb.g * (1 - percent / 100))),
            b: Math.max(0, Math.floor(rgb.b * (1 - percent / 100)))
        };
    },
    
    // Aclara un color RGB en un porcentaje dado (para color de acento)
    lighten: (rgb, percent) => {
        return {
            r: Math.min(255, Math.floor(rgb.r + (255 - rgb.r) * (percent / 100))),
            g: Math.min(255, Math.floor(rgb.g + (255 - rgb.g) * (percent / 100))),
            b: Math.min(255, Math.floor(rgb.b + (255 - rgb.b) * (percent / 100)))
        };
    }
};

// ==========================================
// MÓDULO DE VENTAS (v5.0)
// ==========================================
// Maneja el punto de venta: búsqueda de productos, carrito, finalización de venta
const ventas = {
    db: [], cart: [], sel: null,
    init: () => {
        // No pre-cargamos toda la DB en memoria en ambientes de muchos productos.
        // now search on demand via API.
        try {
            ventas.loadDebtors();
            const input = document.getElementById('buscar-producto');
            const resContainer = document.getElementById('resultados-busqueda');
            
            if (!input || !resContainer) {
                console.warn('Elementos de búsqueda no encontrados');
                return;
            }
            
            input.onkeyup = debounce(() => {
                const q = input.value.trim();
                resContainer.innerHTML = '';
                if(q.length < 2){ resContainer.style.display='none'; return; }
                // Llamada al servidor para búsqueda (limit 12)
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
            ventas.render(); ventas.sel = null; document.getElementById('producto-seleccionado').innerHTML = ''; document.getElementById('cantidad-venta').value = 1;
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
                if(!(await Notificador.confirm(`¿Fiar a ${fiado}?`))) return;
            }
            fetchWithCSRF('api/api_ventas.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({carrito: ventas.cart, tipo_pago: tipo, nombre_fiado: fiado, csrf_token: sessionStorage.getItem('csrf_token')}) }).then(r => r.json()).then(d => {
                if(d.success) { 
                    if(tipo !== 'pagado') Notificador.success('Venta Exitosa'); 
                    ventas.cart = []; 
                    ventas.render(); 
                    ventas.loadDebtors(); 
                    ventas.init(); 
                    if(registros && registros.load) registros.load(); 
                    if(estadisticas && estadisticas.cargarAdmin) estadisticas.cargarAdmin();
                } else Notificador.error('Error', d.message);
            });
        };
        document.getElementById('tipo-pago').onchange = (e) => document.getElementById('nombre-fiado-group').style.display = (e.target.value === 'fiado') ? 'block' : 'none';
        } catch (error) {
            console.error('Error inicializando ventas:', error);
            logError('Ventas Init Error', error.message, error.stack);
            Notificador.error('Error', 'No se pudo inicializar el módulo de ventas');
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
            if(d.success && d.deudores) d.deudores.forEach(x => { t.innerHTML += `<tr><td>${x.nombre_fiado}</td><td>${moneyFmt.format(x.total_deuda)}</td><td><button class="btn-icon btn-pay" onclick="ventas.pay('${x.nombre_fiado}',${x.total_deuda})"><i class="fas fa-dollar-sign"></i> Cobrar</button></td></tr>`; });
        });
    },
    pay: async (n, m) => { 
        if(await Notificador.confirm(`¿Cobrar ${moneyFmt.format(m)}?`)) {
            const fd = new FormData(); fd.append('accion','pagar_fiado'); fd.append('nombre_fiado',n); fd.append('monto_pagado',m);
            appendCsrfToFormData(fd);
            fetchWithCSRF('api/api_ventas.php', {method:'POST', body:fd}).then(()=>{ ventas.loadDebtors(); registros.load(); });
        }
    }
};

const inventario = {
    listData: [],
    load: () => {
        // v5.0: Cargar estadísticas admin si aplica
        const role = sessionStorage.getItem('user_role');
        if (role === 'admin' || role === 'superadmin') {
            estadisticas.cargarAdmin();
        }
        
        const t = document.getElementById('cuerpo-tabla-inventario');
        const btnNew = document.getElementById('btn-mostrar-form-producto');
        const form = document.getElementById('form-producto');
        if(btnNew) btnNew.onclick = () => { form.style.display = (form.style.display==='none'||!form.style.display)?'block':'none'; form.reset(); document.getElementById('producto-id').value=''; };
        if(t) {
            fetch('api/api_inventario.php?accion=listar')
                .then(r=>{
                    if (!r.ok) throw new Error(`HTTP ${r.status}`);
                    return r.json();
                })
                .then(d=>{
                    if(d.success){
                        inventario.listData = d.productos; 
                        t.innerHTML = '';
                        if(d.productos && d.productos.length > 0) {
                            d.productos.forEach(p => { 
                                t.innerHTML += `<tr><td><img src="${p.foto_url||PLACEHOLDER_IMG}" class="foto-preview"></td><td>${p.nombre}</td><td>${p.codigo_barras||'-'}</td><td>${moneyFmt.format(p.precio_venta)}</td><td>${p.stock}</td><td class="admin-only"><div style="display:flex; gap:5px;"><button class="btn-icon btn-edit" onclick="inventario.preEdit(${p.id})" title="Editar"><i class="fas fa-pen"></i></button> <button class="btn-icon btn-delete" onclick="inventario.del(${p.id})" title="Eliminar"><i class="fas fa-trash"></i></button></div></td></tr>`; 
                            });
                        } else {
                            t.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:20px; opacity:0.5;">Sin productos</td></tr>';
                        }
                        if(document.body.getAttribute('data-role') === 'vendedor') document.querySelectorAll('.admin-only').forEach(e=>e.style.display='none');
                    } else {
                        console.error('API error:', d);
                        Notificador.error('Error al cargar inventario', d.message || 'Error desconocido');
                    }
                })
                .catch(e=>{
                    console.error('Fetch error:', e);
                    Notificador.error('Error al conectar', e.message);
                });
        }
        if(form) form.onsubmit = (e) => { 
            e.preventDefault(); const fd = new FormData(form); const id = document.getElementById('producto-id').value;
            fd.append('accion', id ? 'editar' : 'crear');
            appendCsrfToFormData(fd);
            fetch('api/api_inventario.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{ if(d.success) { Notificador.success(id?'Actualizado':'Guardado'); form.reset(); form.style.display='none'; inventario.load(); ventas.init(); } else Notificador.error(d.message); }).catch(e=>Notificador.error('Error', e.message)); 
        };
    },
    preEdit: (id) => {
        const p = inventario.listData.find(x => x.id == id); if(!p) return;
        const form = document.getElementById('form-producto'); form.style.display = 'block';
        document.getElementById('producto-id').value = p.id; document.getElementById('producto-nombre').value = p.nombre; document.getElementById('producto-codigo').value = p.codigo_barras;
        document.getElementById('producto-precio').value = p.precio_venta; document.getElementById('producto-stock').value = p.stock; document.getElementById('producto-foto').value = p.foto_url;
        form.scrollIntoView({behavior:'smooth'});
    },
    del: async (id) => { if(await Notificador.confirm('¿Borrar?')) {
            const params = new URLSearchParams({accion:'eliminar', id:id});
            const token = sessionStorage.getItem('csrf_token'); if(token) params.append('csrf_token', token);
            fetchWithCSRF('api/api_inventario.php',{method:'POST', body: params}).then(()=>inventario.load());
        } },
    // v5.0: Nuevas funciones de Admin
    aplicarFiltros: () => {
        const buscar = document.getElementById('filtro-buscar').value.toLowerCase();
        const ordenar = document.getElementById('filtro-ordenar').value;
        const stockFiltro = document.getElementById('filtro-stock').value;
        
        let productos = [...inventario.listData];
        
        // Filtrar por búsqueda
        if (buscar) {
            productos = productos.filter(p => 
                p.nombre.toLowerCase().includes(buscar) || 
                (p.codigo_barras && p.codigo_barras.includes(buscar))
            );
        }
        
        // Filtrar por stock
        if (stockFiltro === 'bajo') productos = productos.filter(p => p.stock < 10);
        if (stockFiltro === 'medio') productos = productos.filter(p => p.stock >= 10 && p.stock <= 50);
        if (stockFiltro === 'alto') productos = productos.filter(p => p.stock > 50);
        
        // Ordenar
        if (ordenar === 'nombre') productos.sort((a, b) => a.nombre.localeCompare(b.nombre));
        if (ordenar === 'nombre_desc') productos.sort((a, b) => b.nombre.localeCompare(a.nombre));
        if (ordenar === 'stock_asc') productos.sort((a, b) => a.stock - b.stock);
        if (ordenar === 'stock_desc') productos.sort((a, b) => b.stock - a.stock);
        if (ordenar === 'precio_asc') productos.sort((a, b) => a.precio_venta - b.precio_venta);
        if (ordenar === 'precio_desc') productos.sort((a, b) => b.precio_venta - a.precio_venta);
        
        // Renderizar
        const t = document.getElementById('cuerpo-tabla-inventario');
        t.innerHTML = '';
        if (productos.length > 0) {
            productos.forEach(p => {
                const stockClass = p.stock < 10 ? 'style="color:var(--color-danger); font-weight:bold;"' : '';
                t.innerHTML += `<tr><td><img src="${p.foto_url||PLACEHOLDER_IMG}" class="foto-preview"></td><td>${p.nombre}</td><td>${p.codigo_barras||'-'}</td><td>${moneyFmt.format(p.precio_venta)}</td><td ${stockClass}>${p.stock}</td><td class="admin-only"><div style="display:flex; gap:5px;"><button class="btn-icon btn-edit" onclick="inventario.preEdit(${p.id})" title="Editar"><i class="fas fa-pen"></i></button> <button class="btn-icon btn-delete" onclick="inventario.del(${p.id})" title="Eliminar"><i class="fas fa-trash"></i></button></div></td></tr>`;
            });
        } else {
            t.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:20px; opacity:0.5;">No se encontraron productos</td></tr>';
        }
        
        Notificador.success('Filtros aplicados', `${productos.length} producto(s) encontrado(s)`);
    },
    mostrarStockBajo: async () => {
        try {
            const res = await fetch('api/api_inventario.php?accion=stock_bajo');
            const data = await res.json();
            
            if (data.success && data.productos) {
                if (data.productos.length === 0) {
                    Notificador.success('Todo bien', 'No hay productos con stock bajo');
                    return;
                }
                
                let html = '<div style="max-height:400px; overflow-y:auto;"><table style="width:100%;">';
                html += '<thead><tr><th>Producto</th><th>Stock</th></tr></thead><tbody>';
                data.productos.forEach(p => {
                    html += `<tr><td>${p.nombre}</td><td style="color:#f44336; font-weight:bold;">${p.stock} unidades</td></tr>`;
                });
                html += '</tbody></table></div>';
                
                const cfg = Notificador.getConfig();
                await Swal.fire({
                    title: `⚠️ ${data.productos.length} Productos con Stock Bajo`,
                    html: html,
                    icon: 'warning',
                    confirmButtonText: 'Entendido',
                    background: cfg.bg,
                    color: cfg.color,
                    width: '600px'
                });
            }
        } catch (error) {
            console.error('Error mostrando stock bajo:', error);
            Notificador.error('Error', 'No se pudo cargar el stock bajo');
        }
    }
};

const registros = {
    load: () => {
        const t = document.getElementById('cuerpo-tabla-registros');
        const tbodyVentas = document.getElementById('cuerpo-tabla-ventas');
        
        // 1. Cargar Tabla Movimientos
        if(t) fetch('api/api_registros.php?accion=listar').then(r=>r.json()).then(d=>{ 
            if(d.success){ 
                t.innerHTML = ''; 
                d.registros.forEach(r => {
                    const colores = {'efectivo':'green', 'ingreso':'green', 'fiado':'blue', 'gasto':'red', 'egreso':'red'};
                    const color = colores[r.tipo] || 'gray';
                    t.innerHTML += `<tr><td>${r.fecha}</td><td><span style="font-weight:bold; color:${color}">${r.tipo.toUpperCase()}</span></td><td>${r.concepto}</td><td>${moneyFmt.format(r.monto)}</td><td>${r.usuario}</td><td class="admin-only"><button class="btn-icon btn-delete" style="width:30px;height:30px;padding:0;" onclick="registros.del(${r.id})"><i class="fas fa-times"></i></button></td></tr>`; 
                }); 
            } 
        });

        // 2. Cargar Historial Ventas
        if(tbodyVentas) fetch('api/api_ventas.php?accion=listar_ventas').then(r=>r.json()).then(d=>{ 
            if(d.success){ 
                tbodyVentas.innerHTML = ''; 
                d.ventas.forEach(v => { 
                    tbodyVentas.innerHTML += `<tr><td>${v.fecha}</td><td>${v.vendedor}</td><td>${v.producto_nombre||'Borrado'}</td><td>${v.cantidad}</td><td>${moneyFmt.format(v.total)}</td><td>${v.tipo_pago}</td><td class="admin-only"><button class="btn-icon btn-undo" onclick="registros.undo(${v.id})" title="Devolver"><i class="fas fa-undo"></i></button></td></tr>`; 
                }); 
            } 
        });

        // 3. CARGAR CORTE DE CAJA (NUEVO)
        fetch('api/api_registros.php?accion=corte_dia').then(r=>r.json()).then(d=>{
            if(d.success) {
                const c = d.corte;
                const gastosTotales = parseFloat(c.gastos) + parseFloat(c.retiros);
                
                document.getElementById('corte-ventas').innerText = moneyFmt.format(c.ventas_efectivo);
                document.getElementById('corte-ingresos').innerText = moneyFmt.format(c.ingresos_extra);
                document.getElementById('corte-gastos').innerText = '-' + moneyFmt.format(gastosTotales);
                document.getElementById('corte-total').innerText = moneyFmt.format(c.total_caja);
                
                // Mostrar fiados pendientes si existe el elemento
                const fiadosEl = document.getElementById('corte-fiados');
                if (fiadosEl && c.fiados_pendientes !== undefined) {
                    fiadosEl.innerText = moneyFmt.format(c.fiados_pendientes);
                }
            }
        });

        const f = document.getElementById('form-registro'); 
        if(f) f.onsubmit = (e) => { 
            e.preventDefault(); const fd=new FormData(f); fd.append('accion','crear'); appendCsrfToFormData(fd);
            fetchWithCSRF('api/api_registros.php',{method:'POST',body:fd}).then(()=>{ 
                Notificador.success('Movimiento Registrado'); 
                f.reset(); 
                registros.load(); // Recarga tablas y corte automáticamente
            }); 
        };
    },
    del: async (id) => { if(await Notificador.confirm('¿Borrar registro?')) {
            const params = new URLSearchParams({accion:'eliminar', id:id});
            const token = sessionStorage.getItem('csrf_token'); if(token) params.append('csrf_token', token);
            fetchWithCSRF('api/api_registros.php',{method:'POST',body:params}).then(()=>registros.load());
        } },
    undo: async (id) => { if(await Notificador.confirm('¿Devolver Venta?')) {
            const params = new URLSearchParams({accion:'eliminar_venta', id:id});
            const token = sessionStorage.getItem('csrf_token'); if(token) params.append('csrf_token', token);
            fetchWithCSRF('api/api_ventas.php',{method:'POST',body:params}).then(()=>registros.load());
        } }
};

// Módulo 'séptimas' deshabilitado en versión regional; se mantiene API por compatibilidad.
const septimas = {
    load: () => { console.warn('Modulo septimas deshabilitado'); },
    pay: async () => { Notificador.error('Módulo deshabilitado'); },
    del: async () => { Notificador.error('Módulo deshabilitado'); }
};

const usuarios = {
    load: () => {
        const t = document.getElementById('cuerpo-tabla-usuarios');
        if(t) fetch('api/api_usuarios.php?accion=listar').then(r=>r.json()).then(d=>{ if(d.success){ t.innerHTML=''; d.usuarios.forEach(u => t.innerHTML+=`<tr><td>${u.username}</td><td>${u.role}</td><td><button class="btn-icon btn-delete" onclick="usuarios.del(${u.id})"><i class="fas fa-times"></i></button></td></tr>`); } });
        const f = document.getElementById('form-crear-usuario'); if(f) f.onsubmit = (e) => { e.preventDefault(); const fd=new FormData(f); fd.append('accion','crear'); appendCsrfToFormData(fd); fetchWithCSRF('api/api_usuarios.php',{method:'POST',body:fd}).then(()=>{ Notificador.success('Creado'); f.reset(); usuarios.load(); }); };
    },
    del: (id) => { if(confirm('¿Borrar?')) {
            const params = new URLSearchParams({accion:'eliminar', id:id});
            const token = sessionStorage.getItem('csrf_token'); if(token) params.append('csrf_token', token);
            fetchWithCSRF('api/api_usuarios.php',{method:'POST',body:params}).then(()=>usuarios.load());
        } }
};

const admin = {
    load: () => {
        const t = document.getElementById('cuerpo-tabla-errores');
        if(t) fetch('api/api_admin.php?accion=ver_errores').then(r=>r.json()).then(d=>{ if(d.success){ t.innerHTML=''; d.errores.forEach(e=>t.innerHTML+=`<tr><td>${e.fecha}</td><td>${e.error}</td></tr>`); } });
        
        const pk = document.getElementById('color-picker');
        if(pk) {
            // Cargar y Aplicar al Navbar
            fetch('api/api_admin.php?accion=get_color').then(r=>r.json()).then(d=>{ 
                if(d.success) { 
                    document.documentElement.style.setProperty('--color-primario', d.color); 
                    pk.value=d.color; 
                } 
            });
            // Evento cambio
            pk.onchange=(e)=>{ 
                const c=e.target.value; 
                document.documentElement.style.setProperty('--color-primario',c); 
                const fd=new FormData(); fd.append('accion','save_color'); fd.append('color',c);
                appendCsrfToFormData(fd);
                fetchWithCSRF('api/api_admin.php',{method:'POST',body:fd}); 
            };
        }
    }
};

// v5.0: Nuevas funciones de diagnóstico
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

// v5.0: Estadísticas y Dashboard para Admin/SuperAdmin
const estadisticas = {
    cargarAdmin: async () => {
        try {
            console.log('[Stats] Cargando estadísticas admin...');
            
            // Top Producto del Mes
            const resTop = await fetch('api/api_admin.php?accion=top_producto_mes');
            const dataTop = await resTop.json();
            console.log('[Stats] Top producto:', dataTop);
            if (dataTop.success && dataTop.producto) {
                document.getElementById('top-producto').innerText = dataTop.producto.nombre || 'Sin datos';
                document.getElementById('top-producto-ventas').innerText = `${dataTop.producto.ventas || 0} ventas`;
            }
            
            // Stock Bajo (< 10 unidades)
            const resStock = await fetch('api/api_inventario.php?accion=stock_bajo');
            const dataStock = await resStock.json();
            console.log('[Stats] Stock bajo:', dataStock);
            if (dataStock.success) {
                const count = dataStock.productos ? dataStock.productos.length : 0;
                const elem = document.getElementById('stock-bajo-count');
                if (elem) {
                    elem.innerText = count;
                    elem.style.animation = count > 0 ? 'pulse 2s infinite' : 'none';
                }
            }
            
            // Ventas del Mes
            const resVentas = await fetch('api/api_admin.php?accion=ventas_mes');
            const dataVentas = await resVentas.json();
            console.log('[Stats] Ventas mes:', dataVentas);
            if (dataVentas.success) {
                document.getElementById('ventas-mes-total').innerText = moneyFmt.format(dataVentas.total || 0);
                const comparativa = dataVentas.comparativa || 0;
                const compText = comparativa > 0 ? `+${comparativa}%` : `${comparativa}%`;
                const compColor = comparativa > 0 ? '#4caf50' : '#f44336';
                document.getElementById('ventas-mes-comparativa').innerHTML = `<span style="color:${compColor}">${compText}</span> vs mes anterior`;
            }
        } catch (error) {
            console.error('[Stats] Error cargando estadísticas admin:', error);
        }
    },
    
    cargarSuperAdmin: async () => {
        try {
            console.log('[Stats] Cargando estadísticas superadmin...');
            // Estadísticas Globales
            const res = await fetch('api/api_admin.php?accion=stats_globales');
            const data = await res.json();
            console.log('[Stats] Stats globales:', data);
            
            if (data.success) {
                document.getElementById('stat-db-size').innerText = data.db_size || '--';
                document.getElementById('stat-ventas-total').innerText = moneyFmt.format(data.ventas_total || 0);
                document.getElementById('stat-usuarios-total').innerText = data.usuarios_total || 0;
                document.getElementById('stat-ultima-venta').innerText = data.ultima_venta || 'Nunca';
            }
        } catch (error) {
            console.error('[Stats] Error cargando stats superadmin:', error);
        }
    }
};

// v5.0: Funciones de Mantenimiento (SuperAdmin)
const mantenimiento = {
    optimizarBD: async () => {
        if (!await Notificador.confirm('¿Optimizar la base de datos?', 'Esto puede tardar unos segundos')) return;
        
        try {
            const res = await fetch('api/api_admin.php?accion=optimizar_bd', { method: 'GET' });
            const data = await res.json();
            
            if (data.success) {
                Notificador.success('¡Optimización Completa!', `Tamaño reducido: ${data.ahorro || '0 KB'}`);
                estadisticas.cargarSuperAdmin(); // Recargar stats
            } else {
                Notificador.error('Error', data.message || 'No se pudo optimizar');
            }
        } catch (error) {
            Notificador.error('Error', 'No se pudo optimizar la BD');
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

window.ventas = ventas; window.inventario = inventario; window.registros = registros; window.usuarios = usuarios; window.admin = admin; window.septimas = septimas; window.diagnostico = diagnostico; window.estadisticas = estadisticas; window.mantenimiento = mantenimiento;

document.addEventListener('DOMContentLoaded', () => {
    initNav(); modTema.init(); modColor.init(); ventas.init(); admin.load();
    const b1=document.getElementById('btn-reporte-inventario'); if(b1) b1.onclick=()=>window.location.href='api/api_reportes.php?reporte=inventario_hoy';
    const b2=document.getElementById('btn-reporte-consolidado'); if(b2) b2.onclick=()=>window.location.href='api/api_reportes.php?reporte=consolidado';
    const b3=document.getElementById('btn-respaldo-db'); if(b3) b3.onclick=()=>window.location.href='api/api_respaldo.php';
    
    // v5.0: Botón de verificar integridad
    const btnIntegridad = document.getElementById('btn-verificar-integridad');
    if (btnIntegridad) btnIntegridad.onclick = () => diagnostico.verificarIntegridad();
    
    // v5.0: Botones de mantenimiento (SuperAdmin)
    const btnOptimizar = document.getElementById('btn-optimizar-bd');
    if (btnOptimizar) btnOptimizar.onclick = () => mantenimiento.optimizarBD();
    
    const btnResetear = document.getElementById('btn-resetear-demo');
    if (btnResetear) btnResetear.onclick = () => mantenimiento.resetearDemo();
    
    // v5.0: Cargar estadísticas SuperAdmin al abrir tab config
    const tabConfig = document.querySelector('[data-tab="config"]');
    if (tabConfig) {
        tabConfig.addEventListener('click', () => {
            const role = sessionStorage.getItem('user_role');
            if (role === 'superadmin') {
                setTimeout(() => estadisticas.cargarSuperAdmin(), 100);
            }
        });
    }
    
    setInterval(()=>{ const d=new Date(); document.getElementById('reloj-digital').innerText=d.toLocaleTimeString('es-MX',{hour:'2-digit',minute:'2-digit'}); },1000);
});