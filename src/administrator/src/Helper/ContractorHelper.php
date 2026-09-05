<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_contractor
 *
 * @author      Xavier Spirlet (Petitpoisson) <https://www.petitpoisson.be>
 * @copyright   Copyright (C) 2026 Xavier Spirlet. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 * @link        https://www.petitpoisson.be
 */

namespace XavierSpirlet\Component\Contractor\Administrator\Helper;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Log\Log;

/**
 * General utility helper class for the Contractor component.
 */
class ContractorHelper
{
    /**
     * Cached configuration array to prevent multiple database queries per page load.
     * @var array|null
     */
    protected static $config = null;

    /**
     * Retrieves the component configuration from the database.
     *
     * @return  array  Associative array of configuration values (item => value).
     */
    public static function getConfig(): array
    {
        // Only load from the database if the array hasn't been cached yet
        if (self::$config === null) {
            // Replaced deprecated Factory::getDatabase() with Joomla 5/6 DI Container
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->getQuery(true);

            $query->select($db->quoteName(['item', 'value']))
                  ->from($db->quoteName('#__ctr_config'));

            $db->setQuery($query);
            $results = $db->loadObjectList();

            self::$config = [];
            
            if ($results) {
                foreach ($results as $row) {
                    self::$config[$row->item] = $row->value;
                }
            }

            $query = $db->getQuery(true);

            $query->select($db->quoteName('manifest_cache'))
                  ->from($db->quoteName('#__extensions'))
                  ->where($db->quoteName('type') . ' = ' . $db->quote('component'))
                  ->where($db->quoteName('element') . ' = ' . $db->quote('com_contractor'));

            $db->setQuery($query);
            $manifestCache = $db->loadResult();

            $version = 'Unknown';

            if ($manifestCache) {
                // Decode the JSON data stored during installation
                $manifest = json_decode($manifestCache, true);
                self::$config["version"] = $manifest['version'] ?? 'Unknown';
            }
        }

        return self::$config;
    }

    /**
     * Writes a log entry into the database.
     *
     * @param   int|null $clientId     The ID of the associated client (null if none).
     * @param   int      $level        The severity level (0 = info, 1 = warning, 2 = error).
     * @param   string   $description  The log message.
     *
     * @return  bool    True on success, false on failure.
     */
    public static function writeLog(?int $clientId, int $level, string $description): bool
    {
        // Replaced deprecated Factory::getDatabase() with Joomla 5/6 DI Container
        $db    = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true);

        // Correctly format NULL for SQL to avoid Foreign Key constraint errors on ID 0
        $clientVal = empty($clientId) ? 'NULL' : (int) $clientId;

        $columns = ['client_id', 'level', 'description'];
        $values  = [
            $clientVal,
            (int) $level,
            $db->quote($description)
        ];

        $query->insert($db->quoteName('#__ctr_log'))
              ->columns($db->quoteName($columns))
              ->values(implode(', ', $values));

        $db->setQuery($query);

        try {
            return (bool) $db->execute();
        } catch (\Exception $e) {
            // Log the error to Joomla's native error file instead of failing completely silently
            Log::add('Contractor writeLog failed: ' . $e->getMessage(), Log::ERROR, 'com_contractor');
            return false;
        }
    }

    /**
     * Determines the best text color (black or white) for a given background hex color.
     *
     * @param   string  $hexColor  The background color in hex format (e.g., '#ff0000' or 'ff0000').
     *
     * @return  string  '#000000' for dark text, '#ffffff' for light text.
     */
    public static function getContrastColor(string $hexColor): string
    {
        // Remove the hash if it exists
        $hexColor = str_replace('#', '', $hexColor);

        // Handle 3-character hex codes (e.g., F00 -> FF0000)
        if (strlen($hexColor) === 3) {
            $hexColor = $hexColor[0] . $hexColor[0] . $hexColor[1] . $hexColor[1] . $hexColor[2] . $hexColor[2];
        }

        // Default to black if the string is invalid
        if (strlen($hexColor) !== 6 || !ctype_xdigit($hexColor)) {
            return '#000000';
        }

        // Convert hex to RGB
        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));

        // Calculate YIQ ratio
        $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;

        // Return black for light backgrounds, white for dark backgrounds
        return ($yiq >= 128) ? '#000000' : '#ffffff';
    }
}