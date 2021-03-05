CREATE TABLE `{{table}}` (
  `place_id` int(10) UNSIGNED NOT NULL,
  `place` varchar(30) NOT NULL DEFAULT '',
  `address` varchar(60) NOT NULL DEFAULT '',
  `map` varchar(100) NOT NULL DEFAULT '',
  `directions` text NOT NULL DEFAULT ''
) ENGINE={{engine}} {{charset}};

ALTER TABLE `{{table}}`
  ADD PRIMARY KEY (`place_id`);
