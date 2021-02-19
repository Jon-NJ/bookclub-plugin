CREATE TABLE `{{table}}` (
  `event_id` varchar(40) NOT NULL,
  `organiser` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `starttime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `endtime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modtime` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,
  `summary` varchar(80) NOT NULL DEFAULT '',
  `location` varchar(60) NOT NULL DEFAULT '',
  `map` varchar(80) NOT NULL DEFAULT '',
  `description` text NOT NULL DEFAULT '',
  `private` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `priority` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `max_attend` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `rsvp_attend` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE={{engine}} {{charset}};

ALTER TABLE `{{table}}`
  ADD PRIMARY KEY (`event_id`);
