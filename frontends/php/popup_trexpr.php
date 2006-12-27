<?php
/* 
** ZABBIX
** Copyright (C) 2000-2005 SIA Zabbix
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
**/
?>
<?php
	require_once "include/config.inc.php";
	require_once "include/users.inc.php";

	$page["title"] = "S_CONDITION";
	$page["file"] = "popup_trexpr.php";

	define('ZBX_PAGE_NO_MENU', 1);
	
include_once "include/page_header.php";

	insert_confirm_javascript();
?>
<?php
	$operators = array(
		'<' => '<',
		'>' => '>',
		'=' => '=',
		'#' => 'NOT');
	$limited_operators = array(
		'=' => '=',
		'#' => 'NOT');

	$functions = array(
		'abschange'	=> array(
			'description'	=> 'Absolute difference between last and previous value {OP} N',
			'operators'	=> $operators
			),
		'avg'		=> array(
			'description'	=> 'Average value for period of T times {OP} N',
			'operators'	=> $operators,
			'T'             => T_ZBX_INT
			),
		'delta'		=> array(
			'description'	=> 'Difference between MAX and MIN value of T times {OP} N',
			'operators'	=> $operators,
			'T'             => T_ZBX_INT
			),
		'change'	=> array(
			'description'	=> 'Difference between last and previous value of T times {OP} N.',
			'operators'	=> $operators
			),
		'count'		=> array(
			'description'	=> 'Number of successfully retrieved values for period of time T {OP} N.',
			'operators'     => $operators,
			'T'             => T_ZBX_INT
			),
		'diff'		=> array(
			'description'	=> 'N {OP} X, where X is 1 - if last and previous values differs, 0 - otherwise.',
			'operators'     => $limited_operators
			),
		'last'	=> array(
			'description'	=> 'Last value {OP} N',
			'operators'	=> $operators
			),
		'max'		=> array(
			'description'	=> 'Maximal value for period of time T {OP} N.',
			'operators'     => $operators,
			'T'             => T_ZBX_INT
			),
		'min'		=> array(
			'description'	=> 'Minimal value for period of time T {OP} N.',
			'operators'     => $operators,
			'T'             => T_ZBX_INT
			),
		'prev'		=> array(
			'description'	=> 'Previous value {OP} N.',
			'operators'     => $operators
			),
		'str'		=> array(
			'description'	=> 'Find string T last value. N {OP} X, where X is 1 - if found, 0 - otherwise',
			'operators'     => $limited_operators,
			'T'		=> T_ZBX_STR
			),
		'sum'		=> array(
			'description'	=> 'Sum of values for period of time T {OP} N',
			'operators'     => $operators,
			'T'             => T_ZBX_INT
			)
		
	);
	
		
//		VAR			TYPE	OPTIONAL FLAGS	VALIDATION	EXCEPTION
	$fields=array(
		"dstfrm"=>	array(T_ZBX_STR, O_MAND,P_SYS,	NOT_EMPTY,	NULL),
		"dstfld1"=>	array(T_ZBX_STR, O_MAND,P_SYS,	NOT_EMPTY,	NULL),
		
		"expression"=>	array(T_ZBX_STR, O_OPT, null,	null,		null),

		"itemid"=>	array(T_ZBX_INT, O_OPT,	null,	null,						'isset({insert})'),
		"expr_type"=>	array(T_ZBX_STR, O_OPT,	null,	NOT_EMPTY,					'isset({insert})'),
		"param"=>	array(T_ZBX_STR, O_OPT,	null,	0,						'isset({insert})'),
		"paramtype"=>	array(T_ZBX_INT, O_OPT, null,	IN(PARAM_TYPE_SECONDS.','.PARAM_TYPE_COUNTS),	'isset({insert})'),
		"value"=>	array(T_ZBX_STR, O_OPT,	null,	NOT_EMPTY,					'isset({insert})'),

		"insert"=>	array(T_ZBX_STR,	O_OPT,	P_SYS|P_ACT,	NULL,	NULL)
	);

	check_fields($fields);

	if(isset($_REQUEST['expression']))
	{
		$pats = array(
				array(
					'pat' => '\{([[:print:]]{1,}):([[:print:]]{1,})\.([[:print:]]{1,})\(([[:print:]]{1,})\)\}'.
						'(['.implode('',array_keys($operators)).'])([[:print:]]{1,})',
					'idx' => array('host' => 1, 'key' => 2, 'function' => 3, 'param' => 4, 'operator' => 5, 'value'=>6)
					)
			);

		foreach($pats as $pat)
		{
			if($res = eregi($pat['pat'],$_REQUEST['expression'],$expr_res))
			{
				if(isset($pat['idx']['host']) && isset($pat['idx']['key']))
				{
					$itemid = DBfetch(DBselect('select i.itemid from items i, hosts h '.
							' where i.hostid=h.hostid and h.host='.zbx_dbstr($expr_res[$pat['idx']['host']]).
							' and i.key_='.zbx_dbstr($expr_res[$pat['idx']['key']])));

					$_REQUEST['itemid'] = $itemid['itemid'];
				}
				
				if(isset($pat['idx']['param']))
				{
					$_REQUEST['paramtype'] = PARAM_TYPE_SECONDS;
					$_REQUEST['param'] = $expr_res[$pat['idx']['param']];
					if($pat['idx']['param'][0] == '#')
					{
						$_REQUEST['paramtype'] = PARAM_TYPE_COUNTS;
						$_REQUEST['param'] = ltrim('#', $expr_res[$pat['idx']['param']]);
					}
				}
					
				$operator = '=';
				if(isset($pat['idx']['operator'])) $operator = $expr_res[$pat['idx']['operator']];
				
				if(isset($pat['idx']['function'])) $_REQUEST['expr_type'] = $expr_res[$pat['idx']['function']].'['.$operator.']';
					
				
				if(isset($pat['idx']['value'])) $_REQUEST['value'] = $expr_res[$pat['idx']['value']];
				
				break;
			}
		}
	}
	unset($expr_res);

	$dstfrm		= get_request("dstfrm",		0);	// destination form
	$dstfld1	= get_request("dstfld1",	'');	// destination field
	$itemid		= get_request("itemid",		0);

	$denyed_hosts	= get_accessible_hosts_by_user($USER_DETAILS,PERM_READ_ONLY,PERM_MODE_LT);
	
	if($item_data = DBfetch(DBselect("select distinct h.host,i.* from hosts h,items i ".
		" where h.hostid=i.hostid and h.hostid not in (".$denyed_hosts.")".
		" and i.itemid=".$itemid)))
	{
		$description = $item_data['host'].':'.item_description($item_data["description"],$item_data["key_"]);
	}
	else
	{
		$itemid = 0;
		$description = '';
	}

	$expr_type	= get_request("expr_type",	'last[=]');
	if(eregi('^([a-z]{1,})\[(['.implode('',array_keys($operators)).'])\]$',$expr_type,$expr_res))
	{
		$function = $expr_res[1];
		$operator = $expr_res[2];

		if(!in_array($function, array_keys($functions)))	unset($function);
	}
	unset($expr_res);

	if(!isset($function))	$function = 'last';
		
	if(!in_array($operator, array_keys($functions[$function]['operators'])))	unset($operator);
	if(!isset($operator))	$operator = '=';
	
	$expr_type = $function.'['.$operator.']';
	
	$param		= get_request('param',	0);
	$paramtype	= get_request('paramtype',	PARAM_TYPE_SECONDS);
	$value		= get_request('value',		0);
	
