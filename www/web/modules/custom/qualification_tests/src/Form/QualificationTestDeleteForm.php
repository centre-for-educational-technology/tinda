<?php

namespace Drupal\qualification_tests\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a form for deleting Qualification Test entities.
 *
 * @ingroup qualification_tests
 */
class QualificationTestDeleteForm extends ContentEntityDeleteForm {

  /**
   * The QualificationTest collection route name.
   *
   * @var string
   */
  protected $collectionRoute = 'view.qualification_test_list.qualification_test_collection';

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();
    return $entity->toUrl('edit-form');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getEntity();
    $message = $this->getDeletionMessage();

    // Make sure that deleting a translation does not delete the whole entity.
    if (!$entity->isDefaultTranslation()) {
      $untranslated_entity = $entity->getUntranslated();
      $untranslated_entity->removeTranslation($entity->language()->getId());
      $untranslated_entity->save();
      $form_state->setRedirect($this->collectionRoute);
    }
    else {
      $entity->delete();
      $form_state->setRedirectUrl($this->getRedirectUrl());
    }

    $this->messenger()->addStatus($message);
    $this->logDeletionMessage();
  }

  /**
   * {@inheritdoc}
   *
   * We need to override this, because we are using views for list builder.
   */
  public function getRedirectUrl() {
    return Url::fromRoute($this->collectionRoute);
  }

}
