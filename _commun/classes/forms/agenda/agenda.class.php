<?php
/**
 * @name agenda.class.php Service d'affichage de l'agenda sur la barre latérale droit
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Agenda
 * @version 1.0
**/

namespace arcec\Agenda;

class agenda implements \wp\Tpl\template {
	/**
	 * Définit le nom du modèle à charger
	 * @var string
	 */
	private $templateName;
	
	/**
	 * Détermine le type d'affichage du calendrier
	 * @var string
	**/
	private $layer;
	
	/**
	 * Objet de visualisation de l'agenda
	 * @var object
	**/
	private $viewObject;
	
	/**
	 * Instancie un nouvel agenda pour publication sur la barre latérale
	**/
	public function __construct($layer=null){
		$this->setTemplateName("agenda");
		$this->layer = is_null($layer) ? "day" : $layer; // Par défaut, on affiche la journée complète
		
		$this->process();
	}
	
	/**
	 * Retourne l'objet pour la visualisation de l'agenda
	 * @return object
	 */
	public function getViewer(){
		return $this->viewObject;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \wp\Tpl\template::setTemplateName()
	 */
	public function setTemplateName($name){
		$this->templateName = "./agenda/" . $name . ".tpl";
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \wp\Tpl\template::getTemplateName()
	 */
	public function getTemplateName(){
		return $this->templateName;
	}
	
	/**
	 * Génère l'objet pour l'affichage du planning
	**/
	private function process(){
		$class = "\\arcec\\Agenda\\" . $this->layer . "View";
		
		$factory = new \wp\Patterns\factory($class);
		
		$this->viewObject = $factory->addInstance();
		
		// Définit les attributs en fonction du type de vue
		switch ($this->layer) {
			case "day":
				$this->viewObject
					->beginAt("07:00")
					->endAt("20:00")
					->step("15","m")
					->setTemplateName("dayView")
					->process()
				;
			break;
		
		}
	}
}
?>