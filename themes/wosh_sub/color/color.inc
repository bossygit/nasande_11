<?php

/**
 * @file
 * Lists available colors and color schemes for the theme.
 */

$info = array(
  // Available colors and color labels used in theme.
  'fields' => array(
    'color' => t('Color Scheme'),
	'bg' => t('Body Background'),
	'page_bg' => t('Page Background'),
	'text' => t('Text Color'),
  ),
  // Pre-defined color schemes.
  'schemes' => array(
    'default' => array(
      'title' => t('Default'),
      'colors' => array(
        'color' => '#e42555',
		'bg' => '#f9f9f9',
		'page_bg' => '#ffffff',
		'text' => '#555555',
      ),
    ),
    'yellow' => array(
      'title' => t('Yellow'),
      'colors' => array(
        'color' => '#ffff00',
		'page_bg' => '#ffffff',
		'bg' => '#ffffff',
		'text' => '#555555',
      ),
    ),

    'blue' => array(
      'title' => t('Blue'),
      'colors' => array(
        'color' => '#0082ff',
		'page_bg' => '#ffffff',
		'bg' => '#ffffff',
		'text' => '#555555',
      ),
    ),
	
    'green' => array(
      'title' => t('Green'),
      'colors' => array(
        'color' => '#1b880b',
		'page_bg' => '#ffffff',
		'bg' => '#ffffff',
		'text' => '#555555',
      ),
    ),
	
    'pink' => array(
      'title' => t('Pinky'),
      'colors' => array(
        'color' => '#eea695',
		'page_bg' => '#ffffff',
		'bg' => '#ffffff',
		'text' => '#555555',
      ),
    ),
	
    'orange' => array(
      'title' => t('Orange'),
      'colors' => array(
        'color' => '#ffa500',
		'page_bg' => '#ffffff',
		'bg' => '#ffffff',
		'text' => '#555555',
      ),
    ),
	
    'orangered' => array(
      'title' => t('Orange Red'),
      'colors' => array(
        'color' => '#d1430d',
		'page_bg' => '#ffffff',
		'bg' => '#ffffff',
		'text' => '#555555',
      ),
    ),

    'red' => array(
      'title' => t('Red'),
      'colors' => array(
        'color' => '#ff0000',
		'page_bg' => '#ffffff',
		'bg' => '#ffffff',
		'text' => '#555555',
      ),
    ),

    'brown' => array(
      'title' => t('Brown'),
      'colors' => array(
        'color' => '#5b4035',
		'page_bg' => '#ffffff',
		'bg' => '#ffffff',
		'text' => '#555555',
      ),
    ),		
	
  ),

  // CSS files (excluding @import) to rewrite with new color scheme.
  'css' => array(
    'assets/css/colors.css',
  ),

  // Files to copy.
  'copy' => array(
    'logo.svg',
  ),

  // Gradient definitions.
  'gradients' => array(
    array(
      // (x, y, width, height).
      'dimension' => array(0, 0, 0, 0),
      // Direction of gradient ('vertical' or 'horizontal').
      'direction' => 'vertical',
      // Keys of colors to use for the gradient.
      'colors' => array('top', 'bottom'),
    ),
  ),

  // Preview files.
  'preview_library' => 'wosh/color.preview',
  'preview_html' => 'color/preview.html',

  // Attachments.
  '#attached' => [
    'drupalSettings' => [
      'color' => [
        // Put the logo path into JavaScript for the live preview.
        'logo' => theme_get_setting('logo.url', 'wosh'),
      ],
    ],
  ],
);
