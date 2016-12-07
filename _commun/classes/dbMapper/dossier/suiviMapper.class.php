<?php
/**
 * @name suiviMapper.class.php : Mapping de la table prefix_suivi
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Mapper
 * @version 1.0
 **/
namespace arcec\Mapper;

class suiviMapper extends \wp\dbManager\dbMapper {
	
	/**
	 * @property int $dossier_id Identifiant du dossier (dossier)
	 * @property int $programme_id Identifiant du programme (paramPRG)
	 * @property int $etapeprojet_id Identifiant de l'étape projet (etapeprojet)
	 * @property int $conseiller_id Identifiant du conseiller (paramCNS)
	 * @property int $action_id Identifiant de la tâche à effectuer (paramWRK)
	 * @property date $date Date de mise à jour de la tâche de suivi
	**/
	

	
	public function __construct(){
		
		$classParts = explode("\\",__CLASS__);
		$className = array_pop($classParts);
		
		$this->namespace = implode("\\",$classParts);
		
		$this->className = substr($className,0,strpos($className,"Mapper"));
		
		$this->columnPrefix = "suivi_";
		
		$this->alias = "suivi";
		
		
		$this->defineScheme();
		
		$this->dependencies[] = array();
		
		$this->namespace = __NAMESPACE__;
	}
	
	private function defineScheme(){

		$this->scheme = array(
			"dossier_id" => array("type" => "int","foreign_key" => true, "parent_table" => "dossier", "null" => false,"mapper" => new \arcec\Mapper\dossierMapper()),
			"programme_id" => array("type" => "int","foreign_key" => true, "parent_table" => "parambase", "null" => false,"mapper" => new \arcec\Mapper\paramPRGMapper()),
			"etapeprojet_id" => array("type" => "int","foreign_key" => true, "parent_table" => "etapeprojet", "null" => false,"mapper" => new \arcec\Mapper\etapeprojetMapper()),
			"conseiller_id" => array("type" => "int","foreign_key" => true, "parent_table" => "parambase", "null" => false,"mapper" => new \arcec\Mapper\paramCNSMapper()),
			"action_id" => array("type" => "int","foreign_key" => true, "parent_table" => "paramcomment", "null" => false,"mapper" => new \arcec\Mapper\paramWRKMapper()),
			"date" => array("type" => "date","null"=>false)
		);
	}

	/**
	 * Définit la requête à partir du schéma et exécute pour alimenter la collection
	 */
	public function set($namespace="\\arcec\\Mapper\\"){
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
	
		$requete .= " FROM " . _DB_PREFIX_ . $this->className;
	
		if(!is_null($this->clause)){
			$requete .= " WHERE ";
			for($i=0; $i<sizeof($this->clause);$i++){
				$requete .= $this->clause[$i]["column"] . " " . $this->clause[$i]["operateur"] . " :" . $this->clause[$i]["column"] . " AND ";
				$params[$this->clause[$i]["column"]] = $this->clause[$i]["value"];
			}
			// Supprime le dernier " AND "
			$requete = substr($requete,0,strlen($requete) - strlen(" AND "));
		}
		
		// Ajoute le critère de tri pour l'affichage cohérent des étapes
		$requete .= " ORDER BY etapeprojet_id";
		
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
				$factory = new \wp\Patterns\factory($namespace . $className);
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
	
	
	public function isForeignKey($column){
		if(array_key_exists($column,$this->scheme)){
			// La clé existe dans le schéma
			$definition = $this->scheme[$column];
			if(array_key_exists("foreign_key",$definition)){
				// La clé foreign_key existe dans la définition
				if($definition["foreign_key"]){
					return true;
				}
			}
		}
		return false;	
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
	
	/**
	 * (non-PHPdoc)
	 * @see \wp\dbManager\dbMapper::usage()
	 **/
	protected function usage(){}
}
?>