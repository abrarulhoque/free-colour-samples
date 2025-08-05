# Free Colour Samples - Development Notes

## Event Order and State Management

This document explains the event handling and state management for the Free Colour Samples plugin, specifically focusing on how variation selection states are tracked and button states are updated.

### Key Components

#### 1. getAttrKey Function Contract

The `getAttrKey($form)` function builds a unique key representing the current attribute selections for a variation form. This key is used to track which color variations have samples added to the cart.

**Key Format**: `attribute_pa_color=value1&attribute_pa_size=value2`

**Priority Order** (highest to lowest):
1. **CFVSW Selected Swatch** - Checks `.cfvsw-selected-swatch` within swatch containers
2. **TP-Woo Selected Swatch** - Checks `.tp-swatches.selected` for tp-woo plugin
3. **Select Value** - Falls back to the native `<select>` element value

**Why this order?** Swatch plugins often update their visual UI before updating the hidden select elements. By reading from the swatch UI first, we get the most accurate current state.

### 2. Event Flow

When a user interacts with color swatches:

1. **User clicks swatch** → Swatch plugin updates its UI
2. **Swatch plugin triggers** → Updates hidden select, fires WooCommerce events
3. **Our handlers catch**:
   - `found_variation` - Main variation change event
   - `woocommerce_variation_has_changed` - Secondary change event
   - Direct swatch clicks (`.cfvsw-swatches-option`, `.tp-swatches`)
4. **Microtask scheduling** → Ensures DOM is fully updated before reading state
5. **Event coalescing** → Prevents duplicate updates within 10ms window
6. **State update** → Button text/class updated based on addedMap lookup

### 3. Button State Management

The plugin maintains button states using:
- **addedMap**: `Map<productId, Set<attributeKey>>` - Tracks which variations have samples added
- **Session integration**: Initializes from PHP session on page load
- **Visual states**: 
  - Default: "Gratis Kleurstaal" (outline style)
  - Added: "Toegevoegd" (filled style, disabled appearance)

### 4. Compatibility Considerations

The plugin is designed to work with:
- **Native WooCommerce** variation forms
- **CartFlows Variation Swatches** (cfvsw) plugin
- **TP Woo Product Variation Swatches** plugin
- **Sticky headers** and dynamically loaded content
- **Quick view modals** and AJAX-loaded forms

### 5. Performance Optimizations

- **Microtask scheduling**: Uses `Promise.resolve().then()` for immediate but deferred execution
- **Event coalescing**: 10ms window to batch rapid updates
- **Duplicate prevention**: Tracks last update key to skip redundant renders
- **Smart button targeting**: Caches and reuses jQuery selections where possible

### 6. Debug Mode

When `tiwscDebug = true`, the plugin logs:
- Raw select values vs. swatch selections
- Button state changes with timestamps
- Event firing sequences
- DOM mutations in key areas

Remember to set `tiwscDebug = false` before production deployment.