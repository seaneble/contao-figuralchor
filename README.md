# Figuralchor Contao Bundle

Generates a hue value (0-359) that stays stable for a visitor's session and
varies between sessions, for per-visitor CSS theming.

## Usage

Use the `{{theme_hue}}` insert tag anywhere Contao resolves insert tags (page
layout CSS, custom HTML, etc.), for example in a layout's custom CSS:

```css
:root {
    --theme-hue: {{theme_hue}};
}

body {
    background: hsl(var(--theme-hue), 70%, 95%);
}
```

Note: since the value depends on the visitor's session, pages using this
insert tag should be excluded from full-page/shared caching.
