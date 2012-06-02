function doBBCode(ele){
    var textarea = $$('textarea')[0];
    var code = ele.readAttribute('data-code');
    if(empty(code)){ return false; }

    var check = code.indexOf('|');
    if(check == -1){
        addText(textarea, code, code);
    }else{
        var code = explode('|', code);
        addText(textarea, code[0], code[1]);
    }
    return false;
}

function addText(ele, txt1, txt2) {
    textarea = $(ele);
    var selection = new Selection(textarea);
    var focus = {x:0, y:0};

    if(document.selection){
        var str = txt1 + selection.getText() + txt2;
    } else if (textarea.selectionStart || textarea.selectionStart == '0') {
        var value = textarea.getValue();

        var str = value.substring(0, textarea.selectionStart);
            if(textarea.selectionStart!=0 || textarea.selectionEnd!=0){
                focus.x = strlen(str+txt1);
            }else{
                focus.x = focus.y = strlen(str+txt1);
            }
        str += txt1 + selection.getText();
            if(textarea.selectionStart!=0 || textarea.selectionEnd!=0){
                focus.y = strlen(str);
            }
        str += txt2+ value.substring(textarea.selectionEnd, strlen(value));
    }

    textarea.value = str;
    if(focus!=0){
        selection.setCaret(focus.x, focus.y);
        textarea.focus();
    }
    return false;
}

function emoticon(ele) {
    var textarea = $$('textarea')[0];
    addText(textarea, ' '+(ele.readAttribute('data-code')), ' ');
    return false;
}

