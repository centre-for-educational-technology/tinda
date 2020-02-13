<?php

namespace Drupal\qualification_test_rounds\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Handles requests for removing files from Dropzone.
 */
class DropzoneRemoveFileController extends ControllerBase {

  /**
   * Deletes uploaded file.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request object.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Returns the file delete result.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function handleRemovingUploadedFile(Request $request) {

    $fileName = $request->get('name');

    if (!is_string($fileName)) {
      throw new AccessDeniedHttpException();
    }

    return new AjaxResponse([
      'jsonrpc' => '2.0',
      'result' => $this->deleteFile($fileName),
    ]);
  }

  /**
   * Deletes the file if it exists and is uploaded by the current user.
   *
   * @param string $fileInfo
   *   File to be deleted.
   *
   * @return bool
   *   Delete status.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function deleteFile($fileInfo) {

    $file = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->loadByProperties([
        'filename' => $fileInfo,
        'uid' => \Drupal::currentUser()->id(),
      ]);

    if (!empty($file)) {
      $file = reset($file);
      // This also deletes the file from storage.
      return $file->delete();
    }

    // Check if we are dealing with file uri instead.
    $file = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->loadByProperties([
        'uri' => $fileInfo,
        'uid' => \Drupal::currentUser()->id(),
      ]);

    if (!empty($file)) {
      $file = reset($file);
      // This also deletes the file from storage.
      return $file->delete();
    }

    return FALSE;
  }

}
