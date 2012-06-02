<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){die('Error: Cannot access directly.');}

/**
* This class handles the DB Caching
*
* @version     1.0
* @since       1.0.0
* @author      xLink
*/
class cache extends coreClass{

    private $cacheToggle = false;
    private $output = array();
    private $cacheDir = '';
    private $fileTpl = '';

    public function __construct($args=array()) {
        $this->cacheToggle = doArgs('useCache', false, $args);
        $this->cacheDir = doArgs('cacheDir', '', $args);
        $this->fileTpl = cmsROOT.'cache/cache_%s.php';
    }

    /**
     * Removes a specific set of cache files
     *
     * @version 1.0
     * @since     1.0.0
     * @author  xLink
     *
     * @param    string     $type
     *
     * @return     bool
     */
    public function remove($type) {
        $cacheFiles = '';
        switch($type){
            case 'config':
                $cacheFiles = glob(cmsROOT.'cache/cache_*.php');
            break;

            case 'media':
                $cacheFiles = glob(cmsROOT.'cache/media/minify_*');
            break;

            case 'template':
                $cacheFiles = glob(cmsROOT.'cache/template/tpl_*');
            break;
        }

        if(is_empty($cacheFiles)){ return false;}

        if(is_array($cacheFiles) && !is_empty($cacheFiles)){
            foreach($cacheFiles as $file){
                unlink($file);
            }
        }

        return true;
    }

    /**
     * Sets up a new cache file
     *
     * @version 1.0
     * @since     1.0.0
     * @author  xLink
     *
     * @param    string     $name
     * @param     string     $file
     * @param     var     $result
     * @param    string     $callback
     *
     * @return     bool
     */
    public function initCache($name, $file, $query, &$result, $callback=null){
        if($this->cacheToggle && is_file($this->cacheDir . $file)){
            include($this->cacheDir . $file);
            $result = $$name;

        }else if($callback!==null){
            eval('$result = '.$callback.'();');

        }else{
            $result = $this->generateCache($name, $file, $query);
        }

        return true;
    }

    /**
     * Regenerates a cache file
     *
     * @version 1.0
     * @since     1.0.0
     * @author  xLink
     *
     * @param     string     $file
     */
    public function regenerateCache($file){
        //if its present, remove it
        if(is_readable(sprintf($this->fileTpl, $file))){
            unlink(sprintf($this->fileTpl, $file));
        }

        //regenerate a new cache file
        $fn = ${$file.'_db'};
        newCache($file, $fn);
    }

    /**
     * Generates and caches a loadable array based on sql query
     *
     * @version 1.0
     * @since     1.0.0
     * @author     xLink
     *
     * @param     string     $name
     * @param     string     $file
     * @param     string     $query
     *
     * @return     array
     */
    public function generateCache($name, $file, $query){
        unset($this->output);

        //query db
        $query = $this->objSQL->getTable($query);

        //check to make sure it worked
        if($query===false){ $this->output = false; }

        //loop through each row of the returned array
        if(!is_empty($query)){
            foreach($query as $row){
                $nline = array();

                //and through each column of the row
                foreach($row as $k => $v){
                    if(!is_number($k) && $k!='0'){
                        $nline[$k] = $v;
                    }
                }

                //grab generated array
                $this->output[] = $nline;
            }
        }

        //if we can cache it
        if($this->output!==false) {
            $this->writeFile($name, $this->output);
        }

        //return the array directly, so its ready for use straight away
        return $this->output;
    }

    /**
     * Generates stats based on current db infomation
     *
     * @version 1.0
     * @since     1.0.0
     * @author     xLink
     *
     * @return     array
     */
    public function generate_statistics_cache(){
        //grab some info to put into the stat file
        $this->objSQL->recordMessage('Cache: Recalculating Statistics', 'INFO');
            //total members in db
            $total_members  = $this->objSQL->getInfo('users');

            //last user info, for the stat menu
            $last_user = $this->objSQL->getLine('SELECT id, username FROM `$Pusers` ORDER BY id DESC LIMIT 1');

            //online members and guests
            $online_users   = $this->objSQL->getTable('SELECT DISTINCT uid FROM `$Ponline` WHERE uid != "0"');
            $online_guests  = $this->objSQL->getTable('SELECT DISTINCT ip_address FROM `$Ponline` WHERE uid = "0"');

            //get cron updates
            $cron = $this->objSQL->getTable('SELECT * FROM `$Pstatistics`');

        if(count($cron) > 0){
            foreach($cron as $i){
                if($i['variable']=='hourly_cron'){     $hourly = $i['value']; }
                if($i['variable']=='weekly_cron'){     $weekly = $i['value']; }
                if($i['variable']=='daily_cron'){     $daily = $i['value']; }
                if($i['variable']=='site_opened'){     $started = $i['value']; }
            }
        }

        $this->output = array(
            'site_opened'         => $started,
            'total_members'     => $total_members,
            'last_user_id'         => $last_user['id'],
            'last_user_user'     => $last_user['username'],
            'online_users'         => count($online_users),
            'online_guests'     => count($online_guests),

            'hourly_cron'         => $hourly,
            'daily_cron'         => $daily,
            'weekly_cron'         => $weekly,

        );

        $this->writeFile('statistics_db', $this->output);
        return $this->output;
    }


    /**
     * Writes a cache file, this file can be reincluded after
     *
     * @version 1.0
     * @since     1.0.0
     * @author     xLink
     *
     * @param     string     $file
     * @param     string     $contents
     *
     * @return     bool
     */
    public function writeFile($file, $contents){
        if(!$this->cacheToggle){ return null; }

        $fp = @fopen(sprintf($this->fileTpl, str_replace('_db', '', $file)), 'wb');
            if(!$fp){ return false; }

        fwrite($fp, '<?php'."\n"."if(!defined('INDEX_CHECK')){die('Error: Cannot access directly.');}".
            "\n".'$'.$file.' = ' .var_export($contents, true).';'."\n".
            '?>');
        fclose($fp);

        return true;
    }
}
?>