<?php

namespace Drupal\qualification_test_rounds;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Qualification Test Round entity.
 *
 * @see \Drupal\qualification_test_rounds\Entity\QualificationTestRound.
 */
class QualificationTestRoundAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\qualification_test_rounds\Entity\QualificationTestRoundInterface $entity */
    switch ($operation) {
      case 'view':
      case 'fill':
        if (!$entity->isPublished() || !$entity->isVisibleByStartAndEndDates()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished qualification test round entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published qualification test round entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit qualification test round entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete qualification test round entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add qualification test round entities');
  }

}
