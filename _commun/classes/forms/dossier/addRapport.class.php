<?php
/**
 * @name addRapport.class.php Formulaire de création d'un rapport d'entretien
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Dossier
 * @version 1.0
**/
namespace arcec;

class addRapport extends \wp\formManager\admin{
	
	/**
	 * Identifiant du dossier de référence
	 * @var int
	 */
	private $dossierId;

	/**
	 * Onglet cible du retour vers le suivi des dossiers
	 * @var string
	 */
	private $targetTab;
	
	/**
	 * Instancie un nouvel objet de création de dossier
	**/
	public function __construct(){
		$this->module = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
		
		$this->setId($this->module)
		->setName($this->module);
		
		$this->setCss("form-inline");
		$this->setCss("container-fluid");
		
		$this->setTemplateName("dossier/adminHeader.tpl");
		
		$this->mapper = new \arcec\Mapper\rapportMapper();

		\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Nouveau rapport d'entretien");
		
		$this->dossierId = !is_null(\wp\Helpers\urlHelper::context("id")) ? \wp\Helpers\urlHelper::context("id") : \wp\Helpers\httpQueryHelper::get($this->mapper->getTableName() .  "_dossier_id");
		
		$this->setDossierHeader();
		
		$this->targetTab = "rapports";
		
		$this->set();
		
		if(!$this->isValidate()){
			$this->setAction(array("com" => $this->module,"context"=>\wp\Helpers\urlHelper::toContext()));
		} else {
			$this->process();
		}
	}

	private function setDossierHeader(){
		$this->dossier = new \arcec\dossierHeader($this->dossierId);
		$this->dossier->setTemplateName("completeHeader");
	}
	
	public function getDossier(){
		return $this->dossier;
	}

	/**
	 * Surcharge de la méthode pour retourner vers le formulaire de mise à jour des dossiers
	 * @see \wp\formManager\admin::getCancelBtn()
	 */
	public function getCancelBtn(){
		$button = new \wp\formManager\Fields\linkButton();
		$button->setId("btnCancel")
		->setTitle("Gestion des dossiers")
		->addAttribut("role","button")
		->setValue("./index.php?com=suiviDossier&context=UPDATE&id=" . $this->dossierId . "&tab=" . $this->targetTab)
		->setCss("btn")
		->setCss("btn-default")
		->setLabel("Retour")
		;
		return $button;
	}
	
	protected function set(){

		// Crée le champ caché pour le stockage de la clé primaire
		$field = new \wp\formManager\Fields\hidden();
		$field->setId("frmDossierParent")
		->setName($this->mapper->getTableName() .  ".dossier_id")
		->setValue($this->dossierId);
		$this->addToFieldset($field);
		
		$field = new \wp\formManager\Fields\hourMinute();
		$field->setId("frmDureeEntretien")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "duree")
		->setLabel("Durée")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setSeparator(":")
		->setRIAScript()
		->setValue("01:00");
			
		$this->addToFieldset($field);
			
		$this->clientRIA .= $field->getRIAScript();

		// Mapping sur paramBase avec code paramètre ACU
		$mapper = new \arcec\Mapper\paramACUMapper();
		
