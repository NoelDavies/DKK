<?php
if(!defined('INDEX_CHECK')){die('Error: Cannot access directly.');}

if(!isset($_GET['action']) || is_empty($_GET['action']))
    hmsgDie('FAIL', 'No idea what you wer trying to do there...');

$uid = $this->objUser->grab('id');

if(IS_MOD && isset($_GET['username'])) 
    $uid = $this->objUser->getIdByUsername($_GET['username']);

switch(strtolower($_GET['action'])){
    case 'offlink':
        if(HTTP_POST){
          
            if(!isset($_POST['avatar']))
                msgDie('FAIL', 'The update failed. Please try again.', '', '', '', 0);
            
            $a = $this->objUser->DoImage($_POST['avatar']);
            $update['avatar'] = $a!=='FAIL' ? $_POST['avatar'] : '/'.root().'images/no_avatar.png';
            if(!$this->objSQL->updateRow('users', $update, 'id = '.$uid))
                msgDie('FAIL', 'The update failed. Please try again.', '', '', '', 0);
                
            $avachgr = '<script>top.change_avatar(\''.$update['avatar'].'\');top.myLightWindow.deactivate();</script>';
            if($a=='FAIL')
                msgdie('FAIL', 'The update failed. Please try again.'.$avachgr, '', '', '', 0);
                
            $this->objLogin->setSessions($uid);
            msgdie('OK', 'The avatar update was successful.'.$avachgr, '', '', '', 0);
        }else{
            $this->objTPL->set_filenames(array(
                'body' => 'modules/profile/template/ava_upload.tpl'
            ));

                $this->objTPL->assign_vars(array(
                    'SFORM'     => $this->objForm->start('offlink', 'POST', '/'.root().'modules/profile/avatar/?action=offlink'),
                    'EFORM'     => $this->objForm->finish(),
                
                    'MSG'       => 'Please insert the URL to your avatar here.',
                    'FIELDS'    => $this->objForm->inputbox('input', '', 'avatar'),
                    'SUBMIT'    => $this->objForm->button('Update', 'submit'),
                    'IMG'       => '/'.root().'images/ajax-loading.gif'
                ));
            
            $this->objTPL->pparse('body');            
        }
    break;
    
    case 'remove':
        if(HTTP_POST){
            $script = '';
            if(isset($_POST['submit']) && $_POST['submit']=='Yes'){
                unset($update);
                $update['avatar'] = '/'.root().'images/no_avatar.png';
                $this->objSQL->updateRow('users', $update, 'id = '.$uid);
                $this->objLogin->setSessions($uid);
                $script = 'top.change_avatar(\'/'.root().'images/no_avatar.png\');';
            }
            echo '<script> ',$script,' top.myLightWindow.deactivate();</script>';
            
            msgDie('OK', "The avatar upload was successful.".$avachgr, '', '', '', 0);
        }else{
            $this->objTPL->set_filenames(array(
                'body' => 'modules/profile/template/ava_remove.tpl'
            ));
            
                $this->objTPL->assign_vars(array(
                    'SFORM'     => $this->objForm->start('remove', 'POST', '/'.root().'modules/profile/avatar/?action=remove'),
                    'EFORM'     => $this->objForm->finish(),
                
                    'MSG'       => 'Are you sure you want to remove '.(isset($_GET['username']) ? $_GET['username'].'\'s ' : 'your ').'avatar?',
                    'YES'       => $this->objForm->button('Yes', 'submit', 'boxgreen'),
                    'NO'        => $this->objForm->button('No', 'submit', 'boxred'),
                ));
            
            $this->objTPL->pparse('body');            
        }
    break;
    
    case 'reset':
        if(HTTP_POST){
            $script = '';
            if(isset($_POST['submit']) && $_POST['submit']=='Yes'){
                unset($update);
                $update['avatar'] = '/'.root().'images/no_avatar.png';
                $this->objSQL->updateRow('users', $update, 'id = '.$uid);
                $this->objUser->setNotification($uid, 'Your Avatar has been reset by '.
                                                    $this->objUser->profile($this->objUser->grab('id')).' ', 'Avatar Reset');
                $script = "top.change_avatar('/".root()."images/no_avatar.png', '".$_GET['username']."');";
            }
            echo '<script> ',$script,' top.myLightWindow.deactivate();</script>';
            
            msgDie('OK', 'The avatar upload was successful.'.$avachgr, '', '', '', 0);
         }else{
            $this->objTPL->set_filenames(array(
                'body' => 'modules/profile/template/ava_remove.tpl'
            ));
            
                $this->objTPL->assign_vars(array(
                    'SFORM'     => $this->objForm->start('remove', 'POST', '/'.root().'modules/profile/avatar/?action=reset'.
                                                                                (isset($_GET['username']) ? '&username='.$_GET['username'] : '')),
                    'EFORM'     => $this->objForm->finish(),
                
                    'MSG'       => 'Are you sure you want to reset '.(isset($_GET['username']) ? $_GET['username'].'\'s ' : 'your ').'avatar?',
                    'YES'       => $this->objForm->button('Yes', 'submit', 'boxgreen'),
                    'NO'        => $this->objForm->button('No', 'submit', 'boxred'),
                ));
            
            $this->objTPL->pparse('body');            
        }
    break;
    
    case 'upload':
        if(HTTP_POST){
            if($_FILES['avatar']['error']==4){
                msgdie('FAIL', 'The upload failed. Please try again.','','','',0);
            }
            
            $result = 0;
            $a = basename($_FILES['avatar']['name']);
            $file_ext = substr($a, strripos($a, '.'));
            $good_ext = array('.jpg','.gif','.bmp','.png','.jpeg');
            if(!in_array($file_ext, $good_ext)){
                msgDie('FAIL', 'The upload failed. Please try again.', '', '', '', 0);
            }
            $file = $this->objUser->mkPasswd(md5($a), $uid).$file_ext;
            
            if(!file_exists(cmsROOT.'images/avatars/'.$uid.'/'.$file)){
                if(!is_dir('images/avatars/'.$uid.'/')){mkdir('images/avatars/'.$uid.'/');}
                $target_path = cmsROOT.'images/avatars/'.$uid.'/'.$file;
    
                if(@move_uploaded_file($_FILES['avatar']['tmp_name'], $target_path)){
                    $result = 1;
                }else{
                    msgDie('FAIL', 'The upload failed. Please try again.','','','',0);
                }
    
                if($result){
                    $update['avatar'] = '/'.root().'images/avatars/'.$uid.'/'.$file;
                    $userava_update = $this->objSQL->updateRow('users', $update, 'id = '.$uid);
                        if($userava_update===NULL){
                            msgDie('FAIL', 'The upload failed. Please try again.', '', '', '', 0);
                        }
                    $avachgr = '<script>top.change_avatar(\''.$update['avatar'].'\');top.myLightWindow.deactivate();</script>';
                     $this->objLogin->setSessions($uid);                   
                    msgDie('OK', 'The avatar upload was successful.'.$avachgr, '', '', '', 0);
                }else{
                    msgDie('FAIL', 'The upload failed. Please try again.', '', '', '', 0);
                }
            }else{
                unset($update);
                $update['avatar'] = '/'.root().'images/avatars/'.$uid.'/'.$file;
                $userava_update = $this->objSQL->updateRow('users', $update, 'id = "'.$uid.'"');
                $avachgr = '<script>top.change_avatar(\''.$update['avatar'].'\');top.myLightWindow.deactivate();</script>';
                    if($userava_update===NULL){
                        msgDie('FAIL', 'The upload failed. Please try again.', '', '', '', 0);
                    }
                 $this->objLogin->setSessions($uid);                   
                msgDie('OK', 'The avatar upload was successful.'.$avachgr, '', '', '', 0);
            }
        }else{
            $this->objTPL->set_filenames(array(
                'body' => 'modules/profile/template/ava_upload.tpl'
            ));

                $form = 'File: '.$this->objForm->inputbox('file', '', 'avatar', array('class' => 'upload_field', 'extra' => ' size="30"')).
                                 $this->objForm->inputbox('hidden', 30000, 'MAX_FILE_SIZE');
                                  
                                 
                $this->objTPL->assign_vars(array(
                    'SFORM'     => $this->objForm->start('upload', 'POST', '/'.root().'modules/profile/avatar/?action=upload', "$(\"uploading\").Show;$(\"uploader\").Hide;", ' enctype="multipart/form-data"'),
                    'EFORM'     => $this->objForm->finish(),
                
                    'MSG'       => 'Please select the image you wish to use as your avatar.',
                    'FIELDS'    => $form,
                    'SUBMIT'    => $this->objForm->button('Upload', 'submit'),
                    'IMG'       => '/'.root().'images/ajax-loading.gif',
                    'YES'       => $this->objForm->button('Yes', 'submit', 'boxgreen'),
                    'NO'        => $this->objForm->button('No', 'submit', 'boxred'),
                ));
            
            $this->objTPL->pparse('body');            
        }
    break;
    
    default:
        hmsgDie('FAIL', 'No idea what you were trying to do there...');
    break;
}
?>