document.observe('dom:loaded', function(){
    $$('ul#tabs li a').each(function(ele){
        ele.observe('click', function(e){ ajaxUpdate(e, ele); });
    });

    document.scrollTo('panel_tabs');
});

function ajaxUpdate(e, ele){
    e.preventDefault();
    new Ajax.Updater('contents', ele.href, {method: 'post', onComplete: function(){
        $$('#contents a').each(function(ele){
            ele.observe('click', function(e){ ajaxUpdate(e, ele); });
        });
    }});
}
