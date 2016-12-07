<?php
/**
 * @name arboressourceMapper.class.php : Mapping de la table prefix_arboressource
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Mapper
 * @version 1.0
 **/
namespace arcec\Mapper;

class arboressourceMapper extends \wp\dbManager\dbMapper {
	/**
	 * @property int $id Identifiant de l'arborescence
	 * @property string $titre Titre affiché
	 * @property string $codification Codification à utiliser dans le nommage des fichiers
	 * @property string $description Description longue du noeud de l'arborescence
	 * @property boolean $ordre N° d'ordre
	 * @property int $parent Identifiant du noeud parent
	**/

	/**
	 * Mapper sur une branche particulière
	 * @var object
	 */
	protected $codificationMapper;
	
	
	public function __construct(){
		$classParts = explode("\\",__CLASS__);
		$className = array_pop($classParts);
		
		$this->namespace = implode("\\",$classParts);
		
		$this->className = substr($className,0,strpos($className,"Mapper"));
		
		$this->columnPrefix = "arboressource_";
		
		$this->alias = "ressource";
		
		$this->defineScheme();
		
		$this->dependencies[] = array();
		
		$this->namespace = __NAMESPACE__;
		
	}
	
	protected function defineScheme(){
		$this->scheme = array(
			"id" => array("type" => "int", "index" => "primary", "autoincrement" => true, "null" => false),
			"titre" => array("type" => "varchar","size"=>75,"null"=>false,"index"=>1),
			"codification" => array("type" => "varchar","size" => 3, "null" => false),
			"description" => array("type" => "text","null"=>true),
			"ordre" => array("type" => "smallint","default" => 1, "null" => false),
			"parent" => array("type" => "int","auto_join" => true, "null" => false,"default" => 0)
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
		
		// Ajoute le critère de tri associé
		$requete .= " ORDER BY " . $this->columnPrefix . "ordre;";
		
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
	
	public function getParentColumn(){
		foreach($this->scheme as $column => $detail){
			if(array_key_exists("auto_join",$detail)){
				return $column;
			}
		}
		return;	
	}
	
	public function getCheckBox(){
		return $this->setCheckBox();
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \arcec\Mapper\parambaseMapper::usage()
	 */
	protected function usage(){}
	
	private function setCheckBox(){
		$checkbox = new \wp\formManager\Fields\checkbox();
		$checkbox->setTemplateName("valuedCheckbox");
		$checkbox->setId("id_" . $this->id)
			->setName("arbo[]")
			->setValue($this->id)
			->isDisabled(!$this->checkIntegrity($this->id))
		;
		return $checkbox;
	}
}
?>