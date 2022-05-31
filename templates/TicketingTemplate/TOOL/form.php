<?php

// Object FORM permet de cr�e un formulaire, il devra etre rempli avec des Items
class Form
{
	private $name = null;
	private $fullName = null;
	private $table = null;
	private $db = null;
	private $PDO = null;
	private $where = null;
	private $item = array();
	private $itemItin = array();
	private $itemBind = array();
	private $newRecord = false;
	private $isNewRecord = null;
	private $isInsert = false;
	private $insert = false;
	private $update = null;
	private $isUpdate = false;
	private $forceUpdate = false;
	private $debug = false;
	private $ajax = false;
	private $postExist = false;
	private $getExist = false;
	private $saveValueInSession = false;
	private $selectData = null;
	private $displayElement = array();
	private $autoCommit = true;


	function __construct($name, $table = null, PDO $PDO = null, $where = null, $db = null)
	{
		$this->name = str_replace(' ', '_', $name);
		$this->table = $table;
		$this->db = $db;
		$this->PDO = $PDO;
		$this->where = $where;
		$this->fullName = 'F' . sha1($name . $table . $db) . '_' . get_class($this) . '_' . $this->name;
	}
	
	function commit(){
		if($this->autoCommit==true){
			$this->getQuery('COMMIT;');
		}
	}

	function setSaveValueInSession($value)
	{
		$this->saveValueInSession = $value;
	}

	function getSaveValueInSession()
	{
		return $this->saveValueInSession;
	}

	function setDebug($value)
	{
		$this->debug = $value;
		foreach ($_POST as $key => $value) {
			$this->debug('getPOST', $key, $value);
		}
	}
	function setDB($value)
	{
		$this->db = $value;
	}

	function getSelectData()
	{
		$this->selectData;
	}

	function setPostExist(bool $bool)
	{
		$this->postExist=(bool)$bool;
	}

	function getInsert()
	{
		if ($this->insert === false) {
			$this->recoveryValue();
			return $this->insert;
		}
		return $this->insert;
	}
	function getUpdate()
	{
		if ($this->update == null) {
			$this->recoveryValue();
		}
		return $this->update;
	}

	function getOption($keysearch)
	{

		foreach ($_POST as $keyp => $value) {
			if (strtolower($keyp) == strtolower($keysearch)) {
				$this->postExist = true;
				return $value;
			}
		}
		foreach ($_GET as $keyp => $value) {
			if (strtolower($keyp) == strtolower($keysearch)) {
				$this->getExist = true;
				return $value;
			}
		}
		return null;
	}

	function getWhere()
	{
		return $this->where;
	}

	function addItem($item)
	{
		if(method_exists($item,'setParent')){
			$item->setParent($this);
		}
		$this->item[$item->getName()] = $item;
		$this->itemItin[] = $item;
		if ($item->getTableChamp() != null) {
			$this->itemBind[strtolower($item->getTableChamp())] = &$this->item[$item->getName()];
		}
	}

	function ReplaceItem($label, $item)
	{
		if(method_exists($item,'setParent')){
			$item->setParent($this);
		}
		
		$this->item[$item->getName()] = $item;
		//$this->itemItin[]=$item;

		if ($item->getTableChamp() != null) {
			$this->itemBind[strtolower($item->getTableChamp())] = &$this->item[$item->getName()];
		}
	}

	function getItem($name = null)
	{
		if ($name == null) {
			return $this->item;
		} else {
			foreach ($this->item as $key => $item) {
				if (strtolower($key) == strtolower($name)) {
					return $item;
				}
			}
		}

		return null;
	}

	function setItem($name, $newItem)
	{
		if(method_exists($newItem,'setParent')){
			$newItem->setParent($this);
		}
		
		foreach ($this->item as $key => $item) {
			if (strtolower($key) == strtolower($name)) {
				$this->item[$key] = $newItem;
				return;
			}
		}
	}

	function getLastItem()
	{
		$count = count($this->itemItin);
		return $this->itemItin[$count - 1];
	}

	function setForceUpdate($value)
	{
		$this->forceUpdate = $value;
	}

	function newRecord()
	{


		$this->recoveryValue();

		if ($this->postExist and $this->isInsert != true) {
			$this->insert();
			if ($this->isInsert == true) {
				foreach ($this->item as $item) {

					$item->setValue('');
				}
			}
		}

		$this->isInsert = true;
	}

	function getPostExist()
	{
		return $this->postExist;
	}

	function searchForeignKey($table, $champ)
	{
		$query = "SELECT concat( table_name, '.', column_name ) AS 'foreign key',referenced_table_name as `table`,referenced_column_name as `champ`  
		FROM information_schema.key_column_usage
		WHERE table_name like '$table' and column_name like '$champ' and not isnull(referenced_table_name);";

		$data = $this->getQuery($query);
		$tmp = array();
		foreach ($data as $line) {
			$tmp[$line['table']] = $line['champ'];
		}
		return $tmp;
	}

	function autocreate()
	{
		if ($this->db == '') $this->db = 'pabx';
		$describe = $this->showFull();

		foreach ($describe as $line) {
			$name = $line['Field'];
			$type = $line['Type'];
			$key = $line['Key'];
			$default = $line['Default'];
			$comment = $line['Comment'];
			$foreignKey = $this->searchForeignKey($this->table, $name);

			$start = strpos($type, '(');
			$end = strpos($type, ')');
			$option = null;

			if ($start > 0 and $end > 0) {
				$option = substr($type, $start + 1, ($end - $start) - 1);
				$type = substr($type, 0, $start);
			}


			if ($key == 'PRI') {
				$this->addItem(new Text($name, $name));
				$this->getItem($name)->setUpdatable(false);
				$this->getItem($name)->setEnable(false);
				$this->getItem($name)->setItemIsUpdatable(false);
			} else if (count($foreignKey)) {
				$this->addItem(new SelectList($name, $name));
				foreach ($foreignKey as $table => $champ) {
					$strtable = "`$table`";
					if ($this->db != '') $strtable = "`" . $this->db . "`.`$table`";
					$popul = $this->getQuery('SELECT * from ' . $strtable . ';');

					foreach ($popul as $line) {
						$stri = '';
						foreach ($line as $nameofi => $i) {
							if ($nameofi != $champ) {
								if ($stri != '') $stri .= ' ';
								$stri .= $i;
							}
						}
						$this->getItem($name)->addSelectOption($stri, $line[$champ]);
						$this->getItem($name)->setDefaultValue($default);
					}
				}
			} else if ($type == 'int') {
				$this->addItem(new Text($name, $name));
				$this->getItem($name)->setDefaultValue($default);
			} else if ($type == 'float') {
				$this->addItem(new Text($name, $name));
				$this->getItem($name)->setDefaultValue($default);
			} else if ($type == 'decimal') {
				$this->addItem(new Text($name, $name));
				$this->getItem($name)->setDefaultValue($default);
			} else if ($type == 'smallint') {
				$this->addItem(new Text($name, $name));
				$this->getItem($name)->setDefaultValue($default);
			} else if ($type == 'bigint') {
				$this->addItem(new Text($name, $name));
				$this->getItem($name)->setDefaultValue($default);
			} else if ($type == 'char' and (int)$option > 0) {
				$this->addItem(new Text($name, $name));
				$this->getItem($name)->setDefaultValue($default);
			} else if ($type == 'time') {
				$this->addItem(new Text($name, $name));
				$this->getItem($name)->setDefaultValue($default);
			} else if ($type == 'varchar' and $option < 256) {
				$this->addItem(new Text($name, $name));
				$this->getItem($name)->addClass('widthMedium');
				$this->getItem($name)->setDefaultValue($default);
				if (strstr($comment, '[SQL]') !== false) {
					$this->getItem($name)->setSQLBuilder(true);
				}
			} else if ($type == 'varchar') {
				$this->addItem(new TextArea($name, $name));
				$this->getLastItem()->size(100, 6);
				$this->getItem($name)->setDefaultValue($default);
				if (strstr($comment, '[SQL]') !== false) {
					$this->getItem($name)->setSQLBuilder(true);
				}
			} else if ($type == 'text' or $type == 'longtext') {
				$this->addItem(new TextAreaEditor($name, $name));
				$this->getLastItem()->size(100, 6);
				$this->getItem($name)->setDefaultValue($default);
				if (strstr($comment, '[SQL]') !== false) {
					$this->getItem($name)->setSQLBuilder(true);
				}
			} else if ($type == 'tinyint' and $option == 1) {
				$this->addItem(new Switcher($name, $name));
				$this->getItem($name)->setDefaultValue($default);
			} else if ($type == 'tinyint') {
				$this->addItem(new Text($name, $name));
				$this->getItem($name)->setDefaultValue($default);
			} else if ($type == 'timestamp') {
				$this->addItem(new Text($name, $name));
				$this->getItem($name)->setUpdatable(false);
				$this->getItem($name)->addClass('datepicker');
				$this->getItem($name)->setDefaultValue($default);
			} else if ($type == 'date') {
				$this->addItem(new Text($name, $name));
				$this->getItem($name)->addClass('datepicker');
				$this->getItem($name)->setDefaultValue($default);
			} else if ($type == 'datetime') {
				$this->addItem(new DateTimeForm($name, $name));
				$this->getItem($name)->setDefaultValue($default);
			} else if ($type == 'enum') {
				$this->addItem(new Enum($name, $name));
				$t = str_replace("'", '', $option);
				$tmp = explode(',', $t);
				foreach ($tmp as $value) {
					$this->getItem($name)->addValue($value);
				}
				$this->getItem($name)->setDefaultValue($default);
			} else {

				$gnl = 'Form::type [' . $type . '] option [' . $option . '] is uncknow !<br>';
				Cadre_Base::addNotice('FORM_TYPE_Error', $gnl, 'error');
			}
		}

		$this->addItem(new Submit('Submit'));

		
	}

	function select()
	{
		$champ = "";

		foreach ($this->item as $item) {

			if ($item->getTableChamp() != '') {
				if ($champ != '') $champ .= ',';
				$champ .= $this->showTable() . ".`{$item->getTableChamp()}`";
			}
		}

		$query = "SELECT $champ FROM " . $this->showTable() . " WHERE {$this->where};";

		$this->debug('REQUETE', 'SELECT', $query);


		$this->selectData = $this->getQuery($query);

		foreach ($this->selectData as $line) {
			foreach ($line as $key => $value) {

				if (isset($this->itemBind[strtolower($key)])) {

					$item = $this->itemBind[strtolower($key)];
					$item->setSelectValue($value);
					$item->setSelectValueFound(true);
					if ($item->getValue() === null) {
						$item->setValue($value);
					}

					$this->debug('setSelectValue', get_Class($item) . '::' . $item->getName(), $value);
				}
			}
			break;
		}
	}

	function showFull()
	{


		$query = "SHOW FULL COLUMNS FROM `" . $this->table . "` FROM `" . $this->db . "`;";

		return $this->getQuery($query);
	}
	function describe($db, $table)
	{


		if ($db === null or $db == '') {
			$query = "DESCRIBE `$table` ;";
		} else {
			$query = "DESCRIBE `$db`.`$table` ;";
		}

		return DbCo::getQuery($query);
	}

	function showTable()
	{
		$table = "`{$this->table}`";
		if ($this->db != '') $table = "`{$this->db}`.`{$this->table}`";
		return $table;
	}

	function insert()
	{

		$column = '';
		$value = '';

		foreach ($this->item as $item) {
			$this->debug('DebugInsert', $item->getTableChamp(), 'getItemIsUpdatable['.$item->getItemIsUpdatable().'], getUpdatable['.$item->getUpdatable().'], getForceValue['.$item->getForceValue().'], getTableChamp['.$item->getTableChamp().']');
			if (($item->getItemIsUpdatable() and $item->getUpdatable()) or $item->getForceValue()!=null) {
				if ($item->getTableChamp() != '') {
					if ($column != '') $column .= ',';
					$column .= "`" . $item->getTableChamp() . "`";

					if ($value != '') $value .= ',';

					$value .= $this->normalizeString($item->getValue());
				}
			}
		}
		$table = "`{$this->table}`";
		if ($this->db != '');
		$table = "`{$this->db}`.`{$this->table}`";
		$commit="";
		if($this->autoCommit===true){
			$commit='COMMIT;';
		}
		$query = "INSERT INTO " . $this->showTable() . " ({$column}) VALUES ({$value}); $commit";
		$this->debug('REQUETE', 'INSERT', $query);
		if ($column != '') {
			$data = $this->getQuery($query);
			//$this->commit();
			$this->insert = true;
			$this->isInsert = true;
		}
	}

	function update()
	{

		$set = '';

		foreach ($this->item as $item) {

			$this->debug('UpdateValueOldNew', $item->getName(), $item->getSelectValue() . ' - ' . $item->getValue());

			if ($item->getItemIsUpdatable() and $item->getUpdatable() and $item->getSelectValue() !== $item->getValue()) {

				if ($item->getTableChamp() != null) {
					if ($set != '') $set .= ', ';
					if ($item->getValue() === null) {
						$set .= "`" . $item->getTableChamp() . "` = NULL";
					} else {
						$set .= "`" . $item->getTableChamp() . "` = " . $this->normalizeString($item->getValue());
					}
				}
			}
		}

		$commit="";
		if($this->autoCommit===true){
			$commit='COMMIT;';
		}

		$query = "UPDATE " . $this->showTable() . " SET {$set} WHERE {$this->where}; $commit";
		$this->debug('REQUETE', 'UPDATE', $query);
		if ($set != "") {

			$this->isUpdate = true;
			$this->update = true;
			
			$this->debug('ISUPDATE', 'TRUE', $this->getIsUpdate());
			$data = $this->getQuery($query);
			//$this->commit();
		}
	}

	function debug($title, $key, $value)
	{
		if ($this->debug != false) {
			echo $this->fullName . '=> title: ' . $title . ', key: ' . $key . ', value: [' . $value . ']<br>';
		}
	}

	function normalizeString($str)
	{
		if ($this->PDO == null) return $str;
		if (is_array($str)) {
			foreach ($str as &$item) {

				$item = $this->PDO->quote($item);
			}
		} else {

			$str = $this->PDO->quote($str);
		}

		return $str;
	}

	function getQuery($query)
	{
		if ($this->PDO == null) return;

		$statement = $this->PDO->query($query);

		$data = array();

		if ($statement) {

			while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
				$tmp = null;
				foreach ($row as $key => $value) {

					//$value = html_entity_decode($value);

					$tmp[$key] = $value;
				}
				array_push($data, $tmp);
			}
		}

