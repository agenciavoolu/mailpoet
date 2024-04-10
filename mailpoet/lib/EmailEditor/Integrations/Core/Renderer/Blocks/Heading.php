<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Integrations\Core\Renderer\Blocks;

use MailPoet\EmailEditor\Engine\SettingsController;
use MailPoet\EmailEditor\Integrations\Utils\DomDocumentHelper;

class Heading extends AbstractBlockRenderer {
  protected function renderContent(string $blockContent, array $parsedBlock, SettingsController $settingsController): string {
    $blockContent = $this->adjustStyleAttribute($blockContent);
    return str_replace('{heading_content}', $blockContent, $this->getBlockWrapper($blockContent, $parsedBlock, $settingsController));
  }

  /**
   * Based on MJML <mj-text>
   */
  private function getBlockWrapper($blockContent, array $parsedBlock, SettingsController $settingsController): string {
    $level = $parsedBlock['attrs']['level'] ?? 2; // default level is 2
    $classes = (new DomDocumentHelper($blockContent))->getAttributeValueByTagName("h$level", 'class') ?? '';

    $blockStyles = $this->getStylesFromBlock([
      'color' => $parsedBlock['attrs']['style']['color'] ?? [],
      'spacing' => $parsedBlock['attrs']['style']['spacing'] ?? [],
      'typography' => $parsedBlock['attrs']['style']['typography'] ?? [],
    ]);

    $styles = [
      'min-width' => '100%', // prevent Gmail App from shrinking the table on mobile devices
    ];

    if (isset($parsedBlock['attrs']['textAlign'])) {
      $styles['text-align'] = $parsedBlock['attrs']['textAlign'];
    }

    $styles = $this->compileCss($blockStyles['declarations'], $styles);

    return '
      <!--[if mso | IE]><table align="left" role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%"><tr><td><![endif]-->
          <table
            role="presentation"
            border="0"
            cellpadding="0"
            cellspacing="0"
            style="min-width: 100%;"
            width="100%"
          >
            <tr>
              <td class="' . esc_attr($classes) . '" style="' . esc_attr($styles) . '">
                {heading_content}
              </td>
            </tr>
          </table>

      <!--[if mso | IE]></td></tr></table><![endif]-->
    ';
  }

  /**
   * 1) We need to remove padding because we render padding on wrapping table cell
   * 2) We also need to replace font-size to avoid clamp() because clamp() is not supported in many email clients.
   * The font size values is automatically converted to clamp() when WP site theme is configured to use fluid layouts.
   * Currently (WP 6.5), there is no way to disable this behavior.
   */
  private function adjustStyleAttribute(string $blockContent): string {
    $html = new \WP_HTML_Tag_Processor($blockContent);

    if ($html->next_tag()) {
      $elementStyle = $html->get_attribute('style') ?? '';
      // Padding may contain value like 10px or variable like var(--spacing-10)
      $elementStyle = preg_replace('/padding[^:]*:.?[0-9a-z-()]+;?/', '', $elementStyle);

      // We define the font-size on the wrapper element, but we need to keep font-size definition here
      // to prevent CSS Inliner from adding a default value and overriding the value set by user, which is on the wrapper element.
      // The value provided by WP uses clamp() function which is not supported in many email clients
      $elementStyle = preg_replace('/font-size:[^;]+;?/', 'font-size: inherit;', $elementStyle);
      $html->set_attribute('style', $elementStyle);
      $blockContent = $html->get_updated_html();
    }

    return $blockContent;
  }
}
