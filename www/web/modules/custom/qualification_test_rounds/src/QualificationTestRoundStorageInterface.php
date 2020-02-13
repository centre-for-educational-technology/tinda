<?php

namespace Drupal\qualification_test_rounds;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface QualificationTestRoundStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Qualification Test Round revision IDs for a specific Qualification Test Round.
   *
   * @param \Drupal\qualification_test_rounds\Entity\QualificationTestRoundInterface $entity
   *   The Qualification Test Round entity.
   *
   * @return int[]
   *   Qualification Test Round revision IDs (in ascending order).
   */
  public function revisionIds(QualificationTestRoundInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Qualification Test Round author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Qualification Test Round revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\qualification_test_rounds\Entity\QualificationTestRoundInterface $entity
   *   The Qualification Test Round entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(QualificationTestRoundInterface $entity);

  /**
   * Unsets the language for all Qualification Test Round with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
