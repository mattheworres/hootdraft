<?php

/**
 * Implements a singleton object that is used to setup global-level settings for PHPDraft to operate, including a database connection.
 * NOTE: DO NOT edit anything in this class, if you edit you do so at your own risk. Open global_setup.php to edit settings.
 */
class PHPDRAFT {
  /** Timezone for GMT-00:00, Greenwich Mean */

  const TIMEZONE_GMT = "Europe/London";
  /** Timezone for GMT-05:00, Eastern Standard */
  const TIMEZONE_EST = "America/New_York";
  /** Timezone for GMT-06:00, Central Standard */
  const TIMEZONE_CST = "America/Chicago";
  /** Timezone for GMT-07:00, Mountain Standard */
  const TIMEZONE_MTN = "America/Denver";
  /** Timezone for GMT-08:00, Pacific Standard */
  const TIMEZONE_PCF = "America/Los_Angeles";

  private $_db_username;
  private $_db_pwd;
  private $_db_host;
  private $_db_name;
  private $_use_csv_timeout;
  private $_use_autocomplete;
  private $_use_nfl_extended;

  public function __construct() {
    $this->_db_host = "localhost";
    $this->_db_name = "phpdraft";
    $this->_db_username = "phpdraft";
    $this->_db_pwd = "password";
    $this->_use_csv_timeout = false;
    $this->_use_autocomplete = true;
    $this->_use_nfl_extended = false;
  }

  /**
   * Set the database username PHPDraft will connect to the database server with.
   * @param string $username 
   */
  public function setDatabaseUsername($username) {
    $this->_db_username = $username;
  }

  /**
   * Set the password PHPDraft will use to connect to the database server.
   * @param string $password 
   */
  public function setDatabasePassword($password) {
    $this->_db_pwd = $password;
  }

  /**
   * Set the hostname of the database server PHPDraft's data is on. By default this is "localhost", so setting this is optional in most cases.
   * @param string $hostname 
   */
  public function setDatabaseHostname($hostname) {
    $this->_db_host = $hostname;
  }

  /**
   * Set the name of the database stored within the database server that PHPDraft will use.
   * NOTE: Most shared hosting providers enforce a database naming scheme that automatically prepends your username with an underscore
   * to every database name (such as "youraccount_phpdraft"), so be sure to double-check the database name if have issues.
   * @param string $database_name
   */
  public function setDatabaseName($database_name) {
    $this->_db_name = $database_name;
  }

  /**
   * Using one of this class' defined timezone constants, tell the application which date timezone to use.
   * @param type $timezone_constant 
   */
  public function setLocalTimezone($timezone_constant) {
    date_default_timezone_set($timezone_constant);
  }

  /**
   * Set the flag to use the timeout for uploading CSVs or not. Defaults to false, because
   * set_timeout can only be used on servers with it enabled (shared hosting disables it often)
   * @param type $useTimeout 
   */
  public function setCsvTimeout($useTimeout) {
    $useTimeout = (bool) $useTimeout;

    $this->_use_csv_timeout = $useTimeout;
  }

  /**
   * Set the flag to use autocomplete on pick entry
   * @param type $useAutocomplete 
   */
  public function setUseAutocomplete($useAutocomplete) {
    $useAutocomplete = (bool) $useAutocomplete;

    $this->_use_autocomplete = $useAutocomplete;
  }

  /**
   * Set the flag to use the extended NFL positions and rosters (defensive players) - false by default
   * @param bool $useNFLExtended 
   */
  public function setUseNFLExtended($useNFLExtended) {
    $useNFLExtended = (bool) $useNFLExtended;

    $this->_use_nfl_extended = $useNFLExtended;
  }

  /**
   * Whether or not to use the CSV Timeout during upload, will help for slower connections or large CSVs
   * @return boolean
   */
  public function useCsvTimeout() {
    return $this->_use_csv_timeout;
  }

  /**
   * Whether Use the autocomplete function on the add pick screen
   * @return boolean
   */
  public function useAutocomplete() {
    return $this->_use_autocomplete;
  }

  public function setupPDOHandle() {
    try {
      $dbh = new PDO('mysql:host=' . $this->_db_host . ';dbname=' . $this->_db_name, $this->_db_username, $this->_db_pwd);
    } catch (PDOException $e) {
      die("Error: " . $e->getMessage());
    }

    return $dbh;
  }

  /**
   * Whether to use extended NFL positions (the defensive positions)
   */
  public function useNFLExtended() {
    return $this->_use_nfl_extended;
  }

}

?>