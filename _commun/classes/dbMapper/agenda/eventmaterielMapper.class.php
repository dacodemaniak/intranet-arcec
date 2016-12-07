<?php
/**
 * @name eventmaterielMapper.class.php Mapper sur la table dbPrefix_eventmateriel Matériel réservé
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Mapper
 * @version 1.0
**/

namespace arcec\Mapper;

class eventmaterielMapper extends \wp\dbManager\dbMapper {
	/**
	 * @property int $id Identifiant de l'association
	 * @property int $event_id Identifiant de l'événement de référence
	 * @property int $materiel_id Identifiant du matériel de référence
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
		
		$this->columnPrefix = "eventmateriel_";
		
		$this->alias = "evtm";
		
		
		$this->defineScheme();
		
		//$this->dependencies[] = _DB_PREFIX_ . "evenement";
		
		$this->namespace = __NAMESPACE__;
	}
	
	private function defineScheme(){
		$this->scheme = array(
				"id" => array("type" => "int", "index" => "primary", "autoincrement" => true, "null" => false),
				"event_id" => array("type" => "int","foreign_key" => true, "parent_table" => "event", "null" => false,"mapper" => new \arcec\Mapper\eventMapper()),
				"materiel_id" => array("type" => "int","foreign_key" => true, "parent_table" => "materiel", "null" => false,"mapper" => new \arcec\Mapper\materielMapper())
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
	
	/**
	 * Supprime en cascade tous les matériels associés à un événement
	 * @param unknown $eventId
	 */
	public function cascadeDelete($eventId){
		$delete = "DELETE FROM " . _DB_PREFIX_ . $this->className . " WHERE event_id=:id;";
			
		$dbInstance = \wp\dbManager\dbConnect::dbInstance();
		$query = $dbInstance->getConnexion()->prepare($delete);
			
		$values["id"] = $eventId;
			
		if(!$query->execute($values)){
			return false;
		}
			
		return $eventId;		
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