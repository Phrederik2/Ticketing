<?php
script('ticketing', 'script');
style('ticketing', 'style');


echo '<div id="app">';
include_once ('/var/www/html/nextcloud/apps/ticketing/templates/TicketingTemplate/index.php');


//var_dump($_SERVER);
$c = new customer_Frame();


echo $c->toString();
	
echo '</div>';

?>