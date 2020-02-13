<?php

namespace Drupal\tinda_users;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

// @note: You only need Reference, if you want to change service arguments.
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the csv importer parser service.
 */
class TindaUsersServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides csv_importer class to set out own parser for user import.
    // Adds entity_type.manager service as an additional argument.
    $definition = $container->getDefinition('csv_importer.parser');
    $definition->setClass('Drupal\tinda_users\Parser')
      ->addArgument(new Reference('entity_type.manager'));

    $definition = $container->getDefinition('plugin.manager.importer');
    $definition->setClass('Drupal\tinda_users\Plugin\ImporterManager');
  }

}
