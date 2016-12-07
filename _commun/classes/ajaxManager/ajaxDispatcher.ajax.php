<?php
/**
 * @name ajaxDispatcher.ajax.php : Service de dispatching de requêtes Ajax
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec;
 * @version 1.0 
**/

if(file_exists(dirname(__FILE__) . "/../../../../appLoader.class.php")){
	require_once(dirname(__FILE__) . "/../../../../appLoader.class.php");
		$results = array("status" =>  1);
} else {
	$results = array("status" => 0);
}

\apps\appLoader::setMappings();

require_once(dirname(__FILE__) . "/../../../../../webprojet.framework-1.0/wp.class.php");

\wp\framework::getFramework()->setAppsURI("myapps.localdomain");
\wp\framework::getFramework()->setAppRoot(dirname(__FILE__) . "/../../../");
\wp\framework::getFramework()->setAppsRoot(dirname(__FILE__) . "/../../../../");

// Charge la configuration de l'application
$appConfig = new \apps\Config\appConfig(\wp\framework::getFramework()->getAppRoot() . "/_commun/configs/appConfigure.xml");

\wp\framework::getFramework()->setTimeZone($appConfig->get("timeZone"));

// Instancie l'objet passé en paramètre
switch(\wp\Helpers\httpQueryHelper::get("object","getPorteur")){
	case "dataLoader":
		if(\wp\Helpers\httpQueryHelper::get("namespace") && \wp\Helpers\httpQueryHelper::get("mapper")){
			if(\wp\Helpers\httpQueryHelper::get("content")){
				// On crée l'objet
				$dataLoader = new \wp\Ajax\dataLoader();
				$dataLoader->setMapper(str_replace("_","\\",\wp\Helpers\httpQueryHelper::get("namespace")) . \wp\Helpers\httpQueryHelper::get("mapper") . "Mapper")
					->setCaption(\wp\Helpers\httpQueryHelper::get("caption"))
					->setContent(\wp\Helpers\httpQueryHelper::get("content"))
					->isLike(\wp\Helpers\httpQueryHelper::get("islike",true))
					->process();
				$results = $dataLoader->getResult();
				
			} else {
				$results = array("status"=>false,"message"=>"Les données sont insuffisantes pour créer l'objet");
			}
		}
	break;
	
	case "rowLoader":
		$object = new \wp\Ajax\rowLoader();
		$object->setMapper(str_replace("_","\\",\wp\Helpers\httpQueryHelper::get("namespace")) . \wp\Helpers\httpQueryHelper::get("mapper") . "Mapper")
		->setCaption(\wp\Helpers\httpQueryHelper::get("caption"))
		->setContent(\wp\Helpers\httpQueryHelper::get("content"))
		->process();
		$results = $object->getResult();		
	break;
	
	case "paramRemove":
		$object = new \arcec\Ajax\paramRemove();
		$object->setMapper(str_replace("_","\\",\wp\Helpers\httpQueryHelper::get("namespace")) . \wp\Helpers\httpQueryHelper::get("mapper") . "Mapper")
			->setId(\wp\Helpers\httpQueryHelper::get("id"))
			->process();
		$results = $object->getResult();
	break;
	
	case "itemDelete":
		$delete = new \wp\Ajax\itemDelete();
		$delete->setMapper(str_replace("_","\\",\wp\Helpers\httpQueryHelper::get("namespace")) . \wp\Helpers\httpQueryHelper::get("mapper") . "Mapper")
			->setId(\wp\Helpers\httpQueryHelper::get("id"))
			->process();
		$results = $delete->getResult();
	break;
	
	case "multipleMapperDataLoader":
		$factory = new \wp\Patterns\factory("\\wp\\Ajax\\" . \wp\Helpers\httpQueryHelper::get("object"));
		if(\wp\Helpers\httpQueryHelper::get("content")){
			// On crée l'objet
			$dataLoader = $factory->addInstance();
			
			$dataLoader
				->mapper(\wp\Helpers\httpQueryHelper::get("mapper"))
				->caption(\wp\Helpers\httpQueryHelper::get("caption"))
				->search(\wp\Helpers\httpQueryHelper::get("search"))
				->content(\wp\Helpers\httpQueryHelper::get("content"))
				->extras(\wp\Helpers\httpQueryHelper::get("extras"))
				->definedIds(\wp\Helpers\httpQueryHelper::get("defined"))
				->process();
			$results = $dataLoader->getResult();
	
		} else {
			$results = array("status"=>false,"message"=>"Les données sont insuffisantes pour créer l'objet");
		}
	break;
		
	case "dossierCounter":
		$factory = new \wp\Patterns\factory("\\arcec\\Ajax\\dossierCounter");
		$counter = $factory->addInstance();
		$counter
			->setMapper(str_replace("_","\\",\wp\Helpers\httpQueryHelper::get("namespace")) . \wp\Helpers\httpQueryHelper::get("mapper") . "Mapper")
			->setParams(\wp\Helpers\httpQueryHelper::get("param"))
			->process();
		$results = $counter->getResult();
			
	break;
	
	case "getPorteur":
		$factory = new \wp\Patterns\factory("\\arcec\\Ajax\\getPorteur");
		$porteur = $factory->addInstance();
		$porteur->setDossier(\wp\Helpers\httpQueryHelper::get("dossier"))
			->process();
		$results = $porteur->getResult();
	break;
	
	case "taxonomyChecker":
		$factory = new \wp\Patterns\factory("\\arcec\\Ajax\\" . \wp\Helpers\httpQueryHelper::get("object"));
		$checker = $factory->addInstance();
		$checker
			->setMapper(str_replace("_","\\",\wp\Helpers\httpQueryHelper::get("namespace")) . \wp\Helpers\httpQueryHelper::get("mapper") . "Mapper")
			->setParams(\wp\Helpers\httpQueryHelper::get("content"))
			->process();
		$results = $checker->getResult();
		
	break;
	
	case "taxonomyMaker":
		$factory = new \wp\Patterns\factory("\\arcec\\Ajax\\" . \wp\Helpers\httpQueryHelper::get("object"));
		$maker = $factory->addInstance();
		$maker
		->setMapper(str_replace("_","\\",\wp\Helpers\httpQueryHelper::get("namespace")) . \wp\Helpers\httpQueryHelper::get("mapper") . "Mapper")
		->setParams(\wp\Helpers\httpQueryHelper::get("content"))
		->process();
		$results = $maker->getResult();
	
	break;

	case "updatePhase":
		$factory = new \wp\Patterns\factory("\\arcec\\Ajax\\" . \wp\Helpers\httpQueryHelper::get("object"));
		$phase = $factory->addInstance();
		$phase->keys(
				array(
					"dossier_id" => \wp\Helpers\httpQueryHelper::get("dossier"),
					"programme_id" => \wp\Helpers\httpQueryHelper::get("programme"),
					"etapeprojet_id" => \wp\Helpers\httpQueryHelper::get("etape")
				)
			)
			->values(
				array(
						"conseiller_id" => \wp\Helpers\httpQueryHelper::get("conseiller"),
						"action_id" => \wp\Helpers\httpQueryHelper::get("action"),
						"date" => \wp\Helpers\dateHelper::today()
				)
			)
			->process();
			$results = $phase->getResult();
	break;
	
	case "zipMaker":
		$factory = new \wp\Patterns\factory("\\arcec\\Ajax\\" . \wp\Helpers\httpQueryHelper::get("object"));
		$zipper = $factory->addInstance();
		$zipper->files(explode("|",\wp\Helpers\httpQueryHelper::get("content")))
			->process();
		$results = $zipper->getResult();
	break;
	
	case "filtreAnnuaire":
		$factory = new \wp\Patterns\factory("\\arcec\\Ajax\\" . \wp\Helpers\httpQueryHelper::get("object"));
		$filtre = $factory->addInstance();
		$filtre->mapper(\wp\Helpers\httpQueryHelper::get("mapper"),"annuaire")
			->mapper(\wp\Helpers\httpQueryHelper::get("parent"),"taxonomie")
			->arborescence(\wp\Helpers\httpQueryHelper::get("content"))
			->alpha(\wp\Helpers\httpQueryHelper::get("alpha"))
			->process();
		$results = $filtre->getResult();
	break;

	case "filtreRessource":
		$factory = new \wp\Patterns\factory("\\arcec\\Ajax\\" . \wp\Helpers\httpQueryHelper::get("object"));
		$filtre = $factory->addInstance();
		$filtre->mapper(\wp\Helpers\httpQueryHelper::get("mapper"),"ressource")
		->mapper(\wp\Helpers\httpQueryHelper::get("parent"),"taxonomie")
		->arborescence(\wp\Helpers\httpQueryHelper::get("content"))
		//->alpha(\wp\Helpers\httpQueryHelper::get("alpha"))
		->process();
		$results = $filtre->getResult();
	break;
	
	case "getDuree":
		$factory = new \wp\Patterns\factory("\\arcec\\Ajax\\" . \wp\Helpers\httpQueryHelper::get("object"));
		$duree = $factory->addInstance();
		$duree->setType(\wp\Helpers\httpQueryHelper::get("content"))
		->process();
		$results = $duree->getResult();
	break;

	case "eventChecker":
		$factory = new \wp\Patterns\factory("\\arcec\\Ajax\\" . \wp\Helpers\httpQueryHelper::get("object"));
		$checker = $factory->addInstance();
		$checker
			->addParam("date",\wp\Helpers\httpQueryHelper::get("selectedDate"))
			->addParam("debut",\wp\Helpers\httpQueryHelper::get("hDeb"))
			->addParam("fin",\wp\Helpers\httpQueryHelper::get("hFin"))
			->addParam("bureau",\wp\Helpers\httpQueryHelper::get("bureau"))
			->addParam("conseiller",\wp\Helpers\httpQueryHelper::get("conseiller"))
			->addParam("event",\wp\Helpers\httpQueryHelper::get("event"))
			->process()
		;
		$results = $checker->getResult();
	break;
	
	case "dayEventGetter":
		$factory = new \wp\Patterns\factory("\\arcec\\Ajax\\" . \wp\Helpers\httpQueryHelper::get("object"));
		$checker = $factory->addInstance();
		$checker
			->addParam("date",\wp\Helpers\httpQueryHelper::get("selectedDate"))
			->process()
		;
		$results = $checker->getResult();

	break;
	
	case "eventFetcher":
		$factory = new \wp\Patterns\factory("\\arcec\\Ajax\\" . \wp\Helpers\httpQueryHelper::get("object"));
		$events = $factory->addInstance();
		$events
			->addParam("namespace",str_replace("_","\\",\wp\Helpers\httpQueryHelper::get("namespace")))
			->addParam("beginAt",\wp\Helpers\httpQueryHelper::get("start"))
			->addParam("endAt",\wp\Helpers\httpQueryHelper::get("end"))
			->addParam("filterCns",\wp\Helpers\httpQueryHelper::get("paramCNS"))
			->addParam("filterType",\wp\Helpers\httpQueryHelper::get("paramType"))
			->addParam("filterBureau",\wp\Helpers\httpQueryHelper::get("paramBureau"))
			->process();
		$results = $events->getResult();
	break;
	
	case "cnsGetter":
		$factory = new \wp\Patterns\factory("\\arcec\\Ajax\\" . \wp\Helpers\httpQueryHelper::get("object"));
		$cns = $factory->addInstance();
		$cns
			->addParam("date",\wp\Helpers\httpQueryHelper::get("selectedDate"))
			->addParam("beginAt",\wp\Helpers\httpQueryHelper::get("hDeb"))
			->addParam("endAt",\wp\Helpers\httpQueryHelper::get("hFin"))
			->addParam("id",\wp\Helpers\httpQueryHelper::get("event"))
			->process();
		$results = $cns->getResult();
	break;
	
	case "addPerson":
		$factory = new \wp\Patterns\factory("\\arcec\\Ajax\\" . \wp\Helpers\httpQueryHelper::get("object"));
		$person = $factory->addInstance();
		$person
			->addParam("id",\wp\Helpers\httpQueryHelper::get("id"))
			->addParam("type",\wp\Helpers\httpQueryHelper::get("type"))
			->addParam("event",\wp\Helpers\httpQueryHelper::get("event"))
			->process();
		$results = $person->getResult();
	break;
	
	case "deleteEvent":
		$factory = new \wp\Patterns\factory("\\arcec\\Ajax\\" . \wp\Helpers\httpQueryHelper::get("object"));
		$deleteEvent = $factory->addInstance();
		$deleteEvent
			->addParam("eventId",\wp\Helpers\httpQueryHelper::get("eventId"))
			->process();
		$results = $deleteEvent->getResult();
	break;
	
}
// Passe les en-têtes JSON
header("Vary: Accept");

if (isset($_SERVER["HTTP_ACCEPT"]) &&
		(strpos($_SERVER["HTTP_ACCEPT"], "application/json") !== false)) {
	header("Content-type: application/json");

} else {
	header("Content-type: text/plain");
}

echo json_encode($results);
?>