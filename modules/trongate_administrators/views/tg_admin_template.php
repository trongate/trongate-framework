<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="<?= BASE_URL ?>css/trongate.css">
	<title>Trongate Administrators</title>
</head>
<body>
	<?php
	if ((segment(2) !== 'login') && (segment(2) !== 'submit_login')) { ?>
	<div id="top-gutter">
		<div>
			<a href="<?= BASE_URL ?>trongate_administrators/go_home">
				<span class="hide-sm">Home</span>
				<i class="fa fa-home"></i>
			</a>
		</div>
		<div>
			<a href="<?= BASE_URL ?>trongate_administrators/manage">
				<span class="hide-sm">Manage Administrators</span>
				<i class="fa fa-gears"></i>
			</a>
			<a href="<?= BASE_URL ?>trongate_administrators/create/<?= $data['my_admin_id'] ?>">
				<span class="hide-sm">Your Account</span>
				<i class="fa fa-user"></i>
			</a>
			<a href="<?= BASE_URL ?>trongate_administrators/logout">
				<span class="hide-sm">Logout</span>
				<i class="fa fa-sign-out"></i>
			</a>
		</div>
	</div>	
	<?php	
	}
	?>
    <div class="container">
        <?= Template::display($data) ?>
    </div>
<style>
body {
    background: rgb(0,0,0);
    background: -webkit-linear-gradient(rgba(0,0,0,1) 0%, rgba(51,51,51,1) 35%, rgba(111,111,111,1) 100%);
    background: -o-linear-gradient(rgba(0,0,0,1) 0%, rgba(51,51,51,1) 35%, rgba(111,111,111,1) 100%);
    background: linear-gradient(rgba(0,0,0,1) 0%, rgba(51,51,51,1) 35%, rgba(111,111,111,1) 100%);
    background-repeat: no-repeat;
    background-size: cover;
    min-height: 100vh;
    color: #eee;
    font-family: "Lucida Sans Unicode", "Lucida Grande", sans-serif;
}

h1 { font-size: 2.4em; }
h2 { font-size: 2.0em; }
h3 { font-size: 1.7em; }
h4 { font-size: 1.5em; }
h5 { font-size: 1.2em; }

#top-gutter {
	display: flex;
	flex-direction: row;
	justify-content: space-between;
	line-height: 2.4em;
	padding: 0 12px;
	text-align: center;
}

#top-gutter > div:nth-child(1) {
	text-align: left !important;
}

#top-gutter a {
	color: #eee;
	text-decoration: none;
	margin-left: 24px;
}

#top-gutter a:hover {
	color: cyan;
}

.container {
	background-color:transparent;
}

.hide-sm {
    display: none;
}

table {
	background-color: #fff;
	color: #000;
}

table button {
	margin: 0;
}

.alt {
	background-color: #fff;
	border: 1px #555 solid;
}

.alt:hover {
	background-color: #fff;
}

.danger {
	background-color: #ff0000;
	border: 1px #dd0000 solid;
}

.danger:hover {
	background-color: #dd0000;
	border: 1px #dd0000 solid;
}

.error {
	background-color: red;
	color: #fff;
	padding: 4px;
	border-radius: 6px;
	font-size: 0.9em;
	margin-bottom: 4px;
}

@media (max-width: 840px) {
	h1 { font-size: 1.6em; text-align: center; }
	h2 { font-size: 1.4em; text-align: center; }
	h3 { font-size: 1.3em; text-align: center; }
	h4 { font-size: 1.2em; text-align: center; }
	h5 { font-size: 1.1em; text-align: center; }

	p {
		display:block;

		text-align: center;
	}
	.button {
		width: 100%;

	}
}

@media (max-width: 550px) {
	.float-right-lg {
		width: 100% !important;
	}
}

@media (min-width: 551px) {
	.float-right-lg {
		float: right;
		position: relative;
	}
}

@media (max-width: 839px) {

	#top-gutter {
		font-size: 1.4em;
	}

	.fa-home {
		left: -12px;
		position: relative;
	}
}

@media (min-width: 840px) {
    .hide-sm {
      display: inline-block;
    }
}
</style>
</body>
</html>
