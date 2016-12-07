<?php
/**
 * @name listeDossier.class.php Services d'affichage de la liste des dossiers des porteurs de projet
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Dossier
 * @version 1.0.1
 **/
namespace arcec;

class listeDossier extends \arcec\Dossier\dossier {
	
	public function __construct(){
		$this->module = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
		
		$this->namespace = __NAMESPACE__;
		
		$this->setTemplate();
		
		$this->setDossier();
		
		$this->filter();
		
		\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Liste des dossiers");
		
		$index = new \wp\formManager\tableIndex($this->dossierMapper);
		$index->setTemplateName("tableIndex.tpl","./");
		$index->setHeaders(array(
				"datecreation" => "Date d'accueil",
				"nomporteur" => "Nom",
				"prenomporteur" => "Prénom",
				"porteurcnscoord" => array("header" => "Conseiller","column"=>"libellelong","mapper"=>new \arcec\Mapper\paramCNSMapper()),
				"etd" => array("header" => "Phase du projet", "column" => "libellelong","mapper" => new \arcec\Mapper\paramETDMapper())
			)
		)
		->setContext("liste")
		->addPager(20)
		->addFilter("type",20)
		->setPlugin("tablesorter")
		;

		// Ajoute le script pour le chargement du formulaire de mise à jour
		$target = \wp\Helpers\urlHelper::setAction(array("com"=>"suiviDossier"));
		$target .= "&context=UPDATE&id=";
		$this->clientRIA .= "
			$(\".tablesorter tbody tr\").on(\"click\",function(){
				var dossierId = $(this).data(\"rel\");
				document.location.replace(\"" . $target . "\"+ dossierId) 
			});
		";
		$this->toControls();
		
		\wp\Tpl\templateEngine::getEngine()->setVar("index", $index);
	}
}
?>