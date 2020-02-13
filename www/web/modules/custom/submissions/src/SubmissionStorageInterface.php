<?php

namespace Drupal\submissions;

use Drupal\Core\Entity\ContentEntityStorageInterface;
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
interface SubmissionStorageInterface extends ContentEntityStorageInterface {

  /**
   * Gets a list of Submission revision IDs for a specific Submission.
   *
   * @param \Drupal\submissions\Entity\SubmissionInterface $entity
   *   The Submission entity.
   *
   * @return int[]
   *   Submission revision IDs (in ascending order).
   */
  public function revisionIds(SubmissionInterface $entity);

  /**
   * Gets a list of revision IDs having a given user as Submission author.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user entity.
   *
   * @return int[]
   *   Submission revision IDs (in ascending order).
   */
  public function userRevisionIds(AccountInterface $account);

  /**
   * Counts the number of revisions in the default language.
   *
   * @param \Drupal\submissions\Entity\SubmissionInterface $entity
   *   The Submission entity.
   *
   * @return int
   *   The number of revisions in the default language.
   */
  public function countDefaultLanguageRevisions(SubmissionInterface $entity);

  /**
   * Unsets the language for all Submission with the given language.
   *
   * @param \Drupal\Core\Language\LanguageInterface $language
   *   The language object.
   */
  public function clearRevisionsLanguage(LanguageInterface $language);

}
