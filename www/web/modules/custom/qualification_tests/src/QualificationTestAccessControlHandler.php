<?php

namespace Drupal\qualification_tests;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Qualification Test entity.
 *
 * @see \Drupal\qualification_tests\Entity\QualificationTest.
 */
class QualificationTestAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\qualification_tests\Entity\QualificationTestInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished qualification test entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published qualification test entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit qualification test entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete qualification test entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add qualification test entities');
  }

}
