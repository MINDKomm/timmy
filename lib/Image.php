<?php

namespace Timmy;

/**
 * Class Image
 *
 * @since 1.0.0
 */
class Image {
	/**
	 * Image ID.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * Image size configuration array.
	 *
	 * @var array
	 */
	protected $size;

	/**
	 * Image size configuration key.
	 *
	 * @var string
	 */
	protected $size_key;

	protected $full_src;

	protected $meta;

	protected $max_width;

	protected $max_height;

	protected $resize_crop;

	protected $resize_force;

	protected $upscale;

	final protected function __construct() {}

	/**
	 * Creates an image.
	 *
	 * @param int   $image_id Attachment image id.
	 * @param array $size     Image size configuration.
	 *
	 * @return \Timmy\Image
	 */
	public static function build( int $image_id, array $size = null ) {
		$image = new static;

		$image->id   = $image_id;
		$image->size = $size;

		$image->resize_crop  = Helper::get_crop_for_size( $image->size );
		$image->resize_force = Helper::get_force_for_size( $image->size );
		$image->upscale      = Helper::get_upscale_for_size( $image->size );

		return $image;
	}

	public function set_size_key( $key ) {
		$this->size_key = $key;
	}

	public function size() {
		return $this->size;
	}

	protected function load_attachment_image_src() {
		if ( empty( $this->full_src ) ) {
			list(
				$this->full_src,
				$this->max_width,
				$this->max_height
			) = wp_get_attachment_image_src( $this->id, 'full' );
		}
	}

	protected function load_attachment_meta_data() {
		if ( empty( $this->meta ) ) {
			/**
			 * Gets metadata not filtered by Timmy.
			 *
			 * @todo: Add a PR to Timber repository that saves the width and the height of an image in the
			 *      metadata. Timber already calls wp_get_attachment_metadata(), but discards the width and
			 *      height.
			 */
			$this->meta = wp_get_attachment_metadata( $this->id, true );
		}
	}

	/**
	 * Gets full image src.
	 *
	 * @return string
	 */
	public function full_src() {
		$this->load_attachment_image_src();

		return $this->full_src;
	}

	/**
	 * Gets the src (url) for the image.
	 *
	 * @return string Image src.
	 */
	public function src() {
		// @todo Test with false image or wrong image size key.

		/**
		 * Directly return full source when full source or an SVG image is requested.
		 *
		 * The full size may be a scaled version of the image. To always request the original
		 * version, 'original' has to be used as the size.
		 */
		if ( 'full' === $this->size_key || $this->is_svg() ) {
			// Deliberately return the attachment URL, which can be a 'scaled' version of an image.
			return wp_get_attachment_url( $this->id );
		} elseif ( 'original' === $this->size_key ) {
			return Helper::get_original_attachment_url( $this->id );
		}

		// Resize the image for that size.
		return Timmy::resize(
			$this->size,
			$this->full_src(),
			$this->width(),
			/**
			 * Always use height from size configuration, because otherwise we would get images with
			 * different filenames, causing a lot of images to be regenerated.
			 */
			Helper::get_height_for_size( $this->size ),
			$this->resize_crop,
			$this->resize_force
		);
	}

	/**
	 * Returns a fallback for the src attribute to provide valid image markup and prevent double
	 * downloads in older browsers.
	 *
	 * @link http://scottjehl.github.io/picturefill/#support
	 *
	 * @return string|bool Image src. False if image can’t be found or no srcset is available.
	 */
	public function src_default() {
		/**
		 * Filters whether a default src attribute should be added as a fallback.
		 *
		 * If this filter returns `true` (the default), then a base64 string will be used as a fallback
		 * to prevent double downloading images in older browsers. If this filter returns `false`, then
		 * no src attribute will be added to the image. Use the `timmy/src_default` filter to define
		 * what should be used as the src attribute’s value.
		 *
		 * @param bool $use_src_default Whether to apply the fallback. Default true.
		 */
		$use_src_default = apply_filters( 'timmy/use_src_default', true );

		if ( ! $use_src_default ) {
			return false;
		}

		// Default fallback src.
		$src_default = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

		/**
		 * Filters the src default.
		 *
		 * @param string       $src_default Src default. Default
		 *                                  `data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7`.
		 * @param \Timmy\Image $image       Timmy image instance.
		 */
		$src_default = apply_filters( 'timmy/src_default', $src_default, $this );

		return $src_default;
	}

