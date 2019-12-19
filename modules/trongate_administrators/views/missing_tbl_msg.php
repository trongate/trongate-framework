<div class="row">
    <div class="twelve columns">
    <div id="logout">
        <?= anchor('trongate_administrators/logout', 'Logout') ?> <span style="font-size: 1.2em;">&#8594;</span>
    </div>

        <h1 style="color: red;">Oops!  <span style="font-size: 0.7em;">Looks like you've hit a bump in the road</span></h1>
        <h4>Let's get you up and running, quick style!</h4>
        <p>
            Trongate 1.3 and higher requires a database table called trongate_administrators, with at least 
            one table row inserted.
        </p>

        <p>
            Below is some SQL code that you can use to get you started.  Run the SQL code below through your 
            database and you should be good to go.
        </p>

        <h5>To get back on the Freelove Freeway, please go through the following steps:</h5>

        <ul>
        <li>STEP 1: make sure you have a table called 'trongate_user_levels' on your database.</li>
        <li>STEP 2: make sure you have a 'trongate_user_levels' module in your 'modules' directory.</li>
        <li>STEP 3: make sure you AT LEAST ONE RECORD on 'trongate_user_levels' with an ID of 1 (the default level_title is 'admin')</li>
        <li>STEP 4: make sure you have a table called 'trongate_users' on your database.</li>
        <li>STEP 5: make sure you have a 'trongate_users' module in your 'modules' directory.</li>
        <li>STEP 6: INSERT ONE RECORD into trongate users with a user_level_id of 1, if you don't already have one.</li>
        <li>STEP 7: Take a note of lowest 'id' from trongate_users where the user_level_id equals 1.</li>
        <li>STEP 8: Replace 'xxxx' in the SQL below with the id that you noted from Step 7.</li>
        <li>STEP 9: Now copy the SQL below and run it through your database administration software (e.g., PHPMyAdmin).</li>
        <li>STEP 10: Go back to doing whatever you were doing and enjoy life.</li>
        </ul>

        <h5>Here's your SQL code.  Good luck:</h5>

        <p>
            <textarea class="u-full-width" style="color: black; min-height: 360px;">CREATE TABLE `trongate_administrators` (
  `id` int(11) NOT NULL,
  `username` varchar(65) NOT NULL,
  `password` varchar(60) NOT NULL,
  `trongate_user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
INSERT INTO `trongate_administrators` (`id`, `username`, `password`, `trongate_user_id`) VALUES
('', 'admin', '$2y$11$u/nkfnoy35OfUugThcM59e4LQwruelWt1gxkiVhfVX2ouy6UnIWV6', xxxx);
ALTER TABLE `trongate_administrators`
  ADD PRIMARY KEY (`id`);
ALTER TABLE `trongate_administrators`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;
COMMIT;
            </textarea>
        </p>

        <p>
            PLEASE NOTE: The SQL code above will create a username of 'admin' with a password of 'admin'. 
            <br><br>If 'ENV' is set to 'dev' (on config.php) then you will not have to login.  Otherwise, you can login at: <?= BASE_URL ?>tg-admin.
        </p>

    </div>
</div>
