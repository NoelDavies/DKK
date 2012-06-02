<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){ die('Error: Cannot access directly.'); }

/**
 * Group Class designed to allow easier access to expand on the group system implemented
 *
 * @version 1.0
 * @since   1.0.0
 * @author  xLink
 */
class coreClass{

    public $classes = array();

    /**
     * Autoloads the $classes
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   bool  $classes
     */
    final public function setup($classes=false){
        if(!is_array($classes)){ return false; }

        //loop through $classes for loading purposes
        foreach($classes as $var => $path){
            //make sure the file is there
            if(file_exists($path[0]) && is_readable($path[0])){
                //require the file
                include_once($path[0]);

                //explode the filename, so we dont get interference pre-filename
                $fileName = explode('/', $path[0]);

                //grab the class name from the file and make sure it exists before continuing
                $fileName = explode('.', end($fileName)); $class = $fileName[1];
                    if($fileName[0] == 'driver'){ $class = 'driver_'.$class; }
                    if(!class_exists($class)){ continue; }

                //set the class to new var and continue
                if(!isset($path[1])){ $path[1] = array(); }
                $this->$var = new $class($path[1]);
                $this->classes[$var] = $this->$var;
            }else{
                die('Error: Couldn\'t load '.$var.'; File not found.');
            }
        }

        if(is_empty($this->classes)){
            $this->setError('No Classes Defined');
            return false;
        }

        //loop through the classes after they have been all init'd
        foreach($this->classes as $objName => $args){
            //loop through the list again
            foreach($this->classes as $class => $args){
                //if this one is == parent, skip it..
                if($objName == $class){ continue; }

                //assign $class, to the parent $objName so all classes can see eachother
                $this->$objName->setVar($class, $this->$class);
            }
        }
        return true;
    }

    /**
     * Sets a variable with a value
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   string  $var
     * @param   mixed   $value
     */
    public function setVar($var, $value){
        $this->$var = $value;
    }

    /**
     * Sets multiple variables with values
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   array $array
     */
    public function setVars($array){
        if(!is_array($array)){ return false; }

        foreach($array as $k => $v){
            $this->$k = $v;
        }
        return true;
    }

    /**
     * Returns a var's value
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   string  $var
     *
     * @return  mixed
     */
    public function getVar($var){
        return (isset($this->$var) ? $this->$var : false);
    }


    /**
     * Returns a config variable
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   string  $array
     * @param   string  $setting
     * @param   mixed   $default
     *
     * @return  mixed
     */
    public function config($array, $setting, $default=null){
        global $config;

        return doArgs($setting, $default, $config[$array]);
    }

    /**
     * Returns the last error set.
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @return  string
     */
    public function error(){
        return $this->_error;
    }

    /**
     * Allows for an error to be set just before returning false
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   string $msg
     */
    public function setError($msg){
        $this->_error = (string)$msg;
    }

    /**
     * Returns the name of the class this var an instance of
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @return  string
     */
    public function name(){
        return get_class($this);
    }

    /**
     * Put together a list query list for the url
     *
     * @version 1.0
     * @since   1.0.0
     *
     * @param   string  $url        The url to be tested
     * @param   array   $vars       An array to be added to the string
     * @param   array   $ignore     An array of values to ignore
     */
    public function getQueryString($url, $vars=array(), $ignore=array()){
        //ensure we have something in $vars
        $query_string = array();
        if(!is_array($vars) || !count($vars)){
            return false;
        }

        //explode the $url and grab anything after the ?
        $url = explode('?', $url);
        parse_str($url[1], $urlVars);
        $vars = array_merge($urlVars, $vars);

        foreach($vars as $key => $value){
            if(in_array($key, $ignore)){
                continue;
            }

            $query = $key;
            if(!is_empty($value)){
                $query .= '='.$value;
            }

            $query_string[] = $query;
        }

        return $url[0].'?'. implode('&', $query_string);
    }

    /**
     * Throws a HTTP Error Code and a pretty CMS Page
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   int    $error
     */
    public function throwHTTP($error=000){
        $msg = NULL;
        switch($error){
            default:
            case 000:
                header('HTTP/1.0 '.$error.'');
                $msg = 'Something went wrong, we cannot determine what. HTTP Error: '.$error;
            break;

            case 400:
                header('HTTP/1.0 400 Bad Request');
                $this->objPage->setTitle('Error 400 - Bad Request');
                $msg = 'Error 400 - The server did not understand your request.' .
                        ' If the error persists contact an administrator with details on how to replicate the error.';
            break;

            case 401:
                header('HTTP/1.0 401 Unauthorized');
                $this->objPage->setTitle('Error 401 Unauthorized');
                $msg = 'Error 401 - You do not have authorization to access this resource.';
            break;

            case 403:
                header('HTTP/1.0 403 Forbidden');
                $this->objPage->setTitle('Error 403 - Forbidden');
                $msg = 'Error 403 - You have been denied access to the requested page.';
            break;

            case 404:
                header('HTTP/1.0 404 Not Found');
                $this->objPage->setTitle('Error 404 - Page Not Found');
                $msg = 'Error 404 - The file you were looking for cannot be found.';
            break;

            case 500:
                header('HTTP/1.0 500 Internal Server Error');
                $this->objPage->setTitle('Error 500 - Internal Server Error');
                $msg = 'Error 500 - Oops it seems we have broken something..   ';
            break;
        }

        hmsgDie('FAIL', $msg);
    }



