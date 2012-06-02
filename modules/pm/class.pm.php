<?php
if (!defined('INDEX_CHECK')) {
    die('Error: Cannot access directly.');
}

/**
 * The PM Sys module
 */
class PM extends Module {

    protected $delimiter    = ',';
    protected $userColors   = array('F0DDD5','DCEDEA','EFCDF8','DBDBFF','F8DAE2','FFEAEA','F7F9D0','D6F8DE','E1E1FF','F9F9FF','DBF0F7','FFC8F2');
    protected $currentUID   = '';

    /**
     * Does all the pretty things to the module
     */
    public function doAction($action) {

        if (!USER::$IS_ONLINE) {
            $this->objPage->redirect('/' . root() . 'login.php', 3);
            hmsgDie('INFO', 'Please login to view your Private Messages. Redirecting you now...');
        }

        $this->currentUID = $this->objUser->grab('id');

        #$this->objPage->setMenu('pm', 'default');
        //Not Needed Right now
        #$this->objPage->addJSFile('/' . root() . '');
        #$this->objPage->addCSSFile('/' . root() . '');

        $this->objPage->setTitle(langVar('T_PM'));

        $this->objPage->addPagecrumb(array(
            array('url' => '/' . root() . 'modules/pm/', 'name' => langVar('T_PM'))
        ));


        if(preg_match('_compose/_i', $action)){
            $action = 'compose';
        }

        if(preg_match('_inbox/_i', $action)){
            $action = 'inbox';
        }

        if(preg_match('_index/_i', $action) || is_empty($action)){
            $action = 'inbox';
        }

        if(preg_match('_add/_i', $action)){
            $action = 'addRecipient';
        }

        switch (strtolower($action)) {

            case 'addRecipient':
                $this->addRecipient( );
                break;

            case 'inbox':
                $this->getBoxes();
                break;

            case 'compose':
                $this->compose();
                break;

            default:
                $this->throwHTTP(404);
            break;
        }
    }

