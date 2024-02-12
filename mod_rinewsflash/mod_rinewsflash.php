<?php
/**
 * @package    Mod_RiNewsflash
 * @author     Rinenweb <info@rinenweb.eu>
 * @license    GNU General Public License v2
 * @link       https://www.rinenweb.eu
 */

// No direct access to this file
defined('_JEXEC') or die;

// Load the helper file
require_once __DIR__ . '/helper.php';

$articleId = $params->get('article_id');
$numberOfAdditions = $params->get('number_of_additions');
// Retrieve additional module parameters
$showLink = $params->get('show_link', 0);
$linkClass = $params->get('link_class', 'btn');
$linkText = $params->get('link_text', 'More updates');


$list = ModLatestAdditionsHelper::getLatestAdditions($articleId, $numberOfAdditions);

require JModuleHelper::getLayoutPath('mod_rinewsflash');

