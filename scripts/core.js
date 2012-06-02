// Setup the dynamic Grid Sys
var ADAPT_CONFIG = {
  // Where is your CSS?
  path: '/'+cmsROOT+'images/',

  /* false = Only run once, when page first loads.
   * true = Change on window resize and page tilt.
   */
  dynamic: true,

  /* First range entry is the minimum.
   * Last range entry is the maximum.
   * Separate ranges by "to" keyword.
   */
  range: [
    '0px to 760px = grid.mobile.css',
    '760px to 980px = grid.720.css',
    '980px to 1280px = grid.960.css',
    '1280px to 1600px = grid.1200.css',
    '1600px to 1920px = grid.1560.css',
    '1920px = grid.fluid.css'
  ]
};
var ignoreAjax = false; //switch for the ajax spinner, the notifications shouldnt generate a spinner now


function updateClock(){
    if(!$('clock')){ return; }
    $('clock').update(date('l H:i:s a', time())).writeAttribute('title', date('jS F Y', time()));
    setTimeout(updateClock, 1000);
}

function inBetween($begin, $end, $contents) {
    $pos1 = strpos($contents, $begin);
    if($pos1 !== false){
        $pos1 += strlen($begin);
        $pos2 = strpos($cotents, $end, $pos1);
        if($pos2 !== false){
            $substr = substr($contents, $pos1, $pos2 - $pos1);
            return $substr;
        }
    }
    return false;
}

function inWindow(url, title, width, height){
    var title = title || '';
    var url = url || '';
    var width = width || 400;
    var height = height || window.viewport.height-200;

    if(empty(url)){
        return false;
    }

      myLightWindow.activateWindow({
           href:    url,
           title:   title,
           width:   width > window.viewport.width ?  window.viewport.width : width,
           height:  height > window.viewport.height ? window.viewport.height : height
      });
    return false;
}

function notify(message, header, sticky){
    growler.growl(message, {
        header: header || "",
        sticky: Boolean(sticky),
        created: function(){ ignoreAjax = true; },
        destroyed: function(){ ignoreAjax = false; }
    });
}

window.viewport = {
    height: function() {
        return document.viewport.getHeight();
    },

    width: function() {
        return document.viewport.getWidth();
    },

    scrollTop: function() {
        return $(window).scrollTop();
    },

    scrollLeft: function() {
        return $(window).scrollLeft();
    }
};

ResizeableTextarea = Class.create();
ResizeableTextarea.prototype = {
    initialize: function(element, options) {
        this.element = $(element);
        this.size = parseFloat(this.element.getStyle('height') || '100');
        this.options = Object.extend({
            inScreen: true,
            resizeStep: 10,
            minHeight: this.size
        }, options || {});
        Event.observe(this.element, "keyup", this.resize.bindAsEventListener(this));
        if ( !this.options.inScreen ) {
            this.element.style.overflow = 'hidden';
        }
        this.element.setAttribute("wrap","virtual");
        this.resize();
    },
    resize : function(){
        this.shrink();
        this.grow();
    },
    shrink : function(){
        if ( this.size <= this.options.minHeight ){
            return;
        }
        if ( this.element.scrollHeight <= this.element.clientHeight) {
            this.size -= this.options.resizeStep;
            this.element.style.height = this.size+'px';
            this.shrink();
        }
    },
    grow : function(){
        if ( this.element.scrollHeight > this.element.clientHeight ) {
            if ( this.options.inScreen && (20 + this.element.offsetTop + this.element.clientHeight) > document.body.clientHeight ) {
                return;
            }
            this.size += (this.element.scrollHeight - this.element.clientHeight) + this.options.resizeStep;
            this.element.style.height = this.size+'px';
            this.grow();
        }
    }
}

function makeReplyForm(formId){
    formArea = $$('#'+formId)[0];
    txtArea = $$('#'+formId+' textarea')[0];
    sendButton = $$('#'+formId+' #submit')[0];

    var show = function(){
        sendButton.show();
        formArea.addClassName('row_color2');
    }

    var hide = function(){
        if(empty(txtArea.value)){
            sendButton.hide();
        }
        formArea.removeClassName('row_color2');
    }

    txtArea.observe('focus', show);
    txtArea.observe('blur', hide);
    txtArea.observe('init:blur', hide);
    txtArea.fire('init:blur');

    formArea.observe('click', function(){ txtArea.focus(); });
}

