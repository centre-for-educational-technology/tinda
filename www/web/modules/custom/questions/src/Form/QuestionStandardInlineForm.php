<?php

namespace Drupal\questions\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\questions\Entity\TestQuestion;
use Drupal\taxonomy\Entity\Term;

/**
 * Class QuestionStandardInlineForm.
 */
class QuestionStandardInlineForm extends FormBase {

  /**
   * The ID of the Test Question that the Standard is being applied to.
   *
   * @var int
   */
  protected $testQuestionId;

  /**
   * QuestionStandardInlineForm constructor.
   *
   * @param int $testQuestionId
   *   The ID of the Test Question that the Standard is being applied to.
   */
  public function __construct($testQuestionId) {
    $this->testQuestionId = $testQuestionId;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'question_standard_inline_form_' . $this->testQuestionId;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $parameters = []) {

    $form['test_question_id'] = [
      '#type' => 'hidden',
      '#required' => TRUE,
      '#value' => $this->testQuestionId,
    ];

    $form['options'] = [
      '#type' => 'fieldset',
      '#attributes' => ['class' => ['container-inline']],
    ];

    $form['options']['qualification_standard'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'taxonomy_term',
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'include_anonymous' => FALSE,
        'target_bundles' => ['qualification_standards'],
      ],
      '#weight' => 0,
      '#default_value' => (array_key_exists('standard_term', $parameters) && $parameters['standard_term'] instanceof Term
        ? $parameters['standard_term']
        : NULL
      ),
    ];

    $form['options']['submit'] = [
      '#type' => 'submit',
      '#value'  => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    parent::validateForm($form, $form_state);

    if ($form_state->isValueEmpty('test_question_id')) {
      $form_state->setErrorByName('test_question_id', $this->t('The Test Question entity is not chosen'));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $test_question_id = $form_state->getValue('test_question_id');

    /** @var \Drupal\questions\Entity\TestQuestion $test_question */
    $test_question = TestQuestion::load($test_question_id);

    $test_question->set('field_qualification_standard', $form_state->getValue('qualification_standard'));

    $test_question->save();

    \Drupal::messenger()->addMessage(t('Successfully updated the Qualification Standard'));

  }

}
