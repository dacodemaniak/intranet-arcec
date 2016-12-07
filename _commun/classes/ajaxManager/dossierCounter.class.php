<?php
/**
 * @name dossierCounter.class.php Services de comptabilisation de dossiers
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Ajax
 * @version 1.0
**/
namespace arcec\Ajax;

class dossierCounter implements \wp\Ajax\ajax{
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
	 * Colonnes à afficher dans la liste de résultat
	 * @var array
	 */
	private $cols;
	
	/**
	 * Contenu permettant la recherche de type %
	 * @var string
	 */
	private $content;
	
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
	 * @param string $param
	 * @return \arcec\Ajax\dossierCounter
	 */
	public function setParams($param){
		$params = explode("|",$param);
		foreach($params as $select){
			if($select != ""){
				$selection = explode(":",$select);
				if($selection[1] != 0){
					if(strtolower($selection[0]) == "cns")
						$selection[0] = "porteurcnscoord";
						
					$this->cols[strtolower($selection[0])] = $selection[1];
				}
			}
		}
		
		return $this;
	}
	
	public function process(){
		foreach($this->cols as $col => $value){
			$searchColumn[] = array(
				"column" => $col,
				"operateur" => "="
			);
			$searchValue[]= $value;
		}
		$this->mapper->searchBy($searchColumn,$searchValue);
		
		$this->result["data"] = $this->mapper->count();
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