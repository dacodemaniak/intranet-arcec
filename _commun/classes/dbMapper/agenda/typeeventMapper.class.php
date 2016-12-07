<?php
/**
 * @name typeeventMapper.class.php Mapper sur la table dbPrefix_typeevent Types d'événements
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Mapper
 * @version 1.0
**/

namespace arcec\Mapper;

class typeeventMapper extends \wp\dbManager\dbMapper {
	/**
	 * @property int $id Identifiant du type
	 * @property string $titre Titre de l'événement
	 * @property string $description Description (optionnelle)
	 * @property time $dureeestimee Durée estimée pour ce type d'événement
	 * @property int $bloquant Détemrine le statut bloquant ou non pour les événements de ce type
	 * @property int $repete Détermine le statut de répétition d'un tel type d'événement
	 * @property string $classname Classe CSS à associer à l'événement
	 * @property int $invisible D�finit le statut de visibilit� de l'�v�nement dans les agendas
	**/
	
	/**
	 * Instancie un nouveau Mapper sur la table concernée
	 * @todo Ajouter les dépendances sur les événements
	 */
	public function __construct(){
		$classParts = explode("\\",__CLASS__);
		$className = array_pop($classParts);
		
		$this->namespace = implode("\\",$classParts);
		
		$this->className = substr($className,0,strpos($className,"Mapper"));
		
		$this->columnPrefix = "typeevent_";
		
		$this->alias = "tevt";
		
		
		$this->defineScheme();
		
		$this->dependencies[] = _DB_PREFIX_ . "event";
		
		$this->namespace = __NAMESPACE__;
	}
	
	private function defineScheme(){
		$this->scheme = array(
				"id" => array("type" => "int", "index" => "primary", "autoincrement" => true, "null" => false),
				"titre" => array("type" => "varchar","size"=>150,"null"=>false,"index"=>1),
				"description" => array("type" => "text", "null" => true),
				"dureeestimee" =>  array("type" => "varchar","null"=>false,"default" => "01:00"),
				"bloquant" => array("type"=>"tinyint","default"=>0),
				"repete" => array("type"=>"tinyint","default"=>0),
				"classname" => array("type"=>"varchar","null"=>true),
				"invisible" => array("type"=>"smallint", "default"=>0)
		);
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