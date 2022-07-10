<?php
include_once ('TOOL/connect.php');
include_once ('TOOL/tools.php');
include_once ('TOOL/Cadre.php');
include_once ('TOOL/onglet.php');
include_once ('TOOL/form.php');
include_once ('DAL/dbco.php');
include_once ('TEMPLATE/V_customer.php');

$c = new customer_Frame();

$str = '<div id="Page"><div class="tabs">'.$c->getOnglet(0)->showCorp().'</div></div>';

if($c->cleanAllSessionAndReturn===false){
    echo $str;
}
else{
    $c->cleanAllSessionAndReturn();
}
