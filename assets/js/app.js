// assets/js/app.js
// VERSIÓN FINAL BLINDADA v2.7
// Lógica del cliente con manejo de errores y validaciones de UI.

// --- 1. SISTEMA DE NOTIFICACIONES (SweetAlert2) ---
const Notificador = {
    success(titulo, texto = '') {
        Swal.fire({
            icon: 'success',
            title: titulo,
            text: texto,
            timer: 1500,
            showConfirmButton: false,
            backdrop: `rgba(0,0,0,0.1)` // Fondo suave
        });
    },
    error(titulo, texto = '') {
        Swal.fire({
            icon: 'error',
            title: titulo,
            text: texto,
            confirmButtonColor: '#d33'
        });
    },
    async confirm(titulo, texto = 'No podrás deshacer esta acción.') {
        const result = await Swal.fire({
            title: titulo,
            text: texto,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, continuar',
            cancelButtonText: 'Cancelar'
        });
        return result.isConfirmed;
    },
    async prompt(titulo, inputLabel, valorInicial = '') {
        const { value } = await Swal.fire({
            title: titulo,
            input: 'text',
            inputLabel: inputLabel,
            inputValue: valorInicial,
            showCancelButton: true,
            confirmButtonText: 'Aceptar',
            cancelButtonText: 'Cancelar',
            inputValidator: (value) => {
                if (!value || value.trim() === '') {
                    return '¡El campo no puede estar vacío!';
                }
            }
        });
        return value;
    }
};

// --- 2. UTILIDADES ---
function safeEl(tag, text, className) {
    const el = document.createElement(tag);
    el.textContent = text || '-'; // Si es null, pone guión
    if (className) el.className = className;
    return el;
}
const PLACEHOLDER_IMG = 'assets/img/placeholder.png'; // Asegúrate de tener una img básica o usa la de internet:
// const PLACEHOLDER_IMG = 'https://via.placeholder.com/50?text=IMG';

// --- 3. NAVEGACIÓN ENTRE PESTAÑAS ---
function inicializarNavegacion() {
    const navItems = document.querySelectorAll('.nav-item');
    const tabs = document.querySelectorAll('.tab-content');

    navItems.forEach(item => {
        item.addEventListener('click', (e) => {
            e.preventDefault();
            const targetTab = item.getAttribute('data-tab');

            // Cambiar clases visuales
            navItems.forEach(nav => nav.classList.remove('active'));
            tabs.forEach(tab => tab.classList.remove('active'));

            item.classList.add('active');
            const targetEl = document.getElementById(targetTab);
            if (targetEl) targetEl.classList.add('active');

            // Cargar datos frescos al cambiar de pestaña
            switch(targetTab) {
                case 'ventas': moduloVentas.cargarDeudores(); break;
                case 'inventario': moduloInventario.cargarInventario(); break;
                case 'registros': moduloRegistros.cargarRegistros(); break;
                case 'septimas': moduloSeptimas.cargar(); break;
                case 'config': moduloUsuarios.cargar(); break;
                case 'errores': moduloAdmin.cargarErrores(); break;
            }
        });
    });
}

// --- 4. RELOJ DIGITAL ---
function inicializarReloj() {
    const relojEl = document.getElementById('reloj-digital');
    if (!relojEl) return;
    
    function actualizar() {
        const now = new Date();
        let horas = now.getHours();
        const minutos = now.getMinutes().toString().padStart(2, '0');
        const ampm = horas >= 12 ? 'PM' : 'AM';
        horas = horas % 12;
        horas = horas ? horas : 12; // El 0 se vuelve 12
        relojEl.textContent = `${horas}:${minutos} ${ampm}`;
    }
    actualizar();
    setInterval(actualizar, 1000);
}

