<?php
/******************************************************************************
 * 
 * remapping.php
 *   
 * Neuzuordnung von Mitgliedern fuer das Admidio-Plugin Mitgliedsbeitrag
 * 
 * Copyright    : (c) 2004 - 2014 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 * 
 * Parameters:    keine
 *
 ****************************************************************************/

require_once(substr(__FILE__, 0,strpos(__FILE__, 'adm_plugins')-1).'/adm_program/system/common.php');
require_once(substr(__FILE__, 0,strpos(__FILE__, 'adm_plugins')-1).'/adm_program/system/classes/tablemembers.php');
require_once(dirname(__FILE__).'/common_function.php');

require_once($plugin_path. '/'.$plugin_folder.'/classes/configtable.php'); 

$pPreferences = new ConfigTablePMB();
$pPreferences->read();

//Vor der Neuzuordnung die altersgestaffelten Rollen auf Lücken oder Überlappungen prüfen
$arr = check_rols();
if (!in_array($gL10n->get('PMB_AGE_STAGGERED_ROLES_RESULT_OK'),$arr))
{
	$gMessage->show($gL10n->get('PMB_AGE_STAGGERED_ROLES_RESULT_ERROR2'));
}
unset($arr);

$stack = array();
$message = '';
$tablemember = new TableMembers($gDb);
$sql = '';
 
$message .= '<strong>'.$gL10n->get('PMB_REMAPPING_INFO3').'</strong><BR>';

// alle Altersrollen einlesen
$altersrollen = beitragsrollen_einlesen('alt',array('FIRST_NAME','LAST_NAME','BIRTHDAY'));

// alle Altersrollen durchlaufen
foreach ($altersrollen as $roleId => $roldata)
{
    foreach($altersrollen[$roleId]['members'] as $member => $memberdata)
    {
        if(strlen($memberdata['BIRTHDAY']) == 0)
        {
            $gMessage->show('<strong>'.$gL10n->get('SYS_ERROR').':</strong> '.$gL10n->get('PMB_REMAPPING_INFO1').' '.$memberdata['FIRST_NAME'].' '.$memberdata['LAST_NAME'].' '.$gL10n->get('PMB_REMAPPING_INFO2'));
        }
    
        $age = ageCalculator( strtotime($memberdata['BIRTHDAY']), strtotime($pPreferences->config['Altersrollen']['altersrollen_stichtag'] ));

        // ist das Alter des Mitglieds außerhalb des Altersschemas der Rolle
        if (($age < $roldata['von'] ) || ($age > $roldata['bis'] )) 
        {
            // wenn ja, dann Mitglied auf den Stack legen und Rollenmitgliedschaft löschen
        	$stack[] = array('last_name' => $memberdata['LAST_NAME'],'first_name' => $memberdata['FIRST_NAME'], 'user_id'=> $member, 'alter' => $age, 'alterstyp' => $roldata['alterstyp']);        	
        	
            $sql = 'UPDATE '.TBL_MEMBERS.'
                    SET mem_end = \''.date("Y-m-d",strtotime('-1 day')).'\'
                    WHERE mem_usr_id = '.$member.'
                    AND mem_rol_id = '.$roleId;   
            $gDb->query($sql);  
            
			// stopMembership() kann nicht verwendet werden, da es unter best. Umständen Mitgliedschaften nicht löscht
			// Beschreibung von stopMembership()
        	// 		only stop membership if there is an actual membership
			// 		the actual date must be after the beginning 
			// 		and the actual date must be before the end date			       
        	//$tablemember->stopMembership( $roleId, $member);
        	       	
        	$message .= '<BR>'.$memberdata['LAST_NAME'].' '.$memberdata['FIRST_NAME'].' '.$gL10n->get('PMB_REMAPPING_INFO4').' '.$roldata['rolle'];
        }
    } 
}

if (sizeof($stack)==0)
{
	$message .= '<BR>'.$gL10n->get('PMB_REMAPPING_INFO5');
}

// wenn ein Mitglied Angehöriger mehrerer Rollen war (dürfte eigentlich gar nicht vorkommen),
// dann wurde er auch mehrfach in das Array $stack aufgenommen
// --> doppelte Vorkommen löschen
$stack = array_map("unserialize", array_unique(array_map("serialize", $stack)));

$message .= '<BR><BR><strong>'.$gL10n->get('PMB_REMAPPING_INFO6').'</strong><BR>';

// den Stack abarbeiten
$marker = false;
foreach ($stack as $key => $stackdata)
{
    // alle Altersrollen durchlaufen und prüfen, ob das Mitglied in das Altersschema der Rolle passt
    foreach ($altersrollen as $roleId => $roldata)
    {
		if (($stackdata['alter'] <= $roldata['bis'] ) 
		&& ($stackdata['alter'] >= $roldata['von'] ) 
		&& ($stackdata['alterstyp']==$roldata['alterstyp']) 
		&& !array_key_exists($stackdata['user_id'],$roldata['members']))   
        {       	
            // das Mitglied passt in das Altersschema der Rolle und das Kennzeichen dieser Altersstaffelung passt auch
        	$tablemember->startMembership($roleId, $stackdata['user_id']);
            $message .= '<BR>'.$stackdata['last_name'].' '.$stackdata['first_name'].' '.$gL10n->get('PMB_REMAPPING_INFO4').' '.$roldata['rolle'];
                        
         	unset($stack[$key]); 
         	$marker = true;
        }
    }    
}

if (!$marker)
{
	$message .= '<BR>'.$gL10n->get('PMB_REMAPPING_INFO7');
}

if (sizeof($stack)>0)
{
 	$message .= '<BR><BR><strong>'.$gL10n->get('PMB_REMAPPING_INFO8').'</strong><BR><small>'.$gL10n->get('PMB_REMAPPING_INFO9').'</small><BR>';   
    foreach ($stack as $stackdata)
    {
        $message .= '<BR>'.$stackdata['last_name'].' '.$stackdata['first_name'].' '.$gL10n->get('PMB_REMAPPING_INFO10').' '.$gL10n->get('PMB_STAGGERING').' '.$stackdata['alterstyp'] ;  
    }
}

// set headline of the script
$headline = $gL10n->get('PMB_REMAPPING_AGE_STAGGERED_ROLES');

// create html page object
$page = new HtmlPage($headline);

$form = new HtmlForm('remapping_form', null, $page); 
$form->addDescription($message);
$form->addButton('next_page', $gL10n->get('SYS_NEXT'), array('icon' => THEME_PATH.'/icons/forward.png', 'link' => 'menue.php?show_option=remapping', 'class' => 'btn-primary'));

$page->addHtml($form->show(false));
$page->show();

?>