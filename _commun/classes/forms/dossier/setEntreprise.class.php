<?php
/**
 * @name setEntreprise.class.php Définition du formulaire de gestion de l'entreprise avec une pagination par fieldset
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Dossier
 * @version 1.0
 **/
namespace arcec;

class setEntreprise extends \wp\formManager\admin{
	
	/**
	 * Objet "pager" pour dérouler les fieldsets
	 * @var object
	**/
	private $pager;
	
	/**
	 * Identifiant du dossier si le mode est mise à jour ou suppression
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
	 * Instancie un nouvel objet de gestion de l'entreprise
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
			\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Entreprise");
		} else {
			\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Entreprise - Création");
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
				
				// Détermine l'état des champs à partir de la valeur de dossier_ca
				if($this->mapper->getObject()->ca != ""){
					$this->globalDisabled = true;
				}
			}
		}
		
		$this->addFieldset("forme");
		$this->defForme("forme");
		$this->pager->addPage("forme");
		
		$this->addFieldset("suivi");
		$this->defSuivi("suivi");
		$this->pager->addPage("suivi");

		// Ajouter la valeur du champ de support...
		if($currentPage = \wp\Helpers\httpQueryHelper::get("frmEntrepriseSupport")){
			$this->clientRIA .= "
				$(\"#frmEntrepriseSupport\").val(\"" . $currentPage . "\");
			";
		} else {
			// Aucune donnée postée, on prend la valeur de base par défaut
			$this->clientRIA .= "
				$(\"#frmEntrepriseSupport\").val(\"forme\");
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
		$this->pager->setId("entreprise-pager")
			->setTemplateName("pager")
			->setNavClass("nav")
			->setListClass("pagination");
		
		// Ajoute le contrôle du champ pour réaffichage de la page courante après mise à jour
		$this->pager->supportField("frmEntrepriseSupport");
		// Ajoute le script pour la gestion du transfert de l'information dans le champ de support
		$this->clientRIA .= "
			$(\"#entreprise-pager ul.pagination li\").on(\"click\",function(ev){
					$(\"#frmEntrepriseSupport\").val($(this).children(\"a\").data(\"rel\"));
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
			$this->after();
	
			// Retourne à l'index de traitement courant
			$locationParams = array(
					"com" => "listeDossier",
					"id" => $this->tableId,
					"frmEntrepriseSupport" => \wp\Helpers\httpQueryHelper::get("frmEntrepriseSupport")
			);
			$location = \wp\Helpers\urlHelper::setAction($locationParams);
			#die("Redirection vers : " . $location);
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
	
	protected function beforeInsert(){}
	
	protected function beforeUpdate(){}
	
	protected function beforeDelete(){}
	
	protected function afterInsert(){}
	
	protected function afterUpdate(){}
	
	protected function afterDelete(){}
	
	/**
	 * Définit les champs du premier fieldset "forme"
	 * @param string $fieldset ID du fieldset à traiter
	 */
	private function defForme($fieldset){
		
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmRaisonSociale")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entraisonsociale")
		->setLabel("Nom ou raison sociale")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-12")
		->setValue($this->mapper->getObject()->entraisonsociale);
		
		$this->addToFieldset($field,$fieldset);

		$field = new \wp\formManager\Fields\text();
		$field->setId("frmSIRET")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entsiret")
		->setLabel("N° de SIRET")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-12")
		->toUpper()
		->setValue($this->mapper->getObject()->entsiret);
		
		$this->addToFieldset($field,$fieldset);
		
		// Téléphone fixe
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmEntTelFixe")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "enttelephone")
		->setLabel("Téléphone fixe")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setValue($this->mapper->getObject()->enttelephone);
		
		$this->addToFieldset($field,$fieldset);
		
		// Téléphone portable
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmEntTelPortable")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "enttelportable")
		->setLabel("Téléphone portable")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired($isRequired)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->isReadOnly($this->globalDisabled)
		->setValue($this->mapper->getObject()->enttelportable);
		
		$this->addToFieldset($field,$fieldset);
		
		// Email
		$field = new \wp\formManager\Fields\mail();
		$field->setId("frmEntMail")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entemail")
		->setLabel("Adresse e-mail")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-12")
		->setValue($this->mapper->getObject()->entemail);
		$this->addToFieldset($field,$fieldset);
		
