<?php

use Timmy\Timmy;

class TestPicture extends TimmyUnitTestCase {
	public function test_picture() {
		$alt_text   = 'Burrito Wrap';
		$attachment = $this->create_image( [
			'alt'         => $alt_text,
			'description' => 'Burritolino',
		] );
		$result     = get_timber_picture_responsive( $attachment, 'picture' );

		$expected = sprintf(
			'<source srcset="%1$s/test-560x0-c-default.jpg 560w, %1$s/test-1400x0-c-default.jpg 1400w" sizes="100vw">%2$s<img src="%1$s/test-1400x0-c-default.jpg" width="1400" height="933" alt="Burrito Wrap" loading="lazy">',
			$this->get_upload_url(),
			PHP_EOL
		);

		$this->assertEquals( $expected, $result );
	}

	public function test_picture_with_full_src() {
		$attachment = $this->create_image( [
			'alt' => 'Burrito Wrap',
		] );

		$image  = Timmy::get_image( $attachment, 'full' );
		$result = $image->picture_responsive();

		$expected = sprintf(
			'<img src="%1$s/test.jpg" width="2400" height="1600" alt="Burrito Wrap" loading="lazy">',
			$this->get_upload_url(),
		);

		$this->assertEquals( $expected, $result );
	}

	public function test_picture_loading_false() {
		$attachment = $this->create_image();
		$result     = get_timber_picture_responsive( $attachment, 'picture', [ 'loading' => false ] );

		$expected = sprintf(
			'<source srcset="%1$s/test-560x0-c-default.jpg 560w, %1$s/test-1400x0-c-default.jpg 1400w" sizes="100vw">%2$s<img src="%1$s/test-1400x0-c-default.jpg" width="1400" height="933" alt="">',
			$this->get_upload_url(),
			PHP_EOL
		);

		$this->assertEquals( $expected, $result );
	}

	public function test_picture_loading_false_timmy_image() {
		$attachment = $this->create_image();

		$image  = Timmy::get_image( $attachment->ID, 'picture' );
		$result = $image->picture_responsive( [ 'loading' => false ] );

		$expected = sprintf(
			'<source srcset="%1$s/test-560x0-c-default.jpg 560w, %1$s/test-1400x0-c-default.jpg 1400w" sizes="100vw">%2$s<img src="%1$s/test-1400x0-c-default.jpg" width="1400" height="933" alt="">',
			$this->get_upload_url(),
			PHP_EOL
		);

		$this->assertEquals( $expected, $result );
	}

	public function test_picture_with_lazy_attributes() {
		$alt_text   = 'Burrito Wrap';
		$attachment = $this->create_image( [
			'alt'         => $alt_text,
			'description' => 'Burritolino',
		] );

		$result = get_timber_picture_responsive( $attachment, 'picture', [
			'lazy_srcset' => true,
			'lazy_src'    => true,
			'lazy_sizes'  => true,
		] );

		$expected = sprintf(
			'<source data-srcset="%1$s/test-560x0-c-default.jpg 560w, %1$s/test-1400x0-c-default.jpg 1400w" data-sizes="100vw">%2$s<img width="1400" height="933" alt="Burrito Wrap" loading="lazy" data-src="%1$s/test-1400x0-c-default.jpg">',
			$this->get_upload_url(),
			PHP_EOL
		);

		$this->assertEquals( $expected, $result );
	}

	/**
	 * @since 2.1.0
	 * @return void
	 */
	public function test_picture_img_class() {
		$alt_text   = 'Burrito Wrap';
		$attachment = $this->create_image( [
			'alt'         => $alt_text,
			'description' => 'Burritolino',
		] );
		$result = get_timber_picture_responsive( $attachment, 'picture', ['img_class' => 'the-class'] );

		$expected = sprintf(
			'<source srcset="%1$s/test-560x0-c-default.jpg 560w, %1$s/test-1400x0-c-default.jpg 1400w" sizes="100vw">%2$s<img src="%1$s/test-1400x0-c-default.jpg" width="1400" height="933" alt="Burrito Wrap" loading="lazy" class="the-class">',
			$this->get_upload_url(),
			PHP_EOL
		);

		$this->assertEquals( $expected, $result );
	}
}
