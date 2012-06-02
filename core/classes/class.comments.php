<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){ die('Error: Cannot access directly.'); }

class comments extends coreClass {

    /**
     * Set the comments up, this will handle the post form also.
     *
     * @version     1.0
     * @since       0.8.0
     */
    public function start($tplVar=NULL, $paginationVar, $module, $module_id, $perPage=10, $author_id=0){
        $this->paginationVar    = $paginationVar;
        $this->module           = $module;
        $this->module_id        = $module_id;
        $this->perPage          = $perPage;
        $this->author_id        = $author_id;

        // TODO: check this out...not sure if it will work ...properly with the new getQueryString
        $this->aURL = explode('?', $this->config('global', 'fullPath'));
        parse_str($this->aURL[1], $vars);
        $this->fURL = $this->getQueryString($this->aURL[0], $vars, array('mode', 'id'));

        if($tplVar!==NULL){
            $this->getComments($tplVar);
        }
    }

    /**
     * Inserts a comment into the database
     *
     * @version     1.0
     * @since       1.0.0
     * @autor       xLink
     *
     * @param       string  $module         The module name
     * @param       int     $module_id      The Unique ID of the content
     * @param       int     $author         The comment author's UID
     * @param       string  $comment        The comment's content
     *
     * @return      int     mysql_inserted_id()
     */
    function insertComment($module, $module_id, $author, $comment){
        unset($array);
        $array['module']        = $module;
        $array['module_id']     = $module_id;
        $array['author']        = $author;
        $array['comment']       = secureMe($comment);
        $array['timestamp']     = time();

        $log = 'Comments System: '.$this->objUser->profile($this->objUser->grab('id'), RAW).' commented on <a href="'.$this->aURL[1].'">this</a>.';
        return $this->objSQL->insertRow('comments', $array, $log);
    }

    /**
     * Grabs all avalible comments for the requested module and id
     *
     * @version     1.0
     * @since       1.0.0
     * @autor       xLink
     *
     * @param       string  $tplVar
     */
    function getComments($tplVar){
        //set the template for the comments
        $this->objTPL->set_filenames(array(
            'comments'  =>  'modules/core/template/comments/viewComments.tpl'
        ));

        if(User::$IS_ONLINE){
            $dontShow = false;
            switch($_GET['mode']){
                case 'postComment':
                    if(HTTP_POST){
                        if(doArgs('comment_'.$this->module_id, false, $_SESSION[$this->module]) != $_POST['sessid']){
                            msg('FAIL', 'Error: Cant remember where you wer posting to.', '_ERROR');
                        }else{
                            $comment = $this->insertComment($this->module, $this->module_id, $this->objUser->grab('id'), $_POST['comment']);
                            if(!mysql_affected_rows()){
                                msg('FAIL', 'Error: Your comment wasnt posted, please try again.', '_ERROR');
                            }
                            unset($_SESSION[$module]);
                        }
                        $dontShow = true;
                    }
                break;

                case 'ajPostComment':
                    if(HTTP_AJAX && HTTP_POST){
                        if(doArgs('comment_'.$this->module_id, false, $_SESSION[$this->module]) != $_POST['sessid']){
                            die('1 <script>console.log('.json_encode(array('comment_'.$this->module_id, $_SESSION[$this->module], $_POST['sessid'], $_POST)).');</script>');
                        }else{
                            $comment = $this->insertComment($this->module, $this->module_id, $this->objUser->grab('id'), $_POST['comment']);
                                if(!mysql_affected_rows()){ die('0'); }

                            echo $this->getLastComment($comment);
                        }
                        exit;
                    }
                break;

                case 'deleteComment':
                    $id = doArgs('id', 0, $_GET, 'is_number');
                    $comment = $this->objSQL->getLine('SELECT * FROM `$Pcomments` WHERE id = "%d"', array($id));
                        if(!$comment){ msg('FAIL', 'Error: Comment not found.', '_ERROR'); break; }

                    //check if user has perms
                    if(User::$IS_ADMIN || User::$IS_MOD ||
                        (User::$IS_ONLINE && ($this->objUser->grab('id')==$comments['author'] || $this->objUser->grab('id')==$this->author_id))){

                        //do teh the delete
                        $log = 'Comments System: '.$this->objUser->profile($this->objUser->grab('id'), RAW).' deleted comment from <a href="'.$this->aURL[1].'">this</a>.';
                        $delete = $this->objSQL->deleteRow('comments', array('id = "%d"', $id), $log);
                        if(!$delete){
                            msg('FAIL', 'Error: The comment was not deleted.', '_ERROR');
                        }else{
                            msg('INFO', 'The comment was successfully deleted.', '_ERROR');
                        }
                    }
                break;

                case 'ajDelComment':
                    if(HTTP_AJAX && HTTP_POST){
                        $id = doArgs('id', 0, $_GET, 'is_number');
                        $comment = $this->objSQL->getLine('SELECT * FROM `$Pcomments` WHERE id = "%d"', array($id));
                            if(!$comment){ die('-1'); }

                        //check if user has perms
                        if(User::$IS_ADMIN || User::$IS_MOD ||
                            (User::$IS_ONLINE && ($this->objUser->grab('id')==$comments['author'] || $this->objUser->grab('id')==$this->author_id))){

                            //do teh the delete
                            $log = 'Comments System: '.$this->objUser->profile($this->objUser->grab('id'), RAW).' deleted comment from <a href="'.$this->aURL[1].'">this</a>.';
                            $delete = $this->objSQL->deleteRow('comments', array('id = "%d"', $id), $log);
                            die((!$delete ? '0' : '1'));
                        }
                    }else{
                        die('-1');
                    }
                    die('0');
                break;
            }

            //make sure the submit form only shows when we want it to
            if(!$dontShow){ $this->makeSubmitForm(); }
        }

        //get a comments count for this module and id
        $commentsCount = $this->getCount();
        $comPagination = new pagination('commentsPage', $this->perPage, $commentsCount);

            //check to see if we have a positive number
            if($commentsCount){
                //now lets actually grab the comments
                $commentsData = $this->objSQL->getTable(
                    'SELECT * FROM `$Pcomments`
                        WHERE module="%s" AND module_id="%d"
                        ORDER BY timestamp ASC
                        LIMIT %s',
                array(
                    $this->module,
                    $this->module_id,
                    $comPagination->getSqlLimit()
                ));

                if(!$commentsData){ //something went wrong
                    msg('INFO', 'Error loading comments.', '_ERROR');
                }else{
                    $this->objTPL->assign_var('COM_PAGINATION', $comPagination->getPagination());
                    $i=0;

                    //assign the comments to the template
                    foreach($commentsData as $comments){
                        $this->objTPL->assign_block_vars('comment', array(
                            'ID'        => $comments['id'],
                            'cID'       => 'comment-'.$comments['id'],
                            'ROW'       => $i%2 ? 'row_color2' : 'row_color1',
                            'ALT_ROW'   => $i%2 ? 'row_color1' : 'row_color2',

                            'AUTHOR'    => $this->objUser->profile($comments['author']),
                            'POSTED'    => $this->objTime->mk_time($comments['timestamp']),
                            'POST'      => contentParse($comments['comment']),
                        ));

                        if(User::$IS_ADMIN || User::$IS_MOD ||
                            (User::$IS_ONLINE && ($this->objUser->grab('id')==$comments['author'] || $this->objUser->grab('id')==$this->author_id))){

                            $this->objTPL->assign_block_vars('comment.functions', array(
                                'URL'   => $this->aURL[0].'?mode=deleteComment&id='.$comments['id'],
                            ));
                        }
                    $i++;
                    }
                }
        }else{
            //we have no comments so output a msg box saying so
            msg('INFO', 'No Comments.', '_ERROR');
        }

        //and then output the comments to the parent template
        $this->objTPL->assign_var_from_handle($tplVar, 'comments');
    }

