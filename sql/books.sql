CREATE TABLE `{{table}}` (
  `book_id` int(10) UNSIGNED NOT NULL,
  `author_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(60) NOT NULL DEFAULT '',
  `cover_url` varchar(40) NOT NULL DEFAULT '',
  `summary` text NOT NULL DEFAULT ''
) ENGINE={{engine}} {{charset}};

ALTER TABLE `{{table}}`
  ADD PRIMARY KEY (`book_id`);
