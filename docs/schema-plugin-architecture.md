# Architecture & Roadmap — WordPress Structured Data (Schema Markup) Plugin

## Context

The goal is a **professional WordPress plugin that manages JSON-LD structured data
across every content type** — posts, pages, custom post types (CPTs), and taxonomy
archives. Sites that need this typically already run an SEO plugin (Yoast, Rank Math,
SEOPress) and often build content with **Elementor**, so the plugin must add rich,
mappable schema *without* colliding with those systems.

This document is the **research + architectural design phase only** — no code is written
yet. It analyzes the technical stack, defines the file structure, the automation/override
logic, the configuration-storage model, Elementor + SEO coexistence strategy, and a phased
development roadmap.

**Decisions locked in with the user:**
- **SEO coexistence:** *Detect & complement.* Auto-detect active SEO plugins, defer to
  their base schema graph (Article / BreadcrumbList / WebSite / Organization), and only
  **add** the schema types they don't emit or **override** where the user explicitly opts in.
- **Dynamic data sources:** (1) Core fields + a token system, (2) ACF / raw post meta,
  (3) Taxonomy & term meta. *Elementor dynamic tags are NOT a value source* — Elementor is
  compatibility-only (no rendering/SEO conflict).
- **Scope:** Deliver the complete conceptual architecture and full-phase roadmap now; build
  order is decided later.

> The current git repo (`plugin.video.kmediatorrent`) is an unrelated Kodi/XBMC Python
> add-on. This is a **greenfield WordPress plugin**; none of the existing code is reused.
> Work lives on branch `claude/wordpress-schema-plugin-design-45hlc5`.

---

## 1. Technical Stack Analysis

| Concern | Choice | Rationale |
|---|---|---|
| Language | **PHP 7.4+ (target 8.1+)** | Typed properties, arrow fns; matches modern WP hosting. |
| WP baseline | **WP 6.0+** | Block editor + stable Settings API + term meta. |
| Output format | **JSON-LD** in `wp_head` (priority 99, late) | Google-preferred; decoupled from markup so Elementor rendering is untouched. |
| Admin UI (global) | **WordPress Settings API** (`register_setting`, `add_settings_section/field`) + a custom tabbed page under a top-level menu | Standards-compliant, nonce/capability handled by core. |
| Admin UI (per-post) | **Meta boxes** (classic) + **block-editor sidebar panel** via `@wordpress/plugins` + `PluginDocumentSettingPanel` | Works in both Gutenberg and Elementor (Elementor uses the classic-editor metabox path). |
| Admin UI (per-term) | **Term meta** fields on `{taxonomy}_edit_form_fields` | Native taxonomy edit-screen hooks. |
| Data mapping | **Token engine** (`{{post.title}}`, `{{acf.price}}`, `{{term.name}}`) resolved at render time | Keeps stored config small; values stay live. |
| Custom fields | **ACF API** (`get_field`) with graceful fallback to `get_post_meta` | Covers the common Product/Event/Recipe use cases. |
| Config storage | **Options API** (global rules) + **post/term meta** (overrides) | See §4. |
| REST/JS | `@wordpress/scripts` build (webpack) for the editor panel | Standard tooling; enqueue only on edit screens. |
| Coding standards | **WordPress-Extra + WordPress-Docs** via **PHPCS `WPCS`** + PHP 8 nullsafe; `composer` for dev deps | Meets "follow WP coding standards" requirement. |
| Schema definitions | Bundled **schema.org type registry** (curated subset as PHP arrays/JSON) | Drives the property-mapping UI dynamically. |
| Namespacing | PSR-4 autoload under `KSchema\` via Composer | Prevents fatal collisions with other plugins. |

**Key APIs used:** Settings API, Options API, Metadata API (post + term meta), Plugin API
(hooks/filters), Block Editor data/plugins packages, `wp_head`, `register_activation_hook`,
Transients API (cache), and conditionally the **ACF** and **Elementor** APIs (feature-detected).

---

## 2. Proposed File Structure

```
kschema-structured-data/
├── kschema-structured-data.php        # Bootstrap: header, constants, autoload, activation
├── uninstall.php                      # Clean removal of options/meta
├── composer.json                      # PSR-4 autoload + phpcs dev deps
├── package.json / webpack.config.js   # Editor-panel JS build (@wordpress/scripts)
├── readme.txt                         # WP.org-style readme
├── includes/
│   ├── Plugin.php                     # Singleton container / bootstraps subsystems
│   ├── Activator.php  Deactivator.php # Lifecycle, default options, version/migrations
│   ├── Schema/
│   │   ├── TypeRegistry.php           # Loads schema.org type + property definitions
│   │   ├── SchemaBuilder.php          # Builds the JSON-LD @graph for a request
│   │   ├── Piece/                     # One class per schema type (Article, FAQPage, Product, Event, Recipe, LocalBusiness...)
│   │   └── GraphAssembler.php         # Merges pieces, dedups @id nodes, links references
│   ├── Mapping/
│   │   ├── TokenParser.php            # Resolves {{...}} tokens
│   │   ├── Resolvers/                 # PostResolver, AcfResolver, TermResolver, SiteResolver
│   │   └── PropertyMapper.php         # Applies a mapping profile -> concrete values
│   ├── Rules/
│   │   ├── RuleEngine.php             # Global automation: match context -> schema type(s)
│   │   └── RuleMatcher.php            # post_type / taxonomy / template conditions
│   ├── Override/
│   │   └── OverrideResolver.php       # Merges global rule output with per-post/term overrides
│   ├── Admin/
│   │   ├── SettingsPage.php           # Global rules + mapping UI (Settings API, tabbed)
│   │   ├── PostMetabox.php            # Per-post override UI (classic + Elementor path)
│   │   ├── BlockSidebar.php           # Gutenberg sidebar panel registration
│   │   ├── TermFields.php             # Per-term override UI
│   │   └── Assets.php                 # Conditional enqueue on edit screens
│   ├── Frontend/
│   │   └── HeadInjector.php           # Hooks wp_head, prints JSON-LD, caches
│   ├── Integration/
│   │   ├── SeoPluginBridge.php        # Detect Yoast/RankMath/SEOPress; complement/dedupe
│   │   ├── AcfBridge.php              # Feature-detected ACF access
│   │   └── ElementorBridge.php        # Ensures no double-output on Elementor pages
│   └── Support/
│       ├── Cache.php  Sanitizer.php  Validator.php  Logger.php
├── assets/
│   ├── src/  (js/ scss/ editor-panel/)   # Source
│   └── build/                            # Compiled
├── data/schema-types.json                # Curated schema.org registry (types + props + expected data types)
└── tests/  (phpunit/  js/)               # Unit + integration tests
```

---

## 3. Automation + Override Logic (core design)

The render pipeline for a single request:

```
Request (single post / term archive / page)
        │
        ▼
