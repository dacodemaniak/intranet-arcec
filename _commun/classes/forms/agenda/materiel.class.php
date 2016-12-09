<?php
/**
 * @name materiel.class.php : Gestion des matériels disponibles
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package formManager
 * @version 1.0
 **/
namespace arcec;

class materiel extends \wp\formManager\admin{

	
	public function __construct(){
		$this->module = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
		
		$this->setId($this->module)
		->setName($this->module);
		
		$this->setCss("form-horizontal");
		$this->setCss("container-fluid");
		
		$this->setTemplateName("admin.tpl");
		
		$this->mapper = new \arcec\Mapper\materielMapper();
		
		if(is_null(\wp\Helpers\urlHelper::context())){
			$this->index = $this->setIndex($this->mapper,"table");
			$this->index->setHeaders(array(
						"libelle" => "Dénomination"
					)
				)
				->addPager(20)
				->addFilter("type",20)
				->setPlugin("tablesorter")
			;
			
			\wp\Tpl\templateEngine::getEngine()->setVar("index", $this->index);
		}
		
		$this->set();
		
		\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Agenda - Matériels");
		
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
		$field->setId("frmLibelle")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "libelle")
		->setLabel("Libellé")
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->isRequired()
		->setCss("form-control")
		->setValue($this->mapper->getObject()->libelle);
		
		$this->addToFieldset($field);

		$field = new \wp\formManager\Fields\textarea();
		$field->setId("frmDescription")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "description")
		->setLabel("Description")
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->setCss("form-control")
		->setValue($this->mapper->getObject()->description);
		
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