		$error = $this->PDO->errorInfo();
		if ($error[1] != 0) {
			$context = "";
			$e = new Exception();
			$context = str_replace('/path/to/code/', '', $e->getTraceAsString());
			$context = str_replace('#', '<br>#', $context);
			//$context=self::debug_string_backtrace();

			$str = "<form><fieldset><legend>SQL ERROR</legend>";
			$str = "Query:<p>" . nl2br($query) . "</p>";
			$str .= "<div class=\"error\">";
			$str .= "SQLSTATE :                         $error[0]<br>";
			$str .= "Error:                         $error[1]<br>";
			$str .= "Message:                         $error[2]<br>";
			$str .= "Context:                         
				$context";
			$str .= "</div></fieldset></form>";
			$this->debug('REQUETE', 'Erreur', $str);
		}

		return $data;
	}


	function getFullName()
	{
		return $this->fullName;
	}

	function recoveryValue()
	{

		$update = false;

		// suppression des ancienne entr�e
		$_SESSION[$this->fullName] = array();

		$post = false;
		foreach ($_POST as $key => $value) {

			if (strstr($key, $this->fullName)) {
				$post = true;
				// echo 'true ';
			}
			//echo $key.'=>'.$this->fullName.'<br>';
		}
		// il a t'il des valeur du formulaire dans le post ?
		$this->debug('setPOSTValue', '$_POST[' . $this->fullName . ']', (int)isset($_POST[$this->fullName]));

		if (isset($_POST[$this->fullName]) or $post == true) {
			$this->postExist = true;
			// il s'agit d'une tentative d'update
			$update = true;

			// assignation des valeurs dans les items
			foreach ($this->item as $item) {
				$this->debug('CLASS', $item->getName(), get_class($item));
				$this->debug('FULLNAME', $item->getName(), $item->getFullName());
				if (isset($_POST[$item->getFullName()])) {
					$item->setValue($_POST[$item->getFullName()]);
					$item->setPOSTValue(true);
				}
				$this->debug('setPOSTValue', $item->getName(), $item->getValue());
			}
		}


		// r�cuperation des valeurs du select
		if ($this->isInsert == false and $this->table != null and $this->PDO != null and $this->where != null) {
			$this->select();

			// si r�cuperation select et si update valid�, alors update
			if ($update == true or $this->forceUpdate != false) {

				$this->update();
			}
		}
	}

	function showStart()
	{
		return '
		<form action="'.Tool::url().'" method="POST" ' . $this->showAction() . ' name="' . $this->fullName . '" id="' . $this->fullName . '" enctype="multipart/form-data" >';
	}

	function getIsInsert()
	{
		return $this->isInsert;
	}

	function getIsUpdate()
	{
		return $this->isUpdate;
	}

	function showEnd()
	{
		return '
		</form>';
	}

	function AJAX($value)
	{
		$this->ajax = $value;
	}

	function showAction()
	{

		if ($this->ajax == true) {
			$action = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			foreach ($_POST as $key => $value) {
				if ($action != '') $action .= '&';
				$action .= $key . '=' . $value;
			}
			return 'action="' . $action . '"';
		}

		if ($this->newRecord == true) {
			//return 'action="'.$_SERVER['PHP_SELF'].'"';
		}
	}

	function toString_OLD()
	{
		$str = '
		<form method="POST" class="formcorfu" ' . $this->showAction() . ' name="' . $this->fullName . '" enctype="multipart/form-data" >';

		foreach ($this->item as $item) {
			$str .= $item->toString();
		}

		$str .= '
		</form>';
		return $str;
	}

	function init(bool $force=false){

		if($force===true){
			$this->displayElement=array();
		}
		
		if(count($this->displayElement)==0){
			//$this->newRecord();
			$this->displayElement['showStart'] = new Item($this->showStart());

			foreach ($this->getItem() as $item) {
				
				$this->displayElement[$item->getName()] = new Field($item->getName(), $item);
					
				
			}

			$this->displayElement['showEnd'] = new Item($this->showEnd());

		}
		
	}

	function getElement($key){
		if(isset($this->displayElement[strtolower($key)])){
			return $this->displayElement[strtolower($key)];
		}
		else{
			return null;
		}
	}

	function toString()
	{
		if(count($this->displayElement)==0){
			$this->init();
		}

		$str = '';

		foreach ($this->displayElement as $k=>$field) {
				$item = $field->getValueBrut();
			if (is_object($item) and $item->getEnable() == true and $item->getDisplay() === true) {
				
				$str .= $field->toString();
					
			}
			else if (is_string($item)){
				
				$str .= $field->toString();
			}
		}

		return $str;
	}
}

abstract class FormItem
{
	protected $name = null;
	protected $tablechamp = null;
	protected $value = null;
	protected $selectValue = null;
	protected $selectValueFound = false;
	protected $parent = null;
	protected $fullname = null;
	protected $itemIsUpdatable = true;
	protected $updatable = true;
	protected $defaultValue = false;
	protected $classhtml = array();
	protected $idhtml = array();
	protected $listoption = array();
	protected $enable = true;
	protected $POSTValue = false;
	protected $postExist = false;
	protected $readOnly = false;
	protected $forceValue = null;
	protected $SQLBuilder = false;
	protected $autoCompletion = true;
	protected $required = false;
	protected $pattern = false;
	protected $tooltip = null;
	protected $placeHolder = null;
	protected $multiSelectValue = false;
	protected $display = true;


	static protected $staticclasshtml = array();
	static protected $staticidhtml = array();

	function __construct($name, $tablechamp = null)
	{
		$this->name = $name;
		$this->tablechamp = $tablechamp;
		$this->setParent(null);
	}

	function verifPost()
	{

		if (isset($_POST[$this->getFullName()])) {
			$this->setDefaultValue($_POST[$this->getFullName()]);
			$this->setValue($_POST[$this->getFullName()]);
			$this->setPostExist(true);
			if ($this->getParent() != null and $this->getParent()->getSaveValueInSession() == true) {
				$this->getParent()->setPostExist(true);
				$_SESSION[$this->getFullName()] = $_POST[$this->getFullName()];
			}
		}
		else if ($this->getParent() != null and $this->getParent()->getSaveValueInSession() == true and isset($_SESSION[$this->getFullName()]) == true) {
			$this->setDefaultValue($_SESSION[$this->getFullName()]);
			$this->setValue($_SESSION[$this->getFullName()]);
		}

		if($this->multiSelectValue===true){
			if($this->getParent()!=null and $this->getParent()->getPostExist()===true and $this->getPostExist()===false){
			
				$this->forceValue([]);
				$_SESSION[$this->getFullName()] = [];
				
			} 
		}
	}

	function setDisplay(bool $bool){
		$this->display=$bool;
	}
	function getDisplay(){
		return $this->display;
	}

	/**
	 * Get the value of placeHolder
	 */ 
	public function getPlaceHolder()
	{
		return $this->placeHolder;
	}

	/**
	 * Set the value of placeHolder
	 *
	 * @return  self
	 */ 
	public function setPlaceHolder($placeHolder)
	{
		$this->placeHolder = $placeHolder;

		return $this;
	}
	

	function setAutoCompletion($value)
	{
		$this->autoCompletion = (bool)$value;
	}

	function getAutoCompletion()
	{
		return $this->autoCompletion;
	}
	function setRequired($value)
	{
		$this->required = (bool)$value;
	}

	function getRequired()
	{
		return $this->required;
	}
	function setPattern($value)
	{
		$this->pattern = $value;
	}

	function getPattern()
	{
		return $this->pattern;
	}

	function setPOSTValue($value)
	{
		$this->POSTValue = $value;
	}

	function getPOSTValue()
	{
		$this->POSTValue;
	}
	function setPostExist(bool $bool)
	{
		$this->postExist = $bool;
	}

	function getPostExist()
	{
		return $this->postExist;
	}

	function setSQLBuilder($value)
	{
		$this->SQLBuilder = $value;
	}

	function getSQLBuilder()
	{
		return $this->SQLBuilder;
	}
	function setReadOnly($value)
	{
		$this->readOnly = $value;
	}

	function getReadOnly()
	{
		$this->readOnly;
	}

	function getItemIsUpdatable()
	{
		return $this->itemIsUpdatable;
	}
	function setItemIsUpdatable($value)
	{
		$this->itemIsUpdatable = $value;
	}
	function setUpdatable($value)
	{
		$this->updatable = $value;
		$this->enable = !$value;
	}
	function getUpdatable()
	{
		return $this->updatable;
	}
	function getDefaultValue()
	{
		return $this->defaultValue;
	}
	function setDefaultValue($value)
	{
		$this->defaultValue = $value;
	}

	function setParent($parent)
	{
		$this->parent = $parent;
		if ($this->parent == null) {
			$this->fullname = str_replace(' ', '_', '_' . $this->name);
		} else {
			$this->fullname = str_replace(' ', '_', $this->parent->getFullName() . '_' . $this->name);
		}

		$this->verifPost();
	}

	function getParent()
	{
		return $this->parent;
	}
	function getFullName()
	{
		return $this->fullname;
	}

	function addClass($value)
	{

		$this->classhtml[] = $value;
	}

	function cleanClass()
	{
		$this->classhtml=array();
	}

	function addId($value)
	{
		$this->idhtml[] = $value;
	}

	static function defaultClass($value)
	{
		static::$staticclasshtml[] = $value;
	}

	static function defaultId($value)
	{
		static::$staticidhtml[] = $value;
	}


	function setToolTip($title, $body)
	{
		$this->tooltip = array('body' => $body, 'title' => $title);
	}
	function setEnable($value)
	{
		$this->enable = $value;
	}
	function getEnable()
	{
		return $this->enable;
	}

	function showClass()
	{
		$str = "";

		if (count($this->classhtml)) {
			foreach ($this->classhtml as $class) {
				$str .= $class . ' ';
			}
		} else {
			foreach (static::$staticclasshtml as $class) {
				$str .= $class . ' ';
			}
		}

		return $str;
	}
	function showSQLBuilder()
	{
		if ($this->getSQLBuilder() == true) {
			$b = new SQLBuilder($this->showId());
			return $b->toString();
		}
	}

	function showId()
	{
		$str = "";

		if (count($this->idhtml)) {
			foreach ($this->idhtml as $id) {
				if ($str != '') $str .= ' ';
				$str .= $id;
			}
			if ($str == '') {
				$str = $this->name;
			}
		} else {
			foreach (static::$staticidhtml as $id) {
				if ($str != '') $str .= ' ';
				$str .= $id;
			}
			if ($str == '') {
				$str = $this->name;
			}
		}
		return $str;
	}

	function showAutoCompletion()
	{
		if ($this->getAutoCompletion() == true) {
			return;
		}
		return ' autocomplete="off" ';
	}

	function showRequired()
	{
		if ($this->getRequired() == true) {
			return ' required="required" ';
		}
		return;
	}
	function showPattern()
	{
		if ($this->getpattern() != '') {
			return ' pattern="' . $this->getpattern() . '" ';
		}
		return;
	}

	function setSelectValue($value)
	{
		$this->selectValue = $value;
	}
	function getSelectValue()
	{
		return $this->selectValue;
	}

	function setSelectValueFound($value)
	{
		$this->selectValueFound = $value;
	}

	function getName()
	{
		return $this->name;
	}

	function getTableChamp()
	{
		return $this->tablechamp;
	}

	function getValue()
	{

		if(is_array($this->getValueOrDefautlValue())){
			foreach($this->getValueOrDefautlValue() as &$l){
				$l = trim($l);
			}
			unset($l);
			return $this->getValueOrDefautlValue();
		}
		
		return trim($this->getValueOrDefautlValue());
	}
	function getValueText()
	{

		if(is_array($this->getValueOrDefautlValue())){
			$tmp='';
			foreach($this->getValueOrDefautlValue() as &$l){
				if($tmp!='')$tmp.=', ';
				$tmp.= htmlentities(stripslashes(trim($l())));
			}
			unset($l);
			return $tmp;
		}
		
		return htmlentities(stripslashes(trim($this->getValueOrDefautlValue())));
	}

	function getParentFullName()
	{


		if ($this->parent == null) {
			$this->getFullName();
		} else {
			$this->parent->getFullName();
		}
	}

	function forceValue($value)
	{
		$this->forceValue = $value;
		$_SESSION[$this->getFullName()]=$value;
	}
	function getForceValue()
	{
		return $this->forceValue ;
	
	}


	function getValueOrDefautlValue()
	{
		if ($this->forceValue !== null) {
			return $this->forceValue;
		} else if ($this->postExist == true or $this->value != null) {
			return $this->value;
		} else if ($this->selectValueFound == true) {
			return $this->getSelectValue();
		} else if ($this->defaultValue !== false) {
			return $this->getDefaultValue();
		}
	}

	function setValue($value)
	{
		$this->value = $value;
	}

	function getOption()
	{
		return $this->listoption;
	}

	function addOption($name, $value)
	{
		$this->listoption[$name] = $value;
	}

	function showHtmlOption()
	{
		$str = "";

		foreach ($this->listoption as $key => $value) {
			$str .= ' ' . $key . '="' . $value . '" ';
		}
		return $str
			. $this->showAutoCompletion()
			. $this->showRequired()
			. $this->showPattern();
	}

	function showPlaceHolder()
	{
		return $this->placeHolder;
		/*if ($this->getDefaultValue() != false) {
			return $this->getDefaultValue();
		}else{
			return $this->placeHolder;
		}*/
		
	}

	function showEnable()
	{
		$str = "";
		if ($this->enable != true) {
			$str .= 'disabled';
		}
		if ($this->readOnly == true) {
			$str .= ' readonly';
		}

		return $str;
	}

	abstract function show();

	function toString()
	{
		if ($this->tooltip == null) {
			return $this->show();
		} else {
			$value = new Tooltips($this->show(), $this->tooltip['title'], $this->tooltip['body']);
			return $value->toString();
		}
	}
}


class FreeText extends FormItem
{

	function show()
	{

		return '<div ' . $this->showHtmlOption() . ' class="' . $this->showClass() . '" id="' . $this->showId() . '">' . $this->value . '</div>';
	}
}
class Text extends FormItem
{

	static protected $staticclasshtml = array();
	static protected $staticidhtml = array();

	function show()
	{
		return $this->showSQLBuilder() . '
			<input type="text" for="' . $this->getParentFullName() . '" ' . $this->showHtmlOption() . ' class="' . $this->showClass() . '" id="' . $this->showId() . '" name="' . $this->fullname . '" placeholder="' . $this->showPlaceHolder() . '" value="' . $this->getValueText() . '" ' . $this->showEnable() . '>';
	}
}

class Number extends FormItem
{

	static protected $staticclasshtml = array();
	static protected $staticidhtml = array();
	private $min=null;
	private $max=null;

	function setMinMax($min,$max){
		$this->min = $min;
		$this->max = $max;
	}

	function showMinMax(){
		if($this->min!==null and $this->max!==null){
			return 'min="'.$this->min.'" max="'.$this->max.'"';
		}
	}
	