function updateDimensions(){
    $$('img[class="bbcode_img"]').each(function (ele){
        var needed = {width: 500};
        var curImg = $(ele).getDimensions();
        if(curImg.width > needed.width){
            var newWidth = 328;
            var scaleFactor = newWidth/curImg.width;
            var newHeight = scaleFactor*curImg.height;
            $(ele).writeAttribute({width: newWidth, height: newHeight});
            $(ele).wrap('a', {'href': $(ele).readAttribute('src'), 'class': 'lightwindow', 'title': 'Resized Image: Click to open fullscreen'});
        }
    });
}

function spinnerMove(e){
    var spinner = $('spinner_');
    if(!isset(spinner)){
        Event.stopObserving(document, 'mousemove', spinnerMove);
        return;
    }
    
    mouseX = Event.pointerX(e);
    mouseY = Event.pointerY(e);
    spinner.setStyle({'top': (mouseY-10)+'px', 'left': (mouseX+10)+'px', 'position': 'fixed'});    
}

//keep a div updated at the mouse pointed, this will be shown if we fire an ajax event
Ajax.Responders.register({
    onCreate: function(){ 
        if(!ignoreAjax){
            $('spinner_').show();
        }
    },
    onComplete: function(){ 
        if(!ignoreAjax){
            $('spinner_').hide(); 
        }
        ignoreAjax = false;
    },
});

document.observe('dom:loaded', function(){
    $$('textarea').each(function (txtarea){
        if(!txtarea.hasClassName('noResize')){
            txtarea.onkeyup = new ResizeableTextarea(txtarea);
        }

        //if(!txtarea.hasClassName('noTab')){
        //    txtarea.writeAttribute('onkeydown', 'return catchTab(this, event)');
        //}
    });
    updateDimensions();
    Event.observe(document, 'mousemove', spinnerMove);

    if($('clock')){ updateClock(); }
});

function getDataAttributes(ele){
    var list = [];

    var prefix = 'data-';
    var attr = $(ele).attributes;

    var attributes = {};
    for(var key in attr) {
        if(isNaN(key)){ continue; }

        key = attr[key];
        if(typeof key != 'object'){ continue; }

        if(!prefix || !empty(key.nodeName) && substr(key.nodeName, 0, prefix.length) == prefix){
            attributes[substr(key.nodeName, prefix.length)] = key.nodeValue;
        }
    }
    list.push(attributes);
        
    return attributes;
}

//+ Jonas Raoni Soares Silva
//@ http://jsfromhell.com/forms/selection [rev. #1]
Selection = function(input){
    this.isTA = (this.input = input).nodeName.toLowerCase() == "textarea";
};
with({o: Selection.prototype}){
    o.setCaret = function(start, end){
        var o = this.input;
        if(Selection.isStandard)
            o.setSelectionRange(start, end);
        else if(Selection.isSupported){
            var t = this.input.createTextRange();
            end -= start + o.value.slice(start + 1, end).split("\n").length - 1;
            start -= o.value.slice(0, start).split("\n").length - 1;
            t.move("character", start), t.moveEnd("character", end), t.select();
        }
    };
    o.getCaret = function(){
        var o = this.input, d = document;
        if(Selection.isStandard)
            return {start: o.selectionStart, end: o.selectionEnd};
        else if(Selection.isSupported){
            var s = (this.input.focus(), d.selection.createRange()), r, start, end, value;
            if(s.parentElement() != o)
                return {start: 0, end: 0};
            if(this.isTA ? (r = s.duplicate()).moveToElementText(o) : r = o.createTextRange(), !this.isTA)
                return r.setEndPoint("EndToStart", s), {start: r.text.length, end: r.text.length + s.text.length};
            for(var $ = "[###]"; (value = o.value).indexOf($) + 1; $ += $);
            r.setEndPoint("StartToEnd", s), r.text = $ + r.text, end = o.value.indexOf($);
            s.text = $, start = o.value.indexOf($);
            if(d.execCommand && d.queryCommandSupported("Undo"))
                for(r = 3; --r; d.execCommand("Undo"));
            return o.value = value, this.setCaret(start, end), {start: start, end: end};
        }
        return {start: 0, end: 0};
    };
    o.getText = function(){
        var o = this.getCaret();
        return this.input.value.slice(o.start, o.end);
    };
    o.setText = function(text){
        var o = this.getCaret(), i = this.input, s = i.value;
        i.value = s.slice(0, o.start) + text + s.slice(o.end);
        this.setCaret(o.start += text.length, o.start);
    };
    new function(){
        var d = document, o = d.createElement("input"), s = Selection;
        s.isStandard = "selectionStart" in o;
        s.isSupported = s.isStandard || (o = d.selection) && !!o.createRange;
    };
}