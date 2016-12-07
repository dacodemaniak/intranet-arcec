<?php
/**
 * @name setDossier.class.php Définition du formulaire de gestion du porteur avec une pagination par fieldset
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Dossier
 * @version 1.0
 **/
namespace arcec;

class setDossier extends \wp\formManager\admin{
	
	/**
	 * Objet "pager" pour dérouler les fieldsets
	 * @var object
	**/
	private $pager;
	
	/**
	 * Identifiant du porteur si le mode est mise à jour ou suppression
	 * @var int
	 */
	private $id;
	
	/**
	 * Détermine si les champs sont désactivés globalement
	 * Valeur définie à partir du positionnement de dossier_ca
	 * @var boolean
	 */
	private $globalDisabled;
	
	/**
	 * En-tête du dossier
	 * @var object
	 */
	private $dossier;
	
	/**
	 * Instancie un nouvel objet de gestion de porteur
	 **/
	public function __construct($isSubForm=false,$tabId=null){
		$this->module = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
	
		$this->setId($this->module)
		->setName($this->module);
	
		//$this->setCss("form-inline");
		$this->setCss("container-fluid");
	
		$this->setTemplateName("./dossier/adminPager.tpl");
		
		$this->id = \wp\Helpers\urlHelper::context("id");
		
		$this->mapper = new \arcec\Mapper\dossierMapper();
		
		$this->setPager();
		
		$this->globalDisabled = false;
		
		$this->toggleSubForm($isSubForm);
		$this->setTabId($tabId);
		
		$this->set();
		
		if(\wp\Helpers\urlHelper::context() != "INSERT"){
			\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Dossier");
		} else {
			\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Dossier - Création");
		}
		
		if(!$this->isSubForm()){
			if(!$this->isValidate()){
				$this->setAction(array("com" => $this->module,"context"=>\wp\Helpers\urlHelper::toContext()));
			} else {
				$this->process();
			}
		}
	}
	
	/**
	 * Surcharge de la méthode pour retourner vers le formulaire de recherche des Dossiers
	 * @see \wp\formManager\admin::getCancelBtn()
	 */
	public function getCancelBtn(){
		$button = new \wp\formManager\Fields\linkButton();
		$button->setId("btnCancel")
		->setTitle("Retour à la liste")
		->addAttribut("role","button")
		->setValue("./index.php?com=listeDossier")
		->setCss("btn")
		->setCss("btn-default")
		->setLabel("Retour")
		;
		return $button;
	}

	public function getDossier(){
		return $this->dossier;
	}
	
