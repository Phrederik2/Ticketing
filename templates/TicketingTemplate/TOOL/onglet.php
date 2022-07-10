<?php


/**
 * Object qui permet de crée un systeme d'onglet
 */
class Onglet
{
	private $title = "";
	private $option;
	private $timer = false;
	private $tab = array();
	private $ajax = false;
	protected $items = array();

	function __construct($title)
	{
		$this->setTitle($title);
	}

	function getTitle()
	{
		return $this->title;
	}
	function setTitle($title)
	{
		$this->title = $title;
	}
	function getTimer()
	{
		return $this->timer;
	}
	function setTimer($timer)
	{
		$this->timer = round($timer, 3);
	}

	/**
	 * Ajoute une tab dans la pile
	 * ne peux etre que de type TAB
	 * @param Tab $tab
	 * @return void
	 */
	function add(Tab $tab)
	{

		$tmp = array();
		$tmp['type'] = 'fixe';
		$tmp['tab'] = array('tab' => $tab);

		$tab->setParent($this);
		\array_push($this->tab, $tmp);
	}

	function addCallable(Tab $tab, $callable = "", $timeUpdate = 0, $args = null)
	{
		$tmp = array();
		$tmp['type'] = 'callable';
		$tmp['tab'] = array('tab' => $tab, 'callable' => $callable, 'timeUpdate' => $timeUpdate, 'args' => $args);

		$tab->setParent($this);
		\array_push($this->tab, $tmp);
	}

	function getAjax()
	{
		return $this->ajax;
	}
	function setAjax($ajax)
	{
		$this->ajax = $ajax;
	}


	function getOption()
	{
		return $this->option;
	}
	function setOption($option)
	{
		$this->option = $option;
	}


	function getLastTab()
	{
		if (count($this->tab) == 0) return null;

		return $this->tab[count($this->tab) - 1]['tab']['tab'];
	}

	function getTab($number)
	{
		if (isset($this->tab[$number])) {

			return $this->tab[$number];
		}
	}

	/**
	 * recupere le header des onglets afin de construire le menu dinamyque de selection
	 *
	 * @return string
	 */
	function showHeader()
	{
		$displayheader = false;

		$str = "";
		foreach ($this->tab as $i => $tab) {
			$type = $tab['type'];

			if ($type == 'callable' or $i > 0) {
				$displayheader = true;
				break;
			}
		}

		if ($displayheader) {
			foreach ($this->tab as $tab) {
				$type = $tab['type'];
				$item = $tab['tab']['tab'];
				$arg = $tab['tab'];

				if ($type == 'callable') {
					$str .= $item->showHeader($arg['callable'], $arg['args']);
				}
				if ($type == 'fixe') {
					$str .= $item->showHeader();
				}
			}
		}

		return $str;
	}

	/**
	 * recupere le contenu des onglets pour les afficher
	 *
	 * @return string
	 */
	function showCorp()
	{
		$str = "";
		foreach ($this->tab as $tab) {
			$type = $tab['type'];
			$item = $tab['tab']['tab'];
			$arg = $tab['tab'];
			if ($type == 'callable') {
				$str .= $item->showCorp($arg['callable'], $arg['timeUpdate']);
			}
			if ($type == 'fixe') {
				$str .= $item->showCorp();
			}
		}

		return $str;
	}

	function showTimer()
	{
		if ($this->timer != false) return " " . $this->timer . " sec";
	}

	/**
	 * affiche l'onglet complet avec les sous onglet et le menu
	 *
	 * @return string
	 */

	function full()
	{
		return "
			<div class=\"tabs\">".$this->title . $this->showTimer() . $this->getOption() . "
				<ul>
				" . $this->showHeader() . "
				</ul>
			" . $this->showCorp() . "
			</div>
		";
	}

	function showTitle()
	{

		$str = $this->getTitle();
		$balStart = '<';
		$balEnd = '>';



		$startpos = strpos($this->getTitle(), $balStart);
		$endpos = strrpos($this->getTitle(), $balEnd);

		if ($startpos < $endpos) {

			$str = substr($this->getTitle(), 0, $startpos);
			$str .= substr($this->getTitle(), $endpos + 1);
		}

		return $str;
	}

