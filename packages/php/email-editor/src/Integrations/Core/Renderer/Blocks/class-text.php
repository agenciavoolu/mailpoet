<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\Settings_Controller;

/**
 * This renderer covers both core/paragraph and core/heading blocks
 */
class Text extends Abstract_Block_Renderer {
	protected function renderContent( string $blockContent, array $parsedBlock, Settings_Controller $settingsController ): string {
		// Do not render empty blocks.
		if ( empty( trim( strip_tags( $blockContent ) ) ) ) {
			return '';
		}

		$blockContent    = $this->adjustStyleAttribute( $blockContent );
		$blockAttributes = wp_parse_args(
			$parsedBlock['attrs'] ?? array(),
			array(
				'textAlign' => 'left',
				'style'     => array(),
			)
		);
		$html            = new \WP_HTML_Tag_Processor( $blockContent );
		$classes         = 'email-text-block';
		if ( $html->next_tag() ) {
			$blockClasses = $html->get_attribute( 'class' ) ?? '';
			$classes     .= ' ' . $blockClasses;
			// remove has-background to prevent double padding applied for wrapper and inner element
			$blockClasses = str_replace( 'has-background', '', $blockClasses );
			// remove border related classes because we handle border on wrapping table cell
			$blockClasses = preg_replace( '/[a-z-]+-border-[a-z-]+/', '', $blockClasses );
			$html->set_attribute( 'class', trim( $blockClasses ) );
			$blockContent = $html->get_updated_html();
		}

		$blockStyles = $this->getStylesFromBlock(
			array(
				'color'      => $blockAttributes['style']['color'] ?? array(),
				'spacing'    => $blockAttributes['style']['spacing'] ?? array(),
				'typography' => $blockAttributes['style']['typography'] ?? array(),
				'border'     => $blockAttributes['style']['border'] ?? array(),
			)
		);

		$styles = array(
			'min-width' => '100%', // prevent Gmail App from shrinking the table on mobile devices
		);

		$styles['text-align'] = 'left';
		if ( isset( $parsedBlock['attrs']['textAlign'] ) ) {
			$styles['text-align'] = $parsedBlock['attrs']['textAlign'];
		} elseif ( in_array( $parsedBlock['attrs']['align'] ?? null, array( 'left', 'center', 'right' ) ) ) {
			$styles['text-align'] = $parsedBlock['attrs']['align'];
		}

		$compiledStyles = $this->compileCss( $blockStyles['declarations'], $styles );
		$tableStyles    = 'border-collapse: separate;'; // Needed because of border radius

		return sprintf(
			'<table
            role="presentation"
            border="0"
            cellpadding="0"
            cellspacing="0"
            width="100%%"
            style="%1$s"
          >
            <tr>
              <td class="%2$s" style="%3$s" align="%4$s">%5$s</td>
            </tr>
          </table>',
			esc_attr( $tableStyles ),
			esc_attr( $classes ),
			esc_attr( $compiledStyles ),
			esc_attr( $styles['text-align'] ?? 'left' ),
			$blockContent
		);
	}

	/**
	 * 1) We need to remove padding because we render padding on wrapping table cell
	 * 2) We also need to replace font-size to avoid clamp() because clamp() is not supported in many email clients.
	 * The font size values is automatically converted to clamp() when WP site theme is configured to use fluid layouts.
	 * Currently (WP 6.5), there is no way to disable this behavior.
	 */
	private function adjustStyleAttribute( string $blockContent ): string {
		$html = new \WP_HTML_Tag_Processor( $blockContent );

		if ( $html->next_tag() ) {
			$elementStyle = $html->get_attribute( 'style' ) ?? '';
			// Padding may contain value like 10px or variable like var(--spacing-10)
			$elementStyle = preg_replace( '/padding[^:]*:.?[0-9a-z-()]+;?/', '', $elementStyle );

			// Remove border styles. We apply border styles on the wrapping table cell
			$elementStyle = preg_replace( '/border[^:]*:.?[0-9a-z-()#]+;?/', '', $elementStyle );

			// We define the font-size on the wrapper element, but we need to keep font-size definition here
			// to prevent CSS Inliner from adding a default value and overriding the value set by user, which is on the wrapper element.
			// The value provided by WP uses clamp() function which is not supported in many email clients
			$elementStyle = preg_replace( '/font-size:[^;]+;?/', 'font-size: inherit;', $elementStyle );
			$html->set_attribute( 'style', esc_attr( $elementStyle ) );
			$blockContent = $html->get_updated_html();
		}

		return $blockContent;
	}
}
