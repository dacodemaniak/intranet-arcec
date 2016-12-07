<?php
/**
 * @name suiviDossier.class.php : Services de suivi des dossiers sous forme d'onglets
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec
 * @version 1.0.1
**/

namespace arcec;

class suiviDossier extends \wp\formManager\admin {
	
	/**
	 * Objet de type boîte à onglets permettant de regrouper les différents champs
	 * @var object
	 */
	private $tabContent;

	/**
	 * Instance du dossier courant pour définition de l'en-tête
	 * @var object
	 **/
	private $dossier;
	
	/**
	 * Onglet par défaut à afficher
	 * @var string
	 */
	private $defaultTab;
	
	/**
	 * Ajoute une boîte à onglets avec les formulaires de gestion associés
	**/
	public function __construct(){
		
		$this->tabContent = new \wp\htmlManager\tabContent();
		
		$this->setTemplateName("./dossier/adminTabs.tpl");
		
		$this->tabContent->add("accueil")
			->add("accompagnement")
			->add("programme")
			->add("entreprise")
			->add("rapports")
			->add("documents")
		;
		
		$this->tabContent->setCss("tab-content","contentCssClasses");

		$this->module = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
	
		$this->setId($this->module)
		->setName($this->module);
		
		$this->id = \wp\Helpers\urlHelper::context("id");
		
		$this->mapper = new \arcec\Mapper\dossierMapper();
		
		$this->setDossierHeader();
		
		$this->defaultTab = \wp\Helpers\httpQueryHelper::get("tab","accueil");
		
		$this->set();
		
		\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Suivi des dossiers");
		\wp\Tpl\templateEngine::getEngine()->setVar("tabs",$this->tabContent);
		
		if(!$this->isValidate()){
			$this->setAction(array("com" => $this->module,"context"=>\wp\Helpers\urlHelper::toContext()));
		} else {
			$this->process();
		}
	}

	private function setDossierHeader(){
		$this->dossier = new \arcec\dossierHeader(\wp\Helpers\urlHelper::context("id"));
	}
	
	public function getDossier(){
		return $this->dossier;
	}
	