// --- MÓDULO: VENTAS ---
const moduloVentas = (function() {
    // Elementos DOM
    const buscarInput = document.getElementById('buscar-producto');
    const resultadosDiv = document.getElementById('resultados-busqueda');
    const productoSelDiv = document.getElementById('producto-seleccionado');
    const cantidadInput = document.getElementById('cantidad-venta');
    const agregarBtn = document.getElementById('agregar-carrito');
    const carritoLista = document.getElementById('carrito-lista');
    const carritoTotalEl = document.getElementById('carrito-total');
    const finalizarBtn = document.getElementById('finalizar-venta');
    const cancelarBtn = document.getElementById('cancelar-venta');
    const tipoPagoSelect = document.getElementById('tipo-pago');
    const nombreFiadoGroup = document.getElementById('nombre-fiado-group');
    const nombreFiadoInput = document.getElementById('nombre-fiado');
    const cuerpoTablaDeudores = document.getElementById('cuerpo-tabla-deudores');

    // Estado local
    let productosDB = []; 
    let productoSeleccionado = null;
    let carrito = [];
    
    // Cargar productos al inicio para búsqueda rápida
    function cargarProductosVenta() {
        fetch('api/api_inventario.php?accion=listar')
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    productosDB = data.productos;
                    // Sincronizar también con el módulo de inventario si es necesario
                    if(typeof moduloInventario !== 'undefined') moduloInventario.setProductosDB(data.productos);
                }
            })
            .catch(() => Notificador.error("Error de Red", "No se pudo cargar el catálogo."));
    }

    function cargarDeudores() {
        if(!cuerpoTablaDeudores) return;
        fetch('api/api_ventas.php?accion=listar_fiados')
            .then(res => res.json())
            .then(data => {
                if(!data.success) return;
                cuerpoTablaDeudores.innerHTML = '';
                if (data.deudores.length === 0) {
                    cuerpoTablaDeudores.innerHTML = '<tr><td colspan="3" style="text-align:center; color:#999;">Sin deudas pendientes</td></tr>';
                    return;
                }
                data.deudores.forEach(d => {
                    const tr = document.createElement('tr');
                    const btnPagar = document.createElement('button');
                    btnPagar.className = 'btn-accion-tabla btn-success';
                    btnPagar.innerHTML = '<i class="fas fa-money-bill-wave"></i> Pagar';
                    btnPagar.onclick = () => pagarDeuda(d.nombre_fiado, d.total_deuda);
                    
                    const tdAccion = document.createElement('td');
                    tdAccion.appendChild(btnPagar);

                    tr.append(safeEl('td', d.nombre_fiado), safeEl('td', `$${parseFloat(d.total_deuda).toFixed(2)}`), tdAccion);
                    cuerpoTablaDeudores.appendChild(tr);
                });
            });
    }

    async function pagarDeuda(nombre, monto) {
        if (!(await Notificador.confirm(`¿Cobrar $${monto} a ${nombre}?`, 'Se registrará el ingreso en caja.'))) return;
        
        const formData = new FormData();
        formData.append('accion', 'pagar_fiado');
        formData.append('nombre_fiado', nombre);
        formData.append('monto_pagado', monto);

        fetch('api/api_ventas.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                Notificador.success('Deuda Saldada');
                cargarDeudores();
                moduloRegistros.cargarRegistros();
            } else {
                Notificador.error('Error', data.message);
            }
        });
    }

    function buscarProductos() {
        if(!buscarInput) return;
        const query = buscarInput.value.toLowerCase();
        resultadosDiv.innerHTML = '';
        
        if (query.length < 2) {
            resultadosDiv.style.display = 'none';
            return;
        }
        
        const filtrados = productosDB.filter(p => p.nombre.toLowerCase().includes(query) || (p.codigo_barras && p.codigo_barras.includes(query)));
        
        if (filtrados.length > 0) {
            resultadosDiv.style.display = 'block';
            filtrados.forEach(p => {
                const item = document.createElement('div');
                item.className = 'resultado-item';
                item.innerHTML = `<strong>${p.nombre}</strong> - $${p.precio_venta} <br><small>Stock: ${p.stock}</small>`;
                item.onclick = () => seleccionarProducto(p);
                resultadosDiv.appendChild(item);
            });
        } else {
            resultadosDiv.style.display = 'none';
        }
    }

    function seleccionarProducto(producto) {
        productoSeleccionado = producto;
        productoSelDiv.innerHTML = `
            <div style="background:#e3f2fd; padding:10px; border-radius:5px; border-left:4px solid #2196f3;">
                <strong>${producto.nombre}</strong><br>
                Precio: $${producto.precio_venta} | Disponible: ${producto.stock}
            </div>`;
        resultadosDiv.innerHTML = '';
        resultadosDiv.style.display = 'none';
        buscarInput.value = '';
        cantidadInput.value = 1;
        cantidadInput.max = producto.stock;
        cantidadInput.focus();
    }

    function agregarAlCarrito() {
        if (!productoSeleccionado) { Notificador.error('Selecciona un producto primero.'); return; }
        
        const cantidad = parseInt(cantidadInput.value);
        if (isNaN(cantidad) || cantidad <= 0) { Notificador.error('Cantidad inválida.'); return; }
        if (cantidad > productoSeleccionado.stock) { Notificador.error('Stock insuficiente', `Solo quedan ${productoSeleccionado.stock}`); return; }

        // Buscar si ya existe en carrito
        const itemExistente = carrito.find(item => item.id === productoSeleccionado.id);
        
        if (itemExistente) {
            if ((itemExistente.cantidad + cantidad) > productoSeleccionado.stock) {
                Notificador.error('Límite de stock alcanzado en carrito.');
                return;
            }
            itemExistente.cantidad += cantidad;
        } else {
            carrito.push({
                id: productoSeleccionado.id,
                nombre: productoSeleccionado.nombre,
                precio: parseFloat(productoSeleccionado.precio_venta),
                cantidad: cantidad,
                foto: productoSeleccionado.foto_url
            });
        }
        
        actualizarCarrito();
        // Limpiar selección
        productoSeleccionado = null;
        productoSelDiv.innerHTML = '';
        buscarInput.focus();
    }

    function actualizarCarrito() {
        carritoLista.innerHTML = '';
        let total = 0;
        
        if (carrito.length === 0) {
            carritoLista.innerHTML = '<li style="text-align:center; color:#888; padding:20px;">Carrito vacío</li>';
            carritoTotalEl.textContent = 'Total: $0.00';
            return;
        }

        carrito.forEach((item, index) => {
            const li = document.createElement('li');
            
            // Contenedor Imagen + Texto
            const divInfo = document.createElement('div');
            divInfo.style.display = 'flex';
            divInfo.style.alignItems = 'center';

            const img = document.createElement('img');
            img.src = item.foto || PLACEHOLDER_IMG;
            img.className = 'carrito-item-img';
            img.onerror = () => { img.src = PLACEHOLDER_IMG; };
            
            divInfo.append(img, safeEl('span', `${item.cantidad}x ${item.nombre}`, 'carrito-item-info'));

            // Contenedor Precio + Botón
            const divPrecio = document.createElement('div');
            divPrecio.style.display = 'flex';
            divPrecio.style.alignItems = 'center';
            divPrecio.style.gap = '10px';

            const precio = safeEl('span', `$${(item.precio * item.cantidad).toFixed(2)}`, 'carrito-item-precio');
            const btnQuitar = document.createElement('button');
            btnQuitar.className = 'btn-quitar-item';
            btnQuitar.innerHTML = '<i class="fas fa-trash-alt"></i>';
            btnQuitar.onclick = () => { carrito.splice(index, 1); actualizarCarrito(); };
            
            divPrecio.append(precio, btnQuitar);
            li.append(divInfo, divPrecio);
            carritoLista.appendChild(li);
            total += item.precio * item.cantidad;
        });
        
        carritoTotalEl.textContent = `Total: $${total.toFixed(2)}`;
    }

    async function finalizarVenta() {
        if (carrito.length === 0) { Notificador.error('El carrito está vacío.'); return; }
        
        const tipoPago = tipoPagoSelect.value;
        const nombreFiado = nombreFiadoInput.value.trim();

        if (tipoPago === 'fiado' && nombreFiado === '') {
            Notificador.error('Falta el nombre', 'Debes escribir a quién se le fía.');
            nombreFiadoInput.focus();
            return;
        }

        // Confirmación de pago
        if (tipoPago === 'pagado') {
            const total = carrito.reduce((acc, item) => acc + (item.precio * item.cantidad), 0);
            const pagoClienteStr = await Notificador.prompt(`Total a pagar: $${total.toFixed(2)}`, '¿Con cuánto paga el cliente?', total.toFixed(2));
            if (!pagoClienteStr) return; 
            
            const pagoCliente = parseFloat(pagoClienteStr);
            if (isNaN(pagoCliente) || pagoCliente < total) {
                Notificador.error('Monto insuficiente.'); return;
            }
            
            await Swal.fire({ 
                title: '¡Cobro Exitoso!', 
                html: `<h2 style='color:green'>Cambio: $${(pagoCliente - total).toFixed(2)}</h2>`, 
                icon: 'success',
                timer: 4000
            });
        } else {
            if (!(await Notificador.confirm(`¿Fiado a ${nombreFiado}?`))) return;
        }

        // Enviar al backend
        fetch('api/api_ventas.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ carrito: carrito, tipo_pago: tipoPago, nombre_fiado: nombreFiado })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if(tipoPago === 'fiado') Notificador.success('Fiado Registrado');
                
                // Limpiar todo
                carrito = [];
                actualizarCarrito();
                tipoPagoSelect.value = 'pagado';
                nombreFiadoInput.value = '';
                nombreFiadoGroup.style.display = 'none';
                
                // Recargar datos
                cargarProductosVenta();
                cargarDeudores(); 
                moduloRegistros.cargarRegistros();
            } else {
                Notificador.error('Error al registrar', data.message);
            }
        });
    }

    return {
        init: () => {
            if(buscarInput) {
                cargarProductosVenta();
                cargarDeudores(); 
                buscarInput.addEventListener('keyup', buscarProductos);
                agregarBtn.addEventListener('click', agregarAlCarrito);
                cancelarBtn.addEventListener('click', () => { carrito = []; actualizarCarrito(); });
                finalizarBtn.addEventListener('click', finalizarVenta);
                tipoPagoSelect.addEventListener('change', () => {
                    nombreFiadoGroup.style.display = (tipoPagoSelect.value === 'fiado') ? 'block' : 'none';
                });
            }
        },
        cargarDeudores: cargarDeudores
    };
})();

