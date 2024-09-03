<?php

namespace Timmy;

use Timber;
use WP_Post;

/**
 * Class Timmy
 *
 * @package Timmy
 */
class Timmy {
	/**
	 * Image sizes that can be selected in the backend.
	 *
	 * @var array
	 */
	public $image_sizes_for_ui = array();

	/**
	 * Timmy can’t be instantiated directly.
	 */
	final protected function __construct() {}

	/**
	 * Hook into WordPress
	 */
	public static function init() {
		if ( ! class_exists( 'Timber\ImageHelper' ) ) {
			return;
		}

		$self = new self;

		// Wait for theme to initialize to make sure that we can access all image sizes.
		add_action( 'after_setup_theme', [ $self, 'after_setup_theme' ] );

		// Add filters and functions to integrate Timmy into Timber and Twig.
		add_filter('timber/twig/filters', [ $self, 'add_filters' ]);
		add_filter('timber/twig/functions', [ $self, 'add_functions' ]);

		add_filter( 'timmy/resize/ignore', array( __CLASS__, 'ignore_unallowed_files' ), 10, 2 );
	}

	/**
	 * Initializes the responsive content image feature.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     Optional. An array of arguments for the Responsive Content Image handler.
	 *
	 *     @type array|string $map_sizes An associative array of size keys used in the content and
	 *                                   Timmy sizes to replace them with. E.g., when a large image
	 *                                   is used in the content, you could use `'large' => 'content`
	 *                                   to use the 'content' size from your image configuration
	 *                                   instead of the large size. If you use a string, all sizes
	 *                                   will be mapped to the size you pass in the string.
	 *     @type array $content_filters  An array of filters that are added where reponsive content
	 *                                   image filtering is applied. By default, it’s only applied
	 *                                   to `the_content`. You can add other filters here, like
	 *                                   `acf_the_content` or choose to not add `the_content` as a
	 *                                   filter. If you want `the_content` to work, you need to add
	 *                                   it yourself when defining this argument.
	 * }
	 *
	 * @return \Timmy\Responsive_Content_Images
	 */
	public static function responsive_content_images( array $args = [] ) {
		$responsive_content_images = Responsive_Content_Images::instance( $args );
		$responsive_content_images->init();

		return $responsive_content_images;
	}

	/**
	 * Setup Timmy
	 */
	public function after_setup_theme() {
		$this->validate_get_image_sizes();

		// Add filters to make Timber Images work with normal WordPress image functionality.
		add_filter( 'image_downsize', array( $this, 'filter_image_downsize' ), 10, 3 );
		add_filter( 'image_size_names_choose', array( $this, 'filter_image_size_names_choose' ), 10 );

		// Filters the list of intermediate image sizes.
		add_filter( 'intermediate_image_sizes', array( $this, 'filter_intermediate_image_sizes' ) );

		// Filters the image sizes automatically generated when uploading an image.
		add_filter( 'intermediate_image_sizes_advanced', array( $this, 'filter_intermediate_image_sizes_advanced' ) );

		// Filters the metadata for an image.
		add_filter( 'wp_get_attachment_metadata', array( $this, 'filter_attachment_metadata' ), 10, 2 );

		// Hook into generating attachment meta data filter.
		add_filter( 'wp_generate_attachment_metadata', array( $this, 'delete_generated_image_sizes' ), 5, 2 );
		add_filter( 'wp_generate_attachment_metadata', array( $this, 'filter_wp_generate_attachment_metadata' ), 30, 2 );

		// Filters the attachment data prepared for JavaScript.
		add_filter( 'wp_prepare_attachment_for_js', array( $this, 'filter_wp_prepare_attachment_for_js' ), 10, 3 );

		// Set global $_wp_additional_image_sizes.
		$this->set_wp_additional_image_sizes();

		/**
		 * Third party filters
		 *
		 * - Make image sizes selectable in ACF
		 */
		add_filter( 'acf/get_image_sizes', array( $this, 'filter_acf_get_image_sizes' ) );

		if ( is_admin() ) {
			$this->image_sizes_for_ui = $this->get_image_sizes_for_ui();
		}
	}

	/**
	 * Gets a Timmy image.
	 *
	 * @param mixed        $attachment Attachment ID.
	 * @param string|array $size
	 *
	 * @return null|\Timmy\Image
	 */
	public static function get_image( $attachment, $size ) {
		// Already an image.
		if ( $attachment instanceof Image ) {
			return $attachment;
		}

		if ( $attachment instanceof WP_Post || $attachment instanceof Timber\Attachment ) {
			$attachment = $attachment->ID;
		} elseif ( is_array( $attachment ) && isset( $attachment['ID'] ) ) {
			// Get ID from ACF image array.
			$attachment = (int) $attachment['ID'];
		}

		if (!$attachment) {
			return null;
		}

		// Check if we work with a WordPress post. This doesn’t necessarily have
		// to be an attachment, that’s why we don’t check for the 'attachment'
		// post type.
		$wp_post = \get_post( $attachment );

		if ( ! $wp_post ) {
			return null;
		}

		$class      = apply_filters( 'timmy/image/class', Image::class );
		$size_array = $size;

		if ( is_string( $size ) ) {
			if ( in_array( $size, [ 'full', 'original' ], true ) ) {
				$size_array = [];
			} else {
				$size_array = Helper::get_image_size( $size );
			}
		}

		$image = $class::build( $attachment, $size_array );

		if ( is_string( $size ) ) {
			$image->set_size_key( $size );
		}

		return $image;
	}

