<?php namespace bookclub;

/*
 * This is the layout library. It defines global instances $twig_loader and
 * $twig_env_html, but these are only used in this file. The actual
 * functionality is provided by the Symphony Twig package. The few remaining
 * macro functions were also put here because of the similarity with twig.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage framework
 * @license    https://opensource.org/licenses/MIT MIT
 */

use Twig\Extra\Markdown\MarkdownExtension;
use Twig\Extra\Markdown\DefaultMarkdown;
use Twig\Extra\Markdown\MarkdownRuntime;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

/** template loader for twig */
$twig_loader = new \Twig\Loader\FilesystemLoader([
        BOOKCLUBPATH.DS.'twig/',
        BOOKCLUBPATH.DS.'email/',
        BOOKCLUBPATH.DS.'help/']);

/** twig environment */
$twig_env_html = new \Twig\Environment($twig_loader, [
    'auto_reload' => true
]);

/* include Markdown loader */
$twig_env_html->addRuntimeLoader(new class implements RuntimeLoaderInterface {
    public function load($class) {
        if (MarkdownRuntime::class === $class) {
            return new MarkdownRuntime(new DefaultMarkdown());
        }
    }
});

/* include Markdown package */
$twig_env_html->addExtension(new MarkdownExtension());

/**
 * Twig filter 'ical_format' splits up lines wider than the specified maximum
 * @param string $string string to filter
 * @param int $mlen maximum width
 * @return string filter results
 */
function twig_ical_format(string $string, int $mlen = 76): string
{
    $dest = mb_substr($string, 0, $mlen);
    $source = mb_substr($string, $mlen);
    while (mb_strlen($source)) {
        $dest .= "\n " . mb_substr($source, 0, $mlen);
        $source = mb_substr($source, $mlen);
    }
    return $dest . "\n";
}

$twig_env_html->addFilter(new \Twig\TwigFilter('ical_format',
        '\\' . __NAMESPACE__ . '\\twig_ical_format'));

/**
 * Twig filter 'base64_encode' encodes into base64.
 * @param string $string string to filter
 * @return string filter results
 */
function twig_base64_encode(string $string): string
{
    return base64_encode($string);
}

$twig_env_html->addFilter(new \Twig\TwigFilter('base64_encode',
        '\\' . __NAMESPACE__ . '\\twig_base64_encode'));

/**
 * Twig filter 'chunk_split' splits up lines larger than maximum size.
 * @param string $string string to filter
 * @param int $chunklen maximum chunk size
 * @return string filter results
 */
function twig_chunk_split(string $string, int $chunklen = 76): string
{
    return chunk_split($string, $chunklen);
}

$twig_env_html->addFilter(new \Twig\TwigFilter('chunk_split',
        '\\' . __NAMESPACE__ . '\twig_chunk_split'));

/**
 * Twig filter 'html_to_ical_text' takes HTML, strips out markup and adjust
 * for ICAL format.
 * @param string $string string to filter
 * @return string filter results
 */
function twig_html_to_ical_text(string $string): string
{
    $string = preg_replace('/<a[^>]*>/', '', $string);
    $string = str_replace('</a>', '', $string);
    $string = str_replace('<b>', '', $string);
    $string = str_replace('</b>', '', $string);
    $string = str_replace('<i>', '', $string);
    $string = str_replace('</i>', '', $string);
    $string = str_replace('<u>', '', $string);
    $string = str_replace('</u>', '', $string);
    $string = str_replace('<li>', '', $string);
    $string = str_replace('</li>', '', $string);
    $string = str_replace('<p>', '', $string);
    $string = str_replace('</p>', '', $string);

    // additional substitions
    $string = str_replace("\\", "\\\\", $string);
    $string = str_replace("\r", '', $string);
    $string = str_replace("\n", '', $string);
    $string = str_replace('<br>', "\\n", $string);
    $string = str_replace('<br\>', "\\n", $string);
    $string = str_replace('<hr>', "---\\n", $string);
    $string = str_replace('&nbsp;', ' ', $string);
    $string = str_replace(',', "\,", $string);
    return $string;
}

