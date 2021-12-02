<?php

namespace MailPoet\Segments\DynamicSegments\Filters;

use MailPoet\Entities\DynamicSegmentFilterData;
use MailPoet\Entities\DynamicSegmentFilterEntity;
use MailPoet\Entities\SubscriberEntity;
use MailPoet\Segments\DynamicSegments\Exceptions\InvalidFilterException;
use MailPoet\Util\Security;
use MailPoetVendor\Doctrine\DBAL\Query\QueryBuilder;
use MailPoetVendor\Doctrine\ORM\EntityManager;

class UserRole implements Filter {
  const TYPE = 'wordpressRole';

  /** @var EntityManager */
  private $entityManager;

  public function __construct(
    EntityManager $entityManager
  ) {
    $this->entityManager = $entityManager;
  }

  public function apply(QueryBuilder $queryBuilder, DynamicSegmentFilterEntity $filter): QueryBuilder {
    global $wpdb;
    $filterData = $filter->getFilterData();
    $role = $filterData->getParam('wordpressRole');
    $operator = $filterData->getParam('operator');
    if (!$role) {
      throw new InvalidFilterException('Missing role', InvalidFilterException::MISSING_ROLE);
    }
    if (!is_array($role)) {
      // compatibility with the older segment before multiple roles were added
      $role = [$role];
    }
    if (!$operator) {
      $operator = DynamicSegmentFilterData::OPERATOR_ANY;
    }

    $subscribersTable = $this->entityManager->getClassMetadata(SubscriberEntity::class)->getTableName();
    $parameterSuffix = $filter->getId() ?? Security::generateRandomString();
    return $queryBuilder->join($subscribersTable, $wpdb->users, 'wpusers', "$subscribersTable.wp_user_id = wpusers.id")
      ->join('wpusers', $wpdb->usermeta, 'wpusermeta', 'wpusers.id = wpusermeta.user_id')
      ->andWhere("wpusermeta.meta_key = '{$wpdb->prefix}capabilities' AND wpusermeta.meta_value LIKE :role" . $parameterSuffix)
      ->setParameter(':role' . $parameterSuffix, '%"' . $role[0] . '"%');
  }

}
