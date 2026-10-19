<?php
/**
 * @package    Mod_RiNewsflash
 * @author     Rinenweb <info@rinenweb.eu>
 * @license    GNU General Public License v3
 * @link       https://www.rinenweb.eu
 */

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Helper\ModuleHelper;

require_once __DIR__ . '/helper.php';

$articleId = (int) $params->get('article_id');
$numberOfAdditions = (int) $params->get('number_of_additions', 5);
$order = $params->get('order', 'desc');
$showLink = (int) $params->get('show_link', 0);
$linkClass = $params->get('link_class', 'btn');
$linkText = $params->get('link_text', '');

$list = ModRinewsflashHelper::getLatestAdditions($articleId, $numberOfAdditions, $order);

if (!$list) {
    return;
}

$articleUrl = $showLink ? ModRinewsflashHelper::getArticleUrl($articleId) : '';

require ModuleHelper::getLayoutPath('mod_rinewsflash', $params->get('layout', 'default'));
