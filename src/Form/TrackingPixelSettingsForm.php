<?php

namespace Drupal\pixel_manager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class TrackingPixelSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pixel_manager.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pixel_manager_tracking_pixel_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Check for permission.
    //if (!\Drupal::currentUser()->hasPermission('manage tracking pixels')) {
    //  throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
    //}

    $config = $this->config('pixel_manager.settings');
    $pixels = $form_state->get('pixels') ?? $config->get('pixels') ?? [];
  
    // Inner container to wrap each pixel entry.
    $form['pixels_container']['pixels'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'pixel-container'],
    ];
  
    foreach ($pixels as $key => $pixel) {
      $form['pixels_container']['pixels'][$key] = [
        '#type' => 'details',
        '#title' => $pixel['title'] ?? $this->t('Untitled Pixel'),
        '#open' => FALSE,
      ];
    
      // Title field.
      $form['pixels_container']['pixels'][$key]['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#default_value' => $pixel['title'] ?? '',
        '#parents' => ['pixels', $key, 'title'], // Ensures correct form structure.
      ];
    
      // Description field.
      $form['pixels_container']['pixels'][$key]['description'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Description'),
        '#description' => $this->t('Check this box to enable the pixel on your site.'),
        '#default_value' => $pixel['description'] ?? '',
        '#parents' => ['pixels', $key, 'description'],
      ];
    
      // Enabled checkbox.
      $form['pixels_container']['pixels'][$key]['enabled'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Enabled'),
        '#default_value' => $pixel['enabled'] ?? TRUE,
        '#parents' => ['pixels', $key, 'enabled'],
      ];
    
      // Remove checkbox.
      $form['pixels_container']['pixels'][$key]['remove'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Remove'),
        '#description' => $this->t('Check this box to remove the pixel when saving the configuration.'),
        '#parents' => ['pixels', $key, 'remove'],
      ];
    
      // JavaScript Code field.
      $form['pixels_container']['pixels'][$key]['code'] = [
        '#type' => 'textarea',
        '#title' => $this->t('JavaScript Code'),
        '#description' => $this->t('Paste the JavaScript code for the pixel here.'),
        '#default_value' => $pixel['code'] ?? '',
        '#parents' => ['pixels', $key, 'code'],
      ];
    
      // Scope selector.
      $form['pixels_container']['pixels'][$key]['scope'] = [
        '#type' => 'select',
        '#title' => $this->t('Scope'),
        '#description' => $this->t('Select where this pixel will be applied: globally, by taxonomy, or by specific paths.'),
        '#options' => [
          'global' => $this->t('Global'),
          'taxonomy' => $this->t('Taxonomy-based'),
          'path' => $this->t('Specific Path'),
        ],
        '#default_value' => $pixel['scope'] ?? 'global',
        '#parents' => ['pixels', $key, 'scope'],
      ];
    
      // Taxonomy Terms field.
      $form['pixels_container']['pixels'][$key]['terms'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Taxonomy Terms'),
        '#description' => $this->t('Enter taxonomy term IDs separated by commas for taxonomy-based pixels.'),
        '#default_value' => is_array($pixel['terms']) ? implode(', ', $pixel['terms']) : '',
        '#parents' => ['pixels', $key, 'terms'],
        '#states' => [
          'visible' => [
            ':input[name="pixels[' . $key . '][scope]"]' => ['value' => 'taxonomy'],
          ],
        ],
      ];
    
      // Paths field.
      $form['pixels_container']['pixels'][$key]['paths'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Paths'),
        '#description' => $this->t('Enter specific paths (e.g., /about, /node/1) separated by commas for path-based pixels.'),
        '#default_value' => is_array($pixel['paths']) ? implode(', ', $pixel['paths']) : '',
        '#parents' => ['pixels', $key, 'paths'],
        '#states' => [
          'visible' => [
            ':input[name="pixels[' . $key . '][scope]"]' => ['value' => 'path'],
          ],
        ],
      ];
    }
    
    
    // Place the "Add Pixel" button outside the container to avoid duplication.
    $form['add_pixel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Pixel'),
      '#ajax' => [
        'callback' => '::addPixelAjaxCallback',
        'wrapper' => 'pixel-container',
      ],
      '#submit' => ['::addPixel'],
    ];
  
    return parent::buildForm($form, $form_state);
  }
  
  
  /**
   * AJAX callback to add a new pixel.
   */
  public function addPixel(array &$form, FormStateInterface $form_state) {
    // Retrieve the current list of pixels from form state.
    $pixels = $form_state->get('pixels') ?? $this->config('pixel_manager.settings')->get('pixels') ?? [];
  
    // Add a new pixel entry with default values.
    $pixels[] = [
      'title' => 'untitled pixel',
      'description' => '',
      'enabled' => TRUE,
      'code' => '',
      'scope' => 'global',
      'terms' => '',
      'paths' => '',
    ];
  
    // Update form state with the modified pixels array.
    $form_state->set('pixels', $pixels);
    //\Drupal::logger('pixel_manager')->notice('Pixels after adding new: @pixels', ['@pixels' => print_r($pixels, TRUE)]);
  
    // Set form to rebuild for the AJAX callback.
    $form_state->setRebuild(TRUE);
  }
  
  
  
  /**
   * AJAX callback function to rebuild the form when a new pixel is added.
   */
  public function addPixelAjaxCallback(array &$form, FormStateInterface $form_state) {
    //\Drupal::logger('pixel_manager')->notice('AJAX callback triggered.');
    return $form['pixels_container'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the pixels from the form submission directly.
    $pixels = $form_state->getValue('pixels') ?? [];
  
    //\Drupal::logger('pixel_manager')->notice('Pixels retrieved on submit: @pixels', ['@pixels' => print_r($pixels, TRUE)]);
  
    // Continue with filtering and saving as usual.
    $pixels = array_filter($pixels, function($pixel) {
      return empty($pixel['remove']);
    });
  
    foreach ($pixels as &$pixel) {
      $pixel['title'] = $pixel['title'] ?? '';
      $pixel['description'] = $pixel['description'] ?? '';
      $pixel['terms'] = array_map('trim', explode(',', $pixel['terms'] ?? ''));
      $pixel['paths'] = array_map('trim', explode(',', $pixel['paths'] ?? ''));
      $pixel['enabled'] = !empty($pixel['enabled']);
    }
  
    // Save the updated pixels array to the configuration.
    $this->config('pixel_manager.settings')
      ->set('pixels', $pixels)
      ->save();
  
    parent::submitForm($form, $form_state);
  }
  
  
}
