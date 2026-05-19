<footer class="footer mt-auto py-3 bg-white border-top">
    <div class="container text-center">
        <span class="text-muted small">Marginación Digital &copy; <?php echo date('Y'); ?> - Sistema de Gestión de Marginaciones</span>
    </div>
</footer>

<!-- jQuery PRIMERO -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Select2 CSS y JS (global para todo el sistema) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/es.js"></script>

<script>
// Inicialización global de Select2 para todo el sistema
$(document).ready(function() {
    
    // Para selects normales (solo selección)
    function inicializarSelect2Normal() {
        $('select.select2-init').each(function() {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                $(this).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: $(this).data('placeholder') || $(this).attr('placeholder') || '-- Seleccione --',
                    language: 'es',
                    allowClear: !$(this).prop('required')
                });
            }
        });
    }
    
    // Para selects con TAGS (permiten escribir nuevos valores)
    function inicializarSelect2Tags() {
        $('select.select2-tag').each(function() {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                $(this).select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: $(this).data('placeholder') || 'Escriba o seleccione...',
                    language: 'es',
                    tags: true,
                    allowClear: true,
                    createTag: function(params) {
                        var term = $.trim(params.term);
                        if (term === '') {
                            return null;
                        }
                        return {
                            id: term,
                            text: term + ' (nuevo)',
                            newTag: true
                        };
                    },
                    templateResult: function(data) {
                        if (data.newTag) {
                            return $('<span class="text-success"><i class="bi bi-plus-circle-fill"></i> Crear: ' + data.text.replace(' (nuevo)', '') + '</span>');
                        }
                        return data.text;
                    },
                    templateSelection: function(data) {
                        return data.text.replace(' (nuevo)', '');
                    }
                });
            }
        });
    }
    
    // Ejecutar todas las inicializaciones
    inicializarSelect2Normal();
    inicializarSelect2Tags();
    
    // Observador para selects agregados dinámicamente
    const observer = new MutationObserver(function(mutations) {
        let necesitaInit = false;
        mutations.forEach(function(mutation) {
            if (mutation.addedNodes.length) {
                mutation.addedNodes.forEach(function(node) {
                    if ($(node).find('select.select2-init, select.select2-tag').length || 
                        ($(node).is && ($(node).is('select.select2-init') || $(node).is('select.select2-tag')))) {
                        necesitaInit = true;
                    }
                });
            }
        });
        if (necesitaInit) {
            setTimeout(function() {
                inicializarSelect2Normal();
                inicializarSelect2Tags();
            }, 100);
        }
    });
    
    observer.observe(document.body, { childList: true, subtree: true });
});
</script>

</body>
</html>