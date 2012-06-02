<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){die('Error: Cannot access directly.');}

//
//--Before we begin, lets define some stuff up
//
    $START_CMS_LOAD = microtime(true); $START_RAM_USE = memory_get_usage();
    //Lets set a simple error template up till we have the template engine going
    $errorTPL = '<h3>%s</h3> <p>%s Killing Process...</p>';
    @set_magic_quotes_runtime(false);

    //if we havent a session, lets start one up
    if(!isset($_SESSION)){ session_start(); }

    //setup a few things, these are kept out of the constants.php file cause we need em before thats included.
    define('cmsVERSION', '1.0.0');
    if(!defined('cmsDEBUG')){
        define('cmsDEBUG', false);
    }

    /**
     * cmsROOT - Internal way of getting to the project root
     * @note for internal use, use cmsROOT, for external use, eg js and html paths, use root();
     */
    define('cmsROOT', (isset($cmsROOT) && !empty($cmsROOT) ? $cmsROOT : null)); unset($cmsROOT);

    //so we can turn errors off if we are not running locally
    define('LOCALHOST', (isset($_SERVER['HTTP_HOST']) &&
                            ($_SERVER['HTTP_HOST']=='localhost' ||
                             $_SERVER['HTTP_HOST']=='127.0.0.1'))
                        ? true
                        : false);

    //define the error reporting level, dont want PHP errors on the live version now do we :)
    error_reporting(LOCALHOST ? E_ALL & ~E_NOTICE | E_STRICT : 0);
//
//--Include the core CMS files needed
//
    //Kill the killSwitch
    $file = cmsROOT.'killCMS';
    if(is_writable($file)){ unlink($file); }

    //The config file
    $file = cmsROOT.'cache/config.php';
    if(is_file($file) && file_get_contents($file)!='' && !isset($_SESSION['allow_config'])){
        die(sprintf($errorTPL, 'Fatal Error', 'CMS has already been installed. Cannot run installer.'));
    }

    //we need constants.php, same deal as above
    $file = cmsROOT.'core/constants.php';
    if(!is_readable($file)){
        die(sprintf($errorTPL, 'Fatal Error - 404', 'We have been unable to locate/read the constants file.'));
    }else{ require_once($file); }

    //make sure we are running a compatible PHP Version
    if(PHP_VERSION_ID < '50300'){
        die(sprintf($errorTPL, 'Fatal Error - 500',
            'This server is not capable of running this CMS, please upgrade PHP to version 5.3+ before trying to continue.'));
    }

    $redoHandler = false;
    $file = cmsROOT.'core/debugFunctions.php';
    if(!is_readable($file)){
        function dump(){} function getExecInfo(){} function memoryUsage(){}
    }else{ $redoHandler = true; require_once($file); }

    $file = cmsROOT.'core/baseFunctions.php';
    if(!is_readable($file)){
        die(sprintf($errorTPL, 'Fatal Error - 404', 'We have been unable to locate/read the baseFunctions file.'));
    }else{ require_once($file); }

    //kill magic quotes completely
    if(@get_magic_quotes_gpc()){
        //strip all the global arrays
        recursiveArray($_POST,      'stripslashes');
        recursiveArray($_GET,       'stripslashes');
        recursiveArray($_COOKIE,    'stripslashes');
        recursiveArray($_REQUEST,   'stripslashes');
    }

    (LOCALHOST ? set_error_handler('cmsError') : '');
    if($redoHandler && cmsDEBUG == true){
        set_error_handler('error_handler');
        set_exception_handler('exception_handler');
        register_shutdown_function('fatal_error_handler');
    }unset($redoHandler);

    //set the default timezone
    if(function_exists('date_default_timezone_set')){
        date_default_timezone_set('Europe/London'); //ive set it to London, as i use the GMdate functions
    }