	function show()
	{
		return $this->showSQLBuilder() . '
			<input type="number" for="' . $this->getParentFullName() . '" ' . $this->showHtmlOption() . ' class="' . $this->showClass() . '" id="' . $this->showId() . '" name="' . $this->fullname . '" placeholder="' . $this->showPlaceHolder() . '" value="' . $this->getValueText() . '" ' . $this->showEnable() . ' '.$this->showMinMax().' >';
	}
}

class DateTimeForm extends FormItem
{

	static protected $staticclasshtml = array();
	static protected $staticidhtml = array();
	
	
	function getValue(){
		$value = parent::getValue();
		$value = str_replace('T', ' ',$value).':00';
		if(strlen($value)==19)return $value;
		return '';
	}

	function show()
	{
		$value = str_replace(' ','T',parent::getValue());
		if(substr($value,-3)==':00'){
			$value = substr($value,0,strlen($value)-3);
		}
		return $this->showSQLBuilder() . '
			<input type="datetime-local" for="' . $this->getParentFullName() . '" ' . $this->showHtmlOption() . ' class="' . $this->showClass() . '" id="' . $this->showId() . '" name="' . $this->fullname . '" placeholder="' . $this->showPlaceHolder() . '" value="' . $value . '" ' . $this->showEnable() . ' >';
	}
}
class Password extends FormItem
{

	static protected $staticclasshtml = array();
	static protected $staticidhtml = array();

	function show()
	{
		return '
			<input type="password" for="' . $this->getParentFullName() . '" ' . $this->showHtmlOption() . ' class="' . $this->showClass() . '" id="' . $this->showId() . '" name="' . $this->fullname . '" placeholder="' . $this->showPlaceHolder() . '" value="' . $this->getValue() . '" ' . $this->showEnable() . '>';
	}
}

class Hidden extends FormItem
{

	static protected $staticclasshtml = array();
	static protected $staticidhtml = array();
	protected $display=false;

	function show()
	{
		return '
			<input type="hidden" for="' . $this->getParentFullName() . '" ' . $this->showHtmlOption() . ' class="' . $this->showClass() . '" id="' . $this->showId() . '" name="' . $this->fullname . '" placeholder="' . $this->showPlaceHolder() . '" value="' . $this->getValue() . '" ' . $this->showEnable() . '>';
	}
}

class Textarea extends FormItem
{

	static protected $staticclasshtml = array();
	static protected $staticidhtml = array();
	private $col = "";
	private $row = "";

	function size($col, $row)
	{
		$this->col = $col;
		$this->row = $row;
	}

	function show()
	{

		return $this->showSQLBuilder() . '
			<textarea for="' . $this->getParentFullName() . '" ' . $this->showHtmlOption() . ' class="' . $this->showClass() . '" id="' . $this->showId() . '" cols="' . $this->col . '" rows="' . $this->row . '" placeholder="' . $this->showPlaceHolder() . '" name="' . $this->fullname . '" ' . $this->showEnable() . '>' . $this->getValueText() . '</textarea>';
	}
}

class TextareaEditor extends FormItem
{

	static protected $staticclasshtml = array();
	static protected $staticidhtml = array();
	private $col = "";
	private $row = "";
	static private $addscript = true;

	function size($col, $row)
	{
		$this->col = $col;
		$this->row = $row;
	}

	function show()
	{

		$str = '';

		$str .= '
			<textarea hidden  edit="true" for="' . $this->getParentFullName() . '" ' . $this->showHtmlOption() . ' class="' . $this->showClass() . '" id="' . $this->fullname . '" cols="' . $this->col . '" rows="' . $this->row . '" placeholder="' . $this->showPlaceHolder() . '" name="' . $this->fullname . '" ' . $this->showEnable() . '>' . $this->getValue() . '</textarea>';
		$str .=	"<script AJAX_KEY=\"" . (bool)self::getOption('AJAX_KEY') . "\"> ";

		
			$t = "$('#" . $this->fullname . "').trumbowyg(
				{
					
					semantic: false,
					btns: [
						['viewHTML'],
						['undo', 'redo'], 
						['custom'],
						['formatting'],
						['fontfamily'],
						['fontsize'],
						['strong', 'em', 'del','underline'],
						['foreColor', 'backColor'],
						['link'],
						['insertImage'],
						['preformatted'],
						['justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'],
						['table'],
						['unorderedList', 'orderedList'],
						['horizontalRule'],
						['removeformat'],
						['historyUndo', 'historyRedo'],
						['fullscreen']
						
					]
				
				}
			   );  </script>";
		


		return $str;
	}
}

class Checkbox extends FormItem
{

	static protected $staticclasshtml = array();
	static protected $staticidhtml = array();

	function getValue()
	{

		if ($this->parent != null) $this->parent->debug(get_class($this) . '::' . $this->name, '$this->selectValue', $this->selectValue);
		if ($this->parent != null) $this->parent->debug(get_class($this) . '::' . $this->name, '$this->value', $this->value);

		if ($this->value == '1' or $this->value == 'on') {
			if ($this->parent != null) $this->parent->debug(get_class($this) . '::' . $this->name, '$this->getvalue()', '1');
			//echo 'value-1';
			return '1';
		} else if ($this->parent != null and $this->parent->getPostExist() == true) {
			if ($this->parent != null) $this->parent->debug(get_class($this) . '::' . $this->name, '$this->getvalue()', '0');
			//echo 'value-2';
			return '0';
		} else if ($this->selectValueFound == true) {
			$this->value = $this->selectValue;
			//echo 'value-3';
		} else if ($this->defaultValue != false) {
			$this->value = $this->defaultValue;
			//echo 'value-4';
		}

		if ($this->value == '1' or $this->value == 'on') {
			if ($this->parent != null) $this->parent->debug(get_class($this) . '::' . $this->name, '$this->getvalue()', '1');
			//echo 'value-5';
			return '1';
		}
		if ($this->parent != null) $this->parent->debug(get_class($this) . '::' . $this->name, '$this->getvalue()', '0');
		//echo 'value-6';
		return '0';
	}

	function showEnable()
	{
		$str = "";
		if ($this->enable != true) {
			$str .= 'disabled="disabled"';
		}

		return $str;
	}





	function show()
	{

		$checked = "";
		if ($this->getValue() == 1) $checked = "checked";

		return '
			<Input type="checkbox" for="' . $this->getParentFullName() . '" ' . $this->showHtmlOption() . ' class="' . $this->showClass() . '" id="' . $this->fullname . ' ' . $this->showId() . '" name="' . $this->fullname . '" ' . $checked . ' ' . $this->showEnable() . '>';
	}
}

class Switcher extends Checkbox
{
	function show()
	{

		$checked = "";
		if ($this->getValue() == 1) $checked = "checked";
		$ajaxkey = self::getOption('AJAX_KEY');

		$script = '
		<script AJAX_KEY="' . (int)$ajaxkey . '">
		
			$.switcher("#' . $this->fullname . '");
		  
		</script>';
		return $script . '
			<Input type="checkbox" for="' . $this->getParentFullName() . '" ' . $this->showHtmlOption() . ' class="ONOFF ' . $this->showClass() . '" id="' . $this->fullname . '" name="' . $this->fullname . '" ' . $checked . ' ' . $this->showEnable() . '>';
	}
}

class Checkbox2 extends FormItem
{

	static protected $staticclasshtml = array();
	static protected $staticidhtml = array();
	private $valueid = "";

	function getValue()
	{
		$value = parent::getValue();

		if ($value == 'on' or $value == '1') {
			return 1;
		} else {
			return 0;
		}
	}

	function showEnable()
	{
		$str = "";
		if ($this->enable != true) {
			$str .= 'disabled="disabled"';
		}

		return $str;
	}



	function show()
	{

		$checked = "";
		if ($this->value == 'on' or $this->value == '1') $checked = "checked";

		return '
			<Input type="checkbox" for="' . $this->getParentFullName() . '" ' . $this->showHtmlOption() . ' class="' . $this->showClass() . '" id="' . $this->fullname . ' ' . $this->showId() . '" name="' . $this->fullname . '[]" ' . $checked . ' ' . $this->showEnable() . '>';
	}
}

class ListPivot extends FormItem
{
	private $items = array();
	private $popul_table = "";
	private $popul_option = "1";
	private $nameOfValue = "";
	private $nameOfLabel = "";

	function __construct($name, $table, $option, $value, $label)
	{
		parent::__construct($name, null);
		$this->setPopul($table, $option, $value, $label);
	}

	function setPopul($table, $option, $value, $label)
	{
		$this->popul_table = $table;
		$this->popul_option = $option;
		$this->nameOfValue = $value;
		$this->nameOfLabel = $label;
		$this->createPopul();
	}

	function createPopul()
	{
		$query = "SELECT `{$this->nameOfValue}`,`{$this->nameOfLabel}` FROM `{$this->popul_table}` WHERE {$this->popul_option} ;";
		$this->items = $this->parent->getQuery($query);
	}

	function show()
	{
	}
}

class Submit extends FormItem
{

	static protected $staticclasshtml = array();
	static protected $staticidhtml = array();
	protected $itemIsUpdatable = false;

	function show()
	{
		return '
			<input type="submit" for="' . $this->getParentFullName() . '" ' . $this->showHtmlOption() . ' class="' . $this->showClass() . '" id="' . $this->showId() . '" value="' . $this->name . '" name="' . $this->getParentFullName() . '">';
	}
}

class Button extends FormItem
{

	static protected $staticclasshtml = array();
	static protected $staticidhtml = array();
	protected $itemIsUpdatable = false;

	function showValue(){
		if($this->getValue()!=''){
			return $this->getValue();
		}
		else{
			return $this->getName();
		}
	}

	function show()
	{
		return '
			<input type="button" for="' . $this->getParentFullName() . '" ' . $this->showHtmlOption() . ' class="' . $this->showClass() . '" id="' . $this->showId() . '" value="' . $this->showValue() . '" name="' . $this->getParentFullName() . '">';
	}
}

class Tooltips extends FormItem
{
	protected $value = '';
	protected $title = '';
	protected $tips = '';


	function __construct($value, $title, $tips)
	{
		$this->value = $value;
		$this->title = $title;
		$this->tips = $tips;
	}

	function show()
	{

		$title = '';
		if ($this->title != '') {
			$title = '<strong>' . $this->title . '</strong><br>';
		}
		return
			'<div class="tool">' . $this->value . '
		<div class="tooltips">' . '
			<div class="tooltipstext">' . $title . $this->tips . '</div> 
		</div></div>';
	}
}

class Enum extends FormItem
{

	static protected $staticclasshtml = array();
	static protected $staticidhtml = array();
	protected $listValue = array();

	function __construct($name, $tablechamp = null, $dataset = null, $fieldLabel = null)
	{
		parent::__construct($name, $tablechamp);
		$this->addAllValue($dataset, $fieldLabel);
	}

	function addAllValue($dataset, $fieldLabel)
	{
		if ($dataset != null) {

			foreach ($dataset as $key => $line) {

				if (is_array($line) == true and $fieldLabel != null and isset($line[$fieldLabel])) {
					$this->addValue($line[$fieldLabel]);
				} else if (is_array($line) == false) {
					$this->addValue($line);
				}
			}
		}
	}

	function show()
	{

		return '
			<select for="' . $this->getParentFullName() . '" ' . $this->showHtmlOption() . ' class="' . $this->showClass() . '" id="' . $this->fullname . ' ' . $this->showId() . '" name="' . $this->fullname . '" ' . $this->showEnable() . '>' . $this->showValue() . '</select>';
	}

	function addValue($value)
	{
		$this->listValue[] = $value;
	}

	function showValue()
	{

		$str = '';
		foreach ($this->listValue as $item) {
			$checked = '';
			if ($item == $this->getValueOrDefautlValue()) $checked = ' selected';

			$str .= '<option value="' . $item . '" ' . $checked . '>' . $item . '</option>';
		}

		return $str;
	}
}

class SelectList extends FormItem
{

	static protected $staticclasshtml = array();
	static protected $staticidhtml = array();
	protected $listValue = array();

	function __construct($name, $tablechamp = null, $dataset = null, $fieldLabel = null, $fieldValue = null)
	{
		parent::__construct($name, $tablechamp);
		$this->addAllSelectOption($dataset, $fieldLabel, $fieldValue);
	}

	function show()
	{

		return '
			<select for="' . $this->getParentFullName() . '" ' . $this->showHtmlOption() . ' class="' . $this->showClass() . '" id="' . $this->fullname . ' ' . $this->showId() . '" name="' . $this->fullname . '" ' . $this->showEnable() . '>' . $this->showValue() . '</select>';
	}

	function addAllSelectOption($dataset, $fieldLabel, $fieldValue)
	{
		if ($dataset != null) {

			foreach ($dataset as $key => $line) {

				if (is_array($line) == true and $fieldLabel != null and $fieldValue != null and isset($line[$fieldLabel]) and isset($line[$fieldValue])) {
					$this->addSelectOption($line[$fieldLabel], $line[$fieldValue]);
				} else if (is_array($line) == false) {
					$this->addSelectOption($key, $line);
				}
			}
		}
	}

	function addSelectOption($label, $value = null)
	{
		if ($value === null) {
			$this->listValue[$label] = $label;
		} else {
			$this->listValue[$label] = $value;
		}
	}

	function showValue()
	{
		$str = '';
		foreach ($this->listValue as $label => $value) {
			$checked = '';
			if ($value == $this->getValueOrDefautlValue()) $checked = ' selected';
			$str .= '<option value="' . $value . '" ' . $checked . '>' . $label . '</option>';
		}

		return $str;
	}
}

class MultiSelect extends SelectList{

	protected $multiSelectValue=true;

	function __construct($name, $tablechamp = null, $dataset = null, $fieldLabel = null, $fieldValue = null)
	{
		parent::__construct($name, $tablechamp);
		$this->addAllSelectOption($dataset, $fieldLabel, $fieldValue);
		
	}
	function showValue()
	{
		
		$str = '';
		foreach ($this->listValue as $label => $value) {
			$checked = '';
			
			if (is_array($this->getValueOrDefautlValue()) and in_array($value, $this->getValueOrDefautlValue())) $checked = ' selected';
			$str .= '<option value="' . $value . '" ' . $checked . '>' . $label . '</option>';
		}

		return $str;
	}
	function show()
	{

		return '
			<select for="' . $this->getParentFullName() . '" ' . $this->showHtmlOption() . ' class="js-example-basic-multiple ' . $this->showClass() . '" multiple="multiple" id="' . $this->fullname . ' ' . $this->showId() . '" name="' . $this->fullname . '[]" ' . $this->showEnable() . '>' . $this->showValue() . '</select>
			<script AJAX_KEY="' . cadre_base::getOption('AJAX_KEY') . '">
					$(".js-example-basic-multiple").select2();
					</script>';
			;
	}
}
class ico
{

