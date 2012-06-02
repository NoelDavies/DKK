<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){ die('Error: Cannot access directly.'); }

/**
 * This class handles the time functionality
 *
 * @version     1.0
 * @since       1.0.0
 * @author      xLink
 */
class time extends coreClass {

    /**
     * Generate a timestamp according to the timezone & DST settings
     *
     * @version    1.0
     * @since   1.0.0
     * @author     xLink
     *
     * @param     int $timestamp
     *
     * @return     int
     */
    public function localTime($timestamp=null, $tz=null){
        $a = func_get_args();
        $timestamp = (is_empty($timestamp) ? time() : $timestamp);

        //if we got DST set then go for it
        if($this->config('time', 'dst') == 1){
            $timestamp = $this->mod_time($timestamp, 0, 0, 1);
        }

        //if $tz is null, then we want to set this from the site setting or user local setting
        if(is_empty($tz)){
            $tz = $this->config('time', 'timezone');
            if(!is_empty($this->objUser->grab('timezone'))){
                $tz = $this->objUser->grab('timezone');
            }
        }

        return $this->mod_time($timestamp, 0, 0, $tz);
    }

    /**
     * Function to convert a timestamp to your date format.
     * inspired from phpbb2
     *
     * @version    1.2
     * @since   1.0.0
     * @author     xLink
     *
     * @param     int     $timestamp    Timestamp to get sorted out
     * @param     string     $format        Format for timestamp - see php.net/date for more info
     * @param     int        $tz            Set timezone
     */
    public function mk_time($timestamp=0, $format='db', $tz=null) {
        //set the time format
        $timeFormat = $this->config('time', 'default_format');
        if($format == 'db'){
            $format = (!is_empty($timeFormat) ? $timeFormat : 'jS F h:ia');
        }

        //grab the timestamp
        $timestamp = $this->localTime($timestamp, $tz);

        //setup the translate array
        $translate = array();
        if(is_empty($translate) && $this->objPage->getVar('language') != 'en') {
            $lang_date = langVar('DATETIME');
            @reset($lang_date);
            while(list($match, $replace) = @each($lang_date)) {
                $translate[$match] = $replace;
            }
        }

        //return all nicely translated
        return (!is_empty($translate)
                    ? strtr(gmdate($format, $timestamp), $translate)
                    : gmdate($format, $timestamp));
    }

    /**
     * Determines a timestamps length
     *
     * @version    1.0
     * @since   1.0.0
     * @author     xLink
     *
     * @param     int $timestamp
     *
     * @return     int
     */
    public function calc_time($timestamp){
        return $this->timer(time()-$timestamp);
    }

    /**
     * Determine how long till next birthday
     *
     * @version    1.0
     * @since   1.0.0
     * @author     xLink
     *
     * @param     int $day        Day of birthday
     * @param     int $month        Month for birthday
     * @param     int $year        Year of Birthday / null sets it to this year or next
     * @param     int $return        Return type check timer() for more info on this one
     *
     * @return     mixed
     */
    public function calc_birthday($day=1, $month=1, $year=null, $return=0){
        //year is empty, then set it to the current year
        if(is_empty($year)){ $year = date('y'); }

        $now = time();
        $time = gmmktime(0, 0, 0, $month, $day, $year);

        //if $now is less than $time then set it to next year
        if($time < $now) {
            $year = $year+1;
            $time = gmmktime(0, 0, 0, $month, $day, $year);
        }

        return $this->timer($now, $time, null, $return);
    }

    /**
     * Modify a timestamp.
     *
     * @version    2.0
     * @since   1.0.0
     * @author     xLink
     *
     * @param     int     $timestamp        Timestamp to work with, defaults to time()
     * @param     int     $seconds        Modify by how many seconds?
     * @param    int     $minutes        "          "       Mins
     * @param     int     $hours            "          "       Hours
     * @param    string     $action            Modify How? 'ADD' / 'DEL'
     *
     * @return     int
     */
    public function mod_time($timestamp=null, $seconds=0, $minutes=0, $hours=0, $action='ADD'){
        //grab the timestamp, or set one if needed
        $time = ($timestamp==0 ? time() : $timestamp);

        //set some vars
        $second = 1; $minute = 60; $hour = 3600;

        //calc some stuffz
        $nSeconds = $second * $seconds;
        $nMinute = $minute * $minutes;
        $nHours = $hour * $hours;

        //return what we need
        return (strtolower($action) == 'add'
                ? ($time + $nSeconds + $nMinute + $nHours)
                : ($time - $nSeconds - $nMinute - $nHours));
    }

