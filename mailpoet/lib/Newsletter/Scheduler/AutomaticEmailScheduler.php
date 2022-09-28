<?php

namespace MailPoet\Newsletter\Scheduler;

use MailPoet\Cron\Workers\SendingQueue\SendingQueue;
use MailPoet\Entities\NewsletterEntity;
use MailPoet\Entities\NewsletterOptionFieldEntity;
use MailPoet\Entities\ScheduledTaskEntity;
use MailPoet\Entities\ScheduledTaskSubscriberEntity;
use MailPoet\Entities\SendingQueueEntity;
use MailPoet\Newsletter\Sending\ScheduledTasksRepository;
use MailPoet\Newsletter\Sending\ScheduledTaskSubscribersRepository;
use MailPoet\Newsletter\Sending\SendingQueuesRepository;
use MailPoet\Subscribers\SubscribersRepository;

class AutomaticEmailScheduler {

  /** @var Scheduler */
  private $scheduler;

  /** @var ScheduledTasksRepository */
  private $scheduledTasksRepository;

  /** @var SendingQueuesRepository */
  private $sendingQueuesRepository;

  /** @var ScheduledTaskSubscribersRepository */
  private $scheduledTaskSubscribersRepository;

  /** @var SubscribersRepository */
  private $subscribersRepository;

  public function __construct(
    Scheduler $scheduler,
    ScheduledTasksRepository $scheduledTasksRepository,
    ScheduledTaskSubscribersRepository $scheduledTaskSubscribersRepository,
    SendingQueuesRepository $sendingQueuesRepository,
    SubscribersRepository $subscribersRepository
  ) {
    $this->scheduler = $scheduler;
    $this->scheduledTasksRepository = $scheduledTasksRepository;
    $this->scheduledTaskSubscribersRepository = $scheduledTaskSubscribersRepository;
    $this->sendingQueuesRepository = $sendingQueuesRepository;
    $this->subscribersRepository = $subscribersRepository;
  }

