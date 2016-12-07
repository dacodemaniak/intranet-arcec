<?php
/**
 * @name deleteEvent.class.php Service de suppression d'un événement
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Ajax
 * @version 1.0
**/

namespace arcec\Ajax;

class deleteEvent implements \wp\Ajax\ajax{
	
	/**
	 * Tableau des résultats du processus d'ajout
	 * @var array
	 */
	private $result;
	
	/**
	 * Instance d'un objet de type eventMapper pour suppression
	 * @var object
	 */
	private $eventMapper;
	
	/**
	 * Enregistrement actif pour les opérations de suppression
	 * @var object
	 */
	private $activeRecord;
	
	/**
	 * Structure de stockage des événements enfants
	 * @var array
	 */
	private $childrens;
	
	/**
	 * Instance de mapping sur les personnes concernées
	 * @var object
	 */
	private $personMapper;
	
	/**
	 * Instance de mapping sur les matériels associés
	 * @var unknown
	 */
	private $materielMapper;
	
	/**
	 * Identifiant de l'événement de référence
	 * @var int
	 */
	private $eventId;
	
	
	public function __construct(){
		$this->result["statut"] = 1;
		$this->result["data"] = null;
		
		$this->eventMapper = new \arcec\Mapper\eventMapper();
		
		$this->childrens = array();
	}
	
	/**
	 * Définit les proriétés de la classe
	 * @param string $name
	 * @param multitype $value
	 * @return \arcec\Ajax\addPerson
	 */
	public function addParam($name,$value){
		if(property_exists($this, $name)){
			$this->{$name} = $value;
		}
		return $this;
	}
	
	/**
	 * Traite la suppression de l'événement concerné et de toutes les données
	 * associées.
	 */
	public function process(){
		$this->getEventScope();
		
		$person = new \arcec\Mapper\eventpersonMapper();
		$person->cascadeDelete($this->eventId);
		
		$materiel = new \arcec\Mapper\eventmaterielMapper();
		$materiel->cascadeDelete($this->eventId);
		
		// Supprime l'événement proprement dit
		$this->activeRecord->activeDelete();
		
		if(sizeof($this->childrens)){
			foreach ($this->childrens as $event){
				$person = new \arcec\Mapper\eventpersonMapper();
				$person->cascadeDelete($event->id);
				
				$materiel = new \arcec\Mapper\eventmaterielMapper();
				$materiel->cascadeDelete($event->id);
				
				// Supprime l'événement proprement dit
				$event->activeDelete();				
			}
		}
		
		$this->result["data"] = array(
			"id" => $this->eventId,
			"content" => "L'événement a été supprimé"
		);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \wp\Ajax\ajax::getResult()
	 */
	public function getResult(){
		return $this->result;
	}
	
	private function getEventScope(){
		
		$this->eventMapper->setId($this->eventId);
		$this->eventMapper->set("\\arcec\\Mapper\\");
		
		// Récupère l'enregistrement par défaut
		$this->activeRecord = $this->eventMapper->getObject();
		
		if($this->activeRecord->parent == 0){
			// Contrôle l'existence d'événements enfants
			$childEvents = new \arcec\Mapper\eventMapper();
			$childEvents->searchBy("parent",$this->eventId);
			$childEvents->set("\\arcec\\Mapper\\");
			if($childEvents->getNbRows() > 0){
				// Récupère les autres événements à supprimer
				foreach($childEvents->getCollection() as $event){
					$childrens[] = $event;
				}
			}
		}
		
	}
}
?>