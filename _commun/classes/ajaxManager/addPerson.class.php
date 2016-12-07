<?php
/**
 * @name addPerson.class.php Service d'ajout d'une personne pour un événement
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Ajax
 * @version 1.0
**/

namespace arcec\Ajax;

class addPerson implements \wp\Ajax\ajax{
	
	/**
	 * Tableau des résultats du processus d'ajout
	 * @var array
	 */
	private $result;
	
	/**
	 * Instance d'un objet de type eventpersonMapper pour mise à jour
	 * @var object
	 */
	private $eventPersonMapper;
	
	/**
	 * Instance d'un objet paramCNS ou dossier en fonction du type
	 * @var object
	 */
	private $personMapper;
	
	/**
	 * Identifiant de l'événement de référence
	 * @var int
	 */
	private $event;
	
	/**
	 * Identifiant du participant : conseiller ou porteur de projet
	 * @var int
	 */
	private $id;
	
	/**
	 * Détermine le type de personne à traiter
	 * @var string
	 */
	private $type;
	
	public function __construct(){
		$this->result["statut"] = 1;
		$this->result["data"] = null;
		
		$this->eventPersonMapper = new \arcec\Mapper\eventpersonMapper();
		
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
			
			if($name == "type"){
				if($value == "paramCNS"){
					$this->personMapper = new \arcec\Mapper\paramCNSMapper();
				} else {
					$this->personMapper = new \arcec\Mapper\dossierMapper();
				}
			}
		}
		return $this;
	}
	
	public function process(){
		// Remonte les données à afficher
		$this->personMapper->setId($this->id);
		$this->personMapper->set($this->personMapper->getNameSpace());
		$activeRecord = $this->personMapper->getObject();
		
		if($this->type == "paramCNS"){
			$content = $activeRecord->libellelong;
		} else {
			$content = $activeRecord->prenomporteur . " " . $activeRecord->nomporteur; 
		}
		
		// Traite la création d'une nouvelle personne pour l'événement donné
		$this->eventPersonMapper->person = $this->id;
		$this->eventPersonMapper->mapper = $this->type;
		$this->eventPersonMapper->event_id = $this->event;
		
		$id = $this->eventPersonMapper->save();
		
		$this->result["data"] = array(
			"id" => $id,
			"content" => $content
		);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \wp\Ajax\ajax::getResult()
	 */
	public function getResult(){
		return $this->result;
	}
}
?>