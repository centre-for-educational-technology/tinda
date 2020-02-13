<?php

namespace Drupal\qualification_test_rounds\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting a Qualification Test Round revision.
 *
 * @ingroup qualification_test_rounds
 */
class QualificationTestRoundRevisionDeleteForm extends ConfirmFormBase {


  /**
   * The Qualification Test Round revision.
   *
   * @var \Drupal\qualification_test_rounds\Entity\QualificationTestRoundInterface
   */
  protected $revision;

  /**
   * The Qualification Test Round storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $QualificationTestRoundStorage;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new QualificationTestRoundRevisionDeleteForm.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(EntityStorageInterface $entity_storage, Connection $connection) {
    $this->QualificationTestRoundStorage = $entity_storage;
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $entity_manager = $container->get('entity.manager');
    return new static(
      $entity_manager->getStorage('qualification_test_round'),
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'qualification_test_round_revision_delete_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure you want to delete the revision from %revision-date?', ['%revision-date' => format_date($this->revision->getRevisionCreationTime())]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('entity.qualification_test_round.version_history', ['qualification_test_round' => $this->revision->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $qualification_test_round_revision = NULL) {
    $this->revision = $this->QualificationTestRoundStorage->loadRevision($qualification_test_round_revision);
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->QualificationTestRoundStorage->deleteRevision($this->revision->getRevisionId());

    $this->logger('content')->notice('Qualification Test Round: deleted %title revision %revision.', ['%title' => $this->revision->label(), '%revision' => $this->revision->getRevisionId()]);
    drupal_set_message(t('Revision from %revision-date of Qualification Test Round %title has been deleted.', ['%revision-date' => format_date($this->revision->getRevisionCreationTime()), '%title' => $this->revision->label()]));
    $form_state->setRedirect(
      'entity.qualification_test_round.canonical',
       ['qualification_test_round' => $this->revision->id()]
    );
    if ($this->connection->query('SELECT COUNT(DISTINCT vid) FROM {qualification_test_round_field_revision} WHERE id = :id', [':id' => $this->revision->id()])->fetchField() > 1) {
      $form_state->setRedirect(
        'entity.qualification_test_round.version_history',
         ['qualification_test_round' => $this->revision->id()]
      );
    }
  }

}
