<?php
/**
 * @name eventpersonMapper.class.php Mapper sur la table dbPrefix_eventperson Participants à un événement
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Mapper
 * @version 1.0
**/

namespace arcec\Mapper;

class eventpersonMapper extends \wp\dbManager\dbMapper {
	/**
	 * @property int $id Identifiant du type
	 * @property int $person ID du participant
	 * @property string $mapper Préfixe du mapper à utiliser pour récupérer le participant
	 * @property int $event_id Identifiant de l'événement de référence
	**/
	
	/**
	 * Instancie un nouveau Mapper sur la table concernée
	 * @todo Ajouter les dépendances sur les événements
	 */
	public function __construct(){
		$classParts = explode("\\",__CLASS__);
		$className = array_pop($classParts);
		
		$this->namespace = implode("\\",$classParts);
		
		$this->className = substr($className,0,strpos($className,"Mapper"));
		
		$this->columnPrefix = "eventperson_";
		
		$this->alias = "evtp";
		
		
		$this->defineScheme();
		
		//$this->dependencies[] = _DB_PREFIX_ . "evenement";
		
		$this->namespace = __NAMESPACE__;
	}
	
	public function select(){
		$params 				= null;
		$requete				= "SELECT ";
		$object					= null;
		$className				= $this->className . "Mapper";
		
		$dbInstance = \wp\dbManager\dbConnect::dbInstance();
		
		foreach($this->scheme as $column => $definition){
			$requete .= (!array_key_exists("foreign_key",$definition)) ? $this->columnPrefix . $column : $column;
			$requete .= " AS " . $column . ",";
		}
		$requete = substr($requete,0,strlen($requete)-1);
		
		$requete .= " FROM " . _DB_PREFIX_ . $this->getSchemeDetail("event_id","parent_table");
		$requete .= " INNER JOIN " . _DB_PREFIX_ . $this->className;
		$requete .= " USING (event_id) ";
		
		if(!is_null($this->clause) || !is_null($this->specialClause)){
			$requete .= " WHERE ";
			if(!is_null($this->clause)){
				for($i=0; $i<sizeof($this->clause);$i++){
					if($this->clause[$i]["operateur"] != "IN" && $this->clause[$i]["operateur"] != "BETWEEN"){
						$requete .= $this->clause[$i]["column"] . " " . $this->clause[$i]["operateur"] . " :" . $this->clause[$i]["column"] . " AND ";
						$params[$this->clause[$i]["column"]] = $this->clause[$i]["value"];
					} else {
						if($this->clause[$i]["operateur"] == "IN")
							$requete .= $this->clause[$i]["column"] . " IN " . $this->clause[$i]["value"] . " AND ";
						elseif($this->clause[$i]["operateur"] == "BETWEEN")
							$requete .= $this->clause[$i]["column"] . " BETWEEN " . $this->clause[$i]["value"] . " AND ";
					}
				}
				// Supprime le dernier " AND "
				$requete = substr($requete,0,strlen($requete) - strlen(" AND "));
			}
			if(!is_null($this->specialClause)){
				$requete .= " AND ";
				foreach($this->specialClause as $clause){
					$requete .= $clause;
				}
			}
		}
		
		if(!is_null($this->order)){
			$requete .= " ORDER BY ";
			for($i=0; $i<sizeof($this->order);$i++){
				$requete .= $this->order[$i]["column"] . " " . $this->clause[$i]["direction"] . ",";
			}
			// Supprime la dernière virgule inutile
			$requete = substr($requete,0,strlen($requete) - 1);
		}
		
		#begin_debug
		#echo "Requête : $requete avec :<br />\n";
		#var_dump($params);
		#echo "<br />\n";
		#end_debug
		
		$this->sqlStatement = $requete;
		
		// Prépare la requête
		$query = $dbInstance->getConnexion()->prepare($requete);
		if($query->execute($params)){
			$query->setFetchMode(\PDO::FETCH_OBJ);
			while($row = $query->fetch()){
				$factory = new \wp\Patterns\factory("\\arcec\\Mapper\\" . $className);
				$object = $factory->addInstance();
				foreach($this->scheme AS $column => $definition){
					if($definition["type"] != "date")
						$object->{$column} = $row->$column;
						else {
							if($row->$column != ""){
								// Une type date doit être reconverti
								$object->{$column} = \wp\Helpers\dateHelper::fromSQL($row->$column);
							}
						}
				}
				$this->collection[] = $object;
			}
			$this->nbRows = sizeof($this->collection);
		} else {
			// Gérer l'exception le cas échéant
		}
		
		return;		
	}

