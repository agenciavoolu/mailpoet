<?php declare(strict_types = 1);

namespace MailPoet\Subscribers;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Settings\SettingsController;
use MailPoet\Test\DataFactories\Newsletter as NewsletterFactory;

class ConfirmationEmailCustomizerTest extends \MailPoetTest {

  /** @var SettingsController */
  private $settings;

  /** @var NewsletterFactory */
  private $newsletterFactory;

  private $partialTemplateContent = 'Please confirm your subscription to receive emails from us';

  public function _before() {
    parent::_before();
    $this->settings = $this->diContainer->get(SettingsController::class);
    $this->newsletterFactory = new NewsletterFactory();
  }

  public function testItGeneratesNewsletterOnInit() {
    $controller = $this->generateController();

    verify($this->settings->get(ConfirmationEmailCustomizer::SETTING_EMAIL_ID, false))->equals(false);
    $controller->init();

    verify($this->settings->get(ConfirmationEmailCustomizer::SETTING_EMAIL_ID, false))->notEquals(false);
  }

  public function testItGenerateNewsletterIfNoneExist() {
    $controller = $this->generateController();

    verify($this->settings->get(ConfirmationEmailCustomizer::SETTING_EMAIL_ID, false))->equals(false);
    $newsletter = $controller->getNewsletter();

    verify($newsletter)->instanceOf(NewsletterEntity::class);

    verify($this->settings->get(ConfirmationEmailCustomizer::SETTING_EMAIL_ID, false))->notEquals(false);
  }

  public function testItRegenerateNewsletterIfIdIsSetButNewsletterDoesNotExist() {
    $this->settings->set(ConfirmationEmailCustomizer::SETTING_EMAIL_ID, 5);

    $controller = $this->generateController();
    $newsletter = $controller->getNewsletter();

    verify($newsletter)->instanceOf(NewsletterEntity::class);

    verify($newsletter->getId())->notEquals(5);

    verify($this->settings->get(ConfirmationEmailCustomizer::SETTING_EMAIL_ID, false))->equals($newsletter->getId());
  }

  public function testItGenerateNewsletterOfTypeConfirmationEmail() {
    $controller = $this->generateController();
    $newsletter = $controller->getNewsletter();

    verify($newsletter->getType())->equals(NewsletterEntity::TYPE_CONFIRMATION_EMAIL_CUSTOMIZER);
  }

  public function testItFetchAlreadyCreatedNewsletter() {
    $newsletter = $this->newsletterFactory
      ->loadBodyFrom('newsletterThreeCols.json')
      ->withType(NewsletterEntity::TYPE_CONFIRMATION_EMAIL_CUSTOMIZER)
      ->create();

    $this->settings->set(ConfirmationEmailCustomizer::SETTING_EMAIL_ID, $newsletter->getId());

    $controller = $this->generateController();
    $newNewsletter = $controller->getNewsletter();

    verify($newNewsletter->getId())->equals($newsletter->getId());
  }

  public function testItFetchesConfirmationEmailTemplate() {
    $controller = $this->generateController();
    $newsletter = $controller->getNewsletter();

    expect($newsletter->getBody())->array();
    $stringBody = json_encode($newsletter->getBody());
    verify($stringBody)->stringContainsString($this->partialTemplateContent);
  }

  public function testItRendersEmail() {
    $controller = $this->generateController();
    $newsletter = $controller->getNewsletter();

    $renderedContent = $controller->render($newsletter);

    expect($renderedContent)->array();

    verify($renderedContent)->arrayHasKey('html');
    verify($renderedContent)->arrayHasKey('text');
    verify($renderedContent)->arrayHasKey('subject');
  }

  public function testItRendersEmailWithDefaultTemplateContent() {
    $subject = 'Confirm your subscription';
    $this->settings->set('signup_confirmation.subject', $subject);

    $controller = $this->generateController();
    $newsletter = $controller->getNewsletter();

    $renderedContent = (array)$controller->render($newsletter);

    verify($renderedContent['html'])->stringContainsString($this->partialTemplateContent);
    verify($renderedContent['text'])->stringContainsString($this->partialTemplateContent);
    verify($renderedContent['subject'])->stringContainsString($subject);
  }

  private function generateController(): ConfirmationEmailCustomizer {
    return $this->diContainer->get(ConfirmationEmailCustomizer::class);
  }
}
