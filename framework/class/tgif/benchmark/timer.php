<?php
// vim:set expandtab tabstop=4 shiftwidth=4 softtabstop=4 foldmethod=marker syntax=php:
/**
 * Container for {@link tgif_benchmark_timer}
 *
 * @package tgiframework
 * @subpackage debugging
 * @copyright 2009 terry chay
 * @license GNU Lesser General Public License <http://www.gnu.org/licenses/lgpl.html>
 */
// {{{ tgif_benchmark_timer
// docs {{{
/**
 * Similar to PEAR {@link Benchmark_Timer}, except without the userspace
 * profiling and the named laptiming facilities.
 *
 * Unlike Benchmark_Timer, this can also use {@link getrusage()} to get real
 * system ticks on the process.
 *
 * @package tgiframework
 * @subpackage debugging
 * @author terry chay <tychay@php.net> refactored from {@link tgif_diagnostics}
 */
// }}}
class tgif_benchmark_timer
{
    // PRIVATE INTERNALS
    // {{{ - $_trackRusage
    /**
     * If set to true, this will track resource usage via
     * {@link http://php.net/getrusage getrusage()}
     * @var boolean
     */
    private $_trackRusage;
    // }}}
    // {{{ - $_startTime
    /**
     * Stores the start time in microtime(false) format.
     * @var string
     */
    private $_startTime;
    // }}}
    // {{{ - $_stopTime
    /**
     * Stores the stop time in microtime(false) format.
     * @var string
     */
    private $_stopTime;
    // }}}
    // {{{ - $_startRusage
    /**
     * Stores the rusage stats at the start of the timer
     * @var array
     */
    private $_startRusage;
    // }}}
    // {{{ - $_stopRusage
    /**
     * Stores the rusage stats at the end of the timer
     * @var array
     */
    private $_stopRusage;
    // }}}
    // {{{ - $_summary
    /**
     * Stores the time differences and the like
     * @var array
     */
    private $_summary = array();
    // }}}
    // RESERVED METHODS
    // {{{- __construct($shouldStart[$trackRSuage])
    /**
     * Constructor for object.
     *
     * If you want trackRusage to work in windows, install cygwin.
     *
     * @param boolean $shouldStart if true then it will start the timer
     * @param boolean $trackRusage if true then it will track the resource usage
     * also
     * automatically
     */
    public function __construct($shouldStart,$trackRusage=false)
    {
        $this->_trackRusage = $trackRusage;
        if ($shouldStart) { $this->start(); }
    }
    // }}}
    // TIMERS
    // {{{ - start()
    /**
     * Start the timer.
     */
    function start()
    {
        $this->_startTime = microtime();
        if ($this->_trackRusage) {
            $this->_startRusage = getrusage();
        }
    }
    // }}}
    // {{{ - stop([$sumTotal])
    /**
     * Stops the timer.
     *
     * Moved the math function around the timers to make the timers slightly
     * more accurate. Also improved the adding function to not lose precision.
     *
     * @param boolean $sumTotal should summary keep a running total?
     */
    function stop($sumTotal=false)
    {
        // stop timers {{{
        if ($this->_trackRusage) {
            $this->_stopRusage = getrusage();
        }
        $this->_stopTime = microtime();
        // }}}
        // compute time taken {{{
        $time = self::microtime_subtract($this->_stopTime, $this->_startTime);
        if ($this->_trackRusage) {
            $utime = self::microtime_subtract(self::rusage_to_microtime($this->_stopRusage,'ru_utime.tv'),self::rusage_to_microtime($this->_startRusage,'ru_utime.tv'));
            $stime = self::microtime_subtract(self::rusage_to_microtime($this->_stopRusage,'ru_stime.tv'),self::rusage_to_microtime($this->_startRusage,'ru_stime.tv'));
            $rtime = self::bc_add($utime,$stime);
        }
        // }}}
        // add to total {{{
        if (!$sumTotal && !$this->_trackRusage) {
            $this->_summary = array('time' => $time);
        } elseif (!$sumTotal) { //rusage
            $this->_summary = array(
                'time'  => $time,
                'utime' => $utime,
                'stime' => $stime,
                'rtime' => $rtime,
            );
        } else {
            // zero out array {{{
            if (empty($this->_summary) && !$this->_trackRusage) {
                $this->_summary = array('time' => '0.0');
            } else {
                $this->_summary = array(
                    'time'  => '0.0',
                    'utime' => '0.0',
                    'stime' => '0.0',
                    'rtime' => '0.0',
                );
            }
            // }}}
            $this->_summary['time'] = self::bc_add($this->_summary['time'],$time);
            if ($this->_trackRusage) {
                $this->_summary['utime'] = self::bc_add($this->_summary['utime'],$utime);
                $this->_summary['stime'] = self::bc_add($this->_summary['stime'],$stime);
                $this->_summary['rtime'] = self::bc_add($this->_summary['rtime'],$rtime);
            }
        }
        // }}}
    }
    // }}}
    // ACCESSORS
    // {{{ - __get($name)
    /**
     * Allow you to get values
     * @return mixed if false you didn't record that value
     */
    function __get($name)
    {
        switch (strtolower($name)) {
            case 'starttime':
            case 'begintime':
            return self::microtime_float($this->_startTime);
            case 'stoptime':
            case 'endtime':
            return self::microtime_float($this->_stopTime);
            case 'timetaken':
            case 'timedifference':
            return $this->_summary['time'];
            case 'rtimetaken':
            return $this->_summary['rtime'];
            case 'stimetaken':
            return $this->_summary['stime'];
            case 'utimetaken':
            return $this->_summary['utime'];
        }
        trigger_error(sprintf('Unknown property %s',$name), E_USER_WARNING);
    }
    // }}}
    // {{{ - __set($name,$value)
    /**
     * Allow you to change the values
     */
    function __set($name, $value)
    {
        switch (strtolower($name)) {
            case 'starttime':
            case 'begintime':
            $this->_startTime = $value;
            return;
            case 'startrusage':
            case 'beginusage':
            $this->_startRusage = $value;
            return;
        }
        trigger_error(sprintf('Unknown property %s',$name), E_USER_WARNING);
    }
    // }}}
    // STATIC METHODS
    // {{{ + rusage_to_microtime($rusageArray,$indexName[,$asFloat])
    /**
     * Turns rusage time results into microtime format
     *
     * @param array $rusageArray the Rusage time
     * @param string $indexName e.g. 'ru_utime.tv'
     * @param boolean $asFloat If true, it ends up looking like the time was
     * passed through {@link microtime_float()}.
     * @return string the time in microtime format
     * float)
     */
    static function rusage_to_microtime($rusageArray, $indexName, $asFloat=false)
    {
        if ($asFloat) {
            return $rusageArray[$indexName.'_sec'] . '.' . $rusageArray[$indexName.'_usec'];
        } else {
            return '0.'.$rusageArray[$indexName.'_usec'] . ' ' . $rusageArray[$indexName.'_sec'];
        }
    }
    // }}}
    // {{{ + bc_add($time1,$time2)
    /**
     * Add two precision strings
     *
     * @param string $time1 addend in bc format
     * @param string $time2 addend in bc format
     * @return string the sum of times seconds (can be interpreted as a
     * float)
     */
    static function bc_add($time1,$time2)
    {
        // use bc math to do subtraction if available {{{
        if (extension_loaded('bcmath')) {
            return bcadd($time1, $time2, 6);
        }
        // }}}
        if (!$time1) { $time1 = '0.0'; }
        if (!$time2) { $time2 = '0.0'; }
        if (!strpos($time1,'.')) { $time1 .= '.0'; }
        if (!strpos($time2,'.')) { $time2 .= '.0'; }
        list($end_usec, $end_sec) = explode('.', $time1);
        list($start_usec, $start_sec) = explode('.',$time2);
        $usec = ('0.'.$end_usec) + ('0.'+$start_usec);
        $carry = ($usec > 1)  ? 1 : 0;
        $sec = $end_sec + $start_sec + $carry;
        return $sec.substr($usec,1);
    }
    // }}}
    // {{{ + bc_sub($time1,$time2)
    /**
     * Subtracts two precision strings
     *
     * Enhanced to use bcmath if available.
     *
     * @param string $time1 end time in bc format
     * @param string $time2 start time in bc format
     * @return string the time difference in seconds (can be interpreted as a
     * float)
     */
    static function bc_sub($time1,$time2)
    {
        // use bc math to do subtraction if available {{{
        if (extension_loaded('bcmath')) {
            return bcsub($time1, $time2, 6);
        }
        // }}}
        if (!$time1) { $time1 = '0.0'; }
        if (!$time2) { $time2 = '0.0'; }
        if (!strpos($time1,'.')) { $time1 .= '.0'; }
        if (!strpos($time2,'.')) { $time2 .= '.0'; }
        list($end_usec, $end_sec) = explode('.', $time1);
        list($start_usec, $start_sec) = explode('.',$time2);
        $usec = ('0.'.$end_usec) - ('0.'.$start_usec);
        $carry = 0;
        if ($usec < 0) {
            $usec = 1 + $usec;
            $carry = 1;
        }
        $sec = $end_sec - $start_sec - $carry;
        //return $sec.substr($usec,1).' = '.$time1.' - '.$time2;
        return $sec.substr($usec,1);
    }
    // }}}
    // {{{ + microtime_subtract($time1,$time2)
    /**
     * Subtracts two microtime strings
     *
     * Enhanced to use bcmath if available.
     *
     * @param string $time1 end time in microtime format
     * @param string $time2 start time in microtime format
     * @return string the time difference in seconds (can be interpreted as a
     * float)
     */
    static function microtime_subtract($time1,$time2)
    {
        // use bc math to do subtraction if available {{{
        if (extension_loaded('bcmath')) {
            return bcsub(self::microtime_float($time1), self::microtime_float($time2), 6);
        }
        // }}}
        list($end_usec, $end_sec) = explode(' ', $time1);
        list($start_usec, $start_sec) = explode(' ',$time2);
        $usec = $end_usec - $start_usec;
        if ($usec == 0) { $usec = '0.0'; } //edge case: zero seconds (microtime not really a microtime)
        $carry = 0;
        if ($usec < 0) {
            $usec = 1 + $usec;
            $carry = 1;
        }
        $sec = $end_sec - $start_sec - $carry;
        //return $sec.substr($usec,1).' = '.$time1.' - '.$time2;
        return $sec.substr($usec,1);
    }
    // }}}
    // {{{ + microtime_float([$microtime])
    /**
     * Emulates microtime(true) for PHP < 4
     * @param $microtime string the time to convert. If false, then it will
     *      assume you want this instant.
     * @return string a GMP formatted microtime string
     */
    static function microtime_float($microtime=false)
    {
        if ($microtime === false) {
            $microtime = microtime();
        }
        list($usec, $sec) = explode(' ', $microtime);
        return $sec.substr($usec,1);
    }
    // }}}
}
// }}}
?>
