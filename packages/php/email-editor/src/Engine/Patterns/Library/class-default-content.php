<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Patterns\Library;

class Default_Content extends Abstract_Pattern {
	protected $blockTypes = array(
		'core/post-content',
	);

	protected $templateTypes = array(
		'email-template',
	);

	protected function getContent(): string {
		return '
    <!-- wp:columns {"backgroundColor":"white","style":{"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}}}} -->
    <div class="wp-block-columns has-white-background-color has-background" style="padding-top:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--20)"><!-- wp:column -->
    <div class="wp-block-column">
    <!-- wp:heading {"fontSize":"medium","style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10"}}}} -->
    <h2 class="wp-block-heading has-medium-font-size" style="padding-top:var(--wp--preset--spacing--10);padding-bottom:var(--wp--preset--spacing--10)">' . __( 'One column layout', 'mailpoet' ) . '</h2>
    <!-- /wp:heading -->
    <!-- wp:image {"width":"620px","sizeSlug":"large"} -->
    <figure class="wp-block-image"><img src="' . esc_url( $this->cdnAssetUrl->generateCdnUrl( 'newsletter/congratulation-page-illustration-transparent-LQ.20181121-1440.png' ) ) . '" alt="Banner Image"/></figure>
    <!-- /wp:image -->
    <!-- wp:paragraph -->
    <p>' . esc_html__( 'A one-column layout is great for simplified and concise content, like announcements or newsletters with brief updates. Drag blocks to add content and customize your styles from the styles panel on the top right.', 'mailpoet' ) . '</p>
    <!-- /wp:paragraph -->
    </div>
    <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
    ';
	}

	protected function getTitle(): string {
		return __( 'Default Email Content', 'mailpoet' );
	}
}
