<?php namespace bookclub;

/*
 * Class wraps code used to generate the type='test' page.
 * Global instance at the end of the file.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @subpackage shortcodes
 * @license    https://opensource.org/licenses/MIT MIT
 */

/**
 * Description of PageTest
 *
 * @author jonnj
 */
class PageTest extends ShortCode
{
    /**
     * Initialize the object.
     * @return \bookclub\PageTest
     */
    public function __construct()
    {
        // jquery-ui-datepicker
        parent::__construct('bc_test',
            [
                'shorttype' => 'test',
                'script'    => 'page_test.js',
                'style'     => 'page_test.css'
            ]);
    }

    public function render(): string
    {
        parent::enqueue();
        \wp_enqueue_script('jquery-ui-datepicker');
        $json = $this->jsonEMail();
        return twig_render('email_invite', $json);
        //return twig_render('page_test', $json);
    }

    private function jsonEMail(): array
    {
        $event = TableEvents::findByID('20200304_bc_3');
        $person = TableMembers::findByID(1089);

        $json = twig_macro_fields([$person, $event]);
        $json['format']   = 1;
        $json['ical']     = 1;
        return $json;
    }
}

new PageTest();