	function toString()
	{

		$isnew = false;
		foreach ($this->tab as $tab) {
			$item = $tab['tab']['tab'];
			if ($isnew == true and $item->getActive() == true) {
				$item->setActive(false);
			}
			if ($item->getActive() == true) {
				$isnew = true;
			}
		}

		if ($isnew == false) {
			foreach ($this->tab as &$tab) {
				$item = $tab['tab']['tab'];
				$item->setActive(true);
				break;
			}
		}

		$str = "";
		if ($this->getAjax() == false) {

			$str .= "
			<div id=\"" . $this->showTitle() . "\" class=\"onglet \">
			" . $this->full() . "
			</div>";
		} else {
			$str .= "
			" . $this->full() . "
			";
		}
		return $str;
	}
}

/**
 * Tab ou onglet (sous onglet)
 * permet de crée une section selectionnable de l'onglet
 */
class Tab
{
	private $parent;
	private $title = "";
	private $active = false;
	private $uniqKey;
	private $option;
	private $idDoc;
	private $item = array();

	/**
	 * crée le title et defini si l'onglet est celui qui doit etre actif par defaut
	 *
	 * @param string $title
	 * @param bool $active
	 */
	function __construct($title = '', $active = false, $option = null)
	{
		$this->title = $title;
		$this->active = $active;
		if ($option != null) $this->option = $option;
		$this->uniqKey = \sha1($this->title);
	}

	function getTitle()
	{
		return $this->title;
	}
	function setTitle($value)
	{
		$this->title = $value;
	}

	function getidDoc()
	{
		return $this->idDoc;
	}
	function setidDoc($value)
	{
		$this->idDoc = $value;
	}

	function getActive()
	{
		return $this->active;
	}

	function setActive($value)
	{
		$this->active = $value;
	}

	function getParent()
	{
		return $this->parent;
	}
	function setParent($parent)
	{
		$this->parent = $parent;
		$this->uniqKey = \sha1($parent->getTitle() . $this->title);
	}


	/**
	 * retourne combien d'object est defini dans l'onglet
	 *
	 * @return integer
	 */
	function getCountItem()
	{
		return \count($this->item);
	}

	/**
	 * retourne en texte si l'onglet est actif ou pas
	 *
	 * @return string
	 */
	function showActive()
	{
		$str = "";
		if ($this->active === true) $str = "active";
		return $str;
	}

	/**
	 * retourne en texte si l'onglet dipose d'un ID (CSS) ou pas
	 *
	 * @return string
	 */
	function showOption()
	{
		$str = "";
		if ($this->option != null) $str = ' ' . $this->option . ' ';
		return $str;
	}

	/**
	 * retourne en boolean string si l'onglet est actif
	 *
	 * @return string
	 */
	function showActiveBool()
	{
		$str = "false";
		if ($this->active === true) $str = "true";
		return $str;
	}

	/**
	 * ajout un item dans la liste des object contenu dans l'onglet
	 * l'object en question ne peux etre que de type item ou herité d'item
	 *
	 * @param Item $item
	 * @return void
	 */
	function addItem($item)
	{
		\array_push($this->item, $item);
	}

	function show_Doc()
	{
		if ($this->idDoc != "" and $this->idDoc > 0) {
			$name = "info3.png";
			$link = Tool::linkInline('<img class="info" alt="Go to Documentation" title="Go to Documentation" src="' . Tool::urlPictures($name) . '" max-width="20" max-height="20" />', Tool::url(array('View' => 'Documentation', 'read' => $this->idDoc), false), true);
			return $link;
		}
	}

	/**
	 * recupere les elements afin d'afficher correctement le header (menu cliquable) de l'onglet
	 *
	 * @return string
	 */
	function showHeader($callable = "", $args = null)
	{
		$action = '';
		$script = '';
		$option = '';
		if ($callable != '') {
			if ($args != null) {

				foreach ($args as $key => $item) {
					if ($option != '') $option .= '&';
					$option .= $key . '=' . $item;
				}
			}


			$action = 'onclick="callable(null,\'' . $this->uniqKey . '\',\'' . $callable . '\',\'' . $option . '\')"';
			$script = '';
			if ($this->active) {
				//$action = '';
				$script = '<script>window.addEventListener("load", function(){callable(null,\'' . $this->uniqKey . '\',\'' . $callable . '\',\'' . $option . '\');});</script>';
			}
		}


		$str = $script . '
		<li class="' . $this->showActive() . '">
			<a ' . $action . ' ' . $this->showOption() . ' href="#' . $this->uniqKey . '">' . $this->title . '' . $this->show_Doc() . '</a>
		</li>';
		return $str;
	}

