<?php
/**
 * @name defParamBase.class.php : Gestion des paramètres de base de l'intranet ARCEC
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package formManager
 * @version 1.0
 **/
namespace arcec;

class defParamBase extends \wp\formManager\admin{

	
	public function __construct(){
		$this->module = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
		
		$this->setId($this->module)
		->setName($this->module);
		
		$this->setCss("form-horizontal");
		$this->setCss("container-fluid");
		
		$this->setTemplateName("admin.tpl");
		
		$this->mapper = new \arcec\Mapper\parambaseMapper();
		
		if(is_null(\wp\Helpers\urlHelper::context())){
			$this->index = $this->setIndex($this->mapper,"table");
			$this->index->setHeaders(array(
						"nom" => "Type", "libellecourt" => "Code","libellelong" => "Libellé"
					)
				)
				->addPager(10)
				->addFilter("nom",10)
				->setPlugin("tablesorter")
			;
			
			\wp\Tpl\templateEngine::getEngine()->setVar("index", $this->index);
		}
		
		$this->set();
		
		\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Paramètres de base");
		
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
		$field->setId("frmCode")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "libellecourt")
		->setLabel("Libellé court")
		->setMaxLength(3)
		->toUpper(true)
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->isRequired()
		->setCss("form-control")
		->setValue($this->mapper->getObject()->libellecourt);
		
		$this->addToFieldset($field);

		$field = new \wp\formManager\Fields\text();
		$field->setId("frmNom")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "libellelong")
		->setLabel("Libellé long")
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->isRequired()
		->setCss("form-control")
		->setValue($this->mapper->getObject()->libellelong);
		
		$this->addToFieldset($field);
		
		// L'éventuelle dépendance dans la tables des définitions
		$field = new \wp\formManager\Fields\popup();
		$codeMapper = $this->mapper->getSchemeDetail("param_definition_id","mapper");
		$field->setId("frmDefCode")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "defcode")
		->setLabel("Implique le paramètre")
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->setCss("form-control")
		->setHeaderLine(0,"Aucune dépendance nécessaire")
		->setMapping($codeMapper,array("value" => "id", "content"=>array("code","nom")))
		//->setMapping($factory->addInstance(),array("value" => "id", "content"=>array("code","nom")))
		->setValue($this->mapper->getObject()->defcode)
		->setForceHeaderStatut(true)
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
		
		// Champ inactif pour la gestion du statut actif ou pas
		$field = new \wp\formManager\Fields\checkbox();
		$field
			->setId("frmActif")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "actif")
			->setLabel("Disponible dans les formulaires")
			->setCss("control-label",true)
			->setCss("col-sm-10",true)
			->isRequired(false)
			->isDisabled($this->mapper->getObject()->param_definition_id == 2 ? false : true)
			->setCss("form-control")
			->isChecked($this->mapper->getObject()->actif);
		$this->addToFieldset($field);
		
		// Définit le code de contrôle des listes...
		$this->clientRIA .= "$(\"#frmDefCode\").on(\"change\",function(){
				console.log(\"Changement de ligne\");
				// Supprime l'attribut disable des options de la liste dépendante
				$(\"#frmTypeParam option\").removeAttr(\"disabled\");
				if($(\"#frmDefCode option:selected\").val() != 0){
					var value = $(\"#frmDefCode option:selected\").val();
					console.log(\"Désactive la ligne \" + value);
					$(\"#frmTypeParam option[value=\" + value + \"]\").attr(\"disabled\",true);
				}
			});
				
			// Détermine si l'objet actif doit être activé ou non
			$(\"#frmTypeParam\").on(\"change\",function(){
					var param = $(\"#frmTypeParam option:selected\").text().substr(0,3);
					if(param == \"CNS\"){
						$(\"#frmActif\").removeProp(\"disabled\");
					} else {
						$(\"#frmActif\").prop(\"disabled\",\"disabled\");
					}
				}
			);
			// Gère la suppression d'un paramètre
			$(\"ul.dropdown-menu li a\").on(\"click\",function(){
				var dialog = null;
				
					if($(this).children(\"span\").hasClass(\"icon-cross\")){
						var id  = $(this).children(\"span\").data(\"rel\");
						// Récupère les informations relatives au paramètres
						$.ajax({
								url:\"" . \wp\framework::getFramework()->getAjaxDispatcher() . "\",
								type:\"POST\",
								data:\"object=rowLoader&namespace=" . str_replace("\\","_",$this->mapper->getNameSpace()) . "&mapper=" . $this->mapper->getClassName() . "&caption=libellelong&content=\" + id,
								dataType:\"json\"
							}
						).success(function(result){
								data = result.data;
										
								$(\"#delete-dlg\").attr(\"title\",\"Suppression\");
								$(\"p.dlg-content\")
									.html(\"Etes-vous sûr de vouloir supprimer le paramètre : \" + data.caption + \" ?\");

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
								data:\"object=paramRemove&namespace=" . str_replace("\\","_",$this->mapper->getNameSpace()) . "&mapper=" . $this->mapper->getClassName() . "&id=\" + id,
								dataType:\"json\"
							}).success(function(result){
									data = result.data;
									var tableRow = $(\"label[for='id_\" + data.id + \"']\").parent().parent().parent();
									tableRow.remove();
								}
							);
							$(\"#delete-dlg\" ).dialog(\"close\");
						}
					}
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
	 * Définit le bouton de sélection des actions pour une ligne de table
	 * @param object $mapper : Défintion de la table à traiter
	 * @param int $id : Identifiant de la ligne de la table à traiter
	 * return array Tableau des options disponibles
	 **/
	public function setActionBtn($mapper,$id){
		$action[] = array(
				"url" => "./index.php?com=" . $this->module . "&context=UPDATE&id=" . $id,
				"libelle" => "<span class=\"icon-loop2\"></span>Mettre à jour"
		);
	
		if($mapper->checkIntegrity($id)){
			$action[] = array(
					"url" => "#",
					"libelle" => "<span class=\"icon-cross\" data-rel=\"" . $id . "\"></span>Supprimer"
			);
		}
	
		$action[] = array(
				"url" => "divider"
		);
	
		$action[] = array(
				"url" => "#",
				"libelle" => "<span class=\"icon-plus\" data-copy=\"" . $id . "\"></span>Dupliquer"
		);
	
		return $action;
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