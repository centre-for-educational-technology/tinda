<?php

namespace Drupal\qualification_test_rounds\Builder;

use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\qualification_test_rounds\EntityTrait;
use Drupal\questions\Entity\TestQuestionInterface;
use Drupal\Core\Render\Markup;
/**
 * Class AssociateBuilder.
 *
 * @package Drupal\qualification_test_rounds\Builder
 */
class MatchBuilder implements FormElementBuilderInterface {

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

    $columns = $this->getCols($testQuestion->get('field_column_options')->getValue());
    $rows = $this->getRows($testQuestion->get('field_row_options')->getValue());

    $options = [];




    // We need to know which checkbox elements need to be checked.
    // Simply using the #default_value on checkboxes resulted in Drupal
    // comparing the checkbox name as value and wrong elements being checked.
    foreach ($options as $key => $name) {
      $element[$key] = [
        '#value' => in_array($name, $default_value),
        '#return_value' => $name,
      ];
    }


// Code for tabular checkboxes: Issue with label

    // Preparing column checkboxes
    $col_options = array();
    $header_options = array();

    $header_options[] = ['#markup' => '<th></th>'];

    foreach($columns as $i => $col) {
      $col_options [] = [
        '#type' => 'checkbox',
        '#title'=> '',
        '#return_value' => $col,
        '#prefix' => '<td>',
        '#suffix' => '</td>',
      ];
      $header_options [] = [
        '#markup' => '<th>'.$col.'</th>',
      ];
    }



    $pairs = [];

    foreach ($rows as $i=>$row) {

      $pairs[$i] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [''],
        ],
        '#prefix' => '<tr>',
        '#suffix' => '</tr>',
        [
          '#markup' => '<td>'.$row.'</td>',

        ],

        $col_options,

      ];

    }

    $pairs  = array_merge($header_options,$pairs);

      return [
        '#type' => 'container',
        'title' => [
          '#markup' => $testQuestion->getName(),
        ],
        'description' => [
          '#markup' => '<div><span class="m-form__help">' . $testQuestion->getHelpText() . '</span></div>',
        ],
        'widget' => $pairs,
        '#required' => self::isRequired($testQuestion, $id),
        '#prefix' => '<table class="table">',
        '#suffix' => '</table>',
      ];



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
  protected function getCols(array $options) : array {
    $form_options = [];
    foreach ($options as $option) {
      $option = $this->getEntityByLanguage(Paragraph::load($option['target_id']));
      if ($option) {
        $value = $option->get('field_match_column')->getValue()[0]['value'];;
        $form_options[$value] = $value;
      }
    }
    return $form_options;
  }

  protected function getRows(array $options) : array {
    $form_options = [];
    foreach ($options as $option) {
      $option = $this->getEntityByLanguage(Paragraph::load($option['target_id']));
      if ($option) {
        $value = $option->get('field_match_row')->getValue()[0]['value'];;
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
  protected function getOption(ParagraphInterface $paragraph) : string {
    return $paragraph->get('field_options')->getValue()[0]['value'];
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
    $real_answer = self::removeEmptyValues($answer);

    $formatted_answer = [];

    // Access answer aray
    foreach ($real_answer['widget'] as $key =>$value) {

      $res = $key.'[';

      $temp_ans = '';


      foreach ($value[1] as $option => $selected){
        #print($option.'='.$selected);
        #print(gettype($option).'='.gettype($selected));
        if(strval($selected) != '0') {
          // replacing whitespace with underscore
          $selected = str_replace(' ', '_', $selected);
          $temp_ans = $temp_ans.$selected.' ';
        }


      }
      $res = $res.$temp_ans.']';

      $formatted_answer[] = ['value' => $res];



    }
    #print_r($formatted_answer);

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
    // Only check for values when the question is marked as required.
    if ($is_required_question) {
      $real_answer = self::removeEmptyValues($answer);
      foreach ($real_answer['widget'] as $key =>$value) {
        $sub_response_count = 0;

        foreach ($value[1] as $option => $selected){

          if(!empty($selected) && strval($selected) != '0') {
            $sub_response_count = $sub_response_count + 1;
          }

        }

        // Check if the user selected one or more than one $options
        if ($sub_response_count == 0){
          $form_state->setError($element,
          t(' You have not selected option for <i> @name </i> Please select one option!',
            [
              '@name' => $key,
            ]
          )
        );

        }
        // Check if the user selected one or more than one $options
        if ($sub_response_count > 1){
          $form_state->setError($element,
          t('You have selected more than one option for @name Please select only one option!',
            [
              '@name' => $key,
            ]
          )
        );

        }





      }

    }

    return $form_state;
  }

}
