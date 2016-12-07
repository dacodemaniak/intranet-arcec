<?php
/**
 * @name prgtoetapesMapper.class.php : Service de mappage des étapes des programmes
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Mapper
 * @version 1.0
**/

namespace arcec\Mapper;

class prgtoetapesMapper extends \wp\dbManager\dbMapper{
	/**
	 * @property int $programme_id Identifiant du programme de suivi
	 * @property int $etapeprojet_id Identifiant de l'étape projet
	 * @property string $libellecourt Libellé court du programme
	 * @property string $libellelong Libellé long du programme
	 * @property string $description Description de l'étape
	 * @property string $libelle Libellé de l'étape
	**/
	
	public function __construct(){
		$className = \wp\Helpers\stringHelper::lastOf(__CLASS__,"\\");
		$this->className = substr($className,0,strpos($className,"Mapper"));
	
		$this->columnPrefix = "";
	
		$this->alias = "prgtoet";
		
		$this->defineScheme();
	}
	
	private function defineScheme(){
		$this->scheme = array(
				"programme_id" => array("type" => "int","foreign_key" => true, "parent_table" => "parambase", "null" => false,"mapper" => new \arcec\Mapper\paramPRGMapper()),
				"etapeprojet_id" => array("type" => "int","foreign_key" => true, "parent_table" => "module", "null" => false, "mapper" => new \arcec\Mapper\etapeprojetMapper())
		);
		$this->isAssociation = true;
	}
	
	/**
	 * Définit la requête spécifique de jointure entre les trois tables :
	 * prefix_parambase | prefix_prgtoetapes | prefix_etapeprojet
	 * @see \wp\dbManager\dbMapper::set()
	 */
	public function set($namespace="\\arcec\\Mapper\\"){
		$params 				= null;
		$requete				= "SELECT ";
		$object					= null;
		$className				= $this->className . "Mapper";
		
		$collection				= array();
		
		$dbInstance = \wp\dbManager\dbConnect::dbInstance();
		
		// Parcours les colonnes de la table des paramètres
		$scheme = $this->scheme["programme_id"]["mapper"]->getScheme();
		foreach($scheme as $column => $definition){
			$requete .= $this->scheme["programme_id"]["mapper"]->getAlias() . ".";
			if(!array_key_exists("foreign_key",$definition)){
				$requete .= $this->scheme["programme_id"]["mapper"]->getColumnPrefix() . $column;
			} else {
				$requete .= $column;
			}
			$requete .= " AS " . $column . ",";
		}
		
		// Parcours les colonnes de la tables des étapes
		$scheme = $this->scheme["etapeprojet_id"]["mapper"]->getScheme();
		foreach($scheme as $column => $definition){
			$requete .= $this->scheme["etapeprojet_id"]["mapper"]->getAlias() . ".";
			if(!array_key_exists("foreign_key",$definition)){
				$requete .= $this->scheme["etapeprojet_id"]["mapper"]->getColumnPrefix() . $column;
			} else {
				$requete .= $column;
			}
			$requete .= " AS " . $column . ",";
		}
				
		$requete = substr($requete,0,strlen($requete)-1);		
		
		// Détermine les jointures
		$requete .= " FROM " . $this->scheme["programme_id"]["mapper"]->getTableName() . " AS " . $this->scheme["programme_id"]["mapper"]->getAlias();
		$requete .= " INNER JOIN " . $this->getTableName() . " AS " . $this->getAlias() . " ON  " . $this->scheme["programme_id"]["mapper"]->getAlias() . ".param_base_id = " . $this->getAlias() . ".programme_id ";
		$requete .= " INNER JOIN " . $this->scheme["etapeprojet_id"]["mapper"]->getTableName() . " AS " . $this->scheme["etapeprojet_id"]["mapper"]->getAlias();
		$requete .= " USING(etapeprojet_id)";
		
		// Ajouter éventuellement une restriction sur l'une ou l'autre des clés étrangères
		if(!is_null($this->clause)){
			$requete .= " WHERE ";
			for($i=0; $i<sizeof($this->clause);$i++){
				$requete .= $this->clause[$i]["column"] . " " . $this->clause[$i]["operateur"] . " :" . $this->clause[$i]["column"] . " AND ";
				$params[$this->clause[$i]["column"]] = $this->clause[$i]["value"];
			}
			// Supprime le dernier " AND "
			$requete = substr($requete,0,strlen($requete) - strlen(" AND "));
		}
				
		// Prépare la requête
		$query = $dbInstance->getConnexion()->prepare($requete);
		if($query->execute($params)){
			$query->setFetchMode(\PDO::FETCH_OBJ);
			while($row = $query->fetch()){
				//$factory = new \wp\Patterns\factory("\\wp\\dbManager\\Mapper\\" . $className);
				//$object = $factory->addInstance();
				$object = new \wp\dbManager\Mapper\aclbackendMapper();
				foreach($scheme AS $column => $definition){
					$object->{$column} = $row->$column;
				}
				$collection[] = $object;
			}
			$this->setNbRows();
		} else {
			// Gérer l'exception le cas échéant
			echo "Impossible d'exécuter la requête : $requete<br />\n";
		}
		$this->setCollection($collection);
		
		return $collection;		
	}

