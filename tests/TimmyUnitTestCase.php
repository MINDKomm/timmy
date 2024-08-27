<?php

use Timber\Image;
use Timber\Timber;
use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * Class TimmyUnitTestCase
 */
abstract class TimmyUnitTestCase extends TestCase {
	/**
	 * Maintain a list of action/filter hook removals to perform at the end of each test.
	 */
	private $temporary_hook_removals = [];

	public function tear_down() {
		parent::tear_down();

		// Remove any hooks added during this test run.
		foreach ( $this->temporary_hook_removals as $callback ) {
			$callback();
		}

		// Reset hooks
		$this->temporary_hook_removals = [];

		// Deletes files added to the `uploads` directory during tests.
		$this->remove_added_uploads();
	}

	/**
	 * @param string $img
	 * @param null   $dest_name
	 *
	 * @return string
	 */
	public function copy_test_image( $img = 'test.jpg', $dest_name = null ) {
		$upload_dir = wp_upload_dir();

		if ( is_null( $dest_name ) ) {
			$dest_name = $img;
		}

		$destination = $upload_dir['path'] . '/' . $dest_name;

		copy( __DIR__ . '/assets/' . $img, $destination );

		return $destination;
	}

	/**
	 * Gets the upload dir base path.
	 *
	 * @return string
	 */
	public function get_upload_url() {
		return wp_upload_dir()['url'];
	}

	public function get_upload_path() {
		return wp_upload_dir()['path'];
	}

	/**
	 * Gets a file path from an attachment object.
	 *
	 * @param \Timber\Post $attachment An attachment.
	 * @param string       $size       Image size.
	 *
	 * @return string
	 */
	public function get_file_path( $attachment, $size ) {
		$src = $attachment->src( $size );

		return \Timber\ImageHelper::get_server_location( $src );
	}

	/**
	 * Deletes all images from current upload folder.
	 */
	public function delete_test_images() {
		$dir = wp_upload_dir();

		// @link https://stackoverflow.com/a/26423999/1059980
		array_map( 'unlink', glob( "{$dir['path']}/*.*" ) );
	}

	/**
	 * Gets an attachment for a post.
	 *
	 * @param int    $post_id
	 * @param string $file
	 *
	 * @return int|\WP_Error
	 */
	public function create_image_attachment( $post_id = 0, $file = 'test.jpg' ) {
		$filename = $this->copy_test_image( $file );

		$filetype = wp_check_filetype( basename( $filename ), null );

		/**
		 * Primitive check for SVG extension.
		 *
		 * In a normal WordPress environment, SVG images have to be allowed manually.
		 */
		if ( ! $filetype['type'] ) {
			if ( '.svg' === substr( $file, - 4, 4 ) ) {
				$filetype = [
					'type' => 'image/svg+xml',
				];
			}
		}

		$attachment = [
			'post_title'     => 'The Arch',
			'post_content'   => '',
			'post_mime_type' => $filetype['type'],
		];

		$attachment_id = wp_insert_attachment( $attachment, $filename, $post_id );
		$meta          = wp_generate_attachment_metadata( $attachment_id, $filename );

		wp_update_attachment_metadata( $attachment_id, $meta );

		return $attachment_id;
	}

	public function create_image( $args = [] ) {
		$args = wp_parse_args( $args, [
			'file'        => 'test.jpg',
			'post_parent' => 0,
			'alt'         => null,
			'caption'     => null,
			'description' => null,
		] );

		$attachment_id = $this->create_image_attachment( $args['post_parent'], $args['file'] );

		if ( ! empty( $args['alt'] ) ) {
			$this->set_alt_text( $attachment_id, $args['alt'] );
		}

		if ( ! empty( $args['caption'] ) ) {
			$this->set_caption( $attachment_id, $args['caption'] );
		}

		if ( ! empty( $args['description'] ) ) {
			$this->set_description( $attachment_id, $args['description'] );
		}

		return Timber::get_image( $attachment_id );
	}

	/**
	 * @return \Timber\Post
	 */
	public function create_post_with_image() {
		$post_id       = $this->factory->post->create();
		$attachment_id = $this->create_image_attachment( $post_id );

		set_post_thumbnail( $post_id, $attachment_id );

		$post = Timber::get_post( $post_id );

		return $post;
	}

	public function set_alt_text( $attachment_id, $alt_text ) {
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
	}

	public function set_caption( $attachment_id, $description ) {
		wp_update_post( [
			'ID'           => $attachment_id,
			'post_excerpt' => $description,
		] );
	}

	public function set_description( $attachment_id, $description ) {
		wp_update_post( [
			'ID'           => $attachment_id,
			'post_content' => $description,
		] );
	}

	/**
	 * Exactly the same as add_filter, but automatically calls remove_filter with the same
	 * arguments during tear_down().
	 */
	protected function add_filter_temporarily( string $filter, callable $callback, int $pri = 10, int $count = 1 ) {
		add_filter( $filter, $callback, $pri, $count );
		$this->temporary_hook_removals[] = function() use ( $filter, $callback, $pri, $count ) {
			remove_filter( $filter, $callback, $pri, $count );
		};
	}

	/**
	 * Exactly the same as add_action, but automatically calls remove_action with the same
	 * arguments during tear_down().
	 */
	protected function add_action_temporarily( string $action, callable $callback, int $pri = 10, int $count = 1 ) {
		add_action( $action, $callback, $pri, $count );
		$this->temporary_hook_removals[] = function() use ( $action, $callback, $pri, $count ) {
			remove_action( $action, $callback, $pri, $count );
		};
	}
}