	/**
	 * Adds Twig filters.
	 *
	 * @param array $filters
	 *
	 * @return array
	 */
	public function add_filters( array $filters ): array {
		$filters['get_timber_image']                = [ 'callable' => 'get_timber_image' ];
		$filters['get_timber_image_src']            = [ 'callable' => 'get_timber_image_src' ];
		$filters['get_timber_image_srcset']         = [ 'callable' => 'get_timber_image_srcset' ];
		$filters['get_timber_image_responsive']     = [ 'callable' => 'get_timber_image_responsive' ];
		$filters['get_timber_image_responsive_src'] = [ 'callable' => 'get_timber_image_responsive_src' ];
		$filters['get_timber_picture_responsive']   = [ 'callable' => 'get_timber_picture_responsive' ];
		$filters['lazy']                            = [ 'callable' => 'make_timber_image_lazy' ];

		return $filters;
	}

	/**
	 * Adds Twig functions.
	 *
	 * @param array $functions
	 *
	 * @return array
	 */
	public function add_functions( array $functions ): array {
		$functions['get_timmy_image']                 = [ 'callable' => [ '\Timmy\Timmy', 'get_image' ] ];

		// ACF.
		$functions['get_timber_image_responsive_acf'] = [ 'callable' => 'get_timber_image_responsive_acf' ];

		// Image texts.
		$functions['get_timber_image_alt']         = [ 'callable' => 'get_timber_image_alt' ];
		$functions['get_timber_image_caption']     = [ 'callable' => 'get_timber_image_caption' ];
		$functions['get_timber_image_description'] = [ 'callable' => 'get_timber_image_description' ];

		return $functions;
	}

	/**
	 * Define global $_wp_additional_image_sizes with Timmy sizes
	 *
	 * Many WordPress functions and plugins rely on this global variable to integrate with images.
	 * We want this functionality to return all image sizes we defined ourselves.
	 *
	 * @since 0.10.0
	 */
	public function set_wp_additional_image_sizes() {
		global $_wp_additional_image_sizes;

		foreach ( Helper::get_image_sizes() as $key => $size ) {
			$sizes[] = $key;

			list( $width, $height ) = Helper::get_dimensions_for_size( $size );

			$crop = isset( $size['resize'][1] ) ? true : false;

			$_wp_additional_image_sizes[ $key ] = array(
				'width'  => $width,
				'height' => $height,
				'crop'   => $crop,
			);
		}
	}

	/**
	 * Tell WordPress to use our custom image configuration.
	 *
	 * @param array $sizes Image sizes.
	 *
	 * @return array Image sizes from image configuration.
	 */
	public function filter_intermediate_image_sizes( $sizes ) {
		return array_keys( Helper::get_image_sizes() );
	}

	/**
	 * Filter the image sizes automatically generated when uploading an image.
	 *
	 * We tell WordPress that we don’t have intermediate sizes, because we have our own image
	 * thingy we want to work with.
	 *
	 *
	 * @since 0.10.0
	 * @see wp_generate_attachment_metadata()
	 * @param array $sizes Image sizes.
	 * @return array
	 */
	public function filter_intermediate_image_sizes_advanced( $sizes ) {
		return array();
	}

	/**
	 * Filters the attachment meta data.
	 *
	 * Adds missing image sizes to the sizes array dynamically. This is useful when image sizes are
	 * requested in other places than your templates, e.g. through the media endpoint of REST API
	 * in the Block Editor.
	 *
	 * Image sizes can be missing when a new image size was added after an image was uploaded. This
	 * can be circumvented when image meta data is regenerated, e.g. with Regenerate Thumbnails.
	 *
	 * When an image size is present in the array, it doesn’t mean that the image will be generated
	 * automatically. You still have to control when an image is generated through the
	 * `post_types` configuration key. Otherwise, the full size will be used as a fallback.
	 *
	 * @since 0.14.3
	 *
	 * @param array|bool $meta_data     Array of meta data for the given attachment, or false
	 *                                  if the object does not exist.
	 * @param int        $attachment_id Attachment post ID.
	 *
	 * @return array|bool
	 */
	public function filter_attachment_metadata( $meta_data, $attachment_id ) {
		/**
		 * Bail out if no meta data is present yet or if the attachment is not an image. Meta data
		 * will be empty when uploading images.
		 */
		if ( empty( $meta_data )
			|| ! is_array( $meta_data )
			|| ! isset( $meta_data['file'] )
			|| ! wp_attachment_is_image( $attachment_id )
		) {
			return $meta_data;
		}

		$missing_sizes    = [];
		$configured_sizes = Helper::get_image_sizes();

		if ( ! isset( $meta_data['sizes'] ) && ! empty( $configured_sizes ) ) {
			$meta_data['sizes'] = [];
		}

		$sizes = array_keys( $meta_data['sizes'] );

		foreach ( $configured_sizes as $size => $config ) {
			if ( ! in_array( $size, $sizes, true ) ) {
				$missing_sizes[ $size ] = $config;
			}
		}

		if ( ! empty( $missing_sizes ) ) {
			foreach ( $missing_sizes as $size => $config ) {
				$meta_data['sizes'][ $size ] = $this->generate_meta_size(
					$meta_data,
					$meta_data['file'],
					$meta_data['width'],
					$meta_data['height']
				);
			}
		}

		return $meta_data;
	}

