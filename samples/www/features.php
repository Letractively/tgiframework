<html><head><title>Some examples of framework features</title>
<body><?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
//345678901234567890123456789012345678901234567890123456789012345678901234567890
/**
 * Overview of various features in the site.
 *
 * Test global config and variables
 *
 * @package tgisamples
 * @subpackage testing
 * @copyright 2009 terry chay <tychay@php.net>
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 * @author terry chay <tychay@php.net>
 */
?>
<h1>Features test page</h1>
<p>This contains a list of various framework features and what they look like to the developer.</p>

<h2>Errors</h2>
<p>Trigger a user error for testing purposes.</p>
<?php
trigger_error('Testing user error');
?>

<h2>Runkit</h2>
<p>Test whether Runkit superglobals are working.</p>
<p>Expect:</p>
<ul>
<li>_TAG->foo is bar</li>
<li>foo is (null) [+ undefined error]</li>
</ul>
<p>Got:</p>
<?php
/**
 * Just a dummy test function to test superglobals
 */
function test_runkit()
{
?>
<ul>
<li>'_TAG->foo' is <?php var_dump($_TAG->foo); ?></li>
<li>'foo' global is <?php var_dump($foo); ?></li>
</ul>
<?php
}
$_TAG->foo = 'bar';
$foo = 'bar';
test_runkit();
?>

<h2>Configuration & Globals</h2>
<p>Make sure $_TAG global system works and configuration system is working and properly instantiated.</p>
<ul>
<li>Cache configuration? <?php var_dump($_TAG->config('readConfig')); ?></li>
<li>Files read for configuration: <?php var_dump($_TAG->config('configFiles')); ?></li>
<li>'gld_firephp' global config is <?php var_dump($_TAG->config('gld_firephp')); ?></li>
<li>'testConf' local config is <?php var_dump($_TAG->config('testConf')); ?></li>
<li>'testConfMacros' local config is <?php var_dump($_TAG->config('testConfMacros')); ?></li>
<li>'firephp' global (test subconfig override) <?php var_dump($_TAG->config('firephp')); ?></li>
<li>'testGlobal' global is <?php var_dump($_TAG->testGlobal); ?></li>
<li>'testCollection' of foo:<?php var_dump($_TAG->testCollection['foo']); ?></li>
<li>'testCollection' of bar:<?php var_dump($_TAG->testCollection['bar']); ?></li>
</ul>

<h2>FirePHP debugging</h2>
<p>You need Firefox with firebug and firephp installed and enabled. If you have this then open up console to see a variable called "Iterators" sent to you.</p>
<?php
// No longer needed (see config.php)
//require_once('FirePHPCore/FirePHP.class.php');
//ob_start();
//echo 'This is a test of FirePHP';
//$firephp = FIREPHP::getInstance(true);
$var = array('i'=>10, 'j'=>20);
//$firephp->log($var, 'Iterators');
$_TAG->firephp->log($var, 'Iterators');
?>
<h2>Memcache</h2>
<?php
if ($foo = $_TAG->memcached->get('foo')) {
    echo 'foo=';
    var_dump($foo);
} else {
    $_TAG->memcached->set('foo','bar');
    echo 'Reload to see foo=bar';
}

?>
<h2>Benchmarking</h2>
<?php
// {{{ gen_guid_data()
/**
 * Generator to generate test data similar to guid in {@link tgif_diagnostics}.
 * @return string
 */
function gen_guid_data() {
    global $pid, $server;
    return uniqid(rand(),true).$server.$pid;
}
// }}}
// add a comment start here to bypass benchmarking
///*
// date test {{{
//$error_level = error_reporting(E_ALL | E_STRICT);
//ini_set('date.timezone',false);
//echo date('c');

//mimic production
$error_level = error_reporting(0);
// {{{ date()
ini_set('date.timezone',false);
$b1 = new tgif_benchmark_iterate(true);
//$b1->startStop = true;
$b1->run(10000, 'date', 'c');
$b1->description = 'date("c")';
// }}}
//echo var_dump($b1->summary);die;
// {{{ date() + date.timezone
ini_set('date.timezone','America/Los_Angeles');
$b2 = new tgif_benchmark_iterate(true);
//$b2->startStop = true;
$b2->run(10000, 'date', 'c');
$b2->description = 'date("c") + date.timezone';
// }}}
// {{{ iterate date() + ini_set
//date_default_timezone_set('');
ini_set('date.timezone',false);
function ini_and_date() {
    ini_set('date.timezone','America/Los_Angeles');
    date('c');
}
$b4 = new tgif_benchmark_iterate(true);
//$b4->startStop = true;
$b4->run(10000, 'ini_and_date');
$b4->description = 'iterate date("c") + ini_set';
// }}}
// {{{ date() + date_default_timezone_set
ini_set('date.timezone',false);
date_default_timezone_set('America/Los_Angeles');
$b3 = new tgif_benchmark_iterate(true);
//$b3->startStop = true;
$b3->run(10000, 'date', 'c');
$b3->description = 'date("c") + date_default_timezone_set()';
// }}}
// {{{ iterate date() + date_default_timezone_set
//date_default_timezone_set('');
ini_set('date.timezone',false);
function set_and_date() {
    date_default_timezone_set('America/Los_Angeles');
    date('c');
}
$b5 = new tgif_benchmark_iterate(true);
//$b5->startStop = true;
$b5->run(10000, 'set_and_date');
$b5->description = 'iterate date("c") + date_default_timezone_set';
// }}}
echo tgif_benchmark_iterate::format($b1->compare($b2,$b3,$b4,$b5)),"\n";
// restore
error_reporting($error_level);
// }}}
// internal time vs server time
// {{{ date() on internal time
$b1 = new tgif_benchmark_iterate(true);
//$b1->startStop = true;
$b1->run(10000, 'date', 'c');
$b1->description = 'date("c") internal time';
// }}}
// {{{ date() on server time
$b2 = new tgif_benchmark_iterate(true);
//$b2->startStop = true;
$b2->run(10000, 'date', 'c', $_SERVER['REQUEST_TIME']);
$b2->description = 'date("c") server time';
// }}}
echo tgif_benchmark_iterate::format($b1->compare($b2)),"\n";
// }}}
// hash test {{{
// emulate diagnostic guid hash
$pid    = getmypid();
$server = (isset($_SERVER['SERVER_ADDR'])) ? $_SERVER['SERVER_ADDR'] : php_uname('n');
// md5 {{{
$b1 = new tgif_benchmark_iterate(true);
//$b1->startStop = true; //(so small get division by 0)
$b1->runGenerator(1000, 'md5', 'gen_guid_data');
// }}}
//echo var_dump($b1->summary);die;
// crc32 {{{
$b2 = new tgif_benchmark_iterate(true);
//$b2->startStop = true; //(so small get division by 0)
$b2->runGenerator(1000, 'crc32', 'gen_guid_data');
// }}}
// md5 {{{
$b3 = new tgif_benchmark_iterate(true);
//$b3->startStop = true; //(so small get division by 0)
$b3->runGenerator(1000, 'sha1', 'gen_guid_data');
// }}}
echo tgif_benchmark_iterate::format($b1->compare($b2,$b3));
/* */
// }}}
?>
<h2>Diagnostics</h2>
<?php
echo $_TAG->diagnostics->summary();
$data = $_TAG->diagnostics->summary('data');
var_dump($data,$data['times']['memcache']);
echo $_TAG->diagnostics->summary();
?>
</body>
</html>