	/**
	 * appelle la fonction tostring de tous les object contenu dans l'onglet
	 *
	 * @return string
	 */
	function showItem()
	{

		try {
			$str = "";
			foreach ($this->item as $item) {

				$str .= $item->toString();
			}
			return $str;
		} catch (Error $err) {
			echo "catched: ", $err->getMessage(), PHP_EOL;
			Tool::context();
		}
	}

	/**
	 * genere le code requis pour l'onglet et insere les données des item qu'il contient
	 *
	 * @return string
	 */
	function showCorp($callable = null, $minimumDelay = 0)
	{
		$item = "";
		$time = 0;
		if ($callable == null or $this->active == true) {
			$item = $this->showItem();
		}

		$str = '
		<div class="' . $this->showActive() . '" lastUpdate="0" minimumDelay="' . $minimumDelay . '" id="' . $this->uniqKey . '" >
			
			' . $item . '
			
		</div>';

		return $str;
	}

	function getLastItem()
	{

		$i = $this->getCountItem();

		if ($i == 0) return null;

		return $this->item[$i - 1];
	}
}

/**
 * element commun du contenu des onglet
 * facilite l'insertion par heritage et permet un control des object pouvans etre introduit
 */
class Item
{

	private $value = "";
	protected $html_Class = array("FieldValue");
	private $html_Id = "";
	private $html_Title = "";

	function __construct($value = "")
	{
		$this->setValue($value);
	}

	function getValue()
	{
		if(is_object($this->value) and method_exists($this->value,'toString')){
			return $this->value->toString();
		}
		return $this->value;
	}

	function getValueBrut()
	{
		return $this->value;
	}
	function setValue($value)
	{
		$this->value = $value;
	}

	function addHtml_Class($class)
	{
		\array_push($this->html_Class, $class);
	}

	function getHtml_Class()
	{
		return $this->html_Class;
	}
	function setHtml_Class($html_Class)
	{
		$this->html_Class = $html_Class;
	}
	function getHtml_Id()
	{
		return $this->html_Id;
	}
	function setHtml_Id($html_Id)
	{
		$this->html_Id = $html_Id;
	}
	function getHtml_Title()
	{
		return $this->html_Title;
	}
	function setHtml_Title($html_Title)
	{
		$this->html_Title = $html_Title;
	}
	function add_HTML_Class($value)
	{
		\array_push($this->html_Class, $value);
	}

	function show_HTML_Class()
	{

		if (\count($this->html_Class) > 0) {
			$str = " class=\"";
			foreach ($this->html_Class as $key => $value) {
				$str .= $value . " ";
			}
			$str .= "\"";

			return $str;
		}
	}

	function show_HTML_ClassInline()
	{

		if (\count($this->html_Class) > 0) {
			$str = " class=\"flex2 ";
			foreach ($this->html_Class as $key => $value) {
				$str .= $value . " ";
			}
			$str .= "\"";

			return $str;
		}
	}

	function show_HTML_Id()
	{
		if ($this->html_Id != "") {
			$str = " id=\"" . $this->html_Id . "\"";
			return $str;
		}
	}
	function show_HTML_Title()
	{
		if ($this->html_Title != "") {
			$str = " title=\"" . $this->html_Title . "\"";
			return $str;
		}
	}


	function toString()
	{
		return $this->getValue();
	}
}

/**
 * element de base de formulaire d'affichage permet d'associer un titre et un valeurs
 */
class Field extends Item
{
	protected static $items = array();
	private $label = "";
	private $title = "";
	private $newLine;
	private $visibleIfNull;

	/**
	 * crée l'objet et l'envoi dans un tableau static pour former une collection
	 *
	 * @param string $title
	 * @param string $value
	 * @param bool $newLine defini si il faut effectué un retour a la ligne apres les données
	 * @param bool $visibleIfNull defini si l'object doit etre visible si il ne contien pas de donnée
	 */
	function __construct($title, $value = "", $newLine = true, $visibleIfNull = true)
	{
		$this->title = $title;
		$this->setValue($value);
		$this->newLine = $newLine;
		$this->visibleIfNull = $visibleIfNull;
		Field::$items[$this->title] = $this;
	}

	static function cleanItems()
	{
		Field::$items = array();
	}

