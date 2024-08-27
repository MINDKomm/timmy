<?php

use Timmy\Timmy;

class TestFunctions extends TimmyUnitTestCase {
	public function test_get_timber_image_src() {
		$attachment = $this->create_image();
		$result     = get_timber_image_src( $attachment, 'large' );

		$image = $this->get_upload_url() . '/test-1400x0-c-default.jpg';

		$this->assertEquals( $image, $result );
	}

	public function test_get_timber_image_src_small_image() {
		$attachment = $this->create_image( [ 'file' => 'test-200px.jpg' ] );

		$image  = Timmy::get_image( $attachment, 'large' );
		$result = $image->src();

		$image = $this->get_upload_url() . '/test-200px.jpg';

		$this->assertEquals( $image, $result );
	}

	public function test_get_timber_image_src_without_metadata() {
		$attachment = $this->create_image();

		// Remove attachment metadata.
		wp_update_attachment_metadata( $attachment->ID, [] );

		$result   = get_timber_image_src( $attachment, 'large' );
		$expected = $this->get_upload_url() . '/test-1400x0-c-default.jpg';

		$this->assertEquals( $expected, $result );
	}

	public function test_get_timber_image_src_non_image() {
		$result = get_timber_image_src( 0, 'large' );

		$this->assertEquals( false, $result );
	}

	public function test_get_timber_image() {
		$attachment = $this->create_image();
		$result     = get_timber_image( $attachment, 'large' );

		$image = ' src="' . $this->get_upload_url() . '/test-1400x0-c-default.jpg" alt=""';

		$this->assertEquals( $image, $result );
	}

	public function test_get_timber_image_without_metadata() {
		$attachment = $this->create_image();

		// Remove attachment metadata.
		wp_update_attachment_metadata( $attachment->ID, [] );

		$result = get_timber_image( $attachment, 'large' );

		$image = ' src="' . $this->get_upload_url() . '/test-1400x0-c-default.jpg" alt=""';

		$this->assertEquals( $image, $result );
	}

	public function test_get_timber_image_non_image() {
		$result = get_timber_image( 0, 'large' );

		$this->assertEquals( false, $result );
	}

	/**
	 * Tests whether we get a scaled or a non-scaled image in return.
	 *
	 * With images bigger than 2560px, WordPress will create an image version with '-scaled' in its
	 * name. We always want the image to be created from the original size.
	 *
	 * @since 0.14.4
	 */
	public function test_get_timber_image_huge_nonscaled() {
		$attachment = $this->create_image( [ 'file' => 'huge.jpg' ] );
		$result     = get_timber_image( $attachment, 'large' );

		$image = ' src="' . $this->get_upload_url() . '/huge-1400x0-c-default.jpg" alt=""';

		$this->assertEquals( $image, $result );
	}

	/**
	 * Tests whether a '-scaled' version of the image is returned when the
	 * 'big_image_size_threshold' kicks in.
	 */
	public function test_get_timber_image_full_scaled() {
		$attachment = $this->create_image( [ 'file' => 'huge.jpg' ] );
		$result     = get_timber_image( $attachment, 'full' );

		$image = ' src="' . $this->get_upload_url() . '/huge-scaled.jpg" alt=""';

		$this->assertEquals( $image, $result );
	}

	/**
	 * Tests whether a non-scaled version of the image is returned when the
	 * `original` size is requested.
	 */
	public function test_get_timber_image_original_nonscaled() {
		$attachment = $this->create_image( [ 'file' => 'huge.jpg' ] );
		$result     = get_timber_image( $attachment, 'original' );

		$image = ' src="' . $this->get_upload_url() . '/huge.jpg" alt=""';

		$this->assertEquals( $image, $result );
	}

	/**
	 * Tests whether the 'big_image_size_threshold' works properly and non-scaled version of the
	 * image is returned.
	 */
	public function test_get_timber_image_full_ignored_threshold() {
		$this->add_filter_temporarily( 'big_image_size_threshold', '__return_false' );

		$attachment = $this->create_image( [ 'file' => 'huge.jpg' ] );
		$result     = get_timber_image( $attachment, 'full' );

		$image = ' src="' . $this->get_upload_url() . '/huge.jpg" alt=""';

		$this->assertEquals( $image, $result );
	}

