=== Custom Schema for Yoast ===
Contributors: sajib1223
Tags: schema, json-ld, structured data, yoast, seo
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Paste JSON-LD on any post or page and merge it into Yoast SEO's schema graph, or replace Yoast's schema on that page.

== Description ==

Yoast SEO builds a schema graph for every page but offers no way to add your own node — a SoftwareApplication, a Product, a Course — to that graph. Custom Schema for Yoast adds a meta box where you paste JSON-LD, and then:

* **Append** (default): your nodes join Yoast's `@graph`, and, if the "Set as the page's main entity" checkbox is ticked (it is by default), the page's WebPage node gets `mainEntity` pointing at your first node. Breadcrumbs, Organization and WebSite stay intact.
* **Replace**: Yoast's JSON-LD is switched off on that page and only your JSON is printed.

If Yoast SEO is not active, your JSON is printed as a standalone `<script type="application/ld+json">`.

Accepted input: a single node, an array of nodes, or a full document with `@graph`.

== Frequently Asked Questions ==

= What JSON shapes can I paste? =

A single object with `@type`; an array of such objects; or an object with `@context` and `@graph`. In Append mode the `@graph` wrapper is dropped and its nodes are merged; in Replace mode the JSON is re-encoded rather than printed byte for byte, and any node without an `@id` gets one added.

= My node has no @id. What happens? =

The plugin assigns `<page URL>#csfy-1` (and `#csfy-2`, …) so the mainEntity link always resolves.

= Yoast's schema is disabled in its settings. What happens? =

Yoast then prints no graph at all, so there is nothing to append to. Your JSON is printed as a standalone `<script type="application/ld+json">` in either mode, and the meta box tells you so.

= What if the JSON is invalid? =

It is saved so you can fix it, nothing is printed on the front end, and the meta box shows the parser's error.

== Changelog ==

= 1.0.0 =
* Initial release.
