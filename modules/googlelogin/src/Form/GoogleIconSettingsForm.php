<?php

namespace Drupal\googlelogin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for Social API Icon Google.
 */
class GoogleIconSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_oauth_login_icon_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'googlelogin.icon.settings',
    ];
  }

  /**
   * Build Admin Settings Form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('googlelogin.icon.settings');

    $form['help'] = [
      '#type' => 'markup',
      '#markup' => 'You can find more details of this configuration at <a href ="https://developers.google.com/identity/gsi/web/reference/html-reference">Documentation</a> and <a href="https://developers.google.com/identity/gsi/web/tools/configurator">Code Generator</a>'
    ];
    $form['icon']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Button Type'),
      '#default_value' => $config->get('type'),
      '#required'=> TRUE,
      '#options' => ['icon' => 'Icon', 'standard' => 'Standard'],
    ];
    $form['icon']['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Button Theme'),
      '#default_value' => $config->get('theme'),
      '#options' => ['outline' => 'Default white', 'filled_blue' => 'Blue', 'filled_black' => 'Black'],
    ];
    $form['icon']['size'] = [
      '#type' => 'select',
      '#title' => $this->t('Button Size'),
      '#default_value' => $config->get('size'),
      '#options' => ['small' => 'Small', 'medium' => 'Medium', 'large' => 'Large'],
    ];
    $form['icon']['text'] = [
      '#type' => 'select',
      '#title' => $this->t('Button Text'),
      '#default_value' => $config->get('text'),
      '#options' => [
        'signin_with' => 'Sign in with Google',
        'signup_with' => 'Sign up with Google',
        'continue_with' => 'Continue with Google',
        'signin' => 'Sign In',
      ],
    ];
    $form['icon']['shape'] = [
      '#type' => 'select',
      '#title' => $this->t('Button Shape'),
      '#default_value' => $config->get('shape'),
      '#options' => [
        'rectangular' => 'Rectangular',
        'pill' => 'Pill',
        'circle' => 'Circle',
        'square' => 'Square',
      ],
    ];
    $form['icon']['logo_alignment'] = [
      '#type' => 'select',
      '#title' => $this->t('Button Logo Alignment'),
      '#default_value' => $config->get('logo_alignment'),
      '#options' => [
        'left' => 'Left',
        'center' => 'Center',
      ],
    ];
    $form['icon']['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Width'),
      '#default_value' => $config->get('width'),
      '#description' => $this->t('Width of the button or icon in pixels'),
    ];
    $form['icon']['show_on_login_form'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show on Login Form'),
      '#default_value' => $config->get('show_on_login_form'),
      '#description' => $this->t('Whether to show on the login by default'),
    ];

    $form['icon']['locale'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Locale'),
      '#default_value' => $config->get('locale'),
      '#description' => $this->t("The pre-set locale of the button text. If it's not set, the browser's default locale"),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit Common Admin Settings.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('googlelogin.icon.settings')
      ->set('type', $values['type'])
      ->set('theme', $values['theme'])
      ->set('size', $values['size'])
      ->set('text', $values['text'])
      ->set('shape', $values['shape'])
      ->set('logo_alignment', $values['logo_alignment'])
      ->set('width', $values['width'])
      ->set('locale', $values['locale'])
      ->set('show_on_login_form', $values['show_on_login_form'])
      ->save();

    $this->messenger()->addMessage($this->t('Icon Settings are updated'));
  }

}
