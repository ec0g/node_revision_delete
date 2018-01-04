<?php
/**
 * @contains RevisionQuery.php
 * User: goce
 * Date: 12/20/17
 * Time: 10:38 AM
 */

namespace Drupal\node_revision_delete;

use DateTime;
use Drupal\node\NodeTypeInterface;
use Drupal\node_revision_delete\WorkQuery\NodeWorkQuery;
use Drupal\node_revision_delete\WorkQuery\WorkQueryInterface;


/**
 * File: RevisionQuery.php
 * Author: goce
 * Created:  2017.12.20
 *
 * Description:
 */
class RevisionQuery implements RevisionQueryInterface {

  /** @var string */
  protected $type;

  /** @var int */
  protected $minRev;

  /** @var \DateTime */
  protected $minRetainAge;

  /** @var \DateTime */
  protected $nodeInactivityAge;

  /** @var \Drupal\node_revision_delete\WorkQuery\WorkQueryInterface */
  protected $nodeQuery;

  /** @var \Drupal\node_revision_delete\WorkQuery\WorkQueryInterface */
  protected $revQuery;

  /**
   * RevisionQuery constructor.
   *
   * @param \Drupal\node\NodeTypeInterface $content_type
   * @param int                            $revision_items_to_keep
   * @param \DateTime|NULL                 $revision_history_to_retain
   * @param \DateTime|NULL                 $nodeInactivityAge
   *   Nodes modified after this datetime will be filtered out and skipped. Null means the node age filter will not be applied and all nodes will be considered.
   */
  public function __construct(NodeTypeInterface $content_type, $revision_items_to_keep, DateTime $revision_history_to_retain = NULL, DateTime $nodeInactivityAge = NULL) {

    $this->setType($content_type->id());
    $this->setMinRetainRev($revision_items_to_keep);
    $this->setMinRetainAge($revision_history_to_retain);
    $this->setInactivityAge($nodeInactivityAge);

    $this->nodeQuery = NodeWorkQuery::create()
      ->setQuery($this->type, $this->minRev, $this->minRetainAge, $this->nodeInactivityAge);
  }

  /**
   * Sets the minimum age to retain for a revision. If NULL then the filter is not applied.
   *
   * This means that revisions older than the specified DateTime will be deleted.
   *
   * @param \DateTime|NULL $min_age_to_keep
   *    Revisions older than this date will be removed. A NULL means that this filter will not be applied.
   *
   * @return \Drupal\node_revision_delete\RevisionQueryInterface
   */
  public function setMinRetainAge(DateTime $min_age_to_keep = NULL) {
    $this->minRetainAge = $min_age_to_keep;

    return $this;
  }

  public function setInactivityAge(DateTime $nodeInactivityAge = NULL) {
    $this->nodeInactivityAge = $nodeInactivityAge;

    return $this;
  }

  /**
   * The Node type.
   *
   * @param string $content_type
   *    The node bundle. Ex. article,basic_page
   *
   * @return \Drupal\node_revision_delete\RevisionQuery
   */
  public function setType($content_type) {
    // @todo: should add type validation? If the bundle doesn't exist then no harm no foul.
    $this->type = $content_type;

    return $this;
  }

  /**
   * Sets the minimum number of revisions for each node to retain. This value will always be equal to, or greater than 1.
   *
   * Invalid values are ignored. This function can be chained.
   *
   * @param int $minimum_revisions_to_keep
   *    The minimum number of revisions to keep for each node.
   *
   * @return \Drupal\node_revision_delete\RevisionQueryInterface
   */
  public function setMinRetainRev($minimum_revisions_to_keep) {
    if (is_numeric($minimum_revisions_to_keep) && $minimum_revisions_to_keep >= 1) {
      $this->minRev = $minimum_revisions_to_keep;
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function countAffectedNodes() {
    return $this->nodeQuery->count();
  }

  public function countRemovableRevisions() {
    return $this->revQuery->count();
  }

  /**
   * {@inheritdoc}
   */
  public function fetchRemovableRevisions() {
    // TODO: Implement fetchRemovable() method.
  }


  /**
   * @param array $options
   *
   * @return \Drupal\Core\Database\Query\SelectInterface
   */
  protected function query(array $options) {
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->connection->select('node', 'n');
    $query->addField('n', 'nid');
    $query->addField('r', 'vid');
    $query->addExpression('COUNT(n.nid)', 'num_revisions');
    $query->join('node_revision', 'r', 'n.nid=r.nid');
    $query->condition('n.type', $this->type);
    //$query->groupBy('n.nid');

    // couldn't use here havingCondition and couldn't use the num_revisions alias. May be an ORM issue.
    $query->having('COUNT(n.nid) > :min_retain_revisions', [':min_retain_revisions' => $this->minRev]);

    return $query;
  }

}