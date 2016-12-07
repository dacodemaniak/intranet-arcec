<?php
/**
 * @name listeRapport.class.php Services d'affichage de la liste des rapports d'entretien pour un dossier donné
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Dossier
 * @version 1.0
 **/
namespace arcec;

class listeRapport extends \arcec\Dossier\dossier {
	
	/**
	 * Mapping sur la table prefix_rapport
	 * @var object
	**/
	private $rapportMapper;
	
	/**
	 * Définit le module de mise à jour pour l'interface concernée
	 * @var string
	**/
	private $updateModule;
	
	public function __construct(){
		$this->module = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
		
		$this->namespace = __NAMESPACE__;
		
		$this->setTemplateName("./sorterTable.tpl");

		$locationParams = array(
				"com" => "setRapport",
				"context" => "UPDATE"
		);
		$this->updateModule = \wp\Helpers\urlHelper::setAction($locationParams);
		
		$this->setDossier();
		
		$this->filter();
		$this->dossierMapper->set($this->dossierMapper->getNameSpace());
		 
		$this->rapportMapper = new \arcec\Mapper\rapportMapper();
		
		$this->rapportMapper->searchBy("dossier_id",$this->dossierMapper->getObject()->id);
		
		\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Rapports d'entretien");
		
		$index = new \wp\formManager\tableIndex($this->rapportMapper);
		
		$this->addResource();
		
		//$index->setTemplateName("tableIndex.tpl","./");
		$index->setTemplateName("sorterTable.tpl");
		
		$index->setHeaders(array(
				"date" => "Date de création",
				"acu" => array("header" => "Lieu","column"=>"libellelong","mapper"=>new \arcec\Mapper\paramACUMapper()),
				"cns" => array("header" => "Conseiller","column"=>"libellelong","mapper"=>new \arcec\Mapper\paramCNSMapper()),
				"duree" => "Durée",
			)
		)
		->setContext("liste")
		->addPager(20)
		->addFilter("type",20)
		->setPlugin("tablesorter")
		;
		
		/*
		// Ajoute le script pour le chargement du formulaire de mise à jour
		$target = \wp\Helpers\urlHelper::setAction(array("com"=>"suiviDossier"));
		$target .= "&context=UPDATE&id=";
		$this->clientRIA .= "
			$(\".tablesorter tbody tr\").on(\"click\",function(){
				var dossierId = $(this).data(\"rel\");
				document.location.replace(\"" . $target . "\"+ dossierId) 
			});
		";
		*/
		$this->toControls();
		
		\wp\Tpl\templateEngine::getEngine()->setVar("liste", $index);
		\wp\Tpl\templateEngine::getEngine()->setVar("instance", $this);
	}

	/**
	 * Crée et retourne le bouton d'ajout dans l'index d'administration
	 */
	public function getAddBtn(){
		$button = new \wp\formManager\Fields\linkButton();
		$button->setId("btnAdd")
		->setTitle("Ajouter")
		->addAttribut("role","button")
		->setValue("./index.php?com=addRapport&context=INSERT&id=".$this->dossierMapper->getObject()->id)
		->setCss("btn")
		->setCss("btn-success")
		->setLabel("Nouveau Rapport")
		;
		return $button;
	}
	
	
	/**
	 * Retourne le module lié à l'action à réaliser
	 * @return string
	**/
	public function getUpdateModule(){
		return $this->updateModule;
	}
	
