<?php
/**
 * @contains WorkQueryInterface.php
 * User: goce
 * Date: 12/21/17
 * Time: 10:51 AM
 */

namespace Drupal\node_revision_delete\WorkQuery;


use DateTime;

interface WorkQueryInterface {

  /**
   * @return int
   */
  public function count();

  /**
   * @return array
   */
  public function fetchRecords();

  /**
   * @param string    $type
   * @param int       $minRevRetain
   * @param \DateTime $minAgeRetain
   *   Skip revisions created before this DateTime.
   *
   * @param \DateTime $minNodeInactivityAge
   *   Skip nodes altogether whose most recent revision is after this variable's value.
   *
   * @return \Drupal\node_revision_delete\WorkQuery\WorkQueryInterface
   *   Returns an instance of the object for convenient chaining.
   */
  public function setQuery($type, $minRevRetain, DateTime $minAgeRetain = NULL, DateTime $minNodeInactivityAge = NULL);
}