<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){die('Error: Cannot access directly.');}

/**
 * Handles logging in and out for the user, and admin control panel
 *
 * @version 2.0
 * @since   1.0.0
 * @author  Jesus
 */
class login extends coreClass{

    /**
     * Returns the online row for the current user logged in or not.
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Jesus
     *
     * @return  array
     */
    public function onlineData(){
        if(isset($this->onlineData)){ return $this->onlineData; }

        $query = 'SELECT `id`, `uid`, `username`, `ip_address`, `timestamp`, `location`,
                    `referer`, `language`, `useragent`, `login_attempts`, `login_time`, `userkey`, `mode`
                        FROM `$Ponline` WHERE userkey="%s"';
        return $this->onlineData = $this->objSQL->getLine($query, array($_SESSION['user']['userkey']));
    }

    /**
     * Checks whether the user has exceeded the login quota
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Jesus
     *
     * @param   bool    $dontUpdate
     *
     * @return  bool
     */
    public function attemptsCheck($dontUpdate=false){
        if($this->onlineData['login_time'] >= time()){
            return false;

        }elseif($this->onlineData['login_attempts'] > $this->config('login', 'max_login_tries')){
            if($this->onlineData['login_time'] == '0'){
                $this->objSQL->updateRow('online', array(
                    'login_time'         => $this->objTime->mod_time(time(), 0, 15),
                    'login_attempts'    => '0'
                ), 'userkey = "'.$_SESSION['user']['userkey'].'"');
            }
            return false;
        }

        if($dontUpdate){ return true; }

        if($this->userData['login_attempts'] >= $this->config('login', 'max_login_tries')){
            if($this->userData['login_attempts'] == $this->config('login', 'max_login_tries')){
                //deactivate the users account
                $this->objUser->toggleActivation($this->userData['id'], false);
            }
            return false;
        }

        return true;
    }

    /**
     * Returns the active flag of the account
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Jesus
     *
     * @return  bool
     */
    public function activeCheck(){
        return (bool)$this->userData['active'];
    }

    /**
     * Returns the ban flag of the account
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Jesus
     *
     * @return  bool
     */
    public function banCheck(){
        return !(bool)$this->userData['banned'];
    }

    /**
     * Verifies the PIN with what’s in the user row
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Jesus
     *
     * @return  bool
     */
    public function verifyPin(){
        return (isset($_POST['pin']) && md5($_POST['pin'].$this->config('db', 'ckeauth')) == $this->userData['pin'] ? true : false);
    }

    /**
     * Updates the login attempts for the user
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Jesus
     */
    public function updateLoginAttempts(){
        if(!is_empty($this->userData)){
            $this->objUser->updateUserSettings($this->userData['id'], array('login_attempts' => $this->userData['login_attempts']+1));
        }

        $query = 'UPDATE `$Ponline` SET login_attempts = (login_attempts + 1) WHERE userkey = "%s"';
        $this->objSQL->query(
            $query,
            array($_SESSION['user']['userkey']),
            'Online System: '.USER::getIP().' failed to login to '.$this->userData['username'].'\'s account.'
        );
    }

    /**
     * Updates the login attempts for Admin panel
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Jesus
     *
     * @return  int
     */
    public function updateACPAttempts(){
        if(!is_empty($this->userData)){
            $this->objSQL->updateRow(
                'users',
                array('pin_attempts' => $this->userData['pin_attempts']+1),
                array('id = "%s"', $this->userData['id']),
                'Online System: '.$this->userData['username'].' attemped to authenticate as administrator.'
            );
        }

        if(($this->userData['pin_attempts']+1) == 4){
            unset($update);
            $update['active']       = '0';
            $update['banned']       = '1';
            $update['pin_attempts'] = '0';

            $this->objSQL->updateRow('users', $update, array('id = "%s"', $this->userData['id']),
                'Online System: Logged '.$this->userData['username'].' out as a security measure. 3 Wrong Authentication attempts for ACP.');

            $this->objSQL->updateRow('online', array(
                'login_time'        => $this->objTime->mod_time(time(), 0, 15),
                'login_attempts'    => '0'
            ), 'userkey = "'.$_SESSION['user']['userkey'].'"');

            $this->logout($this->userData['usercode']);
        }

        return ($this->userData['pin_attempts']+1);
    }

