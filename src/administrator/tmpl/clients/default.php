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

// Detecting active filters
$fPub = $this->state->get('filter.published');
$fTag = $this->state->get('filter.tag_id');
$fCli = $this->state->get('filter.client_id');

$hasActiveFilters = ($fPub !== '' && $fPub !== null) || !empty($fTag) || !empty($fCli);
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (<?php echo $hasActiveFilters ? 'true' : 'false'; ?>) {
        document.body.classList.add('filters-shown');
                const filterContainer = document.querySelector('.js-stools-container-filters');
        if (filterContainer) {
            filterContainer.classList.add('js-stools-container-filters-visible');
        }
        
        const filterBtn = document.querySelector('.js-stools-btn-filter');
        if (filterBtn) {
            filterBtn.classList.remove('collapsed');
            filterBtn.setAttribute('aria-expanded', 'true');
        }
    }
});
</script>
<form action="<?php echo Route::_('index.php?option=com_contractor&view=clients'); ?>" method="post" name="adminForm" id="adminForm">
    
    <?php echo \Joomla\CMS\Layout\LayoutHelper::render('joomla.searchtools.default', ['view' => $this]); ?>

    <table class="table table-striped">
        <thead>
            <tr>
                <th width="1%">
                    <?php echo HTMLHelper::_('grid.checkall'); ?>
                </th>
                <th width="1%">
                    <?php echo HTMLHelper::_('searchtools.sort', 'ID', 'a.id', $listDirn, $listOrder); ?>
                </th>
                <th width="5%" class="text-center">
                    <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.published', $listDirn, $listOrder); ?>
                </th>
                <th width="">
                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_CONTRACTOR_CLIENT_PNAME', 'a.client_name', $listDirn, $listOrder); ?>
                </th>
                <th width="1%">
                    <?php echo Text::_('COM_CONTRACTOR_CLIENT_CTRACT'); ?>
                </th>
                <th>
                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_CONTRACTOR_CLIENT_TAGS', 't.value', $listDirn, $listOrder); ?>
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
                        <?php if (!empty($item->company_name)) : ?>
                            <br><small class="text-muted"><?php echo $this->escape($item->company_name); ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo $this->escape($item->nbcontract) . " / " . $this->escape($item->nbinvoices); ?>
                    </td>
                    <td>
                        <?php if (!empty($item->tag_data)) : ?>
                            <?php 
                            $tags = explode(';;;', $item->tag_data);
                            foreach ($tags as $tagString) :
                                $tagParts  = explode('|||', $tagString);
                                $tagValue  = $tagParts[0] ?? '';
                                $tagColor  = $tagParts[1] ?? '#ffffff';
                                $textColor = ContractorHelper::getContrastColor($tagColor);
                                
                                if (!empty($tagValue)) :
                            ?>
                                    <div class="badge py-2 px-3 rounded-pill mb-1" style="background-color: <?php echo $this->escape($tagColor); ?>; color: <?php echo $this->escape($textColor); ?>;">
                                        <?php echo $this->escape($tagValue); ?>
                                    </div>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        <?php else : ?>
                            n/a
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
