<?php
/**
 * @name paramEDOMapper.class.php Mapping de données sur la table dbPrefix_parambase avec le code Parent CNS ou id 2
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Mapper
 * @version 1.0
**/
namespace arcec\Mapper;

class paramCNSMapper extends \arcec\Mapper\parambaseMapper {
	/**
	 * Définit si les mots clés "*", "tous" sont activés pour ce mapper
	 * @var boolean
	 */
	private $enableAll;
	
	/**
	 * Détermine si on affiche aussi les conseillers "inactifs"
	 * @var boolean
	 */
	private $addInactive;
	
	public function __construct(){

		parent::__construct();
		
		/*
		$classParts = explode("\\",__CLASS__);
		$className = array_pop($classParts);
		
		$this->className = substr($className,0,strpos($className,"Mapper"));
		*/
		
		$mapper = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
		
		$code = substr($mapper,5,strpos($mapper,"Mapper")-5);
		
		$this->definitionMapper->searchBy("code",$code);
		$this->definitionMapper->set($this->namespace . "\\");
		
		$this->searchBy("param_definition_id",$this->definitionMapper->getObject()->id);
		
		// Ajoute la clause de tri sur les libellés longs
		$this->order("libellelong","asc");
		
		$this->enableAll = true;
		$this->addInactive = false;
		
		$this->usage();
		
		//$this->searchBy("param_definition_id",2); // Restreint la liste aux paramètres de base
		
	}
	
	/**
	 * Définit ou retourne le statut de récupération de tous les conseillers
	 * @param boolean $enable
	 */
	public function enableAll($enable=null){
		if(!is_null($enable) && is_bool($enable)){
			$this->enableAll = $enable;
			return $this;
		}
		return $this->enableAll;
	}
	
	/**
	 * Détermine le statut de récupération des conseillers actifs
	 * @param boolean $add
	 */
	public function addInactive($add=null){
		if(!is_null($add) && is_bool($add)){
			$this->addInactive = $add;
			if(!$this->addInactive){
				$this->searchBy("actif",1);
			} else {
				$this->removeSearch("actif");
			}
			return $this;
		}
		return $this->addInactive;
	}
	/**
	 * Efface les contraintes de recherche
	 **/
	public function clearSearch(){
		$this->clause = array();
		$this->collection = array();
		$this->searchBy("param_definition_id",$this->definitionMapper->getObject()->id);
	}

	/**
	 * (non-PHPdoc)
	 * @see \arcec\Mapper\parambaseMapper::usage()
	 */
	protected function usage(){
		$this->usage["dossier"] = array(
			"cns",
			"porteurcnscoord"
		);
		$this->usage["rapport"] = array(
			"cns"
		);
		$this->usage["suivi"] = array(
			"conseiller_id"
		);
		$this->usage["eventperson"] = array(
			"person"
		);
		$this->usage["event"] = array(
				"createur"
		);
	}
}
?>