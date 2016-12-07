<?php
/**
 * @name parambaseMapper.class.php : Mapping de la table prefix_parambase
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Mapper
 * @version 1.0
 * @todo Créer la jointure pour récupérer les données de la table parente
 **/
namespace arcec\Mapper;

class parambaseMapper extends \wp\dbManager\dbMapper {
	/**
	 * @property int $id Identifiant du paramètre
	 * @property string $libellecourt Code du paramètre
	 * @property string $libellelong Nom du paramètre
	 * @property boolean $utilise Vrai si le paramètre est toujours utilisé
	 * @property date $date_suppression Date de la suppression logique du paramètre
	 * @property string $defcode : code d'ouverture de dépendance pour la valeur courante
	 * @property int $actif Uniquement dans paramCNS pour déterminer si oui ou non le conseiller peut être présenté
	 * @property string $nom Nom du type de paramètre de la table parente
	 * @property int $param_definition_id Identifiant de la table de paramètre (voir paramdefinitionMapper)
	**/
	
	/**
	 * Code permettant la récupération des paramètres
	 * @var string
	**/
	protected $code;
	
	/**
	 * Mapper sur les définitions des paramètres
	 * @var object
	 */
	protected $definitionMapper;
	
	/**
	 * Objet checkbox pour les actions multiples
	 * @var object
	**/
	private $checkObject;
	
	public function __construct(){
		$classParts = explode("\\",__CLASS__);
		$className = array_pop($classParts);
		
		$this->namespace = implode("\\",$classParts);
		
		$this->className = substr($className,0,strpos($className,"Mapper"));
		
		$this->columnPrefix = "param_base_";
		
		$this->alias = "bs";
		
		$this->defineScheme();
		
		$this->dependencies[] = array();
		
		$this->namespace = __NAMESPACE__;
		
		$this->checkObject = null;
	}
	