	static function toString($name)
	{

		return '<img src="' . Tool::urlPictures($name) . '" width="20" height="20">';
	}
}



class SQLBuilder
{

	private $data = array();
	private $name = '';

	function __construct($name)
	{
		$this->name = $name;
	}

	function constructData()
	{
		$data = DbCo::getListOfJoin();
		$result = array();
		foreach ($data as $line) {
			$table = $line['nameOfTable'];
			$db = $line['nameOfDB'];
			$alias = $line['alias'];
			$subQuery = $line['subQuery'];

			if ($subQuery == '') {
				$d = DbCo::showFull($db, $table);
				foreach ($d as $l) {
					$champ = $l['Field'];
					$result[] = $alias . '.' . $champ;
				}
			}
		}

		$_SESSION[get_class($this)] = $result;
	}

	function toString()
	{

		if (isset($_SESSION[get_class($this)])) {
			$data = $_SESSION[get_class($this)];
		} else {
			$this->constructData();
			$data = $_SESSION[get_class($this)];
		}


		$elements = '';
		foreach ($data as $value) {
			if ($elements != '') $elements .= ',';
			$elements .= '"' . $value . '"';
		}

		$str = '		
		<script AJAX_KEY="' . cadre_base::getOption('AJAX_KEY') . '">
		
		$( function() {
		  var availableTags = [
			' . $elements . '
		  ];
		  function split( val ) {
			return val.split( / \s*/ );
		  }
		  function extractLast( term ) {
			return split( term ).pop();
		  }
	   
		  $( "#' . $this->name . '" )
			// don\'t navigate away from the field on tab when selecting an item
			.on( "keydown", function( event ) {
			  if ( event.keyCode === $.ui.keyCode.TAB &&
				  $( this ).autocomplete( "instance" ).menu.active ) {
				event.preventDefault();
			  }
			})
			.autocomplete({
			  minLength: 0,
			  source: function( request, response ) {
				// delegate back to autocomplete, but extract the last term
				response( $.ui.autocomplete.filter(
				  availableTags, extractLast( request.term ) ) );
			  },
			  focus: function() {
				// prevent value inserted on focus
				return false;
			  },
			  select: function( event, ui ) {
				var terms = split( this.value );
				// remove the current input
				terms.pop();
				// add the selected item
				terms.push( ui.item.value );
				// add placeholder to get the comma-and-space at the end
				terms.push( "" );
				this.value = terms.join( " " );
				return false;
			  }
			});
		} );
		</script>
		
	 ';

		return $str;
	}
}


class FullForm
{

	private $title = '';
	private $table = '';
	private $db = '';
	private $shatable = '';
	private $form = null;
	private $view = null;
	private $PRI = null;
	private $select = null;
	private $new = null;
	private $newDuplicate = null;
	private $tagSelect = null;
	private $tagOutbound = null;
	private $tagNew = null;
	private $outbound = null;
	private $formSTR = '';
	private $describe = '';
	private $where = null;
	private $orderby = null;
	private $dataViewer = null;
	private $ajax = false;
	private $debug = false;
	private $postExist = false;
	private $champ = array();
	private $listJoin = array();
	private $headerIfUpdate = true;
	private $headerIfCreate = true;

	function __construct($title, $table, $where = null, $orderby = null, $db = null, $champ = null, $option = array())
	{
		$pre = "";
		if (isset($GLOBALS['prefixe_include'])) {
			$pre = $GLOBALS['prefixe_include'];
		}
		include_once($pre . "ajax.php");
		$this->title = $title;
		$this->table = $table;
		$this->db = $db;
		$this->where = $where;
		$this->orderby = $orderby;
		$this->shatable = sha1($table);
		$this->tagSelect = sha1($this->shatable . '::Select');
		$this->tagNew = sha1($this->shatable . '::New');
		$this->tagNewDuplicate = sha1($this->shatable . '::NewDuplicate');
		$this->tagOutbound = sha1($this->shatable . '::Outbound');

		$option2 = array();
		foreach ($option as $key => $value) {
			$option2[strtolower($key)] = $value;
		}

		if (isset($option2['debug'])) $this->debug = (bool) $option2['debug'];
		if (isset($option2['headerifupdate'])) $this->headerIfUpdate = (bool) $option2['headerifupdate'];
		if (isset($option2['headerifcreate'])) $this->headerIfCreate = (bool) $option2['headerifcreate'];

		if ($champ != null) $this->champ = $champ;

		if (isset($_GET[$this->tagSelect])) {
			$this->select = $_GET[$this->tagSelect];
		}

		if (isset($_GET[$this->tagOutbound])) {
			$this->outbound = $_GET[$this->tagOutbound];
		}

		if (isset($_GET[$this->tagNew])) {
			$this->new = $_GET[$this->tagNew];
		}

		if (isset($_GET[$this->tagNewDuplicate])) {
			$this->newDuplicate = $_GET[$this->tagNewDuplicate];
		}

		$this->describe = $this->describe($db, $table);

		foreach ($this->describe as $line) {
			if ($line['Key'] == 'PRI') {
				$this->PRI = $line['Field'];
			}
		}

		$this->dataViewer = new DataViewer($this->table, $this->where, $this->orderby, $this->db, null, $this->champ);
		$this->init();
	}

	function setDebug($value)
	{
		$this->debug = $value;
	}

	function getPostExist()
	{
		return $this->dataViewer->getPostExist();
	}

	function getDataViewer()
	{
		return $this->dataViewer;
	}

	function AJAX($value)
	{
		$this->ajax = $value;
		if ($this->ajax == true and $this->form != null) $this->form->ajax(true);
	}

	function addJoin($db, $table, $alias, $where)
	{
		$this->listJoin[] = array('db' => $db, 'table' => $table, 'alias' => $alias, 'where' => $where);
	}
	

	function showAction()
	{

		if ($this->ajax == true) {
			$action = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			foreach ($_POST as $key => $value) {
				if ($action != '') $action .= '&';
				$action .= $key . '=' . $value;
			}
			return 'action="' . $action . '"';
		}

		if ($this->newRecord == true) {
			//return 'action="'.$_SERVER['PHP_SELF'].'"';
		}
	}



	function isSelect()
	{
		return $this->select;
	}
	function isNew()
	{
		return $this->new;
	}

	function createView()
	{
		$where = '';
		$orderby = '';
		if ($this->where != null) $where = " WHERE " . $this->where;
		if ($this->orderby != null) $orderby = " ORDER BY " . $this->orderby;
		$this->view = DbCo::getQuery('SELECT * FROM ' . $this->showTable() . $where . $orderby . ';');
	}

	function addOption()
	{
		if ($this->PRI != null) {
			$pri = $this->PRI;
			$view = $this->dataViewer->getData();
			$describe = $this->dataViewer->getDescribe();

			foreach ($describe as $line) {
				if ($pri == $line['Field']) {
					$pri = $line['nameOfChamp'];
					break;
				}
			}



			foreach ($view as &$line) {
				if (isset($line[$pri])) {
					$tmpline = array();
					$tmpline['Option'] = '';
					if (is_numeric($line[$pri])) {
						if ($this->select != $line[$pri]) {
							//$tmpline['Option'].='<span class="inline"> <a href="'.Tool::url(array($this->tagNewDuplicate=>$line[$pri],$this->tagSelect=>null,$this->tagOutbound=>'true')).'">'.ico::toString('duplicate2.png').'</a>';
							$tmpline['Option'] .= '<span class="inline"> <a href="' . Tool::url(array($this->tagNew => null, $this->tagSelect => $line[$pri], $this->tagNewDuplicate => null, $this->tagOutbound => 'true')) . '">' . ico::toString('select.png') . '</a>';
						} else {
							$tmpline['Option'] .= ' <a href="' . Tool::url(array($this->tagSelect => null, $this->tagOutbound => null, $this->tagNew = null, $this->tagNewDuplicate => null)) . '">' . ico::toString('deselect.png') . '</a>';
						}
					}

					$line = array_merge($tmpline, $line);
				}
			}
			$this->dataViewer->setData($view);
		}
	}

	function addNewRecord()
	{
		return;
		if (isset($this->view[0])) {
			$tmp = array();

			foreach ($this->view as $lines) {
				foreach ($lines as $key => $value) {
					$tmp[$key] = '';
					if ($key == 'Option') {
						$tmp[$key] = '';
					}
				}
				break;
			}
			$this->view[] = $tmp;
		}
	}

	function getSelectID()
	{
		return $this->select;
	}


	function init()
	{

		$this->addOption();
		$this->addNewRecord();

		if ($this->select or $this->new) {

			if ($this->select) {
				$this->form = new Form($this->title, $this->table, DbCo::getPDO(), $this->PRI . '=' . $this->select, $this->db);
				$this->form->setdebug($this->debug);
				//if(User::get('per')=='093318')$this->form->setDebug(true);
				$this->form->autocreate();
				if ($this->ajax == true) $this->form->ajax(true);
			}

			if ($this->new) {
				$this->form = new Form($this->title, $this->table, DbCo::getPDO(), null, $this->db);
				$this->form->setdebug($this->debug);
				$this->form->autocreate();
				if ($this->ajax == true) $this->form->ajax(true);
			}
		}
	}

	function describe($db, $table)
	{


		if ($db === null or $db == '') {
			$query = "DESCRIBE `$table` ;";
		} else {
			$query = "DESCRIBE `$db`.`$table` ;";
		}

		return DbCo::getQuery($query);
	}

	function addChamp($array)
	{
		$this->dataViewer->addChamp($array);
	}

	function showTable()
	{
		$table = "`{$this->table}`";
		if ($this->db != '') $table = "`{$this->db}`.`{$this->table}`";
		return $table;
	}

	function getForm()
	{
		return $this->form;
	}

	function toString()
	{



		if ($this->form != null) {


			if ($this->select or $this->new) {

				if ($this->select) {

					$this->form->recoveryValue();

					if ($this->form->getUpdate() == true and $this->headerIfUpdate) {

						$this->goHeader();
					}
				}

				if ($this->new) {

					$this->form->newRecord();

					if ($this->form->getInsert() == true and $this->headerIfCreate) {
						$this->goHeader();
					}
				}
			}



			if ($this->outbound != null) {
				$array = array();
				$list = $this->form->getItem();
				$str = "";

				$array[] = new Item($this->form->showStart());
				foreach ($list as $item) {
					if ($item->getEnable() == true) {
						$array[] = new Field($item->getName(), $item->toString());
					}
				}
				$array[] = new Item($this->form->showEnd());

				$str = '';
				foreach ($array as $item) {
					$str .= $item->toString();
				}

				$this->formSTR = $str;
			} else {
				$view = $this->dataViewer->getData();
				foreach ($view as &$line) {
					if ($line[$this->PRI] == $this->select) {
						foreach ($line as $fkey => &$fitem) {
							if ($this->form->getItem($fkey) != null) $fitem = $this->form->getItem($fkey)->toString();
							if ($fkey == 'Option') $fitem = $this->form->getItem('submit')->toString() . $fitem;
						}
					}
				}
				$this->dataViewer->setData($view);
			}
		}

		$str = '';
		if ($this->form != null) $str .= $this->form->showStart();
		$data = $this->dataViewer->getData();
		$str .= $this->formSTR;
		$row = $this->dataViewer->getReload() . ' ';
		$row .= $this->dataViewer->getRowCount();
		$searsh = $this->dataViewer->getGlobalSearch();
		$add = ' <a href="' . Tool::url(array($this->tagSelect => null, $this->tagNewDuplicate = null, $this->tagNew => '1', $this->tagOutbound => '1')) . '">' . 'New Record' . '</a>';
		$add .= ' - ' . $searsh;


		foreach ($data as &$line) {
			foreach ($line as &$value) {

				if (strlen($value) > 10000) {
					$value = '<span class="Box Bad">the value exceeds 10.000 characters and cannot be displayed</span>';
				} else if (strlen($value) > 100) {
					$value = '<div class="overflow2">' . $value . '</div>';
				}
			}
		}
		unset($line);
		unset($value);
		$set = new DataSet($row . $add, $data);
		$str .= '<form name="' . $this->dataViewer->uniqueName() . '" method="POST" ' . $this->dataViewer->showAction() . '>';
		$str .= $set->toString();
		$str .= $this->dataViewer->getPagination();
		$str .= '</form>';
		if ($this->form != null) $str .= $this->form->showEnd();

		return $str;
	}

	function goHeader()
	{
		header('Location: ' . Tool::url(array($this->tagSelect => null, $this->tagNew => null, $this->tagOutbound => null)));
	}

	function headerIsUpdate()
	{
		$this->goHeader();
	}
	function headerIscreate()
	{
		$this->goHeader();
	}
}



class DataViewer
{
	private $dataset = null;
	private $tableName = null;
	private $dbName = null;
	private $describe = null;
	private $where = null;
	private $orderby = null;
	private $listChampAvailable = array();
	private $count = 0;
	private $queryNotLimit = '';
	private $querySqlPrepare = array();
	private $query = '';
	private $queryCount = '';
	private $listChamp = array();
	private $champArray = array();
	public $sqlPrepare = array();
	private $listJoin = array();
	private $limit = 25;
	private $page = 1;
	private $ajax = false;
	private $postExist = false;
	private $uniqueName = null;
	private $maxItemMultiSelect = 20;
	private $global_search = null;
	private $showWhereData = null;
	public $request = false;

	function __construct($tableName, $where = null, $orderby = null, $dbName = null, $uniqueName = null, $champ = null)
	{
		$this->tableName = $tableName;
		$this->dbName = $dbName;
		$this->where = $where;
		$this->orderby = $orderby;
		$this->uniqueName = $uniqueName;
		if ($champ != null) $this->addChamp($champ);
		$this->describe = $this->describe($dbName, $tableName);
		$this->preparedescribe();
	}

	function addJoin($db, $table, $alias, $where)
	{
		$this->listJoin[] = array('db' => $db, 'table' => $table, 'alias' => $alias, 'where' => $where);
	}

	function setMaxItemMultiSelect($value)
	{
		$this->maxItemMultiSelect = $value;
	}

	function getMaxItemMultiSelect()
	{
		return $this->maxItemMultiSelect;
	}

	function getPostExist()
	{
		return $this->postExist;
	}
	function setPostExist(bool $bool)
	{
		return $this->postExist=(bool)$bool;
	}

	function getDescribe()
	{
		return $this->describe;
	}

