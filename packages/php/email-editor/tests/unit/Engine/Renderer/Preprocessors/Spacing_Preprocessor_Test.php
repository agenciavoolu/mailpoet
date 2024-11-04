<?php declare(strict_types = 1);

namespace MailPoet\EmailEditor\Engine\Renderer\Preprocessors;

use MailPoet\EmailEditor\Engine\Renderer\ContentRenderer\Preprocessors\Spacing_Preprocessor;

class Spacing_Preprocessor_Test extends \MailPoetUnitTest {

	/** @var Spacing_Preprocessor */
	private $preprocessor;

	/** @var array{contentSize: string} */
	private array $layout;

	/** @var array{spacing: array{padding: array{bottom: string, left: string, right: string, top: string}, blockGap: string}} $styles */
	private array $styles;

	public function _before() {
		parent::_before();
		$this->preprocessor = new Spacing_Preprocessor();
		$this->layout       = array( 'contentSize' => '660px' );
		$this->styles       = array(
			'spacing' => array(
				'padding'  => array(
					'left'   => '10px',
					'right'  => '10px',
					'top'    => '10px',
					'bottom' => '10px',
				),
				'blockGap' => '10px',
			),
		);
	}

	public function testItAddsDefaultVerticalSpacing(): void {
		$blocks = array(
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(),
						'innerBlocks' => array(
							array(
								'blockName'   => 'core/list',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
							array(
								'blockName'   => 'core/img',
								'attrs'       => array(),
								'innerBlocks' => array(),
							),
						),
					),
				),
			),
			array(
				'blockName'   => 'core/columns',
				'attrs'       => array(),
				'innerBlocks' => array(
					array(
						'blockName'   => 'core/column',
						'attrs'       => array(),
						'innerBlocks' => array(),
					),
				),
			),
		);

		$result = $this->preprocessor->preprocess( $blocks, $this->layout, $this->styles );
		$this->assertCount( 2, $result );
		$firstColumns           = $result[0];
		$secondColumns          = $result[1];
		$nestedColumn           = $firstColumns['innerBlocks'][0];
		$nestedColumnFirstItem  = $nestedColumn['innerBlocks'][0];
		$nestedColumnSecondItem = $nestedColumn['innerBlocks'][1];

		// First elements should not have margin-top, but others should.
		$this->assertArrayNotHasKey( 'margin-top', $firstColumns['email_attrs'] );
		$this->assertArrayNotHasKey( 'margin-top', $secondColumns['email_attrs'] );
		$this->assertArrayNotHasKey( 'margin-top', $nestedColumn['email_attrs'] );
		$this->assertArrayNotHasKey( 'margin-top', $nestedColumnFirstItem['email_attrs'] );
		$this->assertArrayHasKey( 'margin-top', $nestedColumnSecondItem['email_attrs'] );
		$this->assertEquals( '10px', $nestedColumnSecondItem['email_attrs']['margin-top'] );
	}
}