	function setLabel($label)
	{
		$this->label = $label;
	}
	function getTitle()
	{
		return $this->title;
	}
	function setTitle($title)
	{
		$this->title = $title;
	}
	function getVisibleIfNull()
	{
		return $this->visibleIfNull;
	}
	function setVisibleIfNull($visibleIfNull)
	{
		$this->visibleIfNull = $visibleIfNull;
	}




	/**
	 * sur base d'une clé, permet de recuperé la valeur d'un item repris dans la collection
	 * retournera NULL sur l'object n'est pas trouvé
	 *
	 * @param string $key
	 * @return Field
	 */
	static function get($key)
	{
		//var_dump(Field::$items);
		if (isset(Field::$items[$key])){
			return Field::$items[$key];
		} else{
			return null;
		}
		
	}

	
	static function push($key, $value = "", $separator = ", ", $duplicate = false)
	{
		if ($value == "") return;
		if (strlen(Field::$items[$key]->getValue()) == 0){
			Field::get($key)->setValue($value);
		} 
		if (isset(Field::$items[$key])) {
			if ($duplicate === false) {
				if (strstr(Field::$items[$key]->getValue(), $value) == false) {
					Field::$items[$key]->setValue(Field::$items[$key]->getValue() . $separator . $value);
				}
			} else {
				Field::$items[$key]->setValue(Field::$items[$key]->getValue() . $separator . $value);
			}
		}
	}


	/**
	 * retourne un breack si necessaire
	 *
	 * @return string
	 */
	function showNewLine()
	{
		if ($this->newLine === true) return "<br>";
	}

	/**
	 * retourne le contenu de l'object
	 *
	 * @return string
	 */
	function toString()
	{
		if (Field::get($this->getTitle()) == null) Field::$items[$this->getTitle()] = $this;

		if ($this->getVisibleIfNull() === true and $this->getValue() == "" or $this->getValue() == null) return;
		$str = "
		<span class=\"Field\">
			<span class=\"FieldTitle\">
				{$this->title}
			</span>
			<span class=\"FieldSeparator\">
				:
			</span>
			<span {$this->show_HTML_Class()}{$this->show_HTML_Id()}{$this->show_HTML_Title()}>
				{$this->getValue()}
			</span>
		</span>{$this->showNewLine()}
		";
		return $str;
	}
}

class FieldSet extends Item
{
	private $items = array();

	function __construct($title)
	{
		parent::__construct($title);
		$this->html_Class = array();
	}

	function add($item)
	{
		array_push($this->items, $item);
	}

	function get(){
		return $this->items;
	}

	function showItems()
	{
		$str = "";
		foreach ($this->items as $item) {
			$str .= $item->toString();
		}
		return $str;
	}

	function toString()
	{
		$str = '<fieldset ' . $this->show_HTML_Class() . '>
					<legend>
					' . $this->getValue() . '
					</legend>
					' . $this->showItems() . '
				</fieldset>';
		return $str;
	}
}


class Dataset
{

	private $dataset = array();
	private $classhtml = '';
	private $title = '';

	function __construct($title, $dataset)
	{
		
		//parent::__construct($title);
		
		$this->dataset = $dataset;
		$this->title = $title;
		//$this->classhtml = $classhtml;
	}

	function toString()
	{

		return Tool::getTableLight($this->dataset, $this->classhtml, 0, false, null, $this->title);
	}
}

/**
 * un group gere un ensemble d'elements plus petit (Item, Field,...) afin de les voir comme un ensemble et non pas individuel
 * Le groupe fonctionne sur base d'un template.
 * une fois le template determiné, la fonction utilisée pour l'injection des données determinera comment les données serons conservée
 */
class Group extends Item
{
	private $item = array();
	private $template = array();

	/**
	 * Ajout un item dans la collection des items du groupe
	 *
	 * @param Item $item
	 * @return void
	 */
	function addItem(Item $item)
	{
		\array_push($this->item, $item);
	}

	/**
	 * Ajoute un item dans la collection du template de base du groupe
	 *
	 * @param Item $item
	 * @return void
	 */
	function addTemplate(Item $item)
	{
		\array_push($this->template, $item);
	}

