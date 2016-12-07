<?php
/**
 * @name docporteurMapper.class.php : Mapping de la table prefix_rapport
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Mapper
 * @version 1.0
 **/
namespace arcec\Mapper;

class docporteurMapper extends \wp\dbManager\dbMapper {
	/**
	 * @property int $id Identifiant du document
	 * @property string $nomcalcule Nom du document calculé en fonction de l'arborescence
	 * @property string $nomoriginal Nom du document original téléchargé
	 * @property string $description Description éventuelle du document
	 * @property int $size Taille du fichier en octets
	 * @property date $datedepot Date de dépôt du document
	 * @property int $mimetype_id Clé étrangère sur la tables des types MIME
	 * @property int $arbofichier_id Clé étrangère sur la table des noeuds d'arborescence
	 * @property int $dossier_id Identifiant du dossier (arc_dossier)
	**/
	

	
	public function __construct(){
		$classParts = explode("\\",__CLASS__);
		$className = array_pop($classParts);
		
		$this->namespace = implode("\\",$classParts);
		
		$this->className = substr($className,0,strpos($className,"Mapper"));
		
		$this->columnPrefix = "docporteur_";
		
		$this->alias = "docp";
		
		
		$this->defineScheme();
		
		$this->dependencies[] = array();
		
		$this->namespace = __NAMESPACE__;
	}
	
	private function defineScheme(){
		$dossierMapper = new \arcec\Mapper\dossierMapper();
		$arboMapper = new \arcec\Mapper\arbofichierMapper();
		$mimeMapper = new \wp\dbManager\Mapper\mimetypeMapper();
		
		$this->scheme = array(
			"id" => array("type" => "int", "index" => "primary", "autoincrement" => true, "null" => false),
			"nomcalcule" => array("type" => "text","size"=>255,"null"=>false),
			"nomoriginal" => array("type" => "text","size"=>255,"null"=>false),
			"description" => array("type" => "text","null"=>true),
			"datedepot" => array("type" => "date","null"=>false),
			"taille" =>  array("type" => "int","null"=>false,"default" => 0),
			"mimetype_id" => array("type" => "int","foreign_key" => true, "parent_table" => "mimetype", "null" => false,"mapper" => $mimeMapper),
			"arbofichier_id" => array("type" => "int","foreign_key" => true, "parent_table" => "arbofichier", "null" => false,"mapper" => $arboMapper),
			"dossier_id" => array("type" => "int","foreign_key" => true, "parent_table" => "dossier", "null" => false,"mapper" => $dossierMapper)
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
				$requete .= $this->clause[$i]["column"] . " " . $this->clause[$i]["operateur"] . " :" . $this->clause[$i]["column"] . " AND ";
				$params[$this->clause[$i]["column"]] = $this->clause[$i]["value"];
			}
			// Supprime le dernier " AND "
			$requete = substr($requete,0,strlen($requete) - strlen(" AND "));
		}		
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