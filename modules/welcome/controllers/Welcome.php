<?php
class Welcome extends Trongate {
 
	/**
	 * Renders the (default) 'welcome' webpage for public access.
	 *
	 * @return void
	 */
	function index(): void {
	    $data['view_module'] = 'welcome'; // Indicates the module where the view file exists.
	    $data['view_file'] = 'welcome'; // Specifies the base name of the target PHP view file.
	    $this->template('public', $data); // Loads the 'welcome' view file within the public template.
	}

}