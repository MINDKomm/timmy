<?php

namespace Timmy;

/**
 * Class Helper
 *
 * @package Timmy
 */
class Helper {
	/**
	 * Image configuration cache.
	 *
	 * @var array|null Image configuration array.
	 */
	public static $sizes = null;

	/**
	 * Get image size configuration.
	 *
	 * Try first to get image sizes from cache. If the cache is not set, try to get image sizes from the timmy/sizes
	 * filter. And as a last resort, try to get images from the discouraged get_image_sizes() function.
	 *
	 * @since 0.13.0
	 */
	public static function get_image_sizes() {
		/**
		 * Filters whether the internal sizes cache should be skipped.
		 *
		 * Mainly used for testing.
		 *
		 * @since 0.14.8
		 */
		$use_sizes_cache = apply_filters( 'timmy/sizes/use_cache', true );

		// Bailout early if cached image configuration is available.
		if ( self::$sizes && $use_sizes_cache ) {
			return self::$sizes;
		}

		/**
		 * Filters image sizes used in Timmy.
		 *
		 * @since 0.13.0
		 *
		 * @param array $sizes Image configuration array. Default array().
		 */
		$sizes = apply_filters( 'timmy/sizes', array() );

		/**
		 * Fallback for get_image_sizes() function
		 *
		 * TODO: deprecate in 1.0.0
		 */
		if ( empty( $sizes ) && function_exists( 'get_image_sizes' ) ) {
			$sizes = get_image_sizes();
		}

		// Cache sizes for next requests.
		self::$sizes = $sizes;

		return $sizes;
	}

	/**
	 * Get an image size from image config.
	 *
	 * @since 0.11.0
	 *
	 * @param array|string $size Image size configuration array or image size key.
	 *
	 * @return array|bool Image size configuration array.
	 */
	public static function get_image_size( $size ) {
		// Check for a directly passed image configuration array.
		if ( is_array( $size ) ) {
			return $size;
		}

		$sizes = self::get_image_sizes();

		// Return found image size.
		if ( isset( $sizes[ $size ] ) ) {
			return $sizes[ $size ];
		}

		self::notice( "Image size \"{$size}\" does not exist in your image configuration." );

		return false;
	}

	/**
	 * Get thumbnail size from image config.
	 *
	 * When an image is requested without a size name or with dimensions only, try to return the thumbnail.
	 * Otherwise take the first image in the image array.
	 *
	 * @return array Image size configuration array.
	 */
	public static function get_thumbnail_size() {
		$sizes = self::get_image_sizes();

		if ( isset( $sizes['thumbnail'] ) ) {
			return $sizes['thumbnail'];
		}

		$img_size = reset( $sizes );

		return $img_size;
	}

	/**
	 * Gets width and height dimensions by considering the upscale parameters.
	 *
	 * @since 1.0.0
	 *
	 * @param int   $width  Current width.
	 * @param int   $height Current height.
	 * @param array $args
	 *
	 * @return array|int[]
	 */
	public static function get_dimensions_upscale( $width, $height, $args ) {
		$upscale = $args['upscale'];

		// Bail out if upscale is allowed.
		if ( $upscale['allow'] ) {
			return [ $width, $height ];
		}

		$max_width  = $args['max_width'];
		$max_height = $args['max_height'];
		$desired_width  = $width;
		$desired_height = $height;

		// Bail out if no valid max width or height could be found.
		if ( $max_width === 0 && $max_height === 0 ) {
			return [ $width, $height ];
		}

		/**
		 * Check upscale.
		 *
		 * If upscale is not allowed, then the image will not grow over its original size.
		 *
		 * Check whether the image source width is smaller than the desired width
		 * or the image source height is smaller than the desired height.
		 *
		 * Inline styles will only be applied if $upscale['allow'] is false. It doesn’t make
		 * sense to include bigger, low-quality sizes and still constrain an image’s dimensions.
		 */
		if ( $width > $max_width ) {
			// Overwrite $width to use the max width.
			$width = $max_width;

			// Set max_height for use in second if clause.
			if ( $desired_width > 0 ) {
				$max_height = min( $max_height, $max_width * ( $desired_height / $desired_width ) );
			}

			// Calculate new height based on new width.
			$height = (int) round( $max_width * ( $desired_height / $desired_width ) );
		}

		if ( $height > 0 && $height > $max_height ) {
			// Overwrite height to use the max height.
			$height = $max_height;

			if ( $desired_height > 0 && $desired_width > 0 ) {
				$max_width = min( $max_width, $max_height * ( $desired_width / $desired_height ) );
			}

			// Calculate new width based on height.
			$width  = (int) round( $max_width / $max_height * $height );
		}

		return [ $width, $height ];
	}

	/**
	 * Get width and height for an image size based on the image configuration.
	 *
	 * @param array $img_size Image size configuration array.
	 *
	 * @return array Width and height.
	 */
	public static function get_dimensions_for_size( $img_size ) {
		return array( self::get_width_for_size( $img_size ), self::get_height_for_size( $img_size ) );
	}

