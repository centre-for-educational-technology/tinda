<?php

namespace Drupal\qualification_test_rounds\Builder;

use Drupal\Core\Form\FormStateInterface;
use Drupal\questions\Entity\TestQuestionInterface;

/**
 * Interface FormElementBuilderInterface.
 *
 * @package Drupal\qualification_test_rounds\Builder
 */
interface FormElementBuilderInterface {

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
  public function build(TestQuestionInterface $testQuestion, int $id, $default_value = NULL) : array;

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
  public static function validate(FormStateInterface $form_state, $answer, TestQuestionInterface $testQuestion, array $element) : FormStateInterface;

  /**
   * Format answer.
   *
   * @param array $answer
   *   Typed answer.
   *
   * @return array
   *   Formatted answer.
   */
  public static function formatAnswer(array $answer) : array;

}