[1] RuleEngine.resolve(context)              ← global automation rules (Options)
        │   returns: ordered list of schema types to emit for this context
        ▼
[2] OverrideResolver.apply(context, rules)   ← per-post / per-term meta
        │   • disabled types removed
        │   • property overrides layered on
        │   • manually-added types appended
        ▼
[3] PropertyMapper + TokenParser             ← resolve each property's value
        │   {{post.title}} → live value; {{acf.brand}} → get_field(); {{term.name}} → term
        ▼
[4] GraphAssembler                           ← build @graph, assign @id, link nodes,
        │                                       drop empty/invalid properties
        ▼
[5] SeoPluginBridge.reconcile(graph)         ← if Yoast/RankMath active: remove types they
        │                                       already emit (unless override forces ours)
        ▼
[6] HeadInjector                             ← print single <script type="application/ld+json">
                                               (cached via transient keyed by object+version)
```

**Merge precedence (lowest → highest):**
`schema.org defaults` → `global rule mapping profile` → `taxonomy-level override` →
`per-post override` → `SEO-bridge reconciliation`.

- **Automation rule shape:** `{ id, label, target: {post_type|taxonomy}, condition (all/template/specific IDs), schema_type, mapping_profile_id, priority, enabled }`.
- **Override shape (per post/term):** `{ mode: inherit|customize|disable, added_types[], property_overrides{ type: {prop: value|token} }, disabled_types[] }`. Default is **`inherit`** so automation "just works" until a user opts to customize.
- **Mapping profile:** a named, reusable set of `schema_property → token/static-value` pairs, driven by the `data/schema-types.json` registry so the UI only shows valid properties per type.

This cleanly satisfies the four requirements: **automation** = RuleEngine, **override** =
OverrideResolver, **metadata mapping** = Mapping subsystem, **comprehensive support** =
Piece classes + registry across posts/pages/CPTs/taxonomies.

---

## 4. Configuration / Data Storage Model

**No custom DB tables** (keeps it portable, migration-free, WP-standard). Everything uses
Options + Metadata APIs:

| Data | Storage | Key |
|---|---|---|
| Global automation rules | Options API (single serialized array) | `kschema_rules` |
| Named mapping profiles | Options API | `kschema_mapping_profiles` |
| Global settings (SEO-bridge mode, output toggles, per-type on/off) | Options API | `kschema_settings` |
| Schema version / migrations | Options API | `kschema_db_version` |
| Per-post override | Post meta (single JSON-encoded key) | `_kschema_override` |
| Per-term override | Term meta | `_kschema_override` |
| Rendered JSON-LD cache | Transients | `kschema_ld_{object_type}_{id}_{ver}` |

- Single-key JSON blobs (vs. many meta rows) keep the editor UI atomic and queries cheap.
- A global `kschema_db_version` gates **migration routines** in `Activator` for forward
  compatibility.
- `uninstall.php` deletes all `kschema_*` options, `_kschema_override` meta, and transients.

---

## 5. Elementor & SEO Coexistence

**Elementor (compatibility-only, no conflict):**
- Schema is injected in `wp_head`, **not** into Elementor's DOM/widgets — so Elementor's
  rendering, editing, and its own SEO handling are never touched.
- `ElementorBridge` detects Elementor-built pages and guarantees the metabox override UI
  loads via the classic-editor metabox path (Elementor pages still expose standard meta boxes).
- No dependency on Elementor Pro's schema/dynamic-tag features → works on free Elementor too.

**SEO plugins (Detect & complement):**
- `SeoPluginBridge` feature-detects Yoast (`WPSEO_VERSION`), Rank Math (`rank_math()`),
  SEOPress. When present, it **subscribes to their schema filters** where available
  (e.g. `wpseo_schema_graph`) OR reconciles at step [5]: it drops types the SEO plugin
  already emits to avoid duplicate `@type` nodes, keeping only complementary/overridden types.
- A settings toggle lets advanced users flip a given type to **"force ours"** (override) or
  **"defer to SEO plugin"** (default).

---

## 6. Development Roadmap (phased)

**Phase 0 — Scaffolding & standards**
Plugin bootstrap, Composer PSR-4 autoload, PHPCS (WPCS) config, `@wordpress/scripts` build,
activation/deactivation + version option, uninstall cleanup.

**Phase 1 — Frontend JSON-LD core**
`TypeRegistry` + `schema-types.json`, first Piece classes (Article, WebSite, Organization),
`GraphAssembler`, `HeadInjector` with transient cache. Output valid JSON-LD for posts/pages.

**Phase 2 — Automation engine + settings**
`RuleEngine`/`RuleMatcher`, Settings API tabbed page, global rules mapping post types &
taxonomies → schema types. Mapping profiles (static values first).

**Phase 3 — Dynamic data mapping**
`TokenParser` + resolvers (Post, Site, ACF, Term). Property-mapping UI driven by the registry;
ACF/meta and taxonomy/term-meta sources wired in.

**Phase 4 — Override system**
Per-post metabox + Gutenberg sidebar panel; per-term fields. `OverrideResolver` merge
precedence; inherit/customize/disable modes.

**Phase 5 — Integrations & reconciliation**
`SeoPluginBridge` (Yoast/RankMath/SEOPress detect + dedupe/force), `ElementorBridge`,
`AcfBridge` hardening.

**Phase 6 — Expanded schema library**
FAQPage, Product, Event, Recipe, LocalBusiness, BreadcrumbList, Person + more Piece classes.

**Phase 7 — Validation, QA & polish**
`Validator` (required-property checks), optional Google Rich Results preview link, PHPUnit +
JS tests, i18n (`load_plugin_textdomain`), performance pass, `readme.txt`, docs.

---

## 7. Verification (how we'll test once built)

- **Standards:** `composer run phpcs` passes WordPress-Extra ruleset; `npm run lint`.
- **Output validity:** paste rendered JSON-LD into **Google Rich Results Test** and
  **Schema.org validator**; assert no errors for each shipped type.
- **Automation:** create a rule (e.g. CPT `product` → `Product`), publish a post, confirm
  correct `@graph` in `wp_head` source.
- **Override:** set a per-post override (disable + custom property), confirm merge precedence
  in output; repeat for a term archive.
- **Coexistence:** activate Yoast/Rank Math, confirm **no duplicate** Article/Breadcrumb
  nodes; toggle "force ours" and confirm override wins.
- **Elementor:** build a page in Elementor, confirm schema still injects once and Elementor
  rendering/SEO is unaffected.
- **Unit/integration:** PHPUnit for RuleEngine, TokenParser, OverrideResolver merge logic;
  JS tests for the editor panel.

---

## Notes / Open Items for Build Phase
- Curate the initial `schema-types.json` scope (which ~10–12 types ship in Phase 1/6).
- Decide free vs. pro split later (architecture already supports feature-gating).
- Confirm minimum PHP/WP targets against the host environment before Phase 0.