		if($mapper->count() > 0){
			$field = new \wp\formManager\Fields\popup();
		
			$field->setId("frmParamACU")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "acu")
			->setLabel("Lieu d'accueil")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-6")
			->isDisabled($this->globalDisabled)
			->isRequired()
			->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
			->setValue($this->mapper->getObject()->acu)
			;
			$this->addToFieldset($field);
		}
		
		// Mapping sur paramBase avec code paramètre CNS
		$mapper = new \arcec\Mapper\paramCNSMapper();
		
		if($mapper->count() > 0){
			// Liste des conseillers => paramètres CNS
			$field = new \wp\formManager\Fields\popup();
			
			$field->setId("frmParamCNS")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "cns")
			->setLabel("Conseiller lors de l'entretien")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->setCss("form-control")
			->setGroupCss("col-sm-12")
			->isDisabled($this->globalDisabled)
			->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
			->setValue($this->mapper->getObject()->cns)
			;
			
			$this->addToFieldset($field);
		}

		$field = new \wp\formManager\Fields\datePicker();
		$field->setId("frmDateEntretien")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "date")
		->setLabel("Date de l'entretien")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->isRequired()
		->setCss("form-control")
		->setGroupCss("col-sm-6")
		->setTriggerId("entretien")
		->setRIAScript()
		->setValue(\wp\Helpers\dateHelper::today("d/m/Y"));
			
		$this->addToFieldset($field);
			
		$this->clientRIA .= $field->getRIAScript();
		
		// Ajouter champ statique d'affichage de la phase
		
		// Contenu
		$field = new \wp\formManager\Fields\textarea();
		$field->setId("frmContenu")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "contenu")
		->setLabel("Rapport")
		->setCss("control-label",true)
		->setCss("col-sm-12",true)
		->setCss("form-control")
		->setGroupCss("col-sm-12")
		->isRequired();
		
		$this->addToFieldset($field);

		// Y-a-t-il un email défini pour un pescripteur
		if(\wp\Helpers\urlHelper::context() == "INSERT" || \wp\Helpers\urlHelper::context() == "add"){

			if($this->dossier->get("porteurprs") != 0){
				
				if($this->dossier->get("porteuremailpresc") != ""){
					
					// Ajouter la boîte à cocher pour l'envoi de l'email de rapport uniquement si création
					$field = new \wp\formManager\Fields\checkbox();
					$field->setId("frmDoMail")
					->setName("doMail")
					->setLabel("Envoyer le rapport à")
					->setCss("control-label",true)
					->setCss("col-sm-6",true)
					->setGroupCss("col-sm-6")
					->isChecked(true);
						
					$this->addToFieldset($field);
		
					// Champ statique pour l'affichage de l'e-mail du prescripteur
					$field = new \wp\formManager\Fields\staticText();
					$field->setId("frmShowedEmail")
					->setName("showedEmail")
					->setGroupCss("col-sm-6")
						
					->setValue($this->dossier->get(porteuremailpresc))
					;
					$this->addToFieldset($field);
						
					// Champ caché pour la récupération de l'e-mail dans le traitement
					$field = new \wp\formManager\Fields\hidden();
					$field->setId("frmToEmail")
					->setName("toEmail")
					->setValue($this->dossier->get("porteurprs"))
					;
					$this->addToFieldset($field);
						
				}
			}
		}
		
		// Ajoute le plug-in JS pour la gestion des dates
		$js = new \wp\htmlManager\js();
		$js->addPlugin("jquery.plugin",true);
		$js->addPlugin("jquery.datepick",true);
		$js->addPlugin("jquery.datepick-fr");
		$js->addPlugin("jquery.maskedinput",true);
		
		\wp\Tpl\templateEngine::getEngine()->addContent("js",$js);
		
		// Ajoute le script d'ouverture du datepicker
		$this->toControls();
		
		// Ajoute les CSS
		$css = new \wp\htmlManager\css();
		$css->addSheet("jquery.datepick");
		
		\wp\Tpl\templateEngine::getEngine()->addContent("css",$css);
	}
	
	/**
	 * Traite le formulaire
	 * @see \wp\formManager\admin::process()
	 * @todo Créer la méthode pour définir l'URL d'erreur (rester sur place et afficher le message)
	 */
	protected function process(){
	
		$this->before();
	
		if($this->tableId = parent::process()){
			$this->after();
			
			// Récupère l'identifiant
			$this->dossierId = \wp\Helpers\httpQueryHelper::get($this->mapper->getTableName() .  "_dossier_id");
			
			// Retourne au suivi des dossiers
			$locationParams = array(
				"com" => "suiviDossier",
				"context" => "UPDATE",
				"id" => $this->dossierId,
				"tab" => $this->targetTab
			);
			$location = \wp\Helpers\urlHelper::setAction($locationParams);
			#die("Redirection vers : " . $location);
			header("Location:" . $location);
			return;
		}
	
		$this->sucess = false;
		$this->failedMsg = "Une erreur est survenue lors de l'enregistrement";
		header("Location:" . \wp\Helpers\urlHelper::toURL($this->module));
	}
	
	protected function before(){
		switch(\wp\Helpers\urlHelper::context()){
			case "add":
				return $this->beforeInsert();
				break;
					
			case "upd":
				return $this->beforeUpdate();
				break;
					
			case "del":
				return $this->beforeDelete();
				break;
		}
	
		return;
	}
	
	protected function after(){
		switch(\wp\Helpers\urlHelper::context()){
			case "add":
				return $this->afterInsert();
				break;
					
			case "upd":
				return $this->afterUpdate();
				break;
					
			case "del":
				return $this->afterDelete();
				break;
		}
	
		return;
	}
	
	protected function beforeInsert(){
	}
	
	protected function beforeUpdate(){}
	
	protected function beforeDelete(){}
	
	protected function afterInsert(){
		// Vérifier le statut de l'envoi du mail...
		$field = $this->getField("frmDoMail");
		
		if(!is_null($field)){
			if($field->getPostedData()){
				// Remonte les informations relatives au destinataire
				$mailer = new \wp\mailManager\mailer(true);
				$mailer->Host = "ssl0.ovh.net";
				$mailer->Port = 587;
				$mailer->SMTPAuth = true;
				$mailer->Username = "association@arcec.net";                 // SMTP username
				$mailer->Password = "M@1l4A33o";                           // SMTP password
				$mailer->SMTPSecure = "tls";				
				$mailer->sender("arcec@arcec.net","ARCEC");
				$mailer->addRecipient($this->dossier->get("porteuremailpresc"),$this->dossier->get("porteurnompresc"));
				$mailer->setTemplateName("dossier/rapport");
				$mailer->setSubject("Transmission de rapport d'entretien ARCEC");
				$mailer->mergeTemplate(
					array(
						"porteur" => $this->toPorteur(),
						"date" => $_POST["arc_rapport_rapport_date"],
						"message" => nl2br($_POST["arc_rapport_rapport_contenu"])
					)
				);
				if(!$mailer->process()){
					die("Le mail n'a pas pu être envoyé à " . $this->dossier->get("porteuremailpresc") . " : " . $mailer->ErrorInfo);
					return false;
				}
				return true;
			}
		}
		return false; 
	}
	
	protected function afterUpdate(){}
	
	protected function afterDelete(){}
	
	/**
	 * Retourne les informations relatives au porteur
	 * @return string
	 */
	private function toPorteur(){
		$porteur = $this->dossier->get("porteursexe") == "M" ? "Monsieur " : "Madame ";
		$porteur .= $this->dossier->get("prenomporteur") . " " . $this->dossier->get("nomporteur");
		
		return $porteur;
	}
}
?>