CREATE TABLE `{{table}}` (
  `timestamp` timestamp(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
  `type` varchar(20) NOT NULL,
  `param1` varchar(20) DEFAULT NULL,
  `param2` varchar(20) DEFAULT NULL,
  `param3` varchar(20) DEFAULT NULL,
  `message` text NOT NULL DEFAULT ''
) ENGINE={{engine}} {{charset}};

ALTER TABLE `{{table}}`
  ADD UNIQUE KEY `params` (`timestamp`,`type`,`param1`,`param2`,`param3`);
