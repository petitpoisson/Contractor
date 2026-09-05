<?php
/**
 * @package     Joomla.Site
 * @subpackage  com_contractor
 *
 * @author      Xavier Spirlet (Petitpoisson) <https://www.petitpoisson.be>
 * @copyright   Copyright (C) 2026 Xavier Spirlet. All rights reserved.
 * @license     GNU General Public License version 3 or later; see LICENSE.txt
 * @link        https://www.petitpoisson.be
 */

// No direct access
\defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use XavierSpirlet\Component\Contractor\Administrator\Helper\ContractorHelper;

// Get config and today via Helper
$config   = ContractorHelper::getConfig();
$warnDays = isset($config['warn_days']) ? (int) $config['warn_days'] : 30;
if ($warnDays <= 0) {
    $warnDays = 30; // Sécurité si la valeur est absente ou négative
}
$today = new \DateTime();
$today->setTime(0, 0, 0);

$hasContracts = count($this->contracts) > 0;
$hasInvoices  = count($this->invoices) > 0;

$customTitle = $this->params->get('custom_page_title', '');
$pageTitle   = !empty($customTitle) ? $customTitle : Text::_('COM_CONTRACTOR_CLIENT_DASHBOARD_TITLE');
$pageClass   = $this->params->get('pageclass_sfx', '');

// Fetch custom message parameters
$showCtMsg = $this->params->get('ctmsg', '1');
$ctMsgText = $this->params->get('ctmsgtext', '');
// Fallback translation if the parameter was never saved and returns the raw constant
if ($ctMsgText === 'COM_CONTRACTOR_CTMSGTXTDEF') {
    $ctMsgText = Text::_('COM_CONTRACTOR_CTMSGTXTDEF');
}

