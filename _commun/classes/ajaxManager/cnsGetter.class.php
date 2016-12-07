<?php
/**
 * @name cnsGetter.php Service de récupération des conseillers disponibles sur un créneau donné
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Ajax
 * @version 1.0
**/

namespace arcec\Ajax;

class cnsGetter implements \wp\Ajax\ajax{
	
	/**
	 * Identifiant de l'événement à traiter
	 * @var int
	 */
	private $id;
	
	/**
	 * Date de l'événement
	 * @var string
	 */
	private $date;
	
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
	 * Objet de mapping sur les conseillers disponibles
	 * @var object
	 */
	private $cnsMapper;
	
	/**
	 * Objet de mapping sur les conseillers déjà définis
	 * @var object
	 */
	private $participants;

	
	/**
	 * Instancie un nouvel objet de récupération d'événements
	 */
	public function __construct(){
		$this->result = array();
		
		$this->cnsMapper = new \arcec\Mapper\paramCNSMapper();
		$this->participants = new \arcec\Mapper\eventpersonMapper();
		
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
				case "date":
					$value = new \DateTime($value);
				break;
				
				case "beginAt":
				case "endAt":
					$value = $this->date->format("Y-m-d") . " " . $value;
					$date = new \DateTime($value);
					//$date = new \DateTime(date("Y-m-d H:i",$value));
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
		$event = new \arcec\Mapper\eventMapper();
		
		$this->participants->searchBy("mapper","paramCNS");
		$this->participants->specialClause(
			"'" . $this->beginAt->format("H:i") . "' BETWEEN ". $event->getColumnPrefix() . "heuredebut AND " . $event->getColumnPrefix() . "heurefin"
		);
		$this->participants->searchBy("date",$this->date->format("Y-m-d"));
		$this->participants->select();
		
		if($this->participants->getNbRows() > 0){
			foreach($this->participants->getCollection() as $cns){
				$participants[] = $cns->person;
			}
		}
		
		// Ne récupère que les conseillers disponibles sur ce créneau
		if(sizeof($participants)){
			$search[] = array(
					"column" => "id",
					"operateur" => "NOT IN"
			);
			$value[] = "(" . implode(",",$participants) . ")";
			$this->cnsMapper->searchBy($search,$value);
		}	
		$this->cnsMapper->set($this->cnsMapper->getNameSpace());
		
		if($this->cnsMapper->getNbRows() > 0){
			foreach($this->cnsMapper->getCollection() as $cns){
				$this->result["data"][] = array(
					"id" => $cns->id,
					"content" => $cns->libellelong
				);
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
}
?>