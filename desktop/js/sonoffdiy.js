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
function CaseCocheeSamba()
{
	if (document.formulaire.caseSamba.checked==1) {
		document.formulaire.caseLocal.checked=0;
		document.formulaire.caseFacebook.checked=0;
		$('#albumsFacebook').parent().hide();
	}
}
function CaseCocheeLocal()
{
    if (document.formulaire.caseLocal.checked==1) {
		document.formulaire.caseSamba.checked=0;
		document.formulaire.caseFacebook.checked=0;
		$('#albumsFacebook').parent().hide();
	}
}
function CaseCocheeFacebook()
{
    if (document.formulaire.caseFacebook.checked==1) {
		document.formulaire.caseSamba.checked=0;
		document.formulaire.caseLocal.checked=0;
		$('#albumsFacebook').parent().show();
	}
}
function bt_enregistreAlbumFB()
{
	var albums = new Array();
	for (const album of albumsFacebook) {
		$value = $('.eqLogicAttr[data-l1key=configuration][data-l2key=albumsFacebook][data-l3key=albumfb_'+album.id+']').value();
		albums.push([album.id,$value]);
	}
  $.ajax({
      type: "POST", 
      url: "plugins/sonoffdiy/core/ajax/sonoffdiy.ajax.php", 
      data:
      {
          action: "enregistreAlbumFB",
		  albums: json_encode(albums),
		  id: $('.eqLogicAttr[data-l1key=id]').value()
      },
      dataType: 'json',
      error: function (request, status, error)
      {
          handleAjaxError(request, status, error);
      },
      success: function (data)
      { 
          if (data.state != 'ok') {
              $('#div_alert').showAlert({message: data.result, level: 'danger'});
              return;
          }
          window.location.reload();
      }
  });
}
function scanLienPhotos()
{
  $.ajax({
      type: "POST", 
      url: "plugins/sonoffdiy/core/ajax/sonoffdiy.ajax.php", 
      data:
      {
          action: "scanLienPhotos",
		  id: $('.eqLogicAttr[data-l1key=id]').value()
      },
      dataType: 'json',
      error: function (request, status, error)
      {
          handleAjaxError(request, status, error);
      },
      success: function (data)
      { 
          if (data.state != 'ok') {
              $('#div_alert').showAlert({message: data.result, level: 'danger'});
              return;
          }
          window.location.reload();
      }
  });
}

function addCmdToTable(_cmd)
{
  if (!isset(_cmd))
    var _cmd = {configuration: {}};

					var DefinitionDivPourCommandesPredefinies='style="display: none;"';
					if (init(_cmd.logicalId)=="")
					DefinitionDivPourCommandesPredefinies="";
//  if ((init(_cmd.logicalId) == 'whennextreminder') || (init(_cmd.logicalId) == '00whennextalarm') || (init(_cmd.logicalId) == 'whennextreminderlabel') || (init(_cmd.logicalId) == 'musicalalarmmusicentity') || (init(_cmd.logicalId) == 'whennextmusicalalarm')) {
								
  if ((init(_cmd.logicalId) == 'updateallalarms')) {
    return;
  }
  
  if (init(_cmd.type) == 'info')
  {
    var tr =
       '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">'
     +   '<td>'
     +     '<span class="cmdAttr" data-l1key="id"></span>'
     +   '</td>'
     +   '<td>'
     +     '<div class="row">'
     +       '<div class="col-lg-1">'
 //    +         '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fa fa-flag"></i> Icone</a>'
     +         '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>'
     +       '</div>'
     +   '<div class="col-lg-8">'
     +     '<input class="cmdAttr form-control input-sm" data-l1key="name" placeholder="{{Nom du capteur}}"></td>'
     +   '<td>'
//     +     '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>'
     +     '<input class="cmdAttr form-control type input-sm" data-l1key="type" value="info" disabled style="margin-bottom : 5px;" />'
//     +     '<span class="subType" subType="' + init(_cmd.subType) + '"></span>'
     +   '</td>'
     +   '<td>'
//     +     '<small><span class="cmdAttr"  data-l1key="configuration" data-l2key="cmd"></span> Résultat de la commande <span class="cmdAttr"  data-l1key="configuration" data-l2key="taskname"></span> (<span class="cmdAttr"  data-l1key="configuration" data-l2key="taskid"></span>)</small>'

 //    +     '<span class="cmdAttr"  data-l1key="configuration" data-l2key="value"></span>'
     +   '</td>'
     +   '<td>'
  //   +     '<input class="cmdAttr form-control input-sm" data-l1key="unite" style="width : 90px;" placeholder="{{Unite}}">'
     +   '</td>'
     +   '<td>'
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

    $('#table_cmd tbody').append(tr);
    $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
  }

  if (init(_cmd.type) == 'action')
  {
	var tr =
	'<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">'
	+   '<td>'
	+     '<span class="cmdAttr" data-l1key="id"></span>'
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


	tr  +=   '<td>';
	tr  +='<input class="cmdAttr form-control type input-sm" data-l1key="type" value="action" disabled />';
	tr  +='<div '+DefinitionDivPourCommandesPredefinies+'>';
	tr  +=     '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
	tr  +=   '</div></td>';
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
	
	  
	tr +=   '</td>'
     +   '<td>'
     +     '<span><label class="checkbox-inline"><input type="checkbox" class="cmdAttr checkbox-inline" data-l1key="isVisible" checked/>{{Afficher}}</label></span> '
     +   '</td>'
     + '<td>';

    if (is_numeric(_cmd.id))
    {
      tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
		   if (!((init(_cmd.name)=="Routine")||(init(_cmd.name)=="xxxxxxxx"))) //Masquer le bouton Tester
			  tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
	}
    tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i>'
     + '  </td>'
     + '</tr>';

    $('#table_cmd tbody').append(tr);
    var tr = $('#table_cmd tbody tr:last');
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
