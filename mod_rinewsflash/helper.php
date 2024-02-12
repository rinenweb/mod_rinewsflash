<?php
/**
 * @package Joomla.Site
 * @subpackage Mod_RiNewsflash
 * @author Rinenweb <info@rinenweb.eu>
 * @link https://www.rinenweb.eu
 * @license GNU General Public License v2
 */
// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Filter\OutputFilter;

class ModLatestAdditionsHelper
{
    public static function getLatestAdditions($articleId, $numberOfAdditions)
    {
        // Initialize an empty array to store the latest additions
        $latestAdditions = [];

        // Get the database object
        $db = Factory::getDbo();

        // Create a new query object
        $query = $db->getQuery(true);

        // Select the full article text from the content table
        $query->select($db->quoteName('fulltext'))
              ->from($db->quoteName('#__content'))
              ->where($db->quoteName('id') . ' = ' . (int) $articleId);

        // Set the query and load the result
        $db->setQuery($query);
        $articleFullText = $db->loadResult();

        // Check if the article text is not empty
        if (!empty($articleFullText)) {
            // Split the article text by <hr /> to get individual additions
            $additions = explode('<hr>', $articleFullText);

            // Reverse the array to start with the latest additions
            $additions = array_reverse($additions);

            // Slice the array to get only the number of additions specified
            $latestAdditions = array_slice($additions, 0, $numberOfAdditions);
        }

        // Return the latest additions
        return $latestAdditions;
    }
}

