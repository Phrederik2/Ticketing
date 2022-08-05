<?php

use Dompdf\Dompdf;
use Dompdf\Options;

/*
URL de test
http://localhost/nextcloud/index.php/apps/ticketing/pdf?booklet=0132f220f3e8f251f4ccdc230e4af32cc2461194

*/

class Control_PDF_Frame extends Cadre_Base
{

	private $listCss = array();
	private $template = 'apps/ticketing/templates/TicketingTemplate/TEMPLATE/pdf.html';

	function __construct($key, $useCache = false)
	{
		parent::__construct($key, $useCache, false);
	}

	function init()
	{
		$tmpCss = array();
		$dir = './apps/ticketing/css/';
		$this->addCss($dir . 'style.css');
		$this->addCss($dir . 'trumbowyg.colors.css');
		$this->addCss($dir . 'trumbowyg.css');
	}



	function addCss($filename)
	{
		if (file_exists($filename)) {
			$this->listCss[] = $filename;
		}
	}

	function getCss()
	{
		$tmp = '';
		foreach ($this->listCss as $element) {
			$tmp .= " " . file_get_contents($element);
		}
		return $tmp;
	}

	function setInfoBooklet()
	{
		$tab = new Tab();
		$booklet = Tool::getOption('booklet');

		$data = Query::getBooklet(null,$booklet);
		
		$fieldset = new FieldSet('Information du carnet');
		$fieldset->add(new Item('<br><br>'));
		$fieldset->add(new Field('Nom du client', $data[0]['name']));
		$fieldset->add(new Field('Nom du carnet', $data[0]['bookletName']));
		$fieldset->add(new Field('Date de création', $data[0]['createDate']));
		$fieldset->add(new Field('Nombre de point initale', (string)(int)$data[0]['initialpoint']));
		$fieldset->add(new Field('Somme des points utilisés', (string)(int)$data[0]['sumpointuse']));
		$fieldset->add(new Field('Somme des points cadeaux', (string)(int)$data[0]['sumpointgift']));
		$fieldset->add(new Field('Somme des points restant', (string)(int)$data[0]['sumpointremaining']));

		$tab->addItem($fieldset);

		return $tab;
	}

	function getPDF()
	{
		$tmp = file_get_contents($this->template);
		$css = $this->getCss();
		$body = $this->setInfoBooklet();
		$tmp = str_replace('<css></css>', '<style>' . $css . '</style>', $tmp);
		$tmp = str_replace('<content></content>',  $body->showCorp(), $tmp);



		$dompdf = new Dompdf();


		$dompdf->loadHtml($tmp);

		$dompdf->setPaper('A4', 'landscape');

		$dompdf->render();

		$dompdf->stream('sample1.pdf');
	}
}