// --- MÓDULO: INVENTARIO ---
const moduloInventario = (function() {
    const btnMostrarForm = document.getElementById('btn-mostrar-form-producto');
    const formProducto = document.getElementById('form-producto');
    const cuerpoTablaInventario = document.getElementById('cuerpo-tabla-inventario');
    const productoIdInput = document.getElementById('producto-id');
    let productosDB = []; 

    function cargarInventario() {
        if(!cuerpoTablaInventario) return;
        fetch('api/api_inventario.php?accion=listar')
            .then(res => res.json())
            .then(data => {
                if(!data.success) return;
                productosDB = data.productos; 
                cuerpoTablaInventario.innerHTML = '';
                data.productos.forEach(p => {
                    const tr = document.createElement('tr');
                    
                    // Imagen con fallback
                    const img = document.createElement('img');
                    img.src = (p.foto_url && p.foto_url.length > 5) ? p.foto_url : PLACEHOLDER_IMG;
                    img.className = 'foto-preview';
                    img.onerror = () => { img.src = PLACEHOLDER_IMG; };

                    const tdAcciones = document.createElement('td');
                    tdAcciones.className = 'admin-only';
                    
                    // Botón Editar
                    const btnEditar = document.createElement('button');
                    btnEditar.className = 'btn-accion-tabla btn-editar';
                    btnEditar.innerHTML = '<i class="fas fa-edit"></i>';
                    btnEditar.onclick = () => editarProducto(p);
                    
                    // Botón Eliminar
                    const btnEliminar = document.createElement('button');
                    btnEliminar.className = 'btn-accion-tabla btn-eliminar';
                    btnEliminar.innerHTML = '<i class="fas fa-trash"></i>';
                    btnEliminar.onclick = () => eliminarProducto(p.id);
                    
                    tdAcciones.append(btnEditar, btnEliminar);

                    const tdFoto = document.createElement('td');
                    tdFoto.appendChild(img);
                    
                    tr.append(tdFoto, safeEl('td', p.nombre), safeEl('td', p.codigo_barras), safeEl('td', `$${p.precio_venta}`), safeEl('td', p.stock), tdAcciones);
                    cuerpoTablaInventario.appendChild(tr);
                });
            });
    }

    function guardarProducto(e) {
        e.preventDefault();
        const formData = new FormData(formProducto);
        formData.append('accion', productoIdInput.value ? 'editar' : 'crear');
        
        fetch('api/api_inventario.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Notificador.success('Producto guardado.');
                productoIdInput.value = ''; 
                formProducto.reset(); 
                formProducto.style.display = 'none';
                cargarInventario();
            } else {
                Notificador.error('Error', data.message);
            }
        });
    }
    
    function editarProducto(p) {
        productoIdInput.value = p.id;
        document.getElementById('producto-nombre').value = p.nombre;
        document.getElementById('producto-codigo').value = p.codigo_barras || '';
        document.getElementById('producto-precio').value = p.precio_venta;
        document.getElementById('producto-stock').value = p.stock;
        document.getElementById('producto-foto').value = p.foto_url || '';
        formProducto.style.display = 'block';
        window.scrollTo(0, 0);
    }
    
    async function eliminarProducto(id) {
        if (await Notificador.confirm('¿Eliminar producto?', 'Si tiene ventas, se archivará.')) {
            const formData = new FormData();
            formData.append('accion', 'eliminar');
            formData.append('id', id);
            fetch('api/api_inventario.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.success) { 
                        Notificador.success(data.message); // Mensaje dinámico (Eliminado o Archivado)
                        cargarInventario(); 
                    } else { 
                        Notificador.error('Error', data.message); 
                    }
                });
        }
    }

    return {
        init: () => {
            if(btnMostrarForm){
                btnMostrarForm.addEventListener('click', () => {
                    formProducto.style.display = (formProducto.style.display === 'none') ? 'block' : 'none';
                    productoIdInput.value = ''; formProducto.reset();
                });
                formProducto.addEventListener('submit', guardarProducto);
            }
        },
        cargarInventario, 
        setProductosDB: (data) => { productosDB = data; }
    };
})();

