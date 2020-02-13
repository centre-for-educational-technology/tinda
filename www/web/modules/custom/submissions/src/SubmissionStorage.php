<?php

namespace Drupal\submissions;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\submissions\Entity\SubmissionInterface;

/**
 * Defines the storage handler class for Submission entities.
 *
 * This extends the base storage class, adding required special handling for
 * Submission entities.
 *
 * @ingroup submissions
 */
class SubmissionStorage extends SqlContentEntityStorage implements SubmissionStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(SubmissionInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {submission_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {submission_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(SubmissionInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {submission_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('submission_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
