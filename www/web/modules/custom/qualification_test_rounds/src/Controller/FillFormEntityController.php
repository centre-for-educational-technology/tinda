<?php

namespace Drupal\qualification_test_rounds\Controller;

use Drupal\Core\Entity\Controller\EntityController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Class FillFormEntityController.
 *
 * @package Drupal\qualification_test_rounds\Controller
 */
class FillFormEntityController extends EntityController {

  /**
   * @inheritdoc
   */
  public function editTitle(RouteMatchInterface $route_match, EntityInterface $_entity = NULL) {
    if ($entity = $this->doGetEntity($route_match, $_entity)) {
      return $entity->label();
    }
  }

}