	public function srcset() {
		$return  = false;
		$width   = $this->width();
		/**
		 * Always use height from size configuration, because otherwise we would get images with
		 * different filenames, causing a lot of images to be regenerated.
		 */
		$height = Helper::get_height_for_size( $this->size );
		$srcset  = [];

		list( , $height ) = Helper::get_dimensions_upscale( $width, $height, [
			'upscale'    => $this->upscale,
			'resize'     => $this->size['resize'],
			'max_width'  => $this->max_width(),
			'max_height' => $this->max_height(),
		] );

		// Get default size for image.
		$default_size = Timmy::resize(
			$this->size,
			$this->full_src(),
			$width,
			$height,
			$this->resize_crop,
			$this->resize_force
		);

		// Get proper width descriptor to handle width values of 0.
		$width_descriptor = $this->srcset_width_descriptor( $width, $height );

		// Add the image source with the width as the descriptor so that they can be sorted later.
		$srcset[ $width_descriptor ] = $default_size . " {$width_descriptor}w";

		// Add additional image sizes to srcset.
		if ( isset( $this->size['srcset'] ) ) {
			foreach ( $this->size['srcset'] as $srcset_src ) {
				list(
					$width_intermediate,
					$height_intermediate
				) = Helper::get_dimensions_for_srcset_size( $this->size['resize'], $srcset_src );

				// Bail out if the current size’s width is bigger than available width.
				if ( ! $this->upscale['allow']
					&& ( $width_intermediate > $this->max_width()
						|| ( 0 === $width_intermediate && $height_intermediate > $this->max_height() )
					)
				) {
					continue;
				}

				$width_descriptor = $this->srcset_width_descriptor( $width_intermediate, $height_intermediate );

				// Check for x-notation in srcset, e.g. '2x'.
				$suffix = is_string( $srcset_src ) && 'x' === substr( $srcset_src, -1, 1 )
					? " {$srcset_src}"
					: " {$width_descriptor}w";

				// For the new source, we use the same $crop and $force values as the default image.
				$srcset[ $width_descriptor ] = Timmy::resize(
					$this->size,
					$this->full_src(),
					$width_intermediate,
					$height_intermediate,
					$this->resize_crop,
					$this->resize_force
				) . $suffix;
			}
		}

		/**
		 * Only add responsive srcset and sizes attributes if there are any present.
		 *
		 * If there’s only one srcset src, it’s always the default size. In that case, we just add
		 * it as a src.
		 */
		if ( count( $srcset ) > 1 ) {
			// Sort entries from smallest to highest
			ksort( $srcset );

			$return = implode( ', ', $srcset );
		}

		return $return;
	}

	/**
	 * Gets the width descriptor for a srcset image resource.
	 *
	 * When 0 is passed to Timber as a width, it calculates the image ratio based on the height of
	 * the image. We have to account for that, when we use the responsive image, because in the
	 * srcset, there can’t be a value like "image.jpg 0w". So we have to calculate the width based
	 * on the values we have.
	 *
	 * @param int $width  The value of the resize parameter for width.
	 * @param int $height The value of the resize parameter for height.
	 *
	 * @return int The width at which the image will be displayed.
	 */
	protected function srcset_width_descriptor( $width, $height ) {
		if ( 0 === (int) $width ) {
			/**
			 * Calculate image width based on image ratio and height. We need a rounded value
			 * because we will use this number as an array key and for defining the srcset size in
			 * pixel values.
			 */
			return (int) round( $this->aspect_ratio() * $height );
		}

		return (int) $width;
	}

