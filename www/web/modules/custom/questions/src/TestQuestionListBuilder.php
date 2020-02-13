<?php

namespace Drupal\questions;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\questions\Form\QuestionStandardInlineForm;
use Drupal\taxonomy\Entity\Term;

/**
 * Defines a class to build a listing of Test Question entities.
 *
 * @ingroup questions
 */
class TestQuestionListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery();

    $query->condition('is_clone', FALSE);

    $header = $this->buildHeader();
    $query->tableSort($header);
    $query->sort('id', 'DESC');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#attached']['library'] = ['core/drupal.autocomplete'];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Test Question ID');
    $header['name'] = [
      'data' => $this->t('Name'),
      'field' => 'name',
      'specifier' => 'name',
    ];
    $header['type'] = [
      'data' => $this->t('Type'),
      'field' => 'type',
      'specifier' => 'type',
    ];
    $header['lang'] = [
      'data' => $this->t('Language'),
      'field' => 'langcode',
      'specifier' => 'langcode',
    ];
    $header['author'] = [
      'data' => $this->t('Author'),
      'field' => 'author',
      'specifier' => 'author',
    ];
    $header['status'] = [
      'data' => $this->t('Published'),
      'field' => 'status',
      'specifier' => 'status',
    ];
    $header['changed'] = [
      'data' => $this->t('Last changed'),
      'field' => 'changed',
      'specifier' => 'changed',
    ];
    $header['standard'] = $this->t('Qualification standard');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\questions\Entity\TestQuestion */
    $test_question_id = $entity->id();

    $row['id'] = $test_question_id;
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.test_question.edit_form',
      ['test_question' => $test_question_id]
    );

    $bundle = $entity->bundle();
    $bundle = $bundle === 'textentry' ? 'Text Entry' : ucfirst($bundle);

    $row['type'] = $bundle;

    $language = $entity->language();
    $row['lang'] = $language->getName();

    $row['author'] = $entity->getOwner()->getAccountName();

    $row['status'] = $entity->isPublished() ? $this->t('Yes') : $this->t('No');

    $row['changed'] = \Drupal::service('date.formatter')->format(
      $entity->getChangedTime(), 'custom', 'd.m.Y H:i:s'
    );

    $question_standard = $entity->get('field_qualification_standard')->entity;
    $question_standard = $question_standard instanceof Term ? $question_standard : NULL;

    // @todo: These should be injected, but who has the time...
    $form = new QuestionStandardInlineForm($test_question_id);
    $question_standard_form = \Drupal::formBuilder()->getForm(
      $form,
      [
        'standard_term' => $question_standard,
      ]
    );

    $row['standard'] = \Drupal::service('renderer')->renderRoot($question_standard_form);

    return $row + parent::buildRow($entity);

  }

}
