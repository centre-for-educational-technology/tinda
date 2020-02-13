<?php

namespace Drupal\zip_import;

/**
 * Interface ZipImporterInterface.
 */
interface ZipImporterInterface {

  /**
   * Constructs a new ZipImporter object.
   */
  public function __construct();

  /**
   * Extract zip file.
   */
  public function getFiles();

  /**
   * Find qti.xml from extracted files.
   *
   * @return array
   *   List on xml files.
   */
  public function getExtractedXmlFiles() : array;

  /**
   * Start the process of importing the zip file.
   *
   * @param string $file
   *   Zip file where to find xmls.
   */
  public function import(string $file);

  /**
   * Read the xml and start Parsing them.
   *
   * @param array $files
   *   Files list where questions xmls are stored.
   */
  public function readXmls(array $files);

}
