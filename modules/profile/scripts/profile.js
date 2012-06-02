//contactInfo buttons
$$('a.hoverWatch').invoke('observe', 'mouseover', function() {
    $('lastLoggedIn').toggle();
    $('hoverInfo').toggleClassName('ico').addClassName($(this).readAttribute('ico')).update(this.title).toggle();
});     
$$('a.hoverWatch').invoke('observe', 'mouseout', function() {
    $('lastLoggedIn').toggle();
    $('hoverInfo').toggleClassName('ico').removeClassName($(this).readAttribute('ico')).update('&nbsp;').toggle();
});

document.observe('dom:loaded', function(){
    var tabs = new tabset('tabContents');
    tabs.autoActivate($('tab_bio'));
});