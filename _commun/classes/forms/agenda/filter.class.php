<?php
/**
 * @name filter.class.php Services de filtrage de l'agenda
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Agenda;
 * @version 1.0
**/
namespace arcec\Agenda;

class filter extends \wp\formManager\form {
	/**
	 * ID CSS du calendrier à filtrer
	 * @var string
	 */
	private $supportId;
	
	public function __construct(){
		// Définition des paramètres du formulaire de filtrage
		$this
			->setId("filtre-agenda")
			->setName("filtre-agenda")
			->setMethod("post");
	}
	
	public function isValidate(){}
	
	public function process(){}
	
	public function supportId($id){
		$this->supportId = $id;
		$this->set();
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \wp\formManager\form::set()
	 */
	protected function set(){
		
		// Liste des conseillers
		$mapper = new \arcec\Mapper\paramCNSMapper();
		if($mapper->count() > 0){
			$field = new \wp\formManager\Fields\popup();
			$field
				->setId("frmCNS")
				->setName("paramCNS")
				->setLabel("Conseiller")
				->setCss("control-label",true)
				->setCss("col-sm-12",true)
				->setCss("form-control")
				->setCss("trigger")
				->setGroupCss("col-sm-4")
				->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
				->setHeaderLine(0,"Tous...")
				->setValue(\wp\Helpers\httpQueryHelper::get("paramCNS"));		
		
				$this->addToFieldset($field);
		}

		// Type de l'événement
		$mapper = new \arcec\Mapper\typeeventMapper();
		
		if($mapper->count() > 0){
			$field = new \wp\formManager\Fields\popup();
				
			$field->setId("frmTypeEvent")
			->setName("paramType")
			->setLabel("Type d'événement")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-4")
			->setCss("trigger")
			->setMapping($mapper,array("value" => "id", "content"=>array("titre")))
			->setHeaderLine(0,"Tous...")
			;
				
			$this->addToFieldset($field);
		}
		
		// Listes des bureaux
		$mapper = new \arcec\Mapper\bureauMapper();
		if($mapper->count() > 0){
			$field = new \wp\formManager\Fields\optgroup();
			$field->setId("frmBureau")
			->setName("paramBureau")
			->setLabel("Lieu")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-4")
			->setCss("trigger")
			->setMapping($mapper,array("value" => "id", "content"=>array("codification","libelle")))
			->parentMapper("acu",$mapper->getSchemeDetail("acu","mapper"),array("libellecourt","libellelong"))
			->setHeaderLine(0,"Tous...")
			;
		
			$this->addToFieldset($field);
			
			// Ajoute le script de contrôle du clic sur une des lignes de la liste
			$this->clientRIA .= "
				$(\".trigger\").on(\"change\",function(evt){
						console.log(\"Recharge les événements avec les filtres concernés\");
						$(\"#" . $this->supportId . "\").fullCalendar(\"refetchEvents\");
					}
				);
			";
			
			$this->toControls();
		}		
	}
}
?>