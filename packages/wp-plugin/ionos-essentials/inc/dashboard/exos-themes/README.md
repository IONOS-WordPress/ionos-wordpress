# Exos-Theming

The original exos themings relies on an HTML-Tag with a data-attribute.
Inside the shadow dom, we cannot use an HTML-Tag.

Therefor the theme-variables are stored in our repo and loaded, if present.

Download theme.css-files from https://github.com/IONOS-CPH/cp-exos/tree/main/src/main/css/tenants/variables

## Arsys

There is a small tweak in the original css-file

```css
.input-switch label {
  border-radius: 0px !important;
}
```

was added by ours. I case of an update, check if this tweak should be reapplied or not.
