<?php
/**
 * @name setAccompagnement.class.php Définition du formulaire de gestion des données d'accompagnement
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Dossier
 * @version 1.0
 **/
namespace arcec;

class setAccompagnement extends \wp\formManager\admin{
	
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
	 * Instancie un nouvel objet de gestion de porteur
	 **/
	public function __construct($isSubForm){
		$this->module = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
	
		$this->setId($this->module)
		->setName($this->module);
	
		//$this->setCss("form-inline");
		$this->setCss("container-fluid");
	
		$this->setTemplateName("admin.tpl");
		
		$this->id = \wp\Helpers\urlHelper::context("id");
		
		$this->mapper = new \arcec\Mapper\dossierMapper();
		
		$this->globalDisabled = false;
		
		$this->toggleSubForm($isSubForm);
		
		$this->set();
		
		\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Dossier");
		\wp\Tpl\templateEngine::getEngine()->setVar("index", null);
		
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
				
			$this->addToFieldset($field);
				
			// Gestion du script de chargement de la taxonomie éventuellement associée au changement
			$this->clientRIA .= $this->addTaxonomie($field,$mapper,"paramEDO","frmParamCA",$this->mapper->getObject()->edo);
		}
		
		if($this->globalDisabled){
			// Ajoute les champs pour l'affichage des compléments si le paramètre "ca" est défini
			$paramTaxonomy = new \arcec\paramTaxonomy();
			$paramTaxonomy->id($this->mapper->getObject()->ca)
				->ancestor($this->mapper->getObject()->edo)
				->home("Motif de l'arrêt");
			
			$field = new \wp\formManager\Fields\staticText();
			$field->setId("frmMotifArret")
				->setName("motifArret")
				->setGroupCss("col-sm-6")
				
				->setValue($paramTaxonomy->toBreadCrumb())
			;
			$this->addToFieldset($field);
		}
		
