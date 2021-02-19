CREATE TABLE `{{table}}` (
  `message_id` varchar(250) NOT NULL DEFAULT '',
  `wordpress_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `time_sent` timestamp NULL DEFAULT NULL
) ENGINE={{engine}} {{charset}};

ALTER TABLE `{{table}}`
  ADD PRIMARY KEY (`message_id`,`wordpress_id`);
