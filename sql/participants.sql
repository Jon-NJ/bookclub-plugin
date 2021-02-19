CREATE TABLE `{{table}}` (
  `event_id` varchar(40) NOT NULL,
  `member_id` int(10) UNSIGNED NOT NULL,
  `rsvp` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `waiting` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `modtime` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `comment` varchar(60) NOT NULL DEFAULT '',
  `email_sent` timestamp NULL DEFAULT NULL
) ENGINE={{engine}} {{charset}};

ALTER TABLE `{{table}}`
  ADD PRIMARY KEY (`event_id`,`member_id`);
