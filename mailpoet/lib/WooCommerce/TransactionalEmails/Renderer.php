<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace MailPoet\WooCommerce\TransactionalEmails;

use MailPoet\Entities\NewsletterEntity;
use MailPoet\Newsletter\Renderer\Renderer as NewsletterRenderer;
use MailPoet\Newsletter\Shortcodes\Shortcodes;
use MailPoetVendor\csstidy;
use MailPoetVendor\csstidy_print;

class Renderer {
  const CONTENT_CONTAINER_ID = 'mailpoet_woocommerce_container';

  /** @var csstidy */
  private $cssParser;

  /** @var NewsletterRenderer */
  private $renderer;

  /** @var string */
  private $htmlBeforeContent;

  /** @var string */
  private $htmlAfterContent;

  /** @var Shortcodes */
  private $shortcodes;

  public function __construct(
    csstidy $cssParser,
    NewsletterRenderer $renderer,
    Shortcodes $shortcodes
  ) {
    $this->cssParser = $cssParser;
    $this->htmlBeforeContent = '';
    $this->htmlAfterContent = '';
    $this->renderer = $renderer;
    $this->shortcodes = $shortcodes;
  }

  public function render(NewsletterEntity $newsletter, ?string $subject = null) {
    $preparedNewsletter = $this->prepareNewsletterForRendering($newsletter);
    $renderedNewsletter = $this->renderer->renderAsPreview($preparedNewsletter, 'html', $subject);
    $headingText = $subject ?? '';

    $renderedHtml = $this->processShortcodes($preparedNewsletter, $renderedNewsletter);

    $renderedHtml = str_replace(ContentPreprocessor::WC_HEADING_PLACEHOLDER, $headingText, $renderedHtml);
    $html = explode(ContentPreprocessor::WC_CONTENT_PLACEHOLDER, $renderedHtml);
    $this->htmlBeforeContent = $html[0];
    $this->htmlAfterContent = $html[1];
  }

  public function getHTMLBeforeContent() {
    if (empty($this->htmlBeforeContent)) {
      throw new \Exception("You should call 'render' before 'getHTMLBeforeContent'");
    }
    return $this->htmlBeforeContent . '<!--WooContent--><div id="' . self::CONTENT_CONTAINER_ID . '"><div id="body_content"><div id="body_content_inner"><table style="width: 100%"><tr><td style="padding: 10px 20px;">';
  }

  public function getHTMLAfterContent() {
    if (empty($this->htmlAfterContent)) {
      throw new \Exception("You should call 'render' before 'getHTMLAfterContent'");
    }
    return '<!--WooContent--></td></tr></table></div></div></div>' . $this->htmlAfterContent;
  }

  /**
   * In this method we alter the rendered content that is output when processing the WooCommerce email template.
   * - We update inlined font-family rules in the content block generated by Woo
   */
  public function updateRenderedContent(NewsletterEntity $newsletter, string $content): string {
    $contentParts = explode('<!--WooContent-->', $content);
    if (count($contentParts) !== 3) {
      return $content;
    }
    [$beforeWooContent, $wooContent, $afterWooContent] = $contentParts;
    $fontFamily = $newsletter->getGlobalStyle('text', 'fontFamily');
    $replaceFontFamilyCallback = function ($matches) use ($fontFamily) {
      $pattern = '/font-family\s*:\s*[^;]+;/i';
      $style = $matches[1];
      $style = preg_replace($pattern, "font-family:$fontFamily;", $style);
      return 'style="' . esc_attr($style) . '"';
    };
    $stylePattern = '/style="(.*?)"/i';
    $wooContent = (string)preg_replace_callback($stylePattern, $replaceFontFamilyCallback, $wooContent);
    return implode('', [$beforeWooContent, $wooContent, $afterWooContent]);
  }

  /**
   * In this method we alter CSS that is later inlined into the WooCommerce email template. WooCommerce use Emogrifier to inline CSS.
   * The inlining is called after the rendering and after the modifications we apply to the rendered content in self::updateRenderedContent
   * - We prefix the original selectors to avoid inlining those rules into content added int the MailPoet's editor.
   * - We update the font-family in the original CSS if it's set in the editor.
   */
  public function enhanceCss(string $css, NewsletterEntity $newsletter): string {
    // We allow setting global font family in the editor. The global font is saved in text.fontFamily
    $fontFamily = $newsletter->getGlobalStyle('text', 'fontFamily');
    $this->cssParser->settings['compress_colors'] = false;
    $this->cssParser->parse($css);
    foreach ($this->cssParser->css as $index => $rules) {
      $this->cssParser->css[$index] = [];
      foreach ($rules as $selectors => $properties) {
        $selectors = explode(',', $selectors);
        $selectors = array_map(function($selector) {
          return '#' . self::CONTENT_CONTAINER_ID . ' ' . $selector;
        }, $selectors);
        $selectors = implode(',', $selectors);
        // Update font family if it's set in the editor
        if ($fontFamily && !empty($properties['font-family'])) {
          $properties['font-family'] = $fontFamily;
        }
        $this->cssParser->css[$index][$selectors] = $properties;
      }
    }

    /** @var csstidy_print */
    $print = $this->cssParser->print;
    return $print->plain();
  }

  private function processShortcodes(NewsletterEntity $newsletter, $content) {
    $this->shortcodes->setQueue(null);
    $this->shortcodes->setSubscriber(null);
    $this->shortcodes->setNewsletter($newsletter);
    return $this->shortcodes->replace($content);
  }

  /**
   * This method prepares the newsletter for rendering
   * - We ensure that the font-family and branding color are used as default for all headings
   */
  private function prepareNewsletterForRendering(NewsletterEntity $newsletter): NewsletterEntity {
    $newsletterClone = clone($newsletter);
    $fontFamily = $newsletter->getGlobalStyle('text', 'fontFamily');
    $brandingColor = $newsletter->getGlobalStyle('woocommerce', 'brandingColor');
    $newsletterClone->setGlobalStyle('h1', 'fontFamily', $fontFamily);
    $newsletterClone->setGlobalStyle('h1', 'color', $brandingColor);
    $newsletterClone->setGlobalStyle('h2', 'fontFamily', $fontFamily);
    $newsletterClone->setGlobalStyle('h2', 'color', $brandingColor);
    $newsletterClone->setGlobalStyle('h3', 'fontFamily', $fontFamily);
    $newsletterClone->setGlobalStyle('h3', 'color', $brandingColor);
    return $newsletterClone;
  }
}
