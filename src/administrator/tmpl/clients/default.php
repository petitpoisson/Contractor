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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use XavierSpirlet\Component\Contractor\Administrator\Helper\ContractorHelper;

HTMLHelper::_('behavior.multiselect');

// Retrieve list order and direction for column sorting
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo Route::_('index.php?option=com_contractor&view=clients'); ?>" method="post" name="adminForm" id="adminForm">
    
    <?php echo \Joomla\CMS\Layout\LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

    <table class="table table-striped">
        <thead>
            <tr>
                <th width="1%">
                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                </th>
                <th width="5%">
                    <?php echo HTMLHelper::_('searchtools.sort', 'ID', 'a.id', $listDirn, $listOrder); ?>
                </th>
                <th width="5%" class="text-center">
                    <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.published', $listDirn, $listOrder); ?>
                </th>
                <th>
                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_CONTRACTOR_CLIENT_PNAME', 'a.client_name', $listDirn, $listOrder); ?>
                </th>
                <th>
                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_CONTRACTOR_CLIENT_CY', 'a.company_name', $listDirn, $listOrder); ?>
                </th>
                <th>
                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_CONTRACTOR_CLIENT_MAIL', 'a.email', $listDirn, $listOrder); ?>
                </th>
                <th>
                    <?php echo HTMLHelper::_('searchtools.sort','COM_CONTRACTOR_CLIENT_CTRACT', 'nbcontract', $listDirn, $listOrder); ?>
                </th>
                <th>
                    <?php echo HTMLHelper::_('searchtools.sort','COM_CONTRACTOR_CLIENT_INVOICES', 'nbinvoices', $listDirn, $listOrder); ?>
                </th>
                <th>
                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_CONTRACTOR_CLIENT_TAG', 't.value', $listDirn, $listOrder); ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($this->items as $i => $item) : ?>
                <tr>
                    <td>
                        <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                    </td>
                    <td>
                        <?php echo $item->id; ?>
                    </td>
                    <td class="text-center">
                        <?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'clients.', true, 'cb'); ?>
                    </td>
                    <td>
                        <a href="<?php echo Route::_('index.php?option=com_contractor&task=client.edit&id=' . $item->id); ?>">
                            <?php echo $this->escape($item->client_name); ?>
                        </a>
                    </td>
                    <td>
                        <?php echo $this->escape($item->company_name); ?>
                    </td>
                    <td>
                        <?php echo $this->escape($item->email); ?>
                    </td>
                    <td>
                        <?php echo $this->escape($item->nbcontract) . " (" . $this->escape($item->nbactive) . ")"; ?>
                    </td>
                    <td>
                        <?php echo $this->escape($item->nbinvoices); ?>
                    </td>
                    <td>
                        <?php if (!empty($item->tag_value)) : ?>
                            <?php 
                            $bgColor   = !empty($item->tag_color) ? $this->escape($item->tag_color) : '#ffffff';
                            $textColor = ContractorHelper::getContrastColor($bgColor);
                            ?>
                            <div class="badge py-2 rounded" style="background-color: <?php echo $bgColor; ?>; color: <?php echo $textColor; ?>;">
                                <?php echo $this->escape($item->tag_value); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php echo $this->pagination->getListFooter(); ?>
    
    <input type="hidden" name="task" value="">
    <input type="hidden" name="boxchecked" value="0">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>