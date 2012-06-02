<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){ die('Error: Cannot access directly.'); }

/**
 * This class handles the DB Caching
 *
 * @version     1.0
 * @since       1.0.0
 * @author      xLink
 */
class user extends coreClass{

    //some static vars, these save function calls
    static $IS_ONLINE = false;
    static $IS_ADMIN = false, $IS_MOD = false, $IS_USER = false;

    /**
     * Sets the current user to online
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   bool $value
     *
     * @return  bool
     */
    public function setIsOnline($value=true){
        return self::$IS_ONLINE = $value;
    }

    /**
     * Returns the status of the current user
     * Note: This function is depreciated, it has been left here purely for old code.
     *
     * @deprecated  true
     *
     * @return      bool
     */
    public function is_online(){ return self::$IS_ONLINE; }


    /**
     * Defines global CMS permissions
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     */
    public function initPerms(){
        self::$IS_USER      = $this->checkPermissions($this->grab('id'), USER);
        self::$IS_ADMIN     = $this->checkPermissions($this->grab('id'), ADMIN);
        self::$IS_MOD       = $this->checkPermissions($this->grab('id'), MOD);
    }

    /**
     * Inserts a users info into the database.
     *
     * @version 1.1
     * @since   1.0.0
     * @author  Jesus
     *
     * @param   array $userInfo   Array of the users details.
     *
     * @return  bool
     */
    public function register(array $userInfo){
        //Check all the args are good and valid
        $userInfo['username']   = doArgs('username',    false,  $userInfo);
        $userInfo['password']   = doArgs('password',    false,  $userInfo);
        $userInfo['email']      = doArgs('email',       false,  $userInfo);

        //if we have a false, in the above array, we has a problem
        if(in_array(false, $userInfo)){
            $this->setError('username, password and email are all required to continue.');
            return false;
        }

        //add some extra stuff in before we submit it
        $userInfo['password']       = $this->mkPassword($userInfo['password']);
        $userInfo['register_date']  = time();
        $userInfo['usercode']       = substr(md5(time()), 0, 6);
        $userInfo['primary_group']  = $this->config('site', 'user_group');
        $userInfo['theme']          = $this->config('site', 'theme');

        //active needs to be the opposite of whatever 'register_verification' is...
        $userInfo['active']         = !$this->config('site', 'register_verification');

        //Implement a hook before a users' registration has completed
        $this->objPlugins->hook('CMSUser_Before_Registered', $userInfo);

        if(!is_array($userInfo) || is_empty($userInfo)){
            $this->setError('$userInfo is no longer a useable array. Check plugins attached to CMSUser_Before_Register.');
            return false;
        }

        $insert_id = $this->objSQL->insertRow('users', $userInfo, langVar(
            'LOG_CREATED_USER',
            sprintf('/%smodules/profile/%s', root(), $userInfo['username']),
            $userInfo['username']
        ));

        //Implement a hook after a users' registration has completed
        $this->objPlugins->hook('CMSUser_After_Registered', $insert_id);

        if(!$insert_id){
            $this->setError('insert_id has a false value, SQL: '.mysql_error());
            return false;
        }

        //add a new row into user_extras for this users settings
        unset($insert);
        $insert['uid'] = $insert_id;
        $this->objSQL->insertRow('user_extras', $insert);

        //register the user into the group
        $this->objGroups->joinGroup($insert_id, $userInfo['primary_group'], 0);

        unset($userInfo);
        return $insert_id;
    }

    /**
     * Returns a setting's value set on the current user
     *
     * @version 2.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   string $setting
     *
     * @return  mixed
     */
    public function grab($setting){
        global $config;

        return doArgs($setting, false, $config['global']['user']);
    }

