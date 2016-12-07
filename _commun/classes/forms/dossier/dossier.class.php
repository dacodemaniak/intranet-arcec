<?php
/**
 * @name dossier.class.php : Classe abstraite pour la gestion des dossiers ARCEC
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Dossier
 * @version 1.0
 **/
namespace arcec\Dossier;

abstract class dossier extends \wp\formManager\form{
	/**
	 * Objet de mapping sur la table prefix_dossier
	 * @var object
	**/
	protected $dossierMapper;
	
	/**
	 * Nom du module de gestion de dossier courant
	 * @var string
	**/
	protected $module;
	
	/**
	 * Espace de nom pour la gestion des dossiers
	 * @var string
	 */
	protected $namespace;
	
	
	public function isValidate(){}
	
	/**
	 * Définit les formulaires
	 * @see \wp\formManager\form::set()
	 */
	public function set(){}
	
	public function process(){}
	
	/**
	 * Instancie un mapping sur la table prefix_dossier
	**/
	protected function setDossier(){
		$this->dossierMapper = new \arcec\Mapper\dossierMapper();
		return;
	}
	
	public function getTotalRows(){
		return $this->dossierMapper->count();
	}
	public function getNameSpace(){
		if(is_null($this->namespace)){
			$this->namespace = __NAMESPACE__;
		}
		
		return $this->namespace . "\\";
	}
	
	/**
	 * Définit le nom du modèle à utiliser pour l'affichage du formulaire
	**/
	protected function setTemplate(){
		$mapper = new \wp\dbManager\Mapper\moduleMapper();
		$mapper->searchBy("component", $this->module);
		$mapper->set();
		$this->setTemplateName("./dossier/" . $mapper->getObject()->template . ".tpl");
		//$this->setTemplateName("dossier/" . $mapper->getObject()->template . ".tpl");
	}
	
	/**
	 * Ajoute les contraintes de filtrage sur les dossiers
	**/
	protected function filter(){
		$columns 				= array();
		$values 				= array();
		
		if($id = \wp\Helpers\httpQueryHelper::get($this->dossierMapper->getTableName(). "." . $this->dossierMapper->getColumnPrefix() . "id")){
			$this->dossierMapper->setId($id);
			return;
		}
		
		if($id = \wp\Helpers\httpQueryHelper::get("id")){
			$this->dossierMapper->setId($id);
			return;			
		}
		
		if($etd = \wp\Helpers\httpQueryHelper::get($this->dossierMapper->getTableName(). "." . $this->dossierMapper->getColumnPrefix() . "etd")){
			$columns[] = array(
				"column" => "etd",
				"operateur" => "="
			);
			$values[] = $etd;
		}
		
		if($edo = \wp\Helpers\httpQueryHelper::get($this->dossierMapper->getTableName(). "." . $this->dossierMapper->getColumnPrefix() . "edo")){
			$columns[] = array(
					"column" => "edo",
					"operateur" => "="
			);
			$values[] = $edo;
		}
		
		if($cns = \wp\Helpers\httpQueryHelper::get($this->dossierMapper->getTableName(). "." . $this->dossierMapper->getColumnPrefix() . "porteurcnscoord")){
			$columns[] = array(
					"column" => "porteurcnscoord",
					"operateur" => "="
			);
			$values[] = $cns;
		}
		
		$this->dossierMapper->searchBy($columns,$values);
		
		return;
	}
}

?>