	/**
	 * Ajoute les ressources nécessaires pour la gestion du tableau en fonction du plugin utilisé
	 **/
	private function addResource(){
	
			$js = new \wp\htmlManager\js();
			$js->addPlugin("jquery.tablesorter");
			$js->addPlugin("jquery.tablesorter.widgets",true);
				
			$css = new \wp\htmlManager\css();
			$css->addSheet("theme.bootstrap",true);
			//$css->addSheet("theme.grey",true);
				
			if($this->addPager){
				$js->addPlugin("jquery.tablesorter.pager");
				$js->addPlugin("widget-pager",true);
				$css->addSheet("jquery.tablesorter.pager",true);
			}
				
			if(sizeof($this->filters)){
				$js->addPlugin("widget-filter",true);
			}
				
			// Crée le script pour la gestion du tableau
			$jQuery = "
				//var adminTable = $(\"#" . $this->id . "\").tablesorter({
				$(\"#" . $this->id . "\").tablesorter({
			";
				
			if(sizeof($this->filters)){
				$jQuery .= "
					widgets: [\"filter\"],
					    widgetOptions : {
					      filter_defaultFilter: { 1 : '~{query}' },
					      // include column filters
					      filter_columnFilters: true,
					      filter_placeholder: { search : 'Chercher...' },
					      filter_saveFilters : true,
					      filter_reset: '.reset'
					    },
				";
			}
				
			// Ajoute les colonnes de tri, le cas échéant
			if(sizeof($this->sorters)){
				$jQuery .= "sortList:[";
				for($i=0;$i<sizeof($this->sorters);$i++){
					$jQuery .= "[" . $this->sorters[0] ."," . $this->sorters[1] . "],";
				}
				$jQuery = substr($jQuery,0,strlen($jQuery)-1);
				$jQuery .= "],\n";
			} else {
				// Tri systématique sur la première colonne
				$jQuery .= "sortList:[[1,0]],\n";
			}
				
			// En-têtes...
			$jQuery .= "headers:{\n";
				
			if($this->context == "admin"){
				$jQuery .= "\t0:{sorter:false},\n";
	
				if(sizeof($this->sorters)){
					$indice = 1;
						
					for($i=0;$i<sizeof($this->getHeaders("keys"));$i++){
						foreach($this->sorters as $sorter){
	
						}
					}
				} else {
					for($i=0;$i<sizeof($this->getHeaders("keys"));$i++){
						if($i >= 1){
							$indice = $i+1;
							$jQuery .= $indice . ":{sorter:false},\n";
						}
					}
						
						
				}
			$jQuery = substr($jQuery,0,strlen($jQuery)-2);
		}
				
		$jQuery .= "},\n";
				
		$jQuery = substr($jQuery,0,strlen($jQuery)-1);
				
			// Gère les filtres
		if(sizeof($this->filters) && $this->filterThresold > $this->nbRows){
			$jQuery .= "
				    widgetOptions : {
				      // filter_anyMatch replaced! Instead use the filter_external option
				      // Set to use a jQuery selector (or jQuery object) pointing to the
				      // external filter (column specific or any match)
				      filter_external : \".search\",
				      // add a default type search to the first name column
				      filter_defaultFilter: { 1 : \"~{query}\" },
				      // include column filters
				      filter_columnFilters: true,
				      filter_placeholder: { search : \"Search...\" },
				      filter_saveFilters : true,
				      filter_reset: \".reset\"
				    }
				";
		}
				
		$jQuery .=	"\n})";
	
		if($this->addPager && $this->pagerThresold < $this->nbRows){
			$jQuery .= "
					.tablesorterPager({
			
					    // target the pager markup - see the HTML block below
					    container: $(\".pager\"),
			
					    // use this url format \"http:/mydatabase.com?page={page}&size={size}\"
					    ajaxUrl: null,
			
					    // process ajax so that the data object is returned along with the
					    // total number of rows; example:
					    // {
					    //   \"data\" : [{ \"ID\": 1, \"Name\": \"Foo\", \"Last\": \"Bar\" }],
					    //   \"total_rows\" : 100
					    // }
					    ajaxProcessing: function(ajax) {
					        if (ajax && ajax.hasOwnProperty(\"data\")) {
					            // return [ \"data\", \"total_rows\" ];
					            return [ajax.data, ajax.total_rows];
					        }
					    },
			
					    // output string - default is '{page}/{totalPages}';
					    // possible variables:
					    // {page}, {totalPages}, {startRow}, {endRow} and {totalRows}
					    output: \"{startRow} to {endRow} ({totalRows})\",
			
					    // apply disabled classname to the pager arrows when the rows at
					    // either extreme is visible - default is true
					    updateArrows: true,
			
					    // starting page of the pager (zero based index)
					    page: 0,
			
					    // Number of visible rows - default is 10
					    size: " . $this->pagerThresold . ",
			
					    // if true, the table will remain the same height no matter how many
					    // records are displayed. The space is made up by an empty
					    // table row set to a height to compensate; default is false
					    fixedHeight: false,
			
					    // remove rows from the table to speed up the sort of large tables.
					    // setting this to false, only hides the non-visible rows; needed
					    // if you plan to add/remove rows with the pager enabled.
					    removeRows: false,
			
					    // css class names of pager arrows
					    // next page arrow
					    cssNext: '.next',
					    // previous page arrow
					    cssPrev: '.prev',
					    // go to first page arrow
					    cssFirst: '.first',
					    // go to last page arrow
					    cssLast: '.last',
					    // select dropdown to allow choosing a page
					    cssGoto: '.gotoPage',
					    // location of where the \"output\" is displayed
					    cssPageDisplay: '.pagedisplay',
					    // dropdown that sets the \"size\" option
					    cssPageSize: '.pagesize',
					    // class added to arrows when at the extremes
					    // (i.e. prev/first arrows are \"disabled\" when on the first page)
					    // Note there is no period "." in front of this class name
					    cssDisabled: 'disabled'
			
				});
			";
		}
				
		$jQuery .= "// Fin de tablesorter\n";
				
		$js->addScript("function", $jQuery);
				
		\wp\Tpl\templateEngine::getEngine()->addContent("js",$js);
		\wp\Tpl\templateEngine::getEngine()->addContent("css",$css);

	
		return;
	}
}
?>