	/**
	 * Deletes all existing image sizes for that file.
	 *
	 * This function will run when you upload an image. It will also run if you
	 * run plugins like Regenerate Thumbnails. All additional images sizes
	 * registered with Timber will be first deleted.
	 *
	 * Because Timber also creates image sizes when they’re needed, we can
	 * safely do this.
	 *
	 * By running with a priority of 5, we make sure that generated image files
	 * are not present before other plugins run their filters.
	 */
	public function delete_generated_image_sizes( $meta_data, $attachment_id ) {
		/**
		 * Don’t automatically generate image sizes on upload for SVG and GIF
		 * images. GIF images will still be resized when requested on the fly.
		 */
		if ( self::ignore_attachment( $attachment_id ) ) {
			return $meta_data;
		}

		// Timber needs the file src as a URL.
		$file_src = Helper::get_original_attachment_url( $attachment_id );

		Timber\ImageHelper::delete_generated_files( $file_src );

		return $meta_data;
	}

	/**
	 * Hooks into the filter that generates additional image sizes to generate
	 * all additional image sizes with TimberImageHelper.
	 *
	 * This function will run when you upload an image. It will also run if you
	 * run a plugin like Regenerate Thumbnails. All additional images sizes
	 * registered with Timber will be regenerated through Timmy.
	 *
	 * By running this with a priority of 30, we make sure that image sizes are
	 * generated after other plugins have run their filters.
	 *
	 * @param array $meta_data     Meta data for an attachment.
	 * @param int   $attachment_id Attachment ID.
	 *
	 * @return array $meta_data
	 */
	public function filter_wp_generate_attachment_metadata( $meta_data, $attachment_id ) {
		/**
		 * Don’t automatically generate image sizes on upload for SVG and GIF
		 * images. GIF images will still be resized when requested on the fly.
		 */
		if ( self::ignore_attachment( $attachment_id ) ) {
			return $meta_data;
		}

		$attachment = get_post( $attachment_id );

		$meta_data['sizes'] = $this->generate_image_sizes( $attachment );

		$this->generate_srcset_sizes( $attachment );

		return $meta_data;
	}

	/**
	 * Replace the default image sizes with the sizes from the image config.
	 *
	 * The image will only be shown if the config key 'show_in_ui' is not false.
	 */
	public function filter_image_size_names_choose() {
		return $this->image_sizes_for_ui;
	}

	/**
	 * Add the same sizes to ACF image field options as when we choose an image size in the content
	 * editor.
	 *
	 * @since 0.10.0
	 *
	 * @param  array $sizes Sizes prepared by ACF.
	 *
	 * @return array Our own image sizes
	 */
	public function filter_acf_get_image_sizes( $sizes ) {
		return $this->image_sizes_for_ui;
	}

	/**
	 * Build up array of image sizes to choose from in the backend.
	 *
	 * @since 0.12.0
	 *
	 * @return array Array of image sizes from image config for Timmy.
	 */
	public function get_image_sizes_for_ui() {
		// We start from scratch and build our own sizes array.
		$sizes = array();

		// Build up new array of image sizes.
		foreach ( Helper::get_image_sizes() as $key => $size ) {
			// Do not add our own size if it is set to false in the image config.
			if ( isset( $size['show_in_ui'] ) && false === $size['show_in_ui'] ) {
				continue;
			}

			$name = $key;

			if ( isset( $size['name'] ) ) {
				$name = $size['name'] . ' (' . $key . ')';
			}

			$sizes[ $key ] = $name;
		}

		/**
		 * Re-add full size so it can still be selected.
		 *
		 * The full size is needed, if a e.g. a logo has to be displayed in the page content and no
		 * predefined size fits.
		 */
		$sizes['full'] = __( 'Full Size' );

		return $sizes;
	}

