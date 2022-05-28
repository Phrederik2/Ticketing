
function addlistener(event){
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

