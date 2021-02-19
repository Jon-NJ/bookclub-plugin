CREATE TABLE `{{table}}` (
  `post_dt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `poster` varchar(20) NOT NULL DEFAULT '',
  `member_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `message` text NOT NULL DEFAULT ''
) ENGINE={{engine}} {{charset}};

ALTER TABLE `{{table}}`
  ADD PRIMARY KEY (`post_dt`);
