<?php

namespace Drupal\qualification_test_rounds\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Qualification Test Round entities.
 *
 * @ingroup qualification_test_rounds
 */
interface QualificationTestRoundInterface extends ContentEntityInterface, RevisionLogInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Qualification Test Round name.
   *
   * @return string
   *   Name of the Qualification Test Round.
   */
  public function getName();

  /**
   * Sets the Qualification Test Round name.
   *
   * @param string $name
   *   The Qualification Test Round name.
   *
   * @return \Drupal\qualification_test_rounds\Entity\QualificationTestRoundInterface
   *   The called Qualification Test Round entity.
   */
  public function setName($name);

  /**
   * Gets the Qualification Test Round creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Qualification Test Round.
   */
  public function getCreatedTime();

  /**
   * Sets the Qualification Test Round creation timestamp.
   *
   * @param int $timestamp
   *   The Qualification Test Round creation timestamp.
   *
   * @return \Drupal\qualification_test_rounds\Entity\QualificationTestRoundInterface
   *   The called Qualification Test Round entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Qualification Test Round published status indicator.
   *
   * Unpublished Qualification Test Round are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Qualification Test Round is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Qualification Test Round.
   *
   * @param bool $published
   *   TRUE to set this Qualification Test Round to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\qualification_test_rounds\Entity\QualificationTestRoundInterface
   *   The called Qualification Test Round entity.
   */
  public function setPublished($published);

  /**
   * Gets the Qualification Test Round revision creation timestamp.
   *
   * @return int
   *   The UNIX timestamp of when this revision was created.
   */
  public function getRevisionCreationTime();

  /**
   * Sets the Qualification Test Round revision creation timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of when this revision was created.
   *
   * @return \Drupal\qualification_test_rounds\Entity\QualificationTestRoundInterface
   *   The called Qualification Test Round entity.
   */
  public function setRevisionCreationTime($timestamp);

  /**
   * Gets the Qualification Test Round revision author.
   *
   * @return \Drupal\user\UserInterface
   *   The user entity for the revision author.
   */
  public function getRevisionUser();

  /**
   * Sets the Qualification Test Round revision author.
   *
   * @param int $uid
   *   The user ID of the revision author.
   *
   * @return \Drupal\qualification_test_rounds\Entity\QualificationTestRoundInterface
   *   The called Qualification Test Round entity.
   */
  public function setRevisionUserId($uid);

  /**
   * Get All questions for test.
   *
   * @return array
   *   List on questions.
   */
  public function getAllQuestions() : array;

  /**
   * Get count of the questions.
   *
   * @return int
   *   Count of the questions.
   */
  public function countQuestions() : int;

  /**
   * The max possible score for the test.
   *
   * @return int
   *   The score.
   */
  public function getMaxPossibleScore() : int;

  /**
   * Determines if the Test Round is visible in current time.
   *
   * @return bool
   *   Boolean indicating if Start and End Time are within current time.
   */
  public function isVisibleByStartAndEndDates() : bool;

}
