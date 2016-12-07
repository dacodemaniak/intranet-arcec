<?php
/**
 * @name paramRemove.class.php Services de suppression logique des tables de paramètres
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package wp\Ajax
 * @version 1.0
**/
namespace arcec\Ajax;

class paramRemove implements \wp\Ajax\ajax{
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
	 * Identifiant de la ligne à supprimer
	 * @var int
	 */
	private $id;
	
	public function __construct(){
		$this->result["statut"] = 0;
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
	 * Définit l'identifiant de la ligne à supprimer à partir du mapper
	 * @param int $id
	 * @return \wp\Ajax\itemDelete
	 */
	public function setId($id){
		$this->id = $id;
		return $this;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \wp\Ajax\ajax::process()
	 */
	public function process(){
		$this->mapper->setId($this->id);
		$this->mapper->set($this->mapper->getNameSpace());
		$activeRecord = $this->mapper->getObject();
		$activeRecord->utilise = 0;
		$activeRecord->datesuppression = \wp\Helpers\dateHelper::today();
		if($activeRecord->rawUpdate()){
			$this->result["statut"] = 1;
			$this->result["data"] = array("id" => $this->id);
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