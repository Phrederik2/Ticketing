<?php
class customer_Frame extends Cadre_Base{
	
	function __construct($key=null, $useCache = false)
    {
        parent::__construct($key, $useCache, false);
    }
	
    function init() 
    {
		$this->split();	
		
		//Selection du formulaire à afficher

		// si le GET contient 'Booklet' alors vue Intervention
		if(Tool::getOption('carnet')){
			$GLOBALS['Title']='Intervention';
			$this->onglet = new Onglet('Ticketing::Intervention'); 
			$this->onglet->add($this->intervention());

			// si le GET contient 'Customer' alors vue Booklet
		}else if(Tool::getOption('client')){
			$GLOBALS['Title']='Carnet';
			$this->onglet = new Onglet('Ticketing::Carnet'); 
			$this->onglet->add($this->booklet());

			// sinon, par defaut vue Customer
		}else{
			$GLOBALS['Title']='Client';
			$this->onglet = new Onglet('Ticketing::Client'); 
			$this->onglet->add($this->customer());
		}
		$this->onglet->add(new Tab('test'));
		
		
	}

	function split(){

		$uniquekey='850d4394eebc3760c94a207db6683e2d6d0afacc';

		if(Tool::getOption('split') and Tool::getTransaction(Tool::getOption('split'))!==null){
			$_SESSION['SplitIntervention'.$uniquekey]=Tool::getOption('split');
			header('Location:'.Tool::url(['Split'=>null]));
		}
			
		if(isset($_SESSION['SplitIntervention'.$uniquekey])){

			$arg = Tool::getTransaction($_SESSION['SplitIntervention'.$uniquekey]);
			Tool::cleanTransaction($_SESSION['SplitIntervention'.$uniquekey]);
			unset($_SESSION['SplitIntervention'.$uniquekey]);
			
			$id = $arg['id'];
			$sum = $arg['sum'];
			$remaining = $arg['remaining'];
			$customerid = $arg['customer_id'];
			$bookletid = $arg['booklet_id'];
			$point = $arg['point'];
			$rest = $sum;

			if($id==''){
				$result = Query::getFoundIdIntervention($customerid,$bookletid,$point);
				if(isset($result[0]['id'])){
					$id=$result[0]['id'];
				}
			}

			Query::setOverrideIntervention($id,$remaining);
			$rest-=$remaining;

			$message = '<strong>ID Intervention:'.$id.'</strong><br>';
			$message .= '<strong>Changement manuel de l\'intervention</strong><br>';
			$message .= 'changement manuel de '.$sum.' vers '.$remaining.'<br>';
			$message .= 'Carnet complet!';
			$message .= '<br>Il reste '.$rest.' points à distribuer';
			self::addNotice('',$message,'ERROR');

			$data = Query::getListBooklet($customerid);

			foreach($data as $line){
				$bookletid = $line['id'];
				$solde = $line['solde'];
				if($rest>0){
					if($rest>$solde){
						Query::setNewInterventionOverride($id,$bookletid,$solde);
						
						$message = '<strong>ID Carnet:'.$bookletid.'</strong><br>';
						$message .= '<strong>Ajout d\'une intervention sur le carnet</strong><br>';
						$message .= 'Ajout  d\'une intervention de '.$solde.' point<br>';
						$message .= 'Carnet complet!';
						$message .= '<br>Il reste '.($rest-$solde).' points à distribuer';
						self::addNotice('',$message,'ERROR');

						$rest=$rest-$solde;
					}else{
						Query::setNewInterventionOverride($id,$bookletid,$rest);
						

						$message = '<strong>ID Carnet:'.$bookletid.'</strong><br>';
						$message .= '<strong>Ajout d\'une intervention sur le carnet</strong><br>';
						$message .= 'Ajout  d\'une intervention de '.$rest.' point<br>';
						$message .= 'Il reste '. ($solde-$rest) .' points dans le carnet!';
						$message .= '<br>Il reste '.($rest-$rest).' points à distribuer';
						self::addNotice('',$message,'ERROR');

						$rest=$rest-$rest;
					}
				}
			}
			
			if($rest>0){
				$iPoint=40;
				if($rest>$iPoint){
					Query::setNewCarnet($customerid,$rest);
					$TPoint = $rest;

					$message = '<strong>Création d\'un nouveau carnet.</strong><br>';
					$message .= 'Création d\'un carnet avec '.$rest .' points';
					self::addNotice('',$message,'ERROR');
				}else{
					Query::setNewCarnet($customerid,$iPoint);
					$TPoint = $iPoint;

					$message = '<strong>Création d\'un nouveau carnet.</strong><br>';
					$message .= 'Création d\'un carnet avec '.$iPoint .' points';
					self::addNotice('',$message,'ERROR');
				}
				

				$maxBooklet = Query::getMaxBookletForCustomer($customerid);
	
				$max = $maxBooklet[0]['max'];
	
				Query::setNewInterventionOverride($id,$max,$rest,$id);

				$message = '<strong>ID Carnet:'.$max.'</strong><br>';
				$message .= '<strong>Ajout d\'une intervention sur le carnet.</strong><br>';
				$message .= 'Ajout  d\'une intervention de '.$rest.' point<br>';
				$message .= 'Il reste '. ($TPoint-$rest) .' points dans le carnet!';
				self::addNotice('',$message,'ERROR');

				
			}
			


		}
	}

