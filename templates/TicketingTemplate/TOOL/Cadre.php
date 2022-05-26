<?php



// chargement des controlleurs ce trouvant dans le reportoire $dir extention .php (ignore les .old)
/*$dir    = 'TEMPLATE/';
$files = scandir($dir);
foreach($files as $file){ 
	if(strstr($file,'.php')!==false){
        $file = $dir.$file;
		include_once($file);
	}
}*/


/**
 * Cadre_Base est la base de tout les cadres (qu'il soit onglet ou non)
 * Il reprend les elements fondamentaux et generalise le fonctionnement
 * - gere le timer
 * - recupere le cache en DB et recupere son object en cache si possible (si base du nom de la classe)
 * - lance la fonction init() qui doit crée le cadre dans son ensemble
 * - envoi en db l'objet serialisé
 * 
 * - la fonction to string validera les données via la fonctions validation()
 * renvera le string du résultat
 */
class Cadre_Base
{
    private $firstTab=true;
    private $timer;
    private $key;
    private static $keyGlobal=0;
    public $onglet = null;
    private $useCache;
    private $setCache;
    private $link;
	protected $option=array();
	protected $istimer=false;
	protected $readOnly=false;
	static protected $listNotice=array();

    /**
     * Initialise le cadre, récupere le cache (si $usecache==false) et renvoi le resulat d'init() en db
     *
     * @param string $key la clé est utiliser en interne par init() pour faire la liaison des données
     * @param boolean $useCache par defaut = true permet d'utilier le cache, si false, init() sera utiliser plutot que le cache
     */
    function __construct($key, $useCache = true, $setCache = true, $link = null,$readOnly=false)
    {
        $this->useCache = $useCache;
        $this->key = $key;
        if(self::$keyGlobal != $key) self::$keyGlobal = $key;
        
        $this->setCache = $setCache;
        $this->link = $link;
        $this->timer = microtime(true);
		$this->readOnly=$readOnly;
		
		$this->onglet = new Onglet('');
		$this->onglet->add(new Tab(''),'');

        if ($useCache == true) {

            $this->setCache($this->key);
        }
        if ($useCache == false or $this->recupCache() != true) {

            $this->init();

           /* if ($this->setCache == true) {
                $error = Dbco::delCache($this->key, get_class($this));
                $error2 = DbCo::setCache($this->key, get_class($this), serialize($this));
            }*/

        }

        $this->timer = microtime(true) - $this->timer;
		
		if($this->timer>1){
			$this->istimer=true;
		}
		
        if ($this->onglet != null and $this->istimer==true) $this->onglet->setTimer($this->timer);
	}
	
    /**
     * n'existe que pour le polymorphisme
     * doit exister dans les derivée car est appellée par __construct()
     *
     * @return void
     */
    function init()
    {

    }
	function getFirstTab(){
		if($this->firstTab==true){
			$this->firstTab=false;
			return true;
		}
		return false;
	}

	static function getOption($keysearch=null){

		$tmp = array();
        foreach ($_POST as $keyp => $value) {
			$tmp[$keyp]=$value;
            if(strtolower($keyp)==strtolower($keysearch)){
                return $value;
            }
        }
        foreach ($_GET as $keyp => $value) {
			$tmp[$keyp]=$value;
            if(strtolower($keyp)==strtolower($keysearch)){
                return $value;
            }
		}

    
        // recherche dans les elements du dossier en DB
        $keyGlobal = self::getKeyGlobal();
        
       if($keyGlobal!=0){
            if(isset($GLOBALS[$keyGlobal]) and $keyGlobal!=0){
                
                $reference = $GLOBALS[$keyGlobal];
            }else{
                $GLOBALS[$keyGlobal]= DbCo::getFolder($keyGlobal);
                $reference = $GLOBALS[$keyGlobal];
            }

            if(isset($reference[0])){
                foreach ($reference[0] as $keyp => $value) {
                    $tmp[$keyp]=$value;
                
                    if(strtolower($keyp)==strtolower($keysearch)){
                    
                        return $value;
                    }
                }
            }	
       }    

		if($keysearch==null){
			return $tmp;
		}
        return null;
    }

	static function addNotice($title,$message,$type){
		$message = str_replace(chr(13),'',$message);
		$message = str_replace(chr(10),'<br>',$message);
		$message = str_replace("\t",'',$message);
		self::$listNotice[]=array('message'=>$message,'type'=>$type);
		
		//Dbco::setGuideAndHelp(User::get('per'),$title,$message,$type,Tool::context());

	}
	
	function getreadOnly(){
		return $this->readOnly;
	}
	
	function isTimer($value){
		$this->istimer=$value;
	}

    function getKey()
    {
        return $this->key;
    }

    static function getKeyGlobal()
    {
        return self::$keyGlobal;
    }
	
	function setKey($value)
    {
        $this->key=$value;
    }

    function getLink()
    {
        return $this->link;
    }

