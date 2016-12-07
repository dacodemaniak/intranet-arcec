<?php
/**
 * @name ressourceMapper.class.php : Mapping de la table prefix_ressource
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Mapper
 * @version 1.0
 **/
namespace arcec\Mapper;

class ressourceMapper extends \wp\dbManager\dbMapper {
	/**
	 * @property int $id Identifiant de la ressource
	 * @property string $titre Titre
	 * @property string $description Description facultative de la ressource
	 * @property int $type Type de la ressource, par défaut un lien vers une autre ressource web
	 * @property string $contenu Contenu de la ressource elle-même (lien ou nom de fichier)
	 * @property int $poids Taille du fichier, 0 par défaut
	 * @property int $mimetype_id Identifiant du type MIME du document, par défaut "text/html"
	 * @property int $arboressource_id Identifiant du noeud de l'arborescence (arc_arboressource)
	**/
	

	
	public function __construct(){
		$classParts = explode("\\",__CLASS__);
		$className = array_pop($classParts);
		
		$this->namespace = implode("\\",$classParts);
		
		$this->className = substr($className,0,strpos($className,"Mapper"));
		
		$this->columnPrefix = "ressource_";
		
		$this->alias = "rsc";
		
		
		$this->defineScheme();
		
		$this->dependencies[] = array();
		
		$this->namespace = __NAMESPACE__;
	}
	
	private function defineScheme(){
		$arboMapper = new \arcec\Mapper\arboressourceMapper();
		
		$mimeMapper = new \wp\dbManager\Mapper\mimetypeMapper();
		
		$this->scheme = array(
			"id" => array("type" => "int", "index" => "primary", "autoincrement" => true, "null" => false),
			"titre" => array("type" => "text","size"=>75,"null"=>true),
			"description" => array("type" => "text","size"=>150,"null"=>true),
			"type" => array("type" => "int","default" => 0, "null"=>false),
			"contenu" => array("type" => "text","size" => 255, "null"=>false),
			"poids" => array("type" => "int","default" => 0, "null"=>true),
			"mimetype_id" => array("type" => "int","foreign_key" => true, "parent_table" => "mimetype", "null" => false,"mapper" => $mimeMapper),
			"arboressource_id" => array("type" => "int","foreign_key" => true, "parent_table" => "arboressource", "null" => false,"mapper" => $arboMapper)
		);
	}

	public function count(){
		$params = array();
		
		$requete = "SELECT COUNT(*) AS nbrows
			FROM " . $this->getTableName();
		
		// Ajoute les contraintes à partir des données de recherche
		if(!is_null($this->clause)){
			$requete .= " WHERE ";
			for($i=0; $i<sizeof($this->clause);$i++){
				if($this->clause[$i]["operateur"] != "IN"){
					$requete .= $this->clause[$i]["column"] . " " . $this->clause[$i]["operateur"] . " :" . $this->clause[$i]["column"] . " AND ";
					$params[$this->clause[$i]["column"]] = $this->clause[$i]["value"];
				} else {
					$requete .= $this->clause[$i]["column"] . " IN " . $this->clause[$i]["value"] . " AND ";
				}
			}
			// Supprime le dernier " AND "
			$requete = substr($requete,0,strlen($requete) - strlen(" AND "));
		}
		
		#begin_debug
		#echo "Requête de décomptage : $requete<br />\n";
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
	
	/**
	 * (non-PHPdoc)
	 * @see \arcec\Mapper\parambaseMapper::usage()
	 */
	protected function usage(){}
	
	private function setCheckBox(){
		$checkbox = new \wp\formManager\Fields\checkbox();
		$checkbox->setId("id_" . $this->id)
			->setName("id_" . $this->id)
			->setValue($this->id)
			->isDisabled(!$this->checkIntegrity($this->id))
		;
		return $checkbox;
	}
}
?>