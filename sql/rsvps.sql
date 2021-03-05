CREATE TABLE `{{table}}` (
  `event_id` varchar(40) NOT NULL,
  `member_id` int(10) UNSIGNED NOT NULL,
  `rsvp` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `modtime` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE={{engine}} {{charset}};

ALTER TABLE `{{table}}`
  ADD KEY `event_id` (`event_id`,`member_id`);
