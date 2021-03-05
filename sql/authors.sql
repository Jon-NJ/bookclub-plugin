CREATE TABLE `{{table}}` (
  `author_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(40) NOT NULL DEFAULT '',
  `link` varchar(80) NOT NULL DEFAULT '',
  `bio` text NOT NULL DEFAULT ''
) ENGINE={{engine}} {{charset}};

ALTER TABLE `{{table}}`
  ADD PRIMARY KEY (`author_id`);
