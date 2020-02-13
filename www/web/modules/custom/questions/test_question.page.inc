<?php

/**
 * @file
 * Contains test_question.page.inc.
 *
 * Page callback for Test Question entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Test Question templates.
 *
 * Default template: test_question.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_test_question(array &$variables) {
  // Fetch TestQuestion Entity Object.
  $test_question = $variables['elements']['#test_question'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
