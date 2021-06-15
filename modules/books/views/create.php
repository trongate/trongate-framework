<?php 
echo form_open('books/submit');
echo form_input('username', '');
echo form_submit('submit', 'Submit');
echo form_close();