	/**
	 * Détermine les ensembles de champs à traiter et le pager
	 * @see \wp\formManager\admin::set()
	**/
	public function set(){
		
		if(\wp\Helpers\urlHelper::context() == "UPDATE" || \wp\Helpers\urlHelper::context() == "DELETE" || \wp\Helpers\urlHelper::context() == "upd" || \wp\Helpers\urlHelper::context() == "del"){
			
			$this->mapper->setId($this->id);
			$this->mapper->set($this->mapper->getNameSpace());
			
			if(!$this->isSubForm()){	
				// Crée le champ caché pour le stockage de la clé primaire
				$field = new \wp\formManager\Fields\hidden();
				$field->setId($this->mapper->getTableName() . ".primary")
				->setName($this->mapper->getTableName() . ".primary")
				->setValue($this->id);
				$this->addToFieldset($field);
				
			}
			
			// Détermine l'état des champs à partir de la valeur de dossier_ca
			if($this->mapper->getObject()->ca != ""){
				$this->globalDisabled = true;
			}
		}
		
		$this->addFieldset("dossier");
		$this->defDossier("dossier");
		$this->pager->addPage("dossier");
		
		$this->addFieldset("immatriculation");
		$this->defImmatriculation("immatriculation");
		$this->pager->addPage("immatriculation");
		
		$this->addFieldset("coords");
		$this->defCoords("coords");
		$this->pager->addPage("coords");
		
		$this->addFieldset("etudes");
		$this->defEtudes("etudes");
		$this->pager->addPage("etudes");
		
		$this->addFieldset("projet");
		$this->defProjet("projet");
		$this->pager->addPage("projet");
		
		// Ajouter la valeur du champ de support...
		if($currentPage = \wp\Helpers\httpQueryHelper::get("frmPorteurSupport")){
			$this->clientRIA .= "
				$(\"#frmPorteurSupport\").val(\"" . $currentPage . "\");
			";	
		} else {
			// Aucune donnée postée, on prend la valeur de base par défaut
			$this->clientRIA .= "
				$(\"#frmPorteurSupport\").val(\"dossier\");
			";
		}
		
		// Ajoute le plug-in JS pour la gestion des dates
		$js = new \wp\htmlManager\js();
		$js->addPlugin("jquery.plugin",true);
		$js->addPlugin("jquery.datepick",true);
		$js->addPlugin("jquery.datepick-fr");
		
		\wp\Tpl\templateEngine::getEngine()->addContent("js",$js);
		
		// Ajoute le script d'ouverture du datepicker et de la gestion du pager
		$this->clientRIA .= $this->pager->getRIAScript($this->getTabId());
		
		$this->toControls();
		
		// Ajoute les CSS
		$css = new \wp\htmlManager\css();
		$css->addSheet("jquery.datepick");
		
		\wp\Tpl\templateEngine::getEngine()->addContent("css",$css);
		
	}
	
	private function setPager(){
		$this->pager = new \wp\htmlManager\pager();
		$this->pager->setId("porteur-pager")
			->setTemplateName("pager")
			->setNavClass("nav")
			->setListClass("pagination");
		
		// Ajoute le contrôle du champ pour réaffichage de la page courante après mise à jour
		$this->pager->supportField("frmPorteurSupport");
		// Ajoute le script pour la gestion du transfert de l'information dans le champ de support
		$this->clientRIA .= "
			$(\"#porteur-pager ul.pagination li\").on(\"click\",function(ev){
					$(\"#frmPorteurSupport\").val($(this).children(\"a\").data(\"rel\"));
				}
			);
		";
		
	}
	
	public function getPager(){
		return $this->pager;
	}
	
	/**
	 * Traite le formulaire
	 * @see \wp\formManager\admin::process()
	 * @todo Créer la méthode pour définir l'URL d'erreur (rester sur place et afficher le message)
	 */
	protected function process(){
		
		$this->before();
	
		if($this->tableId = parent::process()){
			
			$location = $this->after();
			
			if(is_null($location)){
				// Retourne à l'index de traitement courant
				$locationParams = array(
						"com" => "listeDossier",
						"id" => $this->tableId,
						"frmPorteurSupport" => \wp\Helpers\httpQueryHelper::get("frmPorteurSupport")
				);
				$location = \wp\Helpers\urlHelper::setAction($locationParams);
				#die("Redirection vers : " . $location);
			}
			
			header("Location:" . $location);
			return;
		}
		
		$this->sucess = false;
		$this->failedMsg = "Une erreur est survenue lors de l'enregistrement";
		header("Location:" . \wp\Helpers\urlHelper::toURL($this->module));
	}
	
	protected function before(){
		switch(\wp\Helpers\urlHelper::context()){
			case "add":
				return $this->beforeInsert();
				break;
					
			case "upd":
				return $this->beforeUpdate();
				break;
					
			case "del":
				return $this->beforeDelete();
				break;
		}
	
		return;
	}
	
	protected function after(){
		switch(\wp\Helpers\urlHelper::context()){
			case "add":
				return $this->afterInsert();
			break;
					
			case "upd":
				return $this->afterUpdate();
			break;
					
			case "del":
				return $this->afterDelete();
			break;
		}
	
		return;
	}
	
	protected function beforeInsert(){
		
		// Détermine si les données de programme ont été définies
		if($this->getField("frmParamTYP")->getPostedData() == 0){
			$typeMapper = new \arcec\Mapper\paramTYPMapper();
			$typeMapper->searchBy("libellecourt","CRE");
			$typeMapper->set($typeMapper->getNameSpace());
			$this->mapper->porteurtyp = $typeMapper->getObject()->id;
			\wp\Helpers\httpQueryHelper::post($this->getField("frmParamTYP")->getPostedName(),$this->mapper->porteurtyp);
			
			$prgMapper = new \arcec\Mapper\paramPRGMapper();
			$prgMapper->searchBy("libellecourt","AAC");
			$prgMapper->set($prgMapper->getNameSpace());
			$this->mapper->porteurprg = $prgMapper->getObject()->id;
			\wp\Helpers\httpQueryHelper::post($this->mapper->getTableName()."_".$this->mapper->getColumnPrefix() . "porteurprg",$this->mapper->porteurprg);
		} else {
			$typeMapper = new \arcec\Mapper\paramTYPMapper();
			$typeMapper->setId($this->getField("frmParamTYP")->getPostedData());
			$typeMapper->set($typeMapper->getNameSpace());
			
			$prgMapper = new \arcec\Mapper\paramPRGMapper();
			if($typeMapper->getObject()->libellecourt == "CRE"){
				$prgMapper->searchBy("libellecourt","AAC");
			} else {
				$prgMapper->searchBy("libellecourt","APC");
			}
			
			$prgMapper->set($prgMapper->getNameSpace());
			$this->mapper->porteurprg = $prgMapper->getObject()->id;
			\wp\Helpers\httpQueryHelper::post($this->mapper->getTableName()."_".$this->mapper->getColumnPrefix() . "porteurprg",$this->mapper->porteurprg);
		}
		
		$this->mapper->datecreation = \wp\Helpers\dateHelper::toSQL(\wp\Helpers\dateHelper::today(),"yyyy-mm-dd");
		
		$edo = new \arcec\Mapper\paramEDOMapper();
		$edo->searchBy("libellecourt", "ENC");
		$edo->set($edo->getNameSpace());
		
		$this->mapper->edo = $edo->getObject()->id;
		
		return;
	}
	
	protected function beforeUpdate(){}
	
	protected function beforeDelete(){}
	
	protected function afterInsert(){
		#begin_debug
		#echo "Créer les lignes de suivi de projet : pour " . $this->tableId . " <br />\n";
		#end_debug
		
		// Crée les lignes de suivi... en fonction du programme choisi
		$dossier = new \arcec\Mapper\dossierMapper();
		
		$dossier->setId($this->tableId);
		$dossier->set($dossier->getNameSpace());
		$activeRecord = $dossier->getObject();

		
		// Récupère les étapes du programme sélectionné
		$etapes = new \arcec\Mapper\prgtoetapesMapper();
		$etapes->searchBy("programme_id",$activeRecord->porteurprg);
		$etapes->set($etapes->getNameSpace());
		
		// Récupère l'identifiant de la tâche de base
		$task = new \arcec\Mapper\paramWRKMapper();
		//$task->setId(2);
		$task->searchBy("defaut", 1);
		$task->set($task->getNameSpace());
		
		// Crée les lignes associées
		$insert = "
			INSERT INTO " . _DB_PREFIX_ . "suivi 
			(dossier_id,programme_id,etapeprojet_id,conseiller_id,action_id,suivi_date) 
			VALUES (:dossier,:programme,:etapeprojet,:conseiller,:action,:date);";
		
		$params["dossier"] = $this->tableId;
		$params["programme"] = $activeRecord->porteurprg;
		$params["conseiller"] = $activeRecord->cns;
		$params["date"] = \wp\Helpers\dateHelper::today();
		$params["action"] = $task->getObject()->id;
		
		$dbInstance = \wp\dbManager\dbConnect::dbInstance();
		$query = $dbInstance->getConnexion()->prepare($insert);
		
		foreach($etapes->getCollection() as $etape){
			$params["etapeprojet"] = $etape->id;
			#begin_debug
			#echo "Exécute $insert avec <br />\n";
			#var_dump($params);
			#echo "<br />\n<br/>\n";
			#end_debug
			$query->execute($params);
		}
		
		// Affiche le formulaire de création du premier rappport
		$locationParams = array(
				"com" => "addRapport",
				"context" => "INSERT",
				"id" => $this->tableId
		);
		
		#begin_debug
		#$location = \wp\Helpers\urlHelper::setAction($locationParams);
		#end_debug
		
		return \wp\Helpers\urlHelper::setAction($locationParams);
		
	}
	
	protected function afterUpdate(){

	}
	
	protected function afterDelete(){}
	
	
	private function defDossier($fieldset){
		// Liste des conseillers => paramètres CNS
		$field = new \wp\formManager\Fields\popup();
		
		// Mapping sur paramBase avec code paramètre CNS
		$mapper = new \arcec\Mapper\paramCNSMapper();
		
		$field->setId("frmParamCNS")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "cns")
		->setLabel("Conseiller accueil")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->isDisabled($this->globalDisabled)
		->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
		->setValue($this->mapper->getObject()->cns)
		;
		
		$this->addToFieldset($field,$fieldset);
		
		// Liste des lieux d'accueil => paramètres ACU
		$field = new \wp\formManager\Fields\popup();
		
		// Mapping sur paramBase avec code paramètre ACU
		$mapper = new \arcec\Mapper\paramACUMapper();
		
		$field->setId("frmParamACU")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "acu")
		->setLabel("Lieu d'accueil")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->isDisabled($this->globalDisabled)
		->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
		->setValue($this->mapper->getObject()->acu)
		;
		$this->addToFieldset($field,$fieldset);
		
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmNom")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "nomporteur")
		->setLabel("Nom du porteur")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->requiredMsg("Le nom du porteur ne peut pas être vide")
		->pattern("empty")
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->toUpper()
		->setValue($this->mapper->getObject()->nomporteur);
		
		$this->addToFieldset($field,$fieldset);
		
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmPrenom")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "prenomporteur")
		->setLabel("Prénom")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setValue($this->mapper->getObject()->prenomporteur);
		
		$this->addToFieldset($field,$fieldset);
		
		// Liste des phases => paramètres ETD
		$field = new \wp\formManager\Fields\popup();
		
		// Mapping sur paramBase avec code paramètre ETD
		$mapper = new \arcec\Mapper\paramETDMapper();
		
		$field->setId("frmParamETD")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "etd")
		->setLabel("Etat du projet")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-12")
		->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
		->isDisabled($this->globalDisabled)
		->setValue($this->mapper->getObject()->etd)
		;
		
