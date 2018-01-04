<?php
/**
 * @contains NodeWorkQuery.php
 * User: goce
 * Date: 12/21/17
 * Time: 10:53 AM
 */

namespace Drupal\node_revision_delete\WorkQuery;

use DateTime;

/**
 * Class NodeWorkQuery
 *
 * @package Drupal\node_revision_delete\WorkQuery
 */
class NodeWorkQuery extends BaseWorkQuery {

  /**
   * Constructs a complicated query with several subqueries. There is one query that constructs a derived table which filters out nodes based on bundle and the number of revisions
   * for each node we want to retain. This was done so that no mater what we always retain a minimum number of revisions. There is also an outer query we may use
   * if we want to filter out node revisions based on their age.
   *
   * This is an example query that may be produced:
   *
   * # trim nodes by the minimum revision age filter.
   * SELECT rv.nid,rv.vid
   * FROM node_revision rv
   * JOIN
   * (
   * # trim nodes by the number of revisions for each node first
   * SELECT n.nid AS nid, COUNT(n.nid) AS num_revisions
   * FROM node n
   * INNER JOIN node_revision r ON n.nid=r.nid
   * WHERE (n.type = 'article')
   * GROUP BY n.nid
   * HAVING (COUNT(n.nid) > 3)
   * ) trimmed
   * ON trimmed.nid=rv.nid
   * WHERE rv.revision_timestamp < 1511405964
   *
   * {@inheritdoc}
   */
  public function setQuery($type, $minimum_revisions_to_keep, DateTime $min_rev_age_to_keep = NULL, DateTime $min_node_inactivity_age = NULL) {
    $subQuery = $this->countRevisionSubQuery($type, $minimum_revisions_to_keep, $min_node_inactivity_age);
    // initially set the revision count query as the main query. It may become a subquery
    $this->query = $subQuery;

    if ($min_rev_age_to_keep) {
      $outerQuery = $this->connection->select('node_revision', 'rv');
      $outerQuery->fields('rv', ['nid', 'vid']);
      $outerQuery->join($subQuery, 'trimmed', 'trimmed.nid=rv.nid');
      $outerQuery->condition('rv.revision_timestamp', $min_rev_age_to_keep->format('U'), '<');
      $outerQuery->groupBy('rv.nid');
      $this->query = $outerQuery;
    }

    return $this;
  }

  /**
   * Marking as private. The table aliases in this query are defined in the main query.
   *
   * Note: This query operates on a node's CURRENT revision, which may not necessarily be a node's most recent revision.
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   */
  private function currentRevisionTimeStampSubQuery() {
    /** @var \Drupal\Core\Database\Query\SelectInterface $subQuery */
    $subQuery = $this->connection->select('node_revision', 'r2');
    $subQuery->addField('r2', 'revision_timestamp');
    $subQuery->where('r2.vid = n.vid');
    //$subQuery->orderBy('r2.revision_timestamp', 'DESC');
    //$subQuery->range(0, 1);
    return $subQuery;

  }

  /**
   * Constructs a query that returns all the nids and their current vid based on the bundle, and the minimum revision count.
   *
   * @param           $type
   * @param           $minimum_revisions_to_keep
   * @param \DateTime $min_node_inactivity_age
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   */
  private function countRevisionSubQuery($type, $minimum_revisions_to_keep, DateTime $min_node_inactivity_age = NULL) {
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->connection->select('node', 'n');
    $query->addField('n', 'nid');
    //$query->addField('n', 'vid');
    $query->addExpression('COUNT(n.nid)', 'num_revisions');
    $query->join('node_revision', 'r', 'n.nid=r.nid');
    $query->condition('n.type', $type);

    if ($min_node_inactivity_age instanceof DateTime) {
      // Construct a WHERE clause to filter out nodes more recent than the $min_rev_age_to_keep. Constructs a WHERE with a SELECT subquery on the CURRENT revision.
      $query->condition($min_node_inactivity_age->format('U'), $this->currentRevisionTimeStampSubQuery(), '>');
    }

    $query->groupBy('n.nid');
    // couldn't use here havingCondition and couldn't use the num_revisions alias.
    $query->having('COUNT(n.nid) > :min_retain_revisions', [':min_retain_revisions' => $minimum_revisions_to_keep]);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return $this->query->countQuery()
      ->execute()
      ->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function fetchRecords() {
    return $this->query->execute()
      ->fetchAll();
  }
}