	function setNameOfChamp($name)
	{

		foreach ($this->listChampAvailable as $key => $value) {
			if (strtolower($name) == strtolower($key) and $value != '') {

				return $value;
			}
		}
		return $name;
	}
	function setAvailableChamp($name)
	{

		foreach ($this->listChampAvailable as $key => $value) {
			if (strtolower($name) == strtolower($key)) {
				return true;
			}
		}

		if (count($this->listChampAvailable) == 0) {
			return true;
		}

		return false;
	}

	function preparedescribe()
	{
		foreach ($this->describe as &$line) {
			$name = str_replace(' ', '_', $line['Field']);
			$fullName = $this->uniqueName() . $name;
			$line['nameOfChamp'] = $this->setNameOfChamp($line['Field']);
			//$line['nameOfChamp']=$line['Field'];
			$line['availableChamp'] = $this->setAvailableChamp($line['Field']);
			//$line['availableChamp']=$line['Field'];
			$line['value'] = $this->recupvalue($fullName);
			//$line['valueList']=$this->recupvalueList($fullName);
			$line['orderby'] = $this->recupvalue($fullName . '_orderby');
			$line['orderby_timestamp'] = $this->recupvalue($fullName . '_orderby_timestamp');
			$line['countListValue'] = $this->recupvalue($fullName . '_countListValue');
			$line['listValue'] = $this->recupvalue($fullName . '_listValue');

			if ($line['countListValue'] === null) {
				$countListValue = $this->listValue($line['Field']);
				$line['countListValue'] = count($countListValue);
				$_SESSION[$fullName . '_countListValue'] = count($countListValue);
			}
		}

		unset($line);
		foreach ($this->describe as &$line) {
			if ($line['availableChamp'] == true) {
				$name = str_replace(' ', '_', $line['Field']);
				$fullName = $this->uniqueName() . $name;

				$line['valueList'] = $this->recupvalueList($fullName);
			}
		}
		unset($line);

		// traitement champ recherche global
		$this->global_search = $this->recupvalue($this->uniqueName() . 'var_' . 'global_search');

		foreach ($this->describe as &$line) {
			if ($line['availableChamp'] == true) {
				$field = $line['Field'];
				if ($line['countListValue'] <= $this->maxItemMultiSelect) {

					$line['listValue'] = $this->listValueWhere($field);
				}
			}
		}
		unset($line);

		// traitement des champ page et limit

		$tmplimit = $this->recupvalue($this->uniqueName() . 'var_' . 'limit');
		$tmppage = $this->recupvalue($this->uniqueName() . 'var_' . 'page');



		if ($tmplimit != null) $this->limit = $tmplimit;
		if ($tmppage != null) $this->page = $tmppage;

		if (self::getOption($this->uniqueName() . 'const_clearWhere') !== null) {
			header('Location: ' . Tool::url(array('View' => Tool::getOption('view')), false));
		}
	}

	function AJAX($value)
	{
		$this->ajax = $value;
	}

	function showAction()
	{

		if ($this->ajax == true) {
			$action = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
			foreach ($_POST as $key => $value) {
				if ($action != '') $action .= '&';
				$action .= $key . '=' . $value;
			}
			return 'action="' . $action . '"';
		}
	}

	static function getOption($keysearch = null)
	{
		$tmp = array();

		foreach ($_POST as $keyp => $value) {
			$tmp[$keyp] = $value;
			if (strtolower($keyp) == strtolower($keysearch)) {
				//$this->request=true;
				return $value;
			}
		}

		foreach ($_GET as $keyp => $value) {
			$tmp[$keyp] = $value;
			if (strtolower($keyp) == strtolower($keysearch)) {
				//$this->request=true;
				return $value;
			}
		}

		if ($keysearch === null) {
			return $tmp;
		}

		return null;
	}

	function recupvalue($fullName)
	{

		$value = null;

		if (self::getOption($this->uniqueName() . 'const_clearWhere') !== null) {
			unset($_SESSION[$fullName]);
		}

		if (self::getOption($fullName) !== null) {
			$_SESSION[$fullName] = self::getOption($fullName);
			$this->postExist = true;

			if ($_SESSION[$fullName] == '') {
				unset($_SESSION[$fullName]);
			}
		}
		if (isset($_SESSION[$fullName])) {
			$value = $_SESSION[$fullName];
		}

		return $value;
	}
	function recupvalueList($fullName)
	{

		$value = null;

		if (self::getOption($this->uniqueName() . 'const_clearWhere') !== null) {
			unset($_SESSION[$fullName]);
		}

		if (self::getOption($fullName) !== null) {
			$_SESSION[$fullName] = self::getOption($fullName);
			$this->postExist = true;
		}

		if ($this->postExist == true and self::getOption($fullName) === null) {
			unset($_SESSION[$fullName]);
		}

		if (isset($_SESSION[$fullName])) {
			$value = $_SESSION[$fullName];
		}

		return $value;
	}

	function getRowCount()
	{
		return 'RowCount: ' . $this->getCount();
	}

	function getGlobalSearch()
	{

		return ' <input type="text" name="' . $this->uniqueName() . 'var_' . 'global_search' . '" placeholder="Global Search..." value="' . $this->global_search . '" onKeyPress="if(event.keyCode == 13) document.' . $this->uniqueName() . '.submit();">';
	}

	function getReload()
	{
		return '<a href="#" onclick="document.' . $this->uniqueName() . '.submit();">Search</a> / <a href="' . Tool::url(array('View' => Tool::getOption('view'), $this->uniqueName() . 'const_clearWhere' => 'true'), false) . '">Reinitialize</a>';
	}

	function getCount()
	{

		return $this->count;
	}


	function describe($db, $table)
	{


		if ($db === null or $db == '') {
			$query = "DESCRIBE `$table` ;";
		} else {
			$query = "DESCRIBE `$db`.`$table` ;";
		}

		return DbCo::getQuery($query);
	}

	function listValue($champ)
	{


		$query = "SELECT distinct count(`$champ`) as `count`, `$champ` as field FROM " . $this->showTable() . " GROUP BY field ORDER BY field ASC ;";

		return DbCo::getQuery($query);
	}

	function listValueWhere($champ)
	{

		if ($this->showWhereData !== null) $r = $this->showWhereData;
		else $r = $this->showWhere();

		$where = '1';
		if ($this->where != '') $where = $this->where;

		$query = "SELECT distinct count(if((" . $r['sql'] . "),1,null)) as count, `$champ` as field FROM " . $this->showTable() . "WHERE " . $where . " GROUP BY field ORDER BY field ASC ;";

		return DbCo::getQuery($query, $r['sqlPrepare']);
	}

	function showTable()
	{
		$table = "`{$this->tableName}`";
		if ($this->dbName != '') $table = "`{$this->dbName}`.`{$this->tableName}`";
		return $table;
	}

	function addBandFilter()
	{
		$tmp = array();
		$tmp2 = array();
		foreach ($this->dataset as $line) {
			foreach ($line as $key => $value) {
				$value = '';
				$orderby = '';
				$countValue = '';
				$listValue = '';
				$realName = '';
				$valueList = '';
				foreach ($this->describe as $line) {
					if ($line['nameOfChamp'] == $key and $line['availableChamp'] == true) {
						$realName = $line['Field'];
						$value = $line['value'];
						$valueList = $line['valueList'];
						$orderby = $line['orderby'];
						$countValue = $line['countListValue'];
						$listValue = $line['listValue'];
						break;
					}
				}

				$name = $this->uniqueName() . str_replace(' ', '_', $realName);
				if (is_array($listValue)) {
					$str = '';

					foreach ($listValue as $v) {
						$selected = '';

						if (is_array($valueList) and in_array($v['field'], $valueList)) {
							$selected = 'selected';
						}
						if ($v['field'] == '') {
							$str .= '<option value="' . $v['field'] . '" ' . $selected . '>' . "(Empty)" . ' (' . $v['count'] . ')</option>';
						} else {
							$str .= '<option value="' . $v['field'] . '" ' . $selected . '>' . $v['field'] . ' (' . $v['count'] . ')</option>';
						}
					}
					$tmp[$key] = '<div class="whereinput"><select class="js-example-basic-multiple" for="' . $this->uniqueName() . '" name="' . $name . '[]" multiple="multiple"  onchange="document.' . $this->uniqueName() . '.submit();"> ' . $str . ' </select>
					<script AJAX_KEY="' . cadre_base::getOption('AJAX_KEY') . '">
					$(".js-example-basic-multiple").select2();
					</script>';
				} else {
					$tmp[$key] = '<div class="whereinput"><input type="text" for="' . $this->uniqueName() . '" class="width75" name="' . $name . '" value="' . $value . '" onKeyPress="if(event.keyCode == 13) document.' . $this->uniqueName() . '.submit();">';
				}

				$tmp[$key] .= '<br><select for="' . $this->uniqueName() . '" name="' . $name . '_orderby" onchange="document.' . $this->uniqueName() . '.submit();">
				<option value="" ' . $this->valueOrderby($orderby, '') . ' ></option>
				<option value="ASC" ' . $this->valueOrderby($orderby, 'ASC') . ' >ASC</option>
				<option value="DESC" ' . $this->valueOrderby($orderby, 'DESC') . ' >DESC</option>
				</select></div>';
				//$tmp2[$key].='<span class="Box inline"><label for="'.$name.'_orderby_ASC'.'">ASC </label><input type="radio" for="'.$this->uniqueName().'"  id="'.$name.'_orderby_ASC'.'" name="'.$name.'_orderby" value="ASC" onclick="document.'.$this->uniqueName().'.submit();" onKeyPress="if(event.keyCode == 13) document.'.$this->uniqueName().'.submit();" '.$this->valueOrderby($orderby,'ASC').'></span>';
				//$tmp2[$key].='<span class="Box inline"><label for="'.$name.'_orderby_DESC'.'">DESC </label><input type="radio" for="'.$this->uniqueName().'"  id="'.$name.'_orderby_DESC'.'" name="'.$name.'_orderby" value="DESC"  onclick="document.'.$this->uniqueName().'.submit();" onKeyPress="if(event.keyCode == 13) document.'.$this->uniqueName().'.submit();" '.$this->valueOrderby($orderby,'DESC').'></span></div>';
			}
			break;
		}
		$t = array();
		//$t2 = array();
		$t[] = $tmp;
		//$t2[]=$tmp2;
		$this->dataset = array_merge($t, $this->dataset);
		//$this->dataset = array_merge($t2,$this->dataset);
		//var_dump($_POST);

	}

	function valueOrderby($valuepost, $value)
	{
		if ($value == $valuepost) {
			return 'selected';
		}
	}

	function uniqueName()
	{
		if ($this->uniqueName != null) {
			return $this->uniqueName;
		}
		return 'A' . sha1($_SERVER['SCRIPT_NAME'] . '::' . $this->tableName . '::');
	}

	function getSqlPrepare()
	{
	}


	function getData()
	{
		if ($this->dataset == null) {
			$sql = $this->createSelect();
			$this->queryCount = $sql['count'];
			$this->query = $sql['sql'];
			$this->queryNotLimit = $sql['sqlNotLimit'];
			$this->querySqlPrepare = $sql['sqlPrepare'];

			//var_dump($this->query); 
			//var_dump($this->sqlPrepare);
			//DbCo::$debugStatement=true;
			$this->dataset = DbCo::getQuery($this->query, $sql['sqlPrepare']);
			//echo nl2br(DbCo::$strDebug);
			$count = DbCo::getQuery($this->queryCount, $sql['sqlPrepare']);

			$this->count = $count[0]['count'];

			if ($this->count == 0) {
				$tmp = array();
				$tmp[] = $this->champArray;
				$this->dataset = $tmp;
			}

			$this->addBandFilter();
		}

		return $this->dataset;
	}

	function setData($data)
	{
		$this->dataset = $data;
	}

	function addChamp($array)
	{
		foreach ($array as $key => $value) {
			if (is_string($key)) {
				$this->listChampAvailable[$key] = $value;
			} else {
				$this->listChampAvailable[$value] = '';
			}
		}
	}

	function showChamp()
	{

		$str = '';
		$array = array();
		if (count($this->listChampAvailable) > 0) {

			foreach ($this->listChampAvailable as $key => $value) {
				foreach ($this->describe as $line) {
					if (strtolower($line['Field']) == strtolower($key)) {
						if ($str != '') $str .= ', ';
						$str .= '`' . $line['Field'] . '` as `' . $line['nameOfChamp'] . '`';
						$array[$line['Field']] = '';
					}
				}
			}
		} else {
			foreach ($this->describe as $line) {
				if ($str != '') $str .= ', ';
				$str .= '`' . $line['Field'] . '`';
				$array[$line['Field']] = '';
			}
		}

		$this->champArray = $array;

		return $str;
	}

	function constructWhere($field, $value, $str, $sqlPrepare, $op, $found)
	{
		if ($value !== null) {
			$found = false;
			$arrayOp = array();

			preg_match_all('/(<>|!=|>=|<=|=|<|>)([^(<>|!=|>=|<=|=|<|>)]+)/i', $value, $arrayOp);

			if (isset($arrayOp[2]) and count($arrayOp[2])) {
				foreach ($arrayOp[2] as $i => $item) {
					if ($item != '') {
						$operator = $arrayOp[1][$i];
						$sha = ':A' . 'operator' . '_' . $i . $field . rand();
						if ($str != "") $str .= $op;
						$str .= "`" . $field . "` $operator $sha";
						$sqlPrepare[$sha] = trim($item);
						$found = true;
					}
				}
			}

			$arrayIn = array();
			preg_match_all('/(not in|in)\(([^in\(]+)\)/i', $value, $arrayIn);

			if (isset($arrayIn[2]) and count($arrayIn[2])) {
				foreach ($arrayIn[2] as $i => $item) {
					$operator = $arrayIn[1][$i];
					$listvalues = preg_split('/(,|;|-)/i', $item);
					$strval = '';
					foreach ($listvalues as $j => $val) {

						$sha = ':A' . '_IN' . $i . '_' . $j . '_' . $field . rand();
						if ($strval != "") $strval .= ', ';
						$strval .= $sha;
						$sqlPrepare[$sha] = trim($val, " \t\n\r\0\x0B'");
					}

					if ($str != "") $str .= $op;
					$str .= "`" . $field . "` $operator($strval)";

					$found = true;
				}
			}

			if ($found == false) {
				if ($value != '') {
					$sha = ':A' . '_DEFAULT_' . $field . rand();
					if ($str != "") $str .= $op;
					$str .= "`" . $field . "` like $sha";
					$sqlPrepare[$sha] = '%' . trim($value) . '%';
				}
			}
		}

		return array('str' => $str, 'sqlPrepare' => $sqlPrepare, 'found' => $found);
	}

