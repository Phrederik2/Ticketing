
<?php

$title=".::Ticketing::.";
if(isset($GLOBALS['Title']) and $GLOBALS['Title']!=''){
  $title = ".::T::".$GLOBALS['Title']."::.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo $title  ?></title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta content="width=device-width, initial-scale=1" name="viewport" />
    <meta content="" name="description" />
    <meta content="" name="author" />
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700&subset=all" rel="stylesheet" type="text/css"/>
    <link href="assets2/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets2/bootstrap/css/components-rounded.min.css" rel="stylesheet" id="style_components" type="text/css" />
    
	  <link href="assets2/ON-OFF-Toggle-Switches-Switcher/css/switcher.css" rel="stylesheet" type="text/css">

		<link href="BASE/_another.css" rel="stylesheet" type="text/css" />
	
	  <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="assets2/resources/demos/style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.1/css/responsive.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/3.2.6/css/fixedColumns.dataTables.min.css ">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="assets2/resources/demos/style.css">
    <link rel="stylesheet" type="text/css" href="assets2/Auto-hiding-Notification-jQuery-notice/docs/assets/css/notice.css" />
    
 
  <script src="https://code.jquery.com/jquery-3.6.0.js"></script>
  <script src="https://code.jquery.com/ui/1.13.0-alpha.1/jquery-ui.js"></script>
  <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
  <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
  
  
  <script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script> 
  <script type="text/javascript" language="javascript" src="https://cdn.datatables.net/scroller/1.5.1/js/dataTables.scroller.min.js "></script> 
  <script type="text/javascript" language="javascript" src="https://cdn.datatables.net/fixedcolumns/3.2.6/js/dataTables.fixedColumns.min.js "></script> 
  <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/javascript" language="javascript" src="librairies/floatThead/dist/jquery.floatThead.js"></script>
<script type="text/javascript" language="javascript" src="librairies/floatThead/dist/jquery.floatThead.min.js"></script>
<script type="text/javascript" src="assets2/Auto-hiding-Notification-jQuery-notice/docs/assets/js/jquery.notice.js"></script>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-beta.1/dist/js/select2.min.js"></script>

<link rel="stylesheet" href="assets2/Trumbowyg/dist/ui/trumbowyg.min.css">
<script src="assets2/Trumbowyg/dist/trumbowyg.min.js"></script>
<script src="assets2/Trumbowyg/dist/plugins/base64/trumbowyg.base64.min.js"></script>
<link rel="stylesheet" href="assets2/Trumbowyg/dist/plugins/colors/ui/trumbowyg.colors.min.css">
<script src="assets2/Trumbowyg/dist/plugins/colors/trumbowyg.colors.min.js"></script>
<script src="assets2/Trumbowyg/dist/plugins/fontfamily/trumbowyg.fontfamily.min.js"></script>
<script src="assets2/Trumbowyg/dist/plugins/fontsize/trumbowyg.fontsize.min.js"></script>
<script src="assets2/Trumbowyg/dist/plugins/history/trumbowyg.history.min.js"></script>
<script src="assets2/Trumbowyg/dist/plugins/preformatted/trumbowyg.preformatted.min.js"></script>
<script src="//rawcdn.githack.com/RickStrahl/jquery-resizable/0.35/dist/jquery-resizable.min.js"></script>
<script src="assets2/Trumbowyg/dist/plugins/resizimg/trumbowyg.resizimg.min.js"></script>
<script src="assets2/Trumbowyg/dist/plugins/table/trumbowyg.table.min.js"></script>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

	<script>

	  $( function() {

		$( ".datepicker" ).datepicker({
			dateFormat: "yy-mm-dd", 
			firstDay: 1
			});

	  } );
	  
	  $( function() {

		$( ':input[type=date]' ).datepicker({
			dateFormat: "yy-mm-dd", 
			firstDay: 1
			});

	  } );
	  
	  
	$(function(){ 
	  // $.switcher(); 
	  $.switcher('.ONOFF');
	}); 

 

  </script>

  <link rel="stylesheet" href="assets2/resources/demos/style.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.4.0/Chart.min.js"></script>

    
</head>

    
<body>

<p></p>
  <div id="page">
  <div id="table">
         <div id="View">
