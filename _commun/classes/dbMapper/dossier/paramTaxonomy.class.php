<?php
/**
 * @name paramTaxonomy.class.php Services de récupération de la taxonomie d'un paramètre (Hiérarchie)
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec
 * @version 1.0
**/

namespace arcec;

class paramTaxonomy {
	
	/**
	 * Type de mapper de base à traiter
	 * @var string
	**/
	private $mapperType;
	
	/**
	 * Objet de mapping sur la table de base
	 * @var object
	 */
	private $mapper;
	
	/**
	 * Identifiant du noeud enfant de base
	 * @var int
	**/
	private $id;
	
	/**
	 * Ancêtre à atteindre pour la définition du fil
	 * @var int
	**/
	private $ancestor;
	
	/**
	 * Stocke le fil de la taxonomie associée
	 * @var array
	 */
	private $fil;
	
	/**
	 * Définit le titre de l'élément racine du fil
	 * @var string
	**/
	private $homeTitle;
	
	/**
	 * Classe CSS à associer à la structure en sortie
	 * @var string
	 */
	private $css = "breadcrumb";
	
	/**
	 * Instancie un nouvel objet de tpe paramTaxonomy
	**/
	public function __construct(){
		$this->mapper("base");
		$this->fil = array();
	}
	
	/**
	 * Détinit ou retourne le type de mapper de base
	 * @param string $type (optionnel) Type du mapper de base
	 * @return \arcec\paramTaxonomy|string
	**/
	public function mapper($type=null){
		if(!is_null($type)){
			$this->mapperType = $type;
			
			$factory = new \wp\Patterns\factory("\\arcec\\Mapper\\param" . $type . "Mapper");
			$this->mapper = $factory->addInstance();
			
			return $this;
		}
		
		return $this->mapperType;
	}
	
	/**
	 * Définit ou retourne l'identifiant de base du mapper
	 * @param int $id (optionnel) Identifiant du paramètre de base
	 * @return \arcec\paramTaxonomy|number
	 */
	public function id($id=null){
		if(!is_null($id)){
			$this->id = $id;
			if(!is_null($this->mapper))
				$this->mapper->setId($id);
			
			return $this;
		}
		
		return $this->id;
	}
	
	/**
	 * Définit ou retourne le noeud racine de la taxonomie
	 * @param int $id
	 * @return \arcec\paramTaxonomy|number
	 */
	public function ancestor($id=null){
		if(!is_null($id)){
			$this->ancestor = $id;
			return $this;
		}
		return $this->ancestor;
	}
	
	/**
	 * Définit ou retourne le titre de la racine à traiter
	 * @param string $title
	 * @return \arcec\paramTaxonomy|string
	 */
	public function home($title=null){
		if(!is_null($title)){
			$this->homeTitle = $title;
			return $this;
		}
		return $this->homeTitle;
	}
	/**
	 * Retourne le fil d'Ariane d'un paramètre dépendant
	**/
	public function toBreadCrumb(){
		if(!is_null($this->mapper)){
			$this->mapper->set("\\arcec\\Mapper\\");
			$activeRecord = $this->mapper->getObject();
			
			$this->fil[] = $activeRecord->libellelong;
			
			$this->getAncestor($activeRecord->param_definition_id);
			
			if(!is_null($this->homeTitle)){
				$this->fil[] = $this->homeTitle;
			}
			
			$this->fil = array_reverse($this->fil);
			
			#\wp\Tpl\templateEngine::getEngine()->setTemplate();
			\wp\Tpl\templateEngine::getEngine()->get()->assign(
				array(
					"css" => $this->css,
					"fil" => $this->fil
				)
			);
			
			return \wp\Tpl\templateEngine::getEngine()->get()->fetch(\wp\framework::getFramework()->getAppsRoot() . "/templates/breadcrumb.tpl");
		}
	}
	
	private function getAncestor($dependentId){
		$this->mapper->clearSearch();
		
		$this->mapper->searchBy("defcode",$dependentId);
		$this->mapper->set("\\arcec\\Mapper\\");
		$activeRecord = $this->mapper->getObject();
		
		if($this->ancestor != $activeRecord->id){
			$this->fil[] = $activeRecord->libellelong;
			// Remonte à l'ancêtre précédent
			$this->getAncestor($activeRecord->param_definition_id);		
		}

	}
	
	/**
	 * (non-PHPdoc)
	 * @see \wp\dbManager\dbMapper::usage()
	 **/
	protected function usage(){}
}
?>