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
        $articleId = (int) $articleId;
        $numberOfAdditions = (int) $numberOfAdditions;
        $order = strtolower((string) $order);

        if ($articleId <= 0 || $numberOfAdditions <= 0) {
            return array();
        }

        if ($order !== 'asc' && $order !== 'desc') {
            $order = 'desc';
        }

        $db = self::getDatabase();
        $query = $db->getQuery(true);

        // Select both introtext and fulltext from the article.
        $query->select($db->quoteName(array('introtext', 'fulltext')))
            ->from($db->quoteName('#__content'))
            ->where($db->quoteName('id') . ' = ' . $articleId)
            ->where($db->quoteName('state') . ' = 1'); // Ensure the article is published.

        $db->setQuery($query);
        $result = $db->loadObject();

        if (!$result) {
            return array();
        }

        // Combine introtext and fulltext for complete content.
        $articleContent = $result->introtext . $result->fulltext;

        // Process the content through Joomla's content plugins.
        $article = new stdClass;
        $article->text = $articleContent;

        $params = new Registry;

        self::prepareContent($article, $params);

        // Now $article->text contains the processed content.
        $processedContent = $article->text;

        // Split the content by <hr>, <hr />, or <hr class="...">.
        $pattern = '/<hr\b[^>]*\/?>/i';
        $additions = preg_split($pattern, $processedContent);

        if ($order === 'desc') {
            // Reverse the array to start with the latest additions.
            $additions = array_reverse($additions);
        }

        // Slice the array to get the specified number of additions.
        return array_slice($additions, 0, $numberOfAdditions);
    }

    protected static function getDatabase()
    {
        if (
            class_exists('Joomla\\Database\\DatabaseInterface')
            && method_exists('Joomla\\CMS\\Factory', 'getContainer')
        ) {
            try {
                return Factory::getContainer()->get('Joomla\\Database\\DatabaseInterface');
            } catch (Exception $e) {
                // Fall back to Joomla 3.x style below.
            }
        }

        return Factory::getDbo();
    }

    protected static function prepareContent(&$article, $params)
    {
        PluginHelper::importPlugin('content');

        $app = Factory::getApplication();

        // Joomla 5/6 path: concrete ContentPrepareEvent + dispatcher.
        if (
            method_exists($app, 'getDispatcher')
            && class_exists('Joomla\\CMS\\Event\\Content\\ContentPrepareEvent')
        ) {
            try {
                $eventClass = 'Joomla\\CMS\\Event\\Content\\ContentPrepareEvent';

                $event = new $eventClass(
                    'onContentPrepare',
                    array(
                        'context' => 'com_content.article',
                        'subject' => $article,
                        'params'  => $params,
                        'page'    => 0,
                    )
                );

                $app->getDispatcher()->dispatch('onContentPrepare', $event);

                // Keep compatibility if a plugin replaces the subject object.
                if (method_exists($event, 'getItem')) {
                    $preparedArticle = $event->getItem();

                    if (is_object($preparedArticle)) {
                        $article = $preparedArticle;
                    }
                } elseif (method_exists($event, 'getArgument')) {
                    $preparedArticle = $event->getArgument('subject', $article);

                    if (is_object($preparedArticle)) {
                        $article = $preparedArticle;
                    }
                }

                return;
            } catch (Exception $e) {
                // Fall through to legacy triggerEvent for maximum compatibility.
            }
        }

        // Joomla 3.x fallback, also safe for Joomla 4 sites without the concrete event class.
        if (method_exists($app, 'triggerEvent')) {
            $app->triggerEvent(
                'onContentPrepare',
                array(
                    'com_content.article',
                    &$article,
                    &$params,
                    0,
                )
            );
        }
    }
}

// Optional alias for custom code that may call the module by its extension name.
if (!class_exists('ModRiNewsflashHelper', false)) {
    class ModRiNewsflashHelper extends ModLatestAdditionsHelper
    {
    }
}
