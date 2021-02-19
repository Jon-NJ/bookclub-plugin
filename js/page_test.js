/*
 * JavaScript used for editing dates page.
 * @author     Jon Wolfe <jonnj@connectberlin.de>
 * @package    bookclub
 * @license    https://opensource.org/licenses/MIT MIT
 */
/* global bookclub_ajax_object */

//jQuery('#datepicker').on('click', function(e) {
//    e.preventDefault();
//    console.log('Run date picker');
//    jQuery("#datepicker").datepicker();
//});

jQuery(document).ready(function() {
    console.log('Test page loaded');
    jQuery(document).ready(function() {
        jQuery("#datepicker").datepicker();
    });
});