	protected function defineScheme(){
		$this->definitionMapper = new \arcec\Mapper\paramdefinitionMapper();
		$this->definitionMapper->searchBy("type_table_param_id",1); // Restreint la liste aux paramètres de base
		
		$this->scheme = array(
			"id" => array("type" => "int", "index" => "primary", "autoincrement" => true, "null" => false),
			"libellecourt" => array("type" => "varchar","size"=>3,"null"=>false,"index"=>1),
			"libellelong" => array("type" => "varchar","size" => 32, "null" => false),
			"utilise" => array("type" => "tinyint","default" => 1, "null" => false),
			"actif" => array("type" => "tinyint","null" => false),
			"datesuppression" => array("type" => "date"),
			"defcode" => array("type"=>"int","null"=>true,"default"=>0),
			"param_definition_id" => array("type" => "int","foreign_key" => true, "parent_table" => "paramdefinition", "null" => false,"mapper" => $this->definitionMapper)
		);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \wp\dbManager\dbMapper::checkIntegrity()
	 */
	public function checkIntegrity($id){
		// Récupère la ligne courante du mapper
		$mapper = clone $this;
		$mapper->clearSearch();
		$mapper->setId($id);
		$mapper->set($this->namespace);
		
		#begin_debug
		#echo "Requête de récupération : " . $mapper->getSQL() . "<br />\n";
		#echo "Contrôle d'intégrité pour l'ID $id avec le code parent : " . $mapper->getObject()->code . "<br />\n";
		#end_debug
		
		// Détermine le mapper à utiliser à partir du code de la table parente
		if(class_exists("\arcec\Mapper\param" . $mapper->getObject()->code . "Mapper")){
			
			$instance = new \wp\Patterns\factory("\arcec\Mapper\param" . $mapper->getObject()->code . "Mapper");
			$checkMapper = $instance->addInstance();
			if(sizeof($checkMapper->usage)){
				foreach($checkMapper->usage as $table => $foreignKeys){
					$factory = new \wp\Patterns\factory("\arcec\Mapper\\" . $table . "Mapper");
					$mapper = $factory->addInstance();
					foreach($foreignKeys as $foreignKey){
						$mapper->clearSearch();
						$mapper->searchBy($foreignKey,$id);
						if($table == "eventperson"){
							$mapper->searchBy("mapper","paramCNS");
						}
						$mapper->set($mapper->getNameSpace());

						if($mapper->getNbRows() > 0){
							return false;
						}
					}
				}
				return true;
			}
		} else {
			return parent::checkIntegrity($id);
		}		
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
		
		// Ajoute systématiquement la contrainte d'utilisation du paramètre
		$requete .= " WHERE " . $this->columnPrefix . "utilise = 1 AND ";
		
		if(!is_null($this->clause) || !is_null($this->specialClause)){
			for($i=0; $i<sizeof($this->clause);$i++){
				if($this->clause[$i]["operateur"] != "IN" && $this->clause[$i]["operateur"] != "NOT IN" && $this->clause[$i]["operateur"] != "BETWEEN"){
					$requete .= $this->clause[$i]["column"] . " " . $this->clause[$i]["operateur"] . " :" . $this->clause[$i]["column"] . " AND ";
					$params[$this->clause[$i]["column"]] = $this->clause[$i]["value"];
				} else {
					if($this->clause[$i]["operateur"] == "IN" || $this->clause[$i]["operateur"] == "NOT IN")
						$requete .= $this->clause[$i]["column"] . " " . $this->clause[$i]["operateur"] . $this->clause[$i]["value"] . " AND ";
					elseif($this->clause[$i]["operateur"] == "BETWEEN")
						$requete .= $this->clause[$i]["column"] . " BETWEEN " . $this->clause[$i]["value"] . " AND ";
				}
			}

		}
		// Supprime le dernier " AND "
		$requete = substr($requete,0,strlen($requete) - strlen(" AND "));
		
		if(!is_null($this->specialClause)){
			$requete .= " AND ";
			foreach($this->specialClause as $clause){
				$requete .= $clause;
			}
		}
		
		$requete .= " ORDER BY " . $this->scheme["param_definition_id"]["mapper"]->getAlias() . ".param_definition_nom;";
		
		#begin_debug
		#echo "Requête $requete<br />\n";
		#end_debug
		
		$this->sqlStatement = $requete;
		
		// Prépare la requête
		$query = $dbInstance->getConnexion()->prepare($requete);
		if($query->execute($params)){
			$query->setFetchMode(\PDO::FETCH_OBJ);
			while($row = $query->fetch()){

				$object = new \arcec\Mapper\parambaseMapper();
				
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
			var_dump($params);
			echo "<br />\n";
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
		if($attrName != ""){
			if(!property_exists($this,$attrName)){
				return $this->{$attrName};
			}
		}
		return;
	}
	
	
	/**
	 * Retourne le nombre de lignes de la restriction courante
	 * @see \wp\dbManager\dbMapper::count()
	 * @return int
	 */
	public function count(){
		$params 				= null;
		$requete				= "SELECT COUNT(*) AS nbligne ";
		$object					= null;
		$className				= $this->className . "Mapper";
		
		
		$dbInstance = \wp\dbManager\dbConnect::dbInstance();
		

		
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
		
		
		#begin_debug
		#echo "Requête $requete avec<br />\n";
		#var_dump($params);
		#echo "<br />\n";
		#end_debug
		
		// Prépare la requête
		$query = $dbInstance->getConnexion()->prepare($requete);
		if($query->execute($params)){
			$query->setFetchMode(\PDO::FETCH_OBJ);
			$row = $query->fetch();
			#echo "Nombre de lignes : " . $row->nbLigne . "<br />\n";
			return $row->nbligne;
		}
		
		return 0;		
	}
	
	public function getCheckBox(){
		if(is_null($this->checkObject))
			$this->setCheckBox();
		
		return $this->checkObject;
		
	}
	private function setCheckBox(){
		$this->checkObject = new \wp\formManager\Fields\checkbox();
		$this->checkObject->setId("id_" . $this->id)
			->setName("id_" . $this->id)
			->setValue($this->id)
			->isDisabled(!$this->checkIntegrity($this->id))
		;
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \wp\dbManager\dbMapper::usage()
	**/
	protected function usage(){}
}
?>