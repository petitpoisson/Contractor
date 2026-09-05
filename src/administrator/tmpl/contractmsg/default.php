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

HTMLHelper::_('behavior.keepalive');

$config  = $this->data->config;
$nowDate = Factory::getDate()->format('D, d M Y H:i:s O');

// Determine Mail Engine
$engineMap = [
    0 => Text::_('COM_CONTRACTOR_MSG_ENGINE_JOOMLA'),
    1 => Text::_('COM_CONTRACTOR_MSG_ENGINE_SENDMAIL'),
    2 => Text::_('COM_CONTRACTOR_MSG_ENGINE_SMTP')
];
$engineType = (int) ($config['mailengine'] ?? 0);
$engineName = $engineMap[$engineType] ?? $engineMap[0];
?>

<form action="<?php echo Route::_('index.php?option=com_contractor'); ?>" method="post" name="adminForm" id="adminForm">
    
    <div class="row">
        <div class="col-md-6" style="min-width: 0;">
            
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <strong><span class="icon-mail" aria-hidden="true"></span> <?php echo Text::_('COM_CONTRACTOR_MSG_SETTINGS_TITLE'); ?></strong>
                    <span class="badge bg-light text-dark"><?php echo Text::_('COM_CONTRACTOR_MSG_ENGINE_LABEL'); ?>: <?php echo $engineName; ?></span>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <label class="col-sm-3 col-form-label text-end fw-bold"><?php echo Text::_('COM_CONTRACTOR_MSG_HEADER_FROMNAME'); ?></label>
                        <div class="col-sm-9">
                            <input type="text" name="mail_from_name" id="input_mail_from_name" class="form-control form-control-sm" value="<?php echo $this->escape($config['mailfromname'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="row mb-2">
                        <label class="col-sm-3 col-form-label text-end fw-bold"><?php echo Text::_('COM_CONTRACTOR_MSG_HEADER_FROM'); ?></label>
                        <div class="col-sm-9">
                            <input type="text" name="mail_from" id="input_mail_from" class="form-control form-control-sm" value="<?php echo $this->escape($config['mailfrom'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="row mb-2">
                        <label class="col-sm-3 col-form-label text-end fw-bold"><?php echo Text::_('COM_CONTRACTOR_MSG_HEADER_REPLYTO'); ?></label>
                        <div class="col-sm-9">
                            <input type="text" name="mail_replyto" id="input_mail_replyto" class="form-control form-control-sm" value="<?php echo $this->escape($config['mailreply'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="row mb-2">
                        <label class="col-sm-3 col-form-label text-end fw-bold"><?php echo Text::_('COM_CONTRACTOR_MSG_HEADER_TO'); ?></label>
                        <div class="col-sm-9">
                            <input type="text" name="mail_to" id="input_mail_to" class="form-control form-control-sm" value="<?php echo $this->escape($this->data->email); ?>">
                        </div>
                    </div>
                    <div class="row">
                        <label class="col-sm-3 col-form-label text-end fw-bold"><?php echo Text::_('COM_CONTRACTOR_MSG_HEADER_SUBJECT'); ?></label>
                        <div class="col-sm-9">
                            <input type="text" name="mail_subject" id="input_mail_subject" class="form-control form-control-sm" value="<?php echo $this->escape($config['mailsubject'] ?? 'Notification'); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <strong><span class="icon-file-text" aria-hidden="true"></span> <?php echo Text::_('COM_CONTRACTOR_MSG_SOURCE_TITLE'); ?></strong>
                </div>
                <div class="card-body p-0">
                    <textarea name="mail_source" id="mail_source" class="form-control border-0 p-3 mail-source-textarea" spellcheck="false"><?php echo htmlspecialchars($this->data->raw_source); ?></textarea>
                </div>
                <div class="card-body py-2">
                <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-info btn-sm tag-insert" data-tag="{client_name}">{client_name}</button>
                        <button type="button" class="btn btn-outline-info btn-sm tag-insert" data-tag="{contract_name}">{contract_name}</button>
                        <button type="button" class="btn btn-outline-info btn-sm tag-insert" data-tag="{contract_validthru}">{contract_validthru}</button>
                        <button type="button" class="btn btn-outline-info btn-sm tag-insert" data-tag="{currency}">{currency}</button>
                        <button type="button" class="btn btn-outline-info btn-sm tag-insert" data-tag="{total_price}">{total_price}</button>
                        <button type="button" class="btn btn-outline-info btn-sm tag-insert" data-tag="{serviceslist}">{serviceslist}</button>
                        <button type="button" class="btn btn-outline-info btn-sm tag-insert" data-tag="{rem}">{rem}</button>
                        <button type="button" class="btn btn-outline-info btn-sm tag-insert" data-tag="{signature}">{signature}</button>
                    </div>
                </div>
                <div class="card-footer bg-light text-muted small">
                    <?php echo Text::_('COM_CONTRACTOR_MSG_SOURCE_HELP'); ?>
                </div>
            </div>
        </div>

        <div class="col-md-6" style="min-width: 0;">
            <div class="card shadow-sm mb-4" style="height: 100%;">
                <div class="card-header bg-secondary text-white">
                    <strong><span class="icon-eye" aria-hidden="true"></span> <?php echo Text::_('COM_CONTRACTOR_MSG_PREVIEW_TITLE'); ?></strong>
                </div>
                
                <div class="card-body bg-light border-bottom p-3" style="font-family: Arial, sans-serif; font-size: 14px;">
                    <div class="row mb-1">
                        <div class="col-sm-3 text-muted text-end fw-bold"><?php echo Text::_('COM_CONTRACTOR_MSG_HEADER_FROMNAME'); ?></div>
                        <div class="col-sm-9 text-dark" id="preview_mail_from_name"></div>
                    </div>
                    <div class="row mb-1">
                        <div class="col-sm-3 text-muted text-end fw-bold"><?php echo Text::_('COM_CONTRACTOR_MSG_HEADER_FROM'); ?></div>
                        <div class="col-sm-9 text-dark" id="preview_mail_from"></div>
                    </div>
                    <div class="row mb-1">
                        <div class="col-sm-3 text-muted text-end fw-bold"><?php echo Text::_('COM_CONTRACTOR_MSG_HEADER_REPLYTO'); ?></div>
                        <div class="col-sm-9 text-dark" id="preview_mail_replyto"></div>
                    </div>
                    <div class="row mb-1">
                        <div class="col-sm-3 text-muted text-end fw-bold"><?php echo Text::_('COM_CONTRACTOR_MSG_HEADER_TO'); ?></div>
                        <div class="col-sm-9 text-primary" id="preview_mail_to"></div>
                    </div>
                    <div class="row mb-1">
                        <div class="col-sm-3 text-muted text-end fw-bold"><?php echo Text::_('COM_CONTRACTOR_MSG_HEADER_SUBJECT'); ?></div>
                        <div class="col-sm-9 text-dark" id="preview_mail_subject"></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-3 text-muted text-end fw-bold"><?php echo Text::_('COM_CONTRACTOR_MSG_HEADER_DATE'); ?></div>
                        <div class="col-sm-9 text-dark"><?php echo $nowDate; ?></div>
                    </div>
                </div>

                <div class="card-body bg-white p-4" style="overflow-y: auto; height: calc(100% - 180px);" id="mail_preview_container">
                    </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value="">
    <input type="hidden" name="id" value="<?php echo (int) $this->data->id; ?>">
    <input type="hidden" name="return" value="<?php echo $this->escape($this->returnCtx); ?>">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<style>
    /* Styling the raw textarea for optimal code visibility and stability */
    textarea.mail-source-textarea {
        height: 500px;
        font-family: Consolas, Monaco, 'Andale Mono', 'Ubuntu Mono', monospace;
        font-size: 13px;
        line-height: 1.5;
        resize: none;
        border-radius: 0 0 0.375rem 0.375rem;
        box-sizing: border-box;
        background-color: #fafafa;
    }
    textarea.mail-source-textarea:focus {
        background-color: #fff;
    }
