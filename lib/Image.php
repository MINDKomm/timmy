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

	/**
	 * Absolute attachment path.
	 *
	 * @var
	 */
	protected $path;

	protected $meta;

	protected $max_width = 0;

	protected $max_height = 0;

	protected $resize_crop;

	protected $resize_force;

	protected $upscale;

	/**
	 * Image ID for dark mode / dark color scheme.
	 *
	 * @var int
	 */
	protected $color_scheme_dark_image;

	/**
	 * Alt text.
	 *
	 * @var string
	 */
	protected $alt;

	/**
	 * Caption text.
	 *
	 * @var string
	 */
	protected $caption;

	/**
	 * Dimensions for SVG image.
	 *
	 * @var array
	 */
	protected array $svg_dimensions = [];

	final protected function __construct() {
	}

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

	/**
	 * Sets the image for dark mode / dark color scheme.
	 *
	 * @param int $image_id
	 *
	 * @return void
	 */
	public function set_color_scheme_dark_image( int $image_id ) {
		$this->color_scheme_dark_image = $image_id;
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
	 * Gets the optimal full size for the given image size and mime type.
	 *
	 * Directly returns the full source when a full source or an SVG image is
	 * requested.
	 *
	 * The full size may be a scaled version of the image. To always request the
	 * original version, 'original' has to be used as the size.
	 *
	 * @return string|null
	 */
	public function auto_full_src() : ?string {
		if ( 'original' === $this->size_key ) {
			return Helper::get_original_attachment_url( $this->id );
		}

		// Deliberately return the attachment URL, which can be a 'scaled'
		// version of an image.
		return wp_get_attachment_url( $this->id );
	}

	/**
	 * Gets absolute attachment path.
	 *
	 * @return false|string
	 */
	public function path() {
		if ( empty( $this->path ) ) {
			$this->path = get_attached_file( $this->id );
		}

		return $this->path;
	}

	/**
	 * Gets the src (url) for the image.
	 *
	 * @return string Image src.
	 */
	public function src( $args = [] ) {
		// @todo Test with false image or wrong image size key.
		if ( $this->is_full_size() || $this->is_svg() ) {
			return $this->auto_full_src();
		}

		$args = wp_parse_args( $args, [
			'webp' => $this->is_webp(),
		] );

		list( $width, $height ) = Helper::get_dimensions_for_size( $this->size );
		list( $width, $height ) = Helper::get_dimensions_upscale( $width, $height, [
			'upscale'    => $this->upscale,
			'max_width'  => $this->max_width(),
			'max_height' => $this->max_height(),
		] );

		if ( $this->upscale['allow']
			|| ( $this->max_width() !== $width )
			|| ( 0 === $width && $height !== $this->max_height() )
		) {
			// Resize the image for that size.
			$src = Timmy::resize(
				$this->size,
				$this->full_src(),
				$width,
				$height,
				$this->resize_crop,
				$this->resize_force
			);
		} else {
			$src = $this->auto_full_src();
		}

		if ( $args['webp'] ) {
			$src = Timmy::to_webp( $src, $this->size );
		}

		return $src;
	}

	/**
	 * Get srcset and size for the image.
	 *
	 * @return string Image srcset, sizes, width, height alt attributes.
	 */
	public function responsive_src( array $args = [] ) : string {
		return Helper::get_attribute_html( $this->responsive_attributes( $args ) );
	}

	/**
	 * Get the responsive markup for the image.
	 *
	 * @return string Image srcset, sizes, width, height alt attributes.
	 */
	public function responsive( array $args = [] ) : string {
		return Helper::get_attribute_html( array_merge( $this->responsive_attributes( $args ), [
			'alt' => $this->alt(),
		] ) );
	}

	/**
	 * Gets the HTML markup for a responsive picture.
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function picture_responsive( $args = [] ) {
		/**
		 * Default arguments for image markup.
		 */
		$default_args = [
			'loading'     => 'lazy',
			'lazy_srcset' => false,
			'lazy_src'    => false,
			'lazy_sizes'  => false,
		];

		$args = wp_parse_args( $args, $default_args );

		$to_webp   = $this->is_webp();
		$mime_type = false;
		$html      = '';

		$light_attributes = [];

		if ( $to_webp ) {
			$mime_type = isset( $this->size['tojpg'] ) && $this->size['tojpg']
				? 'image/jpeg'
				: $this->mime_type();
		}

		// Dark color scheme.
		if ( $this->color_scheme_dark_image ) {
			$light_attributes['media'] = '(prefers-color-scheme: light)';
			$dark_image = Timmy::get_image( $this->color_scheme_dark_image, $this->size );

			$dark_image->set_size_key( $this->size_key );
			$dark_image->set_color_scheme_dark_image( $this->id );

			// WebP source needs to come first.
			if ( $to_webp ) {
				$source_attributes = Helper::responsive_webp_attributes( $dark_image, $args );
				$source_attributes['media'] = '(prefers-color-scheme: dark)';

				$html .= '<source' . Helper::get_attribute_html( $source_attributes ) . '>' . PHP_EOL;
			}

			$source_attributes = Helper::responsive_source_attributes( $dark_image, $args, $mime_type );
			$source_attributes['media'] = '(prefers-color-scheme: dark)';

			$html .= '<source' . Helper::get_attribute_html( $source_attributes ) . '>' . PHP_EOL;
		}

		// WebP source needs to come first.
		if ( $to_webp ) {
			$source_attributes = Helper::responsive_webp_attributes( $this, $args );
			$source_attributes = array_merge( $source_attributes, $light_attributes );

			$html .= '<source' . Helper::get_attribute_html( $source_attributes ) . '>' . PHP_EOL;
		}

		if ( ! $this->is_full_size() && ! $this->is_svg() ) {
			$source_attributes = Helper::responsive_source_attributes( $this, $args, $mime_type );
			$source_attributes = array_merge( $source_attributes, $light_attributes );

			$html .= '<source' . Helper::get_attribute_html( $source_attributes ) . '>' . PHP_EOL;
		}

		// Add fallback.
		$html .= $this->picture_fallback_image( $args );

		return $html;
	}

	public function picture_fallback_image( $args = [] ) {
		/**
		 * Default arguments for image markup.
		 */
		$default_args = [
			'loading' => 'lazy',
			'img_class' => '',
		];

		$args = wp_parse_args( $args, $default_args );

		$fallback_attributes = [
			'src'     => $this->src( [ 'webp' => false ] ),
			'width'   => $this->width(),
			'height'  => $this->height(),
			'alt'     => $this->alt(),
			'loading' => $this->loading( $args['loading'] ),
		];

		if (!empty($args['img_class'])) {
			$fallback_attributes['class'] = $args['img_class'];
		}

		$fallback_attributes = $this->add_data_attributes( $fallback_attributes, $args );

		return '<img' . Helper::get_attribute_html( $fallback_attributes ) . '>';
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

	/**
	 * Gets the srcset for an image.
	 *
	 * @param array $args Optional. Args for the srcset.
	 *
	 * @return false|string
	 */
	public function srcset( $args = [] ) {
		$args = wp_parse_args( $args, [
			'webp' => $this->is_webp(),
		] );

		$return = false;
		$width  = $this->width();
		/**
		 * Always use height from size configuration, because otherwise we would get images with
		 * different filenames, causing a lot of images to be regenerated.
		 */
		$height = Helper::get_height_for_size( $this->size );
		$srcset = [];

		list( , $height ) = Helper::get_dimensions_upscale( $width, $height, [
			'upscale'    => $this->upscale,
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

		if ( $args['webp'] ) {
			$default_size = Timmy::to_webp( $default_size, $this->size );
		}

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

				$max_width = $this->max_width();

				// Bail out if the current size’s width is bigger than available width.
				if ( ! $this->upscale['allow']
					&& (
						( $max_width > 0 && $width_intermediate > $max_width )
						|| ( 0 === $width_intermediate && $height_intermediate > $this->max_height() )
					)
				) {
					continue;
				}

				$width_descriptor = $this->srcset_width_descriptor( $width_intermediate, $height_intermediate );

				// Check for x-notation in srcset, e.g. '2x'.
				$suffix = is_string( $srcset_src ) && 'x' === substr( $srcset_src, - 1, 1 )
					? " {$srcset_src}"
					: " {$width_descriptor}w";

				// For the new source, we use the same $crop and $force values as the default image.
				$src = Timmy::resize(
					$this->size,
					$this->full_src(),
					$width_intermediate,
					$height_intermediate,
					$this->resize_crop,
					$this->resize_force
				);

				if ( $args['webp'] ) {
					$src = Timmy::to_webp( $src, $this->size );
				}

				$srcset[ $width_descriptor ] = $src . $suffix;
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
		if ( ! empty( $this->max_width ) ) {
			return $this->max_width;
		}

		if ( $this->is_svg() ) {
			$dimensions = $this->svg_dimensions();

			if ( $dimensions['width'] > 0 ) {
				$this->max_width = round( $dimensions['width'] );
			}
		} else {
			$this->load_attachment_meta_data();

			if ( ! empty( $this->meta['width'] ) ) {
				$this->max_width = $this->meta['width'];
			}
		}

		return $this->max_width;
	}

	public function max_height() {
		if ( ! empty( $this->max_height ) ) {
			return $this->max_height;
		}

		if ( $this->is_svg() ) {
			$dimensions = $this->svg_dimensions();

			if ( $dimensions['height'] > 0 ) {
				$this->max_height = round( $dimensions['height'] );
			}
		} else {
			$this->load_attachment_meta_data();

			if ( ! empty( $this->meta['height'] ) ) {
				$this->max_height = $this->meta['height'];
			}
		}

		return $this->max_height;
	}

	/**
	 * Gets the image width for a size.
	 *
	 * @return false|int Width on success, false on error.
	 */
	public function width() {
		if ( $this->is_full_size() ) {
			$width  = $this->max_width();
			$height = $this->max_height();
		} else {
			list( $width, $height ) = Helper::get_dimensions_for_size( $this->size );
		}

		if ( $this->is_svg() ) {
			$dimensions = $this->svg_dimensions();

			if ( empty( $dimensions ) ) {
				return false;
			}

			// SVG image can scale, so we can always return the requested width.
		} else {
			list( $width ) = Helper::get_dimensions_upscale( $width, $height, [
				'upscale'    => $this->upscale,
				'max_width'  => $this->max_width(),
				'max_height' => $this->max_height(),
			] );
		}

		return $width;
	}

	/**
	 * Gets the height for a size.
	 *
	 * @return false|int Height on success, false on error.
	 */
	public function height() {
		if ( $this->is_full_size() ) {
			$width  = $this->max_width();
			$height = $this->max_height();
		} else {
			list( $width, $height ) = Helper::get_dimensions_for_size( $this->size );
		}

		if ( $this->is_svg() ) {
			$dimensions = $this->svg_dimensions();

			if ( empty( $dimensions ) ) {
				return false;
			}

			if ( $dimensions['width'] && $dimensions['height'] ) {
				$height = Helper::get_height_from_width( $width, $dimensions['width'], $dimensions['height'] );
			}
		} else {
		$max_width  = $this->max_width();
		$max_height = $this->max_height();

		// Only calculate new height if height and width metadata was loaded.
		if ( $max_width > 0 && $max_height > 0 ) {
			$height = Helper::maybe_fix_height( $height, $width, $max_width, $max_height );
			list( , $height ) = Helper::get_dimensions_upscale( $width, $height, [
				'upscale'    => $this->upscale,
				'max_width'  => $max_width,
				'max_height' => $max_height,
			] );
		}
		}

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
	 * Checks whether an image is a portrait image.
	 *
	 * @return bool
	 */
	public function is_landscape() {
		return $this->aspect_ratio() > 1;
	}

	/**
	 * Checks whether an image is a portrait image.
	 *
	 * @return bool
	 */
	public function is_portrait() {
		return $this->aspect_ratio() < 1;
	}

	/**
	 * Checks whether an image is exactly square.
	 *
	 * @return bool
	 */
	public function is_square() {
		return $this->aspect_ratio() === 1;
	}

	/**
	 * Checks whether an image is pretty much a square image with an allowed
	 * deviation of +- 2%.
	 *
	 * @return bool
	 */
	public function is_squarish( $deviation = 0.02 ) {
		// Prevent division by 0.
		if ( $this->height() === 0 ) {
			return false;
		}

		$aspect = $this->aspect_ratio();

		// Check for squareness with a 2% deviation.
		return $aspect > ( 1 - $deviation ) && $aspect < ( 1 + $deviation );
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

	/**
	 * Gets the responsive attributes for an image.
	 *
	 * @param array $args Arguments to customize the output. For a list of arguments, see
	 *                    {@see get_timber_image_responsive_src()}.
	 *
	 * @return array
	 */
	public function responsive_attributes( $args = [] ) {
		/**
		 * Default arguments for image markup.
		 *
		 * @since 0.12.0
		 */
		$default_args = [
			'attr_width'  => true,
			'attr_height' => true,
			'lazy_srcset' => false,
			'lazy_src'    => false,
			'lazy_sizes'  => false,
			'loading'     => 'lazy',
			'webp'        => $this->is_webp(),
			'src_default' => true,
			// Whether the attributes are for a <source> element.
			'is_source'   => false,
		];

		$args = wp_parse_args( $args, $default_args );

		$attributes = [];

		/**
		 * Directly return full source when full source or an SVG image is requested.
		 *
		 * The full size may be a scaled version of the image. To always request the original
		 * version, 'original' has to be used as the size.
		 */
		if ( $this->is_full_size() || $this->is_svg() ) {
			$attributes['src'] = $this->auto_full_src();
		} else {
			$srcset = $this->srcset( [ 'webp' => $args['webp'] ] );

			if ( $srcset ) {
				$attributes['srcset'] = $srcset;

				if ( $args['src_default'] ) {
					$attributes['src'] = $this->src_default();
				}

				$attributes['sizes'] = $this->sizes();
			} else {
				$attributes['src'] = $this->src( [ 'webp' => $args['webp'] ] );
			}

			$attributes['style'] = $this->style();
		}

		if ( $args['attr_width'] ) {
			$attributes['width'] = $this->width();
			$attributes['style'] = false;
		}

		if ( $args['attr_height'] ) {
			$attributes['height'] = $this->height();
			$attributes['style']  = false;
		}

		// Lazy-loading.
		$attributes['loading'] = $this->loading( $args['loading'] );

		// Maybe update attributes with "data-" prefixes.
		$attributes = $this->add_data_attributes( $attributes, $args );

		// Maybe rename src attribute to srcset
		if ( $args['is_source'] && ! empty( $attributes['src'] ) ) {
			$attributes['srcset'] = $attributes['src'];
			unset( $attributes['src'] );
		}

		// Remove any falsy attributes.
		$attributes = array_filter( $attributes );

		return $attributes;
	}

	/**
	 * Adds "data-" attributes for usage with JavaScript lazy loading libraries.
	 *
	 * @param array $args       Args.
	 * @param array $attributes Updated attributes.
	 *
	 * @return mixed
	 */
	protected function add_data_attributes( array $attributes, array $args ) {
		$args = wp_parse_args( $args, [
			'lazy_srcset' => false,
			'lazy_src'    => false,
			'lazy_sizes'  => false,
		] );

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
	 * If the alt text wasn’t set before, the alt text is loaded from the attachment metadata.
	 *
	 * @return string False on error or image alt text on success.
	 */
	public function alt() {
		if ( ! isset( $this->alt ) ) {
			$alt = get_post_meta( $this->id, '_wp_attachment_image_alt', true );
			$alt = trim( strip_tags( $alt ) );

			$this->alt = $alt;
		}

		return $this->alt;
	}

	/**
	 * Sets the image alt text.
	 *
	 * @param string $alt The alt text to set.
	 *
	 * @return void
	 */
	public function set_alt( string $alt ) {
		$this->alt = $alt;
	}

	/**
	 * Gets the image caption.
	 *
	 * @return string|null Null on error or caption on success.
	 */
	public function caption() {
		if ( ! isset( $this->caption ) ) {
			$post = get_post( $this->id );

			if ( $post && ! empty( $post->post_excerpt ) ) {
				$this->caption = $post->post_excerpt;
			}
		}

		return $this->caption;
	}

	/**
	 * Sets the image caption text.
	 *
	 * @param string $caption The image caption to set.
	 *
	 * @return void
	 */
	public function set_caption( string $caption ) {
		$this->caption = $caption;
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
	 * Gets dimensions for an SVG image.
	 *
	 * @return array
	 */
	public function svg_dimensions() {
		if ( empty( $this->svg_dimensions ) ) {
			$this->svg_dimensions = Helper::get_svg_dimensions( $this->path() ) ?: [];
		}

		return $this->svg_dimensions;
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

	/**
	 * Checks whether an image is considered a full size image.
	 *
	 * @return bool
	 */
	public function is_full_size() {
		return in_array( $this->size_key, [ 'full', 'original' ], true );
	}

	/**
	 * Checks whether an image will be converted to WebP.
	 *
	 * @return bool
	 */
	public function is_webp() {
		return isset( $this->size['webp'] )
			&& $this->size['webp']
			&& ! $this->is_svg()
			&& ! $this->is_gif()
			&& ! $this->is_pdf();
	}

	/**
	 * Sets whether an image will be converted to WebP.
	 *
	 * @param bool $webp Whether to convert the image to WebP.
	 *
	 * @return void
	 */
	public function set_webp( bool $webp ) {
		$this->size['webp'] = $webp;
	}

	/**
	 * Checks whether an image is an SVG image.
	 *
	 * @return bool
	 */
	public function is_svg() {
		return 'image/svg+xml' === $this->mime_type();
	}

	/**
	 * Checks whether an image is a GIF.
	 *
	 * @return bool
	 */
	public function is_gif() {
		return 'image/gif' === $this->mime_type();
	}

	/**
	 * Checks whether an image is a PDF.
	 *
	 * @return bool
	 */
	public function is_pdf() {
		return 'application/pdf' === $this->mime_type();
	}
}
