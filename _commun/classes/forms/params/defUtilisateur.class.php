<?php
/**
 * @name defUtilisateur.class.php : Définition des utilisateurs de l'Intranet
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package formManager
 * @version 1.0
 **/
namespace arcec;

class defUtilisateur extends \wp\formManager\admin{

	
	public function __construct(){
		$this->module = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
		
		$this->setId($this->module)
		->setName($this->module);
		
		$this->setCss("form-horizontal");
		$this->setCss("container-fluid");
		
		$this->setTemplateName("admin.tpl");
		
		$this->mapper = new \wp\dbManager\Mapper\utilisateurMapper();
		
		if(is_null(\wp\Helpers\urlHelper::context())){
			$this->index = $this->setIndex($this->mapper,"table");
			$this->index->setHeaders(array(
						"login" => "Identifiant",
						"groupe_utilisateur_id" => array("header" => "Groupe","column"=>"libelle","mapper"=>new \wp\dbManager\Mapper\groupeutilisateurMapper()),
					)
				)
				->addPager(20)
				->addFilter("type",20)
				->setPlugin("tablesorter")
			;
			
			\wp\Tpl\templateEngine::getEngine()->setVar("index", $this->index);
		}
		
		$this->set();
		
		\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Utilisateurs");
		
		if(!$this->isValidate()){
			$this->setAction(array("com" => $this->module,"context"=>\wp\Helpers\urlHelper::toContext()));
		} else {
			$this->process();
		}		
	}
	

	
	protected function set(){
		
		if(\wp\Helpers\urlHelper::context() == "UPDATE" || \wp\Helpers\urlHelper::context() == "DELETE"){
			
			$this->mapper->setId(\wp\Helpers\urlHelper::context("id"));
			$this->mapper->set($this->mapper->getNameSpace());
			
			// Crée le champ caché pour le stockage de la clé primaire
			$field = new \wp\formManager\Fields\hidden();
			$field->setId($this->mapper->getTableName() . ".primary")
				->setName($this->mapper->getTableName() . ".primary")	
				->setValue(\wp\Helpers\urlHelper::context("id"));
			$this->addToFieldset($field);
				
		}
		
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmLogin")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "login")
		->setLabel("Identifiant")
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->isRequired()
		->setCss("form-control")
		->setValue($this->mapper->getObject()->login);
		
		$this->addToFieldset($field);
		
		$field = new \wp\formManager\Fields\popup();
		// Récupère le mapper sur la table parente
		$parentMapper = $this->mapper->getSchemeDetail("groupe_utilisateur_id","mapper");
		//$factory = new \wp\Patterns\factory($this->mapper->getNameSpace() . $parentTable . "Mapper");
		
		$field->setId("frmGroupe")
		->setName($this->mapper->getTableName() . ".groupe_utilisateur_id")
		->setLabel("Groupe")
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->setCss("form-control")
		->isRequired()
		->setMapping($parentMapper,array("value" => "id", "content"=>array("libelle")))
		//->setMapping($factory->addInstance(),array("value" => "id", "content"=>array("code","nom")))
		->setValue($this->mapper->getObject()->groupe_utilisateur_id)
		;
		
		$this->addToFieldset($field);
		
		// Champ mot de passe
		$value = is_null($this->mapper->getObject()->login) ? "" : "masqué";
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmPassword")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "pass")
		->setLabel("Mot de passe")
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->isRequired()
		->setCss("form-control");
		
		if($value == ""){
			$field->addAttribut("placeholder","Choisissez...");
		} else {
			$field->setValue($value);
		}
		
		$this->addToFieldset($field);		
		
		// Sel de renforcement
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmSalt")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "salt")
		->setLabel("Clé de renforcement")
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->isRequired()
		->setCss("form-control")
		->setValue($this->mapper->getObject()->salt);
		
		$this->addToFieldset($field);
		
		// Compte associé
		$cnsMapper = new \arcec\Mapper\paramCNSMapper();
		if($cnsMapper->count() > 0){
			$field = new \wp\formManager\Fields\popup();
			$field
				->setId("frmAccount")
				->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "accountid")
				->setLabel("Compte associé")
				->setCss("control-label",true)
				->setCss("col-sm-5",true)
				->isRequired()
				->setCss("form-control")
				->setMapping($cnsMapper,array("value"=>"id","content" => array("libellecourt","libellelong")))
				->setValue($this->mapper->getObject()->accountid);
			$this->addToFieldSet($field);
		}
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
			header("Location:" . \wp\Helpers\urlHelper::toURL($this->module));
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
		// Définit le mot de passe avec le sel de renforcement
		$password = $this->getField("frmPassword")->getPostedData();
		$this->getField("frmPassword")->setPostedData(\wp\Helpers\cryptHelper::encrypt($password,$this->getField("frmSalt")->getPostedData()));
	}
	
	protected function beforeUpdate(){
		// Recalcule le nouveau mot de passe
		if(($password = $this->getField("frmPassword")->getPostedData()) != "masqué"){
			/*
			$this->mapper->pass = \wp\Helpers\cryptHelper::encrypt($password,$this->getField("frmSalt")->getPostedData());
			$_POST[str_replace(".","_",$this->getField("frmPassword")->getName())] = $this->mapper->pass;
			*/
			$this->getField("frmPassword")->setPostedData(\wp\Helpers\cryptHelper::encrypt($password,$this->getField("frmSalt")->getPostedData()));
		} else {
			// Restaure les valeurs existantes
			$mapper = clone $this->mapper;
			$mapper->clearSearch();
			$mapper->setId($this->getPrimaryVal());
			$mapper->set($mapper->getNameSpace());
			$object = $mapper->getObject();
			$this->getField("frmPassword")->setPostedData(\wp\Helpers\cryptHelper::encrypt($object->pass,$object->salt));
			//$this->mapper->pass = \wp\Helpers\cryptHelper::encrypt($mapper->pass,$mapper->salt);
			//$this->mapper->salt = $mapper->salt;
			$this->getField("frmSalt")->setPostedData($object->salt);
		}
	}
	
	protected function beforeDelete(){}
	protected function afterInsert(){}
	protected function afterUpdate(){}
	protected function afterDelete(){}
}
?>