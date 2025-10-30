<?php
/**
 ***********************************************************************************************
 * Rollenübersicht fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:  none
 *
 ***********************************************************************************************
 */

use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Roles\Entity\Role;
use Plugins\MembershipFee\classes\Config\ConfigTable;

require_once(__DIR__ . '/../../../system/common.php');
require_once(__DIR__ . '/common_function.php');

// only authorized user are allowed to start this module
if (!isUserAuthorized($_SESSION['pMembershipFee']['script_name']))
{
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

$pPreferences = new ConfigTable();
$pPreferences->read();

$role = new Role($gDb);

$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_OVERVIEW');

$page = new HtmlPage('plg-mitgliedsbeitrag-eoleoverview', $headline);
  
$gNavigation->addUrl(CURRENT_URL, $headline);

$datatable = true;
$hoverRows = true;
$classTable  = 'table table-condensed';

$table = new HtmlTable('table_role_overview', $page, $hoverRows, $datatable, $classTable);

$columnAlign  = array('left', 'right', 'right');
$table->setColumnAlignByArray($columnAlign);

$columnValues = array($gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_NAME'), 'dummy', $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBER_ACCOUNT'));
$table->addRowHeadingByArray($columnValues);

$rollen = beitragsrollen_einlesen('', array('LAST_NAME'));
foreach ($rollen as $rol_id => $data)
{
    $role->readDataById($rol_id);
    
    $columnValues = array();
    $columnValues[] = '<a href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/groups-roles/groups_roles.php', array('mode' => 'edit', 'role_uuid' => $role->getValue('rol_uuid'))). '">'.$data['rolle']. '</a>';
    $columnValues[] = expand_rollentyp($data['rollentyp']);
    $columnValues[] = count($data['members']);
    $table->addRowByArray($columnValues);
}
$table->setDatatablesGroupColumn(2);
$table->setDatatablesRowsPerPage(10);

$page->addHtml($table->show(false));

$page->show();
