<?php
/**
 * @name convertRapport.class.php Services de conversion de rapports une ligne par rapport
 * @author web-Projet.com (Jean-Luc Aubert)
 * @package arcec
 * @version 1.0
 * Le fichier original contient les colonnes suivantes :
 * 	0 => Identifiant du PDP
 * 	1 => Date 1
 * 	2 => Rapport 1
 * ...
 * Jusqu'à Date 29 - Rapport 29
 * Le fichier converti contiendra une ligne par dates
**/
namespace arcec;

class convertRapport extends \wp\Files\importCSV {
	/**
	 * Données à intégrer dans la base de données
	 * @var array
	 */
	private $datas;

	/**
	 * Données non intégrables
	 * @var array
	 **/
	private $badDatas;
	
	public function __construct(){
		$this->columns = array();
	
		$this->columns();
	
		$this->filePath = "_repository/rapports_en_colonne.csv";
		$this->packets = 50000;
	
		/* Définit la signature d'une ligne incohérente
		 *	Colonne date à vide
			*	Colonne rapport à vide
			*/
		$this->badSignature = sha1("vide");
	
		if($this->read()){
			$this->toMapper();
			$this->process();
		}
	}

	/**
	 * Traite les informations définitives à traiter
	 */
	private function toMapper(){
		$indice		= 0;
		$colIndice	= 0; // Indice de la colonne courante
	
		foreach($this->result as $line){
			$datas = array();
			$signature = ""; // Pour le contrôle des signatures de lignes incohérentes
			if($indice == 0){
				if($this->processFirstLine){
					foreach($line as $value){
						$column = $this->columns[$colIndice];
	
						if($column->isMapped()){
							if(sizeof($column->method())){
								// Calcule la donnée en fonction des paramètres
								$computeInfo = $column->method();
								$methods = array_keys($computeInfo); // Stocke la méthode à appeler
								$methodName = $methods[0];
								$params = array();
								foreach($computeInfo[$methodName] as $columnDefinition){
									$params[] = $this->getValueFromDefinition($line,$columnDefinition);
								}
								$data = $this->{$methodName}($params);
							}
	
							if(sizeof($column->translations())){
								// Transforme la donnée en fonction du type de translation à opérer
								$data = $column->translate($this->getValue($line,$column));
							}
							// Ajoute l'information à la collection finale
							$datas[] = array($column->name() => $data);
						}
	
						$colIndice++;
					}
					$this->datas[] = $datas;
				}
			} else {
				foreach($line as $value){
					$column = $this->columns[$colIndice];
					if(!is_null($column)){
						// Détermine les informations de signature
						if($column->name() == "dateRapport"){
							if($value == "" || is_null($value) || $value === false)
								$signature = "vide";
								else
									$signature = $value;
						}
	
	
						if($column->isMapped()){
							$data = $value;
							if(sizeof($column->method())){
								// Calcule la donnée en fonction des paramètres
								$computeInfo = $column->method();
								$methods = array_keys($computeInfo); // Stocke la méthode à appeler
								$methodName = $methods[0];
								$params = array();
								foreach($computeInfo[$methodName] as $columnDefinition){
									$params[] = $this->getValueFromDefinition($line,$columnDefinition);
								}
								$data = $this->{$methodName}($params);
							}
	
							if(sizeof($column->translations())){
								// Transforme la donnée en fonction du type de translation à opérer
								//$data = $column->translate($this->getValue($line,$column));
								$data = $column->translate($data);
							}
	
							// Ajoute l'information à la collection finale
							if($data == ""){
								$default = $column->defaultVal();
								if($default !== false){
									$data = $default;
								}
							}
							$datas[] = array($column->name() => $data);
	
							#begin_debug
							#echo "Donnée retournée pour " . $column->mappedColumn() . " : $data<br />\n";
							#end_debug
	
						}
	
					}
					$colIndice++;
				}
				if(sha1($signature) != $this->badSignature){
					$this->datas[] = $datas;
				} else {
					$this->badDatas[] = $datas;
				}
			}
			$colIndice = 0;
			$indice++;
			#begin_debug
			#if($indice > 1) die();
			#end_debug
		}
	}
	
	/**
	 * Parcourt les données récupérées, pour chaque ligne :
	 * 	Stocke l'identifiant du porteur qui deviendra la clé du tableau final,
	 * 	Pour chaque données suivante, créer une nouvelle colonne avec le couple date/rapport
	 */
	private function process(){
		$indice = 0;
		$line = array();
		$lines = array();
		
		foreach($this->datas as $row){
			$id = array_shift($row);
			// Boucle sur le reste du tableau pour récupérer les données date et rapport
			for($i=0;$i<sizeof($row);$i++){
				if($indice < 2){
					$values = array_values($row[$i]);
					echo $id["pdp"] . " => [$indice] : $values[0]<br />\n";
					$line[] = $values[0];
					$indice++;
				} else {
					$indice = 0;
				}
			}
			$indice = 0;
		}
		// On reconstruit le fichier final avec les données remappées
		die();
		
		$content = "";
		$this->filePath = "_repository/rapports_".date("YmdHi").".txt";
		
		$this->create();
		// Crée l'en-tête du fichier
		$content = "pdp\tdate\tentretien\n";
		$this->write($content);
		
		foreach($lines as $line){
			$content = "";
			foreach($line as $column){
				$content .= $column . "\t";
			}
			$content = substr($content,0,strlen($content)-1) . "\n";
			$this->write($content);
		}
	}
	/**
	 * Définit les colonnes à traiter du fichier d'origine
	 */
	private function columns(){
		// ID du porteur de projet
		$column = new \wp\Files\column();
		$column
		->name("pdp")
		->type("int")
		;
		$this->addColumn($column);
		
		// Date
		$date = new \wp\Files\column();
		$date
		->name("date1")
		->type("datefr")
		->defaultVal("")
		->addTranslation("toSQL")
		;
		$this->addColumn($date);
		
		// Rapport
		$rapport = new \wp\Files\column();
		$rapport
		->name("rapport1")
		->type("string")
		->defaultVal("")
		;
		$this->addColumn($rapport);
		
		// Clone les objets pour faciliter le traitement
		$this->duplique($date,$rapport,29);
	}
	
	/**
	 * Clone les objets date et rapport jusqu'à atteindre la limite "until"
	 * @param object $date
	 * @param object $rapport
	 * @param int $until
	 */
	private function duplique($date,$rapport,$until){
		for($i=2;$i<=$until;$i++){
			$newDate = clone $date;
			$newRapport = clone($rapport);
			
			$newDate->name("date" . $i);
			$newRapport->name("rapport" . $i);
			
			$this->addColumn($newDate);
			$this->addColumn($newRapport);
		}
	}
}