CREATE TABLE `{{table}}` (
  `chat_id` int(10) UNSIGNED NOT NULL,
  `timestamp` timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `wordpress_id` int(10) UNSIGNED NOT NULL,
  `deleted_by` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `target_type` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `target_id` varchar(40) NOT NULL DEFAULT '',
  `message` varchar(255) NOT NULL DEFAULT ''
) ENGINE={{engine}} {{charset}};

ALTER TABLE `{{table}}`
  ADD PRIMARY KEY (`chat_id`);

ALTER TABLE `{{table}}`
  MODIFY `chat_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;