	/**
	 * Creates an image size based on the parameters given in the image configuration.
	 *
	 * @param bool         $return        Whether to short-circuit the image downsize.
	 * @param int          $attachment_id Attachment ID for image.
	 * @param array|string $size          Size of image. Image size or array of width and height
	 *                                    values (in that order).
	 *
	 * @return bool|array Array containing the image URL, width, height, and boolean for whether
	 *                    the image is an intermediate size. False on failure.
	 */
	public function filter_image_downsize( $return, $attachment_id, $size ) {
		// Timber needs the file src as a URL. Also checks if ID belongs to an attachment.
		$file_src = Helper::get_original_attachment_url( $attachment_id );

		if ( ! $file_src ) {
			return false;
		}

		// When media files are requested through an AJAX call, an action will be present in $_POST.
		$action = is_admin() && isset( $_POST['action'] )
			? htmlspecialchars( $_POST['action'] )
			: false;

		$attachment = get_post( $attachment_id );
		$mime_type  = $attachment->post_mime_type;

		// Bail out if mime type can’t be determined.
		if ( ! $mime_type ) {
			return $return;
		}

		$ignore = false;

		/**
		 * Filters whether we should resize an image size.
		 *
		 * When true is returned in this filter, the function will bail out early and the
		 * image will not be processed further.
		 *
		 * @since 0.13.0
		 *
		 * @param bool   $ignore     Whether to ignore an image size. Default false.
		 * @param int    $attachment The attachment post.
		 * @param string $size       The requested image size.
		 * @param string $file_src   The file src URL.
		 */
		$ignore = apply_filters( 'timmy/resize/ignore',
			$ignore,
			$attachment,
			$size,
			$file_src
		);

		if ( true === $ignore ) {
			return $return;
		}

		/**
		 * Return thumbnail size when media files are requested through an AJAX call.
		 *
		 * When image data is prepared for the Media view, WordPress calls 'image_size_names_choose'
		 * to get all selectable sizes for the backend and then 'image_downsize' to get all the
		 * image data. All image sizes that don’t exist yet would be generated, which probably
		 * causes a max execution timeout error.
		 *
		 * We make sure that for the Media view, we only return the thumbnail size for an image. If
		 * the thumbnail size doesn’t exist yet, it is generated.
		 *
		 *
		 * @see   wp_prepare_attachment_for_js()
		 * @since 0.12.0
		 */
		if ( 'query-attachments' === $action ) {
			$thumbnail_size = Helper::get_thumbnail_size();

			list( $width, $height ) = Helper::get_dimensions_for_size( $thumbnail_size );

			$crop  = Helper::get_crop_for_size( $thumbnail_size );
			$force = Helper::get_force_for_size( $thumbnail_size );

			// Resize to thumbnail size.
			$src = self::resize( $thumbnail_size, $file_src, $width, $height, $crop, $force );

			/**
			 * Get original dimensions for a file that are used for the image data and the select
			 * input when an image size can be chosen in the backend.
			 *
			 * The src is still the thumbnail size, so that it doesn’t trigger a resize.
			 */
			$original_size = Helper::get_image_size( $size );

			list( $width, $height ) = Helper::get_dimensions_for_size( $original_size );

			return array( $src, $width, $height, true );
		}

		/**
		 * Bailout if a GIF is uploaded in the backend and a size other than the thumbnail size is
		 * requested.
		 *
		 * Generating sizes for a GIF takes a lot of performance. When uploading a GIF, this could
		 * quickly lead to an error if the maximum execution time is reached. That’s why Timmy only
		 * generates the thumbnail size. This leads to better performance in the Media Library, when
		 * only small GIFs have to be loaded. Other GIF sizes will still be generated on the fly.
		 *
		 * @since 0.11.0
		 */
		if ( 'upload-attachment' === $action && 'image/gif' === $mime_type ) {
			if ( Helper::get_thumbnail_size() !== $size ) {
				return $return;
			}
		}

		/**
		 * Return full size when full size of image is requested.
		 *
		 * Fall back to a width and height of '0' if metadata can’t be read.
		 * Certain functions or plugins ask for the full size of an image.
		 */
		if ( in_array( $size, array( 'original', 'full' ), true ) ) {
			$meta_data = wp_get_attachment_metadata( $attachment_id, true );

			if ( ! empty( $meta_data['width'] ) && ! empty( $meta_data['height'] ) ) {
				return array(
					$file_src,
					$meta_data['width'],
					$meta_data['height'],
					false,
				);
			}

			return array( $file_src, 0, 0, false );
		}

		$img_sizes = Helper::get_image_sizes();

		// Sort out which image size we need to take from our own image configuration.
		if ( ! is_array( $size ) && isset( $img_sizes[ $size ] ) ) {
			$img_size = $img_sizes[ $size ];

			if ( ! $this->timber_should_resize( $attachment->post_parent, $img_size ) ) {
				return $return;
			}
		} else {
			$img_size = Helper::get_thumbnail_size();
		}

		if ( ! $img_size ) {
			return $return;
		}

		// Get meta data not filtered by Timmy.
		$meta_data = wp_get_attachment_metadata( $attachment_id, true );

		$max_width  = $meta_data['width'];
		$max_height = $meta_data['height'];

		list( $width, $height ) = Helper::get_dimensions_for_size( $img_size );
		list( $width, $height ) = Helper::get_dimensions_upscale( $width, $height, [
			'upscale'    => Helper::get_upscale_for_size( $img_size ),
			'max_width'  => $max_width,
			'max_height' => $max_height,
		] );

		// Bail out if no resize is needed.
		if ( $width === $max_width ) {
			$height = Helper::maybe_fix_height( $height, $width, $max_width, $max_height );

			return [ $file_src, $width, $height, false ];
		}

		$crop  = Helper::get_crop_for_size( $img_size );
		$force = Helper::get_force_for_size( $img_size );

		// Resize the image for that size.
		$src = self::resize( $img_size, $file_src, $width, $height, $crop, $force );

		// Maybe convert to WebP.
		if (
			self::should_convert_to_webp( $file_src, $img_size )
			/**
			 * We don’t want to convert to WebP when the image size is saved in the metadata of an
			 * image, which happens when generating a downsized version. Saving the image sizes in
			 * the metadata happens inside the wp_generate_attachment_metadata filter, which we can
			 * check for.
			 *
			 * If WebP images would be saved in the attachment metadata, WebP images would also be
			 * used by other plugins, which can lead to unextected side effects. For example,
			 * Yoast SEO uses this for generating the OG image in the HTML head. But platforms like
			 * LinkedIn don’t support WebP images (yet). So we shouldn’t use it there.
			 */
			&& ! doing_filter( 'wp_generate_attachment_metadata' )
		) {
			$src = self::to_webp( $src, $img_size );
		}

		// When the input size is an array of width and height.
		if ( is_array( $size ) ) {
			$width  = $size[0];
			$height = $size[1];
		}

		$height = Helper::maybe_fix_height( $height, $width, $max_width, $max_height );

		/**
		 * For the return, we also send in a fourth parameter, which stands for is_intermediate.
		 * It is true if $src is a resized image, false if it is the original.
		 */
		return array( $src, $width, $height, true );
	}

