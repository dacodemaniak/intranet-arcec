<?php
/**
 * @name dayEventGetter.class.php Services de récupération des événements à une date donnée
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Ajax
 * @version 1.0
**/
namespace arcec\Ajax;

class dayEventGetter implements \wp\Ajax\ajax{
	/**
	 * Tableau de résultat à retourner
	 * @var array
	**/
	private $result;
	
	/**
	 * Instance d'un objet de récupération de données
	 * @var object
	 */
	private $dayView;
	
	/**
	 * Date de l'événement courant convertie au format SQL
	 * @var string
	 */
	private $date;

	/**
	 * Stocke les événements traités en cas d'événements multiples sur une plage
	 * @var array
	 */
	private $eventsTreated;
	
	public function __construct(){
		$this->result = array();
		
		$this->date = null;
		
		$this->eventsTreated = array();
	}

	
	/**
	 * Définit les paramètres de récupération des données
	 * @param string $name Nom du paramètre à traiter
	 * @param mixed $value
	 * @return \arcec\Ajax\dayEventGetter
	 */
	public function addParam($name,$value){
		if(property_exists($this, $name)){
			$this->{$name} = $value;
		}
		
		return $this;
	}
	
	public function process(){
		
		$this->dayView = new \arcec\Agenda\dayView($this->date,"Y-m-d");
		$this->dayView->process();
		
		$this->result["nbEvent"] = $this->dayView->nbEvent();
		$this->result["date"] = $this->date;
		
		if($this->dayView->nbEvent() > 0)
			$this->result["content"] = $this->prepare($this->dayView->getBrutEvents());
		else 
			$this->result["content"] = null;
		
	}
	
	/**
	 * Retourne la liste des résultats
	 * @see \wp\Ajax\ajax::getResult()
	 */
	public function getResult(){
		return $this->result;
	}
	
	/**
	 * Prépare le tableau à retourner à la fonction Ajax de traitement
	 * @param array $events
	 * @return array
	 */
	private function prepare($events){
		$preparedEvents = array();
		
		$urlParams		= array(
			"com" => "setEvent",
			"context" => "UPDATE",
			"id" => null
		);
		
		$moreParams		= array(
			"com" => "viewEvent",
			"context" => "VIEW",
			"date" => $this->dayView->getInitDate("url"),
			"heure" => null
		);
		
		foreach ($events as $event){
			if(!in_array($event["id"],$this->eventsTreated)){
				if(!$moreEvents = $this->multipleEventByPlage($events,$event)){
					// Un seul événement sur la page courante
					$urlParams["id"] = $event["id"];
					$preparedEvents[] = array(
						"datarel" =>\wp\Helpers\dateHelper::to($this->dayView->getInitDate("sql") . " " . $event["debut"],"H:i"),
						"titre" => $event["titre"],
						"description" => $event["description"],
						"link" => \wp\Helpers\urlHelper::setAction($urlParams,false,"./index.php"),
						"moreEvents" => null
					);
				} else {
					$moreParams["heure"] = \wp\Helpers\dateHelper::to($this->dayView->getInitDate("sql") . " " . $event["debut"],"H:i");
					$urlParams["id"] = $event["id"];
					$preparedEvents[] = array(
							"datarel" => \wp\Helpers\dateHelper::to($this->dayView->getInitDate("sql") . " " . $event["debut"],"H:i"),
							"titre" => $event["titre"],
							"description" => $event["description"],
							"link" => \wp\Helpers\urlHelper::setAction($urlParams,false,"./index.php"),
							"moreEvents" => array(
								"nb" => $moreEvents,
								"titre" => "de plus...",
								"link" => \wp\Helpers\urlHelper::setAction($moreParams,false,"./index.php")
							)
					);				
				}
			}
		}
		return $preparedEvents;
	}
	
	private function multipleEventByPlage($events,$event){
		$currentId = $event["id"];
		$currentDeb = $event["debut"];
		
		$moreEvents	= 0;
		foreach($events as $checkEvent){
			if($checkEvent["id"] != $currentId && $checkEvent["debut"] == $currentDeb){
				$this->eventsTreated[] = $checkEvent["id"];
				$moreEvents++;
			}
		}
		
		return $moreEvents;
	}
}
?>