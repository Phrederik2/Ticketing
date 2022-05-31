<?php

script('ticketing', 'assets2/ON-OFF-Toggle-Switches-Switcher/js/jquery.switcher');

//script('ticketing', 'assets2/bootstrap/js/bootstrap.min');
//script('ticketing', 'assets2/bootstrap/js/bootstrap.min');

script('ticketing', 'assets2/Trumbowyg/dist/trumbowyg.min');
script('ticketing', 'assets2/Trumbowyg/dist/plugins/base64/trumbowyg.base64.min');
script('ticketing', 'assets2/Trumbowyg/dist/plugins/colors/ui/trumbowyg.colors.min');
script('ticketing', 'assets2/Trumbowyg/dist/plugins/fontfamily/trumbowyg.fontfamily.min');
script('ticketing', 'assets2/Trumbowyg/dist/plugins/fontsize/trumbowyg.fontsize.min');
script('ticketing', 'assets2/Trumbowyg/dist/plugins/history/trumbowyg.history.min');
script('ticketing', 'assets2/Trumbowyg/dist/plugins/preformatted/trumbowyg.preformatted.min');
script('ticketing', 'assets2/Trumbowyg/dist/plugins/resizimg/trumbowyg.resizimg.min');
script('ticketing', 'assets2/Trumbowyg/dist/plugins/table/trumbowyg.table.min');

script('ticketing', 'script');


style('ticketing', 'style');
style('ticketing', 'switcher');
style('ticketing', 'trumbowyg');
style('ticketing', 'trumbowyg.colors');

include_once ('/var/www/html/nextcloud/apps/ticketing/templates/TicketingTemplate/index.php');

?>