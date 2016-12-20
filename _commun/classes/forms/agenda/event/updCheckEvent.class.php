<?php
/**
 * @name updCheckEvent.class.php Services de contrôle de mise à jour d'un événement
 * @author web-Projet.com (jean-luc.aubert@web-projet.com) - Juin 2016
 * @package arcec\Event
 * @version 1.0
 */
namespace arcec\Event;

class updCheckEvent {
	/**
	 * Evénement courant
	 * @var \arcec\Mapper\eventMapper
	 */
	private $event;
	
	/**
	 * Détermine si l'événement courant est passé ou non
	 * @var boolean
	 */
	private $isPast;
	
	/**
	 * Détermine s'il s'agit d'un événement Maître
	 * @var boolean
	 */
	private $isMaster;
	
	/**
	 * Objet Maître
	 * @var unknown
	 */
	private $masterEvent;
	
	/**
	 * Détermine s'il s'agit d'un événement enfant
	 * @var boolean
	 */
	private $isChildren;
	
	/**
	 * Collection des événements enfants
	 * @var array
	 */
	private $childrenEvents;
	
	/**
	 * Instancie un nouvel objet de contrôle de mise à jour d'événement
	 * @param unknown $event
	 */
	public function __construct($event){
		$this->event = $event;
		
		$this->isPast = \wp\Helpers\dateHelper::isPast(\wp\Helpers\dateHelper::toSQL($this->event->date,"d/m/yyyy"),substr($this->event->heurefin,0,5));
		$this->uniqueEvent();
	}
	
	/**
	 * Retourne le statut passé de l'événement courant
	 */
	public function isPast(){
		return $this->isPast;
	}
	
	/**
	 * Retourne l'état "Maître" de l'événement
	 */
	public function isMaster(){
		return $this->isMaster;
	}
	
	/**
	 * Retourne le statut "Unique" d'un événement
	 */
	public function isUnique(){
		if(!$this->isMaster && !$this->isChildren){
			return true;
		}
		return false;
	}
	
	public function isChildren(){
		return $this->isChildren;
	}
	
	public function toString(){
		$output					= "";
		
		if($this->isPast){
			return "L'événement courant est passé.<br />Vous ne pouvez plus le modifier.";
		}
		
		if($this->isMaster){
			$output .= "Cet événement est répété.<br />Vous pourrez modifier les " . sizeof($this->childrenEvents) . " autres événements associés.";
		}
		
		return $output;
	}
	
	/**
	 * Détermine les propriétés de l'événement
	 */
	private function uniqueEvent(){
		$this->isChildren = false;
		$this->isMaster = false;
		
		$mapper = new \arcec\Mapper\eventMapper();
		
		// Cet événement est-il un enfant d'un événement principal
		if($this->event->parent != 0 || $this->event->parent != ""){
			$this->isChildren = true;
			$mapper->clearSearch();
			$mapper->setId($this->event->parent);
			$mapper->set($mapper->getNameSpace());
			$this->masterEvent = $mapper->getObject();
		} else {
			// Cet événement est-il un événement Maître
			$this->isMaster = true;
			$mapper->clearSearch();
			$searchParams[] = array("column" => "parent","operateur" => "=");
			$searchParams[] = array("column" => "id","operateur" => "NOT IN");
			$searchValues = array($this->event->id,$this->event->id);
			$mapper->searchBy($searchParams, $searchValues);
			$mapper->set($mapper->getNameSpace());
			if($mapper->getNbRows() > 0){
				
				$this->masterEvent = clone $this->event;
				foreach ($mapper->getCollection() as $object){
					$this->childrenEvents[] = $object;
				}
			}
		}		
	}
	
}