	/**
	 * Gets 'sizes' configuration from the image size.
	 *
	 * @return false|mixed
	 */
	public function sizes() {
		/**
		 * Check for 'sizes' option in image configuration.
		 * Before v0.10.0, this was just `size`'.
		 *
		 * @since 0.10.0
		 */
		if ( isset( $this->size['sizes'] ) ) {
			return $this->size['sizes'];
		} elseif ( isset( $this->size['size'] ) ) {
			Helper::notice( 'Timmy: The "size" key is deprecated and will be removed in a future version of Timmy. You should use "sizes" instead of "size" in your image configuration.' );

			/**
			 * For backwards compatibility.
			 *
			 * @deprecated since 1.0.0
			 * @todo       Remove in 2.0.0
			 */
			return $this->size['size'];
		}

		return false;
	}

	public function max_width() {
		if ( empty( $this->max_width ) ) {
			$this->load_attachment_meta_data();

			$this->max_width = $this->meta['width'];
		}

		return $this->max_width;
	}

	public function max_height() {
		if ( empty( $this->max_height ) ) {
			$this->load_attachment_meta_data();

			$this->max_height = $this->meta['height'];
		}

		return $this->max_height;
	}

	/**
	 * Gets the image width for a size.
	 *
	 * @return false|int False on error or image width.
	 */
	public function width() {
		list( $width, $height ) = Helper::get_dimensions_for_size( $this->size );
		list( $width ) = Helper::get_dimensions_upscale( $width, $height, [
			'upscale'    => $this->upscale,
			'resize'     => $this->size['resize'],
			'max_width'  => $this->max_width(),
			'max_height' => $this->max_height(),
		] );

		return $width;
	}

	public function height() {
		list( $width, $height ) = Helper::get_dimensions_for_size( $this->size );

		$height = Helper::maybe_fix_height( $height, $width, $this->max_width(), $this->max_height() );

		list( , $height ) = Helper::get_dimensions_upscale( $width, $height, [
			'upscale'    => $this->upscale,
			'resize'     => $this->size['resize'],
			'max_width'  => $this->max_width(),
			'max_height' => $this->max_height(),
		] );

		return $height;
	}

	/**
	 * Gets the aspect ratio for an image size.
	 *
	 * @return float|int
	 */
	public function aspect_ratio() {
		$width  = (int) $this->width();
		$height = (int) $this->height();

		if ( $height > 0 ) {
			return $width / $height;
		}

		return 0;
	}

	/**
	 * Gets value for loading attributes.
	 *
	 * @param string $value Optional. The value that is validated against a list of allowed values.
	 *                      Default 'lazy'.
	 *
	 * @return false|string
	 */
	public function loading( string $value = 'lazy' ) {
		if ( ! wp_lazy_loading_enabled( 'img', 'timmy' ) ) {
			return false;
		}

		$allowed_lazy_values = [ 'lazy', 'eager', 'auto' ];

		if ( ! empty( $value ) && in_array( $value, $allowed_lazy_values, true ) ) {
			return $value;
		}

		return false;
	}

	public function upscale() {
		 return $this->upscale;
	}

	/**
	 * Gets image style attribute.
	 *
	 * Sets width or height in px as a style attribute to act as max-width and max-height
	 * and prevent the image to be displayed bigger than it is.
	 *
	 * Using a style attribute is better than using width and height attributes, because width and
	 * height attributes are presentational, which means that any CSS will have higher specificity.
	 * If you automatically stretch images to the full width using "width: 100%", there’s no way you
	 * can prevent these images from growing bigger than they should. With a style attributes, that
	 * works.
	 *
	 * @since 0.10.0
	 */
	public function style() {
		if ( $this->upscale['style_attr'] ) {
			if ( 'width' === $this->upscale['style_attr'] ) {
				return 'width:' . $this->max_width . 'px;';
			} elseif ( 'height' === $this->upscale['style_attr'] ) {
				return 'height:' . $this->max_height . 'px;';
			}
		}

		return false;
	}

