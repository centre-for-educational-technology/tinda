<?php

namespace Drupal\zip_import;

use Drupal\zip_import\Builder\QuestionBuilderInterface;

/**
 * Class ZipImporter.
 */
class ZipImporter implements ZipImporterInterface {

  /**
   * Filename with uploaded path.
   *
   * @var string
   */
  protected $file;

  /**
   * Zip file.
   *
   * @var \ZipArchive
   */
  protected $zip;

  /**
   * Temporary location where to upload extracted zip files.
   *
   * @var string
   */
  protected $tempLocation;

  /**
   * Constructs a new ZipImporter object.
   */
  public function __construct() {
    $this->generateTempLocation();
  }

  /**
   * Initialize new ZipArchive and open the zip.
   */
  private function openZip() {
    $this->zip = new \ZipArchive();

    if ($this->file) {
      $this->zip->open($this->file);
    }
  }

  /**
   * Generates location where to extract zip files.
   */
  protected function generateTempLocation() {
    $temp_location = sys_get_temp_dir();
    $folder = '/zip_uploads';

    if (!file_exists($temp_location . $folder)) {
      mkdir($temp_location . $folder, 0777);
    }

    $this->tempLocation = $temp_location . $folder;
  }

  /**
   * Deletes a directory.
   *
   * @param null|string $dir
   *   Directory which to delete.
   */
  protected function removeTempLocation(?string $dir = NULL) {
    if (!$dir) {
      $dir = $this->tempLocation;
    }

    // The preg_replace is necessary in order to traverse certain types
    // of folder paths (such as /dir/[[dir2]]/dir3.abc#/)
    // The {,.}* with GLOB_BRACE is necessary to pull all
    // hidden files (have to remove or get "Directory not empty" errors)
    $files = glob(preg_replace('/(\*|\?|\[)/', '[$1]', $dir) . '/{,.}*', GLOB_BRACE);
    foreach ($files as $file) {
      if ($file == $dir . '/.' || $file == $dir . '/..') {
        continue;
      } // skip special dir entries
      is_dir($file) ? $this->removeTempLocation($file) : unlink($file);
    }
    rmdir($dir);
  }

  /**
   * Extract zip file.
   */
  public function getFiles() {
    $this->openZip();
    $this->zip->extractTo($this->tempLocation);
    $this->zip->close();
  }

  /**
   * Get array of extracted files.
   *
   * @return array|null
   *   List of files in directory or null.
   */
  private function readExtractedFiles() : ?array {
    return array_diff(scandir($this->tempLocation), array('.', '..'));
  }

  /**
   * Find qti.xml from extracted files.
   *
   * @return array
   *   List on xml files.
   */
  public function getExtractedXmlFiles() : array {
    $files = [];

    foreach ($this->readExtractedFiles() as $extractedFile) {
      // We only take xml file from folder, which is called qti.xml.
      $check_folder = $this->tempLocation . '/' . $extractedFile;
      if (is_dir($check_folder)) {
        // Check if file exists in there, if yes, then add it to array.
        if (file_exists($check_folder . '/qti.xml')) {
          $files[] = $check_folder . '/qti.xml';
        }
      }
    }

    return $files;
  }

  /**
   * Start the process of importing the zip file.
   *
   * @param string $file
   *   Zip file where to find xmls.
   */
  public function import(string $file) {
    $this->file = $file;
    $this->getFiles();
    $files = $this->getExtractedXmlFiles();

    if ($files) {
      $this->readXmls($files);
    }

    $this->removeTempLocation();
  }

  /**
   * Read the xml and start Parsing them.
   *
   * @param array $files
   *   Files list where questions xmls are stored.
   */
  public function readXmls(array $files) {
    foreach ($files as $file) {
      $xml = simplexml_load_file($file);
      $this->parseXml($xml);
    }
  }

  /**
   * Find the type of the Question and get The builder and feed data to builder.
   *
   * @param \SimpleXMLElement $xml
   *   XML data.
   */
  protected function parseXml(\SimpleXMLElement $xml) {
    $type = $this->getQuestionType($xml->itemBody->div->div);
    /** @var \Drupal\zip_import\Builder\QuestionBuilderInterface $builder */
    $builder = $this->getBuilder($type);
    if (!$builder) {
      return;
    }

    $lang = $this->getLangFormXml($xml);
    $created = $builder->build($xml->attributes()->identifier, $xml->itemBody->div->div, $lang, $xml->responseDeclaration);

    if ($created) {
      $entity = $builder->getEntity();
      $entity->save();
      \Drupal::messenger()->addMessage('Import of ' . ((array) $xml)['@attributes']['title'] . ' is done.');
    }
  }

  /**
   * Find the type of the question.
   *
   * @param \SimpleXMLElement $element
   *   Simple xml element.
   *
   * @return mixed
   *   Type of question.
   */
  protected function getQuestionType(\SimpleXMLElement $element) {
    $array_element = (array) $element;
    // Unset attributes, because we only need type of question.
    unset($array_element['@attributes']);
    unset($array_element['p']);

    // Get keys, because key is the type of the element.
    $keys = array_keys($array_element);
    $type = reset($keys);
    return $type;
  }

  /**
   * Find the Builder based on the given type.
   *
   * @param string $type
   *   Type of Question.
   *
   * @return mixed
   *   Builder or null
   */
  protected function getBuilder(string $type) : ?QuestionBuilderInterface {
    $builders = [
      'choiceInteraction' => \Drupal\zip_import\Builder\Checkbox::class,
      'sliderInteraction' => \Drupal\zip_import\Builder\Slider::class,
      'orderInteraction' => \Drupal\zip_import\Builder\Order::class,
      'associateInteraction' => \Drupal\zip_import\Builder\Associate::class,
      'uploadInteraction' => \Drupal\zip_import\Builder\Upload::class,
      'textEntryInteraction' => \Drupal\zip_import\Builder\TextEntry::class,
      'extendedTextInteraction' => \Drupal\zip_import\Builder\ExtendedText::class,
      'matchInteraction' => \Drupal\zip_import\Builder\MatchInteraction::class,
    ];

    if (isset($builders[$type])) {
      return new $builders[$type]();
    }
    else {
      \Drupal::messenger()->addError('Import of ' . $type . ' is not yet supported');
      return NULL;
    }
  }

  /**
   * Get the language code from xml.
   *
   * @param \SimpleXMLElement $xml
   *   Xml element where is lang stored.
   *
   * @return mixed|string
   *   language code.
   */
  public function getLangFormXml(\SimpleXMLElement $xml) {
    $lang_param = (array) $xml->xpath("@xml:lang");
    if ($lang_param) {
      $lang_code = $lang_param[0]->lang[0];
      $lang = ((array) $lang_code)[0];
      $languge_codes = explode('-', $lang);
      $code = reset($languge_codes);
    }
    else {
      $code = 'en';
    }
    return $code;
  }

}
