<?php
script('ticketing', 'script');
script('ticketing', 'assets2/ON-OFF-Toggle-Switches-Switcher/js/jquery.switcher');
script('ticketing', 'assets2/bootstrap/js/bootstrap.min');




style('ticketing', 'style');
style('ticketing', 'switcher');
style('ticketing', 'jqueryui');
include_once ('/var/www/html/nextcloud/apps/ticketing/templates/TicketingTemplate/index.php');

?>