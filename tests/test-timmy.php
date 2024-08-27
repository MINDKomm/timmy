<?php

use Timber\ImageHelper;
use Timber\Timber;

/**
 * Class TestTimmy
 */
class TestTimmy extends TimmyUnitTestCase {
	public function test_create_image() {
		$attachment = $this->create_image();

		$src  = Timber::compile_string( '{{ img.src }}', [ 'img' => $attachment ] );
		$path = ImageHelper::get_server_location( $src );

		$this->assertFileExists( $path );
	}

	public function test_twig_lazy_filter() {
		$attachment = $this->create_image();
		$context    = [
			'size'  => 'large',
			'image' => $attachment,
		];

		// Default, srcset only.
		$result   = Timber::compile_string(
			'{{ image|get_timber_image_responsive(size)|lazy }}',
			$context
		);
		$expected = sprintf(
			' data-srcset="%1$s/test-560x0-c-default.jpg 560w, %1$s/test-1400x0-c-default.jpg 1400w" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" sizes="100vw" width="1400" height="933" loading="lazy" alt=""',
			$this->get_upload_url()
		);

		$this->assertEquals( $expected, $result );

		// Srcset and src.
		$result   = Timber::compile_string(
			"{{ image|get_timber_image_responsive(size)|lazy(['srcset', 'src']) }}",
			$context
		);
		$expected = sprintf(
			' data-srcset="%1$s/test-560x0-c-default.jpg 560w, %1$s/test-1400x0-c-default.jpg 1400w" data-src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" sizes="100vw" width="1400" height="933" loading="lazy" alt=""',
			$this->get_upload_url()
		);

		$this->assertEquals( $expected, $result );

		// Srcset, src and sizes.
		$result   = Timber::compile_string(
			"{{ image|get_timber_image_responsive(size)|lazy(['srcset', 'src', 'sizes']) }}",
			$context
		);
		$expected = sprintf(
			' data-srcset="%1$s/test-560x0-c-default.jpg 560w, %1$s/test-1400x0-c-default.jpg 1400w" data-src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-sizes="100vw" width="1400" height="933" loading="lazy" alt=""',
			$this->get_upload_url()
		);

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Tests when an image has only one size.
	 *
	 * No 'sizes' attribute should be present when an image has only one size.
	 *
	 * @ticket https://github.com/mindkomm/timmy/issues/32
	 */
	function test_one_size_without_srcset() {
		$attachment = $this->create_image();
		$result     = get_timber_image_responsive_src( $attachment, 'one-size' );

		$expected = ' src="' . $this->get_upload_url() . '/test-768x0-c-default.jpg" width="768" height="512" loading="lazy"';

		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test with when an attachment is assigned to a post that was already delete.
	 *
	 * @link https://github.com/mindkomm/timmy/issues/39
	 */
	function test_attached_image_with_missing_post() {
		$post_id = $this->factory()->post->create();
		wp_delete_post( $post_id, true );

		$attachment = $this->create_image( [
			'post_parent' => $post_id,
		] );

		$expected = sprintf(
			' srcset="%1$s/test-560x0-c-default.jpg 560w, %1$s/test-1400x0-c-default.jpg 1400w" src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" sizes="100vw" width="1400" height="933" loading="lazy" alt=""',
			$this->get_upload_url()
		);

		$result = get_timber_image_responsive( $attachment, 'large' );

		$this->assertEquals( $expected, $result );
	}

	public function test_timmy_ignores_pdf() {
		$attachment_id = $this->create_image_attachment( 0, 'example.pdf' );
		$result = image_downsize( $attachment_id );

		$this->assertFalse( $result );
	}

	public function test_timmy_ignores_svg() {
		$filter = function( $ignore) {
			$this->assertEquals( true, $ignore );

			return $ignore;
		};

		$this->add_filter_temporarily( 'timmy/resize/ignore', $filter, 99 );

		$attachment = $this->create_image( [ 'file' => 'sveegee.svg' ] );
		image_downsize( $attachment->ID );
	}

	public function test_timmy_ignores_video() {
		$attachment_id = $this->create_image_attachment( 0, 'video.mp4' );
		$result = image_downsize( $attachment_id );

		$this->assertFalse( $result );
	}

	public function test_timmy_ignores_svg_when_generating_metadata() {
		$attachment = $this->create_image( [ 'file' => 'sveegee.svg' ] );
		$file_src   = get_attached_file( $attachment->ID );
		$meta       = wp_generate_attachment_metadata( $attachment->ID, $file_src );

		$this->assertArrayNotHasKey( 'sizes', $meta );
	}

	public function test_timmy_ignores_gif_when_generating_metadata() {
		$attachment = $this->create_image( [ 'file' => 'logo-small.gif' ] );
		$file_src   = get_attached_file( $attachment->ID );
		$meta       = wp_generate_attachment_metadata( $attachment->ID, $file_src );

		$this->assertEmpty( $meta['sizes'] );
	}

	public function test_timmy_ignores_video_when_generating_metadata() {
		$attachment_id = $this->create_image_attachment( 0, 'video.mp4' );
		$file_src   = get_attached_file( $attachment_id );
		$meta       = wp_generate_attachment_metadata( $attachment_id, $file_src );

		$this->assertArrayNotHasKey( 'sizes', $meta );
	}
}
