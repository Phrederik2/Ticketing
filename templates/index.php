<?php
script('ticketing', 'script');
style('ticketing', 'style');

include_once ('TicketingTemplate/BASE/before.php');
echo '<div id="app">';

$c = new customer_Frame();


echo $c->toString();
	
echo '</div>';

?>