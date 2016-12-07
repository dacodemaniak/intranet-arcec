<?php
/**
 * @name addDocument.class.php Formulaire d'ajout d'un document attaché à un dossier
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Dossier
 * @version 1.0
**/
namespace arcec;

class addDocument extends \wp\formManager\admin implements \wp\formManager\externalField{
	
	/**
	 * Identifiant du dossier de référence
	 * @var int
	 */
	private $dossierId;
	
	/**
	 * Objet associé à un noeud de l'arborescence des documents
	 * @var object
	**/
	private $nodeField;

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
		->setName($this->module)
		->setEncType("file")
		->setCss("form-inline")
		->setCss("container-fluid");
		
		$this->setTemplateName("dossier/adminHeader.tpl");
		
		$this->mapper = new \arcec\Mapper\docporteurMapper();

		\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Nouveau document");
		
		$this->dossierId = !is_null(\wp\Helpers\urlHelper::context("id")) ? \wp\Helpers\urlHelper::context("id") : \wp\Helpers\httpQueryHelper::get($this->mapper->getTableName() .  "_dossier_id");
		
		$this->setDossierHeader();
		
		$this->targetTab = "documents";
		
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
	
	/**
	 * Définit ou retourne le champ associé au noeud de l'arborescence
	 * @see \wp\formManager\form::getField()
	 */
	public function getField($id=null,$name=null,$value=null){
		if(!is_null($id)){
			$this->nodeField = $this->setNodeField($id,$name,$value);
		}
		
		return $this->nodeField;
	}
	
	/**
	 * Définit le champ associé au noeud de l'arborescence
	 * @param unknown_type $params
	**/
	private function setNodeField($id,$name,$value=null){
		if(is_null($value)){
			$value = $id;
		}
		
		$field = new \wp\formManager\Fields\radio();
		$field->setId("frmDossierCible_" . $id)
			->setName($name)
			->isRequired(true)
			->setValue($value);
		
		return $field;
			
	}
	
	public function set(){

		// Crée le champ caché pour le stockage de la clé du dossier
		$field = new \wp\formManager\Fields\hidden();
		$field->setId("frmDossierParent")
		->setName($this->mapper->getTableName() .  ".dossier_id")
		->setValue($this->dossierId);
		$this->addToFieldset($field);
		
		$field = new \wp\formManager\Fields\arbo();
		$field->setId("frmDossierCible")
		->setName($this->mapper->getTableName() . "." . "arbofichier_id")
		->caption("Dossier")
		->CSSId("dossier-cible")
		->isRequired()
		->setClass("tree-node-field")
		->setGroupCss("col-sm-12")
		->content(array("codification","titre"))
		->source(new \arcec\Mapper\arbofichierMapper())
		->parentForm($this)
		->isRequired(true)
		->setRIAScript();
			
		$this->addToFieldset($field);
			
		$this->clientRIA .= $field->getRIAScript();

		
		// Fichier lui-même
		$field = new \wp\formManager\Fields\upload();
		$field->setId("frmDocPorteur")
			//->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "nomoriginal")
			->setName("docPorteur")
			->setLabel("Document")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->isRequired(true)
			->setGroupCss("col-sm-12")
			->setRIAScript();
		
		$this->addToFieldset($field);
		
		$this->clientRIA .= $field->getRIAScript();
		
		// Description
		$field = new \wp\formManager\Fields\textarea();
		$field->setId("frmDescription")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "description")
		->setLabel("Description")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-12");
		
		$this->addToFieldset($field);
		
		// Ajoute le plug-in JS pour la gestion des dates
		$js = new \wp\htmlManager\js();
		$js->addPlugin("jquery.plugin",true);
		$js->addPlugin("jquery.datepick",true);
		$js->addPlugin("jquery.datepick-fr");
		$js->addPlugin("jquery.maskedinput",true);
		
		\wp\Tpl\templateEngine::getEngine()->addContent("js",$js);
		
		// Désactive le bouton d'ajout
		$this->clientRIA .= "
			$(\"#btnSubmit\").prop(\"disabled\",\"disabled\");
		";
		
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
				"com" => "addDocument",
				"context" => "INSERT",
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
		// Contrôle de validité du type de doucment envoyé
		$uploadFile = parent::getField("frmDocPorteur")->getPostedData();
		if(!is_null($uploadFile)){
			if($uploadFile->check()){
				// Recalcul du nom du document final : hiérarchie du dossier + date du jour + indice + extension
				$taxonomy = new \wp\dbManager\tableTaxonomy();
				$taxonomy->mapper(new \arcec\Mapper\arbofichierMapper())
					->columns("codification")
						->id(parent::getField("frmDossierCible")->getPostedData())
						->separator("-")
						->process();
				// Récupération du nom du document à traiter
				$mimes = new \wp\dbManager\Mapper\mimetypeMapper();
				$mimes->searchBy("type",$uploadFile->mimeType());
				$mimes->set($mimes->getNameSpace());
				
				$uploadFile->repository(\wp\framework::getFramework()->getAppRoot() . "/_repository/");
				$this->mapper->nomcalcule = $uploadFile->indice($taxonomy->toString() . "-" . \wp\Helpers\dateHelper::today("dmY"), "1",$mimes->getObject()->extension);
				$this->mapper->nomoriginal = $uploadFile->name();
				$this->mapper->datedepot = \wp\Helpers\dateHelper::today();
				$this->mapper->taille = $uploadFile->size();
				$this->mapper->mimetype_id = $uploadFile->mimeId();
				
				if($uploadFile->process($this->mapper->nomcalcule)){
					\wp\Helpers\httpQueryHelper::post($this->mapper->getTableName()."_".$this->mapper->getColumnPrefix() . "nomcalcule",$this->mapper->nomcalcule);
					\wp\Helpers\httpQueryHelper::post($this->mapper->getTableName()."_".$this->mapper->getColumnPrefix() . "nomoriginal",$uploadFile->name());
					\wp\Helpers\httpQueryHelper::post($this->mapper->getTableName()."_".$this->mapper->getColumnPrefix() . "datedepot",$this->mapper->datedepot);
					\wp\Helpers\httpQueryHelper::post($this->mapper->getTableName()."_".$this->mapper->getColumnPrefix() . "taille",$this->mapper->taille);
					\wp\Helpers\httpQueryHelper::post($this->mapper->getTableName()."_"."mimetype_id",$this->mapper->mimetype_id);
					
					
					return true;
				}
				
			}
		}
		
		return false;
	}
	
	protected function beforeUpdate(){}
	
	protected function beforeDelete(){}
	
	protected function afterInsert(){}
	
	protected function afterUpdate(){}
	
	protected function afterDelete(){}
	
	/**
	 * Retourne les informations relatives au porteur
	 * @return string
	 */
	private function toPorteur(){
		$porteur = $this->dossier->get("porteursexe") == "M" ? "Monsieur " : "Madame ";
		$porteur .= $this->dossier->get("prenomporteur") . " " . $this->dossier->get("nomporteur");
		
		return $porteur;
	}
}
?>