		$this->addToFieldset($field,$fieldset);
		
		
		/*
		// Liste des états => paramètres EDO uniquement en mise à jour
		if(\wp\Helpers\urlHelper::context() == "UPDATE" || \wp\Helpers\urlHelper::context() == "upd"){
			$field = new \wp\formManager\Fields\popup();
			
			// Mapping sur paramBase avec code paramètre EDO
			$mapper = new \arcec\Mapper\paramEDOMapper();
			
			$field->setId("frmParamEDO")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "edo")
			->setLabel("Etat du dossier")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
			->setValue($this->mapper->getObject()->edo)
			;
			
			$this->addToFieldset($field,$fieldset);
			
			// Gestion du script de chargement de la taxonomie éventuellement associée au changement
			$this->clientRIA .= $this->addTaxonomie($field,$mapper,"paramEDO","frmParamCA",$this->mapper->getObject()->edo);
		}
		
		// Champ caché pour la gestion de l'état du dossier
		$field = new \wp\formManager\Fields\hidden();
		$field->setId("frmParamCA")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "ca")
			->setValue($this->mapper->getObject()->ca);
		$this->addToFieldset($field,$fieldset);
		*/
		
		if(\wp\Helpers\urlHelper::context() == "INSERT" || \wp\Helpers\urlHelper::context() == "add"){
			$field = new \wp\formManager\Fields\datePicker();
			$field->setId("frmDateAdhesion")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "dateadhesion")
			->setLabel("Date d'adhésion à l'ARCEC")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->isRequired()
			->requiredMsg("La date d'adhésion est obligatoire")
			->pattern("empty_or_date")
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->setTriggerId("adhesion")
			->isReadOnly($this->globalDisabled)
			->setRIAScript()
			->setValue(!is_null($this->mapper->getObject()->dateadhesion) ? $this->mapper->getObject()->dateadhesion : \wp\Helpers\dateHelper::today("d/m/Y"));
			
			$this->addToFieldset($field,$fieldset);
			
			$this->clientRIA .= $field->getRIAScript();

			$field = new \wp\formManager\Fields\datePicker();
			
