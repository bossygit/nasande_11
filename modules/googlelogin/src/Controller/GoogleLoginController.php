<?php

namespace Drupal\googlelogin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Google Client Callback Controller.
 *
 * @package Drupal\googlelogin\Controller
 */
class GoogleLoginController extends ControllerBase {

  /**
   * The tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * Callback constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private')
    );
  }

  /**
   * Callback URL for Google API Auth.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return array|\Symfony\Component\HttpFoundation\Response
   *   Return markup for the page.
   */
  public function callback(Request $request) {
    $tempStore = $this->tempStoreFactory->get('googlelogin');
    $csrf_token = $request->get('g_csrf_token');
    $is_error = FALSE;
    if ($csrf_token) {
      $jwt = new \Firebase\JWT\JWT;
      $jwt::$leeway = 360;
      $client = new \Google_Client(['jwt' => $jwt]);
      $client->setClientId($request->get('client_id'));
      $payload = $client->verifyIdToken($request->get('credential'));
      if ($payload) {
        $destination = FALSE;
        if ($tempStore->get('state_destination')) {
          $destination = $tempStore->get('state_destination');
        }
        $tempStore->delete('state_destination');
        googlelogin_user_exist($payload);
        if ($destination) {
          if (!str_starts_with($destination, '/') && !str_starts_with($destination, 'http')) {
            $destination = '/' . $destination;
          }
          return new RedirectResponse(Url::fromUserInput($destination)->toString());
        }
      }
      else {
        $is_error = TRUE;
      }
    }
    else {
      $is_error = TRUE;
    }
    if ($is_error) {
      if ($request->get('error') == 'access_denied') {
        $this->messenger()->addError($this->t('You denied access so account is not authenticated'));
      }
      else {
        $this->messenger()->addError($this->t('Something caused error in authentication.'));
      }

      if ($tempStore->get('state_destination')) {
        $destination = $tempStore->get('state_destination');
        if (!str_starts_with($destination, '/') && !str_starts_with($destination, 'http')) {
          $destination = '/' . $destination;
        }
        $tempStore->delete('state_destination');
        return new RedirectResponse(Url::fromUserInput($destination)->toString());
      }
    }
    return $this->redirect('<front>');
  }

}
