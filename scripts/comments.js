//hide the comment x's and show only on hover
function doXs(){
    $$('#comments .comment').invoke('observe', 'mouseover', function(){
        $(this).down('#btnRM').show();
    }).invoke('observe', 'mouseout', function(){
        $(this).down('#btnRM').hide();
    });
    
    $$('#comments .comment #btnRM').each(function(e){
        e.hide();
        e.setAttribute('onClick', 'return rmEle(this);');
    });
}

function rmEle(e){
    id = e.readAttribute('cmntId');
    url = str_replace([location.search, location.hash], '', location.href);
    new Ajax.Request(url+'?mode=ajDelComment&id='+id, { method: 'post',
        onComplete: function(transport) {
            switch(transport.responseText){
                case -1:
                    growl('The comment you tried to delete could not be found.', 'Comment System');
                    sleep(2);
                break;
                case 0:
                    growl('The comment deletion was unsuccessful.', 'Comment System');
                    sleep(2);
                break;
                
                default:
                    Effect.BlindUp('comment-'+id, { duration: 1.0 });
                    setTimeout('$(\'comment-'+id+'\').remove(); growl(\'The comment deletion was successful.\', \'Comment System\');', 2000);
                    return false;
                break;
            }
        }
    });
    return false;
}

function submitComment(){
    url = str_replace([location.search, location.hash], '', location.href);
    new Ajax.Request(url+'?mode=ajPostComment', {
        method: 'post', parameters: {'comment': $F('comment'), 'sessid': $F('sessid'), 'module': $F('module'), 'url': url},
        onComplete: function(transport) {
            switch(transport.responseText){
                case 0:
                    growl('There was an error submitting the form, please refresh and try again.', 'Comment System');
                    sleep(2);
                break;
                case 1:
                    growl('Your Comment was not submitted, please try again.', 'Comment System');
                    sleep(2);
                break;
                
                default:
                    $('commentError').hide();
                    $('commentContents').insert({bottom: transport.responseText});
                    $('comment').setValue('').blur(); doXs();
                    return false;
                break;
            }
        }
    });
    return false;
}



document.observe('dom:loaded', function(){
    //make sure the JS loads for the replybox :D
    makeReplyForm('replybox', 'Click to comment...');
    
    //watch the submit button for a click and handle it via ajax cause wer nice
    $$('#comments form')[0].setAttribute('onSubmit', 'return submitComment();');
    
    doXs();
});