	/**
	 * Filters image data before it is returned to the Media view.
	 *
	 * When the details for an image are requested in the Media view, WordPress displays the large
	 * size of an image if it exists, otherwise it displays the full size. If the large size
	 * exists, this filter replaces the large size with the full size of an image, because if the
	 * large size is changed, it would cause regeneration of all the images, which results in the
	 * Media view to become unresponsive and finally run into a max excecution time error.
	 *
	 * @see wp-includes/media-template.php
	 *
	 * @param array $response   Response data.
	 * @param array $attachment Attachment data.
	 * @param array $meta       Meta data for an image.
	 *
	 * @return array
	 */
	public function filter_wp_prepare_attachment_for_js( $response, $attachment, $meta ) {
		if ( isset( $response['sizes']['large'] ) ) {
			$response['sizes']['large'] = $response['sizes']['full'];
		}

		return $response;
	}

	/**
	 * Convert an image into a TimberImage.
	 *
	 * @param mixed $timber_image The ID of the image, an array containing an ID key or an instance
	 *                            of Timber\Image.
	 *
	 * @return mixed              Instance of Timber\Image.
	 */
	public static function get_timber_image( $timber_image ) {
		if ( is_numeric( $timber_image ) ) {
			$timber_image = Timber::get_image( $timber_image );
		} elseif ( is_array( $timber_image ) && isset( $timber_image['ID'] ) ) {
			// Convert an ACF image array into a Timber image.
			$timber_image = Timber::get_image( $timber_image['ID'] );
		}

		// Check if non-empty TimberImage was found before returning it.
		if ( ! $timber_image instanceof Timber\Image
			|| empty( $timber_image->post_type )
			|| 'attachment' !== $timber_image->post_type
		) {
			return false;
		}

		return $timber_image;
	}

	/**
	 * Get an array with image parameters required for generating a new size.
	 *
	 * @since 0.10.0
	 *
	 * @param Timber\Image|int $timber_image Instance of TimberImage.
	 * @param array            $img_size     Image configuration array for image size to be used.
	 *
	 * @return array A non-associative array with $file_src, $width, $height, $crop, $force,
	 *               $max_width, $undersized (in that order). Thought to be used with list().
	 */
	public static function get_image_params( $timber_image, $img_size ) {
		list(
			$file_src,
			$max_width,
			$max_height
		) = wp_get_attachment_image_src( (int) $timber_image->ID, 'full' );

		$upscale = Helper::get_upscale_for_size( $img_size );

		// Get values for the default image size.
		list( $width, $height ) = Helper::get_dimensions_for_size( $img_size );

		// Update upscale style_attr parameter.
		// @todo Don’t forget to put this at the right place.
		if ( $upscale['style_attr'] ) {
			if ( $width > $max_width ) {
				// Restrict to width.
				$upscale['style_attr'] = 'width';
			} elseif ( $height > 0 && $height > $max_height ) {
				// Restrict to height.
				$upscale['style_attr'] = 'height';
			}
		}

		// Get updated width and height.
		list( $width, $height ) = Helper::get_dimensions_upscale( $width, $height, [
			'upscale'    => $upscale,
			'max_width'  => $max_width,
			'max_height' => $max_height,
		] );

		$crop  = Helper::get_crop_for_size( $img_size );
		$force = Helper::get_force_for_size( $img_size );

		return array(
			$file_src,
			$width,
			$height,
			$crop,
			$force,
			$max_width,
			$max_height,
			$upscale,
		);
	}