		// Adresse Numéro
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmEntAdrNum")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entnumrue")
		->setLabel("N°")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->isReadOnly($this->globalDisabled)
		->setValue($this->mapper->getObject()->entnumrue);
		
		$this->addToFieldset($field,$fieldset);
		
		// Mapping sur paramBase avec code paramètre VOI
		$mapper = new \arcec\Mapper\paramVOIMapper();
		
		if($mapper->count() > 0){
			// Liste des types de voies
			$field = new \wp\formManager\Fields\popup();
			$field->setId("frmEntParamVOI")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entvoi")
			->setLabel("Type de voie")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
			->isDisabled($this->globalDisabled)
			->setValue($this->mapper->getObject()->entvoi)
			;
			$this->addToFieldset($field,$fieldset);
		}
		
		// Libellé voie 1
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmEntAdr1")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entadresse1")
		->setLabel("Libellé voie")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setValue($this->mapper->getObject()->entadresse1);
		
		$this->addToFieldset($field,$fieldset);
		
		// Libellé voie 2
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmEntAdr2")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entadresse2")
		->setLabel("Libellé voie (2)")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->isReadOnly($this->globalDisabled)
		->setValue($this->mapper->getObject()->entadresse2);
		
		$this->addToFieldset($field,$fieldset);
		
		// Code postal
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmEntCodePostal")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entcodepostal")
		->setLabel("Code Postal")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setValue($this->mapper->getObject()->entcodepostal);
		
		$this->addToFieldset($field,$fieldset);
		
		// Ville
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmEntVille")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entville")
		->setLabel("Ville")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setValue($this->mapper->getObject()->entville);
		
		$this->addToFieldset($field,$fieldset);
		

		
		// Mapping sur paramBase avec code paramètre FJU : Forme juridique
		$mapper = new \arcec\Mapper\paramFJUMapper();
		
		if($mapper->count() > 0){
			$field = new \wp\formManager\Fields\popup();
			
			$field->setId("frmParamFJU")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entfju")
			->setLabel("Forme juridique")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
			->setValue($this->mapper->getObject()->entfju)
			;
			
			$this->addToFieldset($field,$fieldset);
		}

		// Mapping sur paramBase avec code paramètre STS : Statuts Sociaux
		$mapper = new \arcec\Mapper\paramSTSMapper();
		
		if($mapper->count() > 0){
			$field = new \wp\formManager\Fields\popup();

			$field->setId("frmParamSTS")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entsts")
			->setLabel("Statut Social")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
			->setValue($this->mapper->getObject()->entsts)
			;
			$this->addToFieldset($field,$fieldset);
		}

		// Mapping sur paramBase avec code paramètre OPF : Options Fiscales
		$mapper = new \arcec\Mapper\paramOPFMapper();
		
		if($mapper->count() > 0){
			$field = new \wp\formManager\Fields\popup();
		
			$field->setId("frmParamOPF")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entopf")
			->setLabel("Option Fiscale")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
			->setValue($this->mapper->getObject()->entopf)
			;
			$this->addToFieldset($field,$fieldset);
		}

		// Mapping sur paramBase avec code paramètre REM : Rémunérations
		$mapper = new \arcec\Mapper\paramOPFMapper();
		
		if($mapper->count() > 0){
			$field = new \wp\formManager\Fields\popup();
		
			$field->setId("frmParamREM")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entrem")
			->setLabel("Rémunération")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
			->setValue($this->mapper->getObject()->entrem)
			;
			$this->addToFieldset($field,$fieldset);
		}

		// Adhérent Centre de Gestion
		$field = new \wp\formManager\Fields\checkbox();
		$field->setId("frmAdherentCtGestion")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entadherentctgestion")
		->setLabel("Adhérent Centre de Gestion")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setGroupCss("col-sm-6")
		->isChecked($this->mapper->getObject()->entadherentctgestion);
		
		$this->addToFieldset($field,$fieldset);
		
		// Dépendance entre boîte à cocher frmEntCreee et date frmEntDateCreation
		$dependency = new \wp\formManager\Dependencies\dependency();
		
		// Création de l'entreprise
		$field = new \wp\formManager\Fields\checkbox();
		$field->setId("frmEntCreee")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entcreee")
		->setLabel("Entreprise créée")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setGroupCss("col-sm-12")
		->isReadOnly($this->globalDisabled)
		->isChecked($this->mapper->getObject()->entcreee);
		
		$this->addToFieldset($field,$fieldset);
		
		$dependency->setMasterObject($field);
		
		// Date de création...
		$field = new \wp\formManager\Fields\datePicker();
		$field->setId("frmEntDateCreation")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entdatecreation")
		->setLabel("Entreprise créée le")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setTriggerId("entcreation")
		->isReadOnly($this->globalDisabled)
		->isDisabled($this->globalDisabled || $this->mapper->getObject()->entcreee == 0)
		->setRIAScript()
		->setValue($this->mapper->getObject()->entdatecreation);
			
		$this->addToFieldset($field,$fieldset);
			
		$this->clientRIA .= $field->getRIAScript();
		
		$dependency->setDependantObject($field);

		$dependency
		->setEvent("change")
		->ifContent("check")
		->addAction("enable");
		$this->addDependency($dependency);
				
		// Définit les classes spécifiques pour ce fieldset
		$object = $this->getFieldset($fieldset);
		$object->setLegend("Entreprise");
		
		// Détermine l'état de la page
		$activeState = false;
		if($currentDossier = \wp\Helpers\httpQueryHelper::get("frmEntrepriseSupport")){
			if($currentDossier == $fieldset){
				$activeState = true;
			}
		} else {
			$activeState = true; // Il s'agit de l'onglet à son état initial
		}
		$object->setCss(array("fieldset",$activeState ? "active" : "inactive"));
		
	}
	
	private function defSuivi($fieldset){

		// Suivi PC après AC et gestion des dépendances avec les champs associés
		$field = new \wp\formManager\Fields\checkbox();
		$field->setId("frmPCPostAC")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entsuivipcpostac")
		->setLabel("Suivi PC après AC")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setGroupCss("col-sm-6")
		->isChecked($this->mapper->getObject()->entadherentctgestion ? true : false);
		
		$this->addToFieldset($field,$fieldset);
		
		// Gestion de la dépendance avec la date à intégrer
		
		// Date du premier rendez-vous PC
		$field = new \wp\formManager\Fields\datePicker();
		$field->setId("frmDateRdVPC")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entdaterdvpc")
		->setLabel("Premier rendez-vous PC")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setTriggerId("rdvpc")
		->setMaxDate()
		->setRIAScript()
		->setValue($this->mapper->getObject()->entdaterdvpc);
		$this->addToFieldset($field,$fieldset);
		
		$this->clientRIA .= $field->getRIAScript();

		$field = new \wp\formManager\Fields\text();
		$field->setId("frmConvention")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entconvention")
		->setLabel("Convention")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-12")
		->setValue($this->mapper->getObject()->entconvention);

		$field = new \wp\formManager\Fields\text();
		$field->setId("frmNumConvention")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entnumconvention")
		->setLabel("N° de Convention")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-12")
		->setValue($this->mapper->getObject()->entnumconvention);
		
		// Gérer la dépendance date / numéro
		
		// Date de signature de la convention
		$field = new \wp\formManager\Fields\datePicker();
		$field->setId("frmDateConvention")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entdateconvention")
		->setLabel("Date de la convention")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setTriggerId("dateconvention")
		->setMaxDate()
		->setRIAScript()
		->setValue($this->mapper->getObject()->entdateconvention);
		$this->addToFieldset($field,$fieldset);

		// Nombre d'emplois à l'origine
		$field = new \wp\formManager\Fields\integer();
		$field->setId("frmNbEmploiInitial")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entnbemploiinitial")
		->setLabel("Nombre d'emplois initial")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setRIAScript()
		->setValue($this->mapper->getObject()->entnbemploiinitial ? $this->mapper->getObject()->entnbemploiinitial : 1);
		$this->addToFieldset($field,$fieldset);

		// Nombre d'emplois créés
		$field = new \wp\formManager\Fields\integer();
		$field->setId("frmNbEmploiCrees")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entnbemploicrees")
		->setLabel("Nombre d'emplois créés")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setRIAScript()
		->setValue($this->mapper->getObject()->entnbemploiinitial ? $this->mapper->getObject()->entnbemploicrees : 0);
		$this->addToFieldset($field,$fieldset);
		
		
		// Demande de l'entrepreneur
		$field = new \wp\formManager\Fields\textarea();
		$field->setId("frmDemandeEntrepreneur")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entdemandeentrepreneur")
		->setLabel("Demande de l'entrepreneur")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-12")
		->setValue($this->mapper->getObject()->entdemandeentrepreneur);
		$this->addToFieldset($field,$fieldset);
		
		// Demande de l'entrepreneur
		$field = new \wp\formManager\Fields\textarea();
		$field->setId("frmResumeMission")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entresumemission")
		->setLabel("Résumé de la mission")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-12")
		->setValue($this->mapper->getObject()->entresumemission);
		$this->addToFieldset($field,$fieldset);

		// Résultats obtenus
		$field = new \wp\formManager\Fields\textarea();
		$field->setId("frmResultatObtenu")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "entresultatobtenu")
		->setLabel("Résultats obtenus")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-12")
		->setValue($this->mapper->getObject()->entresultatobtenu);
		$this->addToFieldset($field,$fieldset);
		
		// Définit les classes spécifiques pour ce fieldset
		$object = $this->getFieldset($fieldset);
		$object->setLegend("Suivi");
		// Détermine l'état de la page
		$activeState = false;
		if($currentDossier = \wp\Helpers\httpQueryHelper::get("frmEntrepriseSupport")){
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