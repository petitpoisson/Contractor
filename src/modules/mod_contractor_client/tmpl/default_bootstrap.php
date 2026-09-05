<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_contractor_client
 *
 * @author      Xavier Spirlet (Petitpoisson) <https://www.petitpoisson.be>
 * @copyright   Copyright (C) 2026 Xavier Spirlet. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 * @link        https://www.petitpoisson.be
 */

// No direct access
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

// Warning logic for dates
$warnDays = isset($config['warn_days']) ? (int) $config['warn_days'] : 30;
if ($warnDays <= 0) {
    $warnDays = 30; // Safety check
}
$today = new \DateTime();
$today->setTime(0, 0, 0);

// Module specific switches
$showContractsSw = $params->get('show_contracts', '1') == '1';
$showInvoicesSw  = $params->get('show_invoices', '1') == '1';

if (!$is_guest) {
    $hasContracts = count($contracts) > 0;
    $hasInvoices  = count($invoices) > 0;
}
$moduleClass = $params->get('moduleclass_sfx', '');

$showCtMsg = $params->get('ctmsg', '1');
$ctMsgText = $params->get('ctmsgtext', '');
if ($ctMsgText === 'COM_CONTRACTOR_CTMSGTXTDEF') {
    $ctMsgText = Text::_('COM_CONTRACTOR_CTMSGTXTDEF');
}
?>
<div class="mod-contractor-client com-contractor-client-view mt-4 <?php echo htmlspecialchars($moduleClass, ENT_QUOTES, 'UTF-8'); ?>">
    <?php if ($is_guest): ?>
        <div class="alert shadow-none border border-dark rounded-1 px-3 py-2">
            <a href="<?php echo htmlspecialchars($login_url, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-outline-dark mt-2 rounded-1 text-uppercase mt-2 px-3 py-1 float-end"><?php echo Text::_('JLOGIN'); ?></a>
            <p class="alert-heading align-middle mt-2">
                <span class="fas fa-user-lock me-2" aria-hidden="true"></span> <?php echo Text::_('JGLOBAL_YOU_MUST_LOGIN_FIRST'); ?></p>
        </div>
    <?php elseif ((!$hasContracts && !$hasInvoices) || (!$showContractsSw && !$showInvoicesSw)): ?>
        <div class="alert alert-secondary shadow-none border border-dark rounded-0">
            <h4 class="alert-heading">
                <span class="icon-info-circle" aria-hidden="true"></span> <?php echo Text::_('COM_CONTRACTOR_CLIENT_EMPTY_TITLE'); ?>
            </h4>
            <p class="mb-0"><?php echo Text::_('COM_CONTRACTOR_CLIENT_EMPTY_DESC'); ?></p>
        </div>
    <?php else: ?>
        <div class="accordion" id="clientDashboardAccordion<?php echo $module->id; ?>">

            <?php if ($showContractsSw): ?>
                <?php foreach ($contracts as $contract):  
                        $headingId  = 'headingModContract' . $module->id . '_' . $contract->id;
                        $collapseId = 'collapseModContract' . $module->id . '_' . $contract->id;
                        
                        $dateCellClass = "";
                        $statusIcon = "";

                        if (!empty($contract->valid_thru) && $contract->valid_thru !== '0000-00-00 00:00:00') {
                            $validDate = new \DateTime($contract->valid_thru);
                            $validDate->setTime(0, 0, 0);
                            
                            $diff = $today->diff($validDate);
                            $daysLeft = (int) $diff->format('%R%a');

                            if ($daysLeft < 0) {
                                $dateCellClass = 'bg-danger ';
                                $statusIcon = '<span class="fas fa-circle-exclamation text-danger me-1" title="'.Text::_('COM_CONTRACTOR_CLIENT_EXPIRED').'"></span>';
                            } elseif ($daysLeft <= $warnDays) {
                                $dateCellClass = 'bg-warning ';
                                $statusIcon = '<span class="fas fa-triangle-exclamation text-warning me-1" title="'.Text::_('COM_CONTRACTOR_CLIENT_EXPSOON').'"></span>';
                            }
                        }
                ?>
                    <div class="accordion-item rounded-0 mb-4 border border-dark">
                        <h2 class="accordion-header" id="<?php echo $headingId; ?>">
                            <button class="accordion-button collapsed rounded-0 text-dark custom-accordion-contract-bg py-2 px-2" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>" aria-expanded="false" aria-controls="<?php echo $collapseId; ?>">
                                
                                <span class="fas fa-chevron-right custom-bs-chevron me-3"></span>
                                <span class="fas fa-file-contract fa-lg me-2"></span>
                                
                                <strong class="text-uppercase flex-grow-1 text-start"><?php echo htmlspecialchars($contract->name, ENT_QUOTES, 'UTF-8'); ?></strong>
                                
                                <div class="d-flex align-items-center">
                                    <?php echo $statusIcon; ?>
                                    <span class="<?php echo $dateCellClass; ?>badge bg-secondary rounded-0 fw-normal me-2">
                                        <?php echo Text::_('COM_CONTRACTOR_CLIENT_EXPIRES'); ?> 
                                        <?php echo \Joomla\CMS\Factory::getDate($contract->valid_thru)->format('d/m/Y'); ?>
                                    </span>
                                    <span class="badge bg-dark rounded-0 fs-6 fw-normal">
                                        <?php echo number_format($contract->total_amount / 100, 2, ',', ' '); ?> €
                                    </span>
                                </div>
                            </button>
                        </h2>
                        <div id="<?php echo $collapseId; ?>" class="accordion-collapse collapse" aria-labelledby="<?php echo $headingId; ?>" data-bs-parent="#moduleDashboardAccordion<?php echo $module->id; ?>">
                            <div class="accordion-body p-0">
                                
                                <?php if (!empty($contract->lines)): ?>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($contract->lines as $line): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center rounded-0 border-light py-1">
                                                <span class="text-secondary"><?php echo htmlspecialchars($line->description, ENT_QUOTES, 'UTF-8'); ?></span>
                                                <span class="fw-bold text-dark"><?php echo number_format($line->price / 100, 2, ',', ' '); ?> €</span> 
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="p-3">
                                        <p class="text-muted fst-italic mb-0 small"><?php echo Text::_('COM_CONTRACTOR_CLIENT_NOTHING_DISPLAY'); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if ($showCtMsg == '1' && !empty($ctMsgText)): ?>
                                    <div class="bsc-rem d-flex align-items-start py-3 px-4 bg-light border-top border-light">
                                        <span class="fas fa-comment-dots text-secondary me-3 mt-1"></span>
                                        <div class="text-secondary small flex-grow-1">
                                            <?php echo $ctMsgText; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if ($showInvoicesSw): ?>
                <div class="accordion-item rounded-0 mb-4 border border-dark">
                    <h2 class="accordion-header" id="headingModInvoices<?php echo $module->id; ?>">
                        <button class="accordion-button collapsed rounded-0 text-dark custom-accordion-invoice-bg py-2 px-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseModInvoices<?php echo $module->id; ?>" aria-expanded="false" aria-controls="collapseModInvoices<?php echo $module->id; ?>">
                            <span class="fas fa-chevron-right custom-bs-chevron me-3"></span>
                            <span class="fas fa-file-pdf fa-lg me-2" aria-hidden="true"></span>
                            <strong class="text-uppercase flex-grow-1 text-start"><?php echo Text::_('COM_CONTRACTOR_CLIENT_INVOICES_TITLE'); ?></strong>
                            <span class="badge bg-secondary rounded-0 fw-normal me-2"><?php echo count($invoices)." ".Text::_('COM_CONTRACTOR_CLIENT_INVOICES_TITLE'); ?></span>
                        </button>
                    </h2>
                    <div id="collapseModInvoices<?php echo $module->id; ?>" class="accordion-collapse collapse" aria-labelledby="headingModInvoices<?php echo $module->id; ?>" data-bs-parent="#moduleDashboardAccordion<?php echo $module->id; ?>">
                        <div class="accordion-body p-1">
                            <?php if ($hasInvoices): ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($invoices as $invoice): ?>
                                        <li class="list-group-item d-flex rounded-0 border-light py-1 px-2">
                                            <span class="text-secondary pe-5"><?php echo htmlspecialchars($invoice->invoicedate, ENT_QUOTES, 'UTF-8'); ?></span>
                                            <a class="text-dark text-decoration-none" href="<?php echo htmlspecialchars($invoice->pdf_link, ENT_QUOTES, 'UTF-8'); ?>" target="_blank"><?php echo htmlspecialchars($invoice->reference, ENT_QUOTES, 'UTF-8'); ?> 
                                            <span class="text-uppercase" style="font-size: 75%;">(<?php echo Text::_('COM_CONTRACTOR_CLIENT_DL'); ?>)</span></a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted fst-italic mb-0 small"><?php echo Text::_('COM_CONTRACTOR_CLIENT_NO_INVOICES'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    <?php endif; ?>
</div>
<style>
    span {
        --warning-rgb: 242, 125, 0;
        --danger-rgb: 197, 40, 39;
    }
</style>