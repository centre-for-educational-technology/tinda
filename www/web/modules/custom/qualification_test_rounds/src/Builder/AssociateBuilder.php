<?php

namespace Drupal\qualification_test_rounds\Builder;

use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\qualification_test_rounds\EntityTrait;
use Drupal\questions\Entity\TestQuestionInterface;

/**
 * Class AssociateBuilder.
 *
 * @package Drupal\qualification_test_rounds\Builder
 */
class AssociateBuilder implements FormElementBuilderInterface {

  use BuilderHelpers;
  use EntityTrait;

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
  public function build(TestQuestionInterface $testQuestion, int $id, $default_value = NULL) : array {
    $is_required_question = self::isRequired($testQuestion, $id);
    $required_pairs = $testQuestion->get('field_min')->getValue()[0]['value'];
    $max = $testQuestion->get('field_max')->getValue()[0]['value'];

    $pairs = [];
    for ($i = 1; $i <= $max; $i++) {

      $pairs['pair_' . $i] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['row c-pair-row'],
        ],
        [
          '#type' => 'markup',
          '#markup' => '<div class="c-pair-row__connector"></div>',
        ],
        [
          '#type' => 'select',
          '#required' => $is_required_question && $required_pairs > 0,
          '#options' => $this->getSelectOptions($testQuestion->get('field_associate_answers')->getValue()),
          '#empty_option' => t('Select'),
          '#default_value' => isset($default_value['widget']['pair_' . $i]) ? $default_value['widget']['pair_' . $i][1] : NULL,
          '#attributes' => [
            'class' => ['form-control m-input'],
          ],
        ],
        [
          '#type' => 'select',
          '#required' => $is_required_question && $required_pairs > 0,
          '#options' => $this->getSelectOptions($testQuestion->get('field_associate_answers')->getValue()),
          '#empty_option' => t('Select'),
          '#default_value' => isset($default_value['widget']['pair_' . $i]) ? $default_value['widget']['pair_' . $i][2] : NULL,
          '#attributes' => [
            'class' => ['form-control m-input'],
          ],
        ],
      ];

      if ($required_pairs > 0) {
        $required_pairs--;
      }

    }

    return [
      '#type' => 'container',
      'title' => [
        '#markup' => $testQuestion->getName(),
      ],
      'description' => [
        '#markup' => '<div><span class="m-form__help">' . $testQuestion->getHelpText() . '</span></div>',
      ],
      'widget' => $pairs,
      '#required' => $is_required_question,
    ];
  }

  /**
   * Get Question formatted answers.
   *
   * @param array $options
   *   Question options to choose from.
   *
   * @return array
   *   Formatted question possible answers
   */
  protected function getSelectOptions(array $options) : array {
    $form_options = [];
    foreach ($options as $option) {
      $option = $this->getEntityByLanguage(Paragraph::load($option['target_id']));
      if ($option) {
        $values = $option->get('field_associate_options')->getValue();
        foreach ($values as $key => $value) {
          $output = $value['value'];
          $form_options[$output] = $output;
        }
      }
    }
    return $form_options;
  }

  /**
   * Format answer. Format is: first_of_pair-second_of_pair.
   *
   * @param array $answer
   *   List of answers.
   *
   * @return array
   *   Formatted answer.
   */
  public static function formatAnswer(array $answer) : array {
    $formatted_answer = [];
    foreach ($answer['widget'] as $pair) {
      if ($pair[1] && $pair[2]) {
        $formatted_answer[] = ['value' => $pair[1] . '-' . $pair[2]];
      }
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
      $answer = $answer['widget'];
      $min = $testQuestion->get('field_min')->getValue()[0]['value'];
      $max = $testQuestion->get('field_max')->getValue()[0]['value'];

      $pairs = 0;
      foreach ($answer as $pair) {
        if ($pair[1] && $pair[2]) {
          $pairs++;
        }
        if (($pair[1] && !$pair[2]) || (!$pair[1] && $pair[2])) {
          $form_state->setError($element,
            t('@name You need to select from both sides to connect a pair!',
              [
                '@name' => $element['title']['#markup'],
              ]
            )
          );
        }
      }
      if ($pairs < (int) $min) {
        $form_state->setError($element,
          t('@name You need at least @count pair(s)!',
            [
              '@name' => $element['title']['#markup'],
              '@count' => $min,
            ]
          )
        );
      }
      elseif ($pairs > (int) $max) {
        $form_state->setError($element,
          t('@name You can have maximum of @count pair(s)!',
            [
              '@name' => $element['title']['#markup'],
              '@count' => $max,
            ]
          )
        );
      }
    }

    return $form_state;
  }

}
