<?php
/**
 * @name defPorteur.class.php Formulaire de création de la fiche porteur
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Dossier
 * @version 1.0
**/
namespace arcec;

class defPorteur extends \wp\formManager\admin{
	
	/**
	 * Instancie un nouvel objet de création de dossier
	**/
	public function __construct(){
		$this->module = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
		
		$this->setId($this->module)
		->setName($this->module);
		
		//$this->setCss("form-inline");
		$this->setCss("container-fluid");
		
		$this->setTemplateName("admin.tpl");
		
		$this->mapper = new \arcec\Mapper\porteurMapper(\wp\Helpers\urlHelper::context("dossier"));

		\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Porteur de projet");
		
		$this->set();
		
		if(!$this->isValidate()){
			$this->setAction(array("com" => $this->module,"context"=>\wp\Helpers\urlHelper::toContext()));
		} else {
			$this->process();
		}
	}
	
	/**
	 * Composition du formulaire d'administration
	 * @see \wp\formManager\admin::set()
	 * @todo Ajouter un fieldset pour la gestion de l'adresse
	**/
	public function set(){
		
		// Identifiant de la table parente arc_dossier
		$field = new \wp\formManager\Fields\hidden();
		$parentMapper = $this->mapper->getSchemeDetail("dossier_id","mapper");
		$field->setId("frmDossierId")
			->setName($this->mapper->getTableName() . "." . "dossier_id")
			->setValue(\wp\Helpers\urlHelper::context("dossier"));
		$this->addToFieldset($field);
		
		// Date de naissance
		$field = new \wp\formManager\Fields\datePicker();
		$field->setId("frmDateNaissance")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "datenaissance")
		->setLabel("Date de naissance")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setTriggerId("naissance")
		->setMaxDate()
		->setRIAScript()
		->setValue($this->mapper->getObject()->datenaissance);
		
		$this->addToFieldset($field);
		
		$this->clientRIA .= $field->getRIAScript();
		
