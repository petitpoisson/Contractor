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
use Joomla\CMS\Uri\Uri;
use Joomla\Event\Event;
use Joomla\CMS\Plugin\PluginHelper;
use XavierSpirlet\Component\Contractor\Administrator\Helper\ContractorHelper;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

$config   = ContractorHelper::getConfig();
$warnDays = $config['warn_days'] ?? 30;
$today    = new \DateTime();
$today->setTime(0, 0, 0);
$clientId = (int) $this->item->id;

if (!empty($this->item->contracts)) {
    usort($this->item->contracts, function($a, $b) {
        return $a->valid_thru <=> $b->valid_thru;
    });
}
?>
<form action="<?php echo Route::_('index.php?option=com_contractor&layout=edit&id=' . $clientId); ?>" method="post" name="adminForm" id="client-form" class="form-validate" enctype="multipart/form-data" data-1p-ignore="true" data-lpignore="true" data-bwignore="true">
    
    <h2><?php echo $this->escape($this->form->getValue('client_name', null, Text::_('COM_CONTRACTOR_NEW_CLIENT_TITLE'))); ?></h2>

    <?php echo HTMLHelper::_('uitab.startTabSet', 'myTab', ['active' => 'details', 'recall' => false, 'breakpoint' => 768]); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'details', Text::_('COM_CONTRACTOR_CLIENT_DETAILS')); ?>
        <fieldset class="options-form">
            <legend><?php echo Text::_('COM_CONTRACTOR_CLIENT_DETAILS'); ?></legend>
            <?php echo $this->form->renderFieldset('details'); ?>
        </fieldset>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'contracts', Text::_('COM_CONTRACTOR_CLIENT_CONTRACTS')); ?>
        
        <?php if ($clientId == 0): ?>
            <div class="alert alert-warning">
                <span class="icon-exclamation-triangle"></span> <?php echo Text::_('COM_CONTRACTOR_CONTRACT_SAVE_CLIENT_FIRST'); ?>
            </div>
        <?php else: ?>

            <?php if (!empty($this->item->contracts)) : ?>
                <table class="table table-striped" id="contractsTable">
                    <thead>
                        <tr>
                            <th class="sortable" width="1%" style="cursor: pointer;"><?php echo Text::_('COM_CONTRACTOR_GLOBAL_ID'); ?> <span class="sort-icon"></span></th>
                            <th class="sortable text-center" width="1%" style="cursor: pointer;"><?php echo Text::_('JSTATUS'); ?> <span class="sort-icon"></span></th>
                            <th class="sortable" style="cursor: pointer;"><?php echo Text::_('COM_CONTRACTOR_CLIENT_CONTRACT_NAME'); ?> <span class="sort-icon"></span></th>
                            <th class="sortable" style="cursor: pointer;"><?php echo Text::_('COM_CONTRACTOR_CLIENT_CONTRACT_START'); ?> <span class="sort-icon"></span></th>
                            <th width="1%"></th>
                            <th class="sortable" data-initial-sort="asc" style="cursor: pointer;"><?php echo Text::_('COM_CONTRACTOR_CLIENT_CONTRACT_END'); ?> <span class="sort-icon">&#x25B2;</span></th>
                            <th width="10%" class="text-center"><?php echo Text::_('COM_CONTRACTOR_GLOBAL_ACTIONS'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->item->contracts as$contract) : ?>
                            <?php 
                            $formattedStart = HTMLHelper::_('date', $contract->date_start, 'd F Y');$formattedEnd   = HTMLHelper::_('date', $contract->valid_thru, 'd F Y');$startDateObj     = Factory::getDate($contract->date_start);$validDateForSort = Factory::getDate($contract->valid_thru);$isUnpublished = ($contract->published == 0);$isExpired     = false;
                            $isWarning     = false;

                            if (!empty($contract->valid_thru) &&$contract->valid_thru !== '0000-00-00 00:00:00') {
                                $validDate = new \DateTime($contract->valid_thru);
                                $validDate->setTime(0, 0, 0);$daysLeft = (int) $today->diff($validDate)->format('%R%a');
                                if ($daysLeft < 0)$isExpired = true;
                                elseif ($daysLeft <= $warnDays)$isWarning = true;
                            }

                            $rowClass      =$isUnpublished ? 'text-muted' : '';
                            $dateCellClass = '';$statusIcon    = '';

                            if ($isExpired) {
                                if (!$isUnpublished) $dateCellClass = 'bg-danger text-white';$statusIcon = '<span class="icon-exclamation-circle text-danger" style="font-size: 24px;" aria-hidden="true" title="' . Text::_('COM_CONTRACTOR_STATUS_EXPIRED') . '"></span>';
                            } elseif ($isWarning) {
                                if (!$isUnpublished) $dateCellClass = 'bg-warning text-dark';$statusIcon = '<span class="icon-exclamation-triangle text-warning" style="font-size: 24px;" aria-hidden="true" title="' . Text::_('COM_CONTRACTOR_STATUS_EXPIRING_SOON') . '"></span>';
                            }
                            
                            $editContractLink = Route::_('index.php?option=com_contractor&task=contract.edit&id=' . (int) $contract->id);
                            
                            if ($isUnpublished) {$publishIcon = '<span class="icon-unpublish" aria-hidden="true" title="' . Text::_('COM_CONTRACTOR_STATUS_UNPUBLISHED') . '"></span>';
                            } else {
                                $publishIcon = '<span class="icon-publish text-success" aria-hidden="true" title="' . Text::_('COM_CONTRACTOR_STATUS_PUBLISHED') . '"></span>';
                            }
                            ?>
                            <tr class="<?php echo $rowClass; ?>">
                                <td><?php echo (int) $contract->id; ?></td>
                                <td class="text-center" data-sort="<?php echo (int) $contract->published; ?>"><span class="tbody-icon"><?php echo $publishIcon; ?></span></td>
                                <td><?php echo $this->escape($contract->name); ?></td>
                                <td data-sort="<?php echo $startDateObj->toUnix(); ?>"><?php echo $formattedStart; ?></td>
                                <td><?php echo $statusIcon; ?></td>
                                <td data-sort="<?php echo $validDateForSort->toUnix(); ?>" class="<?php echo $dateCellClass; ?> align-middle">
                                    <?php echo ($contract->valid_thru && $contract->valid_thru != '0000-00-00 00:00:00') ?$formattedEnd : '-'; ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?php echo $editContractLink; ?>" class="btn btn-sm btn-primary">
                                        <span class="icon-edit" aria-hidden="true"></span> <?php echo Text::_('COM_CONTRACTOR_INVOICE_EDIT'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <div class="alert alert-info">
                    <?php echo Text::_('COM_CONTRACTOR_CLIENT_NO_CONTRACTS'); ?>
                </div>
            <?php endif; ?>

            <div class="mb-3 text-end">
                <a href="<?php echo Route::_('index.php?option=com_contractor&view=contract&layout=edit&id=0&client_id=' . $clientId); ?>" class="btn btn-primary">
                    <span class="fas fa-file-circle-plus me-1" aria-hidden="true"></span> <?php echo Text::_('COM_CONTRACTOR_CLIENT_ADD_CONTRACT'); ?>
                </a>
            </div>
            
        <?php endif; ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'invoices', Text::_('COM_CONTRACTOR_CLIENT_INVOICES_TAB')); ?>
        
        <?php if ($clientId == 0): ?>
            <div class="alert alert-warning">
                <span class="icon-exclamation-triangle"></span> <?php echo Text::_('COM_CONTRACTOR_INVOICE_SAVE_CLIENT_FIRST'); ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped align-middle" id="invoicesTable">
                    <thead>
                        <tr>
                            <th width="5%"><?php echo Text::_('COM_CONTRACTOR_GLOBAL_ID'); ?></th>
                            <th width="15%"><?php echo Text::_('COM_CONTRACTOR_INVOICE_DATE'); ?></th>
                            <th width="20%"><?php echo Text::_('COM_CONTRACTOR_INVOICE_REF'); ?></th>
                            <th><?php echo Text::_('COM_CONTRACTOR_INVOICE_DATA'); ?></th>
                            <th width="10%" class="text-center"><?php echo Text::_('COM_CONTRACTOR_GLOBAL_ACTIONS'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="invoicesBody">
                        <tr class="table-info align-top" id="invoiceAddRow">
                            <td class="text-muted">-</td>
                            <td>
                                <input type="date" id="newInvDate" class="form-control form-control-sm" value="<?php echo Factory::getDate()->format('Y-m-d'); ?>">
                            </td>
                            <td>
                                <input type="text" id="newInvRef" class="form-control form-control-sm" placeholder="<?php echo Text::_('COM_CONTRACTOR_INVOICE_REF_PH'); ?>">
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-2" id="invoiceInputContainer">
                                    <div class="input-group input-group-sm transition-height overflow-hidden" id="newInvLinkWrapper">
                                        <span class="fw-bold pe-2 fs-4 text-dark"><?php echo Text::_('COM_CONTRACTOR_INVOICE_DATALINK'); ?></span>
                                        <input type="url" id="newInvLink" class="form-control" placeholder="https://..." oninput="toggleInvoiceInputs('link')">
                                        <button type="button" class="btn btn-outline-secondary" onclick="testPdfLink(this, 'newInvLink')">
                                            <?php echo Text::_('COM_CONTRACTOR_INVOICE_TEST_LINK'); ?>
                                        </button>
                                    </div>
                                                                        
                                    <div class="input-group input-group-sm transition-height overflow-hidden" id="newInvFileWrapper">
                                        <span class="fw-bold pe-2 fs-4 text-dark"><?php echo Text::_('COM_CONTRACTOR_INVOICE_DATAPDF'); ?></span>
                                        <input type="file" id="newInvFile" accept="application/pdf" class="form-control" onchange="toggleInvoiceInputs('file')">
                                    </div>

<?php
    // HOOK : add other invoice methods
    PluginHelper::importPlugin('contractor');
        $event = new Event('onContractorRenderInvoiceInputs', [
        'clientId' => $clientId,
        'html'     => [] 
    ]);
    $dispatcher = Factory::getApplication()->getDispatcher();
    $event = $dispatcher->dispatch('onContractorRenderInvoiceInputs', $event);
    echo implode("\n", $event->getArgument('html', []));
?>
                                </div>
                                <small id="newInvMimeResult" class="mime-result text-muted mt-1 d-block"></small>
                            </td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <button type="button" class="btn btn-sm btn-success" onclick="addInvoiceAjax()" title="<?php echo Text::_('COM_CONTRACTOR_INVOICE_ADD'); ?>">
                                        <span class="icon-plus" aria-hidden="true"></span>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetNewInvoice()" title="<?php echo Text::_('COM_CONTRACTOR_INVOICE_RESET'); ?>">
                                        <span class="icon-undo" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'message', Text::_('COM_CONTRACTOR_CLIENT_COM_PREFS')); ?>
        <div class="row">
            <div class="col-md-9">
                <fieldset class="options-form">
                    <strong><?php echo Text::_("COM_CONTRACTOR_CLIENT_COM_DESC"); ?></strong>
                    <?php echo $this->form->renderFieldset('communication'); ?>
                </fieldset>
            </div>
            <div class="col-md-3">
                <div class="card border shadow-none">
                    <div class="card-header bg-light">
                        <strong><?php echo Text::_('COM_CONTRACTOR_INSERT_TAGS'); ?></strong>
                    </div>
                    <div class="card-body d-flex flex-column gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm tag-insert" data-tag="{client_name}">{client_name}</button>
                        <button type="button" class="btn btn-outline-primary btn-sm tag-insert" data-tag="{contract_name}">{contract_name}</button>
                        <button type="button" class="btn btn-outline-primary btn-sm tag-insert" data-tag="{contract_validthru}">{contract_validthru}</button>
                        <button type="button" class="btn btn-outline-primary btn-sm tag-insert" data-tag="{currency}">{currency}</button>
                        <button type="button" class="btn btn-outline-primary btn-sm tag-insert" data-tag="{total_price}">{total_price}</button>
                        <button type="button" class="btn btn-outline-primary btn-sm tag-insert" data-tag="{serviceslist}">{serviceslist}</button>
                        <button type="button" class="btn btn-outline-primary btn-sm tag-insert" data-tag="{rem}">{rem}</button>
                        <button type="button" class="btn btn-outline-primary btn-sm tag-insert" data-tag="{signature}">{signature}</button>
                    </div>
                </div>
            </div>
        </div>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'myTab', 'log', Text::_('COM_CONTRACTOR_CLIENT_LOG')); ?>
        <?php if (!empty($this->item->logs)) : ?>
            <div class="border" style="max-height: 800px; overflow-y: auto;">
                <table class="table table-striped table-sm mb-0">
                    <thead class="sticky-top bg-white">
                        <tr>
                            <th width="20%"><?php echo Text::_('COM_CONTRACTOR_LOG_DATE'); ?></th>
                            <th width="10%"><?php echo Text::_('COM_CONTRACTOR_LOG_LEVEL'); ?></th>
                            <th><?php echo Text::_('COM_CONTRACTOR_LOG_DESC'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($this->item->logs as$log) : ?>
                            <?php
                                $levelLabel = Text::_('COM_CONTRACTOR_LOG_INFO');$badgeClass = 'bg-info';
                                if ($log->level == 1) {
                                    $levelLabel = Text::_('COM_CONTRACTOR_LOG_WARNING');$badgeClass = 'bg-warning text-dark';
                                } elseif ($log->level == 2) {
                                    $levelLabel = Text::_('COM_CONTRACTOR_LOG_ERROR');$badgeClass = 'bg-danger';
                                }
                            ?>
                            <tr>
                                <td><?php echo HTMLHelper::_('date', $log->timedate, 'Y-m-d H:i:s'); ?></td>
                                <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $levelLabel; ?></span></td>
                                <td><?php echo $this->escape($log->description); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else : ?>
            <div class="alert alert-info">
                <?php echo Text::_('COM_CONTRACTOR_CLIENT_NO_LOGS'); ?>
            </div>
        <?php endif; ?>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<style>
    .transition-height { 
        transition: max-height 0.4s ease-in-out, opacity 0.3s ease-in-out, margin 0.3s ease-in-out; 
        max-height: 50px; 
        opacity: 1;
    }
    
    .collapsed-input {
        max-height: 0 !important;
        opacity: 0 !important;
        margin-top: 0 !important;
        margin-bottom: 0 !important;
        border: none !important;
        padding: 0 !important;
    }

    .w-25 { width: 25% !important; justify-content: center; }

    .inv-editable:hover { outline: 1px dashed #ccc; background: #f8f9fa; }
    .cursor-pointer { cursor: pointer; }
    .inv-inline-edit { transition: all 0.2s; }
    .inv-inline-edit:hover, .inv-inline-edit:focus { background-color: #fff !important; border: 1px solid #ccc !important; outline: none; box-shadow: 0 0 5px rgba(0,0,0,0.1); }
</style>

<script>
    const txtDelWarn      = "<?php echo Text::_('COM_CONTRACTOR_INVOICE_DEL_WARN', true); ?>";
    const txtOpenLink     = "<?php echo Text::_('COM_CONTRACTOR_INVOICE_OPEN_LINK', true); ?>";
    const txtErrReqRef    = "<?php echo Text::_('COM_CONTRACTOR_INVOICE_ERR_REQ_REF', true); ?>";
    const txtErrPrefix    = "<?php echo Text::_('COM_CONTRACTOR_INVOICE_ERR_PREFIX', true); ?>";
    const txtErrNetwork   = "<?php echo Text::_('COM_CONTRACTOR_INVOICE_ERR_NETWORK', true); ?>";
    const txtTestMissing  = "<?php echo Text::_('COM_CONTRACTOR_INVOICE_TEST_MISSING', true); ?>";
    const txtTestChecking = "<?php echo Text::_('COM_CONTRACTOR_INVOICE_TEST_CHECKING', true); ?>";
    const txtTestValid    = "<?php echo Text::_('COM_CONTRACTOR_INVOICE_TEST_VALID', true); ?>";
    const txtTestInvalid  = "<?php echo Text::_('COM_CONTRACTOR_INVOICE_TEST_INVALID_TYPE', true); ?>";
    const txtTestHttpErr  = "<?php echo Text::_('COM_CONTRACTOR_INVOICE_TEST_HTTP_ERR', true); ?>";
    const txtTestCorsErr  = "<?php echo Text::_('COM_CONTRACTOR_INVOICE_TEST_CORS_ERR', true); ?>";
    const dirtywarn1      = "<?php echo Text::_('COM_CONTRACTOR_DIRTYWARN1', true); ?>";

    const clientId = <?php echo $clientId; ?>;
    const rootUrl  = "<?php echo Uri::root(); ?>";
    let invoicesData = <?php echo json_encode($this->item->invoices ?? []); ?>;

    function escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    function toggleInvoiceInputs(source) {
        const linkWrapper = document.getElementById('newInvLinkWrapper');
        const fileWrapper = document.getElementById('newInvFileWrapper');
        const linkInput   = document.getElementById('newInvLink');
        const fileInput   = document.getElementById('newInvFile');
        
        const pluginWrappers = document.querySelectorAll('.plugin-invoice-input');

        if (source === 'link') {
            if (linkInput.value.trim() !== '') {
                fileWrapper.classList.add('collapsed-input');
                pluginWrappers.forEach(el => el.classList.add('collapsed-input'));
            } else {
                fileWrapper.classList.remove('collapsed-input');
                pluginWrappers.forEach(el => el.classList.remove('collapsed-input'));
            }
        } else if (source === 'file') {
            if (fileInput.files.length > 0) {
                linkWrapper.classList.add('collapsed-input');
                pluginWrappers.forEach(el => el.classList.add('collapsed-input'));
            } else {
                linkWrapper.classList.remove('collapsed-input');
                pluginWrappers.forEach(el => el.classList.remove('collapsed-input'));
            }
        } else if (source === 'plugin') {
            linkWrapper.classList.add('collapsed-input');
            fileWrapper.classList.add('collapsed-input');
        }
    }
    
    window.resetInvoiceInputsVisibility = function() {
        document.getElementById('newInvLinkWrapper').classList.remove('collapsed-input');
        document.getElementById('newInvFileWrapper').classList.remove('collapsed-input');
        document.querySelectorAll('.plugin-invoice-input').forEach(el => el.classList.remove('collapsed-input'));
    };

    function resetNewInvoice() {
        document.getElementById('newInvDate').value = "<?php echo Factory::getDate()->format('Y-m-d'); ?>";
        document.getElementById('newInvRef').value = '';
        document.getElementById('newInvLink').value = '';
        document.getElementById('newInvFile').value = '';
        document.getElementById('newInvMimeResult').innerHTML = '';
        
        window.resetInvoiceInputsVisibility();
    }

    function appendInvoiceRowToDOM(inv) {
        const tbody = document.getElementById('invoicesBody');
        const tr = document.createElement('tr');
        tr.id = 'invoice_row_' + inv.id;
        
        let displayLink = '-';
        if (inv.invdata) {
            if (inv.invdata.startsWith('images/')) {
                const filename = inv.invdata.split('/').pop();
                displayLink = `<a href="${rootUrl}${inv.invdata}" target="_blank" class="btn btn-sm btn-outline-secondary"><span class="icon-file-pdf text-danger"></span> ${escapeHtml(filename)}</a>`;
            } else {
                displayLink = `<a href="${escapeHtml(inv.invdata)}" target="_blank" class="btn btn-sm btn-outline-secondary"><span class="icon-link"></span> ${txtOpenLink}</a>`;
            }
        }

        tr.innerHTML = `
            <td>${inv.id}</td>
            <td>
                <input type="date" class="inv-inline-edit form-control form-control-sm border-0 bg-transparent px-1" data-id="${inv.id}" data-field="invoicedate" value="${inv.invoicedate}" title="<?php echo Text::_('JACTION_EDIT'); ?>">
            </td>
            <td class="fw-bold">
                <input type="text" class="inv-inline-edit form-control form-control-sm border-0 bg-transparent px-1 fw-bold" data-id="${inv.id}" data-field="reference" value="${escapeHtml(inv.reference)}" title="<?php echo Text::_('JACTION_EDIT'); ?>">
            </td>
            <td>${displayLink}</td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-danger" onclick="deleteInvoiceAjax(${inv.id})">
                    <span class="icon-trash" aria-hidden="true"></span>
                </button>
            </td>
        `;
        const addRow = document.getElementById('invoiceAddRow');
        tbody.insertBefore(tr, addRow.nextSibling);
    }

    function renderInitialInvoices() {
        if(clientId === 0) return;
        invoicesData.forEach(inv => appendInvoiceRowToDOM(inv));
        sortInvoicesByDateDesc();
    }

    function sortInvoicesByDateDesc() {
        const tbody = document.getElementById('invoicesBody');
        const rows = Array.from(tbody.querySelectorAll('tr[id^="invoice_row_"]'));
        
        rows.sort((a, b) => {
            const inputA = a.querySelector('input[data-field="invoicedate"]');
            const inputB = b.querySelector('input[data-field="invoicedate"]');
            
            const dateA = inputA ? inputA.value : '';
            const dateB = inputB ? inputB.value : '';
            
            return dateB.localeCompare(dateA);
        });

        rows.forEach(row => tbody.appendChild(row));
    }

    async function addInvoiceAjax() {
        const refInput = document.getElementById('newInvRef');
        if (!refInput.value.trim()) {
            alert(txtErrReqRef);
            return;
        }

        const dateInput = document.getElementById('newInvDate').value;
        const linkInput = document.getElementById('newInvLink').value;
        const fileInput = document.getElementById('newInvFile').files[0];

        const formData = new FormData();
        formData.append('client_id', clientId);
        formData.append('invoicedate', dateInput);
        formData.append('reference', refInput.value);
        formData.append('invdata', linkInput);
        
        const tokenStr = Joomla.getOptions('csrf.token');
        if(tokenStr) formData.append(tokenStr, '1');

        if (fileInput) formData.append('invoice_file', fileInput);

        const btn = event.currentTarget;
        btn.innerHTML = '<span class="icon-loop"></span>';
        btn.disabled = true;

        try {
            const response = await fetch('index.php?option=com_contractor&task=client.addInvoice&format=json', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                appendInvoiceRowToDOM(data.invoice);
                sortInvoicesByDateDesc();
                resetNewInvoice();
            } else {
                alert(txtErrPrefix + data.message);
            }
        } catch (err) {
            console.error(err);
            alert(txtErrNetwork);
        } finally {
            btn.innerHTML = '<span class="icon-plus"></span>';
            btn.disabled = false;
        }
    }

    async function deleteInvoiceAjax(id) {
        if (!confirm(txtDelWarn)) return;

        const formData = new FormData();
        formData.append('id', id);
        
        const tokenStr = Joomla.getOptions('csrf.token');
        if(tokenStr) formData.append(tokenStr, '1');

        try {
            const response = await fetch('index.php?option=com_contractor&task=client.deleteInvoice&format=json', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('invoice_row_' + id).remove();
            } else {
                alert(txtErrPrefix + data.message);
            }
        } catch (err) {
            console.error(err);
            alert(txtErrNetwork);
        }
    }

    async function testPdfLink(btnElement, inputId) {
        const urlInput = document.getElementById(inputId);
        const url = urlInput.value.trim();
        const resultSpan = document.getElementById('newInvMimeResult');

        if (!url) {
            resultSpan.innerHTML = '<span class="text-warning"><span class="icon-exclamation-triangle"></span> ' + txtTestMissing + '</span>';
            return;
        }

        resultSpan.innerHTML = '<span class="text-info"><span class="icon-loop"></span> ' + txtTestChecking + '</span>';

        try {
            const response = await fetch(url, { method: 'HEAD' });
            if (response.ok) {
                const contentType = response.headers.get('Content-Type');
                if (contentType && contentType.toLowerCase().includes('application/pdf')) {
                    resultSpan.innerHTML = '<span class="text-success"><span class="icon-check"></span> ' + txtTestValid + '</span>';
                } else {
                    resultSpan.innerHTML = '<span class="text-warning"><span class="icon-exclamation-triangle"></span> ' + txtTestInvalid + (contentType || 'Unknown') + ')</span>';
                }
            } else {
                resultSpan.innerHTML = '<span class="text-danger"><span class="icon-cancel"></span> ' + txtTestHttpErr + response.status + '</span>';
            }
        } catch (error) {
            resultSpan.innerHTML = '<span class="text-danger"><span class="icon-cancel"></span> ' + txtTestCorsErr + '</span>';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        renderInitialInvoices();

        const getCellValue = (tr, idx) => tr.children[idx].dataset.sort || tr.children[idx].innerText || tr.children[idx].textContent;
        const comparer = (idx, asc) => (a, b) => ((v1, v2) => (v1 || '').toString().localeCompare((v2 || ''), undefined, { numeric: true, sensitivity: 'base' }))(getCellValue(asc ? a : b, idx), getCellValue(asc ? b : a, idx));

        document.querySelectorAll('th.sortable').forEach(th => {
            if (th.dataset.initialSort === 'asc') th.asc = true;
            else if (th.dataset.initialSort === 'desc') th.asc = false;

            th.addEventListener('click', function() {
                const table = th.closest('table');
                const tbody = table.querySelector('tbody');
                table.querySelectorAll('th.sortable .sort-icon').forEach(icon => { icon.innerHTML = ''; });
                this.asc = !this.asc;
                const currentIcon = this.querySelector('.sort-icon');
                if (currentIcon) currentIcon.innerHTML = this.asc ? '&#x25B2;' : '&#x25BC;';
                Array.from(tbody.querySelectorAll('tr'))
                    .sort(comparer(Array.from(th.parentNode.children).indexOf(th), this.asc))
                    .forEach(tr => tbody.appendChild(tr));
            });
        });

        document.querySelectorAll('.tag-insert').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault(); 
                const tag = this.getAttribute('data-tag');
                const editorId = 'jform_mailtext'; 
                
                if (window.Joomla && window.Joomla.editors && window.Joomla.editors.instances && window.Joomla.editors.instances[editorId]) {
                    window.Joomla.editors.instances[editorId].replaceSelection(tag);
                } else {
                    const joomlaTextarea = document.getElementById(editorId);
                    if (joomlaTextarea) {
                        const start = joomlaTextarea.selectionStart;
                        const end = joomlaTextarea.selectionEnd;
                        const text = joomlaTextarea.value;
                        joomlaTextarea.value = text.slice(0, start) + tag + text.slice(end);
                        joomlaTextarea.selectionStart = joomlaTextarea.selectionEnd = start + tag.length;
                        joomlaTextarea.focus();
                        joomlaTextarea.dispatchEvent(new Event('change', { bubbles: true })); 
                    }
                }
            });
        });
    });

    document.getElementById('invoicesBody').addEventListener('focusin', function(e) {
        if (e.target.classList.contains('inv-inline-edit')) {
            e.target.dataset.original = e.target.value;
        }
    });

    document.getElementById('invoicesBody').addEventListener('keydown', function(e) {
        if (e.target.classList.contains('inv-inline-edit')) {
            if (e.key === 'Enter') {
                e.preventDefault();
                e.target.blur(); 
            } else if (e.key === 'Escape') {
                e.target.value = e.target.dataset.original;
                e.target.blur();
            }
        }
    });

    document.getElementById('invoicesBody').addEventListener('change', function(e) {
        const target = e.target;
        if (target.classList.contains('inv-inline-edit')) {
            const id = target.dataset.id;
            const field = target.dataset.field;
            const value = target.value;
            const original = target.dataset.original;

            if (value.trim() !== '' && value !== original) {
                saveInvoiceFieldAjax(id, field, value, target, original);
            } else {
                target.value = original; 
            }
        }
    });

    async function saveInvoiceFieldAjax(id, field, value, inputElement, originalValue) {
        const formData = new FormData();
        formData.append('id', id);
        formData.append('field', field);
        formData.append('value', value);
        
        const tokenStr = Joomla.getOptions('csrf.token');
        if(tokenStr) formData.append(tokenStr, '1');

        inputElement.disabled = true; 

        try {
            const response = await fetch('index.php?option=com_contractor&task=client.updateInvoiceField&format=json', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            if (data.success) {
                inputElement.dataset.original = value;
                if (field === 'invoicedate') {
                    sortInvoicesByDateDesc();
                }
            } else {
                alert(txtErrPrefix + data.message);
                inputElement.value = originalValue;
            }
        } catch (err) {
            console.error(err);
            alert(txtErrNetwork);
            inputElement.value = originalValue;
        } finally {
            inputElement.disabled = false;
        }
    }

    let isFormDirty = false;

    const formElement = document.getElementById('client-form');
    
    formElement.addEventListener('input', function(e) {
        if (!e.target.closest('#invoicesTable')) {
            isFormDirty = true;
        }
    });
    
    formElement.addEventListener('change', function(e) {
        if (!e.target.closest('#invoicesTable')) {
            isFormDirty = true;
        }
    });

    window.addEventListener('beforeunload', function (e) {
        if (isFormDirty) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    const originalSubmitbutton = Joomla.submitbutton;
    Joomla.submitbutton = function(task) {
        if (task === 'client.cancel' && isFormDirty) {
            if (!confirm(dirtywarn1)) {
                return false;
            }
        }
        
        isFormDirty = false; 
        
        if (typeof originalSubmitbutton === 'function') {
            const formElement = document.getElementById('client-form');
            const isSaving = task.includes('save') || task.includes('apply');
            
            if (isSaving && typeof document.formvalidator !== 'undefined') {
                if (!document.formvalidator.isValid(formElement)) {
                    const detailsBtn = document.querySelector('button[aria-controls="details"]');
                    if (detailsBtn) {
                        detailsBtn.click();
                        detailsBtn.blur();
                    }
                    
                    sessionStorage.removeItem('ctr_client_tab_restore');
                    
                    return originalSubmitbutton(task);
                }
            }

            if (task === 'client.apply') {
                const activeBtn = document.querySelector('button.active[aria-controls]') || document.querySelector('button[aria-selected="true"][aria-controls]');
                if (activeBtn) {
                    sessionStorage.setItem('ctr_client_tab_restore', activeBtn.getAttribute('aria-controls'));
                }
            } else {
                sessionStorage.removeItem('ctr_client_tab_restore');
            }
            
            return originalSubmitbutton(task);
        }
    };

    window.addEventListener('load', function() {
        const tabToRestore = sessionStorage.getItem('ctr_client_tab_restore');
        const targetTabId = tabToRestore ? tabToRestore : 'details';
        
        const btn = document.querySelector('button[aria-controls="' + targetTabId + '"]');
        if (btn) {
            setTimeout(() => {
                btn.click();
                btn.blur();
            }, 100); 
        }
        
        if (tabToRestore) {
            sessionStorage.removeItem('ctr_client_tab_restore');
        }
    });

</script>
<?php
$db = \Joomla\CMS\Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
$query = $db->getQuery(true)->select($db->quoteName(['id', 'color']))->from($db->quoteName('#__ctr_tags'));
$db->setQuery($query);
$tagsData = $db->loadObjectList();
$tagColorsMap = [];
if ($tagsData) {
    foreach ($tagsData as $t) {
        $tagColorsMap[$t->id] = !empty($t->color) ? $t->color : '#586a7a';
    }
}
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tagColors = <?php echo json_encode($tagColorsMap); ?>;
    
    function getContrastColor(hexColor) {
        hexColor = hexColor.replace('#', '');
        if (hexColor.length === 3) {
            hexColor = hexColor[0] + hexColor[0] + hexColor[1] + hexColor[1] + hexColor[2] + hexColor[2];
        }
        const r = parseInt(hexColor.substr(0, 2), 16);
        const g = parseInt(hexColor.substr(2, 2), 16);
        const b = parseInt(hexColor.substr(4, 2), 16);
        const yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
        return (yiq >= 128) ? '#000000' : '#ffffff';
    }

    function applyTagColors() {
        const wrap = document.querySelector('joomla-field-fancy-select');
        if (!wrap) return;

        const items = wrap.querySelectorAll('.choices__list--multiple .choices__item[data-value]');
        
        items.forEach(item => {
            const val = item.getAttribute('data-value');
            if (!val || !tagColors[val]) return;
            
            const bgColor = tagColors[val];
            const textColor = getContrastColor(bgColor);
            
            item.style.setProperty('background-color', bgColor, 'important');
            item.style.setProperty('color', textColor, 'important');
            item.style.setProperty('border', 'none', 'important');
            
            item.classList.add('rounded-pill');
            item.style.setProperty('margin-right', '4px', 'important');
        });
    }

    const wrap = document.querySelector('joomla-field-fancy-select');
    if (wrap) {
        const observer = new MutationObserver(() => applyTagColors());
        observer.observe(wrap, { childList: true, subtree: true });
        
        setTimeout(applyTagColors, 100);
    }
});

document.addEventListener('DOMContentLoaded', function() {
    if (typeof document.formvalidator !== 'undefined') {
        document.formvalidator.setHandler('emails', function(value) {
            if (value.trim() === '') return true; 

            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const emails = value.split(',');

            for (let i = 0; i < emails.length; i++) {
                if (emails[i].trim() !== '' && !regex.test(emails[i].trim())) {
                    return false;
                }
            }
            return true;
        });
    }

    const emailInput = document.getElementById('jform_email');
    
    if (emailInput) {
        emailInput.classList.remove('validate-email');
        emailInput.classList.add('validate-emails');
        
        emailInput.addEventListener('blur', function() {
            let cleaned = this.value.replace(/;/g, ',');
            
            cleaned = cleaned.split(',')
                             .map(e => e.trim())
                             .filter(e => e.length > 0)
                             .join(',');
            
            this.value = cleaned;

            if (typeof document.formvalidator !== 'undefined') {
                document.formvalidator.validate(this);
            }
        });
    }
});
</script>