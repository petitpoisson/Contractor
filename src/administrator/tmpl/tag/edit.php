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

\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use XavierSpirlet\Component\Contractor\Administrator\Helper\ContractorHelper;

// Load Joomla's form validation and keepalive behavior
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

// Retrieve dynamic configuration
$config   = ContractorHelper::getConfig();

?>
<form action="<?php echo Route::_('index.php?option=com_contractor&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="tag-form" class="form-validate">
    
    <h2><?php echo Text::_('COM_CONTRACTOR_TAGEDIT') . $this->escape($this->form->getValue('value', null, Text::_('COM_CONTRACTOR_NEW_TAG_TITLE'))); ?></h2>

    <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'details', 'recall' => true, 'breakpoint' => 768]); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'details', Text::_('COM_CONTRACTOR_TAG_DETAILS')); ?>
        <fieldset class="options-form">
            <legend><?php echo Text::_('COM_CONTRACTOR_TAG_DETAILS'); ?></legend>
            <?php echo $this->form->renderFieldset('details'); ?>
        </fieldset>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>