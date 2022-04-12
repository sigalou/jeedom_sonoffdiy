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
		    alert( 'vide' );

*/

$(document).ready(function() {

	
/*
  //$('.eqLogicAttr[data-l1key=configuration][data-l2key=switch]').on('change', function () {
    if($('.eqLogicAttr[data-l1key=configuration][data-l2key=switch]').value() != 'Off') {
		$('#img_switch').attr("style", 'color: red; #ccc;float:center');	
		$('#img_switch').attr("class", 'fas fa-bolt fa-3x');
	}
    else {
		$('#img_switch').attr("style", 'color: green; #ccc;float:center');	
		$('#img_switch').attr("class", 'fas fa-times fa-3x');
	}
 // });
*/
  $('.eqLogicAttr[data-l1key=configuration][data-l2key=device]').on('change', function () {
    if($('.eqLogicAttr[data-l1key=configuration][data-l2key=device]').value() != '')
      $('#img_device').attr("src", 'plugins/sonoffdiy/core/config/devices/'+$(this).value()+'.png');
    else
      $('#img_device').attr("src",'plugins/sonoffdiy/plugin_info/sonoffdiy_icon.png');
  
  //alert ("coucou")
  
  });
  
$Model = $('.eqLogicAttr[data-l1key=configuration][data-l2key=device]').value();
	if(($Model != '') && $($Model != null))  
		$('#img_device').attr("src", 'plugins/sonoffdiy/core/config/devices/'+$Model+'.png');
	else
		$('#img_device').attr("src",'plugins/sonoffdiy/plugin_info/sonoffdiy_icon.png');
   
  
});

$("#table_cmd_actions").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});
$("#table_cmd_infos").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

