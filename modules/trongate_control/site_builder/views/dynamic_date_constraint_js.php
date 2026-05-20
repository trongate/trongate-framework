<?php if ($needs_date_constraint_js): ?>
&lt;script&gt;
document.addEventListener('DOMContentLoaded', () =&gt; {
    const now = new Date();
    const localToday = now.getFullYear() + '-' +
        String(now.getMonth() + 1).padStart(2, '0') + '-' +
        String(now.getDate()).padStart(2, '0');

    const localNow = localToday + 'T' +
        String(now.getHours()).padStart(2, '0') + ':' +
        String(now.getMinutes()).padStart(2, '0');

    document.querySelectorAll('[data-date-constraint]').forEach(input =&gt; {
        const constraint = input.dataset.dateConstraint;
        const isDatetime = input.type === 'datetime-local';
        const isTime = input.type === 'time';

        let bound;
        if (isTime) {
            bound = String(now.getHours()).padStart(2, '0') + ':' +
                    String(now.getMinutes()).padStart(2, '0');
        } else if (isDatetime) {
            bound = localNow;
        } else {
            bound = localToday;
        }

        if (constraint === 'past') {
            input.max = bound;
        } else if (constraint === 'future') {
            input.min = bound;
        }
    });
});
&lt;/script&gt;
<?php endif; ?>