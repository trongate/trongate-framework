# Changelog

This file captures all notable changes to the [Trongate PHP framework](https://github.com/trongate/trongate-framework) from v2 onward. v2 was released in January 2026.

The format of this file is based on [Keep a Changelog](https://keepachangelog.com/). 

The Trongate project uses the version format: `{major version}.{year}.{month}{day}`, for example, `2.2026.0522`, which stands for: major version 2, released, 22 May 2026.

The current version of the framework is documented in its [license.txt](https://github.com/trongate/trongate-framework/blob/master/license.txt) file.

## [2.2026.0523] - 2026-06-23

### Added
- **Flo code generator** (`trongate_control/flo`) — now fully functional with `home()` view and working `draw_flow_trigger()`, accompanied by dedicated CSS and JS assets. ([7c014d8](https://github.com/trongate/trongate-framework/commit/7c014d89f4ee393983e0c2dd1c86468cc490cb0a))
- **Evo module wizard** (`trongate_control/evo`) — step-by-step module generation wizard with 11+ views for naming, property configuration, ordering, URL column selection, and generation. ([7c014d8](https://github.com/trongate/trongate-framework/commit/7c014d89f4ee393983e0c2dd1c86468cc490cb0a))
- **Plural_maker** (`trongate_control/plural_maker`) — comprehensive English pluralisation engine using pattern-matching rules and embedded dictionaries. ([7c014d8](https://github.com/trongate/trongate-framework/commit/7c014d89f4ee393983e0c2dd1c86468cc490cb0a))
- **Properties module** (`trongate_control/properties`) — field property definitions with address data sets for American, British, Canadian, and International formats. ([7c014d8](https://github.com/trongate/trongate-framework/commit/7c014d89f4ee393983e0c2dd1c86468cc490cb0a))
- **Properties_builder** (`trongate_control/properties_builder`) — visual properties builder interface loaded in an iframe overlay, with custom CSS, JS, and fonts. ([7c014d8](https://github.com/trongate/trongate-framework/commit/7c014d89f4ee393983e0c2dd1c86468cc490cb0a))
- **Query_builder** (`trongate_control/query_builder`) — visual SQL query builder with JOIN support, migrated from the mothership for zero cross-origin dependency. ([7c014d8](https://github.com/trongate/trongate-framework/commit/7c014d89f4ee393983e0c2dd1c86468cc490cb0a))
- Module manifest system — `manifest.json` for `trongate_control` with version, dependencies, features, and child module declarations. ([7c014d8](https://github.com/trongate/trongate-framework/commit/7c014d89f4ee393983e0c2dd1c86468cc490cb0a))
- `Templates::module_details()` method and corresponding `module_details.php` view for Flo's overlay iframe. ([7c014d8](https://github.com/trongate/trongate-framework/commit/7c014d89f4ee393983e0c2dd1c86468cc490cb0a))
- New SVG icon `list` and corresponding `.tg-list` CSS class. ([#234](https://github.com/trongate/trongate-framework/pull/234))
- `form_url()` helper and accompanying CSS to render `<input type="url">` on HTML forms. ([#231](https://github.com/trongate/trongate-framework/pull/231))
- Trongate MX `mx-swap-title` attribute. ([#230](https://github.com/trongate/trongate-framework/pull/230))
- `README.md` in project root. ([#224](https://github.com/trongate/trongate-framework/pull/224))
- `Login::hash_password()` public method so other modules can reuse the same bcrypt hashing. ([#229](https://github.com/trongate/trongate-framework/pull/229))
- New CSS custom properties in `trongate.css`: `--modal-danger`, `--modal-danger-dark`, `--overlay-bg`, `--modal-textarea-bg`, `--row-odd-bg`, `--row-hover-bg`. ([7c014d8](https://github.com/trongate/trongate-framework/commit/7c014d89f4ee393983e0c2dd1c86468cc490cb0a))
- Global `box-sizing: border-box` reset in `trongate.css`. ([7c014d8](https://github.com/trongate/trongate-framework/commit/7c014d89f4ee393983e0c2dd1c86468cc490cb0a))

### Changed
- `Flo::draw_flow_trigger()` reactivated (previously returned empty string). ([7c014d8](https://github.com/trongate/trongate-framework/commit/7c014d89f4ee393983e0c2dd1c86468cc490cb0a))
- Flo, Trongate_control, and Site_builder now load Evo for environment-disabled responses instead of inline 403 handling. ([7c014d8](https://github.com/trongate/trongate-framework/commit/7c014d89f4ee393983e0c2dd1c86468cc490cb0a))
- `admin.css` reorganised with theme token section headers. ([7c014d8](https://github.com/trongate/trongate-framework/commit/7c014d89f4ee393983e0c2dd1c86468cc490cb0a))
- `trongate.css` refactored — line-height values standardised, structural CSS cleaned up. ([7c014d8](https://github.com/trongate/trongate-framework/commit/7c014d89f4ee393983e0c2dd1c86468cc490cb0a))
- Site_builder rewritten: removed simulation mode, uses Evo for disabled responses, removed stale views. ([7c014d8](https://github.com/trongate/trongate-framework/commit/7c014d89f4ee393983e0c2dd1c86468cc490cb0a))
- Trongate MX updated (JS and minified JS). ([7c014d8](https://github.com/trongate/trongate-framework/commit/7c014d89f4ee393983e0c2dd1c86468cc490cb0a))
- Improved spacing of bullet and line elements in the `list` icon. ([#235](https://github.com/trongate/trongate-framework/pull/235))
- Simplified the blink CSS animation. ([#223](https://github.com/trongate/trongate-framework/pull/223))
- The `setup` `database_config` view now displays a note that the database will be created automatically. ([#227](https://github.com/trongate/trongate-framework/pull/227))

### Removed
- `engine/Trongate.php`: `read_manifest()` method (replaced by JSON-based `manifest.json`). ([7c014d8](https://github.com/trongate/trongate-framework/commit/7c014d89f4ee393983e0c2dd1c86468cc490cb0a))
- `modules/trongate_control/js/code-generator.js` — deleted. ([7c014d8](https://github.com/trongate/trongate-framework/commit/7c014d89f4ee393983e0c2dd1c86468cc490cb0a))
- `modules/trongate_control/js/flo-fetch.js` — deleted. ([7c014d8](https://github.com/trongate/trongate-framework/commit/7c014d89f4ee393983e0c2dd1c86468cc490cb0a))
- `modules/trongate_control/webhooks/Webhooks.php` — entire webhooks child module removed. ([7c014d8](https://github.com/trongate/trongate-framework/commit/7c014d89f4ee393983e0c2dd1c86468cc490cb0a))
- Stale Site_builder view files: `donor_showBACKUP.php`, `module_created.php`, `views_helper.php`. ([7c014d8](https://github.com/trongate/trongate-framework/commit/7c014d89f4ee393983e0c2dd1c86468cc490cb0a))

### Fixed
- Placeholder sidebar links set to `href='#'` to prevent 404 errors. ([#238](https://github.com/trongate/trongate-framework/pull/238))
- Selected themes now apply properly across the admin panel. ([#223](https://github.com/trongate/trongate-framework/pull/223))

## [2.2026.0522] - 2026-05-22

### Added
- Trongate MX `mx-build-iframe` attribute. ([#ad95458](https://github.com/trongate/trongate-framework/commit/ad954589426786fddc426a83470881460cd6d238), [#574c0fa](https://github.com/trongate/trongate-framework/commit/574c0fab45211038208f61a81875aed51a30da78))

### Changed
- Disabled the `trongate_control/flo` module trigger (`Flo::draw_flow_trigger()`). ([#574c0fa](https://github.com/trongate/trongate-framework/commit/574c0fab45211038208f61a81875aed51a30da78))

## [2.2026.0520] - 2026-05-20

### Added
- New SVG icons and corresponding CSS classes: `eye`, `pencil`, `search`. ([#d3be0c0](https://github.com/trongate/trongate-framework/commit/d3be0c04a90b6d5f649f248c694d855a1220f656)) 
- `site_builder` child module of `trongate_control`. ([#a04e8ed](https://github.com/trongate/trongate-framework/commit/a04e8edaeaa53254924e092a11b56ab6d0b2443e))

### Changed
- The `tg-admin` custom route is now an alias for `login/login/tg-admin`. ([#e83e8c4](https://github.com/trongate/trongate-framework/commit/e83e8c43a2741d4f625d04688c0b27e6586b2ee6))
- In the `login` config file, for user level `1`, a `secret_login_word` attribute was added and set to `tg-admin`, and `enable_forgot_password` was set to `false` by default. ([#e83e8c4](https://github.com/trongate/trongate-framework/commit/e83e8c43a2741d4f625d04688c0b27e6586b2ee6))
- `Login::logout()` was modified to attempt to redirect to the user's secret word page upon logout. ([#e83e8c4](https://github.com/trongate/trongate-framework/commit/e83e8c43a2741d4f625d04688c0b27e6586b2ee6))

### Fixed
- Added missing `Login::show_404()` method. ([#67088e4](https://github.com/trongate/trongate-framework/commit/67088e47e617fd246f0485f692716ee6102aded8))

## [2.2026.0506] - 2026-05-06

### Added
- `trongate_email` module and `trongate_email` config file. ([#21e9960](https://github.com/trongate/trongate-framework/commit/21e9960e841891ff8a05ede84327883779afecdf))

### Changed
- The `login` module sends password reminders using the `trongate_email` module. ([#21e9960](https://github.com/trongate/trongate-framework/commit/21e9960e841891ff8a05ede84327883779afecdf))
- Updated `‎modules/trongate_control/js/code-generator.js` so that modal width and height values for the Flo module can be non-numeric.  The Query Builder modal dimensions are now approximately `96vw` by `96vh`. ([#e8f1361](https://github.com/trongate/trongate-framework/commit/e8f13611464fe2a38a7410f63cfdb2beb59e71f2))

### Fixed
- In `config.php`, `DEFAULT_MODULE` and `DEFAULT_METHOD` were set to `setup` and `index` respectively. ([#88191b4](https://github.com/trongate/trongate-framework/commit/88191b43727550cf75d69a8407ee2c51db55a0c2))

## [2.2026.0505] - 2026-05-05

### Added
- `login` module and `login` config file. ([#61ecc0a](https://github.com/trongate/trongate-framework/commit/61ecc0a07b103f4e3b5249b07bdd8fd28b7f987c))
- `setup` module. ([#61ecc0a](https://github.com/trongate/trongate-framework/commit/61ecc0a07b103f4e3b5249b07bdd8fd28b7f987c))
- The `trongate_control/flo` (FLO) code generator child module, accessible through the admin UI when in `dev` mode. ([#b644873](https://github.com/trongate/trongate-framework/commit/b644873f7e54d312044c38296f3ef67eecabe728), [#70ab01b](https://github.com/trongate/trongate-framework/commit/70ab01b0fec4dac709fb8499120dec1dbe110fbf))

### Changed
- In Trongate CSS, made `.card-body` elements equal height in flexbox layouts. ([#8b40353](https://github.com/trongate/trongate-framework/commit/8b403536be84aa37a8e10cedd96cbcd5f6c088bd))

### Removed
- The `trongate_administrators` `login` and `not_allowed` view files. ([#61ecc0a](https://github.com/trongate/trongate-framework/commit/61ecc0a07b103f4e3b5249b07bdd8fd28b7f987c))

### Fixed
- `Language::load()` now returns the correct array, `$phrases`. ([#226](https://github.com/trongate/trongate-framework/pull/226))

## [2.2026.0425] - 2026-04-25

### Added
- The `Db::attempt_truncate()` public method attempts a `TRUNCATE` SQL statement on a table, resetting the autoincrement counter on success. ([#32cda9e](https://github.com/trongate/trongate-framework/commit/32cda9e74fff1e2b87b0677288a36a0f4820e81a))
- A new `language` module was added to facilitate multilingual validation messages. ([#245c2c7](https://github.com/trongate/trongate-framework/commit/245c2c7e98b0eadc660c156eda7a5a792347df9a))

### Changed
- Global helper functions are now thin wrappers over corresponding modules. ([#245c2c7](https://github.com/trongate/trongate-framework/commit/245c2c7e98b0eadc660c156eda7a5a792347df9a))
- Input validation now supports displaying messages in multiple languages. ([#245c2c7](https://github.com/trongate/trongate-framework/commit/245c2c7e98b0eadc660c156eda7a5a792347df9a))
- Dummy and broken links in the footer of the admin template were replaced with links to the framework homepage, GitHub repo, and documentation. ([#222](https://github.com/trongate/trongate-framework/pull/222))

### Removed
- The `file/file_validation` child module was removed. ([#245c2c7](https://github.com/trongate/trongate-framework/commit/245c2c7e98b0eadc660c156eda7a5a792347df9a))
- The `trongate_administrators/setup.sql` setup file was removed. ([#245c2c7](https://github.com/trongate/trongate-framework/commit/245c2c7e98b0eadc660c156eda7a5a792347df9a))

## [2.2026.0303] - 2026-03-03

### Added
- The `File::delete_directory()` public method recursively deletes all files and subdirectories of a given directory. ([#24b15ac](https://github.com/trongate/trongate-framework/commit/24b15ac1812bc7cd3f1b781dc34bfa39d2baca3f))

## [2.2026.0223] - 2026-02-23

### Changed
- `Core::invoke_controller_method()` behavior was modified to make it consistent with `block_url()` behavior. ([#b3bc943](https://github.com/trongate/trongate-framework/commit/b3bc943fa72f2445b98a7e1fdd5a091270689c45))
- Added `block_url('db')` to `Db.php` constructor to prevent direct URL access to all database methods. ([#b3bc943](https://github.com/trongate/trongate-framework/commit/b3bc943fa72f2445b98a7e1fdd5a091270689c45))
- Reintroduced `resequence_ids()` method to `Db.php` from v1. ([#b3bc943](https://github.com/trongate/trongate-framework/commit/b3bc943fa72f2445b98a7e1fdd5a091270689c45))
- The top margin on modal footer buttons was adjusted from `6` to `2` pixels. ([#9e81843](https://github.com/trongate/trongate-framework/commit/9e81843121dc2f46ae7297ee76597c4ff302272a))

## [2.2026.0128] - 2026-01-28

### Changed
- The date-based versioning system was introduced. ([#91c85f8](https://github.com/trongate/trongate-framework/commit/91c85f83c84c0b465190c0745fabb37c86b8920d))
- Minor changes to Trongate CSS, using variables instead of hard-coded color hex codes. ([#91c85f8](https://github.com/trongate/trongate-framework/commit/91c85f83c84c0b465190c0745fabb37c86b8920d))
- Input validation callbacks now prevent URL access by calling the `block_url()` utility helper function. Previously, the callbacks used the underscore (`_`) prefix convention. ([#e2253a0](https://github.com/trongate/trongate-framework/commit/e2253a08857aabfaabf37e70de26c012842eb187))

## [2.0.0-beta.1] - 2026-01-20
- Initial v2 release. Includes various breaking changes compared to v1.
