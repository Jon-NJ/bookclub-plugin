CREATE TABLE `{{table}}` (
  `create_dt` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `member_id` int(10) UNSIGNED NOT NULL,
  `email_sent` timestamp NULL DEFAULT NULL
) ENGINE={{engine}} {{charset}};

ALTER TABLE `{{table}}`
  ADD PRIMARY KEY (`create_dt`,`member_id`);
