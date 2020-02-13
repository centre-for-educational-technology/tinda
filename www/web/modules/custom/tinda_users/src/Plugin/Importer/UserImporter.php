<?php

namespace Drupal\tinda_users\Plugin\Importer;

use Drupal\csv_importer\Plugin\ImporterBase;

/**
 * Class UserImporter.
 *
 * @Importer(
 *   id = "user_importer",
 *   entity_type = "user",
 *   label = @Translation("User importer")
 * )
 */
class UserImporter extends ImporterBase {

  /**
   * {@inheritdoc}
   */
  public function add($content, array &$context) {
    if (!$content) {
      return NULL;
    }
    // Count items and return null if in wrong format.
    $items = reset($content['content']);
    if (count($items) < 7) {
      \Drupal::messenger()->addError('Csv is in wrong format, delimiter must be ";"');
      return NULL;
    }

    $entity_type = $this->configuration['entity_type'];
    $entity_type_bundle = $this->configuration['entity_type_bundle'];
    $entity_definition = $this->entityTypeManager->getDefinition($entity_type);

    $added = 0;
    $updated = 0;

    foreach ($content['content'] as $key => $data) {
      if ($entity_definition->hasKey('bundle') && $entity_type_bundle) {
        $data[$entity_definition->getKey('bundle')] = $this->configuration['entity_type_bundle'];
      }

      /** @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage $entity_storage */
      $entity_storage = $this->entityTypeManager->getStorage($this->configuration['entity_type']);

      try {
        if (isset($data[$entity_definition->getKeys()['id']]) && $entity = $entity_storage->load($data[$entity_definition->getKeys()['id']])) {
          /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
          foreach ($data as $id => $set) {
            $entity->set($id, $set);
          }

          $this->preSave($entity, $data, $context);

          if ($entity->save()) {
            $updated++;
          }
        }
        else {
          /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
          $entity = $this->entityTypeManager->getStorage($this->configuration['entity_type'])->create($data);

          $this->preSave($entity, $data, $context);

          if ($entity->save()) {
            // Set operation.
            $op = 'register_admin_created';
            // Send an email.
            _user_mail_notify($op, $entity);

            $added++;
          }
        }

        if (isset($content['translations'][$key]) && is_array($content['translations'][$key])) {
          foreach ($content['translations'][$key] as $code => $translations) {
            $entity_data = array_replace($translations, $translations);

            if ($entity->hasTranslation($code)) {
              $entity_translation = $entity->getTranslation($code);

              foreach ($entity_data as $key => $translation_data) {
                $entity_translation->set($key, $translation_data);
              }
            }
            else {
              $entity_translation = $entity->addTranslation($code, $entity_data);
            }

            $entity_translation->save();
          }
        }
      }
      catch (\Exception $e) {
      }
    }

    $context['results'] = [$added, $updated];
  }

}
