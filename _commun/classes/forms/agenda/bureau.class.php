<?php
/**
 * @name bureau.class.php : Gestion des salles et bureaux ARCEC
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package formManager
 * @version 1.0
 **/
namespace arcec;

class bureau extends \wp\formManager\admin{

	
	public function __construct(){
		$this->module = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
		
		$this->setId($this->module)
		->setName($this->module);
		
		$this->setCss("form-horizontal");
		$this->setCss("container-fluid");
		
		$this->setTemplateName("admin.tpl");
		
		$this->mapper = new \arcec\Mapper\bureauMapper();
		
		if(is_null(\wp\Helpers\urlHelper::context())){
			$this->index = $this->setIndex($this->mapper,"table");
			$this->index->setHeaders(array(
						"libelle" => "Libellé", "codification" => "Code"
					)
				)
				->addPager(10)
				->addFilter("libelle",10)
				->setPlugin("tablesorter")
			;
			
			\wp\Tpl\templateEngine::getEngine()->setVar("index", $this->index);
		}
		
		$this->set();
		
		\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Agenda - Gestion des salles");
		
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
		$field->setId("frmCodification")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "codification")
		->setLabel("Code de la salle")
		->setMaxLength($this->mapper->getSchemeDetail("codification","size"))
		->toUpper(true)
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->isRequired()
		->setCss("form-control")
		->setValue($this->mapper->getObject()->codification);
		
		$this->addToFieldset($field);

		$field = new \wp\formManager\Fields\text();
		$field->setId("frmLibelle")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "libelle")
		->setLabel("Libellé")
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->setCss("form-control")
		->setValue($this->mapper->getObject()->libelle);
		
		$this->addToFieldset($field);

		// Capacité maximale
		$field = new \wp\formManager\Fields\integer();
		$field->setId("frmCapacite")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "capacite")
		->setLabel("Capacité maximale")
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->setCss("form-control")
		->setRIAScript()
		->setValue($this->mapper->getObject()->capacite ? $this->mapper->getObject()->capacite : $this->mapper->getSchemeDetail("capacite","default"));
		$this->addToFieldset($field);
		
		// Ajoute la liste parente
		$field = new \wp\formManager\Fields\popup();
		
		// Récupère le mapper sur la table parente
		$parentMapper = $this->mapper->getSchemeDetail("acu","mapper");
		//$factory = new \wp\Patterns\factory($this->mapper->getNameSpace() . $parentTable . "Mapper");
		
		$field->setId("frmACU")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "acu")
			->setLabel("Lieu")
			->setCss("control-label",true)
			->setCss("col-sm-5",true)
			->setCss("form-control")
			->isRequired()
			->setMapping($parentMapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
			//->setMapping($factory->addInstance(),array("value" => "id", "content"=>array("code","nom")))
			->setValue($this->mapper->getObject()->acu)
		;
		
		$this->addToFieldset($field);
		
		// Ajoute le script de suppression
		$this->clientRIA .= $this->deleteScript("libelle");
		
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
	
	protected function beforeInsert(){}
	protected function beforeUpdate(){}
	protected function beforeDelete(){}
	protected function afterInsert(){}
	protected function afterUpdate(){}
	protected function afterDelete(){}
}
?>