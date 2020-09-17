<?php

namespace Drupal\zip_import\Builder;

use Drupal\questions\Entity\TestQuestion;

/**
 * Class TextEntry.
 *
 * @package Drupal\zip_import\Builder
 */
class ExtendedText implements QuestionBuilderInterface {

  use EntityHelper;
  const TYPE = 'extended_text';

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
   */
  public function build(\SimpleXMLElement $id, \SimpleXMLElement $data, string $lang, \SimpleXMLElement $answer) {
    try {
      $node = $this->findEntity((string) $id);
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addMessage($e->getMessage());
      return FALSE;
    }

    if ($node) {
      $this->entity = $node;
    }
    else {
      $this->entity = TestQuestion::create(['type' => self::TYPE]);
    }

    $this->entity->set('langcode', $lang);



    $this->entity->set('name', $data->extendedTextInteraction->prompt);
    $this->entity->set('field_id', $id[0]);
    #$this->entity->set('field_answer', (string) $answer->correctResponse->value);
    return TRUE;
  }

}