    /**
     * Retrieves information about a given user
     *
     * @version 1.2
     * @since   1.0.0
     * @author  xLink
     *
     * @param   string $uid         Either Username of UserID
     * @param   string $field       Name of the field wanted or * for all
     *
     * @return  mixed               Field requested or whole user information.
     */
    public function getUserInfo($uid, $field='*'){
        //we need to populate the query
        if(!isset($this->userInfo[$uid])){
            //figure out if they gave us a username or a user id
            $user = (is_number($uid) ? 'u.id = "%s" ' : 'upper( u.username ) = upper( "%s" ) ');

            $info = $this->objSQL->getLine(
                'SELECT u.*, e.*, u.id as id, o.timestamp, o.hidden, o.userkey '.
                'FROM `$Pusers` u '.
                    'LEFT JOIN `$Puser_extras` e '.
                        'ON u.id = e.uid '.
                    'LEFT JOIN `$Ponline` o '.
                        'ON u.id = o.uid '.
                'WHERE '.$user.' '.
                'LIMIT 1;',
                array($uid)
            );
                if(!count($info)){
                    $this->setError('User query failed. SQL: '.mysql_error()."\n<br />".$query);
                    return false;
                }

            //uid is for the extras table, no need to have it here
            unset($info['uid']);

            //this is so the cache will work even if they give you a username first time and uid the second
            $this->userInfo[strtolower($info['username'])] = $info;
            $this->userInfo[$info['id']] = $info;
            unset($info);
        }

        //if we didnt want it all then make sure the bit they wanted is there
        if($field != '*'){
            if(isset($this->userInfo[strtolower($uid)][$field])){
                return $this->userInfo[strtolower($uid)][$field];
            }else{
                //if what they wanted isnt there, no point returning the whole thing, might confuse a few people
                $this->setError('Requested field dosen\'t exist. ('.$field.')');
                return false;
            }
        }

        //worst case, return the entire user
        return $this->userInfo[strtolower($uid)];
    }


    /**
     * Determines whether the user is online or not.
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   mixed $uid     Username used to retreive the UID
     *
     * @return  bool
     */
    public function isUserOnline($uid){
        $ts = $this->getUserInfo($uid, 'timestamp');

        return (is_empty($ts) ? false : true);
    }

    /**
     * Retrieves the UID from the username.
     *
     * @version 2.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   int $uid    UID used to retreive the Username
     *
     * @return  string      The username that was returned, Or Guest if it failed.

     */
    public function getUsernameById($username){
        $return = $this->getUserInfo($uid, 'username');

        if($return === false){ return 'Guest'; }
        return $return;
    }

    /**
     * Retrieves the Username from the UID.
     *
     * @version 2.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   string $username    Username used to retreive the UID
     *
     * @return  int                 The UID that was returned, Or 0 if it failed.
     */
    public function getIdByUsername($uid){
        $return = $this->getUserInfo($username, 'id');

        if($return === false){ return 0; }
        return $return;
    }

    /**
     * Updates the users on-site location
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @return  bool    True on Successful Update
     */
    public function updateLocation(){
        // generate the array for the db update
        $update['timestamp']    = time();
        $update['location']     = secureMe(doArgs('REQUEST_URI', 'null', $_SERVER));
        $update['referer']      = secureMe(doArgs('HTTP_REFERER', 'null', $_SERVER));

        //force the location system to ignore js and css files, these like to be the entry in the database which isnt useful
        if(preg_match('/(scripts|styles|js|css|xml)/sm', $update['location'])) {
            unset($update['location'], $update['referer']);
        }

        if(doArgs('userkey', false, $_SESSION['user'])) {
            $this->objSQL->updateRow('online', $update, array('`userkey` = "%s"', $_SESSION['user']['userkey']));
            $result = (mysql_affected_rows() ? true : false);
        }

        unset($update);
        return $result;
    }

    /**
     * Generates a hash from the $string var.
     *
     * @version 2.0
     * @since   1.0.0
     * @author  Jesus
     *
     * @param   string $password
     * @param   string $hash
     *
     * @return  string                Password Hashed Input
     */
    public function mkPassword($string, $salt=null){
        // Use the new portable password hashing framework
        $objPass = new phpass(8, true);

        // Hash the password
        $hashed = $objPass->HashPassword($salt.$string);

        unset($objPass, $password, $hash);
        return $hashed;
    }