	public function test_get_timber_image_attributes_responsive() {
		$alt_text   = 'A good boye.';
		$attachment = $this->create_image( [ 'alt' => $alt_text ] );
		$result     = get_timber_image_attributes_responsive( $attachment, 'large' );

		$attributes = [
			'sizes'   => '100vw',
			'src'     => 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7',
			'srcset'  => sprintf( '%1$s/test-560x0-c-default.jpg 560w, %1$s/test-1400x0-c-default.jpg 1400w', $this->get_upload_url() ),
			'alt'     => $alt_text,
			'width'   => 1400,
			'height'  => 933,
			'loading' => 'lazy',
		];

		$this->assertEquals( $attributes, $result );
	}

	public function test_get_timber_image_responsive() {
		$alt_text   = 'Burrito Wrap';
		$attachment = $this->create_image( [
			'alt'         => $alt_text,
			'description' => 'Burritolino',
		] );
		$result     = get_timber_image_responsive( $attachment, 'large' );

		$expected = sprintf(
			' srcset="%1$s/test-560x0-c-default.jpg 560w, %1$s/test-1400x0-c-default.jpg 1400w" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" sizes="100vw" width="1400" height="933" loading="lazy" alt="Burrito Wrap"', $this->get_upload_url()
		);

		$this->assertEquals( $expected, $result );
	}

	public function test_get_timber_image_responsive_apiv1() {
		$alt_text   = 'Burrito Wrap';
		$attachment = $this->create_image( [
			'alt'         => $alt_text,
			'description' => 'Burritolino',
		] );

		$image  = Timmy::get_image( $attachment, 'large' );
		$result = $image->responsive();

		$expected = sprintf(
			' srcset="%1$s/test-560x0-c-default.jpg 560w, %1$s/test-1400x0-c-default.jpg 1400w" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" sizes="100vw" width="1400" height="933" loading="lazy" alt="Burrito Wrap"', $this->get_upload_url()
		);

		$this->assertEquals( $expected, $result );
	}

	public function test_get_timber_image_responsive_without_metadata() {
		$alt_text   = 'Burrito Wrap';
		$attachment = $this->create_image( [
			'alt'         => $alt_text,
			'description' => 'Burritolino',
		] );

		// Remove attachment metadata.
		wp_update_attachment_metadata( $attachment->ID, [] );

		$result = get_timber_image_responsive( $attachment, 'large' );

		$expected = sprintf(
			' srcset="%1$s/test-560x0-c-default.jpg 560w, %1$s/test-1400x0-c-default.jpg 1400w" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" sizes="100vw" width="1400" loading="lazy" alt="Burrito Wrap"',
			$this->get_upload_url()
		);

		$this->assertEquals( $expected, $result );
	}

	public function test_get_timber_image_responsive_src() {
		$attachment = $this->create_image();
		$result     = get_timber_image_responsive_src( $attachment, 'large' );

		$expected = sprintf(
			' srcset="%1$s/test-560x0-c-default.jpg 560w, %1$s/test-1400x0-c-default.jpg 1400w" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" sizes="100vw" width="1400" height="933" loading="lazy"',
			$this->get_upload_url()
		);

		$this->assertEquals( $expected, $result );
	}

	public function test_get_timber_image_responsive_src_without_metadata() {
		$attachment = $this->create_image();
		$result     = get_timber_image_responsive_src( $attachment, 'large' );

		// Remove attachment metadata.
		wp_update_attachment_metadata( $attachment->ID, [] );

		$expected = sprintf(
			' srcset="%1$s/test-560x0-c-default.jpg 560w, %1$s/test-1400x0-c-default.jpg 1400w" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" sizes="100vw" width="1400" height="933" loading="lazy"',
			$this->get_upload_url()
		);

		$this->assertEquals( $expected, $result );
	}

