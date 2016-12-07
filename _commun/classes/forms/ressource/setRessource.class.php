<?php
/**
 * @name setRessource.class.php Gestion des ressources documentaires
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package formManager
 * @version 1.0
 **/
namespace arcec;

class setRessource extends \wp\formManager\admin{

	
	public function __construct(){
		$this->module = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
		
		$this->setId($this->module)
		->setName($this->module)
		->setEncType("file");
		
		$this->setCss("form-horizontal");
		$this->setCss("container-fluid");
		
		$this->setTemplateName("admin.tpl");
		
		$this->mapper = new \arcec\Mapper\ressourceMapper();
		
		if(is_null(\wp\Helpers\urlHelper::context())){
			$this->index = $this->setIndex($this->mapper,"taxonomy");
			$this->index->setHeaders("parent",array(
						"codification" => "code", "titre" => "titre"
					)
				)
				->setHeaders("main",
					array(
						"titre" => "titre",
						"contenu" => "contenu"
					)
				)
				//->anchors("alpha","nom")
				->toggleButtonAdd(true)
				->module($this->module)
				->formObject($this)
				->parentMapper(new \arcec\Mapper\arboressourceMapper())
				->taxonomyModule("defArboRessource")
				->setTemplateName("ressourceIndex.tpl","./ressources/")
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
						data:\"object=filtreRessource&mapper=" . $this->mapper->getClassName() . "&parent=arboressource&content=\" + arbo,
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
		";
				
		$this->toControls();
		
		$this->set();
		
		\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Ressources - Gérer");
		
		if(!$this->isValidate()){
			$this->setAction(array("com" => $this->module,"context"=>\wp\Helpers\urlHelper::toContext()));
		} else {
			$this->process();
		}		
	}
	
	/**
	 * Retourne un lien vers une ressource ou un document à télécharger
	 * @param array $params
	 * @return string
	 */
	public function toLink($params){
		$record = new \arcec\Mapper\ressourceMapper();
		
		$record->setId($params[0]);
		$record->set($record->getNameSpace());
		
		$activeRecord = $record->getObject();
		
		if($activeRecord->type == 1){
			$filePath = \wp\framework::getFramework()->getAppRoot() . "/_repository/" . $activeRecord->contenu;
			
			return "<a href=\"" . \wp\framework::getFramework()->getAppsRoot() . "/_utilities/download.php?file=" . $activeRecord->contenu . "&mime=\"" . $activeRecord->mimetype_id . "\"  target=\"_new\" title=\"" . $activeRecord->description . "\">" . $activeRecord->contenu . "</a>";
		} else {
			return "<a href=\"" . $activeRecord->contenu . "\"  target=\"_new\" title=\"" . $activeRecord->description . "\">" . $activeRecord->contenu . "</a>";
		}
		return;
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
		->setCss("col-sm-3",true)
		->setCss("col-sm-12")
		->isRequired()
		->setCss("form-control")
		->setValue($this->mapper->getObject()->titre);
		
		$this->addToFieldset($field);

		// Type de ressource, seulement et uniquement si INSERT
		if(\wp\Helpers\urlHelper::context() == "INSERT" || \wp\Helpers\urlHelper::context() == "add"){
			$field = new \wp\formManager\Fields\group();
			$field->setId("frmType")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "type")
			->setLabel("Type")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->isRequired()
			->setCss("panel")
			->setCss("panel-default")
			->setCss("col-sm-12")
			->setDefault($this->mapper->getObject()->type,0);
			
			// Ajoute les objets au groupe...
			$radio = new \wp\formManager\Fields\radio();
			$radio->setId("frmIsLink")
			->setLabel("Lien")
			->setCss("type")
			->setValue(0);
			$field->add($radio);
			
			$radio = new \wp\formManager\Fields\radio();
			$radio->setId("frmIsFile")
			->setLabel("Document")
			->setCss("type")
			->setValue(1);
			$field->add($radio);
	
			$radio = new \wp\formManager\Fields\radio();
			$radio->setId("frmIsOther")
			->setLabel("Autre")
			->setCss("type")
			->setValue(2);
			$field->add($radio);
			
			$this->addToFieldset($field);
			
			// Ajoute le contrôle sur le clic sur le bouton radio
			$this->clientRIA .= "
				$(\".type\").on(\"click\",function(){
						if($(this).val() == 1){
							$(\"#frmContenuStd\").parent().parent().addClass(\"hidden-lg\").addClass(\"hidden-md\").addClass(\"hidden-sm\").addClass(\"hidden-xs\");
							$(\"#frmContenuFile\").parent().parent().removeClass(\"hidden-lg\").removeClass(\"hidden-md\").removeClass(\"hidden-sm\").removeClass(\"hidden-xs\");
							$(\"#frmContenuStd\").removeAttr(\"required\");
							$(\"#frmContenuFile\").attr(\"required\",\"required\");
						} else {
							$(\"#frmContenuFile\").parent().parent().addClass(\"hidden-lg\").addClass(\"hidden-md\").addClass(\"hidden-sm\").addClass(\"hidden-xs\");
							$(\"#frmContenuStd\").parent().parent().removeClass(\"hidden-lg\").removeClass(\"hidden-md\").removeClass(\"hidden-sm\").removeClass(\"hidden-xs\");
							$(\"#frmContenuFile\").removeAttr(\"required\");
							$(\"#frmContenuStd\").attr(\"required\",\"required\");
						}
					}
				);
			";
		} else {
			// Champ de type caché pour stocker l'information
			$field = new \wp\formManager\Fields\hidden();
			$field->setId("frmType")
				->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "type")
				->setValue($this->mapper->getObject()->type);
		}
		
		// Définition du contenu, dépend du type
		if(!is_null($this->mapper->getObject()->type)){
			if($this->mapper->getObject()->type == 0){
				$label = "Lien";
			} elseif($this->mapper->getObject()->type == 2){
				$label = "Autre ressource";
			} else {
				$label = "Document";
			}
		} else {
			$label = "Lien";
		}
		
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmContenuStd")
		->setName("contenuStd")
		->setLabel($label)
		->setMaxLength(255)
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->setCss("form-control")
		->isRequired(true)
		->setValue($this->mapper->getObject()->contenu);
		
		$this->addToFieldset($field);

		// Champ de type document
		$field = new \wp\formManager\Fields\upload();
		$field->setId("frmContenuFile")
		->setName("contenuFile")
		->setLabel("Document")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-12")
		->setGroupCss("hidden-lg")
		->setGroupCss("hidden-md")
		->setGroupCss("hidden-sm")
		->setGroupCss("hidden-xs")
		->setRIAScript();
		
		$this->addToFieldset($field);
		
		$this->clientRIA .= $field->getRIAScript();
				
		$field = new \wp\formManager\Fields\textarea();
		$field->setId("frmDescription")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "description")
		->setLabel("Description")
		->setCss("control-label",true)
		->setCss("col-sm-3",true)
		->setCss("form-control")
		->setGroupCss("col-sm-12")
		->setValue($this->mapper->getObject()->description);
		
		$this->addToFieldset($field,$fieldset);

		
		// Récupère le mapper sur la table parente
		$parentMapper = new \arcec\Mapper\arboressourceMapper();
		
		if(\wp\Helpers\urlHelper::context() == "INSERT"){
			$default = 0;	
		} else {
			$default = $this->mapper->getObject()->arboressource_id;
		}
		
		$field = new \wp\formManager\Fields\popup();
		$field->setId("frmArbo")
			->setName($this->mapper->getTableName() . ".arboressource_id")
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
	
	protected function beforeInsert(){
		if(\wp\Helpers\httpQueryHelper::get($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "type") != 1){
			$this->mapper->contenu = \wp\Helpers\httpQueryHelper::get("contenuStd");
			$htmlMapper = new \wp\dbManager\Mapper\mimeHTMLMapper();
			$this->mapper->mimetype_id = $htmlMapper->getId();
		} else {
			// Contrôle de validité du type de doucment envoyé
			$uploadFile = parent::getField("frmContenuFile")->getPostedData();
			if(!is_null($uploadFile)){
				if($uploadFile->check()){
					// Récupération du nom du document à traiter
					$mimes = new \wp\dbManager\Mapper\mimetypeMapper();
					$mimes->searchBy("type",$uploadFile->mimeType());
					$mimes->set($mimes->getNameSpace());
						
					$uploadFile->repository(\wp\framework::getFramework()->getAppRoot() . "/_repository/");
					$this->mapper->contenu = $uploadFile->name();
					$this->mapper->poids = $uploadFile->size();
					$this->mapper->mimetype_id = $uploadFile->mimeId();
					
					if($uploadFile->process($this->mapper->contenu)){
						return true;
					}
					return false;
				}
			}
		}
		return true;		
		
	}
	
	protected function beforeUpdate(){
		if(\wp\Helpers\httpQueryHelper::get($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "type") != 1){
			$this->mapper->contenu = \wp\Helpers\httpQueryHelper::get("contenuStd");
		} else {
			// Contrôle de validité du type de doucment envoyé
			$uploadFile = parent::getField("frmContenuFile")->getPostedData();
			if(!is_null($uploadFile)){
				echo "Transfert du fichier " . $uploadFile->name() . "<br />\n";
				if($uploadFile->check()){
					// Récupération du nom du document à traiter
					$mimes = new \wp\dbManager\Mapper\mimetypeMapper();
					$mimes->searchBy("type",$uploadFile->mimeType());
					$mimes->set($mimes->getNameSpace());
					
					$uploadFile->repository(\wp\framework::getFramework()->getAppRoot() . "/_repository/");
					$this->mapper->contenu = $uploadFile->name();
					$this->mapper->poids = $uploadFile->size();
					$this->mapper->mimetype_id = $uploadFile->mimeId();					
				}
			}			
		}
		return true;
	}
	
	protected function beforeDelete(){}
	protected function afterInsert(){}
	protected function afterUpdate(){}
	protected function afterDelete(){}
}
?>