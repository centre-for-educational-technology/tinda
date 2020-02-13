<?php

namespace Drupal\questions\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Test Question entity.
 *
 * @ingroup questions
 *
 * @ContentEntityType(
 *   id = "test_question",
 *   label = @Translation("Test Question"),
 *   bundle_label = @Translation("Test Question type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\questions\TestQuestionListBuilder",
 *     "views_data" = "Drupal\questions\Entity\TestQuestionViewsData",
 *     "translation" = "Drupal\questions\TestQuestionTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\questions\Form\TestQuestionForm",
 *       "add" = "Drupal\questions\Form\TestQuestionForm",
 *       "edit" = "Drupal\questions\Form\TestQuestionForm",
 *       "delete" = "Drupal\questions\Form\TestQuestionDeleteForm",
 *     },
 *     "access" = "Drupal\questions\TestQuestionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\questions\TestQuestionHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "test_question",
 *   data_table = "test_question_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer test question entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/tests/test_question/{test_question}",
 *     "add-page" = "/admin/tests/test_question/add",
 *     "add-form" = "/admin/tests/test_question/add/{test_question_type}",
 *     "edit-form" = "/admin/tests/test_question/{test_question}/edit",
 *     "delete-form" = "/admin/tests/test_question/{test_question}/delete",
 *     "collection" = "/admin/tests/test_question",
 *   },
 *   bundle_entity_type = "test_question_type",
 *   field_ui_base_route = "entity.test_question_type.edit_form"
 * )
 */
class TestQuestion extends ContentEntityBase implements TestQuestionInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Test Question entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Test Question entity.'))
      ->setSettings([
        'max_length' => 522,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setTranslatable(TRUE)
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Test Question is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    // Create check if question is clone or not,
    // because we need to clone every question for saving purposes.
    $fields['is_clone'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Is clone'))
      ->setDescription(t('A boolean indicating whether the Test Question is clone or real one.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'region' => 'hidden',
        'weight' => -3,
      ]);

    return $fields;
  }

  /**
   * Sets entity as clone.
   */
  public function setIsClone() {
    $this->set('is_clone', TRUE);
  }

  /**
   * Checks if entity is clone or not.
   *
   * @return mixed
   *   Indicatior showing if entity is clone.
   */
  public function isClone() : bool {
    $field = $this->get('is_clone')->getValue();
    if (!$field) {
      return FALSE;
    }
    return (int) $field[0]['value'];
  }

  /**
   * {@inheritdoc}
   */
  public function getHelpText() : ?string {
    return $this->get('field_help_text')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setHelpText($helpText) : TestQuestionInterface {
    $this->set('field_help_text', $helpText);
    return $this;
  }

}
