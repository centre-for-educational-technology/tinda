<?php

namespace Drupal\qualification_tests\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Qualification Test entities.
 */
class QualificationTestViewsData extends EntityViewsData {

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
