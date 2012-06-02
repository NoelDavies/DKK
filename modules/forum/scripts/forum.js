function toggleMenu(id){
    var img = $('img_'+id);
    var imgMode = img.readAttribute('data-mode');

    img.writeAttribute({
        'src': '/'+THEME_ROOT+'buttons/'+(imgMode==0 ? 'minimize' : 'maximize')+'.png',
        'data-mode': imgMode==0 ? '1' : '0'
    });

    new Effect.toggle($('f_'+id), 'slide');
    updateCats();
}

function grabState(){
    var str = ''; var count = 0;
    $$('img[data-mode]').each(function(img) {
        and = count++==0 ? '' : '&';
        mode = img.readAttribute('data-mode')==0 ? 0 : 1;
        str = str+and+img.readAttribute('name')+'='+mode;
    });
    return str;
}

function updateCats(){
    if(!User.IS_ONLINE){ return; }
    new Ajax.Request('/'+cmsROOT+'modules/forum/ajax/sortForum.php', {
        method: 'post',
        parameters: {
            order: Sortable.serialize('sortable_forums'),
            state: grabState()
        }
    });
}

function doPreview(){
    new Ajax.Updater('preview', '/'+cmsROOT+'modules/forum/preview/?ajax', {
        method: 'post', 
        parameters: { post_val: $F('post') },
        onSuccess: function(){
            togglePreview();
            setTimeout(function () { updateDimensions(); } , 550);
        }
    });
}

function togglePreview(){
    $('preview', 'post-content').invoke('toggle');
}

function quotePost(id){
    new Ajax.Request('/'+cmsROOT+'modules/forum/ajax/quote.php?id='+id, {
        method: 'get',
        onSuccess: function(t) {
            textarea = $$('textarea')[0];
            textarea.value = textarea.value+ t.responseText;
            Effect.ScrollTo(textarea.id);
        }
    });
}

function post_eip(id){
    var post = $("post_id_"+id);
    if(post.readAttribute("editInProgress") === null){ post.writeAttribute({"editInProgress": "false"}); }
    if(post.readAttribute("editInProgress") == "true"){ post.editor.enterEditMode("click"); }
    if(post.readAttribute("editInProgress") == "false"){
        post.writeAttribute({"editInProgress": "true"});
        post.editor = new Ajax.InPlaceEditor(post.id, '/'+cmsROOT+'modules/forum/ajax/eip.php?action=save&id='+id, {
            rows: 15,
            cancelControl: "button",
            highlightcolor: Page.row_color1,
            highlightendcolor: Page.row_color2,
            loadTextURL: '/'+cmsROOT+'modules/forum/ajax/eip.php?action=load&id='+id
        });
        post.editor.enterEditMode("click");
    }
}

document.observe('dom:loaded', function(){
    if(User.IS_ONLINE && $('sortable_forums')){
        Sortable.create('sortable_forums', {scroll:window, tag:'div', handle: 'cat_handle',
            onUpdate: function () {
                updateCats();
            }
        });
    }

    if(User.IS_ONLINE){
        $$('img[data-mode]').each(function(img) {
            img.writeAttribute({'onclick': 'toggleMenu(this.name);'});
        });

        //smilies
        $$('input[data-code][class*=smilie]').each(function(img) {
            img.writeAttribute({'onclick': 'emoticon(this); return false;'});
        });

        //bb buttons
        $$('input[class*=bbButton]').each(function(e){
            e.writeAttribute({'onclick': 'doBBCode(this); return false;'});
        });

        //EIP buttons
        $$("a[class=editBtn]").each(function(ele){
            Event.observe(ele, 'click', function(e){
                Event.stop(e);
                post_eip(str_replace("post_", "", ele.id));
            });
        });

        if($('qreply')){
            makeReplyForm('qreply');
            Event.observe('qreply', 'submit', function(e){
                $('qreply').request({
                    onFailure: function() { return true; },
                    onSuccess: function(t) {
                        $$('#qreply textarea')[0].update('');
                        $('post_container').insert(t.responseText);
                        Effect.ScrollTo($$('table[name*=post_]').last().readAttribute('id'));
                    }
                });
                Event.stop(e);
            });
        }
    }

    //make all td's with data-url's on em clickable
    $$('td[data-url]').each(function(ele) {
        var url = ele.readAttribute('data-url');

        if(!empty(url)){
            ele.writeAttribute({
                onclick: 'document.location = "'+url+'";',
                style: 'cursor: pointer'
            });
        }
    });
});
