CREATE TABLE `{{table}}` (
  `group_id` int(10) UNSIGNED NOT NULL,
  `wordpress_id` int(10) UNSIGNED NOT NULL
) ENGINE={{engine}} {{charset}};

ALTER TABLE `{{table}}`
  ADD PRIMARY KEY (`group_id`,`wordpress_id`);
