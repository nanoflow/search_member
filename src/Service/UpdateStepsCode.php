<?php
namespace AdmidioPlugin\SearchMember\Service;

use Admidio\Infrastructure\Database;
use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Plugins\LegacyConfig;

/**
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 */
final class UpdateStepsCode
{
    /**
     * @var Database
     */
    private static Database $db;

    /**
     * Set the database
     * @param Database $database The database instance
     */
    public static function setDatabase(Database $database)
    {
        self::$db = $database;
    }

    /**
     * Take over the settings of the config.php the plugin had before it kept them as preferences.
     * @throws Exception
     */
    public static function updateStep10RetrievePreviousSettings()
    {
        LegacyConfig::import('search_member', array(
            'plg_show_names'  => array('search_member_show_names', 'intval'),
            'plg_rolle_sql'   => array('search_member_roles_sql', array(self::class, 'roles')),
            'plg_search_city' => array('search_member_search_city', 'boolval')
        ));
    }

    /**
     * The roles an old setting names. An empty list does not restrict the search.
     * @param mixed $roles
     * @return array<int,mixed>
     */
    public static function roles(mixed $roles): array
    {
        return is_array($roles) ? $roles : array();
    }
}
