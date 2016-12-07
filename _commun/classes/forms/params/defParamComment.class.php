<?php
/**
 * @name defParamComment.class.php : Gestion des paramètres de messages et commentaires de l'intranet ARCEC
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package formManager
 * @version 1.0.1
 **/
namespace arcec;

class defParamComment extends \wp\formManager\admin{

	
	public function __construct(){
		$this->module = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
		
		$this->setId($this->module)
		->setName($this->module);
		
		$this->setCss("form-horizontal");
		$this->setCss("container-fluid");
		
		$this->setTemplateName("admin.tpl");
		
		$this->mapper = new \arcec\Mapper\paramcommentMapper();
		
		if(is_null(\wp\Helpers\urlHelper::context())){
			$this->index = $this->setIndex($this->mapper,"table");
			$this->index->setHeaders(array(
						"nom" => "Type", "message" => "Message"
					)
				)
				->addPager(20)
				->addFilter("nom",20)
				->setPlugin("tablesorter")
			;
			
			\wp\Tpl\templateEngine::getEngine()->setVar("index", $this->index);
		}
		
		$this->set();
		
		\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Paramètres de messages");
		
		if(!$this->isValidate()){
			$this->setAction(array("com" => $this->module,"context"=>\wp\Helpers\urlHelper::toContext()));
		} else {
			$this->process();
		}		
	}
	

	/**
	 * Définit les champs du formulaire de gestion
	 * @see \wp\formManager\admin::set()
	 * @todo Créer le champ de type number pour gérer les valeurs numériques
	**/
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
		
		$field = new \wp\formManager\Fields\limitedTextarea();
		$field->setId("frmMessage")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "message")
		->setLabel("Message")
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->setCols(30)
		->setRows(5)
		->toggleCharLeft()
		->setMaxLength(79)
		->isRequired()
		->setCss("form-control")
		->setValue($this->mapper->getObject()->message)
		;
		
		$this->addToFieldset($field);
		
		$this->clientRIA .= $field->getRIAScript();
		
		// Définition du champ par défaut
		$field = new \wp\formManager\Fields\checkbox();
		$field
			->setId("frmDefaut")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "defaut")
			->setLabel("Par défaut")
			->setCss("control-label",true)
			->setCss("col-sm-3",true)
			->setGroupCss("col-sm-12")
			->isChecked($this->mapper->getObject()->defaut);
		$this->addToFieldset($field);
		
		// Ajoute la liste parente
		$field = new \wp\formManager\Fields\popup();
		
		// Récupère le mapper sur la table parente
		$parentMapper = $this->mapper->getSchemeDetail("param_definition_id","mapper");
		//$factory = new \wp\Patterns\factory($this->mapper->getNameSpace() . $parentTable . "Mapper");
		
		$field->setId("frmTypeParam")
			->setName($this->mapper->getTableName() . ".param_definition_id")
			->setLabel("Table de paramètres")
			->setCss("control-label",true)
			->setCss("col-sm-5",true)
			->setCss("form-control")
			->isRequired()
			->setMapping($parentMapper,array("value" => "id", "content"=>array("code","nom")))
			//->setMapping($factory->addInstance(),array("value" => "id", "content"=>array("code","nom")))
			->setValue($this->mapper->getObject()->param_definition_id)
		;
		
		$this->addToFieldset($field);
		
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