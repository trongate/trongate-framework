# image_uploader_builder — the 'Add Image Uploader' wizard

A **Trongate v2** dev tool, part of the **Flo** suite inside
`trongate_control`. It gives an existing CRUD module a single-image
feature: a **Picture** panel on the record's show page, a dedicated
upload page, and picture files that are removed when the record is
deleted.

The wizard chooses a module, writes a per-app settings JSON, runs an
**idempotent** `ALTER TABLE`, and **injects working code** into the
target module. Everything else lives in the runtime: generated code calls
the top-level `modules/image_uploader/` module.

## What generation produces

1. **Schema** — `ALTER TABLE {table} ADD COLUMN {column} VARCHAR(255)
   NOT NULL DEFAULT ''`. A column that already exists with a compatible
   definition is left alone (reported as "no schema change was needed"); a
   column with an **incompatible** definition fails loudly and nothing is
   changed — never a silent alter.
2. **Settings** — `modules/image_uploader/settings/{module}.json`, the
   contract the runtime reads on every request (column, destination,
   size/dimension caps, resize target, thumbnail settings). Its presence
   doubles as the "already has an uploader" marker, and it is **deleted if
   generation fails mid-way**, so a retry is never blocked by a stale
   marker.
3. **Code injection** — three items, the runtime's whole reach into the
   target module:
   - `views/show.php` — the `draw_panel()` call, appended behind the
     details card's final closing `</div>` (and behind any panel call a
     sibling wizard has already appended there);
   - `{Module}.php` — a **capture** line inserted *before* the row
     deletion in `submit_delete()`, recording the picture file name while
     the row still exists;
   - `{Module}.php` — the **file-removal** call inserted *after* it: row
     first, files second.

   Each item carries a marker that guards against double-injection.

## Conventions

- `''` (empty string) is the single "no picture" sentinel: the column is
  `VARCHAR(255) NOT NULL DEFAULT ''`. Numeric settings default to `0` —
  never `NULL` (the framework's no-NULL sentinel convention).
- The database stores the **file name only**. Paths and URLs are always
  constructed server-side:
  `modules/{module}/{destination}/{update_id}/` for the main image and a
  `thumbs/` subfolder for the thumbnail.
- Identifiers (`module`, `table`, `column`, `destination`) must match
  `^[a-z0-9_]+$` — the same rule the runtime enforces on every read, so
  table, column and directory names are safe to concatenate into SQL and
  filesystem paths. The client's selection is never trusted: the eligible
  module list is recomputed server-side on submission.
- Injected methods are `private` and contain no SQL — generated modules
  delegate all query and file work to the `image_uploader` runtime via
  `Modules::run()`.
- Injection anchors are properties of the wizard's own scaffold output,
  never guesses about a particular module: the generated show view ends
  with the details card, and `submit_delete()` contains one record load
  and one row-deletion call. Behind that card the preflight tolerates one
  thing only — a panel call a sibling wizard has already appended (a
  module that has been through the module relations wizard ends with
  `<?= Modules::run('module_relations/draw_summary_panel', '…') ?>`, not
  with the `</div>`); the uploader's call goes after it. Anything else is
  refused by the preflight — never worked around.
- Generation-time only: the wizard refuses to run unless `ENV` is `'dev'`.
- No public method name may contain the module-assets trigger (`_module`,
  see `MODULE_ASSETS_TRIGGER`) as a substring — the router would serve it
  as a module asset instead of routing it. Hence `choose_mod()` and
  `submit_mod()`.

## Runtime contract

Generated code calls the top-level `modules/image_uploader/` module:
`draw_panel()` and `delete_files()` are `Modules::run()` internals,
`block_url()`-guarded; `render_panel_body`, `upload_form`,
`submit_upload` and `remove_picture` are the browser/MX surface
(session-authenticated, with both mutating endpoints CSRF-gated). The
settings JSON shape and the storage layout are documented in the runtime
model's docblock.

## Wizard flow

`choose_mod` (menu entry, clears stale wizard state) → `submit_mod`
(re-validates the choice, refuses a module that already has an uploader,
seeds defaults) → `conf_generate_uploader` → `uploader_details` (review
step, also renderable as a full page inside evo's shared details iframe)
→ `run_gen` (pre-flight guards → injection plan → idempotent ALTER →
settings JSON → commit; any failure after the settings write removes it).

## Requirements

- **GD** — the framework `Image` module needs it, so a missing `gd` is a
  pre-flight failure with an actionable message.
- The runtime settings directory and the target module's destination
  folder must be writable by the PHP process; the destination root is
  created (empty) at generation time and per-record folders are created
  by the runtime on upload.
- **Injection target files must be writable by the PHP process.** A fresh
  `git checkout` leaves them at 0644 — the preflight then **fails loudly**
  and writes nothing. `chmod 0666` the target module's controller and show
  view (same convention as `modules/templates/views/admin.php`) to
  proceed.