	/**
	 * Get width for an image size based on the image configuration.
	 *
	 * @since 0.15.0
	 * @param array $img_size Image size configuration array.
	 *
	 * @return float|int
	 */
	public static function get_width_for_size( $img_size ) {
		return abs( (int) $img_size['resize'][0] );
	}

	/**
	 * Get height for an image size based on the image configuration.
	 *
	 * @since 0.15.0
	 * @param array $img_size Image size configuration array.
	 *
	 * @return float|int
	 */
	public static function get_height_for_size( $img_size ) {
		return isset( $img_size['resize'][1] )
			? abs( (int) $img_size['resize'][1] )
			: 0;
	}

	/**
	 * Gets a new height calculated from a width and the original dimensions.
	 *
	 * @since 0.15.0
	 *
	 * @param int $width           Known width.
	 * @param int $original_width  Original width.
	 * @param int $original_height Original height.
	 *
	 * @return int
	 */
	public static function get_height_from_width( $width, $original_width, $original_height ) {
		if ( $original_height > 0 && $original_width > 0 ) {
			return (int) round( $width * ( $original_height / $original_width ) );
		}

		return $width;
	}

	/**
	 * Calculates correct height based on a width and the original dimensions.
	 *
	 * @since 0.15.0
	 *
	 * @param int $height          Height to check.
	 * @param int $width           Known width.
	 * @param int $original_width  Original width.
	 * @param int $original_height Original height.
	 *
	 * @return int|mixed
	 */
	public static function maybe_fix_height( $height, $width, $original_width, $original_height ) {
		/**
		 * Calculate the correct image height if it wasn’t set before.
		 *
		 * Attention: If we would pass in the image height to the resize function itself, then we
		 * would end up with different file names. This would cause the same image sizes to be
		 * generated again, just with a different file name.
		 */
		if ( $height < 1 ) {
			$height = Helper::get_height_from_width( $width, $original_width, $original_height );
		}

		return $height;
	}

	/**
	 * Get width and height for a srcset size.
	 *
	 * @param array            $resize      Resize configuration array.
	 * @param array|string|int $srcset_size Srcset definition.
	 *
	 * @return array Width and height.
	 */
	public static function get_dimensions_for_srcset_size( $resize, $srcset_size ) {
		// Get width and height for the additional src
		if ( is_array( $srcset_size ) ) {
			$width  = $srcset_size[0];
			$height = isset( $srcset_size[1] ) ? $srcset_size[1] : 0;
		} else {
			// Check for x-notation, e.g. '2x' or '1.5x'.
			if ( ! is_numeric( $srcset_size ) && 'x' === substr( $srcset_size, -1, 1 ) ) {
				$srcset_size = (float) rtrim( $srcset_size, 'x' );
			}

			$width  = (int) round( $resize[0] * $srcset_size );
			$height = isset( $resize[1] ) ? (int) round( $resize[1] * $srcset_size ) : 0;
		}

		return array( $width, $height );
	}

	public static function get_upscale_for_size( $img_size ) {
		$upscale_defaults = array(
			'allow'      => false,
			'style_attr' => true,
		);

		/**
		 * Filters the default upscale parameters used for an image.
		 *
		 * An upscale parameter set for an individual image size will always overwrite values set
		 * through this filter.
		 *
		 * @since 0.13.1
		 * @todo Deprecate timmy/oversize filter.
		 *
		 * @param array|bool $upscale Default upscale parameters. Can be a boolean to set all
		 *                            values in the array or an array with keys `allow` and
		 *                            `style_attr`.
		 *                            Default `array( 'allow' => false, 'style_attr' => true )`.
		 */
		$upscale = apply_filters( 'timmy/oversize', $upscale_defaults );
		$upscale = apply_filters( 'timmy/upscale', $upscale );

		// Overwrite default value with upscale value.

		// Deprecated.
		// @todo Add proper deprecation in the future.
		$upscale = isset( $img_size['oversize'] ) ? $img_size['oversize'] : $upscale;

		// New naming.
		$upscale = isset( $img_size['upscale'] ) ? $img_size['upscale'] : $upscale;

		// Turn shortcut boolean value for upscale into array.
		if ( is_bool( $upscale ) ) {
			$upscale = array(
				'allow'      => $upscale,
				'style_attr' => $upscale,
			);
		}

		// Make sure all required values are set.
		$upscale = wp_parse_args( $upscale, $upscale_defaults );

		return $upscale;
	}

	/**
	 * Gets crop value from a resize parameter.
	 *
	 * @param array $img_size Image size configuration array.
	 *
	 * @return string Crop value.
	 */
	public static function get_crop_for_size( $img_size ) {
		return isset( $img_size['resize'][2] ) ? $img_size['resize'][2] : 'default';
	}

	/**
	 * Gets force value from a resize parameter.
	 *
	 * @param array $img_size Image size configuration array.
	 *
	 * @return bool Force value.
	 */
	public static function get_force_for_size( $img_size ) {
		return isset( $img_size['resize'][3] ) ? $img_size['resize'][3] : false;
	}

