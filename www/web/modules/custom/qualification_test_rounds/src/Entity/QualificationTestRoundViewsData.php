<?php

namespace Drupal\qualification_test_rounds\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Qualification Test Round entities.
 */
class QualificationTestRoundViewsData extends EntityViewsData {

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
