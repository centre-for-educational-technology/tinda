<?php

namespace Drupal\qualification_test_rounds\Builder;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\dropzonejs\Element\DropzoneJs;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\questions\Entity\TestQuestionInterface;

/**
 * Class UploadBuilder.
 *
 * @package Drupal\qualification_test_rounds\Builder
 */
class UploadBuilder implements FormElementBuilderInterface {

  use BuilderHelpers;

  const FORM_TYPE = 'dropzonejs';

  /**
   * Generates a render array from TestQuestion entity.
   *
   * @param \Drupal\questions\Entity\TestQuestionInterface $testQuestion
   *   Entity which will be processed to the question.
   * @param int $id
   *   Id to give to name.
   * @param null $default_value
   *   Default value of the element if exists.
   *
   * @return array
   *   Drupal form render array element.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function build(TestQuestionInterface $testQuestion, int $id, $default_value = NULL): array {
    $file = NULL;
    // @todo: Dropzone default file handling needs refactoring.
    // There are several use cases where the dropzone element drops the ball and
    // we need to handle uploaded files, but this handling should be refactored,
    // to be easier to understand the data flow.
    $default_value = $this->parseDefaultValue($default_value);
    $class = get_class($this);
    return [
      '#type' => self::FORM_TYPE,
      '#description' => $testQuestion->getHelpText(),
      '#description_display' => 'before',
      '#title' => $testQuestion->getName(),
      '#upload_location' => 'public://testfiles',
      '#name' => "files[$id]",
      '#dropzone_description' => t('Drop files here'),
      '#max_filesize' => '512M', // @todo: this should also come from TestQuestion.
      '#extensions' => $this->getValidExtensionsForDropzone($testQuestion),
      '#max_files' => 1,
      '#clientside_resize' => FALSE,
      '#attributes' => [
        'class' => ['m-dropzone dropzone m-dropzone--primary'],
      ],
      '#default_value' => $default_value,
      '#process' => [[$class, 'processDefaultValue']],
      '#required' => self::isRequired($testQuestion, $id),
    ];
  }

  /**
   * DropzoneJs element does not know how to manage uploaded files.
   *
   * This is used after element's build.
   *
   * @param array $element
   *   The DropzoneJs form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   State.
   * @param array $complete_form
   *   Complete form.
   *
   * @return array
   *   Processed form element including possible default values.
   */
  public static function processDefaultValue(array &$element, FormStateInterface $form_state, array &$complete_form) {
    // Call the DropzoneJS element.
    $element = DropzoneJs::processDropzoneJs($element, $form_state, $complete_form);
    $element_id = $element['#id'];

    // If we send a url with a token through drupalSettings the placeholder
    // doesn't get replaced, because the actual scripts markup is not there
    // yet. So we pass this information through a data attribute.
    $element['uploaded_files']['#attributes']['data-remove-path'] = Url::fromRoute(
      'qualification_test_rounds.remove_uploaded_file_from_dropzone'
    )->setAbsolute()->toString();
    $files = [];

    // Load our JS so we can tweak dropzoneJS and pre-load data.
    $element['#attached']['library'][] = 'qualification_test_rounds/dropzone_default_values';

    if (!empty($element['#default_value'])) {
      // Put together the data to send to the JS.
      foreach ($element['#default_value'] as $file) {
        if ($file instanceof FileInterface
          || (is_int($file) && $file = File::load($file))
        ) {
          $is_image = FALSE;
          switch ($file->getMimeType()) {
            case 'image/jpeg':
            case 'image/gif':
            case 'image/png':
              $is_image = TRUE;
              break;
          }

          $files[] = [
            'path' => $file->url(),
            'name' => $file->getFilename(),
            'size' => $file->getSize(),
            'accepted' => TRUE,
            'is_image' => $is_image,
          ];
        }
        // We are dealing with temp files.
        elseif (is_array($file)) {
          $files[] = $file;
        }
      }
    }

    // Send the uploaded files to a JS variable.
    $settings = &$element['#attached']['drupalSettings']['qualificationTestRounds'];
    $settings[$element_id]['files'] = $files;
    return $element;
  }

  /**
   * Format answer.
   *
   * @param array $answer
   *   Typed answer.
   *
   * @return array
   *   Formatted answer.
   */
  public static function formatAnswer(array $answer) : array {
    $formatted_answer = [];
    foreach ($answer as $key => $file) {
      if (!empty($file)) {
        foreach ($file as $uri) {
          $formatted_answer[] = ['value' => $uri];
        }
      }

    }
    return $formatted_answer;

  }

