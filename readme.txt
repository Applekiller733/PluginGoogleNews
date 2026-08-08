=== Follow on Google Buttons ===
Contributors: yourwporgusername
Tags: google news, follow button, google discover, block, gutenberg
Requires at least: 6.4
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A customizable block with buttons linking to Google News, Google Discover, and preferred-source settings.

== Description ==

Follow on Google Buttons adds a single Gutenberg block that displays up to three
customizable call-to-action buttons:

* **Follow on Google News** — links to your Google News publication so signed-in
  readers can follow you with one click.
* **Follow on Google Discover** — links to a destination you choose (Discover has
  no dedicated per-site follow URL; most publishers point this at their News
  publication or an explainer page).
* **Set as preferred source** — links to a how-to or Google settings page
  (preferred source is a user-controlled setting inside Google Search).

Each button can be shown or hidden independently. Site owners control the URL,
the button text, alignment, and whether links open in a new tab — all from the
block settings sidebar. Each button can also be styled individually: background
color, text color, font size, font weight, and border (width, color, and style).
The layout is fully responsive and stacks the buttons on small screens for easy
tapping.

All custom style values are sanitized before output — colors are validated
against hex/rgb/hsl formats, sizes are clamped to safe integer ranges, and
font-weight and border-style are checked against fixed allowlists — so
user-entered values cannot inject arbitrary CSS.

= Important note about what is (and isn't) possible =

This plugin creates ordinary links styled as buttons. It does not — and cannot —
programmatically add a site to a user's Google account, alter search ranking, or
change personalization. No public Google API exposes that. The "Follow on Google
News" action is a genuine one-click follow for signed-in users; the other two
buttons are best-effort links to Google surfaces or help pages, because Google
does not provide one-click URLs for them.

== Installation ==

This is the source distribution. The block must be compiled once before use.

1. From the plugin folder, run `npm install` to fetch build dependencies.
2. Run `npm run build`. This compiles `src/` into a `build/` folder that
   WordPress loads at runtime.
3. Upload the whole folder (including the generated `build/`) to
   `/wp-content/plugins/`, or run `npm run plugin-zip` to produce a
   ready-to-install zip.
4. Activate the plugin through the 'Plugins' screen.
5. Edit any page or post, add the "Follow on Google Buttons" block, and configure
   your URLs, labels, and styling in the block settings sidebar.

For development, run `npm run start` to rebuild automatically on save.

== Frequently Asked Questions ==

= Where do I get my Google News publication URL? =

Create a publication in Google Publisher Center
(https://publishercenter.google.com/). Once approved, your publication has a URL
of the form https://news.google.com/publications/... — paste that into the
Google News button field.

= Can this button set my site as someone's preferred source automatically? =

No. Preferred source is a setting the user chooses inside Google Search. The
button can only link them to the relevant Google page or your own instructions.

= Does it work with the Classic Editor? =

This version ships a Gutenberg block. A shortcode fallback may be added in a
future release.

== Screenshots ==

1. The three buttons rendered on the front end.
2. The block settings sidebar with per-button controls.

== Changelog ==

= 1.1.0 =
* Added per-button styling controls: background color, text color, font size,
  font weight, and border (width, color, style).
* All custom style values are sanitized before output.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.0 =
Adds per-button styling controls (colors, typography, border).

= 1.0.0 =
Initial release.
