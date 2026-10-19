<?php
/**
 * @package    Mod_RiNewsflash
 * @author     Rinenweb <info@rinenweb.eu>
 * @license    GNU General Public License v3
 * @link       https://www.rinenweb.eu
 */

// No direct access to this file
defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

$lastIndex = count($list) - 1;
$linkLabel = trim($linkText) !== '' ? Text::_($linkText) : Text::_('MOD_RINEWSFLASH_LINK_TEXT_DEFAULT');
?>
<table class="latest-additions">
    <?php foreach ($list as $index => $item) : ?>
        <tr>
            <td>
                <?php echo $item; ?>
            </td>
        </tr>
        <?php if ($index < $lastIndex) : ?>
            <tr>
                <td>
                    <hr />
                </td>
            </tr>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if ($showLink === 1 && $articleUrl !== '') : ?>
        <tr>
            <td style="text-align: center;">
                <a href="<?php echo $articleUrl; ?>" class="<?php echo htmlspecialchars($linkClass, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($linkLabel, ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </td>
        </tr>
    <?php endif; ?>
</table>
