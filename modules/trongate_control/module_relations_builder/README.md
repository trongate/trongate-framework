# module_relations_builder — the 'Create Module Relation' wizard

A **Trongate v2** dev tool, part of the **Flo** suite inside
`trongate_control`. It creates relations between two modules:

- **one to one** — with or without a bridging table
- **one to many** — child module gains a parent dropdown
- **many to many** — junction table, panel wiring, no create-form injection

The wizard (4 steps: relation type → parent → child → bridging option)
writes a per-app settings JSON, executes the schema SQL (FK columns /
junction table), and **injects working code** into the target modules.
Every relation type wires the show-page summary panel into **both**
modules' show views; 1:1 and 1:N relations additionally inject
create-form code: dropdowns, `sync_*`/`release_*` claim logic, and model
FK lines. Injected methods are `private` (no URL exposure). Injected
dropdowns lead with a '— None —' option (key 0), so records can be
created unlinked and existing 1:1 links can be cleared (disassociate)
straight from the edit form.

## Relation types

| Type | SQL produced |
|---|---|
| `one to one` (no bridging table) | Mirrored FKs on **BOTH** tables — `{singular_b}_id` on parent, `{singular_a}_id` on child, plain index each |
| `one to one` (with bridging table) | `CREATE TABLE associated_{parent}_and_{child}` junction — `id` PK, two singular `*_id` columns, **UNIQUE index per side** (one link per record) |
| `one to many` | `ALTER TABLE child` — adds `{singular parent}_id INT` + plain index |
| `many to many` | `CREATE TABLE associated_{parent}_and_{child}` junction — `id` PK, two singular `*_id` columns, composite UNIQUE index (no duplicate pairs) |

## Conventions

- Junction tables are named `associated_{parent}_and_{child}` (never
  `{parent}_{child}`).
- Relation-type keys are the **literal spaced values** (`one to one`,
  `one to many`, `many to many`) — underscored variants (`one_to_one`) are
  not valid anywhere: not in the UI (`mx-vals`), not in the controller
  validation, not in `run_gen()`.
- Relations follow Trongate's idiomatic **soft** style: plain INT `*_id`
  columns with indexes — **no DB-level FOREIGN KEY constraints** (Trongate
  modules manage their own cascades, so hard constraints would break delete
  flows).
- Injected methods are `private`, use no underscores, and contain no SQL —
  generated modules delegate all query logic to the `module_relations`
  runtime module via `Modules::run()`.
- Numeric FK defaults are `0` — never `NULL` (the framework's no-NULL
  sentinel convention).

## Runtime contract

Generated code calls the top-level `modules/module_relations/` module
(browser endpoints are session-authenticated and CSRF-gated; `Modules::run`
internals are `block_url()`-guarded). Per-app relation contracts are
written to `modules/module_relations/settings/` as JSON at generation time;
the settings shape is documented in the runtime model's docblock.

## Wizard flow

`choose_relation_type` (menu entry, clears stale state) →
`submit_relation_type` → `submit_parent` → `submit_child` (computes
defaults; asks the bridging question for one to one) →
`submit_bridging_option` (one to one only) → `conf_generate_relation` →
`run_gen` (pre-flight eligibility guard → schema SQL executed → settings
JSON written **after** the schema exists; any failure removes the settings
file so a retry is never blocked by a stale "relation exists" marker).

> **Writable-file preflight:** injection target files must be writable by
> the PHP process. A fresh `git checkout` leaves them at 0644 — the
> preflight then **fails loudly** and writes nothing. `chmod 0666` the
> target module files (same convention as
> `modules/templates/views/admin.php`) to proceed.
