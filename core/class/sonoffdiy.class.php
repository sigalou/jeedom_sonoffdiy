<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
class sonoffdiy extends eqLogic {
	public static function cron($_eqlogic_id = null) {
		$eqLogics = ($_eqlogic_id !== null) ? array(eqLogic::byId($_eqlogic_id)) : eqLogic::byType('sonoffdiy', true);
		foreach ($eqLogics as $sonoffdiy) {
			$autorefresh = $sonoffdiy->getConfiguration('autorefresh','00 22 01 01 3 2020');
			if ($autorefresh != '') {
				try {
					//log::add('sonoffdiy', 'debug', __('Expression cron valide pour ', __FILE__) . $sonoffdiy->getHumanName() . ' : ' . $autorefresh);
					$c = new Cron\CronExpression($autorefresh, new Cron\FieldFactory);
					if ($c->isDue()) {
						$sonoffdiy->refresh();
					}
				} catch (Exception $exc) {
					log::add('sonoffdiy', 'error', __('Expression cron non valide pour ', __FILE__) . $sonoffdiy->getHumanName() . ' : ' . $autorefresh);
				}
			}
		}
	}	
	public static function enregistreAlbumFB($Id, $Albums) {
		$Albums=json_decode($Albums);
		$device = eqLogic::byId($Id);
		if (is_object($device)) {
			// Enregistrement dans Configuration du device en cours ($this)
			$device->setConfiguration('arrayAlbumsFacebook', $Albums);
			log::add('sonoffdiy', 'debug', 'On enregistre : '.json_encode($Albums).' dans plugin/device('.$Id.')/config/arrayAlbumsFacebook');
			$device->save();
		} else
			event::add('jeedom::alert', array('level' => 'warning', 'page' => 'sonoffdiy', 'message' => __('Device id:'.$Id.' introuvable', __FILE__)));
	}
	public static function scanLienPhotos($Id) {
		$device = eqLogic::byId($Id);
		if (is_object($device)) {
			$diapo = array();
			$device->setConfiguration('localEtat', "nok"); 
			$sambaShare	= config::byKey('samba::backup::share')	;
			log::add('sonoffdiy', 'debug', 'sambaShare->>>'.$sambaShare);
			log::add('sonoffdiy', 'debug', 'dossierSambasonoffdiy->>>'.$device->getConfiguration('dossierSambasonoffdiy'));
			$dos=$sambaShare.$device->getConfiguration('dossierSambasonoffdiy');
				try {
					$nbPhotos=self::lsjpg_count($device->getConfiguration('dossierSambasonoffdiy'));
					$device->setConfiguration('cheminsonoffdiyMessage', "");
				}
				catch(Exception $exc) {
					//log::add('sonoffdiy', 'error', __('Erreur pour ', __FILE__) . ' : ' . $exc->getMessage());
					$device->setConfiguration('cheminsonoffdiyMessage', $exc->getMessage());
					$nbPhotos=0;
				}		
			$device->setConfiguration('sambaEtat', "ok"); 
			log::add('sonoffdiy', 'debug', "sambaEtat:ok");				
			$device->setConfiguration('cheminsonoffdiyComplet', $dos);
			$device->setConfiguration('nombrePhotos', $nbPhotos);
			$device->setConfiguration('derniereMAJ', date("d-m-Y H:i:s"));
				if ($nbPhotos==0) {
					$device->setConfiguration('cheminsonoffdiyValide', "nok");
					$device->setConfiguration('localEtat', "nok"); 
					$device->setConfiguration('sambaEtat', "nok"); 
				}
				else {
					$device->setConfiguration('cheminsonoffdiyValide', "ok");
				}
			$device->save();
		} else
		event::add('jeedom::alert', array('level' => 'warning', 'page' => 'sonoffdiy', 'message' => __('Device id:'.$Id.' introuvable', __FILE__)));
	}
	public function sortBy($field, &$array, $direction = 'asc') {
		usort($array, create_function('$a, $b', '
			$a = $a["' . $field . '"];
			$b = $b["' . $field . '"];
			if ($a == $b) return 0;
			$direction = strtolower(trim($direction));
			return ($a ' . ($direction == 'desc' ? '>' : '<') . ' $b) ? -1 : 1;
			'));
		return true;
	}
	public function redimensionne_Photo($tirageSort,$maxWidth,$maxHeight, $arrondiPhoto, $centrerLargeur)  {
		$fichier='/tmp/sonoffdiy_'.$this->getId()."_".$tirageSort.'_rotate.jpg';
		$fichiercomplet='/var/www/html'.$fichier;
		if (!file_exists($fichiercomplet)) {	
			$fichier='/tmp/sonoffdiy_'.$this->getId()."_".$tirageSort.'.jpg';
			$fichiercomplet='/var/www/html'.$fichier;
		}
		if (file_exists($fichiercomplet)) {
			# Passage des paramètres dans la table : imageinfo
			$imageinfo= getimagesize("$fichiercomplet");
			$iw=$imageinfo[0];
			$ih=$imageinfo[1];
			# Paramètres : Largeur et Hauteur souhaiter $maxWidth, $maxHeight
			# Calcul des rapport de Largeur et de Hauteur
			$widthscale = $iw/$maxWidth;
			$heightscale = $ih/$maxHeight;
			$rapport = $ih/$widthscale;
			# Calul des rapports Largeur et Hauteur à afficher
			if($rapport < $maxHeight)
				{$nwidth = $maxWidth;}
			 else
				{$nwidth = round($iw/$heightscale);}
			 if($rapport < $maxHeight)
				{$nheight = $rapport;}
			 else
				{$nheight = $maxHeight;}
			$decalerAdroite="";
			if ($centrerLargeur) {
				$decalage=round(($maxWidth-$nwidth)/2);
				if ($decalage > 1)
					$decalerAdroite="position: relative; left: ".$decalage."px;";
			log::add('sonoffdiy', 'debug', '--> Image '.$iw.'x'.$ih.' redimensée en '.$nwidth.'x'.$nheight);
			}
			return '<img height="'.$nheight.'" width="'.$nwidth.'" class="rien" style="'.$decalerAdroite.'height: '.$nheight.';width: '.$nwidth.';border-radius: '.$arrondiPhoto.';" src="'.$fichier.'" alt="image">';
		} else {
			log::add('sonoffdiy', 'debug', '**********************file_exists PAS:'.$fichiercomplet.'***********************************');
			return "Le fichier $fichiercomplet n'existe pas.";
		}    
	}
	public function redimensionne_PhotoFacebook($source,$Width,$Height,$maxWidth,$maxHeight, $arrondiPhoto, $centrerLargeur)  {
		$iw=$Width;
		$ih=$Height;
		# Paramètres : Largeur et Hauteur souhaiter $maxWidth, $maxHeight
		# Calcul des rapport de Largeur et de Hauteur
		$widthscale = $iw/$maxWidth;
		$heightscale = $ih/$maxHeight;
		$rapport = $ih/$widthscale;
		# Calul des rapports Largeur et Hauteur à afficher
		if($rapport < $maxHeight)
			{$nwidth = $maxWidth;}
		 else
			{$nwidth = round($iw/$heightscale);}
		 if($rapport < $maxHeight)
			{$nheight = $rapport;}
		 else
			{$nheight = $maxHeight;}
		$decalerAdroite="";
		if ($centrerLargeur) {
			$decalage=round(($maxWidth-$nwidth)/2);
			if ($decalage > 1)
				$decalerAdroite="position: relative; left: ".$decalage."px;";
		log::add('sonoffdiy', 'debug', '--> Image '.$iw.'x'.$ih.' redimensée en '.$nwidth.'x'.$nheight);
		}
		return '<img height="'.$nheight.'" width="'.$nwidth.'" class="rien" style="'.$decalerAdroite.'height: '.$nheight.';width: '.$nwidth.';border-radius: '.$arrondiPhoto.';" src="'.$source.'" alt="image">';
	}
	public function infosExif($tirageSort, $_indexPhoto, $_device)  {
		$fichier='/tmp/sonoffdiy_'.$this->getId()."_".$tirageSort.'.jpg';
		$fichiercomplet='/var/www/html'.$fichier;
		$fichiercompletRotate='/var/www/html/tmp/sonoffdiy_'.$this->getId()."_".$tirageSort.'_rotate.jpg';
		if (file_exists($fichiercomplet)) {
			$exif = exif_read_data($fichiercomplet, 'EXIF');
			$intDate=0;
			if     (strtotime($exif['FileDateTime'])) $intDate=strtotime($exif['FileDateTime']);
			elseif (strtotime($exif['DateTimeOriginal'])) $intDate=strtotime($exif['DateTimeOriginal']);
			elseif (strtotime($exif['DateTimeDigitized'])) $intDate=strtotime($exif['DateTimeDigitized']);
			elseif (strtotime($exif['DateTimeDigitized'])) $intDate=strtotime($exif['DateTimeDigitized']);
			elseif (strtotime($exif['GPSDateStamp'])) $intDate=strtotime($exif['GPSDateStamp']);
			else $intDate=$exif['FileDateTime'];
			$formatDateHeure = config::byKey('formatDateHeure', 'sonoffdiy', '0');
			if ($formatDateHeure =="") $formatDateHeure="d-m-Y H:i:s";
			$_device->checkAndUpdateCmd('date'.$_indexPhoto, date($formatDateHeure, $intDate));
			log::add('sonoffdiy', 'debug', '--> Date&Heure récupérés: '.date($formatDateHeure, $intDate));
			//log::add('sonoffdiy', 'debug', '--> Orientation récupérée: '.$exif['GPSLatitude']);
			if (config::byKey('rotate', 'sonoffdiy', '0')) {
				$photoaTraiter = ImageCreateFromJpeg($fichiercomplet);
				switch ($exif['Orientation']) {
					case "6":
						imagejpeg(imagerotate($photoaTraiter, 270, 0),$fichiercompletRotate);
						break;
					case "8":
						imagejpeg(imagerotate($photoaTraiter, 90, 0),$fichiercompletRotate);
						break;
					case "3":
						imagejpeg(imagerotate($photoaTraiter, 180, 0),$fichiercompletRotate);
						break;
				}	
			}
			$siteGPS="";
			$APIGoogleMaps = config::byKey('APIGoogleMaps', 'sonoffdiy', '0');
			if ($APIGoogleMaps !="" && is_array($exif['GPSLatitude'])) {
				$requete="https://maps.googleapis.com/maps/api/geocode/json?latlng=".self::DMSversDD($exif['GPSLatitudeRef'],$exif['GPSLatitude']).",".self::DMSversDD($exif['GPSLongitudeRef'],$exif['GPSLongitude'])."&key=".$APIGoogleMaps;
				log::add('sonoffdiy', 'debug', '--> Requete Web: '."https://maps.googleapis.com/maps/api/geocode/json?latlng=".self::DMSversDD($exif['GPSLatitudeRef'],$exif['GPSLatitude']).",".self::DMSversDD($exif['GPSLongitudeRef'],$exif['GPSLongitude'])."&key=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx");
				$recupereJson=file_get_contents($requete);
				$json = json_decode($recupereJson,true);
				if ($json['error_message'] != "")
					$siteGPS=$json['error_message'];
				else
					$siteGPS=strstr($json['plus_code']['compound_code'], ' ');
				log::add('sonoffdiy', 'debug', '--> Adresse trouvée: '.$siteGPS);
			} else {
			log::add('sonoffdiy', 'debug', "--> Pas de coodonnées GPS de détectées (ou pas de clé Google Maps configurée)"); }
			$_device->checkAndUpdateCmd('site'.$_indexPhoto, $siteGPS); 
		}
	}
	public function DMSversDD($WouS, $arrayGPS) {
		if ($WouS=="W" || $WouS=="S") $negatif=-1; else $negatif=1;
		$nombre=(floatval(str_replace("/1", "", self::recupGPS($arrayGPS[0]))))+((floatval(str_replace("/1", "", self::recupGPS($arrayGPS[2]))) /60 + floatval(str_replace("/1", "", self::recupGPS($arrayGPS[1]))))/60);
		$nombre=$nombre*$negatif;
		return $nombre;
	}
	public function recupGPS($chaineGPS) {
		return intval(strstr($chaineGPS, '/', true))/intval(str_replace("/", "", strstr($chaineGPS, '/')));
	}
	public function refresh() {
		$largeurPhoto=$this->getConfiguration('largeurPhoto');
		$hauteurPhoto=$this->getConfiguration('hauteurPhoto');
		$arrondiPhoto=$this->getConfiguration('arrondiPhoto');
		if ($largeurPhoto =="") $largeurPhoto="250";
		if ($hauteurPhoto =="") $hauteurPhoto="250";		
		if ($arrondiPhoto =="") $arrondiPhoto="30%";		
		$tirageSort="999";//999 pour boucler dans tirageSort
		$touteslesValeurs= array($tirageSort);
		$nbPhotosaGenerer=$this->getConfiguration('nbPhotosaGenerer');
		$centrerLargeur=$this->getConfiguration('centrerLargeur');
		//log::add('sonoffdiy', 'debug', '~~~~~~~~~~~~~~~~~~~~~~$centrerLargeur:'.$centrerLargeur.'~~~~~~~~~~~~~~~~~~~~~~~~~');
		$formatDateHeure = config::byKey('formatDateHeure', 'sonoffdiy', '0');
		if ($formatDateHeure =="") $formatDateHeure="d-m-Y H:i:s";
		if ($nbPhotosaGenerer<1 || $nbPhotosaGenerer>9) $nbPhotosaGenerer=2;
		if ($this->getConfiguration('stockageSamba')==1) {
			$sambaShare	= config::byKey('samba::backup::share')	;
			$dos=$sambaShare.$this->getConfiguration('dossierSambasonoffdiy');
			//log::add('sonoffdiy', 'debug', '**********************1***********************************');
			$diapo=self::jpg_list($this->getConfiguration('dossierSambasonoffdiy'));
			//log::add('sonoffdiy', 'debug', '**********************diapo:'.json_encode($diapo).'***********************************');
			$nbPhotos=count($diapo);
			log::add('sonoffdiy', 'debug', '----------------------------------------------------------------------------');
			log::add('sonoffdiy', 'debug', 'Dans le dossier '.$dos.', il y a '.$nbPhotos.' photos');
			for ($i = 1; $i <= $nbPhotosaGenerer; $i++) {
			while ($compteurparSecurite < 20 && in_array($tirageSort, $touteslesValeurs))
				{
				$tirageSort=mt_rand(0,$nbPhotos-1);
				$compteurparSecurite++;
				}
			array_push($touteslesValeurs, $tirageSort);
			$file = $diapo[$tirageSort];
			$newfile = '/var/www/html/tmp/sonoffdiy_'.$this->getId()."_".$tirageSort.'.jpg';
			log::add('sonoffdiy', 'debug', 'Fichier sélectionné au hasard:'.$file.' copié dans '.$this->getConfiguration('dossierSambasonoffdiy').' en '.$newfile);
			try {
				self::downloadCore($this->getConfiguration('dossierSambasonoffdiy'), $file, $newfile);
				self::infosExif($tirageSort,$i,$this);
				$image=self::redimensionne_Photo($tirageSort,$largeurPhoto,$hauteurPhoto, $arrondiPhoto, $centrerLargeur);
				$this->checkAndUpdateCmd('photo'.$i, $image);	
			}
			catch(Exception $exc) {
				log::add('sonoffdiy', 'error', __('Erreur pour ', __FILE__) . ' : ' . $exc->getMessage());
			}			
			}
			self::chmod777();
		}
		elseif ($this->getConfiguration('stockageFacebook')==1) {
			log::add('sonoffdiy', 'debug', '**********************Refresh Facebook***********************************');
			// on va tirer au sort l'album photo
			$Albums=$this->getConfiguration('arrayAlbumsFacebook');
			//log::add('sonoffdiy', 'debug', 'Albums : '.json_encode($Albums).'***********************************');
			$CompteAlbums=0;
			foreach ($Albums as $key2 => $value2) {
				if ($Albums[$key2][1] == '1') $CompteAlbums++;
			}
			for ($i = 1; $i <= $nbPhotosaGenerer; $i++) {
				$tirageSort=mt_rand(0,$CompteAlbums-1);
				$compteurPourTrouverAlbum=0;
				foreach ($Albums as $key2 => $value2) {
					if (($Albums[$key2][1] == '1') && ($compteurPourTrouverAlbum==$tirageSort)) {$idAlbumChoisi=$value2[0]; break;}
					if ($Albums[$key2][1] == '1') $compteurPourTrouverAlbum++;
				}	
				$albumsFacebook = config::byKey('albumsFacebook', 'sonoffdiy', '0');
				foreach ($albumsFacebook as $value) {
					if ($value['id'] == $idAlbumChoisi) { $nbdePhotosdansAlbum=$value['count']; break;}
				}		
				$tirageSort=mt_rand(0,$nbdePhotosdansAlbum-1);
				log::add('sonoffdiy', 'debug', "On a tiré au sort la ".$tirageSort."ème photo de l'album ".$idAlbumChoisi." qui en compte ".$nbdePhotosdansAlbum);
				$TokenFacebook = config::byKey('TokenFacebook', 'sonoffdiy', '0');
				$requete="https://graph.facebook.com/v5.0/".$idAlbumChoisi."/photos?fields=height%2Cwidth&limit=100&access_token=".$TokenFacebook;
				log::add('sonoffdiy', 'debug', 'On cherche la photo avec la requète : '.$requete);
				$onaTrouvePhoto = false;
				$countdata=0;
				while (!$onaTrouvePhoto) {
					if ($recupereJson=file_get_contents($requete, true)) {
					$json = json_decode($recupereJson,true);
					$data=$json['data'];
					$indexPhoto=$tirageSort-1-$countdata;
					$countdata=$countdata+count($data);
					$paging=$json['paging'];
						if ($tirageSort<=$countdata) {
								// c'est ok, la photo est dans $json['data']
							$idphotoChoisie=$data[$indexPhoto]['id'];
							log::add('sonoffdiy', 'debug', 'ID de la photo choisie : '.$idphotoChoisie);
							$onaTrouvePhoto = true;
							} else {
							log::add('sonoffdiy', 'debug', 'On recherche la photo avec la requète : '.json_encode($paging['next']));
							$requete=$paging['next'];
							}
					} else {
					log::add('sonoffdiy', 'debug', "*********************** Souci de récupération de l'ID de la photo");
					$onaTrouvePhoto = true; // on sort	
					}
				}
				$requete="https://graph.facebook.com/v5.0/".$idphotoChoisie."?fields=event%2Calbum%2Calt_text_custom%2Cbackdated_time%2Cbackdated_time_granularity%2Ccreated_time%2Cheight%2Cname%2Cname_tags%2Cpage_story_id%2Cplace%2Cwidth%2Cimages&access_token=".$TokenFacebook;
				log::add('sonoffdiy', 'debug', 'On cherche les infos sur la photo avec la requète : '.$requete);
				if ($recupereJson=file_get_contents($requete, true)) {
					$json = json_decode($recupereJson,true);
					$this->checkAndUpdateCmd('date'.$i, date($formatDateHeure,  strtotime($json['created_time'])));				
					$this->checkAndUpdateCmd('site'.$i, $json['place']['name']);		
					$this->checkAndUpdateCmd('pays'.$i, $json['place']['location']['country']);		
					$this->checkAndUpdateCmd('ville'.$i, $json['place']['location']['city']);		
					$this->checkAndUpdateCmd('album'.$i, $json['album']['name']);		
					$image=self::redimensionne_PhotoFacebook($json['images']['0']['source'],$json['images']['0']['width'],$json['images']['0']['height'],$largeurPhoto,$hauteurPhoto, $arrondiPhoto, $centrerLargeur);
					$this->checkAndUpdateCmd('photo'.$i, $image);		
				} else {
					log::add('sonoffdiy', 'debug', "*********************** Souci de récupération des infos de la photo");
				}		
			}
		}
		else {
			$dossierLocal=$this->getConfiguration('cheminsonoffdiy');
			if ($dossierLocal =="") $dossierLocal="/../images/"; // par défaut
			$dos=dirname(__FILE__).$dossierLocal; 
			$diapo=glob($dos.'*.jpg');
			$nbPhotos=count($diapo);
			for ($i = 1; $i <= $nbPhotosaGenerer; $i++) {
			while ($compteurparSecurite < 20 && in_array($tirageSort, $touteslesValeurs))
				{
				$tirageSort=mt_rand(0,$nbPhotos-1);
				$compteurparSecurite++;
				}
			array_push($touteslesValeurs, $tirageSort);
			$file = $diapo[$tirageSort];
			$newfile = '/var/www/html/tmp/sonoffdiy_'.$this->getId()."_".$tirageSort.'.jpg';
			if (!copy($file, $newfile)) log::add('sonoffdiy', 'debug', 'Copie image '.$file.' en sonoffdiy_'.$this->getId()."_".$tirageSort.'.jpg NOK'); else log::add('sonoffdiy', 'debug', 'Copie image '.$file.' en sonoffdiy_'.$this->getId()."_".$tirageSort.'.jpg OK');
			$image=self::redimensionne_Photo($tirageSort,$largeurPhoto,$hauteurPhoto, $arrondiPhoto, $centrerLargeur);
			$this->checkAndUpdateCmd('photo'.$i, $image);			
			}
		}
	}
	public function postSave() {
	$nbPhotosaGenerer=$this->getConfiguration('nbPhotosaGenerer');
	for ($i = 1; $i <= $nbPhotosaGenerer; $i++) {
		$cmd = $this->getCmd(null, 'photo'.$i);
		if (!is_object($cmd)) {
			$cmd = new sonoffdiyCmd();
			$cmd->setType('info');
			$cmd->setLogicalId('photo'.$i);
			$cmd->setSubType('string');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setName('Photo '.$i);
			$cmd->setIsVisible(1);
			$cmd->setOrder($i*6);
			//$cmd->setDisplay('icon', '<i class="loisir-musical7"></i>');
			$cmd->setDisplay('title_disable', 1);
		}
		$cmd->save();	
		$cmd = $this->getCmd(null, 'date'.$i);
		if (!is_object($cmd)) {
			$cmd = new sonoffdiyCmd();
			$cmd->setType('info');
			$cmd->setLogicalId('date'.$i);
			$cmd->setSubType('string');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setName('Date '.$i);
			$cmd->setIsVisible(1);
			$cmd->setOrder($i*6+1);
			//$cmd->setDisplay('icon', '<i class="loisir-musical7"></i>');
			$cmd->setDisplay('title_disable', 1);
		}
		$cmd->save();		
		
		$cmd = $this->getCmd(null, 'site'.$i);
		if (!is_object($cmd)) {
			$cmd = new sonoffdiyCmd();
			$cmd->setType('info');
			$cmd->setLogicalId('site'.$i);
			$cmd->setSubType('string');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setName('Site '.$i);
			$cmd->setIsVisible(1);
			$cmd->setOrder($i*6+2);
			//$cmd->setDisplay('icon', '<i class="loisir-musical7"></i>');
			$cmd->setDisplay('title_disable', 1);
		}
		$cmd->save();						
		
		$cmd = $this->getCmd(null, 'ville'.$i);
		if (!is_object($cmd)) {
			$cmd = new sonoffdiyCmd();
			$cmd->setType('info');
			$cmd->setLogicalId('ville'.$i);
			$cmd->setSubType('string');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setName('Ville '.$i);
			$cmd->setIsVisible(1);
			$cmd->setOrder($i*6+3);
			//$cmd->setDisplay('icon', '<i class="loisir-musical7"></i>');
			$cmd->setDisplay('title_disable', 1);
		}
		$cmd->save();							
			
		$cmd = $this->getCmd(null, 'pays'.$i);
		if (!is_object($cmd)) {
			$cmd = new sonoffdiyCmd();
			$cmd->setType('info');
			$cmd->setLogicalId('pays'.$i);
			$cmd->setSubType('string');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setName('Pays '.$i);
			$cmd->setIsVisible(1);
			$cmd->setOrder($i*6+4);
			//$cmd->setDisplay('icon', '<i class="loisir-musical7"></i>');
			$cmd->setDisplay('title_disable', 1);
		}
		$cmd->save();					
		
		$cmd = $this->getCmd(null, 'album'.$i);
		if (!is_object($cmd)) {
			$cmd = new sonoffdiyCmd();
			$cmd->setType('info');
			$cmd->setLogicalId('album'.$i);
			$cmd->setSubType('string');
			$cmd->setEqLogic_id($this->getId());
			$cmd->setName('Album '.$i);
			$cmd->setIsVisible(1);
			$cmd->setOrder($i*6+5);
			//$cmd->setDisplay('icon', '<i class="loisir-musical7"></i>');
			$cmd->setDisplay('title_disable', 1);
		}
		$cmd->save();						
	}			
	//Commande Refresh
	$createRefreshCmd = true;
	$refresh = $this->getCmd(null, 'refresh');
	if (!is_object($refresh)) {
		$refresh = cmd::byEqLogicIdCmdName($this->getId(), __('Rafraichir', __FILE__));
		if (is_object($refresh)) {
			$createRefreshCmd = false;
		}
	}
	if ($createRefreshCmd) {
		if (!is_object($refresh)) {
			$refresh = new sonoffdiyCmd();
			$refresh->setLogicalId('refresh');
			$refresh->setIsVisible(1);
			$refresh->setDisplay('icon', '<i class="fa fa-sync"></i>');
			$refresh->setName(__('Refresh', __FILE__));
		}
		$refresh->setType('action');
		$refresh->setSubType('other');
		$refresh->setEqLogic_id($this->getId());
		$refresh->save();
	}
	$this->setStatus('forceUpdate', false); //dans tous les cas, on repasse forceUpdate à false
	}
	public function preUpdate() {
	}
	public function preRemove () {
	}
	public static function lsjpg($_dir = '', $_type = 'backup') {
		$cmd = repo_samba::makeSambaCommand('cd ' . $_dir . ';ls *.jpg', $_type);
		$result = explode("\n", com_shell::execute($cmd));
		$return = array();
		for ($i = 2; $i < count($result) - 2; $i++) {
			$line = array();
			foreach (explode(" ", $result[$i]) as $value) {
				if (trim($value) == '') {
					continue;
				}
				$line[] = $value;
			}
			$file_info = array();
			log::add('sonoffdiy', 'debug', 'filename->>>'.$line[0]);
			$file_info['filename'] = $line[0];
			$file_info['size'] = $line[2];
			$file_info['datetime'] = date('Y-m-d H:i:s', strtotime($line[5] . ' ' . $line[4] . ' ' . $line[7] . ' ' . $line[6]));
			$return[] = $file_info;
		}
		return array_reverse($result);
	}
	// functions de samba.repo.php repris et simplifié
	public static function lsjpg_count($_dir = '', $_type = 'backup') {
		$cmd = repo_samba::makeSambaCommand('cd ' . $_dir . ';ls *.jpg -U', $_type);
		return count(explode("\n", com_shell::execute($cmd)))-4;
	}	
	public static function jpg_list($_dir = '') {
		$return = array();
		foreach (self::ls($_dir) as $file) {
			if (stripos($file['filename'],'.jpg') !== false) {
				$return[] = $file['filename'];
			}
		}
		return $return;
	}	
	public static function downloadCore($_dir= '', $_fileOrigine, $_fileDestination) {
		$cmd = repo_samba::makeSambaCommand('cd ' . $_dir . ';get '.$_fileOrigine.' '.$_fileDestination, 'backup');
		com_shell::execute($cmd);
		return;
	}
	public static function chmod777() {
		com_shell::execute(system::getCmdSudo() . 'chmod 777 -R /var/www/html/tmp/' );
		//return;
	}
	public static function ls($_dir = '', $_type = 'backup') {
		$cmd = repo_samba::makeSambaCommand('cd ' . $_dir . ';ls *.JPG -U', $_type);
		$result = explode("\n", com_shell::execute($cmd));
		$return = array();
		for ($i = 2; $i < count($result) - 2; $i++) {
			$line = array();
			foreach (explode(" ", $result[$i]) as $value) {
				if (trim($value) == '') {
					continue;
				}
				$line[] = $value;
			}
			$file_info = array();
			$file_info['filename'] = $line[0];
			$file_info['size'] = $line[2];
			$file_info['datetime'] = date('Y-m-d H:i:s', strtotime($line[5] . ' ' . $line[4] . ' ' . $line[7] . ' ' . $line[6]));
			$return[] = $file_info;
		}
		//usort($return, 'repo_samba::sortByDatetime');
		return array_reverse($return);
	}	
	public static function Utf8_ansi($valor='') {
		$utf8_ansi2 = array(
		"\u00c0" =>"À",
		"\u00c1" =>"Á",
		"\u00c2" =>"Â",
		"\u00c3" =>"Ã",
		"\u00c4" =>"Ä",
		"\u00c5" =>"Å",
		"\u00c6" =>"Æ",
		"\u00c7" =>"Ç",
		"\u00c8" =>"È",
		"\u00c9" =>"É",
		"\u00ca" =>"Ê",
		"\u00cb" =>"Ë",
		"\u00cc" =>"Ì",
		"\u00cd" =>"Í",
		"\u00ce" =>"Î",
		"\u00cf" =>"Ï",
		"\u00d1" =>"Ñ",
		"\u00d2" =>"Ò",
		"\u00d3" =>"Ó",
		"\u00d4" =>"Ô",
		"\u00d5" =>"Õ",
		"\u00d6" =>"Ö",
		"\u00d8" =>"Ø",
		"\u00d9" =>"Ù",
		"\u00da" =>"Ú",
		"\u00db" =>"Û",
		"\u00dc" =>"Ü",
		"\u00dd" =>"Ý",
		"\u00df" =>"ß",
		"\u00e0" =>"à",
		"\u00e1" =>"á",
		"\u00e2" =>"â",
		"\u00e3" =>"ã",
		"\u00e4" =>"ä",
		"\u00e5" =>"å",
		"\u00e6" =>"æ",
		"\u00e7" =>"ç",
		"\u00e8" =>"è",
		"\u00e9" =>"é",
		"\u00ea" =>"ê",
		"\u00eb" =>"ë",
		"\u00ec" =>"ì",
		"\u00ed" =>"í",
		"\u00ee" =>"î",
		"\u00ef" =>"ï",
		"\u00f0" =>"ð",
		"\u00f1" =>"ñ",
		"\u00f2" =>"ò",
		"\u00f3" =>"ó",
		"\u00f4" =>"ô",
		"\u00f5" =>"õ",
		"\u00f6" =>"ö",
		"\u00f8" =>"ø",
		"\u00f9" =>"ù",
		"\u00fa" =>"ú",
		"\u00fb" =>"û",
		"\u00fc" =>"ü",
		"\u00fd" =>"ý",
		"\u00ff" =>"ÿ");
		return strtr($valor, $utf8_ansi2);      
	}	
	public function preSave() {
		// Controle si 	nbPhotosaGenerer n'est pas vide
		$nbPhotosaGenerer=$this->getConfiguration('nbPhotosaGenerer');
		if ($nbPhotosaGenerer<1 || $nbPhotosaGenerer>9) 
			{$nbPhotosaGenerer=2;
			$this->setConfiguration('nbPhotosaGenerer',"2");
		}
		$this->setConfiguration('cheminsonoffdiyValide', "question"); 
		$diapo = array();
		if (($this->getConfiguration('stockageSamba')!=1) && ($this->getConfiguration('stockageFacebook')!=1)) {
			// On est sur le mode Stockage LOCAL
			$this->setConfiguration('stockageLocal',1); // par défaut
			$this->setConfiguration('sambaEtat', "nok"); 		
			$this->setConfiguration('facebookEtat', "nok"); 
			$this->setConfiguration('cheminsonoffdiyMessage', "");
			$dossierLocal=$this->getConfiguration('cheminsonoffdiy');
			if ($dossierLocal =="") $dossierLocal="/../images/"; // par défaut
			$dos=dirname(__FILE__).$dossierLocal; 
			$diapo=glob($dos.'*.jpg');
			$this->setConfiguration('cheminsonoffdiyComplet', realpath($dos)); 
			$this->setConfiguration('localEtat', "ok"); 
			$nbPhotos=count($diapo);
			$this->setConfiguration('nombrePhotos', $nbPhotos);
			$this->setConfiguration('derniereMAJ', date("d-m-Y H:i:s"));
			if ($nbPhotos==0) {
				$this->setConfiguration('cheminsonoffdiyValide', "nok");
				$this->setConfiguration('localEtat', "nok"); 
			}
			else $this->setConfiguration('cheminsonoffdiyValide', "ok");
		} elseif ($this->getConfiguration('stockageSamba')==1)	{
			// On est sur le mode Stockage SAMBA
				if ($this->getConfiguration('sambaEtat') != "ok") {
					if ($this->getConfiguration('cheminsonoffdiyMessage') == "") {
					$this->setConfiguration('cheminsonoffdiyValide', "question");
					$this->setConfiguration('localEtat', "nok"); 
					$this->setConfiguration('sambaEtat', "nok"); 	
					$this->setConfiguration('facebookEtat', "nok"); 	
					$this->setConfiguration('nombrePhotos', "");
					$this->setConfiguration('derniereMAJ', " ");
					$this->setConfiguration('cheminsonoffdiyComplet', "");
					} else {				
					//$this->setConfiguration('cheminsonoffdiyValide', "question");
					//$this->setConfiguration('localEtat', "nok"); 
					$this->setConfiguration('sambaEtat', "nok"); 	
					$this->setConfiguration('cheminsonoffdiyValide', "nok");
					//$this->setConfiguration('facebookEtat', "nok"); 	
					//$this->setConfiguration('nombrePhotos', "");
					//$this->setConfiguration('derniereMAJ', " ");
					//$this->setConfiguration('cheminsonoffdiyComplet', "");
					}
				}
				else {
					$this->setConfiguration('cheminsonoffdiyValide', "ok");
				}
				
		} else {
			// On est sur le mode Recupération FACEBOOK
			$this->setConfiguration('sambaEtat', "nok"); 		
			$this->setConfiguration('localEtat', "nok"); 
			$this->setConfiguration('cheminsonoffdiyMessage', "");
			$TokenFacebook = config::byKey('TokenFacebook', 'sonoffdiy', '0');
			$requete="https://graph.facebook.com/v5.0/me?access_token=".$TokenFacebook;
			log::add('sonoffdiy', 'debug', 'On teste le compte Facebook avec la requète : '.$requete.'***********************************');
			$recupereJson=file_get_contents($requete);
			if(empty($recupereJson)) {log::add('sonoffdiy', 'debug', 'vide');}
			if ($recupereJson=file_get_contents($requete, true)) {
				$json = json_decode($recupereJson,true);
				$this->setConfiguration('cheminsonoffdiyValide', "ok");
				$this->setConfiguration('facebookEtat', "ok"); 		
				$this->setConfiguration('cheminsonoffdiyComplet', "Facebook : Page ".self::Utf8_ansi($json['name'])); 
				$this->setConfiguration('derniereMAJ', date("d-m-Y H:i:s"));
				$requete="https://graph.facebook.com/v5.0/me/albums?fields=count%2Cname%2Ccreated_time&access_token=".$TokenFacebook;	
				log::add('sonoffdiy', 'debug', 'On teste les albums photos Facebook avec la requète : '.$requete.'***********************************');
				if ($recupereJson=file_get_contents($requete, true)) {
					$json = json_decode($recupereJson,true);
					$compteur=0;
					foreach($json['data'] as $item)
					{
						$compteur=$compteur+$item['count'];
						// Enregistrement dans la Config du Plugin
						config::save('albumsFacebook', $json['data'], 'sonoffdiy');
						log::add('sonoffdiy', 'debug', 'On enregistre : '.json_encode($json['data']).' dans plugin/config/albumsFacebook');
						// on va compter le nb de photos des albums cochés 
						// Lecture de arrayAlbumsFacebook dans configuration du device en cours ($device)
						$Albums=$this->getConfiguration('arrayAlbumsFacebook');
						$totalPhotosCochees=0;
						foreach ($json['data'] as $value) {
							foreach ($Albums as $key2 => $value2) {
								if (($value['id'] == $Albums[$key2][0]) && $Albums[$key2][1] == '1') $totalPhotosCochees=$totalPhotosCochees+$value['count'];
							}	
						}
						$this->setConfiguration('nombrePhotos', $compteur." (".$totalPhotosCochees." sélectionnées)");
					}
				}
				else {
					log::add('sonoffdiy', 'debug', '******* Souci dans la requète JSON '.$recupereJson);
					log::add('sonoffdiy', 'debug', '******* Souci dans la requète JSON ');
					$this->setConfiguration('cheminsonoffdiyValide', "nok");
					$this->setConfiguration('facebookEtat', "nok");
					$this->setConfiguration('derniereMAJ', date("d-m-Y H:i:s"));	
					$this->setConfiguration('nombrePhotos', "");			
					$this->setConfiguration('cheminsonoffdiyComplet', "Facebook : Error"); 
				}
			}
		}
	}
}
class sonoffdiyCmd extends cmd {
	public function dontRemoveCmd() {
		if ($this->getLogicalId() == 'refresh') {
			return true;
		}
		return false;
	}
	public function postSave() {
	}
	public function preSave() {
		if ($this->getLogicalId() == 'refresh') {
			return;
		}
	}
	public function execute($_options = null) {
		if ($this->getLogicalId() == 'refresh') {
			$this->getEqLogic()->refresh();
			return;
		}
	}
}