    /**
     * Determine the time between 2 timestamps
     *
     * @version    2.0
     * @since   1.0.0
     * @author     xLink
     *
     * @param    int     $olderTimestamp
     * @param     int     $newerTimestamp
     * @param    string     $format
     * @param     int     $return     1 => raw diffrence, int output
     *                                 2 => array output
     *                                 0 => string output
     *
     * @return  mixed
     */
    public function timer($olderTimestamp, $newerTimestamp=null, $format='yfwdhms', $return=0) {
        //if $format is empty, then set it to default
        if(is_empty($format)) { $format = 'yfwdhms'; }

        //if newerTimestamp is empty, set it to time() so we have soemthing to compare from
        if(is_empty($newerTimestamp)){
            $newerTimestamp = time();
        }

        //check to make sure $newer > $older
        $diffrence = abs($newerTimestamp - $olderTimestamp);
        $sign = ($newerTimestamp > $olderTimestamp ? 1 : -1);

        //if return is 1, then they want the diffrence, return here
        if($return == 1) { return $diffrence; }


        //make sure what we have in the format is valid
        $format = array_unique(str_split(preg_replace('`[^yfwdhms]`', '', strtolower($format))));

        //calc numbers
        $conditions = array('y' => 31556926, 'f' => 2629744, 'w' => 604800, 'd' => 86400, 'h' => 3600, 'm' => 60, 's' => 1);

        //set some needed vars
        $out = array();
        $formatCount = count($format);
        $i = 0;

        //loop through the conditions and figure out which are needed
        foreach($conditions as $k => $v){
            //make sure key is valid
            if(!in_array($k, $format)){
                $out[$k] = 0;
                continue;
               }

            //figure out the diffrences
            ++$i;
            if($i != $formatCount){
                $out[$k] = $sign * ($diffrence / $v);
                $diffrence = $diffrence % $v;
            }else{
                $out[$k] = $sign * ($diffrence / $v);
            }
            $out[$k] = (int)$out[$k];
        }

        //return here for an array of the diffrences
        if($return == 2) { return $out; }

        //now setup the string output for $return=0
        $str = array(); $i = 0;
        foreach($out as $k => $v) {
            if($v > 0) {
                $s = ($v > 1 ? 's' : '');
                $k = '$'.$k.$s;
                $str[] = sprintf('%d %s', $v, $k);
            }
        }

        $lastStr = end($str); //grab the last one
        $strCount = count($str); //do a count
        $str = implode(', ', $str); //implode the array so its a string

        //if the array had more than 1 element in it, replace the last implode with the word and
        if($strCount > 1){
            $str = str_replace(', '.$lastStr, ' and '.$lastStr, $str);
        }

        //replace the codes with propper words
        $vals = array('$y', '$f', '$w', '$d', '$h', '$m', '$s');
        $words = array('Year', 'Month', 'Week', 'Day', 'Hour', 'Minute', 'Second');
        $str = str_replace($vals, $words, $str);

        //trim and return
        return trim($str);
    }

    /**
     * Outputs a human readable (facebook-like) time formatted string,
     *  relative to the current time/date
     *
     * @version 1.5
     * @since   1.0
     * @author  Jesus
     *
     * @param     int     $timestamp     Unix Timestamp of time in the past
     *
     * @return     string     $words         Language parsed time-ago string
     */
    public function timeago($timestamp=0) {
        $timestamp = $this->localTime($timestamp);
        $now = $this->localTime(time());

        // Calculate all the times
        $seconds     = $now - $timestamp;
        $minutes     = $seconds / 60;
        $hours         = $minutes / 60;
        $days         = $hours / 24;
        $years         = $days / 365;

        // Load in the conditions for the foreach loop
        $conditions = array(
            array($seconds,    45,  'TIMEAGO_SECONDS'),
            array($seconds,    90,  'TIMEAGO_MINUTE'),
            array($minutes,    45,  'TIMEAGO_MINUTES'),
            array($minutes,    90,  'TIMEAGO_HOUR'),
            array($hours,      24,  'TIMEAGO_HOURS'),
            array($hours,      48,  'TIMEAGO_DAY'),
            array($days,       7,   'TIMEAGO_DAYS'),
            array($days,       14,  'TIMEAGO_WEEK'),
            array(($days/7),   4,   'TIMEAGO_WEEKS'),
            array($days,       60,  'TIMEAGO_MONTH'),
            array(($days/30),  6,   'TIMEAGO_MONTHS'),
            array($days,       365, 'TIMEAGO_YEAR'),
        );

        foreach($conditions as $condition) {
            if($condition[0] < $condition[1]) {
                $words = langVar($condition[2], $condition[0]);
                break;
            }
        }

        if(is_empty($words)) {
            $words = langVar('TIMEAGO_YEARS', $years);
        }

        return $words . langVar('TIMEAGO_SUFFIXAGO');
    }

}
?>