	public function responsive_attributes( $args ) {
		/**
		 * Default arguments for image markup.
		 *
		 * @since 0.12.0
		 */
		$default_args = [
			'attr_width'    => true,
			'attr_height'   => true,
			'lazy_srcset'   => false,
			'lazy_src'      => false,
			'lazy_sizes'    => false,
			'loading'       => 'lazy',
		];

		$args = wp_parse_args( $args, $default_args );

		$attributes = [];

		/**
		 * Directly return full source when full source or an SVG image is requested.
		 *
		 * The full size may be a scaled version of the image. To always request the original
		 * version, 'original' has to be used as the size.
		 */
		if ( in_array( $this->size_key, [ 'full', 'original' ], true ) || $this->is_svg() ) {
			if ( 'original' === $this->size_key ) {
				$attributes['src'] = Helper::get_original_attachment_url( $this->id );
			} else {
				// Deliberately get the attachment URL, which can be a 'scaled' version of an image.
				$attributes['src'] = wp_get_attachment_url( $this->id );
			}
		} else {
			$srcset = $this->srcset();

			if ( $srcset ) {
				$attributes['srcset'] = $srcset;
				$attributes['src']    = $this->src_default();
				$attributes['sizes']  = $this->sizes();
			} else {
				// Get default size for image.
				$attributes['src'] =  Timmy::resize(
					$this->size,
					$this->full_src(),
					$this->width(),
					/**
					 * Always use height from size configuration, because otherwise we would get images with
					 * different filenames, causing a lot of images to be regenerated.
					 */
					Helper::get_height_for_size( $this->size ),
					$this->resize_crop(),
					$this->resize_force()
				);
			}

			$attributes['style'] = $this->style();
		}

		if ( $args['attr_width'] ) {
			$attributes['width'] = $this->width();
			$attributes['style'] = false;
		}

		if ( $args['attr_height'] ) {
			$attributes['height'] = $this->height();
			$attributes['style'] = false;
		}

		// Lazy-loading.
		$attributes['loading'] = $this->loading( $args['loading'] );

		/**
		 * Maybe rename attributes with "data-" prefixes.
		 */

		if ( $args['lazy_srcset'] && ! empty( $attributes['srcset'] ) ) {
			$attributes['data-srcset'] = $attributes['srcset'];
			unset( $attributes['srcset'] );
		}

		if ( $args['lazy_src'] && ! empty( $attributes['src'] ) ) {
			$attributes['data-src'] = $attributes['src'];
			unset( $attributes['src'] );
		}

		if ( $args['lazy_sizes'] && ! empty( $attributes['sizes'] ) ) {
			$attributes['data-sizes'] = $attributes['sizes'];
			unset( $attributes['sizes'] );
		}

		// Remove any falsy attributes.
		$attributes = array_filter( $attributes );

		return $attributes;
	}

	public function resize_crop() {
		return $this->resize_crop;
	}

	public function resize_force() {
		return $this->resize_force;
	}

	/**
	 * Gets the image alt text.
	 *
	 * @return string False on error or image alt text on success.
	 */
	public function alt() {
		$alt = get_post_meta( $this->id, '_wp_attachment_image_alt', true );
		$alt = trim( strip_tags( $alt ) );

		return $alt;
	}

	/**
	 * Gets the image caption.
	 *
	 * @return string|null Null on error or caption on success.
	 */
	public function caption() {
		$post = get_post( $this->id );

		if ( $post && ! empty( $post->post_excerpt ) ) {
			return $post->post_excerpt;
		}

		return null;
	}

	/**
	 * Gets the image description.
	 *
	 * @return string|null Null on error or image description on success.
	 */
	public function description() {
		$post = get_post( $this->id );

		if ( $post && ! empty( $post->post_content ) ) {
			return $post->post_content;
		}

		return null;
	}

	/**
	 * Gets the mime type for a Timber image.
	 *
	 * @return string|null
	 */
	public function mime_type() {
		$post = get_post( $this->id );

		if ( $post && ! empty( $post->post_mime_type ) ) {
			return $post->post_mime_type;
		}

		return null;
	}

	public function is_svg() {
		return 'image/svg+xml' === $this->mime_type();
	}
}