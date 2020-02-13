<?php

namespace Drupal\qualification_test_rounds\Builder;

use Drupal\Core\Form\FormStateInterface;
use Drupal\questions\Entity\TestQuestionInterface;

/**
 * Class OrderFieldBuilder.
 *
 * @package Drupal\qualification_test_rounds\Builder
 */
class OrderFieldBuilder implements FormElementBuilderInterface {

  use BuilderHelpers;

  /**
   * Generates a render array from TestQuestion entity.
   *
   * @param \Drupal\questions\Entity\TestQuestionInterface $testQuestion
   *   Entity which will be processed to the question.
   * @param int $id
   *   Id to give to name.
   * @param null $default_value
   *   Default value of the element if exists.
   *
   * @return array
   *   Drupal form render array element.
   */
  public function build(TestQuestionInterface $testQuestion, int $id, $default_value = NULL): array {
    $default_value = is_array($default_value) ? self::removeEmptyValues($default_value) : [];

    return [
      '#type' => 'checkboxes',
      '#theme' => 'form_element_order',
      '#options' => $this->getOptions($testQuestion, $default_value),
      '#title' => $testQuestion->getName(),
      '#description' => $testQuestion->getHelpText(),
      '#description_display' => 'before',
      '#name' => "question[$id]",
      '#default_value' => $default_value,
      '#required' => self::isRequired($testQuestion, $id),
      '#attributes' => [
        'class' => ['order-selection'],
      ],
    ];
  }

  /**
   * Get Question all possible answers and merge them into one array.
   *
   * @param \Drupal\questions\Entity\TestQuestionInterface $testQuestion
   *   Entity of the question.
   * @param array|null $default_value
   *   Stored values of form.
   *
   * @return array
   *   Formatted and merged answers.
   */
  protected function getOptions(TestQuestionInterface $testQuestion, ?array $default_value) : array {
    $options = [];
    $correct = $testQuestion->get('field_order_answers_correct')->getValue();

    if ($default_value) {
      foreach ($default_value as $item) {
        $options[$item] = $item;
      }
    }

    $this->getOnlyValues($correct, $options);

    $wrong = $testQuestion->get('field_order_answers_wrong')->getValue();
    $this->getOnlyValues($wrong, $options);

    return $options;
  }

  /**
   * Get only values of the input.
   *
   * @param array $items
   *   List of elements.
   * @param array $options
   *   Where to add values from items.
   */
  protected function getOnlyValues(array $items, array &$options) {
    foreach ($items as $item) {
      if (!in_array($item['value'], $options)) {
        $options[$item['value']] = $item['value'];
      }
    }
  }

  /**
   * Format answer. Format is just a 1 answer per row.
   *
   * @param array $answer
   *   List of answers.
   *
   * @return array
   *   Formatted answer.
   */
  public static function formatAnswer(array $answer) : array {
    $real_answer = self::removeEmptyValues($answer);

    $formatted_answer = [];
    foreach ($real_answer as $value) {
      $formatted_answer[] = ['value' => $value];
    }
    return $formatted_answer;
  }

  /**
   * Validate question.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param $answer
   *   User answers for the question.
   * @param \Drupal\questions\Entity\TestQuestionInterface $testQuestion
   *   Testquestion entity.
   * @param array $element
   *   Form element array which to validate.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   validated form state.
   */
  public static function validate(FormStateInterface $form_state, $answer, TestQuestionInterface $testQuestion, array $element) : FormStateInterface {
    $is_required_question = $element['#required'];

    // Only check for values when the question is marked as required.
    if ($is_required_question) {

      $field_min_value = $testQuestion->get('field_min')->getValue();
      $field_max_value = $testQuestion->get('field_max')->getValue();

      $min = !empty($field_min_value) ? (int) $field_min_value[0]['value'] : 0;
      $max = !empty($field_max_value) ? (int) $field_max_value[0]['value'] : 0;

      $real_answer = NULL;

      if (is_array($answer)) {
        $real_answer = self::removeEmptyValues($answer);
      }

      if (count($real_answer) < (int) $min) {
        $form_state->setError($element,
          t('@name You Need at least @count checked!',
            [
              '@name' => $element['#title'],
              '@count' => $min,
            ]
          ));
      }
      elseif (count($real_answer) > (int) $max) {
        $form_state->setError($element,
          t('@name You can have maximum of @count checked!',
            [
              '@name' => $element['#title'],
              '@count' => $max,
            ]
          ));
      }

    }

    return $form_state;
  }

}
