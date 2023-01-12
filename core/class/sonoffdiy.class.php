<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

if (!class_exists('mDNS')) {
	require_once dirname(__FILE__) . '/../../3rdparty/lib/mdns.php';
	//// Fichier récupéré sur https://github.com/ChrisRidings/PHPmDNS

}

// Ressources : https://notenoughtech.com/featured/sonoff-r3-diy-mode/
//http://developers.sonoff.tech/sonoff-diy-mode-api-protocol.html#RESTful-API-Control-Protocol%EF%BC%88HTTP-POST%EF%BC%89

class sonoffdiy extends eqLogic {


	public static function deamon_info() {
		$return = array();
		$return['log'] = '';
		$return['state'] = 'nok';
		$cron = cron::byClassAndFunction('sonoffdiy', 'daemon');
		if (is_object($cron) && $cron->running()) {
			$return['state'] = 'ok';
		}
		$return['launchable'] = 'ok';
		return $return;
	}
	public static function cron() {
		//log::add('sonoffdiy', 'debug', '!!************************** Start cron update sonoffdiy *******************************!!');
        $plugin = plugin::byId('sonoffdiy');
        $eqLogics = eqLogic::byType($plugin->getId());
            foreach ($eqLogics as $eqLogic) {
                if (($eqLogic->getConfiguration('device')=="SPM") && ($eqLogic->getIsEnable())) { // On n'actualise que les SPM
                    //log::add('sonoffdiy', 'debug', 'Refresh automatique (CRON) de ' . $eqLogic->getName());
					log::add('sonoffdiy','info', "╞══════════════════════[Refresh automatique (CRON) de ". $eqLogic->getName()."]═════════════════════════════════════════════════════════");
                    $eqLogic->refresh();
                }
            }
       	
	}
	public static function deamon_start($_debug = false) {
		log::add('sonoffdiy','debug', "╞══════════════════════[Deamon Start]═════════════════════════════════════════════════════════");
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}
		$cron = cron::byClassAndFunction('sonoffdiy', 'daemon');
		if (!is_object($cron)) {
			throw new Exception(__('Cron et Daemon introuvables - réinstaller le plugin', __FILE__));
		}
		$cron->run();
	}

	public static function deamon_stop() {
		log::add('sonoffdiy','debug', "╞══════════════════════[Deamon Stop ]═════════════════════════════════════════════════════════");
		$cron = cron::byClassAndFunction('sonoffdiy', 'daemon');
		if (!is_object($cron)) {
			throw new Exception(__('Cron et Daemon introuvables - réinstaller le plugin', __FILE__));
		}
		$cron->halt();
  
	}
	public static function daemon() { 
		//gc_enable();
		//log::add('sonoffdiy', 'debug', 'Lancement du Daemon mDNS Debug 1');
		log::add('sonoffdiy_mDNS','info', '-----------------------------------------------------------------');

		$port = 6901; // port
        $address = '0.0.0.0';
		$mdns = new mDNS();
		//log::add('sonoffdiy', 'debug', 'Lancement du Daemon mDNS Debug 2');
		
		if (isset($sock) && $sock != NULL) socket_close($sock);
		//log::add('sonoffdiy', 'debug', 'Lancement du Daemon mDNS Debug 3');

        if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
			log::add('sonoffdiy', 'debug', "socket_create() failed: reason: " . socket_strerror(socket_last_error()));
            exit();
        }
        if (!socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1)) {
            log::add('sonoffdiy', 'debug', "socket_set_option() failed: reason: " . socket_strerror(socket_last_error()));
            exit();
        } 
        if (socket_bind($sock, $address, $port) === false) {
           log::add('sonoffdiy', 'debug', "socket_bind() failed: reason: " . socket_strerror(socket_last_error($sock))." port :". $port);
           log::add('sonoffdiy', 'debug', "Un autre PLUGIN utilise déja ce port, désactivez les plugins un par un pour voir lequel est en conflit");
            exit();
        }
        if (socket_listen($sock, 5) === false) {
           log::add('sonoffdiy', 'debug', "socket_listen() failed: reason: " . socket_strerror(socket_last_error($sock)));
        }
        socket_set_nonblock($sock);
		//log::add('sonoffdiy', 'debug', 'Lancement du Daemon mDNS Debug 4');
        // search for connected devices
		$time=0;
		log::add('sonoffdiy_mDNS','info', 'Lancement du Daémon');
		//log::add('sonoffdiy_mDNS','info','Mémoire utilisée :'.round(memory_get_usage()/1000). " ko ".memory_get_usage()%1000 . " o ");
		//$memDep = round(memory_get_usage()/1000);
		while(true) {

			// search for ewelink devices using mDNS
			$inpacket = $mdns->readIncoming();		
			if ($inpacket->packetheader !=NULL){
				$ans = $inpacket->packetheader->getAnswerRRs();
				if ($ans> 0) {
					
				/*	if (substr($inpacket->answerrrs[0]->name, 0, 15) === "MR2200ac-BWQQT0")  { 
						log::add('sonoffdiy_mDNS','info', "");
						log::add('sonoffdiy_mDNS','info', "SYNOSYNOSYNO Une Trame qui nous intéresse de ".$inpacket->answerrrs[0]->name);
						log::add('sonoffdiy_mDNS','info', "Trame mDNS entrante ".json_encode($inpacket));
					}
					*/
					
					
					if ($inpacket->answerrrs[0]->name == "_ewelink._tcp.local") { 
						log::add('sonoffdiy_mDNS','info', "**************** Une Trame mDNS qui nous intéresse de ".$inpacket->answerrrs[0]->name);
						//log::add('sonoffdiy_mDNS','info', "Trame mDNS entrante ".json_encode($inpacket));
						//log::add('sonoffdiy_mDNS','info', "Trame mDNS entrante depuis ".$inpacket->answerrrs[0]->name);
						for ($x=0; $x < sizeof($inpacket->answerrrs); $x++) {
							//log::add('sonoffdiy_mDNS','info', "   x:$x  qtype:".$inpacket->answerrrs[$x]->qtype);
							if ($inpacket->answerrrs[$x]->qtype == 12) {
								$str="";
								for($i=0;$i<sizeof($inpacket->answerrrs[$x]->data);$i++) {
									$caractere=$inpacket->answerrrs[$x]->data[$i];
									if ($caractere>31 && $caractere<127 && $caractere!= 34  && $caractere!= 39 && $caractere!= 92 && $caractere!=96) $str.=chr($caractere);
								}
								if ($inpacket->answerrrs[$x]->name == "_ewelink._tcp.local") {
									$name = "";
									for ($y = 0; $y < sizeof($inpacket->answerrrs[$x]->data); $y++) {
										$name .= chr($inpacket->answerrrs[$x]->data[$y]);
									}
									//log::add('sonoffdiy_mDNS','info',"  | Nom de l'émetteur :".$name);
								}
							}
							if ($inpacket->answerrrs[$x]->qtype == 16) {
								$str="";
								for($i=0;$i<sizeof($inpacket->answerrrs[$x]->data);$i++) {
									$caractere=$inpacket->answerrrs[$x]->data[$i];
									if ($caractere>31 && $caractere<127 && $caractere!= 34  && $caractere!= 39 && $caractere!= 92 && $caractere!=96) $str.=chr($caractere);
								}
								$data = $caractere=$inpacket->answerrrs[$x]->data;
								$sequence="{";
								$offset = 0;
								$size = $data[$offset];
								$str="";
								for ($ls=1; $ls <= $size; $ls++) { 
								  $caractere=$data[$offset+$ls];
								  if ($caractere>31 && $caractere<127 && $caractere!= 34  && $caractere!= 39 && $caractere!= 92 && $caractere!=96) $str.=chr($data[$offset+$ls]); 
								}
								$pos = strpos ( $str , '=');
								$key = substr($str,0,$pos);
								$val = substr($str,$pos+1);
								$sequence.='"'.$key.'":"'.$val.'"';
								$offset = $offset + $size+1;
								while ($data[$offset]<> 0  && sizeof($data)) {
									$size = $data[$offset];
									$str="";
									for ($ls=1; $ls <= $size; $ls++) { 
										$caractere=$data[$offset+$ls];
										if ($caractere>31 && $caractere<127 && $caractere!= 39 && $caractere!= 92 && $caractere!=96) $str.=chr($data[$offset+$ls]); 
									}
									$pos = strpos ( $str , '=');
									$key = substr($str,0,$pos);
									$val = substr($str,$pos+1);
									if ($key!='data1') 	$sequence.=',"'.$key.'":"'.$val.'"';
									else 				$data_data=$val;
									$offset = $offset + $size+1;
								} 
								$sequence.="}";
								/*
								if ($inpacket->answerrrs[$x]->name == "_ewelink._tcp.local") {
									$name = "";
									for ($y = 0; $y < sizeof($inpacket->answerrrs[$x]->data); $y++) {
										$name .= chr($inpacket->answerrrs[$x]->data[$y]);
									}
								}*/
							}/*
							if ($inpacket->answerrrs[$x]->qtype == 33) {
								$d = $inpacket->answerrrs[$x]->data;
								$portm = ($d[4] * 256) + $d[5];
								$offset = 6;
								$size = $d[$offset];
								$offset++;
								$target = "";
								for ($z=0; $z < $size; $z++) {
									$target .= chr($d[$offset + $z]);
								}
								$target .= ".local";
							}*/
							if ($inpacket->answerrrs[$x]->qtype == 1) {
								$d = $inpacket->answerrrs[$x]->data;
								$ip = $d[0] . "." . $d[1] . "." . $d[2] . "." . $d[3];
							}
						}
						
						//$sequence = {"txtvers":"1","id":"1000ab1e93","type":"diy_plug","apivers":"1","seq":"66"}
						//$data_data= {"switch":"on","startup":"on","pulse":"off","sledOnline":"on","pulseWidth":5000,"rssi":-77}

						$sequence_decoded=json_decode($sequence, true);
						$IPetSEQ=$ip.".".$sequence_decoded['seq'];
						if ($IPetSEQ!=$last_IPetSEQ)
						{
						$last_IPetSEQ=$ip.".".$sequence_decoded['seq'];
						$data_data_decoded=json_decode($data_data, true);
						$data_data_decoded['IDdetectee']=$sequence_decoded['id'];
						//log::add('sonoffdiy_mDNS','info',"  | données : ".$data_data);
						//log::add('sonoffdiy','debug',"  | ip : ".$ip);
						log::add('sonoffdiy_mDNS', 'info', '═══════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════');
				
						log::add('sonoffdiy_mDNS','info',"╠═══> séquence : ".$sequence);

						
 						if ((isset($sequence_decoded['type'])) && ($sequence_decoded['type'] =="plug")) {
						log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
						log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
						log::add('sonoffdiy_mDNS','warning',"* un device avec l'ID : ".$sequence_decoded['id']." est bien détecté mais est en mode eWelink, donc non compatible LAN");
						log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
						log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
						} elseif ((isset($sequence_decoded['type'])) && ($sequence_decoded['type'] =="enhanced_plug")) {
						log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
						log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
						log::add('sonoffdiy_mDNS','warning',"* un device avec l'ID : ".$sequence_decoded['id']." est bien détecté mais il n'est pas compatible DIY Mode");
						log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
						log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
						} else {
						self::sauvegardeCmdsInfo($data_data_decoded, true, $sequence_decoded['id'],$ip); // ok fonctionne
						}
							
							
							
							
							
						}
						//else
						//log::add('sonoffdiy_mDNS','info',"  | Trame non traitée identique à la précédente -> ignorée");

					}
					elseif ((substr($inpacket->answerrrs[0]->name, 0, 8) == "eWeLink_") && (substr($inpacket->answerrrs[0]->name, -6) == ".local")) { 
					/*ICI POUR LE SONOFF MINI R3

[2022-01-19 13:43:06]INFO : ╔══════════════════════[ Une Trame eWeLink_ qui nous intéresse de eWeLink_1001439ed1.local]═════════════════════════════════════════════════════════
[2022-01-19 13:43:06]INFO : ╠═══> séquence : {"txtvers":"1","id":"1001439ed1","type":"diy_plug","apivers":"1","seq":"12"}
[2022-01-19 13:43:06]INFO : ╠═══> seq : 12
[2022-01-19 13:43:06]INFO : ╠═══> type : diy_plug
[2022-01-19 13:43:06]INFO : ╠═══> id : 1001439ed1
[2022-01-19 13:43:06]INFO : ╠═══> données : {"configure":[{"startup":"off","outlet":0},{"startup":"off","outlet":1},{"startup":"off","outlet":2},{"startup":"off","outlet":3}],"pulses":[{"pulse":"off","switch":"on","outlet":0,"width":0},{"pulse":"off","switch":"on","outlet":1,"width":0},{"pulse":"off","switch":"on","outlet":2,"width":0},{"pulse":"off","switch":"on","outlet":3,"width":0}],"sledOnline":"on","fwVersion":"1.4.0","switches":[{"switch":"on","outlet":0},{"switch":"off","outlet":1},{"switch":"off","outlet":2},{"switch":"off","outlet":3}],"IDdetectee":"1001439ed1"}
[2022-01-19 13:43:06]INFO : ╠═══> ip : 192.168.1.222
[2022-01-19 13:43:06]INFO : ╠═══> fwVersion : 1.4.0
[2022-01-19 13:43:06]INFO : ╠═ Enregistrement dans MiniR3  de startup : off
[2022-01-19 13:43:06]INFO : ╠═ Enregistrement dans MiniR3  de pulse : off
[2022-01-19 13:43:06]INFO : ╠═ Enregistrement dans MiniR3  de pulseWidth : 0
[2022-01-19 13:43:06]INFO : ╠═ Enregistrement dans MiniR3  de sledOnline : on
[2022-01-19 13:43:06]INFO : ╠═ Enregistrement dans MiniR3  de fwVersion : 1.4.0
[2022-01-19 13:43:06]INFO : ╠═ Enregistrement dans MiniR3  de switch : on
[2022-01-19 13:43:06]INFO : ╠═ Enregistrement dans MiniR3  de IDdetectee : 1001439ed1
[2022-01-19 13:43:06]INFO : ╚═══════════════════════════════════════════════════════════════════════════════════════════════════════*/

					
						//log::add('sonoffdiy_mDNS','info', "╔══════════════════════[ Une Trame eWeLink_ qui nous intéresse de ".$inpacket->answerrrs[0]->name."]═════════════════════════════════════════════════════════");
						//log::add('sonoffdiy_mDNS','info', "=============== Trame mDNS entrante ".json_encode($inpacket));
						//log::add('sonoffdiy_mDNS','info', "Trame mDNS entrante depuis ".$inpacket->answerrrs[0]->name);
						for ($x=0; $x < sizeof($inpacket->answerrrs); $x++) {
						//log::add('sonoffdiy_mDNS','info', "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!");
							//log::add('sonoffdiy_mDNS','info', "   x:$x  qtype:".$inpacket->answerrrs[$x]->qtype);
							if ($inpacket->answerrrs[$x]->qtype == 12) {
								$str="";
								for($i=0;$i<sizeof($inpacket->answerrrs[$x]->data);$i++) {
									$caractere=$inpacket->answerrrs[$x]->data[$i];
									if ($caractere>31 && $caractere<127 && $caractere!= 34  && $caractere!= 39 && $caractere!= 92 && $caractere!=96) $str.=chr($caractere);
								}
									//log::add('sonoffdiy_mDNS','info'," str1 :".$str."-----".json_encode($inpacket->answerrrs[$x]));
								
								if ($inpacket->answerrrs[$x]->name == "_ewelink._tcp.local") {
									$name = "";
									for ($y = 0; $y < sizeof($inpacket->answerrrs[$x]->data); $y++) {
										$name .= chr($inpacket->answerrrs[$x]->data[$y]);
									}
									//log::add('sonoffdiy_mDNS','info',"  | Nom de l'émetteur :".$name);
								}
							}
							if ($inpacket->answerrrs[$x]->qtype == 16) {
								$str="";
								for($i=0;$i<sizeof($inpacket->answerrrs[$x]->data);$i++) {
									$caractere=$inpacket->answerrrs[$x]->data[$i];
									if ($caractere>31 && $caractere<127 && $caractere!= 34  && $caractere!= 39 && $caractere!= 92 && $caractere!=96) $str.=chr($caractere);
								}
								$data = $caractere=$inpacket->answerrrs[$x]->data;
								//log::add('sonoffdiy_mDNS','info',"================ Création de SEQUENCE");
								$sequence="{";
								$offset = 0;
								$size = $data[$offset];
								$str="";
								for ($ls=1; $ls <= $size; $ls++) { 
								  $caractere=$data[$offset+$ls];
								  if ($caractere>31 && $caractere<127 && $caractere!= 34  && $caractere!= 39 && $caractere!= 92 && $caractere!=96) $str.=chr($data[$offset+$ls]); 
								}
								//log::add('sonoffdiy_mDNS','info',"================ str : ".$str);
								
								
								$pos = strpos ( $str , '=');
								$key = substr($str,0,$pos);
								$val = substr($str,$pos+1);
								$sequence.='"'.$key.'":"'.$val.'"';
								log::add('sonoffdiy_mDNS','info',"================ ajout dans SEQUENCE : ".$sequence); //vaut toujours {"txtvers":"1"
								$offset = $offset + $size+1;
								$data_data="";
								while ($data[$offset]<> 0  && sizeof($data)) {
									$size = $data[$offset];
								//log::add('sonoffdiy_mDNS','info',"=========================================== $size : ".$size); 
									$str="";
									for ($ls=1; $ls <= $size; $ls++) { 
										$caractere=$data[$offset+$ls];
										if ($caractere>31 && $caractere<127 && $caractere!= 39 && $caractere!= 92 && $caractere!=96) $str.=chr($data[$offset+$ls]); 
									}
									$pos = strpos ( $str , '=');
									$key = substr($str,0,$pos);
									$val = substr($str,$pos+1);
									//log::add('sonoffdiy_mDNS','info',"================ trouvé clé : ".$key.'":"'.$val.'"--'.substr($key,0,4)); 
									if (substr($key,0,4)!='data') 	$sequence.=',"'.$key.'":"'.$val.'"';  	// si on a une clé/valeur
									else $data_data=$data_data.$val;						//si c'est la partie des data
									$offset = $offset + $size+1;
								} 
								$sequence.="}";
							//	log::add('sonoffdiy_mDNS','info',"================ DATA	 : ".$data_data);
								
								
								//log::add('sonoffdiy_mDNS','info',"================ fin de SEQUENCE : ".$sequence);
								/*
								if ($inpacket->answerrrs[$x]->name == "_ewelink._tcp.local") {
									$name = "";
									for ($y = 0; $y < sizeof($inpacket->answerrrs[$x]->data); $y++) {
										$name .= chr($inpacket->answerrrs[$x]->data[$y]);
									}
								}*/
							}/*
							if ($inpacket->answerrrs[$x]->qtype == 33) {
								$d = $inpacket->answerrrs[$x]->data;
								$portm = ($d[4] * 256) + $d[5];
								$offset = 6;
								$size = $d[$offset];
								$offset++;
								$target = "";
								for ($z=0; $z < $size; $z++) {
									$target .= chr($d[$offset + $z]);
								}
								$target .= ".local";
							}*/
							if ($inpacket->answerrrs[$x]->qtype == 1) {
								$d = $inpacket->answerrrs[$x]->data;
								$ip = $d[0] . "." . $d[1] . "." . $d[2] . "." . $d[3];
							}
						}


						//séquence : {"txtvers":"1","id":"1001439ed1","type":"diy_plug","apivers":"1","seq":"24","data2":"lse":"off","switch":"on","outlet":2,"width":0},{"pulse":"off","switch":"on","outlet":3,"width":0}],"sledOnline":"on","fwVersion":"1.4.0","switches":[{"switch":"on","outlet":0},{"switch":"off","outlet":1},{"switch":"off","outlet":2},{"switch":"off"","data3":","outlet":3}]}"}

						//				{"txtvers":"1","id":"1001439ed1","type":"diy_plug","apivers":"1","seq":"49"}
						//$sequence = 	{"txtvers":"1","id":"1000ab1e93","type":"diy_plug","apivers":"1","seq":"66"}
						//$data_data= {"switch":"on","startup":"on","pulse":"off","sledOnline":"on","pulseWidth":5000,"rssi":-77}
						
						// DATA {
						//"configure":[{"startup":"off","outlet":0},{"startup":"off","outlet":1},{"startup":"off","outlet":2},{"startup":"off","outlet":3}],
						//"pulses":[{"pulse":"off","switch":"on","outlet":0,"width":0},{"pulse":"off","switch":"on","outlet":1,"width":0},{"pulse":"off","switch":"on","outlet":2,"width":0},{"pulse":"off","switch":"on","outlet":3,"width":0}],
						//"sledOnline":"on","fwVersion":"1.4.0",
						//"switches":[{"switch":"off","outlet":0},{"switch":"off","outlet":1},{"switch":"off","outlet":2},{"switch":"off","outlet":3}]}

						$sequence_decoded=json_decode($sequence, true);
						$IPetSEQ=$ip.".".$sequence_decoded['seq'];
						if ($IPetSEQ!=$last_IPetSEQ) // les sequences sont répétées, pour éviter de les relancer
						{
						log::add('sonoffdiy_mDNS','info', "╔══════════════════════[ Une Trame eWeLink_ qui nous intéresse de ".$inpacket->answerrrs[0]->name."]═════════════════════════════════════════════════════════");
						$last_IPetSEQ=$IPetSEQ;
						$data_data_decoded=json_decode($data_data, true);
						$data_data_decoded['IDdetectee']=$sequence_decoded['id'];
						//log::add('sonoffdiy_mDNS','info',"  | données : ".$data_data);
						//log::add('sonoffdiy','debug',"  | ip : ".$ip);
						//log::add('sonoffdiy_mDNS', 'info', $last_IPetSEQ.'═══════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════');
						log::add('sonoffdiy', 'info', "╔══════════════════════[Réception info du device id:".$sequence_decoded['id']."]═════════════════════════════════════════════════════════");
				
						log::add('sonoffdiy_mDNS','info',"╠═══> séquence : ".$sequence);
						log::add('sonoffdiy_mDNS','info',"╠═══> seq : ".$sequence_decoded['seq']);
						log::add('sonoffdiy_mDNS','info',"╠═══> type : ".$sequence_decoded['type']);
						log::add('sonoffdiy_mDNS','info',"╠═══> id : ".$sequence_decoded['id']);
						
 						if ((isset($sequence_decoded['type'])) && ($sequence_decoded['type'] =="plug")) {
							log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
							log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
							log::add('sonoffdiy_mDNS','warning',"* un device avec l'ID : ".$sequence_decoded['id']." est bien détecté mais est en mode eWelink, donc non compatible LAN");
							log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
							log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
						} elseif ((isset($sequence_decoded['type'])) && ($sequence_decoded['type'] =="enhanced_plug")) {
							log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
							log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
							log::add('sonoffdiy_mDNS','warning',"* un device avec l'ID : ".$sequence_decoded['id']." est bien détecté mais il n'est pas compatible DIY Mode");
							log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
							log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
						} else {
							self::sauvegardeCmdsInfo($data_data_decoded, true, $sequence_decoded['id'],$ip); // ok fonctionne
						}
						log::add('sonoffdiy_mDNS', 'info', '╚═══════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════');
						}
						//else
						//log::add('sonoffdiy_mDNS','info',"  | Trame non traitée identique à la précédente -> ignorée");

					}
					elseif ($inpacket->answerrrs[0]->name == "_services._dns-sd._udp.local") { 
					//ICI POUR LE SONOFF SPM
	
					
						//log::add('sonoffdiy_mDNS','info', "╔══════════════════════[ Une Trame SONOFF SPM de ".$inpacket->answerrrs[0]->name."]═════════════════════════════════════════════════════════");
						//log::add('sonoffdiy_mDNS','info', "=============== Trame mDNS entrante ".json_encode($inpacket));
						//log::add('sonoffdiy_mDNS','info', "Trame mDNS entrante depuis ".$inpacket->answerrrs[0]->name);
						for ($x=0; $x < sizeof($inpacket->answerrrs); $x++) {
						//log::add('sonoffdiy_mDNS','info', "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!");
							//log::add('sonoffdiy_mDNS','info', "   x:$x  qtype:".$inpacket->answerrrs[$x]->qtype);
							if ($inpacket->answerrrs[$x]->qtype == 12) {
								$str="";
								for($i=0;$i<sizeof($inpacket->answerrrs[$x]->data);$i++) {
									$caractere=$inpacket->answerrrs[$x]->data[$i];
									if ($caractere>31 && $caractere<127 && $caractere!= 34  && $caractere!= 39 && $caractere!= 92 && $caractere!=96) $str.=chr($caractere);
								}
									//log::add('sonoffdiy_mDNS','info'," str1 :".$str."--!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!---".json_encode($inpacket->answerrrs[$x]));
								
								if ($inpacket->answerrrs[$x]->name == "_ewelink._tcp.local") {
									$name = "";
									for ($y = 0; $y < sizeof($inpacket->answerrrs[$x]->data); $y++) {
										$name .= chr($inpacket->answerrrs[$x]->data[$y]);
									}
									//log::add('sonoffdiy_mDNS','info',"  | Nom de l'émetteur :".$name);
								}
							}
							
							
							
							
							if ($inpacket->answerrrs[$x]->qtype == 16) {
								
								
							// Paquet de données
								$str="";
								//log::add('sonoffdiy_mDNS','info',"==On boucle de 0 à ".sizeof($inpacket->answerrrs[$x]->data));
								//log::add('sonoffdiy_mDNS','info',"==chaine de départ : ".json_encode(json_encode($inpacket->answerrrs[$x]->data)));
								
								for($i=0;$i<sizeof($inpacket->answerrrs[$x]->data);$i++) {
									$caractere=$inpacket->answerrrs[$x]->data[$i];
									if ($caractere>31 && $caractere<127 && $caractere!= 34  && $caractere!= 39 && $caractere!= 92 && $caractere!=96) $str.=chr($caractere);
								}
								$data = $caractere=$inpacket->answerrrs[$x]->data; // c'est la liste de donnéées encodée [48,100,97,116,97,49,61,123,34,115,119,105,116,99,104,101,115,34,58,91,123,34,115,119,105,116,99,104,34,58,34,111,102,102,34,44,34,111,117,116,108,101,116,34,58,48,125,93,125,6,115,101,113,61,52,56,9,97,112,105,118,101,114,115,61,49,14,116,121,112,101,61,100,105,121,95,109,101,116,101,114,27,105,100,61,98,54,52,56,52,54,52,101,51,54,51,53,51,54,49,51,51,53,51,54,51,50,51,56,9,116,120,116,118,101,114,115,61,49]"
								
								
								//log::add('sonoffdiy_mDNS','info',"==data ".json_encode(json_encode($data)));
								
								
								//log::add('sonoffdiy_mDNS','info',"================ Création de SEQUENCE");
								$sequence="{";
								$offset = 0;
								$size = $data[$offset];
								$str="";
								for ($ls=1; $ls <= $size; $ls++) { 
								  $caractere=$data[$offset+$ls];
								  if ($caractere>31 && $caractere<127) $str.=chr($data[$offset+$ls]); 
								 // if ($caractere>31 && $caractere<127 && $caractere!= 34  && $caractere!= 39 && $caractere!= 92 && $caractere!=96) $str.=chr($data[$offset+$ls]); 
								}
								//log::add('sonoffdiy_mDNS','info',"================ str : ".$str); // c'est la chaine décodée issue de data : data1={switches:[{switch:off,outlet:0}]}
								
								//$str="";
								$pos = strpos ( $str , '=');
								$key = substr($str,0,$pos);
								$val = substr($str,$pos+1);
								//$sequence.='"'.$key.'":"'.$val.'"';
								$sequence.='"1":"1"';
								$data_data="";
								
								
								
									if (substr($key,0,4)!='data') 	$sequence.=',"'.$key.'":"'.$val.'"';  			// si on a une clé/valeur
									else 							$data_data=$data_data.$val;						//si c'est la partie des data

							
								//$data_data={"configure":[{"startup":"off","outlet":0},{"startup":"off","outlet":1},{"startup":"off","outlet":2},{"startup":"off","outlet":3}],"pulses":[{"pulse":"off","switch":"on","outlet":0,"width":0},{"pulse":"off","switch":"on","outlet":1,"width":0},{"pulse":"off","switch":"on","outlet":2,"width":0},{"pulse":"off","switch":"on","outlet":3,"width":0}],"sledOnline":"on","fwVersion":"1.4.0","switches":[{"switch":"off","outlet":0},{"switch":"off","outlet":1},{"switch":"off","outlet":2},{"switch":"off","outlet":3}]}

								//DATA	 : {"data1":"{switches:[{switch:on,outlet:0}]}"
								
								//{switches:[{switch:off,outlet:0}]}
								
								
								
								
								//log::add('sonoffdiy_mDNS','info',"================ ajout dans SEQUENCE : ".$sequence); //vaut toujours {"txtvers":"1"
								$offset = $offset + $size+1;
								while ($data[$offset]<> 0  && sizeof($data)) {
								//log::add('sonoffdiy_mDNS','info',"======[$offset]<> 0========================== : ".$offset); 
								//log::add('sonoffdiy_mDNS','info',"======sizeof($data)========================== : ".sizeof($data)); 
									$size = $data[$offset];
								//log::add('sonoffdiy_mDNS','info',"=========================================== $size : ".$size); 
									$str="";
									for ($ls=1; $ls <= $size; $ls++) { 
										$caractere=$data[$offset+$ls];
										if ($caractere>31 && $caractere<127 && $caractere!= 39 && $caractere!= 92 && $caractere!=96) $str.=chr($data[$offset+$ls]); 
									}
								//log::add('sonoffdiy_mDNS','info',"================ str2 : ".$str); // c'est la chaine décodée issue de data : data1={switches:[{switch:off,outlet:0}]}
									
									$pos = strpos ( $str , '=');
									$key = substr($str,0,$pos);
									$val = substr($str,$pos+1);
									//log::add('sonoffdiy_mDNS','info',"================ trouvé clé : ".$key.'":"'.$val); 
									if (substr($key,0,4)!='data') 	$sequence.=',"'.$key.'":"'.$val.'"';  	// si on a une clé/valeur
									else $data_data=$data_data.$val;						//si c'est la partie des data

									
									
									

									
									$offset = $offset + $size+1;
								} 
								$sequence.="}";

								//log::add('sonoffdiy_mDNS','info',"================ fin de SEQUENCE : ".$sequence);
								/*
								if ($inpacket->answerrrs[$x]->name == "_ewelink._tcp.local") {
									$name = "";
									for ($y = 0; $y < sizeof($inpacket->answerrrs[$x]->data); $y++) {
										$name .= chr($inpacket->answerrrs[$x]->data[$y]);
									}
								}*/
							}/*
							if ($inpacket->answerrrs[$x]->qtype == 33) {
								$d = $inpacket->answerrrs[$x]->data;
								$portm = ($d[4] * 256) + $d[5];
								$offset = 6;
								$size = $d[$offset];
								$offset++;
								$target = "";
								for ($z=0; $z < $size; $z++) {
									$target .= chr($d[$offset + $z]);
								}
								$target .= ".local";
							}*/
							if ($inpacket->answerrrs[$x]->qtype == 1) {
								$d = $inpacket->answerrrs[$x]->data;
								$ip = $d[0] . "." . $d[1] . "." . $d[2] . "." . $d[3];
							}
						}


						//séquence : {"txtvers":"1","id":"1001439ed1","type":"diy_plug","apivers":"1","seq":"24","data2":"lse":"off","switch":"on","outlet":2,"width":0},{"pulse":"off","switch":"on","outlet":3,"width":0}],"sledOnline":"on","fwVersion":"1.4.0","switches":[{"switch":"on","outlet":0},{"switch":"off","outlet":1},{"switch":"off","outlet":2},{"switch":"off"","data3":","outlet":3}]}"}

						//				{"txtvers":"1","id":"1001439ed1","type":"diy_plug","apivers":"1","seq":"49"}
						//$sequence = 	{"txtvers":"1","id":"1000ab1e93","type":"diy_plug","apivers":"1","seq":"66"}
						//$data_data= {"switch":"on","startup":"on","pulse":"off","sledOnline":"on","pulseWidth":5000,"rssi":-77}
						
						// DATA {
						//"configure":[{"startup":"off","outlet":0},{"startup":"off","outlet":1},{"startup":"off","outlet":2},{"startup":"off","outlet":3}],
						//"pulses":[{"pulse":"off","switch":"on","outlet":0,"width":0},{"pulse":"off","switch":"on","outlet":1,"width":0},{"pulse":"off","switch":"on","outlet":2,"width":0},{"pulse":"off","switch":"on","outlet":3,"width":0}],
						//"sledOnline":"on","fwVersion":"1.4.0",
						//"switches":[{"switch":"off","outlet":0},{"switch":"off","outlet":1},{"switch":"off","outlet":2},{"switch":"off","outlet":3}]}

						$sequence_decoded=json_decode($sequence, true);
						$IPetSEQ=$ip.".".$sequence_decoded['seq'];
						if ($IPetSEQ!=$last_IPetSEQ) // les sequences sont répétées, pour éviter de les relancer
						{
						//log::add('sonoffdiy_mDNS','info', "╠══════════════════════[ Une Trame eWeLink_ qui nous intéresse de ".$inpacket->answerrrs[0]->name."]═════════════════════════════════════════════════════════");
						log::add('sonoffdiy_mDNS','info', "╔══════════════════════[ Une Trame SONOFF SPM de ".$inpacket->answerrrs[0]->name."]═════════════════════════════════════════════════════════");
						$last_IPetSEQ=$IPetSEQ;
						
						
						
								//log::add('sonoffdiy_mDNS','info',"================ DATA	 : ".$data_data);

								//$data_data_decoded=$data_data;
								$data_data_decoded=json_decode($data_data, true);
						$data_data_decoded['IDdetectee']=$sequence_decoded['id'];
								//log::add('sonoffdiy_mDNS','info',"================ DATA	 : ".$data_data);						
								//log::add('sonoffdiy_mDNS','info',"================ data_data_decoded	 : ".$data_data_decoded);						
						//log::add('sonoffdiy_mDNS','info',"  | données : ".$data_data);
						//log::add('sonoffdiy','debug',"  | ip : ".$ip);
						//log::add('sonoffdiy_mDNS', 'info', $last_IPetSEQ.'═══════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════');
						log::add('sonoffdiy', 'info', "╔══════════════════════[Réception info du device id:".$sequence_decoded['id']."]═════════════════════════════════════════════════════════");
				
						log::add('sonoffdiy_mDNS','info',"╠═══> séquence : ".$sequence);
						log::add('sonoffdiy_mDNS','info',"╠═══> seq : ".$sequence_decoded['seq']);
						log::add('sonoffdiy_mDNS','info',"╠═══> type : ".$sequence_decoded['type']);
						log::add('sonoffdiy_mDNS','info',"╠═══> id : ".$sequence_decoded['id']);
						
 						if ((isset($sequence_decoded['type'])) && ($sequence_decoded['type'] =="plug")) {
							log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
							log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
							log::add('sonoffdiy_mDNS','warning',"* un device avec l'ID : ".$sequence_decoded['id']." est bien détecté mais est en mode eWelink, donc non compatible LAN");
							log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
							log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
						} elseif ((isset($sequence_decoded['type'])) && ($sequence_decoded['type'] =="enhanced_plug")) {
							log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
							log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
							log::add('sonoffdiy_mDNS','warning',"* un device avec l'ID : ".$sequence_decoded['id']." est bien détecté mais il n'est pas compatible DIY Mode");
							log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
							log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
						} else {
							self::sauvegardeCmdsInfo($data_data_decoded, true, $sequence_decoded['id'],$ip); // ok fonctionne
						}
						log::add('sonoffdiy_mDNS', 'info', '╚═══════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════');
						}
						//else
						//log::add('sonoffdiy_mDNS','info',"  | Trame non traitée identique à la précédente -> ignorée");

					}		
					else log::add('sonoffdiy_mDNS','info', "Trame mDNS entrante depuis ".$inpacket->answerrrs[0]->name." -> ignorée");

				}
			}
			

			//log::add('sonoffdiy_mDNS','info', date('h:i:s'));
			usleep(200000);
			//log::add('sonoffdiy_mDNS','info', date('h:i:s'));
			
			[$CompteurMoniteur, $dernierMicrotime] = self::LanceMonitor($CompteurMoniteur, 4,5,$dernierMicrotime); //4 est le max de moniteurs de conso - 5 est la tempo entre deux moniteurs
			
			//log::add('sonoffdiy','info', "╞══════════════════════[Refresh automatique (CRON) de ". $eqLogic->getName()."]═════════════════════════════════════════════════════════");

			
			
			
		}
	}		
	
	public function sauvegardeCmdsInfoBis($LogicalId, $value, $eqLogic) {
		
									$cmd=$eqLogic->getCmd(null, $LogicalId);
									$SubType="string";
									$Unite="";
									$Visible="0";
									$Template="default";
									if ($value===false) $value="0";
									if ($value===true) $value="1";
									
									
									
									
									
									switch (substr($LogicalId, 0, 6)) {
										case "actPow":
											$Visible="1";
											$Template="badge";
											$Unite="W";
											$SubType="numeric";
											$value=intval($value)/100;
											break;										
										case "appare":
											$Unite="VA";
											$SubType="numeric";
											$value=intval($value)/100;
											break;										
										case "curren":
											$Unite="A";
											$SubType="numeric";
											$value=intval($value)/100;
											break;										
										case "reactP":
											$Unite="VAR";
											$SubType="numeric";
											$value=intval($value)/100;
											break;										
										case "voltag":
											$Unite="V";
											$Template="badge";
											$Visible="1";
											$SubType="numeric";
											$value=intval($value)/100;
											break;
									}
										if (!(is_object($cmd))) { //on regarde si la commande ayant le logicalId $LogicalId existe
										// n'existe pas	
										$cmd = new sonoffdiyCmd();
										$cmd->setType('info');
										$cmd->setLogicalId($LogicalId);
										$cmd->setSubType($SubType);
										$cmd->setUnite($Unite);
										$cmd->setEqLogic_id($eqLogic->getId());
										$cmd->setName($LogicalId);
										$cmd->setIsVisible($Visible);
										$cmd->setTemplate('dashboard', $Template);
										$cmd->setOrder(80);
										log::add('sonoffdiy','debug',"║ Ajout de la Commande info : ".$LogicalId);
										}
									$cmd->save();
									log::add('sonoffdiy_mDNS', 'info', '╠═ Enregistrement dans '.$eqLogic->getName().'  de '.$LogicalId.' : '.$value);
									log::add('sonoffdiy', 'debug', '╠═ Enregistrement dans '.$eqLogic->getName().' de '.$LogicalId.' : '.$value);
									$eqLogic->checkAndUpdateCmd($LogicalId, $value);		
	}
		
	
	public function sauvegardeCmdsInfo($_data_decoded, $save, $_ID, $ip) {
		//log::add('sonoffdiy_mDNS','info'," **** Lancement sauvegardeCmdsInfo");
		//log::add('sonoffdiy_mDNS', 'info', 'data:01:: '.$_data_decoded);
		//log::add('sonoffdiy_mDNS', 'info', 'data:02:: '.json_encode($_data_decoded));
		//log::add('sonoffdiy_mDNS', 'info', 'data:03:: '.json_decode($_data_decoded, true));
		
		// ici à reprendre pour créer les commandes automatiquement avec _data_decoded au lieu de boucler autour des commandes existantes


						log::add('sonoffdiy_mDNS','info',"╠═══> données : ".json_encode($_data_decoded));
						log::add('sonoffdiy_mDNS','info',"╠═══> ip : ".$ip);
						log::add('sonoffdiy_mDNS','info',"╠═══> fwVersion : ".$_data_decoded['fwVersion']);

						/*if (is_array($_data_decoded['configure'])) {
						log::add('sonoffdiy_mDNS','info',"  | >> configure : ".json_encode($_data_decoded['configure']));
						}
						if (is_array($_data_decoded['pulses'])) {
						log::add('sonoffdiy_mDNS','info',"  | >> pulses : ".json_encode($_data_decoded['pulses']));
						}						
*/
							
						$cestBonOnaTrouveleDevice=false;
						foreach (eqLogic::byType('sonoffdiy') as $eqLogic){
							//log::add('sonoffdiy_mDNS','info'," ***on test si ".$eqLogic->getConfiguration('device_id')." = ".$_ID);
							//if ((!($eqLogic->getConfiguration('device_id') == $_ID)) && (!($eqLogic->getConfiguration('esclave_id') == $_ID)) && ($_ID!='')) continue;

							/*VB-)*/
                            // ----- S'il s'agit d'un miniR3 ou d'un miniR2 alors ils supportent de ne pas avoir de deviceID dans la 
                            // configuration. Il faut donc ne prendre en compte que le eqLogic id de l'objet concerné. 
                            // Sans impact sur la logic pour les autres objets
                            //log::add('sonoffdiy','debug'," device type ".$this->getConfiguration('device'));
                            //log::add('sonoffdiy','debug'," this ip ".$this->getId());
                            //log::add('sonoffdiy','debug'," eqlogic ip ".$eqLogic->getId());
                            if (   (($this->getConfiguration('device')=="miniR3") || ($this->getConfiguration('device')=="miniR2")) 
                                && ($eqLogic->getId() != $this->getId())) continue;
                                
							if (   ($this->getConfiguration('device')!="miniR3") 
                                && ($this->getConfiguration('device')!="miniR2") 
                                && (!($eqLogic->getConfiguration('device_id') == $_ID)) && (!($eqLogic->getConfiguration('esclave_id') == $_ID)) && ($_ID!='')) continue;
						
							//log::add('sonoffdiy_mDNS','info'," ***ok trouvé ".$_ID);
							$cestBonOnaTrouveleDevice=true;
							foreach ($_data_decoded as $LogicalId => $value){
								
								if ((is_array($value)) && ($LogicalId=='switches')) {
									if (is_array($_data_decoded['switches'])) {
										foreach ($_data_decoded['switches'] as $switches){
											if ($switches['outlet']=="0") self::sauvegardeCmdsInfoBis("switch", $switches['switch'], $eqLogic);// Pour MiniR3 et SPM
											if ($switches['outlet']=="1") self::sauvegardeCmdsInfoBis("switch1", $switches['switch'], $eqLogic);// Pour SPM
											if ($switches['outlet']=="2") self::sauvegardeCmdsInfoBis("switch2", $switches['switch'], $eqLogic);// Pour SPM
											if ($switches['outlet']=="3") self::sauvegardeCmdsInfoBis("switch3", $switches['switch'], $eqLogic);// Pour SPM
										}
									}
								} 
								elseif ((is_array($value)) && ($LogicalId=='subDevList')) {
									if (is_array($_data_decoded['subDevList'])) {
										foreach ($_data_decoded['subDevList'] as $subDevList){
											self::sauvegardeCmdsInfoBis("subDevId", $subDevList['subDevId'], $eqLogic);// on part du principe à ce stade qu'il n'y a qu'un esclave sur le SPM, faudra voir si quelqu'un en a plus qu'un
											$subDevId=$subDevList['subDevId'];
											if (($subDevId!='') && ($eqLogic->getConfiguration('esclave_id')!=$subDevId)) {
												// ICI on va enregistrer automatiquement dans CONFIGURATION l'info ESCLAVE ID
												log::add('sonoffdiy_mDNS', 'info', "╠═══> L'information subDevId n'était pas présente dans la config de ".$eqLogic->getName(). " : ".$subDevId." a été ajoutée.");
												$eqLogic->setConfiguration('esclave_id', $subDevId);
												$eqLogic->save();
											}
										}
									}
								} 
								elseif ((is_array($value)) && ($LogicalId=='configure')) {
									if (is_array($_data_decoded['configure'])) {
										foreach ($_data_decoded['configure'] as $configure){
											if ($configure['outlet']=="0") self::sauvegardeCmdsInfoBis("startup", $configure['startup'], $eqLogic);// on part du principe à ce stade (MiniR3) qu'il n'y a qu'une chaine, la chaine 0 les 3 autres sont ignorés, à voir pour les prochains devices
										}
									}
								} 								
								elseif ((is_array($value)) && ($LogicalId=='pulses')) {
									if (is_array($_data_decoded['pulses'])) {
										foreach ($_data_decoded['pulses'] as $pulses){
											if ($pulses['outlet']=="0") {
												self::sauvegardeCmdsInfoBis("pulse", $pulses['pulse'], $eqLogic);// on part du principe à ce stade (MiniR3) qu'il n'y a qu'une chaine, la chaine 0 les 3 autres sont ignorés, à voir pour les prochains devices
												self::sauvegardeCmdsInfoBis("pulseWidth", $pulses['width'], $eqLogic);// on part du principe à ce stade (MiniR3) qu'il n'y a qu'une chaine, la chaine 0 les 3 autres sont ignorés, à voir pour les prochains devices
/*VB-)*/
   												self::sauvegardeCmdsInfoBis("pulseEndState", $pulses['switch'], $eqLogic);// on part du principe à ce stade (MiniR3) qu'il n'y a qu'une chaine, la chaine 0 les 3 autres sont ignorés, à voir pour les prochains devices
/*VB-)*/
											}
										}
									}
								} 								
								elseif (!is_array($value)) self::sauvegardeCmdsInfoBis($LogicalId, $value, $eqLogic);
								else log::add('sonoffdiy_mDNS','info'," Des données non enregistrées : ".json_encode($LogicalId)." = ".json_encode($value));
							
								
								//$cmd->save(); // ??? doublon non ?? supprimé le 08/01/2022 SIGALOU
							}							
							
							/*
							foreach ($eqLogic->getCmd('info') as $cmd) {
							//log::add('sonoffdiy','debug'," **** Test de la commande : ".$cmd->getName()." (".$cmd->getLogicalId().")");
							//log::add('sonoffdiy','debug'," **** --> ".$_data_decoded[$cmd->getLogicalId()]);
							//log::add('sonoffdiy_mDNS','info'," **** switch : ".$data_data_decoded['switch']);
							//log::add('sonoffdiy_mDNS','info'," **** getLogicalId : ".$cmd->getLogicalId());
							//log::add('sonoffdiy_mDNS','info'," **>>** _data_decoded : ".$data_data_decoded);
							//log::add('sonoffdiy_mDNS','info'," **>>** _data_decoded : ".$data_data_decoded);
							$cmd->enregistreCmdInfo($cmd->getLogicalId(), $_data_decoded, $eqLogic);
							//log::add('sonoffdiy_mDNS','info'," FINI**** getLogicalId : ".$cmd->getLogicalId());
							}*/
							if ($save) $eqLogic->save(); // à voir si on garde c'est que pour actualiser les infos du desktop
						}
						if (!$cestBonOnaTrouveleDevice) {
						log::add('sonoffdiy_mDNS', 'warning', "╔══════════════════════[Il y a un souci dans l'ID d'un des devices]═════════════════════════════════════════════════════════");
						log::add('sonoffdiy_mDNS', 'warning', "║ Il devrait y avoir un device avec l'ID : ".$_ID." || Peut-être le device ayant l'IP ".$ip);
						log::add('sonoffdiy', 'warning', "╔══════════════════════[Il y a un souci dans l'ID d'un des devices]═════════════════════════════════════════════════════════");
						log::add('sonoffdiy', 'warning', "║ Il devrait y avoir un device avec l'ID : ".$_ID." || Peut-être le device ayant l'IP ".$ip);
							foreach (eqLogic::byType('sonoffdiy') as $eqLogic){
								if ($eqLogic->getConfiguration('device_id') != "") continue;
								if (($eqLogic->getConfiguration('adresse_ip') == $ip) && ($eqLogic->getConfiguration('device')!="SPM")) {
									$eqLogic->setConfiguration('device_id', $_ID);
									log::add('sonoffdiy_mDNS', 'warning', "║ Device avec l'IP ".$ip ." et sans Id trouvé, ID ".$_ID."  ajoutée");
									$eqLogic->save();
								}
							}						
						log::add('sonoffdiy', 'warning', '╚═══════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════');
						log::add('sonoffdiy_mDNS', 'warning', '╚═══════════════════════════════════════════════════════════════════════════════════════════════════════════════════════════');
						}
							

	}	
	
	public function LanceMonitor($indice,$max, $tempo, $derniertime) {
						if ((time() - $derniertime)>$tempo) {
							$indice=$indice+1;
							if ($indice>=$max) $indice=0;							
							foreach (eqLogic::byType('sonoffdiy') as $eqLogic){
								if ($eqLogic->getConfiguration('device')=="SPM" && ($eqLogic->getIsEnable())) {
									log::add('sonoffdiy_mDNS','info', "***********************[Lancement monitor".$indice."-".$eqLogic->getName()."]**************************************************");
									$cmd = $eqLogic->getCmd(null, "monitor".$indice);
									if (is_object($cmd)) $cmd->execute();
									
								}
							
							
								//if ($save) $eqLogic->save(); // à voir si on garde c'est que pour actualiser les infos du desktop
							}
						$derniertime = time();
						}
//log::add('sonoffdiy_Conso','info', "envoyé derniertime :".$derniertime);
						
								
return [$indice, $derniertime];							

	}	

	
	public function stopDaemon() {
		log::add('sonoffdiy', 'debug', 'stopDaemon');
		$cron = cron::byClassAndFunction('sonoffdiy', 'daemon');
		$cron->stop();
		$cron->start();
	}
	

	public function refresh() {
		
			//log::add('sonoffdiy', 'info', '>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>--Refresh');
			
			try {
				foreach ($this->getCmd('action') as $cmd) {
							//log::add('sonoffdiy', 'info', 'Test refresh de la commande '.$cmd->getName().'--'.$cmd->getConfiguration('RunWhenRefresh', 0));

					if ($cmd->getConfiguration('RunWhenRefresh', 0) != '1') {
						continue; // si le lancement n'est pas prévu, ça va au bout de la boucle foreach
					}
					//log::add('sonoffdiy', 'info', 'OUI pour '.$cmd->getName());
					$value = $cmd->execute();
					
					
				}
			}
			catch(Exception $exc) {log::add('sonoffdiy', 'error', __('Erreur pour ', __FILE__) . $this->getHumanName() . ' : ' . $exc->getMessage());}
	}
	
	
	public function postSave() {
				

				log::add('sonoffdiy', 'info', '╚═══════════════════════Sauvegarde '.$this->getName().'══════════════════════════════════════════════════════════════════════════════════════');
					
				
				
		$premierSAVE = false;
		$compteurOrderCmd=1;
		$createRefreshCmd = true;

		if ($this->getConfiguration('device')=="miniR3") $R3=true; else $R3=false;

		if ($this->getConfiguration('device')!="") {
	
				$switch = $this->getCmd(null, 'switch');
				if (!is_object($switch)) {
					$switch = new sonoffdiyCmd();
					$switch->setType('info');
					$switch->setLogicalId('switch');
					$switch->setSubType('binary');
					$switch->setEqLogic_id($this->getId());
					$switch->setName('Etat du relais');
					if ($this->getConfiguration('device')=="SPM")	$switch->setName('Etat du relais 0');
					//$switch->setDisplay('title_disable', 1);
					$switch->setIsVisible(1);
					$switch->setOrder($compteurOrderCmd); $compteurOrderCmd++;
					//$switch->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
					//$switch->setDisplay('forceReturnLineBefore', true);
					$switch->save();
				}
				
				if ($this->getConfiguration('device')=="SPM")	{
					// UNIQUEMENT LES COMMANDES SPM
					
					/*$cmd = $this->getCmd(null, "monitor0"); // A utiliser pour réinitialiser les monitorx (pour le dev)
						if (is_object($cmd)) $cmd->remove();
					$cmd = $this->getCmd(null, "monitor1");
						if (is_object($cmd)) $cmd->remove();
					$cmd = $this->getCmd(null, "monitor2");
						if (is_object($cmd)) $cmd->remove();
					$cmd = $this->getCmd(null, "monitor3");
						if (is_object($cmd)) $cmd->remove();*/
					//---- Securité pour supprimer la commande monitor qui est devenue monitor0 monitor1 monitor2... pourra etre supprimée en 2023
					$cmd = $this->getCmd(null, "monitor");
						if (is_object($cmd)) $cmd->remove();
					$cmd = $this->getCmd(null, "ops_mode"); // mode pour retourner en ewelink, ne pas le laisser activé.
						if (is_object($cmd)) $cmd->remove();
						
					//--------------------------------------------------------------------------------------------------
					$R3=true;
					for ($ligne=1; $ligne<4; $ligne++) {
						$switch = $this->getCmd(null, 'switch'.$ligne);
						if (!is_object($switch)) {
							$switch = new sonoffdiyCmd();
							$switch->setType('info');
							$switch->setLogicalId('switch'.$ligne);
							$switch->setSubType('binary');
							$switch->setEqLogic_id($this->getId());
							$switch->setName('Etat du relais '.$ligne);
							//$switch->setDisplay('title_disable', 1);
							$switch->setIsVisible(1);
							$switch->setOrder($compteurOrderCmd); $compteurOrderCmd++;
							//$switch->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
							//$switch->setDisplay('forceReturnLineBefore', true);
							$switch->save();			
						}
					}
					for ($ligne=0; $ligne<4; $ligne++) {
						$cmd = $this->getCmd(null, 'On'.$ligne);
						if (!is_object($cmd)) {
							$premierSAVE = true; // ? peut etre plus utile...
							$cmd = new sonoffdiyCmd();
							$cmd->setType('action');
							$cmd->setLogicalId('On'.$ligne);
							$cmd->setSubType('other');
							$cmd->setEqLogic_id($this->getId());
							$cmd->setName('On '.$ligne);
							$cmd->setConfiguration('request', 'switches?command=on&outlet='.$ligne);
							$cmd->setConfiguration('expliq', 'Allumer');
							$cmd->setDisplay('title_disable', 1);
							$cmd->setOrder($compteurOrderCmd); $compteurOrderCmd++;
							$cmd->setValue($switch->getId());
							$cmd->setDisplay('icon', '<i class="icon_green icon fas fa-check"></i>');
							$cmd->setIsVisible(1);
							$cmd->save();
						}				
						$cmd = $this->getCmd(null, 'Off'.$ligne);
						if (!is_object($cmd)) {
							$premierSAVE = true;// ? peut etre plus utile...
							$cmd = new sonoffdiyCmd();
							$cmd->setType('action');
							$cmd->setLogicalId('Off'.$ligne);
							$cmd->setSubType('other');
							$cmd->setEqLogic_id($this->getId());
							$cmd->setName('Off '.$ligne);
							$cmd->setConfiguration('request', 'switches?command=off&outlet='.$ligne);
							$cmd->setConfiguration('expliq', 'Eteindre');
							$cmd->setDisplay('title_disable', 1);
							$cmd->setOrder($compteurOrderCmd); $compteurOrderCmd++;
							$cmd->setValue($switch->getId());
							//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
							$cmd->setIsVisible(1);
							$cmd->setDisplay('icon', '<i class="icon_red icon fas fa-times"></i>');
							$cmd->save();
						}
						$cmd = $this->getCmd(null, 'monitor'.$ligne);
						if (!is_object($cmd)) {
							$cmd = new sonoffdiyCmd();
							$cmd->setType('action');
							$cmd->setLogicalId('monitor'.$ligne);
							$cmd->setSubType('other');
							$cmd->setEqLogic_id($this->getId());
							$cmd->setName('Lancer Temps réel '.$ligne);
							$cmd->setConfiguration('request', 'monitor?outlet='.$ligne);
							$cmd->setDisplay('title_disable', 1);
							$cmd->setOrder($compteurOrderCmd); $compteurOrderCmd++;
							$cmd->setConfiguration('RunWhenRefresh', 0);				
							//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
							$cmd->setIsVisible(0);
							$cmd->save();
						}						
					}
					$cmd = $this->getCmd(null, 'subDevList');
					if (!is_object($cmd)) {
						$cmd = new sonoffdiyCmd();
						$cmd->setType('action');
						$cmd->setLogicalId('subDevList');
						$cmd->setSubType('other');
						$cmd->setEqLogic_id($this->getId());
						$cmd->setName('subDevList');
						$cmd->setConfiguration('request', 'subDevList');
						$cmd->setDisplay('title_disable', 1);
						$cmd->setConfiguration('RunWhenRefresh', 1);				
						//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
						$cmd->setIsVisible(0);
						$cmd->save();
					}
					$cmd = $this->getCmd(null, 'getState');
					if (!is_object($cmd)) {
						$cmd = new sonoffdiyCmd();
						$cmd->setType('action');
						$cmd->setLogicalId('getState');
						$cmd->setSubType('other');
						$cmd->setEqLogic_id($this->getId());
						$cmd->setName('getState');
						$cmd->setConfiguration('request', 'getState');
						$cmd->setDisplay('title_disable', 1);
						$cmd->setConfiguration('RunWhenRefresh', 1);				
						//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
						$cmd->setIsVisible(0);
						$cmd->save();
					}				
					$cmd = $this->getCmd(null, 'getStateEsclave');
					if (!is_object($cmd)) {
						$cmd = new sonoffdiyCmd();
						$cmd->setType('action');
						$cmd->setLogicalId('getStateEsclave');
						$cmd->setSubType('other');
						$cmd->setEqLogic_id($this->getId());
						$cmd->setName('getStateEsclave');
						$cmd->setConfiguration('request', 'getState?getStateEsclave=getStateEsclave'); // pour récupérer getStateEsclave  dans value au moment de l'envoi de la commande
						$cmd->setDisplay('title_disable', 1);
						$cmd->setConfiguration('RunWhenRefresh', 1);				
						//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
						$cmd->setIsVisible(0);
						$cmd->save();
					}						
					// Commande qui permet de repasser en mode eWelink, désactivée car trop dangereuse pour les utilisateurs
					/*$cmd = $this->getCmd(null, 'ops_mode');
					if (!is_object($cmd)) {
						$cmd = new sonoffdiyCmd();
						$cmd->setType('action');
						$cmd->setLogicalId('ops_mode');
						$cmd->setSubType('other');
						$cmd->setEqLogic_id($this->getId());
						$cmd->setName('Repasser en eWelink');
						$cmd->setConfiguration('expliq', 'Repasser en eWelink');
						$cmd->setConfiguration('request', 'ops_mode');
						//$cmd->setDisplay('title_disable', 1);
						//$cmd->setConfiguration('RunWhenRefresh', 1);				
						//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
						$cmd->setIsVisible(0);
						$cmd->save();
					}	*/				
				} else {
					//UNIQUEMENT LES NON SPM
						$cmd = $this->getCmd(null, 'Info');
						if (!is_object($cmd)) {
							$cmd = new sonoffdiyCmd();
							$cmd->setType('action');
							$cmd->setLogicalId('Info');
							$cmd->setSubType('other');
							$cmd->setEqLogic_id($this->getId());
							$cmd->setName('Info');
							$cmd->setConfiguration('request', 'info');
							$cmd->setDisplay('title_disable', 1);
							$cmd->setConfiguration('RunWhenRefresh', 1);				
							//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
							$cmd->setIsVisible(0);
							$cmd->save();		
						}
						$cmd = $this->getCmd(null, 'signal_strength');
						if (!is_object($cmd)) {
							$cmd = new sonoffdiyCmd();
							$cmd->setType('action');
							$cmd->setLogicalId('signal_strength');
							$cmd->setSubType('message');
							$cmd->setEqLogic_id($this->getId());
							$cmd->setName('Signal Action');
							$cmd->setConfiguration('request', 'signal_strength');
							$cmd->setConfiguration('expliq', 'Détecte la force du signal Wifi');
							$cmd->setConfiguration('RunWhenRefresh', 1);				
							//$cmd->setConfiguration('infoName', $signalinfo->getId());
							$cmd->setIsVisible(0);
							$cmd->save();						
						}
						$cmd = $this->getCmd(null, 'On');
						if (!is_object($cmd)) {
							$premierSAVE = true;
							$cmd = new sonoffdiyCmd();
							$cmd->setType('action');
							$cmd->setLogicalId('On');
							$cmd->setSubType('other');
							$cmd->setEqLogic_id($this->getId());
							$cmd->setName('On');
							if ($R3)
								$cmd->setConfiguration('request', 'switches?command=on&outlet=0');
							else
								$cmd->setConfiguration('request', 'switch?command=on');
							$cmd->setConfiguration('expliq', 'Allumer');
							$cmd->setDisplay('title_disable', 1);
							$cmd->setOrder($compteurOrderCmd); $compteurOrderCmd++;
							$cmd->setValue($switch->getId());
							$cmd->setDisplay('icon', '<i class="icon_green icon fas fa-check"></i>');
							$cmd->setIsVisible(1);
							$cmd->save();
						}				
						$cmd = $this->getCmd(null, 'Off');
						if (!is_object($cmd)) {
							$premierSAVE = true;
							$cmd = new sonoffdiyCmd();
							$cmd->setType('action');
							$cmd->setLogicalId('Off');
							$cmd->setSubType('other');
							$cmd->setEqLogic_id($this->getId());
							$cmd->setName('Off');
							if ($R3)
								$cmd->setConfiguration('request', 'switches?command=off&outlet=0');
							else
								$cmd->setConfiguration('request', 'switch?command=off');
							$cmd->setConfiguration('expliq', 'Eteindre');
							$cmd->setDisplay('title_disable', 1);
							$cmd->setOrder($compteurOrderCmd); $compteurOrderCmd++;
							$cmd->setValue($switch->getId());
							//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
							$cmd->setIsVisible(1);
							$cmd->setDisplay('icon', '<i class="icon_red icon fas fa-times"></i>');
							$cmd->save();
						}				
                        
                        
    
					}


					$cmd = $this->getCmd(null, 'PulseOff');
					if (!is_object($cmd)) {
						$cmd = new sonoffdiyCmd();
						$cmd->setType('action');
						$cmd->setLogicalId('PulseOff');
						$cmd->setSubType('other');
						$cmd->setEqLogic_id($this->getId());
						$cmd->setName('Pulse Off');
						//$cmd->setConfiguration('parameter', '5000');					
    					if ($R3)
    						$cmd->setConfiguration('request', 'pulses?command=off&outlet=0');
                        else
    						$cmd->setConfiguration('request', 'pulse?command=off');
						$cmd->setConfiguration('expliq', 'Désactive le mode Pulse');
						$cmd->setDisplay('title_disable', 1);
						$cmd->setOrder($compteurOrderCmd); $compteurOrderCmd++;
						//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
						$cmd->setIsVisible(0);
						$cmd->save();
					}

					
					$cmd = $this->getCmd(null, 'PulseOn');
					if (!is_object($cmd)) {
						$cmd = new sonoffdiyCmd();
						$cmd->setType('action');
						$cmd->setLogicalId('PulseOn');
						$cmd->setSubType('message');
						$cmd->setEqLogic_id($this->getId());
						$cmd->setName('Pulse On');
						$cmd->setConfiguration('parameter', '5000');
    					if ($R3)
    						$cmd->setConfiguration('request', 'pulses?command=on&outlet=0');
                        else
    						$cmd->setConfiguration('request', 'pulse?command=on');
						$cmd->setConfiguration('expliq', 'Active le mode Pulse et fixe la tempo en ms (multiple de 500ms)');
						$cmd->setDisplay('title_disable', 1);
						$cmd->setOrder($compteurOrderCmd); $compteurOrderCmd++;
						//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
						$cmd->setIsVisible(0);
						$cmd->save();
					}

    				$cmd = $this->getCmd(null, 'startup_action'); // 
    				if (!is_object($cmd)) {
    					$cmd = new sonoffdiyCmd();
    					$cmd->setType('action');
    					$cmd->setLogicalId('startup_action');
    					$cmd->setSubType('select');
    					$cmd->setEqLogic_id($this->getId());
    					$cmd->setName('Etat initial');					
    					if ($R3)
    					      $cmd->setConfiguration('request', 'startups?state=#select#&outlet=0');
                          else
    					      $cmd->setConfiguration('request', 'startup?state=#select#');
    					$cmd->setConfiguration('listValue', 'on|on;off|off;stay|stay');
    					$cmd->setConfiguration('expliq', "Définir l'état à la mise sous tension");
    					$cmd->setDisplay('title_disable', 1);
    					$cmd->setOrder($compteurOrderCmd); $compteurOrderCmd++;
    					//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
    					$cmd->setIsVisible(0);
    					$cmd->save();
    				}
    
    				$cmd = $this->getCmd(null, 'startup');
    				if (!is_object($cmd)) {
    					$cmd = new sonoffdiyCmd();
    					$cmd->setType('info');
    					$cmd->setLogicalId('startup');
      					$cmd->setSubType('string');        
    					$cmd->setEqLogic_id($this->getId());
    					$cmd->setName('Etat à la mise sous tension');
    					$cmd->setIsVisible(0);
    					$cmd->setOrder($compteurOrderCmd); $compteurOrderCmd++;
    					//$cmd->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
    					//$cmd->setDisplay('forceReturnLineBefore', true);
    					$cmd->save();
    				}
                  					
					$cmd = $this->getCmd(null, 'pulse');
					if (!is_object($cmd)) {
						$cmd = new sonoffdiyCmd();
						$cmd->setType('info');
						$cmd->setLogicalId('pulse');
						$cmd->setSubType('binary');
						$cmd->setEqLogic_id($this->getId());
						$cmd->setName('Etat de la fonction Pulse');
						$cmd->setIsVisible(0);
						$cmd->setOrder($compteurOrderCmd); $compteurOrderCmd++;
						//$cmd->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
						//$cmd->setDisplay('forceReturnLineBefore', true);
						$cmd->save();
					}
					
					
					$cmd = $this->getCmd(null, 'pulseWidth');
					if (!is_object($cmd)) {
						$cmd = new sonoffdiyCmd();
						$cmd->setType('info');
						$cmd->setLogicalId('pulseWidth');
						$cmd->setSubType('string');
						$cmd->setEqLogic_id($this->getId());
						$cmd->setName('Tempo de la fonction Pulse');
						$cmd->setIsVisible(0);
						$cmd->setOrder($compteurOrderCmd); $compteurOrderCmd++;
						//$cmd->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
						//$cmd->setDisplay('forceReturnLineBefore', true);
						$cmd->save();
					}
                    
                    if ($R3) {
    					$cmd = $this->getCmd(null, 'pulseEndState');
    					if (!is_object($cmd)) {
    						$cmd = new sonoffdiyCmd();
    						$cmd->setType('info');
    						$cmd->setLogicalId('pulseEndState');
    						$cmd->setSubType('string');
    						$cmd->setEqLogic_id($this->getId());
    						$cmd->setName('Etat à la fin du Pulse');
    						$cmd->setIsVisible(0);
    						$cmd->setOrder($compteurOrderCmd); $compteurOrderCmd++;
    						//$cmd->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
    						//$cmd->setDisplay('forceReturnLineBefore', true);
    						$cmd->save();
    					}
                    }
					
/*VB-)*/                    
				if (!$R3) {
					$cmd = $this->getCmd(null, 'ssid');
					if (!is_object($cmd)) {
						$cmd = new sonoffdiyCmd();
						$cmd->setType('info');
						$cmd->setLogicalId('ssid');
						$cmd->setSubType('string');
						$cmd->setEqLogic_id($this->getId());
						$cmd->setName('SSID');
						$cmd->setIsVisible(0);
						$cmd->setOrder($compteurOrderCmd); $compteurOrderCmd++;
						//$cmd->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
						//$cmd->setDisplay('forceReturnLineBefore', true);
						$cmd->save();				
					}
				}
								
				$cmd = $this->getCmd(null, 'rssi');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('info');
					$cmd->setLogicalId('rssi');
					$cmd->setSubType('string');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('RSSI');
					$cmd->setIsVisible(0);
					$cmd->setOrder($compteurOrderCmd); $compteurOrderCmd++;
					//$cmd->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
					//$cmd->setDisplay('forceReturnLineBefore', true);
					$cmd->save();
				}
				
/*VB-)*/                    
				if (!$R3) {
				$cmd = $this->getCmd(null, 'IDdetectee');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('info');
					$cmd->setLogicalId('IDdetectee');
					$cmd->setSubType('string');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('ID');
					if ($this->getConfiguration('device')=="SPM")	$cmd->setName('ID Esclave');
					$cmd->setIsVisible(0);
					$cmd->setOrder($compteurOrderCmd); $compteurOrderCmd++;
					//$cmd->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
					//$cmd->setDisplay('forceReturnLineBefore', true);
					$cmd->save();	
				}
				}
				
	
				
				/* ne fonctionne pas avec Mini R3
				$cmd = $this->getCmd(null, 'getState');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('action');
					$cmd->setLogicalId('getState');
					$cmd->setSubType('message');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('getState');
					$cmd->setConfiguration('request', 'getState');
					$cmd->setConfiguration('expliq', 'getState');
					$cmd->setConfiguration('RunWhenRefresh', 1);				
					//$cmd->setConfiguration('infoName', $signalinfo->getId());
					$cmd->setIsVisible(0);
				}
				$cmd->save();	*/
				
				/* ca va pas, on a pas l'ip
				if ($premierSAVE) {
				log::add('sonoffdiy', 'debug', '*********PREMIERSAVE***********');
				$cmd = $this->getCmd(null, 'refresh');
				if (!is_object($cmd)) $cmd->execCmd();
				}*/
				
			
