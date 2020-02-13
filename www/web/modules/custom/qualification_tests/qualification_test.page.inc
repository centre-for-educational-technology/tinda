<?php

/**
 * @file
 * Contains qualification_test.page.inc.
 *
 * Page callback for Qualification Test entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Qualification Test templates.
 *
 * Default template: qualification_test.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_qualification_test(array &$variables) {
  // Fetch QualificationTest Entity Object.
  $qualification_test = $variables['elements']['#qualification_test'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
