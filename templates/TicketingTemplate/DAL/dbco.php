<?php

/**
 * Class connexion db
 */

/**
 * Undocumented class
 */
class DbCo
{
	//Paramètrage des valeurs pour connexion

	//public static $pdo = null;
	public static $pdo = array();
	public static $oldQuery = null;
	public static $debugStatement = false;
	public static $strDebug = '';
	public static $oldStatement = null;
	public static $safeData = true;
	public static $errorstr = false;
	public static $queryError = false;

	/**
	 * Undocumented function
	 */
	public function __construct()
	{
		self::getQuery('');
	}


	static function destroyPDO()
	{
		DbCo::$pdo = array();
	}

	static function selectDB($db = 'default')
	{
		if ($db === 'default') {
			$dbopen = 'Ticketing';

			self::init($db, 'localhost', 'nextcloud', 'prouteproute!tagada', $dbopen);
		}
	}

	/**
	 * Initialisation des valeur pour la connexion et création de la connexion
	 *
	 * @return void
	 */
	static function init($name, $localhost, $user, $password, $db)
	{
		//echo $name.' - '.$localhost.' - '.$user.' - '.$password.' - '.$db.'<br>';

		if (!isset(self::$pdo[$name]) or self::$pdo[$name] == null)
			/*if(session_status()==PHP_SESSION_NONE){
			if (!@\session_start()) return false;
		}*/

			try {
				//Utilisation d'une connexion pdo

				self::$pdo[$name] = new \PDO(
					"mysql:host=" . $localhost . ";dbname=" . $db . '; charset=utf8',
					$user,
					$password,
					array(
						PDO::ATTR_TIMEOUT => 5 // in seconds

					)
				);


				//self::$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
				//self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			} catch (Exception $e) {

				die('Erreur : ' . $e->getMessage());
			}
	}

	public static function isError()
	{
		return self::$errorstr;
	}

	public static function setPDO(PDO $value)
	{
		self::$pdo = $value;
	}
	/**
	 * Undocumented function
	 *
	 * @param string $db
	 * @return PDO
	 */
	public static function getPDO($db = 'default')
	{
		if (!isset(self::$pdo[$db])) {
			self::selectDB($db);
			//self::init(s$db,self::$localhost,self::$user,self::$password,self::$db);
		}
		if (isset(self::$pdo[$db])) return self::$pdo[$db];
		else return null;
	}

	static function debugLastStatement()
	{
		self::$oldStatement->debugDumpParams();
	}

	/** */
	static function getQuery($query, $args = [], $nl2br = true, $limit = " LIMIT 0,1000;", $ExplainSQLActiv = true, $safeData = true, $inError = array(), $db = 'default')
	{
		// verifie si la query est identique et ne traite que son execution si necessaire.
		if (self::getPDO($db) == null) {
			return [];
		}
		if (self::$safeData == false) {
			$safeData = false;
			self::$safeData = true;
		}

		//if(strstr(strtolower($query), "show processlist")==false and Tool::isbusy()==true) return array();

		if ($query == '') return array();
		$addLimit = "";
		if (!isset($_SESSION['notRecurviveDebug'])) {
			foreach (get_defined_constants() as $key => $value) {

				$query = str_replace("_{$key}_", $value, $query);
			}
		}
		if (
			strstr(strtolower($query), ";") == false
			and strstr(strtolower($query), "limit") == false
			and strstr(strtolower($query), "update") == false
			and strstr(strtolower($query), "delete") == false
			and strstr(strtolower($query), "create") == false
			and strstr(strtolower($query), "insert") == false
			and strstr(strtolower($query), "select") == true
		) {
			$addLimit = $limit;
			$query .=  $addLimit;
		}

		if (
			strstr(strtolower($query), "update")
			or strstr(strtolower($query), "delete")
			or strstr(strtolower($query), "insert")
		) {
			self::$oldStatement = null;
		}
		$start = microtime(true);


		if (count($args)) {

			// verifie si la requete actuel est la meme que la requete precedente
			// si c'est le cas, réutilise le statement precedent car la requete est deja preparée.
			$statement = null;


			if ($query == self::$oldQuery and self::$oldStatement != null) {
				//$statement=self::$oldStatement;
			} else {
				// prepare la requete si la query n'est pas la meme que la query precedente
				$statement = self::getPDO($db)->prepare($query . " " . $addLimit);
				//self::$oldStatement=$statement;
			}

			//self::$oldQuery=$query;
			$statement = self::getPDO($db)->prepare($query . " " . $addLimit);
			// dans tout les cas execute les arguments

			if (is_array($args)) {

				$statement->execute($args);
			} else {
				$statement->execute();
			}




			if (self::$debugStatement == true) {
				self::$debugStatement = false;
				ob_start();
				$statement->debugDumpParams();
				$r = ob_get_contents();
				ob_end_clean();
				self::$strDebug = $r;
			}
		} else {
			/*if(self::$oldStatement!=null){
				self::$oldStatement->closeCursor();
			}*/

			$statement = self::getPDO($db)->query($query . " " . $addLimit);
		}

		$end = microtime(true);


		if ($ExplainSQLActiv == true and isset($_GET['ExplainSQL']) and $end - $start > $_GET['ExplainSQL'] and strstr(strtolower(strtolower($query)), strtolower("SELECT")) == true) {

			$explain = self::getQuery('explain ' . $query, $args, true, "", false);
			$count = 1;
			foreach ($explain as $line) {
				$count = $count * $line['rows'];
			}
			$explain = Tool::getTableLight($explain);

			$query = self::getLastQuery($query, $args);
			$sec = 0;
			if ($count != 0) {
				$sec = round(($end - $start) / $count, 5);
			}

			echo '<br><fieldset><legend>Duration SQL: ' . round($end - $start, 5) . '</legend>' . nl2br($query . " ")
				. '<br>' . $explain . '
				<br> rows=> ' . $count . ', / sec => ' . $sec . '
				</fieldset>';
			echo '<br>' . Tool::context() . '<br>';
		}


		return self::getData($statement, $query, $args, $nl2br, $safeData, $inError);
	}

