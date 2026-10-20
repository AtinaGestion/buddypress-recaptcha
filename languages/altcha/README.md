# ALTCHA widget translations

The 67 catalogs in `catalogs.json` are the official string data from ALTCHA
v2.2.2, matching the widget bundled by this plugin.

Source: https://github.com/altcha-org/altcha/tree/v2.2.2/src/i18n

The upstream MIT license is preserved in `LICENSE.txt`. These are local data
files; the installed plugin makes no translation requests to external services.

The shared ALTCHA service embeds a catalog in every widget's `strings` attribute.
The existing CAPTCHA Language setting takes precedence; Auto-detect uses the
current WordPress frontend locale, including locale filters from multilingual
plugins. Regional aliases are normalized, with English as the fallback for
unsupported locales. No changes to Divi or other theme files are required.
