<?php
/*include_once('TOOL/connect.php');
include_once('TOOL/tools.php');
include_once('TOOL/Cadre.php');
include_once('TOOL/onglet.php');
include_once('TOOL/form.php');
include_once('DAL/dbco.php');*/

//include_once('dompdf/src/Dompdf.php');
require_once 'dompdf/autoload.inc.php';
// reference the Dompdf namespace
use Dompdf\Dompdf;
use Dompdf\Options;

// instantiate and use the dompdf class
$dompdf = new Dompdf();
$dompdf->loadHtml('hello world');
echo $dompdf->outputHtml();

//$dompdf->loadHtml(Lorem::ipsum(5));

// (Optional) Setup the paper size and orientation
//$dompdf->setPaper('A4', 'landscape');

// Render the HTML as PDF
$dompdf->render();
sprintf('test before pdf');
// Output the generated PDF to Browser
$dompdf->stream('sample1.pdf');

sprintf('test after pdf');
