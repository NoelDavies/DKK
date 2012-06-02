<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){ die('Error: Cannot access directly.'); }
if(!defined('PANEL_CHECK')){ die('Error: Cannot include panel from current location.'); }
$objPage->setTitle(langVar('B_ACP').' > '.langVar('L_FILE_REG'));
$objPage->addPagecrumb(array( array('url' => $url, 'name' => langVar('L_FILE_REG')) ));
$objTPL->set_filenames(array(
	'body' => 'modules/core/template/panels/panel.fileregistry.tpl',
));

$mode = doArgs('action', false, $_GET);
$changed = doArgs('chg', 0, $_GET, 'is_number');
$output = null;

switch($mode){
    default:
    	$output = msg('INFO', 'WARNING: This panel is designed to keep you informed of any changes in your files, '.
            'as such the operations that are avalible here are resource intensive and may take several mins to complete '.
            'depending on the size of your installation. This panel will keep information for files inside the CMS Install '.
            'Directory "<strong>/'.root().'</strong>".<br /><br />Please click an option from above to continue.',
        'return');
    break;

    case 'new':
    	$empty = $objSQL->query('TRUNCATE TABLE `$Pfileregistry`');
        	if($empty){ $output .= 'File Hashes removed<br />'; }

        //update the table with the new info
    	recursive_filechk('./', '', 'php');

        //upadte the db tell it we did an update
        $update = $objSQL->updateRow('config', array('value'=>time()), 'var = "registry_update"');
            if(!$update){
                hmsgDie('FAIL', 'Error: Could not update the check time.');
            }

        //reboot the cache
        $objCache->regenerateCache('config');
        $output .= 'File Hashes renewed<br />';
    break;

    case 'check':
    	$query = $objSQL->getTable('SELECT * FROM `$Pfileregistry`');
        $i = 0;
    	foreach($query as $row){
            $current_hash = -1;
            if(is_file($row['filename'])){
                $current_hash = @filesize($row['filename']) . '-' . count(@file($row['filename'])) . '-' . sha1(file_get_contents($row['filename']));
            }

    		if($current_hash == '-1'){
    			$filestatus = langVar('L_DELETED');
    			$color 		= '#0300FF';
    			$show = ($changed==1 ? true : ($changed==0 ? true : false));
    		}else if( sha1($current_hash) != $row['hash']){
    			$filestatus = langVar('L_FC_CHANGED', $objTime->timer(filemtime($row['filename']), time()));
    			$color      = '#FF1200';
    			$show = ($changed==1 ? true : ($changed==0 ? true : false));
    		}else{
    			$filestatus = langVar('L_OK');
    			$color 		= '#269F00';
    			$show = ($changed==1 ? false : true);
    		}
    		$path_cleaned = str_replace('./', '', $row['filename']);
    		if($show === true){
    			$objTPL->assign_block_vars('filestructure', array(
    				'FNAME'		=> '<a href="/'.root().$path_cleaned.'">'.$path_cleaned.'</a>',
    				'STATUS'	=> '<font color="'.$color.'">'.$filestatus.'</font>',
                    'ROW'       => ($i++%2 ? 'row_color2' : 'row_color1'),
    			));
    		}
    	}
    break;

}

$objTPL->assign_vars(array(
    'ADMIN_MODE'        => langVar('L_FILE_REG'),
    'L_FILENAME'        => langVar('L_FILENAME'),
    'L_STATUS'          => langVar('L_FILE_STATUS'),

    'CREATE_NEW'        => '<a href="?action=check" class="button">'.langVar('L_CHECK_FH').'</a>',
    'UPDATE_OLD'        => '<a href="?action=new" class="button">'.langVar('L_UPDATE_FH').'</a>',
    'CHANGED_ONLY'      => '<a href="?action=check&chg=1" class="button">'.langVar('L_CHANGED_ONLY').'</a>',
    'L_LAST_CHANGED'    => 'Last Updated On: '.$objTime->mk_time($objCore->config('site', 'registry_update')),

    'OUTPUT'            => $output,
));

$objTPL->parse('body', false);

/**
 * Function used to gather data from each file within a directory
 */
function recursive_filechk($dir, $prefix = '', $extension){
    global $config, $objSQL;

    $directory = @opendir($dir);

    while ($file = @readdir($directory)){
        if (!in_array($file, array('.', '..'))){
            $is_dir = (@is_dir($dir . '/' . $file)) ? true : false;

            // Create a nice Path for the found Files / Folders
            $temp_path = '';
            $temp_path = $dir . '/' . (($is_dir) ? strtoupper($file) : $file);
            $temp_path = str_replace('//', '/', $temp_path);

            // Remove dots from extension Parameter
            $extension = str_replace('.', '', $extension);

            // Fill it in our File Array if the found file is matching the extension
            if(preg_match('/^.*?\.' . $extension . '$/', $temp_path) && !preg_match('/cache\\//m', $temp_path)){
				$filehash = @filesize($temp_path) . '-' . count(@file($temp_path)) . '-' . sha1(file_get_contents($temp_path));
				$filehash = sha1($filehash);

                $insert['filename'] = $temp_path;
                $insert['hash'] = $filehash;

                $objSQL->insertRow('fileregistry', $insert);
            }

            // Directory found, so recall this function
            if ($is_dir){
                recursive_filechk($dir . '/' . $file, $dir . '/', $extension);
            }
        }
    }

    @closedir($directory);
}

?>