	protected function set(){
		
		if(\wp\Helpers\urlHelper::context() == "UPDATE" || \wp\Helpers\urlHelper::context() == "DELETE" || \wp\Helpers\urlHelper::context() == "upd" || \wp\Helpers\urlHelper::context() == "del"){
			if(!$this->isSubForm()){
				$this->mapper->setId($this->id);
				$this->mapper->set($this->mapper->getNameSpace());
			
				// Crée le champ caché pour le stockage de la clé primaire
				$field = new \wp\formManager\Fields\hidden();
				$field->setId($this->mapper->getTableName() . ".primary")
				->setName($this->mapper->getTableName() . ".primary")
				->setValue($this->id);
				$this->addToFieldset($field);
					
				// Détermine l'état des champs à partir de la valeur de dossier_ca
				if($this->mapper->getObject()->ca != ""){
					$this->globalDisabled = true;
				}
			}
		}
		
		$tabContent = new \arcec\setDossier(true,"accueil");
		
		$tabContent->setTemplateName("dossier/pager.tpl");
		
		$tab = $this->tabContent->getTabInstance("accueil");
		$tab->setContent($tabContent)
			->setTitle("Accueil")
			->setCss("tab-pane");

		if($this->defaultTab == "accueil")
			$tab->toggleActive()
				->setCss("active")
				->setCss("fade")
				->setCss("in");
				
		$tabContent = new \arcec\setAccompagnement(true);
		
		$tab = $this->tabContent->getTabInstance("accompagnement");
		$tab->setContent($tabContent)
			->setTitle("Accompagnement")
			->setCss("tab-pane")
		;
		
		if($this->defaultTab == "accompagnement")
			$tab->toggleActive()
				->setCss("active")
				->setCss("fade")
				->setCss("in");
		
		// Onglet spécifique Entreprise
		$tabContent = new \arcec\setEntreprise(true,"entreprise");
				
		$tab = $this->tabContent->getTabInstance("entreprise");
		$tabContent->setTemplateName("dossier/pager.tpl");
				
		$tab->setContent($tabContent);
		$tab->setTitle("Entreprise")
		->setCss("tab-pane")
		;
		
		if($this->defaultTab == "entreprise")
			$tab->toggleActive()
				->setCss("active")
				->setCss("fade")
				->setCss("in");
		
		// Onglet suivi
		$tabContent = new \arcec\suivi($this->mapper->getObject());
		$tab = $this->tabContent->getTabInstance("programme");
		$tab->setContent($tabContent);
		$tab->setTitle("Suivi")
			->setCss("tab-pane");

		if($this->defaultTab == "programme")
			$tab->toggleActive()
				->setCss("active")
				->setCss("fade")
				->setCss("in");
		
		// Script de mise à jour des étapes en ajax
		$this->clientRIA .= $tabContent->setClientRIA();
			
		// Onglets Rapports d'entretien
		$tabContent = new \arcec\listeRapport($this->id);
			
		$tab = $this->tabContent->getTabInstance("rapports");
		$tab->setContent($tabContent);
		$tab->setTitle("Rapports d'entretien")
			->setCss("tab-pane");
		
		if($this->defaultTab == "rapports")
			$tab->toggleActive()
				->setCss("active")
				->setCss("fade")
				->setCss("in");			
			
			
		// Onglets documents
		$tabContent = new \arcec\listeDocument();
		
		$tab = $this->tabContent->getTabInstance("documents");
		$tab->setContent($tabContent);
		$tab->setTitle("Documents")
			->setCss("tab-pane");

		if($this->defaultTab == "documents")
			$tab->toggleActive()
				->setCss("active")
				->setCss("fade")
				->setCss("in");

		$js = new \wp\htmlManager\js();
		$js->addPlugin("jquery-ui");
				
		\wp\Tpl\templateEngine::getEngine()->addContent("js",$js);
				
		
		$this->toControls();
		
		$this->toValidation($this->getId());

		// Ajoute les CSS
		$css = new \wp\htmlManager\css();
		$css->addSheet("jquery-ui");
		$css->addSheet("jquery-ui.structure");
		$css->addSheet("jquery-ui.theme");
		
		\wp\Tpl\templateEngine::getEngine()->addContent("css",$css);
		
		return;
	}

	/**
	 * Surcharge de la méthode pour retourner vers le formulaire de recherche des Dossiers
	 * @see \wp\formManager\admin::getCancelBtn()
	 */
	public function getCancelBtn(){
		$button = new \wp\formManager\Fields\linkButton();
		$button->setId("btnCancel")
		->setTitle("Retour à la liste")
		->addAttribut("role","button")
		->setValue("./index.php?com=listeDossier")
		->setCss("btn")
		->setCss("btn-default")
		->setLabel("Retour")
		;
		return $button;
	}