  /**
   * Validate question.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $answer
   *   User answers for the question.
   * @param \Drupal\questions\Entity\TestQuestionInterface $testQuestion
   *   Testquestion entity.
   * @param array $element
   *   Form element array which to validate.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   validated form state.
   */
  public static function validate(FormStateInterface $form_state, $answer, TestQuestionInterface $testQuestion, array $element) : FormStateInterface {

    if ($element['#required'] && empty($answer['uploaded_files'])) {
      $form_state->setError($element,
        t('@name is required', ['@name' => $element['#title']]));
    }

    return $form_state;
  }

  /**
   * Parses the default value for Dropzone render element.
   *
   * When the user upload the file, it gets saved to temp storage via AJAX.
   * Now when the form validation fails, the file is not yet uploaded,
   * so we need to retrieve it from tempstorage. If the validation passes,
   * we need to use the File storage to find the uploaded file.
   * And we also need to be aware that there can be multiple files uploaded.
   *
   * @param mixed $default_value
   *   The clients answer to the question.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]|null
   *   A single uploaded file, or file info about temp files.
   *   Null if there isn't an answer or we were unable to get file info.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function parseDefaultValue($default_value) {
    if (is_array($default_value)) {

      $files = [];

      foreach ($default_value as $key => $value) {
        if ($key === 'uploaded_files') {

          if (is_array($value)) {

            foreach ($value as $fileInfo) {
              if (is_array($fileInfo)
                || (is_string($fileInfo)
                  && (strpos($fileInfo, "public://") === 0
                    || strpos($fileInfo, "private://") === 0
                  )
                )
              ) {

                $properties = [
                  'uid' => \Drupal::currentUser()->id(),
                ];

                if (is_array($fileInfo)) {
                  $properties['filename'] = $fileInfo['filename'];
                }
                else {
                  $properties['uri'] = $fileInfo;
                }

                $file = \Drupal::entityTypeManager()
                  ->getStorage('file')
                  ->loadByProperties($properties);

                $first_file_value = reset($file);
                $files[key($file)] = $first_file_value;
              }
            }

          }
          // The file is not yet uploaded, get the temp file info.
          // We reach here only when then there is a validation error
          // while moving to next page. In this case the file is not
          // yet uploaded and we need to deal with the temp file.
          else {
            $tmp_file_storage = file_directory_temp();

            if (is_string($default_value['uploaded_files'])) {
              $uploaded_files = explode(';', $default_value['uploaded_files']);
              $uploaded_files = array_unique($uploaded_files);
            }
            else {
              $uploaded_files = array_column($default_value['uploaded_files'], 'filename');
            }

            if (!empty($uploaded_files)) {

              foreach ($uploaded_files as $file) {
                if ($file !== '') {

                  $file_with_path = $tmp_file_storage . '/' . $file;
                  $size = FALSE;

                  if (file_exists($file_with_path)) {
                    $size = filesize($file_with_path);
                  }

                  // Check in case the file was considered dangerous
                  // and txt was added to it.
                  if (!$size && $file_info = pathinfo($file_with_path)) {
                    $file_with_path = $tmp_file_storage . '/' . $file_info['filename'];
                    if (file_exists($file_with_path)) {
                      $size = filesize($file_with_path);
                    }
                  }

                  $data = [
                    'name' => $file,
                  ];

                  if ($size) {
                    $data['size'] = $size;
                  }

                  $files[] = $data;
                }
              }
            }
          }

        }

      }

      return $files;
    }
    // We were unable to detect any files from the default file.
    return NULL;
  }

  /**
   * Parses file format field values for Dropzone.
   *
   * @param \Drupal\questions\Entity\TestQuestionInterface $testQuestion
   *   The Upload Question entity.
   *
   * @return string
   *   String containing possible extensions.
   */
  protected function getValidExtensionsForDropzone(TestQuestionInterface $testQuestion) {

    $extensions = '';

    $mimeTypeFieldValue = $testQuestion->get('field_file_format')->getValue();

    if (!empty($mimeTypeFieldValue)) {
      $mimeType = $mimeTypeFieldValue[0]['value'];

      // Field value 0 means all extensions are allowed.
      if ($mimeType === '0') {
        $extensions = '*';
      }
      else {
        $fileExtension = $this->fileExtensionToMimeTypeMapping($mimeType);
        $extensions = $fileExtension !== FALSE ? $fileExtension : '';
      }

    }

    return $extensions;
  }