    /**
     * Verifies the password
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Jesus
     *
     * @param   string $password
     * @param   string $hash
     *
     * @return  bool
     */
    public function checkPassword($password, $hash){
        //use the new portable password hashing framework
        $objPass = new phpass(8, true);

        //verify the password
        $hashed = $objPass->CheckPassword($password, $hash);

        //and return
        unset($objPass, $password, $hash);
        return $hashed;
    }

    /**
     * Updates the users settings according to $settings.
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   mixed     $uid          Username or UID.
     * @param   array     $settings     An array of settings, (columnName => value).
     *
     * @return  bool                    True if settings were fully updated, False if they wasnt.
     */
    public function updateUserSettings($uid, array $setting, $log=false){
        unset($setting['id'], $setting['uid']);

        if(!count($setting)){
            $this->setError('No setting changes detected. Make sure the array you gave was populated. '.
                                'The following columns are blacklisted from being updated with this function: '.
                                'id, uid, password, pin ');
            return false;
        }

        //make sure user exists first
        $user = $this->getUserInfo($uid, 'id');
            if(!$user){ return false; }

        //grab the columns for users and user_extras tables
        if(!isset($this->userColumns)){     $this->userColumns     = $this->objSQL->getColumns('users'); }
        if(!isset($this->extraColumns)){     $this->extraColumns = $this->objSQL->getColumns('user_extras'); }
            if(!$this->userColumns || !$this->extraColumns){
                $this->setError('Could not get columns. SQL: '.mysql_error());
                return false;
            }

        //run thru the array given to us and assign them to the array needed
        $userUpdate = $extraUpdate = array();
        foreach($setting as $column => $value){
            if(in_array($column, $this->userColumns)){
                $userUpdate[$column] = $value;
                continue;
            }

            if(in_array($column, $this->extraColumns)){
                $extraUpdate[$column] = $value;
                continue;
            }
        }

        if(!count($userUpdate) && !count($extraUpdate)){
            $this->setError('Could not find any fields in $settings to update. Aborting.');
            return false;
        }

        //now run the updates, and if all goes well return true
        if(count($userUpdate)){
            $return = $this->objSQL->updateRow('users', $userUpdate, array('id = "%s" ', $user));
                if($return===false){
                    $this->setError('User update portion failed. SQL: '.mysql_error());
                    return false;
                }
        }

        if(count($extraUpdate)){
            $return = $this->objSQL->updateRow('user_extras', $extraUpdate, array('uid = "%s" ', $user));
                if($return===false){
                    $this->setError('Extras update portion failed. SQL: '.mysql_error());
                    return false;
                }
        }

        if($log!==false){
            $this->objSQL->recordLog('', $log);
        }


        unset($return, $user, $userUpdate, $extraUpdate, $userColumns, $extraColumns);
        return true;
    }

    /**
     * Check to see if the username is a valid one.
     *
     * @version 1.0
     * @since   0.8.0
     * @author  xLink
     *
     * @param   $username
     *
     * @return  bool
     */
    public function validateUsername($username, $existCheck=false){
        if(strlen($username) > 25 || strlen($username) < 2){
            $this->setError('Username dosen\'t fall within usable length parameters. Between 2 and 25 characters long.');
            return false;
        }
        if(preg_match('~[^a-z0-9_\-@^]~i', $username)){
            $this->setError('Username dosen\'t validate. Please ensure that you are using no special characters etc.');
            return false;
        }
        if($existCheck==true && $this->getUserInfo($username, 'username')){
            $this->setError('Username alerady exists. Please make sure your username is unique.');
            return false;
        }

        return true;
    }

    /**
     * Check to see if the email is a valid one.
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   $email
     *
     * @return  bool
     */
    public function validateEmail($email) {
        global $objBBCode;

        $email = strtolower($email);
        $email = $objBBCode->UnHTMLEncode(strip_tags($email));

        if(!$objBBCode->IsValidEmail($email)){
            return false;
        }
        return true;
    }

