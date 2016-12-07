<?php
/**
 * @name defArboRessource.class.php : Gestion de l'arborescence des ressources
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package formManager
 * @version 1.0
 **/
namespace arcec;

class defArboRessource extends \wp\formManager\admin{

	
	public function __construct(){
		$this->module = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
		
		$this->setId($this->module)
		->setName($this->module);
		
		$this->setCss("form-horizontal");
		$this->setCss("container-fluid");
		
		$this->setTemplateName("admin.tpl");
		
		$this->mapper = new \arcec\Mapper\arboressourceMapper();
		
		if(is_null(\wp\Helpers\urlHelper::context())){
			$this->index = $this->setIndex($this->mapper,"tree");
			$this->index->setHeaders(array(
						"codification" => "code", "titre" => "titre"
					)
				)
				->toggleButtonAdd(true)
				->toggleButtonDelete(true)
				->childrenMapper(new \arcec\Mapper\ressourceMapper())
				->module($this->module)
				->process()
			;
			
			\wp\Tpl\templateEngine::getEngine()->setVar("index", $this->index);
		}
		
		$this->set();
		
		\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Ressources-Arborescence");
		
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
		$field->setId("frmTitre")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "titre")
		->setLabel("Titre")
		->setMaxLength(75)
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->isRequired()
		->setCss("form-control")
		->setValue($this->mapper->getObject()->titre);
		
		$this->addToFieldset($field);

				
		// Description du champ
		$field = new \wp\formManager\Fields\textarea();
		$field->setId("frmDescription")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "description")
			->setLabel("Description")
			->setCss("control-label",true)
			->setCss("col-sm-5",true)
			->setCss("col-sm-12")
			->setCss("form-control")
			->setValue($this->mapper->getObject()->description);
		$this->addToFieldset($field);
				
		$isReadOnly = \wp\Helpers\urlHelper::context() == "UPDATE" ? true : false;
		
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmCodification")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "codification")
		->setLabel("Codification")
		->setMaxLength(3)
		->toUpper(true)
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->isRequired()
		->isReadOnly($isReadOnly)
		->setCss("form-control")
		->setValue($this->mapper->getObject()->codification);
		
		$this->addToFieldset($field);

		
		// Récupère le mapper sur la table parente
		$parentMapper = new \arcec\Mapper\arboressourceMapper();
		
		if(\wp\Helpers\urlHelper::context() == "INSERT"){
			$default = \wp\Helpers\httpQueryHelper::get("parent",0);	
		} else {
			$default = $this->mapper->getObject()->parent;
		}
		
		$field = new \wp\formManager\Fields\popup();
		$field->setId("frmParent")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "parent")
			->setLabel("Noeud parent")
			->setCss("control-label",true)
			->setCss("col-sm-5",true)
			->setCss("form-control")
			->setHeaderLine(0,"Racine")
			->setForceHeaderStatut(true)
			->isRequired()
			->setMapping($parentMapper,array("value" => "id", "content"=>array("codification","titre")))
			->setValue($default)
		;
		
		$this->addToFieldset($field);
		
		// Ajoute le script de gestion des suppression des noeuds
		$this->clientRIA .= "
			// Gestion de la suppression des noeuds
			$(\".delete-item\").on(\"click\",function(event){
					event.preventDefault(); // Ne pas gérer le lien
					if($(this).hasClass(\"icon-disabled\")){
						return;
					}
					var id = $(this).data(\"rel\");
					console.log(\"Demande de suppression de l'id : \" + id);
					// Début du traitement de suppression
					// Récupère les informations relatives au paramètres
					$.ajax({
								url:\"" . \wp\framework::getFramework()->getAjaxDispatcher() . "\",
								type:\"POST\",
								data:\"object=rowLoader&namespace=" . str_replace("\\","_",$this->mapper->getNameSpace()) . "&mapper=" . $this->mapper->getClassName() . "&caption=titre&content=\" + id,
								dataType:\"json\"
							}
						).success(function(result){
							data = result.data;
		
							$(\"#delete-dlg\").attr(\"title\",\"Suppression\");
							$(\"p.dlg-content\")
								.html(\"Etes-vous sûr de vouloir supprimer le noeud : \" + data.caption + \" ?\");
		
							$(\"#delete-dlg\" ).dialog({
								autoOpen: false,
								height: 300,
								width: 350,
								modal: true,
								buttons: {
									\"Supprimer\": function(){
										removeParam(data.id);
									},
									\"Annuler\": function() {
										$(this).dialog(\"close\" );
									}
								},
								close: function() {}
							}).dialog(\"open\");
						}
					);
					// Définit la fonction de suppression logique du paramètre
					function removeParam(id){
						console.log(\"Suppression logique du paramètre \" + id);
						$.ajax({
							url:\"" . \wp\framework::getFramework()->getAjaxDispatcher() . "\",
							type:\"POST\",
							data:\"object=itemDelete&namespace=" . str_replace("\\","_",$this->mapper->getNameSpace()) . "&mapper=" . $this->mapper->getClassName() . "&id=\" + id,
							dataType:\"json\"
						}).success(function(result){
								data = result.data;
								var row = $(\"a[data-rel='\" + data.id + \"']\").parent(\"li\");
								row.remove();
							}
						);
						$(\"#delete-dlg\" ).dialog(\"close\");
					}
					// Fin du traitement de suppression
				}
			);
		";
		
		$js = new \wp\htmlManager\js();
		$js->addPlugin("jquery-ui");
		
		\wp\Tpl\templateEngine::getEngine()->addContent("js",$js);
		
		$this->toControls();
		
		// Ajoute les CSS
		$css = new \wp\htmlManager\css();
		$css->addSheet("jquery-ui");
		
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