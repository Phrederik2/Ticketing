<?php


date_default_timezone_set('Europe/Brussels');
class Tool
{
	static private $initIsbusy = false;
	static private $ListOfJoins=array(); 
	static private $TMP_JOIN=array();
	
    static function concat($array, $separator = ", ")
    {
        $str = "";
        foreach ($array as $key => $value) {
            if ($str != "") $str .= $separator;
            $str .= $value;
        }
        return $str;
	}
	
	static function formatSize($bytes,$format = '%.2f',$lang = 'fr'){
		static $units = array(
		'fr' => array(
		'o',
		'Ko',
		'Mo',
		'Go',
		'To'
		),
		'en' => array(
		'B',
		'KB',
		'MB',
		'GB',
		'TB'
		));
		$translatedUnits = &$units[$lang];
		if(isset($translatedUnits)  === false)
		{
			$translatedUnits = &$units['en'];
		}
		$b = (double)$bytes;
		/*On gére le cas des tailles de fichier négatives*/
		if($b > 0)
		{
			$e = (int)(log($b,1024));
			/**Si on a pas l'unité on retourne en To*/
			if(isset($translatedUnits[$e]) === false)
			{
				$e = 4;
			}
			$b = $b/pow(1024,$e);
		}
		else
		{
			$b = 0;
			$e = 0;
		}
	return sprintf($format.' %s',$b,$translatedUnits[$e]);
}

static function xmlToArray($xml){
	$plainXML = self::mungXML(trim($xml));
	$arrayResult = json_decode(json_encode(SimpleXML_Load_String($plainXML, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
	return $arrayResult;
}

static function xmlToJson($xml){
	$plainXML = self::mungXML(trim($xml));
	$Result = json_encode(SimpleXML_Load_String($plainXML, 'SimpleXMLElement', LIBXML_NOCDATA),JSON_PRETTY_PRINT);
	return $Result;
}

static function escapeReturnLine($line){
	$line = str_replace(["'",chr(13).chr(10),chr(10).chr(13),chr(10),chr(13)],["\'",'\n','\n','\n','\n'],$line);
	return $line;
}
static function removeReturnLine($line){
	$line = strip_tags(str_replace([chr(10),chr(13)],' ',$line));
	return $line;
}

static function GUIDv4 ($trim = true)
	{
		// Windows
		if (function_exists('com_create_guid') === true) {
			if ($trim === true)
				return trim(com_create_guid(), '{}');
			else
				return com_create_guid();
		}

		// OSX/Linux
		if (function_exists('openssl_random_pseudo_bytes') === true) {
			$data = openssl_random_pseudo_bytes(16);
			$data[6] = chr(ord($data[6]) & 0x0f | 0x40);    // set version to 0100
			$data[8] = chr(ord($data[8]) & 0x3f | 0x80);    // set bits 6-7 to 10
			return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
		}

		// Fallback (PHP 4.2+)
		mt_srand((double)microtime() * 10000);
		$charid = strtolower(md5(uniqid(rand(), true)));
		$hyphen = chr(45);                  // "-"
		$lbrace = $trim ? "" : chr(123);    // "{"
		$rbrace = $trim ? "" : chr(125);    // "}"
		$guidv4 = $lbrace.
				substr($charid,  0,  8).$hyphen.
				substr($charid,  8,  4).$hyphen.
				substr($charid, 12,  4).$hyphen.
				substr($charid, 16,  4).$hyphen.
				substr($charid, 20, 12).
				$rbrace;
		return $guidv4;
	}

// FUNCTION TO MUNG THE XML SO WE DO NOT HAVE TO DEAL WITH NAMESPACE
static function mungXML($xml)
{
	$obj = SimpleXML_Load_String($xml);
	if ($obj === FALSE) return $xml;

	// GET NAMESPACES, IF ANY
	$nss = $obj->getNamespaces(TRUE);
	if (empty($nss)) return $xml;

	// CHANGE ns: INTO ns_
	$nsm = array_keys($nss);
	foreach ($nsm as $key)
	{
		// A REGULAR EXPRESSION TO MUNG THE XML
		$rgx
		= '#'               // REGEX DELIMITER
		. '('               // GROUP PATTERN 1
		. '\<'              // LOCATE A LEFT WICKET
		. '/?'              // MAYBE FOLLOWED BY A SLASH
		. preg_quote($key)  // THE NAMESPACE
		. ')'               // END GROUP PATTERN
		. '('               // GROUP PATTERN 2
		. ':{1}'            // A COLON (EXACTLY ONE)
		. ')'               // END GROUP PATTERN
		. '#'               // REGEX DELIMITER
		;
		// INSERT THE UNDERSCORE INTO THE TAG NAME
		$rep
		= '$1'          // BACKREFERENCE TO GROUP 1
		. '_'           // LITERAL UNDERSCORE IN PLACE OF GROUP 2
		;
		// PERFORM THE REPLACEMENT
		$xml =  preg_replace($rgx, $rep, $xml);
	}

	return $xml;

} 

static function formatNumber($number){

	if($number>1000000) return round($number/1000000,1).' M';
	if($number>1000) return round($number/1000,1).' K';
	return $number;

}

static function formatTime($number){

	if($number>60) return round($number/60,1).' Min';
	if($number>3600) return round($number/3600,1).' Hour';
	return round($number,1).' Sec';

}

static function getTable2($data){

	$str='';

	foreach($data as $key=>$value){
		if(is_array($value)){
			$str.='<tr><th>'.$key.'</th><td>'.self::getTable2($value).'</td></tr>';
		}else{
			$str.='<tr><th>'.$key.'</th><td>'.$value.'</td></tr>';
		}
		
	}

	return '<table>'.$str.'</table>';
}
	

	// transforme les balises de code en element HTML pour forcer l'affichage sous forme de texte brut
	// transforme les retours de ligne HTML en element \n pour garder la meme structure que le texte d'origine
	static function codeToText($text){
		//$sha = sha1(time().'_parse');
		$sha = '\n';
		
		$tmp = str_replace(['<br>','<br />','<br/>'],[$sha],$text);
		$tmp = str_replace(['<','>'],['&lt;','&gt;'],$tmp);
		$tmp = str_replace([$sha],['<br>'],$tmp);
		$tmp = nl2br($tmp);
		
		return $tmp;

	}
	// extrait et retourne une chaine contenue entre $start et $end
	static function strExtract($string,$start,$end){
		
		if(strpos($string,$start)!==false and strpos($string,$end)!==false){
			
				$posStart = strpos($string,'<');
				$posEnd = strpos($string,'>');
				$string = substr($string,$posStart+1,($posEnd-$posStart)-1);
			}
			
		return $string;
	}
	
	// extrait et retourne les chiffres dans une chaine
	static function numExtract($string){
		$tmp = str_split($string);
		
		$strfinal="";
		
		foreach($tmp as $char){
			if(ord($char) >=48 and ord($char) <=57)$strfinal.=$char;
		}
		return (int)$strfinal;
	}
	
	static function numExtract2($string){
		
		return (int)preg_replace('/[^0-9]/', '', $string);
	}

	static function floatExtract($string){
		
		return (float)preg_replace('/[^0-9.]/', '', $string);
	}

    static function formatDate($date)
    {
		// format d'entrée = dd/mm/yyyy hh:mm:ss
        if (strlen($date) < 16) {
            return null;
        }
        $y = substr($date, 6, 4);
        $mo = substr($date, 3, 2);
        $d = substr($date, 0, 2);
        $h = substr($date, 11, 2);
        $m = substr($date, 14, 2);
        $correction = $y . '-' . $mo . '-' . $d . ' ' . $h . ':'. $m . ':00';
		
        return $correction;
    }
	
	static function formatDate3($date)
    {
		// format d'entrée = dd/mm/yyyy hh:mm:ss
        if (strlen($date) < 16) {
            return null;
        }
        $y = substr($date, 6, 4);
        $mo = substr($date, 3, 2);
        $d = substr($date, 0, 2);
        $h = substr($date, 12, 2);
        $m = substr($date, 15, 2);
        $correction = $y . '-' . $mo . '-' . $d . ' ' . $h . ':'. $m . ':00';
		
        return $correction;
    }

	static function execBetweenBrackets($txt){
		preg_match_all("`\[([^]]*)\]`", $txt, $result, PREG_PATTERN_ORDER); 

			if(count($result[0])){
				$full = $result[0];
				$values = $result[1];

				foreach($values as $key=>$value){
					$result=null;
					$value = str_replace('()','',$value);
					$explode = explode('::',$value);
					if(count($explode)==2){
						$object=$explode[0];
						$function=$explode[1];
	
						if(method_exists($object, $function)){
							$returnCall = call_user_func(array($object,
							$function,),
							array(0)); 
							
							if(is_string($returnCall)==true){
								$result = $returnCall;
							}
							else if(get_class($returnCall)=='String'){
								$result = $returnCall;
							}
							else if(get_class($returnCall)=='Tab'){
								$result = $returnCall->showItem();
							}
							else if(is_object($returnCall)){
								$result = $returnCall->toString();
							}
							
						}
					}else if(count($explode)==1){
						if(function_exists($explode[0])){
							$result = $explode[0]();
						}
					}
					
					if($result==null){
						
						$query="SELECT ($value) as result;";
						$data = DbCo::getQuery($query);
						if(isset($data[0]['result'])){
							
							$result = $data[0]['result'];
						} 
					}

					if($result!=null){
						$txt = str_replace($full[$key],$result,$txt);
						
					}
				}

			}

		return $txt;
	}
	static function execBetweenBrackets2($txt){
		preg_match_all("`\{([^]]*)\}`", $txt, $result, PREG_PATTERN_ORDER); 

			if(count($result[0])){
				$full = $result[0];
				$values = $result[1];

				foreach($values as $key=>$value){
					$result=null;
					$value = str_replace('()','',$value);
					$explode = explode('::',$value);
					if(count($explode)==2){
						$object=$explode[0];
						$function=$explode[1];
	
						if(method_exists($object, $function)){
							$returnCall = call_user_func(array($object,
							$function,),
							array(0)); 
							if(is_string($returnCall)==true){
								$result = $returnCall;
							}
							else if(get_class($returnCall)=='String'){
								$result = $returnCall;
							}
							else if(get_class($returnCall)=='Tab'){
								$result = $returnCall->showItem();
							}
							else if(is_object($returnCall)){
								$result = $returnCall->toString();
							}
						}
					}
					else if(count($explode)==1){
						if(function_exists($explode[0])){
							$result = $explode[0]();
						}
					}
					
					if($result==null){
						
						$query="SELECT ($value) as result;";
						$data = DbCo::getQuery($query);
						if(isset($data[0]['result'])){
							
							$result = $data[0]['result'];
						} 
					}

					if($result!=null){
						$txt = str_replace($full[$key],$result,$txt);
						
					}
				}

			}

		return $txt;
	}
	
	static function date_eur_eng($date)
    {
		// format d'entrée = dd/mm/yyyy, sortie yyyy-mm-dd
        if (strlen($date) < 10) {
            return null;
        }
        $y = substr($date, 6, 4);
        $m = substr($date, 3, 2);
        $d = substr($date, 0, 2);
        $correction = "$y-$d-$m";
		
        return $correction;
    }

    static function formatDate2($date)
    {
        if (strlen($date) < 10) {
            return null;
        }
        $y = substr($date, 0, 4);
        $m = substr($date, 5, 2);
        $d = substr($date, 8, 2);
        
        $correction = $y . '-' . $m . '-' . $d . ' '.'00:00:00';
        return $correction;
    }
	
	 static function Mysql_Date($date) { 
        
	//Passe de dd/mm/yyyy vers yyyy-mm-dd
        $y = substr($date, 6, 4);
        $m = substr($date, 3, 2);
        $d = substr($date, 0, 2);
        
        $correction = $y . '-' . $m . '-' . $d ;
        return $correction;
    }

    static function concatArrayValue($data, $indice)
    {
        if (is_array($indice)) {
            $str = "";
            foreach ($indice as $value) {
                if (isset($data[$value])) {
                    $str .= $data[$value];
                }
                else {
                    $str .= $value;
                }
            }
            return $str;
        }
        else {
            return $data[$indice];
        }
    }

    
	static function serialize($data){
		$str='';
		foreach($data as $key=>$value){
			if($str!='')$str.='&';
			$str.=$key.'='.$value;
		}
		return $str;
	}

	static function serialize2($data){
		$str='';
		if($data==false)return $str;
		foreach($data as $key=>$value){
			if($str!='')$str.=',';
			$str.="'".$key."'";
		}
		return $str;
	}

	static function serializeValue($data){
		$str='';
		if($data==false)return $str;
		foreach($data as $key=>$value){
			if($str!='')$str.=',';
			$str.="'".$value."'";
		}
		return $str;
	}
	
	static function url($var=array(),$recupvalue=true){

		$strvar='';
		$get=array();

		if($recupvalue){
			foreach($_GET as $key=>$value){
				$get[$key]=$value;
			}
		}
		

		foreach($var as $key=>$value){
			if($value==null){
				unset($get[$key]);
			}else{
				$get[$key]=$value;
			}
			
		}

		foreach($get as $key=>$value){
			if($strvar=='')$strvar.='?';
			if($strvar!='?')$strvar.='&';
			$strvar.= $key.'='.$value;
		}
		return self::baselink().$strvar;
	}

	static function getOption($keysearch){

        foreach ($_POST as $keyp => $value) {
            if(strtolower($keyp)==strtolower($keysearch)){
				
				return $value;
            }
        }
        foreach ($_GET as $keyp => $value) {
            if(strtolower($keyp)==strtolower($keysearch)){
				
                return $value;
            }
        }
        return null;
    }

	static function link($label,$url='#',$target=false,$title=""){
		if($target){
			return '<a href="'.$url.'" target="_blank" title="'.$title.'">'.$label.'</a>';
		}
		return '<a href="'.$url.'" title="'.$title.'">'.$label.'</a>';
	}

	static function linkInline($label,$url='#',$target=false){
		if($target){
			return '<span onclick="window.open(\''.$url.'\').focus();" class="inline">'.$label.'</span>';
		}
		return '<span onclick="window.open(\''.$url.'\',\'_blank\').focus();;" class="inline">'.$label.'</span>';
	}

	static function baselink(){
		$link='https://TOFU.bc/';

		$protocol='http';
		if(isset($_SERVER['HTTPS']))$protocol='https';

		if(isset($_SERVER['HTTP_HOST']) and isset($_SERVER['SCRIPT_NAME'])){
			$baselink = $protocol.'://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'].$_SERVER['PATH_INFO'] ;
			$baselink = str_replace('var/www/html','TOFU.bc',$baselink);
			$baselink = str_replace('callback.php','',$baselink);
			
			return $baselink;
		}
		else{
			return $link;
		}
		
	}

	static function thisServerIsProd(){
		$host= gethostname();
		$ip = gethostbyname($host);
		if($ip == '10.61.134.235'){
			return true;
		}

		return false;
	}

	static function gethostname(){
		
		return gethostname();
	}

	 

	static function urlPictures($name){
		$pre="";
		if(isset($GLOBALS['prefixe_include'])){
			$pre = $GLOBALS['prefixe_include'];
		}
		return $pre."/pictures/$name";

	}

	static function urlCallable($view,$function,$args=array()){
		$array=array('View'=>null,
		'Callable'=>$view,
		'Request'=>$function,
		'AJAX_KEY'=>time());

		foreach($args as $key=>$item){
			$array[$key]=$item;
		}

		return Tool::url($array);
	}

	static function hrefCallable($title,$view,$function,$args=array(),$target=true){
		$url = Tool::urlCallable($view,$function,$args);
		$t = $target==true?'target="_blank"':'';
		return '<a href="'.$url.'" '.$t.'>'.$title.'</a>';
	}

    static function exportExcell($data,$fileName){
        //header("Content-type: application/vnd.ms-excel");
		//header("Content-disposition: attachment; filename=$fileName");  
		
		header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
		header("Content-Disposition: attachment;filename=\"$fileName\"");
		header("Cache-Control: max-age=0");


		print Tool::getTableLight($data); 

		exit;

    }
	
	static function getTableLight($data,$html="",$repeatheader=10,$usejquery=false,$header=null,$caption=''){
		if(count($data)==0 and $caption=='')return '';
		
		foreach($data as $line){
			if(!is_array($line)){
				$tmp=array();
				$tmp[]=$data;
				$data=$tmp;
				break;
			}
		}

		$script='';
		if($usejquery==true){
			$sha = sha1(serialize($data));
			$script='<script>$(document).ready(function() {$("#'.$sha.'").DataTable( {"info":false, "paging": false, "searching": false } );} );</script>';
		}
		
		
		
        $strFinal="";
		$lastheader=0;
		
      
        foreach ($data as $line) {
			$str="";
			if($lastheader==$repeatheader)$lastheader==0;
			if($lastheader==0){
				if($header==null){
					$str = Tool::getHeader($data)."<tbody>";
				}
				else{
					if(is_array($header)){
						$headerstr ="";
						foreach($header as $item){
							$headerstr .='<th>'.$item.'</th>';
						}
						$str="<thead><tr>".$headerstr."</tr></thead><tbody>";
					}
					else{
						$str="<thead><tr>".$header."</tr></thead><tbody>";
					}
					
				}
					
			}

			$str.="<tr>";
			foreach ($line as $key => $value) {
				if($str!="")$str.="";
					if(is_array($value)){
						$str.="<td>".Tool::getTableLight($value,$html,$repeatheader,$usejquery,$header,$caption)."</td>";
					}
					else if(!is_object($value)){	
						$str.="<td>".$value."</td>";
					}
					else if(is_object($value) and method_exists(get_class($value),'toString')){
						$str.="<td>".$value->toString()."</td>";
					}
					
				}
				
			$str.="</tr>";	
            $strFinal.=$str;
			$lastheader++;
        }
		if($usejquery==true and $html==""){
			$html = 'id="'.$sha.'"';
		}

		$strcaption='';
		if($caption!=''){
			$strcaption= '<caption>'.$caption.'</caption>';
		}

        $str='<div class="overflow_free">'."$script<table $html>$strcaption".$strFinal."</tbody></table>".'</div>'; 
			return $str;

    }
	
	static function getValue($data){
		$str="<tr>";
			foreach ($data as $key => $value) {
				if($str!="")$str.="";
				if(is_object($value)){
					$str.="<td>".$value->toString()."</td>";
				}
				else{	
					$str.="<td>".$value."</td>";
				}
			}
		$str.="</tr>";
		return $str; 
	}
	
	static function getHeader($data){
		$str="";
		foreach ($data as $line) {
            $str="<thead><tr>";
            foreach ($line as $key => $value) {
                if($str!="")$str.="";
                $str.="<th>".$key."</th>";
            }
            $str.="</tr></thead>";
            
            break;
        }
		return $str;
	}
	
	 static function moveDay($day,$shifting,$format ="Y-m-d") { 
		if(date("w",strtotime($day))==5)$shifting+=2;
		if(date("w",strtotime($day))==6)$shifting+=1;
		$date = date($format,(mktime(0, 0, 0, date("m",strtotime($day))  , date("d",strtotime($day))+$shifting, date("Y",strtotime($day)))));
		return $date;
     }
     
     static function getTable($data){
         		
		$str="<table>";
		$i=0;
		foreach($data as $line){
			$str.="<tr>";
					
			foreach($line as $key => $item){
                
				if(is_object($item)){
                    $tmp = $item->toString();
                }
                else{
                    $tmp=$item;
                }
				if($i==0){
					
					$str.="<th>".$tmp ."</th>";
				}
				else
				{
					$str.="<td>".$tmp ."</td>";
				}
			}
			
			$str.="</tr>";
			$i++;
		}
		$str.="</table>";
		return $str;
    }

	static function TimerException($folder) { 
		$time2=null;
		$t1 = null;
		$t2 = null;
		if(isset($_SESSION[$folder."Time"])){
			
			date_default_timezone_set('UTC');
			$t1 = microtime(true);
			$t2 = $_SESSION[$folder."Time"];
			$time = $t1-$t2;
			$_SESSION[$folder."Time"]= microtime(true);	
			$hour = strftime("%H",$time);
			$min = strftime("%M",$time);
			$sec = strftime("%S",$time);
			return ("$hour:$min:$sec"); 
		}
		$_SESSION[$folder."Time"]= microtime(true);
		
		return null;
		
     }
	 
	 static function strtotimestamp($time,$correctHour=0){
		date_default_timezone_set('Europe/Brussels');
		$y= substr($time,6,4);
		$M= substr($time,3,2);
		$d= substr($time,0,2);
		$h= (int)substr($time,11,2)+$correctHour;
		$m= substr($time,14,2);
		$s= substr($time,17,2);
		$timestamp=mktime($h,$m,$s,$M,$d,$y);
		return $timestamp;
		
	 }
	 
	

	 static function uService_To_Json_To_CSV($file,$destination){
		$data = file_get_contents($file);
		
		$tab = json_decode($data, true);
		//echo Tool::getTableLight($tab);
		//return;
		$str="";
		foreach($tab as $key=>$value){
			foreach($value as $k=>$v){
				if($str!='')$str.='|';
				$str.=$k;
			}
			$str.=chr(13);
			break;
		}

		foreach($tab as $key=>$value){
			foreach($value as $k=>$v){
				if($str!='')$str.='|';
				$str.=str_replace('|',';',$v);
			}
			$str.=chr(13);
			
		}

		$handle = fopen($destination, 'a');
		fwrite($handle, $str);
		fclose($handle);

		return true;
	 }
	 
	 static function normalizeString($str)
	{
		if(is_array($str)){
			foreach($str as &$item){
				
				//$item = htmlentities($item, ENT_QUOTES, "UTF-8");
			}
		}
		else{
			
			//$str = htmlentities($str, ENT_QUOTES, "UTF-8");
		}
		
		return $str;
	}
	 static function formatCalc($calc)
	{
			$t= (float)($calc*100);
			
		return round($t,2,PHP_ROUND_HALF_EVEN).'%';
	}
	
	 static function htmlDecode($data)
	{
			if(is_array($data)){
				foreach($data as &$line){
					$line = self::htmlDecode($line);
				}
			}
			else{
				
				$data = html_entity_decode($data);
				$data = html_entity_decode($data);
				$data = utf8_encode($data);
			}
			
		return $data;
	}
	 static function htmlEncode($data)
	{
			if(is_array($data)){
				foreach($data as &$line){
					$line = self::htmlEncode($line);
				}
			}
			else{
				
				
			}
			
		return $data;
	}
	
	
	static function uploadFile($idhtml,$extensionAutorised,$target_dir,$sizelimit,$name=null){
		
		$finalName="";
		
		//Voir si il existe un fichier en upload
		if (!isset($_FILES[$idhtml])) return false;
		
		$target_file = $target_dir . basename($_FILES[$idhtml]['name']);
			
		// recuperer la fin du nam du fichier pour extraire le préfixe
		$extension=null;
		$tmpExtention = substr($_FILES[$idhtml]['name'],strrpos($_FILES[$idhtml]['name'],'.')+1);
			
		// verifier le prefixe du fichier avec la liste des préfixe autorisé
		foreach($extensionAutorised as $value){
			if(stripos($tmpExtention,$value)!==false){
				$extension=$value;
			}
		}
		
		// si  l'extention est ok et que la taille aussi, alors definir le chemin du fichier et le deplacer
		if($extension!=null and $_FILES[$idhtml]["size"]<=$sizelimit){
			
			
			
			if($name==null){
				$finalName= $target_file;
			}
			else{
				$finalName= $target_dir.'/'.$name.'.'.$extension;
			}

			move_uploaded_file($_FILES[$idhtml]["tmp_name"], $finalName);
		}
			
		return $finalName;
	}
	
	
	
	
	static function context($var=null)
	{
			
			ob_start();
			var_dump($var);
			$var = ob_get_clean();
			
				$context="";
				$e = new Exception();
				$context=str_replace('/path/to/code/', '', $e->getTraceAsString());
				$context=str_replace('#', '<br>#', $context);
				//$context=self::debug_string_backtrace();		
				
				$str="<form><fieldset><legend>Context</legend>";
				
				$str .= "var_dump: ".$var.'<br>';                        
				$str .= "Context:                         
				$context";
				$str .= "</div></fieldset></form>";
		
		return $str;
	}
	
	/*static function ReadExcell($source){
		include_once('classes/PHPExcel-1.8/PHPExcel-1.8/Classes/PHPExcel.php');
		
		date_default_timezone_set('Europe/Brussels');
		$inputFileName = $source;


		$objPHPExcel = PHPExcel_IOFactory::load($inputFileName);

		$sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
		
		$header=array();
		$data=array();
		
		foreach($sheetData as $i=>$line){
			if($i==1){
				foreach($line as $j=>$value){
					$header[$j]=$value;
				}
				var_dump($header);

			}
			else{
				$tmp=array();
				foreach($line as $j=>$value){
					$tmp[$header[$j]]=$value;
				
				}
				$data[]=$tmp;
			}
			
		}
		
		return $data;
	}*/

	



	

	static function skip_accents( $str, $charset='utf-8' ) {
 
		$str = htmlentities( $str, ENT_NOQUOTES, $charset );
		
		$str = preg_replace( '#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str );
		$str = preg_replace( '#&([A-za-z]{2})(?:lig);#', '\1', $str );
		$str = preg_replace( '#&[^;]+;#', '', $str );
		
		return $str;
	}

	static function prefixe(){
		
		if(isset($GLOBALS['prefixe_include'])){
			return $GLOBALS['prefixe_include'];
		}
		return '';
	}


	

	static function CSVDeposit($data,$destination,$separator=';',$lineHeader=0){
		$str = "";
			
		
		$tmp="";
		if(isset($data[$lineHeader])){
			foreach($data[$lineHeader] as $key=>$value){
				if($tmp!="")$tmp.=$separator;
				$tmp.=$key;
			}
			if($tmp!="")$str.=$tmp.chr(10);
		
			foreach($data as $line){
				$tmp="";
				foreach($line as $key=>$value){
					if($tmp!="")$tmp.=$separator;
					$value = utf8_decode(html_entity_decode($value));
					$value = strip_tags($value);
					$value = strip_tags($value);
					$value = str_replace(array('&#39;',$separator,"\n","\r"),array("'",'','\n','\r'),$value);
					$tmp.=$value;
				}
				if($tmp!="")$str.=$tmp.chr(10);
					
			}

			$dest = str_replace('/','\\',$destination);
			$dest = $destination;
			if(file_exists($dest)){
				unlink($dest);
			}
			
			$fh = fopen($dest, 'a');
			if($fh){
				
				$str = str_replace('&#39;',"'",$str);
				fwrite($fh, html_entity_decode($str));
				fclose($fh);
				return true;
			}
		}
		return false;
		
		
	}

	static function genericCallableURL( $args ) {
 
		$sha = sha1(serialize($args).time());
		$_SESSION['CallableURLSHA'][$sha]=$args;
		
		return Tool::urlCallable('CallableURL','Launch',['CallableURLSHA'=>$sha]);
	}

	static function setTransaction( $args ) {
 
		$sha = sha1(serialize($args).time());
		$_SESSION['CallableURLSHA'][$sha]=$args; 
		
		return $sha;
	}

	
	static function transaction($title=null, $object,$function,$args=[] ) {
		$args['object']=$object;
		$args['function']=$function;
		$sha = sha1(serialize($args).time());
		$_SESSION['CallableURLSHA'][$sha]=$args; 
		if($title==null){
			return Tool::genericCallableURL($args);
		}
		
		return '<a href="'.Tool::genericCallableURL($args).'"> '.$title.'</a>';
	}

	static function getgenericCallableURL_Args( $sha ) {
 
		if(isset($_SESSION['CallableURLSHA'][$sha])){
			return $_SESSION['CallableURLSHA'][$sha];
		}
		return null;
		
	}
	static function getTransaction( $sha ) {
 
		if(isset($_SESSION['CallableURLSHA'][$sha])){
			return $_SESSION['CallableURLSHA'][$sha];
		}
		return null;
		
	}

	static function cleanTransaction( $sha ) {
 
		if(isset($_SESSION['CallableURLSHA'][$sha])){
			unset($_SESSION['CallableURLSHA'][$sha]);
		}
		
		
	}


	static function checkBOM($filename){
	
		$contents=file_get_contents($filename);
		$charset[1]=substr($contents,0,1);
		$charset[2]=substr($contents,1,1);
		$charset[3]=substr($contents,2,1);
		if(ord($charset[1])==239 && ord($charset[2])==187 && ord($charset[3])==191){	
				$rest=substr($contents,3);
				unlink($filename);
				$filenum=fopen($filename,'a');

				if($filenum){
					flock($filenum,LOCK_EX);
					fwrite($filenum,$rest);
					fclose($filenum);
					return true;
				}
		}
		else{
			return true;
		}
	}

	static function checkBOMString($string){
	
		$rest = $string;
		$charset[1]=substr($string,0,1);
		$charset[2]=substr($string,1,1);
		$charset[3]=substr($string,2,1);
		if(ord($charset[1])==239 && ord($charset[2])==187 && ord($charset[3])==191){	
			$rest=substr($string,3);
		}

		return $rest;
	}

	static function iconv_UCS2L_To_UTF8BE($file,$destination){
	
		$content = file_get_contents($file);
		$content = iconv('UCS-2LE', 'UTF-8', $content);
		$content = Tool::checkBOMString($content);
		$r = file_put_contents($destination, $content);
		return $r;

	}

	static function strToUnixDateTime($value){
		$pregTime=0;
		$value = trim($value);
		$s2=microtime(true);
		$r = str_replace('  ',' ',$value);
		$r = preg_split('/[ tT]/', $r);
		
		if(count($r)==2){
			$d = self::strToUnixDate($r[0]);
			$t = self::strToUnixTime($r[1]);
			$pregTime+=(microtime(true)-$s2);
			//echo $value.'=>'. $d.' '.$t.'<br>';
			return $d.' '.$t;
		}
		//echo $value.'=>'.''.'<br>';
		return $value;

		
	}
	static function strToUnixTime($value){
		$pregTime=0;
		$value = trim($value);
		$s2=microtime(true);

		$pattern=array();
		$replace = array();
		$pattern[]='/^([0-9]{1,2})([0-9]{1,2})$/';
		$replace[]='$1:$2:00';
		$pattern[]='/^([0-9]{1,2}):([0-9]{1,2})$/';
		$replace[]='$1:$2:00';
		$pattern[]='/^([0-9]{1,2}):([0-9]{1,2}):([0-9]{1,2})$/';
		$replace[]='$1:$2:$3';
		$pattern[]='/^([0-9]{1}):([0-9]{1,2}):([0-9]{1,2})$/';
		$replace[]='0$1:$2:$3';
		$pattern[]='/^([0-9]{2}):([0-9]{1}):([0-9]{1,2})$/';
		$replace[]='$1:0$2:$3';
		$pattern[]='/^([0-9]{2}):([0-9]{2}):([0-9]{1})$/';
		$replace[]='$1:$2:0$3';
		
		
		$r = preg_replace($pattern, $replace, $value);
		//echo $value.'=>'.$r.'<br>';
		$pregTime+=(microtime(true)-$s2);
		return $r;

		
	}

	static function validateDate($date, $format = 'Y-m-d H:i:s')
	{
		// replace a 'Z' at the end by '+00:00'
		$date = preg_replace('/(.*)Z$/', '${1}+00:00', $date);
	
		$d = DateTime::createFromFormat($format, $date);
		return $d && $d->format($format) == $date;
	}

	static function strToUnixDate($value){
		$pregTime=0;
		$value = trim($value);
		$value = substr($value,0,10);
		$s2=microtime(true);
		
		$pattern=array();
		$replace = array();
		
		$pattern[]='/([0-9]{4})([0-9]{2})([0-9]{2})$/';
		$replace[]='$1-$2-$3';
		$pattern[]='/([0-9]{1,2})[-|\/|\.]([0-9]{1,2})[-|\/|\.]([0-9]{4})$/';
		$replace[]='$3-$2-$1';
		$pattern[]='/([0-9]{4})[-|\/|\.]([0-9]{1,2})[-|\/|\.]([0-9]{1,2})$/';
		$replace[]='$1-$2-$3';
		$pattern[]='/([0-9]{4})[-]([0-9]{1})[-]([0-9]{1})$/';
		$replace[]='$1-0$2-0$3';
		$pattern[]='/([0-9]{4})[-]([0-9]{1})[-]([0-9]{2})$/';
		$replace[]='$1-0$2-$3';
		$pattern[]='/([0-9]{4})[-]([0-9]{2})[-]([0-9]{1})$/';
		$replace[]='$1-$2-0$3';
		
		$r = preg_replace($pattern, $replace, $value);
		/*$r=$value;
		foreach($pattern as $nb=>$pat){
			$r = preg_replace($pat, $replace[$nb], $r);
		}*/
		//echo $value.'=>'.$r.'<br>';
		$pregTime+=(microtime(true)-$s2);
		
		return $r;

		
	}
	

	/*static function CSVDeposit2($data,$destination,$separator=';'){
		$str = "";

		if(file_exists($dest)){
			unlink($dest);
		}

		$fh = fopen($dest, 'a');	
		foreach($data as $line){
			$tmp="";
			foreach($line as $key=>$value){
				if($tmp!="")$tmp.=$separator;
				$tmp.=$key;
			}
			if($tmp!=""){
				$str.=$tmp.chr(10);
				fwrite($fh, utf8_decode($tmp.chr(10)));
			}
			break;
		}
			
		foreach($data as $line){
			$tmp="";
			foreach($line as $key=>$value){
				if($tmp!="")$tmp.=$separator;
				$tmp.=$value;
			}
			if($tmp!=""){
				$str.=$tmp.chr(10);
				fwrite($fh, utf8_decode($tmp.chr(10)));
				sleep(1);
			}

				
		}

		$dest = str_replace('/','\\',$destination);
		$dest = $destination;
		
		
		
		
		fclose($fh);
		
		
	}*/
	
}

abstract class Lorem {
    public static function ipsum($nparagraphs) {
        $paragraphs = [];
        for($p = 0; $p < $nparagraphs; ++$p) {
            $nsentences = random_int(3, 8);
            $sentences = [];
            for($s = 0; $s < $nsentences; ++$s) {
                $frags = [];
                $commaChance = .33;
                while(true) {
                    $nwords = random_int(3, 15);
                    $words = self::random_values(self::$lorem, $nwords);
                    $frags[] = implode(' ', $words);
                    if(self::random_float() >= $commaChance) {
                        break;
                    }
                    $commaChance /= 2;
                }

                $sentences[] = ucfirst(implode(', ', $frags)) . '.';
            }
            $paragraphs[] = implode(' ', $sentences);
        }
        return implode("\n\n", $paragraphs);
    }

    private static function random_float() {
        return random_int(0, PHP_INT_MAX - 1) / PHP_INT_MAX;
    }

    private static function random_values($arr, $count) {
        $keys = array_rand($arr, $count);
        if($count == 1) {
            $keys = [$keys];
        }
        return array_intersect_key($arr, array_fill_keys($keys, null));
    }

    private static $lorem = ['lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur', 'adipiscing', 'elit', 'praesent', 'interdum', 'dictum', 'mi', 'non', 'egestas', 'nulla', 'in', 'lacus', 'sed', 'sapien', 'placerat', 'malesuada', 'at', 'erat', 'etiam', 'id', 'velit', 'finibus', 'viverra', 'maecenas', 'mattis', 'volutpat', 'justo', 'vitae', 'vestibulum', 'metus', 'lobortis', 'mauris', 'luctus', 'leo', 'feugiat', 'nibh', 'tincidunt', 'a', 'integer', 'facilisis', 'lacinia', 'ligula', 'ac', 'suspendisse', 'eleifend', 'nunc', 'nec', 'pulvinar', 'quisque', 'ut', 'semper', 'auctor', 'tortor', 'mollis', 'est', 'tempor', 'scelerisque', 'venenatis', 'quis', 'ultrices', 'tellus', 'nisi', 'phasellus', 'aliquam', 'molestie', 'purus', 'convallis', 'cursus', 'ex', 'massa', 'fusce', 'felis', 'fringilla', 'faucibus', 'varius', 'ante', 'primis', 'orci', 'et', 'posuere', 'cubilia', 'curae', 'proin', 'ultricies', 'hendrerit', 'ornare', 'augue', 'pharetra', 'dapibus', 'nullam', 'sollicitudin', 'euismod', 'eget', 'pretium', 'vulputate', 'urna', 'arcu', 'porttitor', 'quam', 'condimentum', 'consequat', 'tempus', 'hac', 'habitasse', 'platea', 'dictumst', 'sagittis', 'gravida', 'eu', 'commodo', 'dui', 'lectus', 'vivamus', 'libero', 'vel', 'maximus', 'pellentesque', 'efficitur', 'class', 'aptent', 'taciti', 'sociosqu', 'ad', 'litora', 'torquent', 'per', 'conubia', 'nostra', 'inceptos', 'himenaeos', 'fermentum', 'turpis', 'donec', 'magna', 'porta', 'enim', 'curabitur', 'odio', 'rhoncus', 'blandit', 'potenti', 'sodales', 'accumsan', 'congue', 'neque', 'duis', 'bibendum', 'laoreet', 'elementum', 'suscipit', 'diam', 'vehicula', 'eros', 'nam', 'imperdiet', 'sem', 'ullamcorper', 'dignissim', 'risus', 'aliquet', 'habitant', 'morbi', 'tristique', 'senectus', 'netus', 'fames', 'nisl', 'iaculis', 'cras', 'aenean'];
}