/*
			//$signalinfo = $this->getCmd(null, 'signal');
			$signalaction = $this->getCmd(null, 'signal_strength');
					if((is_object($signalinfo)) && (is_object($signalaction))) {
					$signalaction->setValue($signalinfo->getId());// Lien 
					$signalaction->save();
				log::add('sonoffdiy', 'debug', '***lien:signalaction/signalinfo');
				}
*/


	//	log::add('alexaamazonmusic', 'info', ' ╚══════════════════════════════════════════════════════════════════════════════════════════════════════════');
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
					$refresh->setName(__('Rafraichir', __FILE__));
				}
				$refresh->setType('action');
				$refresh->setSubType('other');
				$refresh->setEqLogic_id($this->getId());
				$refresh->setOrder(10);
				$refresh->setConfiguration('expliq', 'Rafraîchit manuellement toutes les données du device');
				$refresh->save();
			}
		}
	}
	
	public function preUpdate() {
	}
	
	public function preRemove () {
	}
	
	public function preSave() {
		
			
			//log::add('sonoffdiy', 'debug', 'Presave '.$this->getName());
			try {
				foreach ($this->getCmd('info') as $cmd) {
					//log::add('sonoffdiy', 'info', '--------->Test CONF de la commande '.$cmd->getName().'--'.$cmd->execCmd());
					$this->setConfiguration($cmd->getLogicalId(), self::chiffreenOnOff($cmd->execCmd()));
				}
				$this->setConfiguration('LastMAJ', date('Y-m-d H:i:s'));
			}
			catch(Exception $exc) {log::add('sonoffdiy', 'error', __('Erreur pour ', __FILE__) . $this->getHumanName() . ' : ' . $exc->getMessage());}
		

	}
	
	public function chiffreenOnOff($unouzero) {
		// transforme 1 en On et 0 en Off
		if ($unouzero == '1') $unouzero="On";
		if ($unouzero == '0') $unouzero="Off";
		return $unouzero;
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
		
		// Enregistre dans configuration/value les valeurs pour pouvoir les afficher sur les ecrans du desktop et des commandes
		if ($this->getType() == 'info')	$this->setConfiguration('value', $this->execCmd());
	
		if ($this->getLogicalId() == 'refresh') {
			return;
			
		}
	}
	
	public function execute($_options = null) {
		$eqLogic = $this->getEqLogic();
		//log::add('sonoffdiy', 'debug', 'Execute '.$this->getLogicalId());
		//log::add('sonoffdiy', 'info', 'options : ' . json_encode($_options));//Request : http://192.168.0.21:3456/volume?value=50&device=G090LF118173117U
		
		if ($this->getLogicalId() == 'refresh') {
		//log::add('sonoffdiy', 'debug', 'Faut Execute1 '.$this->getLogicalId());
			$this->getEqLogic()->refresh();
		//log::add('sonoffdiy', 'debug', 'Faut Execute2 '.$this->getLogicalId());
			return;
		}
		
		$adresse_ip = $this->getEqLogic()->getConfiguration('adresse_ip');
		//log::add('sonoffdiy', 'debug', '----adresse_ip:'.$adresse_ip);
		$device_id = $this->getEqLogic()->getConfiguration('device_id');
		$esclave_id = $this->getEqLogic()->getConfiguration('esclave_id');
		if ($esclave_id=='') $esclave_id="123456";
		//log::add('sonoffdiy', 'debug', '----device_id:'.$device_id);
	if ($this->getType() != 'action') return $this->getConfiguration('request');
	list($command, $arguments) = explode('?', $this->getConfiguration('request'), 2);
	list($argument1, $argument2) = explode('&', $arguments, 2);
	list($variable, $valeur) = explode('=', $argument1, 2);
	list($variable2, $outlet) = explode('=', $argument2, 2);
	if ($outlet=="") $outlet=$valeur; // dans le cas ou la commande ne comporte pas de valeur mais uniquement outlet comme par exemple Monitor
	$outlet=str_replace('"', '', $outlet); 
	$parameter=(int)$this->getConfiguration('parameter');
	//log::add('sonoffdiy', 'info', '----variable:*'.$variable.'* valeur:'.$valeur.'* outlet:'.$outlet);
	//log::add('sonoffdiy', 'info', '----Command:*'.$command.'* arguments:'.$arguments);
	//log::add('sonoffdiy', 'info', '----Options:*'.json_encode($_options));
	//log::add('sonoffdiy', 'info', '----$_optionsselect:'.$_options['select']);
	//log::add('sonoffdiy', 'info', '----parameter:*'.$parameter);
	if ((isset($_options['select'])) && ($_options['select'] != '')) $valeur=$_options['select']; // Pour Etat Initial
	//if ((isset($_options['select'])) && ($_options['select'] != '')) log::add('sonoffdiy', 'info', '*****************************************************************************');
	//if ((isset($_options['message'])) && ($_options['message'] != '')) $parameter=$_options['message']; // pour Pulse ON
	if (($command=="startup") && (isset($_options['select'])) && ($_options['select'] != '') && ($valeur == '')) $valeur="stay";
/*VB-)*/
	if (($command=="startups") && (isset($_options['select'])) && ($_options['select'] != '') && ($valeur == '')) $valeur="stay";
/*VB-)*/
	if (($command=="pulse") && ($valeur=="off")) $parameter="123";
	
	// Rustine pour corriger l'erreur de $cmd->setConfiguration('listValue', 'on|on; off|off; stay|stay'); (espace en trop avant Off et Stay)
	// Ajouté le 27/07/2020 pourra être supprimé dans quelques années
	if ($valeur==" off") $valeur="off";
	if ($valeur==" stay") $valeur="stay";


			$url = "http://".$adresse_ip.":8081/zeroconf/".$command; // Envoyer la commande Refresh via jeeAlexaapi
			$ch = curl_init($url);
			
			
			if ($command=="switches")	{		
				$switches=[
					[
						"switch" => $valeur,
						"outlet" => intval($outlet)
					]
				];	
				$data = array(
				'deviceid'        => $device_id,
				'data'    => array(
					'subDevId'      => $esclave_id,
					'switches'    => $switches
					
					)
				);	
			}

			if ($command=="iAmHere") //non utilisé mais dans la doc	du SPM	http://developers.sonoff.tech/spm-main-http-api.html#I-Am-Here
			
				$data = array(
				'deviceid'        => $device_id,
				'data'    => array(
					'subDevId'      => $esclave_id
										
				)
			);	
			
			if ($command=="switch")			
			$data = array(
				'deviceid'        => $device_id,
				'data'    => array(
					'switch'      => $valeur
				)
			);
			
			if ($command=="monitor")			
			$data = array(
				'deviceid'        => $device_id,
				'data'    => array(
//					'url'      => "http://192.168.1.21",
					'url'      => network::getNetworkAccess('internal'),
					'port'      => 5353,
					'subDevId'      => $esclave_id,
					'outlet'      => intval($outlet),
					'time'      => 5 
				)
			);		
			
			if ($command=="startup")			
			$data = array(
				'deviceid'        => $device_id,
				'data'    => array(
					'startup'      => $valeur
				)
			);				

/*VB-)*/
			if ($command=="startups")	{		
                // ----- On doit indiquer les 4 outlets sinon la commande est refusée (contrairement à switches)
				$configure=[
					[
						"startup" => $valeur,
						"outlet" => intval($outlet)
					],
					[
						"startup" => "off",
						"outlet" => 1
					],
					[
						"startup" => "off",
						"outlet" => 2
					],
					[
						"startup" => "off",
						"outlet" => 3
					]
				];	
				$data = array(
				'deviceid'        => $device_id,
				'data'    => array(
					'configure'    => $configure					
					)
				);	
			}
/*VB-)*/

			if ($command=="ops_mode")			
			$data = array(
				'deviceid'        => $device_id,
				'data'    => array(
					'ops_mode'      => 'ewelink'
				)
			);	
		
			if ($command=="pulse")	
				
				//if ($parameter>500)
				if($parameter % 500 == 0)					
					$data = array(
						'deviceid'        => $device_id,
						'data'    => array(
							'pulse'      => $valeur,
							'pulseWidth'      => $parameter,
						)
					);	
				 else 					
					$data = array(
						'deviceid'        => $device_id,
						'data'    => array(
							'pulse'      => $valeur
						)
					);
					
/*VB-)*/
			if ($command=="pulses") {
                // ----- width doit être multiple de 500ms et entre 500 et 3599500
                if (($parameter < 500) || ($parameter > 3599500) || ($parameter % 500 != 0)) {
                  $parameter = 5000;
                }
                // ----- On doit indiquer les 4 outlets sinon la commande est refusée (contrairement à switches)
				$pulses=[
					[
                        "pulse" => $valeur,
                        "switch" => "off",
                        "width" => $parameter,
                        "outlet" => intval($outlet)
					],
					[
                        "pulse" => "off",
                        "switch" => "off",
                        "width" => 2000,
                        "outlet" => 1
					],
					[
                        "pulse" => "off",
                        "switch" => "off",
                        "width" => 2000,
                        "outlet" => 2
					],
					[
                        "pulse" => "off",
                        "switch" => "off",
                        "width" => 2000,
                        "outlet" => 3
					]
				];	
				$data = array(
				'deviceid'        => $device_id,
				'data'    => array(
					'pulses'    => $pulses					
					)
				);	
            }
/*VB-)*/
                    
                    
			$vide = (object)[];
			if (($command=="signal_strength") || ($command=="subDevList") || ($command=="info") || ($command=="getState"))		
				$data = array(
					'deviceid'        => $device_id,
					'data'    => $vide
				);	
			if ($command=="getState") {
				if ($valeur=="getStateEsclave") 
					$data = array(
						'deviceid'        => $device_id,
						'data'    => array(
							'subDevId'      => $esclave_id
						)
					);
					else
					$data = array(
						'deviceid'        => $device_id,
						'data'    => $vide
					);		
			}
			$payload = json_encode($data);
		log::add('sonoffdiy', 'info', ' ');
		log::add('sonoffdiy', 'info', '╔══════════════════════[Envoi '.$command.' sur '.$eqLogic->getName().']═════════════════════════════════════════════════════════');
		log::add('sonoffdiy', 'info', '╠═══> de '.$url." ".$payload);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
			//curl_setopt($ch, CURLOPT_HEADER, true); //TRUE pour inclure l'en-tête dans la valeur de retour
			$result = json_decode(curl_exec($ch),true);
			curl_close($ch);
			
		if (is_null($result)) {
			log::add('sonoffdiy', 'warning', '║ ******** Souci sur la commande '.$this->getName().' de '.$eqLogic->getName().' ********');
			return false;
		}
		log::add('sonoffdiy', 'info', '║ ════envoi══> '.$url." ".$payload);
		log::add('sonoffdiy', 'info', '║ <══réponse═  '.json_encode($result));
		//log::add('sonoffdiy', 'debug', '<< Data recues de '.$eqLogic->getName().' : '.$result['data']);
		//log::add('sonoffdiy', 'debug', '<< error recue de '.$eqLogic->getName().' : '.$result['error']);
		$nberror=$result['error'];
		if ($nberror!="0") {
				switch ($nberror) {
			case 400:
				$txterror="L'opération a échoué et la demande n'a pas été formatée correctement. Le corps de la requête n'est pas un format JSON valide.";
				break;
			case 401:
				$txterror="L'opération a échoué et la demande n'a pas été autorisée. Le chiffrement des informations sur l'appareil est activé sur l'appareil, mais la demande n'est pas chiffrée.";
				break;
			case 404:
				$txterror="L'opération a échoué et le périphérique n'existe pas. L'appareil ne prend pas en charge l'ID d'appareil demandé.";
				break;
			case 422:
				$txterror="L'opération a échoué et les paramètres de la demande ne sont pas valides. Par exemple, l'appareil ne prend pas en charge la définition d'informations spécifiques sur l'appareil.";
				break;			
				}
			log::add('sonoffdiy', 'warning', '║ ******** Souci sur la commande '.$this->getName().' de '.$eqLogic->getName().' Error N°'.$result['error'].' '.$txterror.'********');
			// Pour avoir les codes erreur : https://github.com/itead/Sonoff_Devices_DIY_Tools/blob/master/SONOFF%20DIY%20MODE%20Protocol%20Doc%20v1.4.md
		}

		//$_id=$eqLogic->getConfiguration('device_id');

/*VB-)*/
        // ----- Mise à jour des états car pas d'erreur de retour donc normalement le status a été appliqué
        if ($nberror=="0") {
          if (($command == 'switch') || ($command == 'switches')) {
            $eqLogic->checkAndUpdateCmd('switch', ($valeur=='on'?1:0));
          }
          if (($command == 'startup') || ($command == 'startups')) {
            $eqLogic->checkAndUpdateCmd('startup', $valeur);
          }
        }
/*VB-)*/

		
		
		if (isset($result['data'])) {
			//log::add('sonoffdiy', 'debug', 'Lancement sauvegardeCmdsInfo avec eqLogic -->'.json_encode($result['data']));
			//$eqLogic->sauvegardeCmdsInfo(json_decode($result['data'], true), false, $eqLogic->getConfiguration('device_id'), ""); //modif SIGALOU 08/01/2022 ?? souci 
			$eqLogic->sauvegardeCmdsInfo($result['data'], false, $eqLogic->getConfiguration('device_id'), "");
		}
		
		log::add('sonoffdiy', 'info', '╚══════════════════════════════════════════════════════════════════════════════════════════════════════════');


		return true;
	}
		public function enregistreCmdInfo($_CmdInfo, $_Data, $_eqLogic) {
			//$_Data=json_decode($_Data, true);
			//log::add('sonoffdiy', 'debug', 'debut  enregistreCmdInfo :'.$_CmdInfo." ---> ".$_Data[$_CmdInfo]);
			//log::add('sonoffdiy', 'debug', 'ddddd  enregistreCmdInfo :'.$_CmdInfo." ---> ".$_Data['switch']);
			//log::add('sonoffdiy', 'debug', '$_Data1 : '.json_encode($_Data));
			log::add('sonoffdiy', 'debug', '$_Data : '.json_encode($_Data));
			//log::add('sonoffdiy', 'debug', '$_Data3 : '.$_Data);
		
			if (isset($_Data) && (array_key_exists($_CmdInfo,$_Data))) {
				
			//log::add('sonoffdiy_mDNS','info', '$_CmdInfo : '.$_CmdInfo);
			//log::add('sonoffdiy_mDNS','info', '>>>>>>>>>>>>>>Data[CmdInfo] : '.$_Data['switch']);
			//log::add('sonoffdiy_mDNS','info', '>>>>>>>>>>>>>>Data[CmdInfo] : '.$_Data[$_CmdInfo]);
				if ($_Data[$_CmdInfo]!="") {
					$valeur_enregistree=$_Data[$_CmdInfo];
					//if ($_CmdInfo=='signalStrength') $_CmdInfo='rssi';
					log::add('sonoffdiy', 'debug', '╠═ Enregistrement dans '.$_eqLogic->getName().' de '.$_CmdInfo.' : '.$valeur_enregistree);
					//if ($valeur_enregistree=='on') $valeur_enregistree=1;	if ($valeur_enregistree=='off') $valeur_enregistree=0;
					$_eqLogic->checkAndUpdateCmd($_CmdInfo, $valeur_enregistree);	
				}
			}
		//else log::add('sonoffdiy', 'debug', 'ERREUR Enregistrement de '.$_CmdInfo.' dans '.$_eqLogic->getName().' : '.$_Data[$_CmdInfo]. "dans ".json_decode($_Data, true));
			//log::add('sonoffdiy_mDNS','info', 'ERREUR Enregistrements dans '.json_decode($_Data, true));
		}
}