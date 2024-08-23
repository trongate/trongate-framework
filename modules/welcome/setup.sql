SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

CREATE TABLE IF NOT EXISTS `trongate_administrators` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(65) DEFAULT NULL,
  `password` varchar(60) DEFAULT NULL,
  `trongate_user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `trongate_administrators` (`id`, `username`, `password`, `trongate_user_id`) VALUES
(1, 'admin', '$2y$11$SoHZDvbfLSRHAi3WiKIBiu.tAoi/GCBBO4HRxVX1I3qQkq3wCWfXi', 1);

CREATE TABLE IF NOT EXISTS `trongate_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comment` text DEFAULT NULL,
  `date_created` int(11) DEFAULT 0,
  `user_id` int(11) DEFAULT NULL,
  `target_table` varchar(125) DEFAULT NULL,
  `update_id` int(11) DEFAULT NULL,
  `code` varchar(6) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `trongate_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url_string` varchar(255) DEFAULT NULL,
  `page_title` varchar(255) DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `page_body` text DEFAULT NULL,
  `date_created` int(11) DEFAULT NULL,
  `last_updated` int(11) DEFAULT NULL,
  `published` tinyint(1) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `trongate_pages` (`id`, `url_string`, `page_title`, `meta_keywords`, `meta_description`, `page_body`, `date_created`, `last_updated`, `published`, `created_by`) VALUES
    (1, 'homepage', 'Homepage', '', '', '<div style=\"text-align: center;\">\n    <h1>It Totally Works!</h1>\n</div>\n<div class=\"text-div\">\n    <p>\n        <i>Congratulations!</i> You have successfully installed Trongate.  <b>This is your homepage.</b>  Trongate was built with a focus on lightning-fast performance, while minimizing dependencies on third-party libraries. By adopting this approach, Trongate delivers not only exceptional speed but also rock-solid stability.\n    </p>\n    <p>\n        <b>You can change this page and start adding new content through the admin panel.</b>\n    </p>\n</div>\n<h2>Getting Started</h2>\n<div class=\"text-div\">\n    <p>\n        To get started, log into the <a href=\"[website]tg-admin\">admin panel</a>. From the admin panel, you\'ll be able to easily edit <i>this</i> page or create entirely <i>new</i> pages. The default login credentials for the admin panel are as follows:\n    </p>\n    <ul>\n        <li>Username: <b>admin</b></li>\n        <li>Password: <b>admin</b></li>\n    </ul>\n</div>\n<div class=\"button-div\" style=\"cursor: pointer; font-size: 1.2em;\">\n    <div style=\"text-align: center;\">\n        <button onclick=\"window.location=\'[website]tg-admin\';\">Admin Panel</button>\n        <button class=\"alt\" onclick=\"window.location=\'https://trongate.io/docs\';\">Documentation</button>\n    </div>\n</div>\n<h2 class=\"mt-2\">About Trongate</h2>\n<div class=\"text-div\">\n    <p>\n        <a href=\"https://trongate.io/\" target=\"_blank\">Trongate</a> is an open source project, written in PHP. The GitHub repository for Trongate is <a href=\"https://github.com/trongate/trongate-framework\" target=\"_blank\">here</a>. Contributions are welcome! If you\'re interested in learning how to build custom web applications with Trongate, a good place to start is The Learning Zone. The URL for the Learning Zone is: <a href=\"https://trongate.io/learning-zone\" target=\"_blank\">https://trongate.io/learning-zone</a>. <b>If you enjoy working with Trongate, all we ask is that you give Trongate a star on <a href=\"https://github.com/trongate/trongate-framework\" target=\"_blank\">GitHub</a>.</b> It really helps!\n    </p>\n    <p>\n        Finally, if you run into any issues or you require technical assistance, please do visit our free Help Bar, which is at: <a href=\"https://trongate.io/help_bar\" target=\"_blank\">https://trongate.io/help_bar</a>.\n    </p>\n</div>', 1723807486, 0, 1, 1);

CREATE TABLE IF NOT EXISTS `trongate_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `token` varchar(125) DEFAULT NULL,
  `user_id` int(11) DEFAULT 0,
  `expiry_date` int(11) DEFAULT NULL,
  `code` varchar(3) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `trongate_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(32) DEFAULT NULL,
  `user_level_id` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `trongate_users` (`id`, `code`, `user_level_id`) VALUES
(1, 'Tz8tehsWsTPUHEtzfbYjXzaKNqLmfAUz', 1);

CREATE TABLE IF NOT EXISTS `trongate_user_levels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `level_title` varchar(125) DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `trongate_user_levels` (`id`, `level_title`) VALUES
(1, 'admin');
COMMIT;