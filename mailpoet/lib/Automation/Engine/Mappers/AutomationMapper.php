<?php declare(strict_types = 1);

namespace MailPoet\Automation\Engine\Mappers;

use DateTimeImmutable;
use MailPoet\Automation\Engine\Data\Automation;
use MailPoet\Automation\Engine\Data\AutomationStatistics;
use MailPoet\Automation\Engine\Data\NextStep;
use MailPoet\Automation\Engine\Data\Step;
use MailPoet\Automation\Engine\Registry;
use MailPoet\Automation\Engine\Storage\AutomationStatisticsStorage;

class AutomationMapper {
  /** @var AutomationStatisticsStorage */
  private $statisticsStorage;

  /** @var Registry */
  private $registry;

  public function __construct(
    AutomationStatisticsStorage $statisticsStorage,
    Registry $registry
  ) {
    $this->statisticsStorage = $statisticsStorage;
    $this->registry = $registry;
  }

  public function buildAutomation(Automation $automation): array {

    return [
      'id' => $automation->getId(),
      'name' => $automation->getName(),
      'status' => $automation->getStatus(),
      'created_at' => $automation->getCreatedAt()->format(DateTimeImmutable::W3C),
      'updated_at' => $automation->getUpdatedAt()->format(DateTimeImmutable::W3C),
      'activated_at' => $automation->getActivatedAt() ? $automation->getActivatedAt()->format(DateTimeImmutable::W3C) : null,
      'author' => [
        'id' => $automation->getAuthor()->ID,
        'name' => $automation->getAuthor()->display_name,
      ],
      'stats' => $this->statisticsStorage->getAutomationStats($automation->getId())->toArray(),
      'steps' => array_map(function (Step $step) {
        $stepDefinition = $this->registry->getStep($step->getKey());
        return [
          'id' => $step->getId(),
          'type' => $step->getType(),
          'key' => $step->getKey(),
          'subject_keys' => $stepDefinition ? $stepDefinition->getSubjectKeys() : [],
          'args' => $step->getArgs(),
          'next_steps' => array_map(function (NextStep $nextStep) {
            return $nextStep->toArray();
          }, $step->getNextSteps()),
        ];
      }, $automation->getSteps()),
      'meta' => (object)$automation->getAllMetas(),
    ];
  }

  /** @param Automation[] $automations */
  public function buildAutomationList(array $automations): array {
    $statistics = $this->statisticsStorage->getAutomationStatisticsForAutomations(...$automations);
    return array_map(function (Automation $automation) use ($statistics) {
      return $this->buildAutomationListItem($automation, $statistics[$automation->getId()]);
    }, $automations);
  }

  private function buildAutomationListItem(Automation $automation, AutomationStatistics $statistics): array {
    return [
      'id' => $automation->getId(),
      'name' => $automation->getName(),
      'status' => $automation->getStatus(),
      'created_at' => $automation->getCreatedAt()->format(DateTimeImmutable::W3C),
      'updated_at' => $automation->getUpdatedAt()->format(DateTimeImmutable::W3C),
      'stats' => $statistics->toArray(),
      'activated_at' => $automation->getActivatedAt() ? $automation->getActivatedAt()->format(DateTimeImmutable::W3C) : null,
      'author' => [
        'id' => $automation->getAuthor()->ID,
        'name' => $automation->getAuthor()->display_name,
      ],
    ];
  }
}
