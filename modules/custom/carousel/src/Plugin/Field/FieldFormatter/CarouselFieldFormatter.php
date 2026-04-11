<?php

/**
 * @file
 * Contains \Drupal\carousel\Plugin\Field\FieldFormatter\CarouselFieldFormatter.
 */

namespace Drupal\carousel\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\image\Plugin\Field\FieldFormatter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'carousel_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "carousel_field_formatter",
 *   label = @Translation("Carousel"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class CarouselFieldFormatter extends EntityReferenceFormatterBase implements ContainerFactoryPluginInterface {

  protected $currentUser;
  protected $imageStyleStorage;

  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityStorageInterface $image_style_storage) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->currentUser = $current_user;
    $this->imageStyleStorage = $image_style_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('current_user'),
      $container->get('entity_type.manager')->getStorage('image_style')	  
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return _carousel_default_settings() + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $image_styles = image_style_options(FALSE);
    $description_link = Link::fromTextAndUrl(
      $this->t('Configure Image Styles'),
      Url::fromRoute('entity.image_style.collection')
    );
    $element['image_style'] = [
      '#title' => t('Image style'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_style'),
      '#empty_option' => t('None (original image)'),
      '#options' => $image_styles,
      '#description' => $description_link->toRenderable() + [
          '#access' => $this->currentUser->hasPermission('administer image styles')
        ],
    ];
    $link_types = array(
      'content' => t('Content'),
      'file' => t('File'),
    );
    $element['image_link'] = array(
      '#title' => t('Link image to'),
      '#type' => 'select',
      '#default_value' => $this->getSetting('image_link'),
      '#empty_option' => t('Nothing'),
      '#options' => $link_types,
    );

    $element['items'] = array(
      '#type' => 'number',
      '#title' => $this->t('Items'),
      '#description' => $this->t('Maximum amount of items displayed at a time with the widest browser width.'),
      '#default_value' => $this->getSetting('items'),
    );
	
    //margin
    $element['margin'] = array(
      '#type' => 'number',
      '#title' => $this->t('Margin'),
      '#default_value' => $this->getSetting('margin'),
      '#description' => $this->t('margin-right(px) on item.'),
    );
    //autoplay
    $element['autoplay'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('AutoPlay'),
      '#default_value' => $this->getSetting('autoplay'),
    );
    //autoplayHoverPause
    $element['autoplayHoverPause'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Pause On Hover'),
      '#default_value' => $this->getSetting('autoplayHoverPause'),
      '#description' => $this->t('Pause autoplay on mouse hover.'),
    );
    //autoplaySpeed
    $element['autoplaySpeed'] = array(
      '#type' => 'number',
      '#title' => $this->t('AutoPlay Speed'),
      '#default_value' => $this->getSetting('autoplaySpeed'),
	  '#description' => $this->t('AutoPlay speed in milliseconds.'),
    );
    //autoplayTimeout
    $element['autoplayTimeout'] = array(
      '#type' => 'number',
      '#title' => $this->t('AutoPlay Timeout'),
      '#default_value' => $this->getSetting('autoplayTimeout'),
	  '#description' => $this->t('AutoPlay Timeout in milliseconds.'),
    );
    //navigation
    $element['nav'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Navigation'),
      '#default_value' => $this->getSetting('nav'),
      '#description' => $this->t('Display "next" and "prev" buttons.'),
    );
    //navigation Speed
    $element['navSpeed'] = array(
      '#type' => 'number',
      '#title' => $this->t('Navigation Speed'),
      '#default_value' => $this->getSetting('navSpeed'),
      '#description' => $this->t('Navigation speed in milliseconds.'),
    );
    //loop
    $element['loop'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Rewind Loop'),
      '#default_value' => $this->getSetting('loop'),
      '#description' => $this->t('Slide to first item.'),
    );
    //navRewind
    $element['navRewind'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Rewind'),
      '#default_value' => $this->getSetting('navRewind'),
      '#description' => $this->t('Rewind to first item.'),
    );	
    //rewindSpeed
    $element['rewindSpeed'] = array(
      '#type' => 'number',
      '#title' => $this->t('Rewind Speed'),
      '#default_value' => $this->getSetting('rewindSpeed'),
      '#description' => $this->t('Rewind speed in milliseconds.'),
    );
    //pagination
    $element['dots'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('pagination'),
      '#default_value' => $this->getSetting('dots'),
      '#description' => $this->t('Show pagination.'),
    );
    //paginationSpeed
    $element['dotsSpeed'] = array(
      '#type' => 'number',
      '#title' => $this->t('Pagination Speed'),
      '#default_value' => $this->getSetting('dotsSpeed'),
      '#description' => $this->t('Pagination speed in milliseconds.'),
    );
    //slideBy
    $element['slideBy'] = array(
      '#type' => 'number',
      '#title' => $this->t('Slide By'),
      '#default_value' => $this->getSetting('slideBy'),
      '#description' => $this->t('Slide by how many items.'),
    );
    //mouseDrag
    $element['mouseDrag'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Mouse Drag'),
      '#default_value' => $this->getSetting('mouseDrag'),
      '#description' => $this->t('Turn off/on mouse events.'),
    );
    //touchDrag
    $element['touchDrag'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Touch Drag'),
      '#default_value' => $this->getSetting('touchDrag'),
      '#description' => $this->t('Turn off/on touch events.'),
    );	
    //pullDrag
    $element['pullDrag'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Pull Drag'),
      '#default_value' => $this->getSetting('pullDrag'),
      '#description' => $this->t('Turn off/on pull events.'),
    );
    //freeDrag
    $element['freeDrag'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Free Drag'),
      '#default_value' => $this->getSetting('freeDrag'),
      '#description' => $this->t('Turn off/on free events.'),
    );
    //stagePadding
    $element['stagePadding'] = array(
      '#type' => 'number',
      '#title' => $this->t('Stage Padding'),
      '#default_value' => $this->getSetting('stagePadding'),
      '#description' => $this->t('Stage padding in px.'),
    );
    //merge
    $element['merge'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Merge'),
      '#default_value' => $this->getSetting('merge'),
      '#description' => $this->t('Merge items.'),
    );
    //mergeFit
    $element['mergeFit'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Merge Fit'),
      '#default_value' => $this->getSetting('mergeFit'),
      '#description' => $this->t('Merge fit.'),
    );
    //center
    $element['center'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Center'),
      '#default_value' => $this->getSetting('center'),
      '#description' => $this->t('center (works with even number of items).'),
    );
    //autoWidth
    $element['autoWidth'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Auto Width'),
      '#default_value' => $this->getSetting('autoWidth'),
      '#description' => $this->t('Auto width items.'),
    );
    //video
    $element['video'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Video'),
      '#default_value' => $this->getSetting('video'),
      '#description' => $this->t('Video items.'),
    );
    //videoHeight
    $element['videoHeight'] = array(
      '#type' => 'number',
      '#title' => $this->t('Video Height'),
      '#default_value' => $this->getSetting('videoHeight'),
      '#description' => $this->t('Video height in px.'),
    );
    //videoWidth
    $element['videoWidth'] = array(
      '#type' => 'number',
      '#title' => $this->t('Video Width'),
      '#default_value' => $this->getSetting('videoWidth'),
      '#description' => $this->t('Video width in px.'),
    );

    return $element + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {

    $elements = array();

    $files = $this->getEntitiesToView($items, $langcode);

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    $url = NULL;
    $image_link_setting = $this->getSetting('image_link');
    // Check if the formatter involves a link.
    if ($image_link_setting == 'content') {
      $entity = $items->getEntity();
      if (!$entity->isNew()) {
        $url = $entity->toUrl();
      }
    }
    elseif ($image_link_setting == 'file') {
      $link_file = TRUE;
    }

    $image_style_setting = $this->getSetting('image_style');

    // Collect cache tags to be added for each item in the field.
    $cache_tags = array();
    if (!empty($image_style_setting)) {
      $image_style = $this->imageStyleStorage->load($image_style_setting);
      $cache_tags = $image_style->getCacheTags();
    }

    foreach ($files as $delta => $file) {
      if (isset($link_file)) {
        $image_uri = $file->getFileUri();
        $url = Url::fromUri(file_create_url($image_uri));
      }
      $cache_tags = Cache::mergeTags($cache_tags, $file->getCacheTags());


      // Extract field item attributes for the theme function, and unset them
      // from the $item so that the field template does not re-render them.
      $item = $file->_referringItem;
      $item_attributes = $item->_attributes;
      unset($item->_attributes);

      $elements[$delta] = array(
        '#theme' => 'image_formatter',
        '#item' => $item,
        '#item_attributes' => $item_attributes,
        '#image_style' => $image_style_setting,
        '#url' => $url,
        '#cache' => array(
          'tags' => $cache_tags,
        ),
      );
    }


    $settings = _carousel_default_settings();
    foreach ($settings as $k => $v) {
      $s = $this->getSetting($k);
      $settings[$k] = isset($s) ? $s : $settings[$k];
    }
    return array(
      '#theme' => 'carousel',
      '#items' => $elements,
      '#settings' => $settings,
      '#attached' => array('library' => array('carousel/owl-carousel'))
    );


  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }


}