	/**
	 * verifie que le nombre de données correspond au template
	 * verifie que les données en parametre est un jeux de valeurs nouveaux par rapport a ce qui est deja introduit et si c'est le cas, les ajoutes comme item
	 *
	 * @return void
	 */
	function pushValue()
	{
		$value = array();
		foreach (\func_get_args() as $param) {
			\array_push($value, $param);
		}
		if (count($value) == count($this->template)) {
			$verif = 0;
			foreach ($value as $param) {
				$unique = true;
				foreach ($this->item as $item) {

					if (\strcmp($item->getValue(), $param) == 0) $unique = false;
				}
				if ($unique == true) $verif++;
			}

			if ($verif != 0) {
				for ($i = 0; $i < count($value); $i++) {
					$new = clone $this->template[$i];
					$new->setValue($value[$i]);
					$this->addItem($new);
				}
			}
		}
		return false;
	}


	/**
	 * retourne le contenu des items
	 *
	 * @return void
	 */
	function toString()
	{
		$str = "";
		$count = 0;
		foreach ($this->item as $item) {
			if ($count == count($this->template)) {
				$str .= "<br>";
				$count = 0;
			}
			$str .= $item->toString();
			$count++;
		}
		return $str;
	}
}

class Table extends Item
{

	private $header = array();
	private $groupBy = "";
	private $row = array();
	private $linkRow = array();
	private $duplicate = false;
	private $repeatHeader = 0;
	public $separatorAddRow = "<br>";
	public $id = "";
	public $class = array();


	function __construct()
	{
		$this->header = $this->insertVal(func_get_args(), $this->header);
	}

	function getCount()
	{
		return count($this->row);
	}

	function getHeader()
	{
		return $this->header;
	}
	function setHeader($header)
	{

		$this->header = $this->insertVal(func_get_args(), $this->header);
	}
	function setRepeatHeader($repeatHeader)
	{
		$this->repeatHeader = $repeatHeader;
	}

	function set($data)
	{
		$header = array();
		if (isset($data[0])) {

			foreach ($data[0] as $key => $value) {
				$header[$key] = $key;
			}
			$this->setHeader($header);

			foreach ($data as $line) {
				$row = array();
				foreach ($line as $item) {
					$row[] = $item;
				}
				$this->addRow($row);
			}
		}
	}


	function getLinkRow()
	{
		return $this->linkRow;
	}
	function setLinkRow($linkRow)
	{
		$this->linkRow = $linkRow;
	}

	function getGroupBy()
	{
		return $this->groupBy;
	}
	function setGroupBy($groupBy)
	{
		$this->groupBy = $groupBy;
	}

	function getRow()
	{
		return $this->row;
	}
	function setRow($row)
	{
		$this->row = $row;
	}


	function getCountRow()
	{
		return count($this->row);
	}

	function insertVal($origin, $destination)
	{

		if (is_array($origin)) {

			foreach ($origin as $param) {
				if (is_array($param)) {
					foreach ($param as $param2) {

						\array_push($destination, $param2);
					}
				} else {

					\array_push($destination, $param);
				}
			}
		} else {
			\array_push($destination, $origin);
		}
		return $destination;
	}

	function addHeader()
	{

		$this->header = $this->insertVal(func_get_args(), $this->header);
	}


	function setLastLinkRow($value)
	{
		$this->linkRow[count($this->linkRow) - 1] = $value;
	}

	function addRow($data)
	{

		$tmp = array();
		$serialTmp = null;
		$tmp = $this->insertVal(func_get_args(), $tmp);
		//$tmp=$data;

		if (count($tmp) == count($this->header)) {

			if ($this->duplicate === false) {
				$serialTmp = serialize($tmp);
				foreach ($this->row as $tmpRow) {
					if (strcmp($serialTmp, serialize($tmpRow)) == 0) return false;
				}
			}
		}

		$count = 0;
		foreach ($tmp as $item) {
			$count += strlen($item);
		}

		if ($count > 0) {
			$update = true;
			$i = array_search($this->getGroupby(), $this->header);
			if ($i !== false and count($this->row) > 0) {
				$count = 0;

				foreach ($this->row as $line) {

					if ($line[$i] == $tmp[$i] and $tmp[$i] != '') {

						$update = false;
						for ($j = 0; $j < count($line); $j++) {
							if ($tmp[$j] != "" and (strpos("" . $line[$j], "" . $tmp[$j]) === false)) {
								$line[$j] .= $this->separatorAddRow . $tmp[$j];
							}
						}
						if ($update == false) {
							$this->row[$count] = $line;
						}
					}

					$count++;
				}
			}

			if ($update == true) {
				\array_push($this->row, $tmp);
				\array_push($this->linkRow, "");
			}
		}

		return true;
	}



