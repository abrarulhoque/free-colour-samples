jQuery(document).ready(function ($) {
  console.log('TIWSC Script loaded')
  console.log(
    'tiwsc_ajax:',
    typeof tiwsc_ajax !== 'undefined' ? tiwsc_ajax : 'undefined'
  )

  console.groupCollapsed('[TIWSC] Initial DOM scan')
  console.log('Free sample links found:', $('.tiwsc-free-sample-link').length)
  console.log(
    'Variable sample buttons found:',
    $('.tiwsc-variable-sample-button').length
  )
  console.log(
    'Product variation forms found:',
    $('form.variations_form').length
  )
  console.groupEnd()

  $('form.variations_form').on('found_variation', function (event, variation) {
    console.log('[TIWSC] found_variation event fired:', variation)
  })

  $('form.variations_form').on('reset_data', function () {
    console.log('[TIWSC] reset_data event fired')
  })

  // Check if sidebar elements exist
  console.log('Sidebar element exists:', $('#tiwsc-sidebar').length)
  console.log('Overlay element exists:', $('#tiwsc-sidebar-overlay').length)

  // Toggle sample functionality for simple products
  $(document).on('click', '.tiwsc-free-sample-link', function (e) {
    e.preventDefault()
    console.log('Sample link clicked')
    var $this = $(this)
    var productId = $this.data('product-id')
    console.log('Product ID:', productId)

    if (typeof tiwsc_ajax === 'undefined') {
      console.error('tiwsc_ajax is undefined')
      return
    }

    $.post(
      tiwsc_ajax.ajax_url,
      {
        action: 'tiwsc_toggle_sample',
        product_id: productId
      },
      function (response) {
        console.log('Toggle response:', response)
        if (response.not_allowed) {
          alert('Deze functie is niet beschikbaar.')
          return
        }

        if (response.limit) {
          alert(
            response.message || 'Je kunt maximaal 5 kleurstalen selecteren.'
          )
          return
        }

        // Update all instances of this product's button
        var $allButtons = $(
          '.tiwsc-free-sample-link[data-product-id="' + productId + '"]'
        )

        if (response.added) {
          $allButtons.addClass('tiwsc-added')
          $allButtons.find('.tiwsc-free-sample-text').text('Toegevoegd')
          // Update SVG to filled version
          $allButtons.find('svg path:first-child').attr('fill', '#88ae98')

          // Auto-open sidebar when sample is added
          openSidebar()
        } else {
          $allButtons.removeClass('tiwsc-added')
          $allButtons.find('.tiwsc-free-sample-text').text('Gratis Kleurstaal')
          // Update SVG to outline version
          $allButtons
            .find('svg path:first-child')
            .attr('fill', 'none')
            .attr('stroke', '#222')
        }
      },
      'json'
    ).fail(function (xhr, status, error) {
      console.error('AJAX error:', status, error)
    })
  })

  // Toggle sample functionality for variable products (color-specific)
  $(document).on('click', '.tiwsc-variable-sample-button', function (e) {
    e.preventDefault()
    console.log('Variable sample button clicked')
    var $this = $(this)
    var productId = $this.data('product-id')
    var attributeName = $this.data('attribute-name')
    var attributeValue = $this.data('attribute-value')
    var colorName = $this.data('color-name')
    console.log(
      'Product ID:',
      productId,
      'Attribute:',
      attributeName,
      'Value:',
      attributeValue
    )

    if (typeof tiwsc_ajax === 'undefined') {
      console.error('tiwsc_ajax is undefined')
      return
    }

    $.post(
      tiwsc_ajax.ajax_url,
      {
        action: 'tiwsc_toggle_sample',
        product_id: productId,
        attribute: attributeName,
        value: attributeValue
      },
      function (response) {
        console.log('Toggle response:', response)
        if (response.not_allowed) {
          alert('Deze functie is niet beschikbaar.')
          return
        }

        if (response.limit) {
          alert(
            response.message || 'Je kunt maximaal 5 kleurstalen selecteren.'
          )
          return
        }

        if (response.added) {
          $this.addClass('tiwsc-added')
          $this.find('.tiwsc-button-text').text('Toegevoegd')
          // Update SVG to filled version
          $this.find('svg path:first-child').attr('fill', '#88ae98')

          // Auto-open sidebar when sample is added
          openSidebar()
        } else {
          $this.removeClass('tiwsc-added')
          $this.find('.tiwsc-button-text').text(colorName)
          // Update SVG to outline version
          $this
            .find('svg path:first-child')
            .attr('fill', 'none')
            .attr('stroke', '#333')
        }
      },
      'json'
    ).fail(function (xhr, status, error) {
      console.error('AJAX error:', status, error)
    })
  })

  // Add after existing variable-sample-button logic
  $(document).on('click', '.tiwsc-variable-sample-main-button', function (e) {
    e.preventDefault()
    console.log('[TIWSC] Main variable sample button clicked')
    var $this = $(this)
    var productId = $this.data('product-id')
    var attributeName = $this.data('attribute-name') // e.g., pa_color

    // Find the closest variation form or fallback to global
    var $form = $this.closest('form.variations_form')
    if ($form.length === 0) {
      $form = $('form.variations_form')
    }
    var attributeSelector = '[name="attribute_' + attributeName + '"]'
    var attributeValue =
      $form.find(attributeSelector).val() || // select
      $form.find(attributeSelector + ':checked').val() || // radio
      $form
        .find(
          '[data-attribute_name="attribute_' +
            attributeName +
            '"] .selected, [data-attribute_name="attribute_' +
            attributeName +
            '"] .active'
        )
        .data('value') || // image swatch plugins
      '' // nothing picked

    console.log('[TIWSC] attribute:', attributeName, 'value:', attributeValue)

    if (!attributeValue) {
      alert('Selecteer eerst een kleur.')
      return
    }

    if (typeof tiwsc_ajax === 'undefined') {
      console.error('tiwsc_ajax is undefined')
      return
    }

    $.post(
      tiwsc_ajax.ajax_url,
      {
        action: 'tiwsc_toggle_sample',
        product_id: productId,
        attribute: attributeName,
        value: attributeValue
      },
      function (response) {
        console.log('[TIWSC] toggle response:', response)
        if (response.not_allowed) {
          alert('Deze functie is niet beschikbaar.')
          return
        }
        if (response.limit) {
          alert(
            response.message || 'Je kunt maximaal 5 kleurstalen selecteren.'
          )
          return
        }

        if (response.added) {
          $this.addClass('tiwsc-added')
          $this.find('.tiwsc-button-text').text('Toegevoegd')
          $this
            .find('svg path:first-child')
            .attr('fill', '#88ae98')
            .attr('stroke', '#88ae98')
          // Auto-open sidebar
          openSidebar()
        } else {
          $this.removeClass('tiwsc-added')
          $this.find('.tiwsc-button-text').text('Gratis Kleurstaal')
          $this
            .find('svg path:first-child')
            .attr('fill', 'none')
            .attr('stroke', '#333')
        }
      },
      'json'
    ).fail(function (xhr, status, error) {
      console.error('AJAX error:', status, error)
    })
  })

  // Open sidebar functionality
  $(document).on('click', '.tiwsc-open-sidebar-link', function (e) {
    e.preventDefault()
    console.log('Open sidebar link clicked')
    openSidebar()
  })

  // Close sidebar functionality
  $(document).on(
    'click',
    '#tiwsc-sidebar-close, #tiwsc-sidebar-overlay',
    function (e) {
      e.preventDefault()
      console.log('Close sidebar clicked')
      closeSidebar()
    }
  )

  // Remove sample from sidebar
  $(document).on('click', '.tiwsc-remove-sample', function (e) {
    e.preventDefault()
    console.log('Remove sample clicked')
    var $this = $(this)
    var productId = $this.data('product-id')
    var sampleKey = $this.data('sample-key')

    if (typeof tiwsc_ajax === 'undefined') {
      console.error('tiwsc_ajax is undefined')
      return
    }

    $.post(
      tiwsc_ajax.ajax_url,
      {
        action: 'tiwsc_remove_sample',
        product_id: productId,
        sample_key: sampleKey
      },
      function (response) {
        console.log('Remove response:', response)
        if (response.removed) {
          // Check if this is a variable product sample
          if (sampleKey && sampleKey.indexOf('|') !== -1) {
            // Parse the sample key for variable products
            var parts = sampleKey.split('|')
            if (parts.length === 3) {
              var attrName = parts[1]
              var attrValue = parts[2]
              // Update variable product buttons
              var $variableButtons = $(
                '.tiwsc-variable-sample-button[data-product-id="' +
                  productId +
                  '"][data-attribute-name="' +
                  attrName +
                  '"][data-attribute-value="' +
                  attrValue +
                  '"]'
              )
              $variableButtons.removeClass('tiwsc-added')
              $variableButtons.each(function () {
                var colorName = $(this).data('color-name')
                $(this).find('.tiwsc-button-text').text(colorName)
              })
              // Update SVG to outline version
              $variableButtons
                .find('svg path:first-child')
                .attr('fill', 'none')
                .attr('stroke', '#333')
            }
            // Also update samples page buttons
            var $samplePageButtons = $(
              '.tiwsc-add-sample-btn[data-product-id="' +
                productId +
                '"][data-attribute-name="' +
                attrName +
                '"][data-attribute-value="' +
                attrValue +
                '"]'
            )
            $samplePageButtons.removeClass('tiwsc-added').html('+ TOEVOEGEN')
          } else {
            // Update simple product buttons
            var $allButtons = $(
              '.tiwsc-free-sample-link[data-product-id="' + productId + '"]'
            )
            $allButtons.removeClass('tiwsc-added')
            $allButtons
              .find('.tiwsc-free-sample-text')
              .text('Gratis Kleurstaal')
            // Update SVG to outline version
            $allButtons
              .find('svg path:first-child')
              .attr('fill', 'none')
              .attr('stroke', '#222')
          }

          // Reload sidebar content
          loadSidebarContent()

          // Also update the unified main button
          var $mainBtn = $(
            '.tiwsc-variable-sample-main-button[data-product-id="' +
              productId +
              '"][data-attribute-name="' +
              attrName +
              '"]'
          )
          if ($mainBtn.length) {
            $mainBtn.removeClass('tiwsc-added')
            $mainBtn.find('.tiwsc-button-text').text('Gratis Kleurstaal')
            $mainBtn
              .find('svg path:first-child')
              .attr('fill', 'none')
              .attr('stroke', '#333')
          }
        }
      },
      'json'
    ).fail(function (xhr, status, error) {
      console.error('AJAX error:', status, error)
    })
  })

  // Submit form functionality
  $(document).on('submit', '#tiwsc-sample-form', function (e) {
    e.preventDefault()
    console.log('Form submitted')
    var $form = $(this)
    var formData = $form.serialize()
    formData += '&action=tiwsc_submit_sample_form'

    $('#tiwsc-sample-form-result').html(
      '<div style="color:#666;">Bezig met verzenden...</div>'
    )

    if (typeof tiwsc_ajax === 'undefined') {
      console.error('tiwsc_ajax is undefined')
      return
    }

    $.post(tiwsc_ajax.ajax_url, formData, function (response) {
      console.log('Form response:', response)
      $('#tiwsc-sample-form-result').html(
        '<div style="color:#008000;font-weight:bold;">' + response + '</div>'
      )

      // Clear form and reload sidebar after successful submission
      if (response.indexOf('Bedankt') !== -1) {
        $form[0].reset()
        setTimeout(function () {
          closeSidebar()
          // Update all sample buttons to not-added state
          $('.tiwsc-free-sample-link').removeClass('tiwsc-added')
          $('.tiwsc-free-sample-link .tiwsc-free-sample-text').text(
            'Gratis Kleurstaal'
          )
          $('.tiwsc-free-sample-link svg path:first-child')
            .attr('fill', 'none')
            .attr('stroke', '#222')

          // Also clear variable product buttons
          $('.tiwsc-variable-sample-button').removeClass('tiwsc-added')
          $('.tiwsc-variable-sample-button').each(function () {
            var colorName = $(this).data('color-name')
            $(this).find('.tiwsc-button-text').text(colorName)
          })
          $('.tiwsc-variable-sample-button svg path:first-child')
            .attr('fill', 'none')
            .attr('stroke', '#333')

          // Clear samples page buttons
          $('.tiwsc-add-sample-btn')
            .removeClass('tiwsc-added')
            .html('+ TOEVOEGEN')
        }, 2000)
      }
    }).fail(function (xhr, status, error) {
      console.error('AJAX error:', status, error)
      $('#tiwsc-sample-form-result').html(
        '<div style="color:#d00;">Er is een fout opgetreden. Probeer het opnieuw.</div>'
      )
    })
  })

  // Function to open sidebar with animation
  function openSidebar () {
    console.log('openSidebar() called')

    var $sidebar = $('#tiwsc-sidebar')
    var $overlay = $('#tiwsc-sidebar-overlay')

    console.log('Sidebar element found:', $sidebar.length)
    console.log('Overlay element found:', $overlay.length)

    if ($sidebar.length === 0) {
      console.error('Sidebar element not found!')
      return
    }

    if ($overlay.length === 0) {
      console.error('Overlay element not found!')
      return
    }

    // Load content first
    loadSidebarContent()

    // Show overlay
    $overlay.show()
    console.log('Overlay shown')

    // Add body class to prevent scrolling
    $('body').addClass('tiwsc-sidebar-active')
    console.log('Body class added')

    // Force a reflow to ensure elements are ready
    $sidebar[0].offsetHeight

    // Trigger animations
    setTimeout(function () {
      $sidebar.addClass('tiwsc-sidebar-open')
      $overlay.addClass('tiwsc-overlay-open')
      console.log('Animation classes added')
    }, 10)
  }

  // Function to close sidebar with animation
  function closeSidebar () {
    console.log('closeSidebar() called')
    var $sidebar = $('#tiwsc-sidebar')
    var $overlay = $('#tiwsc-sidebar-overlay')

    // Remove animation classes
    $sidebar.removeClass('tiwsc-sidebar-open')
    $overlay.removeClass('tiwsc-overlay-open')
    console.log('Animation classes removed')

    // Remove body class
    $('body').removeClass('tiwsc-sidebar-active')

    // Hide overlay after animation completes
    setTimeout(function () {
      $overlay.hide()
      console.log('Overlay hidden')
    }, 300) // Match the CSS transition duration
  }

  // Function to load sidebar content
  function loadSidebarContent () {
    console.log('Loading sidebar content...')

    if (typeof tiwsc_ajax === 'undefined') {
      console.error('tiwsc_ajax is undefined')
      return
    }

    $.post(
      tiwsc_ajax.ajax_url,
      {
        action: 'tiwsc_get_sidebar'
      },
      function (response) {
        console.log('Sidebar content loaded')
        $('#tiwsc-sidebar-content').html(response)
      }
    ).fail(function (xhr, status, error) {
      console.error('Failed to load sidebar content:', status, error)
    })
  }

  // Close sidebar with Escape key
  $(document).keyup(function (e) {
    if (e.keyCode === 27) {
      // Escape key
      console.log('Escape key pressed')
      closeSidebar()
    }
  })

  // Prevent sidebar from closing when clicking inside it
  $(document).on('click', '#tiwsc-sidebar', function (e) {
    e.stopPropagation()
  })

  // Add a test button for debugging
  if ($('.tiwsc-open-sidebar-link').length === 0) {
    console.warn('No sidebar open links found on page')
  } else {
    console.log(
      'Found',
      $('.tiwsc-open-sidebar-link').length,
      'sidebar open links'
    )
  }
})
