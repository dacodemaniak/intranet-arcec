<?php
/**
 * @name rapportMapper.class.php : Mapping de la table prefix_rapport
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Mapper
 * @version 1.0
 **/
namespace arcec\Mapper;

class rapportMapper extends \wp\dbManager\dbMapper {
	/**
	 * @property int $id Identifiant du rapport
	 * @property string $contenu Contenu du rapport
	 * @property string $date Date du rapport
	 * @property int $dureeheure
	 * @property int $dureeminute
	 * @property int $cns
	 * @property int $acu
	 * @property int $dossier_id Identifiant du dossier (arc_dossier)
	**/
	

	
	public function __construct(){
		$classParts = explode("\\",__CLASS__);
		$className = array_pop($classParts);
		
		$this->namespace = implode("\\",$classParts);
		
		$this->className = substr($className,0,strpos($className,"Mapper"));
		
		$this->columnPrefix = "rapport_";
		
		$this->alias = "rap";
		
		
		$this->defineScheme();
		
		$this->dependencies[] = array();
		
		$this->namespace = __NAMESPACE__;
	}
	
	private function defineScheme(){
		$dossierMapper = new \arcec\Mapper\dossierMapper();
		
		$this->scheme = array(
			"id" => array("type" => "int", "index" => "primary", "autoincrement" => true, "null" => false),
			"contenu" => array("type" => "text","null"=>false),
			"date" => array("type" => "date","null"=>false),
			"duree" =>  array("type" => "varchar","null"=>false,"default" => "01:00"),
			"cns" =>  array("type" => "int","null"=>false),
			"acu" =>  array("type" => "int","null"=>false),
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