<?php
/**
 * @name listeDocument.class.php Services d'affichage de la liste des documents pour un dossier donné
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Dossier
 * @version 1.0
 **/
namespace arcec;

class listeDocument extends \arcec\Dossier\dossier {
	
	/**
	 * Mapping sur la table prefix_rapport
	 * @var object
	**/
	private $documentMapper;
	
	/**
	 * Définit le module de mise à jour pour l'interface concernée
	 * @var string
	**/
	private $updateModule;
	
	/**
	 * Boutons de sélection
	 * @var array
	 */
	private $buttonList;
	
	
	public function __construct(){
		
		$this->module = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
		
		$this->namespace = __NAMESPACE__;
		
		$this->setTemplateName("./docSorterTable.tpl");

		$locationParams = array(
				"com" => "setDocument",
				"context" => "UPDATE"
		);
		$this->updateModule = \wp\Helpers\urlHelper::setAction($locationParams);
		
		$this->setDossier();
		
		$this->filter();
		
		$this->dossierMapper->set($this->dossierMapper->getNameSpace());
		 
		$this->documentMapper = new \arcec\Mapper\docporteurMapper();
		
		$this->documentMapper->searchBy("dossier_id",$this->dossierMapper->getObject()->id);
		
		\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Documents");
		
		$index = new \wp\formManager\tableIndex($this->documentMapper);
		
		$this->addResource();
		
		//$index->setTemplateName("tableIndex.tpl","./");
		$index->setTemplateName("docSorterTable.tpl");
		
		$index->setHeaders(array(
				"nomcalcule" => "Document",
				"arbofichier_id" => array("header" => "Arborescence","taxonomy"=>true,"columns"=>array("titre"),"mapper"=>new \arcec\Mapper\arbofichierMapper()),
			)
		)
		->setContext("liste")
		->addPager(20)
		->addFilter("type",20)
		->setPlugin("tablesorter")
		;
		
		// Ajoute les boutons de sélection
		$this->buttonList[] = $this->setAddBtn();
		$this->buttonList[] = $this->setDelBtn();
		$this->buttonList[] = $this->setDownloadBtn();
		
		// Script pour la gestion des boutons en fonction des boîtes à cocher
		$this->clientRIA .= "
			$(\"#documents td input\").on(\"click\",function(){
				console.log(\"Sélection / Déselection d'un fichier\");
				var enableDocManageButton = false;
				$(\"#documents td input\").each(function(){
					console.log(\"Teste de la boîte : \" + $(this).attr(\"id\"));
						if($(this).is(\":checked\")){
							enableDocManageButton = true;
						}
					}
				);
				if(enableDocManageButton){
					$(\"#btnDelDoc\").removeAttr(\"disabled\");
					$(\"#btnDnlDoc\").removeAttr(\"disabled\");
				} else {
					$(\"#btnDelDoc\").attr(\"disabled\",\"disabled\");
					$(\"#btnDnlDoc\").attr(\"disabled\",\"disabled\");
				}
			});

			// Gestion des téléchargements
			$(\"#btnDnlDoc\").on(\"click\",function(){
					var docToDnl = new String;
				
					$(\"#documents td input\").each(function(){
							if($(this).is(\":checked\")){
								var attrId = $(this).attr(\"id\");
								var id = attrId.substr(3);
								docToDnl += id + \"|\";
							}
						}
					);
					// Lance l'appel Ajax pour la génération de l'archive
					$.ajax(
						{
							url:\"" . \wp\framework::getFramework()->getAjaxDispatcher() . "\",
							type:\"POST\",
							data:\"object=zipMaker&content=\" + docToDnl,
							dataType:\"json\"
						}
					).success(function(data){
							if(data.error == 0){
								console.log(\"Force le téléchargement de : \" + data.zipFile);
							    
								if (!window.ActiveXObject) {
							        var save = document.createElement('a');
							        save.href = data.zipFile;
							        save.target = \"_blank\";
							        save.download = \"archive.zip\" || \"unknown\";
							
							        var evt = new MouseEvent(\"click\", {
							            \"view\": window,
							            \"bubbles\": true,
							            \"cancelable\": false
							        });
							        save.dispatchEvent(evt);
							
							        (window.URL || window.webkitURL).revokeObjectURL(save.href);
							    } else if ( !! window.ActiveXObject && document.execCommand)     {
			        				var _window = window.open(fileURL, '_blank');
			        				_window.document.close();
			        				_window.document.execCommand('SaveAs', true, fileName || fileURL)
			        				_window.close();
			    				}
								
							} else {
								alert(data.error);
							}
						}
					);
				}
			);
		";
		
		$this->toControls();
		
		\wp\Tpl\templateEngine::getEngine()->setVar("docs", $index);
		\wp\Tpl\templateEngine::getEngine()->setVar("listeDocument", $this);
	}

	public function getButton(){
		return $this->buttonList;
	}
	
	/**
	 * Crée et retourne le bouton d'ajout dans l'index d'administration
	 */
	private function setAddBtn(){
		$button = new \wp\formManager\Fields\linkButton();
		$button->setId("btnAddDoc")
		->setTitle("Ajouter")
		->addAttribut("role","button")
		->setValue("./index.php?com=addDocument&context=INSERT&id=".$this->dossierMapper->getObject()->id)
		->setCss("btn")
		->setCss("btn-success")
		->setLabel("Nouveau Document")
		;
		return $button;
	}

	/**
	 * Crée et retourne le bouton de suppression
	 */
	private function setDelBtn(){
		$button = new \wp\formManager\Fields\button();
		$button->setId("btnDelDoc")
		->addAttribut("role","button")
		->setValue("Supprimer")
		->setCss("btn")
		->setCss("btn-danger")
		->isDisabled(true)
		->setLabel("Supprimer")
		;
		return $button;
	}

	/**
	 * Crée et retourne le bouton de téléchargement
	 */
	private function setDownloadBtn(){
		$button = new \wp\formManager\Fields\button();
		$button->setId("btnDnlDoc")
		->addAttribut("role","button")
		->setValue("Télécharger")
		->setCss("btn")
		->setCss("btn-info")
		->isDisabled()
		->setLabel("Télécharger")
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