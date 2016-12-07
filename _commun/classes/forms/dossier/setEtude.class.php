<?php
/**
 * @name setEtude.class.php Formulaire de création de la fiche porteur : niveau d'études et prescripteur
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Dossier
 * @version 1.0
**/
namespace arcec;

class setEtude extends \wp\formManager\admin{
	
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
		
		\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Porteur de projet : niveau d'étude");
		
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
		if(\wp\Helpers\urlHelper::context() == "UPDATE" || \wp\Helpers\urlHelper::context() == "DELETE"){
			
			$this->mapper->setId(\wp\Helpers\urlHelper::context("porteur"));
			$this->mapper->set($this->mapper->getNameSpace());
				
			// Crée le champ caché pour le stockage de la clé primaire
			$field = new \wp\formManager\Fields\hidden();
			$field->setId($this->mapper->getTableName() . ".primary")
			->setName($this->mapper->getTableName() . ".primary")
			->setValue(\wp\Helpers\urlHelper::context("porteur"));
			$this->addToFieldset($field);
		
		}
		
		
		// Liste des niveaux d'études
		// Mapping sur paramBase avec code paramètre ETU
		$mapper = new \arcec\Mapper\paramETUMapper();
		if($mapper->getNbRows() > 0){
			$field = new \wp\formManager\Fields\popup();
			
			$field->setId("frmParamETU")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "etu")
			->setLabel("Niveau d'études")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-12")
			->setForceHeaderStatut(true)
			->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
			;
			
			$this->addToFieldset($field);
		}
		
		// Liste des diplômes
		// Mapping sur paramBase avec code paramètre DPL
		$mapper = new \arcec\Mapper\paramDPLMapper();
		
		if($mapper->getNbRows() > 0){
			$field = new \wp\formManager\Fields\popup();
			
	
			$field->setId("frmParamDPL")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "dpl")
			->setLabel("Diplômes")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->setForceHeaderStatut(true)
			->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
			;
			
			$this->addToFieldset($field);
	
			// Spécialité
			$field = new \wp\formManager\Fields\text();
			$field->setId("frmSpecialite")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "specdiplome")
			->setLabel("Spécialité")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->isRequired()
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->isDisabled(true)
			->setValue($this->mapper->getObject()->specdiplome);
			
			$this->addToFieldset($field);
			
			// Ajouter la dépendance entre les deux champs
		}
		
		// Expérience professionnelle
		$field = new \wp\formManager\Fields\textarea();
		$field->setId("frmExperience")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "exppro")
		->setLabel("Expérience professionnelle")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setValue($this->mapper->getObject()->exppro);
		
		$this->addToFieldset($field);
		
		// Liste des prescripteurs
		$field = new \wp\formManager\Fields\popup();
		
		// Mapping sur paramBase avec code paramètre PRS
		$mapper = new \arcec\Mapper\paramPRSMapper();
		
		$field->setId("frmParamPRS")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "prs")
		->setLabel("Prescripteur")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setForceHeaderStatut(true)
		->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
		;
		
		$this->addToFieldset($field);
		
		// Nom du prescripteur
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmNomPresc")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "nompresc")
		->setLabel("Nom de l'interlocuteur")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setValue($this->mapper->getObject()->nompresc);
		
		$this->addToFieldset($field);

		// Nom du prescripteur
		$field = new \wp\formManager\Fields\mail();
		$field->setId("frmMailPresc")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "emailpresc")
		->setLabel("E-Mail")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setValue($this->mapper->getObject()->emailpresc);
		
		$this->addToFieldset($field);
		
		
		// Résumé du projet
		$field = new \wp\formManager\Fields\textarea();
		$field->setId("frmResume")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "resumeprojet")
		->setLabel("Expérience professionnelle")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setValue($this->mapper->getObject()->resumeprojet);
		
		$this->addToFieldset($field);
		
		// Ajoute le script d'ouverture du datepicker
		$this->toControls();
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
					"dossier" => $this->tableId,
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
		return;
	}
	
	protected function beforeUpdate(){}
	
	protected function beforeDelete(){}
	
	protected function afterInsert(){}
	
	protected function afterUpdate(){}
	
	protected function afterDelete(){}
}
?>