	/**
	 * Resize an image and apply letterbox and tojpg filters when defined.
	 *
	 * @since 0.9.2
	 *
	 * @param  array  $img_size Configuration values for an image size.
	 * @param  string $file_src The src of the original image.
	 * @param  int    $width    The width the new image should be resized to.
	 * @param  int    $height   The height the new image should be resized to.
	 * @param  string $crop     Optional. Cropping option. Default 'default'.
	 * @param  bool   $force    Optional. Force cropping. Default false.
	 *
	 * @return string The src of the image.
	 */
	public static function resize( $img_size, $file_src, $width, $height, $crop, $force ) {
		// Check if image should be converted to JPG.
		if ( self::should_convert_to_jpg( $img_size, $file_src ) ) {
			// Sort out background color which will show instead of transparency.
			$bgcolor  = is_string( $img_size['tojpg'] ) ? $img_size['tojpg'] : '#FFFFFF';
			$file_src = Timber\ImageHelper::img_to_jpg( $file_src, $bgcolor, $force );
		}

		// Check for letterbox parameter.
		if ( isset( $img_size['letterbox'] ) && $img_size['letterbox']
			&& $width > 0 && $height > 0
		) {
			$color = is_string( $img_size['letterbox'] ) ? $img_size['letterbox'] : '#000000';
			$file_src = Timber\ImageHelper::letterbox( $file_src, $width, $height, $color );
		} else {
			$file_src = Timber\ImageHelper::resize( $file_src, $width, $height, $crop, $force );
		}

		return $file_src;
	}

	/**
	 * Converts an image to WebP.
	 *
	 * Uses 80 as the defaultquality setting, because a higher setting might result in a bigger file
	 * size. As quoted from the WebP FAQ:
	 *
	 *      "If the source is in lossy format and you are trying to compress it as a lossy WebP with
	 *      higher quality setting. For instance, trying to convert a JPEG file saved at quality 80
	 *      to a WebP file with quality 95 will usually result in a larger file, even if both
	 *      formats are lossy. Assessing the source's quality is often impossible, so it is advised
	 *      to lower the target WebP quality if the file size is consistently larger."
	 *
	 * @link https://developers.google.com/speed/webp/faq
	 *
	 * @param $file_src
	 * @param $size
	 *
	 * @return string
	 */
	public static function to_webp( $file_src, $size ) {
		$args = [
			'quality' => 80,
			'force'   => false,
		];

		if ( isset( $size['webp'] ) && is_array( $size['webp'] ) ) {
			$args = wp_parse_args( $size['webp'], $args );
		}

		return Timber\ImageHelper::img_to_webp( $file_src, $args['quality'], $args['force'] );
	}

	/**
	 * Check if an image should be converted to JPG.
	 *
	 * Checks for the existence of the `tojpg` parameter and whether the image is a PDF. Trying to
	 * convert PDF images will lead to an error, which we need to catch here.
	 *
	 * @since 0.13.2
	 *
	 * @param array  $img_size Configuration values for an image size.
	 * @param string $file_src The src of the original image.
	 *
	 * @return bool Whether the image should be converted.
	 */
	public static function should_convert_to_jpg( $img_size, $file_src ) {
		if ( isset( $img_size['tojpg'] )
			&& $img_size['tojpg']
			&& 'application/pdf' !== wp_check_filetype(
				$file_src,
				Helper::get_mime_types()
            )['type']
		) {
			return true;
		}

		return false;
	}

	/**
	 * Checks whether an image should be converted to webp.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file_src The src of the original image.
	 * @param array  $img_size Configuration values for an image size.
	 *
	 * @return bool
	 */
	public static function should_convert_to_webp( $file_src, $img_size ) {
		if ( isset( $img_size['webp'] )
			&& $img_size['webp']
			&& 'application/pdf' !== wp_check_filetype(
				$file_src,
				Helper::get_mime_types()
            )['type']
		) {
			return true;
		}

		return false;
	}

	/**
	 * Get the actual width at which the image will be displayed.
	 *
	 * When 0 is passed to Timber as a width, it calculates the image ratio based on the height of
	 * the image. We have to account for that, when we use the responsive image, because in the
	 * srcset, there can’t be a value like "image.jpg 0w". So we have to calculate the width based
	 * on the values we have.
	 *
	 * @todo Deprecate this.
	 * @since 0.9.3
	 *
	 * @param  int          $width        The value of the resize parameter for width.
	 * @param  int          $height       The value of the resize parameter for height.
	 * @param  Timber\Image $timber_image Instance of TimberImage.
	 *
	 * @return int The width at which the image will be displayed.
	 */
	public static function get_width_key( $width, $height, $timber_image ) {
		if ( 0 === (int) $width ) {
			/**
			 * Calculate image width based on image ratio and height. We need a rounded value
			 * because we will use this number as an array key and for defining the srcset size in
			 * pixel values.
			 */
			return (int) round( $timber_image->aspect() * $height );
		}

		return (int) $width;
	}

