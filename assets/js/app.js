// assets/js/app.js

const Notificador = {
    success(titulo, texto = '') { Swal.fire({ icon: 'success', title: titulo, text: texto, timer: 2000, showConfirmButton: false, toast: true, position: 'top-end', }); },
    error(titulo, texto = '') { Swal.fire({ icon: 'error', title: titulo, text: texto, }); },
    async confirm(titulo, texto = 'Esta acción no se puede revertir.') { const result = await Swal.fire({ title: titulo, text: texto, icon: 'warning', showCancelButton: true, confirmButtonColor: '#3085d6', cancelButtonColor: '#d33', confirmButtonText: 'Sí, continuar', cancelButtonText: 'Cancelar' }); return result.isConfirmed; },
    async prompt(titulo, inputLabel, valorInicial = '') { const { value } = await Swal.fire({ title: titulo, input: 'text', inputLabel: inputLabel, inputValue: valorInicial, showCancelButton: true, confirmButtonText: 'Aceptar', cancelButtonText: 'Cancelar', inputValidator: (value) => { if (!value || value.trim() === '') { return '¡Necesitas escribir un valor!'; } } }); return value; }
};

function safeEl(tag, text, className) { const el = document.createElement(tag); el.textContent = text; if (className) el.className = className; return el; }
const PLACEHOLDER_IMG = 'https://via.placeholder.com/150?text=Sin+Imagen';

// --- NAVEGACIÓN (ACTUALIZADA) ---
function inicializarNavegacion() {
    const navItems = document.querySelectorAll('.nav-item');
    const tabs = document.querySelectorAll('.tab-content');

    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const targetTab = item.getAttribute('data-tab');

            navItems.forEach(nav => nav.classList.remove('active'));
            tabs.forEach(tab => tab.classList.remove('active'));

            item.classList.add('active');
            const targetEl = document.getElementById(targetTab);
            if (targetEl) targetEl.classList.add('active');

            if (targetTab === 'ventas') moduloVentas.cargarDeudores();
            if (targetTab === 'inventario') moduloInventario.cargarInventario();
            if (targetTab === 'registros') moduloRegistros.cargarRegistros();
            if (targetTab === 'septimas') moduloSeptimas.cargar();
            if (targetTab === 'errores') moduloAdmin.cargarErrores();
            
            // AHORA CARGAMOS USUARIOS AL ENTRAR EN CONFIGURACIÓN
            if (targetTab === 'config') moduloUsuarios.cargar(); 
        });
    });
}

function inicializarReloj() {
    const relojEl = document.getElementById('reloj-digital'); if (!relojEl) return;
    function actualizarReloj() { const now = new Date(); const hora = now.getHours().toString().padStart(2, '0'); const min = now.getMinutes().toString().padStart(2, '0'); const seg = now.getSeconds().toString().padStart(2, '0'); const ampm = hora >= 12 ? 'PM' : 'AM'; const hora12 = (hora % 12) || 12; relojEl.textContent = `${hora12}:${min}:${seg} ${ampm}`; }
    actualizarReloj(); setInterval(actualizarReloj, 1000);
}

