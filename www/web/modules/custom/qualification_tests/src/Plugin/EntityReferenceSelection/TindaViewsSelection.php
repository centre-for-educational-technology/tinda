<?php

namespace Drupal\qualification_tests\Plugin\EntityReferenceSelection;

use Drupal\views\Plugin\EntityReferenceSelection\ViewsSelection;

/**
 * Plugin override of the 'selection' entity_reference.
 *
 * @EntityReferenceSelection(
 *   id = "qualification_tests",
 *   label = @Translation("Tinda Qualification test: Filter by an entity reference view"),
 *   base_plugin_label = @Translation("Views: Filter by an entity reference view"),
 *   group = "qualification_tests",
 *   weight = 0
 * )
 */
class TindaViewsSelection extends ViewsSelection {

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableEntities(array $ids) {
    $display_name = $this->getConfiguration()['view']['display_name'];

    // Get qualification standard argument from form,
    // This view is based on dynamic value from form.
    $argument_id = \Drupal::request()->get('qualification_standard_id');
    $arguments = $this->getConfiguration()['view']['arguments'];

    $arguments[] = $argument_id;
    $result = [];
    if ($this->initializeView(NULL, 'CONTAINS', 0, $ids)) {
      // Get the results.
      $entities = $this->view->executeDisplay($display_name, $arguments);
      $result = array_keys($entities);
    }
    return $result;
  }

}
