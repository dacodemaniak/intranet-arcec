<?php
/**
 * @name updatePhase.class.php Service de mise à jour des phases d'un dossier
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Ajax
 * @version 1.0
**/
namespace arcec\Ajax;

class updatePhase implements \wp\Ajax\ajax{
	/**
	 * Tableau de résultat à retourner
	 * @var array
	**/
	private $result;
	
	/**
	 * Mapper sur la table de suivi
	 * @var object
	**/
	private $mapper;
	
	/**
	 * Stocke les clés à partir desquelles se fera la mise à jour
	 * @var array
	**/
	private $keys;
	
	/**
	 * Stocke les valeurs à mettre à jour
	 * @var array
	**/
	private $values;
	
	
	public function __construct(){
		$this->result = array();
		$this->mapper = new \arcec\Mapper\suiviMapper();
	}
	
	public function keys($keys){
		$this->keys = $keys;
		
		return $this;
	}
	
	public function values($values){
		$this->values = $values;
		
		return $this;
	}
	public function process(){
		$updates = array();
		
		$update = "UPDATE " . $this->mapper->getTableName() . " SET ";
		foreach($this->values as $col => $value){
			if($this->mapper->isForeignKey($col)){
				$update .= $col . "= :" . $col . ","; 
			} else {
				$update .= $this->mapper->getColumnPrefix() . $col . "= :" . $col . ",";
			}
			$updates[$col] = $value;
		}
		$update = substr($update,0,strlen($update)-1);
		
		// Ajoute les contraintes
		$update .= " WHERE ";
		foreach($this->keys as $col => $value){
			if($col != "programme_id"){
				$update .= $col . " = :" . $col . " AND ";
				$updates[$col] = $value;
			}
		}
		
		$update = substr($update,0,strlen($update)-5);
		
		#begin_debug
		#echo "Exécute la requête $update avec <br />\n";
		#var_dump($updates);
		#echo "<br />\n";
		#end_debug
		
		$dbInstance = \wp\dbManager\dbConnect::dbInstance();
		$query = $dbInstance->getConnexion()->prepare($update);
		$query->execute($updates);
		
		$this->result["statut"] = 1;
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