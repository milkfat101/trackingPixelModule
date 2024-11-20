(function (Drupal, drupalSettings) {
    Drupal.behaviors.trackingPixels = {
      attach: function (context, settings) {
        if (drupalSettings.pixel_manager) {
          drupalSettings.pixel_manager.forEach(function(jsCode) {
            try {
              eval(jsCode); // Execute the JavaScript code for each tracking pixel.
            } catch (e) {
              console.error("Error executing tracking pixel:", e);
            }
          });
        }
      }
    };
  })(Drupal, drupalSettings);
  