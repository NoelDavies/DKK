<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){ die('Error: Cannot access directly.'); }

//Highlight and Row Stuff
//row_color1 and 2 should be the same in the css files
$tpl['row_color1']          = '#ECECEC';
$tpl['row_color2']          = '#D7DEE3';
$tpl['row_highlight']       = '#2B2B2B';


$i = '/'.root().Page::$THEME_ROOT.'buttons/';
$img = '/'.root().'images/icons/';

$tpl['IMG_locked']          = $i.'locked_old.png';
$tpl['IMG_moved']           = $i.'redirected.png';
$tpl['IMG_posts_new']       = $i.'post_new.png';
$tpl['IMG_posts_old']       = $i.'post_old.png';
$tpl['IMG_announcement_new']= $i.'announcement_new.png';
$tpl['IMG_announcement_old']= $i.'announcement_old.png';
$tpl['IMG_sticky_new']      = $i.'sticky_new.png';
$tpl['IMG_sticky_old']      = $i.'sticky_old.png';
$tpl['IMG_subForum_new']    = $i.'new_mini.png';
$tpl['IMG_subForum_old']    = $i.'old_mini.png';

$tpl['FIMG_post_edit']      = $i.'edit.png';
$tpl['FIMG_post_move']      = $i.'move.png';
$tpl['FIMG_post_del']       = $i.'delete.png';
$tpl['FIMG_locked']         = $i.'lock.png';
$tpl['FIMG_unlocked']       = $i.'unlock.png';

$tpl['FIMG_reply']          = $img.'comments.png';
$tpl['FIMG_post_quote']     = $img.'comment.png';

$tpl['PM_compose']            = $i.'sendpm.gif';
$tpl['PM_reply']            = $i.'reply_small.gif';

$tpl['IMG_expand']          = $i.'maximize.png';
$tpl['IMG_retract']         = $i.'minimize.png';

$tpl['USER_ONLINE']         = $i.'online.png';
$tpl['USER_OFFLINE']        = $i.'offline.png';
$tpl['USER_HIDDEN']         = $i.'hidden.png';

$objPage->updateTplVars($tpl);
unset($tpl);
?>