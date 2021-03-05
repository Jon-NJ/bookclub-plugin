<?php

/*
 * This configures the logging library. The actual functionality is provided by
 * the Apache Log4php package.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage framework
 * @license    https://opensource.org/licenses/MIT MIT
 */

namespace {
    /**
     * Implement formatter for 'localdate' (normal formatter uses UTC).
     */
    class LoggerPatternConverterLocalDate extends LoggerPatternConverterDate {

	private $format = self::DATE_FORMAT_ISO8601;

	public function activateOptions()
        {
            $this->format = $this->option;
	}

        public function convert(LoggerLoggingEvent $event)
        {
            $tz = \get_option('timezone_string') ?: 'Europe/Berlin';
            $dateTimeZone = new DateTimeZone($tz);
            $dateTime = new DateTime('now', $dateTimeZone);
            $ts = $event->getTimeStamp() + $dateTime->getOffset();
            return date($this->format, $ts);
        }
    }

    /**
     * Create formatter for 'localdate' (normal formatter uses UTC).
     */
    class LoggerLayoutPatternExtended extends LoggerLayoutPattern {
        public function __construct()
        {
            parent::__construct();
            $this->converterMap['localdate'] = 'LoggerPatternConverterLocalDate';
        }
    }
}

namespace bookclub {

/**
 * Class used for logging to database.
 * This appender uses a layout.
 */
class LoggerAppenderDatabase extends \LoggerAppender {

    /**
     * @var boolean true if the appender requires a layout
     */
    protected $requiresLayout = false;

    /**
     * Log a single event to the database.
     * @param \LoggerLoggingEvent $event logging event
     */
    public function append(\LoggerLoggingEvent $event): void
    {
        $table = tablePrefix('logs');
        if (existsTable($table)) {
            TableLogs::addLog(['LOG', $event->getLoggerName(),
                $event->getLevel(), input_server('REMOTE_ADDR')],
                    $this->layout ?
                        $this->layout->format($event) : $event->getMessage());
        }
    }
}

/** default logging level */
$default_level = is_development() ? 'debug': 'info';

$logger_from = getOption('error_sender');
$logger_to   = getOption('error_recipient');

/** appenders - (database and email) or file - production vs. development */
$default_appenders = $logger_to ?
        ['DatabaseAppender', 'EMailAppender'] :
        ['DatabaseAppender', 'FileAppender'];

/** logger configuration */
\Logger::configure([
    'appenders' => [
        'DatabaseAppender' => [
            'class' => 'bookclub\LoggerAppenderDatabase',
            'filters' => [[
                'class' => 'LoggerFilterLevelRange',
                'params' => [
                    'LevelMin' => $default_level,
                    'LevelMax' => 'fatal'
                ]
            ]]
        ],
        'FileAppender' => [
            'class' => 'LoggerAppenderFile',
            'layout' => [
                'class' => 'LoggerLayoutPatternExtended',
                'params' => [
                    'conversionPattern' =>
                        is_development()
                            ? '%localdate{m-d H:i:s} %-5level [%logger] %message%newline'
                            : '%localdate{H:i} %-5level %s{REMOTE_ADDR} [%logger] %message%newline'
                ]
            ],
            'params' => [
                'file' => is_development()
                    ? BOOKCLUBLOGS.DS.'bookclub.log'
                    : BOOKCLUBLOGS.DS.'bookclub_' . date('md') . '.log'
            ]
        ],
        'EMailAppender' => [
            'class' => 'LoggerAppenderMailEvent',
            'layout' => [
                'class' => 'LoggerLayoutPatternExtended',
                'params' => [
                    'conversionPattern' =>
                        '%localdate{m-d H:i:s} %-5level [%logger] %message%newline'
                ]
            ],
            'params' => [
                // should not be empty
                'from' => $logger_from ? $logger_from :'dummy@gmail.com',
                'to' => $logger_to ? $logger_to : 'dummy@gmail.com',
                'subject' => 'Bookclub plugin error'
            ],
            'filters' => [[
                'class' => 'LoggerFilterLevelRange',
                'params' => [
                    'LevelMin' => 'error',
                    'LevelMax' => 'fatal'
                ]
            ]]
        ]
    ],
    'rootLogger' => [
        'level' => $default_level,
        'appenders' => $default_appenders
    ],
    'loggers' => [
        'db'    => [
            'level' => $default_level,
            'appenders' => $default_appenders,
            'additivity' => false
        ],
        'db.options' => [
            'level' => 'info',
            'appenders' => $default_appenders,
            'additivity' => false
        ],
        'page'  => [
            'level' => $default_level,
            'appenders' => $default_appenders,
            'additivity' => false
        ],
        'ajax'  => [
            'level' => $default_level,
            'appenders' => $default_appenders,
            'additivity' => false
        ],
        'files' => [
            'level' => $default_level,
            'appenders' => $default_appenders,
            'additivity' => false
        ]
    ]
]);

}