    /**
     * Gets all the active boxes (Inbox, Outbox, etc)
     *
     * @author  DarkMantis
     * @since   1.0
     *
     * @version 1.0
     *
     * @return  boolean true/false
     */
    protected function getBoxes( ){

        //Setting the template filename
        $this->objTPL->set_filenames(array(
            'body' => 'modules/pm/template/messageIndex.tpl'
        ));

        //Grab the current User ID
        $user = $this->objUser->grab('id');

        $inboxResults = $this->objSQL->getTable(
                'SELECT pm.*, info.read, info.deleted, l.subject as lSubject, l.message as lMessage, l.creator as lCreator
                FROM $Ppm AS pm
                    LEFT JOIN $Ppm as l
                        ON l.id = (SELECT id FROM $Ppm WHERE parent_id = pm.id ORDER BY last_updated DESC LIMIT 1)
                    LEFT JOIN $Ppm_extras AS info
                        ON pm.id = info.pm_id
                    WHERE pm.parent_id = 0
                        AND info.uid = "%d"
                        AND info.deleted = 0
                GROUP BY pm.id
                ORDER BY l.last_updated ASC, pm.last_updated ASC', array($user)
        );

        if (!is_empty($inboxResults)) {
            //Show the messages
            foreach ($inboxResults as $key => $row) {
                if ($row['creator'] == $user) {
                    $children = $this->objSQL->getTable(
                            'SELECT * FROM $Ppm
                            WHERE parent_id = %$1d
                            OR id= %$1d', array($row['id']));

                    $showMsg = false;

                    foreach ($children as $child) {
                        if ($child['creator'] !== $user) {
                            $showMsg = true;
                        }
                    }

                    //If the msg isn't theres then don't display the message
                    if ($showMsg !== true) {
                        unset($inboxResult[$key]);
                    }

                    $fullSubject = $row[( is_empty($row['lSubject']) ? 'subject' : 'lSubject' )];
                    $subject = truncate($fullSubject);
                    $msgSnippet = truncate($row[( is_empty($row['lMessage']) ? 'message' : 'lMessage')], 100, true);
                    $message = truncate(contentParse($row[( is_empty($row['lMessage']) ? 'message' : 'lMessage' )], false, true), 450, false);
                    $pmURL = $this->modConf['path'] . 'view/' . seo(truncate($subject, 30)) . '-' . $row['id'] . '.html';

                    //THIS IS TEH BIT TO CHANGE ->>
                    $authorName     = $this->objUser->profile($row[( $row['lAuthor'] === null ? 'author' : 'lAuthor' )]);
                    $hiddenCheckBox = $this->objForm->checkbox(false, 'pm_selected[]', $row['id'], array('extra' => 'class="pmIndexCheckbox"'));
                    $authorAvatar   = $this->objUser->parseAvatar($row[( $row['lAuthor'] === null ? 'author' : 'lAuthor' )], 48);

                    // Assign the above data to the template
                    $this->objTPL->assign_block_vars('messages', array(
                        'SUBJECT'       => $subject,
                        'FULL_SUBJECT'  => $fullSubject,
                        'MESSAGE'       => $msgMessage,
                        'THREAD_URL'    => $pmURL,
                        'AUTHOR_NAME'   => $authorName,
                        'AUTHOR_AVATAR' => $authorAvatar,
                        'HIDDEN_INPUT'  => $hiddenCheckBox,
                        'READ_CLASS'    => 'row_color' . ( $row['read'] == '1' ? '1' : '2' ),
                        'READ_ICON'     => $tplVars['MSG_' . ($row['read'] == '1' ? '' : 'UN') . 'READ'],
                        'TIMESTAMP'     => $this->objTime->timer($result['lastUpdated'])
                    ));

                    // Setup and Loop through the message options
                    $options = array(
                        'Reply' => array(
                            'imagePath' => $tplVars['PM_REPLY'],
                            'altText' => langVar('PM_T_REPLY'),
                            'linkSrc' => $this->modConf['path'] . 'view/' . seo($row['subject']) . '-' . $row['id'],
                        ),
                        'Delete' => array(
                            'imagePath' => $tplVars['FIMG_post_del'],
                            'altText' => langVar('PM_DELETE'),
                            'linkSrc' => $this->modConf['path'] . 'delete/' . $row['id'],
                        ),
                        'Mark' => array(
                            'imagePath' => $tplVars['MSG_' . ($row['read'] == '1' ? '' : 'UN') . 'READ'],
                            'altText' => langVar('PM_MAS_' . ($row['read'] == '1' ? 'UN' : '') . 'READ'),
                            'linkSrc' => $this->modConf['path'] . 'mark/' . $row['id'],
                            'extra' => ' onclick="markAs(this); return false;"'
                        )
                    );

                    foreach ($options as $name => $arg) {

                        $this->objTPL->assign_block_vars('messages.options', array(
                            'HREF'      => $arg['linkSrc'],
                            'IMG_PATH'  => $arg['imagePath'],
                            'IMG_ALT'   => $arg['altText'],
                            'EXTRA'     => (!is_empty($arg['extra']) ? $arg['extra'] : null),
                            'NAME'      => $name,
                        ));
                    }
                }
            }
        } else {
            hmsgDie('INFO', '<i> Sorry, This box is currently empty. <a href="'.root().'modules/pm/compose/"> Compose? </a> </i>');
        }

        //Output the module
        $this->objTPL->parse('body', false);

        return true;
    }

    /**
     * Function to compose Priv Msgs
     *
     * @author
     * @since 1.0.0
     * @version
     *
     * @return  boolean true/false
     */
    public function compose() {

        //If there is no HTTP post then show the template stuff
        if (!HTTP_POST) {

            //Need these scripts for the editor
            $this->objPage->addJSFile('/'.root().'modules/forum/scripts/forum.js');
            $this->objPage->addJSFile('/'.root().'scripts/editor.js');
            $this->objPage->addCSSFile('/'.root().'modules/forum/styles/forum.css');

             //Create the Template for the composition of the PM
            $this->objTPL->set_filenames(array(
                'body' => 'modules/forum/template/forum_post.tpl'
            ));

            $uid = doArgs('uid', 0, $_GET, 'is_number');
            if (!is_empty($uid)) {
                $user = $objUser->getUserInfo($uid);
            }


            //If errors were set, then display them to the user
            if(isset($_SESSION['pmSys']['error'])){
                $this->objPage->redirect($this->modConf['path'] . '/compose', 3);

                //Change this to be correct, just using this for testing purposes
                hmsgDie('INFO', 'Sorry, some errors occured: '. dump($_SESSION['pmSys']['error']));

                return false;
            }

        //
        //-- BBCode Buttons
        //
            $this->autoLoadModule('forum', $objForum);
            $button[] = array('text_heading_1.png', 'Heading 1', 'h1', '[h1]|[/h1]');
            $button[] = array('text_heading_2.png', 'Heading 2', 'h2', '[h2]|[/h2]');
            $button[] = array('text_heading_3.png', 'Heading 3', 'h3', '[h3]|[/h3]');
            $button[] = '---';
            $button[] = array('text_bold.png', 'Bold', 'bold', '[b]|[/b]');
            $button[] = array('text_italic.png', 'Italics', 'italics', '[i]|[/i]');
            $button[] = array('text_underline.png', 'Underlined', 'underlined', '[u]|[/u]');
            $button[] = array('text_strikethrough.png', 'Strikethrough', 'strikethrough', '[s]|[/s]');
            $button[] = $objForum->genSelects('color');
            $button[] = '---';
            $button[] = array('link.png', 'Link', 'links', "[url]|[/url]");
            $button[] = array('email.png', 'Email Link', 'email', "[email]|[/email]");
            $button[] = array('photo_delete.png', 'Image', 'image', "[img]|[/img]");
            $button[] = array('comment.png', 'Add Quote', 'quote', "[quote]\n|\n[/quote]");
            $button[] = '---';
            $button[] = array('script_code.png', 'Code Block', 'code', "[code]\n|\n[/code]");
            $button[] = array('php.png', 'PHP Code Block', 'phpcode', "[code=php]\n|\n[/code]");
            $button[] = $objForum->genSelects('code');
            $button[] = '---';
            $button[] = array('text_columns.png', 'Add Table Columns', 'columns', "[columns]|[/columns]");
            $button[] = array('text_list_bullets.png', 'Add Bullet Points', 'ul', "[list]\n[*]|[/list]");
            $button[] = array('text_list_numbers.png', 'Add Numbered Points', 'ol', "[list=ol]\n[*]|\n[/list]");
            $button[] = array('text_superscript.png', 'Add Superscript Text', 'sup', "[sup]|[/sup]");
            $button[] = array('text_subscript.png', 'Add Subscript Text', 'sub', "[sub]|[/sub]");

            $this->objPlugins->hook('MODForum_post_buttons', $buttons);

            $buttons = NULL;
            foreach($button as $b){
                if(!is_array($b) && strlen($b)>3){ $buttons .= $b; continue; }
                if(!is_array($b) && $b == '---'){ $buttons .= ' &nbsp; '; continue; }

                $buttons .= sprintf(
                '<input type="image" src="%s" class="bbButton" title="%s" data-code="%s" />',
                    '/'.root().'images/icons/'.$b[0],
                    $b[1],
                    $b[3]
                );
            }

            // template variables
            $this->objTPL->assign_vars(array(
                'F_START'   => $this->objForm->start('compose', array('method'=>'POST', 'action'=>'?')),
                'F_END'     => $this->objForm->finish(),


                'F_TO'      =>  $this->objForm->inputbox('to', 'text', null, array(
                    'placeholder'   => 'For multiple recipients type \',\' after the persons name',
                    'style'         => 'width: 98%',
                )),
                'TO'        =>  'Recipient',

                'L_TITLE'   => 'Subject',
                'F_TITLE'   => $this->objForm->inputbox('subject', 'text', null, array(
                    'placeholder'   => 'Subject...',
                    'style'         => 'width: 98%',
                )),

                'L_POST_BODY' => 'Post Body',
                'F_POST'      =>  $this->objForm->textarea('post', null, array('placeholder' => 'The Message Body Goes here...', 'style'=> 'height:350px;width:99%;')),

                'BUTTONS' => $buttons,
                'SMILIES' => $objForum->generateSmilies(),

                'SUBMIT'        => $this->objForm->button('submit', 'Submit', array('extra'=> ' tabindex="3"')),
                'RESET'         => $this->objForm->button('preview', 'Preview', array('extra'=> ' tabindex="4" onclick="doPreview();"')),

            ));

            $this->objTPL->assign_block_vars('pm', array());
            $this->objTPL->assign_block_vars('title', array());

            $this->objTPL->parse('body', false);

        } else {

            $this->objPage->redirect( cmsROOT, 3 );
            hmsgDie( 'INFO', 'We do apologise, but this is currently under development and is not currently working.' );

            $_SESSION['pmSys']['errors'] = array();
            $oops = false;

            //grab the users in which the author wants to send to
            $to = doArgs('to', null, $_POST);
            if(strpos($to, $this->delimiter) !== false){
                $involved = explode($this->delimiter, $to);
            } else {
                //if only one user, wrap it in an array
                $involved = array($to);
            }

            //grab the subject
            $subject = doArgs('subject', false, $_POST);
                if(!$subject){
                    $_SESSION['pmSys']['errors'][] = 'Subject was empty.';
                    $oops = true;
                }

            //grab the message body
            $body = doArgs('body', false, $_POST);
                if(!$body){
                    $_SESSION['pmSys']['errors'][] = 'Message Body was empty.';
                    $oops = true;
                }

            // run thru each of the users make sure they exist
            $errors = array();
            foreach($involved as $to) {
                //If the user doesn't exist in the Database, then return an error
                if($this->objUser->getIdByUsername($to) === false){
                   $errors['users'] = $to;
                   $oops = true;
                }
            }

            //If errors exist within the array then redirect back to the previous page and throw error
            if($oops){
                if(!is_empty($errors['users'])){
                    $_SESSION['pmSys']['errors'][] = 'The following user(s) do not exist: '.implode(', ', $errors['users']);
                }

                $_SESSION['pmSys']['form'] = $_POST;
                $this->objPage->redirect($this->modConf['path'] . 'compose', 3);
                hmsgDie('INFO', 'Error: One or more errors occured.');
            }

            //insert PM into DB
            unset($insert);
            $insert['involved'] = json_encode((array)$to);
            $insert['subject'] = $subject;
            $insert['message'] = $body;

            $insertedPM = $this->objSQL->insertRow('pm', $insert);
                if($insertedPM === false){
                    $this->objPage->redirect($this->modConf['path'] . '/compose', 3);
                    hmsgDie('INFO', 'Sorry, an error occured sending the PM, please try again.');
                }

            //run thru all users, inserting their info into pm_extras
            foreach($involved as $uid){
                //Insert all data into database
                unset($insert);
                $insert['pm_id'] = $insertedPM;
                $insert['uid'] = $uid;

                $query = $this->objSQL->insertRow('pm_extras', $insert);
                    //If the query fails then return error
                    if (!$query) {
                        $this->objPage->redirect($this->modConf['path'] . '/compose', 3);
                        hmsgDie('INFO', 'Sorry, an error occured sending the PM, please try again.');
                    }
            }
            //Redirect the user back to the orignal page, and then reply with a message saying how special they are for sending a msg
            $this->objPage->redirect('?', 3);
            msgDie('OK', 'Congratulations, You special bunny! I hope your happy. The PM was sent.');
            return true;
        }
    }

    /**
     * Mark the status of the Private Message to be read/unread
     *
     * @author  DarkMantis
     * @since   1.0
     * @version 0.1
     *
     * @param   int $id
     * @param   int $status 1/0
     *
     * @return  mixed
     */
    public function markStatus( $id, $status ){
        if( is_empty( $status ) || !is_number( $id )){
            hmsgDie('FAIL', 'Sorry that operation was invalid, please specify a valid id and status');
        }

        //Unset to make sure there is no extra data which is un-needed
        unset($update);

        $update['uid']          =   $this->objUser->grab('id');
        $update['read']         =   ( ( $status == 1 ) ? 1 : 0 );

        $sql = $this->objSQL->updateRow('pm_extras', $update, array('pm_id=%d', $id));

        if($sql == false){
            hmsgDie('FAIL', 'Sorry the PM could not be marked as read, please try again.');
        }

        return true;
    }


    /**
     * Reads the Private Message [full] with the Given ID
     *
     * @author  DarkMantis
     * @version 1.0
     * @since   1.0
     *
     * @param int $id
     *
     * @return  boolean
     */
    public function readPM( $id ){
        /*
         * Todo:
         *
         * 1. Loop through the replies and show them all in order of timestamp
         * 2. Show quick reply function at the bottom of the templates
         * 3. If post quick reply then grab text-area data and perform new function $this->reply();
         */

        if( is_empty( $id ) ){
            hmsgDie('FAIL', 'The ID specified was invalid, please specify a valid Private Message');
        }

        // If HTTP_POST === false
        if(!HTTP_POST){

            $this->objTPL->set_filenames(array(
                'body'  =>  'modules/pm/template/readPM.tpl'
            ));

            //Get the PM Information from the two tables
            $getPM = $this->objSQL->getLine(
                'SELECT *
                    FROM $Ppm
                    WHERE id = %d', array( $id )
            );

            //checks if the previous query was successful
            if(is_empty( $getPM )){
                hmsgDie('FAIL', 'There was no information for us to pull on that Private Message');
            }

            $getPMInfo = $this->objSQL->getLine(
                    'SELECT pm_id, uid, timestamp
                        FROM $Ppm_extras
                        WHERE pm_id = %d', array( $id )
            );

            if(is_empty( $getPMInfo )){
                hmsgDie('FAIL', 'There was no information for us to pull on that Private Message');
            }

            $this->objTPL->assign_vars(array(
                'title'         =>  $getPM['subject'],
                'message'       =>  $getPM['message'],
                'sent'          =>  $getPMInfo['timestamp'],
                'last_updated'  =>  $getPM['last_updated'],
                'involved'      =>  $getPM['involved']
            ));

            $this->objTPL->parse('body', false);

        //If HTTP_POST === true
        } else {
            return true;
        }
    }

    /**
     * Checks whether a user in involved in the PM convorsation
     *
     * @author  DarkMantis
     * @since   1.0
     *
     * @version 1.0
     *
     * @param   int $user_id
     * @param   int $pm_id
     *
     * @return bool
     */
    public function isInvolved( $user_id, $pm_id ){
        if( is_empty( $user_id ) || is_empty( $pm_id ) ){
            return false;
        }

        $sql = $this->objSQL->getLine( '
            SELECT involved
                FROM $Ppm
                WHERE pm_id = %d', array( $pm_id ));

        if( is_empty( $sql )){
            return false;
        }

        $users = json_decode( $sql );

        foreach( $users as $user ){
            if( $user == $user_id ){
                return true;
            }
        }


        return false;
    }

    /**
     * Checks whether a user is the owner of the original PM
     *
     * @author  DarkMantis
     * @since   1.0
     *
     * @param   int     $convo_ID
     * @param   int     $user_ID
     *
     * @return  bool
     */
    public function isOwner( $convo_ID, $user_ID ){
        foreach( func_get_args() as $arg ){
            if( is_empty( $arg ) ){
                return false;
            }
        }

        $user = $this->objSQL->getLine(
            'SELECT author
                FROM $Ppm
                WHERE pm_id = %d',
            array( $convo_ID ));

        if( $user['author'] != $user_ID ){
            return false;
        }

        return true;
    }


    /**
     * Grab all the information about the Private Message and outputs the convorsation
     *
     * @author  DarkMantis
     * @since   1.0
     *
     * @version 1.0
     *
     * @param   int $pm_id
     *
     * @return  boolean
     */
    public function grabConvoInfo( $pm_id ){
        //make sure PM ID exists & grab info
        $selectPm = $this->objSQL->getLine(
            'SELECT pm_id
                FROM $Ppm_info
                WHERE pm_id = %d', array(
            $pm_id
        ));

        if( !is_empty( $selectPm ) ){
            if( $this->isInvolved( $this->objUser->grab( 'id' ), $pm_id ) ){

                //Edit this to use pm_id and privMsg instead of userID
                $getPMInfo = $this->objSQL->getTable(
                    'SELECT pm.*, info.read, info.deleted, l.subject as lSubject, l.message as lMessage, l.creator as lCreator
                    FROM $Ppm AS pm
                        LEFT JOIN $Ppm as l
                            ON l.id = (SELECT id FROM $Ppm WHERE parent_id = pm.id ORDER BY last_updated DESC LIMIT 1)
                        LEFT JOIN $Ppm_extras AS info
                            ON pm.id = info.pm_id
                        WHERE info.pm_id = "%d"
                            AND info.deleted = 0
                    GROUP BY pm.id
                    ORDER BY l.last_updated ASC, pm.last_updated ASC', array(
                        $pm_id
                    )
                );

                if( !is_empty( $getPMInfo ) ){

                    // assign it to an array and return the array OR set it to template vars?
                    $this->objTPL->set_filenames(array(
                        'body'  =>  'theTemplateName.tpl'
                    ));

                    foreach( $getPMInfo as $pmInfo ){

                        //Assign normal vars for the original message, then below will be all replies to it (block vars).
                        $this->objTPL->assign_block_vars( array(
                            'SUBJECT'       =>  $pmInfo['subject'],
                            'AUTHOR'        =>  $pmInfo['author'],
                            'TIMESTAMP'     =>  $pmInfo['timestamp'],
                            'INVOLVED'      =>  json_decode( $pmInfo['involved'] ),
                            'MSG.MESSAGE'   =>  $pmInfo['message'],
                            'MSG.AUTHOR'    =>  $pmInfo['author'],
                            'MSG.READTS'    =>  $pmInfo['read_timestamp'],
                            'MSG.COLOR'     =>  $pmInfo['color']
                        ));
                    }

                    //Parse the body
                    $this->objTPL->parse( 'body', false );
                } else {
                    hmsgDie( 'INFO', 'That Private Message was empty or did not exist.' );
                }
            } else {
                hmsgDie('INFO', 'Permission Denied: You are not part of this convorsation.');
            }
        } else {
            return false;
        }
        return true;
    }


