<?php
/**
 * @name eventChecker.class.php Services de contrôle de création d'un événement
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Ajax
 * @version 1.0
**/
namespace arcec\Ajax;

class eventChecker implements \wp\Ajax\ajax{
	/**
	 * Tableau de résultat à retourner
	 * @var array
	**/
	private $result;
	
	/**
	 * Instance d'un objet de contrôle
	 * @var object
	 */
	private $checker;
	
	/**
	 * Date de l'événement courant convertie au format SQL
	 * @var string
	 */
	private $date;

	/**
	 * Heure de début de l'événement, peut être nul
	 * @var string
	 */
	private $debut;
	
	/**
	 * Heure de fin de l'événement, peut être nul
	 * @var string
	 */
	private $fin;
	
	/**
	 * Bureau ou salle d'accueil de l'événement 
	 * @var int
	 */
	private $bureau;
	
	/**
	 * Identifiant de l'événement lui même si défini
	 * @var int
	 */
	private $event;
	
	/**
	 * Conseiller sélectionné
	 * @var int
	 */
	private $conseiller;
	
	public function __construct(){
		$this->result = array("statut" => 1,"content" => "Aucun événement ce jour...");
		
		$this->checker = new \arcec\Event\checkEvent();
		
		$this->date = null;
		$this->debut = null;
		$this->fin = null;
		$this->bureau = null;
		$this->event = null;
		
		// Traitement spécial pour le conseiller : ne peut se trouver positionner à plus d'un endroit en même temps
		$this->conseiller = null;
		
	}

	
	/**
	 * Définit les paramètres de récupération des données
	 * @param string $name Nom du paramètre à traiter
	 * @param mixed $value
	 * @return \arcec\Ajax\eventChecker
	 */
	public function addParam($name,$value){
		if(property_exists($this, $name)){
			$this->{$name} = $value;
		}
		
		return $this;
	}
	
	public function process(){
		$this->checker
			->setDate($this->date)
			->setDebut($this->debut)
			->setFin($this->fin)
			->setBureau($this->bureau)
			->setConseiller($this->conseiller)
			->setEvent($this->event)
			->process()
		;
		
		$nbEvent = $this->checker->getNbEvent();
		
		if(!is_null($this->bureau) && $nbEvent >= 1){
			$this->result["statut"] = 0;
		}
		$this->result["content"] = $this->checker->toString();
		
	}
	
	/**
	 * Retourne la liste des résultats
	 * @see \wp\Ajax\ajax::getResult()
	 */
	public function getResult(){
		return $this->result;
	}
}
?>