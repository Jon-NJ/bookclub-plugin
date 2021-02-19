CREATE TABLE `{{table}}` (
  `message_id` varchar(250) NOT NULL DEFAULT '',
  `subject` varchar(100) NOT NULL DEFAULT '',
  `uid` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `wordpress_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `timestamp` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  `processed` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `status` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `target` varchar(40) NOT NULL DEFAULT '',
  `target_type` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `target_id` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE={{engine}} {{charset}};

ALTER TABLE `{{table}}`
  ADD PRIMARY KEY (`message_id`);
