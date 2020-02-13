<?php

namespace Drupal\questions\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Test Question edit forms.
 *
 * @ingroup questions
 */
class TestQuestionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\questions\Entity\TestQuestion */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    $form['field_id']['#disabled'] = TRUE;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity = parent::validateForm($form, $form_state);

    $min = $form_state->getValue('field_min');
    $max = $form_state->getValue('field_max');
    if ($min && $max) {
      $min = (int) $min[0]['value'];
      $max = (int) $max[0]['value'];
      if ($min > $max) {
        $form_state->setError(
          $form['field_min'],
          $this->t('Minimum cannot be bigger than maximum.')
        );
      }
    }

    $type = $entity->get('type')->getValue();
    if ($type['0']['target_id'] == 'associate') {
      $form_state = $this->validateAssosicate($form, $form_state);
    }
    elseif ($type['0']['target_id'] == 'order') {
      $form_state = $this->validateOrder($form, $form_state);
    }
    elseif ($type['0']['target_id'] == 'checkbox') {
      $form_state = $this->validateCheckBox($form, $form_state);
    }
    elseif ($type['0']['target_id'] == 'slider') {
      $form_state = $this->validateSlider($form, $form_state);
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function validateAssosicate(array &$form, FormStateInterface $form_state) {
    $min = (int) $form_state->getValue('field_min')[0]['value'];
    $pairs = $form_state->getValue('field_associate_answers');
    unset($pairs['add_more']);
    if (count($pairs) < $min) {
      $form_state->setError(
        $form['field_associate_answers'],
        $this->t('You have to have at least @count pairs(s).', ['@count' => $min])
      );
    }

    return $form_state;
  }

  /**
   * {@inheritdoc}
   */
  public function validateOrder(array &$form, FormStateInterface $form_state) {
    $min = (int) $form_state->getValue('field_min')[0]['value'];
    $options = $form_state->getValue('field_order_answers_correct');
    unset($options['add_more']);
    if (count($options) < $min) {
      $form_state->setError(
        $form['field_order_answers_correct'],
        $this->t('You have to have at least correct @count answer(s).', ['@count' => $min])
      );
    }

    return $form_state;
  }

  /**
   * {@inheritdoc}
   */
  public function validateCheckBox(array &$form, FormStateInterface $form_state) {
    $min = (int) $form_state->getValue('field_min')[0]['value'];
    $options = $form_state->getValue('field_checkbox_answers');
    unset($options['add_more']);
    if (count($options) < $min) {
      $form_state->setError(
        $form['field_checkbox_answers'],
        $this->t('You have to have at least @count answer(s).', ['@count' => $min])
      );
    }

    return $form_state;
  }

  /**
   * {@inheritdoc}
   */
  public function validateSlider(array &$form, FormStateInterface $form_state) {
    $min = (int) $form_state->getValue('field_start')[0]['value'];
    $max = (int) $form_state->getValue('field_end')[0]['value'];
    $steps = (int) $form_state->getValue('field_steps')[0]['value'];
    $answer = (int) $form_state->getValue('field_slider_answer')[0]['value'];

    if ($min > $max) {
      $form_state->setError(
        $form['field_start'],
        $this->t('Start cannot be bigger than end.')
      );
    }

    if ($min + $steps > $max) {
      $form_state->setError(
        $form['field_steps'],
        $this->t('Slider step cannot be bigger than maximum.')
      );
    }

    if ($answer > $max || $answer < $min) {
      $form_state->setError(
        $form['field_slider_answer'],
        $this->t('Slider answer must be between start and end.')
      );
    }

    return $form_state;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Test Question.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Test Question.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.test_question.collection', ['test_question' => $entity->id()]);
  }

}
