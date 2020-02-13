<?php

/**
 * @file
 * Contains qualification_test_round.page.inc.
 *
 * Page callback for Qualification Test Round entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Qualification Test Round templates.
 *
 * Default template: qualification_test_round.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_qualification_test_round(array &$variables) {
  // Fetch QualificationTestRound Entity Object.
  $qualification_test_round = $variables['elements']['#qualification_test_round'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
