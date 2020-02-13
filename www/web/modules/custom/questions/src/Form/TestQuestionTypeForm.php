<?php

namespace Drupal\questions\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TestQuestionTypeForm.
 */
class TestQuestionTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $test_question_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $test_question_type->label(),
      '#description' => $this->t("Label for the Test Question type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $test_question_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\questions\Entity\TestQuestionType::load',
      ],
      '#disabled' => !$test_question_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $test_question_type = $this->entity;
    $status = $test_question_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Test Question type.', [
          '%label' => $test_question_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Test Question type.', [
          '%label' => $test_question_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($test_question_type->toUrl('collection'));
  }

}
