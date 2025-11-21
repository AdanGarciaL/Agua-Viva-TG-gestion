// assets/js/app.js - v4.0

const moneyFmt = new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' });
const debounce = (func, delay) => { let t; return (...args) => { clearTimeout(t); t = setTimeout(() => func.apply(this, args), delay); }; };
const PLACEHOLDER_IMG = 'assets/img/placeholder.png';

const Notificador = {
    getConfig() {
        const esDark = document.body.getAttribute('data-theme') === 'dark';
        return { bg: esDark ? '#1e1e1e' : '#fff', color: esDark ? '#e0e0e0' : '#333' };
    },
    success(t, tx) {
        const c = this.getConfig();
        Swal.fire({icon:'success', title:t, text:tx, timer:1500, showConfirmButton:false, background:c.bg, color:c.color});
    },
    error(t, tx) {
        const c = this.getConfig();
        Swal.fire({icon:'error', title:t, text:tx, background:c.bg, color:c.color});
    },
    async confirm(t, tx='') {
        const c = this.getConfig();
        return (await Swal.fire({title:t, text:tx, icon:'warning', showCancelButton:true, confirmButtonColor:'#0d47a1', cancelButtonColor:'#d33', background:c.bg, color:c.color})).isConfirmed;
    }
};

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
            if(targetId==='config') usuarios.load();
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
            if(dark) { body.setAttribute('data-theme', 'dark'); localStorage.setItem('theme', 'dark'); if(icon) icon.className='fas fa-sun'; }
            else { body.removeAttribute('data-theme'); localStorage.setItem('theme', 'light'); if(icon) icon.className='fas fa-moon'; }
        };
        if(localStorage.getItem('theme') === 'dark') set(true);
        if(btn) btn.onclick = () => set(!body.hasAttribute('data-theme'));
    }
};

// Módulos de Negocio
const ventas = {
    db: [], cart: [], sel: null,
    init: () => {
        fetch('api/api_inventario.php?accion=listar').then(r=>r.json()).then(d=>{ if(d.success) ventas.db=d.productos; });
        ventas.loadDebtors();
        const input = document.getElementById('buscar-producto');
        const resContainer = document.getElementById('resultados-busqueda');
        
        input.onkeyup = debounce(() => {
            const q = input.value.toLowerCase();
            resContainer.innerHTML = '';
            if(q.length < 2){ resContainer.style.display='none'; return; }
            const hits = ventas.db.filter(p => p.nombre.toLowerCase().includes(q) || (p.codigo_barras && p.codigo_barras.includes(q)));
            if(hits.length){
                resContainer.style.display='block';
                hits.forEach(p => {
                    const d = document.createElement('div'); d.className = 'resultado-item';
                    d.innerHTML = `<b>${p.nombre}</b> - ${moneyFmt.format(p.precio_venta)}`;
                    d.onclick = () => { 
                        ventas.sel = p;
                        document.getElementById('producto-seleccionado').innerHTML = `<div style="background:var(--color-input-bg); padding:10px; border-radius:8px; border-left:4px solid var(--color-primario);"><b>${p.nombre}</b> | Stock: ${p.stock}</div>`;
                        resContainer.style.display = 'none'; input.value = '';
                        document.getElementById('cantidad-venta').focus();
                    };
                    resContainer.appendChild(d);
                });
            } else resContainer.style.display='none';
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
            fetch('api/api_ventas.php', { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({carrito: ventas.cart, tipo_pago: tipo, nombre_fiado: fiado}) }).then(r => r.json()).then(d => {
                if(d.success) { if(tipo !== 'pagado') Notificador.success('Venta Exitosa'); ventas.cart = []; ventas.render(); ventas.loadDebtors(); ventas.init(); if(registros && registros.load) registros.load(); } else Notificador.error('Error', d.message);
            });
        };
        document.getElementById('tipo-pago').onchange = (e) => document.getElementById('nombre-fiado-group').style.display = (e.target.value === 'fiado') ? 'block' : 'none';
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
            fetch('api/api_ventas.php', {method:'POST', body:fd}).then(()=>{ ventas.loadDebtors(); registros.load(); });
        }
    }
};

