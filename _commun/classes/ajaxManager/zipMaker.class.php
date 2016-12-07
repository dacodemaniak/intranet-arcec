<?php
/**
 * @name zipMaker.class.php Service de compression de documents porteurs
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Ajax
 * @version 1.0
**/
namespace arcec\Ajax;

class zipMaker implements \wp\Ajax\ajax{
	/**
	 * Tableau de résultat à retourner
	 * @var array
	**/
	private $result;
	
	/**
	 * Tableau contenant les fichiers à compresser
	 * @var array
	 */
	private $files;
	
	/**
	 * Instance d'objet de mapping de données
	 * @var object
	 */
	private $mapper;
	
	
	public function __construct(){
		$this->result = array();
		$this->mapper = new \arcec\Mapper\docporteurMapper();
	}
	
	/**
	 * Définit les paramètres de récupération des données
	 * @param string $param
	 * @return \arcec\Ajax\dossierCounter
	 */
	public function files($files){
		foreach($files as $fileId){
			$this->mapper->clearSearch();
			$this->mapper->setId($fileId);
			$this->mapper->set($this->mapper->getNameSpace());
			
			$this->files[] = "../../../_repository/" . $this->mapper->getObject()->nomcalcule;
		}
		
		return $this;
	}
	
	public function process(){

		$zipName = \wp\framework::getFramework()->getAppRoot() . "/_repository/archive.zip";
		if(file_exists($zipName)){
			unlink($zipName);
		}
		$archive = new \pclzip($zipName);
		$fileList = $archive->create($this->files,PCLZIP_OPT_REMOVE_PATH,"../../../_repository/");
		
		if ($fileList == 0) {
			$this->result["error"] = "Erreur lors de la compression : ". $archive->errorInfo(true);
			$this->result["zipFile"] = null;
			
		} else {
			$this->result["error"] = 0;
			
			$this->result["zipFile"] = "http://" . $_SERVER["SERVER_NAME"] . "/_repository/archive.zip";
		}	
		/*
		foreach($this->files as $name){
			$archive->add($name);
		}
		*/
		
	}
	
	/**
	 * Retourne la liste des résultats
	 * @see \wp\Ajax\ajax::getResult()
	 */
	public function getResult(){
		return $this->result;
	}
}
?>