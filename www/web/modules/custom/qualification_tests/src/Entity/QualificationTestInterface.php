<?php

namespace Drupal\qualification_tests\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Qualification Test entities.
 *
 * @ingroup qualification_tests
 */
interface QualificationTestInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Qualification Test name.
   *
   * @return string
   *   Name of the Qualification Test.
   */
  public function getName();

  /**
   * Sets the Qualification Test name.
   *
   * @param string $name
   *   The Qualification Test name.
   *
   * @return \Drupal\qualification_tests\Entity\QualificationTestInterface
   *   The called Qualification Test entity.
   */
  public function setName($name);

  /**
   * Gets the Qualification Test creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Qualification Test.
   */
  public function getCreatedTime();

  /**
   * Sets the Qualification Test creation timestamp.
   *
   * @param int $timestamp
   *   The Qualification Test creation timestamp.
   *
   * @return \Drupal\qualification_tests\Entity\QualificationTestInterface
   *   The called Qualification Test entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Qualification Test published status indicator.
   *
   * Unpublished Qualification Test are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Qualification Test is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Qualification Test.
   *
   * @param bool $published
   *   TRUE to set this Qualification Test to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\qualification_tests\Entity\QualificationTestInterface
   *   The called Qualification Test entity.
   */
  public function setPublished($published);

}
