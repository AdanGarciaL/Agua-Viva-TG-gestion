/**
 * download-handler.js - Maneja descargas y agrega botón de retorno
 * Detecta cuando se inicializa una descarga y muestra notificación con botón de retorno
 */

(function() {
    // Flag para evitar múltiples notificaciones
    let notificationTimeout = null;
    
    // NOTA: Este archivo ya no es necesario con el nuevo método de descarga
    // pero se mantiene por compatibilidad con descargas CSV/JSON
    
    // Interceptar fetch para descargas de CSV/JSON
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        const url = args[0];
        if (url && typeof url === 'string' && url.includes('api_reportes') && 
            (url.includes('exportar') || url.includes('formato'))) {
            // Solo notificar para CSV/JSON, Excel se maneja en app.js
            if (url.includes('csv') || url.includes('json')) {
                showDownloadNotification('Descargando reporte...');
            }
        }
        return originalFetch.apply(this, args);
    };
    
    function handleDownloadInitiated(url) {
        // Mostrar notificación inicial
        showDownloadNotification('📥 Iniciando descarga...', true);
        
        // Detectar cuando la descarga se complete (por cambio del documento)
        const startTime = Date.now();
        const checkInterval = setInterval(() => {
            // Después de 2 segundos, mostrar opción de retorno
            if (Date.now() - startTime > 1500) {
                clearInterval(checkInterval);
                showDownloadNotification('✅ Descarga completada en tu carpeta de Descargas', false);
                
                // Auto-cerrar después de 6 segundos
                setTimeout(() => {
                    closeNotification();
                }, 6000);
            }
        }, 100);
        
        // Crear invisible iframe para la descarga
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.name = 'downloadFrame' + Date.now();
        iframe.onload = function() {
            clearInterval(checkInterval);
            showDownloadNotification('✅ Descarga completada en tu carpeta de Descargas', false);
            
            setTimeout(() => {
                closeNotification();
            }, 5000);
        };
        
        document.body.appendChild(iframe);
        iframe.src = url;
    }
    
    function showDownloadNotification(message, isLoading = false) {
        // Limpiar timeout anterior
        if (notificationTimeout) clearTimeout(notificationTimeout);
        
        // Remover notificación anterior si existe
        const existing = document.getElementById('downloadNotifContainer');
        if (existing) existing.parentNode.removeChild(existing);
        
        // Crear contenedor
        const container = document.createElement('div');
        container.id = 'downloadNotifContainer';
        container.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 10000;
            max-width: 350px;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        `;
        
        // Crear notificación
        const notif = document.createElement('div');
        notif.style.cssText = `
            background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
            color: white;
            padding: 16px 20px;
            border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease-out;
        `;
        
        if (isLoading) {
            notif.innerHTML = `
                <div style="
                    display: inline-block;
                    width: 16px;
                    height: 16px;
                    border: 2px solid rgba(255,255,255,0.3);
                    border-top: 2px solid white;
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                "></div>
                <span style="flex: 1;">${message}</span>
            `;
        } else {
            notif.innerHTML = `
                <span style="flex: 1;">${message}</span>
                <button onclick="document.getElementById('downloadNotifContainer').style.display='none'" style="
                    background: transparent;
                    border: none;
                    color: white;
                    cursor: pointer;
                    font-size: 18px;
                    padding: 0;
                    margin: 0;
                    line-height: 1;
                " title="Cerrar">✕</button>
            `;
        }
        
        // Agregar estilos de animación
        if (!document.getElementById('downloadHandlerStyles')) {
            const style = document.createElement('style');
            style.id = 'downloadHandlerStyles';
            style.textContent = `
                @keyframes slideIn {
                    from {
                        transform: translateX(400px);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                @keyframes spin {
                    to { transform: rotate(360deg); }
                }
            `;
            document.head.appendChild(style);
        }
        
        container.appendChild(notif);
        document.body.appendChild(container);
    }
    
    function closeNotification() {
        const container = document.getElementById('downloadNotifContainer');
        if (container) {
            container.style.animation = 'slideIn 0.3s ease-out reverse';
            setTimeout(() => {
                if (container.parentNode) container.parentNode.removeChild(container);
            }, 300);
        }
        
        // Auto-remover después de 3 segundos
        setTimeout(() => {
            if (notif.parentNode) {
                notif.parentNode.removeChild(notif);
            }
        }, 3000);
    }
})();
