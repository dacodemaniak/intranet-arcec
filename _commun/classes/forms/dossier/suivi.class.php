<?php
/**
 * @name suivi.class.php Service d'affichage et de mise à jour du suivi des dossiers
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec
 * @version 1.0
**/
namespace arcec;

class suivi implements \wp\Tpl\template{
	
	/**
	 * Modèle à utiliser 
	 * @var string
	**/
	private $templateName;
	
	/**
	 * Mapping sur le dossier en cours d'édition
	 * @var object
	 */
	private $dossier;
	
	/**
	 * Identifiant du dossier courant
	 * @var int
	**/
	private $dossierId;
	
	/**
	 * Identifiant du programme
	 * @var int
	 */
	private $programmeId;
	
	/**
	 * Mapping de données sur la table de suivi
	 * @var object
	**/
	private $mapper;
	
	
	/**
	 * Nombre de blocs par lignes
	 * @var int Nombre de blocs, toujours inférieurs à 12 pour rester dans la grille et diviseur de 12
	**/
	private $blocsPerLine;
	
	/**
	 * Structure de stockage des classes pour le bloc
	 * @var array
	 */
	private $blocCSSClass;
	
	/**
	 * Objet pour l'affichage de la liste des actions
	 * @var object Objet de type popup
	 */
	private $actionList;
	
	/**
	 * Liste des conseillers
	 * @var object
	 */
	private $conseillerList;
	
	/**
	 * Instancie un nouvel objet de suivi
	 * @param int $dossier Mapping du dossier courant
	**/
	public function __construct($dossier){
		
		$this->dossier = $dossier;
		
		$this->dossierId = $this->dossier->id;
		$this->programmeId = $this->dossier->porteurprg;
		
		$this->mapper = new \arcec\Mapper\suiviMapper();
		$this->mapper->searchBy("dossier_id",$this->dossierId);
		$this->mapper->set("\\arcec\\Mapper\\");
		
		$this->setTemplateName("dossier/blocs");
		
		$this->blocsPerLine = 2;
		$this->blocCSSClass[] = "col-sm-6";
		
		// Transmets l'objet au modèle
		\wp\Tpl\templateEngine::getEngine()->setVar("suivi",$this);
		
		$this->actionList = null;
		
	}
	
	/**
	 * Définit ou retourne le nombre de blocs par ligne
	 * @param int $nb
	 * @return \arcec\suivi|number
	**/
	public function blocsPerLine($nb=null){
		if(!is_null($nb)){
			if($nb > 12){
				$nb = 12;
			} else {
				if($nb <= 0){
					$this->blocsPerLine = 1;
				}
			}
			
			// Le nombre définit est-il un diviseur de 12
			if(12 % $nb == 0){
				$this->blocsPerLine = $nb;
			} else {
				$this->blocsPerLine = 2;
			}
			
			$this->blocCSSClass = array_reverse($this->blocCSSClass);
			array_pop($this->blocCSSClass);
			$this->blocCSSClass[] = "col-sm-" . 12/$this->blocsPerLine;
			$this->blocCSSClass = array_reverse($this->blocCSSClass);
			
			return $this;
		}
		
		
		return $this->blocsPerLine;
	}
	
	public function getCollection(){
		return $this->mapper->getCollection();
	}
	
	/**
	 * Définit ou retourne les classes CSS associées 
	 * @param unknown_type $class
	 * @return \arcec\suivi|string
	 */
	public function blocCSS($class=null){
		if(!is_null($class)){
			if(!in_array($class,$this->blocCSSClass)){
				$this->blocCSSClass[] = $class;
			}
			return $this;
		}

		return implode(" ", $this->blocCSSClass);
	}
	
	
	/**
	 * Récupère une valeur à partir du mapper d'origine
	 * @param string $col Nom de la colonne à retourner
	 * @param string $from Clé primaire du mapper parent
	 * @param object $activeRecord Enregistrement courant
	 */
	public function get($col,$from=null,$activeRecord=null){
		if(!is_null($from)){
			$parentMapper = $this->mapper->getSchemeDetail($from,"mapper");
			$parentMapper->clearSearch();
			$parentMapper->setId($activeRecord->{$from});
			$parentMapper->set($parentMapper->getNameSpace());
			return $parentMapper->getObject()->{$col};
		}
	}
	
	/**
	 * Définit ou retourne la liste des actions
	 * @param object $activeRecord Etape courante
	 * @return object
	**/
	public function actionList($activeRecord=null){
		if(!is_null($activeRecord)){
			$actionMapper = $this->mapper->getSchemeDetail("action_id","mapper");
			$this->actionList = new \wp\formManager\Fields\popup();
			$this->actionList->setTemplateName("popup");
			$this->actionList->setMapping($actionMapper, array("value" => "id", "content"=>array("message")))
				->setId("actionEtape" . $activeRecord->etapeprojet_id)
				->setName("actionEtape" . $activeRecord->etapeprojet_id)
				->setLabel("Action")
				->setCss("control-label",true)
				->setCss("col-sm-12",true)
				->setCss("form-control")
				->setGroupCss("col-sm-12")
				->setValue($activeRecord->action_id);
		}
		
		return $this->actionList;
	}

	/**
	 * Définit ou retourne la liste des conseillers
	 * @param object $activeRecord Etape courante
	 * @return object
	 **/
	public function conseillerList($activeRecord=null){
		if(!is_null($activeRecord)){
			$actionMapper = $this->mapper->getSchemeDetail("conseiller_id","mapper");
			$this->conseillerList = new \wp\formManager\Fields\popup();
			$this->conseillerList->setTemplateName("popup");
			$this->conseillerList->setMapping($actionMapper, array("value" => "id", "content"=>array("libellecourt","libellelong")))
			->setId("conseiller" . $activeRecord->etapeprojet_id)
			->setName("conseiller" . $activeRecord->conseiller_id)
			->setLabel("Conseiller")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-12")
			->setValue($activeRecord->conseiller_id);
		}
	
		return $this->conseillerList;
	}
	
	public function setClientRIA(){
		$jQuery = "
			$(\".phase select\").on(\"change\",function(){
					var parentDiv = $(this).parent().parent().parent();
					var etape = parentDiv.data(\"rel\");
					var dossier = " . $this->dossierId  . ";
					var programme = " . $this->programmeId . ";
					//console.log(\"Changement dans la liste : \" + $(this).attr(\"id\") + \" dans la div reliée à l'étape \" + parentDiv.data(\"rel\"));
					var action = $(\"#actionEtape\"+etape + \" option:selected\").val();
					var conseiller = $(\"#conseiller\"+etape + \" option:selected\").val();
					console.log(\"Mise à jour avec action : \" + action + \" conseiller : \" + conseiller + \" pour le dossier : " . $this->dossierId . "\");
					$.ajax({
							url:\"" . \wp\framework::getFramework()->getAjaxDispatcher() . "\",
							type:\"POST\",
							data:\"object=updatePhase&dossier=\" + dossier + \"&etape=\" + etape + \"&programme=\" + programme + \"&action=\" + action + \"&conseiller=\" + conseiller,
							dataType:\"json\"
						}
					).success(function(data){
						}
					).error(function(e){
						}
					);
				}
			);
		";
		return $jQuery;
			
	}
	
	/**
	 * Définit le modèle à utiliser pour l'affichage des contenus
	 * @see \wp\Tpl\template::setTemplateName()
	 */
	public function setTemplateName($templateName){
		$this->templateName = $templateName . ".tpl";
		return $this;	
	}
	
	public function getTemplateName(){
		return $this->templateName;
	}
	 
}
?>