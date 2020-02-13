<?php

namespace Drupal\qualification_test_rounds\Builder;

use Drupal\Core\Form\FormStateInterface;
use Drupal\questions\Entity\TestQuestionInterface;

/**
 * Class RangeBuilder.
 *
 * @package Drupal\qualification_test_rounds\Builder
 */
class RangeBuilder implements FormElementBuilderInterface {

  use BuilderHelpers;

  const FORM_TYPE = 'range';

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
    return [
      '#type' => self::FORM_TYPE,
      '#name' => "question[$id]",
      '#description' => $testQuestion->getHelpText(),
      '#description_display' => 'before',
      '#min' => $testQuestion->get('field_start')->getValue()[0]['value'],
      '#max' => $testQuestion->get('field_end')->getValue()[0]['value'],
      '#title' => $testQuestion->getName(),
      '#attributes' => [
        'step' => $testQuestion->get('field_steps')->getValue()[0]['value'],
      ],
      '#default_value' => $default_value,
      '#required' => self::isRequired($testQuestion, $id),
    ];
  }

  /**
   * Format answer.
   *
   * @param array $answer
   *   Typed answer.
   *
   * @return array
   *   Formatted answer.
   */
  public static function formatAnswer(array $answer) : array {
    $answer = reset($answer);
    return [['value' => $answer]];
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

    return $form_state;
  }

}