		// Champ caché pour la gestion de l'état du dossier
		$field = new \wp\formManager\Fields\hidden();
		$field->setId("frmParamCA")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "ca")
		->setValue($this->mapper->getObject()->ca);
		$this->addToFieldset($field,$fieldset);
		
		
		// Date d'adhésion
		$field = new \wp\formManager\Fields\datePicker();
			$field->setId("frmDateAdhesion")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "dateadhesion")
			->setLabel("Date d'adhésion à l'ARCEC")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setGroupCss("col-sm-6")
			->isReadOnly(true)
			->isDisabled(true)
			->setValue($this->mapper->getObject()->dateadhesion);
			
		$this->addToFieldset($field);
		$this->clientRIA .= $field->getRIAScript();
		
		$field = new \wp\formManager\Fields\datePicker();
		$field->setId("frmDateCotisation")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "datecotisation")
		->setLabel("Date de cotisation")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setTriggerId("cotisation")
		->isReadOnly($this->globalDisabled)
		->setRIAScript()
		->setValue($this->mapper->getObject()->datecotisation);
			
		$this->addToFieldset($field);
		$this->clientRIA .= $field->getRIAScript();

		// Situation sociale particulière
		$field = new \wp\formManager\Fields\textarea();
		$field->setId("frmSitSocialeParticuliere")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteursitsocialeparticuliere")
		->setLabel("Situation sociale particulière")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-12")
		->isReadOnly($this->globalDisabled)
		->isDisabled($this->globalDisabled)
		->setValue($this->mapper->getObject()->porteursitsocialeparticuliere);	

		$this->addToFieldset($field);
		
		
		// Mapping sur paramcomment avec code paramètre T03
		$mapper = new \arcec\Mapper\paramT03Mapper();
		
		if($mapper->count() > 0){
			$field = new \wp\formManager\Fields\popup();
				
			$field->setId("frmParamT03")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteureligibleaccre")
			->setLabel("Eligible ACCRE")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->isReadOnly($this->globalDisabled)
			->isDisabled($this->globalDisabled)
			->setMapping($mapper,array("value" => "id", "content"=>"message"))
			->setValue($this->mapper->getObject()->porteureligibleaccre)
			;
				
			$this->addToFieldset($field);
			
			// Boîte à cocher associée
			$field = new \wp\formManager\Fields\checkbox();
			$field->setId("frmACCRE")
			->setName("ACCRE")
			->setLabel("Oui/Non")
			->setCss("control-label",true)
			->setCss("col-sm-6",true)
			->setGroupCss("col-sm-6")
			->isDisabled(true)
			->isChecked($this->mapper->getObject()->porteureligibleaccre ? true : false);
			
			$this->addToFieldset($field);
			
			// Définir la relation de dépendance entre frmParamT01 et frmACCRE : coché si T03 <> 0
			$dependency = new \wp\formManager\Dependencies\dependency();
			$dependency->setMasterField("frmParamT03")
			->setType("popup")
			->setDependantField("frmACCRE")
			->setEvent("change")
			->ifNotContent(0)
			->addAction("check");
			$this->addDependency($dependency);
		}
		
		
		// Mapping sur paramcomment avec code paramètre T01
		$mapper = new \arcec\Mapper\paramT01Mapper();
		
		if($mapper->count() > 0){
			$field = new \wp\formManager\Fields\popup();
			
			$field->setId("frmParamT01")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteureligiblenacre")
			->setLabel("Eligible NACRE")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->isReadOnly($this->globalDisabled)
			->isDisabled($this->globalDisabled)
			->setMapping($mapper,array("value" => "id", "content"=>"message"))
			->setValue($this->mapper->getObject()->porteureligiblenacre)
			;
			
			$this->addToFieldset($field);
			

			// Boîte à cocher associée
			$field = new \wp\formManager\Fields\checkbox();
			$field->setId("frmNACRE")
			->setName("NACRE")
			->setLabel("Oui/Non")
			->setCss("control-label",true)
			->setCss("col-sm-6",true)
			->setGroupCss("col-sm-6")
			->isDisabled(true)
			->isChecked($this->mapper->getObject()->porteureligiblenacre ? true : false);
				
			$this->addToFieldset($field);
				
			// Définir la relation de dépendance entre frmParamT01 et frmNACRE : coché si T01 <> 0
			$dependency = new \wp\formManager\Dependencies\dependency();
			$dependency->setMasterField("frmParamT01")
			->setType("popup")
			->setDependantField("frmNACRE")
			->setEvent("change")
			->ifNotContent(0)
			->addAction("check");
			$this->addDependency($dependency);
			
			// Date de transmission si NACRE défini...
			$field = new \wp\formManager\Fields\datePicker();
			$field->setId("frmDateTransmissionNACRE")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurdatetransmissionnacre")
			->setLabel("Date de transmission du dossier NACRE")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->setTriggerId("transNACRE")
			->isReadOnly($this->globalDisabled)
			->setRIAScript()
			->isDisabled($this->globalDisabled || $this->mapper->getObject()->porteureligiblenacre ? true : false)
			->setValue($this->mapper->getObject()->porteurdatetransmissionnacre);
				
			$this->addToFieldset($field);
			$this->clientRIA .= $field->getRIAScript();
			
			// Définir la relation de dépendance entre frmParamT01 et frmDateTransmissionNCRE : coché si T01 <> 0
			$dependency = new \wp\formManager\Dependencies\dependency();
			$dependency->setMasterField("frmParamT01")
			->setType("popup")
			->setDependantField("frmDateTransmissionNACRE")
			->setEvent("change")
			->ifNotContent(0)
			->addAction("enable");
			$this->addDependency($dependency);
			
			// Champ libre Destinaire de la transmission NACRE
			$field = new \wp\formManager\Fields\text();
			$field->setId("frmDestNACRE")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurdesttransmissionnacre")
			->setLabel("Destinataire du dossier NACRE")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->isReadOnly($this->globalDisabled)
			->isDisabled($this->globalDisabled || $this->mapper->getObject()->porteureligiblenacre ? true : false)
			->setValue($this->mapper->getObject()->porteurdesttransmissionnacre);
			
			$this->addToFieldset($field);
			
			// Définir la relation de dépendance entre frmParamT01 et frmDateTransmissionNCRE : coché si T01 <> 0
			$dependency = new \wp\formManager\Dependencies\dependency();
			$dependency->setMasterField("frmParamT01")
			->setType("popup")
			->setDependantField("frmDestNACRE")
			->setEvent("change")
			->ifNotContent(0)
			->addAction("enable");
			$this->addDependency($dependency);
		}
		
		
		// Gestion de l'éligibilité FONGECIF : seulement si salarié et non interimaire
		if(is_null($this->mapper->getObject()->porteureligiblefongecif)){
			if(is_null($this->mapper->getObject()->porteurdatetransfongecif)){
				$eligible = 0;
			} else {
				$eligible = 1;
			}
		} else {
			$eligible = $this->mapper->getObject()->porteureligiblefongecif;
		}
		
		// Champ caché pour la gestion de l'état du dossier
		$field = new \wp\formManager\Fields\hidden();
		$field->setId("frmEligibleFONGECIF")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteureligiblefongecif")
		->setValue($eligible);
		$this->addToFieldset($field);		

		// Date de transmission FONGECIF...
		$field = new \wp\formManager\Fields\datePicker();
		$field->setId("frmDateTransmissionFONGECIF")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurdatetransfongecif")
		->setLabel("Dossier FONGECIF transmis le")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setTriggerId("transNACRE")
		->isReadOnly($this->globalDisabled)
		->setRIAScript()
		->isDisabled($this->globalDisabled)
		->setValue($this->mapper->getObject()->porteurdatetransfongecif);
		
		$this->addToFieldset($field);
		$this->clientRIA .= $field->getRIAScript();

		// Champ libre Destinaire de la transmission FONGECIF
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmDestFONGECIF")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurdestdossierfongecif")
		->setLabel("au destinataire...")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->isReadOnly($this->globalDisabled)
		->isDisabled($this->globalDisabled || $this->mapper->getObject()->porteureligiblefongecif ? true : false)
		->setValue($this->mapper->getObject()->porteurdestdossierfongecif);
			
		$this->addToFieldset($field);
		
		// Mapping sur parambase avec code paramètre SCP
		$mapper = new \arcec\Mapper\paramSCPMapper();
		
		if($mapper->count() > 0){
			$field = new \wp\formManager\Fields\popup();
				
			$field->setId("frmParamSCP")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurscp")
			->setLabel("Secteur Professionnel")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-12")
			->isReadOnly($this->globalDisabled)
			->isDisabled($this->globalDisabled ? true : false)
			->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
			->setValue($this->mapper->getObject()->porteurscp)
			;
				
			$this->addToFieldset($field);
		}

		// Mapping sur parambase avec code paramètre MET
		$mapper = new \arcec\Mapper\paramMETMapper();
		
		if($mapper->count() > 0){
			$field = new \wp\formManager\Fields\popup();
		
			$field->setId("frmParamMET")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurmet")
			->setLabel("Métier APCE")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->isReadOnly($this->globalDisabled)
			->isDisabled($this->globalDisabled)
			->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
			->setValue($this->mapper->getObject()->porteurmet)
			;
		
			$this->addToFieldset($field);
		}
		
		// Champ libre Autre Métier
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmAutreMetier")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurautremetier")
		->setLabel("Autre métier")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->isReadOnly($this->globalDisabled)
		->isDisabled($this->globalDisabled)
		->setValue($this->mapper->getObject()->porteurautremetier);
		
		$this->addToFieldset($field);		
		
		// Ajouter le script de contrainte sur la saisie d'un métier devant réinitialiser la liste des métiers
		$this->clientRIA .= "
			$(\"#" . $field->getId() . "\").on(\"keyup\",function(){
					var value=$(this).val();
					if(value != \"\"){
						// Si la valeur n'est pas vide, on réinitialise la liste des métiers
						var selectedVal = $(\"#frmParamMET option:selected\").val();
						console.log(\"Valeur sélectionnée : \" + selectedVal);
						if($(\"#frmParamMET option:selected\").val() != 0){
							$(\"#frmParamMET\").removeProp(\"selected\");
							var opt = $(\"#frmParamMET[val=0]\");
							opt.attr(\"selected\",\"selected\");
							var liste = $(\"#frmParamMET\");
							document.getElementById(\"frmParamMET\").options.selectedIndex = 0;
						}
					}
				}
			);
		";

		// Liste des programmes, pour savoir si le projet est en cours...
		// Mapping sur parambase avec code paramètre MET
		$mapper = new \arcec\Mapper\paramPRGMapper();
		
		if($mapper->count() > 0){
			$field = new \wp\formManager\Fields\popup();
		
			$field->setId("frmParamPRG")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "porteurprg")
			->setLabel("Programme d'accompagnement")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-12")
			->isReadOnly($this->globalDisabled)
			->isDisabled($this->globalDisabled)
			->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
			->setValue($this->mapper->getObject()->porteurprg)
			;
		
			$this->addToFieldset($field);
		}
				
		// Ajoute le plug-in JS pour la gestion des dates
		$js = new \wp\htmlManager\js();
		$js->addPlugin("jquery.plugin",true);
		$js->addPlugin("jquery.datepick",true);
		$js->addPlugin("jquery.datepick-fr");
		
		\wp\Tpl\templateEngine::getEngine()->addContent("js",$js);
		
		
		$this->toControls();
		
		// Ajoute les CSS
		$css = new \wp\htmlManager\css();
		$css->addSheet("jquery.datepick");
		
		\wp\Tpl\templateEngine::getEngine()->addContent("css",$css);
		
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
					"id" => $this->tableId
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
	
	protected function beforeInsert(){
		$this->mapper->datecreation = \wp\Helpers\dateHelper::toSQL(\wp\Helpers\dateHelper::today(),"yyyy-mm-dd");
		
		$edo = new \arcec\Mapper\paramEDOMapper();
		$edo->searchBy("libellecourt", "ENC");
		$edo->set($edo->getNameSpace());
		
		$this->mapper->edo = $edo->getObject()->id;
	}
	
	protected function beforeUpdate(){}
	
	protected function beforeDelete(){}
	
	protected function afterInsert(){}
	
	protected function afterUpdate(){}
	
	protected function afterDelete(){}

	/*
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
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->toUpper()
		->isReadOnly($this->globalDisabled)
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
		->isReadOnly($this->globalDisabled)
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
		->setGroupCss("col-sm-6")
		->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
		->isDisabled($this->globalDisabled)
		->setValue($this->mapper->getObject()->etd)
		;
		
		$this->addToFieldset($field,$fieldset);
		

		
		$field = new \wp\formManager\Fields\datePicker();
		$field->setId("frmDateAdhesion")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "dateadhesion")
		->setLabel("Date d'adhésion à l'ARCEC")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setTriggerId("adhesion")
		->setRIAScript()
		->setValue($this->mapper->getObject()->dateadhesion);
		
		$this->addToFieldset($field,$fieldset);
		
		$this->clientRIA .= $field->getRIAScript();
		
		$field = new \wp\formManager\Fields\datePicker();
		$field->setId("frmDateCotisation")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "datecotisation")
		->setLabel("Date de cotisation")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setTriggerId("cotisation")
		->setRIAScript()
		->isReadOnly($this->globalDisabled)
		->setValue($this->mapper->getObject()->datecotisation);
		
		$this->addToFieldset($field,$fieldset);
		$this->clientRIA .= $field->getRIAScript();
		
		// Définit les classes spécifiques pour ce fieldset
		$object = $this->getFieldset($fieldset);
		$object->setLegend("Dossier");
		$object->setCss(array("fieldset","active"));
		
		
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
		->isDisabled()
		->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
		->isDisabled($this->globalDisabled)
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
		->setGroupCss("col-sm-6")
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
		->isDisabled(true)
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
		->setEvent("change")
		->ifContent("DE")
		->addAction("enable");
		$this->addDependency($dependency);
		
		// Liste des régimes d'indemnisation
		
		
		// Mapping sur paramBase avec code paramètre RIN
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
		->isDisabled($this->globalDisabled)
		;
		$this->addToFieldset($field,$fieldset);
		
		// Définir la relation de dépendance entre frmParamSIS et frmParamCNT : actif seulement si SIS est Salarié
		$dependency = new \wp\formManager\Dependencies\dependency();
		$dependency->setMasterField("frmParamSIS")
		->setType("popup")
		->setDependantField("frmParamCNT")
		->setEvent("change")
		->ifContent("SA")
		->addAction("enable");
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
		$object->setCss(array("fieldset","inactive"));
		
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
		->setCss("form-control")
		->setGroupCss("col-sm-6")
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
		$object->setCss(array("fieldset","inactive"));
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
			->isDisabled(true)
			->setValue($this->mapper->getObject()->porteurspecdiplome);
			$this->addToFieldset($field,$fieldset);
				
			// Ajouter la dépendance entre les deux champs
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
		$object->setCss(array("fieldset","inactive"));
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
		$object->setCss(array("fieldset","inactive"));
	}
	*/
	
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
									console.log(\"Ouverture de la boîte modale\");
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
									console.log(\"Rétablissement des saisies dans les champs...\");
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