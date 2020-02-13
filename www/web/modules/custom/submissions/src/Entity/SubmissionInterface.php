<?php

namespace Drupal\submissions\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Submission entities.
 *
 * @ingroup submissions
 */
interface SubmissionInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Submission name.
   *
   * @return string
   *   Name of the Submission.
   */
  public function getName();

  /**
   * Sets the Submission name.
   *
   * @param string $name
   *   The Submission name.
   *
   * @return \Drupal\submissions\Entity\SubmissionInterface
   *   The called Submission entity.
   */
  public function setName($name);

  /**
   * Gets the Submission creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Submission.
   */
  public function getCreatedTime();

  /**
   * Sets the Submission creation timestamp.
   *
   * @param int $timestamp
   *   The Submission creation timestamp.
   *
   * @return \Drupal\submissions\Entity\SubmissionInterface
   *   The called Submission entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Submission published status indicator.
   *
   * Unpublished Submission are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Submission is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Submission.
   *
   * @param bool $published
   *   TRUE to set this Submission to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\submissions\Entity\SubmissionInterface
   *   The called Submission entity.
   */
  public function setPublished($published);

  /**
   * Gets the Submission revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Submission revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\submissions\Entity\SubmissionInterface
   *   The called Submission entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Submission revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Submission revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\submissions\Entity\SubmissionInterface
   *   The called Submission entity.
   */
  public function setRevisionUserId($uid);

  /**
   * Checks if the user is marked as the Filler of this submission.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to check against.
   *
   * @return bool
   *   Indicates whether the user filled out this Submission or not.
   */
  public function isFiller(AccountInterface $account) : bool;

}
