<?php
/**
 * @name getRepository.class.php Outil de récupération des dossiers et fichiers
 * @author web-Projet.com (contact@web-projet.com) - Mars 2017
 * @package arcec
 * @version 1.0
**/
namespace arcec;

class getRepository {
	/**
	 * Itérateur pour le listing des documents du dossier de dépôt
	 * @var \RecursiveDirectoryIterator
	 */
	private $directoryIterator;
	
	/**
	 * Itérateur récursif
	 * @var \RecursiveIteratorIterator
	 */
	private $iterator;
	
	/**
	 * Dossier racine de l'exploration
	 * @var string
	 */
	private $rootDirectory;
	
	/**
	 * Structure de stockage du résultat du parcours des dossiers
	 * @var \RecursiveArrayIterator
	 */
	private $files;
	
	/**
	 * Tableau final des résultats
	 * @var array
	 */
	private $results;
	
	/**
	 * Listing des fichiers pour la clé asso
	 * @var array
	 */
	private $assoResult;
	
	public function __construct(){
		$this->rootDirectory = "/home/arcec/www/files/home";
		
		$this->directoryIterator = new \RecursiveDirectoryIterator($this->rootDirectory,\RecursiveIteratorIterator::CHILD_FIRST | \RecursiveDirectoryIterator::SKIP_DOTS);
		
		$this->iterator = new \RecursiveIteratorIterator($this->directoryIterator);
		
		$this->files = new \RecursiveArrayIterator(array());
		
		$this->results = array();
		
		$this->process();
		
		
		$this->toArray($this->results, $this->files);
		
		var_dump($this->results);
		
	}
	
	private function process(){
		foreach($this->iterator as $file){	
			$path = $file->isDir() ? array($file->getFilename() => []) : array($file->getFilename());
			for ($depth = $this->iterator->getDepth() - 1; $depth >= 0; $depth--) {
				$path = array($this->iterator->getSubIterator($depth)->current()->getFilename() => $path);
			}
			$this->results = array_merge_recursive($this->results, $path);
		}
	}
	
	private function toArray(&$array, $files){
		foreach($files as $key => $tmp){
			if(is_string($tmp)){
				$array[] = $tmp;
			} else {
				$array[$key] = array();
				$this->toArray($array[$key], $tmp);
			}
		}
	}
	
	private function get($search, $array=null){
		if(is_null($array)){
			$array = $this->results;
		}
		foreach($array as $key => $values){
			echo $key . " => " . $search . "<br />\n";
			if($key == $search){
				return $array[$key];
			}
			if(is_array($values)){
				$this->get($search,$values);
			}
		}
	}
}