	/**
	 * Traite le formulaire
	 * @see \wp\formManager\admin::process()
	 * @todo Créer la méthode pour définir l'URL d'erreur (rester sur place et afficher le message)
	 */
	protected function process(){
		$columns					= array();
		$otherMapperField			= array();
		
		$this->before();
		
		// Parcourir l'ensemble des fieldsets de l'ensemble des onglets
		foreach($this->tabContent->getTabs() as $id => $content){
			$subForm = $content->getContent();
			if(is_object($subForm)){
				foreach($subForm->getCollection() as $fieldset){
					foreach($fieldset->getCollection() as $field){
						#begin_debug
						#echo "Traite un champ de type " . $field->getType() . " avec le nom : " . $field->getName() . "<br />\n";
						#end_debug
						
						if($field->isPosted()){
								
							if($column = $this->mapper->isColumn($field->getName())){
								if(method_exists($field,"getPostedData")){
										
									#begin_debug
									#echo "Et retourne la valeur : " . $field->getPostedData() . "<br />\n";
									#end_debug
										
									$values[$column] = $field->getPostedData();
									$columns[] = $column;
								} else {
									#begin_debug
									#echo "La méthode getPostedData n'existe pas pour le champ : " . $field->getName() . "<br />\n";
									#end_debug
								}
							} else {
								// Il s'agit d'un champ pour un autre mapping
								$otherMapperField[] = $field;
							}
						}
					}				
				}
			}
		}

		// Ajoute éventuellement les champs non présents mais avec des valeurs par défaut
		foreach($this->mapper->getScheme() as $column => $definition){
			if(!array_key_exists("autoincrement",$definition)){
				if(array_key_exists("default",$definition)){
					if(!in_array($column,array_keys($values))){
						$values[$column] = $definition["default"];
						$columns[] = $column;
					}
				}
			}
		}

		#begin_debug
		#var_dump($_POST);
		#echo "<br />\nSuivi dossier<br />\n";
		#var_dump($values);
		#var_dump($columns);
		#die();
		#end_debug
		
		// Affecter les données au mapper
		foreach($values as $column => $value){
			$this->mapper->{$column} = $value;
		}
		
		// Affecter l'éventuel identifiant
		if($id = $this->getPrimaryVal()){
			$this->mapper->setId($id);
		}
		
		if($this->tableId = $this->mapper->save($columns)){
			$this->after();
	
			// Retourne à l'index de traitement courant
			$locationParams = array(
					"com" => "suiviDossier",
					"id" => $this->tableId,
					"context" => "UPDATE",
					"frmPorteurSupport" => \wp\Helpers\httpQueryHelper::get("frmPorteurSupport"),
					"frmEntrepriseSupport" => \wp\Helpers\httpQueryHelper::get("frmEntrepriseSupport")
			);
			$location = \wp\Helpers\urlHelper::setAction($locationParams);
			#die("Redirection vers : " . $location);
			header("Location:" . $location);
			return;
		}
		
		die("Erreur dans la mise à jour de la table..." . $this->mapper->getError());
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
	
	/**
	 * Exécute une action avant insertion dans la base de données
	 **/
	protected function beforeInsert(){}
	
	/**
	 * Exécute une action avant mise à jour dans la base de données
	**/
	protected function beforeUpdate(){}
	
	/**
	 * Exécute une action avant suppression de la base de données
	**/
	protected function beforeDelete(){}
	
	/**
	 * Exécute une action après insertion dans la base de données
	**/
	protected function afterInsert(){}
	
	/**
	 * Exécute une action après mise à jour de la base de données
	**/
	protected function afterUpdate(){
		// Y-a-t-il eu changement de programme d'accompagnement
		$suivi = new \arcec\Mapper\suiviMapper();
		$suivi->searchBy("dossier_id",$this->tableId);
		$suivi->set($suivi->getNameSpace());
		
		$this->mapper->setId($this->tableId);
		$this->mapper->set($this->mapper->getNameSpace());
		
		if($suivi->getObject()->programme_id != $this->mapper->getObject("porteurprg")){
			// Changement de programme, on modifie les données
			$update = "
				UPDATE " . $suivi->getTableName() . " SET programme_id = :programme WHERE dossier_id = :id;
			";
			$dbInstance = \wp\dbManager\dbConnect::dbInstance();
			$query = $dbInstance->getConnexion()->prepare($update);
			$query->execute(array("id" => $this->tableId,"programme" => $this->mapper->getObject("porteurprg")));
		}
		
		// Vérifie le prescripteur et ajoute la ligne le cas échéant dans le carnet d'adresses
		$dossier = new \arcec\Mapper\dossierMapper();
		$dossier->setId($this->tableId);
		$dossier->set($dossier->getNameSpace());
		
		$activeDossier = $dossier->getObject();
		
		// Instancie un nouvel objet Annuaire
		$annuaire = new \arcec\annuaire();
		$annuaire->setDossier($activeDossier);
		
		if($activeDossier->porteurprs != 0){
			// Instancie un objet annuaire pour
			$annuaire->addFromParam("PRS");
		}
		return;
	}
	
	/**
	 * Exécute une action après suppression dans la base de données
	**/
	protected function afterDelete(){}
}