    function getViewException($data, $column, $filterException, $hidden = null, $isComment = 0)
    {

        $dataNew = array();
        foreach ($data as $item) {
            if (isset($item[$column])) {

                $ex = $item[$column];

                foreach ($filterException as $value) {
                    if (strpos($ex, $value['name']) !== false) {

                        $t = new Exception2_Frame();
                        $t->setIsComment($isComment);
                        $t->setListException($filterException);
                        $t->setResultFilter($value['name']);
                        if ($hidden != null) {
                            foreach ($hidden as $line) {
                                if ($line["Filter"] == $value['name']) {
                                    $t->setUntil($line["until"]);

                                }
                            }
                        }
                        $t->setKey($this->getKey());
                        $t->init();

                        $result = $t->toString();
                        $ex = str_replace($value['name'], $result, $ex);
                        $item[$column] = $ex;
                    }
                }
            }

            array_push($dataNew, $item);
        }

        return $dataNew;

    }

    /**
     * si la global n'existe pas, récupere le cache en DB et l'envoi dans la global
     *
     * @return void
     */
    function setCache()
    {
        if ($this->key != null and !isset($GLOBALS["Cache::" . $this->key])) {
            $GLOBALS["Cache::" . $this->key] = DbCo::getCache($this->key);
        }
    }

    /**
     * si existe, récupere le cache dans la global et tente de recomposé l'object (onglet)
     * si le resultat c'est pas clairement un object de type get_class($this), 
     * alors cela sera consideré comme un echec ce qui provoquera l'utilisation de init()
     *
     * @return void
     */
    function recupCache()
    {
        if (isset($GLOBALS["Cache::" . $this->key])) {
            foreach ($GLOBALS["Cache::" . $this->key] as $item) {
                if ($item['_key'] == get_class($this)) {
                    $t = html_entity_decode($item["cache"], ENT_QUOTES);
                    $tmp = unserialize($t);
                    if ($tmp != false and get_class($tmp) == get_class($this)) {

                        $this->onglet = $tmp->getOnglet();
                        $this->onglet->setTitle($this->onglet->getTitle() . " (Cache)");
                        $this->toString(false);
                        return true;
                    }
                }
            }
        }
        return false;
    }

    function setTimer($timer)
    {
        $this->onglet->setTimer($timer);
    }

    function getOnglet()
    {
        return $this->onglet;
    }

    /**
     * n'existe que pour le polymorphisme
     * doit exister dans les derivée car est appellée par toString()
     *
     * @return void
     */
    function validation()
    {

	}

    function getFolder(){

    }
	
	static function showNotification(){
		$notice="";
		
		foreach(self::$listNotice as $key=>&$note){
            $note['type'] = strtolower($note['type']);
			switch ($note['type']) {
				case 'sucess':
				$type='sucess';
				$option='canAutoHide: true,
                		holdup: "5000"';
					break;
				
				case 'connected':
				$type='info';
				$option='canAutoHide: true,
                		holdup: "60000"';
					break;
				
				case 'info':
				$type='info';
				$option='canAutoHide: true,
                		holdup: "5000"';
					break;
				
				case 'warning':
				$type='warning';
				$option='canAutoHide: true,
                		holdup: "8000"';
					break;
				
				case 'error':
				$type='error';
				$option='canAutoHide: false,
                		holdup: "5000"';
					break;
				
				default:
				$type='info';
				$option='canAutoHide: true,
						holdup: "5000"';
					break;
			}
			$notice.='
			<script AJAX_KEY="'.cadre_base::getOption('AJAX_KEY').'">
			$(function(){ 
				$.notice({
					text: "'.str_replace('"','\"',$note['message']).'",
						  type: "'.$type.'",
						  '.$option.'

				  });
			  });
			  </script>
			
			';
			unset(self::$listNotice[$key]);
		}
		
		return $notice;
	}

    /**
     * lance validation() et ensuite execute et renvoi la fonction toString de l'object onglet
     *
     * @return void
     */
    function toString($runValidation = true)
    {
		$str="";

        if ($runValidation === true) $this->validation();

        if ($this->key != null and $this->useCache == true) {

            $this->onglet->setOption("<span class=\"cleanCache\" id=\"clean_" . get_class($this) . "\" 
			onclick=\"request(delCache,'delCache=true&key=".$this->key."&cache=" . get_class($this) . "',this)\" >  [CleanCache]</span> ");
        }
		
		
		if ($this->onglet != null)  $str.= $this->onglet->toString();
		$str.=self::showNotification();
		return $str;
    }

    function createSelect($data, $name, $indiceName, $indiceValue, $ajax = "search")
    {


        $str = '
        <select name="' . $name . '" id="" onchange="' . $ajax . '(this,event)">';
        $str .= '<option value="' . null . '">' . "" . '</option>';
        foreach ($data as $item) {
            $selected = "";
            $tmpName = Tool::concatArrayValue($item, $indiceName);
            $tmpValue = Tool::concatArrayValue($item, $indiceValue);
            if (isset($_COOKIE[$name])) {
                if ($_COOKIE[$name] == $tmpName) {
                    $selected = "selected";
                }
            }
            $str .= '<option value="' . $tmpName . '" ' . $selected . '>' . $tmpValue . '</option>
            ';
        }
        $str .= '</select>
        ';
        return $str;
    }

}