	function showWhere()
	{

		$strG = '';

		$str = 'true';
		$sqlPrepare = array();

		if ($this->where != null) $str = $this->where;

		if ($this->global_search !== null) {
			foreach ($this->describe as $line) {
				$strval = '';
				$str2 = '';
				$field = $line['Field'];
				$value = $line['value'];
				$search = $this->global_search;
				$r = $this->constructWhere($field, $search, $str2, $sqlPrepare, ' OR ', false);
				if ($r['str'] != '') {
					if ($strG != '') $strG .= ' OR ';
					$strG .= $r['str'];
					$sqlPrepare = $r['sqlPrepare'];
					$found = $r['found'];
				}
			}
		}

		if ($strG != '') $strG = '(' . $strG . ') AND ';

		foreach ($this->describe as $line) {
			$strval = '';
			$field = $line['Field'];
			$value = $line['value'];

			if ($line['availableChamp'] == true) {

				$found = false;
				$valueList = $line['valueList'];
				if (is_array($valueList)) {

					foreach ($valueList as $i => $v) {
						$sha = ':A' . '_IN' . $i . '_' . $field . rand();
						if ($strval != "") $strval .= ', ';
						$strval .= $sha;
						$sqlPrepare[$sha] = trim($v, " \t\n\r\0\x0B'");
					}

					$found = true;
					if ($str != '') {
						$str .= ' AND ' . '`' . $field . '`' . 'IN(' . $strval . ')';
					}
				} else {
					$str2 = '';
					$r = $this->constructWhere($field, $valueList, $str2, $sqlPrepare, ' AND ', $found);
					if ($r['str'] != '') {
						$str .= ' AND ' . $r['str'];
					}

					$sqlPrepare = $r['sqlPrepare'];
					$found = $r['found'];
				}
			}
		}
		$this->sqlPrepare = $sqlPrepare;

		$this->showWhereData = array('sql' => $strG . ' ' . $str, 'sqlPrepare' => $sqlPrepare);

		return $this->showWhereData;
	}

	function showOrderBy()
	{
		$str = '';

		foreach ($this->describe as $line) {
			if ($line['orderby'] == 'ASC' or $line['orderby'] == 'DESC') {
				if ($str != "") $str .= ' , ';
				$str .= "`" . $line['Field'] . "` " . $line['orderby'];
			}
		}
		if ($str == '') $str = $this->orderby;
		return $str;
	}


	function createSelect()
	{
		$sql = '';
		$where = '';
		$orderby = '';
		$limit = $this->limit;
		$page = $this->page;
		$pagecor = $this->page - 1;
		$limitstart = 0;
		$limitend = $limit;

		if ($page != 0) {
			$limitstart = ($pagecor * $limit);
			$limitend = ($pagecor * $limit) + $limit;
		}

		if ($this->showWhereData !== null) $r = $this->showWhereData;
		else $r = $this->showWhere();

		$where = " WHERE " . $r['sql'];
		$orderbyform = $this->showOrderBy();
		if ($this->orderby != null and $orderbyform == '') {
			$orderby = " ORDER BY " . $this->orderby;
		} else if ($orderbyform != '') {
			$orderby = ' ORDER BY ' . $orderbyform;
		}

		$sql = 'SELECT ' . 'count(*) as count' . ' FROM ' . $this->showTable() . $where . $orderby . ';';

		$sql2 = 'SELECT ' . $this->showChamp() . ' FROM ' . $this->showTable() . $where . $orderby . ' LIMIT ' . $limitstart . ',' . $limit . ';';

		$sqlNoLimit = 'SELECT ' . $this->showChamp() . ' FROM ' . $this->showTable() . $where . $orderby . ';';

		return array('count' => $sql, 'sql' => $sql2, 'sqlNotLimit' => $sqlNoLimit, 'sqlPrepare' => $r['sqlPrepare']);
	}

	function getSql()
	{
		if ($this->queryNotLimit == '') {
			$this->toString();
		}

		return ['sql' => $this->queryNotLimit, 'prepare' => $this->querySqlPrepare];
	}

	function export($title)
	{
		$sql = $this->getSql();
		$data = DbCo::getQuery($sql['sql'], $sql['prepare']);

		Tool::exportExcell($data, $title);
	}


	function getLinkPage($namepage, $cal, $actualPage)
	{
		$linkPage = "";
		if ($actualPage > 1) {
			$linkPage .= '<a href="' . Tool::url(array('View' => self::getOption('view'), $namepage => $actualPage - 1), true) . '">' . 'Previous' . '</a> - ';
		} else {
			$linkPage .= '' . 'Previous' . ' - ';
		}

		if ($actualPage < $cal) {
			$linkPage .= '<a href="' . Tool::url(array('View' => self::getOption('view'), $namepage => $actualPage + 1), true) . '">' . 'Next' . '</a> - ';
		} else {
			$linkPage .= '' . 'Next' . ' - ';
		}


		$point3 = false;
		for ($i = 1; $i < $cal + 1; $i++) {

			$write = false;

			if ($i < 10 or $i > ($cal - 10) or ($i > $actualPage - 10 and $i < $actualPage + 10)) {
				$write = true;
			} else {
				if ($point3 == false) {
					$linkPage .= ' ... ';
					$point3 = true;
				}
			}

			if ($write) {
				$point3 = false;
				if ($i != $actualPage) {
					$linkPage .= '<a href="' . Tool::url(array('View' => self::getOption('view'), $namepage => $i), true) . '">' . $i . '</a> ';
				} else {
					$linkPage .= '<strong>' . $i . '</strong> ';
				}
			}
		}
		return $linkPage;
	}

	function getPagination()
	{

		$str = '';
		$cal = ceil($this->count / $this->limit);
		$namelimit = $this->uniqueName() . 'var_' . 'limit';
		$namepage = $this->uniqueName() . 'var_' . 'page';
		$page = '<input type="text" for="' . $this->uniqueName() . '" class="width50" name="' . $namepage . '" value="' . $this->page . '" onKeyPress="if(event.keyCode == 13) document.' . $this->uniqueName() . '.submit();">';
		$limit = '<input type="text" for="' . $this->uniqueName() . '" class="width50" name="' . $namelimit . '" value="' . $this->limit . '" onKeyPress="if(event.keyCode == 13) document.' . $this->uniqueName() . '.submit();">';
		$linkPage = $this->getLinkPage($namepage, $cal, $this->page);

		if ($this->page > $cal and $this->page > 1) {

			$redirect = (int)Tool::getOption('redirect');
			header('Location: ' . Tool::url(array('View' => Tool::getOption('view'), $this->uniqueName() . 'var_' . 'page' => '1', 'redirect' => $redirect + 1)));
		}

		$str .= 'Page ' . $page . ' of ' . $cal . ' - ' . $limit . ' by page ' . $linkPage;
		return $str;
	}

	function toString()
	{

		$str = '';
		$data = $this->getData();
		foreach ($data as &$line) {
			foreach ($line as &$value) {
				if (strlen($value) > 100) {
					$value = '<div class="overflow2">' . $value . '</div>';
				}
			}
		}
		unset($line);
		unset($value);

		$row = $this->getReload() . ' ';
		$row .= $this->getRowCount();
		$row .= $this->getGlobalSearch();
		$set = new DataSet($row, $data);
		$str .= '<form name="' . $this->uniqueName() . '" method="POST" ' . $this->showAction() . '>';
		$str .= $set->toString();
		$str .= $this->getPagination();
		$str .= '</form>';
		return $str;
	}
}

class DataViewer2
{
	private $title = '';
	private $sql_brut = '';
	private $dataset = null;
	private $datasetFinal = null;
	private $formFilter = null;
	private $pagination = 25;
	private $count = 0;
	private $currentPage = 0;
	private $currentPagination = 0;
	private $editMode = true;
	private $recordMode = true;
	private $labelEditMode = 'Edit';
	private $commandeOption = '';
	private $labelcommandeOption = '';
	private $formEdit = null;
	private $listMultiSelect = null;
	private $maxMultiSelectDefault = 20;
	private $init = false;
	private $init2 = false;
	private $displayForm = true;
	private $itemDisplay =null;
	private $debug =false;
	
	
	function __construct(String $title, SQL $sql, $editMode=true)
	{
		$this->title = $title;
		$this->labelcommandeOption = get_class($this).'_'.$title.'_CommandeOption';
		
		if(!isset($_SESSION[$this->labelcommandeOption])){
			$_SESSION[$this->labelcommandeOption]='';
		}

		$this->sql_brut = $sql;
		$this->editMode=$editMode;
		$this->recordMode=$editMode;
	}

	function setDebug(bool $bool){
		$this->debug=$bool;
	}
	
	function setLabelEditMode($label){
		$this->labelEditMode=$label;
	}

	function setPagination($recordByPage){
		$this->pagination=$recordByPage;
	}

	function addMultiSelect($alias,$maxItem=null,$displayCount=true){
		if($maxItem===null){
			$maxItem = $this->maxMultiSelectDefault;
		}
		$this->listMultiSelect[$alias]=['maxItem'=>$maxItem,'displayCount'=>$displayCount];
	}

	function setRecordMode(bool $bool){
		$this->recordMode=$bool;
	}

	function setEditMode(bool $bool){
		$this->editMode=$bool;
	}

	function setCount()
	{
		$list = $this->sql_brut->getValidField();
		$sql2 = clone $this->sql_brut;
		
		$sql2->cleanFields();
		$sql2->cleanPrimaryField();

		$sql2->addFunctionForced('count(*)', 'count');

		foreach($list as $f){
			
			if($f['type']==='function'){
				$sql2->addFunctionForced($f['field'], $f['alias']);
			}
		}
		
		$count = DbCo::getQuery($sql2->toString(), $sql2->getArgs());
		$c = 0;
		if (isset($count[0]['count'])) {
			$c = $count[0]['count'];
		}
		if (count($sql2->getGroupBy())) {
			$c = count($count);
		}

		$this->count=$c;
	}

	function getCount(){
		return $this->count;
	}

	function getCommandeOption(){
		return $_SESSION[$this->labelcommandeOption];
	}
	function setCommandeOption($commande){
		$_SESSION[$this->labelcommandeOption]=$commande;
	}

	static function setEvent($target,$action, $event='click'){
		
		
		$target->addOption('Form-Action-'.$event, true);
		$target->addOption('Action', $action);
		//$target->addOption('ActionEvent', $event);

		$target->addOption('LinkOption',null);
		$target->addOption('LinkForm', null);

		if($target->getParent()!=null){
			$form = $target->getParent();
			if($form->getItem('Option')!=null){
				$target->addOption('LinkOption',$form->getItem('Option')->getFullName());
			}
			
			$target->addOption('LinkForm', $form->getFullName());
		}
		

	}


