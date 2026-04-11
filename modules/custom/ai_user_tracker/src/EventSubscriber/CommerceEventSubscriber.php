<?php

namespace Drupal\ai_user_tracker\EventSubscriber;

use Drupal;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\HttpFoundation\RequestStack;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CommerceEventSubscriber implements EventSubscriberInterface {

  protected CurrentRouteMatch $currentRouteMatch;
  protected LoggerInterface $logger;
  protected RequestStack $requestStack;

  public function __construct(CurrentRouteMatch $currentRouteMatch, RequestStack $requestStack, LoggerInterface $logger) {
    $this->currentRouteMatch = $currentRouteMatch;
    $this->requestStack = $requestStack;
    $this->logger = $logger;
  }

  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::REQUEST => ['onRequest', 0],
    ];
  }

  public function onRequest(RequestEvent $event): void {
    if (!$event->isMainRequest()) {
      return;
    }

    $request = $event->getRequest();
    $path = $request->getPathInfo() ?? '';
    $routeName = (string) $this->currentRouteMatch->getRouteName();

    try {
      // Log product page views (server-side) with product_id when on product canonical route.
      if ($routeName === 'entity.commerce_product.canonical') {
        $productId = $this->extractProductId();
        if ($productId !== NULL) {
          $this->logBehavior('product_view', [
            'route' => $routeName,
            'path' => $path,
            'product_id' => $productId,
          ]);
        }
      }

      // Detect add to cart routes precisely.
      $addToCartRoutes = [
        'commerce_cart.add',
        'commerce_cart.page',
      ];
      if (in_array($routeName, $addToCartRoutes, TRUE) || preg_match('#/cart/add$#', $path)) {
        $meta = [
          'route' => $routeName,
          'path' => $path,
        ];
        $productId = $this->extractProductId();
        if ($productId !== NULL) {
          $meta['product_id'] = $productId;
        }
        // Try to enrich with price/currency from variation.
        $enriched = $this->enrichVariationMeta($productId);
        $meta += $enriched;
        $this->logBehavior('cart_add', $meta);
      }

      // Detect cart remove.
      if (strpos($routeName, 'commerce_cart.remove') === 0 || preg_match('#/cart/remove#', $path)) {
        $meta = [
          'route' => $routeName,
          'path' => $path,
        ];
        $productId = $this->extractProductId();
        if ($productId !== NULL) {
          $meta['product_id'] = $productId;
        }
        $enriched = $this->enrichVariationMeta($productId);
        $meta += $enriched;
        $this->logBehavior('cart_remove', $meta);
      }

      // Detect checkout start and complete based on known routes.
      $checkoutStartRoutes = [
        'commerce_checkout.form',
        'commerce_checkout.form_default',
      ];
      $checkoutCompleteRoutes = [
        'commerce_checkout.complete',
      ];

      if (in_array($routeName, $checkoutCompleteRoutes, TRUE) || preg_match('#/checkout/[^/]+/complete$#', $path)) {
        $meta = [
          'route' => $routeName,
          'path' => $path,
        ];
        $orderId = $this->extractOrderId();
        if ($orderId !== NULL) {
          $meta['order_id'] = $orderId;
        }
        $this->logBehavior('checkout_complete', $meta);
      }
      elseif (in_array($routeName, $checkoutStartRoutes, TRUE) || preg_match('#/checkout(?!/[^/]+/complete)#', $path)) {
        // Any checkout route that is not the complete page.
        $meta = [
          'route' => $routeName,
          'path' => $path,
        ];
        $orderId = $this->extractOrderId();
        if ($orderId !== NULL) {
          $meta['order_id'] = $orderId;
        }
        $this->logBehavior('checkout_start', $meta);
      }
    }
    catch (\Throwable $e) {
      $this->logger->warning('AI tracker subscriber error: @msg', ['@msg' => $e->getMessage()]);
    }
  }

  protected function logBehavior(string $eventType, array $metadata = []): void {
    $connection = Drupal::database();
    $uid = (int) Drupal::currentUser()->id();
    $pagePath = Drupal::request()->getPathInfo() ?? '';
    $productId = isset($metadata['product_id']) ? (int) $metadata['product_id'] : NULL;

    $connection->insert('ai_user_behavior')
      ->fields([
        'uid' => $uid,
        'session_id' => NULL,
        'page_path' => substr((string) $pagePath, 0, 512),
        'event_type' => substr((string) $eventType, 0, 64),
        'metadata' => !empty($metadata) ? json_encode($metadata) : NULL,
        'created' => time(),
        'product_id' => $productId,
      ])->execute();
  }

  protected function extractProductId(): ?int {
    try {
      // Try from route parameters (product or variation).
      $route = $this->currentRouteMatch;
      if ($product = $route->getParameter('commerce_product')) {
        return (int) (is_object($product) && method_exists($product, 'id') ? $product->id() : (int) $product);
      }
      if ($variation = $route->getParameter('commerce_product_variation')) {
        // Try to get the parent product ID if possible.
        if (is_object($variation) && method_exists($variation, 'getProductId')) {
          return (int) $variation->getProductId();
        }
      }
      // Fallback: parse from query or path id param.
      $req = $this->requestStack->getCurrentRequest();
      if ($req) {
        $pid = $req->query->get('product_id');
        if ($pid !== NULL) return (int) $pid;
      }
    } catch (\Throwable $e) {}
    return NULL;
  }

  protected function extractOrderId(): ?int {
    try {
      $route = $this->currentRouteMatch;
      if ($order = $route->getParameter('commerce_order')) {
        return (int) (is_object($order) && method_exists($order, 'id') ? $order->id() : (int) $order);
      }
      // Checkout completes often contain order id in the URL.
      $req = $this->requestStack->getCurrentRequest();
      if ($req) {
        $oid = $req->attributes->get('commerce_order');
        if ($oid !== NULL) return (int) $oid;
      }
    } catch (\Throwable $e) {}
    return NULL;
  }

  protected function enrichVariationMeta(?int $productId): array {
    $meta = [];
    try {
      if ($productId) {
        // Attempt to derive from current route purchasable entity if available.
        $variation = $this->currentRouteMatch->getParameter('commerce_product_variation');
        if ($variation && is_object($variation) && method_exists($variation, 'getPrice')) {
          $price = $variation->getPrice();
          $meta['price'] = method_exists($price, 'getNumber') ? $price->getNumber() : NULL;
          $meta['currency'] = method_exists($price, 'getCurrencyCode') ? $price->getCurrencyCode() : NULL;
          $meta['item_name'] = method_exists($variation, 'label') ? $variation->label() : NULL;
        }
      }
    } catch (\Throwable $e) {}
    return array_filter($meta, static function($v) { return $v !== NULL && $v !== ''; });
  }
}


