<?php
/**
 * @name checkEvent.class.php Services de contrôle des événements
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Event
 * @version 1.0
 * @version 1.1 Déc. 2016 Modification du filtre pour ne tenir compte, sur les événements invisibles, que des personnes
**/
namespace arcec\Event;

class checkEvent {
	/**
	 * Objet ORM sur la structure des événements
	 * @var object
	 */
	private $eventMapper;
	
	/**
	 * Date pour le contrôle
	 * @var string
	**/
	private $date;
	
	/**
	 * Heure de début de l'événement
	 * @var string
	 */
	private $hDebut;
	
	/**
	 * Heure de fin de l'événement
	 * @var string
	 */
	private $hFin;
	
	
	/**
	 * Lieu défini pour l'événement
	 * @var int
	 */
	private $bureau;
	
	
	/**
	 * Identifiant(s) du ou des conseillers
	 * @var string|array
	 */
	private $conseiller;
	
	/**
	 * Identifiant de l'événement courant le cas échéant
	 * @var int
	 */
	private $event;
	
	/**
	 * Nombre d'événements retournés
	 * @var int
	 */
	private $nb;
	
	/**
	 * Instancie un nouvel objet de contrôle d'événement
	**/
	public function __construct(){
		$this->eventMapper = new \arcec\Mapper\eventMapper();
		
		$this->date = null;
		$this->hDebut = null;
		$this->hFin = null;
		$this->bureau = null;
		
		$this->conseiller = null;
		
		
	}
	
	/**
	 * Retourne la chaîne de contrôle des événements
	 * @return string
	 */
	public function toString(){
		$creneau = " ce jour";
		
		if(!is_null($this->bureau) && $this->bureau != 0){
			if(is_null($this->conseiller))
				$creneau .= "<br />\n sur ce bureau";
			else
				if($this->nb == 0){
					$creneau .= "<br />\n sur ce bureau";
			}	
		}
		
		if(!is_null($this->hDebut) && $this->hDebut != ""){
			if(!is_null($this->hFin) && $this->hFin != ""){
				$creneau .= "<br />\n Entre " . $this->hDebut . " et " . $this->hFin;
			} else {
				$creneau .= "<br />\n à partir de " . $this->hDebut;
			}
		}
		
		
		if($this->nb == 0){
			return "Aucun événement" . $creneau;
		}
		
		if($this->nb == 1){
			if(is_null($this->conseiller))
				return "Un événement enregistré" . $creneau;
			else
				return "Ce conseiller ne peut pas être à deux endroits " . $creneau;
		}
		
		if($this->nb > 1){
			if(is_null($this->conseiller))
				return $this->nb . " événements définis" . $creneau;
			else
				return "Ce conseiller ne peut pas être à deux endroits sur " . $creneau;
		}
	}
	
	/**
	 * Définit la date à partir de laquelle le contrôle doit s'effectuer
	 * @param string $date
	 * @return \arcec\Event\checkEvent
	 */
	public function setDate($date){
		$this->date = $date;
		
		return $this;
	}

	/**
	 * Heure de début définie pour l'événement
	 * @param string $heure
	 * @return \arcec\Event\checkEvent
	 */
	public function setDebut($heure){
		if(!is_null($heure) && $heure != "")
			$this->hDebut = $heure;
	
		return $this;
	}

	/**
	 * Heure de fin définie pour l'événement
	 * @param string $heure
	 * @return \arcec\Event\checkEvent
	 */
	public function setFin($heure){
		if(!is_null($heure) && $heure != "")
			$this->hFin = $heure;
	
		return $this;
	}

	/**
	 * Définit le bureau souhaité pour l'événement
	 * @param int $bureau
	 * @return \arcec\Event\checkEvent
	 */
	public function setBureau($bureau){
		if($bureau != 0)
			$this->bureau = $bureau;
	
		return $this;
	}
	
	public function setConseiller($conseiller){
		if($conseiller != 0){
			$this->conseiller = $conseiller;
		}
		return $this;
	}
	
	public function setEvent($event){
		if(!is_null($event) && $event != 0){
			$this->event = $event;
		}
		return $this;
	}
	/**
	 * Retourne le nombre d'événements avec ces paramètres
	 * @return number
	 */
	public function getNbEvent(){
		return $this->nb;
	}
	
	public function process(){
		// Traite les contraintes
		if(!is_null($this->date)){
			$this->eventMapper->searchBy("date",$this->date);
		}
		
		if(!is_null($this->bureau) && is_null($this->conseiller)){
			$this->eventMapper->searchBy("bureau_id",$this->bureau);	
		}
		
		if(!is_null($this->hDebut)){
			$this->eventMapper->specialClause(
				"'" . $this->hDebut . "' BETWEEN event_heuredebut AND event_heurefin" 
			);	
		}
		
		if(!is_null($this->event)){
			$this->eventMapper->specialClause("event_id <> " . $this->event);
		}
		
		if(!is_null($this->conseiller)){
			$this->eventMapper->set($this->eventMapper->getNameSpace());
			if($this->eventMapper->getNbRows() > 0){
				$this->nb = $this->filter($this->eventMapper->getCollection());
			}
		} else {
			$this->eventMapper->set($this->eventMapper->getNameSpace());
			if($this->eventMapper->getNbRows() > 0){
				// Ne pas tenir compte des "absences"
				foreach($this->eventMapper->getCollection() as $event){
					if(!$this->isInvisible($event))
						$this->nb++;
				}
			}
		}
	}
	
	/**
	 * Filtre les événements pour les conseillers sélectionnés
	 * @param array $collection Evénéments retournés
	 * @return int Nombre de lignes concernées du filtre courant
	**/
	private function filter($collection){
		$personEvent = new \arcec\Mapper\eventpersonMapper();
		
		foreach($collection as $event){
			$personEvent->clearSearch();
			
			$personEvent->searchBy("event_id", $event->id);
			$personEvent->searchBy("person", $this->conseiller);
			$personEvent->searchBy("mapper", "paramCNS");
			
			$personEvent->set($personEvent->getNameSpace());
			
			return $personEvent->getNbRows();
		}
	}
	
	/**
	 * Détermine si l'événement est de type invisible
	 * @param \arcec\Event $event
	 * @return boolean
	 */
	private function isInvisible($event){
		$type = new \arcec\Mapper\typeeventMapper();
		$type->setId($event->typeevent_id);
		$type->set($type->getNameSpace());
	
		return $type->getObject()->invisible == 1 ? true : false;
	}
}
?>