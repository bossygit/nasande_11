<?php

namespace Drupal\googlelogin\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a Google OAuth Login Block.
 *
 * @Block(
 *   id = "google_oauth_login_block",
 *   admin_label = @Translation("Google OAuth Login"),
 *   category = @Translation("Blocks")
 * )
 */
class GoogleOAuthLoginBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current account.
   *
   * @var Drupal\Core\Session\AccountInterface
   */
  protected  $account;

  /**
   * Overrides \Drupal\Core\Block::__construct().
   *
   * Overrides the construction of context aware plugins to allow for
   * unvalidated constructor based injection of contexts.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The curren logged in user
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $account) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->account = $account;
  }

  /**
   * Create function.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container object.
   * @param array $configuration
   *   The config array.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $output = [];
    if ($this->account->isAuthenticated()) {
      $output = [
        '#markup' => $this->account->getAccountName(),
        '#cache' => [
          'max-age' => 0,
        ],
      ];
    }
    else {
      googlelogin_login_button_code($output);
    }

    return $output;
  }

}
