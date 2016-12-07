<?php
/**
 * @name setRapport.class.php Formulaire de mise à jour d'un rapport d'entretien
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Dossier
 * @version 1.0.1
**/
namespace arcec;

class setRapport extends \wp\formManager\admin{
	
	/**
	 * Identifiant du rapport
	 * @var int
	**/
	private $id;
	
	/**
	 * Identifiant du dossier de référence
	 * @var int
	 */
	private $dossierId;
	
	/**
	 * Onglet cible du retour vers le suivi des dossiers
	 * @var string
	 */
	private $targetTab;
	
	/**
	 * Instancie un nouvel objet de création de dossier
	**/
	public function __construct(){
		$this->module = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
		
		$this->setId($this->module)
		->setName($this->module);
		
		$this->setCss("form-inline");
		$this->setCss("container-fluid");
		
		$this->setTemplateName("dossier/adminHeader.tpl");
		
		$this->mapper = new \arcec\Mapper\rapportMapper();

		\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Mettre à jour");
		
		$this->id = \wp\Helpers\urlHelper::context("id");
		$this->mapper->setId($this->id);
		$this->mapper->set($this->mapper->getNameSpace());
		
		$this->dossierId = $this->mapper->getObject()->dossier_id;
		$this->setDossierHeader();
		
		$this->targetTab = "rapports";
		
		$this->set();
		
		if(!$this->isValidate()){
			$this->setAction(array("com" => $this->module,"context"=>\wp\Helpers\urlHelper::toContext()));
		} else {
			$this->process();
		}
	}
	
	private function setDossierHeader(){
		$this->dossier = new \arcec\dossierHeader($this->dossierId);
		$this->dossier->setTemplateName("completeHeader");
	}
	
	public function getDossier(){
		return $this->dossier;
	}

	/**
	 * Surcharge de la méthode pour retourner vers le formulaire de mise à jour des dossiers
	 * @see \wp\formManager\admin::getCancelBtn()
	 */
	public function getCancelBtn(){
		$button = new \wp\formManager\Fields\linkButton();
		$button->setId("btnCancel")
		->setTitle("Gestion des dossiers")
		->addAttribut("role","button")
		->setValue("./index.php?com=suiviDossier&context=UPDATE&id=" . $this->dossierId . "&tab=" . $this->targetTab)
		->setCss("btn")
		->setCss("btn-default")
		->setLabel("Retour")
		;
		return $button;
	}
	
	protected function set(){

		// Crée le champ caché pour le stockage de la clé primaire
		$field = new \wp\formManager\Fields\hidden();
		$field->setId($this->mapper->getTableName() . ".primary")
			->setName($this->mapper->getTableName() . ".primary")
			->setValue($this->id);
		$this->addToFieldset($field);
		
		// Crée le champ caché pour le stockage de la clé primaire
		$field = new \wp\formManager\Fields\hidden();
		$field->setId("frmDossierParent")
		->setName($this->mapper->getTableName() .  ".dossier_id")
		->setValue($this->dossierId);
		$this->addToFieldset($field);
		
		
		$field = new \wp\formManager\Fields\hourMinute();
		$field->setId("frmDureeEntretien")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "duree")
		->setLabel("Durée")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setSeparator(":")
		->setRIAScript()
		->isDisabled()
		->setValue($this->mapper->getObject()->duree);
			
		$this->addToFieldset($field);
			
		$this->clientRIA .= $field->getRIAScript();

		// Mapping sur paramBase avec code paramètre ACU
		$mapper = new \arcec\Mapper\paramACUMapper();
		
		if($mapper->count() > 0){
			$field = new \wp\formManager\Fields\popup();
		
			$field->setId("frmParamACU")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "acu")
			->setLabel("Lieu d'accueil")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			//->isDisabled()
			->isRequired()
			->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
			->setValue($this->mapper->getObject()->acu)
			;
			$this->addToFieldset($field);
		}
		
		// Mapping sur paramBase avec code paramètre CNS
		$mapper = new \arcec\Mapper\paramCNSMapper();
		
		if($mapper->count() > 0){
			// Liste des conseillers => paramètres CNS
			$field = new \wp\formManager\Fields\popup();
			
			$field->setId("frmParamCNS")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "cns")
			->setLabel("Conseiller lors de l'entretien")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-12")
			->isDisabled()
			->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
			->setValue($this->mapper->getObject()->cns)
			;
			
			$this->addToFieldset($field);
		}

		$field = new \wp\formManager\Fields\datePicker();
		$field->setId("frmDateEntretien")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "date")
		->setLabel("Date de l'entretien")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setTriggerId("entretien")
		->setRIAScript()
		->isDisabled()
		->setValue($this->mapper->getObject()->date);
			
		$this->addToFieldset($field);
			
		$this->clientRIA .= $field->getRIAScript();
		
		// Ajouter champ statique d'affichage de la phase
		
		// Contenu
		$field = new \wp\formManager\Fields\textarea();
		$field->setId("frmContenu")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "contenu")
		->setLabel("Rapport")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-12")
		->isRequired()
		->setValue($this->mapper->getObject()->contenu);
		
		$this->addToFieldset($field);
		
		// Ajoute le plug-in JS pour la gestion des dates
		$js = new \wp\htmlManager\js();
		$js->addPlugin("jquery.plugin",true);
		$js->addPlugin("jquery.datepick",true);
		$js->addPlugin("jquery.datepick-fr");
		$js->addPlugin("jquery.maskedinput",true);
		
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
			
			// Récupère l'identifiant
			$this->dossierId = \wp\Helpers\httpQueryHelper::get($this->mapper->getTableName() .  "_dossier_id");
			
			// Retourne au suivi des dossiers
			$locationParams = array(
				"com" => "suiviDossier",
				"context" => "UPDATE",
				"id" => $this->dossierId,
				"tab" => $this->targetTab
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
	}
	
	protected function beforeUpdate(){}
	
	protected function beforeDelete(){}
	
	protected function afterInsert(){
		// Vérifier le statut de l'envoi du mail...
		
		// Procéder à l'envoi proprement dit...
		
	}
	
	protected function afterUpdate(){}
	
	protected function afterDelete(){}
}
?>