?>
<div class="com-contractor-client-view uk-margin-top <?php echo $this->escape($pageClass); ?>">
    <h1 class="uk-heading-bullet uk-text-uppercase uk-margin-medium-bottom"><?php echo $this->escape($pageTitle); ?></h1>

    <?php if (!$hasContracts && !$hasInvoices): ?>
        <div class="uk-alert-primary" uk-alert>
            <h3><span uk-icon="icon: info"></span> <?php echo Text::_('COM_CONTRACTOR_CLIENT_EMPTY_TITLE'); ?></h3>
            <p><?php echo Text::_('COM_CONTRACTOR_CLIENT_EMPTY_DESC'); ?></p>
        </div>
    <?php else: ?>
        
        <ul uk-accordion="multiple: true" class="uk-margin-medium-top">

            <?php foreach ($this->contracts as $contract):  
                    //$headingId  = 'headingContract' . $contract->id;
                    //$collapseId = 'collapseContract' . $contract->id;
                    // Compute date alerts.
                    if (!empty($contract->valid_thru) && $contract->valid_thru !== '0000-00-00 00:00:00') {
                        $validDate = new \DateTime($contract->valid_thru);
                        $validDate->setTime(0, 0, 0);
                        
                        $diff = $today->diff($validDate);
                        $daysLeft = (int) $diff->format('%R%a');
                        $dateCellClass="";
                        $statusIcon="";

                        if ($daysLeft < 0) {
                            $dateCellClass = 'uk-label-danger ';
                            $statusIcon = '<span class="fas fa-circle-exclamation uk-text-danger uk-margin-xsmall-right" title="'.Text::_('COM_CONTRACTOR_CLIENT_EXPIRED').'"></span>';
                        } elseif ($daysLeft <= $warnDays) {
                            $dateCellClass = 'uk-label-warning ';
                            $statusIcon = '<span class="fas fa-triangle-exclamation uk-text-warning uk-margin-xsmall-right" title="'.Text::_('COM_CONTRACTOR_CLIENT_EXPSOON').'"></span>';
                        }
                    }
            ?>

                <li class="uk-card uk-card-default uk-card-small uk-margin-small-bottom uk-border-rounded-0">
                    <a class="uk-accordion-title custom-uikit-contract-bg custom-uikit-padding uk-flex uk-flex-middle" href="#">
                        <span class="fas fa-chevron-right custom-uikit-chevron uk-margin-small-right"></span>
                        <span class="fas fa-file-contract uk-margin-small-right"></span>
                        
                        <span class="uk-text-uppercase uk-text-bold uk-width-expand"><?php echo $this->escape($contract->name); ?></span>
                        
                        <div class="uk-flex uk-flex-middle">
                            <?php echo $statusIcon; ?>
                            <span class="<?php echo $dateCellClass; ?>uk-label uk-margin-small-right uk-border-rounded-0">
                                <?php echo Text::_('COM_CONTRACTOR_CLIENT_EXPIRES'); ?> 
                                <?php echo \Joomla\CMS\Factory::getDate($contract->valid_thru)->format('d/m/Y'); ?>
                            </span>
                            <span class="uk-label uk-label-default uk-text-bold uk-border-rounded-0">
                                <?php echo number_format($contract->total_amount / 100, 2, ',', ' '); ?> €
                            </span>
                        </div>
                    </a>
                    <div class="uk-accordion-content uk-margin-remove-top">
                        
                        <?php if (!empty($contract->lines)): ?>
                            <ul class="uk-list uk-list-divider uk-margin-remove-top uk-margin-remove-bottom">
                                <?php foreach ($contract->lines as $line): ?>
                                    <li class="uk-flex uk-flex-between uk-flex-middle custom-uikit-padding-lines">
                                        <span class="uk-text-muted"><?php echo $this->escape($line->description); ?></span>
                                        <span class="uk-text-bold uk-text-emphasis"><?php echo number_format($line->price / 100, 2, ',', ' '); ?> €</span> 
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="custom-uikit-padding-lines">
                                <p class="uk-text-muted uk-text-italic uk-margin-remove"><?php echo Text::_('COM_CONTRACTOR_CLIENT_NOTHING_DISPLAY'); ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($showCtMsg == '1' && !empty($ctMsgText)): ?>
                            <div class="uk-flex uk-flex-top custom-uikit-padding-lines uk-background-muted" style="border-top: 1px solid #e5e5e5; padding-top: 15px !important; padding-bottom: 15px !important;">
                                <span class="fas fa-comment-dots uk-text-muted uk-margin-small-right" style="margin-top: 3px;"></span>
                                <div class="uirem uk-text-muted uk-text-small uk-width-expand">
                                    <?php echo $ctMsgText; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                    </div>
                </li>
            <?php endforeach; ?>

            <li class="uk-card uk-card-default uk-card-small uk-margin-small-bottom uk-border-rounded-0">
                <a class="uk-accordion-title custom-uikit-invoice-bg custom-uikit-padding uk-flex uk-flex-middle" href="#">
                    <span class="fas fa-chevron-right custom-uikit-chevron uk-margin-small-right"></span>
                    <span class="fas fa-file-pdf uk-margin-small-right"></span>
                    <span class="uk-text-uppercase uk-text-bold uk-width-expand"><?php echo Text::_('COM_CONTRACTOR_CLIENT_INVOICES_TITLE'); ?></span>
                    <span class="uk-label uk-border-rounded-0"><?php echo count($this->invoices)." ".Text::_('COM_CONTRACTOR_CLIENT_INVOICES_TITLE'); ?></span>
                </a>
                <div class="uk-accordion-content uk-margin-remove-top">
                    <div class="custom-uikit-padding-lines">
                        <?php if ($hasInvoices): ?>

                            <ul class="uk-list uk-list-divider">
                                <?php foreach ($this->invoices as $invoice): ?>
                                    <li class="uk-flex">
                                        <span class="uk-text-muted uk-margin-large-right"><?php echo $this->escape($invoice->invoicedate); ?></span>
                                        <a class="uk-link-reset" href="<?php echo $this->escape($invoice->pdf_link); ?>" target="_blank"><?php echo $this->escape($invoice->reference); ?> 
                                        <span class="uk-text-uppercase" style="font-size: 75%;">(<?php echo Text::_('COM_CONTRACTOR_CLIENT_DL'); ?>)</span></a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                        <?php else: ?>
                            <p class="uk-text-muted uk-text-italic uk-margin-remove"><?php echo Text::_('COM_CONTRACTOR_CLIENT_NO_INVOICES'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </li>

        </ul>
    <?php endif; ?>
</div>