	static function getLastQuery($query, $args)
	{
		foreach ($args as $key => $arg) {

			if (is_numeric($key)) {
				$pos = strpos($query, '?');
				if ($pos) $query = substr_replace($query, self::getPDO()->quote($arg), $pos, 1);
			} else {

				$query = str_replace($key, self::getPDO()->quote($arg) . '(' . $key . ')', $query);
			}
		}

		return $query;
	}

	/**
	 * Prend un statement SQL en parametre et place le tout dans un array et retourne l'array
	 *
	 * @param [statement SQL] $statement
	 * @return array
	 */

	static function getData($statement, $query = null, $args, $nl2br = true, $safeData = true, $inError = array())
	{

		//return array();

		$data = array();

		$meta = array();

		// si le statement existe
		if ($statement) {
			// récuperé le nombre de collone contenue dans le statement
			$count = $statement->columnCount();

			//si le statement possede au moins 1 collones
			if ($count) {

				// récuperer tout les meta données de toutes les collones
				for ($i = 0; $i < $count; $i++) {
					$meta[$i] = $statement->getColumnMeta($i);
				}

				// composer le nom de la collone 
				$header = array();
				foreach ($meta as &$line) {

					if (!isset($line['table'])) $line['table'] = null;
					if (!isset($line['name'])) $line['name'] = null;

					$table = $line['table'];
					$name = $line['name'];

					// si la collone[$name] existe deja alors le nom de la collones est $table.'.'.$name (on rajoute l'alias de la table)
					if (isset($header[$name]) and $table != null) {
						$tmpName = $table . '.' . $name;
						$header[$tmpName] = true;
						$line['finalName'] = $tmpName;
					} else { // si c'est la premiere fois qu'on rencontre le nom alors la collone s'appelle
						$header[$name] = true;
						$line['finalName'] = $name;
					}
				}
				unset($line);

				// récuperation des données lignes par ligne 
				$dataTMP = $statement->fetchall(\PDO::FETCH_NUM);

				foreach ($dataTMP as $row) {
					$tmp = array();
					foreach ($row as $key => $value) {
						$tmpvalue = $value;
						if ($safeData == true) {
							//$tmpvalue = Tool::htmlEncode($tmpvalue);
						}
						if ($nl2br == true and $safeData == true) {
							if (is_string($tmpvalue)) {
								$tmpvalue = nl2br($tmpvalue);
							} else {
								if ($safeData == true) {
									$tmpvalue = (string)($tmpvalue);
								}
							}
						}

						// envoi des données avec l'association clé=>valeur dans le tableau temporaire
						$tmp[$meta[$key]['finalName']] = $tmpvalue;
					}


					array_push($data, $tmp);
				}
			}
		}


		//echo "Aucune donnée retournée pour cette demande!";

		if ($statement != null) {
			$error = $statement->errorInfo();
		} else {
			$error = self::getPDO()->errorInfo();
		}


		if (isset($_SESSION['Tracking']) and $_SESSION['Tracking'] == true and !isset($_SESSION['notRecurviveDebug'])) {
			$_SESSION['notRecurviveDebug'] = true;
			unset($_SESSION['notRecurviveDebug']);
		}


		if ($error[1] != 0 or $error[2] != "") {

			foreach ($args as $key => $arg) {

				if (is_numeric($key)) {
					$pos = strpos($query, '?');
					if ($pos) $query = substr_replace($query, self::getPDO()->quote($arg), $pos, 1);
				} else {

					$query = str_replace($key, self::getPDO()->quote($arg), $query);
				}
			}

			//$query = str_replace(array(chr(10),chr(13),"\n","\r",'<br />'),array(' ', ' ',' ',' ',' '),$query);
			//$query =strip_tags($query);

			$str = "<form><fieldset><legend>SQL ERROR</legend>";
			$str = "Query:<p>" . nl2br($query) . "</p>";
			$str .= "<div class=\"error\">";
			$str .= "SQLSTATE :                         $error[0]<br>";
			$str .= "Error:                         $error[1]<br>";
			$str .= "Message:                         $error[2]<br>";
			$str .= "Context:                         
				" . Tool::context();
			$str .= "</div></fieldset></form>";
			echo $str;




			return $inError;
		}

		return $data;
	}