const inventario = {
    listData: [],
    load: () => {
        const t = document.getElementById('cuerpo-tabla-inventario');
        const btnNew = document.getElementById('btn-mostrar-form-producto');
        const form = document.getElementById('form-producto');
        if(btnNew) btnNew.onclick = () => { form.style.display = (form.style.display==='none'||!form.style.display)?'block':'none'; form.reset(); document.getElementById('producto-id').value=''; };
        if(t) fetch('api/api_inventario.php?accion=listar').then(r=>r.json()).then(d=>{
            if(d.success){
                inventario.listData = d.productos; t.innerHTML = '';
                d.productos.forEach(p => { t.innerHTML += `<tr><td><img src="${p.foto_url||PLACEHOLDER_IMG}" class="foto-preview"></td><td>${p.nombre}</td><td>${p.codigo_barras||'-'}</td><td>${moneyFmt.format(p.precio_venta)}</td><td>${p.stock}</td><td class="admin-only"><div style="display:flex; gap:5px;"><button class="btn-icon btn-edit" onclick="inventario.preEdit(${p.id})" title="Editar"><i class="fas fa-pen"></i></button> <button class="btn-icon btn-delete" onclick="inventario.del(${p.id})" title="Eliminar"><i class="fas fa-trash"></i></button></div></td></tr>`; });
                if(document.body.getAttribute('data-role') === 'vendedor') document.querySelectorAll('.admin-only').forEach(e=>e.style.display='none');
            }
        });
        if(form) form.onsubmit = (e) => { 
            e.preventDefault(); const fd = new FormData(form); const id = document.getElementById('producto-id').value;
            fd.append('accion', id ? 'editar' : 'crear'); 
            fetch('api/api_inventario.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{ if(d.success) { Notificador.success(id?'Actualizado':'Guardado'); form.reset(); form.style.display='none'; inventario.load(); ventas.init(); } else Notificador.error(d.message); }); 
        };
    },
    preEdit: (id) => {
        const p = inventario.listData.find(x => x.id == id); if(!p) return;
        const form = document.getElementById('form-producto'); form.style.display = 'block';
        document.getElementById('producto-id').value = p.id; document.getElementById('producto-nombre').value = p.nombre; document.getElementById('producto-codigo').value = p.codigo_barras;
        document.getElementById('producto-precio').value = p.precio_venta; document.getElementById('producto-stock').value = p.stock; document.getElementById('producto-foto').value = p.foto_url;
        form.scrollIntoView({behavior:'smooth'});
    },
    del: async (id) => { if(await Notificador.confirm('¿Borrar?')) fetch('api/api_inventario.php',{method:'POST',body:new URLSearchParams({accion:'eliminar',id:id})}).then(()=>inventario.load()); }
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
                    t.innerHTML += `<tr><td>${r.fecha}</td><td><span style="font-weight:bold; color:${r.tipo=='ingreso'?'green':'red'}">${r.tipo.toUpperCase()}</span></td><td>${r.concepto}</td><td>${moneyFmt.format(r.monto)}</td><td>${r.usuario}</td><td class="admin-only"><button class="btn-icon btn-delete" style="width:30px;height:30px;padding:0;" onclick="registros.del(${r.id})"><i class="fas fa-times"></i></button></td></tr>`; 
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
            }
        });

        const f = document.getElementById('form-registro'); 
        if(f) f.onsubmit = (e) => { 
            e.preventDefault(); const fd=new FormData(f); fd.append('accion','crear'); 
            fetch('api/api_registros.php',{method:'POST',body:fd}).then(()=>{ 
                Notificador.success('Movimiento Registrado'); 
                f.reset(); 
                registros.load(); // Recarga tablas y corte automáticamente
            }); 
        };
    },
    del: async (id) => { if(await Notificador.confirm('¿Borrar registro?')) fetch('api/api_registros.php',{method:'POST',body:new URLSearchParams({accion:'eliminar',id:id})}).then(()=>registros.load()); },
    undo: async (id) => { if(await Notificador.confirm('¿Devolver Venta?')) fetch('api/api_ventas.php',{method:'POST',body:new URLSearchParams({accion:'eliminar_venta',id:id})}).then(()=>registros.load()); }
};