    /**
     * Like the forum quick reply function, the user can just perform a quick reply
     *
     * @author  DarkMantis
     * @since   1.0
     *
     * @version 1.0
     *
     * @param   int $id
     *
     * @return boolean
     */
    public function quickReply( $id ){

    }


    /**
     * Adds a recipient to a convorsation in the Private Messages
     *
     * @author  DarkMantis
     * @since   1.0
     *
     * @version 1.0
     *
     * @param   int     $convo_ID
     * @param   int     $user_ID
     *
     * @return  bool
     */
    public function addRecipient( $convo_ID, $user_ID ){
        foreach( func_get_args() as $arg ){
            if( is_empty( $arg ) ){
                return false;
            }
        }


        //Not 100% sure about this check
        if( !$this->isOwner( $convo_ID, $user_ID ) || ( !$this->isOwner( $convo_ID, $this->currentUID ) && $user_ID != $this->currentUID ) ){
            return false;
        }

        //Gets the current involved lists
        $select = $this->objSQL->getLine(
            'SELECT involved
                FROM $Ppm_info
                WHERE pm_id = %d',
                array($convo_ID ));

        if( is_empty( $select )){
            return false;
        }

        $users = json_decode( $select );
        $count = count( $users );
        $users[] = $user_ID;

        if( count( $users ) <= $count && !in_array( $user_ID, $users ) ){
            return false;
        }

        $updateUsers = $this->objSQL->updateRow( '$Ppm_info', json_encode( $users ), 'parent_id=' . $convo_ID );

        if( !$updateUsers ){
            return false;
        }

        return true;
    }