$twig_env_html->addFilter(
        new \Twig\TwigFilter('html_to_ical_text',
                '\\' . __NAMESPACE__ . '\\twig_html_to_ical_text'));

/**
 * Twig filter 'html_to_text' removes markup from HTML.
 * @param string $string string to filter
 * @return string filter results
 */
function twig_html_to_text(string $string): string
{
    $string = preg_replace('/<a[^>]*>/', '', $string);
    $string = str_replace('</a>', '', $string);
    $string = str_replace('<b>', '', $string);
    $string = str_replace('</b>', '', $string);
    $string = str_replace('<i>', '', $string);
    $string = str_replace('</i>', '', $string);
    $string = str_replace('<u>', '', $string);
    $string = str_replace('</u>', '', $string);
    $string = str_replace('<li>', '', $string);
    $string = str_replace('</li>', '', $string);
    $string = preg_replace('/<p[^>]*>/', '', $string);
    $string = str_replace('</p>', '', $string);
    $string = preg_replace('/<style>[^<]*<\/style>/', '', $string);

    // additional substitions
    $string = str_replace("\\", "\\\\", $string);
    $string = str_replace("\r", '', $string);
    $string = str_replace("\n", '', $string);
    $string = str_replace('<br>', "\n", $string);
    $string = str_replace('<br\>', "\n", $string);
    $string = str_replace('<hr>', "---\n", $string);
    $string = str_replace('&nbsp;', ' ', $string);
    return $string;
}

$twig_env_html->addFilter(
        new \Twig\TwigFilter('html_to_text',
                '\\' . __NAMESPACE__ . '\\twig_html_to_text'));

function twig_render($layout, array $data): string
{
    global $twig_env_html;
    $logger = \Logger::getLogger("files.render");
    $debug  = $logger->isDebugEnabled();
    if ($debug) {
        write_debug_file('debug_json_file', BOOKCLUBLOGS.DS.'bookclub.json',
                json_encode($data));
    }
    if (is_string($layout)) {
        $template = $twig_env_html->load($layout . '.twig');
    } else {
        $template = $layout;
    }
    $html = $template->render($data);
    if ($debug) {
        write_debug_file('debug_html_file', BOOKCLUBLOGS.DS.'bookclub.html',
                $html);
    }
    return $html;
}

/**
 * Create a template from the given string.
 * @param string $source template as a string
 * @return \Twig\TemplateWrapper
 */
function twig_template(string $source): \Twig\TemplateWrapper
{
    global $twig_env_html;
    return $twig_env_html->createTemplate(macro_convert($source));
}

/* old style macro handling */

/**
 * Sometimes a user name contains special characters. This detects if a
 * conversion is necessary and converts the name to base64 encoding.
 * @param string $name original user name
 * @return string original or adjusted user name
 */
function getAdjustedName(string $name): string
{
    $found = false;
    for ($i = 0; !$found && ($i < strlen($name)); $i++){
        $found = ord($name[$i]) >= 128 || $name[$i] == '-';
    }
    return $found
            ? '=?utf-8?b?' . base64_encode($name) . '?='
            : $name;
}

/**
 * Create a JSON array based on the passed object.
 * @param TableMembers|TableEMails|TableEvents|\WP_User|null $donor source object
 * @return array a dictionary of values from the object
 */
