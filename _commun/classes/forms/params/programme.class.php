<?php
/**
 * @name programme.class.php : Association des étapes d'un programme
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec
 * @version 1.0
 **/
namespace arcec;

class programme extends \wp\formManager\admin{
	/**
	 * Identifiant du programme
	 * @var int
	 */
	private $programmeId;
	
	
	public function __construct(){
		$this->module = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
		
		$this->setId($this->module)
		->setName($this->module);
		
		$this->setCss("form-horizontal");
		$this->setCss("container-fluid");
		
		$this->setTemplateName("admin.tpl");
		
		$this->mapper = new \arcec\Mapper\prgtoetapesMapper();
		
		$indexMapper = new \arcec\Mapper\paramPRGMapper();
		
		if(is_null(\wp\Helpers\urlHelper::context())){
			$this->index = $this->setIndex($indexMapper,"table");
			$this->index->setTemplateName("tableRadioIndex.tpl");
			$this->index->setHeaders(array(
						"libellecourt" => "code",
						"libellelong" => "Libellé"
					)
				)
				->addPager(20)
				->addFilter("type",20)
				->setPlugin("tablesorter")
			;
			
			\wp\Tpl\templateEngine::getEngine()->setVar("index", $this->index);
		}
		
		$this->set();
		
		\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Définition des programmes");
		
		if(!$this->isValidate()){
			$this->setAction(array("com" => $this->module,"context"=>\wp\Helpers\urlHelper::toContext()));
		} else {
			$this->process();
		}		
	}

	/**
	 * Définit le bouton de sélection des actions pour une ligne de table
	 * @param object $mapper : Défintion de la table à traiter
	 * @param int $id : Identifiant de la ligne de la table à traiter
	 * return array Tableau des options disponibles
	 **/
	public function setActionBtn($mapper,$id){
		$action[] = array(
				"url" => "./index.php?com=" . $this->module . "&context=UPDATE&id=" . $id,
				"libelle" => "<span class=\"icon-loop2\"></span>Définir les étapes"
		);
	
		return $action;
	}
	

	
	protected function set(){
		
		$this->programmeId = !is_null(\wp\Helpers\urlHelper::context("id")) ? \wp\Helpers\urlHelper::context("id") : \wp\Helpers\httpQueryHelper::get($this->mapper->getTableName() . "_programme_id");
		
		if(\wp\Helpers\urlHelper::context() == "UPDATE" || \wp\Helpers\urlHelper::context() == "upd"){
			$this->mapper->searchBy("programme_id",$this->programmeId);
			$this->mapper->set($this->mapper->getNameSpace());
			
			// Crée le champ caché pour le stockage de la clé de la table des programmes
			$field = new \wp\formManager\Fields\hidden();
			$field->setId("frmProgrammeId")
				->setName($this->mapper->getTableName() . ".programme_id")	
				->setValue($this->programmeId);
			$this->addToFieldset($field);
				
		}
		
		if($this->programmeId){
			$programme = $this->mapper->getSchemeDetail("programme_id","mapper"); 
			$programme->setId($this->programmeId);
			$programme->set($programme->getNameSpace());
			
			$field = new \wp\formManager\Fields\staticText();
			$field->setId("frmProgramme")
			->setName("programme")
			->setLabel("Programme")
			->setCss("control-label",true)
			->setCss("col-sm-5",true)
			->setCss("form-control")
			->setValue($programme->getObject()->libellelong);
			
			$this->addToFieldset($field);
		}
		
		// Ajoute la liste des étapes définies avec les boîtes à cocher pour sélection
		$field = new \wp\formManager\Fields\checkTable();
		$field->setId("frmIdEtapes")
			->setName($this->mapper->getTableName() . ".etapeprojet_id")
			->setHeaders(
				array("libelle" => "Etape")
			)
			->caption("Etapes")
			->source($this->mapper->getSchemeDetail("etapeprojet_id","mapper"))
			->selected($this->mapper);
		
		$this->addToFieldset($field);
		
	}
	
	/**
	 * Traite le formulaire
	 * @see \wp\formManager\admin::process()
	 * @todo Créer la méthode pour définir l'URL d'erreur (rester sur place et afficher le message)
	 */
	protected function process(){
		if($this->before()){
			$checked = $this->getField("frmIdEtapes")->getPostedData();
			foreach($checked as $etape){
				$this->mapper->programme_id = $this->programmeId;
				$this->mapper->etapeprojet_id = $etape;
				$this->mapper->save();
			}
			
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
	
	protected function beforeUpdate(){
		$this->mapper->programme_id = $this->programmeId;
		return $this->mapper->delete("programme_id");
	}
	
	protected function beforeDelete(){}
	protected function afterInsert(){}
	protected function afterUpdate(){}
	protected function afterDelete(){}
}
?>