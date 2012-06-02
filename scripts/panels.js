document.observe('dom:loaded', function(){
    var panels = {'user':1};
    if(User.IS_MOD){     var panels = {'user':2, 'mod':1}; }
    if(User.IS_ADMIN){     var panels = {'user':3, 'mod':2, 'admin':1}; }

    accordion = new Accordion("accNav", panels[explode('/', location.href)[4]]);
});