    /**
     * Returns the IP Address of the user, it will get IP from the proxy client if needed.
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   mixed $uid      Username or UID.
     *
     * @return  mixed           IP address of user.
     */
    public static function getIP(){
        if      ($_SERVER['HTTP_X_FORWARDED_FOR']){ $ip = $_SERVER['HTTP_X_FORWARDED_FOR']; }
        else if ($_SERVER['HTTP_X_FORWARDED']){     $ip = $_SERVER['HTTP_X_FORWARDED']; }
        else if ($_SERVER['HTTP_FORWARDED_FOR']){   $ip = $_SERVER['HTTP_FORWARDED_FOR']; }
        else{                                       $ip = $_SERVER['REMOTE_ADDR']; }

        return $ip;
    }

    /**
     * Sets and Updates the user position on the website. Also allows for automated actions
     *
     * @version 2.0
     * @since   1.0.0
     * @author  xLink
     */
    public function tracker(){

        $update = false; $rmCookie = false;
        $action = null; $logout = false;

        //if user is online
        if(self::$IS_ONLINE){
            //make sure they still have a key
            $action = 'check user key, and reset if needed';
            if(is_empty(doArgs('userkey', null, $this->config('global', 'user')))){
                $this->newKey(); //give em one if they havent
            }

            //force update
            $update = true;
        }else{
            //check for remember me cookie
            if(!is_empty(doArgs('login', null, $_COOKIE))){
                 //try and remember who they are, this sometimes is hard, but we try anyway
                if(!$this->objLogin->runRememberMe()){
                    $action = 'remove remember me cookie';
                    $rmCookie = true;

                //you should be logged in now, so redirect
                }else{
                    $action = 'remember me worked';

                    $this->objPage->redirect('', 1);
                    exit;
                }
            }else{
                $online = $this->objLogin->onlineData();

                if(!is_array($online)){
                    $action = 'register new guest';
                    $this->newOnlineSession();
                }else{
                    $action = 'update guest';
                    $update = true;
                }
            }
        }

        if($update == true){

            if(!isset($online)){
                //grab the online table data
                $online = $this->objLogin->onlineData();
            }

            if(isset($online['mode'])){
                switch($online['mode']){
                    default:
                    case 'active':
                        $action = 'update user location';

                        //make sure the user dosent have guest identification if hes logged in
                        if(self::$IS_ONLINE && $online['username'] == 'Guest'){
                            $this->objSQL->deleteRow('online', array('userkey = "%s"', $this->grab('userkey')));
                            $this->newOnlineSession(false);
                        }

                        //now thats sorted, update
                        $this->updateLocation();
                    break;

                    //we have been ordered to terminate >:}
                    case 'kill':
                        $action = 'kill user';

                        //and log em out
                        $logout = true;
                    break;

                    case 'ban':
                        $action = 'ban user';

                        //ban the user account if they are online
                        if(self::$IS_ONLINE){
                            $this->banUser($objUser->grab('id'));

                        //ban the ip if they are a guest
                        }else{
                            $this->banIP(self::getIP());
                        }

                        $logout = true;
                    break;

                    case 'update':
                        $action = 'update user info';
                        //so we want to grab a new set of sessions
                        if(self::$IS_ONLINE){
                            $this->setSessions($this->grab('id'));
                        }

                        //and notify the user telling them, this notification wont be persistant though
                        #$objUser->notify('Your information has been updated. Changes around the site reflect these changes.', 'Profile Update');

                        //update the online table so we dont have any problems
                        $this->objSQL->updateRow('online', array('mode'=>'active'), array('userkey = "%s"', $this->grab('userkey')));
                    break;
                }

            //user has no mode set...wtf?
            }else{
                $action = 're-reg user info';

                //insert users info back into the online table
                $this->newOnlineSession(false);
            }

            if($logout && self::$IS_ONLINE){
                //remove their online row
                $this->objSQL->deleteRow('online', array('userkey = "%s"', $this->grab('userkey')));

                //and log em out properly
                $this->objLogin->logout($this->grab('usercode'));

                //remove their cookie so auto login dosent kick in
                $rmCookie = true;
            }

        }

        //remove the cookie if needed
        if($rmCookie){
            $action = 'rm remember me cookie';
            set_cookie('login', '', time()-31536000);
            unset($_COOKIE['login']);
        }

        //unset the admin auth after 20 mins of no acp activity
        if(self::$IS_ADMIN && isset($_SESSION['acp']['adminTimeout'])){
            if(time() >= $this->objTime->mod_time($_SESSION['acp']['adminTimeout'], 0, 20)){
                unset($_SESSION['acp']);
            }
        }
        unset($update, $rmCookie, $action, $logout);
    }

