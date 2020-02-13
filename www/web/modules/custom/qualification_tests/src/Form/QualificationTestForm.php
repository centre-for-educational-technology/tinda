<?php

namespace Drupal\qualification_tests\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Qualification Test edit forms.
 *
 * @ingroup qualification_tests
 */
class QualificationTestForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\qualification_tests\Entity\QualificationTest */
    $form['qualification_standard_id']['#type'] = 'hidden';
    if ($row = $this->entity->get('field_qualification_standard')->getValue()) {
      if ($row[0] && !$form_state->getUserInput()) {
        $form['qualification_standard_id']['#value'] = $row[0]['target_id'];
        $form_state->setUserInput(['qualification_standard_id' => $row[0]['target_id']]);
      }
    }

    $form = parent::buildForm($form, $form_state);

    $form['field_qualification_standard']['widget'][0]['target_id']['#ajax'] = [
      'callback' => '::ajaxCallback',
      'event' => 'change',
      'wrapper' => 'qualification-test-add',
      'progress' => [
        'type' => 'throbber',
        'message' => t('Verifying entry...'),
      ],
    ];

    $row_1 = $this->entity->get('field_test_questions')->getValue();
    $standard = $this->entity->get('field_qualification_standard')->getValue();
    if ((!isset($row_1[0]) || (isset($row_1[0]) && !$row_1[0])) && !$standard[0]) {
      // Remove last section, so in default we don't have any open.
      $last_section = $form['field_test_questions']['widget']['#max_delta'];
      unset($form['field_test_questions']['widget'][$last_section]);
      $form['field_test_questions']['widget']['#max_delta']--;
    }

    $form['#prefix'] = '<div id="qualification-test-add">';
    $form['#suffix'] = '</div>';

    $element = $form_state->getTriggeringElement();

    if ($element['#parents'][0] == 'field_qualification_standard') {
      // Paragraphs hold their state in shitty way,
      // so we have to restate 4 different values in Form storage
      // and need to remove values from form too.
      $this->removeExtraSections($form, $form_state);
      $form_state->set(['field_storage', '#parents', '#fields', 'field_test_questions', 'items_count'], 1);
      $form_state->set(['field_storage', '#parents', '#fields', 'field_test_questions', 'real_item_count'], 1);
      $form_state->set(['field_storage', '#parents', '#fields', 'field_test_questions', 'paragraphs'], []);
      $form_state->set(['field_storage', '#parents', '#fields', 'field_test_questions', 'original_deltas'], []);
    }

    return $form;
  }

  /**
   * Simple ajax callback.
   *
   * @param array $form
   *   Drupal form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return mixed
   *   form.
   */
  public function ajaxCallback(array $form, FormStateInterface $form_state) {
    $form['qualification_standard_id']['#value'] = $form_state->getValue('field_qualification_standard')[0]['target_id'];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()->addMessage($this->t('Created the %label Qualification Test.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        \Drupal::messenger()->addMessage($this->t('Saved the %label Qualification Test.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('view.qualification_test_list.qualification_test_collection');
  }

  /**
   * Remove sections.
   *
   * @param array $form
   *   From elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function removeExtraSections(array &$form, FormStateInterface $form_state) {
    $elements_count = $form['field_test_questions']['widget']['#max_delta'];
    for ($i = 0; $i <= $elements_count; $i++) {
      unset($form['field_test_questions']['widget'][$i]);
      $form['field_test_questions']['widget']['#max_delta']--;
    }
    $form_state->set(['field_storage', '#parents', 'field_test_questions'], []);
    $this->entity->set('field_test_questions', []);
  }

}