	/**
	 * Création d'un formulaire qui contiendra tout les elements de gestion du dataset
	 * - les filtres, la gestion des sort, la pagination, les fonctions specials tel que le reset et autres
	 *
	 * @return void
	 */
	function setFilter()
	{
		// création du formulaire
		$sha = sha1(serialize($this->sql_brut->getPrimaryTable()));
		$this->formFilter = new Form(get_class($this).'_'.$this->title.'_'.$sha);

		// on sauve les valeurs dans la session 
		$this->formFilter->setSaveValueInSession(true);

		//création d'un champ invisible "Option" qui contiendra les commandes special (les commandes sont envoyée via des Button)
		$this->formFilter->addItem(new Hidden('Option'));
		// forcevalue ='' afin de réinitialiser la commande éventuelle
		$this->formFilter->getItem('Option')->forceValue('');
		// création du champ PAGE pour la pagination
		$this->formFilter->addItem(new Number('Page'));
		$this->formFilter->getItem('Page')->setDefaultValue(1);
		self::setEvent($this->formFilter->getItem('Page'),'','change');
		//$this->formFilter->getItem('Page')->addOption('onchange', 'document.'.$this->formFilter->getFullName() . '.submit();'); 

		$this->formFilter->getItem('Page')->setAutoCompletion(false);
		$this->formFilter->getItem('Page')->addClass('minimumSize');

		// création du champ Pagination pour la pagination
		$this->formFilter->addItem(new Number('Pagination'));
		$this->formFilter->getItem('Pagination')->setDefaultValue($this->pagination);
		self::setEvent($this->formFilter->getItem('Pagination'),'','change');
		//$this->formFilter->getItem('Pagination')->addOption('onchange', 'document.'.$this->formFilter->getFullName() . '.submit();');
		$this->formFilter->getItem('Pagination')->setAutoCompletion(false);
		$this->formFilter->getItem('Pagination')->addClass('minimumSize');

		//Création d'un champ TEXT pour effectuer une recherche global
		$this->formFilter->addItem(new Text('Global_Search'));
		$this->formFilter->getItem('Global_Search')->setPlaceHolder('Global Search...');		
		$this->formFilter->getItem('Global_Search')->setAutoCompletion(false);

		//Création du bouton 'Search'
		$this->formFilter->addItem(new Submit('Search'));
		$this->formFilter->getItem('Search')->addClass('minimumSize');

		// création du bouton réinitialisation
		$this->formFilter->addItem(new Button('Reinitialize'));
		$this->formFilter->getItem('Reinitialize')->addClass('minimumSize');
		// ajoute un evenement OnCLick pour envoyer la commande 'RESET' dans l'input Option et de valider le formulaire
		self::setEvent($this->formFilter->getItem('Reinitialize'),'RESET');
		/*$this->formFilter->getItem('Reinitialize')->addOption('Form-Action', 'RESET');
		$this->formFilter->getItem('Reinitialize')->addOption('LinkOption', $this->formFilter->getItem('Option')->getFullName());
		$this->formFilter->getItem('Reinitialize')->addOption('LinkForm', $this->formFilter->getFullName());*/
		
		
		// création du bouton New Record
		$this->formFilter->addItem(new Button('New Record'));
		$this->formFilter->getItem('New Record')->addClass('minimumSize');
		// ajoute un evenement OnCLick pour envoyer la commande 'NEWRECORD' dans l'input Option et de valider le formulaire
		self::setEvent($this->formFilter->getItem('New Record'),'NEWRECORD');
		/*$this->formFilter->getItem('New Record')->addOption('Form-Action', 'NEWRECORD');
		$this->formFilter->getItem('New Record')->addOption('LinkOption', $this->formFilter->getItem('Option')->getFullName());
		$this->formFilter->getItem('New Record')->addOption('LinkForm', $this->formFilter->getFullName());*/
		

		// récuperer la valeur de Option dans le POST pour executer les actions requises
		$option = '';
		if(isset($_POST[$this->formFilter->getItem('Option')->getFullName()])){
			$this->commandeOption = $_POST[$this->formFilter->getItem('Option')->getFullName()];
			$this->setCommandeOption($this->commandeOption);
		}
		$option = $this->commandeOption;

		// récuperer les champ dans la requete SQL 
		$fields = $this->sql_brut->getValidField();
		$cleanOrderBy=false;

		// Ajoute un TEXT pour chaque champ
		// Ajout un ENUM SORT pour chaque champ
		foreach ($fields as $l) {
			$alias = $l['alias'];
			$aliasSort = $l['alias'].'_SORT';
			$type = $l['type'];
			if(isset($this->listMultiSelect[$alias])){
				$tmp = $this->listMultiSelect[$alias];
			
				$this->addItemMultiSelectInForm($alias,$tmp['maxItem'],$tmp['displayCount']);
				//$this->formFilter->getItem($alias)->addClass('width100');
				
			}
			else{
				$this->formFilter->addItem(new Text($alias));
				//$this->formFilter->addItem(new Item('<br>'));
				$this->formFilter->getItem($alias)->addClass('width100');
				//$this->formFilter->getItem($alias)->addClass('whereinput');
				//$this->formFilter->getItem($alias)->addOption('onchange', 'document.'.$this->formFilter->getFullName() . '.submit();');
				self::setEvent($this->formFilter->getItem($alias),'','change');
				$this->formFilter->getItem($alias)->setAutoCompletion(false);
			}
			
			
			$this->formFilter->addItem(new Enum($aliasSort,null,['','ASC','DESC']));
			//$this->formFilter->addItem(new Item('<br>'));
			$this->formFilter->getItem($aliasSort)->addClass('width100');
			//$this->formFilter->getItem($aliasSort)->addClass('minimumSize');
			$this->formFilter->getItem($aliasSort)->addOption('onchange', 'document.'.$this->formFilter->getFullName() . '.submit();');
			self::setEvent($this->formFilter->getItem($aliasSort),'','change');
			// si commande = RESET, remet les valeurs à blanc
			if($option==='RESET'){
				$this->formFilter->getItem($alias)->forceValue('');
				$this->formFilter->getItem($aliasSort)->forceValue('');
			}
			
			
			// apres la création du champ les valeurs des POST/SESSION sont récuperée automatiquement
			// on peux donc traiter directement les valeurs pour manipuler WHERE/HAVING dans la requete d'origine
			$value = $this->formFilter->getItem($alias)->getValue();
			$valueSort = $this->formFilter->getItem($aliasSort)->getValue();
			if(($value!='' and !is_array($value)) or (is_array($value) and count($value))){
				switch ($type) {
					case 'field':
						$tmpValue=null;
						if(get_class($this->formFilter->getItem($alias))==='MultiSelect'){
							
							$tmpValue = $this->getOperatorMultiSelect($l['tableAlias'] . '.' . $l['field'],$value);
							
						}
						else{
							$tmpValue = $this->getOperator($l['tableAlias'] . '.' . $l['field'],$value);
						}
						
						if($tmpValue!==null){
							$operator = $tmpValue['operator'];
							$listValue = $tmpValue['values'];
							$this->sql_brut->addWhere($operator, $listValue);
						}
						
						break;
					case 'function':
						$tmpValue=null;
						if(get_class($this->formFilter->getItem($alias))==='MultiSelect'){
							if(is_array($value) and count($value)>0){
								$tmpValue = $this->getOperatorMultiSelect($alias,$value);
							}
						}
						else{
							$tmpValue = $this->getOperator('`'.$alias.'`',$value);
						}
						if($tmpValue!==null){
							$operator = $tmpValue['operator'];
							$listValue = $tmpValue['values'];
							$this->sql_brut->addHaving($operator, $listValue);
						}
						
						break;
	
					default:
						# code...
						break;
				}
			}

			// traitement identique au WHERE/HAVING mais pour la clause ORDER BY
			if($valueSort!=''){
				if($cleanOrderBy==false){
					$this->sql_brut->cleanOrderBy();
					$cleanOrderBy=true;
				}
				switch ($type) {
					case 'field':
						
						$this->sql_brut->addOrderBy($l['tableAlias'] . '.' . $l['field'] , $valueSort);
						break;
					case 'function':
						$this->sql_brut->addOrderBy('`'.$l['alias'].'`' , $valueSort);
						break;
	
					default:
						# code...
						break;
				}
			}	
		}

		// taitement de la recherche Global
		$globalSearch = $this->formFilter->getItem('Global_Search')->getValue();
		if($globalSearch!=''){
			$opWhere='';
			$listValueWhere=array();
			$opHaving='';
			$listValueHaving=array();
			
			foreach ($fields as $l) {
				$alias = $l['alias'];
				$aliasSort = $l['alias'].'_SORT';
				$type = $l['type'];
				switch ($type) {
					case 'field':
						if($opWhere != ''){
							$opWhere .= ' OR ';
						}
						$opWhere .= $l['tableAlias'] . '.' . $l['field'] .' like ?';
						$listValueWhere[]='%'.trim($globalSearch).'%';
						
						break;
					case 'function':
						if($opHaving != ''){
							$opHaving .= ' OR ';
						}
						$opHaving .= $l['alias'] . ' like ?';
						$listValueHaving[]='%'.trim($globalSearch).'%';
						
						break;
	
					default:
						# code...
						break;
				}
	
			}
			if($opWhere!=''){
				$this->sql_brut->addWhere($opWhere, $listValueWhere);
			}
			if($opHaving!=''){
				//$this->sql_brut->addHaving($opHaving, $listValueHaving);
			} 
		
		}
		

		// Si commande reset, ne pas oublier de formater les valeurs par defaut des autres element de formulaire
		if($option==='RESET'){
			$this->formFilter->getItem('Page')->forceValue(1);
			$this->formFilter->getItem('Pagination')->forceValue($this->pagination);
			$this->formFilter->getItem('Global_Search')->forceValue('');
			$this->formFilter->getItem('Global_Search')->setDefaultValue(false);
			$this->commandeOption='';
			$this->setCommandeOption('');
		}
		


	}

	function addItemMultiSelectInForm($alias,$max,$displayCount){
		// chercher la table d'origine
		$tableAlias=null;
		$field=null;
		
		foreach($this->sql_brut->getValidField() as $f){
			if($alias===$f['alias'] and $f['type']==='field'){
				$tableAlias = $f['tableAlias'];
				$field = $f['field'];
				
				break;
			}
		}

		// crée requete pour compter les elements du mutliselect (+ quantity)
		$sql2 = clone $this->sql_brut;
		//$sql2->setDistinct(true);
		$sql2->cleanPrimaryField();
		$sql2->cleanFields();
		$sql2->cleanLimit();
		$sql2->cleanOrderBy();
		$sql2->cleanGroupBy();
		$sql2->cleanHaving();
		$sql2->addGroupBy($alias);
		$sql2->addField($tableAlias,$field,$alias);
		$sql2->addFunction('count(*)','Count');

		$list = $this->sql_brut->getValidField();
		foreach($list as $f){		
			if($f['type']==='function'){
				$sql2->addFunctionForced($f['field'], $f['alias']);
			}
		}

		$sql2->addGroupBy($tableAlias.'.'.$field);
		$sql2->addOrderBy($tableAlias.'.'.$field,'ASC');
		$list = DbCo::getQuery($sql2->toString(),$sql2->getArgs());
		
		// crée multiselect dans le formulaire
		if(count($list)<=$max){
			$this->formFilter->addItem(new MultiSelect($alias,null));
			if($displayCount){
				foreach($list as $l){
					$a=$l[$alias];
					if($l[$alias]==''){
						$a = '[Empty]';
					}
					$this->formFilter->getItem($alias)->addSelectOption($a.' ('.$l['Count'].')',$l[$alias]);
				}
			}
			else{
				foreach($list as $l){
					$a=$l[$alias];
					if($l[$alias]==''){
						$a = '[Empty]';
					}
					$this->formFilter->getItem($alias)->addSelectOption($a,$l[$alias]);
				}
			}
			
			$this->formFilter->getItem($alias)->addOption('onchange', 'document.'.$this->formFilter->getFullName() . '.submit();');
			$this->formFilter->getItem($alias)->setAutoCompletion(false);
		}
		else{
			$this->formFilter->addItem(new Text($alias));
			$this->formFilter->getItem($alias)->addOption('onchange', 'document.'.$this->formFilter->getFullName() . '.submit();');
			$this->formFilter->getItem($alias)->setAutoCompletion(false);
		}

	}

	function addFilterInDataset($dataset){

		$tmp=array();
		if(isset($dataset[0]) and is_array($dataset[0])){
			foreach($dataset[0] as $k=>$v){
				$field = $k;
				$fieldSort = $k.'_SORT';
				$tmp[$field]='';
				if($this->formFilter->getItem($field)!==null){
					
					$tmp[$field].=$this->formFilter->getItem($field)->toString();
					$tmp[$field].=$this->formFilter->getItem($fieldSort)->toString();
				
				}
			}

			$tmp2=array();
			$tmp2[]=$tmp;

			foreach($dataset as $line){
				$tmp2[]=$line;
			}
			$dataset=$tmp2;
		}
		else if($this->sql_brut!=null){

			foreach($this->sql_brut->getValidField() as $line){
				$field = $line['alias'];
				$fieldSort = $line['alias'].'_SORT';
				$tmp[$field]='';
				if($this->formFilter->getItem($field)!==null){
					//$tmp[$k]=$this->formFilter->showStart();
					$tmp[$field].=$this->formFilter->getItem($field)->toString();
					$tmp[$field].=$this->formFilter->getItem($fieldSort)->toString();
					//$tmp[$k].=$this->formFilter->showEnd();
				}
			}

			$tmp2=array();
			$tmp2[]=$tmp;

			foreach($dataset as $line){
				$tmp2[]=$line;
			}
			$dataset=$tmp2;
		}

		return $dataset;
		
	}



	function getOperator($field, $value,$opAND=true){
		$listValue = array();
		$operator = '';
		$found=false;

		$ope = 'AND';
		if($opAND===false){
			$ope='OR';
		}

		$arrayOp = array();
		
		if(!is_array($value))$value = array($value);

		foreach($value as $v){
			preg_match_all('/(<>|!=|>=|<=|=|<|>)([^(<>|!=|>=|<=|=|<|>)]+)/i', $v, $arrayOp);
		
			if (isset($arrayOp[2]) and count($arrayOp[2])) {
				foreach ($arrayOp[2] as $i => $item) {
					if ($item != '') {
						if($operator!='')$operator .= ' '.$ope.' ';
						$operator .= $field.' '.$arrayOp[1][$i].' ?';
						
						$listValue[]=trim($item);
						$found=true;
					}
				}
			}
		
		
		
		
			$arrayIn = array();
			preg_match_all('/(not in|in)\(([^in\(]+)\)/i', $v, $arrayIn);

				if (isset($arrayIn[2]) and count($arrayIn[2])) {
					foreach ($arrayIn[2] as $i => $item) {
						$op = $arrayIn[1][$i];
						$listvalues = preg_split('/(,|;|-)/i', $item);
						$strval = '';
						foreach ($listvalues as $j => $val) {

							
							if ($strval != "") $strval .= ', ';
							$strval .= '?';
							$listValue[] = trim($val, " \t\n\r\0\x0B'");
						}

						if($operator!='')$operator .= ' '.$ope.' ';
						$operator .=  $field.' '.$op.'('.$strval.')';
						$found=true;
					}
				}
			

			if($found==false){
				if($field!=''){
					if($operator!='')$operator .= ' '.$ope.' ';	
					$operator .= $field.' like ?';
					$listValue[] = '%' . $v . '%';
				}
				
				
			}
		}


		return array('operator'=>$operator,'values'=>$listValue);
	}

	function getOperatorMultiSelect($field, $value){
		$listValue = array();
		$operator = '';
		
		$null = '';

		if(!is_array($value))$value = array($value);
		
		foreach($value as $v){
			if($v==''){
				$null = ' OR isnull('.$field.')';
			}
			if($operator!='')$operator.=',';
			$operator.='?';
			
			$listValue[]=trim($v);
		}

		$operator = $field.' in('.$operator.')'.$null;
		
		return array('operator'=>$operator,'values'=>$listValue);
	}

	function adaptPage($correction=true){
		// definition des seuils acceptables de PAGINATION
		$page = $this->formFilter->getItem('Page')->getValue();
		$pagination = $this->formFilter->getItem('Pagination')->getValue();
		$count = $this->getCount();
		
		$min=1;
		$max=$count;

		// réinitialisation à la valeur par defaut si la valeurs sort des cadres spécifier
		if((!is_numeric($pagination) or $pagination>$max or $pagination<$min) and $correction){
			$this->formFilter->getItem('Pagination')->forceValue($this->pagination);
		}

		$pagination = $this->formFilter->getItem('Pagination')->getValue();
		$min=1;

		
		$max=ceil($count/$pagination);
		// definition des seuils acceptables de PAGE
		$this->formFilter->getItem('Page')->setMinMax($min,$max);
		
		// réinitialisation à la valeur par defaut si la valeurs sort des cadres spécifier
		if((!is_numeric($page) or $page>$max or $page<$min) and $correction){
			$this->formFilter->getItem('Page')->forceValue($min);
		}


		

		// stockage des valeurs finals acceptables
		$this->currentPage = $this->formFilter->getItem('Page')->getValue();
		$this->currentPagination = $this->formFilter->getItem('Pagination')->getValue();
	}

	function createDataSet(){
		
		$this->setCount();
		$this->adaptPage();
		$this->sql_brut->setLimit((($this->currentPage-1)*$this->currentPagination),$this->currentPagination);
		$this->dataset = DbCo::getQuery($this->sql_brut->toString(), $this->sql_brut->getArgs());
		
	}
	
	function showPagination(){
		//$this->adaptPage();
		$cal = ceil($this->getCount() / $this->currentPagination);
		

		return 'Page ' . $this->formFilter->getItem('Page')->toString() . ' of ' . $cal . ' - ' . $this->formFilter->getItem('Pagination')->toString() . ' by page ' ;
	}

	function cleanDataset($dataset){

		if($this->sql_brut->showPrimaryKey()===true){
			$pm = $this->sql_brut->getPrimaryField();
			$field = $pm['alias'];
			foreach($dataset as &$line){
				if(isset($line[$field])){
					unset($line[$field]);
				}
			}
			unset($line);
		}
		return $dataset;
	}

	function addEditMode($dataset){
		$key = $this->labelEditMode;
		$pm = $this->sql_brut->getPrimaryField();
		

		if($this->editMode===true and count($pm)){
			$alias = $pm['alias'];
			$form = $this->formFilter;
			foreach($dataset as $nb=>&$line){
				
				$tmp = array($key=>'');
				$line = array_merge($tmp,$line);
				$v = &$line[$key];
				$k = 'EDIT_'.$line[$alias];
				if(isset($line[$alias]) and $this->getCommandeOption()!==$k){
					$ref = $alias.'_'.$nb;
					$form->addItem(new Button($ref));
					$form->getItem($ref)->setValue('Edit');
					self::setEvent($form->getItem($ref),$k);
					$v = $form->getItem($ref)->toString();
				}
				else if(isset($line[$alias]) and $this->getCommandeOption()===$k){
					$ref = $alias.'_'.$nb;
					$form->addItem(new Button($ref));
					$form->getItem($ref)->setValue('Cancel');
					self::setEvent($form->getItem($ref),'');
					$v = $form->getItem($ref)->toString();
				}
				

			}
			unset($line);
		}
		return $dataset;
	}

