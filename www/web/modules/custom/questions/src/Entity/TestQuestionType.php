<?php

namespace Drupal\questions\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Test Question type entity.
 *
 * @ConfigEntityType(
 *   id = "test_question_type",
 *   label = @Translation("Test Question type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\questions\TestQuestionTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\questions\Form\TestQuestionTypeForm",
 *       "edit" = "Drupal\questions\Form\TestQuestionTypeForm",
 *       "delete" = "Drupal\questions\Form\TestQuestionTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\questions\TestQuestionTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "test_question_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "test_question",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/tests/test_question_type/{test_question_type}",
 *     "add-form" = "/admin/tests/test_question_type/add",
 *     "edit-form" = "/admin/tests/test_question_type/{test_question_type}/edit",
 *     "delete-form" = "/admin/tests/test_question_type/{test_question_type}/delete",
 *     "collection" = "/admin/tests/test_question_type"
 *   }
 * )
 */
class TestQuestionType extends ConfigEntityBundleBase implements TestQuestionTypeInterface {

  /**
   * The Test Question type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Test Question type label.
   *
   * @var string
   */
  protected $label;

}