    /**
     * Loads a module and its languagefile with the name from the parameter $module
     *
     * @version 2.0
     * @since   0.8.0
     * @author  xLink
     *
     * @param   string  $module         The name of the module to be loaded
     * @param   bool    $languageFile   Defines weather the language file accociated with the module should be loaded.
     *
     * @return  bool
     */
    function loadModule($module, $languageFile=false, $mode='class'){
        if($mode===NULL){ $mode = 'class'; }
        if($mode=='class'){
            //check weather we've already used this module
            $module_enable = isset($_SESSION['site']['modules'][$module]) ? ($_SESSION['site']['modules'][$module]==1 ? 'enabled' : 'disabled') : 'first';
            $module_enable = 'enabled';
            switch($module_enable){
                case 'disabled': //false means the module is disabled so stop here.
                    $this->objPage->setTitle('Module Disabled');
                    hmsgDie('FAIL', 'Module: "'.$module.'" is disabled.');
                    exit;
                break;

                case 'first': //null means we havent so continue
                    $enable_check = $this->objSQL->getValue('modules', 'enabled', array('name = "%s"', $module));
                    switch($enable_check){
                        case NULL:
                            $this->objPage->setTitle('Module Not Installed');
                            $msg = NULL;
                            if(!is_dir(cmsROOT.'modules/'.$module.'/')){ $this->throwHTTP(404); }

                            if(file_exists(cmsROOT.'modules/'.$module.'/install.php') && User::$IS_ADMIN){
                                $msg = '<br />But it can be, <a href="/'.root().'modules/'.$module.'/install/">Click Here</a>';
                            }

                            if(User::$IS_ADMIN){
                                hmsgDie('FAIL', 'Module "'.secureMe($module).'" isnt installed.'.$msg);
                            }else{
                                $this->throwHTTP(404);
                            }
                            exit;
                        break;

                        case 0:
                            return false;
                        break;

                        default:
                        //cache it in session so we dont have to run the query everytime we use this module
                        $_SESSION['site']['modules'][$module] = $enable_check;
                    }
                break;
            }
        }

        //now with the rest of the checks
        if(!is_file(cmsROOT.'modules/'.$module.'/cfg.php')){
            hmsgDie('FAIL', 'Could not locate the configuration file for "'.$module.'". Load Failed');
        }

        if(!is_file(cmsROOT.'modules/'.$module.'/'.$mode.'.'.$module.'.php')){
            hmsgDie('FAIL', 'Could not locate Module "'.$module.'". Load Failed');
        }

            include_once(cmsROOT.'modules/'.$module.'/'.$mode.'.'.$module.'.php');
            if($languageFile){
                translateFile(cmsROOT.'modules/'.$module.'/language/lang.'.$this->config('global', 'language').'.php');
            }
        return true;
    }

    /**
     * Loads in a instance of the requested module
     *
     * @version 2.0
     * @since   0.8.0
     * @author  xLink
     *
     * @param   string  $module      Module name
     * @param   var     $returnVar   Variable you want the module to be loaded into
     * @param   string  $mode        class, admin, mod, user
     */
    function autoLoadModule($module, &$returnVar, $mode='class'){
        global $objCore;

        $objCore->objSQL->recordMessage('Loading Module: '.$module, 'INFO');

        if(!is_dir(cmsROOT.'modules/'.$module.'/')){
            hmsgDie('FAIL', 'Error loading module file "'.$module.'"');
            return;
        }

        $file = cmsROOT.'modules/'.$module.'/'.$mode.'.'.$module.'.php';
        if(!is_readable($file)){
            hmsgDie('FAIL', 'Error loading module file "'.$module.'"');
            return;
        }

        $fileData = file_get_contents($file);
        $newModule = $module.'_'.substr(md5(microtime()), 0, 6);
        $fileData = preg_replace("/(class[\s])$module([\s]extends[\s]module{)/i", '\\1'.$newModule.'\\2', $fileData);
        $success = eval('?>'.$fileData.'<?php ');
            if($success === false){
                hmsgdie('FAIL', 'Error: There was a syntax error in the class."'.$module.'".php file. Loading Halted.');
                return;
            }

        $returnVar = new $newModule($objCore);
    }

}
?>