    /**
     * Sets a new key for the user
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     */
    public function newKey(){
        //grab the old key before we overwrite it
        $oldKey = $_SESSION['user']['userkey'];

        //set a new one and update it in the db
        $_SESSION['user']['userkey'] = md5('userkey'.microtime(true));
        $this->objSQL->updateRow('online', array('userkey' => $_SESSION['user']['userkey']), array('userkey = "%s"', $oldKey));

        return $_SESSION['user']['userkey'];
    }

    /**
     * Sets the online session for the tracker
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   string $log
     *
     * @return  bool
     */
    public function newOnlineSession($log=NULL){
        $insert['uid']           = $this->grab('id');
        $insert['username']      = $this->grab('username');
        $insert['ip_address']    = User::getIP();
        $insert['timestamp']     = time();
        $insert['location']      = secureMe($this->config('global', 'fullPath', 'null'));
        $insert['referer']       = secureMe($this->config('global', 'referer', 'null'));
        $insert['language']      = secureMe($this->config('site', 'language', 'en'));
        $insert['useragent']     = secureMe($this->config('global', 'browser'));
        $insert['userkey']       = isset($_SESSION['user']['userkey']) ? $_SESSION['user']['userkey'] : $this->newKey();

        if($this->objSQL->insertRow('online', $insert, 0, $log)){
            $this->objCache->generate_statistics_cache();
            return true;
        }
        return false;
    }

    /**
     * Sets the users password.
     *
     * @version 2.0
     * @since   0.8.0
     * @author  Jesus
     *
     * @param   mixed   $uid        Username or UserID
     * @param   string  $password   Plaintext version of the password.
     *
     * @return  bool
     */
    public function setPassword($uid, $password, $log=NULL){
        $array['password']          = $this->mkPassword($password);
        $array['password_update']   = 0;
        $array['login_attempts']    = 0;

        $this->objPlugins->hook('CMSCore_prePasswordChanged', $array);

        $uid = (!is_number($uid) ? $this->getUserInfo($uid, 'id') : $uid);
        if($this->objSQL->updateRow('users', $array, array('id = "%d"', $uid))){
            $this->objPlugins->hook('CMSCore_postPasswordChanged', func_get_args());
            return true;
        }
        return false;
    }

    /**
     * Sets the users PIN.
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Jesus
     *
     * @param   mixed   $uid    Username or UserID
     * @param   string  $pin    Plaintext version of the PIN.
     *
     * @return  bool
     */
    public function setPIN($uid, $pin, $log=NULL){
        $array['pin']           = md5($pin.$this->config('db', 'ckeauth'));
        $array['pin_attempts']  = 0;

        $this->objPlugins->hook('CMSCore_prePinChanged', $array);

        $uid = (!is_number($uid) ? $this->getUserInfo($uid, 'id') : $uid);
        if($this->objSQL->updateRow('users', $array, array('id = "%d"', $uid))){
            $this->objPlugins->hook('CMSCore_postPinChanged', func_get_args());
            return true;
        }
        return false;
    }


    /**
     * Sets the user session on login
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Jesus
     *
     * @param   mixed   $uid        Username or UserID
     * @param   string  $autoLogin
     *
     * @return  bool
     */
    public function setSessions($uid, $autoLogin=false){

        //grab the user info
        $userInfo = $this->getUserInfo($uid);
            if($userInfo === false || is_empty($userInfo)){ return false; }

        //grab timestamp before we clear the array
        $timestamp = doArgs('last_active', time(), $_SESSION['user']);

        //reset the user part of the session
        $_SESSION['user'] = array();
        $_SESSION['user'] = $userInfo;
        $_SESSION['user']['last_active'] = $timestamp;
        $_SESSION['user']['userkey'] = $this->newKey();
        #session_regenerate_id(true);

        //if we are auto logging in, then update last_active
        if($autoLogin){
            $update['last_active'] = time();
            $this->objSQL->updateRow('users', $update, array('id = "%s"', $uid));
        }
        return true;
    }

