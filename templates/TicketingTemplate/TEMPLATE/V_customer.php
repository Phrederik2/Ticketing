<?php
class customer_Frame extends Cadre_Base
{

    private $user = '';
    private $canEdit = false;
    public $cleanAllSessionAndReturn = false;

    function __construct($key = null, $useCache = false)
    {
        parent::__construct($key, $useCache, false);
        $this->changeSchema();
    }

    function initUser()
    {
        if (isset($_SESSION['u']) and $_SESSION['u'] != '') {
            $this->user = $_SESSION['u'];

            $d = Query::getEditUser($this->user);
            if (isset($d[0]['uid']) and $d[0]['uid'] == $this->user) {
                $this->canEdit = true;
            }
        } else {
            $this->user = 'public';
            $this->canEdit = false;
        }

        if (stristr(Tool::baselink(), 'PublicView')) {
            $this->user = 'public';
            $this->canEdit = false;
        }
    }

    function getId($arg, $table)
    {

        if (Tool::getOption($arg) != null and is_numeric(Tool::getOption($arg))) {
            $id = Tool::getOption($arg);
        } else if (Tool::getOption($arg) != null) {
            $data = Query::getFromShaToID(Tool::getOption($arg), 'Ticketing' . ucwords($table));
            $id = $data[0]['id'];
        }

        return $id;
    }

    function init()
    {

        $this->initUser();

        $this->split();

        //Selection du formulaire à afficher

        // si le GET contient 'Booklet' alors vue Intervention

        if (Tool::getOption('carnet')) {
            $GLOBALS['Title'] = 'Intervention';
            $this->onglet = new Onglet('Ticketing::Intervention');
            $this->onglet->add($this->intervention($this->getId('carnet', 'booklet')));

            // si le GET contient 'Customer' alors vue Booklet
        } else if (Tool::getOption('client')) {
            $GLOBALS['Title'] = 'Carnet';
            $this->onglet = new Onglet('Ticketing::Carnet');

            $this->onglet->add($this->booklet($this->getId('client', 'customer')));

            // sinon, par defaut vue Customer
        } else if ($this->canEdit == true) {
            $GLOBALS['Title'] = 'Client';
            $this->onglet = new Onglet('Ticketing::Client');
            $this->onglet->add($this->customer());
        }
    }

    function navigationBand()
    {

        $tmp = array();

        if (Tool::getOption('client') == null and Tool::getOption('carnet') != null) {
            $id = Query::getCustomerKeyByBooklet($this->getId('carnet', 'booklet'));

            $_POST['client'] = $id[0]['publickey'];
        }

        if ($this->canEdit) $tmp[] = Tool::buttonLink('Client', Tool::url([], false));

        if (Tool::getOption('client')) $tmp[] = Tool::buttonLink('Carnet', Tool::url(['client' => Tool::getOption('client')], false));
        if (Tool::getOption('carnet')) $tmp[] = Tool::buttonLink('Intervention', Tool::url(['client' => Tool::getOption('client'), 'carnet' => Tool::getOption('carnet')], false));

        $str = '<input type="button" href="https://fr.w3docs.com/" value="Cliquez sur moi" >';
        $str = '';
        //$str.='['.$this->user.']::['.(bool)$this->canEdit.']::';
        foreach ($tmp as $nav) {
            if ($str != '') {
                $str .= ' ';
            }
            $str .= $nav;
        }

        $f = new FieldSet('Navigation');
        $f->add(new Item($str));

        return $f;
    }

