<?php
/**
 * @name agendaViewer.class.php Classe abstraite de visualisation de l'agenda
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package \arcec\Agenda
 * @version 1.0
**/
namespace arcec\Agenda;

abstract class agendaViewer {
	/**
	 * Début de la plage à afficher
	 * @var string
	 */
	private $beginAt;
	
	/**
	 * Fin de la plage à afficher
	 * @var string
	 */
	private $endAt;
	
	
	/**
	 * Valeur du pas de l'intervalle de temps
	 * @var int
	 */
	protected $step;
	
	/**
	 * Recopie statique du pas de l'intervalle de temps
	 * @var int
	 */
	protected static $sstep;
	
	/**
	 * Expression de l'intervalle de temps (jours, semaines, mois, année, heures, minutes)
	 * @var string
	 */
	protected $stepType;
	
	/**
	 * Recopie statique de l'expression de l'intervalle de temps
	 * @var string
	 */
	protected static $sstepType;
	
	/**
	 * Objet de mapping sur les événements
	 * @var array
	 */
	protected $mappers;
	
	/**
	 * Date du jour pour détermination des affichages
	 * @var object
	 */
	private $initDate;
	
	/**
	 * Tableau associatif contenant les événements 
	 * @var array
	 */
	protected $events;
	
	/**
	 * Evénéments définis pour une plage donnée
	 * @var array
	 * @see setEventContent
	**/
	protected $eventsByPlage;
	
	/**
	 * Nombre d'événements récupérés
	 * @var int
	 */
	protected $nbEvent;
	
	/**
	 * Nombre d'événement pour un plage donnée
	 * @var int
	 */
	protected $nbEventByPlage;
	
	/**
	 * Plages de temps à afficher
	 * @var array
	 */
	protected $plages;
	
	/**
	 * Traite la remontée des informations de l'agenda à partir des paramètres
	 * @return void
	**/
	abstract public function process();
	
	/**
	 * Force la définition des plages à afficher
	 */
	abstract protected function plages();
	
	/**
	 * Définit le début de la plage d'affichage
	 * @param string $date
	 */
	public function beginAt($date=null){
		if(!is_null($date)){
			$this->beginAt = $date;
			return $this;
		}
		return $this->beginAt;
	}
	
	/**
	 * Définit la fin de la plage d'affichage
	 * @param string $date
	 */
	public function endAt($date=null){
		if(!is_null($date)){
			$this->endAt = $date;
			return $this;
		}
		return $this->endAt;
	}

	/**
	 * Détermine le pas et le type d'intervalle entre le début et la fin
	 * @param int $interval
	 * @param string $type
	 */
	public function step($interval,$type){
		$this->step = $interval;
		self::$sstep = $this->step;
		
		$this->stepType = strtoupper($type);
		self::$sstepType = $this->stepType;
		
		return $this;
	}
	
	protected function mappers(){
		$this->mappers["event"] = new \arcec\Mapper\eventMapper();
		$this->mappers["person"] = new \arcec\Mapper\eventpersonMapper();
		$this->mappers["lieu"] = new \arcec\Mapper\bureauMapper();
	}
	
	public function nbEvent(){
		return $this->nbEvent;
	}
	
	public function getPlages(){
		return $this->plages;
	}
	
	/**
	 * Retourne les événements bruts traités
	 * @return array
	 */
	public function getBrutEvents(){
		return $this->events;
	}
	
	/**
	 * Retourne le lieu physique de l'événement
	 * @param unknown_type $id
	 */
	protected function getLieu($id){
		$bureau = $this->mappers["lieu"];
		$bureau->setId($id);
		$bureau->set($bureau->getNameSpace());
		
		if($bureau->getNbRows() > 0){
			$activeRecord = $bureau->getObject();
			$lieu = $bureau->getSchemeDetail("acu","mapper");
			$lieu->setId($activeRecord->acu);
			$lieu->set($lieu->getNameSpace());
			$record = $lieu->getObject();
			return $record->libellelong;
		}
		return "Non défini";
	}
	
	protected function initDate($date=null,$format="Y-m-d"){
		if(is_null($date) || $date == ""){
			$this->initDate = new \DateTime();
		} else {
			if($format != "Y-m-d"){
				$component = date_parse_from_format($format, $date);
				$date = $component["year"] . "-" . $component["month"] . "-" . $component["day"];
			}
			
			$this->initDate = new \DateTime($date);
		}
		return $this;
	}
	
	public function getInitDate($format="long"){
		switch($format){
			case "long": // Forme Lundi .. Dimanche JJ Janvier .. Décembre YYYY
				$date = \wp\Helpers\dateHelper::jourSemaine($this->initDate->format("w"))
					. " " . $this->initDate->format("d")
					. " " . \wp\Helpers\dateHelper::mois($this->initDate->format("n"))
					. " " . $this->initDate->format("Y");
			break;
			
			case "sql":
				return $this->initDate->format("Y-m-d");
			break;
			
			case "url":
				return $this->initDate->format("d/m/Y");
			break;
			
			case "object":
				return $this->initDate;
			break;
		}
		
		return $date;
	}
	
