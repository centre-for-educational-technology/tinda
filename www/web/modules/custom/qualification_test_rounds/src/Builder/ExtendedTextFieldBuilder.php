<?php

namespace Drupal\qualification_test_rounds\Builder;

use Drupal\Core\Form\FormStateInterface;
use Drupal\questions\Entity\TestQuestionInterface;

/**
 * Class TextFieldBuilder.
 *
 * @package Drupal\qualification_test_rounds\Builder
 */
class ExtendedTextFieldBuilder implements FormElementBuilderInterface {

  use BuilderHelpers;

  const FORM_TYPE = 'textfield';

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
      '#title' => $testQuestion->getName(),
      '#description' => $testQuestion->getHelpText(),
      '#description_display' => 'before',
      '#required' => self::isRequired($testQuestion, $id),
      '#name' => "question[$id]",
      '#default_value' => $default_value,
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