const moduloVentas = (function() {
    const buscarInput = document.getElementById('buscar-producto'); const resultadosDiv = document.getElementById('resultados-busqueda'); const productoSelDiv = document.getElementById('producto-seleccionado'); const cantidadInput = document.getElementById('cantidad-venta'); const agregarBtn = document.getElementById('agregar-carrito'); const carritoLista = document.getElementById('carrito-lista'); const carritoTotalEl = document.getElementById('carrito-total'); const finalizarBtn = document.getElementById('finalizar-venta'); const cancelarBtn = document.getElementById('cancelar-venta'); const tipoPagoSelect = document.getElementById('tipo-pago'); const nombreFiadoGroup = document.getElementById('nombre-fiado-group'); const nombreFiadoInput = document.getElementById('nombre-fiado'); const cuerpoTablaDeudores = document.getElementById('cuerpo-tabla-deudores');
    let productosDB = []; let productoSeleccionado = null; let carrito = []; let totalCache = 0;
    function cargarProductosVenta() { fetch('api/api_inventario.php?accion=listar').then(res => res.text()).then(text => { try { const data = JSON.parse(text); if(data.success) { productosDB = data.productos; moduloInventario.setProductosDB(data.productos); } else { console.error("Error API Inventario:", data.message); Notificador.error("Error de Conexión", data.message); } } catch (e) { console.error("Respuesta no válida:", text); } }).catch(err => console.error("Error Red:", err)); }
    function cargarDeudores() { if(!cuerpoTablaDeudores) return; fetch('api/api_ventas.php?accion=listar_fiados').then(res => res.json()).then(data => { if(!data.success) return; cuerpoTablaDeudores.innerHTML = ''; data.deudores.forEach(d => { const tr = document.createElement('tr'); const btnPagar = document.createElement('button'); btnPagar.className = 'btn-accion-tabla btn-success'; btnPagar.textContent = 'Pagar'; btnPagar.onclick = () => pagarDeuda(d.nombre_fiado, d.total_deuda); const tdAccion = document.createElement('td'); tdAccion.appendChild(btnPagar); tr.append(safeEl('td', d.nombre_fiado), safeEl('td', `$${parseFloat(d.total_deuda).toFixed(2)}`), tdAccion); cuerpoTablaDeudores.appendChild(tr); }); }).catch(e => console.log("Error cargando deudores")); }
    async function pagarDeuda(nombre, monto) { if (!(await Notificador.confirm(`¿Confirmar pago de $${monto} de ${nombre}?`))) return; const formData = new FormData(); formData.append('accion', 'pagar_fiado'); formData.append('nombre_fiado', nombre); formData.append('monto_pagado', monto); fetch('api/api_ventas.php', { method: 'POST', body: formData }).then(res => res.json()).then(data => { if(data.success) { Notificador.success('¡Deuda pagada!'); cargarDeudores(); moduloRegistros.cargarRegistros(); } else { Notificador.error('Error al pagar la deuda.'); } }); }
    function buscarProductos() { if(!buscarInput) return; const query = buscarInput.value.toLowerCase(); resultadosDiv.innerHTML = ''; productoSelDiv.innerHTML = ''; productoSeleccionado = null; if (query.length < 2) return; const filtrados = productosDB.filter(p => p.nombre.toLowerCase().includes(query) || (p.codigo_barras && p.codigo_barras.includes(query))); filtrados.forEach(p => { const item = safeEl('div', `${p.nombre} ($${p.precio_venta}) - Stock: ${p.stock}`, 'resultado-item'); item.addEventListener('click', () => seleccionarProducto(p)); resultadosDiv.appendChild(item); }); }
    function seleccionarProducto(producto) { productoSeleccionado = producto; productoSelDiv.innerHTML = `Seleccionado: <strong>${producto.nombre}</strong> (Stock: ${producto.stock})`; resultadosDiv.innerHTML = ''; buscarInput.value = producto.nombre; cantidadInput.value = 1; cantidadInput.max = producto.stock; }
    function agregarAlCarrito() { if (!productoSeleccionado) { Notificador.error('Por favor, selecciona un producto.'); return; } const cantidad = parseInt(cantidadInput.value); if (isNaN(cantidad) || cantidad <= 0 || cantidad > productoSeleccionado.stock) { Notificador.error('Cantidad no válida.'); return; } const itemExistente = carrito.find(item => item.id === productoSeleccionado.id); if (itemExistente) { itemExistente.cantidad += cantidad; } else { carrito.push({ id: productoSeleccionado.id, nombre: productoSeleccionado.nombre, precio: parseFloat(productoSeleccionado.precio_venta), cantidad: cantidad, foto: productoSeleccionado.foto_url }); } actualizarCarrito(); limpiarSeleccion(); }
    function actualizarCarrito() { carritoLista.innerHTML = ''; let total = 0; carrito.forEach((item, index) => { const li = document.createElement('li'); const divInfo = document.createElement('div'); divInfo.style.display = 'flex'; divInfo.style.alignItems = 'center'; const img = document.createElement('img'); img.src = item.foto || PLACEHOLDER_IMG; img.className = 'carrito-item-img'; img.onerror = () => { img.src = PLACEHOLDER_IMG; }; divInfo.append(img, safeEl('span', `${item.cantidad}x ${item.nombre}`, 'carrito-item-info')); const divPrecio = document.createElement('div'); divPrecio.style.display = 'flex'; divPrecio.style.alignItems = 'center'; const precio = safeEl('span', `$${(item.precio * item.cantidad).toFixed(2)}`, 'carrito-item-precio'); const btnQuitar = document.createElement('button'); btnQuitar.className = 'btn-quitar-item'; btnQuitar.innerHTML = '<i class="fas fa-trash-alt"></i>'; btnQuitar.onclick = () => { carrito.splice(index, 1); actualizarCarrito(); }; divPrecio.append(precio, btnQuitar); li.append(divInfo, divPrecio); carritoLista.appendChild(li); total += item.precio * item.cantidad; }); totalCache = total; carritoTotalEl.textContent = `Total: $${total.toFixed(2)}`; }
    function limpiarSeleccion() { productoSeleccionado = null; productoSelDiv.innerHTML = ''; buscarInput.value = ''; cantidadInput.value = 1; }
    async function cancelarVenta() { if (await Notificador.confirm('¿Seguro que quieres vaciar el carrito?')) { carrito = []; actualizarCarrito(); tipoPagoSelect.value = 'pagado'; nombreFiadoInput.value = ''; nombreFiadoGroup.style.display = 'none'; } }
    function toggleFiadoInput() { nombreFiadoGroup.style.display = (tipoPagoSelect.value === 'fiado') ? 'block' : 'none'; }
    async function finalizarVenta() { if (carrito.length === 0) { Notificador.error('El carrito está vacío.'); return; } const tipoPago = tipoPagoSelect.value; const nombreFiado = nombreFiadoInput.value.trim(); if (tipoPago === 'fiado' && nombreFiado === '') { Notificador.error('Falta nombre fiado'); nombreFiadoInput.focus(); return; } if (tipoPago === 'pagado') { const total = totalCache; const pagoClienteStr = await Notificador.prompt(`Total: $${total.toFixed(2)}`, 'Pago del cliente:', total.toFixed(2)); if (!pagoClienteStr) return; const pagoCliente = parseFloat(pagoClienteStr); if (isNaN(pagoCliente) || pagoCliente < total) { Notificador.error('Monto insuficiente.'); return; } await Swal.fire({ title: '¡Pagado!', text: `Cambio: $${(pagoCliente - total).toFixed(2)}`, icon: 'success' }); } else { if (!(await Notificador.confirm(`¿Fiado a ${nombreFiado}?`))) return; } fetch('api/api_ventas.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ carrito: carrito, tipo_pago: tipoPago, nombre_fiado: nombreFiado }) }).then(res => res.json()).then(data => { if (data.success) { Notificador.success('¡Venta registrada!'); carrito = []; actualizarCarrito(); tipoPagoSelect.value = 'pagado'; nombreFiadoInput.value = ''; nombreFiadoGroup.style.display = 'none'; cargarProductosVenta(); cargarDeudores(); moduloRegistros.cargarRegistros(); } else { Notificador.error('Error', data.message); } }); }
    return { init: () => { if(buscarInput) { cargarProductosVenta(); cargarDeudores(); buscarInput.addEventListener('keyup', buscarProductos); agregarBtn.addEventListener('click', agregarAlCarrito); cancelarBtn.addEventListener('click', cancelarVenta); finalizarBtn.addEventListener('click', finalizarVenta); tipoPagoSelect.addEventListener('change', toggleFiadoInput); } }, cargarDeudores: cargarDeudores };
})();

const moduloInventario = (function() {
    const btnMostrarForm = document.getElementById('btn-mostrar-form-producto'); const formProducto = document.getElementById('form-producto'); const cuerpoTablaInventario = document.getElementById('cuerpo-tabla-inventario'); const productoIdInput = document.getElementById('producto-id'); let productosDB = []; 
    function cargarInventario() { if(!cuerpoTablaInventario) return; fetch('api/api_inventario.php?accion=listar').then(res => res.json()).then(data => { if(!data.success) return; productosDB = data.productos; cuerpoTablaInventario.innerHTML = ''; data.productos.forEach(p => { const tr = document.createElement('tr'); const img = document.createElement('img'); img.src = (p.foto_url && p.foto_url.trim() !== '') ? p.foto_url : PLACEHOLDER_IMG; img.className = 'foto-preview'; img.onerror = () => { img.src = PLACEHOLDER_IMG; }; const tdAcciones = document.createElement('td'); tdAcciones.className = 'admin-only'; const btnEditar = document.createElement('button'); btnEditar.className = 'btn-accion-tabla btn-editar'; btnEditar.innerHTML = '<i class="fas fa-edit"></i>'; btnEditar.onclick = () => editarProducto(p); const btnEliminar = document.createElement('button'); btnEliminar.className = 'btn-accion-tabla btn-eliminar'; btnEliminar.innerHTML = '<i class="fas fa-trash"></i>'; btnEliminar.onclick = () => eliminarProducto(p.id); tdAcciones.append(btnEditar, btnEliminar); const tdFoto = document.createElement('td'); tdFoto.appendChild(img); tr.append(tdFoto, safeEl('td', p.nombre), safeEl('td', p.codigo_barras || 'N/A'), safeEl('td', `$${p.precio_venta}`), safeEl('td', p.stock), tdAcciones); cuerpoTablaInventario.appendChild(tr); }); }).catch(e => console.log("Error inventario")); }
    function guardarProducto(e) { e.preventDefault(); const formData = new FormData(formProducto); formData.append('accion', productoIdInput.value ? 'editar' : 'crear'); fetch('api/api_inventario.php', { method: 'POST', body: formData }).then(res => res.json()).then(data => { if (data.success) { Notificador.success('Guardado.'); productoIdInput.value = ''; formProducto.reset(); formProducto.style.display = 'none'; cargarInventario(); } else { Notificador.error('Error', data.message); } }); }
    function editarProducto(p) { productoIdInput.value = p.id; document.getElementById('producto-nombre').value = p.nombre; document.getElementById('producto-codigo').value = p.codigo_barras; document.getElementById('producto-precio').value = p.precio_venta; document.getElementById('producto-stock').value = p.stock; document.getElementById('producto-foto').value = p.foto_url || ''; formProducto.style.display = 'block'; window.scrollTo(0, 0); }
    async function eliminarProducto(id) { if (await Notificador.confirm('¿Eliminar producto?')) { const formData = new FormData(); formData.append('accion', 'eliminar'); formData.append('id', id); fetch('api/api_inventario.php', { method: 'POST', body: formData }).then(res => res.json()).then(data => { if (data.success) { Notificador.success('Eliminado'); cargarInventario(); } else { Notificador.error('Error', data.message); } }); } }
    return { init: () => { if(btnMostrarForm){ btnMostrarForm.addEventListener('click', () => { formProducto.style.display = (formProducto.style.display === 'none') ? 'block' : 'none'; productoIdInput.value = ''; formProducto.reset(); }); formProducto.addEventListener('submit', guardarProducto); } }, cargarInventario, setProductosDB: (data) => { productosDB = data; } };
})();

const moduloRegistros = (function() {
    const formRegistro = document.getElementById('form-registro'); const cuerpoTablaRegistros = document.getElementById('cuerpo-tabla-registros'); const cuerpoTablaVentas = document.getElementById('cuerpo-tabla-ventas'); 
    function cargarRegistrosManuales() { if(!cuerpoTablaRegistros) return; fetch('api/api_registros.php?accion=listar').then(res => res.json()).then(data => { if(!data.success) return; cuerpoTablaRegistros.innerHTML = ''; data.registros.forEach(r => { const tr = document.createElement('tr'); const btnEliminar = document.createElement('button'); btnEliminar.className = 'btn-accion-tabla btn-eliminar'; btnEliminar.innerHTML = '<i class="fas fa-trash"></i>'; btnEliminar.onclick = () => eliminarRegistro(r.id); const tdAccion = document.createElement('td'); tdAccion.className = 'admin-only'; tdAccion.appendChild(btnEliminar); tr.append(safeEl('td', new Date(r.fecha).toLocaleString()), safeEl('td', r.tipo), safeEl('td', r.concepto), safeEl('td', `$${r.monto}`), safeEl('td', r.usuario), tdAccion); cuerpoTablaRegistros.appendChild(tr); }); }); }
    function cargarHistorialVentas() { if(!cuerpoTablaVentas) return; fetch('api/api_ventas.php?accion=listar_ventas').then(res => res.json()).then(data => { if(!data.success) return; cuerpoTablaVentas.innerHTML = ''; data.ventas.forEach(v => { const tr = document.createElement('tr'); if(v.tipo_pago === 'fiado') { tr.classList.add('fiado-row'); if(v.fiado_pagado == 1) tr.classList.add('fiado-pagado'); } const btnEliminar = document.createElement('button'); btnEliminar.className = 'btn-accion-tabla btn-eliminar'; btnEliminar.innerHTML = '<i class="fas fa-trash"></i>'; btnEliminar.onclick = () => eliminarVenta(v.id); const tdAccion = document.createElement('td'); tdAccion.className = 'admin-only'; tdAccion.appendChild(btnEliminar); tr.append(safeEl('td', new Date(v.fecha).toLocaleString()), safeEl('td', v.vendedor), safeEl('td', v.producto_nombre || 'Borrado'), safeEl('td', v.cantidad), safeEl('td', `$${v.total}`), safeEl('td', v.tipo_pago), safeEl('td', v.nombre_fiado || 'N/A'), tdAccion); cuerpoTablaVentas.appendChild(tr); }); }); }
    function guardarRegistro(e) { e.preventDefault(); const formData = new FormData(formRegistro); formData.append('accion', 'crear'); fetch('api/api_registros.php', { method: 'POST', body: formData }).then(res => res.json()).then(data => { if (data.success) { Notificador.success('Guardado'); formRegistro.reset(); cargarRegistrosManuales(); } else { Notificador.error('Error', data.message); } }); }
    async function eliminarRegistro(id) { if (await Notificador.confirm('¿Eliminar registro manual?')) { const formData = new FormData(); formData.append('accion', 'eliminar'); formData.append('id', id); fetch('api/api_registros.php', { method: 'POST', body: formData }).then(res => res.json()).then(d => { if(d.success) { Notificador.success('Eliminado'); cargarRegistrosManuales(); } }); } }
    async function eliminarVenta(id) { if (await Notificador.confirm('¿Eliminar VENTA?', '(Se devolverá el stock)')) { const formData = new FormData(); formData.append('accion', 'eliminar_venta'); formData.append('id', id); fetch('api/api_ventas.php', { method: 'POST', body: formData }).then(res => res.json()).then(d => { if(d.success) { Notificador.success('Venta eliminada/Stock devuelto'); cargarHistorialVentas(); } else { Notificador.error('Error', d.message); } }); } }
    return { init: () => { if(formRegistro) formRegistro.addEventListener('submit', guardarRegistro); }, cargarRegistros: () => { cargarRegistrosManuales(); cargarHistorialVentas(); } };
})();

const moduloSeptimas = (function() {
    const form = document.getElementById('form-septima'); const tabla = document.getElementById('cuerpo-tabla-septimas'); const idInput = document.getElementById('septima-id'); const nombreInput = document.getElementById('septima-nombre'); const montoInput = document.getElementById('septima-monto');
    function cargar() { if(!tabla) return; fetch('api/api_septimas.php?accion=listar').then(r => r.json()).then(d => { if(!d.success) return; tabla.innerHTML = ''; d.septimas.forEach(s => { const tr = document.createElement('tr'); tr.className = s.pagado == 1 ? 'fiado-pagado' : 'fiado-row'; const tdAcciones = document.createElement('td'); if(s.pagado == 0) { const btnPagar = document.createElement('button'); btnPagar.className = 'btn-accion-tabla btn-success'; btnPagar.innerHTML = '<i class="fas fa-check"></i>'; btnPagar.onclick = () => pagar(s.id); tdAcciones.appendChild(btnPagar); } const btnEdit = document.createElement('button'); btnEdit.className = 'btn-accion-tabla btn-editar'; btnEdit.innerHTML = '<i class="fas fa-edit"></i>'; btnEdit.onclick = () => { idInput.value = s.id; nombreInput.value = s.nombre_padrino; montoInput.value = s.monto; form.scrollIntoView(); }; const btnDel = document.createElement('button'); btnDel.className = 'btn-accion-tabla btn-eliminar'; btnDel.innerHTML = '<i class="fas fa-trash"></i>'; btnDel.onclick = () => eliminar(s.id); tdAcciones.append(btnEdit, btnDel); tr.append(safeEl('td', new Date(s.fecha).toLocaleString()), safeEl('td', s.nombre_padrino), safeEl('td', `$${s.monto}`), safeEl('td', s.usuario_registro), safeEl('td', s.pagado == 1 ? 'Pagado' : 'Pendiente'), tdAcciones); tabla.appendChild(tr); }); }); }
    function guardar(e) { e.preventDefault(); const formData = new FormData(); formData.append('accion', idInput.value ? 'editar' : 'crear'); formData.append('id', idInput.value); formData.append('nombre', nombreInput.value); formData.append('monto', montoInput.value); fetch('api/api_septimas.php', { method: 'POST', body: formData }).then(r => r.json()).then(d => { if(d.success) { Notificador.success('Guardado'); idInput.value=''; form.reset(); cargar(); } else Notificador.error('Error', d.message); }); }
    async function pagar(id) { if(await Notificador.confirm('¿Marcar PAGADA?')) { const fd = new FormData(); fd.append('accion', 'pagar'); fd.append('id', id); fetch('api/api_septimas.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{ if(d.success) cargar(); }); } }
    async function eliminar(id) { if(await Notificador.confirm('¿ELIMINAR séptima?')) { const fd = new FormData(); fd.append('accion', 'eliminar'); fd.append('id', id); fetch('api/api_septimas.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{ if(d.success) cargar(); }); } }
    return { init: () => { if(form) form.addEventListener('submit', guardar); }, cargar };
})();

const moduloUsuarios = (function() {
    const form = document.getElementById('form-crear-usuario');
    const tabla = document.getElementById('cuerpo-tabla-usuarios');
    function cargar() {
        if(!tabla) return;
        fetch('api/api_usuarios.php?accion=listar').then(r => r.json()).then(d => {
            if(!d.success) return;
            tabla.innerHTML = '';
            d.usuarios.forEach(u => {
                const tr = document.createElement('tr');
                const btnEliminar = document.createElement('button');
                btnEliminar.className = 'btn-accion-tabla btn-eliminar';
                btnEliminar.innerHTML = '<i class="fas fa-trash"></i>';
                btnEliminar.onclick = () => eliminar(u.id, u.username);
                const tdAccion = document.createElement('td'); tdAccion.appendChild(btnEliminar);
                tr.append(safeEl('td', u.id), safeEl('td', u.username), safeEl('td', u.role.toUpperCase()), tdAccion);
                tabla.appendChild(tr);
            });
        }).catch(e => console.log("Error usuarios"));
    }
    function crear(e) {
        e.preventDefault();
        const user = document.getElementById('nuevo-usuario-user').value;
        const pass = document.getElementById('nuevo-usuario-pass').value;
        const role = document.getElementById('nuevo-usuario-role').value;
        const formData = new FormData();
        formData.append('accion', 'crear'); formData.append('username', user); formData.append('password', pass); formData.append('role', role);
        fetch('api/api_usuarios.php', { method: 'POST', body: formData }).then(res => res.json()).then(data => {
            if(data.success) { Notificador.success('Usuario creado.'); form.reset(); cargar(); } else { Notificador.error('Error', data.message); }
        });
    }
    async function eliminar(id, nombre) {
        if(await Notificador.confirm(`¿Eliminar usuario ${nombre}?`)) {
            const fd = new FormData(); fd.append('accion', 'eliminar'); fd.append('id', id);
            fetch('api/api_usuarios.php', { method: 'POST', body: fd }).then(res => res.json()).then(data => {
                if(data.success) { Notificador.success('Usuario eliminado.'); cargar(); } else { Notificador.error('Error', data.message); }
            });
        }
    }
    return { init: () => { if(form) form.addEventListener('submit', crear); }, cargar };
})();

const moduloAdmin = (function() {
    function cargarErrores() { const tabla = document.getElementById('cuerpo-tabla-errores'); if (!tabla) return; fetch('api/api_admin.php?accion=ver_errores').then(r => r.json()).then(d => { if (d.success) { tabla.innerHTML = ''; d.errores.forEach(e => { const tr = document.createElement('tr'); tr.append(safeEl('td', new Date(e.fecha).toLocaleString()), safeEl('td', e.error)); tabla.appendChild(tr); }); } }); }
    function inicializarConfig() { const pk = document.getElementById('color-picker'); if(!pk) return; const root = document.documentElement; const sv = localStorage.getItem('color-primario-app'); if(sv) { root.style.setProperty('--color-primario', sv); pk.value = sv; } pk.addEventListener('input', e => { root.style.setProperty('--color-primario', e.target.value); localStorage.setItem('color-primario-app', e.target.value); }); }
    function inicializarReportes() { const b1 = document.getElementById('btn-reporte-inventario'); if(b1) b1.addEventListener('click', () => { Notificador.success('Generando...'); window.location.href = 'api/api_reportes.php?reporte=inventario_hoy'; }); const b2 = document.getElementById('btn-reporte-consolidado'); if(b2) b2.addEventListener('click', () => { Swal.fire({title: 'Generando Reporte...', text: 'Espera un momento.', icon: 'info', timer: 3000, showConfirmButton: false}); window.location.href = 'api/api_reportes.php?reporte=consolidado'; }); }
    function inicializarRespaldo() { const btnRespaldo = document.getElementById('btn-respaldo-db'); if (btnRespaldo) { btnRespaldo.addEventListener('click', () => { Swal.fire({ title: 'Descargando Respaldo', text: 'Se descargará un archivo .sql. Guárdalo en un lugar seguro.', icon: 'success', timer: 2000, showConfirmButton: false }); window.location.href = 'api/api_respaldo.php'; }); } }
    return { init: () => { const br = document.getElementById('btn-recargar-errores'); if(br) br.addEventListener('click', cargarErrores); inicializarConfig(); inicializarReportes(); inicializarRespaldo(); }, cargarErrores };
})();

document.addEventListener('DOMContentLoaded', () => {
    inicializarReloj(); inicializarNavegacion();
    moduloVentas.init(); moduloInventario.init(); moduloRegistros.init(); moduloSeptimas.init(); 
    moduloUsuarios.init(); // Módulo de usuarios
    moduloAdmin.init();
});
window.addEventListener('pageshow', () => { document.body.classList.remove('swal2-shown', 'swal2-height-auto'); document.body.style.paddingRight = ''; });