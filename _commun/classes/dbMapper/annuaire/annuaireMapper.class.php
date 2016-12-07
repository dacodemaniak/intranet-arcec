<?php
/**
 * @name annuaireMapper.class.php : Mapping de la table prefix_annuaire
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Mapper
 * @version 1.0
 **/
namespace arcec\Mapper;

class annuaireMapper extends \wp\dbManager\dbMapper {
	/**
	 * @property int $id Identifiant de l'entrée du carnet d'adresses
	 * @property string $titre Titre facultatif
	 * @property string $nom Nom à affecter au carnet d'adresses
	 * @property string $prenom Prénom
	 * @property string $email Adresse e-mail définie
	 * @property string $telephonefixe N° de téléphone
	 * @property string $telephoneportable Téléphone portable
	 * @property string $fax N° de fax
	 * @property string $autreinfos Autres informations au format JSON (expérimental)
	 * @property int $arboannuaire_id Identifiant du noeud de l'arborescence (arc_arboannuaire)
	**/
	

	
	public function __construct(){
		$classParts = explode("\\",__CLASS__);
		$className = array_pop($classParts);
		
		$this->namespace = implode("\\",$classParts);
		
		$this->className = substr($className,0,strpos($className,"Mapper"));
		
		$this->columnPrefix = "annuaire_";
		
		$this->alias = "annuaire";
		
		
		$this->defineScheme();
		
		$this->dependencies[] = array();
		
		$this->namespace = __NAMESPACE__;
	}
	
	private function defineScheme(){
		$arboMapper = new \arcec\Mapper\arboannuaireMapper();
		
		$this->scheme = array(
			"id" => array("type" => "int", "index" => "primary", "autoincrement" => true, "null" => false),
			"titre" => array("type" => "text","size"=>75,"null"=>true),
			"nom" => array("type" => "text","size"=>150,"null"=>false),
			"prenom" => array("type" => "text","size" => 75, "null"=>true),
			"email" => array("type" => "text","size" => 150, "null"=>false),
			"telephonefixe" => array("type" => "text","size" => 50, "null"=>true),
			"telephoneportable" => array("type" => "text","size" => 50, "null"=>true),
			"fax" => array("type" => "text","size" => 50, "null"=>true),
			"autreinfos" => array("type" => "text","null"=>true,"json"=>true),
			"arboannuaire_id" => array("type" => "int","foreign_key" => true, "parent_table" => "arboannuaire", "null" => false,"mapper" => $arboMapper)
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