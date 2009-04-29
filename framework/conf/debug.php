<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Configuration for debugging related stuff
 *
 * @package tgiframework
 * @subpackage debugging
 * @copyright 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
return array(
// {{{ readConfig: set to true to cache configuration (instead of reparse)
'readConfig'    => false,
// }}}
// {{{ $_TAG->firephp
'gld_firephp' => array(
    'construct'         => array('tgif_debug_firephp','_x_get_instance'),
    'version'           => 1,
    'shouldShard'       => false,
    'isSmemable'        => false,
    'isMemcacheable'    => false,
),
// }}}
// {{{ firephp_enable: set to true to turn on firephp debugging
'firephp_enable'    => false,
// }}}
);
?>