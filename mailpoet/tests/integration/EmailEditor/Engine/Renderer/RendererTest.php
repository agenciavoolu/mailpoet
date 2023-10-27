<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer;

use MailPoet\EmailEditor\Engine\EmailEditor;
use MailPoet\EmailEditor\Engine\SettingsController;

require_once __DIR__ . '/DummyBlockRenderer.php';

class RendererTest extends \MailPoetTest {
  /** @var Renderer */
  private $renderer;

  /** @var \WP_Post */
  private $emailPost;

  public function _before() {
    parent::_before();
    $this->diContainer->get(EmailEditor::class)->initialize();
    $this->renderer = $this->diContainer->get(Renderer::class);
    $this->emailPost = new \WP_Post((object)[
      'post_content' => '<!-- wp:paragraph --><p>Hello!</p><!-- /wp:paragraph -->',
    ]);
  }

  public function testItRendersTemplateWithContent() {
    $rendered = $this->renderer->render(
      $this->emailPost,
      'Subject',
      'Preheader content',
      'en',
      'noindex,nofollow'
    );
    verify($rendered['html'])->stringContainsString('Subject');
    verify($rendered['html'])->stringContainsString('Preheader content');
    verify($rendered['html'])->stringContainsString('noindex,nofollow');
    verify($rendered['html'])->stringContainsString('Hello!');

    verify($rendered['text'])->stringContainsString('Preheader content');
    verify($rendered['text'])->stringContainsString('Hello!');
  }

  public function testItInlinesStyles() {
    $stylesCallback = function ($styles) {
      return $styles . 'body { color: pink; }';
    };
    add_filter('mailpoet_email_renderer_styles', $stylesCallback);
    $rendered = $this->renderer->render($this->emailPost, 'Subject', '', 'en');
    $doc = new \DOMDocument();
    $doc->loadHTML($rendered['html']);
    $xpath = new \DOMXPath($doc);
    $nodes = $xpath->query('//body');
    $body = null;
    if (($nodes instanceof \DOMNodeList) && $nodes->length > 0) {
      $body = $nodes->item(0);
    }
    $this->assertInstanceOf(\DOMElement::class, $body);
    $style = $body->getAttribute('style');
    verify($style)->stringContainsString('color:pink');
    remove_filter('mailpoet_email_renderer_styles', $stylesCallback);
  }

  public function testItInlinesEmailStyles() {
    $rendered = $this->renderer->render($this->emailPost, 'Subject', '', 'en');
    $doc = new \DOMDocument();
    $doc->loadHTML($rendered['html']);
    $xpath = new \DOMXPath($doc);
    $nodes = $xpath->query('//body');
    $body = null;
    if (($nodes instanceof \DOMNodeList) && $nodes->length > 0) {
      $body = $nodes->item(0);
    }
    $this->assertInstanceOf(\DOMElement::class, $body);
    $style = $body->getAttribute('style');
    verify($style)->stringContainsString('margin:0;padding:0;');
  }

  public function testItAppliesLayoutStyles() {
    $settingsControllerMock = $this->createMock(SettingsController::class);
    $settingsControllerMock->method('getEmailLayoutStyles')->willReturn([
      'width' => '123px',
      'background' => '#123456',
      'padding' => [
        'left' => '1px',
        'right' => '2px',
        'top' => '3px',
        'bottom' => '4px',
      ],
    ]);
    $renderer = $this->getServiceWithOverrides(Renderer::class, [
      'settingsController' => $settingsControllerMock,
    ]);
    $rendered = $renderer->render($this->emailPost, 'Subject', '', 'en');
    $doc = new \DOMDocument();
    $doc->loadHTML($rendered['html']);
    $xpath = new \DOMXPath($doc);
    $nodes = $xpath->query('//body');
    $body = null;
    if (($nodes instanceof \DOMNodeList) && $nodes->length > 0) {
      $body = $nodes->item(0);
    }
    $this->assertInstanceOf(\DOMElement::class, $body);
    $style = $body->getAttribute('style');
    verify($style)->stringContainsString('background:#123456');

    $xpath = new \DOMXPath($doc);
    $wrapper = null;
    $nodes = $xpath->query('//div[contains(@class, "email_layout_wrapper")]//div');
    if (($nodes instanceof \DOMNodeList) && $nodes->length > 0) {
      $wrapper = $nodes->item(0);
    }
    $this->assertInstanceOf(\DOMElement::class, $wrapper);
    $style = $wrapper->getAttribute('style');
    verify($style)->stringContainsString('max-width: 123px');
  }
}
