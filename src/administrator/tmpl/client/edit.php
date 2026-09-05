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
<form action="<?php echo Route::_('index.php?option=com_contractor&layout=edit&id=' . $clientId); ?>" method="post" name="adminForm" id="client-form" class="form-validate" enctype="multipart/form-data">
    
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
                        <?php foreach ($this->item->contracts as $contract) : ?>
                            <?php 
                            $formattedStart = HTMLHelper::_('date', $contract->date_start, 'd F Y');
                            $formattedEnd   = HTMLHelper::_('date', $contract->valid_thru, 'd F Y');
                            $startDateObj     = Factory::getDate($contract->date_start);
                            $validDateForSort = Factory::getDate($contract->valid_thru);
                            $isUnpublished = ($contract->published == 0);
                            $isExpired     = false;
                            $isWarning     = false;

                            if (!empty($contract->valid_thru) && $contract->valid_thru !== '0000-00-00 00:00:00') {
                                $validDate = new \DateTime($contract->valid_thru);
                                $validDate->setTime(0, 0, 0);
                                $daysLeft = (int) $today->diff($validDate)->format('%R%a');
                                if ($daysLeft < 0) $isExpired = true;
                                elseif ($daysLeft <= $warnDays) $isWarning = true;
                            }

                            $rowClass      = $isUnpublished ? 'text-muted' : '';
                            $dateCellClass = '';
                            $statusIcon    = '';

                            if ($isExpired) {
                                if (!$isUnpublished) $dateCellClass = 'bg-danger text-white';
                                $statusIcon = '<span class="icon-exclamation-circle text-danger" style="font-size: 24px;" aria-hidden="true" title="' . Text::_('COM_CONTRACTOR_STATUS_EXPIRED') . '"></span>';
                            } elseif ($isWarning) {
                                if (!$isUnpublished) $dateCellClass = 'bg-warning text-dark';
                                $statusIcon = '<span class="icon-exclamation-triangle text-warning" style="font-size: 24px;" aria-hidden="true" title="' . Text::_('COM_CONTRACTOR_STATUS_EXPIRING_SOON') . '"></span>';
                            }
                            
                            $editContractLink = Route::_('index.php?option=com_contractor&task=contract.edit&id=' . (int) $contract->id);
                            
                            if ($isUnpublished) {
                                $publishIcon = '<span class="icon-unpublish" aria-hidden="true" title="' . Text::_('COM_CONTRACTOR_STATUS_UNPUBLISHED') . '"></span>';
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
                                    <?php echo ($contract->valid_thru && $contract->valid_thru != '0000-00-00 00:00:00') ? $formattedEnd : '-'; ?>
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
                            <th><?php echo Text::_('COM_CONTRACTOR_INVOICE_LINK'); ?></th>
                            <th width="10%" class="text-center"><?php echo Text::_('COM_CONTRACTOR_GLOBAL_ACTIONS'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="invoicesBody">
                        <tr class="table-info" id="invoiceAddRow">
                            <td class="text-muted">-</td>
                            <td>
                                <input type="date" id="newInvDate" class="form-control form-control-sm" value="<?php echo Factory::getDate()->format('Y-m-d'); ?>">
                            </td>
                            <td>
                                <input type="text" id="newInvRef" class="form-control form-control-sm" placeholder="<?php echo Text::_('COM_CONTRACTOR_INVOICE_REF_PH'); ?>">
                            </td>
                            <td class="align-top">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="input-group input-group-sm w-50 transition-width" id="newInvLinkWrapper">
                                        <input type="url" id="newInvLink" class="form-control" placeholder="https://..." oninput="toggleInvoiceInputs('link')">
                                        <button type="button" class="btn btn-outline-secondary" onclick="testPdfLink(this, 'newInvLink')">
                                            <?php echo Text::_('COM_CONTRACTOR_INVOICE_TEST_LINK'); ?>
                                        </button>
                                    </div>
                                    
                                    <span class="small text-muted text-nowrap fw-bold" id="orUploadText"><?php echo Text::_('COM_CONTRACTOR_INVOICE_OR_UPLOAD'); ?></span>
                                    
                                    <div class="w-50 transition-width" id="newInvFileWrapper">
                                        <input type="file" id="newInvFile" accept="application/pdf" class="form-control form-control-sm" onchange="toggleInvoiceInputs('file')">
                                    </div>
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
                        <?php foreach ($this->item->logs as $log) : ?>
                            <?php
                                $levelLabel = Text::_('COM_CONTRACTOR_LOG_INFO');
                                $badgeClass = 'bg-info';
                                if ($log->level == 1) {
                                    $levelLabel = Text::_('COM_CONTRACTOR_LOG_WARNING');
                                    $badgeClass = 'bg-warning text-dark';
                                } elseif ($log->level == 2) {
                                    $levelLabel = Text::_('COM_CONTRACTOR_LOG_ERROR');
                                    $badgeClass = 'bg-danger';
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

<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>

<style>
    /* Animation et gestion de la largeur des champs */
    .transition-width { transition: width 0.3s ease, flex 0.3s ease; }
    .w-50 { width: 50% !important; }
    .w-100 { width: 100% !important; }

    /* Styles Quill */
    .ql-toolbar.ql-snow { border-top-left-radius: 0.375rem; border-top-right-radius: 0.375rem; border-color: #dee2e6; background-color: #f8f9fa; }
    .ql-container.ql-snow { border-bottom-left-radius: 0.375rem; border-bottom-right-radius: 0.375rem; border-color: #dee2e6; font-family: inherit; font-size: inherit; }
    textarea.source-mode-active { display: block !important; width: 100%; height: 250px; font-family: Consolas, monospace; font-size: 14px; border: 1px solid #dee2e6; border-bottom-left-radius: 0.375rem; border-bottom-right-radius: 0.375rem; padding: 10px; resize: vertical; }
</style>

<script>
    // --- TRANSLATION STRINGS FOR JS ---
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

    const clientId = <?php echo $clientId; ?>;
    const rootUrl  = "<?php echo Uri::root(); ?>";
    let invoicesData = <?php echo json_encode($this->item->invoices ?? []); ?>;

    let quillMailtext = null;
    let isSourceMode = false;

    function escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    function toggleInvoiceInputs(source) {
        const linkWrapper = document.getElementById('newInvLinkWrapper');
        const fileWrapper = document.getElementById('newInvFileWrapper');
        const orText      = document.getElementById('orUploadText');
        const linkInput   = document.getElementById('newInvLink');
        const fileInput   = document.getElementById('newInvFile');
        
        if (source === 'link') {
            if (linkInput.value.trim() !== '') {
                fileWrapper.style.display = 'none';
                orText.style.display = 'none';
                linkWrapper.classList.replace('w-50', 'w-100');
            } else {
                fileWrapper.style.display = 'block';
                orText.style.display = 'inline';
                linkWrapper.classList.replace('w-100', 'w-50');
            }
        } else if (source === 'file') {
            if (fileInput.files.length > 0) {
                linkWrapper.style.display = 'none';
                orText.style.display = 'none';
                fileWrapper.classList.replace('w-50', 'w-100');
            } else {
                linkWrapper.style.display = 'flex';
                orText.style.display = 'inline';
                fileWrapper.classList.replace('w-100', 'w-50');
            }
        }
    }

    function resetNewInvoice() {
        document.getElementById('newInvDate').value = "<?php echo Factory::getDate()->format('Y-m-d'); ?>";
        document.getElementById('newInvRef').value = '';
        document.getElementById('newInvLink').value = '';
        document.getElementById('newInvFile').value = '';
        document.getElementById('newInvMimeResult').innerHTML = '';
        
        document.getElementById('newInvLinkWrapper').style.display = 'flex';
        document.getElementById('newInvFileWrapper').style.display = 'flex';
        document.getElementById('orUploadText').style.display = 'inline';
        
        document.getElementById('newInvLinkWrapper').classList.replace('w-100', 'w-50');
        document.getElementById('newInvFileWrapper').classList.replace('w-100', 'w-50');
    }

    function appendInvoiceRowToDOM(inv) {
        const tbody = document.getElementById('invoicesBody');
        const tr = document.createElement('tr');
        tr.id = 'invoice_row_' + inv.id;
        
        let displayLink = '-';
        if (inv.pdf_link) {
            if (inv.pdf_link.startsWith('images/')) {
                const filename = inv.pdf_link.split('/').pop();
                displayLink = `<a href="${rootUrl}${inv.pdf_link}" target="_blank" class="btn btn-sm btn-outline-secondary"><span class="icon-file-pdf text-danger"></span> ${escapeHtml(filename)}</a>`;
            } else {
                displayLink = `<a href="${escapeHtml(inv.pdf_link)}" target="_blank" class="btn btn-sm btn-outline-secondary"><span class="icon-link"></span> ${txtOpenLink}</a>`;
            }
        }

        tr.innerHTML = `
            <td>${inv.id}</td>
            <td>${inv.invoicedate}</td>
            <td class="fw-bold">${escapeHtml(inv.reference)}</td>
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
        formData.append('pdf_link', linkInput);
        
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

        // Quill init
        const joomlaTextarea = document.getElementById('jform_mailtext'); 
        if (joomlaTextarea) {
            joomlaTextarea.style.display = 'none';
            const editorDiv = document.createElement('div');
            editorDiv.id = 'editor-mailtext';
            editorDiv.style.height = '250px';
            editorDiv.style.backgroundColor = '#fff';
            joomlaTextarea.parentNode.insertBefore(editorDiv, joomlaTextarea.nextSibling);

            const icons = Quill.import('ui/icons');
            icons['source'] = '<svg viewBox="0 0 18 18"><polyline class="ql-even ql-stroke" points="5 7 3 9 5 11"></polyline><polyline class="ql-even ql-stroke" points="13 7 15 9 13 11"></polyline><line class="ql-even ql-stroke" x1="10" x2="8" y1="5" y2="13"></line></svg>';

            const toolbarOptions = [
                ['bold', 'italic', 'underline'],
                [{ 'color': [] }],
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                ['link', 'clean'],
                ['source']
            ];

            editorDiv.innerHTML = joomlaTextarea.value;

            quillMailtext = new Quill(editorDiv, {
                theme: 'snow',
                modules: { 
                    toolbar: {
                        container: toolbarOptions,
                        handlers: {
                            'source': function() {
                                const toolbarButton = this.container.querySelector('.ql-source');
                                isSourceMode = !isSourceMode;

                                if (isSourceMode) {
                                    let html = quillMailtext.getSemanticHTML().replace(/&nbsp;/g, ' ').replace(/\u00a0/g, ' ');
                                    if (html === '<p><br></p>' || html === '<p></p>') html = '';
                                    joomlaTextarea.value = html;
                                    
                                    editorDiv.style.display = 'none';
                                    joomlaTextarea.classList.add('source-mode-active');
                                    toolbarButton.classList.add('ql-active');
                                } else {
                                    quillMailtext.clipboard.dangerouslyPasteHTML(joomlaTextarea.value);
                                    joomlaTextarea.classList.remove('source-mode-active');
                                    joomlaTextarea.style.display = 'none';
                                    editorDiv.style.display = 'block';
                                    toolbarButton.classList.remove('ql-active');
                                }
                            }
                        }
                    } 
                }
            });

            quillMailtext.on('text-change', function() {
                if (!isSourceMode) {
                    let html = quillMailtext.getSemanticHTML().replace(/&nbsp;/g, ' ').replace(/\u00a0/g, ' ');
                    if (html === '<p><br></p>' || html === '<p></p>') html = '';
                    joomlaTextarea.value = html;
                }
            });
        }

        // Tag insertion logic correctly wrapped
        document.querySelectorAll('.tag-insert').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault(); 
                const tag = this.getAttribute('data-tag');
                if (isSourceMode && joomlaTextarea) {
                    const start = joomlaTextarea.selectionStart;
                    const end = joomlaTextarea.selectionEnd;
                    const text = joomlaTextarea.value;
                    joomlaTextarea.value = text.slice(0, start) + tag + text.slice(end);
                    joomlaTextarea.selectionStart = joomlaTextarea.selectionEnd = start + tag.length;
                    joomlaTextarea.focus();
                } else if (quillMailtext) {
                    const range = quillMailtext.getSelection(true); 
                    if (range) {
                        quillMailtext.insertText(range.index, tag);
                        quillMailtext.setSelection(range.index + tag.length);
                    }
                }
            });
        });
    });
</script>