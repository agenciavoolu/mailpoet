<?php
namespace MailPoet\Newsletter\Renderer;

if(!defined('ABSPATH')) exit;

class Renderer {
  public $template = 'Template.html';
  public $blocks_renderer;
  public $columns_renderer;
  public $DOM_parser;
  public $CSS_inliner;
  public $newsletter;

  function __construct($newsletter) {
    $this->blocks_renderer = new Blocks\Renderer();
    $this->columns_renderer = new Columns\Renderer();
    $this->DOM_parser = new \pQuery();
    $this->CSS_inliner = new \MailPoet\Util\CSS();
    $this->newsletter = $newsletter;
    $this->template = file_get_contents(dirname(__FILE__) . '/' . $this->template);
  }

  function render() {
    $newsletter_data = (is_array($this->newsletter['body'])) ?
      $this->newsletter['body'] :
      json_decode($this->newsletter['body'], true);
    $newsletter_body = $this->renderContent($newsletter_data['content']);
    $newsletter_styles = $this->renderGlobalStyles($newsletter_data['globalStyles']);
    $newsletter_subject = $this->newsletter['subject'];
    $newsletter_preheader = $this->newsletter['preheader'];
    $rendered_template = $this->renderTemplate($this->template, array(
      $newsletter_subject,
      $newsletter_styles,
      $newsletter_preheader,
      $newsletter_body
    ));
    $rendered_template_with_innlined_styles = $this->inlineCSSStyles($rendered_template);
    return $this->postProcessTemplate($rendered_template_with_innlined_styles);
  }

  function renderContent($content) {
    $content = array_map(function ($content_block) {
      $column_count = count($content_block['blocks']);
      $column_data = $this->blocks_renderer->render($content_block, $column_count);
      return $this->columns_renderer->render(
        $content_block['styles'],
        $column_count,
        $column_data
      );
    }, $content['blocks']);
    return implode('', $content);
  }

  function renderGlobalStyles($styles) {
    $css = '';
    foreach($styles as $selector => $style) {
      switch($selector) {
      case 'h1':
        $selector = 'h1';
      break;
      case 'h2':
        $selector = 'h2';
      break;
      case 'h3':
        $selector = 'h3';
      break;
      case 'text':
        $selector = '.mailpoet_paragraph, .mailpoet_blockquote';
      break;
      case 'body':
        $selector = 'body, .mailpoet_content-wrapper';
      break;
      case 'link':
        $selector = '.mailpoet_content-wrapper a';
      break;
      case 'wrapper':
        $selector = '.mailpoet_content';
      break;
      }
      if(isset($style['fontSize'])) {
        $css .= StylesHelper::setFontAndLineHeight(
          (int) $style['fontSize'],
          $selector
        );
        unset($style['fontSize']);
      }
      if(isset($style['fontFamily'])) {
        $css .= StylesHelper::setFontFamily(
          $style['fontFamily'],
          $selector
        );
        unset($style['fontFamily']);
      }
      $css .= StylesHelper::setStyle($style, $selector);
    }
    return $css;
  }

  function renderTemplate($template, $data) {
    return preg_replace_callback('/{{\w+}}/', function ($matches) use (&$data) {
      return array_shift($data);
    }, $template);
  }

  function inlineCSSStyles($template) {
    return $this->CSS_inliner->inlineCSS(null, $template);
  }

  function renderTextVersion($template) {
    // TODO: add text rendering
    return $template;
  }

  function postProcessTemplate($template) {
    // replace all !important tags except for in the body tag
    $DOM = $this->DOM_parser->parseStr($template);
    $last_column_element = $DOM->query('.mailpoet_template');
    $last_column_element->html(
      str_replace('!important', '', $last_column_element->html())
    );
    // TODO: return array with html and text body
    return $DOM->__toString();
  }
}