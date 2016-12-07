<?php
/**
 * @name getDuree.class.php Service de récupération de la durée estimée pour un type d'événement
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Ajax
 * @version 1.0
**/
namespace arcec\Ajax;

class getDuree implements \wp\Ajax\ajax{
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
	
	
	public function __construct(){
		$this->result = array();
		$this->mapper = new \arcec\Mapper\typeeventMapper();
	}
	
	/**
	 * Définit l'identifiant du type de durée sélectionnée
	 * @param string $param
	 * @return \arcec\Ajax\getDuree
	 */
	public function setType($type){
		$this->mapper->setId($type);
		
		return $this;
	}
	
	public function process(){
		$this->mapper->set($this->mapper->getNameSpace());
		
		if($data = $this->mapper->getObject()){
			$this->result["heure"] = \wp\Helpers\timeHelper::toHours($data->dureeestimee,true);
			$this->result["minute"] = \wp\Helpers\timeHelper::toMinutes($data->dureeestimee,true);
		} else {
			$this->result["heure"] = 0;
			$this->result["minute"] = 15;
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