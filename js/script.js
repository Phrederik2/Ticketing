
function addEventListener(event){
    $('[Form-Action-'+event+']').on(event,function () {
        
       launchEvent(this);
       
    });
}

function launchEvent(item){
    if($(item).attr('LinkOption')!==undefined){
        var nameO = $(item).attr('LinkOption');
        $('[name="' + nameO + '"]').attr('value',$(item).attr('Action'));
    }
   
    if($(item).attr('LinkForm')!==undefined){
        var nameF = $(item).attr('LinkForm');
        $('[name="' + nameF + '"]').submit();
    }
}

addEventListener('click');
addEventListener('change');

$(function(){ 
    // $.switcher(); 
    $.switcher('.ONOFF');
}); 

$( function() {
    $( ".tabs" ).tabs();
 } );


 $('textarea[edit=true]').trumbowyg(
    {
        
        semantic: false,
        btns: [
            ['viewHTML'],
            ['undo', 'redo'], 
            ['custom'],
            ['formatting'],
            ['fontfamily'],
            ['fontsize'],
            ['strong', 'em', 'del','underline'],
            ['foreColor', 'backColor'],
            ['link'],
            ['insertImage'],
            ['preformatted'],
            ['justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'],
            ['table'],
            ['unorderedList', 'orderedList'],
            ['horizontalRule'],
            ['removeformat'],
            ['historyUndo', 'historyRedo'],
            ['fullscreen']
            
        ]
    
    }
   );  

