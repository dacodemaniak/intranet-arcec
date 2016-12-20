<?php
/**
 * @name eventController.class.php Services de contrôle de validité d'un événement
 * @author web-Projet.com (contact@web-projet.com) - Août 2016
 * @package arcec\Event
 * @version 1.0
 **/
namespace arcec\Event;

use \arcec\Mapper\eventMapper as Event;
use \arcec\Mapper\eventpersonMapper as Person;
use \arcec\Mapper\eventmaterielMapper as Materiel;

class eventController{
	
	/**
	 * Evénement à contrôler
	 * @var Event
	 */
	private $event;
	
	/**
	 * Personne à contrôler sur la plage horaire définie
	 * @var Person
	 */
	private $person;
	
	/**
	 * Matériel dont contrôler la disponibilité sur ce créneau
	 * @var Materiel
	 */
	private $materiel;
	
	/**
	 * Stocke les messages d'erreur à récupérer après traitement
	 * @var array
	 */
	private $messages			= array();
	
	/**
	 * Définit l'événement à contrôler
	 * @param Event $event
	 */
	public function setEvent(Event $event){
		$this->event = $event;
		return $this;
	}
	
	/**
	 * Définit la personne à contrôler sur la plage horaire donnée
	 * @param Person $person
	 */
	public function setPerson(Person $person){
		$this->person = $person;
		return $this;
	}
	
	/**
	 * Définit le matériel dont tester la disponibilité sur ce créneau
	 * @param Materiel $materiel
	 */
	public function setMateriel(Materiel $materiel){
		$this->materiel = $materiel;
		return $this;
	}
	
	/**
	 * Détermine si le bureau définit est occupé à cette date, sur cette tranche horaire
	 * @return boolean
	 */
	public function officeOccupied(){
		$events = new \arcec\Mapper\eventMapper();
		$events->searchBy("date",\wp\Helpers\dateHelper::toSQL($this->event->date,"dd-mm-yyyy"));
		$events->specialClause(
				"'" . $this->event->heuredebut . "' BETWEEN event_heuredebut AND event_heurefin" 
			);	
		$events->searchBy("bureau_id",$this->event->bureau_id);
		$events->specialClause("event_id <> " . $this->event->parent);
		
		$events->set("\\arcec\\Mapper\\");
		
		if($events->getNbRows > 0){
			$this->messages[] = "Le bureau est occupé le " . $this->event->date . " entre " . $this->event->heuredebut . " et " . $this->event->heurefin;
			return true; // Le bureau est occupé à cette date durant cet horaire
		}
		#begin_debug
		#echo "Le bureau n'est pas occupé le " . $this->event->date . " entre " . $this->event->heuredebut . " et " . $this->event->heurefin . "<br />\n";
		#end_debug
		
		return false;
	}
	
	/**
	 * Détermine si la personne concernée est occupée à cette date, dans cette tranche horaire
	 * @return boolean
	 */
	public function personOccupied(){
		$events = new \arcec\Mapper\eventMapper();
		$events->searchBy("date",\wp\Helpers\dateHelper::toSQL($this->event->date,"dd-mm-yyyy"));
		$events->specialClause(
				"'" . $this->event->heuredebut . "' BETWEEN event_heuredebut AND event_heurefin"
				);
		$events->set("\\arcec\\Mapper\\");
		#begin_debug
		#echo "Exécute " . $events->getSQL() . " avec " . $this->event->date . "<br />\n";
		#end_debug
		if($events->getNbRows() > 0){
			foreach($events->getCollection() as $event){
				$person = new \arcec\Mapper\eventpersonMapper();
				$person->searchBy("person",$this->person->person);
				$person->searchBy("mapper",$this->person->mapper);
				$person->set("\\arcec\\Mapper\\");
				if($person->getNbRows() > 0){
					$this->messages[] = $this->getPerson() . " n'est pas disponible le " . $this->event->date . " entre " . $this->event->heuredebut . " et " . $this->event->heurefin;
					return true;
				}
				#begin_debug
				#echo $this->getPerson() . " est disponible le " . $this->event->date . " entre " . $this->event->heuredebut . " et " . $this->event->heurefin . "<br />\n";
				#end_debug
			}
		}
		return false;
	}
	
	/**
	 * Détermine si le matériel concerné est occupé à cette date, dans cette tranche horaire
	 * @return boolean
	 */
	public function materielOccupied(){
		$events = new \arcec\Mapper\eventMapper();
		$events->searchBy("date",\wp\Helpers\dateHelper::toSQL($this->event->date,"dd-mm-yyyy"));
		$events->specialClause(
				"'" . $this->event->heuredebut . "' BETWEEN event_heuredebut AND event_heurefin"
				);
		$events->set("\\arcec\\Mapper\\");
		if($events->getNbRows() > 0){
			foreach($events->getCollection as $event){
				$materiel = new \arcec\Mapper\eventmaterielMapper();
				$materiel->searchBy("materiel_id",$this->materiel->id);
				$materiel->set("\\arcec\\Mapper\\");
				if($materiel->getNbRows() > 0){
					$this->messages[] = $this->getMateriel() . " n'est pas disponible le " . $this->event->date . " entre " . $this->event->heuredebut . " et " . $this->event->heurefin;
					return false;
				}
			}
		}
		return false;
	}
	
	/**
	 * Ajoute les erreurs au message flash
	 */
	public function toSession(){
		if(sizeof($this->messages)){
			\wp\Helpers\sessionHelper::addFlashMessage($this->messages);
		}
	}
	
	/**
	 * Retourne la forme intelligible d'un participant
	 * @return string
	 */
	private function getPerson(){
		$factory = new \wp\Patterns\factory("\\arcec\\Mapper\\" . $this->person->mapper . "Mapper");
		$person = $factory->addInstance();
		$person->setId($this->person->person);
		$person->set("\\arcec\\Mapper\\");
		if($this->person->mapper == "paramCNS"){
			return $person->getObject()->libellelong;
		}
		return $person->getObject()->prenomporteur . " " . $person->getObject()->nomporteur;
	}

	/**
	 * Retourne la forme intelligible d'un matériel
	 * @return string
	 */
	private function getMateriel(){
		$factory = new \wp\Patterns\factory("\\arcec\\Mapper\\materielMapper");
		$materiel = $factory->addInstance();
		$materiel->setId($this->materiel->id);
		$person->set("\\arcec\\Mapper\\");

		return $materiel->getObject()->libelle;
	}
	
	
}