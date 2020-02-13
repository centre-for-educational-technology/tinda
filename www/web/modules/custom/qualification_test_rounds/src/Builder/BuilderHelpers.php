<?php

namespace Drupal\qualification_test_rounds\Builder;

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\questions\Entity\TestQuestionInterface;

/**
 * Simple methods to builders.
 *
 * Trait BuilderHelpers.
 *
 * @package Drupal\qualification_test_rounds\Builder
 */
trait BuilderHelpers {

  /**
   * Filter the values list.
   *
   * @param array $array
   *   List of elements.
   *
   * @return array
   *   Filtered list.
   */
  public static function removeEmptyValues(array $array) : array {
    return array_filter($array, function ($item) {
      return $item !== 0 && $item !== NULL;
    });
  }

  /**
   * Determines if a field is required or not.
   *
   * @param \Drupal\questions\Entity\TestQuestionInterface $testQuestion
   *   The entity containing the checkbox information.
   * @param int $question_settings_paragraph_id
   *   The test_question Paragraph containing extra settings for the question.
   *
   * @return bool
   *   Indicating if the question is required or not.
   */
  public static function isRequired(TestQuestionInterface $testQuestion, int $question_settings_paragraph_id) : bool {
    $question_settings = Paragraph::load($question_settings_paragraph_id);
    $required = $question_settings->get('field_required')->getValue();
    $is_required = !empty($required) ? (bool) $required[0]['value'] : TRUE;

    // There can be a situation where no min/max were set for the field,
    // but it's set as required. In that case the user could never
    // progress from this field as it wouldn't pass validation.
    if ($is_required && $testQuestion->hasField('field_min') && $testQuestion->hasField('field_max')) {
      $field_min_value = $testQuestion->get('field_min')->getValue();
      $field_max_value = $testQuestion->get('field_max')->getValue();

      $min = !empty($field_min_value) ? (int) $field_min_value[0]['value'] : 0;
      $max = !empty($field_max_value) ? (int) $field_max_value[0]['value'] : 0;

      if ($min === 0 && $max === 0) {
        return FALSE;
      }

    }
    return $is_required;
  }

}