	function showHeader()
	{
		if (count($this->row) == 0) return;
		$str = "
		<tr>
		";
		foreach ($this->header as $value) {
			$str .= "
			<th>
				$value
			</th>";
		}
		$str .= "
		</tr>";
		return $str;
	}

	function showRow()
	{
		$str = "";
		$iLink = 0;
		$header = 0;
		$repeat = false;

		if ($this->repeatHeader > 0) {
			$header = $this->repeatHeader;
			$repeat = true;
		}

		foreach ($this->row as $item) {
			if ($header == 0 and $repeat == true) {
				$str .= $this->showHeader();
				$header = $this->repeatHeader;
			}
			$str .= "
			<tr {$this->linkRow[$iLink]}>
			";
			foreach ($item as $value) {
				$str .= "
				<td>
					$value
				</td>";
			}
			$str .= "
			</tr>";
			$iLink++;
			$header--;
		}
		return $str;
	}

	function addClass($class)
	{
		array_push($this->class, $class);
	}


	function showClass()
	{
		if (count($this->class)) {
			$str = "";
			foreach ($this->class as $class) {
				$str .= $class . ' ';
			}
			return ' class="' . $str . '" ';
		}
	}

	function toString()
	{

		//$sha = sha1(serialize($this->header));
		//$script='<script>$(document).ready(function() {$("#'.$sha.'").DataTable( {"info":false, "paging": false, "searching": false } );} );</script>';

		//$str = "$script<table id=\"$sha\" class=\"stripe row-border order-column\" width=\"10%\">
		$str = "<table id=\"{$this->id}\" {$this->showClass()}>
		";
		$str .= "<thead>" . $this->showHeader() . "</thead>";
		$str .= "<tbody>" . $this->showRow() . "</tbody>";
		$str .= "
		</table>";
		return $str;
	}
}

class TableParser
{
	private $header = array();
	private $data = array();
	private $folder;
	private $tmp;
	private $content;
	private $pointer = 0;
	private $fileisgood = false;
	private $refTime;
	private $workArea;

	function __construct($link)
	{
		$this->content = file_get_contents($link);
	}

	function getData()
	{
		return $this->data;
	}
	function getRefTime()
	{
		return $this->refTime;
	}
	function getWorkArea()
	{
		return $this->workArea;
	}
	function setWorkArea($workArea)
	{
		$this->workArea = $workArea;
	}

	function normalize()
	{

		$this->content = htmlentities($this->content, ENT_IGNORE);
		$this->content = html_entity_decode($this->content);
		$this->content = utf8_encode($this->content);
		$this->content = str_replace(chr(0), '', $this->content);
		date_default_timezone_set("Europe/Paris");
		$date_std = date("d/m/Y");
		$date = date("d/m/Y");
		$date2 = date("d-m-y");

		$isValid = stripos($this->content, "<html><body><h3>Date: " . $date);

		if (!$isValid) {
			$date = date("j/m/Y");
			$isValid = stripos($this->content, "<html><body><h3>Date: " . $date);
		}

		$isValid2 = stripos($this->content, "<html><body><h3>Date: " . $date2);

		if (!$isValid2) {
			$date2 = date("j-m-Y");
			$isValid2 = stripos($this->content, "<html><body><h3>Date: " . $date2);
		}


		if ($isValid or $isValid2) {

			if ($isValid) {
				$begin = stripos($this->content, $date);
				$datecreate = substr($this->content, $begin + (strlen($date)), 16) . ":00";

				$this->refTime = Tool::formatDate3($date_std . ' ' . $datecreate);
				//echo $date_std.' '.$datecreate .'=>'.$this->refTime;


			}
			if ($isValid2) {
				$begin = stripos($this->content, $date2);
				$datecreate = substr($this->content, $begin + (strlen($date2)), 16) . ":00";
				$this->refTime = Tool::formatDate3($date_std . ' ' . $datecreate);
				//echo $date_std.' '.$datecreate .'=>'.$this->refTime;
			}

			$this->fileisgood = true;

			$this->content = str_replace(' align="left"', '', $this->content);
			$this->content = str_replace('</th>', '', $this->content);
			$this->content = str_replace('</td>', '', $this->content);
			$this->content = str_replace('</tr>', '', $this->content);

			$this->pointer = stripos($this->content, '<th>');
			$this->content = substr($this->content, $this->pointer);
			$this->tmp = explode("<tr>", $this->content);
		}
	}

