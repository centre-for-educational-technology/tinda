<?php

namespace Drupal\zip_import\Builder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\questions\Entity\TestQuestion;

/**
 * Interface QuestionBuilderInterface.
 *
 * @package Drupal\zip_import\Builder
 */
interface QuestionBuilderInterface {

  /**
   * Build the entity.
   *
   * @param \SimpleXMLElement $id
   *   Entity identifier.
   * @param \SimpleXMLElement $data
   *   Entity xml based data.
   * @param string $lang
   *   Entity lang parameter.
   * @param \SimpleXMLElement $answer
   *   Question correct answer.
   */
  public function build(\SimpleXMLElement $id, \SimpleXMLElement $data, string $lang, \SimpleXMLElement $answer);

  /**
   * Get entity.
   *
   * @return \Drupal\questions\Entity\TestQuestion
   *   built entity.
   */
  public function getEntity(): TestQuestion;

  /**
   * Find Entity.
   *
   * @param string $id
   *   Entity identifier.
   *
   * @return \Drupal\Core\Entity\EntityInterface|mixed
   *   Found entity or null.
   */
  public function findEntity(string $id) : ?EntityInterface;

}