function twig_macro_object(?object $donor): array
{
    $macros = [];
    if ($donor instanceof TableMembers) {
        $member = new TableMembers();
        $macros['web_key']     = $donor->web_key;
        $macros['email']       = $donor->email;
        $macros['name']        = $donor->name;
        $pos                   = strrpos($donor->name, ' ');
        if ($pos) {
            $first = substr($donor->name, 0, $pos);
            $last  = substr($donor->name, $pos + 1);
        } else {
            $first = $donor->name;
            $last  = '(Last name missing)';
        }
        $macros['first']       = $first;
        $macros['last']        = $last;
        if (!$donor->wordpress_id) {
            $macros['signup']  = url_signup($donor);
        }
        $macros['format']      = $donor->format;
        $macros['ical']        = $donor->ical;
        $macros['utf8name']    = getAdjustedName($donor->name);
    } elseif ($donor instanceof TableEMails) {
        $macros['subject']     = $donor->subject;
        $macros['create_dt']   = $donor->create_dt;
        $macros['body']        = twig_template($donor->html);
    } elseif ($donor instanceof TableEvents) {
        $event = $donor;
        $macros['summary']     = $donor->summary;
        $macros['event_id']    = $donor->event_id;
        $macros['location']    = $donor->location;
        $macros['map']         = $donor->map;
        $macros['starttime']   = $donor->starttime;
        $macros['endtime']     = $donor->endtime;
        $macros['modtime']     = $donor->modtime;
        $macros['description'] = twig_template($donor->description);
    } elseif ($donor instanceof \WP_User) {
        $first                 = $donor->first_name ?: '(First name missing)';
        $last                  = $donor->last_name ?: '(Last name missing)';
        $macros['email']       = $donor->user_email;
        $macros['login']       = $donor->user_login;
        $macros['nice']        = $donor->user_nicename;
        $macros['name']        = "$first $last";
        $macros['first']       = $first;
        $macros['last']        = $last;
        $macros['utf8name']    = getAdjustedName("$first $last");
        $macros['utf8nice']    = getAdjustedName($donor->user_nicename);
        $macros['utf8login']   = getAdjustedName($donor->user_login);
    } elseif ($donor instanceof TableGroups) {
        $macros['group_id']    = $donor->group_id;
        $macros['tag']         = $donor->tag;
        $macros['description'] = $donor->description;
        $macros['url']         = $donor->url;
    } elseif ($donor) {
        $logger = \Logger::getLogger("layout.macros");
        $logger->error("Unexpected object: " . get_class($donor));
    }
    return $macros;
}

/**
 * Create JSON array for the passed arguments for use in rendering a template.
 * @param array $json JSON to use for template rendering
 * @param array $donors array of TableMembers|TableEMails|TableEvents|\WP_User|null
 * @return array JSON macro array
 */
function twig_macro_fields(array $donors): array
{
    $event   = null;
    $member  = null;
    $macros  = [ 'hash' => md5(date('r', time())) ];
    foreach (splitOption('defines') as $define) {
        $pos = strpos($define, '=');
        $macros[substr($define, 0, $pos)] = substr($define, $pos + 1);
    }
    foreach ($donors as $donor) {
        $macros = \array_merge($macros, twig_macro_object($donor));
        if ($donor instanceof TableMembers) {
            $member = $donor;
        } elseif ($donor instanceof TableEvents) {
            $event = $donor;
        }
    }
    if ($event && $member) {
        $macros['rsvplink'] = url_rsvp($event, $member->web_key);
    }
    $macros['profile']  = url_profile();
    $macros['host']     = input_server('HTTP_HOST');
    $macros['timezone'] = \get_option('timezone_string');
    $macros['timeinfo'] = getOption('timezone_info');
    //$macros['support']  = email_support();
    return $macros;
}

/**
 * Convert old style templates to twig format with known (expected) macro names.
 * @param string $source original old style template with macros
 * @return string new resulting twig template
 */
function macro_convert(string $source): string
{
    return macro_replace($source,[
        'email'           => '{{ email }}',
        'event_id'        => '{{ event_id }}',
        'first'           => '{{ first }}',
        'last'            => '{{ last }}',
        'name'            => '{{ name }}',
        'sender'          => '{{ sender }}',
        'signature'       => '{{ signature }}',
        'subject'         => '{{ subject }}',
        'summary'         => '{{ summary }}',
        'web_key'         => '{{ web_key }}',
        'who'             => '{{ who }}'
    ]);
}

/**
 * Replace macros within the passed string.
 * @param string $source source string to apply macros to
 * @param array $macros mapping array of macros and replacements
 * @return string result of all replacements
 */
function macro_replace(string $source, array $macros): string
{
    foreach ($macros as $key => $value) {
        if (is_string($value)) {
            $source = str_replace('{{' . $key . '}}', $value, $source);
        }
    }
    return $source;
}
