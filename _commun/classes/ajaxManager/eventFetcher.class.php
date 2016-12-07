<?php
/**
 * @name eventFetcher.ajax.php Service de récupération des événements du planning
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Ajax
 * @version 1.0
**/

namespace arcec\Ajax;

class eventFetcher implements \wp\Ajax\ajax{
	
	/**
	 * Espace de nom de l'agenda
	 * @var string
	 */
	private $namespace;
	
	/**
	 * Plage de début de récupération des événements
	 * @var DateTime
	 */
	private $beginAt;
	
	/**
	 * Plage de fin de récupération des événements
	 * @var DateTime
	 */
	private $endAt;
	
	
	/**
	 * Liste des événements à retourner
	 * @var array
	 */
	private $result;
	
	/**
	 * Objet de mapping sur les événements
	 * @var object
	 */
	private $eventMapper;
	
	/**
	 * Identifiant du conseiller concerné, peut être égal à 0, dans ce cas, ne pas traiter le filtre
	 * @var int
	**/
	private $filterCns;
	
	/**
	 * Identifiant du type d'événement, peut être égal à 0, dans ce cas, ne pas traiter le filtre
	 * @var int
	 */
	private $filterType;
	
	/**
	 * Identifiant du bureau, peut être égal à 0, dans ce cas, ne pas traiter le filtre
	 * @var int
	 */
	private $filterBureau;
	
	/**
	 * Instancie un nouvel objet de récupération d'événements
	 */
	public function __construct(){
		$this->result = array();
		
		$this->eventMapper = new \arcec\Mapper\eventMapper();
		
	}
	