    function split()
    {

        $key = '850d4394eebc3760c94a207db6683e2d6d0afacc';
        $uniquekey = 'SplitIntervention' . $key;

        if (Tool::getOption('split') and Tool::getTransaction(Tool::getOption('split')) !== null) {
            $_SESSION[$uniquekey] = Tool::getOption('split');
            ob_end_clean();
            header('Location: ' . Tool::url(['Split' => null]));
            exit;
        }

        if (isset($_SESSION[$uniquekey])) {

            $arg = Tool::getTransaction($_SESSION[$uniquekey]);
            Tool::cleanTransaction($_SESSION[$uniquekey]);
            unset($_SESSION[$uniquekey]);

            $id = $arg['id'];
            $sum = $arg['sum'];
            $remaining = $arg['remaining'];
            $customerid = $arg['customer_id'];
            $bookletid = $arg['booklet_id'];
            $point = $arg['point'];
            $rest = $sum;

            if ($id == '') {
                $result = Query::getFoundIdIntervention($customerid, $bookletid, $point);
                if (isset($result[0]['id'])) {
                    $id = $result[0]['id'];
                }
            }

            Query::setOverrideIntervention($id, $remaining);
            $rest -= $remaining;

            $title = 'ID Intervention:' . $id . '::';
            $title .= 'Changement manuel de l\'intervention';

            $message = 'changement manuel de ' . $sum . ' vers ' . $remaining . '<br>';
            $message .= 'Carnet complet!';
            $message .= '<br>Il reste ' . $rest . ' points à distribuer';

            $this->addNotification(null, $message, $title, 'Notif');

            $data = Query::getListBooklet($customerid);

            foreach ($data as $line) {
                $bookletid = $line['id'];
                $solde = $line['solde'];
                if ($rest > 0) {
                    if ($rest > $solde) {
                        Query::setNewInterventionOverride($id, $bookletid, $solde, $this->getShaIntervention());

                        $title = 'ID Carnet:' . $bookletid . '::';
                        $title .= 'Ajout d\'une intervention sur le carnet';

                        $message = 'Ajout  d\'une intervention de ' . $solde . ' point<br>';
                        $message .= 'Carnet complet!';
                        $message .= '<br>Il reste ' . ($rest - $solde) . ' points à distribuer';

                        $this->addNotification(null, $message, $title, 'Notif');

                        $rest = $rest - $solde;
                    } else {
                        Query::setNewInterventionOverride($id, $bookletid, $rest, $this->getShaIntervention());


                        $title = 'ID Carnet:' . $bookletid . '::';
                        $title .= 'Ajout d\'une intervention sur le carnet';
                        $message = 'Ajout  d\'une intervention de ' . $rest . ' point<br>';
                        $message .= 'Il reste ' . ($solde - $rest) . ' points dans le carnet!';
                        $message .= '<br>Il reste ' . ($rest - $rest) . ' points à distribuer';

                        $this->addNotification(null, $message, $title, 'Notif');
                        $rest = $rest - $rest;
                    }
                }
            }

            if ($rest > 0) {
                $iPoint = 40;
                $data = Query::getCustomer($customerid);
                $nbr = Query::getNbrBookletCustomer($customerid);
                $name = $data[0]['name'] . '_' . $nbr[0]['count'] + 1;

                if ($rest > $iPoint) {
                    Query::setNewCarnet($customerid, $rest, $this->getShaBooklet(), $name);
                    $TPoint = $rest;

                    $title = 'Création d\'un nouveau carnet.';
                    $message = 'Création d\'un carnet avec ' . $rest . ' points';

                    $this->addNotification(null, $message, $title, 'Notif');
                } else {
                    Query::setNewCarnet($customerid, $iPoint, $this->getShaBooklet(), $name);
                    $TPoint = $iPoint;

                    $title = 'Création d\'un nouveau carnet.';
                    $message = 'Création d\'un carnet avec ' . $iPoint . ' points';

                    $this->addNotification(null, $message, $title, 'Notif');
                }


                $maxBooklet = Query::getMaxBookletForCustomer($customerid);

                $max = $maxBooklet[0]['max'];

                Query::setNewInterventionOverride($id, $max, $rest, $id, $this->getShaIntervention());

                $title = 'ID Carnet:' . $max . '::';
                $title .= 'Ajout d\'une intervention sur le carnet.';
                $message = 'Ajout  d\'une intervention de ' . $rest . ' point<br>';
                $message .= 'Il reste ' . ($TPoint - $rest) . ' points dans le carnet!';

                $this->addNotification(null, $message, $title, 'Notif');
            }
            $this->cleanAllSessionAndReturn = true;
        }
    }