	/**
	 * Generate image sizes defined for Timmy with Timber\ImageHelper.
	 *
	 * @param \WP_Post $attachment The attachment for which all images should be resized.
	 *
	 * @return array An array of generated image sizes that will can saved in attachment metdata.
	 */
	private function generate_image_sizes( $attachment ) {
		$sizes     = [];
		$img_sizes = Helper::get_image_sizes();

		foreach ( $img_sizes as $key => $img_size ) {
			$generated_meta = $this->generate_image_size( $attachment, $key, $img_size );

			if ( ! empty( $generated_meta ) ) {
				$sizes[ $key ] = $generated_meta;
			}
		}

		return $sizes;
	}

	/**
	 * Generates an image size.
	 *
	 * @param \WP_Post $attachment An attachment.
	 * @param string   $size       The image size key.
	 * @param array    $img_size   The configuration array for an image size.
	 *
	 * @return array Generated meta data for an image.
	 */
	private function generate_image_size( $attachment, $size, $img_size ) {
		if ( ! $this->timber_should_resize( $attachment->post_parent, $img_size ) ) {
			return [];
		}

		// Create downsized version of the image.
		$downsized = image_downsize( $attachment->ID, $size );

		// Bail out if there was an error while downsizing the image.
		if ( ! is_array( $downsized ) ) {
			return [];
		}

		list( $file_src, $file_width, $file_height ) = $downsized;

		// Get unfiltered meta data to prevent potential recursion.
		$meta_data = wp_get_attachment_metadata( $attachment->ID, true );

		$generated_meta = $this->generate_meta_size(
			$meta_data,
			$file_src,
			$file_width,
			$file_height
		);

		return $generated_meta;
	}

	/**
	 * Generates the srcset sizes for an image.
	 *
	 * @param \WP_Post $attachment An attachment.
	 */
	private function generate_srcset_sizes( $attachment ) {
		$img_sizes = Helper::get_image_sizes();

		foreach ( $img_sizes as $key => $img_size ) {
			$this->generate_srcset_size( $attachment, $key, $img_size );
		}
	}

	/**
	 * Generates the srcset sizes for an image size.
	 *
	 * @param \WP_Post $attachment An attachment.
	 * @param string   $size       The image size key.
	 * @param array    $img_size   The configuration array for an image size.
	 */
	private function generate_srcset_size( $attachment, $size, $img_size ) {
		if ( ! $this->timber_should_resize( $attachment->post_parent, $img_size ) ) {
			return;
		}

		/**
		 * Filters whether srcset sizes should be generated when an image is uploaded.
		 *
		 * @since 0.13.0
		 *
		 * @param bool     $generate_srcset_sizes Whether to generate srcset sizes. Passing false will prevent
		 *                                        srcset sizes to generated when an image is uploaded. Default false.
		 * @param string   $size                  The image size key.
		 * @param array    $img_size              The image size configuration array.
		 * @param \WP_Post $attachment            The attachment post.
		 */
		$generate_srcset_sizes = apply_filters( 'timmy/generate_srcset_sizes', false, $size, $img_size, $attachment );

		// Get setting from image configuration.
		$generate_srcset_sizes = isset( $img_size['generate_srcset_sizes'] )
			? $img_size['generate_srcset_sizes']
			: $generate_srcset_sizes;

		// Bail out if srcset sizes shouldn’t be generated.
		if ( false === $generate_srcset_sizes ) {
			return;
		}

		// Get values for the default image.
		$crop  = Helper::get_crop_for_size( $img_size );
		$force = Helper::get_force_for_size( $img_size );

		// Timber needs the file src as an URL. Also checks if ID belongs to an attachment.
		$file_src = Helper::get_original_attachment_url( $attachment->ID );

		if ( ! isset( $img_size['srcset'] ) ) {
			return;
		}

		$upscale = Helper::get_upscale_for_size( $img_size );

		// Get meta data not filtered by Timmy.
		$meta_data = wp_get_attachment_metadata( $attachment->ID, true );

		$max_width  = $meta_data['width'];
		$max_height = $meta_data['height'];

		// Generate additional image sizes used for srcset.
		foreach ( $img_size['srcset'] as $srcset_size ) {
			list( $width, $height ) = Helper::get_dimensions_for_srcset_size(
				$img_size['resize'],
				$srcset_size
			);

			list( $width, $height ) = Helper::get_dimensions_upscale( $width, $height, [
				'upscale'    => $upscale,
				'max_width'  => $max_width,
				'max_height' => $max_height,
			] );

			// Skip if no resize is needed.
			if ( $width === $max_width ) {
				continue;
			}

			// For the new source, we use the same $crop and $force values as the default image.
			$src = self::resize( $img_size, $file_src, $width, $height, $crop, $force );

			// Maybe convert to webp.
			if ( self::should_convert_to_webp( $file_src, $img_size ) ) {
				self::to_webp( $src, $img_size );
			}
		}
	}

