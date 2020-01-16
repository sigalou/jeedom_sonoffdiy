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
if (!isConnect('admin')) {
	throw new Exception('401 Unauthorized');
}
echo '<form name="formulaire" id="div_eventEditAlert" class="form-horizontal"><fieldset>';
	$device = eqLogic::byId($_GET['iddevice']);
	if (is_object($device)) {
		// Lecture de arrayAlbumsFacebook dans configuration du device en cours ($device)
		$Albums=$device->getConfiguration('arrayAlbumsFacebook');
		//log::add('sonoffdiy', 'debug', 'Récupération de : '.json_encode($Albums).' depuis plugin/device('.$_GET['iddevice'].')/config/arrayAlbumsFacebook');
		foreach (config::byKey('albumsFacebook', 'sonoffdiy', '0') as $value) {
			$checked="";
			foreach ($Albums as $key2 => $value2) {
				//log::add('sonoffdiy', 'debug', 'id:'.$Albums[$key2][0].'  value:'.$Albums[$key2][1]);
				if (($value['id'] == $Albums[$key2][0]) && $Albums[$key2][1] == '1') $checked=" checked";
			}	
			echo '<input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="albumsFacebook" data-l3key="albumfb_'.$value['id'].'"'.$checked.' />' . $value['name']. ' ('.$value['count'].' photos, créé le '. date('d-m-Y', strtotime($value['created_time'])).')<br>';
		}
	}
echo '</fieldset></form><a class="btn btn-success pull-right" id="bt_enregistreAlbumFB" style="color: white;"><i class="fa fa-check-circle"></i> {{Enregistrer}}</a>';
include_file('desktop', 'sonoffdiy', 'js', 'sonoffdiy'); 
//include_file('desktop', 'sonoffdiy', 'css', 'sonoffdiy'); 
//include_file('core', 'plugin.template', 'js'); 
?>