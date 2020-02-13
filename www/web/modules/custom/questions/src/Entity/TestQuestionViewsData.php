<?php

namespace Drupal\questions\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Test Question entities.
 */
class TestQuestionViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.

    return $data;
  }

}