	/**
	 * Détermine le nombre d'événement pour une plage de date donnée
	 * @param object $oDate Date du jour de récupération
	 * @param string $heure Racine de l'heure à traiter
	 * @return boolean
	**/
	public function hasEvent($oDate,$heure=null){
		$event = clone $this->mappers["event"];
		
		$event->clearSearch();
		
		$search[] = array("column"=>"date","operateur" => "=");
		$values[] = $oDate->format("Y-m-d");
		
		if(!is_null($heure)){
			// Corrige la dernière heure
			if(strlen($heure) != 5){
				$heure .= "00";
			}
			// Tous les événements compris dans la plage horaire
			$begin = new \DateTime($this->getInitDate("object")->format("Y-m-d") . " " . $heure);
			$dateInterval = new \DateInterval("PT59M");
			$end = clone $begin;
			$end->add($dateInterval);
				
			$search[] = array("column"=>"heuredebut","operateur"=>"BETWEEN");
			$values[] = "'" . $begin->format("H:i") . "' AND '" . $end->format("H:i") . "'";
				
			/*
			$search[] = array("column"=>"heuredebut","operateur" => "<=");
			$values[] = $heure;
			$search[] = array("column"=>"heurefin","operateur" => ">");
			$values[] = $heure;
			*/
		}
		
		$event->searchBy($search,$values);
		
		$this->nbEventByPlage = $event->count();
		
		return $this->nbEventByPlage > 0 ? true : false;
	}
	
	/**
	 * Retourne le nombre d'événements pour une plage donnée
	 * @return number
	 */
	public function getNbEventByPlage(){
		return $this->nbEventByPlage - 1;
	}
	/**
	 * Définit les événements sur une plage donnée
	 * @param object $oDate
	 * @param string $heure
	 * @return void
	**/
	public function setEventContent($oDate,$heure=null){
		$events					= array();
		
		$event = clone $this->mappers["event"];
			
		$event->clearSearch();
			
		$search[] = array("column"=>"date","operateur" => "=");
		$values[] = $oDate->format("Y-m-d");
			
		if(!is_null($heure)){
			if(strlen($heure) != 5){
				$heure .= "00";
			}

			
			// Tous les événements compris dans la plage horaire
			$begin = new \DateTime($this->getInitDate("object")->format("Y-m-d") . " " . $heure);
			/*
			if($begin->format("i") == "00")
				$dateInterval = new \DateInterval("PT59M");
			else {
				$step = $this->step - 1;
				$dateInterval = new \DateInterval("PT" . $step . $this->stepType);
			}
			$end = clone $begin;
			$end->add($dateInterval);
				
			$search[] = array("column"=>"heuredebut","operateur"=>"BETWEEN");
			$values[] = "'" . $begin->format("H:i") . "' AND '" . $end->format("H:i") . "'";
			*/
			$search[] = array("column"=>"heuredebut","operateur"=>"=");
			$values[] = $begin->format("H:i");
		}
			
		$event->searchBy($search,$values);
			
		$event->set($event->getNameSpace());
			
			
		$indice = 0;
		$urlParams	= array();
			
		$this->nbEventByPlage = $event->count();
			
		if($event->count() > 0){
			foreach($event->getCollection() as $record){
				$urlParams = array(
					"com" => "setEvent",
					"context" => "UPDATE",
					"id" => $record->id					
				);
					
				$events[] = array(
						"titre" => $record->titre,
						"description" => $record->objet,
						"url" => \wp\Helpers\urlHelper::setAction($urlParams)
	
				);
				$indice++;				
			}
			
		
		} else {
			$events[] = array(
				"titre" => "",
				"description" => "Cliquez pour ajouter un événement sur cette plage horaire",
				"url" => null
			);
		}

		$this->eventsByPlage = $events;
	}
	
	/**
	 * Retourne le premier élément de la liste des événements pour la plage
	 * @return array
	 */
	public function getFirstEvent($label){
		return $this->eventsByPlage[0][$label];
	}
	
	/**
	 * Retourne les événements d'une plage donnée
	 * @return array
	**/
	public function getEventContent(){
		return $this->eventsByPlage;	
	}
	
	/**
	 * Retourne la plage horaire la plus proche de l'heure courante en fonction du pas
	**/
	private function getCurrentStepTime(){
		$currentTime = new \DateTime();	
	}
	
	public static function currentStepTime($step=null,$stepType=null){
		$currentTime = new \DateTime();
		
		$timeParts = explode(":",$currentTime->format("H:i"));
		
		// Définit l'heure de départ
		$hDeb = $currentTime->format("Y-m-d") . " " . $timeParts[0] . ":00";
		$heureDeb = new \DateTime($hDeb);
		
		self::$sstep = !is_null($step) ? $step : 15;
		self::$sstepType = !is_null($stepType) ? $stepType : "M";
		
		
		$dateInterval = new \DateInterval("PT" . self::$sstep . strtoupper(self::$sstepType));
		
		$steppedTime = clone $heureDeb;
		
		do{
			$steppedTime->add($dateInterval);
		} while($steppedTime < $currentTime);		
		
		return $steppedTime->format("H:i");
	}
	
	/**
	 * Compare la date (et l'heure) courante à une date (heure) référente
	 * @param string $usDate Date au format US
	 * @param string $time (Optionnel) Heure
	 * @return boolean
	 */
	private function doAdd($usDate,$time=null){
		$currentDateTime = new \DateTime();
		
		$strDate = $date;
		
		if(!is_null($time)){
			 $strDate .= " " . $time;
		}
		
		$referentDateTime = new \DateTime($strDate);
		
		return $referentDateTime > $currentDateTime;
	}
}
?>