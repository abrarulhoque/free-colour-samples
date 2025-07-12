jQuery(document).ready(function($) {
    console.log('[TIWSC Samples Page] Script loaded');
    
    // Load initial grid
    loadSamplesGrid();
    
    // Handle filter changes
    $('.tiwsc-filter-checkbox input').on('change', function() {
        console.log('[TIWSC] Filter changed');
        loadSamplesGrid();
    });
    
    // Clear filters
    $('.tiwsc-clear-filters').on('click', function() {
        console.log('[TIWSC] Clearing filters');
        $('.tiwsc-filter-checkbox input').prop('checked', false);
        loadSamplesGrid();
    });
    
    // Handle sample button clicks
    $(document).on('click', '.tiwsc-grid-sample-button', function(e) {
        e.preventDefault();
        var $button = $(this);
        var productId = $button.data('product-id');
        var attributeName = $button.data('attribute-name');
        var attributeValue = $button.data('attribute-value');
        var colorName = $button.data('color-name');
        
        console.log('[TIWSC] Grid sample button clicked', {
            productId: productId,
            attribute: attributeName,
            value: attributeValue
        });
        
        // Use the existing AJAX handler
        $.post(
            tiwsc_ajax.ajax_url,
            {
                action: 'tiwsc_toggle_sample',
                product_id: productId,
                attribute: attributeName,
                value: attributeValue
            },
            function(response) {
                console.log('[TIWSC] Toggle response:', response);
                
                if (response.not_allowed) {
                    alert('Deze functie is niet beschikbaar.');
                    return;
                }
                
                if (response.limit) {
                    alert(response.message || 'Je kunt maximaal 5 kleurstalen selecteren.');
                    return;
                }
                
                if (response.added) {
                    $button.addClass('tiwsc-added').html('Toegevoegd');
                    // Open sidebar
                    if (typeof openSidebar === 'function') {
                        openSidebar();
                    } else {
                        // Trigger click on sidebar open link if function not available
                        $('.tiwsc-open-sidebar-link').first().trigger('click');
                    }
                } else {
                    $button.removeClass('tiwsc-added').html('<span class="tiwsc-button-icon">+</span>Toevoegen');
                }
            },
            'json'
        ).fail(function(xhr, status, error) {
            console.error('[TIWSC] AJAX error:', status, error);
        });
    });
    
    // Function to load samples grid
    function loadSamplesGrid() {
        console.log('[TIWSC] Loading samples grid');
        
        // Get selected filters
        var filters = [];
        $('.tiwsc-filter-checkbox input:checked').each(function() {
            filters.push($(this).val());
        });
        
        console.log('[TIWSC] Active filters:', filters);
        
        // Show loading state
        $('#tiwsc-samples-grid').html(
            '<div class="tiwsc-loading">' +
                '<div class="tiwsc-spinner"></div>' +
                '<p>Kleurstalen laden...</p>' +
            '</div>'
        );
        
        // Make AJAX request
        $.post(
            tiwsc_samples_ajax.ajax_url,
            {
                action: 'tiwsc_get_samples_grid',
                filters: filters,
                nonce: tiwsc_samples_ajax.nonce
            },
            function(response) {
                console.log('[TIWSC] Grid loaded, count:', response.count);
                
                // Update grid
                $('#tiwsc-samples-grid').html(response.html);
                
                // Update count
                $('.tiwsc-count-number').text(response.count);
                
                // Animate grid items
                $('.tiwsc-grid-item').each(function(index) {
                    $(this).css({
                        opacity: 0,
                        transform: 'translateY(20px)'
                    }).delay(index * 30).animate({
                        opacity: 1
                    }, 300).css({
                        transform: 'translateY(0)'
                    });
                });
            }
        ).fail(function(xhr, status, error) {
            console.error('[TIWSC] Failed to load grid:', status, error);
            $('#tiwsc-samples-grid').html(
                '<div class="tiwsc-no-results">' +
                    '<p>Er is een fout opgetreden bij het laden van de kleurstalen.</p>' +
                '</div>'
            );
        });
    }
});