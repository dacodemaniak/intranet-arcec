<?php
/**
 * @name defineSuivi.class.php : Création des lignes de suivi pour les dossiers
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec
 * @version 1.0
**/
namespace arcec;

class defineSuivi {
	/**
	 * Collection des dossiers à traiter
	 * @var array
	 */
	private $dossiers;
	
	public function __construct(){
		
		$dossiers = new \arcec\Mapper\dossierMapper();
		$dossiers->set($dossiers->getNameSpace());
		
		$defaultAction = new \arcec\Mapper\paramWRKMapper();
		$defaultAction->searchBy("defaut", 1);
		$defaultAction->set($defaultAction->getNameSpace());
		$default = $defaultAction->getObject()->id;
		
		foreach($dossiers->getCollection() as $dossier){
			if($dossier->porteurprg != 0){
				$etapes = new \arcec\Mapper\prgtoetapesMapper();
				$etapes->searchBy("programme_id", $dossier->porteurprg);
				$etapes->set($etapes->getNameSpace());
				if($etapes->getNbRows() > 0){
					$this->dossiers[] = array(
						"id" => $dossier->id,
						"prg" => $dossier->porteurprg,
						"etapeprojet" => $etapes->getCollection(),
						"cco" => $dossier->porteurcnscoord,
						"action" => $default,
						"date" => \wp\Helpers\dateHelper::today()
							
					);
				}
				$etapes->clearSearch();
			}
		}
		$this->process();
	}
	
	public function process(){
		$mapper = new \arcec\Mapper\suiviMapper();
		
		foreach($this->dossiers as $dossier){
			$mapper->dossier_id = $dossier["id"];
			$mapper->programme_id = $dossier["prg"];
			$mapper->conseiller_id = $dossier["cco"];
			$mapper->action_id = $dossier["action"];
			$mapper->date = $dossier["date"];
			foreach ($dossier["etapeprojet"] as $etapeprojet){
				$mapper->etapeprojet_id = $etapeprojet->id;
				$queryString = $mapper->save(null,true);
				echo $queryString . "<br />\n";
			}
		}
	}
}
?>