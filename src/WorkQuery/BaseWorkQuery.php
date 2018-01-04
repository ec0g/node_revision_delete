<?php
/**
 * @contains BaseWorkQuery.php
 * User: goce
 * Date: 12/21/17
 * Time: 10:54 AM
 */

namespace Drupal\node_revision_delete\WorkQuery;

use DateTime;


/**
 * File: BaseWorkQuery.php
 * Author: goce
 * Created:  2017.12.21
 *
 * Description:
 */
abstract class BaseWorkQuery implements WorkQueryInterface {

  /** @var \Drupal\Core\Database\Query\SelectInterface */
  protected $query;

  /** @var \Drupal\Core\Database\Connection */
  protected $connection;

  /** @var string */
  protected $type;

  /** @var int */
  protected $minRev;

  /** @var \DateTime */
  protected $minRetainAge;

  protected function __construct() {
    $this->connection = \Drupal::service('database');
  }

  public static function create() {
    return new static();
  }
}