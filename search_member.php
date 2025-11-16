<?php
use Admidio\Infrastructure\Plugins\Overview;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Roles\Entity\Role;
use Admidio\UI\Presenter\FormPresenter;

/**
 ***********************************************************************************************
 * Search Member
 *
 * Plugin shows a search field to search for members of the organisation
 *
 * Compatible with Admidio version 5.0
 *
 * @copyright 2004-2022 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */
try {
    $rootPath = dirname(__DIR__, 2);
    $pluginFolder = basename(__DIR__);

    require_once($rootPath . '/system/common.php');

    // only include config file if it exists
    if (is_file(__DIR__ . '/config.php')) {
        require_once(__DIR__ . '/config.php');
    }

    $searchMemberFormPlugin = new Overview($pluginFolder);

    // set default values if there has been no value stored in the config.php
    if (!isset($plg_show_names) || !is_numeric($plg_show_names) || $plg_show_names > 4) {
        $plg_show_names = 1;
    }

    if (!isset($plg_rolle_sql) || !is_array($plg_rolle_sql)) {
        $plg_rolle_sql = null;
    }

    if (!isset($plg_search_city) || !is_numeric($plg_search_city) || $plg_search_city > 1) {
        $plg_search_city = 0;
    }

    // Check if the role condition has been set
    if (isset($plg_rolle_sql) && is_array($plg_rolle_sql) && count($plg_rolle_sql) > 0) {
        $sqlRol = 'IN (' . implode(',', $plg_rolle_sql) . ')';
    } else {
        $sqlRol = 'IS NOT NULL';
    }

    if ($gValidLogin) {
        $form = new FormPresenter(
            id: 'adm_plugin_search_member',
            template: 'plugin.search-member-form.tpl.logged-in',
            action: ADMIDIO_URL . FOLDER_MODULES . '/overview.php',
            htmlPage: null,
            options: array('type' => 'vertical', 'method' => 'get', 'setFocus' => false, 'showRequiredFields' => false)
        );
        $placeholder = $gL10n->get('PLG_SEARCH_PLACEHOLDER');
        if ($plg_search_city == 1) {
            $placeholder = $gL10n->get('PLG_SEARCH_PLACEHOLDER_INCLUDING_CITY');
        }

        $getPlgSearchUser = admFuncVariableIsValid($_GET, 'plg_search_usr', 'string');

        $form->addInput(
            'plg_search_usr',
            '',
            $getPlgSearchUser,
            array('type' => 'search', 'placeholder' => $placeholder)
        );
        $form->addSubmitButton('plg_btn_search', $gL10n->get('PLG_SEARCH'), array('icon' => 'bi bi-search'));

        $smarty = $searchMemberFormPlugin->createSmartyObject();
        $form->addToSmarty($smarty);
        $gCurrentSession->addFormObject($form);
        echo $smarty->fetch('plugin.search-member-form.logged-in.tpl');

        if ($getPlgSearchUser) {
            $search_string = '%' . $getPlgSearchUser . '%';
            $sql = 'SELECT
                    usr_uuid,
                    usr_login_name,
                    first_name ,
                    last_name,
                    city
                FROM
                    (SELECT
                        DISTINCT usr_id,
                            usr_uuid,
                            usr_login_name,
                            last_name.usd_value AS last_name,
                            first_name.usd_value AS first_name,
                            city.usd_value AS city
                        FROM 
                            ' . TBL_USERS . ' AS users
                        LEFT JOIN ' . TBL_USER_DATA . ' AS last_name
                                ON last_name.usd_usr_id = usr_id
                            AND last_name.usd_usf_id = ? -- $gProfileFields->getProperty(\'LAST_NAME\', \'usf_id\')
                        LEFT JOIN ' . TBL_USER_DATA . ' AS first_name
                                ON first_name.usd_usr_id = usr_id
                            AND first_name.usd_usf_id = ? -- $gProfileFields->getProperty(\'FIRST_NAME\', \'usf_id\')
                        LEFT JOIN ' . TBL_USER_DATA . ' AS city
                                ON city.usd_usr_id = usr_id
                            AND city.usd_usf_id = ? -- $gProfileFields->getProperty(\'CITY\', \'usf_id\')
                        LEFT JOIN ' . TBL_MEMBERS . '
                                ON mem_usr_id = usr_id
                            AND mem_begin <= ? -- DATE_NOW
                            AND mem_end    > ? -- DATE_NOW
                        INNER JOIN ' . TBL_ROLES . '
                                ON mem_rol_id = rol_id
                            AND rol_valid  = true
                        INNER JOIN ' . TBL_CATEGORIES . '
                                ON rol_cat_id = cat_id
                            AND cat_org_id = ? -- $gCurrentOrgId
                        WHERE usr_valid = true
                            AND mem_rol_id ' . $sqlRol . '
                    ) as data
                WHERE 
                    CONCAT (first_name, \' \', last_name) LIKE ?';

            $queryParams = array(
                $gProfileFields->getProperty('LAST_NAME', 'usf_id'),
                $gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
                $gProfileFields->getProperty('CITY', 'usf_id'),
                DATE_NOW,
                DATE_NOW,
                $gCurrentOrgId,
                $search_string
            );

            if ($plg_search_city == 1) {
                $sql .= ' OR city LIKE ?';
                $queryParams[] = $search_string;
            }
            $searchUserStatement = $gDb->queryPrepared($sql, $queryParams);

            $allSearchResults = array();
            $textSearchResults = '';

            if ($searchUserStatement->rowCount() > 0) {
                while ($row = $searchUserStatement->fetch()) {
                    switch ($plg_show_names) {
                        case 1:  // first name, last name
                            $plgShowName = $row['first_name'] . ' ' . $row['last_name'];
                            break;
                        case 2:  // last name, first name
                            $plgShowName = $row['last_name'] . ', ' . $row['first_name'];
                            break;
                        case 3:  // first name
                            $plgShowName = $row['first_name'];
                            break;
                        case 4:  // Loginname
                            $plgShowName = $row['usr_login_name'];
                            break;
                        default: // first name, last name
                            $plgShowName = $row['first_name'] . ' ' . $row['last_name'];
                    }

                    $resultString = '<strong><a "title="' . $gL10n->get('SYS_SHOW_PROFILE') . '"href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array('user_uuid' => $row['usr_uuid'])) . '">' . $plgShowName . '</a></strong>';
                    if ($plg_search_city == 1) {
                        $resultString .= ' (' . $row['city'] . ')';
                    }
                    $allSearchResults[] = $resultString;
                }
                $textSearchResults = implode('<br />', $allSearchResults);
                echo $textSearchResults;
            }
        }

    } else {
        if (isset($page)) {
            echo $searchMemberFormPlugin->html('plugin.search-member-form.logged-out.tpl');
        } else {
            $searchMemberFormPlugin->showHtmlPage('plugin.search-member-form.logged-out.tpl');
        }
    }
} catch (Throwable $e) {
    echo $e->getMessage();
}
