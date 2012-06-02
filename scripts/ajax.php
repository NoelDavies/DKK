<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
define('INDEX_CHECK', true);
define('cmsDEBUG', false);
define('cmsCLOSED', true);
define('NO_LOG', true);

$cmsROOT = '../';
include_once($cmsROOT.'core/core.php');

//assign some vars
$mode       = doArgs('action', null, $_GET);
$username   = $objSQL->escape(doArgs('username', null, $_POST));
$email      = $objSQL->escape(doArgs('email', null, $_POST));

if(!HTTP_AJAX){ die('Failed.'); }

switch($mode){
    case 'grabNewNotifications':
        //grab the notifications for the user       
        $notes = $objNotify->getNotifications(false);
        $return = null;
        if($notes){
            foreach($notes as $note){
                $return .= 'if(!isset($("notify_'.$note['id'].'"))){ '.$objNotify->outputNotification($note, true).' }';
            }
        }
        die('<script>'.$return.'</script>');
    break;
    
    case 'notificationRead':
        if(!HTTP_POST){ die('Fail'); }
        
        //make sure we have an id
        $id = doArgs('id', false, $_POST, 'is_number');
        if(!$id){ die('Fail'); }

        //grab the notification from the db
        $notification = $objSQL->getLine('SELECT * FROM $Pnotifications WHERE id = "%s" ', array($id));
            if(is_empty($notification)){ die('Fail'); }

        //if the user is the notify author, then update it as read
        if($notification['uid'] == $objUser->grab('id')){
            unset($update);
            $update['read'] = 1;
            $objSQL->updateRow('notifications', $update, array('id = "%s"', $notification['id']));
        }
        
        die('Done.');
    break;

    case 'userAutocomplete':
        $vars = $objPage->getVar('tplVars');
        $autocomp = $objSQL->escape($_POST['var']);
        $users = $objSQL->getTable('SELECT u.username, u.hidden, u.timestamp, o.timestamp AS otimestamp
                                    FROM `$Pusers` u
                                        LEFT JOIN `$Ponline` o 
                                            ON u.id = o.uid
                                        WHERE u.username LIKE "%'.$autocomp.'%" 
                                        ORDER BY otimestamp DESC');
         $return = "<ul>\n"; $populated = array();
        if(!is_empty($users)){
            foreach($users as $user){
                if(in_array($user['username'], $populated)){ continue; } $populated[] = $user['username'];
                
                $o = $objUser->onlineIndicator(($user['otimestamp']===NULL ? $user['timestamp'] : $user['otimestamp']), $user['hidden'], 'raw');
                $oi = ($o==1 ? $vars['USER_ONLINE'] : ($o==0 ? $vars['USER_OFFLINE'] : ($o==-1 ? $vars['USER_HIDDEN'] : $vars['USER_OFFLINE'])));
                
                $return .= "\t<li><span class=\"informal\"><img src=\"".str_replace('../', '', $oi)."\" /> </span>".$user['username']."</li>\n";
            }
        }    
        $return .= "</ul>";
        
        echo $return;
    break;

}
?>