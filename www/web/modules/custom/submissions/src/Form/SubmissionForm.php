<?php

namespace Drupal\submissions\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Form controller for Submission edit forms.
 *
 * @ingroup submissions
 */
class SubmissionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\submissions\Entity\Submission */
    $form = parent::buildForm($form, $form_state);

    if (!$this->entity->isNew()) {
      $form['new_revision'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Create new revision'),
        '#default_value' => FALSE,
        '#weight' => 10,
      ];
    }

    $this->formatSections($form, $form_state);

    return $form;
  }

  /**
   * Format submission answers.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Drupal form state.
   */
  protected function formatSections(array &$form, FormStateInterface $form_state) {
    $count = $form['field_submission_answers']['widget']['#max_delta'];

    for ($x = 0; $x <= $count; $x++) {
      $target = $form['field_submission_answers']['widget'][$x]['subform']['field_question']['widget'][0]['target_id']['#default_value'];

      if (!empty($target)) {

        $question_count = $form['field_submission_answers']['widget'][$x]['subform']['field_filler_answers']['widget']['#max_delta'];
        if ($target->get('type')->getValue()[0]['target_id'] == 'upload') {
          for ($i = 0; $i <= $question_count; $i++) {
            $link = $form['field_submission_answers']['widget'][$x]['subform']['field_filler_answers']['widget'][$i]['value']['#default_value'];
            if ($link !== NULL) {
              $url = file_create_url($link);
              $form['field_submission_answers']['widget'][$x]['subform']['field_filler_answers']['widget'][$i]['value']['#attributes'] = ['style' => 'display:none;'];

              $form['field_submission_answers']['widget'][$x]['subform']['field_filler_answers']['widget'][$i]['#markup'] =
                Link::fromTextAndUrl($url, Url::fromUri($url, ['attributes' => ['target' => '_blank']]))
                  ->toString();
            }
          }
        }

        unset($form['field_submission_answers']['widget'][$x]['subform']['field_filler_answers']['widget'][$count]);
        $form['field_submission_answers']['widget'][$x]['subform']['field_filler_answers']['widget']['#max_delta']--;

      }

    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    // Save as a new revision if requested to do so.
    if (!$form_state->isValueEmpty('new_revision') && $form_state->getValue('new_revision') != FALSE) {
      $entity->setNewRevision();

      // If a new revision is created, save the current user as revision author.
      $entity->setRevisionCreationTime(\Drupal::time()->getRequestTime());
      $entity->setRevisionUserId(\Drupal::currentUser()->id());
    }
    else {
      $entity->setNewRevision(FALSE);
    }

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()->addMessage($this->t('Created the %label Submission.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        \Drupal::messenger()->addMessage($this->t('Saved the %label Submission.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('view.submissions.submission_collection');
  }

}