    /**
     * Makes sure all information is valid and logs the user in if needed
     *
     * @version 1.5
     * @since   1.0.0
     * @author  xLink
     *
     * @param   bool $ajax
     *
     * @return  bool
     */
    public function doLogin($ajax=false){
        $acpCheck = isset($_SESSION['acp']['doAdminCheck']) ? true : false;

        //make sure we have a post
        if(!HTTP_POST){
            $this->doError('No POST action detected');
            return false;
        }

        //verify username and password are set and not empty
        $username = doArgs('username', null, $_POST);
        $password = doArgs('password', null, $_POST);
        if(is_empty($username) || is_empty($password)){
            $this->doError('0x02', $ajax);
            return false;
        }

        //make sure the user hasnt already exceeded their login attempt quota
        if(!$this->attemptsCheck(true)){
            $this->doError('0x03', $ajax);
        }

        //grab user info
        $this->userData = $this->objUser->getUserInfo($username);
            if(!$this->userData){
                $this->doError('0x02', $ajax);
                return false;
            }

        $this->postData = array(
            'username' => $username,
            'password' => $password,
        );

        //no need to run these if we are in acp mode
        if($acpCheck === FALSE){
            if(!$this->whiteListCheck()){   $this->doError('0x04', $ajax); }
            if(!$this->activeCheck()){      $this->doError('0x05', $ajax); }
            if(!$this->banCheck()){         $this->doError('0x06', $ajax); }
        }

        //update their quota
        if(!$this->attemptsCheck()){        $this->doError('0x03', $ajax); }

        //make sure the password is valid
        if(!$this->objUser->checkPassword($password, $this->userData['password'])){
            $this->doError('0x07', $ajax);
        }

        //if this is aan acp check
        if($acpCheck){
            //verify the pin exists
            if(is_empty($this->userData['pin'])){
                $this->doError('0x10', $ajax);
            }
            //now check its valid
            if(!$this->verifyPin()){
                $this->doError('0x11', $ajax);
            }

            //update attempts to 0
            unset($settings);
            $settings['pin_attempts'] = '0';
            $settings['login_attempts'] = '0';
            $this->objUser->updateUserSettings($this->userData['id'], $settings,
                'Online System: Administration Privileges given to '.$this->userData['username'].'');

            //no need for this to be set anymore
            unset($_SESSION['acp']['doAdminCheck'], $settings);

            //set a session for the acp auth
            $_SESSION['acp']['adminAuth'] = true;
            $_SESSION['acp']['adminTimeout'] = time();

            //redirect em straight to the acp panel if not ajax'd else get JS to do it
            if($ajax){ die('dcne'); }
            $this->objPage->redirect('/'.root().'admin/', 0);
            return;
        }

        $uniqueKey = substr(md5($this->userData['id'].time()), 0, 5);

        // Add Hooks for Login Data
        $this->userData['password_plaintext'] = $this->postData['password'];
        $this->objPlugins->hook('CMSLogin_onSuccess', $this->userData);

        $this->objSQL->updateRow('online',
            array(
                'uid' => $this->userData['id'],
                'username' => $this->userData['username']
            ),
            array('userkey = "%s"', $_SESSION['user']['userkey']),
            'Online System: '.$this->userData['username'].' Logged in'
        );

        $this->objUser->setSessions($this->userData['id']);
        $this->objUser->updateLocation();

        //make sure we want em to be able to auto login first
        if($this->config('login', 'remember_me')){
            if(doArgs('remember', false, $_POST)){
                $this->objUser->updateUserSettings($this->userData['id'], array('autologin'=>1));

                $cookieArray = array(
                    'uData'     => $uniqueKey,
                    'uIP'       => User::getIP(),
                    'uAgent'    => md5($_SERVER['HTTP_USER_AGENT'].$this->config('db', 'ckeauth'))
                );

                set_cookie('login', serialize($cookieArray), $this->objTime->mod_time(time(), 0, 0, 24*365*10));
                $cookieArray['uData'] .= ':'.$this->userData['id']; //add the uid into the db

                $this->objSQL->insertRow('userkeys', $cookieArray,
                    'Online System: RememberMe cookie set for '.$this->userData['username'].'.');

                unset($cookieArray);
            }
        }

        //redirect em straight to the index if not ajax'd else get JS to do it
        if($ajax){ die('done'); }
        $this->objPage->redirect(doArgs('referer', '/'.root().'index.php', $_SESSION['login']), 0);
    }


