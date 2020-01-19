<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

if (!class_exists('mDNS')) {
	require_once dirname(__FILE__) . '/../../3rdparty/lib/mdns.php';
}

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
		log::add('sonoffdiy', 'debug', '!!************************** Start cron update sonoffdiy *******************************!!');
		return;
	}
	public static function deamon_start($_debug = false) {
		log::add('sonoffdiy', 'debug', 'deamon_start');
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
		log::add('sonoffdiy', 'debug', 'deamon_stop');
		$cron = cron::byClassAndFunction('sonoffdiy', 'daemon');
		if (!is_object($cron)) {
			throw new Exception(__('Cron et Daemon introuvables - réinstaller le plugin', __FILE__));
		}
		$cron->halt();
  
	}
	public static function daemon() { 
		log::add('sonoffdiy', 'debug', 'daemon');
		gc_enable();
		$IpDevices=array();
		$classes=array();
		$sockets=array();
		$localKeys=array();
		$LogicIds=array();
        $Faults=array();
		$Devices=array();
		$SocketDevices=array();
		$port = 6900; // port
        $address = '0.0.0.0';
		$lasttime = 0;
		$lasttimegc = 0;
		$mdns = new mDNS();
							// $IpDevices[] Ipadress for each unique ip device
							// $classes[] wifilight class for each unique ip device
							// $sockets [] wifilight socket for each unique ip device
							// $localKeys [] key for each unique ip device
							// $LogicIds [] id for each unique ip device
							// $Faults [] time for each unique ip device
							// $Devices [IPadress,chanel] = eqlogic
							// $SocketDevices [IPadress,chanel] = socket
							// $canal : n° de canal de la prise sinon 1  (erreur ?)(mono canal)
							// $canal >0 : update 1 seul      $Devices [$IpDevices[$keySock]][$canal]
							// $canal = -1 : update tous les $SocketDevices[$IpDevices[$keySock]]

		
		if (isset($sock) && $sock != NULL) socket_close($sock);

        if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
			log::add('wifilightV2', 'debug', "socket_create() failed: reason: " . socket_strerror(socket_last_error()));
            exit();
        }
        if (!socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1)) {
            log::add('wifilightV2', 'debug', "socket_set_option() failed: reason: " . socket_strerror(socket_last_error()));
            exit();
        } 
        if (socket_bind($sock, $address, $port) === false) {
           log::add('wifilightV2', 'debug', "socket_bind() failed: reason: " . socket_strerror(socket_last_error($sock)));
            exit();
        }
        if (socket_listen($sock, 5) === false) {
           log::add('wifilightV2', 'debug', "socket_listen() failed: reason: " . socket_strerror(socket_last_error($sock)));
        }
        socket_set_nonblock($sock);
        // search for connected devices
		$time=0;
		log::add('wifilightV2', 'debug', '>>>>Daemon Started');
		log::add('wifilightV2','debug','   Memory used :'.round(memory_get_usage()/1000). " ko ".memory_get_usage()%1000 . " o ");
		$memDep = round(memory_get_usage()/1000);
		while(true) {
			/* if (memory_get_usage()/1000>2960) {
				log::add('wifilightV2','error','   Restart Demon  @'.round(memory_get_usage()/1000). " ko ");
				self::deamon_start();
			} */
			// search for ewelink devices using mDNS
			$inpacket = $mdns->readIncoming();		
			if ($inpacket->packetheader !=NULL){
				$ans = $inpacket->packetheader->getAnswerRRs();
				if ($ans> 0) {
					log::add('wifilightV2', 'debug', "   mDns from :".$inpacket->answerrrs[0]->name);
					if ($inpacket->answerrrs[0]->name == "_ewelink._tcp.local") { 
						for ($x=0; $x < sizeof($inpacket->answerrrs); $x++) {
							//log::add('wifilightV2', 'debug', "   x:$x  qtype:".$inpacket->answerrrs[$x]->qtype);
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
									log::add('wifilightV2','debug','    name:'.$name);
								}
							}
							if ($inpacket->answerrrs[$x]->qtype == 16) {
								$str="";
								for($i=0;$i<sizeof($inpacket->answerrrs[$x]->data);$i++) {
									$caractere=$inpacket->answerrrs[$x]->data[$i];
									if ($caractere>31 && $caractere<127 && $caractere!= 34  && $caractere!= 39 && $caractere!= 92 && $caractere!=96) $str.=chr($caractere);
								}
								$data = $caractere=$inpacket->answerrrs[$x]->data;
								$tabdata="{";
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
								$tabdata.='"'.$key.'":"'.$val.'"';
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
									$tabdata.=',"'.$key.'":"'.$val.'"';
									$offset = $offset + $size+1;
								} 
								$tabdata.="}";
								if ($inpacket->answerrrs[$x]->name == "_ewelink._tcp.local") {
									$name = "";
									for ($y = 0; $y < sizeof($inpacket->answerrrs[$x]->data); $y++) {
										$name .= chr($inpacket->answerrrs[$x]->data[$y]);
									}
								}
							}
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
							}
							if ($inpacket->answerrrs[$x]->qtype == 1) {
								$d = $inpacket->answerrrs[$x]->data;
								$ip = $d[0] . "." . $d[1] . "." . $d[2] . "." . $d[3];
							}
						}
						log::add('wifilightV2','debug',"    packet is --- port:$portm  ip:$ip  data:$tabdata");
						foreach (eqLogic::byType('wifilightV2') as $eqLogic){  
							$IPaddr = $eqLogic->getConfiguration('addr');
							if ($IPaddr == $ip) {
								$class = $eqLogic->getConfiguration('WLClass');
								$localKey = $eqLogic->getConfiguration('macad');
								$Id = $eqLogic->getConfiguration('identifiant');
								if ($class!='') {
									$classW2 = "W2_".$class;
									$myLight = new $classW2($IPaddr,0, 1, 10, $localKey, $Id);
									$json_decoded_data = json_decode($tabdata, true);
									if (method_exists($myLight,'Decode')){
										$data="";
										if (isset ($json_decoded_data["data"])) $data = $json_decoded_data["data"];
										else if (isset ($json_decoded_data["data1"])) {
											$data = $json_decoded_data["data1"];
											if (isset ($json_decoded_data["data2"])) {
												$data = $data.$json_decoded_data["data2"];
												if (isset ($json_decoded_data["data3"])) {
													$data = $data.$json_decoded_data["data3"];
												}
											}
										}
										$decode = $myLight->Decode($data,$json_decoded_data["iv"]);
										if (($decode!="") && ($decode!=false)) {										
											if (method_exists($myLight,'GetGroup') ){											
												$states = $decode;
												if (is_array($states)) {
													foreach ($states as $canal => $state){
														if ($eqLogic->getConfiguration('canal') == $canal) {
															self::update($state,$eqLogic);
														}
													}
												}
												else {
													self::update($states,$eqLogic);
												}
											}
											else {
												$state = $decode;
												self::update($state,$eqLogic);
											}
										}
										else log::add('wifilightV2', 'debug', "   Data not decoded");
									}	
								}
							}
						}
						log::add('wifilightV2', 'debug', "   %%End mDNS packet");
					}
				}
			}
	
			// scan new devices (tuya + Yeelight) if not : call their state
			if (time() > $time + 60) { 
            	log::add('wifilightV2', 'debug', ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> Search for devices <<<<<<<<<<<<<<<<<<<<<<<<<<<<<");
				foreach (eqLogic::byType('wifilightV2') as $eqLogic){	
					$class = $eqLogic->getConfiguration('WLClass');
					if (($class!== false) && ($class != '' ) ) {
						$classW2 = "W2_".$class;
						$myLight = new $classW2(0,0,0,0);
						if ($eqLogic->getIsEnable()) {
							// for devices with permanent sockets
							if (method_exists($myLight,'Create')) {
								$IPaddr = $eqLogic->getConfiguration('addr');
								log::add('wifilightV2', 'debug', "****** Device listenable ".$eqLogic->getName().' - Class: '.$class." *****");								
								$key = array_keys ( $IpDevices , $IPaddr,true);							
								$localKey = $eqLogic->getConfiguration('macad');
								$defCanal = $eqLogic->getConfiguration('canal');
								if ($defCanal == 0 || $defCanal== NULL || $defCanal== "") $defCanal=1;
								if (!isset($SocketDevices [$IPaddr][$defCanal]) || $SocketDevices [$IPaddr][$defCanal]==false) {
									// non connected device					
									// use unique socket for a multi channels device
									$ret = true;
									if (!isset ($key[0]) || $sockets[$key[0]] == false) {
										$ret = $myLight->Create($socket,$IPaddr);
										socket_set_nonblock($socket);
										log::add('wifilightV2', 'debug', '   Socket updated @'.$IPaddr);
									}
									else {
										$socket = $sockets[$key[0]];
										log::add('wifilightV2', 'debug', '   Socket already created');
									}
									if ($ret === false) {
										$errorcode = socket_last_error();
										$errormsg = socket_strerror($errorcode);
										$socket = false;
										log::add('wifilightV2', 'debug', '   Connection impossible. Err=' . $errorcode . ' : ' . $errormsg);
										$Cmd = $eqLogic->getCmd(null, 'ConnectedGet');
										$Cmd->event(NOTCONNECTED);
										$Cmd->save();
										foreach ($SocketDevices [$IPaddr] as $id => $SockD){
											$Cmd = $Devices [$IPaddr][$id]->getCmd(null, 'ConnectedGet');
											$Cmd->event(NOTCONNECTED);
											$Cmd->save();
										}
									}
									if (isset($SocketDevices [$IPaddr][$defCanal]) ) {
										// update
										$classes[$key[0]] = $classW2;
										$sockets[$key[0]] = $socket;
										$localKeys[$key[0]] = $localKey;
										$exists[$key[0]] = true;
										$IpDevices[$key[0]] = $IPaddr;									
										$Faults[$key[0]]=time();
										$Devices [$IPaddr][$defCanal] = $eqLogic;
										$SocketDevices [$IPaddr][$defCanal] = $socket;
										foreach ($SocketDevices [$IPaddr] as $id => $Sct){
											$SocketDevices [$IPaddr][$id] = $socket;
										}
										log::add('wifilightV2','debug','   Update device @'.$IPaddr.' channel:'.$defCanal);
									}
									else {
										// create
										$classes[] = $classW2;
										$sockets[] = $socket;
										$localKeys[] = $localKey;
										$exists[] = true;
										$IpDevices[] = $IPaddr;									
										$Faults[] = time();
										$Devices [$IPaddr][$defCanal] = $eqLogic;
										$SocketDevices [$IPaddr][$defCanal] = $socket;
										log::add('wifilightV2','debug','   ADD New device @'.$IPaddr.' channel:'.$defCanal);
									}
								}
								if (isset($SocketDevices [$IPaddr][$defCanal]) && $SocketDevices [$IPaddr][$defCanal] !== false){
									$key = array_keys ( $IpDevices , $IPaddr,true);
									log::add('wifilightV2', 'debug',"   Device and socket exist : id:".$key[0]." @".$IpDevices[$key[0]]. " diff:".(time()-$Faults[$key[0]]));
									$incremV = $eqLogic->getConfiguration('incremV');
									if ($incremV === false){
										$incremV = 10;
									}
									$myLight = new $classW2($IPaddr,$eqLogic->getConfiguration('delai'),$eqLogic->getConfiguration('repetitions'),$incremV,$eqLogic->getConfiguration('macad'),$eqLogic->getConfiguration('identifiant'),$eqLogic->getConfiguration('nbLeds'),$eqLogic->getConfiguration('colorOrder'),0,$eqLogic->getConfiguration('cfgTuyaNRJ'),$eqLogic->getConfiguration('noState'));
									$canal = 1; 
									if (method_exists($myLight,'SetGroup')){
										$canal = $eqLogic->getConfiguration('canal'); 
										$myLight->SetGroup($canal);
									}
									if (method_exists($myLight,'Demon')) $myLight->Demon(false);
									if (method_exists($myLight,'setSocket'))$myLight->setSocket($SocketDevices [$IPaddr][$defCanal]);
									if (method_exists($myLight,'StillEnc')) $myLight->StillEnc(true);
									$state = $myLight->retStatus();
									if (method_exists($myLight,'Ping')) {
										if (time() > $Faults[$key[0]]+45){
											// connection lost
											@socket_close($SocketDevices [$IPaddr][$defCanal]);
											log::add('wifilightV2', 'debug',"   Destroy:".$IpDevices[$key[0]]);
											$Cmd = $eqLogic->getCmd(null, 'ConnectedGet');
											$Cmd->event(NOTCONNECTED);
											$Cmd->save();
											foreach ($SocketDevices [$IPaddr] as $id => $Socket){
												$SocketDevices [$IPaddr][$id] = false;
												$Cmd = $Devices [$IPaddr][$id]->getCmd(null, 'ConnectedGet');
												$Cmd->event(NOTCONNECTED);
												$Cmd->save();
											}
											$sockets[$key[0]] = false;	
										}
									}
									else {
										@socket_close($SocketDevices [$IPaddr][$defCanal]);
										$ret = $myLight->Create($socket,$IPaddr);					
										if ($ret === false) {
											log::add('wifilightV2', 'debug', "   Socket KO @$IPaddr ");
											$Cmd = $eqLogic->getCmd(null, 'ConnectedGet');
											$Cmd->event(NOTCONNECTED);
											$Cmd->save();
											foreach ($SocketDevices [$IPaddr] as $id => $SockD){
												$SocketDevices [$IPaddr][$id] = false;
												$Cmd = $Devices [$IPaddr][$id]->getCmd(null, 'ConnectedGet');
												$Cmd->event(NOTCONNECTED);
												$Cmd->save();
											}
											$sockets[$key[0]] = false;
											$errorcode = socket_last_error();
											$errormsg = socket_strerror($errorcode);
											log::add('wifilightV2', 'debug', '   Connection to socket impossible Code:' . $errorcode . ' = ' . $errormsg);
										}
										else {
											$Cmd = $eqLogic->getCmd(null, 'ConnectedGet');
											$Cmd->event(SUCCESS);
											$Cmd->save();
											foreach ($SocketDevices [$IPaddr] as $id => $SockD){
												$SocketDevices [$IPaddr][$id] = $socket;
												$Cmd = $Devices [$IPaddr][$id]->getCmd(null, 'ConnectedGet');
												$Cmd->event(SUCCESS);
												$Cmd->save();
											}
											log::add('wifilightV2', 'debug', "   Socket OK @$IPaddr ");
											$sockets[$key[0]] = $socket;
										}				
									}	
								}								
							}
							else {
								// for devices that do not sent their state
								$class = $eqLogic->getConfiguration('WLClass');	
								if (($class!== false) && ($class != '' ) && $eqLogic->getIsEnable() ) {
									log::add('wifilightV2', 'debug', "****** Device NOT listenable ".$eqLogic->getName().' - Class: '.$class." *****");	
									$incremV = $eqLogic->getConfiguration('incremV');
									if ($incremV === false){
										$incremV = 10;
									}	
									$classW2 = "W2_".$class;
									$myLight = new $classW2($eqLogic->getConfiguration('addr'),$eqLogic->getConfiguration('delai'),$eqLogic->getConfiguration('repetitions'),$incremV,$eqLogic->getConfiguration('macad'),$eqLogic->getConfiguration('identifiant'),$eqLogic->getConfiguration('nbLeds'),$eqLogic->getConfiguration('colorOrder'),0,$eqLogic->getConfiguration('cfgTuyaNRJ'),$eqLogic->getConfiguration('noState'));
									// now cron is not used for devices 	
									if (!method_exists($myLight,'Create')){
										$canal = $eqLogic->getConfiguration('canal'); 
										if (method_exists($myLight,'SetGroup')){
											$canal = $eqLogic->getConfiguration('canal'); 
											$myLight->SetGroup($canal);
										}
										$state = $myLight->retStatus();					
										if (self::update($state,$eqLogic)) {
											$Cmd = $eqLogic->getCmd(null, 'ConnectedGet');
											if ($Cmd !== false) {
												$Connected = $Cmd->execCmd();
											}
										}
									}
								}	
							}
						}
					}
				}
				log::add('wifilightV2', 'debug', ">>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> End <<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<");	
				unset ($classes2);
				unset ($sockets2);
				unset ($exists2);
				unset ($localKeys2);
				unset ($IpDevices2);
                unset ($Faults2);
                unset ($Devices2);
				unset ($SocketDevices2);
				$classes2=array();
				$sockets2=array();
				$exists2=array();
				$localKeys2=array();
				$IpDevices2=array();
                $Faults2=array();
                $Devices2=array();
				$SocketDevices2=array();
				foreach ($exists as $key => $value ){
					if ($exists[$key] == true) { 
						$classes2[] = $classes[$key];
						$sockets2[] = $sockets[$key];
						$exists2[] = true;
						$localKeys2[] = $localKeys[$key];
						$IpDevices2[] = $IpDevices[$key];
                        $Faults2[] = $Faults[$key];
						foreach ($SocketDevices[$IpDevices[$key]] as $id => $Socket){
							$Devices2 [$IpDevices[$key]][$id] = $Devices [$IpDevices[$key]][$id];
                            $SocketDevices2 [$IpDevices[$key]][$id] = $SocketDevices [$IpDevices[$key]][$id];
						}						
					}
				}
				$classes = $classes2;
				$sockets = $sockets2;
				$exists = $exists2;
				$localKeys = $localKeys2;
				$IpDevices = $IpDevices2;
                $Faults = $Faults2;
                $Devices = $Devices2;
				$SocketDevices = $SocketDevices2;
				$time = time();
				log::add('wifilightV2','debug','   Memory used :'.round(memory_get_usage()/1000). " ko ".memory_get_usage()%1000 . " o ");
				gc_collect_cycles();				
			}
			// now connected devices and characteristics are in tables
           
		    // listen from jeedom Cmds 
            $clientsock = socket_accept($sock);
            if($clientsock !== false){
                socket_set_nonblock($clientsock);
                socket_getpeername($clientsock,$address);
                $stringCom = socket_read($clientsock, 4096);
				$str="";
				for($i=0;$i<strlen($stringCom);$i++) {
					$caractere=substr($stringCom,$i,1);
					//log::add('wifilightV2','debug','Commande['.$i.'] : '. dechex(ord($stringCom[$i])));
					if (ord($caractere)>31 && ord($caractere)<128) $str.=$caractere;
                }
				$json_decoded_data = json_decode($stringCom, true);
				if ( ($json_decoded_data != NULL) && ($json_decoded_data != FALSE) ){
					foreach ($json_decoded_data as $key => $value) {
						//log::add('wifilightV2','debug',">>> : $key | $value : ".$json_decoded_data[$key]);
					}
					log::add('wifilightV2','debug',"   Send cmd to device @".$json_decoded_data['Address']);
					$myLight = new $json_decoded_data['Class']($json_decoded_data['Address'],0,0,0);
					$key = array_keys ( $IpDevices , $json_decoded_data['Address'],true);
					if (isset($Devices [$json_decoded_data['Address']][$json_decoded_data['Group']]) && $Devices [$json_decoded_data['Address']][$json_decoded_data['Group']]!=null) {
						$Cmd = $Devices [$json_decoded_data['Address']][$json_decoded_data['Group']]->getCmd(null, 'ConnectedGet');
						$OK = true;
						if ( $Cmd == false) {
							$OK = false;
						}
						else {
							if ($SocketDevices [$json_decoded_data['Address']][$json_decoded_data['Group']] == false ){
								$Cmd->event(NOTCONNECTED);
								$Cmd->save();
								$OK = false;
							}
						}
						if ($key != false && $OK == true) {
							$socket = $SocketDevices [$json_decoded_data['Address']][$json_decoded_data['Group']];
							if (method_exists($myLight,'setSocket'))$myLight->setSocket ($socket);
							if (method_exists($myLight,'Demon')) $myLight->Demon(false);
							$ret = $myLight->send($json_decoded_data['Msg'],0,$json_decoded_data['Crypt']); 
							if ($state==NOSOCKET || $state==NOTCONNECTED || $state==NORESPONSE || $state==BADRESPONSE) {		
								$Connected = $state;
							}
							else $Connected = SUCCESS;
							if ($Cmd !== false) {
								$Cmd->event($Connected);
								$Cmd->save();
							}
						}
					}
				}
				else
					log::add('wifilightV2','debug',"   Bad JSON");	
				socket_close($clientsock);			
				log::add('wifilightV2', 'debug', "!!!!!!!!! End !!!!!!!!!!");
            }  
			
			// if (time() > $lasttime+20) {
				// log::add('wifilightV2', 'debug', "$$$$$$ heartbeat to keep connection $$$$$");						
				// $lasttime = time();
				foreach ($Devices as $key => $line){
					$eqLogic = false;
					foreach ($line as $key2 => $line2){
						if ($Devices[$key][$key2] !== false ) {
							$eqLogic = $Devices[$key][$key2];
							
						}
                      	// if ($SocketDevices[$key][$key2] !== false ) {
							// $socket = $SocketDevices [$key][$key2];
						// }
					}
					$keyFault = array_keys ( $IpDevices , $key,true);	// searches IpDevice with IpAdress = $keyFault
					if (time()>$Faults[$keyFault[0]] +20) {
						$socket =$sockets[$keyFault[0]];
						log::add('wifilightV2','debug',"   Ping @".$IpDevices[$keyFault[0]]."  diff:".(time()-$Faults[$keyFault[0]]));
						$Faults[$keyFault[0]] = time();
						if (($eqLogic != false) && ($socket != false)) {
							$incremV = $eqLogic->getConfiguration('incremV');
							if ($incremV === false){
								$incremV = 10;
							}	
							$class = $eqLogic->getConfiguration('WLClass');
							if ($class!='') {
								$classW2 = "W2_".$class;
								$myLight = new $classW2($eqLogic->getConfiguration('addr'),$eqLogic->getConfiguration('delai'),$eqLogic->getConfiguration('repetitions'),$incremV,$eqLogic->getConfiguration('macad'),$eqLogic->getConfiguration('identifiant'),$eqLogic->getConfiguration('nbLeds'),$eqLogic->getConfiguration('colorOrder'),0,$eqLogic->getConfiguration('cfgTuyaNRJ'),$eqLogic->getConfiguration('noState'));
								if (method_exists($myLight,'setSocket'))$myLight->setSocket($socket);
								if (method_exists($myLight,'Demon')) $myLight->Demon(false);
								if (method_exists($myLight,'StillEnc')) $myLight->StillEnc(true);
								if ( method_exists($myLight,'Ping')) {
									log::add('wifilightV2', 'debug', "    Ping done");
									$myLight->Ping();	
								}
							}
						}
						else if ($eqLogic != false) {
							log::add('wifilightV2', 'debug', "    No socket");
						} 
						else {
						  log::add('wifilightV2', 'debug', "    Bad eqLogic");
						}
					}
				}
			//}
			
			// read from devices tuya + Yeelight
			foreach ($sockets as $keySock =>$socket){
				if ($socket  !=  false ){
					$buf="";	
					socket_clear_error();
					$buf = socket_read($socket, 1024);
					$errorcode = socket_last_error($socket);
					if ($errorcode!=0 && $errorcode!=11 ) {
						// 0 : success
						// 11 : temporary unavailable
						// destroy socket
						$errormsg = socket_strerror($errorcode);
						socket_close($socket);
						$sockets[$keySock] = false;
						foreach ($SocketDevices[$IpDevices[$keySock]] as $Canal => $Socket){
							$SocketDevices[$IpDevices[$keySock]][$Canal] = false;
						}
						log::add('wifilightV2','debug',"    Error on:".$IpDevices[$keySock]." is :" . $errormsg. " n°:".$errorcode."  diff:".(time()-$Faults[$keySock]));
						if ( ($errorcode == 104) && (time()-$Faults[$keySock]> 40)) { // retry each 40s
							$Faults[$keySock]=time();  // reset timeout
							log::add('wifilightV2','debug'," :::::::::::: Reconnect if connection closed by peer ::::::::::::::::: ") ;
							$myLight = new $classes[$keySock](0,0,0,0,0);
							$ret = $myLight->Create($socketR,$IpDevices[$keySock]);						
							if ($ret === false) {
								$errorcode = socket_last_error();
								$errormsg = socket_strerror($errorcode);
								// inutile
									$sockets[$keySock] = false;
									foreach ($SocketDevices[$IpDevices[$keySock]] as $Canal => $Socket){
										$SocketDevices[$IpDevices[$keySock]][$Canal] = false;
									}
								//
								log::add('wifilightV2', 'debug', '   Socket closed by peer @'.$IPaddr.' ' . $errorcode . ' : ' . $errormsg);
							}
							else {
								log::add('wifilightV2', 'debug', '   New socket created @'.$IpDevices[$keySock]);
								$sockets[$keySock] = $socketR;
								foreach ($SocketDevices[$IpDevices[$keySock]] as $Canal => $socket){
									$SocketDevices[$IpDevices[$keySock]][$Canal] = $socketR;
								}
							}				
						}
						else if ($errorcode == 104) {
							log::add('wifilightV2','debug',"   Error : $errorcode") ;
						}
					}
					socket_clear_error();
					if ($buf!="") {
                      	log::add('wifilightV2','debug',"////// Receive from :".$IpDevices[$keySock]." //////");			
						foreach ($Devices[$IpDevices[$keySock]] as $key => $eqLogi){
							$PowerConf="";
							if ($eqLogi !== false && $eqLogi != NULL) {
								$eqLogic = $Devices[$IpDevices[$keySock]][$key];
								$PowerConf = $eqLogic->getConfiguration('cfgTuyaNRJ');
								break;
							}
						}
						$Faults[$keySock]=time();
						$myLight = new $classes[$keySock]($IpDevices[$keySock],0,0,0,$localKeys[$keySock],0,0,0,0,$PowerConf);
						// Decode searches for the channel when a parameter is present
						$iBcl = 0;
						do { // because sometimes the frame contains 2 dps sequences
							$state = $myLight -> Decode($buf);
								// ob_start();
								// var_dump($state);
								// $res = ob_get_clean();
								// log::add('wifilightV2','debug','    State is:'.$res );
							if ($state !== false) { 
								if (!method_exists($myLight,'GetGroup')) { 
									// yeelight bulbs
									if (isset($Devices [$IpDevices[$keySock]][1]) && $Devices [$IpDevices[$keySock]][1]!=null) self::update($state,$Devices [$IpDevices[$keySock]][1]);
									else log::add('wifilightV2','debug',"   No such device @".$IpDevices[$keySock]." Monocanal:1");	
								}
								else {
									// tuya devices
									if (($state['On'] == -1) && (count($state['MultiC'])== 0)) { //V1 or V2 version no channel in state return -> update all devices for consumption
										foreach ($SocketDevices[$IpDevices[$keySock]] as $Canal => $Socket){ // tous les périphériques avec même adresse ip
											log::add('wifilightV2','debug',"   Multiple device @".$IpDevices[$keySock]." canal:$Canal");
											if (isset($Devices [$IpDevices[$keySock]][$Canal]) && $Devices [$IpDevices[$keySock]][$Canal]!=null) self::update($state,$Devices [$IpDevices[$keySock]][$Canal]);
											else log::add('wifilightV2','debug',"   No such device");				
										}
									} else if ($state['On'] != -1) {
										// device with 1 channel
										$canal = $myLight ->GetGroup();
										log::add('wifilightV2','debug',"   Device @".$IpDevices[$keySock]." Monocanal:$canal");
										if (isset($Devices [$IpDevices[$keySock]][$canal]) && $Devices [$IpDevices[$keySock]][$canal]!=null) self::update($state,$Devices [$IpDevices[$keySock]][$canal]);
										else log::add('wifilightV2','debug',"  No such device");
									} else if (count($state['MultiC'])> 0 ) { //V2 version with channels in array
										// multichannel device
										log::add('wifilightV2','debug',"   Multichanel in state : update all"); //V2 version
										foreach ($SocketDevices[$IpDevices[$keySock]] as $Canal => $Socket){ // tous les périphériques avec même adresse ip
											log::add('wifilightV2','debug',"   Multiple device @".$IpDevices[$keySock]." canal:$Canal");
												// ob_start();
												// var_dump($state['MultiC']);
												// $res = ob_get_clean();
												// log::add('wifilightV2','debug','    State canal is:'.$res );
											foreach ($state['MultiC'] as $CanalDev => $value) {
												log::add('wifilightV2','debug',"    CanalDev:$CanalDev in MultiC");
												if ($CanalDev == $Canal) {
													log::add('wifilightV2','debug',"    Found Canal:$Canal OK");
													if (isset($Devices [$IpDevices[$keySock]][$CanalDev]) && $Devices [$IpDevices[$keySock]][$CanalDev]!=null) {
														$stateMod = $state;
														$stateMod['On'] = $state['MultiC'][$CanalDev];
														self::update($stateMod,$Devices [$IpDevices[$keySock]][$CanalDev]);
													}
													else log::add('wifilightV2','debug',"    No such device");	
												}
											}											
										}	
									}
								}
							}
							else log::add('wifilightV2','debug',"   No data returned");
							$iBcl ++ ;
						} while (strlen($buf)>0 && $iBcl <6);
						log::add('wifilightV2','debug',"///////////////// End ///////////////////");
					}
				}
			}
			usleep(100000);
		}
	}		
		
	
	public function stopDaemon() {
		log::add('sonoffdiy', 'debug', 'stopDaemon');
		$cron = cron::byClassAndFunction('sonoffdiy', 'daemon');
		$cron->stop();
		$cron->start();
	}
	

	public function refresh() {
		
			//log::add('sonoffdiy', 'info', '--Refresh');
			
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
					//log::add('sonoffdiy', 'debug', 'postSAVE ');
					
				
		$premierSAVE = false;
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
				$refresh->setName(__('Rafraichir', __FILE__));
			}
			$refresh->setType('action');
			$refresh->setSubType('other');
			$refresh->setEqLogic_id($this->getId());
			$refresh->setOrder(10);
			$refresh->setConfiguration('expliq', 'Rafraîchit manuellement toutes les données du device');
			$refresh->save();
		}

				$cmd = $this->getCmd(null, 'switch');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('info');
					$cmd->setLogicalId('switch');
					$cmd->setSubType('binary');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('Etat du relais');
					//$cmd->setDisplay('title_disable', 1);
					$cmd->setIsVisible(1);
					$cmd->setOrder(1);
					//$cmd->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
					//$cmd->setDisplay('forceReturnLineBefore', true);
				}
				$cmd->save();
				
				$cmd = $this->getCmd(null, 'Off');
				if (!is_object($cmd)) {
					$premierSAVE = true;
					$cmd = new sonoffdiyCmd();
					$cmd->setType('action');
					$cmd->setLogicalId('Off');
					$cmd->setSubType('other');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('Off');
					$cmd->setConfiguration('request', 'switch?command=off');
					$cmd->setConfiguration('expliq', 'Eteindre');
					$cmd->setDisplay('title_disable', 1);
					$cmd->setOrder(3);
					//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
					$cmd->setIsVisible(1);
					$cmd->setDisplay('icon', '<i class="icon_red icon fas fa-times"></i>');
					
				}
				$cmd->save();
				
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
				}
				$cmd->save();
				
				$cmd = $this->getCmd(null, 'On');
				if (!is_object($cmd)) {
					$premierSAVE = true;
					$cmd = new sonoffdiyCmd();
					$cmd->setType('action');
					$cmd->setLogicalId('On');
					$cmd->setSubType('other');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('On');
					$cmd->setConfiguration('request', 'switch?command=on');
					$cmd->setConfiguration('expliq', 'Allumer');
					$cmd->setDisplay('title_disable', 1);
					$cmd->setOrder(2);
					$cmd->setDisplay('icon', '<i class="icon_green icon fas fa-check"></i>');
					$cmd->setIsVisible(1);
				}
				$cmd->save();
				
				$cmd = $this->getCmd(null, 'PulseOff');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('action');
					$cmd->setLogicalId('PulseOff');
					$cmd->setSubType('other');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('Pulse Off');
					//$cmd->setConfiguration('parameter', '5000');					
					$cmd->setConfiguration('request', 'pulse?command=off');
					$cmd->setConfiguration('expliq', 'Désactive le mode Pulse');
					$cmd->setDisplay('title_disable', 1);
					$cmd->setOrder(5);
					//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
					$cmd->setIsVisible(0);
				}
				$cmd->save();

				
				$cmd = $this->getCmd(null, 'PulseOn');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('action');
					$cmd->setLogicalId('PulseOn');
					$cmd->setSubType('message');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('Pulse On');
					$cmd->setConfiguration('parameter', '5000');
					$cmd->setConfiguration('request', 'pulse?command=on');
					$cmd->setConfiguration('expliq', 'Active le mode Pulse (et fixe le nb de ms en paramètre)');
					$cmd->setDisplay('title_disable', 1);
					$cmd->setOrder(4);
					//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
					$cmd->setIsVisible(0);

				}
				$cmd->save();
				
				$cmd = $this->getCmd(null, 'startup_action'); // 
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('action');
					$cmd->setLogicalId('startup_action');
					$cmd->setSubType('select');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('Etat initial');					
					$cmd->setConfiguration('request', 'startup?state=#select#');
					$cmd->setConfiguration('listValue', 'on|on; off|off; stay|stay');
					$cmd->setConfiguration('expliq', "Définir l'état à la mise sous tension");
					$cmd->setDisplay('title_disable', 1);
					$cmd->setOrder(6);
					//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
					$cmd->setIsVisible(0);
				}
				$cmd->save();
				
			
				$cmd = $this->getCmd(null, 'startup');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('info');
					$cmd->setLogicalId('startup');
					$cmd->setSubType('binary');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('Etat à la mise sous tension');
					$cmd->setIsVisible(0);
					$cmd->setOrder(2);
					//$cmd->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
					//$cmd->setDisplay('forceReturnLineBefore', true);
				}
				$cmd->save();

				
				$cmd = $this->getCmd(null, 'pulse');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('info');
					$cmd->setLogicalId('pulse');
					$cmd->setSubType('binary');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('Etat de la fonction Pulse');
					$cmd->setIsVisible(0);
					$cmd->setOrder(3);
					//$cmd->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
					//$cmd->setDisplay('forceReturnLineBefore', true);
				}
				$cmd->save();
				
				
				$cmd = $this->getCmd(null, 'pulseWidth');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('info');
					$cmd->setLogicalId('pulseWidth');
					$cmd->setSubType('string');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('Tempo de la fonction Pulse');
					$cmd->setIsVisible(0);
					$cmd->setOrder(4);
					//$cmd->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
					//$cmd->setDisplay('forceReturnLineBefore', true);
				}
				$cmd->save();
				
				
				$cmd = $this->getCmd(null, 'ssid');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('info');
					$cmd->setLogicalId('ssid');
					$cmd->setSubType('string');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('SSID');
					$cmd->setIsVisible(1);
					$cmd->setOrder(5);
					//$cmd->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
					//$cmd->setDisplay('forceReturnLineBefore', true);
				}
				$cmd->save();
								
				$cmd = $this->getCmd(null, 'signalStrength');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('info');
					$cmd->setLogicalId('signalStrength');
					$cmd->setSubType('string');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('RSSI');
					$cmd->setIsVisible(1);
					$cmd->setOrder(6);
					//$cmd->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
					//$cmd->setDisplay('forceReturnLineBefore', true);
				}
				$cmd->save();
				
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
				}
				$cmd->save();		
				
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




	}
	
	public function preUpdate() {
	}
	
	public function preRemove () {
	}
	
	public function preSave() {
		
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
		//log::add('sonoffdiy', 'debug', '----device_id:'.$device_id);
	if ($this->getType() != 'action') return $this->getConfiguration('request');
	list($command, $arguments) = explode('?', $this->getConfiguration('request'), 2);
	list($variable, $valeur) = explode('=', $arguments, 2);
	$parameter=(int)$this->getConfiguration('parameter');
	//log::add('sonoffdiy', 'info', '----variable:*'.$variable.'* valeur:'.$valeur);
	//log::add('sonoffdiy', 'info', '----Command:*'.$command.'* arguments:'.$arguments);
	if ($_options['select'] != '') $valeur=$_options['select']; // Pour Etat Initial
	if ($_options['message'] != '') $parameter=$_options['message']; // pour Pulse ON
	

			$url = "http://".$adresse_ip.":8081/zeroconf/".$command; // Envoyer la commande Refresh via jeeAlexaapi
			$ch = curl_init($url);
			
			if ($command=="switch")			
			$data = array(
				'deviceid'        => $device_id,
				'data'    => array(
					'switch'      => $valeur
				),
			);	
			
			if ($command=="startup")			
			$data = array(
				'deviceid'        => $device_id,
				'data'    => array(
					'startup'      => $valeur
				),
			);				
		
			if ($command=="pulse")	
				if ($parameter>500) 
					$data = array(
						'deviceid'        => $device_id,
						'data'    => array(
							'pulse'      => $valeur,
							'pulseWidth'      => $parameter,
						),
					);	
				 else 					
					$data = array(
						'deviceid'        => $device_id,
						'data'    => array(
							'pulse'      => $valeur
						),
					);
			$vide = (object)[];
			if (($command=="signal_strength") || ($command=="info")	)		
			$data = array(
				'deviceid'        => $device_id,
				'data'    => $vide,
			);	
			
			$payload = json_encode($data);
	log::add('sonoffdiy', 'info', '>> Envoyé à '.$eqLogic->getName().' : '.$url." ".$payload);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
			//curl_setopt($ch, CURLOPT_HEADER, true); //TRUE pour inclure l'en-tête dans la valeur de retour
			$result = json_decode(curl_exec($ch),true);
			curl_close($ch);
			
		if (is_null($result)) {
			log::add('sonoffdiy', 'error', 'Souci sur la commande '.$this->getName().' de '.$eqLogic->getName());
			return false;
		}
		log::add('sonoffdiy', 'info', '<< Réponse de '.$eqLogic->getName().' : '.json_encode($result));
		//log::add('sonoffdiy', 'debug', '<< Data recues de '.$eqLogic->getName().' : '.$result['data']);
		//log::add('sonoffdiy', 'debug', '<< error recue de '.$eqLogic->getName().' : '.$result['error']);
		if ($result['error']!="0") {
			log::add('sonoffdiy', 'error', 'Souci sur la commande '.$this->getName().' de '.$eqLogic->getName().' error : '.$result['error']);
			// Pour avoir les codes erreur : https://github.com/itead/Sonoff_Devices_DIY_Tools/blob/master/SONOFF%20DIY%20MODE%20Protocol%20Doc%20v1.4.md
		}
		
		
		
		
		$data=json_decode($result['data'], true);
		self::enregistreCmdInfo('signalStrength', $data, $eqLogic);
		self::enregistreCmdInfo('switch', $data, $eqLogic);
		self::enregistreCmdInfo('startup', $data, $eqLogic);
		self::enregistreCmdInfo('pulse', $data, $eqLogic);
		self::enregistreCmdInfo('pulseWidth', $data, $eqLogic);
		self::enregistreCmdInfo('ssid', $data, $eqLogic);
		/*
				if (($this->getType() == 'action') && ($this->getConfiguration('infoName') != '')) {
				$LogicalIdCmd=$this->getConfiguration('infoName');
				
					//$cmd = cmd::byId(str_replace('#', '', $this->getConfiguration('infoName')));
					//return $cmd->execCmd($_options);
				
				//$cmd=$this->getEqLogic()->getCmd(null, $LogicalIdCmd);
				$cmd=cmd::byId($LogicalIdCmd);
				if (is_object($cmd)) { 
					log::add('sonoffdiy', 'debug', $LogicalIdCmd.' prévu dans infoName de '.$this->getName().' trouvé !');
					$this->getEqLogic()->checkAndUpdateCmd($cmd->getLogicalId(), time());
				} else {
					log::add('sonoffdiy', 'warning', $LogicalIdCmd.' prévu dans infoName de '.$this->getName().' mais non trouvé ! donc ignoré');
				} 
				}*/
		
		
		
		

		return true;
	}
		public function enregistreCmdInfo($_CmdInfo, $_Data, $_eqLogic) {
		if ($_Data[$_CmdInfo]!="") {
			$valeur_enregistree=$_Data[$_CmdInfo];
			//log::add('sonoffdiy', 'debug', 'Enregistrement de '.$_CmdInfo.' dans '.$_eqLogic->getName().' : '.$_Data[$_CmdInfo]);
			//if ($valeur_enregistree=='on') $valeur_enregistree=1;	if ($valeur_enregistree=='off') $valeur_enregistree=0;
			$_eqLogic->checkAndUpdateCmd($_CmdInfo, $valeur_enregistree);	
		}		
		}
}