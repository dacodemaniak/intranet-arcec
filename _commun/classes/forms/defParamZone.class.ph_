<?php
/**
 * @name defParamZone.class.php : Gestion des paramètres de zone de l'intranet ARCEC
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package formManager
 * @version 1.0
 **/
namespace arcec;

class defParamZone extends \wp\formManager\admin{

	
	public function __construct(){
		$this->module = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
		
		$this->setId($this->module)
		->setName($this->module);
		
		$this->setCss("form-horizontal");
		$this->setCss("container-fluid");
		
		$this->setTemplateName("admin.tpl");
		
		$this->mapper = new \arcec\Mapper\paramzoneMapper();
		
		if(is_null(\wp\Helpers\urlHelper::context())){
			$this->index = $this->setIndex($this->mapper,"table");
			$this->index->setHeaders(array(
						"nom" => "Type", "codepostal" => "Code Postal","motdirecteur" => "Rue"
					)
				)
				->addPager(20)
				->addFilter("type",20)
				->setPlugin("tablesorter")
			;
			
			\wp\Tpl\templateEngine::getEngine()->setVar("index", $this->index);
		}
		
		$this->set();
		
		\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Paramètres de zones");
		
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
		
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmCodePostal")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "codepostal")
		->setLabel("Code Postal")
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->isRequired()
		->setCss("form-control")
		->setValue($this->mapper->getObject()->codepostal);
		
		$this->addToFieldset($field);

		$field = new \wp\formManager\Fields\text();
		$field->setId("frmMotDiecteur")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "motdirecteur")
		->setLabel("Mot Directeur")
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->isRequired()
		->setCss("form-control")
		->setValue($this->mapper->getObject()->motdirecteur);
		
		$this->addToFieldset($field);

		$field = new \wp\formManager\Fields\text();
		$field->setId("frmTypeVoie")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "typevoie")
		->setLabel("Type de voie")
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->isRequired()
		->setCss("form-control")
		->setValue($this->mapper->getObject()->typevoie);
		
		$this->addToFieldset($field);

		$field = new \wp\formManager\Fields\text();
		$field->setId("frmDebut")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "debut")
		->setLabel("N° de début")
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->isRequired()
		->setCss("form-control")
		->setValue($this->mapper->getObject()->debut);
		
		$this->addToFieldset($field);

		$field = new \wp\formManager\Fields\text();
		$field->setId("frmFin")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "fin")
		->setLabel("N° de fin")
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->isRequired()
		->setCss("form-control")
		->setValue($this->mapper->getObject()->fin);
		
		$this->addToFieldset($field);

		$field = new \wp\formManager\Fields\popup();
		
		$field->setId("frmParite")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "parite")
		->setLabel("Parité")
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->setCss("form-control")
		->isRequired()
		->setDatas(array(
				array("value" => "I","content" => "Impair"),
				array("value" => "P","content" => "Pair"),
				array("value" => "M","content" => "Mixte"),
			)
		)
		//->setMapping($factory->addInstance(),array("value" => "id", "content"=>array("code","nom")))
		->setValue($this->mapper->getObject()->parite)
		;
		
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