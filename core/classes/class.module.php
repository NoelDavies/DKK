<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')) {
    die('Error: Cannot access directly.');
}

/**
 * This class handles everything modules!
 *
 * @version     2.0
 * @since         1.0.0
 * @author         xLink
 */
class Module extends coreClass {

    private $modules = array();
    public $modConf = array();

    function __construct(coreClass $objCore) {

        $this->modConf['mode']   = doArgs('__mode', null, $_GET);
        $this->modConf['module'] = doArgs('__module', null, $_GET);
        $this->modConf['action'] = doArgs('__action', null, $_GET);
        $this->modConf['extra']  = doArgs('__extra', null, $_GET);

        //global the classes for this module
        $this->objPage      = $objCore->objPage;
        $this->objSQL       = $objCore->objSQL;
        $this->objTPL       = $objCore->objTPL;
        $this->objUser      = $objCore->objUser;
        $this->objTime      = $objCore->objTime;
        $this->objForm      = $objCore->objForm;
        $this->objLogin     = $objCore->objLogin;
        $this->objPlugins   = $objCore->objPlugins;
        $this->objCache     = $objCore->objCache;
        $this->objGroups    = $objCore->objGroups;

        $this->objNotify    = $objCore->objNotify;
        $this->objComments  = $objCore->objComments;

        if(isset($_GET['ajax'])) {
            $this->objPage->setVar('simpleTpl', true);
        }

        // Retrieve info from config
        if(is_readable(cmsROOT . 'modules/' . $this->modConf['module'] . '/cfg.php')) {
            require cmsROOT . 'modules/' . $this->modConf['module'] . '/cfg.php';

            $this->modConf['path'] = '/' . root() . substr($mod_dir, 2);
        }

        $exAction   = explode('/', $this->modConf['action']);
        $this->modConf['filename'] = (!is_empty($this->modConf['action']) && !is_empty($this->modConf['extra'])
                                        ? $this->objSQL->escape($exAction[count($exAction)-1].$this->modConf['extra'])
                                        : '');
        $this->modConf['ext'] = ((substr_count($this->modConf['filename'], '.') > 0)
                                    ? (substr($this->modConf['filename'], strrpos($this->modConf['filename'], '.') + 1))
                                    : NULL);
        $this->modConf['action'] = $this->modConf['action'] . $this->modConf['extra'];
        $this->modConf['all'] = $this->modConf['path'] . $this->modConf['action'];

        //specify some deafult actions
        if(preg_match('/images\/(.*?)/i', str_replace($this->modConf['extra'], '', $this->modConf['action']))) {
            $imagesTypes = array('jpg', 'gif', 'png', 'jpeg', 'jfif', 'jpe', 'bmp', 'ico', 'tif', 'tiff');
            if(in_array($this->modConf['ext'], $imagesTypes) &&
                is_readable(cmsROOT . 'modules/' . $this->modConf['module'] . '/images/' . $this->modConf['filename'])) {

                    header('Content-Type: image/' . $this->modConf['ext']);
                    include (cmsROOT . 'modules/' . $this->modConf['module'] . '/images/' . $this->modConf['filename']);
                    exit;
            } else {
                $this->throwHTTP('404');
            }
        }
        if(preg_match('/scripts\/(.*?)/i', str_replace($this->modConf['extra'], '', $this->modConf['action']))) {
            if(file_exists(cmsROOT . 'modules/' . $this->modConf['module'] . '/' . $this->modConf['action'])) {
                header('Content-type: text/javascript');
                include (cmsROOT . 'modules/' . $this->modConf['module'] . '/' . $this->modConf['action']);
                exit;
            } else {
                $this->throwHTTP('404');
            }
        }
        if(preg_match('/styles\/(.*?)/i', str_replace($this->modConf['extra'], '', $this->modConf['action']))) {
            if(file_exists(cmsROOT . 'modules/' . $this->modConf['module'] . '/' . $this->modConf['action'])) {
                header('Content-Type: text/css');
                include (cmsROOT . 'modules/' . $this->modConf['module'] . '/' . $this->modConf['action']);
                exit;
            } else {
                $this->throwHTTP('404');
            }
        }
    }


    /**
     * Check if a module exists in the file structure
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Jesus
     *
     * @param   string     $moduleName
     *
     * @return  bool
     */
    public function moduleExists($moduleName) {
        if(is_empty($moduleName) || !is_dir(cmsROOT . 'modules/' . $moduleName)) {
            return false;
        }

        $files = getFiles(cmsROOT . 'modules/' . $moduleName);
        if(is_empty($files)) {
            return false;
        }
        return true;
    }


    /**
     * Get the list of modules from the database
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Jesus
     *
     * @param   string     $moduleName
     *
     * @return  bool
     */
    private function getModuleListCache($moduleName){
        // Check the argument is valid
        if(is_empty($moduleName)){
            return false;
        }

        // If the result already exists, then gogo fetch.
        if(isset($this->modules[$moduleName])){
            return true;
        } else {
            // Else query the database and find it
            $modules = $this->objSQL->getTable('SELECT * FROM `$Pmodules`');
                if(!$modules){ return false; }

            foreach($modules as $module) {
                $this->modules[$module['name']] = $module;
            }

            return true;
        }
    }

    /**
     * Check if a module is installed or not
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Jesus
     *
     * @param   string     $moduleName
     *
     * @return  bool
     */
    public function moduleInstalled($moduleName){
        $this->getModuleListCache($moduleName);

        if(!array_key_exists($moduleName, $this->modules)){
            return false;
        }

        if($this->modules[$moduleName]['enabled'] == '1'){
            return true;
        }

        return false;
    }


    /**
     * Gets relevent data from a module
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Jesus
     *
     * @param   array
     *
     * @return  bool
     */
    public function getModuleData($moduleName){
        $this->getModuleListCache($moduleName);

        if(!array_key_exists($moduleName, $this->modules)){
            return false;
        }

        return $this->modules[$moduleName];
    }
}
?>