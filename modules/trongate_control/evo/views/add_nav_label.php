<div class="center-stage cloak">
    <div class="mt-1">Add Navigation Label</div>
    <form action="#" id="enter-nav-label-form"
        mx-post="trongate_control-evo/submit_nav_label"
        mx-target="main" mx-after-swap="TrongateCodeGenerator.handleAfterMx"
        mx-target="main" mx-target-loading="cloak">
        <div class="mt-1">
            <input type="text" name="nav_label" value="Manage <?= $record_name_plural ?>" autocomplete="off">
        </div>
        <div class="mt-1">
            <button>Submit</button>
        </div>
        <p>Submit empty value if not required</p>
    </form>
</div>
