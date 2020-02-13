<?php

namespace Drupal\zip_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @file
 * Class UploadZip.
 */
class UploadZip extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'upload_zip';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['upload_zip'] = [
      '#type' => 'file',
      '#title' => $this->t('Upload file'),
      '#description' => t('ZIP format only'),
      '#upload_location' => 'private://import_files',
      '#upload_validators' => [
        'file_validate_extensions' => ['zip'],
      ],
    ];


    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $all_files = $this->getRequest()->files->get('files', []);
    /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
    $file = $all_files['upload_zip'];
    if (!$file) {
      $form_state->setErrorByName('upload_zip', $this->t('Please upload file!'));
      return;
    }

    $ext = $file->getClientOriginalExtension();
    if (!in_array($ext, ['zip'])) {
      $form_state->setErrorByName('upload_zip', $this->t('File format wrong, you can only upload zip files.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // get The zip file and feed it to the zip importer service.
    $all_files = $this->getRequest()->files->get('files', []);
    $file = $all_files['upload_zip'];
    $file_path = $file->getRealPath();
    \Drupal::service('zip_importer')->import($file_path);
  }

}
