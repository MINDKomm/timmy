<?php

class TestSvg extends TimmyUnitTestCase {
	/**
	 * Tests whether we get the full src of an SVG with size full.
	 *
	 * @since 0.14.4
	 */
	public function test_get_timber_image_full_with_svg() {
		$attachment = $this->create_image( [ 'file' => 'sveegee.svg' ] );
		$result     = get_timber_image( $attachment, 'full' );

		$image = ' src="' . $this->get_upload_url() . '/sveegee.svg" alt=""';

		$this->assertEquals( $image, $result );
	}

	/**
	 * Tests whether we still get the image src without an error, even if the
	 * SVG file is missing.
	 *
	 * @since 2.1.0
	 */
	public function test_get_timber_image_full_with_svg_inexistent() {
		$attachment = $this->create_image( [ 'file' => 'sveegee.svg' ] );

		if (file_exists($attachment->file_loc())) {
			unlink($attachment->file_loc());
		}

		$result = get_timber_image( $attachment, 'full' );
		$image = ' src="' . $this->get_upload_url() . '/sveegee.svg" alt=""';

		$this->assertEquals( $image, $result );
	}

	/**
	 * Tests whether we get the full src of an SVG with size large.
	 *
	 * @since 0.14.4
	 */
	public function test_get_timber_image_large_with_svg() {
		$attachment = $this->create_image( [ 'file' => 'sveegee.svg' ] );
		$result     = get_timber_image( $attachment, 'large' );

		$image = ' src="' . $this->get_upload_url() . '/sveegee.svg" alt=""';

		$this->assertEquals( $image, $result );
	}

	public function test_svg_responsive_square() {
		$attachment = $this->create_image( [ 'file' => 'sveegee.svg' ] );
		$result     = get_timber_image_responsive( $attachment, 'large' );

		$image = ' src="' . $this->get_upload_url() . '/sveegee.svg" width="1400" height="1400" loading="lazy" alt=""';

		$this->assertEquals( $image, $result );
	}

	public function test_svg_responsive_rect() {
		$attachment = $this->create_image( [ 'file' => 'svg-400-200.svg' ] );
		$result     = get_timber_image_responsive( $attachment, 'large' );

		$image = ' src="' . $this->get_upload_url() . '/svg-400-200.svg" width="1400" height="700" loading="lazy" alt=""';

		$this->assertEquals( $image, $result );
	}

	public function test_svg_responsive_rect_without_viewbox() {
		$attachment = $this->create_image( [ 'file' => 'svg-without-viewbox.svg' ] );
		$result     = get_timber_image_responsive( $attachment, 'large' );

		$image = ' src="' . $this->get_upload_url() . '/svg-without-viewbox.svg" loading="lazy" alt=""';

		$this->assertEquals( $image, $result );
	}

	public function test_svg_responsive_rect_without_viewbox_responsive() {
		$attachment = $this->create_image( [ 'file' => 'svg-without-viewbox.svg' ] );

		$image  = \Timmy\Timmy::get_image( $attachment, 'large' );
		$result = $image->responsive();

		$image = ' src="' . $this->get_upload_url() . '/svg-without-viewbox.svg" loading="lazy" alt=""';

		$this->assertEquals( $image, $result );
	}

	public function test_svg_responsive_rect_without_viewbox_but_width_and_height_responsive() {
		$attachment = $this->create_image( [ 'file' => 'svg-without-viewbox-width-height.svg' ] );

		$image  = \Timmy\Timmy::get_image( $attachment, 'large' );
		$result = $image->responsive();

		$image = ' src="' . $this->get_upload_url() . '/svg-without-viewbox-width-height.svg" width="1400" height="1400" loading="lazy" alt=""';

		$this->assertEquals( $image, $result );
	}
}
