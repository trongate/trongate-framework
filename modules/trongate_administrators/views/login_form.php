<div class="row">
    <div class="eight columns offset-by-two">
        <h1>Admin Panel Login</h1>
        <?= validation_errors('<div class="validation-error">', '</div>') ?>
        <form action="<?= $form_location ?>" method="post">
            <label>Username</label>
            <input name="username" class="u-full-width" type="text" placeholder="Enter your username here">
           
            <label>Password</label>
            <input name="password" class="u-full-width" type="password" placeholder="Enter your password here">

            <label>
                <input type="checkbox" name="remember" value="1">
                <span class="label-body">Remember me</span>
            </label>
            <input class="button-primary" type="submit" name="submit" value="Submit">
        </form>
    </div>
</div>

<style>
.container {
    margin-top: 20vh;
}
</style>