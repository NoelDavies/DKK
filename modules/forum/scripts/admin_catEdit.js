document.observe('dom:loaded', function(){
    if($('quick_permsC')){ $('quick_permsC').show(); }
});

$('quick_perms').observe('change', function(){
    var val = $('quick_perms').getValue();
        if(val == 0){ return; }

    parts = val.split(',');
        if(parts.length != 9){ return; }

    var i = 0;
    $$('select[name*=auth_]').each(function(input){
        $(input).value = parts[i];
        i++;
    });
});
