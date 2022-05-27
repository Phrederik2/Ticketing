<?php
script('ticketing', 'script');
style('ticketing', 'style');
include_once ('/var/www/html/nextcloud/apps/ticketing/templates/TicketingTemplate/index.php');

echo '<div id="app">';

echo '['.$_SESSION['u'].']';
/*echo 'Baselink:'.Tool::baselink().'<br>';

echo '$_SERVER:<br>';
foreach($_SERVER as $k=>$v){
    echo '['.$k.']=>['.$v.']<br>';
}

echo '$__REQUEST:<br>';
foreach($_REQUEST as $k=>$v){
    echo '['.$k.']=>['.$v.']<br>';
}



//var_dump($_SERVER);*/
/*
echo '$_POST:<br>';
foreach($_POST as $k=>$v){
    echo '['.$k.']=>['.$v.']<br>';
}
*/


$c = new customer_Frame();


echo $c->toString();
	
echo '</div>';

?>