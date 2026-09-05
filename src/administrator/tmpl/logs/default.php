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

// Retrieve list order and direction for column sorting
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo Route::_('index.php?option=com_contractor&view=logs'); ?>" method="post" name="adminForm" id="adminForm">
    
    <div class="card mb-3 shadow-sm">
        <div class="card-body py-3">
            <div class="row g-2 mb-2 align-items-center">
                <div class="col-md-3">
                    <?php echo $this->filterForm->getInput('search', 'filter'); ?>
                </div>
                <div class="col-md-2">
                    <?php echo $this->filterForm->getInput('from_date', 'filter'); ?>
                </div>
                <div class="col-md-2">
                    <?php echo $this->filterForm->getInput('until_date', 'filter'); ?>
                </div>
                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary hasTooltip" title="Search">
                        <span class="icon-search" aria-hidden="true"></span> <?php echo Text::_('JSEARCH_FILTER_SUBMIT'); ?>
                    </button>
                    <button type="button" class="btn btn-secondary hasTooltip" title="Clear filters" onclick="clearLogsFilters();">
                        <span class="icon-cancel" aria-hidden="true"></span> <?php echo Text::_('JSEARCH_FILTER_CLEAR'); ?>
                    </button>
                </div>
            </div>
            
            <div class="row g-2 align-items-center">
                <div class="col-md-2">
                    <?php echo $this->filterForm->getInput('level', 'filter'); ?>
                </div>
                <div class="col-md-3">
                    <?php echo $this->filterForm->getInput('client_id', 'filter'); ?>
                </div>
                <div class="col-md-auto ms-auto d-flex gap-2">
                    <?php echo $this->filterForm->getInput('fullordering', 'list'); ?>
                    <?php echo $this->filterForm->getInput('limit', 'list'); ?>
                </div>
            </div>
        </div>
    </div>
    <table class="table table-striped">
        <thead>
            <tr>
                <th width="15%">
                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_CONTRACTOR_LOG_DT', 'a.timedate', $listDirn, $listOrder); ?>
                </th>
                <th width="10%">
                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_CONTRACTOR_LOG_LEVEL', 'a.level', $listDirn, $listOrder); ?>
                </th>
                <th width="20%">
                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_CONTRACTOR_LOG_CLIENT', 'c.client_name', $listDirn, $listOrder); ?>
                </th>
                <th>
                    <?php echo HTMLHelper::_('searchtools.sort', 'COM_CONTRACTOR_LOG_DESC', 'a.description', $listDirn, $listOrder); ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($this->items)) : ?>
                <?php foreach ($this->items as $i => $item) : ?>
                    <?php
                        $levelLabel = 'Info';
                        $badgeClass = 'bg-info';
                        if ($item->level == 1) {
                            $levelLabel = 'Warning';
                            $badgeClass = 'bg-warning text-dark';
                        } elseif ($item->level == 2) {
                            $levelLabel = 'Error';
                            $badgeClass = 'bg-danger';
                        }
                    ?>
                    <tr>
                        <td><?php echo HTMLHelper::_('date', $item->timedate, 'Y-m-d H:i:s'); ?></td>
                        <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $levelLabel; ?></span></td>
                        <td>
                            <?php if ($item->client_id) {
                                echo $this->escape($item->client_name)." (id ".$this->escape($item->client_id).")"; 
                            } else {
                                echo ('<span class="text-muted">System</span>'); 
                            }
                            ?>
                        </td>
                        <td><?php echo $this->escape($item->description); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="4" class="text-center">
                        <div class="alert alert-info mb-0">No logs found.</div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php echo $this->pagination->getListFooter(); ?>
    
    <input type="hidden" name="prune_date" id="prune_date" value="">
    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<script>
    function clearLogsFilters() {
        document.querySelector('input[name="filter[search]"]').value = '';
        document.querySelector('input[name="filter[from_date]"]').value = '';
        document.querySelector('input[name="filter[until_date]"]').value = '';
        document.querySelector('select[name="filter[level]"]').value = '';
        document.querySelector('select[name="filter[client_id]"]').value = '';
        
        document.getElementById('adminForm').submit();
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Secure auto-submit for dropdowns
        const autoSelects = document.querySelectorAll('.js-select-submit-on-change');
        autoSelects.forEach(function(select) {
            select.addEventListener('change', function() {
                document.getElementById('adminForm').submit();
            });
        });

        // Elegant interception of toolbar button clicks
        if (window.Joomla && window.Joomla.submitbutton) {
            const originalSubmit = window.Joomla.submitbutton;
            window.Joomla.submitbutton = function(task) {
                
                // Intercept the prune task to ask for a date
                if (task === 'logs.prune') {
                    const pruneDate = prompt('<?php echo Text::_("COM_CONTRACTOR_LOG_PRUNEMSG"); ?>');
                    
                    // Cancel operation if the user clicks cancel or leaves it empty
                    if (pruneDate === null || pruneDate.trim() === '') {
                        return false;
                    }
                    
                    // Basic format validation
                    if (!/^\d{4}-\d{2}-\d{2}$/.test(pruneDate.trim())) {
                        alert('<?php echo Text::_("COM_CONTRACTOR_LOG_PRUNEMINV"); ?>');
                        return false;
                    }
                    
                    document.getElementById('prune_date').value = pruneDate.trim();
                }

                // Let Joomla execute its standard function
                originalSubmit.call(window.Joomla, task);
                
                // If the task was export, clear the parameter after 0.5s
                if (task === 'logs.export') {
                    setTimeout(function() {
                        document.getElementById('adminForm').task.value = '';
                    }, 500);
                }
            };
        }
    });
</script>