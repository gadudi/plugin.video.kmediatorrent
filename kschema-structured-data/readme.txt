=== KSchema Structured Data ===
Contributors: kschema
Tags: schema, structured data, json-ld, seo, rich results
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional JSON-LD structured data (schema markup) management for posts, pages, custom post types, and taxonomies.

== Description ==

KSchema injects clean, valid JSON-LD schema markup into your site. It is designed to
*complement* existing SEO plugins (Yoast, Rank Math, SEOPress) rather than conflict with
them, and it is fully compatible with Elementor because output is placed in `wp_head`
instead of the page body.

Core capabilities (delivered across phased releases):

* Comprehensive schema support for posts, pages, CPTs, and taxonomies.
* A global automation engine mapping content types to schema types.
* Per-post and per-term overrides for granular control.
* Dynamic data mapping from core fields, ACF/custom fields, and term meta.

= Current status =

This release (0.1.0) ships Phase 0 (scaffolding + standards) and Phase 1 (frontend
JSON-LD core): valid Organization, WebSite, and Article output with transient caching.

== Changelog ==

= 0.1.0 =
* Initial scaffolding: bootstrap, autoloader, lifecycle, options.
* Frontend JSON-LD core: type registry, Organization/WebSite/Article pieces, graph
  assembler, cached wp_head injector.
