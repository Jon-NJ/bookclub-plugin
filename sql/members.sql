CREATE TABLE `{{table}}` (
  `member_id` int(10) UNSIGNED NOT NULL,
  `web_key` varchar(16)  NOT NULL,
  `name` varchar(32)  NOT NULL DEFAULT '',
  `email` varchar(50)  NOT NULL DEFAULT '',
  `active` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `format` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `ical` int(10) UNSIGNED NOT NULL DEFAULT '1',
  `noemail` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `hittime` datetime DEFAULT NULL,
  `wordpress_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `public_email` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE={{engine}} {{charset}};

ALTER TABLE `{{table}}`
  ADD PRIMARY KEY (`member_id`),
  ADD UNIQUE KEY `web_key` (`web_key`);
