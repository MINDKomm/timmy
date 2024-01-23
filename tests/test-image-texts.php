<?php

use Timber\Image;
use Timber\Timber;

class TestImageTexts extends TimmyUnitTestCase {
	public function test_get_timber_image_texts() {
		$attachment  = $this->create_image();
		$alt_text    = 'A marvellous doggo';
		$description = 'This will be the title';
		$result      = get_timber_image_texts( $attachment, 'large' );

		$this->assertEquals( $result, [
			'alt' => '',
		] );

		$this->set_alt_text( $attachment->ID, $alt_text );
		$this->set_description( $attachment->ID, $description );

		// Reload attachment to get updated values.
		$attachment = Timber::get_image( $attachment->ID );

		$result = get_timber_image_texts( $attachment, 'large' );

		$this->assertEquals( $result, [
			'alt' => $alt_text,
		] );
	}

	public function test_timber_image_alt() {
		$attachment = $this->create_image( [ 'alt' => 'A to B.' ] );
		$alt        = get_timber_image_alt( $attachment->ID );

		$this->assertEquals( 'A to B.', $alt );
	}

	public function test_timber_image_alt_twig() {
		$attachment = $this->create_image( [ 'alt' => 'A to B.' ] );
		$alt        = Timber::compile_string( '{{ get_timber_image_alt(id) }}', [
			'id' => $attachment->ID,
		] );

		$this->assertEquals( 'A to B.', $alt );
	}

	public function test_timber_image_caption() {
		$attachment = $this->create_image( [ 'caption' => 'A to B.' ] );
		$caption    = get_timber_image_caption( $attachment->ID );

		$this->assertEquals( 'A to B.', $caption );
	}

	public function test_timber_image_caption_twig() {
		$attachment = $this->create_image( [ 'caption' => 'A to B.' ] );
		$caption    = Timber::compile_string( '{{ get_timber_image_caption(id) }}', [
			'id' => $attachment->ID,
		] );

		$this->assertEquals( 'A to B.', $caption );
	}

	public function test_timber_image_description() {
		$attachment  = $this->create_image( [ 'description' => 'A to B.' ] );
		$description = get_timber_image_description( $attachment->ID );

		$this->assertEquals( 'A to B.', $description );
	}

	public function test_timber_image_description_twig() {
		$attachment  = $this->create_image( [ 'description' => 'A to B.' ] );
		$description = Timber::compile_string( '{{ get_timber_image_description(id) }}', [
			'id' => $attachment->ID,
		] );

		$this->assertEquals( 'A to B.', $description );
	}
}

