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

    #$arguments[] = $argument_id;

    #############################################
    /* Functionality to allow addition of questions to test which are associated with children term of qualification standard
    Author: Pankaj Chejara
    */
    $context_filter = $argument_id;

   $child_tids = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('qualification_standards', $argument_id, NULL, TRUE);
   foreach ($child_tids as $id => $term)
   {
     $context_filter = $context_filter.'+'.$term->id();
   }

   $arguments[] = $context_filter;

    ###########################################



    $result = [];
    if ($this->initializeView(NULL, 'CONTAINS', 0, $ids)) {
      // Get the results.
      $entities = $this->view->executeDisplay($display_name, $arguments);
      $result = array_keys($entities);
    }
    return $result;
  }

}
