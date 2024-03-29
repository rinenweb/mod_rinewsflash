<?php
/**
 * @package Joomla.Site
 * @subpackage Mod_RiNewsflash
 * @author Rinenweb <info@rinenweb.eu>
 * @link https://www.rinenweb.eu
 * @license GNU General Public License v3
 */
// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Registry\Registry;

class ModLatestAdditionsHelper
{
    public static function getLatestAdditions($articleId, $numberOfAdditions, $order)
    {
        $db = Factory::getDbo();
        $query = $db->getQuery(true);

        // Select both introtext and fulltext from the article
        $query->select($db->quoteName(array('introtext', 'fulltext')))
              ->from($db->quoteName('#__content'))
              ->where($db->quoteName('id') . ' = ' . $db->quote($articleId))
              ->where($db->quoteName('state') . ' = 1'); // Ensure the article is published

        $db->setQuery($query);
        $result = $db->loadObject();

        if (!$result) {
            return []; // No article found, or error occurred
        }

        // Combine introtext and fulltext for complete content
        $articleContent = $result->introtext . $result->fulltext;

        // Process the content through Joomla's content plugins
        $article = new stdClass;
        $article->text = $articleContent; // Set the fetched content to the article object

        $params = new Registry; // Create a registry object for any needed parameters
        PluginHelper::importPlugin('content');

        // Trigger the content preparation plugins
        Factory::getApplication()->triggerEvent('onContentPrepare', ['com_content.article', &$article, &$params, 0]);

        // Now $article->text contains the processed content
        $processedContent = $article->text;

        // Use preg_split with a regex to split the content by <hr> or <hr />
        $pattern = '/<hr\s*\/?>/i'; // This pattern matches both <hr> and <hr />, case-insensitive
        $additions = preg_split($pattern, $processedContent);

        if ($order == 'desc') {
        // Reverse the array to start with the latest additions
        $additions = array_reverse($additions);
    }
    // Slice the array to get the specified number of additions
    $latestAdditions = array_slice($additions, 0, $numberOfAdditions);

    return $latestAdditions;
    }
}
