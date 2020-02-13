<?php

namespace Drupal\qualification_test_rounds;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\qualification_test_rounds\Entity\QualificationTestRoundInterface;

/**
 * Defines the storage handler class for Qualification Test Round entities.
 *
 * This extends the base storage class, adding required special handling for
 * Qualification Test Round entities.
 *
 * @ingroup qualification_test_rounds
 */
class QualificationTestRoundStorage extends SqlContentEntityStorage implements QualificationTestRoundStorageInterface {

  /**
   * {@inheritdoc}
   */
  public function revisionIds(QualificationTestRoundInterface $entity) {
    return $this->database->query(
      'SELECT vid FROM {qualification_test_round_revision} WHERE id=:id ORDER BY vid',
      [':id' => $entity->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function userRevisionIds(AccountInterface $account) {
    return $this->database->query(
      'SELECT vid FROM {qualification_test_round_field_revision} WHERE uid = :uid ORDER BY vid',
      [':uid' => $account->id()]
    )->fetchCol();
  }

  /**
   * {@inheritdoc}
   */
  public function countDefaultLanguageRevisions(QualificationTestRoundInterface $entity) {
    return $this->database->query('SELECT COUNT(*) FROM {qualification_test_round_field_revision} WHERE id = :id AND default_langcode = 1', [':id' => $entity->id()])
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function clearRevisionsLanguage(LanguageInterface $language) {
    return $this->database->update('qualification_test_round_revision')
      ->fields(['langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED])
      ->condition('langcode', $language->getId())
      ->execute();
  }

}
