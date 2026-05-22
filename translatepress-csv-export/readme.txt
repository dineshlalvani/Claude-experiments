=== TranslatePress CSV Export ===
Contributors: euryka
Tags: translatepress, translation, csv, export, multilingual
Requires at least: 5.6
Tested up to: 6.6
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Export every TranslatePress string and translation to a CSV file, grouped by page and section.

== Description ==

This plugin reads the tables that TranslatePress writes to (`wp_trp_dictionary_*`, `wp_trp_gettext_*`, `wp_trp_original_meta`) and produces a single, flat CSV file you can open in Excel, Google Sheets, or hand to a translator.

Each CSV row contains:

* **Page** — the title of the post or page the string was found on
* **Page URL** — the permalink
* **Post type** — page, post, product, etc.
* **Section** — TranslatePress's "block type": regular text, post slug, meta information, images, navigation, block HTML, link, or gettext (theme/plugin strings)
* **Language** — the target language for this row
* **Original** — the source language string
* **Translation** — the stored translation (may be empty)
* **Status** — Not translated / Machine translated / Human reviewed / Deprecated
* **Source** — Dictionary or Gettext

Strings that appear on more than one page are written once per page so you can filter by page in your spreadsheet.

== Usage ==

1. Activate the plugin.
2. Go to **Tools → TP CSV Export**.
3. Choose which languages, sections and statuses to include, then click **Download CSV**.

The export streams to the browser so it works on large catalogs without running out of memory.

== Requirements ==

* TranslatePress (free or Pro) must be installed and active.
* At least one secondary language must be configured in TranslatePress.

== Changelog ==

= 1.0.0 =
* Initial release.
