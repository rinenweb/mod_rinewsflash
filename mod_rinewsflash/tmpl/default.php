<?php
/**
 * @package    Mod_RiNewsflash
 * @author     Rinenweb <info@rinenweb.eu>
 * @license    GNU General Public License v2
 * @link       https://www.rinenweb.eu
 */

// No direct access to this file
defined('_JEXEC') or die; ?>
<?php
// Import the Route class
use Joomla\CMS\Router\Route;

// Build the URL to the article
$articleUrl = Route::_('index.php?option=com_content&view=article&id=' . $articleId);
?>
<div class="latest-additions">
    <?php foreach ($list as $item): ?>
        <div><?php echo htmlspecialchars($item, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php if (next($list)): // Check if there is another item in the array ?>
            <hr />
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($showLink == 1): ?>
        <a href="<?php echo $articleUrl; ?>" class="<?php echo htmlspecialchars($linkClass, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($linkText, ENT_QUOTES, 'UTF-8'); ?>
        </a>
    <?php endif; ?>
</div>