</style>

<script>
    // Injects the PHP replacements dictionary directly into Javascript
    const tagReplacements = <?php echo json_encode($this->data->replacements); ?>;

    document.addEventListener('DOMContentLoaded', function() {
        const sourceEditor     = document.getElementById('mail_source');
        const previewContainer = document.getElementById('mail_preview_container');

        // Header Configuration Fields
        const inputFrom    = document.getElementById('input_mail_from');
        const inputFromN   = document.getElementById('input_mail_from_name');
        const inputReplyTo = document.getElementById('input_mail_replyto');
        const inputTo      = document.getElementById('input_mail_to');
        const inputSubject = document.getElementById('input_mail_subject');

        // Target Preview Elements
        const prevFrom    = document.getElementById('preview_mail_from');
        const prevFromN   = document.getElementById('preview_mail_from_name');
        const prevReplyTo = document.getElementById('preview_mail_replyto');
        const prevTo      = document.getElementById('preview_mail_to');
        const prevSubject = document.getElementById('preview_mail_subject');

        // Real-time synchronization of the HTML message body with Tag Replacements
        function updateBodyPreview() {
            if (previewContainer && sourceEditor) {
                let currentHtml = sourceEditor.value;
                
                // Iterate over the dictionary and replace all tags
                for (const [tag, value] of Object.entries(tagReplacements)) {
                    // Escape curly braces for the Regex, then globally replace
                    const regex = new RegExp(tag.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1"), 'g');
                    currentHtml = currentHtml.replace(regex, value);
                }
                
                previewContainer.innerHTML = currentHtml;
            }
        }

        // Real-time synchronization of text header outputs
        function updateHeaderPreviews() {
            if (prevFrom)    prevFrom.textContent    = inputFrom.value;
            if (prevFromN)   prevFromN.textContent   = inputFromN.value;
            if (prevReplyTo) prevReplyTo.textContent = inputReplyTo.value;
            if (prevTo)      prevTo.textContent      = inputTo.value;
            if (prevSubject) prevSubject.textContent = inputSubject.value;
        }

        // --- TAG INSERTION MECHANISM ---
        document.querySelectorAll('.tag-insert').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault(); 
                
                const tag = this.getAttribute('data-tag');
                
                if (sourceEditor) {
                    const start = sourceEditor.selectionStart;
                    const end   = sourceEditor.selectionEnd;
                    const text  = sourceEditor.value;
                    
                    // Inject selection safely at cursor position
                    sourceEditor.value = text.slice(0, start) + tag + text.slice(end);
                    
                    // Reposition cursor right after the newly inserted token
                    sourceEditor.selectionStart = sourceEditor.selectionEnd = start + tag.length;
                    sourceEditor.focus();
                    
                    // Trigger live display refresh
                    updateBodyPreview();
                }
            });
        });

        // Initialize display bindings
        updateHeaderPreviews();
        updateBodyPreview();

        // Attach listeners for immediate input detection
        if (sourceEditor) sourceEditor.addEventListener('input', updateBodyPreview);
        if (inputFrom)    inputFrom.addEventListener('input', updateHeaderPreviews);
        if (inputReplyTo) inputReplyTo.addEventListener('input', updateHeaderPreviews);
        if (inputTo)      inputTo.addEventListener('input', updateHeaderPreviews);
        if (inputSubject) inputSubject.addEventListener('input', updateHeaderPreviews);
        if (inputFromN)   inputFromN.addEventListener('input', updateHeaderPreviews);
    });
    // Intercept Joomla toolbar submission to add confirmation prompt
    if (window.Joomla && window.Joomla.submitbutton) {
        const originalSubmit = window.Joomla.submitbutton;
        window.Joomla.submitbutton = function(task) {
            if (task === 'contractmsg.send') {
                if (!confirm('<?php echo Text::_("COM_CONTRACTOR_MSG_CONFIRM_SEND"); ?>')) {
                    return false; // Cancel submission
                }
            }
            // Proceed with normal submission logic if confirmed or if it's another task (like cancel)
            return originalSubmit.call(window.Joomla, task);
        };
    }
</script>