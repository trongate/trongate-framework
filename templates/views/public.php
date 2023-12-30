<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="<?= BASE_URL ?>css/trongate.css">
	<link rel="stylesheet" href="<?= BASE_URL ?>css/app.css">
	<!-- don't change anything above here -->
	<!-- add your own stylesheet below here -->
	<title>Public</title>
</head>

<body>
	<div class="wrapper">
		<header>
			<div id="header-sm">
				<div id="hamburger" onclick="openSlideNav()">
					&#9776;
				</div>
				<div class="logo">
					<?= anchor(BASE_URL, WEBSITE_NAME) ?>
				</div>
				<div><?php
							echo anchor('account', '<i class="fa fa-user"></i>');
							echo anchor('logout', '<i class="fa fa-sign-out"></i>');
							?></div>
			</div>
			<div id="header-lg">
				<div class="logo">
					<?= anchor(BASE_URL, WEBSITE_NAME) ?>
				</div>
				<div>
					<ul id="top-nav">
						<li><a href="<?= BASE_URL ?>"><i class="fa fa-home"></i> Home</a></li>
						<li><a href="<?= BASE_URL ?>"><i class="fa fa-lightbulb-o"></i> About Us</a></li>
						<li><a href="<?= BASE_URL ?>"><i class="fa fa-street-view"></i> Our Values</a></li>
						<li><a href="<?= BASE_URL ?>"><i class="fa fa-gears"></i> How We Work</a></li>
						<li><a href="<?= BASE_URL ?>"><i class="fa fa-send"></i> Get In Touch</a></li>
					</ul>
				</div>
			</div>
		</header>
		<main class="container"><?= Template::display($data) ?></main>
	</div>
	<footer>
		<div class="container">
			<!-- it's okay to remove the links and content here - everything is cool (DC) -->
			<div>&copy; Copyright <?= date('Y') . ' ' . OUR_NAME ?></div>
			<div><?= anchor('https://trongate.io', 'Powered by Trongate') ?></div>
		</div>
	</footer>
	<div id="slide-nav">
		<div id="close-btn" onclick="closeSlideNav()">&times;</div>
		<ul auto-populate="true"></ul>
	</div>
	<script src="<?= BASE_URL  ?>js/app.js"></script>
</body>

</html>