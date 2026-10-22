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

// Get config and today via Helper
$config   = ContractorHelper::getConfig();
$warnDays = isset($config['warn_days']) ? (int) $config['warn_days'] : 30;
if ($warnDays <= 0) {
    $warnDays = 30; // Sécurité si la valeur est absente ou négative
}

$today = new \DateTime();
$today->setTime(0, 0, 0);

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

<style>
    .contractor-tool-btn {
        transition: opacity 0.2s ease, transform 0.2s ease;
    }
    .contractor-tool-btn:hover {
        opacity: 0.6;
        transform: scale(1.1);
    }
</style>

<form action="<?php echo Route::_('index.php?option=com_contractor&view=contracts'); ?>" method="post" name="adminForm" id="adminForm">
    
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
                <th width="1%" class="text-center">
                    <?php echo HTMLHelper::_('searchtools.sort', 'JSTATUS', 'a.published', $listDirn, $listOrder); ?>
                </th>
                <th>
                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_CONTRACTOR_CONTRACT_NAMELI', 'a.name', $listDirn, $listOrder); ?>
                </th>
                <th>
                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_CONTRACTOR_CONTRACT_CLIENT', 'a.client_id', $listDirn, $listOrder); ?>
                </th>
                <th width="1%" class="text-end">
                    <?php echo HTMLHelper::_('searchtools.sort', $config['currency'], 'totprice', $listDirn, $listOrder); ?>
                </th>
                <th width="5%">
                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_CONTRACTOR_CONTRACT_VALIDTHRU', 'a.valid_thru', $listDirn, $listOrder); ?>
                </th>
                <th>
                    Tags
                </th>
                <th width="1%" class="text-end">
                    &nbsp;
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($this->items as $i => $item) : ?>
                <?php
                    // --- LOGIQUE D'ÉTAT ET DE DATE ---
                    $isUnpublished = ($item->published == 0);
                    $isExpired     = false;
                    $isWarning     = false;

                    // Compute date alerts.
                    if (!empty($item->valid_thru) && $item->valid_thru !== '0000-00-00 00:00:00') {
                        $validDate = new \DateTime($item->valid_thru);
                        $validDate->setTime(0, 0, 0);
                        
                        $diff = $today->diff($validDate);
                        $daysLeft = (int) $diff->format('%R%a');

                        if ($daysLeft < 0) {
                            $isExpired = true;
                        } elseif ($daysLeft <= $warnDays) {
                            $isWarning = true;
                        }
                    }

                    // Définition des classes
                    $rowClass      = $isUnpublished ? 'text-muted' : '';
                    $dateCellClass = '';
                    $statusIcon    = '';

                    if ($isExpired) {
                        // Fond rouge uniquement si publié
                        if (!$isUnpublished) {
                            $dateCellClass = 'bg-danger text-white';
                        }
                        // L'icône s'affiche dans tous les cas
                        $statusIcon = '<span class="icon-exclamation-circle text-white pe-2" style="font-size: 24px;" aria-hidden="true" title="'.Text::_('COM_CONTRACTOR_STATUS_EXPIRED').'"></span>';
                    } elseif ($isWarning) {
                        // Fond jaune uniquement si publié
                        if (!$isUnpublished) {
                            $dateCellClass = 'bg-warning text-dark';
                        }
                        // L'icône s'affiche dans tous les cas
                        $statusIcon = '<span class="icon-exclamation-triangle text-dark pe-2" style="font-size: 24px;" aria-hidden="true" title="'.Text::_('COM_CONTRACTOR_STATUS_EXPIRING_SOON').'"></span>';
                    }
                ?>
                <tr class="<?php echo $rowClass; ?>">
                    <td>
                        <?php echo HTMLHelper::_('grid.id', $i, $item->id); ?>
                    </td>
                    <td class="<?php echo $isUnpublished ? 'text-muted' : ''; ?>">
                        <?php echo $item->id; ?>
                    </td>
                    <td class="text-center">
                        <?php echo HTMLHelper::_('jgrid.published', $item->published, $i, 'contracts.', true, 'cb'); ?>
                    </td>
                    <td>
                        <a href="<?php echo Route::_('index.php?option=com_contractor&task=contract.edit&id=' . (int) $item->id); ?>" class="text-decoration-none <?php echo $isUnpublished ? 'text-muted' : ''; ?>">
                            <?php echo $this->escape($item->name)." <small>[".$item->nblines."]</small>"; ?>
                        </a>
                    </td>
                    <td>
                        <?php if ($item->client_id) : ?>
                            <a href="<?php echo Route::_('index.php?option=com_contractor&task=client.edit&id=' . (int) $item->client_id); ?>" class="text-decoration-none <?php echo $isUnpublished ? 'text-muted' : ''; ?>">
                                <?php echo $this->escape($item->client_name)." <small>(ID ". $this->escape($item->client_id) .")</small>"; ?>
                            </a>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <?php printf("%0.2f",($item->totprice)/100); ?>
                    </td>
                    <td class="<?php echo $dateCellClass; ?> align-middle">
                        <span<?php echo $isUnpublished ? ' class="text-muted"' : ''?>>
                        <?php echo $statusIcon; echo ($item->valid_thru && $item->valid_thru != '0000-00-00 00:00:00') ? HTMLHelper::_('date', $item->valid_thru, 'Y-m-d') : '-'; ?>
                        </span>
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
                                    <div class="badge py-2 px-3 rounded-pill mb-1 d-inline-block" style="background-color: <?php echo $this->escape($tagColor); ?>; color: <?php echo $this->escape($textColor); ?>;">
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
                    <td>
                        <div class="d-flex gap-3 justify-content-end align-items-center">
                            <a href="<?php echo Route::_('index.php?option=com_contractor&task=contract.edit&id=' . (int) $item->id); ?>" class="contractor-tool-btn btn btn-primary" style="padding: 0.1em 1em;" title="Edit">
                                <span class="icon-edit" aria-hidden="true"></span>
                            </a>
                            <a href="<?php echo Route::_('index.php?option=com_contractor&task=contract.email&id=' . (int) $item->id); ?>" class="contractor-tool-btn btn btn-primary" style="padding: 0.1em 1em;" title="Email">
                                <span class="icon-mail" aria-hidden="true"></span>
                            </a>
                        </div>
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