	/**
	 * Creates an image definition array that will be used for attachment metadata.
	 *
	 * WordPress saves an array with the file src, the width, the height and the mime type of an
	 * attachment in the postmeta database table. By generating this array (and eventually saving it
	 * in the database), we can improve the compatibility with the core image functionality.
	 *
	 * Corrects width and height values of '0' by calculating them from the original image ratio,
	 * if meta data is available.
	 *
	 * @since 1.4.3
	 *
	 * @param array  $meta_data   Image meta data, containing the original width and height of the
	 *                            file.
	 * @param string $file_src    The file src.
	 * @param int    $file_width  The known width of the size.
	 * @param int    $file_height The known height of the size.
	 *
	 * @return array
	 */
	private function generate_meta_size( $meta_data, $file_src, $file_width, $file_height ) {
		if ( ! empty( $meta_data['height'] ) && ! empty( $meta_data['width'] ) ) {
			if ( 0 === (int) $file_width && ! empty( $meta_data['height'] ) ) {
				$file_width = intval(
					round( $file_height / $meta_data['height'] * $meta_data['width'] )
				);
			} elseif ( 0 === (int) $file_height ) {
				$file_height = intval(
					round( $file_width / $meta_data['width'] * $meta_data['height'] )
				);
			}
		}

		if ( is_numeric( $file_width ) && is_numeric( $file_height ) ) {
			return [
				'file'      => wp_basename( $file_src ),
				'width'     => $file_width,
				'height'    => $file_height,
				'mime-type' => wp_check_filetype(
					$file_src,
					Helper::get_mime_types()
				)['type'],
			];
		}

		return [];
	}

	/**
	 * Check if we should pregenerate an image size based on the image configuration.
	 *
	 * @param  int   $attachment_parent_id Parent ID of the attachment.
	 * @param  array $img_size             The image configuration array.
	 *
	 * @return bool Whether the image should or can be resized.
	 */
	private function timber_should_resize( $attachment_parent_id, $img_size ) {
		/**
		 * Normally we don’t have a post type associated with the attachment
		 *
		 * We use an empty array to tell the function that there is no post type
		 * associated with the attachment.
		 */
		$attachment_post_type = array( '' );

		// Check if image is attached to a post and sort out post type.
		if ( 0 !== $attachment_parent_id ) {
			$parent = get_post( $attachment_parent_id );

			// Parent post could have been deleted.
			if ( $parent ) {
				$attachment_post_type = array( $parent->post_type );
			}
		}

		return self::is_size_for_post_type( $img_size, $attachment_post_type );
	}

	/**
	 * Checks whether an image size is restricted to certain post types.
	 *
	 * @param array        $img_size             Image configuration array.
	 * @param string|array $attachment_post_type Post type for attachment.
	 *
	 * @return bool
	 */
	public static function is_size_for_post_type( $img_size, $attachment_post_type ) {
		if ( ! is_array( $attachment_post_type ) ) {
			$attachment_post_type = array( $attachment_post_type );
		}

		// Reset post types that should be applied as a standard.
		$post_types_to_apply = array( '', 'page', 'post' );

		/**
		 * When a post type is given in the arguments, we generate the size
		 * only if the attachment is associated with that post.
		 */
		if ( array_key_exists( 'post_types', $img_size ) ) {
			$post_types_to_apply = $img_size['post_types'];
		}

		if ( ! in_array( 'all', $post_types_to_apply, true ) ) {
			// Check if we should really resize that picture.
			$intersections = array_intersect( $post_types_to_apply, $attachment_post_type );

			if ( ! empty( $intersections ) ) {
				return true;
			}
		} else {
			return true;
		}

		return false;
	}

	/**
	 * Check for errors in image size config.
	 *
	 * This function only runs when WP_DEBUG is set to true.
	 *
	 * @since 0.11.0
	 */
	public function validate_get_image_sizes() {
		if ( WP_DEBUG ) {
			$sizes = Helper::get_image_sizes();

			if ( isset( $sizes['full'] ) ) {
				Helper::notice( 'You can’t use "full" as a key for an image size in get_image_sizes(). The key "full" is reserved for the full size of an image in WordPress.' );
			}
		}
	}

	/**
	 * Ignore resizing files that are not images or non-resizable images.
	 *
	 * @since 0.13.0
	 *
	 * @param bool     $return     Whether to ignore an image size.
	 * @param \WP_Post $attachment Attachment post.
	 *
	 * @return bool
	 */
	public static function ignore_unallowed_files( $return, $attachment ) {
		if ( self::ignore_attachment( $attachment->ID ) ) {
			// Ignore.
			return true;
		}

		return $return;
	}

	/**
	 * Checks whether a file should be ignored based on the file extension.
	 *
	 * This is similar to wp_attachment_is_image(), except that it also ignores GIF images.
	 *
	 * @since 0.14.9
	 *
	 * @param int $attachment_id An attachment ID.
	 *
	 * @return bool
	 */
	private static function ignore_attachment( $attachment_id ) {
		$file = get_attached_file( $attachment_id );

		if ( ! $file ) {
			return true;
		}

		$allowed_ext = [ 'jpg', 'jpeg', 'jpe', 'png' ];
		$file_ext    = wp_check_filetype( $file, Helper::get_mime_types() )['ext'];

		// We can’t use wp_attachment_is() for the check, because that will also allow GIF images.
		if ( ! $file_ext || ! in_array( $file_ext, $allowed_ext, true ) ) {
			// Ignore.
			return true;
		}

		return false;
	}
}