//
//--Classes Setup
//
    $classDir = cmsROOT.'core/classes/';
    $libDir = cmsROOT.'core/lib/';
    $classes = array();

    //load in outside classes
    $classFiles = array(
        $classDir.'base.core.php', #all CMS classes extend this one
        $classDir.'base.sql.php', #this is the SQL template
        $classDir.'driver.mysql.php', #this is the SQL driver

        $libDir.'phpass/class.phpass.php',
        $libDir.'geshi/class.geshi.php',
        $libDir.'nbbc/class.nbbc.php'
    );

        foreach($classFiles as $file){
            $file = $file;
            if(!is_file($file) || !is_readable($file)){
                msgDie('FAIL', sprintf($errorTPL, 'Fatal Error - 404', 'We have been unable to locate/read the '.$file.' file.'));
            }else{ require_once($file); }
        }

    $objCore = new coreClass;

    //cache setup
    $cachePath = cmsROOT.'cache/';
    if(is_dir($cachePath) && !is_writable($cachePath)){ @chmod($cachePath, 0775); }
    if(!is_writable($cachePath)){
        msgDie('FAIL', sprintf($errorTPL, 'Fatal Error', 'Could not set CHMOD permissions on "<i>cache/</i>" set to 775 to continue.'));
    }

    $cacheWritable = (is_writable($cachePath) ? true : false);

//
//--Define new instances of the included classes
//
    $classes['objCache']        = array($classDir.'class.cache.php', array(
                                    'useCache'     => $cacheWritable,
                                    'cacheDir'     => $cachePath
                                ));
    $classes['objLogin']        = array($classDir.'class.login.php');
    $classes['objUser']         = array($classDir.'class.user.php');

    //plugins have been moved here so we can hook into the init of stage 2 classes
    //and possibly load some custom ones such as reCaptcha etc ;)
    $classes['objPlugins']      = array($classDir.'class.plugins.php');

    //init the sql and cache classes, we need these before we can go any further
    $doneSetup = $objCore->setup($classes);
    if(!$doneSetup){
        msgDie('FAIL', sprintf($errorTPL, 'Fatal Error',
            'Cannot load CMS Classes, make sure file structure is intact and $cmsROOT is defined properly if applicable.'));
    }
    unset($classes, $doneSetup);

//
//--Language Setup
//
    //grab the default language info, and test to see if user has a request
    $language = doArgs('language', 'en', $config['site']);
    $langDir = cmsROOT.'languages/';
    if(isset($_SESSION['user']['language'])){
        if(is_dir($langDir.$_SESSION['user']['language'].'/') &&
           is_readable($langDir.$_SESSION['user']['language'].'/main.php')){
                $language = $_SESSION['user']['language'];
        }
    }

    if(is_dir($langDir.$language.'/') || is_readable($langDir.$language.'/main.php')){
        translateFile($langDir.$language.'/main.php');
    }else{
        msgDie('FAIL', sprintf($errorTPL, 'Fatal Error',
            'Cannot open '.($langDir.$language.'/main.php').' for include.'));
    }

//
//-- and now load the rest of the classes
//
    $classes['objTPL']          = array($classDir.'class.template.php', array(
                                    'root'      => '.',
                                    'useCache'  => $cacheWritable,
                                    'cacheDir'  => $cachePath.'template/'
                                ));

    $classes['objPage']         = array($classDir.'class.page.php');
    $classes['objGroups']       = array($classDir.'class.groups.php');
    $classes['objForm']         = array($classDir.'class.form.php');
    $classes['objTime']         = array($classDir.'class.time.php');
    $classes['objNotify']       = array($classDir.'class.notify.php');

    //init these classes
    $doneSetup = $objCore->setup($classes);
    if(!$doneSetup){
        msgDie('FAIL', sprintf($errorTPL, 'Fatal Error',
            'Cannot load CMS Classes, make sure file structure is intact and $cmsROOT is defined properly if applicable.'));
    }

    //globalise the class names
    foreach($objCore->classes as $objName => $args){ $$objName =& $objCore->$objName; }
    $objSQL = false;
    unset($classes, $objCore->classes);

    $objPage->setVar('language', $language);

//
//--BBCode Setup
//
    $objBBCode = new BBCode;
    $objBBCode->SetDebug(true);
    $objBBCode->SetDetectURLs(false);
    $objBBCode->ClearSmileys();
    $objBBCode->SetSmileyDir('/'.root().'images/smilies/');
    $file = cmsROOT.'core/bbcode_tags.php';
    if(is_readable($file)){
        require_once($file);
    }else{ hmsgDie('FAIL', 'Fatal Error - BBCode\'s not available.'); }

?>