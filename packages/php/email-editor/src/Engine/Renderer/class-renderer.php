<?php
/**
 * This file is part of the MailPoet plugin.
 *
 * @package MailPoet\EmailEditor
 */

declare(strict_types = 1);
namespace MailPoet\EmailEditor\Engine\Renderer;

use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Content_Renderer;
use MailPoet\EmailEditor\Engine\Templates\Templates;
use MailPoet\EmailEditor\Engine\Theme_Controller;
use MailPoetVendor\Html2Text\Html2Text;
use MailPoetVendor\Pelago\Emogrifier\CssInliner;
use WP_Style_Engine;
use WP_Theme_JSON;

/**
 * Class Renderer
 */
class Renderer {
	/**
	 * Theme controller
	 *
	 * @var Theme_Controller
	 */
	private Theme_Controller $theme_controller;

	/**
	 * Content renderer
	 *
	 * @var Content_Renderer
	 */
	private Content_Renderer $content_renderer;

	/**
	 * Templates
	 *
	 * @var Templates
	 */
	private Templates $templates;

	/**
	 * Theme data for the template being rendered.
	 *
	 * @var WP_Theme_JSON|null
	 */
	private static $theme = null;

	const TEMPLATE_FILE        = 'template-canvas.php';
	const TEMPLATE_STYLES_FILE = 'template-canvas.css';

	/**
	 * Renderer constructor.
	 *
	 * @param Content_Renderer $content_renderer Content renderer.
	 * @param Templates        $templates Templates.
	 * @param Theme_Controller $theme_controller Theme controller.
	 */
	public function __construct(
		Content_Renderer $content_renderer,
		Templates $templates,
		Theme_Controller $theme_controller
	) {
		$this->content_renderer = $content_renderer;
		$this->templates        = $templates;
		$this->theme_controller = $theme_controller;
	}

	/**
	 * During rendering, this stores the theme data for the template being rendered.
	 */
	public static function get_theme() {
		return self::$theme;
	}

	/**
	 * Renders the email template
	 *
	 * @param \WP_Post $post Post object.
	 * @param string   $subject Email subject.
	 * @param string   $pre_header Email preheader.
	 * @param string   $language Email language.
	 * @param string   $meta_robots Email meta robots.
	 * @return array
	 */
	public function render( \WP_Post $post, string $subject, string $pre_header, string $language, $meta_robots = '' ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$template_id = 'mailpoet/mailpoet//' . ( get_page_template_slug( $post ) ? get_page_template_slug( $post ) : 'email-general' );
		$template    = $this->templates->get_block_template( $template_id );
		$theme       = $this->templates->get_block_template_theme( $template_id, $template->wp_id );

		// Set the theme for the template. This is merged with base theme.json and core json before rendering.
		self::$theme = new WP_Theme_JSON( $theme, 'default' );

		$email_styles  = $this->theme_controller->get_styles( $post, $template );
		$template_html = $this->content_renderer->render( $post, $template );
		$layout        = $this->theme_controller->get_layout_settings();

		ob_start();
		include self::TEMPLATE_FILE;
		$rendered_template = (string) ob_get_clean();

		$template_styles   =
		WP_Style_Engine::compile_css(
			array(
				'background-color' => $email_styles['color']['background'] ?? 'inherit',
				'color'            => $email_styles['color']['text'] ?? 'inherit',
				'padding-top'      => $email_styles['spacing']['padding']['top'] ?? '0px',
				'padding-bottom'   => $email_styles['spacing']['padding']['bottom'] ?? '0px',
				'padding-left'     => $email_styles['spacing']['padding']['left'] ?? '0px',
				'padding-right'    => $email_styles['spacing']['padding']['right'] ?? '0px',
				'font-family'      => $email_styles['typography']['fontFamily'] ?? 'inherit',
				'line-height'      => $email_styles['typography']['lineHeight'] ?? '1.5',
				'font-size'        => $email_styles['typography']['fontSize'] ?? 'inherit',
			),
			'body, .email_layout_wrapper'
		);
		$template_styles  .= '.email_layout_wrapper { box-sizing: border-box;}';
		$template_styles  .= file_get_contents( __DIR__ . '/' . self::TEMPLATE_STYLES_FILE );
		$template_styles   = '<style>' . wp_strip_all_tags( (string) apply_filters( 'mailpoet_email_renderer_styles', $template_styles, $post ) ) . '</style>';
		$rendered_template = $this->inline_css_styles( $template_styles . $rendered_template );

		// This is a workaround to support link :hover in some clients. Ideally we would remove the ability to set :hover
		// however this is not possible using the color panel from Gutenberg.
		if ( isset( $email_styles['elements']['link'][':hover']['color']['text'] ) ) {
			$rendered_template = str_replace( '<!-- Forced Styles -->', '<style>a:hover { color: ' . esc_attr( $email_styles['elements']['link'][':hover']['color']['text'] ) . ' !important; }</style>', $rendered_template );
		}

		return array(
			'html' => $rendered_template,
			'text' => $this->render_text_version( $rendered_template ),
		);
	}

	/**
	 * Inlines CSS styles into the HTML
	 *
	 * @param string $template HTML template.
	 * @return string
	 */
	private function inline_css_styles( $template ) {
		return CssInliner::fromHtml( $template )->inlineCss()->render();
	}

	/**
	 * Renders the text version of the email template
	 *
	 * @param string $template HTML template.
	 * @return string
	 */
	private function render_text_version( $template ) {
		$template = ( mb_detect_encoding( $template, 'UTF-8', true ) ) ? $template : mb_convert_encoding( $template, 'UTF-8', mb_list_encodings() );
		$result   = Html2Text::convert( $template );
		if ( false === $result ) {
			return '';
		}

		return $result;
	}
}
