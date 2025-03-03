<?php
class Welcome extends Trongate {

	/**
	 * Renders the (default) homepage for public access.
	 *
	 * @return void
	 */
	public function index(): void {
		$this->template('public', [
			'view_module' => 'welcome',
			'view_file' => 'welcome'
		]);
	}

	public function flip(): void
	{
		$this->template('public', [
			'view_module' => 'welcome',
			'view_file' => 'flip'
		]);
	}
}