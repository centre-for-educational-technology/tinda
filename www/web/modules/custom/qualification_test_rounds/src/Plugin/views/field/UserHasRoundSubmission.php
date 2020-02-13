<?php

namespace Drupal\qualification_test_rounds\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to flag the node type.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("user_has_round_submission")
 */
class UserHasRoundSubmission extends FieldPluginBase {

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field,
    // as this field is not backed by a regular Drupal field.
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    $qualification_test_round = $values->_entity;
    $current_user_id = \Drupal::currentUser()->id();
    $current_langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();

    $query = \Drupal::entityQuery('submission');

    $query->condition('langcode', $current_langcode);
    $query->condition('field_filler', $current_user_id);
    $query->condition('field_test_round', $qualification_test_round->id());

    $query->range(0, 1);

    $count = $query->execute();

    $completed = !empty($count) ? $this->t('Completed') : '';

    return [
      '#markup' => $completed,
      '#cache' => [
        'tags' => ['submission_list'],
      ],
    ];
  }

}
