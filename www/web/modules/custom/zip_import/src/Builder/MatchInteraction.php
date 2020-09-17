<?php

namespace Drupal\zip_import\Builder;

use Drupal\paragraphs\Entity\Paragraph;
use Drupal\questions\Entity\TestQuestion;

/**
 * Class Associate.
 *
 * @package Drupal\zip_import\Builder
 */
class MatchInteraction implements QuestionBuilderInterface {
  use EntityHelper;

  const TYPE = 'match_interaction';

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
    $this->entity->set('name', $data->matchInteraction->prompt);
    $this->entity->set('field_id', $id[0]);
    $this->entity->set('field_max', $data->matchInteraction->attributes()->maxAssociations[0]);
    $this->entity->set('field_min', $data->matchInteraction->attributes()->minAssociations[0]);

    $this->removeOldOptions();

    $this->entity->set('field_row_options', $this->getRows($data));
    $this->entity->set('field_column_options', $this->getCols($data));

    //$this->entity->set('field_match_options',$this->createOptions($data,$answer));



    return TRUE;
  }

  public function console_log($output, $with_script_tags = true) {
    $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) . ');';
    if ($with_script_tags) {
        $js_code = '<script>' . $js_code . '</script>';
    }
    echo $js_code;
}



protected function getRows(\SimpleXMLElement $data) : array {
  $rows_data = $data->matchInteraction->simpleMatchSet[1]->simpleAssociableChoice;
  $rows = [];

 // Replace correct answers identifiers with real values or add new.
 $i = 0;

   foreach ($rows_data as $row => $ritem) {
     $row_item = (array)$ritem;

     if (isset($row_item['p']))
     {

       $row = $row_item['p'];
     }
     else {

        $row = $row_item[0];
     }
 $rows[] = $row;
}
  $paragraphs = [];
  foreach ($rows as $row) {
    $paragraph = Paragraph::create([
      'type' => 'match_row_option',
      'field_match_row' => $row,
    ]);
    $paragraph->save();

    $paragraphs[] = ['target_id' => $paragraph->id(), 'target_revision_id' => $paragraph->getRevisionId()];
  }

  return $paragraphs;
}

protected function getCols(\SimpleXMLElement $data) : array {
  $cols_data = $data->matchInteraction->simpleMatchSet[0]->simpleAssociableChoice;
  $cols = [];

 // Replace correct answers identifiers with real values or add new.
 $i = 0;

   foreach ($cols_data as $col => $citem) {
     $col_item = (array)$citem;

     if (isset($col_item['p']))
     {

       $col = $col_item['p'];
     }
     else {

        $col = $col_item[0];
     }
 $cols[] = $col;
}
  $paragraphs = [];
  foreach ($cols as $col) {
    $paragraph = Paragraph::create([
      'type' => 'match_column_option',
      'field_match_column' => $col,
    ]);
    $paragraph->save();

    $paragraphs[] = ['target_id' => $paragraph->id(), 'target_revision_id' => $paragraph->getRevisionId()];
  }

  return $paragraphs;
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
        'type' => 'match_answers',
        'field_match_row' => $option[0],
        'field_match_column' => $option[1],
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

    $rows = $data->matchInteraction->simpleMatchSet[1]->simpleAssociableChoice;
    $cols = $data->matchInteraction->simpleMatchSet[0]->simpleAssociableChoice;


   $options = [];


   // Replace correct answers identifiers with real values or add new.
   $i = 0;
   foreach ($cols as $col => $citem) {
     foreach ($rows as $row => $ritem) {
       echo($i);
       echo(':');
       $row_item = (array)$ritem;
       $col_item =  (array)$citem;




       if (isset($row_item['p']))
       {

         $row = $row_item['p'];
       }
       else {

          $row = $row_item[0];
       }

       if (isset($col_item['p']))
       {

         $col = $col_item['p'];
       }
       else {


          $col = $col_item[0];
       }


     $options[] = array($row,$col,0);

     }



  }

   return $options;
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
    }, $this->entity->get('field_match_answers')->getValue());

    $entities = $storage_handler->loadMultiple($ids);
    if ($entities) {
      $storage_handler->delete($entities);
    }
  }

}