    /**
     * Resets the users sessions
     *         if current user, then just do it,
     *         if not, then set flag in online table to do it
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Jesus
     *
     * @param   int     $uid    UserID
     */
    public function reSetSessions($uid){
        if($uid == $this->grab('id')){
            $this->setSessions($uid);
        }else{
            unset($update);
            $update['mode'] = 'update';
            $this->ObjSQL->updateRow('online', $update, array('uid = "%s"', $uid));
        }
    }

    /**
     * Returns a specific Ajax Setting for the user.
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   string   $setting
     *
     * @return  mixed
     */
    function ajaxSettings($setting){
        //grab the setting arsenal
        $uAjaxSettings = $_SESSION['user']['ajax_settings'] = $this->getUserInfo($this->grab('id'), 'ajax_settings');
            if(is_empty($uAjaxSettings)){ return false; }

        //unpack them
        $ajaxSettings = unserialize($uAjaxSettings);
            if(!is_array($ajaxSettings)){ return false; }

        //and then see if the one we want is present
        if(in_array($setting, $ajaxSettings)){
            //and return it
            return $ajaxSettings[$setting];
        }

        //or false
        return false;
    }

    /**
     * Toggles the active flag on the user account
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   int     $uid        UserID
     * @param   bool    $state      true to activate the user,
     *                                  false to deactivate the user,
     *                                  null to toggle it
     *
     * @return  bool
     */
    public function toggleActivation($uid, $state=null){
        //if nothing was given, grab the current state
        if(is_null($state)){
            $state = $this->getUserInfo($uid, 'active');
        }

        //switch these around, if true we want to activate
        if($state===true || $state == 1){ $state = 0; }
        //false should deactivate
        if($state===false || $state == 0){ $state = 1; }

        switch($state){
            // deactivate the account
            case 1:
                $active = array('active' => '0');
                sendEMail($this->userData['email'], 'E_LOGIN_ATTEMPTS', array(
                    'username' => $this->userData['username'],
                    'url' => $this->config('global', 'rootUrl').'login.php?action=active&un='.$this->userData['id'].'&check='.$this->userData['usercode']
                ));
            break;

            // activate the account
            case 0:
                $active = array('active' => '1');
            break;

            default:
                return false;
            break;
        }

        //update the user row
        $this->objUser->updateUserSettings($uid, $active);

        //and hook here
        $this->objPlugins->hook('CMSUser_active', $active);

        return true;
    }

    /**
     * Toggles the ban flag on the user account
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   int     $uid    UserID
     * @param   bool    $state  true to ban the user,
     *                              false to unban the user,
     *                              null to toggle it
     *
     * @return  bool
     */
    public function toggleBan($uid, $state=null){
        //if nothing was given, grab the current state
        if(is_null($state)){
            $state = $this->getUserInfo($uid, 'ban');
        }

        //switch these around, if true we want to ban em
        if($state===true || $state == 1){ $state = 0; }
        //false should deactivate
        if($state===false || $state == 0){ $state = 1; }

        switch($state){
            // unban the account
            case 1:
                $ban = array('ban' => '0');
            break;

            // ban the account
            case 0:
                $ban = array('ban' => '1');
            break;

            default:
                return false;
            break;
        }

        //update the user row
        $this->objUser->updateUserSettings($uid, $ban);

        //and hook here
        $this->objPlugins->hook('CMSUser_ban', $ban);

        return true;
    }

