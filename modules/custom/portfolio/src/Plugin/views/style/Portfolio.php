<?php

namespace Drupal\portfolio\Plugin\views\style;

use Drupal\Component\Utility\Html;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Portfolio style plugin to render rows in a shuffle grid.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "portfolio",
 *   title = @Translation("Portfoilio Shuffle"),
 *   help = @Translation("Displays rows in a shuffle grid."),
 *   theme = "views_view_portfolio",
 *   display_types = {"normal"}
 * )
 */
class Portfolio extends StylePluginBase {

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
   * Does the style plugin support grouping of rows.
   *
   * @var bool
   */
  protected $usesGrouping = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['speed'] = array('default' => 250);
    $options['easing'] = array('default' => 'ease-out');
	$options['sm'] = array('default' => 12);
    $options['md'] = array('default' => 6);
    $options['lg'] = array('default' => 4);
	$options['nogutter'] = array('default' => FALSE);
    $options['filter'] = array('default' => 'none');
    return $options;
  }

  function getTitle($size) {
    if (!is_numeric($size)) {
      return t('None');
    }
    return t("@size", array('@size' => 12 / $size));
  }

  function buildSelectList() {
    $options = array(
      '' => t('None'),
      12 => $this->getTitle(12),
      6 => $this->getTitle(6),
      4 => $this->getTitle(4),
      3 => $this->getTitle(3),
      2 => $this->getTitle(2),
    );
    return $options;
  }
  
  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $fields_filter = [];
    $fields = $this->displayHandler->getOption('fields');
    foreach ($fields as $field_name => $field) {
      // We support only Entity Reference field with formatter set on label
      // and width a multi_type set on separator with a comma (,).
      if ($field['type'] == 'entity_reference_label') {
        $fields_filter[$field_name] = $field['field'];
      }
    }
	//speed
    $form['speed'] = array(
      '#title' => $this->t('Speed'),
      '#description' => $this->t('The transition/animation speed (in milliseconds).'),
      '#type' => 'textfield',
      '#default_value' => $this->options['speed'],
    );
	//easing
    $form['easing'] = array(
      '#title' => $this->t('Easing'),
      '#description' => $this->t('The CSS easing function to use for transition.'),
      '#type' => 'textfield',
      '#default_value' => $this->options['easing'],
    );
	$form['lg'] = array(
      '#title' => t("Desktop"),
      '#type' => 'select',
	  '#prefix' => t('<br /> <h5>Select the Number of Items to Display per Row on Devices</h5>'),
      '#options' => $this->buildSelectList(),
      '#default_value' => $this->options['lg'],
    );

    $form['md'] = array(
      '#title' => t("Tablet"),
      '#type' => 'select',
      '#options' => $this->buildSelectList(),
      '#default_value' => $this->options['md'],
    );

    $form['sm'] = array(
      '#title' => t("Mobile"),
      '#type' => 'select',
      '#options' => $this->buildSelectList(),
      '#default_value' => $this->options['sm'],
    );
	
    $form['nogutter'] = array(
      '#title' => t("Remove Gutter"),
      '#type' => 'checkbox',
      '#default_value' => $this->options['nogutter'],
    );
	
    if (empty($fields_filter) and !$this->usesFields()) {
      $form['error_markup'] = array(
        '#markup' => '<div class="messages messages--warning">' . $this->t('If you want to filter rows using a field, you need to check <strong>Force using fields</strong> above and add at least one field of type entity_reference to the view.') . '</div>',
       );
    }

    $form['filter'] = array(
      '#type' => 'select',
      '#title' => $this->t('Select a field for filter options'),
      '#description' => $this->t('If you want to filter rows using a field, you need to check <strong>Force using fields</strong> above and add at least one field of type entity_reference to the view.'),
      '#default_value' => $this->options['filter'],
      '#options' => ['none' => $this->t('-None-')] + $fields_filter,
    );

  }

  /**
   * {@inheritdoc}
   */
  public function preRender($result) {
    parent::preRender($result);

    // No need to do anything if we we have only one result.
    if (count($result) < 2) {
      return;
    }

    $view_settings['display'] = $this->view->current_display;
    $view_settings['viewname'] = $this->view->storage->id();
    $portfolio_id = Html::cleanCssIdentifier('views-portfolio-' . $view_settings['viewname'] . '-' . $this->view->current_display);

    // Preparing the js variables and adding the js to our display.
    $view_settings['speed'] = ($this->options['speed']) ? $this->options['speed'] : 250;
    $view_settings['easing'] = ($this->options['easing']) ? $this->options['easing'] : 'ease-out';
    $view_settings['lg'] = ($this->options['lg']) ? $this->options['lg'] : 'views-row';
	$view_settings['md'] = ($this->options['md']) ? $this->options['md'] : 'views-row';
	$view_settings['sm'] = ($this->options['sm']) ? $this->options['sm'] : 'views-row';
	$view_settings['nogutter'] = ($this->options['nogutter']) ? $this->options['nogutter'] : FALSE;

    if ($this->options['filter'] != 'none') {
      $view_settings['filter'] = $this->options['filter'];
    }
	
    $this->view->element['#attached']['library'][] = 'portfolio/shuffle';
    $this->view->element['#attached']['drupalSettings']['portfolio'] = [$portfolio_id => $view_settings];

  }

}