  public function scheduleAutomaticEmail(string $group, string $event, $schedulingCondition = false, $subscriberId = false, $meta = false, $metaModifier = null) {
    $newsletters = $this->scheduler->getNewsletters(NewsletterEntity::TYPE_AUTOMATIC, $group);
    if (empty($newsletters)) return false;
    foreach ($newsletters as $newsletter) {
      if ($newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_EVENT) !== $event) continue;
      if (is_callable($schedulingCondition) && !$schedulingCondition($newsletter)) continue;

      /**
       * $meta will be the same for all newsletters by default. If we need to store newsletter-specific meta, the
       * $metaModifier callback can be used.
       *
       * This was introduced because of WooCommerce product purchase automatic emails. We only want to store the
       * product IDs that specifically triggered a newsletter, but $meta includes ALL the product IDs
       * or category IDs from an order.
       */
      if (is_callable($metaModifier)) {
        $meta = $metaModifier($newsletter, $meta);
      }
      $this->createAutomaticEmailSendingTask($newsletter, $subscriberId, $meta);
    }
  }

  public function scheduleOrRescheduleAutomaticEmail(string $group, string $event, int $subscriberId, array $meta): void {
    $newsletters = $this->scheduler->getNewsletters(NewsletterEntity::TYPE_AUTOMATIC, $group);
    if (empty($newsletters)) {
      return;
    }

    foreach ($newsletters as $newsletter) {
      if ($newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_EVENT) !== $event) {
        continue;
      }

      // try to find existing scheduled task for given subscriber
      $task = $this->scheduledTasksRepository->findOneScheduledByNewsletterAndSubscriberId($newsletter, $subscriberId);
      if ($task) {
        $this->rescheduleAutomaticEmailSendingTask($newsletter, $task, $meta);
      } else {
        $this->createAutomaticEmailSendingTask($newsletter, $subscriberId, $meta);
      }
    }
  }

  public function rescheduleAutomaticEmail(string $group, string $event, int $subscriberId): void {
    $newsletters = $this->scheduler->getNewsletters(NewsletterEntity::TYPE_AUTOMATIC, $group);
    if (empty($newsletters)) {
      return;
    }

    foreach ($newsletters as $newsletter) {
      if ($newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_EVENT) !== $event) {
        continue;
      }

      // try to find existing scheduled task for given subscriber
      $task = $this->scheduledTasksRepository->findOneScheduledByNewsletterAndSubscriberId($newsletter, $subscriberId);
      if ($task) {
        $this->rescheduleAutomaticEmailSendingTask($newsletter, $task);
      }
    }
  }

  public function cancelAutomaticEmail(string $group, string $event, int $subscriberId): void {
    $newsletters = $this->scheduler->getNewsletters(NewsletterEntity::TYPE_AUTOMATIC, $group);
    if (empty($newsletters)) {
      return;
    }

    foreach ($newsletters as $newsletter) {
      if ($newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_EVENT) !== $event) {
        continue;
      }

      // try to find existing scheduled task for given subscriber
      $task = $this->scheduledTasksRepository->findOneScheduledByNewsletterAndSubscriberId($newsletter, $subscriberId);
      if ($task) {
        $this->sendingQueuesRepository->deleteByTask($task);
        $this->scheduledTaskSubscribersRepository->deleteByTask($task);
        $this->scheduledTasksRepository->remove($task);
        $this->scheduledTasksRepository->flush();
      }
    }
  }

  public function createAutomaticEmailSendingTask(NewsletterEntity $newsletter, $subscriberId, $meta = false) {
    $subscriber = $subscriberId ? $this->subscribersRepository->findOneById($subscriberId) : null;
    $scheduledTask = new ScheduledTaskEntity();
    $scheduledTask->setType(SendingQueue::TASK_TYPE);
    $scheduledTask->setStatus(SendingQueueEntity::STATUS_SCHEDULED);
    $scheduledTask->setPriority(ScheduledTaskEntity::PRIORITY_MEDIUM);

    $scheduledTask->setScheduledAt($this->scheduler->getScheduledTimeWithDelay(
      $newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_AFTER_TIME_TYPE),
      $newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_AFTER_TIME_NUMBER)
    ));
    $this->scheduledTasksRepository->persist($scheduledTask);
    $this->scheduledTasksRepository->flush();

    $sendingQueue = new SendingQueueEntity();
    $sendingQueue->setNewsletter($newsletter);
    $sendingQueue->setTask($scheduledTask);
    $scheduledTask->setSendingQueue($sendingQueue);

    if ($meta) {
      $scheduledTask->setMeta($meta);
      $sendingQueue->setMeta($meta);
    }

    $this->sendingQueuesRepository->persist($sendingQueue);
    $this->sendingQueuesRepository->flush();

    if ($newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_SEND_TO) === 'user' && $subscriber) {
      $scheduledTaskSubscriber = new ScheduledTaskSubscriberEntity($scheduledTask, $subscriber);
      $this->scheduledTaskSubscribersRepository->persist($scheduledTaskSubscriber);
      $this->scheduledTaskSubscribersRepository->flush();
      $scheduledTask->getSubscribers()->add($scheduledTaskSubscriber);
    }
  }

  private function rescheduleAutomaticEmailSendingTask(NewsletterEntity $newsletter, ScheduledTaskEntity $scheduledTask, $meta = false) {
    $sendingQueue = $this->sendingQueuesRepository->findOneBy(['task' => $scheduledTask]);
    if (!$sendingQueue) {
      return;
    }

    if ($meta) {
      $sendingQueue->setMeta($meta);
      $scheduledTask->setMeta($meta);
    }
    // compute new 'scheduled_at' from now
    $scheduledTask->setScheduledAt($this->scheduler->getScheduledTimeWithDelay(
      $newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_AFTER_TIME_TYPE),
      $newsletter->getOptionValue(NewsletterOptionFieldEntity::NAME_AFTER_TIME_NUMBER)
    ));
    $this->sendingQueuesRepository->flush();
  }
}
