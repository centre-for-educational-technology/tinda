<?php

namespace Drupal\zip_import\Builder;

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\questions\Entity\TestQuestion;

/**
 * Class Associate.
 *
 * @package Drupal\zip_import\Builder
 */
class Associate implements QuestionBuilderInterface {
  use EntityHelper;

  const TYPE = 'associate';

  /**
   * Test entity declaration.
   *
   * @var \Drupal\questions\Entity\TestQuestion
   */
  protected $entity;

  /**
   * Build the entity.
   *
   * @param \SimpleXMLElement $id
   *   Entity identifier.
   * @param \SimpleXMLElement $data
   *   Entity xml based data.
   * @param string $lang
   *   Entity lang parameter.
   * @param \SimpleXMLElement $answer
   *   Question correct answer.
   *
   * @return bool
   *   If is succesful build.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function build(\SimpleXMLElement $id, \SimpleXMLElement $data, string $lang, \SimpleXMLElement $answer) {
    try {
      $node = $this->findEntity((string) $id);
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addError($e->getMessage());
      return FALSE;
    }

    if ($node) {
      $this->entity = $node;
    }
    else {
      $this->entity = TestQuestion::create(['type' => self::TYPE]);
    }

    $this->entity->set('langcode', $lang);
    $this->entity->set('name', $data->associateInteraction->prompt->p);
    $this->entity->set('field_id', $id[0]);
    $this->entity->set('field_max', $data->associateInteraction->attributes()->maxAssociations[0]);
    $this->entity->set('field_min', $data->associateInteraction->attributes()->minAssociations[0]);

    $this->removeOldOptions();
    $this->entity->set('field_associate_answers', $this->createOptions($data->associateInteraction->simpleAssociableChoice, $answer));

    return TRUE;
  }

  /**
   * Create answers for the checkbox questions.
   *
   * @param \SimpleXMLElement $data
   *   Entity building data.
   * @param \SimpleXMLElement $answers
   *   Question correct answer.
   *
   * @return array
   *   Created paragraphs ids.
   */
  protected function createOptions(\SimpleXMLElement $data, \SimpleXMLElement $answers) : array {
    $options = $this->getOptions($data, $answers);
    $paragraphs = [];
    foreach ($options as $option) {
      $paragraph = Paragraph::create([
        'type' => 'associate_answers',
        'field_associate_options' => $option,
      ]);
      $paragraph->save();

      $paragraphs[] = ['target_id' => $paragraph->id(), 'target_revision_id' => $paragraph->getRevisionId()];
    }

    return $paragraphs;
  }

  /**
   * Find the checkbox answers.
   *
   * @param \SimpleXMLElement $data
   *   Entity building data.
   * @param \SimpleXMLElement $answers
   *   Question correct answer.
   *
   * @return array
   *   Question answers.
   */
  protected function getOptions(\SimpleXMLElement $data, \SimpleXMLElement $answers) {
    unset($data['@attributes']);
    $choices = (array) $data;
    unset($choices['@attributes']);

    $correct_answers = $this->getAnswers((array) $answers->correctResponse);

    // Replace correct answers identifiers with real values or add new.
    $i = 0;
    foreach ($data as $row => $item) {
      $item = (array) $item;
      foreach ($correct_answers as $key => $answer) {
        $search = array_search($item['@attributes']['identifier'], $answer);
        if (is_numeric($search)) {
          $correct_answers[$key][$search] = (string) $item[0];
          unset($choices[$i]);
        }
      }
      $i++;
    }

    foreach ($choices as $choice) {
      $correct_answers[] = [$choice];
    }

    return $correct_answers;
  }

  /**
   * Get array of correct answers.
   *
   * @param array $answers
   *   XML imported correct answers.
   *
   * @return array
   *   All correct answers.
   */
  protected function getAnswers(array $answers): array {
    $formatted_answers = [];
    if ($answers) {
      foreach ($answers['value'] as $answer) {
        $formatted_answers[] = explode(' ', (string) $answer);
      }
    }

    return $formatted_answers;
  }

  /**
   * Delete old Options for entity.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function removeOldOptions() {
    $storage_handler = \Drupal::entityTypeManager()->getStorage('paragraph');
    // Get ids of paragraphs.
    $ids = array_map(function ($item) {
      return $item['target_id'];
    }, $this->entity->get('field_associate_answers')->getValue());

    $entities = $storage_handler->loadMultiple($ids);
    if ($entities) {
      $storage_handler->delete($entities);
    }
  }

}
