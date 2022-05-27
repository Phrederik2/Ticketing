

//$('input[name="F26b42c30c06ac6c80adea3219aaebd5a1136d8ea_Form_DataViewer2_Customer_26faea88eaa35bcb567e7a3f68388f87685b49ce_Option"]').val('EDIT_1');


$('[Form-Action]').on('click',function () {
    
    var nameO = $(this).attr('LinkOption');
    $('[name="' + nameO + '"]').attr('value',$(this).attr('Form-Action'));
    
    var nameF = $(this).attr('LinkForm');
    $('[name="' + nameF + '"]').submit();
});




//$('#F26b42c30c06ac6c80adea3219aaebd5a1136d8ea_Form_DataViewer2_Customer_26faea88eaa35bcb567e7a3f68388f87685b49ce').submit();