    /**
     * Makes sure the cookie is valid
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Jesus
     *
     * @return  bool
     */
    public function runRememberMe(){

        if(!$this->config('login', 'remember_me')){
            $this->setError('Remember Me Failed. Remember Me is disabled site wide');
            return false;
        }

        //make sure we have a cookie to begin with
        if(is_empty(doArgs('login', null, $_COOKIE))){
            $this->setError('Remember Me Failed. Cookie not found.');
            return false;
        }

        //this should return something not empty...
        $cookie = unserialize($_COOKIE['login']);
            if(is_empty($cookie)){
                $this->setError('Remember Me Failed. Cookie contained unexpected information.');
                return false;
            }

        //verify we have the data we need
        $values = array('uData', 'uIP', 'uAgent');
        foreach($values as $e){
            if(!isset($cookie[$e]) && !is_empty($cookie[$e])){
                $this->setError('Remember Me Failed. Cookie contained unexpected information.');
                return false;
            }
        }

        //uData should be 5 chars in length
        if(strlen($cookie['uData']) != 5){
            $this->setError('Remember Me Failed. Cookie contained unexpected information.');
            return false;
        }

        //make sure the IP has the right IP of the client
        if($this->config('login', 'ip_lock', false) && $cookie['uIP'] !== User::getIP()){
            $this->setError('Remember Me Failed. Cookie contained unexpected information.');
            return false;
        }

        //and make sure the useragent matches the client
        if($cookie['uAgent'] != md5($_SERVER['HTTP_USER_AGENT'].$this->config('db', 'ckeauth'))){
            $this->setError('Remember Me Failed. Cookie contained unexpected information.');
            return false;
        }

        //setup the query
        unset($query);
        $query[] = 'SELECT uData FROM `$Puserkeys` ';
        $query[] =         'WHERE uData LIKE "%'.secureMe($cookie['uData'], 'MRES').':%" ';
        $query[] =             'AND uAgent = "'.secureMe($cookie['uAgent'], 'MRES').'" ';

        if($this->config('login', 'ip_lock')){
            $query[] =         'AND uIP = "'.secureMe($cookie['uIP'], 'MRES').'" ';
        }

        $query[] = 'LIMIT 1;';

        //prepare and exec
        $query = $this->objSQL->getLine(implode(' ', $query));

        if(!count($query)){
            $this->setError('Could not query for userkey');
            return false;
        }

        //untangle the user id from the query
        $query['uData'] = explode(':', $query['uData']);

        if(!isset($query['uData'][1]) || is_empty($query['uData'][1])){
            $this->setError('No ID Exists');
            return false;
        }

        //now try and grab the user's info
        $this->userData = $this->objUser->getUserInfo($query['uData'][1]);
            if(is_empty($this->userData)){
                $this->setError('No user exists with that ID');
                return false;
            }

        //now check to make sure users info is valid before letting em login properly
        if($this->userData['autologin'] == 0){
            $this->setError('User isn\'t set to autologin.');
            return false;
        }

        if(!$this->activeCheck()){
            $this->setError('User isn\'t active.');
            return false;
        }

        if(!$this->banCheck()){
            $this->setError('User is banned.');
            return false;
        }

        if(!$this->whiteListCheck()){
            $this->setError('You\'re IP dosent match the whitelist.');
            return false;
        }

        //everything seems fine, log them in
        $this->objUser->setSessions($this->userData['id'], true);
        $this->objUser->newOnlineSession('Online System: AutoLogin Sequence Activated for '.$this->userData['username']);
        return true;
    }