	public function test_get_timber_image_responsive_src_lazy_args() {
		$attachment = $this->create_image();
		$result     = get_timber_image_responsive_src( $attachment, 'large', [
			'lazy_src'    => true,
			'lazy_srcset' => true,
			'lazy_sizes'  => true,
		] );

		$expected = sprintf(
			' width="1400" height="933" loading="lazy" data-srcset="%1$s/test-560x0-c-default.jpg 560w, %1$s/test-1400x0-c-default.jpg 1400w" data-src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-sizes="100vw"',
			$this->get_upload_url()
		);

		$this->assertEquals( $expected, $result );
	}

	public function test_get_timber_image_responsive_src_width_attribute_disabled() {
		$attachment = $this->create_image();
		$result     = get_timber_image_responsive_src( $attachment, 'large', [
			'attr_width' => false,
		] );

		$expected = sprintf(
			' srcset="%1$s/test-560x0-c-default.jpg 560w, %1$s/test-1400x0-c-default.jpg 1400w" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" sizes="100vw" height="933" loading="lazy"',
			$this->get_upload_url()
		);

		$this->assertEquals( $expected, $result );
	}

	public function test_get_timber_image_responsive_src_height_attribute_disabled() {
		$attachment = $this->create_image();
		$result     = get_timber_image_responsive_src( $attachment, 'large', [
			'attr_height' => false,
		] );

		$expected = sprintf(
			' srcset="%1$s/test-560x0-c-default.jpg 560w, %1$s/test-1400x0-c-default.jpg 1400w" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" sizes="100vw" width="1400" loading="lazy"',
			$this->get_upload_url()
		);

		$this->assertEquals( $expected, $result );
	}

	public function test_get_timber_image_responsive_src_loading_eager() {
		$attachment = $this->create_image();
		$result     = get_timber_image_responsive_src( $attachment, 'large', [
			'loading' => 'eager',
		] );

		$expected = sprintf(
			' srcset="%1$s/test-560x0-c-default.jpg 560w, %1$s/test-1400x0-c-default.jpg 1400w" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" sizes="100vw" width="1400" height="933" loading="eager"',
			$this->get_upload_url()
		);

		$this->assertEquals( $expected, $result );
	}

	public function test_get_timber_image_responsive_src_loading_false() {
		$attachment = $this->create_image();
		$result     = get_timber_image_responsive_src( $attachment, 'large', [
			'loading' => false,
		] );

		$expected = sprintf(
			' srcset="%1$s/test-560x0-c-default.jpg 560w, %1$s/test-1400x0-c-default.jpg 1400w" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" sizes="100vw" width="1400" height="933"',
			$this->get_upload_url()
		);

		$this->assertEquals( $expected, $result );
	}

	public function test_get_timber_image_responsive_src_loading_false_apiv1() {
		$attachment = $this->create_image();

		$image = Timmy::get_image( $attachment, 'large' );

		$result = $image->responsive_src( [
			'loading' => false,
		] );

		$expected = sprintf(
			' srcset="%1$s/test-560x0-c-default.jpg 560w, %1$s/test-1400x0-c-default.jpg 1400w" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" sizes="100vw" width="1400" height="933"',
			$this->get_upload_url()
		);

		$this->assertEquals( $expected, $result );
	}

	public function test_get_timber_image_full_with_gif() {
		$attachment = $this->create_image( [ 'file' => 'logo-small.gif' ] );
		$result     = get_timber_image( $attachment, 'full' );

		$image = ' src="' . $this->get_upload_url() . '/logo-small.gif" alt=""';

		$this->assertEquals( $image, $result );
	}

	public function test_get_timber_image_full_with_gif_apiv1() {
		$attachment = $this->create_image( [ 'file' => 'logo-small.gif' ] );

		$image  = Timmy::get_image( $attachment, 'full' );
		$result = $image->src();

		$expected = $this->get_upload_url() . '/logo-small.gif';

		$this->assertEquals( $expected, $result );
	}

	public function test_get_timber_image_large_with_gif() {
		$attachment = $this->create_image( [ 'file' => 'logo-small.gif' ] );

		$image  = Timmy::get_image( $attachment, 'large' );
		$result = $image->src();

		$expected = $this->get_upload_url() . '/logo-small.gif';

		$this->assertEquals( $expected, $result );
	}

