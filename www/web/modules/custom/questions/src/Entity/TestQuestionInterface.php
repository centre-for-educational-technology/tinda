<?php

namespace Drupal\questions\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Test Question entities.
 *
 * @ingroup questions
 */
interface TestQuestionInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the Test Question name.
   *
   * @return string
   *   Name of the Test Question.
   */
  public function getName();

  /**
   * Sets the Test Question name.
   *
   * @param string $name
   *   The Test Question name.
   *
   * @return \Drupal\questions\Entity\TestQuestionInterface
   *   The called Test Question entity.
   */
  public function setName($name);

  /**
   * Gets the Test Question creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Test Question.
   */
  public function getCreatedTime();

  /**
   * Sets the Test Question creation timestamp.
   *
   * @param int $timestamp
   *   The Test Question creation timestamp.
   *
   * @return \Drupal\questions\Entity\TestQuestionInterface
   *   The called Test Question entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Test Question published status indicator.
   *
   * Unpublished Test Question are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Test Question is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Test Question.
   *
   * @param bool $published
   *   TRUE to set this Test Question to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\questions\Entity\TestQuestionInterface
   *   The called Test Question entity.
   */
  public function setPublished($published);

  /**
   * Sets entity as clone.
   */
  public function setIsClone();

  /**
   * Checks if entity is clone or not.
   *
   * @return mixed
   *   Indicatior showing if entity is clone.
   */
  public function isClone() : bool;

  /**
   * Gets the Question help text.
   *
   * @return null|string
   *   Help text value.
   */
  public function getHelpText() : ?string;

  /**
   * Sets the Question help text..
   *
   * @param string $helpText
   *   New help text value.
   *
   * @return \Drupal\questions\Entity\TestQuestionInterface
   *   Returns the object.
   */
  public function setHelpText($helpText) : TestQuestionInterface;

}