    /**
     * Turns error codes in to human readable errors
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Jesus
     *
     * @param   mixed     $errCode
     * @param   bool     $ajax
     */
    function doError($errCode, $ajax=false){
        $acpCheck = isset($_SESSION['acp']['doAdminCheck']) ? true : false;

        switch($errCode){
            default:
                $L_ERROR = $errCode;
            break;

            case '0x0':
                $L_ERROR = '('.$errCode.') I Can\'t seem to find the issue, Please contact a system administrator or <a href="mailto:'.
                                $this->config('site', 'admin_email') .'">Email The Site Admin</a>';
            break;

            case '0x1':
                $L_ERROR = 'There was a problem with the form submittion. Please try again.';
                $this->updateLoginAttempts();
            break;

            case '0x2':
                $L_ERROR = 'Your Username or Password combination was incorrect. Please try again.';
                ($acpCheck ? $this->updateACPAttempts() : $this->updateLoginAttempts());
            break;

            case '0x3':
                $L_ERROR = 'You have attempted to login too many times with incorrect credentials. Therefore you have been locked out.';
            break;

            case '0x4':
                $L_ERROR = 'The whitelist check on your account failed. We were unable to log you in.';
                $this->updateLoginAttempts();
            break;

            case '0x5':
                $L_ERROR = 'Your account is not activated. Please check your emails for the activation Email or Contact an Administrator to get this problem resolved.';
            break;

            case '0x6':
                $L_ERROR = 'Your account is banned. We were unable to log you in.';
                $this->updateLoginAttempts();
            break;

            case '0x7':
                $L_ERROR = 'Your Username or Password combination was incorrect. Please try again.';
                ($acpCheck ? $this->updateACPAttempts() : $this->updateLoginAttempts());
            break;

            case '0x8':
                $L_ERROR = 'Your account is now active. If your encounter any problems please notify a member of staff.';
            break;

            case '0x9':
                $L_ERROR = 'Sorry we cannot verify your PIN at this time.';
                ($acpCheck ? $this->updateACPAttempts() : $this->updateLoginAttempts());
            break;

            case '0x10':
                $L_ERROR = 'You need to set your PIN before your able to login to the admin control panel.';
                ($acpCheck ? $this->updateACPAttempts() : $this->updateLoginAttempts());
            break;

            case '0x11':
                $L_ERROR = 'The PIN you provided was invalid.';
                ($acpCheck ? $this->updateACPAttempts() : $this->updateLoginAttempts());
            break;
        }

        $good = array('0x8');

        $_SESSION['login']['error'] = $L_ERROR;
        $_SESSION['login']['class'] = (in_array($errCode, $good) ? 'boxgreen' : 'boxred');

        if($ajax){
            die($L_ERROR);
        }else{
            $this->objPage->redirect('/'.root().'login.php', 0);
        }
    }

    /**
     * Logs the user out
     *
     * @version 1.0
     * @since   1.0.0
     * @author  Jesus
     *
     * @param   string $check    The user code to verify
     */
    public function logout($check){
        if(!is_empty($check) && $check == $this->objUser->grab('usercode')){

            $this->objUser->updateUserSettings($this->objUser->grab('id'), array('autologin'=>'0'));
            $this->objSQL->deleteRow('online', array('userkey = "%s"', $_SESSION['user']['userkey']));
            unset($_SESSION['user']);

            if(isset($_COOKIE['login'])){
                setCookie('login', '', $this->objTime->mod_time(time(), 0, 0, ((24*365*10)*1000)*1000, 'MINUS'));
                unset($_COOKIE['login']);
            }

            session_destroy();
            if(isset($_COOKIE[session_name()])){
                setCookie(session_name(), '', time()-42000);
            }

            $this->objPage->redirect(doArgs('HTTP_REFERER', '/'.root().'index.php', $_SERVER), 0);
        }else{
            $this->objPage->redirect('/'.root().'index.php', 0, '5');
            msgDie('FAIL', 'You\'ve Unsuccessfully attempted to logout.<br />Please use the correct procedures.');
        }
    }


    /**
     * Checks the whitelist associated with an account
     *
     * @version 1.2
     * @since   1.0.0
     * @author  Jesus
     *
     * @return  bool
     */
    public function whiteListCheck(){
        if(!$this->userData['whitelist'] || is_empty($this->userData['whitelisted_ips'])){
            return true;
        }

        $ip         = USER::getIP();
        $whitelist  = json_decode($this->userData['whitelisted_ips']);
            if(!is_array($whitelist) || is_empty($whitelist)){ return true; }

        foreach($whitelist as $range){
            if(checkIPRange($range, $ip)){
                return true;
            }
        }

        return false;
    }

}
?>