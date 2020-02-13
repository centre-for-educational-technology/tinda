<?php

namespace Drupal\qualification_test_rounds;

use Drupal\Core\Entity\ContentEntityInterface;

trait EntityTrait
{

  /**
   * @param ContentEntityInterface $entity
   * @return ContentEntityInterface
   */
  protected function getEntityByLanguage(ContentEntityInterface $entity, $lang = null) {
    if (!$lang) {
      $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
    }

    if ($entity->hasTranslation($lang)) {
      $entity = $entity->getTranslation($lang);
    }

    return $entity;
  }

}