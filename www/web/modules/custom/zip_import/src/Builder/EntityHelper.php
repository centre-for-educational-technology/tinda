<?php

namespace Drupal\zip_import\Builder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\questions\Entity\TestQuestion;

/**
 * Simple helpers for entity builder.
 *
 * Trait EntityHelper.
 *
 * @package Drupal\zip_import\Builder
 */
trait EntityHelper {

  /**
   * Get entity.
   *
   * @return \Drupal\questions\Entity\TestQuestion
   *   built entity.
   */
  public function getEntity(): TestQuestion {
    return $this->entity;
  }

  /**
   * Find Entity.
   *
   * @param string $id
   *   Entity identifier.
   *
   * @return \Drupal\Core\Entity\EntityInterface|mixed
   *   Found entity or null
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function findEntity(string $id) : ?EntityInterface {
    $node = \Drupal::entityTypeManager()
      ->getStorage('test_question')
      ->loadByProperties(['field_id' => $id]);

    $node = reset($node);
    $type = NULL;
    if (!$node) {
      return NULL;
    }

    $type = $node->get('type')->getValue();
    if ($node && $type && $type[0]['target_id'] == self::TYPE) {
      return $node;
    }
    else {
      throw new \Exception('Cannot change question type!');
    }
  }

}