		// Sexe
		$field = new \wp\formManager\Fields\group();
		$field->setId("frmSexe")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "sexe")
		->setLabel("Sexe")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->setCss("panel")
		->setCss("panel-default")
		->setCss("col-sm-6")
		->setDefault($this->mapper->getObject()->sexe,"M");
		
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
		
		$this->addToFieldset($field);
		
		
		// Liste des nationalités
		$field = new \wp\formManager\Fields\popup();
		
		// Mapping sur paramBase avec code paramètre NAT
		$mapper = new \arcec\Mapper\paramNATMapper();
		
		$field->setId("frmParamNAT")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "nat")
		->setLabel("Nationalité")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-12")
		->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
		;
		
		$this->addToFieldset($field);

		// Liste des situations de familles
		$field = new \wp\formManager\Fields\popup();
		
		// Mapping sur paramBase avec code paramètre SIF
		$mapper = new \arcec\Mapper\paramSIFMapper();
		
		$field->setId("frmParamSIF")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "sif")
		->setLabel("Situation de famille")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
		;
		
		$this->addToFieldset($field);

		// Liste des régimes matrimoniaux
		$field = new \wp\formManager\Fields\popup();
		
		// Mapping sur paramBase avec code paramètre RIM
		$mapper = new \arcec\Mapper\paramRIMMapper();
		
		$field->setId("frmParamRIM")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "rim")
		->setLabel("Régime matrimonial")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->isDisabled()
		->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
		;
		
		$this->addToFieldset($field);
		
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
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "handicap")
		->setLabel("Travailleur handicapé")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setGroupCss("col-sm-6")
		->setValue($this->mapper->getObject()->handicap);
		
		$this->addToFieldset($field);
		
		// Penser à ajouter un fieldset pour l'adresse
		
		// Adresse Numéro
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmAdrNum")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "adrnum")
		->setLabel("N°")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setValue($this->mapper->getObject()->adrnum);
		
		$this->addToFieldset($field);		

		// Liste des types de voies
		$field = new \wp\formManager\Fields\popup();
		
		// Mapping sur paramBase avec code paramètre VOI
		$mapper = new \arcec\Mapper\paramVOIMapper();
		
		$field->setId("frmParamVOI")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "voi")
		->setLabel("Type de voie")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
		;
		
		$this->addToFieldset($field);
		
		// Libellé voie 1
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmAdr1")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "adr1")
		->setLabel("Libellé voie")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setValue($this->mapper->getObject()->adr1);
		
		$this->addToFieldset($field);
		
		// Libellé voie 2
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmAdr2")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "adr2")
		->setLabel("Libellé voie (2)")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setValue($this->mapper->getObject()->adr2);
		
		$this->addToFieldset($field);

		// Code postal
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmCodePostal")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "codepostal")
		->setLabel("Code Postal")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setValue($this->mapper->getObject()->codepostal);
		
		$this->addToFieldset($field);

		// Ville
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmVille")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "ville")
		->setLabel("Ville")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setValue($this->mapper->getObject()->ville);
		
		$this->addToFieldset($field);
		
		// Téléphone fixe
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmTelFixe")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "telfixe")
		->setLabel("Téléphone fixe")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setValue($this->mapper->getObject()->telfixe);
		
		$this->addToFieldset($field);

		// Téléphone portable
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmTelPortable")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "telportable")
		->setLabel("Téléphone portable")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setValue($this->mapper->getObject()->telportable);
		
		$this->addToFieldset($field);
		
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
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "email")
		->setLabel("Adresse e-mail")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->setCss("form-control")
		->setGroupCss("col-sm-12")
		->setValue($this->mapper->getObject()->email);
		
		$this->addToFieldset($field);

		// Liste des situations sociales
		$field = new \wp\formManager\Fields\popup();
		
		// Mapping sur paramBase avec code paramètre SIS
		$mapper = new \arcec\Mapper\paramSISMapper();
		
		$field->setId("frmParamSIS")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "sis")
		->setLabel("Situation sociale")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
		;
		
		$this->addToFieldset($field);

		// Date d'inscription Pôle Emploi
		$field = new \wp\formManager\Fields\datePicker();
		$field->setId("frmInscriptionPoleEmploi")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "dateinscpoleemploi")
		->setLabel("Inscription Pôle Emploi")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isDisabled(true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setTriggerId("poleemploi")
		->setRIAScript()
		->setValue($this->mapper->getObject()->dateinscpoleemploi);
		
		$this->addToFieldset($field);
		
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
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "rin")
			->setLabel("Régime d'indemnisation")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
			;
			
			$this->addToFieldset($field);
		}
		// Liste des contrats de travail : dépendance avec frmParamSIS
		$field = new \wp\formManager\Fields\popup();
		
		// Mapping sur paramBase avec code paramètre CNT
		$mapper = new \arcec\Mapper\paramCNTMapper();
		
		$field->setId("frmParamCNT")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "cnt")
		->setLabel("Contrat de travail")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->isDisabled(true)
		->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
		;
		
		$this->addToFieldset($field);
		
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
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "act")
			->setLabel("Ancienneté du contrat de travail")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
			;
			
			$this->addToFieldset($field);
		}
		
		// Mapping sur paramBase avec code paramètre TYP
		$mapper = new \arcec\Mapper\paramTYPMapper();
		$field = new \wp\formManager\Fields\popup();
		$field->setId("frmParamTYP")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "typ")
		->setLabel("Catégorie de projet")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
		;
		
		$this->addToFieldset($field);
		
		
		// Ajoute le plug-in JS pour la gestion des dates
		$js = new \wp\htmlManager\js();
		$js->addPlugin("jquery.plugin",true);
		$js->addPlugin("jquery.datepick",true);
		$js->addPlugin("jquery.datepick-fr");
		
		\wp\Tpl\templateEngine::getEngine()->addContent("js",$js);
		
		// Ajoute le script d'ouverture du datepicker
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
					"com" => "setEtude",
					"context" => "UPDATE",
					"porteur" => $this->tableId,
					"dossier" => $_POST[$this->mapper->getTableName() . "_" . "dossier_id"]
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
	}
	
	protected function beforeUpdate(){}
	
	protected function beforeDelete(){}
	
	protected function afterInsert(){}
	
	protected function afterUpdate(){}
	
	protected function afterDelete(){}
}
?>