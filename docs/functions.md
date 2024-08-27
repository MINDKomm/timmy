# Functions

You can use the following functions to get your images into your template:

## Basic functions

* [get_timber_image()](#get_timber_image) – Returns the src attribute together with the alt attribute for an image.
* [get_timber_image_src()](#get_timber_image_src) – Returns the src for an image.
* [get_timber_image_srcset()](#get_timber_image_srcset) – Returns the srcset attribute based on the `srcset` entry in the image configuration for an image size.
* [get_timber_image_width()](#get_timber_image_width) - Returns the width of an image size.
* [get_timber_image_height()](#get_timber_image_height) - Returns the height of an image size.

## Responsive images

* [get_timber_picture_responsive()](#get_timber_picture_responsive) – Returns the picture markup used for modern image formats using a fallback source.
* [get_timber_image_responsive()](#get_timber_image_responsive) – Returns the srcset, size and alt attributes for an image.
* [get_timber_image_responsive_src()](#get_timber_image_responsive_src) – Returns the srcset and sizes for an image. This is practically the same as *get_timber_image_responsive*, just without the alt tag.
* [get_timber_image_responsive_acf()](#get_timber_image_responsive_acf) – Takes the field name of an ACF image as the input and returns the same output as *get_timber_image_responsive()*.

## Image texts

* [get_timber_image_alt()](#get_timber_image_alt) – Returns the alt text for an image.
* [get_timber_image_caption()](#get_timber_image_caption) – Returns the caption for an image.
* [get_timber_image_description()](#get_timber_image_description) – Returns the description for an image.

## Additional helpers

* [make_timber_image_lazy()](#make_timber_image_lazy) - Prepares the srcset markup for lazy-loading. Replaces `srcset=""` with `data-srcset=""`.
---

## get_timber_image

`get_timber_image( int $post_id|\Timber\Image $timber_image, string|array $size )`

Returns the src attribute together with the alt attribute for an image.

#### Usage in WordPress templates

```php
<img<?php echo get_timber_image( get_post_thumbnail_id(), 'custom-4' ); ?>>
```

#### Usage in Twig

For Twig, this function is used as a filter on the image appended with a `|`.

```twig
<img{{ post.thumbnail|get_timber_image('custom-4-crop') }}>
```

---

## get_timber_image_src

`get_timber_image_src( int $post_id|\Timber\Image $timber_image, string|array $size )`

Returns the src for an image.

#### Usage in WordPress templates

```php
<img src="<?php echo get_timber_image_src( get_post_thumbnail_id(), 'custom-4-crop' ); ?>">
```

#### Usage in Twig

```twig
<img src="{{ post.thumbnail|get_timber_image_src('custom-4-crop') }}">
```

---

## get_timber_image_srcset

`get_timber_image_srcset( int $post_id|\Timber\Image $timber_image, string|array $size )`

Returns the srcset attribute based on the `srcset` entry in the image configuration for an image size.

#### Usage in WordPress templates

```php
<img srcset="<?php echo get_timber_image_srcset( get_post_thumbnail_id(), 'teaser' ); ?>">
```

#### Usage in Twig

```twig
<img srcset="{{ post.thumbnail|get_timber_image_srcset('teaser') }}">
```

---

## get_timber_image_width

`get_timber_image_width( int $post_id|\Timber\Image $timber_image, string|array $size )`

Returns the image width for an image size. If you use the [lazy loading functionality](https://github.com/mindkomm/timmy/blob/master/docs/lazy-loading.md), this is added automatically.

**Usage in WordPress templates**

```php
<img width="<?php get_timber_image_width( get_post_thumbnail_id(), 'header' ); ?>">
```

**Usage in Twig**

```twig
<img width="{{ post.thumbnail|get_timber_image_width('header') }}">
```

---

## get_timber_image_height

`get_timber_image_height( int $post_id|\Timber\Image $timber_image, string|array $size )`

Returns the image height for an image size. If you use the [lazy loading functionality](https://github.com/mindkomm/timmy/blob/master/docs/lazy-loading.md), this is added automatically.

**Usage in WordPress templates**

```php
<img height="<?php get_timber_image_height( get_post_thumbnail_id(), 'header' ); ?>">
```

**Usage in Twig**

```twig
<img height="{{ post.thumbnail|get_timber_image_height('header') }}">
```

---

## get_timber_picture_responsive

`get_timber_picture_responsive( int|Timber\Image $timber_image, string $size, array $args = [] )`

Gets the picture markup used for modern image formats using a fallback source.

### Usage in WordPress templates

```php
<picture>
    <?php echo get_timber_picture_responsive( get_post_thumbnail_id(), 'custom-6' ); ?>
</picture>
```

### Usage in Twig

```twig
<picture>
    {{ post.thumbnail|get_timber_picture_responsive('custom-6') }}
</picture>
```

### img_class

If you want to define the CSS class for the fallback `<img>`, you can use the `img_class` parameter.

**PHP**

```php

<picture>
    <?php echo get_timber_picture_responsive( get_post_thumbnail_id(), 'custom-6', [
        'img_class' => 'the-class',
    ] ); ?>
</picture>
```

**Twig**

```twig
<picture>
    {{ post.thumbnail|get_timber_picture_responsive('custom-6', {
        img_class: 'the-class',
    }) }}
</picture>
```

---

## get_timber_image_responsive

`get_timber_image_responsive( int $post_id|\Timber\Image $timber_image, string|array $size )`

Returns the srcset, size and alt attributes for an image. If this function is used with an SVG image, the single src will be returned instead of srcset.

#### Usage in WordPress templates

```php
<img<?php echo get_timber_image_responsive( get_post_thumbnail_id(), 'custom-6' ); ?>>
```

#### Usage in Twig

```twig
<img{{ post.thumbnail|get_timber_image_responsive('custom-6') }}>
```

---

## get_timber_image_responsive_src

Returns the srcset and sizes for an image. This is practically the same as `get_timber_image_responsive`, just without the alt tag.

---

## get_timber_image_responsive_acf

`get_timber_image_responsive_acf( string $field_name, string|array $size )`

Takes the field name of an ACF image as the input and returns the same output as `get_timber_image_responsive()`.

#### Usage in WordPress templates

```php
<img<?php echo get_timber_image_responsive_acf( 'image', 'custom-4-crop' ); ?>>
<!-- Will use get_field( 'image' ) to get the image information -->
```

#### Usage in Twig

For Twig, you won’t use the function as a filter like in the examples above, but as a function.

```twig
<img{{ get_timber_image_responsive_acf('image', 'custom-4-crop') }}>
```

---

## get_timber_image_alt

`get_timber_image_alt( int|Timber\Image $timber_image )`

Returns the alt text for an image. Be aware that `get_timber_image_responsive()` will always add an `alt` attribute.

### Usage in WordPress templates

```php
<img … alt="<?php echo get_timber_image_alt( $post->thumbail() ); ?>">
```

### Usage in Twig

```twig
<img … alt="{{ get_timber_image_alt(post.thumbnail) }}">
```

---

## get_timber_image_caption

`get_timber_image_caption( int|Timber\Image $timber_image )`

Returns the caption for an image.

### Usage in WordPress templates

```php
<?php echo get_timber_image_caption( $post->thumbail() ); ?>
```

### Usage in Twig

```twig
{{ get_timber_image_caption(post.thumbnail) }}
```

---

## get_timber_image_description

`get_timber_image_description( int|Timber\Image $timber_image )`

Returns the description for an image.

### Usage in WordPress templates

```php
<?php echo get_timber_image_description( $post->thumbail() ); ?>
```

### Usage in Twig

```twig
{{ get_timber_image_description(post.thumbnail) }}
```

---

## make_timber_image_lazy

`make_timber_image_lazy( string $image_markup, array $attributes = ['srcset'] )`

Prepares the srcset markup for lazy-loading. Updates the attributes passed in the `$attributes` parameter with a `data-` prefix. For example `srcset` is replaced with `data-srcset`.

#### Usage in WordPress templates

```php
<img<?php echo make_timber_image_lazy(
	get_timber_image_responsive( get_post_thumbnail_id(), 'custom-6' )
); ?>>

<img<?php echo make_timber_image_lazy(
	get_timber_image_responsive( get_post_thumbnail_id(), 'custom-6' ), ['srcset', 'src', 'sizes']
); ?>>
```

#### Usage in Twig

In Twig, you can use the `lazy` filter.

```twig
{# Default, srcset only. #}
<img{{ post.thumbnail|get_timber_image_responsive('custom-6')|lazy }}>

{# Custom array of attributes #}
<img{{ post.thumbnail|get_timber_image_responsive('custom-6')|lazy(['srcset', 'src', 'sizes']) }}>
```
