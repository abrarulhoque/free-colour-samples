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
  
  // Listen for clicks on variation swatches to ensure compatibility
  $(document).on('click', '.cfvsw-swatches-option', function() {
    console.log('[TIWSC] Swatch clicked:', $(this).attr('data-slug'))
    // Small delay to ensure the swatch plugin has updated the select
    setTimeout(function() {
      // Trigger change on the select to ensure WooCommerce updates
      var $form = $('form.variations_form')
      $form.find('select').trigger('change')
    }, 50)
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
    
    // First try to get value from the select element
    var attributeValue = $form.find(attributeSelector).val()
    
    // If no value from select, try different swatch plugin selectors
    if (!attributeValue) {
      // Debug: log all available swatches containers
      console.log('[TIWSC] Looking for swatches with attribute:', attributeName)
      console.log('[TIWSC] Form:', $form)
      console.log('[TIWSC] Swatches containers:', $form.find('[swatches-attr]'))
      
      // Try variation swatches plugin (cfvsw) - check multiple possible attribute formats
      var possibleSelectors = [
        '[swatches-attr="attribute_' + attributeName + '"] .cfvsw-selected-swatch',
        '[swatches-attr="' + attributeName + '"] .cfvsw-selected-swatch', // Sometimes without attribute_ prefix
        '.cfvsw-swatches-container[swatches-attr="attribute_' + attributeName + '"] .cfvsw-selected-swatch',
        '.cfvsw-swatches-container[swatches-attr="' + attributeName + '"] .cfvsw-selected-swatch'
      ]
      
      for (var i = 0; i < possibleSelectors.length; i++) {
        var $selectedSwatch = $form.find(possibleSelectors[i])
        if ($selectedSwatch.length > 0) {
          attributeValue = $selectedSwatch.attr('data-slug') || $selectedSwatch.data('slug') || $selectedSwatch.attr('data-value')
          console.log('[TIWSC] Found cfvsw swatch with selector:', possibleSelectors[i], 'value:', attributeValue)
          break
        }
      }
      
      // If still not found, try searching globally (outside form)
      if (!attributeValue) {
        for (var i = 0; i < possibleSelectors.length; i++) {
          var $selectedSwatch = $(possibleSelectors[i])
          if ($selectedSwatch.length > 0) {
            attributeValue = $selectedSwatch.attr('data-slug') || $selectedSwatch.data('slug') || $selectedSwatch.attr('data-value')
            console.log('[TIWSC] Found cfvsw swatch globally with selector:', possibleSelectors[i], 'value:', attributeValue)
            break
          }
        }
      }
      
      // If still no value, try other common swatch plugin selectors
      if (!attributeValue) {
        var $otherSwatch = $form.find('[data-attribute_name="attribute_' + attributeName + '"] .selected, [data-attribute_name="attribute_' + attributeName + '"] .active')
        if ($otherSwatch.length > 0) {
          attributeValue = $otherSwatch.attr('data-value') || $otherSwatch.data('value')
          console.log('[TIWSC] Found other swatch:', attributeValue)
        }
      }
      
      // Try radio buttons
      if (!attributeValue) {
        attributeValue = $form.find(attributeSelector + ':checked').val()
      }
    }

    var sampleKeyMain = buildSampleKey(productId, attributeName, attributeValue)

    console.log('[TIWSC] attribute:', attributeName, 'value:', attributeValue, 'sampleKey:', sampleKeyMain)
    console.log('[TIWSC] Selected swatch element:', $form.find('[swatches-attr="attribute_' + attributeName + '"] .cfvsw-selected-swatch'))

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
          if (currentSamples.indexOf(sampleKeyMain) === -1) {
            currentSamples.push(sampleKeyMain)
          }
          $this.addClass('tiwsc-added')
          $this.find('.tiwsc-button-text').text('Toegevoegd')
          $this
            .find('svg path:first-child')
            .attr('fill', '#88ae98')
            .attr('stroke', '#88ae98')
          // Auto-open sidebar
          openSidebar()
        } else {
          currentSamples = currentSamples.filter(function (k) {
            return k !== sampleKeyMain
          })
          $this.removeClass('tiwsc-added')
          $this.find('.tiwsc-button-text').text('Gratis Kleurstaal')
          $this
            .find('svg path:first-child')
            .attr('fill', 'none')
            .attr('stroke', '#333')
        }

        // Re-evaluate button state in case the selected colour is different
        updateMainVariableSampleButton()
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
    '.tiwsc-close-trigger, #tiwsc-sidebar-overlay',
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
          // Remove from local cache
          currentSamples = currentSamples.filter(function (k) {
            return k !== sampleKey
          })
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

          // After UI adjustments, re-evaluate the main button state
          updateMainVariableSampleButton()
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

  /* ------------------------------------------------------------------ */
  /* Track current samples from PHP session so we can disable/enable the
     main variation button depending on whether the chosen colour sample
     is already in the cart. */
  var currentSamples = (typeof tiwsc_ajax !== 'undefined' && Array.isArray(tiwsc_ajax.samples))
    ? tiwsc_ajax.samples.slice()
    : []

  function buildSampleKey (productId, attributeName, attributeValue) {
    if (attributeName && attributeValue) {
      return productId + '|' + attributeName + '|' + attributeValue
    }
    return productId.toString()
  }

  // Helper to discover the currently selected attribute value (re-uses logic
  // from the main click handler so we stay compatible with swatch plugins)
  function getSelectedAttributeValue ($btn, attributeName) {
    var $form = $btn.closest('form.variations_form')
    if ($form.length === 0) {
      $form = $('form.variations_form')
    }

    var attributeSelector = '[name="attribute_' + attributeName + '"]'
    var attributeValue = $form.find(attributeSelector).val()

    if (!attributeValue) {
      // Try CFVSW swatches and other common selectors
      var possibleSelectors = [
        '[swatches-attr="attribute_' + attributeName + '"] .cfvsw-selected-swatch',
        '[swatches-attr="' + attributeName + '"] .cfvsw-selected-swatch',
        '.cfvsw-swatches-container[swatches-attr="attribute_' + attributeName + '"] .cfvsw-selected-swatch',
        '.cfvsw-swatches-container[swatches-attr="' + attributeName + '"] .cfvsw-selected-swatch'
      ]

      for (var i = 0; i < possibleSelectors.length && !attributeValue; i++) {
        var $selectedSwatch = $form.find(possibleSelectors[i])
        if ($selectedSwatch.length) {
          attributeValue =
            $selectedSwatch.attr('data-slug') ||
            $selectedSwatch.data('slug') ||
            $selectedSwatch.attr('data-value')
        }
      }

      // Fallback global search
      if (!attributeValue) {
        for (var i = 0; i < possibleSelectors.length && !attributeValue; i++) {
          var $selectedGlobal = $(possibleSelectors[i])
          if ($selectedGlobal.length) {
            attributeValue =
              $selectedGlobal.attr('data-slug') ||
              $selectedGlobal.data('slug') ||
              $selectedGlobal.attr('data-value')
          }
        }
      }

      // Radio buttons fallback
      if (!attributeValue) {
        attributeValue = $form.find(attributeSelector + ':checked').val()
      }
    }

    return attributeValue
  }

  function updateMainVariableSampleButton () {
    $('.tiwsc-variable-sample-main-button').each(function () {
      var $btn = $(this)
      var productId = $btn.data('product-id')
      var attributeName = $btn.data('attribute-name')
      if (!attributeName) return // safety

      var attributeValue = getSelectedAttributeValue($btn, attributeName)

      if (!attributeValue) {
        // No colour selected yet – ensure button is in default state
        $btn.removeClass('tiwsc-added')
        $btn.find('.tiwsc-button-text').text('Gratis Kleurstaal')
        $btn
          .find('svg path:first-child')
          .attr('fill', 'none')
          .attr('stroke', '#333')
        $btn.css('pointer-events', '')
        return
      }

      var sampleKey = buildSampleKey(productId, attributeName, attributeValue)
      var isAdded = currentSamples.indexOf(sampleKey) !== -1

      if (isAdded) {
        $btn.addClass('tiwsc-added')
        $btn.find('.tiwsc-button-text').text('Toegevoegd')
        $btn
          .find('svg path:first-child')
          .attr('fill', '#88ae98')
          .attr('stroke', '#88ae98')
        $btn.css('pointer-events', 'none')
      } else {
        $btn.removeClass('tiwsc-added')
        $btn.find('.tiwsc-button-text').text('Gratis Kleurstaal')
        $btn
          .find('svg path:first-child')
          .attr('fill', 'none')
          .attr('stroke', '#333')
        $btn.css('pointer-events', '')
      }
    })
  }

  // Run once on page load
  updateMainVariableSampleButton()

  /* Re-evaluate when the variation changes */
  $('form.variations_form').on('found_variation reset_data', function () {
    updateMainVariableSampleButton()
  })

  // Select change handler (covers non-swatch attribute dropdowns)
  $(document).on('change', 'form.variations_form select', function () {
    updateMainVariableSampleButton()
  })

  // Swatch click – small delay to allow plugin to mark selection first
  $(document).on('click', '.cfvsw-swatches-option', function () {
    setTimeout(updateMainVariableSampleButton, 80)
  })
  /* ------------------------------------------------------------------ */
})
