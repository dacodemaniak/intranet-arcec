<?php
/**
 * @name paramTYPMapper.class.php Mapping de données sur la table dbPrefix_parambase avec le code Parent TYP ou id 26
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Mapper
 * @version 1.0
**/
namespace arcec\Mapper;

class paramTYPMapper extends \arcec\Mapper\parambaseMapper {
	public function __construct(){

		parent::__construct();

		$mapper = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
		
		$code = substr($mapper,5,strpos($mapper,"Mapper")-5);
		
		$this->definitionMapper->searchBy("code",$code);
		$this->definitionMapper->set($this->namespace . "\\");
		$this->searchBy("param_definition_id",$this->definitionMapper->getObject()->id);
		
		$this->usage();
		
		//$this->searchBy("param_definition_id",26); // Restreint la liste aux paramètres de base
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \arcec\Mapper\parambaseMapper::usage()
	 */
	protected function usage(){
		$this->usage["dossier"] = array(
				"porteurtyp"
		);
	}
}
?>