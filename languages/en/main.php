<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){ die('Error: Cannot access directly.'); }
if(!isset($LANG_LOAD)){ die('Error: Cannot access directly.'); }

//breadcrumbs
$_lang['B_MAINSITE']            = 'Site Home';
$_lang['B_REGISTER']            = 'Register';

$_lang['B_UCP']                 = 'User Control Panel';
$_lang['B_ACP']                 = 'Admin Control Panel';

//header lang vars
$_lang['L_MAINTENANCE']         = 'This website is in maintenance mode.';
$_lang['L_BREADCRUMB']          = 'You are here:';

$_lang['L_LOGIN']               = 'Login';
$_lang['L_LOGOUT']              = 'Logout';
$_lang['L_UCP']                 = 'User Control Panel';
$_lang['L_ACP']                 = 'Admin Control Panel';

//footer lang vars
$_lang['L_PAGE_GEN']            = '[ Queries: <b>%s</b> | SQL Time: <b>%s</b> | Page Generation Time: <b>%s</b> | Memory Usage: <b>%s</b> | Next Cron Update: <b>%s</b> ]';
$_lang['L_SITE_COPYRIGHT']      = '%s Powered By %s V%s';
$_lang['TPL_INFO']              = 'Theme %s by %s V%s';

//debug lang vars
$_lang['MSG_INFO']              = 'Information';
$_lang['MSG_FAIL']              = 'Failed';
$_lang['MSG_ERR']               = 'Error';
$_lang['MSG_OK']                = 'Successful';

$_lang['MSG_NO_PIN']            = 'Your PIN is not set. You cannot login to the admin control panel at this time.<br />Please make sure you set one in your User Control Panel.';
$_lang['L_CAPTCHA_DESC']        = 'This question is for testing whether you are a human visitor and to prevent automated spam submissions.';

$_lang['L_REG_SUCCESS_EMAIL']   = 'Your registration has been successful. The administrator has asked that you validate your email before being allowed to login. Please do this now, if you have no email in your inbox, check your junk & spam boxes. Otherwise wait a while and then contact the administrator.';
$_lang['L_REG_SUCCESS_NO_EMAIL']= 'Your registration has been successful. You may now login with your username and password.';

//forms
$_lang['L_USERNAME']            = 'Username';
$_lang['L_PIN']                 = 'PIN';
$_lang['L_PIN_DESC']            = 'This is the PIN you set yourself in the user control panel. If you haven\'t set a PIN as yet, please do so.';
$_lang['L_PASSWORD']            = 'Password';
$_lang['L_REMBER_ME']           = 'Remember Me';

//log stuff
$_lang['LOG_CREATED_USER']      = 'We\'ve done it! <a href="%s">%s</a>, he..he lives! Giovanni will be impressed at the power this creature wields...';

//menu titles
$_lang['M_MAIN_MENU']           = 'Main Menu';
$_lang['M_LOGIN']               = 'Login';
$_lang['M_USER_MENU']           = 'User Menu';
$_lang['M_WIO']                 = 'Who Is Online';
$_lang['M_ANNOUNCEMENT']        = 'Announcement';
$_lang['M_STATS']               = 'CMS Statistics';
$_lang['M_AFFILIATES']          = 'Affiliates';
$_lang['M_UMOD']                = 'User Moderation';

//menu blocks
$_lang['L_INVALID_FUNCTION']    = '<b>Fatal Error</b>: Function <i>%s</i> was not found.';

//who is online block
$_lang['L_KEY']                 = 'User Key:';
$_lang['L_USERS_ONLINE']        = 'Users Online:';
$_lang['L_NO_ONLINE_USERS']     = 'There are currently no users online.';

//logon block
$_lang['L_LAST_VISIT']          = 'Last Visit: %s';
$_lang['L_REMME']               = 'Remember Me';

//other stuff
$_lang['L_PER_DAY']             = '<strong>Per Day</strong>: %d';
$_lang['L_EDIT']                = 'Edit';
$_lang['L_DELETE']              = 'Delete';
$_lang['L_COMMENTS']            = 'Comments: %s';

//control panels
require_once('panels.php');

//Time Stuff
$_lang['DATETIME']['Sunday']    = 'Sunday';
$_lang['DATETIME']['Monday']    = 'Monday';
$_lang['DATETIME']['Tuesday']   = 'Tuesday';
$_lang['DATETIME']['Wednesday'] = 'Wednesday';
$_lang['DATETIME']['Thursday']  = 'Thursday';
$_lang['DATETIME']['Friday']    = 'Friday';
$_lang['DATETIME']['Saturday']  = 'Saturday';
$_lang['DATETIME']['Sun']       = 'Sun';
$_lang['DATETIME']['Mon']       = 'Mon';
$_lang['DATETIME']['Tue']       = 'Tue';
$_lang['DATETIME']['Wed']       = 'Wed';
$_lang['DATETIME']['Thu']       = 'Thu';
$_lang['DATETIME']['Fri']       = 'Fri';
$_lang['DATETIME']['Sat']       = 'Sat';
$_lang['DATETIME']['January']   = 'January';
$_lang['DATETIME']['February']  = 'February';
$_lang['DATETIME']['March']     = 'March';
$_lang['DATETIME']['April']     = 'April';
$_lang['DATETIME']['May']       = 'May';
$_lang['DATETIME']['June']      = 'June';
$_lang['DATETIME']['July']      = 'July';
$_lang['DATETIME']['August']    = 'August';
$_lang['DATETIME']['September'] = 'September';
$_lang['DATETIME']['October']   = 'October';
$_lang['DATETIME']['November']  = 'November';
$_lang['DATETIME']['December']  = 'December';
$_lang['DATETIME']['Jan']       = 'Jan';
$_lang['DATETIME']['Feb']       = 'Feb';
$_lang['DATETIME']['Mar']       = 'Mar';
$_lang['DATETIME']['Apr']       = 'Apr';
$_lang['DATETIME']['May']       = 'May';
$_lang['DATETIME']['Jun']       = 'Jun';
$_lang['DATETIME']['Jul']       = 'Jul';
$_lang['DATETIME']['Aug']       = 'Aug';
$_lang['DATETIME']['Sep']       = 'Sep';
$_lang['DATETIME']['Oct']       = 'Oct';
$_lang['DATETIME']['Nov']       = 'Nov';
$_lang['DATETIME']['Dec']       = 'Dec';
$_lang['DATETIME']['am']        = 'am';
$_lang['DATETIME']['pm']        = 'pm';

// Time Class Language Vars
$_lang['TIMEAGO_SUFFIXAGO']     = ' ago';
$_lang['TIMEAGO_SUFFIXFROMNOW'] = 'from now';
$_lang['TIMEAGO_SECONDS']       = 'less than a minute';
$_lang['TIMEAGO_MINUTE']        = 'about a minute';
$_lang['TIMEAGO_MINUTES']       = '%d minutes';
$_lang['TIMEAGO_HOUR']          = 'about an hour';
$_lang['TIMEAGO_HOURS']         = 'about %d hours';
$_lang['TIMEAGO_DAY']           = 'a day';
$_lang['TIMEAGO_DAYS']          = '%d days';
$_lang['TIMEAGO_WEEK']          = 'a week';
$_lang['TIMEAGO_WEEKS']         = '%d weeks';
$_lang['TIMEAGO_MONTH']         = 'about a month';
$_lang['TIMEAGO_MONTHS']        = '%d months';
$_lang['TIMEAGO_YEAR']          = 'about a year';
$_lang['TIMEAGO_YEARS']         = '%d years';