	public function test_get_timber_image_full_with_gif_without_metadata() {
		$attachment = $this->create_image( [ 'file' => 'logo-small.gif' ] );

		// Remove attachment metadata.
		wp_update_attachment_metadata( $attachment->ID, [] );

		$result = get_timber_image( $attachment, 'full' );

		$image = ' src="' . $this->get_upload_url() . '/logo-small.gif" alt=""';

		$this->assertEquals( $image, $result );
	}

	/**
	 * @since 0.14.5
	 */
	public function test_get_timber_image_srcset() {
		$attachment = $this->create_image();
		$result     = get_timber_image_srcset( $attachment, 'large' );

		$expected = sprintf(
			'%1$s/test-560x0-c-default.jpg 560w, %1$s/test-1400x0-c-default.jpg 1400w',
			$this->get_upload_url()
		);

		$this->assertEquals( $expected, $result );
	}

	public function test_get_timber_image_srcset_non_image() {
		$result = get_timber_image_srcset( 0, 'large' );

		$this->assertEquals( false, $result );
	}

	public function test_get_timber_image_srcset_without_srcset() {
		$attachment = $this->create_image();
		$result     = get_timber_image_srcset( $attachment, 'resize-only' );

		$this->assertEquals( false, $result );
	}

	public function test_get_timber_image_srcset_x_descriptors() {
		$attachment = $this->create_image();
		$result     = get_timber_image_srcset( $attachment, 'large-x-descriptors' );

		$expected = sprintf(
			'%1$s/test-560x0-c-default.jpg 560w, %1$s/test-1400x0-c-default.jpg 1x, %1$s/test-2100x0-c-default.jpg 1.5x',
			$this->get_upload_url()
		);

		$this->assertEquals( $expected, $result );
	}

	public function test_get_timber_image_srcset_x_descriptors_apiv1() {
		$attachment = $this->create_image();

		$image  = Timmy::get_image( $attachment, 'large-x-descriptors' );
		$result = $image->srcset();

		$expected = sprintf(
			'%1$s/test-560x0-c-default.jpg 560w, %1$s/test-1400x0-c-default.jpg 1x, %1$s/test-2100x0-c-default.jpg 1.5x',
			$this->get_upload_url()
		);

		$this->assertEquals( $expected, $result );
	}

	public function test_get_timber_image_width() {
		$attachment = $this->create_image();

		$image  = Timmy::get_image( $attachment, 'large' );
		$result = $image->width();

		$this->assertEquals( 1400, $result );
	}

	public function test_get_timber_image_width_without_metadata() {
		$attachment = $this->create_image();

		// Remove attachment metadata.
		wp_update_attachment_metadata( $attachment->ID, [] );

		$image  = Timmy::get_image( $attachment, 'large' );
		$result = $image->width();

		$this->assertEquals( 1400, $result );
	}

	public function test_get_timber_image_height() {
		$attachment = $this->create_image();

		$image  = Timmy::get_image( $attachment, 'large' );
		$result = $image->height();

		$this->assertEquals( 933, $result );
	}

	public function test_get_timber_image_height_without_metadata() {
		$attachment = $this->create_image();

		// Remove attachment metadata.
		wp_update_attachment_metadata( $attachment->ID, [] );

		$image  = Timmy::get_image( $attachment, 'large' );
		$result = $image->height();

		// Can’t calculate a height.
		$this->assertEquals( 0, $result );
	}

	/**
	 * When wp_get_attachment_image_src() is called on a Timmy image size, it should return the
	 * correct size.
	 *
	 * @return void
	 */
	public function test_wp_get_attachment_image_src() {
		$attachment = $this->create_image();
		$image      = wp_get_attachment_image_src( $attachment->ID, 'medium' );

		$url    = $image[0];
		$width  = $image[1];
		$height = $image[2];

		$expected_url = $this->get_upload_url() . '/test-600x0-c-default.jpg';

		$this->assertEquals( $expected_url, $url );
		$this->assertEquals( 600, $width );
		$this->assertEquals( 400, $height );
	}
}
