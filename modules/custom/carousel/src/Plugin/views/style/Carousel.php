<?php
/**
 * @file
 * Contains \Drupal\carousel\Plugin\views\style\Carousel.
 */
namespace Drupal\carousel\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;
/**
 * Style plugin to render each item into carousel.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "carousel",
 *   title = @Translation("Carousel"),
 *   help = @Translation("Displays rows as Carousel."),
 *   theme = "carousel_views",
 *   display_types = {"normal"}
 * )
 */
class Carousel extends StylePluginBase {
  /**
   * Does the style plugin allows to use style plugins.
   *
   * @var bool
   */
  protected $usesRowPlugin = TRUE;
  /**
   * Does the style plugin support custom css class for the rows.
   *
   * @var bool
   */
  protected $usesRowClass = TRUE;
  
  /**
   * Set default options
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $settings = _carousel_default_settings();
    foreach ($settings as $k => $v) {
      $options[$k] = array('default' => $v);
    }
    return $options;
  }

  /**
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
	//items
    $form['items'] = array(
      '#type' => 'number',
      '#title' => $this->t('Items'),
      '#description' => $this->t('Maximum amount of items displayed at a time with the widest browser width.'),
      '#default_value' => $this->options['items'],
    );	
    //margin
    $form['margin'] = array(
      '#type' => 'number',
      '#title' => $this->t('Margin'),
      '#default_value' => $this->options['margin'],
      '#description' => $this->t('margin-right(px) on item.'),
    );
    //autoplay
    $form['autoplay'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('AutoPlay'),
      '#default_value' => $this->options['autoplay'],
    );
    //autoplayHoverPause
    $form['autoplayHoverPause'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Pause On Hover'),
      '#default_value' => $this->options['autoplayHoverPause'],
      '#description' => $this->t('Pause autoplay on mouse hover.'),
    );
    //autoplaySpeed
    $form['autoplaySpeed'] = array(
      '#type' => 'number',
      '#title' => $this->t('AutoPlay Speed'),
      '#default_value' => $this->options['autoplaySpeed'],
	  '#description' => $this->t('AutoPlay speed in milliseconds.'),
    );
    //autoplayTimeout
    $form['autoplayTimeout'] = array(
      '#type' => 'number',
      '#title' => $this->t('AutoPlay Timeout'),
      '#default_value' => $this->options['autoplayTimeout'],
	  '#description' => $this->t('AutoPlay Timeout in milliseconds.'),
    );
    //navigation
    $form['nav'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Navigation'),
      '#default_value' => $this->options['nav'],
      '#description' => $this->t('Display "next" and "prev" buttons.'),
    );
    //navigation Speed
    $form['navSpeed'] = array(
      '#type' => 'number',
      '#title' => $this->t('Navigation Speed'),
      '#default_value' => $this->options['navSpeed'],
      '#description' => $this->t('Navigation speed in milliseconds.'),
    );	
    //loop
    $form['loop'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Slide Loop'),
      '#default_value' => $this->options['loop'],
      '#description' => $this->t('Slide to first item.'),
    );
    //navRewind
    $form['navRewind'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Rewind'),
      '#default_value' => $this->options['navRewind'],
      '#description' => $this->t('Rewind to first item.'),
    );	
    //rewindSpeed
    $form['rewindSpeed'] = array(
      '#type' => 'number',
      '#title' => $this->t('Rewind Speed'),
      '#default_value' => $this->options['rewindSpeed'],
      '#description' => $this->t('Rewind speed in milliseconds.'),
    );
    //pagination
    $form['dots'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Dots Pagination'),
      '#default_value' => $this->options['dots'],
      '#description' => $this->t('Show dots pagination.'),
    );
    //paginationSpeed
    $form['dotsSpeed'] = array(
      '#type' => 'number',
      '#title' => $this->t('Dots Pagination Speed'),
      '#default_value' => $this->options['dotsSpeed'],
      '#description' => $this->t('Pagination speed in milliseconds.'),
    );	
    //slideBy
    $form['slideBy'] = array(
      '#type' => 'number',
      '#title' => $this->t('Slide By'),
      '#default_value' => $this->options['slideBy'],
      '#description' => $this->t('Slide by how many items.'),
    );
    //mouseDrag
    $form['mouseDrag'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Mouse Drag'),
      '#default_value' => $this->options['mouseDrag'],
      '#description' => $this->t('Turn off/on mouse events.'),
    );
    //touchDrag
    $form['touchDrag'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Touch Drag'),
      '#default_value' => $this->options['touchDrag'],
      '#description' => $this->t('Turn off/on touch events.'),
    );	
    //pullDrag
    $form['pullDrag'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Pull Drag'),
      '#default_value' => $this->options['pullDrag'],
      '#description' => $this->t('Turn off/on pull events.'),
    );
    //freeDrag
    $form['freeDrag'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Free Drag'),
      '#default_value' => $this->options['freeDrag'],
      '#description' => $this->t('Turn off/on free events.'),
    );
    //stagePadding
    $form['stagePadding'] = array(
      '#type' => 'number',
      '#title' => $this->t('Stage Padding'),
      '#default_value' => $this->options['stagePadding'],
      '#description' => $this->t('Stage padding in px.'),
    );
    //merge
    $form['merge'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Merge'),
      '#default_value' => $this->options['merge'],
      '#description' => $this->t('Merge items.'),
    );
    //mergeFit
    $form['mergeFit'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Merge Fit'),
      '#default_value' => $this->options['mergeFit'],
      '#description' => $this->t('Merge fit.'),
    );
    //center
    $form['center'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Center'),
      '#default_value' => $this->options['center'],
      '#description' => $this->t('center (works with even number of items).'),
    );
    //autoWidth
    $form['autoWidth'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Auto Width'),
      '#default_value' => $this->options['autoWidth'],
      '#description' => $this->t('Auto width items.'),
    );
    //video
    $form['video'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Video'),
      '#default_value' => $this->options['video'],
      '#description' => $this->t('Video items.'),
    );
    //videoHeight
    $form['videoHeight'] = array(
      '#type' => 'number',
      '#title' => $this->t('Video Height'),
      '#default_value' => $this->options['videoHeight'],
      '#description' => $this->t('Video height in px.'),
    );
    //videoWidth
    $form['videoWidth'] = array(
      '#type' => 'number',
      '#title' => $this->t('Video Width'),
      '#default_value' => $this->options['videoWidth'],
      '#description' => $this->t('Video width in px.'),
    );
  }

}