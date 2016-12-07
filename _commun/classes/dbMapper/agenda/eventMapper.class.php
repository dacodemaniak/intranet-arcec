<?php
/**
 * @name eventMapper.class.php : Mapping de la table prefix_event Gestion des événements
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Mapper
 * @version 1.0
 **/
namespace arcec\Mapper;

class eventMapper extends \wp\dbManager\dbMapper {
	/**
	 * @property int $id Identifiant de l'événement
	 * @property string $titre Titre de l'événement
	 * @property string $objet Objet de l'événement
	 * @property date $date Date de l'événement
	 * @property time $heuredebut Heure de début
	 * @property time $heurefin Heure de fin
	 * @property int $sensibilite Sensibilité de l'événement
	 * @property int $bureau_id Identifiant du lieu et de la salle
	 * @property int $typeevent_id Identifiant du type d'événement
	 * @property int $parent Identifiant de l'événement parent si événement répété
	**/
	

	
	public function __construct(){
		$classParts = explode("\\",__CLASS__);
		$className = array_pop($classParts);
		
		$this->namespace = implode("\\",$classParts);
		
		$this->className = substr($className,0,strpos($className,"Mapper"));
		
		$this->columnPrefix = "event_";
		
		$this->alias = "event";
		
		
		$this->defineScheme();
		
		$this->dependencies[] = array();
		
		$this->namespace = __NAMESPACE__;
	}
	
	private function defineScheme(){
		$this->scheme = array(
			"id" => array("type" => "int", "index" => "primary", "autoincrement" => true, "null" => false),
			"titre" => array("type" => "text","size"=>75,"null"=>false),
			"objet" => array("type" => "text","null"=>true),
			"date" => array("type" => "date", "null"=>false,"default" => \wp\Helpers\dateHelper::today("d/m/Y")),
			"heuredebut" => array("type" => "time", "null"=>false),
			"heurefin" => array("type" => "time", "null"=>false),
			"sensibilite" => array("type" => "int", "default" => 0),
			"createur" => array("type" => "int", "default" => 0),
			"typerepetition" => array("type" => "int","default" => 0),
			"parent" => array("type" => "int","auto_join" => true, "null" => false,"default" => 0),
			"bureau_id" => array("type" => "int","foreign_key" => true, "parent_table" => "bureau", "null" => false,"mapper" => new \arcec\Mapper\bureauMapper()),
			"typeevent_id" => array("type" => "int","foreign_key" => true, "parent_table" => "typeevent", "null" => false,"mapper" => new \arcec\Mapper\typeeventMapper())
		);
	}

	public function count(){
		$params = array();
		
		$requete = "SELECT COUNT(*) AS nbrows
			FROM " . $this->getTableName();
		
		// Ajoute les contraintes à partir des données de recherche
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
		
		#begin_debug
		#echo "Requête de décomptage : $requete<br />\n";
		#var_dump($params);
		#echo "<br />\n";
		#end_debug
		
		$dbInstance = \wp\dbManager\dbConnect::dbInstance();
		$query = $dbInstance->getConnexion()->prepare($requete);
		$query->execute($params);
		$query->setFetchMode(\PDO::FETCH_OBJ);
		$row = $query->fetch();
	
		return (integer) $row->nbrows;
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