    /**
     * Returns a username color coded by group
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   int     $uid     UserID
     * @param   int     $mode    LINK, RAW, NO_LINK, RETURN_USER
     *
     * @return  bool    True/False on successful check, -1 on unknown group
     */
    public function profile($uid, $mode=LINK) {
        //check if the user has a UID of 0
        if(is_number($uid) && $uid == GUEST){
            $user = 'Guest';
            return $this->_profile_processor($user);
        }

        //grab this users info
        $user = $this->getUserInfo($uid);
            if(!$user){
                $user = 'Guest';
                return $this->_profile_processor($user);
            }

        if($user['primary_group']!=0){
            global $config;

            //see if the group we want is in the cache
            foreach($config['groups'] as $g){
                if($g['id'] == $user['primary_group']){
                    $group = $g; break;
                }
            }
        }

        //if not then we'll query for it
        if(!$group){
            $groups = $this->objSQL->getTable(
                'SELECT g.* FROM `$Pgroup_subs` ug
                    JOIN `$Pusers` u
                        ON u.id = ug.uid
                    JOIN `$Pgroups` g
                        ON ug.gid = g.id

                WHERE ug.uid = "%s" AND ug.pending = 0
                ORDER BY g.`order` ASC',
                array($uid)
            );

            //no groups wer received so we'll jump to the output stage
            if(!$groups){
                $group = null;
            }else{

                //we are looking for a specific group here
                if($user['primary_group'] != 0){
                    foreach($groups as $g){
                        if($g['id'] == $user['primary_group']){
                            $group = $g;
                        }
                    }

                //the CMS has been asked to figure out which group is needed
                }else{
                    $curr = 300;

                    foreach($groups as $g){
                        //if this group has a higher number its color wins
                        if($g['order'] < $curr){
                            $curr = $g['order'];
                            $group = $g;
                        }
                    }
                }
             }
        }

        return $this->_profile_processor($user, $group, $mode);
    }

    /**
     * Processes the arguments into a HTML markup
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   array   $user   An array containing all the user information
     * @param   array   $group  An array with the group information
     * @param   int     $mode   LINK, RAW, NO_LINK, RETURN_USER
     *
     * @return  string
     */
    protected function _profile_processor($user, $group=null, $mode=0){
        $user = (is_array($user) ? $user['username'] : $user);
        $color = (!is_empty($group['color']) ? ' style="color: '.$group['color'].';"' : null);
        $title = (!is_empty($group['description']) ? ' title="'.$group['description'].'"' : null);

        //set a generic tag up for the user
        $font = '<font class="username"%s%s>%s</font>';

        $raw = $user;
        $banned = sprintf($font, ' style="text-decoration: line-through;" ', $title, $user);
        $user_link = '<a href="/'.root().'modules/profile/view/'.$user.'" rel="nofollow">'.sprintf($font, $color, $title, $user).'</a>';
        $user_no_link = sprintf($font, $color, $title, $user);

        switch($mode){
            case -1:            $return = $banned;          break;

            default:
            case LINK:          $return = $user_link;       break;

            case RETURN_USER:
            case NO_LINK:       $return = $user_no_link;    break;

            case RAW:           $return = $raw;             break;
            case 4:             $return = $uid;             break;
        }

        return $return;
    }

    /**
     * Returns an fully parsed avatar
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   int     $uid
     * @param   int     $size
     *
     * @return  string  HTML of the parsed avatar.
     */
    public function parseAvatar($uid, $size=100){
        $defaultAvatar = '/'.root().'images/no_avatar.png';

        $avatar = $this->getUserInfo($uid, 'avatar');
            if(is_empty($avatar)){
                $avatar = $defaultAvatar;
                $_avatar = '<img src="%1$s" height="%2$s" width="%3$s" class="avatar corners" />';
                return sprintf($_avatar, $avatar, $size, $size);
            }

        $avatar = secureMe(preg_replace('_^/images/_', '/'.root().'images/', $avatar));
        $username = $this->getUserInfo($uid, 'username');
        $username_avatar = $username.'_avatar';
        $user = strtolower($username);

        $_avatar = '<a href="%1$s" class="lightwindow" title="%4$s\'s Avatar" data-avatar="%5$s">'.
                    '<img src="%1$s" height="%2$s" width="%2$s" name="%3$s" id="%3$s" title="%4$s\'s Avatar" class="avatar corners" data-avatar="%4$s" /></a>';

        return sprintf($_avatar, $avatar, $size, $username_avatar, $username, $user);;
    }

