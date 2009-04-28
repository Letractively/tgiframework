<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Test configuration
 *
 * @package tgisamples
 * @subpackage testing
 * @copyright 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
return array(
    'readConfig'        => true, // don't keep loading config
    //test variables {{{
    'gld_testGlobal'    => array(
        'construct'         => array('sample_test'),
        'version'           => 1,
        'shouldShard'       => false,
        'isSmemable'        => true,
        'isMemcacheable'    => false,
    ),
    'testConf'          => 'testing',
    // }}}
    'firephp_enable'   => true,
);
?>
