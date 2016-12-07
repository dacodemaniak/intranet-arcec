<?php
/**
 * @name filtreAnnuaire.class.php Service de filtrage des données de l'annuaire
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Ajax
 * @version 1.0
 **/
namespace arcec\Ajax;

class filtreAnnuaire implements \wp\Ajax\ajax{
	
	/**
	 * Tableau de résultat à retourner
	 * @var array
	**/
	private $result;
	
	/**
	 * Identifiant du noeud de l'arborescence sélectionné ou courant
	 * @var int
	 */
	private $arboId;
	
	/**
	 * Lettre pour le filtrage de l'annuaire
	 * @var string
	 */
	private $alpha;
	
	/**
	 * Mapper sur l'annuaire
	 * @var object
	**/
	private $annuaire;
	
	/**
	 * Mapper sur la taxonomie de l'annuaire
	 * @var unknown_type
	 */
	private $taxonomie;
	
	/**
	 * Tableau de stockage des noeuds terminaux à traiter dans la taxonomie
	 * @var array
	 */
	private $nodes;
	
	public function mapper($mapper,$kindOf){
		if(property_exists($this, $kindOf)){
			$mapperName = "\\arcec\\Mapper\\" . $mapper . "Mapper";
			$factory = new \wp\Patterns\factory($mapperName);
			$this->{$kindOf} = $factory->addInstance();
		}
		return $this;
	}
	
	public function arborescence($arboId){
		if($arboId !== false){
			$this->arboId = $arboId;
			
			$this->setTaxonomy($arboId);
		}
		return $this;
	}
	
	public function alpha($alpha){
		$this->alpha = $alpha;
		return $this;
	}
	
	/**
	 * Retourne la liste des résultats
	 * @see \wp\Ajax\ajax::getResult()
	 */
	public function getResult(){
		return $this->result;
	}
	
	/**
	 * Traite le filtrage de l'annuaire en fonction des paramètres passés
	 * @see \wp\Ajax\ajax::process()
	 */
	public function process(){
		// Restreint le filtre sur les noeuds terminaux sélectionnés
		if(sizeof($this->nodes)){
			$searchCols[] = array(
				"column" => "arboannuaire_id",
				"operateur" => sizeof($this->nodes) > 1 ? "IN" : "="
			);
			
			if(sizeof($this->nodes) > 1)
				$searchValue[] = "(" . implode(",",$this->nodes) . ")";
			else
				$searchValue[] = $this->nodes[0];
		}
		
		// Y-a-t-il un filtre sur l'initiale
		if(!is_null($this->alpha) && $this->alpha != ""){
			$searchCols[] = array(
				"column" => "nom",
				"operateur" => "LIKE"
			);
			
			$searchValue[] = $this->alpha . "%";
		}
		
		$this->annuaire->searchBy($searchCols,$searchValue);
		
		if($this->annuaire->count() > 0){
			$this->annuaire->set($this->annuaire->getNameSpace());
			
			#begin_debug
			#echo $this->annuaire->getSQL();
			#end_debug
			
			$this->result["statut"] = 1;
			
			foreach($this->annuaire->getCollection() as $annuaire){
				
				$this->result["datas"][] = array(
					"id" => $annuaire->id
				);
			}
			return true;
		}
		
		$this->result["statut"] = 0;
		
		return false;
	}
	
	private function setTaxonomy($arboId){
			
		$this->taxonomie->clearSearch();
			
		$this->taxonomie->searchBy("parent",$arboId);
		$this->taxonomie->set($this->taxonomie->getNameSpace());
		
		if($this->taxonomie->getNbRows() > 0){
			foreach ($this->taxonomie->getCollection() as $node){
				$this->setTaxonomy($node->id);
			}
		} else {
			$this->nodes[] = $arboId;
		}
		
		return;
	}
}
?>