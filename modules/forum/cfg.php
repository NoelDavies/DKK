<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){ die('Error: Cannot access directly.'); }

$mod_name       = 'Forum';
$mod_version    = '1.0';
$mod_desc       = 'Forum Module';
$mod_dir        = './modules/forum/';
$mod_author     = 'xLink';
$mod_url        = 'http://www.cybershade.org';
$mod_id         = 'aaa3078a02a0de4e85925611c8a9b677';

$mod_menu		= array(
    'admin' => array(
        'General' => array(
            'Forum Configuration'   => 'config/',
            'Category Management'   => 'setup/',
        ),
        'Permissions' => array(
            'Group Permissions'     => 'group/?mode=group',
            'User Permissions'      => 'group/?mode=user',
        ),
    ),
);
?>