    /**
     * Returns an online indicator according to $timestamp.
     *
     * @version 2.0
     * @since   0.7.0
     * @author  xLink
     *
     * @param   int     $timestamp  The timestamp of the user
     * @param   bool    $hidden     Whether or not the user should be hidden
     *
     * @return  string              The Online, Offline or Hidden Indicator in HTML.
     */
    function onlineIndicator($uid=0, $returnType='img'){
        $vars = $this->objPage->getVar('tplVars');
        $timestamp = $this->getUserInfo($uid, 'timestamp');

        //make a default img to return, everybody by default are offline
        $img = '<img src="'.$vars['USER_OFFLINE'].'" title="User is Offline">';
        $raw = '0';

        //timestamp is not set, return offline img
        if(!is_number($timestamp)){
            return (strcmp($returnType, 'raw')==0 ? $raw : $img);
        }

        if($timestamp >= $this->objTime->mod_time(time(), 0, 20, 0, 'TAKE')){ //check whether they are 'online'
            if($uid != 0 && $this->getUserInfo($uid, 'hidden')==true){//do they want to be hidden
                //do you have enough perms to see whether they are online or not?
                if(User::$IS_MOD){//oh you do?
                    $img = '<img src="'.$vars['USER_HIDDEN'].'" title="User is hiding">';
                    $raw = '-1';
                }else{//haha didnt think so..
                    $img = '<img src="'.$vars['USER_OFFLINE'].'" title="User is Offline">';
                    $raw = '0';
                }
            }else{//ahh not hidden then
                $img = '<img src="'.$vars['USER_ONLINE'].'" title="User is Online">';
                $raw = '1';
            }
        }
        return (strcmp($returnType, 'raw')==0 ? $raw : $img);
    }

    /**
     * Returns permission state for given user and group
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   int     $uid        UserID
     * @param   int     $group      GUEST, USER, MOD, or ADMIN
     *
     * @return  bool    True/False on successful check, -1 on unknown group
     */
    public function checkUserAuth($type, $key, $u_access, $is_admin){
        $auth_user = 0;

        if(count($u_access)){
            for($j = 0; $j < count($u_access); $j++){
                $result = 0;
                switch($type){
                    case AUTH_ACL:   $result = $u_access[$j][$key]; break;
                    case AUTH_MOD:   $result = $result || $u_access[$j]['auth_mod']; break;
                    case AUTH_ADMIN: $result = $result || $is_admin; break;
                }
                $auth_user = $auth_user || $result;
            }
        }else{
            $auth_user = $is_admin;
        }
        return $auth_user;
    }

    /**
     * Returns permission state for given user and group
     *
     * @version 1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   int     $uid        UserID
     * @param   int     $group      GUEST, USER, MOD, or ADMIN
     *
     * @return  bool    True/False on successful check, -1 on unknown group
     */
    public function checkPermissions($uid, $group=0) {
        $group = (int)$group;

        //make sure we have a group to check against
        if(is_empty($group) || $group == 0 || $group == GUEST){
            return true;
        }

        //check to see whether we have a user id to check against..
        if(is_empty($uid)){
            return false;
        }

        //grab the user level if possible
        $userlevel = GUEST;
        if(self::$IS_ONLINE){
            $userlevel = $this->getUserInfo($uid, 'userlevel');
        }

        //see which group we are checking for
        switch($group){
            case GUEST:
                if(!self::$IS_ONLINE){
                    return true;
                }
            break;

            case USER:
                if(self::$IS_ONLINE){
                    return true;
                }
            break;

            case MOD:
                if($userlevel == MOD){
                    return true;
                }
            break;

            case ADMIN:
                if($userlevel == ADMIN){
                    if(LOCALHOST){
                        return true;
                    }
                    if(doArgs('adminAuth', false, $_SESSION['acp'])){
                        return true;
                    }
                }
            break;

            //no idea what they tried to check for, so we'll return something unexpected too
            default: return -1; break;
        }

        //if we are an admin then give them mod powers regardless
        if(($group == MOD || $group == USER) && $userlevel == ADMIN){
            return true;
        }

        //apparently the checks didnt return true, so we'll go for false
        return false;
    }

}
?>