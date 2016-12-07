<?php
/**
 * @name annuairePRSMapper.class.php Mapping de données sur la table dbPrefix_arboannuaire avec la codification PRS
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Mapper
 * @version 1.0
**/
namespace arcec\Mapper;

class annuairePRSMapper extends \arcec\Mapper\arboannuaireMapper {
	
	/**
	 * Identifiant du noeud de l'annuaire pour les prescripteurs
	 * @var int
	 */
	private $annuaireId;
	
	public function __construct(){

		parent::__construct();

		$mapper = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
		
		$code = substr($mapper,8,strpos($mapper,"Mapper")-8);
		
		$this->searchBy("codification",$code);
		$this->set($this->namespace . "\\");
		
		$this->annuaireId = $this->getObject()->id;
	}
	
	public function getId(){
		return $this->annuaireId;
	}
}
?>