// --- MÓDULO: REGISTROS ---
const moduloRegistros = (function() {
    const formRegistro = document.getElementById('form-registro');
    const cuerpoTablaRegistros = document.getElementById('cuerpo-tabla-registros');
    const cuerpoTablaVentas = document.getElementById('cuerpo-tabla-ventas'); 
    
    function cargarRegistrosManuales() {
        if(!cuerpoTablaRegistros) return;
        fetch('api/api_registros.php?accion=listar')
            .then(res => res.json())
            .then(data => {
                if(!data.success) return;
                cuerpoTablaRegistros.innerHTML = '';
                data.registros.forEach(r => {
                    const tr = document.createElement('tr');
                    const btnEliminar = document.createElement('button');
                    btnEliminar.className = 'btn-accion-tabla btn-eliminar';
                    btnEliminar.innerHTML = '<i class="fas fa-trash"></i>';
                    btnEliminar.onclick = () => eliminarRegistro(r.id);
                    
                    const tdAccion = document.createElement('td');
                    tdAccion.className = 'admin-only'; 
                    tdAccion.appendChild(btnEliminar);
                    
                    tr.append(safeEl('td', r.fecha), safeEl('td', r.tipo), safeEl('td', r.concepto), safeEl('td', `$${r.monto}`), safeEl('td', r.usuario), tdAccion);
                    cuerpoTablaRegistros.appendChild(tr);
                });
            });
    }

    function cargarHistorialVentas() {
        if(!cuerpoTablaVentas) return;
        fetch('api/api_ventas.php?accion=listar_ventas')
            .then(res => res.json())
            .then(data => {
                if(!data.success) return;
                cuerpoTablaVentas.innerHTML = '';
                data.ventas.forEach(v => {
                    const tr = document.createElement('tr');
                    if(v.tipo_pago === 'fiado') {
                        tr.style.backgroundColor = '#fff3e0'; // Color naranja claro para fiados
                        if(v.fiado_pagado == 1) tr.style.textDecoration = 'line-through';
                    }
                    
                    const btnEliminar = document.createElement('button');
                    btnEliminar.className = 'btn-accion-tabla btn-eliminar';
                    btnEliminar.innerHTML = '<i class="fas fa-undo"></i>';
                    btnEliminar.onclick = () => eliminarVenta(v.id);
                    
                    const tdAccion = document.createElement('td');
                    tdAccion.className = 'admin-only'; 
                    tdAccion.appendChild(btnEliminar);
                    
                    tr.append(safeEl('td', v.fecha), safeEl('td', v.vendedor), safeEl('td', v.producto_nombre || '(Eliminado)'), safeEl('td', v.cantidad), safeEl('td', `$${v.total}`), safeEl('td', v.tipo_pago), safeEl('td', v.nombre_fiado || '-'), tdAccion);
                    cuerpoTablaVentas.appendChild(tr);
                });
            });
    }

    function guardarRegistro(e) {
        e.preventDefault();
        const formData = new FormData(formRegistro);
        formData.append('accion', 'crear');
        fetch('api/api_registros.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) { Notificador.success('Movimiento registrado'); formRegistro.reset(); cargarRegistrosManuales(); }
            else { Notificador.error('Error', data.message); }
        });
    }
    
    async function eliminarRegistro(id) {
        if (await Notificador.confirm('¿Eliminar registro?', 'Esto afectará el corte de caja.')) {
            const formData = new FormData(); formData.append('accion', 'eliminar'); formData.append('id', id);
            fetch('api/api_registros.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(d => { if(d.success) { Notificador.success('Eliminado'); cargarRegistrosManuales(); } });
        }
    }
    
    async function eliminarVenta(id) {
        if (await Notificador.confirm('¿Devolución de Venta?', 'El stock será restaurado.')) {
            const formData = new FormData(); formData.append('accion', 'eliminar_venta'); formData.append('id', id);
            fetch('api/api_ventas.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(d => { if(d.success) { Notificador.success('Venta anulada'); cargarHistorialVentas(); } else { Notificador.error('Error', d.message); } });
        }
    }
    
    return {
        init: () => { if(formRegistro) formRegistro.addEventListener('submit', guardarRegistro); },
        cargarRegistros: () => { cargarRegistrosManuales(); cargarHistorialVentas(); }
    };
})();

// --- MÓDULO: SÉPTIMAS ---
const moduloSeptimas = (function() {
    const form = document.getElementById('form-septima');
    const tabla = document.getElementById('cuerpo-tabla-septimas');
    const idInput = document.getElementById('septima-id');
    const nombreInput = document.getElementById('septima-nombre');
    const montoInput = document.getElementById('septima-monto');

    function cargar() {
        if(!tabla) return;
        fetch('api/api_septimas.php?accion=listar').then(r => r.json()).then(d => {
            if(!d.success) return;
            tabla.innerHTML = '';
            d.septimas.forEach(s => {
                const tr = document.createElement('tr');
                const tdAcciones = document.createElement('td');
                
                if(s.pagado == 0) {
                    const btnPagar = document.createElement('button');
                    btnPagar.className = 'btn-accion-tabla btn-success'; 
                    btnPagar.innerHTML = '<i class="fas fa-check"></i>';
                    btnPagar.onclick = () => pagar(s.id); 
                    tdAcciones.appendChild(btnPagar);
                } else {
                    tr.style.opacity = '0.6'; // Visualmente indicar pagado
                    tr.style.backgroundColor = '#e8f5e9';
                }
                
                const btnDel = document.createElement('button'); 
                btnDel.className = 'btn-accion-tabla btn-eliminar'; 
                btnDel.innerHTML = '<i class="fas fa-trash"></i>';
                btnDel.onclick = () => eliminar(s.id);
                
                tdAcciones.appendChild(btnDel);
                
                tr.append(safeEl('td', s.fecha), safeEl('td', s.nombre_padrino), safeEl('td', `$${s.monto}`), safeEl('td', s.usuario_registro), safeEl('td', s.pagado == 1 ? 'Pagado' : 'Pendiente'), tdAcciones);
                tabla.appendChild(tr);
            });
        });
    }

    function guardar(e) {
        e.preventDefault();
        const formData = new FormData();
        formData.append('accion', 'crear');
        formData.append('nombre', nombreInput.value);
        formData.append('monto', montoInput.value);
        
        fetch('api/api_septimas.php', { method: 'POST', body: formData }).then(r => r.json()).then(d => {
            if(d.success) { Notificador.success('Séptima registrada'); form.reset(); cargar(); }
            else Notificador.error('Error', d.message);
        });
    }
    async function pagar(id) {
        if(await Notificador.confirm('¿Marcar como PAGADA?')) {
            const fd = new FormData(); fd.append('accion', 'pagar'); fd.append('id', id);
            fetch('api/api_septimas.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{ if(d.success) cargar(); });
        }
    }
    async function eliminar(id) {
        if(await Notificador.confirm('¿Borrar registro?')) {
            const fd = new FormData(); fd.append('accion', 'eliminar'); fd.append('id', id);
            fetch('api/api_septimas.php', {method:'POST', body:fd}).then(r=>r.json()).then(d=>{ if(d.success) cargar(); });
        }
    }
    return { init: () => { if(form) form.addEventListener('submit', guardar); }, cargar };
})();

// --- MÓDULO: USUARIOS ---
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
                
                const tdAccion = document.createElement('td');
                tdAccion.appendChild(btnEliminar);

                tr.append(safeEl('td', u.id), safeEl('td', u.username), safeEl('td', u.role.toUpperCase()), tdAccion);
                tabla.appendChild(tr);
            });
        });
    }

    function crear(e) {
        e.preventDefault();
        const user = document.getElementById('nuevo-usuario-user').value;
        const pass = document.getElementById('nuevo-usuario-pass').value;
        
        const formData = new FormData();
        formData.append('accion', 'crear'); 
        formData.append('username', user); 
        formData.append('password', pass); 
        
        fetch('api/api_usuarios.php', { method: 'POST', body: formData }).then(res => res.json()).then(data => {
            if(data.success) { Notificador.success('Administrador creado.'); form.reset(); cargar(); } 
            else { Notificador.error('Error', data.message); }
        });
    }

    async function eliminar(id, nombre) {
        if(await Notificador.confirm(`¿Eliminar usuario ${nombre}?`)) {
            const fd = new FormData(); fd.append('accion', 'eliminar'); fd.append('id', id);
            fetch('api/api_usuarios.php', { method: 'POST', body: fd }).then(res => res.json()).then(data => {
                if(data.success) { Notificador.success('Usuario eliminado.'); cargar(); } 
                else { Notificador.error('Error', data.message); }
            });
        }
    }
    return { init: () => { if(form) form.addEventListener('submit', crear); }, cargar };
})();

