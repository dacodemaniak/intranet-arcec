<?php
/**
 * @name taxonomyChecker.class.php Services de contrôle de création de taxonomie
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Ajax
 * @version 1.0
**/
namespace arcec\Ajax;

class taxonomyChecker implements \wp\Ajax\ajax{
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
		
		if($this->mapper->getObject()->defcode != 0){
			$this->result["statut"] = 1;
			$this->result["dependency"] = $this->mapper->getObject()->defcode;
		} else {
			$this->result["statut"] = 0;
			$this->result["dependency"] = 0;			
		}
	}
	
	/**
	 * Retourne la liste des résultats
	 * @see \wp\Ajax\ajax::getResult()
	 */
	public function getResult(){
		return $this->result;
	}
}
?>