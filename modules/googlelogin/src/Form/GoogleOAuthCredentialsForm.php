<?php

namespace Drupal\googlelogin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for Social API Google.
 */
class GoogleOAuthCredentialsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_oauth_login_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['googlelogin.settings'];
  }

  /**
   * Build Admin Settings Form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('googlelogin.settings');

    $form['google_oauth_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Google OAuth Settings'),
      '#open' => TRUE,
    ];
    $form['google_oauth_settings']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google OAuth ClientID'),
      '#required' => TRUE,
      '#default_value' => $config->get('client_id') ?? '',
    ];
    $form['google_oauth_settings']['api_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Api Type to be used'),
      '#options' => ['javascript' => $this->t('Javascript Api'), 'html' => 'Html Api'],
      '#description' => 'More about this can be read in the google doc, keep it javascript if you load login form in modal/popup',
      '#default_value' => $config->get('api_type') ?? 'javascript',
    ];
    $is_https = \Drupal::request()->isSecure();
    $callback_url = \Drupal\Core\Url::fromRoute('googlelogin.callback', [], ['https' => $is_https, 'absolute' => TRUE])->toString();
    $form['google_oauth_settings']['redirect_url'] = [
      '#type' => 'markup',
      '#markup' => $this->t('Google OAuth Redirect URL') . '<br/><code>' . $callback_url . '</code><br/>' . $this->t('Redirect URL to be pasted in the google api console.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Build Admin Submit.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('googlelogin.settings')
      ->set('client_id', $values['client_id'])
      ->set('api_type', $values['api_type'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
