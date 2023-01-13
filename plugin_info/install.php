<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';

function sonoffdiy_install() {
    $cron = cron::byClassAndFunction('sonoffdiy', 'daemon');
    if (!is_object($cron)) {
        $cron = new cron();
        $cron->setClass('sonoffdiy');
        $cron->setFunction('daemon');
        $cron->setEnable(1);
        $cron->setDeamon(1);
        $cron->setSchedule('* * * * *');
        $cron->setTimeout('1440');
        $cron->save();
    }
}

function sonoffdiy_update() {
  $cron = cron::byClassAndFunction('sonoffdiy', 'pull');
  if (is_object($cron)) {
      $cron->remove();
  }
    $cron = cron::byClassAndFunction('sonoffdiy', 'daemon');
    if (!is_object($cron)) {
        $cron = new cron();
        $cron->setClass('sonoffdiy');
        $cron->setFunction('daemon');
        $cron->setEnable(1);
        $cron->setDeamon(1);
        $cron->setSchedule('* * * * *');
        $cron->setTimeout('1440');
        $cron->save();
    }
    $cron->stop();
    
/*VB-)*/                        
  // ----- Look for each equip
  $eqLogics = eqLogic::byType('sonoffdiy');
  foreach ($eqLogics as $v_eq) {
    // ----- Update pour les miniR3
    if ($v_eq->getConfiguration('device')=="miniR3") {
      // ----- On créé la commande 'startup_action' si elle n'existe pas déjà
    	$cmd = $v_eq->getCmd(null, 'startup_action');
    	if (!is_object($cmd)) {
    		$cmd = new sonoffdiyCmd();
    		$cmd->setType('action');
    		$cmd->setLogicalId('startup_action');
    		$cmd->setSubType('select');
    		$cmd->setEqLogic_id($v_eq->getId());
    		$cmd->setName('Etat initial');					
    		$cmd->setConfiguration('request', 'startups?state=#select#&outlet=0');
    		$cmd->setConfiguration('listValue', 'on|on;off|off;stay|stay');
    		$cmd->setConfiguration('expliq', "Définir l'état à la mise sous tension");
    		$cmd->setDisplay('title_disable', 1);
    		$cmd->setOrder(5);
    		$cmd->setIsVisible(0);
    		$cmd->save();
    	}

      // ----- On créé la commande 'startup_action' si elle n'existe pas déjà
		$cmd = $v_eq->getCmd(null, 'startup');
		if (!is_object($cmd)) {
			$cmd = new sonoffdiyCmd();
			$cmd->setType('info');
			$cmd->setLogicalId('startup');
   			$cmd->setSubType('string');
			$cmd->setEqLogic_id($v_eq->getId());
			$cmd->setName('Etat à la mise sous tension');
			$cmd->setIsVisible(0);
			$cmd->setOrder(15); 
			$cmd->save();
		}

		$cmd = $v_eq->getCmd(null, 'PulseOff');
		if (!is_object($cmd)) {
			$cmd = new sonoffdiyCmd();
			$cmd->setType('action');
			$cmd->setLogicalId('PulseOff');
			$cmd->setSubType('other');
			$cmd->setEqLogic_id($v_eq->getId());
			$cmd->setName('Pulse Off');
			$cmd->setConfiguration('request', 'pulses?command=off&outlet=0');
			$cmd->setConfiguration('expliq', 'Désactive le mode Pulse');
			$cmd->setDisplay('title_disable', 1);
			$cmd->setOrder(6); 
			$cmd->setIsVisible(0);
			$cmd->save();
		}

		$cmd = $v_eq->getCmd(null, 'PulseOn');
		if (!is_object($cmd)) {
			$cmd = new sonoffdiyCmd();
			$cmd->setType('action');
			$cmd->setLogicalId('PulseOn');
			$cmd->setSubType('message');
			$cmd->setEqLogic_id($v_eq->getId());
			$cmd->setName('Pulse On');
			$cmd->setConfiguration('parameter', '5000');
			$cmd->setConfiguration('etat_fin_pulse', 'off');                        
			$cmd->setConfiguration('request', 'pulses?command=on&outlet=0');
			$cmd->setConfiguration('expliq', 'Active le mode Pulse et fixe la tempo en ms (multiple de 500ms)');
			$cmd->setDisplay('title_disable', 1);
			$cmd->setOrder(7); 
			$cmd->setIsVisible(0);
			$cmd->save();
		}

		$cmd = $v_eq->getCmd(null, 'pulse');
		if (!is_object($cmd)) {
			$cmd = new sonoffdiyCmd();
			$cmd->setType('info');
			$cmd->setLogicalId('pulse');
			$cmd->setSubType('binary');
			$cmd->setEqLogic_id($v_eq->getId());
			$cmd->setName('Etat de la fonction Pulse');
			$cmd->setIsVisible(0);
			$cmd->setOrder(10);
			$cmd->save();
		}
				
		$cmd = $v_eq->getCmd(null, 'pulseWidth');
		if (!is_object($cmd)) {
			$cmd = new sonoffdiyCmd();
			$cmd->setType('info');
			$cmd->setLogicalId('pulseWidth');
			$cmd->setSubType('string');
			$cmd->setEqLogic_id($v_eq->getId());
			$cmd->setName('Tempo de la fonction Pulse');
			$cmd->setIsVisible(0);
			$cmd->setOrder(11);
			$cmd->save();
		}

		$cmd = $v_eq->getCmd(null, 'pulseEndState');
		if (!is_object($cmd)) {
			$cmd = new sonoffdiyCmd();
			$cmd->setType('info');
			$cmd->setLogicalId('pulseEndState');
			$cmd->setSubType('string');
			$cmd->setEqLogic_id($v_eq->getId());
			$cmd->setName('Etat à la fin du Pulse');
			$cmd->setIsVisible(0);
			$cmd->setOrder(12); 
			$cmd->save();
		}
        
    }
  }    
/*VB-)*/                        
}

function sonoffdiy_remove() {
    $cron = cron::byClassAndFunction('sonoffdiy', 'pull');
    if (is_object($cron)) {
        $cron->remove();
    }
    $cron = cron::byClassAndFunction('sonoffdiy', 'daemon');
    if (is_object($cron)) {
        $cron->stop();
        $cron->remove();
    }
}

?>
