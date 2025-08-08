/**
 * JavaScript para ZfcDatagrid con Bootstrap 5
 * Mejoras de responsividad y funcionalidades avanzadas
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ========== CONFIGURACIÓN INICIAL ==========
    
    const BREAKPOINTS = {
        xs: 0,
        sm: 576,
        md: 768,
        lg: 992,
        xl: 1200,
        xxl: 1400
    };
    
    let currentBreakpoint = getCurrentBreakpoint();
    
    // ========== FUNCIONES UTILITARIAS ==========
    
    function getCurrentBreakpoint() {
        const width = window.innerWidth;
        if (width >= BREAKPOINTS.xxl) return 'xxl';
        if (width >= BREAKPOINTS.xl) return 'xl';
        if (width >= BREAKPOINTS.lg) return 'lg';
        if (width >= BREAKPOINTS.md) return 'md';
        if (width >= BREAKPOINTS.sm) return 'sm';
        return 'xs';
    }
    
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // ========== RESPONSIVIDAD AVANZADA ==========
    
    function handleResponsiveTable() {
        const tables = document.querySelectorAll('.zfcDatagrid table');
        const breakpoint = getCurrentBreakpoint();
        
        tables.forEach(table => {
            // Agregar wrapper responsive si no existe
            if (!table.closest('.table-responsive')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'table-responsive';
                table.parentNode.insertBefore(wrapper, table);
                wrapper.appendChild(table);
            }
            
            // Ajustar clases según breakpoint
            const wrapper = table.closest('.table-responsive');
            wrapper.className = 'table-responsive';
            
            if (breakpoint === 'xs' || breakpoint === 'sm') {
                wrapper.classList.add('table-responsive-sm');
                table.classList.add('table-sm');
            } else {
                table.classList.remove('table-sm');
            }
            
            // Ocultar/mostrar columnas según breakpoint
            handleColumnVisibility(table, breakpoint);
        });
    }
    
    function handleColumnVisibility(table, breakpoint) {
        const headers = table.querySelectorAll('thead th');
        const rows = table.querySelectorAll('tbody tr');
        
        headers.forEach((header, index) => {
            const isActionColumn = header.textContent.trim().toLowerCase().includes('accion');
            const isIdColumn = index === 0;
            const isNameColumn = index === 1 || index === 2;
            
            let shouldShow = true;
            
            // Lógica de visibilidad por breakpoint
            if (breakpoint === 'xs') {
                shouldShow = isActionColumn || isNameColumn;
            } else if (breakpoint === 'sm') {
                shouldShow = !header.textContent.trim().toLowerCase().includes('fecha') || isActionColumn || isNameColumn;
            }
            
            // Aplicar visibilidad
            if (shouldShow) {
                header.classList.remove('d-none');
                rows.forEach(row => {
                    const cell = row.children[index];
                    if (cell) cell.classList.remove('d-none');
                });
            } else {
                header.classList.add('d-none');
                rows.forEach(row => {
                    const cell = row.children[index];
                    if (cell) cell.classList.add('d-none');
                });
            }
        });
    }
    
    // ========== MEJORAS DE BOTONES ==========
    
    function enhanceActionButtons() {
        const actionButtons = document.querySelectorAll('.zfcDatagrid .btn-action');
        const breakpoint = getCurrentBreakpoint();
        
        actionButtons.forEach(button => {
            // Agregar clases Bootstrap adicionales
            if (!button.classList.contains('shadow-sm')) {
                button.classList.add('shadow-sm');
            }
            
            // Ajustar tamaño según breakpoint
            if (breakpoint === 'xs') {
                button.classList.remove('btn-sm');
                button.classList.add('btn-xs');
                
                // Ocultar texto en móviles
                const spans = button.querySelectorAll('span');
                spans.forEach(span => {
                    if (!span.classList.contains('d-none')) {
                        span.classList.add('d-none', 'd-md-inline');
                    }
                });
            } else if (breakpoint === 'sm') {
                button.classList.add('btn-sm');
                button.classList.remove('btn-xs');
            }
            
            // Mejorar efectos hover
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    }
    
    // ========== LOADING STATES ==========
    
    function addLoadingState() {
        const datagridContainer = document.querySelector('.zfcDatagrid-container');
        if (datagridContainer && !datagridContainer.classList.contains('zfcDatagrid-loading')) {
            datagridContainer.classList.add('zfcDatagrid-loading');
            
            // Remover loading después de un tiempo
            setTimeout(() => {
                datagridContainer.classList.remove('zfcDatagrid-loading');
            }, 1000);
        }
    }
    
    // ========== MEJORAS DE FILTROS ==========
    
    function enhanceFilters() {
        const filterInputs = document.querySelectorAll('.zfcDatagrid .filter-row input, .zfcDatagrid .filter-row select');
        
        filterInputs.forEach(input => {
            // Agregar clases Bootstrap
            if (!input.classList.contains('form-control')) {
                input.classList.add('form-control', 'form-control-sm');
            }
            
            // Agregar placeholders útiles
            if (input.type === 'text' && !input.placeholder) {
                const columnHeader = input.closest('th');
                if (columnHeader) {
                    const headerText = columnHeader.textContent.trim();
                    input.placeholder = `Buscar ${headerText.toLowerCase()}...`;
                }
            }
            
            // Agregar eventos de validación visual
            input.addEventListener('input', function() {
                if (this.value.length > 0) {
                    this.classList.add('is-valid');
                    this.classList.remove('is-invalid');
                } else {
                    this.classList.remove('is-valid', 'is-invalid');
                }
            });
        });
    }
    
    // ========== MEJORAS DE PAGINACIÓN ==========
    
    function enhancePagination() {
        const paginationElements = document.querySelectorAll('.zfcDatagrid .pagination');
        
        paginationElements.forEach(pagination => {
            // Agregar wrapper Bootstrap
            if (!pagination.closest('.d-flex')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'd-flex justify-content-center align-items-center flex-wrap';
                pagination.parentNode.insertBefore(wrapper, pagination);
                wrapper.appendChild(pagination);
            }
            
            // Mejorar enlaces de paginación
            const pageLinks = pagination.querySelectorAll('a');
            pageLinks.forEach(link => {
                if (!link.classList.contains('page-link')) {
                    link.classList.add('page-link');
                    
                    // Crear wrapper li si no existe
                    if (!link.closest('li')) {
                        const li = document.createElement('li');
                        li.className = 'page-item';
                        link.parentNode.insertBefore(li, link);
                        li.appendChild(link);
                    }
                }
            });
        });
    }
    
    // ========== MEJORAS DE ACCESIBILIDAD ==========
    
    function enhanceAccessibility() {
        // Agregar roles ARIA
        const tables = document.querySelectorAll('.zfcDatagrid table');
        tables.forEach(table => {
            if (!table.getAttribute('role')) {
                table.setAttribute('role', 'table');
            }
            
            // Agregar caption si no existe
            if (!table.querySelector('caption')) {
                const caption = document.createElement('caption');
                caption.textContent = 'Tabla de usuarios del sistema';
                caption.className = 'visually-hidden';
                table.insertBefore(caption, table.firstChild);
            }
        });
        
        // Mejorar headers
        const headers = document.querySelectorAll('.zfcDatagrid th');
        headers.forEach(header => {
            if (!header.getAttribute('scope')) {
                header.setAttribute('scope', 'col');
            }
        });
        
        // Agregar skip links
        if (!document.querySelector('.skip-link')) {
            const skipLink = document.createElement('a');
            skipLink.href = '#main-table';
            skipLink.className = 'skip-link visually-hidden-focusable btn btn-primary';
            skipLink.textContent = 'Saltar a la tabla principal';
            document.body.insertBefore(skipLink, document.body.firstChild);
            
            const mainTable = document.querySelector('.zfcDatagrid table');
            if (mainTable) {
                mainTable.id = 'main-table';
            }
        }
    }
    
    // ========== ANIMACIONES Y TRANSICIONES ==========
    
    function addAnimations() {
        // Animación de entrada para filas
        const rows = document.querySelectorAll('.zfcDatagrid tbody tr');
        rows.forEach((row, index) => {
            row.style.opacity = '0';
            row.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '1';
                row.style.transform = 'translateY(0)';
            }, index * 50);
        });
    }
    
    // ========== EVENT LISTENERS ==========
    
    // Resize handler con debounce
    const handleResize = debounce(() => {
        const newBreakpoint = getCurrentBreakpoint();
        if (newBreakpoint !== currentBreakpoint) {
            currentBreakpoint = newBreakpoint;
            handleResponsiveTable();
            enhanceActionButtons();
        }
    }, 250);
    
    window.addEventListener('resize', handleResize);
    
    // Observer para cambios dinámicos
    const observer = new MutationObserver((mutations) => {
        let shouldUpdate = false;
        
        mutations.forEach(mutation => {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                shouldUpdate = true;
            }
        });
        
        if (shouldUpdate) {
            setTimeout(() => {
                handleResponsiveTable();
                enhanceActionButtons();
                enhanceFilters();
                enhancePagination();
                
                // Reinicializar tooltips
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    if (window.bootstrap && window.bootstrap.Tooltip) {
                        return new bootstrap.Tooltip(tooltipTriggerEl);
                    }
                });
            }, 100);
        }
    });
    
    // ========== INICIALIZACIÓN ==========
    
    function initialize() {
        console.log('Inicializando mejoras Bootstrap para ZfcDatagrid...');
        
        // Aplicar todas las mejoras
        handleResponsiveTable();
        enhanceActionButtons();
        enhanceFilters();
        enhancePagination();
        enhanceAccessibility();
        
        // Agregar loading state inicial
        addLoadingState();
        
        // Animaciones después de un breve delay
        setTimeout(addAnimations, 500);
        
        // Observar cambios en el contenedor del datagrid
        const datagridContainer = document.querySelector('.zfcDatagrid-container');
        if (datagridContainer) {
            observer.observe(datagridContainer, {
                childList: true,
                subtree: true
            });
        }
        
        console.log('Mejoras Bootstrap aplicadas correctamente.');
    }
    
    // Inicializar cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
    
    // Reinicializar después de un delay para asegurar que ZfcDatagrid haya terminado de renderizar
    setTimeout(initialize, 1000);
});

// ========== UTILIDADES GLOBALES ==========

// Función global para refrescar las mejoras (útil para llamadas AJAX)
window.refreshDatagridEnhancements = function() {
    if (window.bootstrap) {
        // Reinicializar tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Reaplicar mejoras
    const event = new CustomEvent('datagrid:refresh');
    document.dispatchEvent(event);
};

// Función para cambiar el tema (si se implementa dark mode)
window.toggleDatagridTheme = function(theme = 'light') {
    document.documentElement.setAttribute('data-bs-theme', theme);
    localStorage.setItem('datagrid-theme', theme);
};

// Cargar tema guardado
const savedTheme = localStorage.getItem('datagrid-theme');
if (savedTheme) {
    window.toggleDatagridTheme(savedTheme);
}
