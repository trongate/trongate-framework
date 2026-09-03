# module_builder — Flo's "Create Module" wizard

**Role:** this child module owns the interactive module-creation wizard —
and *only* that. Everything here is module-creation-exclusive.

**Scope rule:** if a piece of code could be reused by another Flo feature,
it does **not** belong here — it belongs in `evo` (PHP) or `flo.js` (JS).

## Wizard flow (session-backed, `$_SESSION['evo_wizard']`)

`enter_mod_name` → `submit_mod_name` → `add_nav_label` → `submit_nav_label`
→ `lets_add_properties_conf` → (Properties Builder iframe) →
`store_properties` (raw JSON body) → `choose_url_column` → `submit_url_col`
→ `choose_order_by` → `submit_order_by` → `conf_generate_mod` →
[`module_details/web` optional review overlay] → `run_gen` → **delegates to
`trongate_control-site_builder`** for actual generation.

## What is shared (do NOT duplicate here)

| Concern | Lives in |
|---|---|
| Error view (`render_error`) | `evo` — public, `block_url`-guarded |
| Details-review iframe shell (`render_details_iframe`) | `evo` — generic, title via `$page_title` |
| Generation-failure view (`render_generation_error`) | `evo` — public, `block_url`-guarded |
| Dev-mode guard | `evo::render_disabled_response()` |
| localStorage↔form JS (`populateFormFromLocalStorage`, `populateSelectDropdowns`, `afterValidation`) | `flo.js` — parameterized; this module only supplies `window.floFieldMapping` + `window.floPostedToStorageMap` |
| Reset (clears shared wizard session) | `evo::reset()` — resets the whole Flo app |

## Conventions used here

- Every controller method: doc block + type hints + return types.
- Views set `$data['view_module'] = 'trongate_control/module_builder'`.
- MX: `mx-target="main"`, `mx-target-loading="cloak"`, `mx-after-swap` →
  `TrongateCodeGenerator.focusOnInput`/`handleAfterMx`.
- Wizard session key: **`$_SESSION['evo_wizard']`** — shared across all Flo
  features. Do not rename.