// --- MÓDULO: ADMIN (Configuración y Logs) ---
const moduloAdmin = (function() {
    function cargarErrores() { 
        const tabla = document.getElementById('cuerpo-tabla-errores'); 
        if (!tabla) return; 
        fetch('api/api_admin.php?accion=ver_errores').then(r => r.json()).then(d => { 
            if (d.success) { 
                tabla.innerHTML = ''; 
                d.errores.forEach(e => { 
                    const tr = document.createElement('tr'); 
                    tr.append(safeEl('td', e.fecha), safeEl('td', e.error)); 
                    tabla.appendChild(tr); 
                }); 
            } 
        }); 
    }
    
    function inicializarConfig() { 
        const pk = document.getElementById('color-picker'); 
        const root = document.documentElement;

        // Cargar color al inicio
        fetch('api/api_admin.php?accion=get_color').then(r => r.json()).then(d => {
            if(d.success && d.color) {
                root.style.setProperty('--color-primario', d.color);
                if(pk) pk.value = d.color;
            }
        });

        // Guardar color al cambiar
        if(pk) {
            pk.addEventListener('change', e => {
                const nuevoColor = e.target.value;
                root.style.setProperty('--color-primario', nuevoColor);
                const fd = new FormData();
                fd.append('accion', 'save_color');
                fd.append('color', nuevoColor);
                fetch('api/api_admin.php', { method: 'POST', body: fd });
            });
        }
    }
    
    // Inicialización de botones de reporte y respaldo
    function inicializarBotonesAdmin() {
        const btnInv = document.getElementById('btn-reporte-inventario');
        const btnCons = document.getElementById('btn-reporte-consolidado');
        const btnResp = document.getElementById('btn-respaldo-db');
        
        if(btnInv) btnInv.onclick = () => { window.location.href = 'api/api_reportes.php?reporte=inventario_hoy'; };
        if(btnCons) btnCons.onclick = () => { 
            Notificador.success('Generando Reporte...', 'Espera un momento');
            window.location.href = 'api/api_reportes.php?reporte=consolidado'; 
        };
        if(btnResp) btnResp.onclick = () => { window.location.href = 'api/api_respaldo.php'; };
    }
    
    return { 
        init: () => { 
            const br = document.getElementById('btn-recargar-errores'); 
            if(br) br.onclick = cargarErrores; 
            inicializarConfig(); 
            inicializarBotonesAdmin(); 
        }, 
        cargarErrores 
    };
})();

// --- INICIALIZACIÓN GLOBAL ---
document.addEventListener('DOMContentLoaded', () => {
    inicializarReloj(); 
    inicializarNavegacion();
    moduloVentas.init(); 
    moduloInventario.init(); 
    moduloRegistros.init(); 
    moduloSeptimas.init(); 
    moduloUsuarios.init();
    moduloAdmin.init();
});

// Limpieza visual al regresar
window.addEventListener('pageshow', () => { 
    document.body.classList.remove('swal2-shown', 'swal2-height-auto'); 
    document.body.style.paddingRight = ''; 
});