<?php declare(strict_types = 1);

namespace MailPoet\Test\API\MP;

use MailPoet\API\MP\v1\API;
use MailPoet\API\MP\v1\APIException;
use MailPoet\API\MP\v1\CustomFields;
use MailPoet\API\MP\v1\Segments;
use MailPoet\API\MP\v1\Subscribers;
use MailPoet\Config\Changelog;
use MailPoet\Entities\SegmentEntity;
use MailPoet\Test\DataFactories\Segment as SegmentFactory;

class SegmentsTest extends \MailPoetTest {
  /** @var SegmentFactory */
  private $segmentFactory;

  public function _before() {
    parent::_before();
    $this->segmentFactory = new SegmentFactory();
  }

  public function testItGetsAllDefaultSegments(): void {
    $segments = [
      $this->createOrUpdateSegment('Segment 1'),
      $this->createOrUpdateSegment('Segment 2'),
    ];

    $result = $this->getApi()->getLists();

    $this->assertCount(2, $result);
    foreach ($result as $key => $item) {
      $this->validateResponseItem($segments[$key], $item);
    }
  }

  public function testItExcludesWPUsersAndWooCommerceCustomersSegmentsWhenGettingSegments(): void {
    $this->createOrUpdateSegment('WordPress', SegmentEntity::TYPE_WP_USERS);
    $this->createOrUpdateSegment('WooCommerce', SegmentEntity::TYPE_WC_USERS);
    $defaultSegment = $this->createOrUpdateSegment('Segment 1', SegmentEntity::TYPE_DEFAULT, 'My default segment');

    $result = $this->getApi()->getLists();

    $this->assertCount(1, $result);
    $resultItem = reset($result);
    $this->validateResponseItem($defaultSegment, $resultItem);
  }

  public function testItRequiresNameToAddList() {
    try {
      $this->getApi()->addList([]);
      $this->fail('List name required exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('List name is required.');
    }
  }

  public function testItDoesOnlySaveWhiteListedPropertiesWhenAddingList() {
    $result = $this->getApi()->addList([
      'name' => 'Test segment123',
      'description' => 'Description',
      'type' => 'ignore this field',
    ]);
    expect($result['id'])->greaterThan(0);
    expect($result['name'])->equals('Test segment123');
    expect($result['description'])->equals('Description');
    expect($result['type'])->equals('default');
  }

  public function testItDoesNotAddExistingList() {
    $segment = $this->createOrUpdateSegment('Test Segment');

    try {
      $this->getApi()->addList(['name' => $segment->getName()]);
      $this->fail('List exists exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('This list already exists.');
    }
  }

  public function testItAddsList() {
    $segment = [
      'name' => 'Test segment',
    ];

    $result = $this->getApi()->addList($segment);
    expect($result['id'])->greaterThan(0);
    expect($result['name'])->equals($segment['name']);
  }

  public function testItRequiresIdToUpdateList(): void {
    try {
      $this->getApi()->updateList([]);
      $this->fail('List id required exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('List id is required.');
      expect($e->getCode())->equals(APIException::LIST_ID_REQUIRED);
    }
  }

  public function testItChecksListExistenceForUpdateList(): void {
    try {
      $this->getApi()->updateList(['id' => 2]);
      $this->fail('List id must be valid exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('The list does not exist.');
      expect($e->getCode())->equals(APIException::LIST_NOT_EXISTS);
    }
  }

  public function testItRequiresNameToUpdateList(): void {
    $segment = $this->createOrUpdateSegment('Test Segment');
    try {
      $this->getApi()->updateList(['id' => $segment->getId()]);
      $this->fail('List name required exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('List name is required.');
    }
  }

  public function testItDoesNotUpdateListWhenNameIsAlreadyUsed(): void {
    $segment1 = $this->createOrUpdateSegment('Test Segment 1');
    $segment2 = $this->createOrUpdateSegment('Test Segment 2');

    try {
      $this->getApi()->addList([
        'id' => $segment2->getId(),
        'name' => $segment1->getName(),
      ]);
      $this->fail('List name is already used exception should have been thrown.');
    } catch (\Exception $e) {
      expect($e->getMessage())->equals('This list already exists.');
    }
  }

  public function testItUpdatesList(): void {
    $segment = $this->createOrUpdateSegment(
      'Test Segment',
      SegmentEntity::TYPE_DEFAULT,
      'Description'
    );

    $data = [
      'id' => $segment->getId(),
      'name' => 'new name',
      'description' => 'updated description',
    ];
    $result = $this->getApi()->updateList($data);
    expect($result['id'])->equals($data['id']);
    expect($result['name'])->equals($data['name']);
    expect($result['description'])->equals($data['description']);
  }

  private function getApi(): API {
    return new API(
      $this->diContainer->get(CustomFields::class),
      $this->diContainer->get(Segments::class),
      $this->diContainer->get(Subscribers::class),
      $this->diContainer->get(Changelog::class)
    );
  }

  private function validateResponseItem(SegmentEntity $segment, array $item): void {
    $this->assertEquals($segment->getId(), $item['id']);
    $this->assertEquals($segment->getName(), $item['name']);
    $this->assertEquals($segment->getDescription(), $item['description']);
    $this->assertEquals($segment->getType(), $item['type']);
    $this->assertArrayHasKey('created_at', $item);
    $this->assertArrayHasKey('updated_at', $item);
    $this->assertNull($item['deleted_at']);
  }

  private function createOrUpdateSegment(string $name, string $type = SegmentEntity::TYPE_DEFAULT, string $description = ''): SegmentEntity {
    return $this->segmentFactory
      ->withName($name)
      ->withType($type)
      ->withDescription($description)
      ->create();
  }

  public function _after() {
    $this->truncateEntity(SegmentEntity::class);
  }
}
