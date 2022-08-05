<?php
include_once('TOOL/connect.php');
include_once('TOOL/tools.php');
include_once('TOOL/Cadre.php');
include_once('TOOL/onglet.php');
include_once('TOOL/form.php');
include_once('DAL/dbco.php');
include_once('TEMPLATE/ControlPDF.php');

require_once 'dompdf/autoload.inc.php';

$t = new Control_PDF_Frame(0);
$t->getPDF();



die;
