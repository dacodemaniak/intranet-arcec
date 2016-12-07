<?php
/**
 * @name setAnnuaire.class.php Gestion des entrées de l'annuaire
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package formManager
 * @version 1.0
 **/
namespace arcec;

class setAnnuaire extends \wp\formManager\admin{

	
	public function __construct(){
		$this->module = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
		
		$this->setId($this->module)
		->setName($this->module);
		
		$this->setCss("form-horizontal");
		$this->setCss("container-fluid");
		
		$this->setTemplateName("admin.tpl");
		
		$this->mapper = new \arcec\Mapper\annuaireMapper();
		
		if(is_null(\wp\Helpers\urlHelper::context())){
			$this->index = $this->setIndex($this->mapper,"taxonomy");
			$this->index->setHeaders("parent",array(
						"codification" => "code", "titre" => "titre"
					)
				)
				->setHeaders("main",
					array(
						"nom" => "nom",
						"prenom" => "prenom",
						"email" => "email"
					)
				)
				->anchors("alpha","nom")
				->toggleButtonAdd(true)
				->module($this->module)
				->parentMapper(new \arcec\Mapper\arboannuaireMapper())
				->taxonomyModule("defArboAnnuaire")
				->process()
			;
			
			\wp\Tpl\templateEngine::getEngine()->setVar("index", $this->index);
		}
		
		// Détermine le traitement client : filtre par catégories | filtre par initiales
		$this->clientRIA = "
			// Filtre sur la taxonomie
			$(\".tree-list li\").on(\"click\",function(evt){
					$(\"ul.anchors li\").removeClass(\"active\");
					$(\".tree-list li\").removeClass(\"active\");
					evt.stopPropagation();
					var arbo = $(this).data(\"filter\");
					$(this).addClass(\"active\");
					console.log(\"Filtre sur la catégorie : \" + $(this).data(\"filter\"));
					
					$.ajax({
						url:\"" . \wp\framework::getFramework()->getAjaxDispatcher() . "\",
						type:\"POST\",
						data:\"object=filtreAnnuaire&mapper=" . $this->mapper->getClassName() . "&parent=arboannuaire&content=\" + arbo,
						dataType:\"json\"
						}
					).success(function(data,status){
							$(\".admin-element\").addClass(\"no-active\");
							if(data.satut == 0){
								// Afficher une boîte pour réinitialiser les filtres
								
							} else {
								var alpha = new Array; // Caractères à activer pour le filrage
								// Effacer la liste existante, et reconstruire à partir des données retournées
								var datas = data.datas;
								$.each(datas,function(key,value){
										console.log(\"Montrer : \" + value.id);
										var element = $(\"div.admin-element[data-rel=\" + value.id + \"]\");
										$(element).removeClass(\"no-active\");
										var theNom = $(element).children(\"a\").children(\"span.nom\").text();
										console.log(\"Ajoute l'initiale : \"  + theNom.substring(0,1));
										alpha.push(theNom.substring(0,1));
									}
								);
								
								$(\".anchors li\").each(function(){
										if($.inArray($(this).text(),alpha) != -1){
											console.log(\"Active la lettre \" + $(this).text());
											$(this).addClass(\"active\");
										}
									}
								);

							}
						}
					);
					return false;
				}
			);
			// Filtre sur les initiales
			$(\"ul.anchors li\").on(\"click\",function(evt){
					var post					= new String;
								
					if($(\"ul.anchors li.active\").length > 1){
						if($(this).hasClass(\"active\")){
							var alphaFilter = $(this).text(); // Filtre alpha défini
							post += \"alpha=\" + alphaFilter;
							// Récupère le filtre sur actif sur la taxonomie
							if($(\".tree-list li.active\").length == 1){
								var taxonomyFilter = $(\".tree-list li.active\").data(\"filter\");
								post += \"&content=\" + taxonomyFilter;
							}
							// Lance l'appel Ajax avec le filtrage concerné
							$.ajax({
								url:\"" . \wp\framework::getFramework()->getAjaxDispatcher() . "\",
								type:\"POST\",
								data:\"object=filtreAnnuaire&mapper=" . $this->mapper->getClassName() . "&parent=arboannuaire&\" + post,
								dataType:\"json\"
								}
							).success(function(data,status){
									$(\".admin-element\").addClass(\"no-active\");
									if(data.satut == 0){
										// Afficher une boîte pour réinitialiser les filtres
										
									} else {
										var alpha = new Array; // Caractères à activer pour le filrage
										// Effacer la liste existante, et reconstruire à partir des données retournées
										var datas = data.datas;
										$.each(datas,function(key,value){
												console.log(\"Montrer : \" + value.id);
												var element = $(\"div.admin-element[data-rel=\" + value.id + \"]\");
												$(element).removeClass(\"no-active\");
												var theNom = $(element).children(\"a\").children(\"span.nom\").text();
												console.log(\"Ajoute l'initiale : \"  + theNom.substring(0,1));
												alpha.push(theNom.substring(0,1));
											}
										);
										
										$(\".anchors li\").each(function(){
												if($.inArray($(this).text(),alpha) != -1){
													console.log(\"Active la lettre \" + $(this).text());
													$(this).addClass(\"active\");
												}
											}
										);
		
									}
								}
							);
							// Fin de l'appel Ajax
						}
					}
				}
			);
		";
				
		$this->toControls();
		
		$this->set();
		
		\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Annuaire - Carnet d'adresses");
		
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

		$field = new \wp\formManager\Fields\text();
		$field->setId("frmNom")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "nom")
		->setLabel("Nom")
		->setMaxLength(75)
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->isRequired()
		->setCss("form-control")
		->setValue($this->mapper->getObject()->nom);
		
		$this->addToFieldset($field);
		
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmPrenom")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "prenom")
		->setLabel("Prénom")
		->setMaxLength(75)
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->setCss("form-control")
		->setValue($this->mapper->getObject()->prenom);
		
		$this->addToFieldset($field);

		$field = new \wp\formManager\Fields\mail();
		$field->setId("frmEMail")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "email")
		->setLabel("e-Mail")
		->setMaxLength(75)
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->setCss("form-control")
		->isRequired()
		->setValue($this->mapper->getObject()->email);
		
		$this->addToFieldset($field);
		
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmFixe")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "telephonefixe")
		->setLabel("Téléphone")
		->setMaxLength(20)
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->setCss("form-control")
		->setValue($this->mapper->getObject()->telephonefixe);
		
		$this->addToFieldset($field);

		$field = new \wp\formManager\Fields\text();
		$field->setId("frmGSM")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "telephoneportable")
		->setLabel("Portable")
		->setMaxLength(20)
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->setCss("form-control")
		->setValue($this->mapper->getObject()->telephoneportable);
		
		$this->addToFieldset($field);

		$field = new \wp\formManager\Fields\text();
		$field->setId("frmFAX")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "fax")
		->setLabel("Fax")
		->setMaxLength(20)
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->setCss("form-control")
		->setValue($this->mapper->getObject()->fax);
		
		$this->addToFieldset($field);

		
		// Récupère le mapper sur la table parente
		$parentMapper = new \arcec\Mapper\arboannuaireMapper();
		
		if(\wp\Helpers\urlHelper::context() == "INSERT"){
			$default = \wp\Helpers\httpQueryHelper::get("parent");	
		} else {
			$default = $this->mapper->getObject()->arboannuaire_id;
		}
		
		$field = new \wp\formManager\Fields\popup();
		$field->setId("frmArbo")
			->setName($this->mapper->getTableName() . ".arboannuaire_id")
			->setLabel("Classer dans")
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