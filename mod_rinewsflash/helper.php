<?php
/**
 * @package    Joomla.Site
 * @subpackage Mod_RiNewsflash
 * @author     Rinenweb <info@rinenweb.eu>
 * @link       https://www.rinenweb.eu
 * @license    GNU General Public License v3
 */

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Router\Route;
use Joomla\Registry\Registry;

class ModRinewsflashHelper
{
    /**
     * Article metadata (id, catid, alias, language) loaded by getLatestAdditions(), keyed by article ID.
     *
     * @var array
     */
    protected static $articles = array();

    /**
     * Article IDs currently being rendered, to stop a module loading itself through its own source article.
     *
     * @var array
     */
    protected static $rendering = array();

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

        // Recursion guard: the source article could contain {loadmoduleid} pointing at this module.
        if (isset(self::$rendering[$articleId])) {
            return array();
        }

        $result = self::loadArticle($articleId);

        if (!$result) {
            return array();
        }

        self::$articles[$articleId] = $result;

        // Give content plugins a proper article-like object, so plugins that read id/catid/language
        // under the com_content.article context don't hit undefined properties.
        $article = new stdClass;
        $article->id        = (int) $result->id;
        $article->catid     = (int) $result->catid;
        $article->title     = $result->title;
        $article->alias     = $result->alias;
        $article->language  = $result->language;
        $article->introtext = $result->introtext;
        $article->fulltext  = $result->fulltext;
        $article->text      = $result->introtext . $result->fulltext;

        self::$rendering[$articleId] = true;

        try {
            self::prepareContent($article, new Registry);
        } catch (Exception $e) {
            unset(self::$rendering[$articleId]);

            throw $e;
        }

        unset(self::$rendering[$articleId]);

        // Split the content by <hr>, <hr />, or <hr class="...">.
        $additions = preg_split('/<hr\b[^>]*\/?>/i', $article->text);

        // Drop empty chunks (e.g. a trailing <hr>, or stray <p></p> left around an <hr>).
        $additions = array_values(array_filter(array_map(array(__CLASS__, 'cleanChunk'), $additions), array(__CLASS__, 'hasVisibleContent')));

        if ($order === 'desc') {
            // Reverse the array to start with the latest additions.
            $additions = array_reverse($additions);
        }

        // Slice the array to get the specified number of additions.
        return array_slice($additions, 0, $numberOfAdditions);
    }

    /**
     * SEF-friendly URL of the source article, or an empty string if it was not loaded.
     */
    public static function getArticleUrl($articleId)
    {
        $articleId = (int) $articleId;

        if (!isset(self::$articles[$articleId])) {
            return '';
        }

        $item = self::$articles[$articleId];
        $slug = $item->id . ':' . $item->alias;

        if (class_exists('Joomla\\Component\\Content\\Site\\Helper\\RouteHelper')) {
            // Joomla 4/5/6
            $link = \Joomla\Component\Content\Site\Helper\RouteHelper::getArticleRoute($slug, $item->catid, $item->language);
        } else {
            // Joomla 3
            if (!class_exists('ContentHelperRoute')) {
                JLoader::register('ContentHelperRoute', JPATH_SITE . '/components/com_content/helpers/route.php');
            }

            $link = ContentHelperRoute::getArticleRoute($slug, $item->catid, $item->language);
        }

        return Route::_($link);
    }

    /**
     * Trim a chunk and remove wrapper tags orphaned by the split, e.g. the "<p>" and "</p>"
     * left over when the editor wrapped the separator as <p><hr></p>.
     */
    public static function cleanChunk($chunk)
    {
        $chunk = trim($chunk);
        $chunk = preg_replace('#^(?:\s*</(?:p|div)>)+#i', '', $chunk);
        $chunk = preg_replace('#(?:<(?:p|div)\b[^>]*>\s*)+$#i', '', $chunk);

        return trim($chunk);
    }

    public static function hasVisibleContent($chunk)
    {
        $visible = strip_tags(str_replace('&nbsp;', ' ', $chunk), '<img><iframe><video><audio><object><embed><table>');

        return trim($visible) !== '';
    }

    protected static function loadArticle($articleId)
    {
        $db = self::getDatabase();
        $query = method_exists($db, 'createQuery') ? $db->createQuery() : $db->getQuery(true);

        $levels = array_map('intval', self::getViewLevels());
        $now    = $db->quote(Factory::getDate()->toSql());

        $query->select($db->quoteName(array('id', 'catid', 'title', 'alias', 'language', 'introtext', 'fulltext')))
            ->from($db->quoteName('#__content'))
            ->where($db->quoteName('id') . ' = ' . (int) $articleId)
            ->where($db->quoteName('state') . ' = 1')
            ->where($db->quoteName('access') . ' IN (' . implode(',', $levels) . ')');

        if (version_compare(JVERSION, '4.0', '<')) {
            // Joomla 3 stores "no date" as the null date instead of NULL.
            $nullDate = $db->quote($db->getNullDate());

            $query->where('(' . $db->quoteName('publish_up') . ' = ' . $nullDate . ' OR ' . $db->quoteName('publish_up') . ' <= ' . $now . ')')
                ->where('(' . $db->quoteName('publish_down') . ' = ' . $nullDate . ' OR ' . $db->quoteName('publish_down') . ' > ' . $now . ')');
        } else {
            $query->where('(' . $db->quoteName('publish_up') . ' IS NULL OR ' . $db->quoteName('publish_up') . ' <= ' . $now . ')')
                ->where('(' . $db->quoteName('publish_down') . ' IS NULL OR ' . $db->quoteName('publish_down') . ' > ' . $now . ')');
        }

        $db->setQuery($query);

        return $db->loadObject();
    }

    protected static function getViewLevels()
    {
        $app  = Factory::getApplication();
        $user = method_exists($app, 'getIdentity') ? $app->getIdentity() : null;

        if (!$user && method_exists('Joomla\\CMS\\Factory', 'getUser')) {
            $user = Factory::getUser();
        }

        $levels = $user ? $user->getAuthorisedViewLevels() : array();

        // Fall back to the Public level if no user could be resolved.
        return $levels ? $levels : array(1);
    }

    protected static function getDatabase()
    {
        // Joomla 4/5/6
        if (
            interface_exists('Joomla\\Database\\DatabaseInterface')
            && method_exists('Joomla\\CMS\\Factory', 'getContainer')
        ) {
            return Factory::getContainer()->get('Joomla\\Database\\DatabaseInterface');
        }

        // Joomla 3
        return Factory::getDbo();
    }

    protected static function prepareContent(&$article, $params)
    {
        PluginHelper::importPlugin('content');

        $app = Factory::getApplication();

        // Joomla 5/6: concrete ContentPrepareEvent + dispatcher.
        // The dispatch is deliberately NOT wrapped in try/catch: falling back after a plugin
        // throws would run every content plugin a second time on half-processed text.
        if (
            method_exists($app, 'getDispatcher')
            && class_exists('Joomla\\CMS\\Event\\Content\\ContentPrepareEvent')
        ) {
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
            $preparedArticle = $event->getItem();

            if (is_object($preparedArticle)) {
                $article = $preparedArticle;
            }

            return;
        }

        // Joomla 3 and 4.
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

// Backward-compatible alias for custom code that used the original class name.
if (!class_exists('ModLatestAdditionsHelper', false)) {
    class ModLatestAdditionsHelper extends ModRinewsflashHelper
    {
    }
}
