<?php
/**
 * @name annuaire.class.php Service de gestion de l'annuaire et du carnet d'adresses
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec
 * @version 1.0
**/

namespace arcec;

class annuaire {
	
	/**
	 * Instance courante du dossier porteur
	 * @var object
	 */
	private $dossier;
	
	/**
	 * Mapper de données pour la récupération et mise à jour des infos
	 * @var array
	 */
	private $dataMapper;
	
	public function __construct(){
		$this->dataMapper = array(
			"PRS" => array(
				"id" => "porteurprs",
				"mail" => "porteuremailpresc",
				"libelle" => "porteurnompresc"
			)
		);
		
	}
	
	/**
	 * Définit le dossier courant
	 * @param object $dossier
	 */
	public function setDossier($dossier){
		$this->dossier = $dossier;
	}
	
	/**
	 * Gère l'ajout d'une entrée dans l'annuaire à partir d'un mapper spécifique
	 * @param unknown_type $nodeCode
	 */
	public function addFromParam($nodeCode){
		$datas					= "";
		$dataMapper = $this->dataMapper[$nodeCode];
		
		foreach($dataMapper as $key => $column){
			if($key != "id"){
				$datas .= $this->dossier->{$column};
			}
		}	
		
		
		if(strlen($datas)){
			// Récupère l'ID du noeud de l'annuaire correspondant aux prescripteurs
			$factory = new \wp\Patterns\factory("\arcec\Mapper\annuaire" . $nodeCode . "Mapper");
			$node = $factory->addInstance();
			
			// Récupère le code du paramètre courant
			$paramId = $this->dataMapper[$nodeCode]["id"];
			$factory = new \wp\Patterns\factory("arcec\Mapper\param" . $nodeCode . "Mapper");
			$param = $factory->addInstance();
			
			$param->setId($this->dossier->{$paramId});
			$param->set($param->getNameSpace());
			
			// Vérifie l'existence du noeud dans la taxonomie de l'annuaire
			$arbo = new \arcec\Mapper\arboannuaireMapper();
			$arbo->searchBy("codification",$param->getObject()->libellecourt);
			$arbo->set($arbo->getNameSpace());
			
			if($arbo->getNbRows() == 0){
				// Créer le noeud sous le noeud principal
				$arbo->titre = $param->getObject()->libellelong;
				$arbo->codification = $param->getObject()->libellecourt;
				$arbo->interne = 0;
				$arbo->ordre = 1;
				$arbo->parent = $node->getId();
				
				$nodeId = $arbo->save();
			} else {
				$nodeId = $arbo->getObject()->id;
			}
			
			// Vérifier que dans cette arborescence l'entrée e-mail n'existe pas déjà
			$annuaire = new \arcec\Mapper\annuaireMapper();
			$mailColumn = $this->dataMapper[$nodeCode]["mail"];
			
			$annuaire->searchBy("email",$this->dossier->{$mailColumn});
			$annuaire->set($annuaire->getNameSpace());
			
			if($annuaire->getNbRows() == 0){
				// Crée la ligne dans l'annuaire
				$annuaire->nom = $this->dossier->{$this->dataMapper[$nodeCode]["libelle"]};
				$annuaire->email = $this->dossier->{$this->dataMapper[$nodeCode]["mail"]};
				$annuaire->arboannuaire_id = $nodeId;
				
				// Génère les autres données à inclure dans la fiche
				$jsonArray = array(
					array("import" => "Importation du", "date" => \wp\Helpers\dateHelper::today("d-m-Y")),
					array("dossier" => "Du dossier porteur", "id" => $this->dossier->id)
				);
				
				$annuaire->autreinfos = json_encode($jsonArray);
				
				$annuaire->save();
			}
			
		}
	}
	
}
?>