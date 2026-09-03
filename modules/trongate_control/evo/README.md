# evo — Flo's generic controller (shared utilities & menus)

**Role:** this child module holds ONLY commonly-used, generic Flo
functionality. Feature-specific code lives in dedicated child modules:
`module_builder` (Create Module wizard), `module_relations_builder` (Module Relations
wizard) and `site_builder` (generation engine).

## Public API (for sibling child modules)

| Method | Purpose | URL guard |
|---|---|---|
| `home()` | Flo home menu (loaded inside flo shell) | dev-only |
| `module_manager()` | Feature menu (Create Module / Create Module Relation) | dev-only |
| `reset()` | Clears `$_SESSION['evo_wizard']` — **whole-Flo reset** | dev-only |
| `render_error(string $message = '')` | Step-level error view (`.error` + Okay → `doReset()`) | `block_url` |
| `render_details_iframe(array $data = [])` | Generic "view/confirm details" overlay shell | `block_url` |
| `render_generation_error(string $message, string $more_info_url = '')` | Generation-failure view with Learn More | `block_url` |
| `render_disabled_response()` | 403 view for non-dev environments | n/a |

## The rule

**If code could be reused by another Flo feature, it goes here (PHP) or in
`flo.js` (JS) — not in a feature module.** Shared methods are `public` +
`block_url()`-guarded + optional args (so direct URL hits reach the
`block_url()` check and return a clean 403).

## Views that live here (generic)

`home`, `module_manager`, `error_element`, `disabled`,
`module_details_iframe`, `generation_error`.

