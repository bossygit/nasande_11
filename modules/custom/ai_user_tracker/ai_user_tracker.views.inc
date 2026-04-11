<?php

/**
 * @file
 * Views integration for ai_user_tracker.
 */

/**
 * Implements hook_views_data().
 */
function ai_user_tracker_views_data() {
  $data = [];

  $data['ai_user_behavior']['table'] = [
    'group' => t('AI User Behavior'),
    'provider' => 'ai_user_tracker',
    'base' => [
      'field' => 'id',
      'title' => t('AI User Behavior'),
      'help' => t('Behavioral events captured for AI.'),
    ],
  ];

  $data['ai_user_behavior']['id'] = [
    'title' => t('ID'),
    'help' => t('Event ID'),
    'field' => [ 'id' => 'numeric' ],
    'sort' => [ 'id' => 'standard' ],
  ];

  $data['ai_user_behavior']['event_type'] = [
    'title' => t('Event type'),
    'help' => t('Type of event'),
    'field' => [ 'id' => 'standard' ],
    'filter' => [ 'id' => 'string' ],
  ];

  $data['ai_user_behavior']['product_id'] = [
    'title' => t('Product ID'),
    'help' => t('Commerce product id'),
    'field' => [ 'id' => 'numeric' ],
    'filter' => [ 'id' => 'numeric' ],
    'relationship' => [
      'id' => 'standard',
      'base' => 'commerce_product_field_data',
      'base field' => 'product_id',
      'relationship field' => 'product_id',
      'label' => t('Product'),
    ],
  ];

  $data['ai_user_behavior']['created'] = [
    'title' => t('Created'),
    'help' => t('Event timestamp'),
    'field' => [ 'id' => 'date' ],
    'sort' => [ 'id' => 'date' ],
    'filter' => [ 'id' => 'date' ],
  ];

  return $data;
}


