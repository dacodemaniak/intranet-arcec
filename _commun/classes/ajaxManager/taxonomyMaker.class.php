<?php
/**
 * @name taxonomyMaker.class.php Services de création de taxonomie sur les paramètres
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Ajax
 * @version 1.0
**/
namespace arcec\Ajax;

class taxonomyMaker implements \wp\Ajax\ajax{
	/**
	 * Tableau de résultat à retourner
	 * @var array
	**/
	private $result;
	
	/**
	 * Instance d'objet de mapping de données
	 * @var object
	 */
	private $mapper;
	
	/**
	 * Identifiant de la table de mapping concernée
	 * @var int
	 */
	private $id;

	
	public function __construct(){
		$this->result = array();
	}
	
	/**
	 * Définit l'objet de mapping de données
	 * @param string $mapperName
	 * @return \wp\Ajax\dataLoader
	 */
	public function setMapper($mapperName){
		$factory = new \wp\Patterns\factory($mapperName);
		$this->mapper = $factory->addInstance();
		
		return $this;
	}
	
	/**
	 * Définit les paramètres de récupération des données
	 * @param int $param
	 * @return \arcec\Ajax\dossierCounter
	 */
	public function setParams($param){
		$this->id = $param;
		
		return $this;
	}
	
	public function process(){
		$this->mapper->setId($this->id);
		
		$this->mapper->set($this->mapper->getNameSpace());
		
		$this->result["root"] = array("id" => $this->mapper->getObject()->id,"content" => $this->mapper->getObject()->libellelong);
		
		$this->result["taxonomy"] = $this->setDependencies($this->mapper->getObject()->defcode);
		
	}
	
	/**
	 * Retourne la liste des résultats
	 * @see \wp\Ajax\ajax::getResult()
	 */
	public function getResult(){
		return $this->result;
	}
	
	/**
	 * Alimente la liste des dépendances à partir d'un paramètre donné
	 * @param int $id
	 */
	private function setDependencies($id){
		$base = new \arcec\Mapper\paramdefinitionMapper();
		$base->setId($id);
		$base->set($base->getNameSpace());
		
		if(file_exists("../dbMapper/dossier/param" . $base->getObject()->code . "Mapper.class.php")){
			$factory = new \wp\Patterns\factory("\\arcec\\Mapper\\param" . $base->getObject()->code . "Mapper");
			$mapper = $factory->addInstance();
			
			$mapper->set($mapper->getNameSpace());
			
			foreach($mapper->getCollection() as $object){
				$element["id"] = $object->id;
				$element["content"] = $object->libellelong;
				
				if($object->defcode != 0){
					$element["children"] = $this->setDependencies($object->defcode);
				} else {
					$element["children"] = null;
				}
				$taxonomy[] = $element;
				$element = array();
			}
			
			return $taxonomy;
		}
	}
}
?>