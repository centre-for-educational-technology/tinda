<?php

namespace Drupal\tinda_users;

use Drupal\csv_importer\Parser as CsvParser;

/**
 * Parser extender for csv_importer module.
 *
 * Class Parser.
 *
 * @package Drupal\tinda_users
 */
class Parser extends CSVParser {

  /**
   * {@inheritdoc}
   */
  public function getCsvById(int $id) {
    $mode = \Drupal::request()->request->get('entity_type');
    if ($mode != 'user') {
      return parent::getCsvById($id);
    }

    /* @var \Drupal\file\Entity\File $entity */
    $entity = $this->getCsvEntity($id);
    $return[] = [
      'field_first_name',
      'field_last_name',
      'mail',
      'field_institution',
      'name',
      'roles',
      'status',
    ];

    $first = TRUE;
    if (($csv = fopen($entity->uri->getString(), 'r')) !== FALSE) {
      while (($row = fgetcsv($csv, 0, ';')) !== FALSE) {
        if ($first) {
          $first = FALSE;
          continue;
        }
        if (count($row) > 1) {
          $row[] = $row[2] ?? NULL;
          $row[] = 'applicant';
          $row[] = 1;
          $return[] = $row;
        }
      }

      fclose($csv);
    }

    return $return;
  }

}
