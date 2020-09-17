<?php

namespace Drupal\zip_import\Builder;

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\questions\Entity\TestQuestion;

/**
 * Class Checkbox.
 *
 * @package Drupal\zip_import\Builder
 */
class Checkbox implements QuestionBuilderInterface {
  use EntityHelper;

  const TYPE = 'checkbox';

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
    $this->entity->set('name', $data->choiceInteraction->prompt);
    $this->entity->set('field_id', $id[0]);
    $this->entity->set('field_max', $data->choiceInteraction->attributes()->maxChoices[0]);
    $this->entity->set('field_min', $data->choiceInteraction->attributes()->minChoices[0]);

    $this->removeOldOptions();
    $this->entity->set('field_checkbox_answers', $this->createOptions($data->choiceInteraction->simpleChoice, $answer));
    return TRUE;

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
    }, $this->entity->get('field_checkbox_answers')->getValue());

    $entities = $storage_handler->loadMultiple($ids);
    if ($entities) {
      $storage_handler->delete($entities);
    }
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
        'type' => 'checkbox_selections',
        'field_options' => $option['value'],
        'field_correct' => $option['correct']
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

    $correct_answers = $this->getAnswers((array) $answers->correctResponse);

    $options = [];
    foreach ($data as $item) {
      $item = (array) $item;
      $correct = FALSE;
      if (in_array($item['@attributes']['identifier'], $correct_answers)) {
        $correct = TRUE;
      }

      $options[] = ['value' => $item[0], 'correct' => $correct];
    }

    return $options;
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
      if (is_array($answers['value'])) {
        foreach ($answers['value'] as $answer) {
          $formatted_answers[] = (string) $answer;
        }
      }
      else {
        $formatted_answers[] = (string) $answers['value'];
      }

    }

    return $formatted_answers;
  }

}