	/**
	 * Définit les proriétés de la classe
	 * @param string $name
	 * @param multitype $value
	 * @return \arcec\Ajax\eventFetcher
	 */
	public function addParam($name,$value){
		if(property_exists($this, $name)){
			switch($name){
				case "beginAt":
					$value = $value . " 00:00";
					$date = new \DateTime($value);
					$value = $date;
				break;
				
				case "endAt":
					$value = $value . "00:00";
					$date = new \DateTime($value);
					$date->modify("-1 minutes");
					$value = $date;
				break;
				
			}
			
			$this->{$name} = $value;

			return $this;
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \wp\Ajax\ajax::process()
	 */
	public function process(){
		$events = clone $this->eventMapper;
		
		if($this->beginAt->format("Y-m-d") != $this->endAt->format("Y-m-d")){
			$search[] = array("column"=>"date","operateur"=>"BETWEEN");
			$values[] = "'" . $this->beginAt->format("Y-m-d") . "' AND '" . $this->endAt->format("Y-m-d") . "'";
		} else {
			$search[] = array("column"=>"date","operateur"=>"=");
			$values[] = $this->beginAt->format("Y-m-d");
		}
		
		$events->searchBy($search,$values);
			
		$events->set($events->getNameSpace());
		
		if($events->count() > 0){
			foreach($events->getCollection() as $event){
				$locationParams = array(
					"com" => "setEvent",
					"context" => "UPDATE",
					"id" => $event->id
				);
				$location = \wp\Helpers\urlHelper::setAction($locationParams,false,"index.php");
				if($this->filtered($event)){
					$this->result[] = array(
						"id" => $event->id,
						"title" => $this->title($event),
						"allDay" => false,
						"start" => $this->toDate($event->date,$event->heuredebut),
						"end" => $this->toDate($event->date,$event->heurefin),
						"url" => $location, // Prévoir le calcul de l'URL pour la mise à jour d'un événement
						"className" => $this->classname($event), // Prévoir les classes spécifiques pour chaque type d'événements
						"editable" => false,
						"startEditable" => false,
						"durationEditable" => false,
						"rendering" => null,
						"overlap" => false,
						"constraint" => null,
						"description" => $this->description($event)
					);
				}
			}
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \wp\Ajax\ajax::getResult()
	 */
	public function getResult(){
		return $this->result;
	}
	
	/**
	 * Filtre l'événement en fonction des filtres définis : Conseiller, Type d'événement, Bureau
	 * @param object $event
	 * @return boolean
	 */
	private function filtered($event){
		$filter = true;
		
		if($this->filterCns + $this->filterType + $this->filterBureau == 0){
			return true; // Aucun filtre défini
		}
		
		if($this->filterCns){
			$filter = $this->filterCNS($event);
		}
		
		if($this->filterType){
			$filter = $filter && $this->filterType($event);
		}
		
		if($this->filterBureau){
			$filter = $filter && $this->filterBureau($event);
		}
		
		return $filter;
	}
	
	
	private function filterCNS($event){
		//Récupère les participants
		$persons = new \arcec\Mapper\eventpersonMapper();
		$persons->searchBy("event_id",$event->id);
		$persons->searchBy("mapper","paramCNS");
		
		$persons->set($persons->getNameSpace());
		
		if($persons->getNbRows() > 0){
			$conseillers = new \arcec\Mapper\paramCNSMapper();
			foreach($persons->getCollection() as $person){
				$conseillers->clearSearch();
				$conseillers->setId($person->person);
				$conseillers->set($conseillers->getNameSpace());
				$conseiller = $conseillers->getObject();
				if($conseiller->id == $this->filterCns){
					return true;
				}
			}
		}
		
		return false;		
	}
	
	private function filterType($event){
		return $this->filterType == $event->typeevent_id;	
	}

	private function filterBureau($event){
		return $this->filterBureau == $event->bureau_id;
	}
	
	/**
	 * Retourne une chaîne Date et Heure
	 * @param string $usDate
	 * @param string $time
	 * @return string
	 */
	private function toDate($usDate,$time){
		$usDate = \wp\Helpers\dateHelper::toSQL($usDate, "dd/mm/yyyy");
		$date = new \DateTime($usDate . " " . $time);
		return $date->format("Y-m-d H:i");
	}
	
	private function title($event){
		$title = $event->titre;
		
		// Traiter les conseillers, si disponible et afficher le trigramme si unique
		// ou le premier trigramme suivi de "n de plus..."
		$persons = new \arcec\Mapper\eventpersonMapper();
		$persons->searchBy("event_id",$event->id);
		$persons->searchBy("mapper", "paramCNS");
		$persons->set($persons->getNameSpace());
		if($persons->getNbRows() > 0){
			foreach($persons->getCollection() as $person){
				$factory = new \wp\Patterns\factory("\\arcec\\Mapper\\" . $person->mapper . "Mapper");
				$object = $factory->addInstance();
				$object->setId($person->person);
				$object->set("\\arcec\\Mapper\\");
				$participant = $object->getObject();
				$trigrammes[] = $participant->libellecourt;
			}
			if(sizeof($trigrammes) > 1){
				$title .= " [" . $trigrammes[0] . " et " . sizeof($trigrammes) - 1 . " de plus...]";
			} else {
				$title .= " [" . $trigrammes[0] . "]";
			}
		}		
		// Détermine le type de l'événement
		$type = $event->getSchemeDetail("typeevent_id","mapper");
		$type->setId($event->typeevent_id);
		$type->set($type->getNameSpace());
		$object = $type->getObject();

		$title .= " (" . $object->titre . ")";
		
		return $title;
	}
	/**
	 * Récupère toutes les informations relatives à l'événement
	 * @param object $event
	 */
	private function description($event){
		$description = "";
		
		$description .= $event->objet . "<br />\n";
		
		// Détermine le lieu (Lieu et bureau)
		$bureau = $event->getSchemeDetail("bureau_id","mapper");
		$bureau->setId($event->bureau_id);
		$bureau->set($bureau->getNameSpace());
		$theBureau = $bureau->getObject();
		
		$lieu = $bureau->getSchemeDetail("acu","mapper");
		$lieu->setId($theBureau->acu);
		$lieu->set($lieu->getNameSpace());
		$theLieu = $lieu->getObject();
		
		$description .= $theLieu->libellelong . " : " . $theBureau->libelle;
		
		
		//Récupère les participants
		$persons = new \arcec\Mapper\eventpersonMapper();
		$persons->searchBy("event_id",$event->id);
		$persons->set($persons->getNameSpace());
		
		if($persons->getNbRows() > 0){
			$description .= "<ul>\n";
			foreach($persons->getCollection() as $person){
				$factory = new \wp\Patterns\factory("\\arcec\\Mapper\\" . $person->mapper . "Mapper");
				$object = $factory->addInstance();
				$object->setId($person->person);
				$object->set("\\arcec\\Mapper\\");
				$participant = $object->getObject();
				
				$description .= "\t<li>";
				if($person->mapper == "dossier"){
					$description .= $participant->prenomporteur . " " . $participant->nomporteur;
				} else {
					$description .= $participant->libellelong;
				}
				$description .= "</li>\n";
			}
			$description .= "</ul>\n";
		}
		return $description;
	}
	
	/**
	 * Retourne la classe CSS associée au type d'événement
	 * @param object $event Evénément courant
	 * @return string Classe CSS à utiliser
	 */
	private function classname($event){
		$type = new \arcec\Mapper\typeeventMapper();
		$type->setId($event->typeevent_id);
		$type->set($type->getNameSpace());
		
		$object = $type->getObject();
		
		if($object->classname == ""){
			return null;
		}
		
		return $object->classname;
	}
}
?>