# BigBlueButton Resources Plugin

This plugin extends the BigBlueButton classroom interface by adding a visible **Resources** button to both:

1. The **Options Dropdown** (top-right 3-dot menu)
2. The **Actions Bar** (bottom toolbar)

When clicked, it opens the target resource destination (`https://example.com`) in a new browser tab.

## Structure

- `src/index.tsx`: Plugin entry point implementing `OptionsDropdownOption` and `ActionsBarButton`.
- `manifest.json`: BigBlueButton HTML5 plugin manifest.
- `webpack.config.js`: Production bundling configuration.

## Build Instructions

```bash
npm install
npm run build
```

This builds `dist/ResourcesPlugin.js`, which is hosted alongside `manifest.json`.

## Serving in Laravel

The pre-compiled bundle is published to:
`public/plugins/resources/manifest.json`
`public/plugins/resources/ResourcesPlugin.js`

When creating a meeting, pass `pluginManifests` with the public HTTPS URL pointing to `manifest.json`.
