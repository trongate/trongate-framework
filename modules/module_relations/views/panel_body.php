<?php
/**
 * Module Relations — summary panel body (runtime).
 *
 * Rendered by Module_relations::render_panel_body() into the summary
 * panel's unique-ID container (the mx-get target), and re-rendered by
 * submit_association() / remove_association() as the out-of-band swap
 * response for the same two regions.
 *
 * The body is deliberately TWO panel-scoped regions:
 *
 *   #rel-{panel_id}-associate-form    — the "Associate with X" form.
 *                                       Rendered ONLY when $form_visible
 *                                       (at least one option available,
 *                                       and for one-to-one relations no
 *                                       association existing yet). When no
 *                                       option is available at all AND the
 *                                       calling record has no existing
 *                                       associations, the region holds an
 *                                       empty-state paragraph instead:
 *                                       "There are currently no … records
 *                                       available to assign to this …" —
 *                                       or, when the relation settings
 *                                       carry no record names, the generic
 *                                       "No records are currently
 *                                       available for assignment." A
 *                                       record that already has
 *                                       associations gets no such notice
 *                                       (its items are listed in the
 *                                       region below); nothing is left to
 *                                       assign, so the region is simply
 *                                       empty.
 *   #rel-{panel_id}-associated-items  — the associated-items list; an
 *                                       empty region when none exist (no
 *                                       placeholder paragraph).
 *
 * Every MX operation (add / remove) posts to its endpoint with
 * mx-target="none" and mx-select-oob pointing at these two regions, so
 * the response swaps ONLY what changed — never the whole panel. The
 * response body for a successful add/remove is this same view: MX picks
 * the two regions out of it and swaps them into the live page.
 *
 * All behaviour is Trongate MX attributes — no custom JavaScript.
 *
 * Selector discipline: never reference `.associated-items`
 * alone — a page may hold more than one panel. Everything that targets
 * this panel anchors on the containing element's unique ID
 * (make_rand_str(), CSS-escaped in $panel_selector when it leads with
 * a digit), e.g. `#\37 bgZaem6c6ExxnN2 > ul.associated-items`. The
 * region wrappers below use the `rel-` prefix so they always start with
 * a letter and never need CSS escaping.
 */

// Out-of-band swap instructions: refresh ONLY the two panel regions.
// The `select` key matches a region in the SERVER response; the `target`
// key matches the same region on the LIVE page (same id, two documents).
$oob_swaps = json_encode([
    ['select' => '#rel-' . $panel_id . '-associate-form', 'target' => '#rel-' . $panel_id . '-associate-form'],
    ['select' => '#rel-' . $panel_id . '-associated-items', 'target' => '#rel-' . $panel_id . '-associated-items']
]);
?>

<div id="rel-<?= out($panel_id) ?>-associate-form">
    <?php if ($form_visible): ?>
        <form action="#" class="associate-form"
              mx-post="module_relations/submit_association"
              mx-vals='<?= out(json_encode([
                  'calling_module' => $calling_module,
                  'alt_module'     => $alt_module,
                  'update_id'      => (int) $update_id,
                  'panel_id'       => $panel_id,
                  'csrf_token'     => $csrf_token
              ])) ?>'
              mx-headers='{"X-Requested-With": "XMLHttpRequest"}'
              mx-target="none"
              mx-select-oob='<?= out($oob_swaps) ?>'
              mx-on-error="#<?= out($panel_selector) ?>">
            <label for="<?= out($relation_name) ?>-dropdown" class="associate-form__label">
                Associate with <?= out(ucwords(str_replace('_', ' ', $associated_singular))) ?>
            </label>
            <div class="associate-form__controls">
                <select id="<?= out($relation_name) ?>-dropdown" name="value" class="associate-form__select">
                    <option value="">Select...</option>
                    <?php foreach ($available_options as $option): ?>
                        <option value="<?= (int) $option['key'] ?>"><?= out($option['value']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="associate-form__submit">Add</button>
            </div>
        </form>
    <?php elseif ((count($available_options) === 0) && (count($associated_rows) === 0)): ?>
        <?php if (($associated_singular !== '') && ($calling_singular !== '')): ?>
            <p class="associate-form__empty">There are currently no <?= out(str_replace('_', ' ', $associated_singular)) ?> records available to assign to this <?= out(str_replace('_', ' ', $calling_singular)) ?>.</p>
        <?php else: ?>
            <p class="associate-form__empty">No records are currently available for assignment.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div id="rel-<?= out($panel_id) ?>-associated-items">
    <?php if (count($associated_rows) > 0): ?>
        <ul class="associated-items">
            <?php foreach ($associated_rows as $row): ?>
                <li class="associated-items__item">
                    <a href="<?= out(BASE_URL . $alt_module . '/show/' . (int) $row['foreign_key']) ?>" class="associated-items__link"><?= out($row['value']) ?></a>
                    <button class="associated-items__remove"
                            aria-label="Remove <?= out($row['value']) ?>"
                            mx-post="module_relations/remove_association"
                            mx-vals='<?= out(json_encode([
                                'calling_module' => $calling_module,
                                'alt_module'     => $alt_module,
                                'update_id'      => (int) $update_id,
                                'value'          => (int) $row['id'],
                                'panel_id'       => $panel_id,
                                'csrf_token'     => $csrf_token
                            ])) ?>'
                            mx-headers='{"X-Requested-With": "XMLHttpRequest"}'
                            mx-target="none"
                            mx-select-oob='<?= out($oob_swaps) ?>'
                            mx-on-error="#<?= out($panel_selector) ?>">×</button>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>
