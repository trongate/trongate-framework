<?php
class Welcome extends Trongate {

	/**
	 * Renders the (default) homepage for public access.
	 *
	 * @return void
	 */
	public function index(): void {
		$this->module('trongate_pages');
		$this->trongate_pages->display();
	}

}