CREATE TABLE `{{table}}` (
  `group_id` int(10) UNSIGNED NOT NULL,
  `type` int(10) UNSIGNED NOT NULL,
  `tag` varchar(20) NOT NULL DEFAULT '',
  `description` varchar(50) NOT NULL DEFAULT '',
  `url` varchar(50) NOT NULL DEFAULT '',
  `t_event_id` varchar(80) NOT NULL DEFAULT '',
  `t_max_attend` varchar(5) NOT NULL DEFAULT '',
  `t_starttime` varchar(10) NOT NULL DEFAULT '',
  `t_endtime` varchar(10) NOT NULL DEFAULT '',
  `t_summary` varchar(80) NOT NULL DEFAULT '',
  `t_description` text NOT NULL DEFAULT '',
  `t_include` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE={{engine}} {{charset}};

ALTER TABLE `{{table}}`
  ADD PRIMARY KEY (`group_id`);