const septimas = {
    load: () => {
        const t = document.getElementById('cuerpo-tabla-septimas');
        if(t) fetch('api/api_septimas.php?accion=listar').then(r=>r.json()).then(d=>{ if(d.success){ t.innerHTML=''; d.septimas.forEach(s => { t.innerHTML+=`<tr><td>${s.fecha}</td><td>${s.nombre_padrino}</td><td>${moneyFmt.format(s.monto)}</td><td>${s.pagado?'PAGADO':'PENDIENTE'}</td><td><button class="btn-icon btn-delete" style="margin-right:5px;" onclick="septimas.del(${s.id})"><i class="fas fa-trash"></i></button> ${!s.pagado?`<button class="btn-icon btn-success" onclick="septimas.pay(${s.id})"><i class="fas fa-check"></i></button>`:''}</td></tr>`; }); } });
        const f = document.getElementById('form-septima'); if(f) f.onsubmit = (e) => { e.preventDefault(); const fd=new FormData(f); fd.append('accion','crear'); fetch('api/api_septimas.php',{method:'POST',body:fd}).then(()=>{ Notificador.success('Guardado'); f.reset(); septimas.load(); }); };
    },
    pay: async (id) => { if(await Notificador.confirm('¿Pagado?')) fetch('api/api_septimas.php',{method:'POST',body:new URLSearchParams({accion:'pagar',id:id})}).then(()=>septimas.load()); },
    del: async (id) => { if(await Notificador.confirm('¿Borrar?')) fetch('api/api_septimas.php',{method:'POST',body:new URLSearchParams({accion:'eliminar',id:id})}).then(()=>septimas.load()); }
};

const usuarios = {
    load: () => {
        const t = document.getElementById('cuerpo-tabla-usuarios');
        if(t) fetch('api/api_usuarios.php?accion=listar').then(r=>r.json()).then(d=>{ if(d.success){ t.innerHTML=''; d.usuarios.forEach(u => t.innerHTML+=`<tr><td>${u.username}</td><td>${u.role}</td><td><button class="btn-icon btn-delete" onclick="usuarios.del(${u.id})"><i class="fas fa-times"></i></button></td></tr>`); } });
        const f = document.getElementById('form-crear-usuario'); if(f) f.onsubmit = (e) => { e.preventDefault(); const fd=new FormData(f); fd.append('accion','crear'); fetch('api/api_usuarios.php',{method:'POST',body:fd}).then(()=>{ Notificador.success('Creado'); f.reset(); usuarios.load(); }); };
    },
    del: (id) => { if(confirm('¿Borrar?')) fetch('api/api_usuarios.php',{method:'POST',body:new URLSearchParams({accion:'eliminar',id:id})}).then(()=>usuarios.load()); }
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
                fetch('api/api_admin.php',{method:'POST',body:fd}); 
            };
        }
    }
};

window.ventas = ventas; window.inventario = inventario; window.registros = registros; window.septimas = septimas; window.usuarios = usuarios; window.admin = admin;

document.addEventListener('DOMContentLoaded', () => {
    initNav(); modTema.init(); ventas.init(); admin.load();
    const b1=document.getElementById('btn-reporte-inventario'); if(b1) b1.onclick=()=>window.location.href='api/api_reportes.php?reporte=inventario_hoy';
    const b2=document.getElementById('btn-reporte-consolidado'); if(b2) b2.onclick=()=>window.location.href='api/api_reportes.php?reporte=consolidado';
    const b3=document.getElementById('btn-respaldo-db'); if(b3) b3.onclick=()=>window.location.href='api/api_respaldo.php';
    setInterval(()=>{ const d=new Date(); document.getElementById('reloj-digital').innerText=d.toLocaleTimeString('es-MX',{hour:'2-digit',minute:'2-digit'}); },1000);
});