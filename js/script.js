
function addlistener(event){
    $('[Form-Action-'+event+']').on(event,function () {
        
        if($(this).attr('LinkOption')!==undefined){
            var nameO = $(this).attr('LinkOption');
            $('[name="' + nameO + '"]').attr('value',$(this).attr('Action'));
        }
       
        if($(this).attr('LinkForm')!==undefined){
            var nameF = $(this).attr('LinkForm');
            $('[name="' + nameF + '"]').submit();
        }
       
    });
}
addEventListener('click');
addEventListener('change');

$(function(){ 
    // $.switcher(); 
    $.switcher('.ONOFF');
  }); 

