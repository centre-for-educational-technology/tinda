<?php

namespace Drupal\qualification_test_rounds\Builder;

use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\qualification_test_rounds\EntityTrait;
use Drupal\questions\Entity\TestQuestionInterface;

/**
 * Class CheckboxesBuilder.
 *
 * @package Drupal\qualification_test_rounds\Builder
 */
class CheckboxesBuilder implements FormElementBuilderInterface {

  use BuilderHelpers;
  use EntityTrait;

  const FORM_TYPE = 'checkboxes';

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

    $options = $this->getCheckboxes($testQuestion->get('field_checkbox_answers')->getValue());

    $element = [
      '#type' => self::FORM_TYPE,
      '#options' => $options,
      '#title' => $testQuestion->getName(),
      '#description' => $testQuestion->getHelpText(),
      '#description_display' => 'before',
      '#required' => self::isRequired($testQuestion, $id),
      '#attributes' => [
        'name' => "question[$id][]",
      ],
      '#default_value' => $default_value,
    ];

    // We need to know which checkbox elements need to be checked.
    // Simply using the #default_value on checkboxes resulted in Drupal
    // comparing the checkbox name as value and wrong elements being checked.
    foreach ($options as $key => $name) {
      $element[$key] = [
        '#value' => in_array($name, $default_value),
        '#return_value' => $name,
      ];
    }

    return $element;
  }

  /**
   * Get Question answer options.
   *
   * @param array $options
   *   Question answer options.
   *
   * @return array
   *   Array of question answers.
   */
  protected function getCheckboxes(array $options) : array {
    $form_options = [];
    foreach ($options as $option) {
      $option = $this->getEntityByLanguage(Paragraph::load($option['target_id']));
      if ($option) {
        $value = $this->getCheckboxOption($option);
        $form_options[$value] = $value;
      }
    }
    return $form_options;
  }

  /**
   * Get Question title.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   Paragraph checkbox_selections and get field value.
   *
   * @return mixed
   *   Question title.
   */
  protected function getCheckboxOption(ParagraphInterface $paragraph) : string {
    return $paragraph->get('field_options')->getValue()[0]['value'];
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
    $field_min_value = $testQuestion->get('field_min')->getValue();
    $field_max_value = $testQuestion->get('field_max')->getValue();

    $min = !empty($field_min_value) ? (int) $field_min_value[0]['value'] : 0;
    $max = !empty($field_max_value) ? (int) $field_max_value[0]['value'] : 0;

    $real_answer = NULL;

    if (is_array($answer)) {
      $real_answer = self::removeEmptyValues($answer);
    }

    if (is_array($real_answer) && count($real_answer) < (int) $min) {
      $form_state->setError($element,
        t('@name You Need at least @count checked!',
          [
            '@name' => $element['#title'],
            '@count' => $min,
          ]
        )
      );
    }
    elseif (is_array($real_answer) && count($real_answer) > (int) $max) {
      $form_state->setError($element,
        t('@name You can have maximum of @count checked!',
          [
            '@name' => $element['#title'],
            '@count' => $max,
          ]
        )
      );
    }

    return $form_state;
  }

}