function addCmdToTable(_cmd)
{
  if (!isset(_cmd))
    var _cmd = {configuration: {}};

					var DefinitionDivPourCommandesPredefinies='style="display: none;"';
					if (init(_cmd.logicalId)=="")
					DefinitionDivPourCommandesPredefinies="";
//  if ((init(_cmd.logicalId) == 'whennextreminder') || (init(_cmd.logicalId) == '00whennextalarm') || (init(_cmd.logicalId) == 'whennextreminderlabel') || (init(_cmd.logicalId) == 'musicalalarmmusicentity') || (init(_cmd.logicalId) == 'whennextmusicalalarm')) {
	$masqueCmdAction="";							
  if ((init(_cmd.logicalId) == 'Info') || (init(_cmd.logicalId) == 'signal_strength') || (init(_cmd.logicalId).substring(0,7) == 'monitor') || (init(_cmd.logicalId) == 'getState') || (init(_cmd.logicalId) == 'getStateEsclave') || (init(_cmd.logicalId) == 'subDevList')) {
    $masqueCmdAction='style="display:none;"';
  }


 
  if (init(_cmd.type) == 'info')
  {
    var tr =
       '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">'
     +   '<td>'
     +     '<span class="cmdAttr" data-l1key="id" style="font-size: 10px;"></span>'
     +   '</td>'
     +   '<td>'
     +     '<div class="row">'
     +       '<div class="col-lg-1">'
 //    +         '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fa fa-flag"></i> Icone</a>'
     +         '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>'
     +       '</div>'
     +   '<div class="col-lg-12">'
     +     '<input class="cmdAttr form-control input-sm" data-l1key="name" style="margin-bottom : 10px;"></td>'
     +   '<td>'
	+     '<span style="margin-bottom : 5px;" class="subType" subType="' + init(_cmd.subType) + '"></span>'
    +   '</td>'
   +   '<td>'
 //     +     '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>'
     +     '<input class="cmdAttr form-control type input-sm" data-l1key="type" value="info" type="hidden" disabled  />'
     +     '<input class="cmdAttr form-control type input-sm" data-l1key="configuration" data-l2key="value" readonly  />'
//     +     '<input class="cmdAttr form-control type input-sm" data-l1key="value" disabled style="margin-bottom : 5px;" />'
 //   +   '</td>'
//    +   '<td>'     
//    +   '</td>'
 //   +   '<td>'
//     +     '<small><span class="cmdAttr"  data-l1key="configuration" data-l2key="cmd"></span> Résultat de la commande <span class="cmdAttr"  data-l1key="configuration" data-l2key="taskname"></span> (<span class="cmdAttr"  data-l1key="configuration" data-l2key="taskid"></span>)</small>'

 //    +     '<span class="cmdAttr"  data-l1key="configuration" data-l2key="value"></span>'
   //  +   '</td>'
 //    +   '<td>'
  //   +     '<input class="cmdAttr form-control input-sm" data-l1key="unite" style="width : 90px;" placeholder="{{Unite}}">'

     +   '</td>'
     +   '<td>'
     +     '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isHistorized" checked/>{{Historiser}}</label></span> '
     +     '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> '
     +   '</td>'
     + '<td>';

    if (is_numeric(_cmd.id))
    {
      tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> '
          + '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
    }

    tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>'
     +   '</td>'
     + '</tr>';

    $('#table_cmd_infos tbody').append(tr);
    $('#table_cmd_infos tbody tr:last').setValues(_cmd, '.cmdAttr');
    if (isset(_cmd.type)) {
      $('#table_cmd_infos tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
    }
    jeedom.cmd.changeType($('#table_cmd_infos tbody tr').last(), init(_cmd.subType));
  
  }
//-------------------------------------------------------------------------------------------------------------------------------
  if (init(_cmd.type) == 'action')
  {
	var tr =
	'<tr ' + $masqueCmdAction + ' class="cmd" data-cmd_id="' + init(_cmd.id) + '">'
	+   '<td>'
	+     '<span class="cmdAttr" data-l1key="id" style="font-size: 10px;"></span>'
	+   '</td>'
	+   '<td>'
	+     '<div class="row">'
	+       '<div class="col-lg-1">'
	+         '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>'
	+       '</div>'
	+       '<div class="col-lg-8">'
	+         '<input class="cmdAttr form-control input-sm" data-l1key="name">'
	+       '</div>'
	+     '</div>';

	tr  +=   '</td>';



	tr  +=   '<td>'
	+     '<input class="cmdAttr form-control input-sm"';
	if (init(_cmd.logicalId)!="")
	tr  +='readonly';

	if (init(_cmd.logicalId)=="refresh")
	tr  +=' style="display:none;" ';

	tr+= ' data-l1key="configuration" data-l2key="request">';
	
		if (init(_cmd.subType) == 'select') {
    tr += '<input class="tooltips cmdAttr form-control input-sm expertModeVisible" data-l1key="configuration" data-l2key="listValue" placeholder="{{Liste de valeur|texte séparé par ;}}" title="{{Liste}}">';	
		}
	
	tr +=   '</td>';
	tr +=   '<td>';
	
		if ((init(_cmd.logicalId)=="PulseOff")||(init(_cmd.logicalId)=="PulseOn")) {
			tr +=     '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="parameter" style="margin-top : 3px;"> ';
			//tr +=   '</td>';
			//tr +=   '<td>';
			tr +=     '';
		}
		else {
			//tr +=   '</td>';
			//tr +=   '<td>';
		}
		
	tr +=   '</td>';
	tr  +=   '<td>';
	tr  +='<input class="cmdAttr form-control type input-sm" type="hidden" data-l1key="type" value="action" disabled />';
	tr  +='<input class="cmdAttr form-control type input-sm" data-l1key="configuration" data-l2key="expliq" readonly />';
	tr  +='<div '+DefinitionDivPourCommandesPredefinies+'>';
	tr  +=     '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
	tr  +=   '</div></td>';	  
	tr +=   '<td>'
     +     '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> '
     +   '</td>'
     + '<td>';
	 
    if (is_numeric(_cmd.id))
    {
      tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
		   if (!(init(_cmd.logicalId)=="startup")) //Masquer le bouton Tester
			  tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
	}
    tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>'
     + '  </td>'
     + '</tr>';

    $('#table_cmd_actions tbody').append(tr);
    var tr = $('#table_cmd_actions tbody tr:last');
    jeedom.eqLogic.builSelectCmd(
    {
      id: $(".li_eqLogic.active").attr('data-eqLogic_id'),
      filter: {type: 'i'},
      error: function (error)
      {
        $('#div_alert').showAlert({message: error.message, level: 'danger'});
      },
      success: function (result)
      {
        tr.find('.cmdAttr[data-l1key=value]').append(result);
        tr.setValues(_cmd, '.cmdAttr');
        jeedom.cmd.changeType(tr, init(_cmd.subType));
      }
    });
  }
}


$('.eqLogicAttr[data-l1key=configuration][data-l2key=cheminsonoffdiyValide]').on('change', function ()
{
	$icon = $('.eqLogicAttr[data-l1key=configuration][data-l2key=cheminsonoffdiyValide]').value();
	if($icon != '' && $icon != null)
		$('#img_device').attr("src", 'plugins/sonoffdiy/desktop/images/' + $icon + '.png');
});
$('.eqLogicAttr[data-l1key=configuration][data-l2key=localEtat]').on('change', function ()
{
	$icon = $('.eqLogicAttr[data-l1key=configuration][data-l2key=localEtat]').value();
	if($icon != '' && $icon != null)
		$('#img_local').attr("src", 'plugins/sonoffdiy/desktop/images/jeedom_' + $icon + '.png');
});
$('.eqLogicAttr[data-l1key=configuration][data-l2key=sambaEtat]').on('change', function ()
{
	$icon = $('.eqLogicAttr[data-l1key=configuration][data-l2key=sambaEtat]').value();
	if($icon != '' && $icon != null)
		$('#img_samba').attr("src", 'plugins/sonoffdiy/desktop/images/samba_' + $icon + '.png');
});
$('.eqLogicAttr[data-l1key=configuration][data-l2key=facebookEtat]').on('change', function ()
{
	//alert( 'Hello, world!' );
	$icon = $('.eqLogicAttr[data-l1key=configuration][data-l2key=facebookEtat]').value();
	if($icon != '' && $icon != null)
		$('#img_facebook').attr("src", 'plugins/sonoffdiy/desktop/images/facebook_' + $icon + '.png');
});
$('#bt_testLienPhotos').off('click').on('click', function () {
    scanLienPhotos();
});
$('#bt_cronGenerator').off('click').on('click',function(){
    jeedom.getCronSelectModal({},function (result) {
        $('.eqLogicAttr[data-l1key=configuration][data-l2key=autorefresh]').value(result.value);
    });
});
$('#bt_enregistreAlbumFB').off('click').on('click', function () {
    bt_enregistreAlbumFB();
});
$('#bt_configFacebook').off('click').on('click', function ()
{
  $('#md_modal').dialog({title: "{{Config Facebook}}"});
  //$('#md_modal').load('index.php?v=d&plugin=sonoffdiy&modal=configFacebook&iddevice='+ $('.eqLogicAttr[data-l1key=logicalId]').value()).dialog('open');
  //$('#md_modal').load('index.php?v=d&plugin=sonoffdiy&modal=configFacebook&iddevice='+ $('.eqLogicAttr[data-l1key=configuration][data-l2key=stockageLocal]').value()).dialog('open');
  $('#md_modal').load('index.php?v=d&plugin=sonoffdiy&modal=configFacebook&iddevice='+ $('.eqLogicAttr[data-l1key=id]').value()).dialog('open');
  //$('#md_modal').load('index.php?v=d&plugin=sonoffdiy&modal=configFacebook&iddevice=123').dialog('open');

});
