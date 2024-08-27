# Picture

You will use a `<picture>` element instead of an `<img>`

- If you want to use **modern image formats like WebP** and provide fallbacks for browsers that don’t support it yet.
- If you want to use **art direction** and provide different images for differents screens. (Using `<img>` is for serving differently-sized version of the *same image*.)

## WebP

### Simple responsive picture

```html
<picture>
    <source srcset="burrito-720x0-default.webp 720w, burrito-1200x0-default.webp 1200w" sizes="(min-width: 1200px) 1200px, 100vw" type="image/webp">
    <source srcset="burrito-720x0-default.jpg 720w, burrito-1200x0-default.jpg 1200w" sizes="(min-width: 1200px) 1200px, 100vw">
    
    <img src="burrito-1200x0-default.jpg" width="1200" height="600" alt="Your alt text">
</picture>
```

**Twig**

```twig
<picture>
    {{ post.thumbnail|get_timber_picture_responsive('webp-picture') }}
</picture>
```

If you want to add a CSS class to the fallback `<img>`, then you can use the `img_class` parameter:

```twig
<picture>
    {{ post.thumbnail|get_timber_picture_responsive('webp-picture', {
        img_class: 'the-class'
    }) }}
</picture>
```

### Art directed picture with fallbacks

To be implemented …