  /**
   * Maps a file extension to a mime type.
   *
   * @param string $allowedMimeType
   *   The mime type that is used to find the extension.
   *
   * @return bool|string
   *   bool - If no mimeType matches the $allowedMimeTypes.
   *   string - The file extension for $allowedMimeType.
   */
  protected function fileExtensionToMimeTypeMapping($allowedMimeType) {
    $mapping = [
      'hqx' => [
        'application/mac-binhex40',
        'application/mac-binhex',
        'application/x-binhex40',
        'application/x-mac-binhex40',
      ],
      'cpt' => 'application/mac-compactpro',
      'csv' => [
        'text/x-comma-separated-values',
        'text/comma-separated-values',
        'application/octet-stream',
        'application/vnd.ms-excel',
        'application/x-csv',
        'text/x-csv',
        'text/csv',
        'application/csv',
        'application/excel',
        'application/vnd.msexcel',
        'text/plain',
      ],
      'bin' => [
        'application/macbinary',
        'application/mac-binary',
        'application/octet-stream',
        'application/x-binary',
        'application/x-macbinary',
      ],
      'dms' => 'application/octet-stream',
      'lha' => 'application/octet-stream',
      'lzh' => 'application/octet-stream',
      'exe' => ['application/octet-stream', 'application/x-msdownload'],
      'class' => 'application/octet-stream',
      'psd' => ['application/x-photoshop', 'image/vnd.adobe.photoshop'],
      'so' => 'application/octet-stream',
      'sea' => 'application/octet-stream',
      'dll' => 'application/octet-stream',
      'oda' => 'application/oda',
      'pdf' => [
        'application/pdf',
        'application/force-download',
        'application/x-download',
        'binary/octet-stream',
      ],
      'ai' => ['application/pdf', 'application/postscript'],
      'eps' => 'application/postscript',
      'ps' => 'application/postscript',
      'smi' => 'application/smil',
      'smil' => 'application/smil',
      'mif' => 'application/vnd.mif',
      'xls' => [
        'application/vnd.ms-excel',
        'application/msexcel',
        'application/x-msexcel',
        'application/x-ms-excel',
        'application/x-excel',
        'application/x-dos_ms_excel',
        'application/xls',
        'application/x-xls',
        'application/excel',
        'application/download',
        'application/vnd.ms-office',
        'application/msword',
      ],
      'ppt' => [
        'application/powerpoint',
        'application/vnd.ms-powerpoint',
        'application/vnd.ms-office',
        'application/msword',
      ],
      'pptx' => [
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/x-zip',
        'application/zip',
      ],
      'wbxml' => 'application/wbxml',
      'wmlc' => 'application/wmlc',
      'dcr' => 'application/x-director',
      'dir' => 'application/x-director',
      'dxr' => 'application/x-director',
      'dvi' => 'application/x-dvi',
      'gtar' => 'application/x-gtar',
      'gz' => 'application/x-gzip',
      'gzip' => 'application/x-gzip',
      'php' => [
        'application/x-httpd-php',
        'application/php',
        'application/x-php',
        'text/php',
        'text/x-php',
        'application/x-httpd-php-source',
      ],
      'php4' => 'application/x-httpd-php',
      'php3' => 'application/x-httpd-php',
      'phtml' => 'application/x-httpd-php',
      'phps' => 'application/x-httpd-php-source',
      'js' => ['application/x-javascript', 'text/plain'],
      'swf' => 'application/x-shockwave-flash',
      'sit' => 'application/x-stuffit',
      'tar' => 'application/x-tar',
      'tgz' => ['application/x-tar', 'application/x-gzip-compressed'],
      'z' => 'application/x-compress',
      'xhtml' => 'application/xhtml+xml',
      'xht' => 'application/xhtml+xml',
      'zip' => [
        'application/x-zip',
        'application/zip',
        'application/x-zip-compressed',
        'application/s-compressed',
        'multipart/x-zip',
      ],
      'rar' => [
        'application/x-rar',
        'application/rar',
        'application/x-rar-compressed',
      ],
      'mid' => 'audio/midi',
      'midi' => 'audio/midi',
      'mpga' => 'audio/mpeg',
      'mp2' => 'audio/mpeg',
      'mp3' => ['audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3'],
      'aif' => ['audio/x-aiff', 'audio/aiff'],
      'aiff' => ['audio/x-aiff', 'audio/aiff'],
      'aifc' => 'audio/x-aiff',
      'ram' => 'audio/x-pn-realaudio',
      'rm' => 'audio/x-pn-realaudio',
      'rpm' => 'audio/x-pn-realaudio-plugin',
      'ra' => 'audio/x-realaudio',
      'rv' => 'video/vnd.rn-realvideo',
      'wav' => ['audio/x-wav', 'audio/wave', 'audio/wav'],
      'bmp' => [
        'image/bmp',
        'image/x-bmp',
        'image/x-bitmap',
        'image/x-xbitmap',
        'image/x-win-bitmap',
        'image/x-windows-bmp',
        'image/ms-bmp',
        'image/x-ms-bmp',
        'application/bmp',
        'application/x-bmp',
        'application/x-win-bitmap',
      ],
      'gif' => 'image/gif',
      'jpeg' => ['image/jpeg', 'image/pjpeg'],
      'jpg' => ['image/jpeg', 'image/pjpeg'],
      'jpe' => ['image/jpeg', 'image/pjpeg'],
      'jp2' => ['image/jp2', 'video/mj2', 'image/jpx', 'image/jpm'],
      'j2k' => ['image/jp2', 'video/mj2', 'image/jpx', 'image/jpm'],
      'jpf' => ['image/jp2', 'video/mj2', 'image/jpx', 'image/jpm'],
      'jpg2' => ['image/jp2', 'video/mj2', 'image/jpx', 'image/jpm'],
      'jpx' => ['image/jp2', 'video/mj2', 'image/jpx', 'image/jpm'],
      'jpm' => ['image/jp2', 'video/mj2', 'image/jpx', 'image/jpm'],
      'mj2' => ['image/jp2', 'video/mj2', 'image/jpx', 'image/jpm'],
      'mjp2' => ['image/jp2', 'video/mj2', 'image/jpx', 'image/jpm'],
      'png' => ['image/png', 'image/x-png'],
      'tiff' => 'image/tiff',
      'tif' => 'image/tiff',
      'css' => ['text/css', 'text/plain'],
      'html' => ['text/html', 'text/plain'],
      'htm' => ['text/html', 'text/plain'],
      'shtml' => ['text/html', 'text/plain'],
      'txt' => 'text/plain',
      'text' => 'text/plain',
      'log' => ['text/plain', 'text/x-log'],
      'rtx' => 'text/richtext',
      'rtf' => 'text/rtf',
      'xml' => ['application/xml', 'text/xml', 'text/plain'],
      'xsl' => ['application/xml', 'text/xsl', 'text/xml'],
      'mpeg' => 'video/mpeg',
      'mpg' => 'video/mpeg',
      'mpe' => 'video/mpeg',
      'qt' => 'video/quicktime',
      'mov' => 'video/quicktime',
      'avi' => [
        'video/x-msvideo',
        'video/msvideo',
        'video/avi',
        'application/x-troff-msvideo',
      ],
      'movie' => 'video/x-sgi-movie',
      'doc' => ['application/msword', 'application/vnd.ms-office'],
      'docx' => [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip',
        'application/msword',
        'application/x-zip',
      ],
      'dot' => ['application/msword', 'application/vnd.ms-office'],
      'dotx' => [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip',
        'application/msword',
      ],
      'xlsx' => [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip',
        'application/vnd.ms-excel',
        'application/msword',
        'application/x-zip',
      ],
      'word' => ['application/msword', 'application/octet-stream'],
      'xl' => 'application/excel',
      'eml' => 'message/rfc822',
      'json' => ['application/json', 'text/json'],
      'pem' => [
        'application/x-x509-user-cert',
        'application/x-pem-file',
        'application/octet-stream',
      ],
      'p10' => ['application/x-pkcs10', 'application/pkcs10'],
      'p12' => 'application/x-pkcs12',
      'p7a' => 'application/x-pkcs7-signature',
      'p7c' => ['application/pkcs7-mime', 'application/x-pkcs7-mime'],
      'p7m' => ['application/pkcs7-mime', 'application/x-pkcs7-mime'],
      'p7r' => 'application/x-pkcs7-certreqresp',
      'p7s' => 'application/pkcs7-signature',
      'crt' => [
        'application/x-x509-ca-cert',
        'application/x-x509-user-cert',
        'application/pkix-cert',
      ],
      'crl' => ['application/pkix-crl', 'application/pkcs-crl'],
      'der' => 'application/x-x509-ca-cert',
      'kdb' => 'application/octet-stream',
      'pgp' => 'application/pgp',
      'gpg' => 'application/gpg-keys',
      'sst' => 'application/octet-stream',
      'csr' => 'application/octet-stream',
      'rsa' => 'application/x-pkcs7',
      'cer' => ['application/pkix-cert', 'application/x-x509-ca-cert'],
      '3g2' => 'video/3gpp2',
      '3gp' => ['video/3gp', 'video/3gpp'],
      'mp4' => 'video/mp4',
      'm4a' => 'audio/x-m4a',
      'f4v' => ['video/mp4', 'video/x-f4v'],
      'flv' => 'video/x-flv',
      'webm' => 'video/webm',
      'aac' => 'audio/x-acc',
      'm4u' => 'application/vnd.mpegurl',
      'm3u' => 'text/plain',
      'xspf' => 'application/xspf+xml',
      'vlc' => 'application/videolan',
      'wmv' => ['video/x-ms-wmv', 'video/x-ms-asf'],
      'au' => 'audio/x-au',
      'ac3' => 'audio/ac3',
      'flac' => 'audio/x-flac',
      'ogg' => ['audio/ogg', 'video/ogg', 'application/ogg'],
      'kmz' => [
        'application/vnd.google-earth.kmz',
        'application/zip',
        'application/x-zip',
      ],
      'kml' => [
        'application/vnd.google-earth.kml+xml',
        'application/xml',
        'text/xml',
      ],
      'ics' => 'text/calendar',
      'ical' => 'text/calendar',
      'zsh' => 'text/x-scriptzsh',
      '7z' => [
        'application/x-7z-compressed',
        'application/x-compressed',
        'application/x-zip-compressed',
        'application/zip',
        'multipart/x-zip',
      ],
      '7zip' => [
        'application/x-7z-compressed',
        'application/x-compressed',
        'application/x-zip-compressed',
        'application/zip',
        'multipart/x-zip',
      ],
      'cdr' => [
        'application/cdr',
        'application/coreldraw',
        'application/x-cdr',
        'application/x-coreldraw',
        'image/cdr',
        'image/x-cdr',
        'zz-application/zz-winassoc-cdr',
      ],
      'wma' => ['audio/x-ms-wma', 'video/x-ms-asf'],
      'jar' => [
        'application/java-archive',
        'application/x-java-application',
        'application/x-jar',
        'application/x-compressed',
      ],
      'svg' => ['image/svg+xml', 'application/xml', 'text/xml'],
      'vcf' => 'text/x-vcard',
      'srt' => ['text/srt', 'text/plain'],
      'vtt' => ['text/vtt', 'text/plain'],
      'ico' => ['image/x-icon', 'image/x-ico', 'image/vnd.microsoft.icon'],
      'odc' => 'application/vnd.oasis.opendocument.chart',
      'otc' => 'application/vnd.oasis.opendocument.chart-template',
      'odf' => 'application/vnd.oasis.opendocument.formula',
      'otf' => 'application/vnd.oasis.opendocument.formula-template',
      'odg' => 'application/vnd.oasis.opendocument.graphics',
      'otg' => 'application/vnd.oasis.opendocument.graphics-template',
      'odi' => 'application/vnd.oasis.opendocument.image',
      'oti' => 'application/vnd.oasis.opendocument.image-template',
      'odp' => 'application/vnd.oasis.opendocument.presentation',
      'otp' => 'application/vnd.oasis.opendocument.presentation-template',
      'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
      'ots' => 'application/vnd.oasis.opendocument.spreadsheet-template',
      'odt' => 'application/vnd.oasis.opendocument.text',
      'odm' => 'application/vnd.oasis.opendocument.text-master',
      'ott' => 'application/vnd.oasis.opendocument.text-template',
      'oth' => 'application/vnd.oasis.opendocument.text-web',
    ];
    foreach ($mapping as $extension => $mimeTypes) {
      if (is_array($mimeTypes)) {
        foreach ($mimeTypes as $mimeType) {
          if ($mimeType === $allowedMimeType) {
            return $extension;
          }
        }
      }
      else {
        if ($mimeTypes === $allowedMimeType) {
          return $extension;
        }
      }
    }
    return FALSE;
  }

}
