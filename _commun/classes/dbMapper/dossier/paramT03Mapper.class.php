<?php
/**
 * @name paramT03Mapper.class.php Mapping de données sur la table dbprefix_paramcomment avec le code Parent T03
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Mapper
 * @version 1.0
**/
namespace arcec\Mapper;

class paramT03Mapper extends \arcec\Mapper\paramcommentMapper {
	public function __construct(){

		parent::__construct();

		$mapper = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
		
		$code = substr($mapper,5,strpos($mapper,"Mapper")-5);
		
		$this->definitionMapper->searchBy("code",$code);
		$this->definitionMapper->set($this->namespace . "\\");
		
		$this->searchBy("param_definition_id",$this->definitionMapper->getObject()->id);
		
		//$this->searchBy("param_definition_id",45); // Restreint la liste aux paramètres de base
		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \wp\dbManager\dbMapper::usage()
	 **/
	protected function usage(){}
}
?>