	/**
	 * Détermine le type de recherche à exécuter
	 * @param mixed $searchColumn
	 * @param mixed $searchValue
	 */
	public function searchBy($searchColumn,$searchValue){
	
		if(!is_array($searchColumn)){
			$column = $this->exists($searchColumn);
			if($column !== false){
				$this->clause[] = array("column"=>$column, "operateur" => "=", "value" => $searchValue);
			}
		} else {
			for($i=0;$i<sizeof($searchColumn);$i++){
				$column = $this->exists($searchColumn[$i]["column"]);
				if($column !== false){
					$this->clause[] = array("column"=>$column, "operateur" => $searchColumn[$i]["operateur"], "value" => $searchValue[$i]);
				}
			}
		}
	}

	/**
	 * Supprime en cascade toutes les personnes associées à un événement
	 * @param unknown $eventId
	 */
	public function cascadeDelete($eventId){
		$delete = "DELETE FROM " . _DB_PREFIX_ . $this->className . " WHERE event_id=:id;";
			
		$dbInstance = \wp\dbManager\dbConnect::dbInstance();
		$query = $dbInstance->getConnexion()->prepare($delete);
			
		$values["id"] = $eventId;
			
		if(!$query->execute($values)){
			return false;
		}
			
		return $eventId;
	}
	
	/**
	 * Définit si la colonne existe dans le mapping complet
	 * @param string $col Nom de la colonne à tester
	 * @return string|bool 
	 */
	private function exists($col){
		if(in_array($col,array_keys($this->scheme))){
			if(!array_key_exists("foreign_key", $this->scheme[$col])){
				
				return $this->columnPrefix . $col;
			} else {
				return $col;
			}
		}
		
		// La colonne peut faire partie du mapper parent
		$event = $this->scheme["event_id"]["mapper"];
		$scheme = $event->getScheme();
		if(in_array($col,array_keys($scheme))){
			if(!array_key_exists("foreign_key", $scheme[$col])){
				return $event->getColumnPrefix() . $col;
			}
		}		
		return false;		
	}
	
	private function defineScheme(){
		$this->scheme = array(
				"id" => array("type" => "int", "index" => "primary", "autoincrement" => true, "null" => false),
				"person" => array("type" => "int","null"=>false),
				"mapper" => array("type" => "varchar", "size" => 150, "null" => false),
				"event_id" => array("type" => "int","foreign_key" => true, "parent_table" => "event", "null" => false,"mapper" => new \arcec\Mapper\eventMapper())
		);
	}
	
	/**
	 *
	 * @param string $attrName : Nom de l'attribut / colonne
	 * @param mixed $attrValue : Valeur de l'attribut / colonne
	 */
	public function __set($attrName,$attrValue){
		if(!property_exists($this,$attrName) && $this->in($attrName)){
			$this->{$attrName} = $attrValue;
			return true;
		}
		return false;
	}
	
	public function __get($attrName){
		if(!property_exists($this,$attrName)){
			return $this->{$attrName};
		}
		return;
	}
	
	public function getCheckBox(){
		return $this->setCheckBox();
	
	}
	private function setCheckBox(){
		$checkbox = new \wp\formManager\Fields\checkbox();
		$checkbox->setId("id_" . $this->id)
		->setName("id_" . $this->id)
		->setValue($this->id)
		->isDisabled(!$this->checkIntegrity($this->id))
		;
		return $checkbox;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \wp\dbManager\dbMapper::usage()
	 **/
	protected function usage(){}
}
?>