<?php

namespace Drupal\zip_import\Builder;


use Drupal\paragraphs\Entity\Paragraph;
use Drupal\questions\Entity\TestQuestion;

/**
 * Class Order.
 *
 * @package Drupal\zip_import\Builder
 */
class Order implements QuestionBuilderInterface {
  use EntityHelper;
  const TYPE = 'order';

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
    $this->entity->set('name', $data->orderInteraction->prompt->p);
    $this->entity->set('field_id', $id[0]);
    $this->entity->set('field_min', $data->orderInteraction->attributes()->minChoices[0]);
    $this->entity->set('field_max', $data->orderInteraction->attributes()->maxChoices[0]);

    $options =  $this->createOptions($data->orderInteraction->simpleChoice, $answer);
    $this->entity->set('field_order_answers_correct', $options['correct']);
    $this->entity->set('field_order_answers_wrong', $options['wrong']);
    return TRUE;
  }

  /**
   * Create answers for the order questions.
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
    $correct = [];
    $wrong = [];

    usort($options, function ($a, $b) {
      return $a['weight'] <=> $b['weight'];
    });

    foreach ($options as $option) {
      if ($option['weight']) {
        $correct[] = ['value' => $option['value']];
      } else {
        $wrong[] = ['value' => $option['value']];
      }

    }

    return ['correct' => $correct, 'wrong' => $wrong];
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
    $weight = 0;
    if ($answers) {
      foreach ($answers['value'] as $answer) {
        $formatted_answers[(string) $answer] = ++$weight;
      }
    }

    return $formatted_answers;
  }

  /**
   * Find the order answers.
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
    $answers = $this->getAnswers((array) $answers->correctResponse);

    $options = [];
    foreach ($data as $item) {
      $item = (array) $item;
      $options[] = ['value' => ((array) $item)[0], 'weight' => ($answers[$item['@attributes']['identifier']] ?? '')];
    }

    return $options;
  }

}
