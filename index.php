<?php
/**
 * @name index.php
 * @category Dispatcher
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @version 1.0
 **/

use wp\dbManager\dbConnect as db;
use wp\Tpl\templateEngine as tpl;

if(file_exists(dirname(__FILE__) . "/../appLoader.class.php"))
	require_once(dirname(__FILE__) . "/../appLoader.class.php");
else
	die("Impossible de charger la classe : " . dirname(__FILE__) . "/../appLoader.class.php");


$app = new apps\appLoader(dirname(__FILE__));
$app->appConfig->setAppType("intranet");

// Charge le menu utilisateur en fonction du contexte
$user = $app->getUser();
tpl::getEngine()->setVar("userForm",$user->process());
tpl::getEngine()->setVar("menu",new \wp\Menus\menu());

// Charge le module par défaut à traiter le cas échéant
if(is_null(tpl::getEngine()->addModule(\wp\Helpers\urlHelper::getCom($app->getConfigKey("namespace"))))){
	if(\wp\Helpers\sessionHelper::getUserSession()->isLoggedIn()){
		tpl::getEngine()->addModule(array("region" => "_main", "object" => "\arcec\locateDossier"));
	}
}

// Charger le calendrier barre latérale droite
if($user->isLoggedIn()){
	tpl::getEngine()->addModule(array("region" => "_raside", "object" => "\arcec\Agenda\agenda", "params" => array("day"), "exclude" => array("planningViewer")));
}

tpl::getEngine()->render();
?>