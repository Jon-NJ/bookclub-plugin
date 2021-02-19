CREATE TABLE `{{table}}` (
  `create_dt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `member_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `subject` varchar(120) NOT NULL DEFAULT '',
  `html` text NOT NULL DEFAULT ''
) ENGINE={{engine}} {{charset}};

ALTER TABLE `{{table}}`
  ADD PRIMARY KEY (`create_dt`);