			// Gère le trigger lors de la sélection
			$script = "$(\"#frmDateCotisation\").trigger(\"focus\");";
			$field->setId("frmDateCotisation")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "datecotisation")
			->setLabel("Date de cotisation")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->setTriggerId("cotisation")
			->isReadOnly($this->globalDisabled)
			->addEvent("onSelect",$script)
			->setRIAScript()
			->setValue(!is_null($this->mapper->getObject()->datecotisation) ? $this->mapper->getObject()->datecotisation : \wp\Helpers\dateHelper::today("d/m/Y"));
			
			$this->addToFieldset($field,$fieldset);
			$this->clientRIA .= $field->getRIAScript();
			
			// Ajoute la dépendance entre les deux champs pour recopie
			$dependency = new \wp\formManager\Dependencies\dependency();
			$dependency->setMasterField("frmDateAdhesion")
			->setType("popup")
			->setDependantField("frmDateCotisation")
			->setEvent("blur")
			->addAction("dateCopy");
			$this->addDependency($dependency);
			
			// Code de gestion de la date de cotisation qui ne peut pas être inférieure à la date d'adhésion
			$this->clientRIA .= "
				$(\"#frmDateCotisation\").on(\"focus\",function(){
						var masterData = $(\"#frmDateAdhesion\").val();
						var slaveData = $(this).val();
							// Détermine le séparateur
							var dateSep = \"/\";
							if(masterData.indexOf(\"-\") != -1){
								var dateSep = \"-\";
							}
							
							var regExp = new RegExp(\"[\" + dateSep + \"]+\", \"g\");
							var masterDateParts = masterData.split(regExp);
							var slaveDateParts = slaveData.split(regExp);

							var masterDate = new Date(masterDateParts[2],masterDateParts[1],masterDateParts[0]);
							var slaveDate = new Date(slaveDateParts[2],slaveDateParts[1],slaveDateParts[0]);
					
							if(slaveDate < masterDate){
								$(this).val(masterData);
							}
					}
				).on(\"blur\",function(){
					var masterData = $(\"#frmDateAdhesion\").val();
					var slaveData = $(\"#frmDateCotisation\").val();
						// Détermine le séparateur
						var dateSep = \"/\";
						if(masterData.indexOf(\"-\") != -1){
							var dateSep = \"-\";
						}
							
						var regExp = new RegExp(\"[\" + dateSep + \"]+\", \"g\");
						var masterDateParts = masterData.split(regExp);
						var slaveDateParts = slaveData.split(regExp);

						var masterDate = new Date(masterDateParts[2],masterDateParts[1],masterDateParts[0]);
						var slaveDate = new Date(slaveDateParts[2],slaveDateParts[1],slaveDateParts[0]);
					
						if(slaveDate < masterDate){
							$(\"#" . $this->dependantField . "\").val(masterData);
						}
					}
				);
			";
			
		}
		// Définit les classes spécifiques pour ce fieldset
		$object = $this->getFieldset($fieldset);
		$object->setLegend("Dossier");
		
		// Détermine l'état de la page
		$activeState = false;
		if($currentDossier = \wp\Helpers\httpQueryHelper::get("frmPorteurSupport")){
			if($currentDossier == $fieldset){
				$activeState = true;
			}
		} else {
			$activeState = true; // Il s'agit de l'onglet à son état initial
		}
		
		$object->setCss(array("fieldset",$activeState ? "active" : "inactive"));
	}
	
	private function defImmatriculation($fieldset){
		
		// Date de naissance
		$field = new \wp\formManager\Fields\datePicker();
		$field->setId("frmDateNaissance")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurdatenaissance")
		->setLabel("Date de naissance")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->requiredMsg("La date de naissance du porteur n'est pas correcte")
		->pattern("empty_or_date")
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setTriggerId("naissance")
		->setMaxDate()
		->setRIAScript()
		->isReadOnly($this->globalDisabled)
		->setValue($this->mapper->getObject()->porteurdatenaissance);
		$this->addToFieldset($field,$fieldset);
		
		$this->clientRIA .= $field->getRIAScript();
		
		// Sexe
		$field = new \wp\formManager\Fields\group();
		$field->setId("frmSexe")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteursexe")
		->setLabel("Sexe")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->requiredMsg("La sélection du sexe est obligatoire")
		->pattern("empty")
		->setCss("panel")
		->setCss("panel-default")
		->setCss("col-sm-6")
		->isReadOnly($this->globalDisabled)
		->setDefault($this->mapper->getObject()->porteursexe,"M");
		
		// Ajoute les objets au groupe...
		$radio = new \wp\formManager\Fields\radio();
		$radio->setId("frmMasculin")
		->setLabel("M")
		->setValue("M");
		$field->add($radio);
		
		$radio = new \wp\formManager\Fields\radio();
		$radio->setId("frmFeminin")
		->setLabel("F")
		->setValue("F");
		$field->add($radio);
		
		$this->addToFieldset($field,$fieldset);
		
		
		// Liste des nationalités
		// Mapping sur paramBase avec code paramètre NAT
		$mapper = new \arcec\Mapper\paramNATMapper();
		
		if($mapper->count() > 0){
			$field = new \wp\formManager\Fields\popup();
		
			$field->setId("frmParamNAT")
				->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurnat")
				->setLabel("Nationalité")
				->setCss("control-label",true)
				->setCss("col-sm-12",true)
				->setCss("form-control")
				->setGroupCss("col-sm-12")
				->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
				->isDisabled($this->globalDisabled)
				->setValue($this->mapper->getObject()->porteurnat)
				;
			$this->addToFieldset($field,$fieldset);
		}
		
		// Liste des situations de familles
		$field = new \wp\formManager\Fields\popup();
		
		// Mapping sur paramBase avec code paramètre SIF
		$mapper = new \arcec\Mapper\paramSIFMapper();
		
		$field->setId("frmParamSIF")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteursif")
		->setLabel("Situation de famille")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
		->isDisabled($this->globalDisabled)
		->setValue($this->mapper->getObject()->porteursif)
		;
		$this->addToFieldset($field,$fieldset);
		
		// Liste des régimes matrimoniaux
		$field = new \wp\formManager\Fields\popup();
		
		// Mapping sur paramBase avec code paramètre RIM
		$mapper = new \arcec\Mapper\paramRIMMapper();
		
		$field->setId("frmParamRIM")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurrim")
		->setLabel("Régime matrimonial")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->isDisabled($this->globalDisabled || is_null($this->mapper->getObject()->porteursif) ? true : false)
		->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
		->setValue($this->mapper->getObject()->porteurrim)
		;
		$this->addToFieldset($field,$fieldset);
		
		// Définir la relation de dépendance entre frmParamSIF et frmParamRIM : actif seulement si SIF est marié
		$dependency = new \wp\formManager\Dependencies\dependency();
		$dependency->setMasterField("frmParamSIF")
		->setType("popup")
		->setDependantField("frmParamRIM")
		->setEvent("change")
		->ifContent("MP")
		->addAction("enable");
		$this->addDependency($dependency);
		
		
		// Travailleur handicapé
		$field = new \wp\formManager\Fields\checkbox();
		$field->setId("frmHandicap")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurhandicap")
		->setLabel("Travailleur handicapé")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setGroupCss("col-sm-12")
		->isReadOnly($this->globalDisabled)
		->isChecked($this->mapper->getObject()->porteurhandicap);
		
		$this->addToFieldset($field,$fieldset);

		// Liste des situations sociales
		$field = new \wp\formManager\Fields\popup();
		
		// Mapping sur paramBase avec code paramètre SIS
		$mapper = new \arcec\Mapper\paramSISMapper();
		
		$field->setId("frmParamSIS")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteursis")
		->setLabel("Situation sociale")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
		->isDisabled($this->globalDisabled)
		->setValue($this->mapper->getObject()->porteursis)
		;
		$this->addToFieldset($field,$fieldset);
		

		// Date d'inscription Pôle Emploi
		$field = new \wp\formManager\Fields\datePicker();
		$field->setId("frmInscriptionPoleEmploi")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurdateinscpoleemploi")
		->setLabel("Inscription Pôle Emploi")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isDisabled($this->globalDisabled || !(\arcec\dossierHeader::getParentCode("SIS",$this->mapper->getObject()->porteursis,"DE")))
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setTriggerId("poleemploi")
		->setRIAScript()
		->isReadOnly($this->globalDisabled)
		->setValue($this->mapper->getObject()->porteurdateinscpoleemploi);
		$this->addToFieldset($field,$fieldset);
		
		$this->clientRIA .= $field->getRIAScript();
		
		// Définir la relation de dépendance entre frmParamSIS et frmInscriptionPoleEmploi : actif seulement si SIS est Demandeur d'emploi
		$dependency = new \wp\formManager\Dependencies\dependency();
		$dependency->setMasterField("frmParamSIS")
		->setType("popup")
		->setDependantField("frmInscriptionPoleEmploi")
		->setDependantObject($field)
		->setEvent("change")
		->ifContent("DE")
		->addAction("enable");
		$this->addDependency($dependency);
		
		
		// Mapping sur paramBase avec code paramètre RIN Liste des régimes d'indemnisation
		$mapper = new \arcec\Mapper\paramRINMapper();
		if($mapper->getNbRows() > 0){
			$field = new \wp\formManager\Fields\popup();
			$field->setId("frmParamRIN")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurrin")
			->setLabel("Régime d'indemnisation")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
			->setValue($this->mapper->getObject()->porteurrin)
			->isDisabled($this->globalDisabled)
			;
			$this->addToFieldset($field,$fieldset);
		}
		// Liste des contrats de travail : dépendance avec frmParamSIS
		$field = new \wp\formManager\Fields\popup();
		
		// Mapping sur paramBase avec code paramètre CNT
		$mapper = new \arcec\Mapper\paramCNTMapper();
		
		$field->setId("frmParamCNT")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurcnt")
		->setLabel("Contrat de travail")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->isDisabled(true)
		->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
		->setValue($this->mapper->getObject()->porteurcnt)
		;
		$this->addToFieldset($field,$fieldset);
		
		/*
		// Définir la relation de dépendance entre frmParamSIS et frmParamCNT : actif seulement si SIS est Salarié
		$dependency = new \wp\formManager\Dependencies\dependency();
		$dependency->setMasterField("frmParamSIS")
		->setType("popup")
		->setDependantField("frmParamCNT")
		->setEvent("change")
		->ifContent("SA")
		->addAction("enable");
		*/
		// Ajoute la dépendance entre frmParamSIS et frmParamCNT
		$dependency = new \wp\formManager\Dependencies\dependency();
		$dependency->setMasterObject($this->getField("frmParamSIS"))
		->setDependantObject($this->getField("frmParamCNT"))
		->setEvent("change")
		->ifContent("SA")
		->addAction("enable");		
		$this->addDependency($dependency);
		
		#begin_debug
		#echo "Statut initial de frmParamCNT : " . $dependency->disableStatut("libellecourt",$this->mapper->getObject()->porteursis)  ? "désactivé" : "activé";
		#end_debug
		$field->isDisabled($this->globalDisabled || $dependency->disableStatut("libellecourt",$this->mapper->getObject()->porteursis));
		
		// Définir la dépendance entre frmParamCNT et frmEligibleFONGECIF
		$dependency = new \wp\formManager\Dependencies\dependency();
		$dependency->setMasterField("frmParamSIS")
		->setType("popup")
		->setDependantField("frmEligibleFONGECIF")
		->setEvent("change")
		->ifNotContent("ITR")
		->addAction("check");
		$this->addDependency($dependency);
				
		// Mapping sur paramBase avec code paramètre ACT
		$mapper = new \arcec\Mapper\paramACTMapper();
		if($mapper->getNbRows() > 0){
			$field = new \wp\formManager\Fields\popup();
			$field->setId("frmParamACT")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteuract")
			->setLabel("Ancienneté du contrat de travail")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
			->setValue($this->mapper->getObject()->porteuract)
			->isDisabled($this->globalDisabled)
			;
			$this->addToFieldset($field,$fieldset);
		}
		
		// Définit les classes spécifiques pour ce fieldset
		$object = $this->getFieldset($fieldset);
		$object->setLegend("Identification");
		
		// Détermine l'état de la page
		$activeState = false;
		if($currentDossier = \wp\Helpers\httpQueryHelper::get("frmPorteurSupport")){
			if($currentDossier == $fieldset){
				$activeState = true;
			}
		}
		$object->setCss(array("fieldset",$activeState ? "active" : "inactive"));
		
	}
	
	private function defCoords($fieldset){
		// Adresse Numéro
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmAdrNum")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteuradrnum")
		->setLabel("N°")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->requiredMsg("Le numéro de la rue ne peut pas être vide")
		->pattern("empty")
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->isReadOnly($this->globalDisabled)
		->setValue($this->mapper->getObject()->porteuradrnum);
		
		$this->addToFieldset($field,$fieldset);
		
		// Liste des types de voies
		$field = new \wp\formManager\Fields\popup();
		
		// Mapping sur paramBase avec code paramètre VOI
		$mapper = new \arcec\Mapper\paramVOIMapper();
		
		$field->setId("frmParamVOI")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurvoi")
		->setLabel("Type de voie")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
		->isDisabled($this->globalDisabled)
		->setValue($this->mapper->getObject()->porteurvoi)
		;
		$this->addToFieldset($field,$fieldset);
		
		// Libellé voie 1
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmAdr1")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteuradr1")
		->setLabel("Libellé voie")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->requiredMsg("Le libellé de la voie ne peut pas être vide")
		->pattern("empty")
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->isReadOnly($this->globalDisabled)
		->setValue($this->mapper->getObject()->porteuradr1);
		
		$this->addToFieldset($field,$fieldset);
		
		// Libellé voie 2
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmAdr2")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteuradr2")
		->setLabel("Libellé voie (2)")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->isReadOnly($this->globalDisabled)
		->setValue($this->mapper->getObject()->porteuradr2);
		
		$this->addToFieldset($field,$fieldset);
		
		// Code postal
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmCodePostal")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurcodepostal")
		->setLabel("Code Postal")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->requiredMsg("Le code postal est obligatoire")
		->pattern("empty_or_zipcode")
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->isReadOnly($this->globalDisabled)
		->setValue($this->mapper->getObject()->porteurcodepostal);
		
		$this->addToFieldset($field,$fieldset);
		
		// Ville
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmVille")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurville")
		->setLabel("Ville")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->requiredMsg("La ville de résidence du porteur est obligatoire")
		->pattern("empty")
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->toUpper()
		->isReadOnly($this->globalDisabled)
		->setValue($this->mapper->getObject()->porteurville);
		
		$this->addToFieldset($field,$fieldset);
		
		// Détermine l'obligation pour les téléphones
		$isRequired = ($this->mapper->getObject()->porteurtelfixe || $this->mapper->getObject()->porteurtelportable) ? false : true;
		
		// Téléphone fixe
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmTelFixe")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurtelfixe")
		->setLabel("Téléphone fixe")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired($isRequired)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->isReadOnly($this->globalDisabled)
		->setValue($this->mapper->getObject()->porteurtelfixe);
		
		$this->addToFieldset($field,$fieldset);
		
		// Téléphone portable
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmTelPortable")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurtelportable")
		->setLabel("Téléphone portable")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired($isRequired)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->isReadOnly($this->globalDisabled)
		->setValue($this->mapper->getObject()->porteurtelportable);
		
		$this->addToFieldset($field,$fieldset);
		
		// Ajoute la contrainte sur les téléphones : l'un ou l'autre doit être rempli
		$this->clientRIA .= "
			$(\"input[id^=frmTel]\").on(\"blur\",function(){
					var id = $(this).attr(\"id\");
					var value=$(this).val();
					if(value != \"\"){
						// Le statut requis de l'autre champ peut être basculé
						if($(this).attr(\"id\") == \"frmTelFixe\"){
							$(\"#frmTelPortable\").removeAttr(\"required\");
						} else {
							$(\"#frmTelFixe\").removeAttr(\"required\");
						}
					}
				}
			);
		";
		
		// Email
		$field = new \wp\formManager\Fields\mail();
		$field->setId("frmMail")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteuremail")
		->setLabel("Adresse e-mail")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->setCss("form-control")
		->setGroupCss("col-sm-12")
		->isReadOnly($this->globalDisabled)
		->setValue($this->mapper->getObject()->porteuremail);
		
		$this->addToFieldset($field,$fieldset);

		// Définit les classes spécifiques pour ce fieldset
		$object = $this->getFieldset($fieldset);
		$object->setLegend("Coordonnées");
		
		// Détermine l'état de la page
		$activeState = false;
		if($currentDossier = \wp\Helpers\httpQueryHelper::get("frmPorteurSupport")){
			if($currentDossier == $fieldset){
				$activeState = true;
			}
		}
		$object->setCss(array("fieldset",$activeState ? "active" : "inactive"));
	}
	
	private function defEtudes($fieldset){
		// Liste des niveaux d'études
		// Mapping sur paramBase avec code paramètre ETU
		$mapper = new \arcec\Mapper\paramETUMapper();
		if($mapper->count() > 0){
			$field = new \wp\formManager\Fields\popup();
				
			$field->setId("frmParamETU")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteuretu")
			->setLabel("Niveau d'études")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-12")
			->setForceHeaderStatut(true)
			->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
			->setValue($this->mapper->getObject()->porteuretu)
			->isDisabled($this->globalDisabled)
			;
			$this->addToFieldset($field,$fieldset);
		}
		
		// Liste des diplômes
		// Mapping sur paramBase avec code paramètre DPL
		$mapper = new \arcec\Mapper\paramDPLMapper();
		
		if($mapper->count() > 0){
			$field = new \wp\formManager\Fields\popup();
				
		
			$field->setId("frmParamDPL")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurdpl")
			->setLabel("Diplômes")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->setForceHeaderStatut(true)
			->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
			->isDisabled($this->globalDisabled)
			->setValue($this->mapper->getObject()->porteurdpl)
			;
			$this->addToFieldset($field,$fieldset);
		
			// Spécialité
			$field = new \wp\formManager\Fields\text();
			$field->setId("frmSpecialite")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurspecdiplome")
			->setLabel("Spécialité")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->isRequired()
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->isDisabled($this->globalDisabled && $this->mapper->getObject()->porteurdpl == 0)
			->setValue($this->mapper->getObject()->porteurspecdiplome);
			$this->addToFieldset($field,$fieldset);
				
			// Définir la relation de dépendance entre frmParamDPL et frmSpecialite : actif si DPL <> 0
			$dependency = new \wp\formManager\Dependencies\dependency();
			$dependency->setMasterField("frmParamDPL")
			->setType("popup")
			->setDependantField("frmSpecialite")
			->setEvent("change")
			->ifNotContent(0)
			->addAction("enable");
			$this->addDependency($dependency);
		}
		
		// Expérience professionnelle
		$field = new \wp\formManager\Fields\textarea();
		$field->setId("frmExperience")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurexppro")
		->setLabel("Expérience professionnelle")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->isReadOnly($this->globalDisabled)
		->setValue($this->mapper->getObject()->porteurexppro);
		$this->addToFieldset($field,$fieldset);
		


		// Définit les classes spécifiques pour ce fieldset
		$object = $this->getFieldset($fieldset);
		$object->setLegend("Etudes et expérience");
		
		// Détermine l'état de la page
		$activeState = false;
		if($currentDossier = \wp\Helpers\httpQueryHelper::get("frmPorteurSupport")){
			if($currentDossier == $fieldset){
				$activeState = true;
			}
		}
		$object->setCss(array("fieldset",$activeState ? "active" : "inactive"));
	}
	
	private function defProjet($fieldset){

		// Mapping sur paramBase avec code paramètre PRS
		$mapper = new \arcec\Mapper\paramPRSMapper();
		if($mapper->count() > 0){
			// Liste des prescripteurs
			$field = new \wp\formManager\Fields\popup();
			
			$field->setId("frmParamPRS")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurprs")
			->setLabel("Prescripteur")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
			->isDisabled($this->globalDisabled)
			->setValue($this->mapper->getObject()->porteurprs)
			;
			$this->addToFieldset($field,$fieldset);
			
			// Nom du prescripteur
			$field = new \wp\formManager\Fields\text();
			$field->setId("frmNomPresc")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurnompresc")
			->setLabel("Nom de l'interlocuteur")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->isReadOnly($this->globalDisabled)
			->setValue($this->mapper->getObject()->porteurnompresc);
			
			$this->addToFieldset($field,$fieldset);
			
			// Email du prescripteur
			$field = new \wp\formManager\Fields\mail();
			$field->setId("frmMailPresc")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteuremailpresc")
			->setLabel("E-Mail")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->isReadOnly($this->globalDisabled)
			->setValue($this->mapper->getObject()->porteuremailpresc);
			
			$this->addToFieldset($field,$fieldset);
		}
		
		// Mapping sur paramBase avec code paramètre TYP
		$mapper = new \arcec\Mapper\paramTYPMapper();
		if($mapper->count() > 0){
			$field = new \wp\formManager\Fields\popup();
			$field->setId("frmParamTYP")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurtyp")
			->setLabel("Catégorie de projet")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
			->isDisabled($this->globalDisabled)
			->setValue($this->mapper->getObject()->porteurtyp)
			;
			$this->addToFieldset($field,$fieldset);
		}
				
		// Résumé du projet
		$field = new \wp\formManager\Fields\textarea();
		$field->setId("frmResume")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurresumeprojet")
		->setLabel("Résumé du projet")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-12")
		->isReadOnly($this->globalDisabled)
		->setValue($this->mapper->getObject()->porteurresumeprojet);
		
		$this->addToFieldset($field,$fieldset);
		
		// Statique Etat du projet à l'accueil
		
		
	
		// Genèse du projet
		$field = new \wp\formManager\Fields\textarea();
		$field->setId("frmGenese")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurgeneseprojet")
		->setLabel("Genèse du projet")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-12")
		->isReadOnly($this->globalDisabled)
		->setValue($this->mapper->getObject()->porteurgeneseprojet);

		$this->addToFieldset($field,$fieldset);
		
		// Demande du porteur
		$field = new \wp\formManager\Fields\textarea();
		$field->setId("frmDemandePorteur")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurdemande")
		->setLabel("Demande du porteur")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-12")
		->isReadOnly($this->globalDisabled)
		->setValue($this->mapper->getObject()->porteurdemande);

		$this->addToFieldset($field,$fieldset);
		
		// Liste des conseillers => paramètres CNS
		$field = new \wp\formManager\Fields\popup();
		
		// Mapping sur paramBase avec code paramètre CNS
		$mapper = new \arcec\Mapper\paramCNSMapper();
		
		$field->setId("frmParamCNSCoord")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurcnscoord")
		->setLabel("Conseiller Coordinateur")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->isRequired()
		->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
		->isDisabled($this->globalDisabled)
		->setValue($this->mapper->getObject()->porteurcnscoord);		
		;
		
		$this->addToFieldset($field,$fieldset);
		
		// Définit les classes spécifiques pour ce fieldset
		$object = $this->getFieldset($fieldset);
		$object->setLegend("Projet");

		// Détermine l'état de la page
		$activeState = false;
		if($currentDossier = \wp\Helpers\httpQueryHelper::get("frmPorteurSupport")){
			if($currentDossier == $fieldset){
				$activeState = true;
			}
		}
		$object->setCss(array("fieldset",$activeState ? "active" : "inactive"));
		
	}
	
	private function addTaxonomie($field,$mapper,$mapperName,$targetFieldId,$defaultValue){
		$script = "
			$(\"#" . $field->getId() . "\").on(\"change\",function(){
					if($(\"#" . $this->getId() . " option:selected\").val() != 0){
						// Vérifie si la sélection courante nécessite une taxonomie descendante
						$.ajax(
							{
								url:\"" . \wp\framework::getFramework()->getAjaxDispatcher() . "\",
								type:\"POST\",
								data:\"object=taxonomyChecker&namespace=" . str_replace("\\","_",$mapper->getNameSpace()) . "&mapper=" . $mapperName . "&content=\" + $(\"#" . $field->getId() . " option:selected\").val(),
								dataType:\"json\"
							}
						).success(function(data,statut){
								console.log(\"Statut : \" + data.statut + \" => \" + data.dependency);
								if(data.statut == 1){
									// Ouverture de la boîte modale associée
									$(\"#modalSelect\").modal(\"show\");
									$(\"#modalSelect div.modal-body\").empty();
									$(\"#modalSelect .modal-title\").text(\"" . $field->getLabel() . "\");
									$.ajax(
										{
											url:\"" . \wp\framework::getFramework()->getAjaxDispatcher() . "\",
											type:\"POST\",
											data:\"object=taxonomyMaker&namespace=" . str_replace("\\","_",$mapper->getNameSpace()) . "&mapper=" . $mapperName . "&content=\" + $(\"#" . $field->getId() . " option:selected\").val(),
											dataType:\"json\"											
										}
									).success(function(data,statut){
											var root = data.root;
											var id = root.id;
											var content = root.content;
											var taxonomy = data.taxonomy;
											console.log(\"Taxonomy pour : \" + content + \" : \" + taxonomy.length);
											var list = $(\"<ul>\");
											$(list).addClass(\"list-unstyled\");
											var firstLine = $(\"<li>\");
											$(firstLine)
													.addClass(\"first-line\")
													.text(\"Fermer\")
													.attr(\"data-rel\",0)
													.appendTo($(list));
											getList(taxonomy,list);
											$(list).appendTo($(\"#modalSelect div.modal-body\"));
											
											// Code de gestion de la sélection d'une ligne
											$(\"#modalSelect div.modal-body\").on(\"click\",\"li\",function(e){
													var li = e.target; //Cible effective
													
													console.log(\"Ligne cliquée : \" + $(li).data(\"rel\"));
													var id = $(li).data(\"rel\");
													
													console.log(\"Sélection de :  \" + id);
													
													if(id == 0){
														var defaultLine = " . $defaultValue . ";
														$(\"#" . $targetFieldId . "\").val(\"\");
														$(\"#" . $this->getId() . " input\").removeAttr(\"readonly\",\"readonly\");
														$(\"#" . $this->getId() . " select\").removeAttr(\"disabled\",\"disabled\");
														// Restaurer la valeur par défaut du champ
														$(\"#" . $this->getId() . "\").removeAttr(\"selected\");
														//document.getElementById(\"" . $field->getId() . "\").options[defaultLine].selected = true;
														$(\"#" . $field->getId() . " [value='" .  $defaultValue. "']\").prop(\"selected\",\"selected\");
													} else {	
														$(\"#" . $targetFieldId . "\").val(id);
														$(\"#" . $this->getId() . " input\").attr(\"readonly\",\"readonly\");
														$(\"#" . $this->getId() . " select\").attr(\"disabled\",\"disabled\");
														// Sauf le champ courant...
														$(\"#" . $field->getId() . "\").removeAttr(\"readonly\").removeAttr(\"disabled\");		
													}
													
													$(\"#modalSelect\").modal(\"hide\");
												}
											);		
										}
									);
								} else {
									// Statut à 0, la valeur de la cible doit être effacée
									$(\"#" . $targetFieldId . "\").val(\"\");
									$(\"#" . $this->getId() . " input\").removeAttr(\"readonly\",\"readonly\");
									$(\"#" . $this->getId() . " select\").removeAttr(\"disabled\",\"disabled\");
								}
							}
						).fail(function(){
							}
						);					
					}
				}
			);
													
			function getList(item, \$list) {
			    
			    if($.isArray(item)){
			        $.each(item, function (key, value) {
			            getList(value, \$list);
			        });
			        return;
			    }
			    
			    if (item) {
			        var \$li = $(\"<li>\");
					\$li.attr(\"data-rel\",item.id);
													
			        if (item.content) {
						\$li.text(item.content);							
			           // \$li.append($('<a href=\"#\">' + item.content + '</a>'));
			        }
			        if (item.children && item.children.length) {
			            var \$sublist = $(\"<ul>\");
			            getList(item.children, \$sublist)
			            \$li.append(\$sublist);
			        }
			        \$list.append(\$li)
			    }
			}									
		";
		
		return $script;
	}
}
?>