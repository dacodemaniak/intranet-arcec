<?php
/**
 * @name paramzoneMapper.class.php : Mapping de la table prefix_paramzone
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Mapper
 * @version 1.0
 **/
namespace arcec\Mapper;

class paramzoneMapper extends \wp\dbManager\dbMapper {
	/**
	 * @property int $id Identifiant du paramètre
	 * @property string $codepostal Code postal
	 * @property string $motdirecteur Rue, dénomination
	 * @property string $typevoie Type de voie
	 * @property int $debut Début de numérotation
	 * @property int $fin Fin de numérotation
	 * @property string $parite Côtés de rue
	 * @property boolean $utilise Vrai si le paramètre est toujours utilisé
	 * @property date $date_suppression Date de la suppression logique du paramètre
	 * @property string $nom Nom du type de paramètre de la table parente
	 * @property int $param_definition_id Identifiant de la table de paramètre (voir paramdefinitionMapper)
	**/
	

	
	public function __construct(){
		$classParts = explode("\\",__CLASS__);
		$className = array_pop($classParts);
		
		$this->namespace = implode("\\",$classParts);
		
		$this->className = substr($className,0,strpos($className,"Mapper"));
		
		$this->columnPrefix = "param_zone_";
		
		$this->alias = "bs";
		
		$this->defineScheme();
		
		$this->dependencies[] = array();
		
		$this->namespace = __NAMESPACE__;
		
	}
	
	private function defineScheme(){
		$parentMapper = new \arcec\Mapper\paramdefinitionMapper();
		$parentMapper->searchBy("type_table_param_id",2); // Restreint la liste aux paramètres de zone
		
		$this->scheme = array(
			"id" => array("type" => "int", "index" => "primary", "autoincrement" => true, "null" => false),
			"codepostal" => array("type" => "varchar","size"=>10,"null"=>false,"index"=>1),
			"motdirecteur" => array("type" => "varchar","size" => 32, "null" => true),
			"typevoie" => array("type" => "varchar","size" => 3, "null" => true),
			"debut" => array("type" => "int", "null" => false),
			"fin" => array("type" => "int", "null" => false),
			"parite" => array("type" => "enum", "null" => false),
			"utilise" => array("type" => "tinyint","default" => 1, "null" => false),
			"datesuppression" => array("type" => "date"),
			"param_definition_id" => array("type" => "int","foreign_key" => true, "parent_table" => "paramdefinition", "null" => false,"mapper" => $parentMapper)
		);
	}

	/**
	 * Définit la requête spécifique de jointure entre les deux tables :
	 * prefix_paramdefinition | prefix_parambase
	 * @see \wp\dbManager\dbMapper::set()
	 */
	public function set($namespace="\\wp\\dbManager\\Mapper\\"){
		$params 				= null;
		$requete				= "SELECT ";
		$object					= null;
		$className				= $this->className . "Mapper";
	
		$collection				= array();
	
		$dbInstance = \wp\dbManager\dbConnect::dbInstance();
		
		// Colonnes de la table courante
		foreach($this->scheme as $column => $definition){
			$requete .= (!array_key_exists("foreign_key",$definition)) ? $this->columnPrefix . $column : $column;
			$requete .= " AS " . $column . ",";
		}
				
		// Parcours les colonnes de la table parente
		$scheme = $this->scheme["param_definition_id"]["mapper"]->getScheme();
		foreach($scheme as $column => $definition){
			if(!$this->isPrimary($definition)){
				$requete .= $this->scheme["param_definition_id"]["mapper"]->getAlias() . ".";
				if(!array_key_exists("foreign_key",$definition)){
					$requete .= $this->scheme["param_definition_id"]["mapper"]->getColumnPrefix() . $column;
				} else {
					$requete .= $column;
				}
				$requete .= " AS " . $column . ",";
			}
		}
		$requete = substr($requete,0,strlen($requete)-1);
		
		// Détermine la jointures
		$requete .= " FROM " . $this->scheme["param_definition_id"]["mapper"]->getTableName() . " AS " . $this->scheme["param_definition_id"]["mapper"]->getAlias();
		$requete .= " INNER JOIN " . $this->getTableName() . " AS " . $this->getAlias() . " USING(param_definition_id) ";

		if(!is_null($this->clause)){
			$requete .= " WHERE ";
			for($i=0; $i<sizeof($this->clause);$i++){
				$requete .= $this->clause[$i]["column"] . $this->clause[$i]["operateur"] . ":" . $this->clause[$i]["column"] . " AND ";
				$params[$this->clause[$i]["column"]] = $this->clause[$i]["value"];
			}
			// Supprime le dernier " AND "
			$requete = substr($requete,0,strlen($requete) - strlen(" AND "));
		}
		
		
		$requete .= " ORDER BY " . $this->scheme["param_definition_id"]["mapper"]->getAlias() . ".param_definition_nom;";
		
		#begin_debug
		#echo "Requête $requete<br />\n";
		#end_debug
		
		// Prépare la requête
		$query = $dbInstance->getConnexion()->prepare($requete);
		if($query->execute($params)){
			$query->setFetchMode(\PDO::FETCH_OBJ);
			while($row = $query->fetch()){

				$object = new \arcec\Mapper\paramzoneMapper();
				
				foreach($this->scheme AS $column => $definition){
					$object->{$column} = $row->$column;
				}
				// On ajoute les informations de la table parente
				foreach($this->scheme["param_definition_id"]["mapper"]->getScheme() AS $column => $definition){
					if(!$this->isPrimary($definition)){
						#begin_debug
						#echo "Ajoute la colonne $column de la table parente avec la valeur : " . $row->{$column} . "<br />\n";
						#end_debug
						$object->{$column} = $row->{$column};
					}
				}
				#begin_debug
				#var_dump($object);
				#die();
				#end_debug				
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