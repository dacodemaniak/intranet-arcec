<?php
/**
 * @name getPorteur.class.php Service de récupération de données du porteur de projet
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Ajax
 * @version 1.0
**/
namespace arcec\Ajax;

class getPorteur implements \wp\Ajax\ajax{
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
		$this->mapper = new \arcec\Mapper\porteurMapper();
	}
	
	/**
	 * Définit les paramètres de récupération des données
	 * @param string $param
	 * @return \arcec\Ajax\dossierCounter
	 */
	public function setDossier($dossier){
		$this->mapper->searchBy("dossier_id",$dossier);
		
		return $this;
	}
	
	public function process(){
		$this->mapper->set("\\arcec\\Mapper\\");
		
		$data = $this->mapper->getObject();
		
		$this->result["id"] = $data->id;
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