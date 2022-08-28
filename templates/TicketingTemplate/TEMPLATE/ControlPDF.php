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
	private $root = '';
	private $mainPattern = 'MainPattern.html';
	private $bookletDetailsPattern = 'BookletDetailsPattern.html';
	private $interventionItemPattern = 'InterventionItemPattern.html';
	private $header = 'header.html';
	private $footer = 'footer.html';
	private $main = 'main.html';

	private $htmlOnly = false;



	function __construct($key, $useCache = false)
	{
		parent::__construct($key, $useCache, false);
		$this->root = './apps/ticketing/templates/TicketingTemplate/TEMPLATE/PDF_PATTERN/';
	}

	function init()
	{

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

	function setBookletDetails($pattern, $data)
	{

		$pattern = $this->add('CustomerName', $data['name'], $pattern);
		$pattern = $this->add('Reference', 'xxxx' . substr($data['reference'], -4), $pattern);
		$pattern = $this->add('BookletName', $data['bookletName'], $pattern);
		$pattern = $this->add('CreateDate', $data['createDate'], $pattern);
		$pattern = $this->add('InitialPoint', $data['initialpoint'], $pattern);
		$pattern = $this->add('SumPointuse', $data['sumpointuse'], $pattern);
		$pattern = $this->add('SumPointGift', $data['sumpointgift'], $pattern);
		$pattern = $this->add('SumPointRemaining', $data['sumpointremaining'], $pattern);
		$pattern = $this->add('CurrentDate', date("d/m/Y"), $pattern);


		return $pattern;
	}
	function setInterventionItem($pattern, $data)
	{


		$pattern = $this->add('InterventionStart', $data['start'], $pattern);
		$pattern = $this->add('InterventionFinish', $data['end'], $pattern);
		$pattern = $this->add('InterventionRemark', $data['remark'], $pattern);
		$pattern = $this->add('InterventionPoint', $data['finalpoint'], $pattern);
		$pattern = $this->add('InterventionGift', $data['gift'], $pattern);

		$labelGift = (int)$data['gift'] === (int)1 ? '(OFFERT)' : '';
		$pattern = $this->add('InterventionIsGift', $labelGift, $pattern);


		return $pattern;
	}

	function getContent($file)
	{

		return file_get_contents($this->root . '' . $file);
	}

	function getPDF()
	{
		$booklet = Tool::getOption('booklet');
		$data = Query::getBooklet(null, $booklet);

		$html = $this->getContent($this->mainPattern);
		$main = $this->getContent($this->main);
		$details = $this->getContent($this->bookletDetailsPattern);
		$item = $this->getContent($this->interventionItemPattern);
		$header = $this->getContent($this->header);
		$footer = $this->getContent($this->footer);
		$items = "";
		$css = $this->getCss();
		if (isset($data[0])) {

			$bookletId = $data[0]['id'];
			$itemsList = Query::getInterventionsByBooklet($bookletId);
			$details = $this->setBookletDetails($details, $data[0]);

			foreach ($itemsList as $element) {
				$items .= $this->setInterventionItem($item, $element);
			}
		}

		$main = $this->add('BookletDetailsPattern', $details, $main);
		$main = $this->add('InterventionItemPattern', $items, $main);
		$html = $this->addBeetwen('header', $header, $html);
		$html = $this->addBeetwen('footer', $footer, $html);
		$html = $this->addBeetwen('main', $main, $html);
		//var_dump($html);
		//var_dump($footer);


		$html = $this->add('css', '<style>' . $css . '</style>', $html);

		$root = $_SERVER['SCRIPT_FILENAME'] . 'apps/ticketing';

		if (!$this->htmlOnly) {

			$dompdf = new Dompdf(['enable_remote' => true]);

			$dompdf->getOptions()->setChroot([$root]);

			$dompdf->loadHtml($html);

			if (isset($_dompdf_warnings)) {
				echo $_dompdf_warnings;
				exit;
			}

			$dompdf->setPaper('A4', 'portrait');

			$dompdf->render();

			$canvas = $dompdf->getCanvas();
			$canvas->page_script(function ($pageNumber, $pageCount, $canvas, $fontMetrics) {
				$text = "Page $pageNumber of $pageCount";
				$font = $fontMetrics->getFont('monospace');
				$pageWidth = $canvas->get_width();
				$pageHeight = $canvas->get_height();
				$size = 12;
				$width = $fontMetrics->getTextWidth($text, $font, $size);
				$canvas->text($pageWidth - $width - 20, $pageHeight - 20, $text, $font, $size);
			});

			$pdfName = 'CustomerName' . '_' . date("d-m-Y") . '.pdf';

			if (isset($data[0]['bookletName'])) {
				$pdfName = $data[0]['bookletName'] . '_' . date("d-m-Y") . '.pdf';
			}

			$dompdf->stream($pdfName);
		} else {
			echo $html;
		}
	}

	/**
	 * @param mixed $selector la balise HTML de depart exemple <client></client> dans laquelle sera injecté $value
	 * @param mixed $value la valeur qui sera ajouter dans $selector
	 * @param mixed $pattern la donnée brut dans laquelle le $selector doit etre ajouté
	 * @return String  
	 */
	function addBeetwen($selector, $value, $pattern)
	{
		return str_ireplace('<' . $selector . '></' . $selector . '>', '<' . $selector . '>' . $value . '</' . $selector . '>', $pattern);
	}

	/**
	 * @param mixed $selector la balise HTML de depart exemple <client></client> qui sera remplacé par la $value
	 * @param mixed $value la valeur qui remplacera le $selector
	 * @param mixed $pattern la donnée brut dans laquelle le $selector doit etre remplacé
	 * @return String  
	 */
	function add($selector, $value, $pattern)
	{
		return str_ireplace('<' . $selector . '></' . $selector . '>', $value, $pattern);
	}
}