	/**
	 * Gets responsive attributes for <source> elements.
	 *
	 * @since 1.0.0
	 *
	 * @param \Timmy\Image $image     Timmy image instance.
	 * @param array        $args      Args.
	 * @param string       $mime_type Mime type.
	 *
	 * @return array|string[]
	 */
	public static function responsive_source_attributes( Image $image, $args, $mime_type ) : array {
		$source_attributes = [
			'type' => $mime_type,
		];

		return array_merge( $source_attributes, $image->responsive_attributes(
			array_merge( $args, [
				'attr_width'  => false,
				'attr_height' => false,
				'src_default' => false,
				'loading'     => false,
				'webp'        => false,
				'is_source'   => true,
			] )
		) );
	}

	/**
	 * Gets attributes for WebP images in <source>.
	 *
	 * @since 1.0.0
	 *
	 * @param \Timmy\Image $image Timmy image instance.
	 * @param array        $args  Args.
	 *
	 * @return array|string[]
	 */
	public static function responsive_webp_attributes( Image $image, array $args ) : array {
		$attributes = [
			'type' => 'image/webp',
		];

		return array_merge( $attributes, $image->responsive_attributes(
			array_merge( $args, [
				'attr_width'  => false,
				'attr_height' => false,
				'src_default' => false,
				'loading'     => false,
				'webp'        => true,
				'is_source'   => true,
			] )
		) );
	}

	/**
	 * Gets the HTML for an array of HTML tag attributes.
	 *
	 * @since 0.14.0
	 *
	 * @param array $attributes An associative array of HTML attributes.
	 *
	 * @return string HTML attribute string to be used in an HTML tag.
	 */
	public static function get_attribute_html( $attributes = array() ) {
		$html = '';

		if ( ! $attributes ) {
			return $html;
		}

		foreach ( $attributes as $key => $attribute ) {
			if ( '' !== $attribute && empty( $attribute ) ) {
				continue;
			}

			$html .= ' ' . esc_attr( $key ) . '="' . esc_attr( $attribute ) . '"';
		}

		return $html;
	}

	/**
	 * Gets original attachment URL.
	 *
	 * In WordPress 5.3, WordPress added new functionality to create scaled images. This can be
	 * disabled through the `big_image_size_threshold` filter. However, when getting the attachment
	 * URL, we should always get the original size to generate sizes, otherwise we lose a lot of
	 * quality.
	 *
	 * @since 0.14.4
	 *
	 * @param int $attachment_id An attachment ID.
	 *
	 * @return false|string
	 */
	public static function get_original_attachment_url( $attachment_id ) {
		/**
		 * The wp_get_original_image_url() function checks for wp_attachment_is_image(). SVG images
		 * don’t qualify as images, so to not return false here, we need to check for
		 * wp_attachment_is_image() before running wp_get_original_image_url().
		 */
		if ( function_exists( 'wp_get_original_image_url' )
			&& wp_attachment_is_image( $attachment_id )
		) {
			return wp_get_original_image_url( $attachment_id );
		}

		return wp_get_attachment_url( $attachment_id );
	}

	/**
	 * Gets cached mime types.
	 *
	 * Useful in combination with wp_check_filetype(), where you should pass a second parameter to
	 * prevent get_allowed_mime_types() from being called too many times.
	 *
	 * @since 0.14.6
	 * @see \wp_check_filetype()
	 *
	 * @return string[]|null
	 */
	public static function get_mime_types() {
		static $mime_types = null;

		if ( ! isset( $mime_types ) ) {
			$mime_types = wp_get_mime_types();
		}

		return $mime_types;
	}

	/**
	 * Gets SVG size from the width/height or viewport of an SVG.
	 *
	 * @since 1.0.0
	 *
	 * @param string $svg The path to the SVG file.
	 *
	 * @return array|null
	 */
	public static function get_svg_dimensions( $svg ) {
        if ( ! file_exists( $svg ) ) {
            return null;
        }

		$svg    = simplexml_load_file( $svg );
		$width  = 0;
		$height = 0;

		if ( false === $svg ) {
			return null;
		}

		$attributes = $svg->attributes();

		if ( isset( $attributes->width, $attributes->height ) ) {
			$width  = floatval( $attributes->width );
			$height = floatval( $attributes->height );
		} elseif ( isset( $attributes->viewBox ) ) {
			$sizes = explode( ' ', $attributes->viewBox );

			if ( isset( $sizes[2], $sizes[3] ) ) {
				$width  = (float) $sizes[2];
				$height = (float) $sizes[3];
			}
		} else {
			return null;
		}

		return [
			'width'  => $width,
			'height' => $height,
		];
	}

	/**
	 * Output an error message.
	 *
	 * Triggers a notice, but only in development environments, when WP_DEBUG is set to true.
	 *
	 * @since 0.11.0
	 *
	 * @param string $message The message to output.
	 */
	public static function notice( $message ) {
		if ( WP_DEBUG ) {
			trigger_error( $message, E_USER_NOTICE );
		}
	}
}
