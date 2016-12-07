<?php
/**
 * @name locateDossier.class.php Services de recherche des dossiers
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Dossier
 * @version 1.0.1
 * 	Remplacement du libellé Phase du dossier par Etat du dossier
 **/
namespace arcec;

class locateDossier extends \arcec\Dossier\dossier {
	
	public function __construct(){
		$this->module = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
		
		$this->namespace = __NAMESPACE__;
		
		$this->setId("formLocate");
		$this->setName("formLocate");
		
		$this->setTemplate();
		
		$this->setDossier();
		
		$this->setCss("form-horizontal");
		
		$this->set();
		
		\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Chercher un dossier");
		\wp\Tpl\templateEngine::getEngine()->setVar("form",$this);
		
		if(!$this->isValidate()){
			$this->setAction(array("com" => "listeDossier"));
		} else {
			$this->process();
		}
	}
	
	public function set(){
		$this->dossierMapper->set("\\arcec\\Mapper\\");
		$disabledStatut = $this->dossierMapper->getNbRows() == 0 ? true : false;
		
		// Liste des phases => paramètres ETD
		$field = new \wp\formManager\Fields\popup();
		
		// Mapping sur paramBase avec code paramètre ETD
		$mapper = new \arcec\Mapper\paramETDMapper();
		
		$field->setId("frmParamETD")
			->setName($this->dossierMapper->getTableName() . "." . $this->dossierMapper->getColumnPrefix() . "etd")
			->setLabel("Phase du projet")
			->setCss("control-label",true)
			->setCss("col-sm-5",true)
			->setCss("form-control")
			->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
		;
		
		$this->addToFieldset($field);
				
		// Liste des états => paramètres EDO
		$field = new \wp\formManager\Fields\popup();
		
		// Mapping sur paramBase avec code paramètre EDO
		$mapper = new \arcec\Mapper\paramEDOMapper();
		
		$field->setId("frmParamEDO")
		->setName($this->dossierMapper->getTableName() . "." . $this->dossierMapper->getColumnPrefix() . "edo")
		->setLabel("Etat du dossier")
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->setCss("form-control")
		->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
		;
		
		$this->addToFieldset($field);
				
		// Liste des conseillers => paramètres CNS
		$field = new \wp\formManager\Fields\popup();
		
		// Mapping sur paramBase avec code paramètre CNS
		$mapper = new \arcec\Mapper\paramCNSMapper();
		
		$field->setId("frmParamCNS")
		->setName($this->dossierMapper->getTableName() . "." . $this->dossierMapper->getColumnPrefix() . "porteurcnscoord")
		->setLabel("Conseiller")
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->setCss("form-control")
		->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
		;
		
		$this->addToFieldset($field);
			
		// Porteur de projet (nom) : autocomplete field
		$field = new \wp\formManager\Fields\autocomplete();
		$field->setId("frmIdDossier")
		->setName($this->dossierMapper->getTableName() . "." . $this->dossierMapper->getColumnPrefix() . "id")
		->setSupportFieldName("frmNomPorteur")
		->setSupportFieldId("frmNomPorteur")
		->setLabel("Nom du porteur")
		->setCss("control-label",true)
		->setCss("col-sm-5",true)
		->isDisabled($disabledStatut)
		->setMappingObject($this->dossierMapper)
		->addCol("nomporteur")
		->addCol("prenomporteur")
		->setCss("form-control")
		->setRIAScript();
		
		$this->addToFieldset($field);		

		// Ajoute le code de contrôle du champ dans la pile des scripts
		$this->clientRIA .= $field->getRIAScript();
		
		
		// Fieldset pour les boutons de sélection
		$this->addFieldset("actionbar");
		
		// Fieldset pour les boutons de sélection
		$field = new \wp\formManager\Fields\button();
		
		$field->setId("btnListe")
		->setType("submit")
		->setName("btnListe")
		->setLabel("Liste des dossiers (Tous)")
		->setCss("btn")
		->setCss("btn-primary");
		
		$this->addToFieldset($field,"actionbar");
				
		// Bouton de création d'un dossier
		$field = new \wp\formManager\Fields\linkButton();
		
		$field->setId("btnCreate")
		->setName("btnCreate")
		->setLabel("Nouveau dossier")
		->setCss("btn")
		->setCss("btn-success")
		->setValue(\wp\Helpers\urlHelper::setAction(array("com"=>"setDossier","context"=>"INSERT")));
		
		$this->addToFieldset($field,"actionbar");		
		
		// Ajoute le code d'affichage du nombre de dossier récupérés
		$this->clientRIA .= "
			$(\"#frmIdDossier\").on(\"change\",function(){
				if($(this).val() != 0 && $(this).val() != \"\"){
					$(\"#btnListe\").html(\"Voir le dossier\");
				}
			});
			
			// Gestion des listes de paramètres
			$(\"select[id^=frmParam]\").on(\"change\",function(){
				var parents = new String; // Stocke les données des tables paramètres
				var selection = 0;
				// Parcours les selects pour récupérer les valeurs sélectionnées
				$(\"#" . $this->getId() . " select\").each(function(){
					var fieldId = $(this).attr(\"id\");
					var currentValue = $(\"select[id=\" + fieldId + \"] option:selected\").val();
					var param = fieldId.substr(8,3);
					parents += param + \":\" + currentValue + \"|\";
					selection += parseInt(currentValue);
					
				});
				console.log(\"Paramètres : \" + parents + \" => \" + selection);
				if(selection != 0){
					$.ajax({
						url:\"" . \wp\framework::getFramework()->getAjaxDispatcher() . "\",
						type:\"POST\",
						data:\"object=dossierCounter&namespace=" . str_replace("\\","_",$this->dossierMapper->getNameSpace()) . "&mapper=" . $this->dossierMapper->getClassName() . "&param=\" + parents,
						dataType:\"json\",
						success:function(data,statut){
							// Combien de ligne de résultat
							var rows = data.data;
							if(rows < " . $this->getTotalRows() . "){
								$(\"#btnListe\").html(\"Liste des dossiers (\" + rows + \")\");
							} else {
								$(\"#btnListe\").html(\"Liste des dossiers (Tous)\");
							}
						},
						error:function(resultat,statut,erreur){
							console.log(\"Erreur d'appel Ajax : \" + erreur);
						}
					});	
				}			
			});
		";
		
		
		$this->toControls();
	}
}
?>