	function addForm(){
		if($this->editMode and strstr($this->getCommandeOption(),'EDIT_')!==false){

			$id = str_replace('EDIT_','',$this->getCommandeOption());
			$pm = $this->sql_brut->getPrimaryTable();
			$pf = $this->sql_brut->getPrimaryField();
			
			$this->formEdit = new Form($this->title.'_'.$this->labelEditMode,
										$pm['table'],
										DbCo::getPDO(),
										$pf['field'].'='.$id,
										$pm['db']);	
			$this->formEdit->setDebug($this->debug);	
			$this->formEdit->autocreate();	
			
		}
		else if($this->editMode and $this->getCommandeOption()==='NEWRECORD'){
			$pm = $this->sql_brut->getPrimaryTable();
			$pf = $this->sql_brut->getPrimaryField();
			$this->formEdit = new Form($this->title.'_'.$this->labelEditMode,
										$pm['table'],
										DbCo::getPDO(),
										null,
										$pm['db']);	
			$this->formEdit->setDebug($this->debug);	
			$this->formEdit->autocreate();	
		
			
		}
	}

	function saveForm(){
		if($this->editMode and $this->formEdit!=null and strstr($this->getCommandeOption(),'EDIT_')!==false){
			$this->formEdit->recoveryValue();	
			$this->formEdit->init();	
			if($this->formEdit->getIsUpdate()==true){
				$this->setCommandeOption('');
				$this->displayForm=false;
				$this->partialInit(true);
			}
		}
		else if($this->editMode and $this->formEdit!=null and $this->getCommandeOption()==='NEWRECORD'){
			$this->formEdit->newRecord();
			$this->formEdit->init();	
			if($this->formEdit->getInsert()==true){
				$this->setCommandeOption('');
				$this->displayForm=false;
				$this->partialInit(true);
				
			}else{
				$this->formFilter->getItem('New Record')->setValue('Cancel New Record');
				self::setEvent($this->formFilter->getItem('New Record'),'');
			}


		}

	}

	function setDisplayForm(bool $bool){
		$this->displayForm=$bool;
	}
	function getDisplayForm(){
		return $this->displayForm;
	}

	function showForm(){
		if($this->formEdit!==null and $this->displayForm===true and $this->itemDisplay!=null){
			return $this->itemDisplay->toString();
		}
		if($this->formEdit!==null and $this->displayForm===true){
			return $this->formEdit->toString();
		}
		return '';
	}

	function setItemDisplay($itemDisplay){
		$this->itemDisplay=$itemDisplay;
	}

	function partialInit(bool $force=false){
		if($this->init===false or $force===true){
			$this->init=true;
			$this->setFilter();
			$this->addForm();
			$this->createDataSet();
			
		}
		
	}

	function fullinit(bool $force=false){

		$this->partialInit($force);
		$this->saveForm();
		if($this->init2===false or $force===true){
			$this->init2=true;
			$dataset = $this->dataset;
		
		
			$dataset = $this->addEditMode($dataset);
			$dataset = $this->cleanDataset($dataset);
			$dataset = $this->addFilterInDataset($dataset);

			$this->datasetFinal=$dataset;
		}
		
	}



	function getForm(){
		return $this->formEdit;
	}

	function setForm($form){
		$this->formEdit=$form;
	}

	function getDataset(){
		if($this->datasetFinal!==null){
			return $this->datasetFinal;
		}
		return $this->dataset;
	}
	function setDataset($dataset){
		if($this->datasetFinal!==null){
			$this->datasetFinal=$dataset;
		}else{
			$this->dataset = $dataset;
		}
		
	}

	function getIsActif(){
		if($this->formFilter!=null and $this->formFilter->getPostExist()==true){
			return true;
		}
		else if($this->formEdit!= null and get_class($this->formEdit)=='Form' and $this->formEdit->getPostExist()==true){
			return true;
		}
		return false;
	}

	function toString()
	{

		$this->fullInit();
		
		
		$title = '';
		
		$title .= $this->formFilter->getItem('Option')->toString();
		$title .= $this->formFilter->getItem('Search')->toString();
		$title .= $this->formFilter->getItem('Reinitialize')->toString().' ';
		$title .= 'Count:' . $this->getCount();
		
		if($this->recordMode===true){
			$title .= $this->formFilter->getItem('New Record')->toString().' ';
		}
		$title .= ' '.$this->formFilter->getItem('Global_Search')->toString();
		$set = new DataSet($title, $this->datasetFinal);

		$str = '';
		$str .= $this->showForm();
		
		if($this->formFilter!=null)$str .= $this->formFilter->showStart();
		$str .= $set->toString();
		$str .= $this->showPagination();
		if($this->formFilter!=null)$str .= $this->formFilter->showEnd();

		

		
		return  $str;
	}
}

class SQL
{
	private $primaryTable = array();
	private $primaryField = array();
	private $fields = array();
	private $joins = array();
	private $wheres = array();
	private $groupBy = array();
	private $orderBy = array();
	private $having = array();
	private $limit = array();
	private $args = array();
	private $pdo = null;
	private $distinct = false;

	function __construct($db, $table, $alias = null)
	{
		if ($alias === null) {
			$alias = $table;
		}

		$this->primaryTable = array('db' => $db, 'table' => $table, 'alias' => $alias);
		
	}

	function getPrimaryTable(){
		return $this->primaryTable;
	}

	function setPDO()
	{
	}

	function setDistinct(bool $bool=false){
		$this->distinct=$bool;
	}

	function setPrimaryField($tableAlias, $field, $alias = null)
	{
		if ($alias === null or $alias === '') {
			$alias = $field;
		}
		$this->primaryField = array('tableAlias' => $tableAlias, 'field' => $field, 'alias' => $alias, 'type' => 'Primary', 'ExistInField' => false);
	}
	function getPrimaryField(){
		return $this->primaryField;
	}
	function addField($tableAlias, $field, $alias = null)
	{
		if ($alias === null or $alias === '') {
			$alias = $field;
		}
		$this->fields[] = array('tableAlias' => $tableAlias, 'field' => $field, 'alias' => $alias, 'type' => 'field');
	}

	function getValidField(){
		$tmp = array();
		foreach ($this->fields as $line) {
			if (!($line['type'] === 'function' and !count($this->groupBy))) {
				$tmp[]=$line;
			}
		}

		return $tmp;

	}

	function getFields()
	{
		return $this->fields;
	}
	function setFields($fields)
	{
		$this->fields = $fields;
	}
	function cleanFields()
	{
		$this->fields = array();
	}
	function cleanPrimaryField()
	{
		$this->primaryField = array();
	}

	function addFunction($field, $alias = null)
	{
		if ($alias === null or $alias === '') {
			$alias = $field;
		}
		$this->fields[] = array('field' => $field, 'alias' => $alias, 'type' => 'function');
	}
	function addFunctionForced($field, $alias = null)
	{
		if ($alias === null or $alias === '') {
			$alias = $field;
		}
		$this->fields[] = array('field' => $field, 'alias' => $alias, 'type' => 'functionForced');
	}
	function addJoin($db, $table, $alias, $onClause, $type = '')
	{
		$this->joins[] = array('db' => $db, 'table' => $table, 'alias' => $alias, 'onClause' => $onClause, 'type' => $type,'subquery'=>0);
	}
	function addSubQueryJoin($subQuery, $alias, $onClause, $type = '')
	{
		$this->joins[] = array('table' => $subQuery, 'alias' => $alias, 'onClause' => $onClause, 'type' => $type,'subquery'=>1);
	}
	function addWhere($arg, $params = [])
	{
		$this->wheres[] = array('arg' => $arg, 'param' => $params);
	}
	function addHaving($arg, $params = [])
	{
		$this->having[] = array('arg' => $arg, 'param' => $params);
	}
	function cleanHaving()
	{
		$this->having=array();
	}
	function addGroupBy($arg)
	{
		$this->groupBy[] = $arg;
	}
	function getGroupBy()
	{
		return $this->groupBy;
	}

	function cleanGroupBy()
	{
		$this->groupBy=array();
	}
	function addOrderBy($arg, $sort = 'ASC')
	{
		$this->orderBy[] = array('arg' => $arg, 'sort' => $sort);
	}

	function cleanOrderBy(){
		$this->orderBy=array();
	}

	function setLimit($start, $nbrOfRecord)
	{
		$this->limit = array('start' => $start, 'nbrOfRecord' => $nbrOfRecord);
	}
	function cleanLimit()
	{
		$this->limit = array();
	}

	function showPrimaryKey(){
		$addPrimaryField = true;

		if (count($this->primaryField)) {
			foreach ($this->fields as $line) {
				if ($line['type'] === 'field' and $line['tableAlias'] === $this->primaryField['tableAlias'] and $line['field'] === $this->primaryField['field'] and $line['alias'] === $this->primaryField['alias']) {
					$addPrimaryField = false;
					$this->primaryField['ExistInField'] = true;
					break;
				}
			}
		}
		else{
			$addPrimaryField = false;
		}

		return $addPrimaryField;
	}

	function showSelect()
	{
		$str = '';

		if ($this->showPrimaryKey()) {
			if ($str != '') {
				$str .= ', ';
			}
			$str .= $this->primaryField['tableAlias'] . '.' . $this->primaryField['field'] . ' AS `' . $this->primaryField['alias'] . '`';
		}

		foreach ($this->fields as $line) {
			if (!($line['type'] === 'function' and !count($this->groupBy))) {
				if ($str != '') {
					$str .= ', ';
				}

				if ($line['type'] === 'field') {
					$str .= $line['tableAlias'] . '.' . $line['field'] . ' AS `' . $line['alias'] . '`';
				} else if ($line['type'] === 'function') {
					$str .= $line['field'] . ' AS `' . $line['alias'] . '`';
				} else if ($line['type'] === 'functionForced') {
					$str .= $line['field'] . ' AS `' . $line['alias'] . '`';
				}
			}
		}

		$distinct='';
		if($this->distinct===true){
			$distinct = 'DISTINCT ';
		}

		return 'SELECT ' .$distinct. $str . ' FROM `' . $this->primaryTable['db'] . '`.`' . $this->primaryTable['table'] . '` AS `' . $this->primaryTable['alias'] . '`';
	}
	function showJoin()
	{
		$str = '';
		foreach ($this->joins as $line) {
			if ($str != '') {
				$str .= chr(13);
			}
			if($line['subquery']==0){
				$str .= $line['type'] . ' JOIN `' . $line['db'] . '`.`' . $line['table'] . '` AS ' . $line['alias'] . ' ON ' . $line['onClause'];
			}else{
				$str .= $line['type'] . ' JOIN (' . $line['table']. ') AS ' . $line['alias'] . ' ON ' . $line['onClause'];
			}
			
		}

		if ($str != '') {
			return chr(13) . $str;
		}
		return '';
	}

	function showWhere(&$args)
	{
		$str = '';
		foreach ($this->wheres as $line) {
			if ($str != '') {
				$str .= ' AND ';
			}
			$str .= '(' . $line['arg'] . ')';

			foreach ($line['param'] as $k => $v) {
				if (is_numeric($k)) {
					$args[] = $v;
				} else {
					$args[$k] = $v;
				}
			}
		}

		if ($str != '') {
			return chr(13) . 'WHERE ' . $str;
		}
		return '';
	}

	function showGroupBy()
	{
		$str = '';
		foreach ($this->groupBy as $line) {
			if ($str != '') {
				$str .= ', ';
			}
			$str .= $line;
		}

		if ($str != '') {
			return chr(13) . 'GROUP BY ' . $str;
		}
		return '';
	}

	function showOrderBy()
	{
		$str = '';
		foreach ($this->orderBy as $line) {
			if ($str != '') {
				$str .= ', ';
			}

			$str .= $line['arg'] . ' ' . $line['sort'];
		}

		if ($str != '') {
			return chr(13) . 'ORDER BY ' . $str;
		}
		return '';
	}

	function showLimit()
	{

		if (count($this->limit)) {
			return chr(13) . 'LIMIT ' . $this->limit['start'] . ', ' . $this->limit['nbrOfRecord'];
		}


		return '';
	}

	static function escape($string)
	{
		if (function_exists('mb_ereg_replace')) {
			return mb_ereg_replace('[\x00\x0A\x0D\x1A\x22\x27\x5C]', '\\\0', $string);
		} else {

			return preg_replace('~[\x00\x0A\x0D\x1A\x22\x27\x5C]~u', '\\\$0', $string);
		}
	}

	function showHaving()
	{
		$str = '';

		foreach ($this->having as $line) {
			foreach ($line['param'] as $k => $v) {
				if (is_numeric($k)) {
					$pos = strpos($line['arg'], '?');
					if ($this->pdo !== null) {
						$line['arg'] = substr_replace($line['arg'], $this->pdo->quote($v), $pos, 1);
					} else {
						$line['arg'] = substr_replace($line['arg'], "'" . self::escape($v) . "'", $pos, 1);
					}
				} else {
					if ($this->pdo !== null) {
						$line['arg'] = str_replace($k, $this->pdo->quote($v), $line['arg']);
					} else {
						$line['arg'] = str_replace($k, "'" . self::escape($v), $line['arg'] . "'");
					}
				}
			}
			if ($str != '') {
				$str .= ' AND ';
			}
			$str .= '(' . $line['arg'] . ')';
		}

		if ($str != '') {
			return chr(13) . 'HAVING ' . $str;
		}
		return '';
	}

	

	function toString()
	{
		$this->args = array();
		$str = $this->showSelect();
		$str .= $this->showJoin();
		$str .= $this->showWhere($this->args);
		$str .= $this->showGroupBy();
		$str .= $this->showHaving();
		$str .= $this->showOrderBy();
		$str .= $this->showLimit();
		$str .= ';';
		return $str;
	}

	function getArgs()
	{
		return $this->args;
	}

}


/*

$form = new Form('first');
$form->addItem(new Text('text'));
$form->addItem(new Textarea('textarea'));
$form->addItem(new Submit('Enjoy'));
$form->recoveryValue();

//echo $form->toString();

echo $form->showStart();
echo 'Text:'.$form->getItem('text')->toString();
echo 'Textarea:'.$form->getItem('textarea')->toString();
echo 'Enjoy:'.$form->getItem('Enjoy')->toString();
echo $form->showEnd();
*/