    function getCount(){
        $this->count = $this->objSQL->getInfo('comments', array('module="%s" AND module_id="%s"', $this->module, $this->module_id));
        return $this->count;
    }

    /**
     * Outputs the submit form for a new comment
     *
     * @version     1.0
     * @since       0.8.0
     */
    function makeSubmitForm(){
        $rand = rand(1, 99);
        $this->objTPL->set_filenames(array(
            'submitCommentsForm_'.$rand => 'modules/core/template/comments/submitComment.tpl'
        ));

        $this->objPage->addJSFile('/'.root().'scripts/comments.js');

        $sessid = $_SESSION[$this->module]['comment_'.$this->module_id] = md5(time().'�');
        $this->objTPL->assign_vars(array(
            'FORM_START'        =>  $this->objForm->start('comments', array('method'=>'POST', 'action'=>$this->aURL[0].'?mode=postComment')),
            'FORM_END'          =>  $this->objForm->finish(),
            'SUBMIT'            =>  $this->objForm->button('submit', 'Submit'),

            'L_SUBMIT_COMMENT'    =>  'Submit a comment:',
            'TEXTAREA'          =>  $this->objForm->textarea('comment', ''),
            'HIDDEN'            =>  $this->objForm->inputbox('sessid', 'hidden', $sessid) .
                                        $this->objForm->inputbox('module', 'hidden', $this->module),
        ));

        //and then output the comments to the parent template
        $this->objTPL->assign_var_from_handle('_NEW_COMMENT', 'submitCommentsForm_'.$rand);
    }

    /**
     * Outputs a comment wrapped in template for ajax purposes
     *
     * @version     1.0
     * @since       0.8.0
     */
    function getLastComment($id){
        //set the template for the comments
        $this->objTPL->set_filenames(array(
            'ajComments'  =>  'modules/core/template/comments/ajaxComments.tpl'
        ));

        $comments = $this->objSQL->getLine($this->objSQL->prepare(
            'SELECT * FROM `$Pcomments` WHERE id = "%d"',
            $id
        ));
            $this->objTPL->assign_block_vars('comment', array(
                'ID'        => $comments['id'],
                'cID'       => 'comment-'.$comments['id'],
                'ROW'       => $i%2 ? 'row_color2' : 'row_color1',
                'ALT_ROW'   => $i%2 ? 'row_color1' : 'row_color2',

                'AUTHOR'    => $this->objUser->profile($comments['author']),
                'POSTED'    => $this->objTime->mk_time($comments['timestamp']),
                'POST'      => contentParse($comments['comment']),
            ));

            if(User::$IS_ADMIN || User::$IS_MOD ||
                (User::$IS_ONLINE && ($this->objUser->grab('id')==$comments['author'] || $this->objUser->grab('id')==$this->author_id))){

                $this->objTPL->assign_block_vars('comment.functions', array(
                    'URL'   => $this->aURL[0].'?mode=deleteComment&id='.$comments['id'],
                ));
            }
        $this->objTPL->parse('ajComments', false);
        return $this->objTPL->get_html('ajComments');
    }
}

?>