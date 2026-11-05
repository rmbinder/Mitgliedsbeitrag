<?php
/**
 ***********************************************************************************************
 * Functions for viewdefinitions
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameter:
 * 
 * view_definition     : Parameter indicating which view is being saved
 *                      - payments_fields
 *                      - mandates_fields
 *                      - duedates_fields
 *                      - bill_fields
 ***********************************************************************************************
 */
use Admidio\Infrastructure\Exception;
use Plugins\MembershipFee\classes\Config\ConfigTable;

try {
    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/common_function.php');
    require (__DIR__ . '/../../../system/login_valid.php');

    $getViewDefinition = admFuncVariableIsValid($_GET, 'view_definition', 'string', array(
        'defaultValue' => 'payments_fields',
        'validValues' => array(
            'payments_fields',
            'mandates_fields',
            'duedates_fields',
            'bill_fields'
        )
    ));

    $pPreferences = new ConfigTable();
    $pPreferences->read();

    // check the CSRF token of the form against the session token
    $myListForm = $gCurrentSession->getFormObject($_POST['adm_csrf_token']);
    if ($_POST['adm_csrf_token'] !== $myListForm->getCsrfToken()) {
        throw new Exception('Invalid or missing CSRF token!');
    }

    // At least one field should be assigned (has a non-empty value)
    if (empty($_POST['column']) || ! is_array($_POST['column']) || count(array_filter($_POST['column'], static function ($v) {
        return trim((string) $v) !== '';
    })) === 0) {
        throw new Exception('SYS_FIELD_EMPTY', array(
            '1. ' . $gL10n->get('SYS_COLUMN')
        ));
    }

    // go through all existing columns
    $columns = array_values($_POST['column'] ?? []);

    $pPreferences->config['columnconfig'][$getViewDefinition] = array();
    foreach ($columns as $column) {
        // $pPreferences->config['columnconfig'][$conf] = array();

        $usfId = (int) $gProfileFields->getProperty($column, 'usf_id');

        if ($usfId > 0) {
            $pPreferences->config['columnconfig'][$getViewDefinition][] = 'p' . $usfId;
        }
    }
    // doppelte Einträge führen zu einem SQL-FEHLER in der Funktion list_members
    $pPreferences->config['columnconfig'][$getViewDefinition] = array_unique($pPreferences->config['columnconfig'][$getViewDefinition]);

    $pPreferences->save();

    echo json_encode(array(
        'status' => 'success'
    ));
    exit();
} catch (Throwable $e) {
    handleException($e, true);
}
