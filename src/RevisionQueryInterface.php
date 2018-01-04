<?php
/**
 * @contains RevisionQueryInterface.php
 * User: goce
 * Date: 12/20/17
 * Time: 10:38 AM
 */

namespace Drupal\node_revision_delete;


interface RevisionQueryInterface {

  /**
   * Counts the number of node entities that have revision records that could be removed.
   *
   * @return int
   */
  public function countAffectedNodes();

  /**
   * Returns the number of records to be removed.
   *
   * @return int
   */
  public function countRemovableRevisions();

  /**
   * Returns the list of revision records that could be removed.
   *
   * @return array
   */
  public function fetchRemovableRevisions();
}