	function customer(){
		$tab = new Tab('Client');

		// Création du formulaire pour selection des fiches active ou inactive
		$ff = new Form('SeeArchiveCustomer');
		$ff->addItem(new Switcher('Voir Client inactif'));
		
		DataViewer2::setEvent($ff->getItem('Voir Client inactif'),null,'change');
		

		// récupere la valeur du switcher pour l'intégrer dans la requete
		$active=$ff->getItem('Voir Client inactif')->getValue();

		// création de l'objet SQL
		$sql = new SQL('Ticketing','Customer','c');
		$sql->setPrimaryField('c','id');
		$sql->addField('c','id','ID');
		$sql->addField('c','name','Nom du client');
		$sql->addFunction('b.initialpoint','Point Initial');
		$sql->addFunction('i.point','Point utilisé');
		$sql->addFunction('b.initialpoint-i.point','Point Restant');
		$sql->addSubQueryJoin('SELECT customer_id, sum(initialpoint) as initialpoint FROM Ticketing.Booklet where archive=0 group by customer_id','b','c.id=b.customer_id','LEFT');
		$sql->addSubQueryJoin('SELECT i.customer_id, sum(if(i.override=0,i.point,i.overridepoint)) as point FROM Ticketing.Booklet as b
								join Ticketing.Intervention as i on b.id=i.booklet_id and i.gift=0
								where archive=0 group by i.customer_id','i','c.id=i.customer_id','LEFT');
		$sql->addWhere('c.isactif=?',[!$active]);
		$sql->addGroupBy('c.id');

		// créaction du dataviewer sur base de l'object SQL
		$view = new DataViewer2('Customer',$sql);

		// si le sous-formulaire est appeller, alors vider la commande du formulaire principale pour supprimer toutes les actions en cours
		if($ff->getPostExist()==true){
			$view->setCommandeOption('');
		}
		// lancement de l'initialization complete du dataviewer pour manipulation du résultat
		$view->fullInit();

		// si l'edition/création est lancée, manipulation du formulaire
		if($view->getForm()!=null){
			$form = $view->getForm();
			//modification des label (title)
			$form->getElement('name')->setTitle('Nom du client');

			$fieldset = new FieldSet('Edition');
			$fieldset->add_HTML_Class('Box');
			$fieldset->add($form);
			$fieldset->add(new Item('<br>'));
			$view->setItemDisplay($fieldset);
		}

		// extraction du dataset
		$dataset = $view->getDataset();

		// integration des lien 
		foreach($dataset as $nb=>&$line){
			if($nb>0){
				$line['Nom du client'] = Tool::link($line['Nom du client'],Tool::url(['client'=>$line['ID'],'carnet'=>null]),true); 
			}
			// suppresion de l'ID
			unset($line['ID']);
		}
		unset($line);

		
		// renvoi le dataset modifier dans le dataviewer
		$view->setDataset($dataset);

		////////////////////////////////////////////////////////////////
		/////////////////Display///////////////////////
		////////////////////////////////////////////////////////////////

			
		// ajoute le formulaire dans la tab

		$fieldset = new FieldSet('Filtre');
		$fieldset->add_HTML_Class('Box');
		$fieldset->add($ff);
		$fieldset->add(new Item('<br>'));
		$tab->addItem($fieldset);

		

		$tab->addItem($view);

		return $tab;
	}

	function booklet(){
		$tab = new Tab('Carnet');
		$id = Tool::getOption('client');
		if(Tool::getOption('carnet')==null){
			$tab->setActive(true);
		}
		

		$ff = new Form('SeeArchiveBooklet');
		$ff->addItem(new Switcher('Voir Carnet Archivé'));
		
		DataViewer2::setEvent($ff->getItem('Voir Carnet Archivé'),null,'change');
		
		$archive=$ff->getItem('Voir Carnet Archivé')->getValue();
		
		$sql = new SQL('Ticketing','Booklet','c');
		$sql->setPrimaryField('c','id');
		$sql->addField('c','id','ID');
		$sql->addField('c','createmoment','Creation');
		$sql->addField('c','initialpoint','Nombre de point initale');
		$sql->addFunction('sum(if(i.gift!=1,if(i.override=0,i.point,i.overridepoint),0))','Somme point utilisé');
		$sql->addFunction('c.initialpoint-sum(if(i.gift!=1,if(i.override=0,i.point,i.overridepoint),0))','Solde');
		$sql->addFunction('sum(if(i.gift=1,if(i.override=0,i.point,i.overridepoint),0))','Cadeaux');
		$sql->addJoin('Ticketing','Intervention','i','c.id=i.booklet_id','LEFT');
		$sql->addWhere('c.archive=?',[$archive]);
		$sql->addWhere('c.customer_id=?',[$id]);
		$sql->addGroupBy('c.id');

		$view = new DataViewer2('Booklet',$sql);
		//$view->setDebug(true);
		
		$view->partialInit();

		if($view->getForm()!=null){
			$form = $view->getForm();
			$form->getItem('customer_id')->forceValue($id);
			$form->getItem('customer_id')->setDisplay(false);
			$form->getItem('createmoment')->setDisplay(false);
			
			
		}
		$view->FullInit();
		if($view->getForm()!=null){
			$form = $view->getForm();
		
			$form->getElement('initialpoint')->setTitle('Nombre de point initiale');

			$fieldset = new FieldSet('Edition');
			$fieldset->add_HTML_Class('Box');
			$fieldset->add($form);
			$fieldset->add(new Item('<br>'));
			$view->setItemDisplay($fieldset);
		}

		$dataset = $view->getDataset();

		foreach($dataset as $nb=>&$line){
			if($nb>0){
				$line['ID'] = Tool::link($line['ID'],Tool::url(['carnet'=>$line['ID']]),true); 
			}
		}
		unset($line);

		$view->setDataset($dataset);


		$data = Query::getCustomer($id);
		
		$fieldset = new FieldSet('Détail du client selectionné');
		$fieldset->add_HTML_Class('Box');
		if(isset($data[0]['name'])){
			$fieldset->add(new Field('Nom du client',$data[0]['name']));
		}
		$fieldset->add($ff);
		$fieldset->add(new Item('<br>'));
		
		$tab->addItem($fieldset);
		$tab->addItem($view);
	
		return $tab;
	}

	function intervention(){
		$tab = new Tab('Intervention');
		$customer = Tool::getOption('client');
		$booklet = Tool::getOption('carnet');
		$tab->setActive(true);

		$sql = new SQL('Ticketing','Intervention','i');
		$sql->setPrimaryField('i','id');
		$sql->addField('i','id','ID');
		$sql->addField('u','displayname','Créateur');
		$sql->addField('i','start','Début');
		$sql->addField('i','End','Fin');
		$sql->addField('i','gift','Cadeaux');
		$sql->addFunction('if(override=0,point,overridepoint)','Point');
		$sql->addJoin('nextcloud','oc_users','u','i.user=u.uid','LEFT');
		
		$sql->addWhere('i.customer_id=?',[$customer]);
		$sql->addWhere('i.booklet_id=?',[$booklet]);
		$sql->addGroupBy('i.id');
		
		$view = new DataViewer2('Intervention',$sql);
		//$view->setDebug(true);
		
		$view->partialInit();

		if($view->getForm()!=null){
			$form = $view->getForm();
			$form->getItem('customer_id')->forceValue($customer);
			$form->getItem('customer_id')->setDisplay(false);
			$form->getItem('OriginalIntervention')->forceValue(0);
			$form->getItem('OriginalIntervention')->setDisplay(false);

			$form->getItem('booklet_id')->forceValue($booklet);
			$form->getItem('booklet_id')->setDisplay(false);
			$form->getItem('point')->setDisplay(false);
			
			$date = new DateTime('now');
			
			
			$form->getItem('end')->setDefaultValue($date->format('Y-m-d H:i:00'));

			$date->sub(DateInterval::createFromDateString('2 hours'));

			$form->getItem('start')->setDefaultValue($date->format('Y-m-d H:i:00'));
			
			


			
			$form->setItem('user',new SelectList('User','user',Query::getAllNextCloudUser(),'displayname','uid'));


			$this->setPoint($form);
		
		}
		
		$view->fullInit();
		if($view->getForm()!=null){
			$form = $view->getForm();
		
			$form->getElement('start')->setTitle('Début');
			$form->getItem('start')->cleanClass();
			$form->getElement('end')->setTitle('Fin');
			$form->getItem('end')->cleanClass();

			$fieldset = new FieldSet('Edition');
			$fieldset->add_HTML_Class('Box');
			$fieldset->add($form);
			$fieldset->add(new Item('<br>'));
			$view->setItemDisplay($fieldset);
		}

		$data = Query::getBooklet($booklet);
		$fieldset = new FieldSet('Détail du carnet selectionné');
		if(isset($data[0]['name'])){
			$fieldset->add(new Field('Nom du client',$data[0]['name']));
			$fieldset->add(new Field('Nombre de point initale',$data[0]['initialpoint']));
			$fieldset->add(new Field('Somme des points utilisés',$data[0]['sumpointuse']));
			$fieldset->add(new Field('Somme des points cadeaux',$data[0]['sumpointgift']));
			$fieldset->add(new Field('Somme des points restant',$data[0]['sumpointremaining']));
			$fieldset->add(new Item('<br>'));
		}

		if($view->getForm()!=null and ($view->getForm()->getUpdate()===true or $view->getForm()->getInsert()===true ) and $data[0]['sumpointremaining']<0){

			$form = $view->getForm();
			
			$this->splitProposal($form,$data[0]['sumpointremaining']);
		}
		
		$tab->addItem($fieldset);
		$tab->addItem($view);

		
	
		return $tab;
	}

	function splitProposal($form,$remaining){
		$id = $form->getItem('id')->getValue();
		$point = $form->getItem('point')->getValue();
		$override = $form->getItem('override')->getValue();
		$overridePoint = $form->getItem('overridePoint')->getValue();
		$customerid = $form->getItem('customer_id')->getValue();
		$bookletid = $form->getItem('booklet_id')->getValue();
		
		$pointf=0;

		if((bool)$override===true){
			$pointf=$overridePoint;
		}else{
			$pointf=$point; 
		}

		$sha = Tool::setTransaction(['id'=>$id,'sum'=>$pointf,'remaining'=>($remaining+$pointf),'customer_id'=>$customerid,'booklet_id'=>$bookletid,'point'=>$point]);

		$link = Tool::link('cliquez ici',Tool::url(['Split'=>$sha]));
		
		$message = '<p>L\'intervention introduite est de <strong>'.$pointf.'</strong> points cependant le nombre maximum de point restant dans le carnet est de <strong>'.($remaining+$pointf).'</strong> </p>';
		$message .= '<p>Pour ouvrir un nouveaux carnet pour ce client et repartir les points de la derniere intervention '.$link .'.</p>';
		self::addNotice('Ouvrir un nouveaux carnet ?',$message,'ERROR');
	}

	function setPoint($form){
		//if($form->getUpdate()==true or $form->getInsert()==true){
			$s = $form->getItem('start')->getValue();
			$e = $form->getItem('end')->getValue();
			$s = strtotime($s);
			$e = strtotime($e);
		
			if($s!==false and $e!==false){
				$r = $e-$s;
				$m = $r/60;
				$p = ceil($m/15);
				$form->getItem('point')->forceValue($p);
			}
		//}
	}
	

	

}