?>
<script language="JavaScript" type="text/javascript">
<!--
function add_var_to_opener_obj(obj,name,value)
{
        new_variable = window.opener.document.createElement('input');
        new_variable.type = 'hidden';
        new_variable.name = name;
        new_variable.value = value;

        obj.appendChild(new_variable);
}

function InsertText(obj, value)
{
	if (navigator.appName == "Microsoft Internet Explorer") {
		obj.focus();
		var s = window.opener.document.selection.createRange();
		s.text = value;
	} else if (obj.selectionStart || obj.selectionStart == '0') {
		var s = obj.selectionStart;
		var e = obj.selectionEnd;
		obj.value = obj.value.substring(0, s) + value + obj.value.substring(e, obj.value.length);
	} else {
		obj.value += value;
	}
}
-->
</script>
<?php

	if(isset($_REQUEST['insert']))
	{

		$expression = sprintf("{%s:%s.%s(%s%s)}%s%s", 
			$item_data['host'],
			$item_data['key_'],
			$function,
			$paramtype == PARAM_TYPE_COUNTS ? '#' : '',
			$param,
			$operator,
			$value);

?>
<script language="JavaScript" type="text/javascript">
<!--
var form = window.opener.document.forms['<?php echo $dstfrm; ?>'];

if(form)
{
	var el = form.elements['<?php echo $dstfld1; ?>'];

	if(el)
	{
		InsertText(el, '<?php echo $expression; ?>');
		window.close();
	}
}
-->
</script>
<?php
	}

	echo BR;

	$form = new CFormTable(S_CONDITION);
	$form->SetHelp('config_triggers.php');
	$form->SetName('expression');
	$form->AddVar('dstfrm', $dstfrm);
	$form->AddVar('dstfld1', $dstfld1);

	$form->AddVar('itemid',$itemid);
	$form->AddRow(S_ITEM, array(
		new CTextBox('description', $description, 50, 'yes'),
		new CButton('select', S_SELECT, "return PopUp('popup.php?dstfrm=".$form->GetName().
				"&dstfld1=itemid&dstfld2=description&".
				"srctbl=items&srcfld1=itemid&srcfld2=description',0,0,'zbx_popup_item');")
		));

	$cmbFnc = new CComboBox('expr_type', $expr_type	, 'submit()');
	foreach($functions as  $id => $f)
	{
		foreach($f['operators'] as $op => $txt_op)
		{
			$cmbFnc->AddItem($id.'['.$op.']', str_replace('{OP}', $txt_op, $f['description']));
		}
	}
	$form->AddRow(S_FUNCTION, $cmbFnc);

	if(isset($functions[$function]['T']))
	{
		if($functions[$function]['T'] == T_ZBX_INT)
		{
			$cmbParamType = new CComboBox('paramtype', $paramtype);
			$cmbParamType->AddItem(PARAM_TYPE_SECONDS, S_SECONDS);
			$cmbParamType->AddItem(PARAM_TYPE_COUNTS, S_COUNTS);
			
			$form->AddRow(S_LAST_OF.' T', array(
				new CNumericBox('param', $param, 10),
				$cmbParamType
				)); 
		}
		else
		{
			$form->AddRow('T', new CTextBox('param', $param, 30));
			$form->AddVar('paramtype', PARAM_TYPE_SECONDS);
		}
	}
	else
	{
		$form->AddVar('paramtype', PARAM_TYPE_SECONDS);
		$form->AddVar('param', 0);
	}

	$form->AddRow('N', new CTextBox('value', $value, 10));
	
	$form->AddItemToBottomRow(new CButton('insert',S_INSERT));
	$form->Show();
?>
<?php

include_once "include/page_footer.php";

?>
