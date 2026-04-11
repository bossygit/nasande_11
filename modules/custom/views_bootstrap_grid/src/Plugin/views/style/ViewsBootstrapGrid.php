<?php
/**
 * @file
 * Contains \Drupal\views_bootstrap_grid\Plugin\views\style\ViewsBootstrapGrid.
 */
namespace Drupal\views_bootstrap_grid\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;
/**
 * Style plugin to render each item into Bootstrap Grid.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "ViewsBootstrapGrid",
 *   title = @Translation("Bootstrap Grid"),
 *   help = @Translation("Responsive Bootstrap Grid."),
 *   theme = "views_bootstrap_grid_views",
 *   display_types = {"normal"}
 * )
 */
class ViewsBootstrapGrid extends StylePluginBase {
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
    $options['sm'] = array('default' => 12);
    $options['md'] = array('default' => 6);
    $options['lg'] = array('default' => 4);
    $options['grid_item'] = array('default' => FALSE);
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
   * Render the given style.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
	//Desktop
	$form['lg'] = array(
      '#title' => t("Desktop"),
      '#type' => 'select',
	  '#prefix' => t('<br /> <h5>Select the Number of Items to Display per Row on Devices</h5>'),
      '#options' => $this->buildSelectList(),
      '#default_value' => $this->options['lg'],
    );
	//Tablet
    $form['md'] = array(
      '#title' => t("Tablet"),
      '#type' => 'select',
      '#options' => $this->buildSelectList(),
      '#default_value' => $this->options['md'],
    );
	//Mobile
    $form['sm'] = array(
      '#title' => t("Mobile"),
      '#type' => 'select',
      '#options' => $this->buildSelectList(),
      '#default_value' => $this->options['sm'],
    );
	//Mansory Grid
    $form['grid_item'] = array(
      '#type' => 'checkbox',
      '#title' => t("Mansory Grid"),
      '#default_value' => $this->options['grid_item'],
      '#description' => $this->t('Make mansory grid item.'),
    );
  }
}
