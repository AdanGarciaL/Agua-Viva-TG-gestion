// login.js
document.addEventListener('DOMContentLoaded', () => {

    const btnModoAdmin = document.getElementById('btn-modo-admin');
    const btnModoVendedor = document.getElementById('btn-modo-vendedor');
    const formAdmin = document.getElementById('loginFormAdmin');
    const formVendedor = document.getElementById('loginFormVendedor');
    const errorAdmin = document.getElementById('login-error-admin');
    const errorVendedor = document.getElementById('login-error-vendedor');
    
    // --- (1) CAMBIO: Referencia al slider ---
    const slider = document.querySelector('.login-modo-selector .slider');

    // Cambiar a modo Admin
    btnModoAdmin.addEventListener('click', () => {
        formAdmin.classList.add('active');
        formVendedor.classList.remove('active');
        btnModoAdmin.classList.add('active');
        btnModoVendedor.classList.remove('active');
        errorVendedor.textContent = '';
        
        // --- (2) CAMBIO: Mover slider ---
        if (slider) slider.style.transform = 'translateX(0)';
    });

    // Cambiar a modo Vendedor
    btnModoVendedor.addEventListener('click', () => {
        formVendedor.classList.add('active');
        formAdmin.classList.remove('active');
        btnModoVendedor.classList.add('active');
        btnModoAdmin.classList.remove('active');
        errorAdmin.textContent = '';
        
        // --- (3) CAMBIO: Mover slider ---
        if (slider) slider.style.transform = 'translateX(100%)';
    });


    // Enviar formulario Admin/Superadmin
    formAdmin.addEventListener('submit', function(e) {
        e.preventDefault(); 
        const formData = new FormData(this);
        formData.append('modo', 'admin'); // Identificar el modo

        fetch('api/api_login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'dashboard.php';
            } else {
                errorAdmin.textContent = data.message;
                errorAdmin.classList.add('shake');
                setTimeout(() => errorAdmin.classList.remove('shake'), 500);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            errorAdmin.textContent = 'Ocurrió un error de red.';
        });
    });

    // Enviar formulario Vendedor
    formVendedor.addEventListener('submit', function(e) {
        e.preventDefault(); 
        const formData = new FormData(this);
        formData.append('modo', 'vendedor'); // Identificar el modo

        fetch('api/api_login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.href = 'dashboard.php';
            } else {
                errorVendedor.textContent = data.message;
                errorVendedor.classList.add('shake');
                setTimeout(() => errorVendedor.classList.remove('shake'), 500);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            errorVendedor.textContent = 'Ocurrió un error de red.';
        });
    });
});