<?php
class Welcome extends Trongate {

	/**
	 * Display the default welcome page.
	 *
	 * @return void
	 */
	public function index(): void {
	    $this->view('welcome');
	}

	/**
	 * Display the optional database setup instructions page.
	 *
	 * @return void
	 */
	public function database_setup(): void {
	    $this->view('database_setup');
	}

}