	static function debug_string_backtrace()
	{
		ob_start();
		debug_print_backtrace();
		$trace = ob_get_contents();
		ob_end_clean();

		// Remove first item from backtrace as it's this static function which 
		// is redundant. 
		$trace = preg_replace('/^#0\s+' . __FUNCTION__ . "[^\n]*\n/", '', $trace, 1);

		// Renumber backtrace items. 
		$trace = preg_replace('/^#(\d+)/me', '\'#\' . ($1 - 1)', $trace);

		return $trace;
	}

	static function nl2br2($string)
	{
		$string = str_replace(array("\r\n", "\r", "\n"), "<br />", $string);
		return $string;
	}
}

class Query{

	static function getCustomer($id){
		$sql = 'SELECT name FROM Ticketing.Customer WHERE id=?';
		return DbCo::getQuery($sql,[$id]);
	}

	static function getBooklet($id){
		$sql = 'SELECT c.name, b.initialpoint, 
		sum(if(i.gift=0,if(i.override=1,i.overridepoint,i.point),null)) as sumpointuse, 
		sum(if(i.gift!=0,i.point,null)) as sumpointgift, 
		b.initialpoint-sum(if(i.gift=0,if(i.override=1,i.overridepoint,i.point),null)) as sumpointremaining
		FROM Ticketing.Customer as c 
				LEFT join Ticketing.Booklet as b on c.id=b.customer_id
				left join Ticketing.Intervention as i on b.id=i.booklet_id
				WHERE b.id=?';

		return DbCo::getQuery($sql,[$id]);
	}

	static function setOverrideIntervention($id,$overridepoint){
		$sql = 'UPDATE Ticketing.Intervention
				SET override=1, overridepoint=?
				WHERE id=?;
				COMMIT;';

		return DbCo::getQuery($sql,[$overridepoint,$id]);
	}

	static function setNewCarnet($customerid,$initialPoint){
		$sql = 'INSERT INTO Ticketing.Booklet (customer_id,initialpoint) VALUES (?,?);
				COMMIT;';

		return DbCo::getQuery($sql,[$customerid,$initialPoint]);
	}

	static function getMaxBookletForCustomer($customerid){
		$sql = 'SELECT max(id) as max FROM Ticketing.Booklet WHERE customer_id=?';

		return DbCo::getQuery($sql,[$customerid]);
	}

	static function setNewInterventionOverride($interventionOrigine,$bookletid,$overridePoint){
		$sql = 'INSERT INTO Ticketing.Intervention (`customer_id`, `booklet_id`, `gift`, `start`, `end`, `user`, `remark`, `point`, `override`, `overridePoint`,`OriginalIntervention`)
				SELECT `customer_id`, '.$bookletid.', `gift`, `start`, `end`, `user`, `remark`, `point`, true, '.$overridePoint.','.$interventionOrigine.' FROM Ticketing.Intervention where id=?;
				COMMIT;';
		//echo nl2br($sql).'<br>';
		return DbCo::getQuery($sql,[$interventionOrigine]);
	}

	static function getListBooklet($customerid){
		$sql = 'SELECT b.id, b.initialpoint-sum(if(i.gift!=1,if(i.override=0,i.point,i.overridepoint),0)) as solde FROM Ticketing.Booklet as b 
		LEFT JOIN Ticketing.Intervention as i on b.id=i.booklet_id 
		WHERE b.archive=0 and b.customer_id=?
		GROUP BY b.id HAVING solde>0 
		ORDER BY solde asc;';

		return DbCo::getQuery($sql,[$customerid]);
	}

	static function getAllNextCloudUser(){
		$sql = 'SELECT displayname, uid FROM nextcloud.oc_users
				WHERE not isnull(displayname);';

		return DbCo::getQuery($sql,[]);
	}

	static function getFoundIdIntervention($customer_id,$booklet_id,$point){
		$sql = 'SELECT max(id) as id FROM Ticketing.Intervention
				WHERE `customer_id`=? and `booklet_id`=? and `point`=?';

		return DbCo::getQuery($sql,[$customer_id,$booklet_id,$point]);
	}
}
