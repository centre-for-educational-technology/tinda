# Tinda
A tool to assess digital competency skills.

## System Specification
* Drupal 8.7
* Php 7.3

## Usage and Running the development
*  Clone Tinda repository
*  Install using composer (go to www/ directory and run command **composer install**)
*  Create settings.local.php file (you can copy settings.php and add following lines )
  ```php
    $databases['default']['default'] = array (
   'database' => '',
   'username' => '',
   'password' => '',
   'prefix' => '',
   'host' => 'localhost',
   'port' => '3306',
   'namespace' => 'Drupal\\Core\\Database\\Driver\\mysql',
   'driver' => 'mysql',
    );
 ```
    In the database, username, and password fields you need to write your development environment database settings.
* Create a symlink for web directory of Tinda in the server (e.g. in case of MAMP server, create symlink in the htdocs directory)
* Create a standard site
    * go to www directory and run following commands
        ```bash
        php vendor/bin/drush si
        php vendor/bin/drush cr
        ```
    * **Note:** If you get some error then manually install drupal using web-interface.
* Once a standard site is created, delete shortcuts by running following command
    ```bash
     vendor/bin/drush ev '\Drupal::entityManager()->getStorage("shortcut_set)->load("default")->delete();'
     ```
* Set the UUID for the Drupal site to be the same as one for Tinda in the repository
    ```bash
    vendor/bin/drush config-set "system.site" uuid "d6aa6641-0d31-45a6-83d5-5d3ee3296808"
    ```
* Run following commands (the first execution will fail)
     ```bash
        vendor/bin/drush cim -y
        vendor/bin/drush cim -y
     ```
* Rebuild cache
    ```bash
        vendor/bin/drush cr
    ```