    /**
     * Removes a recipient to a convorsation in the Private Messages
     *
     * @author  DarkMantis
     * @since   1.0
     *
     * @version 1.0
     *
     * @param   int     $convo_ID
     * @param   int     $user_ID
     *
     * @return  bool
     */
    public function removeRecipient( $convo_ID, $user_ID ){
        foreach( func_get_args() as $arg ){
            if( is_empty( $arg ) ){
                return false;
            }
        }

        //Gets the current involved lists
        $select = $this->objSQL->getLine(
            'SELECT involved
                FROM $Ppm_info
                WHERE pm_id = %d',
                array($convo_ID ));

        if( is_empty( $select )){
            return false;
        }


        //Not 100% sure about this check
        if( !$this->isOwner( $convo_ID, $user_ID ) || ( !$this->isOwner( $convo_ID, $this->currentUID ) && $user_ID != $this->currentUID ) ){
            return false;
        }

        $decodedUsers = json_decode( $select );

        if( in_array( $user_ID, $decodedUsers ) ){
            foreach( $decodedUsers as $user ){
                //check whether
                if( $user == $user_ID ){
                    unset( $user );
                }

                //Recompile the array
                $decodedUsers[] = $user;
            }
        }

        $updateUsers = $this->objSQL->updateRow( '$Ppm_info', json_encode( $decodedUsers ), 'parent_id=' . $convo_ID );

        if( !$updateUsers ){
            return false;
        }

        return true;
    }
}

?>