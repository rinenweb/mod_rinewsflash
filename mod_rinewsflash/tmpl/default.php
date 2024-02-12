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
use Joomla\CMS\Router\Route;

$articleUrl = Route::_('index.php?option=com_content&view=article&id=' . $articleId);
?>
<table class="latest-additions">
    <?php foreach ($list as $index => $item): ?>
        <tr>
            <td>
                <?php echo $item; ?>
            </td>
        </tr>
        <?php if ($index < count($list) - 1): // Check if there is another item in the array ?>
            <tr>
                <td>
                    <hr />
                </td>
            </tr>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($showLink == 1): ?>
        <tr>
            <td style="text-align: center;">
                <a href="<?php echo $articleUrl; ?>" class="<?php echo htmlspecialchars($linkClass, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($linkText, ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </td>
        </tr>
    <?php endif; ?>
</table>