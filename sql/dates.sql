CREATE TABLE `{{table}}` (
  `book_id` int(10) UNSIGNED NOT NULL,
  `group_id` int(10) UNSIGNED NOT NULL,
  `day` date NOT NULL,
  `place_id` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `hide` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `private` int(10) UNSIGNED NOT NULL DEFAULT '0',
  `priority` int(10) UNSIGNED NOT NULL DEFAULT '0'
) ENGINE={{engine}} {{charset}};

ALTER TABLE `{{table}}`
  ADD KEY `day` (`day`);
