# Changelog

## [1.1.0](https://github.com/mindkomm/timmy/compare/1.0.0...v1.1.0) (2024-03-26)


### Features

* Optimize filter priority for deleting and generating image sizes ([2ec4013](https://github.com/mindkomm/timmy/commit/2ec40134fb81f13e95b6cb90c4a51da5daae3476))


### Bug Fixes

* Add release workflow ([#64](https://github.com/mindkomm/timmy/issues/64)) ([54b4d5e](https://github.com/mindkomm/timmy/commit/54b4d5ebeafa7127f12349a28435d440280ab73b))
* Always export composer.json ([142efa9](https://github.com/mindkomm/timmy/commit/142efa97614325fa74d5570545268227275c3915))
* Fix a PHP 8.1 deprecation ([cbce1f2](https://github.com/mindkomm/timmy/commit/cbce1f26aaa37701cd7f7bc6c4b1cccd0ee6ead9))

## 1.0.0

- Increased minimum supported PHP version to 7.4.
- Increased required Timber version to 1.9.
- Added support for lazy loading with `loading` attribute.
- Added `width` and `height` attributes for images by default.
- Added support for WebP images.
- Added support for getting Timmy image sizes with `wp_get_attachment_image_src()`.
- Added `get_timber_image_alt()` function.
- Added `get_timber_image_caption()` function.
- Added `get_timber_image_description()` function.
- Added `get_timber_picture_responsive()` function.
- Added `get_timber_picture_fallback_image()` function.
- Added better support for SVG images.
- Fixed a bug when Timmy tried to resize files that it shouldn’t (video files for example).
- Fixed issue when Timmy created upscaled image files even though it shouldn’t.
- Changed oversize configuration option naming from `oversize` to `upscale`. You can still use `oversize`, but it will be deprecated in the future.
- Removed the `title` attribute from the `<img>` attributes, which was added by default with an image’s description. That approach was too opinionated and might only make sense in some use cases. If you still want to somehow use the image description, you can use the `get_timber_image_description()` function.
- Improved hints about [controlling image sizes for Yoast](https://github.com/mindkomm/timmy/blob/master/docs/faq.md#how-can-i-control-the-image-size-generated-by-yoast).
- Removed deprecated `get_image_attr_html()`, `get_timber_image_attr()` and `get_acf_image_attr()` functions.

### New API behind the scenes

- Added a new `Timmy\Image` class that is used to generate the markup for an image. Most of the functionality that was in the `get_timber_*()` functions before now lives in that class.
- Added a `Timmy\Timmy::get_image()` function to get a `Timmy\Image` in a way that allows developers to extend the `Timmy\Image` class.

### Breaking changes

#### Changed how Timmy should be initialized.

**🚫 Before**
```php
new Timmy\Timmy();
```

**✅ After**

```php
Timmy\Timmy::init();
```

#### Changed how Responsive Content Images should be initialized

**🚫 Before**
```php
new Timmy\Responsive_Content_Images()
```

**✅ After**

```php
Timmy\Timmy::responsive_content_images();
```

#### Other breaking changes

- Changed additional parameters that are passed to the `timber/src_default` filter. There is no longer an array of additional `$attributes` to this filter, but only an instance of `Timmy\Image`.
- Removed function pluggability. This means that you can’t overwrite any Timber functions anymore. If you want a similar functionality, you can copy the functions. They have way less logic in them than before, because a lot of the logic moved to the `Timmy\Image` class.
- Removed `get_post_thumbnail()` and `get_post_thumbnail_src()` functions, because they are not namespaced as the other functions and are practically the same as `get_timber_image()` and `get_timber_image_src()`.

## 0.14.9

- Added `Timmy\Timmy::VERSION` constant. You can use this to check Timmy’s version using `version_compare()`:
    ```php
    if ( version_compare( Timmy\Timmy::VERSION, '1.0.0', '<' ) ) {
        // Do something for Timmy versions < 1.0.0.
    }
    ```
- Fixed a bug when Timmy tries to resize files that it shouldn’t, like video files.
- Updated testing suite.
- Update

## 0.14.8 - 2021-07-28

- Fixed more issues with regex for Responsive Content Images.

## 0.14.7 - 2021-07-01

- Fixed regex for Responsive Content Images.
- Fixed a bug for attachments that were deleted from the media library.

## 0.14.6 - 2021-03-29

- Added `content_filters` argument for Responsive Content Images constructor.
- Fixed performance when a lot of image data is requested.

## 0.14.5 - 2020-09-11

- Added Block Editor support for the Responsive Content Images functionality.
- Added new `timber_image_srcset()` function to get only the srcset for an image size.
- Added support for using values like `1x` and `2x` in the srcset definition.
- Fixed `get_timber_image_responsive_acf()` function.
- Fixed a bug when `srcset` sizes weren’t generated on upload.
- Fixed a bug when the `sizes` was written into the markup even though there was no `srcset`.
- Deprecated function `get_acf_image_attr()`.

## 0.14.4 - 2020-03-03

This release fixes a bug when low-quality image sizes were generated when using WordPress 5.3+.

### Added support for scaled images

[As of WordPress 5.3](https://make.wordpress.org/core/2019/10/09/introducing-handling-of-big-images-in-wordpress-5-3/), images above 2560px will be scaled down to a smaller version for the `full` size of an image. This version will include `-scaled` in its filename. Because Timmy relied on `wp_get_attachment_url()`, smaller image sizes would be created based on this scaled version, which resulted in a **very visible quality loss**.

Timmy now uses the new [`wp_get_original_image_url()`](https://developer.wordpress.org/reference/functions/wp_get_original_image_url/) function when needed. There’s also a fallback to `wp_get_attachment_url()` for WordPress versions below 5.3.

**Wrongly generated images will be regenerated on the fly** as soon as you install this update.

Timmy now returns the scaled version of an image, when the `full` image size is requested. You can always get the original size of an image when using `original` instead of `full` as the size.

If you want to disable scaled images, you’ll have to use the `big_image_size_threshold` filter:

```php
add_filter( 'big_image_size_threshold', '__return_false' );
```

## 0.14.3 - 2020-02-20

- Added new filter for `wp_get_attachment_metadata` that adds a sizes configuration for image sizes that are missing in the image meta data. This should improve compatibility when image data is requested through the WP REST API.

## 0.14.2 - 2019-08-09

- Added new filter `timmy/use_src_default` to define whether a src attribute should be added as a fallback.
- Added new filter `timmy/src_default` to define what the src attribute’s should be.
- Added new `lazy_sizes` parameter to `get_timber_image_responsive_src()` function. This will allow you to prefix the `sizes` attribute with `data-`.
- Improved lazy filter by adding a parameter to define which attributes should be prefixed with `data-`. E.g., you can now use `image|get_timber_image_responsive('large')|lazy(['srcset', 'src', 'sizes'])` to prefix the `srcset`, the `src` and the `sizes` attributes with `data-`. 
- Improved the default src fallback for images with `data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7`. This fixes bugs in IE 11.

## 0.14.1 - 2019-06-19

- Added bailout in `image_downsize` filter when no image size could be found.
- Updated the order of the image attributes when using `get_timber_image_responsive()`.
- Fixed bug when `full` image size couldn’t be found when using `get_timber_image()`.

### Documentation

- Added [docs section about WooCommerce](https://github.com/mindkomm/timmy/blob/master/docs/best-practices.md#working-with-woocommerce).
- Fixed name of inexistent setting in example in the docs.

### Development

- Added testing setup to be able to test Timmy functionality and make it more robust.

## 0.14.0 - 2018-07-24

- Introduced new function `Helper::get_attribute_html()` that is used to turn an associative array of HTML attributes into a string. Internally, the handling of a list of HTML attributes was improved by introducing attribute arrays that get passed around and will be turned into HTML by this new function.
- Introduced new function `get_timber_image_attributes_responsive()` that can be used to get an associative array of default HTML attributes for a responsive Timber image.
- Introduced new function `get_timber_image_texts()` that is used to retrieve alt and title attributes for an image.
- Deprecated the `get_image_attr_html()` function in favor of `Helper::get_attribute_html`.
- Deprecated the `get_timber_image_attr()` function in favor of `get_timber_image_texts()`.

### Improved Responsive Content Images feature

- Introduced arguments array for `Responsive_Content_Images` class with new option `map_sizes`, that allows the usage of custom image sizes for image sizes used in the content.
- Introduced new filter `timmy/responsive_content_image/attributes` to filter image attributes used for a responsive content image.
- Introduced new filter `timmy/responsive_content_image` to filter the image HTML markup for a responsive content image.
- Added filter that automatically removes any width styles from WordPress-generated `<figure>` tags.

### Meta data for images

When an image is uploaded, Timmy now **generates image metadata that is saved in the database**. This improves compatibility with third party plugins that sometimes check this metadata for various reasons. For example, Yoast SEO needs this data to generate the markup for OG image tags.

To generate this data for existing images, you’d have to run Regenerate Thumbnails.

### Update documentation

The README file was quite long, probably a little too long. The big part of the documentation can now be found in separate files in the `docs/` folder. Use the [Documentation Section](https://github.com/mindkomm/timmy#documentation) in the README for an entry point.

## 0.13.6 - 2018-04-11

- Escaped HTML attributes with `esc_attr()` for better security and to prevent errors when certain characters are used in image alt texts and titles.

## 0.13.5 - 2018-03-09

- Changed how the bug that was fixed in 0.13.4 is handled. Solve it differently, so that the changes made for the `timmy/resize/ignore` filter in 0.13.4 could be reversed. Check the [filter section in the README](https://github.com/mindkomm/timmy#filters) for how the `timmy/resize/ignore` filter works. Sorry!
- Fixed a whitespace bug in `get_timber_image_responsive_src()` that was introduced in 0.13.3 (a17aa2e825c0b0b20e613f70d7aa735711170367).

## 0.13.4 - 2018-03-06

- Fixed a bug when `timmy/resize/ignore` filter was ignored in the backend when attachments were queried.
- <del>Changed parameters passed to `timmy/resize/ignore` filter. Instead of the `$attachment_id` parameter, there’s now a `$mime_type` parameter. The order for the following parameters changed as well. Check the [filter section in the README](https://github.com/mindkomm/timmy#filters).</del>

## 0.13.3 - 2018-02-26

- Added lazy loading helper [filter](https://github.com/mindkomm/timmy#lazy-loading) and [function](https://github.com/mindkomm/timmy#make_timber_image_lazy).

## 0.13.2 - 2018-02-14

- Fixed a bug when Timmy tried to convert a PDF to JPEG. Timmy now checks that a file is not a PDF before converting it to JPG.

## 0.13.1 - 2018-02-08

- Fixed behavior of `oversize` parameter. Prior to this version, images would grow bigger than their original size even if `oversize['allow']` was set to `false`.
- Extended the `oversize` shortcut to also set the `style_attr` parameter. This means that you can use `oversize => true` or `oversize => false` to set all parameters directly. This change only has implications for you if you’ve used `oversize => false` in the past. 
- Added new filter `timmy\oversize` to set the default values for the oversize parameter. Read more about it in the [Filters section of the README](https://github.com/mindkomm/timmy#filters).

## 0.13.0 - 2018-01-18

### Composer type

Changed Composer package type from `wordpress-plugin` to `library`.

With type `wordpress-plugin`, the package was installed into a `wp-content/plugins` folder, even if you used it in your theme. With type `library`, we follow that Timber does, and it’s still possible to [define where the package should be installed](https://getcomposer.org/doc/faqs/how-do-i-install-a-package-to-a-custom-path-for-my-framework.md). And it will not break backwards compatibility, because the default folder will still be the `vendors` folder.

### Introducing filters

In the upcoming versions of Timmy you’ll see filters that allow you to change certain settings more easily. In this version, we introduce 3 new filters. To read more about the filters, there’s a new [Filters section of the README](https://github.com/mindkomm/timmy#filters). Here’s an overview over what these changes mean for you.

#### timmy/sizes

Introduced a new `timmy/sizes` filter to define the image sizes used in Timmy. The way to get image sizes through `get_image_sizes()` will be deprecated in a future version of Timmy. The reason for is that `get_image_sizes()` is quite a generic function name that could lead to conflicts with other plugins.

This means that instead of returning your image configuration from `get_image_sizes()`, you’ll use `timmy/sizes`:

**DON’T USE**

```php
function get_image_sizes() {
    return array(
        // Image configuration
    );
}
```

**USE**

```php
add_filter( 'timmy/sizes', function() {
    return array(
        // Image configuration
    );
} );
```

#### timmy/resize/ignore

- Introduced a `timmy/resize/ignore` filter to make it possible to ignore resizing of images. This filter allows you to disable the resizing based on various conditions, that you can define yourself based on the values passed to this filter.
- Added a default filter to ignore resizing of GIF images by default. If you still want to enable resizing of GIF images, you can disable the filter by adding the following line to `functions.php` of your theme (after you initialized Timmy with `new Timmy\Timmy()`):

```php
remove_filter( 'timmy/resize/ignore', array( 'Timmy\Timmy', 'ignore_gif' ) );
```

#### timmy/generate_srcset_sizes

Introduced a new filter `timmy/generate_srcset_sizes` that filters whether srcset sizes should be generated when an image is uploaded. By default, Timmy generated sizes defined in `srcset` in your image configuration when you uploaded an image in the backend by default. You could disable this by using a `generate_srcset_sizes` in your image configuration. This behavior is now changed:

- `generate_srcset_sizes` will be `false` by default. If you want a size to generate srcset sizes when an image is uploaded, you need to set `generate_srcset_sizes` for an image size to `true`.
- If you want to enable generating srcset sizes for all images sizes, you can use the `timmy/generate_srcset_sizes` filter.
- A value for `generate_srcset_sizes` set on an image size in your configuration will always overwrite values set by the filter.

## 0.12.2 - 2018-01-09

- Added type `wordpress-plugin` to `composer.json`. This allows you to install Timmy in a folder other than `vendor/mindkomm/timmy` (#11, Thanks, @salaros). [Read more about installing a package to a custom path](https://getcomposer.org/doc/faqs/how-do-i-install-a-package-to-a-custom-path-for-my-framework.md).
- Added possibility to [use an image size array directly for frontend functions](https://github.com/mindkomm/timmy#using-an-image-size-array-instead-of-a-key), instead of adding it to the global image configuration array.

## 0.12.1 - 2017-08-17

Changed how Timmy is initialized. It now works the same as initializing Timber. You need to initialize it manually in **functions.php** of your theme:

```php
new Timmy\Timmy();
```  

You can add this right after you called `new Timber\Timber();`.

This change was required to make the library more compatible with environments like Bedrock, where WordPress might not have been loaded when the Composer package is initialized.

**Other changes**

- Fixed missing files when Timmy is installed as a plugin.
- Fixed leading whitespace for `srcset` attributes.

## 0.12.0 - 2017-08-03

- Added support for [responsive content images](https://github.com/MINDKomm/timmy#responsive-content-images), which means that Timmy can now make images inserted in the post content via the WordPress Editor responsive.
- Optimized image markup by using the `srcset` attribute only if multiple images are available. If an image has only one image in srcset, it falls back to using the `src` attribute instead.
- Added `src` fallback attribute to all responsive images by default to fix invalid markup errors (as [recommended by Picturefill](http://scottjehl.github.io/picturefill/#support)). 
- Optimized performance in the backend.
	- Only thumbnails and full sizes of images are shown in the backend to prevent on-the-fly resizing of images (e.g. in the Media Grid).
	- Uses full size instead of large size if 'large' is present as a size.
- Fixed selectable images sizes that appear in various positions in the backend.
- Added fallback for GIFs to return full size when image metadata can’t be read.
- Internal: Introduced new helper class for static helper functions.
- Internal: Replaced deprecated filter `get_twig` with `timber/twig`.

## 0.11.0 - 2017-04-26

- Made Timmy compatible with newest version 1.3.0 of Timber, which is now also the minimum required version.
- Added warning when key "full" is used in image size config.
- Added warning when an image size does not exist in the image configuration.
- Improved how Timmy selects the correct image source internally.
- Improved how SVG and GIF images are handled. See the [FAQ section](https://github.com/MINDKomm/timmy#how-does-timmy-handle-svg-images) for more information.
- Improved how Timmy handles images it can’t find. Now, it will return `false` for all images it can’t find. This means that it will silently fail without any error messages.

## 0.10.6 - 2017-02-21

- Fixed compatibility issue with Timber 1.2.2, where Timber returned the thumbnail size of an image instead of the full size.

## 0.10.5 - 2017-01-03

- Fixed notice that occurred when oversize parameter was not set in image config.

## 0.10.4 - 2017-01-02

- Fixed `oversize` parameter and updated documentation.

## 0.10.3 - 2016-11-18

- Fixed an error when Timmy blocked images showing up in the Media Grid view in the backend.

## 0.10.2 - 2016-10-11

- Optimized function `get_timber_image_responsive_src()` to directly return the image source when the image is an SVG or a GIF.

## 0.10.1 - 2016-05-25

- Added composer.json to make it possible to load Timmy through Composer.
- Added check for valid and non-empty TimberImage. Frontend functions now return an empty string when no image was found.
- Made sure image arrays (like used in ACF) are also converted to a TimberImage. This way, it doesn’t matter if an image ID or an array is returned by ACF, Timmy will convert it to a TimberImage.

## 0.10.0 - 2016-05-09

- **Changed image configuration option `size` to `sizes`** to use the attribute name that is also used in the HTML markup. This means you have to update your image configuration.
- Added functionality that prevent smaller images from being oversized. In the image configuration, there’s a new option: `oversize`. False per default, it can be set to true to allow images to grow bigger than their uploaded size. Otherwise, Timmy returns only sizes smaller or equal than the uploaded image size and also adds a style attribute "width" or "height", to prevent the image to autogrow in the browser.
- Integrated Timmy’s image sizes better into default WordPress functionalities to better support other plugins like Advanced Custom Fields, Admin Columns by codepress and Yoast SEO.
- Improved README with section about image size keys. See README.
- Fixed call to deprecated method.
- Fixed error when `0` was used for the width parameter.
- Namespaced all instances of calls to Timber classes. Timber classes are namespaced since Timber v1.0.0.

## 0.9.3 - 2016-03-17

- Added support for resize values of `0` for the width parameter. In Timber, a user can pass the value 0 as the width parameter. Images will then be resized proportionally based on the height parameter. This now also works in Timmy. (Thanks to @koraysels for pointing this out.)

## 0.9.2 - 2016-03-06
- Improved examples in README.
- Added support for letterbox and tojpg filters. See README for more information.
- Made sure other plugins are loaded before plugin is initialized