		/**
		 * Définit la valeur pour une des colonnes de mapping
		 * @param string $attrName : Nom de l'attribut / colonne
		 * @param mixed $attrValue : Valuer de l'attribut / colonne
		 */
		public function __set($attrName,$attrValue){
			if(!property_exists($this,$attrName)){
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
		 * Met à jour les données
		 * @param array $updateColumns (Optionnel) dans ce cas toujours null et non utilisé
		 * @todo Créer les requêtes de mise à jour INSERT, UPDATE ou REPLACE (uniquement si mySQL)
		 * @return boolean
		 */
		public function save($updateColumns=null,$getSQL=false){
			return $this->insert();
		}
		
		private function insert($getSQL){
			$insert = "INSERT INTO " . _DB_PREFIX_ . $this->className;
		
			foreach($this->scheme as $column => $definition){
				if(!array_key_exists("autoincrement",$definition)){
					if(!array_key_exists("foreign_key",$definition)){
						$cols[] = $this->columnPrefix . $column;
						$holders[] = ":" . $column;
						$values[$column] = $this->{$column};
					} else {
						$cols[] = $column;
						$holders[] = ":" . $column;
						$values[$column] = $this->{$column};
					}
				}
			}
		
			$insert .= "(" . implode(",",$cols) . ") ";
			$insert .= " VALUES (" . implode(",",$holders) . ");";
		
			#begin_debug
			#echo $insert . "<br />\n";
			#end_debug
		
			$dbInstance = \wp\dbManager\dbConnect::dbInstance();
			$query = $dbInstance->getConnexion()->prepare($insert);
		
			if(!$query->execute($values)){
				#echo "$insert<br />\n";
				#var_dump($values);
				#die("Erreur");
				return false;
			}
			return true;
		}
		
		/**
		 * Supprime une donnée de table
		 * @todo Créer la requête DELETE
		 * @return boolean|int Faux si la requête n'a pas abouti ou la valeur de la clé primaire d'origine
		 */
		public function delete(){
			$delete = "DELETE FROM " . $this->getTableName() . " WHERE programme_id=:id;";
		
			$dbInstance = \wp\dbManager\dbConnect::dbInstance();
			$query = $dbInstance->getConnexion()->prepare($delete);
		
			$params["id"] = $this->programme_id;
			
			/*
			if(!$query->execute($params)){
				return false;
			}
			*/
			
			return true;
		}
		
		/**
		 * (non-PHPdoc)
		 * @see \wp\dbManager\dbMapper::usage()
		 **/
		protected function usage(){}
		
}
?>