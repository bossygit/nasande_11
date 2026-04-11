<?php

namespace Drupal\napay;

use Drupal\commerce\AvailabilityCheckerInterface;
use Drupal\commerce\Context;
use Drupal\commerce\PurchasableEntityInterface;

/**
 * Class VariationAvailabilityChecker.
 */
class VariationAvailabilityChecker implements AvailabilityCheckerInterface {

  /**
   * {@inheritdoc}
   */
  public function applies(PurchasableEntityInterface $entity) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function check(PurchasableEntityInterface $entity, $quantity, Context $context) {
    if ($entity->field_stock->value <= 0) {
      return FALSE;
    }
    return TRUE;
  }

}