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
	public static $dbname = null;

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

	static function getDbName()
	{
		if (self::$dbname === null) {
			self::getPDO();
		}
		return self::$dbname;
	}

	static function selectDB($db = 'default')
	{
		if ($db === 'default') {

			$file = $_SERVER['SCRIPT_FILENAME'];
			$file = str_replace('index.php', 'config/config.php', $file);

			if (!file_exists($file)) {
				exit;
			}

			include($file);

			$dbhost = $CONFIG['dbhost'];
			$dbuser = $CONFIG['dbuser'];
			$dbpassword = $CONFIG['dbpassword'];
			self::$dbname = $CONFIG['dbname'];

			unset($CONFIG);

			self::init($db, $dbhost, $dbuser, $dbpassword, self::$dbname);
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

				//$query = str_replace("_{$key}_", $value, $query);
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

class Query
{

	static function getCustomer($id)
	{
		$sql = 'SELECT name FROM TicketingCustomer WHERE id=?';
		return DbCo::getQuery($sql, [$id]);
	}

	static function getNbrBookletCustomer($id)
	{
		$sql = 'SELECT count(id) as count FROM TicketingBooklet WHERE customer_id=?';
		return DbCo::getQuery($sql, [$id]);
	}

	static function getCustomerByBooklet($id)
	{
		$sql = 'SELECT customer_id FROM TicketingBooklet WHERE id=?';
		return DbCo::getQuery($sql, [$id]);
	}
	static function getCustomerKeyByBooklet($id)
	{
		$sql = 'SELECT c.publickey FROM TicketingBooklet as b 
                JOIN TicketingCustomer as c on b.customer_id=c.id
                WHERE b.id=?';
		return DbCo::getQuery($sql, [$id]);
	}

	static function getBooklet($id, $publicKey = null)
	{
		$where = 'b.id=?';
		$args = [$id];
		if ($publicKey != null) {
			$where = 'b.publickey=?';
			$args = [$publicKey];
		}

		$sql = "SELECT c.name,b.name as bookletName, b.initialpoint, date(b.createmoment) as createDate,
		sum(if(i.gift=0,if(i.override=1,i.overridepoint,i.point),null)) as sumpointuse, 
		sum(if(i.gift!=0,i.point,null)) as sumpointgift, 
		b.initialpoint-sum(if(i.gift=0,if(i.override=1,i.overridepoint,i.point),null)) as sumpointremaining,
		b.publicKey as reference, b.id as id
		FROM TicketingCustomer as c 
		LEFT join TicketingBooklet as b on c.id=b.customer_id
		left join TicketingIntervention as i on b.id=i.booklet_id
		WHERE $where and i.isdelete=0
		
		";

		return DbCo::getQuery($sql, $args);
	}

	static function setOverrideIntervention($id, $overridepoint)
	{
		$sql = 'UPDATE TicketingIntervention
				SET override=1, overridepoint=?
				WHERE id=?;
				COMMIT;';

		return DbCo::getQuery($sql, [$overridepoint, $id]);
	}

	static function setNewCarnet($customerid, $initialPoint, $key, $name)
	{
		$sql = 'INSERT INTO TicketingBooklet (publickey,customer_id,initialpoint,name) VALUES (?,?,?,?);
				COMMIT;';

		return DbCo::getQuery($sql, [$key, $customerid, $initialPoint, $name]);
	}

	static function getMaxBookletForCustomer($customerid)
	{
		$sql = 'SELECT max(id) as max FROM TicketingBooklet WHERE customer_id=?';

		return DbCo::getQuery($sql, [$customerid]);
	}

	static function getInterventionsByBooklet($bookletId)
	{
		$sql = "SELECT gift, date_format(start,'%d/%m/%Y %H:%i') start, date_format(end,'%d/%m/%Y %H:%i') end, if(u.displayname='' or u.displayname=null,i.user,u.displayname) displayuser, remark, if(override=1,overridepoint,point) finalpoint FROM TicketingIntervention as i
				LEFT JOIN oc_users as u on i.user=u.uid
				WHERE booklet_id=? and isdelete=0
				ORDER BY start desc, end desc
		";

		return DbCo::getQuery($sql, [$bookletId]);
	}

	static function setNewInterventionOverride($interventionOrigine, $bookletid, $overridePoint, $key)
	{
		$sql = 'INSERT INTO TicketingIntervention (`publickey`,`customer_id`, `booklet_id`, `gift`, `start`, `end`, `user`, `remark`, `point`, `override`, `overridePoint`,`OriginalIntervention`)
				SELECT ' . "'" . $key . "'" . ',`customer_id`, ' . $bookletid . ', `gift`, `start`, `end`, `user`, `remark`, `point`, true, ' . $overridePoint . ',' . $interventionOrigine . ' FROM TicketingIntervention where id=?;
				COMMIT;';
		//echo nl2br($sql).'<br>';
		return DbCo::getQuery($sql, [$interventionOrigine]);
	}

	static function getListBooklet($customerid)
	{
		$sql = 'SELECT b.id, b.initialpoint-sum(if(i.gift!=1,if(i.override=0,i.point,i.overridepoint),0)) as solde FROM TicketingBooklet as b 
		LEFT JOIN TicketingIntervention as i on b.id=i.booklet_id 
		WHERE b.archive=0 and b.customer_id=?
		GROUP BY b.id HAVING solde>0 
		ORDER BY solde asc;';

		return DbCo::getQuery($sql, [$customerid]);
	}

	static function getAllNextCloudUser()
	{
		$sql = 'SELECT u.displayname, u.uid FROM oc_users as u
				JOIN oc_group_user as g on u.uid=g.uid
				WHERE not isnull(displayname) and g.gid=?;';

		return DbCo::getQuery($sql, ['Ticketing']);
	}

	static function getFoundIdIntervention($customer_id, $booklet_id, $point)
	{
		$sql = 'SELECT max(id) as id FROM TicketingIntervention
				WHERE `customer_id`=? and `booklet_id`=? and `point`=?';

		return DbCo::getQuery($sql, [$customer_id, $booklet_id, $point]);
	}

	static function getlastCustomer($name)
	{
		$sql = 'SELECT max(id) as id FROM TicketingCustomer
				WHERE `name`=?';

		return DbCo::getQuery($sql, [$name]);
	}

	static function getFromShaToID($publicKey, $table)
	{
		$sql = 'SELECT max(id) as id FROM `' . $table . '`
				WHERE publickey=?';

		return DbCo::getQuery($sql, [$publicKey]);
	}

	static function setPublicKey($id, $publicKey)
	{
		$sql = 'UPDATE TicketingCustomer
				SET publickey=?
				WHERE id=?';

		return DbCo::getQuery($sql, [$publicKey, $id]);
	}

	static function getEditUser($user)
	{
		$sql = 'SELECT uid FROM oc_group_user
				WHERE gid=? and uid=?';

		return DbCo::getQuery($sql, ['Ticketing', $user]);
	}

	static function createTable()
	{
		$sql = '
		CREATE TABLE IF NOT EXISTS `TicketingCustomer` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
			`publickey` varchar(45) NOT NULL,
			`name` varchar(128) NOT NULL,
			`isactif` tinyint(1) NOT NULL DEFAULT 1,
			PRIMARY KEY (`id`),
			UNIQUE KEY `publickey` (`publickey`)
		  ) ;

		CREATE TABLE IF NOT EXISTS  `TicketingBooklet` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
            `publickey` varchar(45) NOT NULL,
			`customer_id` int(11) NOT NULL,
			`name` varchar(128) NOT NULL,
			`createmoment` timestamp NOT NULL DEFAULT current_timestamp(),
			`initialpoint` int(11) NOT NULL,
			`archive` tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY (`id`),
			KEY `customer_id` (`customer_id`),
            UNIQUE KEY `publickey` (`publickey`)
			)  ;

		CREATE TABLE IF NOT EXISTS `TicketingIntervention` (
			`id` int(11) NOT NULL AUTO_INCREMENT,
            `publickey` varchar(45) NOT NULL,
			`customer_id` int(11) NOT NULL,
			`booklet_id` int(11) NOT NULL,
			`gift` tinyint(1) NOT NULL DEFAULT 0,
			`start` datetime NOT NULL,
			`end` datetime NOT NULL,
			`user` varchar(45) NOT NULL,
			`remark` text NOT NULL,
			`point` int(11) NOT NULL DEFAULT 0,
			`override` tinyint(1) NOT NULL DEFAULT 0,
			`overridePoint` int(11) NOT NULL DEFAULT 0,
			`OriginalIntervention` int(11) NOT NULL,
			`isdelete` tinyint(1) NOT NULL DEFAULT 0,
			PRIMARY KEY (`id`),
			KEY `customer_id` (`customer_id`,`booklet_id`),
			KEY `customer_id_2` (`customer_id`),
			KEY `carnet_id` (`booklet_id`),
			KEY `user` (`user`),
			KEY `isdelete` (`isdelete`),
            UNIQUE KEY `publickey` (`publickey`)
		  ) ;
		';


		return DbCo::getQuery($sql, []);
	}

	static function getSha($table, $sha)
	{
		$sql = 'SELECT count(*) as count FROM `' . $table . '`
				WHERE publickey=?';

		$result = DbCo::getQuery($sql, [$sha]);

		return $result[0]['count'];
	}

	static function getGroup()
	{
		$sql = 'SELECT * FROM `oc_groups`';

		return DbCo::getQuery($sql, []);
	}

	static function setGroup($label)
	{
		$sql = 'INSERT IGNORE INTO `oc_groups`(`gid`, `displayname`) VALUES (?,?);';

		return DbCo::getQuery($sql, [$label, $label]);
	}
	static function addUserInGroup($labelGroup, $labelAdmin)
	{
		$sql = "INSERT IGNORE INTO `oc_group_user`(`gid`, `uid`) 
                SELECT '$labelGroup', `uid` from `oc_group_user`
                WHERE `gid`=?;
        ";

		return DbCo::getQuery($sql, [$labelAdmin]);
	}
	static function getBookletByPublicKey($publickey)
	{
		$sql = "SELECT name, initialPoint, date(createmoment) as createDate  FROM TicketingBooklet
		WHERE publickey=?;
        ";

		return DbCo::getQuery($sql, [$publickey]);
	}

	static function getPublicKeyByBooklet($id)
	{
		$sql = "SELECT publicKey  FROM TicketingBooklet
		WHERE id=?;
        ";

		return DbCo::getQuery($sql, [$id]);
	}
}
