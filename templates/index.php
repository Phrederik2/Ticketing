<?php
script('ticketing', 'script');
style('ticketing', 'style');
include_once ('/var/www/html/nextcloud/apps/ticketing/templates/TicketingTemplate/index.php');

echo ' <div id="page">';

echo '['.$_SESSION['u'].']';

$c = new customer_Frame();


echo $c->toString();
	
echo '</div>';

?>