    function sqlCustomer($active = 0)
    {
        $sql = new SQL(DbCo::getDbName(), 'TicketingCustomer', 'c');
        $sql->setPrimaryField('c', 'id');
        $sql->addField('c', 'id', 'ID');
        $sql->addField('c', 'publickey', 'publickey');
        $sql->addField('c', 'name', 'Nom du client');
        $sql->addFunction('b.initialpoint', 'Point Initial');
        $sql->addFunction('i.point', 'Point utilisé');
        $sql->addFunction('b.initialpoint-i.point', 'Point Restant');
        $sql->addSubQueryJoin('SELECT customer_id, sum(initialpoint) as initialpoint FROM TicketingBooklet where archive=0 group by customer_id', 'b', 'c.id=b.customer_id', 'LEFT');
        $sql->addSubQueryJoin('SELECT i.customer_id, sum(if(i.override=0,i.point,i.overridepoint)) as point FROM TicketingBooklet as b
								join TicketingIntervention as i on b.id=i.booklet_id and i.gift=0 and i.isdelete=0
								where archive=0 group by i.customer_id', 'i', 'c.id=i.customer_id', 'LEFT');
        $sql->addWhere('c.isactif=?', [!$active]);
        $sql->addGroupBy('c.id');

        return $sql;
    }

    function customer()
    {
        $tab = new Tab('Client');
        $tab->addItem($this->navigationBand());
        $this->getNotification($tab);
        // Création du formulaire pour selection des fiches active ou inactive
        $ff = new Form('SeeArchiveCustomer');
        $ff->addItem(new Switcher('Voir Client inactif'));

        DataViewer2::setEvent($ff->getItem('Voir Client inactif'), null, 'change');


        // récupere la valeur du switcher pour l'intégrer dans la requete
        $active = $ff->getItem('Voir Client inactif')->getValue();


        // créaction du dataviewer sur base de l'object SQL
        $view = new DataViewer2('Customer', $this->sqlCustomer($active));

        // si le sous-formulaire est appeller, alors vider la commande du formulaire principale pour supprimer toutes les actions en cours
        if ($ff->getPostExist() == true) {
            $view->setCommandeOption('');
        }

        $view->partialInit();

        if ($view->getForm() != null) {
            $form = $view->getForm();


            if ($form->getItem('publickey')->getValue() == '') {
                $key = $this->getShaCustomer();
                $form->getItem('publickey')->setDefaultValue($key);
            }
        }

        // lancement de l'initialization complete du dataviewer pour manipulation du résultat
        $view->fullInit();

        // si l'edition/création est lancée, manipulation du formulaire
        if ($view->getForm() != null) {
            $form = $view->getForm();
            //modification des label (title)
            $form->getElement('name')->setTitle('Nom du client');
            $form->getItem('name')->setRequired(true);
            $form->getItem('publickey')->setEnable(false);

            $fieldset = new FieldSet('Edition');
            $fieldset->add_HTML_Class('Box');

            $this->addShare($fieldset, $form, 'Client');

            $fieldset->add($form);
            $fieldset->add(new Item('<br>'));
            $view->setItemDisplay($fieldset);
        }

        // extraction du dataset
        $dataset = $view->getDataset();

        // integration des lien 
        foreach ($dataset as $nb => &$line) {
            if ($nb > 0) {
                $line['Edit'] .= Tool::buttonLink('Ouvrir', Tool::url(['client' => $line['publickey'], 'carnet' => null]));
                if ($line['Point Restant'] == 0) {
                    $line['Point Restant'] = '<span class="bad">' . $line['Point Restant'] . '</span>';
                } else if ($line['Point Restant'] < 5) {
                    $line['Point Restant'] = '<span class="medium">' . $line['Point Restant'] . '</span>';
                }
            }


            // suppresion de l'ID
            unset($line['ID']);
            unset($line['publickey']);
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
        //$fieldset->add(new Item('<br>'));
        $tab->addItem($fieldset);



        $tab->addItem($view);

        return $tab;
    }

    function sqlBooklet($archive = 0, $id = 0)
    {
        $sql = new SQL(DbCo::getDbName(), 'TicketingBooklet', 'c');
        $sql->setPrimaryField('c', 'id');
        $sql->addField('c', 'id', 'ID');
        $sql->addField('c', 'name', 'Nom');
        $sql->addField('c', 'createmoment', 'Creation');
        $sql->addField('c', 'initialpoint', 'Nombre de point initale');
        $sql->addFunction('sum(if(i.gift!=1,if(i.override=0,i.point,i.overridepoint),0))', 'Somme point utilisé');
        $sql->addFunction('c.initialpoint-sum(if(i.gift!=1,if(i.override=0,i.point,i.overridepoint),0))', 'Solde');
        $sql->addFunction('sum(if(i.gift=1,if(i.override=0,i.point,i.overridepoint),0))', 'Cadeaux');
        $sql->addJoin(DbCo::getDbName(), 'TicketingIntervention', 'i', 'c.id=i.booklet_id', 'LEFT');
        $sql->addWhere('c.archive=?', [$archive]);
        $sql->addWhere('c.customer_id=?', [$id]);
        $sql->addWhere('(i.isdelete=0 or isnull(i.isdelete))');
        $sql->addGroupBy('c.id');

        return $sql;
    }

    function booklet($id)
    {
        $tab = new Tab('Carnet');
        $tab->addItem($this->navigationBand());
        $this->getNotification($tab);

        $ff = new Form('SeeArchiveBooklet');
        $ff->addItem(new Switcher('Voir Carnet Archivé'));

        DataViewer2::setEvent($ff->getItem('Voir Carnet Archivé'), null, 'change');

        $archive = $ff->getItem('Voir Carnet Archivé')->getValue();

        $view = new DataViewer2('Booklet', $this->sqlBooklet($archive, $id), $this->canEdit);
        //$view->setDebug(true);

        $view->partialInit();

        if ($view->getForm() != null) {
            $form = $view->getForm();

            if ($form->getItem('publickey')->getValue() == '') {
                $key = $this->getShaBooklet();
                $form->getItem('publickey')->setDefaultValue($key);
            }
        }

        if ($view->getForm() != null) {
            $form = $view->getForm();
            $form->getItem('customer_id')->forceValue($id);
            $form->getItem('customer_id')->setDisplay(false);
            $form->getItem('createmoment')->setDisplay(false);
        }
        $view->FullInit();

        if ($view->getForm() != null) {
            $form = $view->getForm();
            $form->getItem('publickey')->setEnable(false);
            $form->getElement('name')->setTitle('Nom');

            // si Name = vide, name = [nomduclient]_[nombre de carnet]+1
            $data = Query::getCustomer($id);
            $nbr = Query::getNbrBookletCustomer($id);
            $form->getItem('name')->setRequired(true);

            $form->getItem('name')->setDefaultValue($data[0]['name'] . '_' . $nbr[0]['count'] + 1);


            $form->getElement('initialpoint')->setTitle('Nombre de point initiale');
            $form->getItem('initialpoint')->setDefaultValue('40');

            $fieldset = new FieldSet('Edition');
            $fieldset->add_HTML_Class('Box');

            $this->addShare($fieldset, $form, 'Carnet');

            $fieldset->add($form);
            $fieldset->add(new Item('<br>'));
            $view->setItemDisplay($fieldset);
        }

        $dataset = $view->getDataset();

        foreach ($dataset as $nb => &$line) {
            if ($nb > 0) {

                $line['Edit'] .= Tool::buttonLink('Ouvrir', Tool::url(['carnet' => $line['ID']]));
                if ($line['Solde'] == 0) {
                    $line['Solde'] = '<span class="bad">' . $line['Solde'] . '</span>';
                } else if ($line['Solde'] < 5) {
                    $line['Solde'] = '<span class="medium">' . $line['Solde'] . '</span>';
                }
            }
            unset($line['ID']);
        }
        unset($line);

        $view->setDataset($dataset);





        $fieldset = new FieldSet('Détail du client selectionné');
        $fieldset->add_HTML_Class('Box');
        if (isset($data[0]['name'])) {
            $fieldset->add(new Field('Nom du client', $data[0]['name']));
        }

        if ($view->getForm() != null and $view->getForm()->getInsert() === true) {
            $this->addNotification(null, 'Votre carnet à ete crée!', 'Nouveau Carnet crée', 'Notif');
            $this->cleanAllSessionAndReturn = true;
        }


        $fieldset->add($ff);
        //$fieldset->add(new Item('<br>'));

        $tab->addItem($fieldset);
        $tab->addItem($view);

        return $tab;
    }

    function sqlIntervention($customer = 0, $booklet = 0)
    {
        $sql = new SQL(DbCo::getDbName(), 'TicketingIntervention', 'i');
        $sql->setPrimaryField('i', 'id');
        $sql->addField('i', 'id', 'ID');
        $sql->addField('u', 'displayname', 'Créateur');
        $sql->addField('i', 'remark', 'Remarque');
        $sql->addField('i', 'start', 'Début');
        $sql->addField('i', 'End', 'Fin');
        $sql->addField('i', 'gift', 'Cadeaux');
        $sql->addFunction('if(override=0,point,overridepoint)', 'Point');

        $sql->addJoin(DbCo::getDbName(), 'oc_users', 'u', 'i.user=u.uid', 'LEFT');

        $sql->addWhere('i.customer_id=?', [$customer]);
        $sql->addWhere('i.booklet_id=?', [$booklet]);
        $sql->addWhere('i.isdelete=0');
        $sql->addGroupBy('i.id');
        $sql->addOrderBy('i.start', 'DESC');
        $sql->addOrderBy('i.end', 'DESC');

        return $sql;
    }

    function intervention($booklet)
    {
        $tab = new Tab('Intervention');

        $customer = Query::getCustomerByBooklet($booklet)[0]['customer_id'];
        $tab->addItem($this->navigationBand());
        $this->getNotification($tab);

        $tab->setActive(true);



        $view = new DataViewer2('Intervention', $this->sqlIntervention($customer, $booklet), $this->canEdit);
        //$view->setDebug(true);

        $view->partialInit();

        if ($view->getForm() != null) {
            $form = $view->getForm();

            if ($form->getItem('publickey')->getValue() == '') {
                $key = $this->getShaIntervention();
                $form->getItem('publickey')->setDefaultValue($key);
            }
        }

        if ($view->getForm() != null) {
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


            $form->setItem('user', new SelectList('User', 'user', Query::getAllNextCloudUser(), 'displayname', 'uid'));
            $form->getItem('user')->setDefaultValue($_SESSION['u']);

            $this->setPoint($form);
        }

        $view->fullInit();

        $data = $view->getDataset();
        $data = $this->setSwitcherInDataset($data, ['Cadeaux']);
        $data = $this->setRemark($data, ['Remarque']);
        //$data = $this->setPublicShare($data,'Public Share');



        $view->setDataset($data);

        if ($view->getForm() != null) {
            $form = $view->getForm();

            $form->getItem('publickey')->setEnable(false);
            $form->getElement('start')->setTitle('Début');
            $form->getItem('start')->cleanClass();
            $form->getElement('end')->setTitle('Fin');
            $form->getItem('end')->cleanClass();

            $form->getElement('isdelete')->setTitle('Suppression');

            $fieldset = new FieldSet('Edition');
            $fieldset->add_HTML_Class('Box');
            $fieldset->add($form);
            $fieldset->add(new Item('<br>'));
            $view->setItemDisplay($fieldset);
        }

        $data = Query::getBooklet($booklet);
        if ($view->getForm() != null) {
            $form = $view->getForm();

            if (!$data[0]['sumpointremaining'] > 0) {
                $form->getItem('gift')->setEnable(false);
                $form->getItem('gift')->setDisplay(true);
                $form->getItem('gift')->setToolTip('Inactif', 'Impossible de rendre gratuit une intervention dans un carnet qui ne possede pas de point.');
            }


            $view->setItemDisplay($fieldset);
        }

        $fieldset = new FieldSet('Détail du carnet selectionné');
        if (isset($data[0]['name'])) {
            $fieldset->add(new Field('Nom du client', $data[0]['name']));
            $fieldset->add(new Field('Nom du carnet', $data[0]['bookletName']));
            $fieldset->add(new Field('Nombre de point initale', $data[0]['initialpoint']));
            $fieldset->add(new Field('Somme des points utilisés', $data[0]['sumpointuse']));
            $fieldset->add(new Field('Somme des points cadeaux', $data[0]['sumpointgift']));
            $fieldset->add(new Field('Somme des points restant', $data[0]['sumpointremaining']));

            $list = $fieldset->get();

            foreach ($list as $element) {
                $element->add_HTML_Class('bold');
            }
            //$fieldset->add(new Item('<br>'));
        }

        $split = false;

        if ($view->getForm() != null and ($view->getForm()->getUpdate() === true or $view->getForm()->getInsert() === true) and $data[0]['sumpointremaining'] < 0) {

            $form = $view->getForm();

            $this->splitProposal($form, $data[0]['sumpointremaining'], $tab);
            $split = true;
        }

        if ($split === false and $view->getForm() != null and $view->getForm()->getInsert() === true) {
            $this->cleanAllSessionAndReturn = true;
        }

        $tab->addItem($fieldset);
        $tab->addItem($view);

        return $tab;
    }


    function cleanAllSessionAndReturn()
    {
        $this->cleanSession('Customer', $this->sqlCustomer());
        $this->cleanSession('Booklet', $this->sqlBooklet());
        $this->cleanSession('Intervention', $this->sqlIntervention());

        $t = ob_end_flush();
        header('Location: ' . Tool::url([], false));

        exit();
    }

    function cleanSession($name, SQL $sql)
    {
        $view = new DataViewer2($name, $sql, $this->canEdit);
        $view->partialInit();
        $filter = $view->getFormFilter();
        if ($filter != null) {
            $f = $filter->getItem();
            foreach ($f as $i) {
                $i->cleanSession();
            }
        }
    }

    /**
     * transforme les valeurs des collones contenue dans [labels] en switcher
     * @param array $data is a dataset 
     * @param array $labels is a list of label from to conversion un switchers values 
     * @return array 
     */
    function setSwitcherInDataset($data, $labels)
    {
        foreach ($data as &$line) {
            foreach ($labels as $label) {
                if (isset($line[$label]) and is_numeric($line[$label])) {
                    $s = new Switcher('');
                    $s->setValue($line[$label]);
                    $s->setEnable(false);
                    $line[$label] = $s->toString();
                }
            }
        }
        unset($line);

        return $data;
    }

    function setRemark($data, $labels)
    {
        foreach ($data as &$line) {
            foreach ($labels as $label) {
                if (isset($line[$label]) and is_numeric($line['ID'])) {
                    $t = str_ireplace('</p>', chr(13), $line[$label]);

                    $t = strip_tags($t);

                    $t = substr($t, 0, 25);
                    $t = strlen($t) > 20 ? substr($t, 0, 20) . "..." : $t;

                    $s = new Tooltips(nl2br($t), '', $line[$label]);
                    $line[$label] = $s->toString();
                }
            }
        }
        unset($line);

        return $data;
    }

    function setPublicShare($data, $label)
    {
        foreach ($data as &$line) {
            if (isset($line[$label])) {
                $line[$label] = Tool::link('Share', Tool::baselink() . 'display/PublicView?client=123');
            }
        }
        unset($line);

        return $data;
    }


    function splitProposal($form, $remaining, $tab)
    {
        $id = $form->getItem('id')->getValue();
        $point = $form->getItem('point')->getValue();
        $override = $form->getItem('override')->getValue();
        $overridePoint = $form->getItem('overridePoint')->getValue();
        $customerid = $form->getItem('customer_id')->getValue();
        $bookletid = $form->getItem('booklet_id')->getValue();

        $pointf = 0;

        if ((bool)$override === true) {
            $pointf = $overridePoint;
        } else {
            $pointf = $point;
        }

        $sha = Tool::setTransaction(['id' => $id, 'sum' => $pointf, 'remaining' => ($remaining + $pointf), 'customer_id' => $customerid, 'booklet_id' => $bookletid, 'point' => $point]);



        $message = '<p>L\'intervention introduite est de <strong>' . $pointf . '</strong> points cependant le nombre maximum de point restant dans le carnet est de <strong>' . ($remaining + $pointf) . '</strong> </p>';
        $message .= '<p>Pour ouvrir un nouveaux carnet pour ce client et repartir les points de la derniere intervention cliquez ici.</p>';

        $link = Tool::link($message, Tool::url(['Split' => $sha]));

        $this->addNotification($tab, $link);
    }

    /**
     * ajoute les notifications dans l'object Tab, s'il n'existe pas, il est stocker en session 
     * et reste en attente de la fonction getNotification pour traiter les notifications en attente
     * @param Tab $tab 
     * @param string $message 
     * @return void 
     */
    function addNotification($tab = null, $message = '', $title = 'Split', $class = 'SplitNotif')
    {


        if ($tab != null) {
            $n = new FieldSet($title);
            $n->add(new item($message));
            $n->add_HTML_Class($class);
            $tab->addItem($n);
        } else {
            if (!isset($_SESSION['Notifications'])) {
                $_SESSION['Notifications'] = array();
            }

            $_SESSION['Notifications'][] = ['title' => $title, 'message' => $message, 'class' => $class];
        }
    }


    /**
     * Ajoute les notifications en attente dans l'objet TAB
     * @param Tab $tab 
     * @return void 
     */
    function getNotification($tab)
    {
        if ($this->cleanAllSessionAndReturn === false) {
            if (isset($_SESSION['Notifications'])) {
                foreach ($_SESSION['Notifications'] as $notif) {

                    $n = new FieldSet($notif['title']);
                    $n->add(new item($notif['message']));
                    $n->add_HTML_Class($notif['class']);
                    $tab->addItem($n);
                    unset($notif);
                }
                unset($_SESSION['Notifications']);
            }
        }
    }

    function setPoint($form)
    {
        //if($form->getUpdate()==true or $form->getInsert()==true){
        $s = $form->getItem('start')->getValue();
        $e = $form->getItem('end')->getValue();
        $s = strtotime($s);
        $e = strtotime($e);

        if ($s !== false and $e !== false) {
            $r = $e - $s;
            $m = $r / 60;
            $p = ceil($m / 15);
            $form->getItem('point')->forceValue($p);
        }
        //}
    }

    static function changeSchema()
    {
        $uid = 'Schemabb96f3bff8fa282c2b67d228de15783f632549e0';
        $labelGroup = 'Ticketing';
        $labelAdmin = 'admin';
        $labelGid = 'gid';

        if (!isset($_SESSION[$uid])) {
            $_SESSION[$uid] = true;
            Query::createTable();

            $group = Query::getGroup();

            $createGroup = true;
            foreach ($group as $line) {
                if ($line[$labelGid] === $labelGroup) {
                    $createGroup = false;
                }
            }

            if ($createGroup === true) {
                Query::setGroup($labelGroup);
                Query::addUserInGroup($labelGroup, $labelAdmin);
            }
        }
    }

    function getSha($table, $recurcount = 0)
    {
        $sha = sha1(time() . microtime(true) . $table . rand(0, 1000000));

        $result = Query::getSha($table, $sha);

        if (intval($result) === 0) {
            return $sha;
        } else {
            if ($recurcount >= 256) {
                //echo 'RECURSIF-' . $recurcount . ':' . $sha;
                exit;
            } else {

                return $this->getSha($table, $recurcount++);
            }
        }
    }

    function getShaCustomer()
    {
        return $this->getSha('TicketingCustomer');
    }

    function getShaBooklet()
    {
        return $this->getSha('TicketingBooklet');
    }

    function getShaIntervention()
    {
        return $this->getSha('TicketingIntervention');
    }

    function addShare(&$fieldset, &$form, $arg)
    {

        if ($form->getItem('publickey') != null) {

            $link = Tool::baselink() . 'display/PublicView?' . $arg . '=' . $form->getItem('publickey')->getValue();
            $share = new Field('Share', Tool::link($link, $link, true, 'URL Public'));

            $fieldset = new FieldSet('Edition');
            $fieldset->add_HTML_Class('Box');

            if ($form->getWhere() != '') {
                $fieldset->add($share);
            }
        }
    }
}