	function normalizeColumn()
	{
		$data = array();
		foreach ($this->data as $line) {
			if (isset($line["ProjectNumber"])) $line["Project ID"] = $line["ProjectNumber"];
			if (isset($line["ExternalRefID"])) $line["WO ID"] = $line["ExternalRefID"];
			if (isset($line["Assignment_Start"])) $line["Start"] = $line["Assignment_Start"];
			if (isset($line["Assignment_Finish"])) $line["Finish"] = $line["Assignment_Finish"];
			if (isset($line["Assignment_AssignedEngineers"])) $line["Assigned Resource"] = $line["Assignment_AssignedEngineers"];
			if (!isset($line["WO ID"]) and isset($line["CallID"])) $line["WO ID"] = $line["CallID"];
			if (isset($line["ProjectID"])) $line["Project ID"] = $line["ProjectID"];
			if (!isset($line["Project ID"])) $line["Project ID"] = "";
			if ($line["Project ID"] == "None") $line["Project ID"] = "";
			if (isset($line["Project ID"])) $line["oms_ref"] = $line["Project ID"];
			if (strtolower($line["Assigned Resource"]) == "none") $line["Assigned Resource"] = "";
			if (isset($line["MCOM ID"]) and $line["MCOM ID"] != "None") $line["Project ID"] = $line["MCOM ID"] . '9';
			array_push($data, $line);
		}
		$this->data = $data;
	}

	function assocFolder()
	{
		$data = array();
		foreach ($this->data as $line) {
			$line["oms_ref"] = "";
			$key = substr($line["WO ID"], 4);
			foreach ($this->folder as $lineFolder) {

				if (isset($line["Project ID"]) and $line["Project ID"] != "") {
					$lineFolder["oms_ref"] = $line["Project ID"];
				} else {
					$line["Project ID"] = $lineFolder["oms_ref"];
				}

				if (strstr($lineFolder["WO_ID"], $key) != false) {
					$line["oms_ref"] = $lineFolder["oms_ref"];
				}
				if ($lineFolder["oms_ref"] == "None") $lineFolder["oms_ref"] = "";
				if ($lineFolder["Project ID"] == "None") $lineFolder["Project ID"] = "";
			}
			array_push($data, $line);
		}
		$this->data = $data;
	}

	function checkSameTech()
	{
		$data = array();

		foreach ($this->data as $line) {
			$line["same_tech"] = true;

			foreach ($this->data as $line2) {

				if ($line["oms_ref"] == $line2["oms_ref"] and $line2["oms_ref"] != "" and $line["oms_ref"] != "" and $line["WO ID"] != $line2["WO ID"] and $line["Assigned Resource"] != $line2["Assigned Resource"] and $line["ClosureCode"] == "None" and $line2["ClosureCode"] == "None" and $line["Assigned Resource"] != "" and $line2["Assigned Resource"] != "") {
					//if($_SESSION['user']=='093318') echo $line["WO ID"].' '.$line["Assigned Resource"] . ' ' . $line2["Assigned Resource"] . '<br>';
					$line["same_tech"] = false;
				}
			}
			array_push($data, $line);
		}
		$this->data = $data;
	}

	function defineHeader()
	{


		$tmp = explode('<th>', $this->tmp[0]);
		foreach ($tmp as $value) {
			$this->header[$value] = "";
		}
		unset($this->tmp[0]);
	}

	function defineLine()
	{
		foreach ($this->tmp as $line) {
			$tmp = explode('<td>', $line);
			$linetmp = $this->header;
			$count = 0;
			if (count($linetmp) == count($tmp)) {

				foreach ($linetmp as $key => $value) {
					$linetmp[$key] = $tmp[$count];
					$count++;
				}
				array_push($this->data, $linetmp);
			}
		}
	}

	function defineWorkArea()
	{
		$count = array();
		foreach ($this->data as $line) {
			if (isset($line["District"])) {
				if (!isset($count[$line["District"]])) $count[$line["District"]] = 0;
				$count[$line["District"]]++;
			}
		}

		$maxcount = 0;
		foreach ($count as $key => $value) {
			if ($value > $maxcount) {
				$maxcount = $value;
				$this->workArea = $key;
			}
		}
	}
}

