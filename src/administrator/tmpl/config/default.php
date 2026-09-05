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
use Joomla\CMS\Session\Session;

// Inject custom CSS to restrict TinyMCE height specifically for this view
$wa = $this->document->getWebAssetManager();
$wa->addInlineStyle('.tox-tinymce { height: 240px !important; min-height: 200px; }');

// Load Joomla's form validation behaviors
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

$csrfToken = Session::getFormToken();
?>
<form action="<?php echo Route::_('index.php?option=com_contractor&view=config'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">

    <?php echo HTMLHelper::_('uitab.startTabSet', 'configTabs', ['active' => 'general', 'recall' => false, 'breakpoint' => 768]); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'configTabs', 'general', Text::_('COM_CONTRACTOR_CONFIGURATION_GEN')); ?>
        <fieldset class="options-form mt-3">
            <legend class="visually-hidden"><?php echo Text::_('COM_CONTRACTOR_CONFIGURATION_GEND'); ?></legend>
            <?php echo $this->form->renderFieldset('general'); ?>
        <div class="d-flex flex-row-reverse">
            <button type="button" class="btn btn-warning" id="btn-reset-delays">
                        <span class="icon-loop" id="icon-reset-delays"></span> <?php echo Text::_('COM_CONTRACTOR_RESET_DELAYS_BTN'); ?>
            </button>
        </div>
        </fieldset>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'configTabs', 'messaging', Text::_('COM_CONTRACTOR_CONFIGURATION_MSG')); ?>
        <fieldset class="options-form mt-3">
            <legend class="visually-hidden"><?php echo Text::_('COM_CONTRACTOR_CONFIGURATION_MSGD'); ?></legend>
            <?php echo $this->form->renderFieldset('messaging'); ?>
        </fieldset>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.addTab', 'configTabs', 'frontend', Text::_('COM_CONTRACTOR_CONFIGURATION_FRE')); ?>
        <fieldset class="options-form mt-3">
            <legend class="visually-hidden"><?php echo Text::_('COM_CONTRACTOR_CONFIGURATION_FRED'); ?></legend>
            <?php echo $this->form->renderFieldset('frontend'); ?>
        </fieldset>
    <?php echo HTMLHelper::_('uitab.endTab'); ?>

    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Inject the Test Button dynamically into the SMTP Password control group
        // This ensures it inherits the Joomla 'showon' display logic perfectly!
        const passwordField = document.getElementById('jform_smtp_password');
        
        if (passwordField) {
            // Find the closest wrapper that Joomla toggles (usually div.control-group or similar)
            const wrapper = passwordField.closest('.control-group') || passwordField.parentNode;
            const btnContainer = document.createElement('div');
            btnContainer.className = 'mt-3 d-flex align-items-center gap-2';
            btnContainer.innerHTML = `
                <button type="button" id="btn-test-smtp" class="btn btn-info btn-sm text-white">
                    <span class="icon-loop" id="icon-test-smtp"></span> <?php echo Text::_('COM_CONTRACTOR_SMTP_TEST_BTN'); ?>
                </button>
                <span id="smtp-test-result" class="fw-bold small"></span>
            `;
            
            wrapper.appendChild(btnContainer);

            // 2. Add AJAX logic for the Test Button
            const btnTest = document.getElementById('btn-test-smtp');
            const resultSpan = document.getElementById('smtp-test-result');
            const iconTest = document.getElementById('icon-test-smtp');

            btnTest.addEventListener('click', function() {
                // UI update: Loading state
                iconTest.classList.add('spin-anim');
                btnTest.disabled = true;
                resultSpan.textContent = '<?php echo Text::_("COM_CONTRACTOR_SMTP_TESTING"); ?>';
                resultSpan.className = 'fw-bold small text-muted';

                // Gather current form data
                const formData = new FormData();
                formData.append('host', document.getElementById('jform_smtp_host').value);
                formData.append('port', document.getElementById('jform_smtp_port').value);
                formData.append('secure', document.getElementById('jform_smtp_security').value);
                formData.append('auth', document.getElementById('jform_smtp_auth').value);
                formData.append('user', document.getElementById('jform_smtp_username').value);
                formData.append('pass', document.getElementById('jform_smtp_password').value);
                formData.append('<?php echo $csrfToken; ?>', '1');

                const baseUrl = '<?php echo Route::_('index.php?option=com_contractor'); ?>';
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 15000); // 15 seconds timeout

                fetch(`${baseUrl}&task=config.testSmtp&format=json`, {
                    method: 'POST',
                    body: formData,
                    signal: controller.signal // Bind the abort signal to the fetch request
                })
                .then(async response => {
                    clearTimeout(timeoutId); // Request succeeded, clear the timeout
                    
                    // Intercept non-JSON errors (e.g., 500 Internal Server Error or HTML output)
                    if (!response.ok) {
                        const errorText = await response.text();
                        throw new Error(`HTTP ${response.status}: ${errorText.substring(0, 100)}...`);
                    }
                    return response.json();
                })
                .then(json => {
                    iconTest.classList.remove('spin-anim');
                    btnTest.disabled = false;

                    if (json.success) {
                        resultSpan.textContent = '<?php echo Text::_("COM_CONTRACTOR_SMTP_TEST_SUCCESS"); ?>';
                        resultSpan.className = 'fw-bold small text-success';
                    } else {
                        resultSpan.textContent = '<?php echo Text::_("COM_CONTRACTOR_SMTP_TEST_FAILED"); ?>: ' + json.message;
                        resultSpan.className = 'fw-bold small text-danger';
                    }
                })
                .catch(err => {
                    clearTimeout(timeoutId); // Clear timeout just in case it was a different error
                    iconTest.classList.remove('spin-anim');
                    btnTest.disabled = false;
                    
                    // Distinguish between our forced timeout and general network errors
                    if (err.name === 'AbortError') {
                        console.error("SMTP Test Error: Client-side timeout reached.");
                        resultSpan.textContent = '<?php echo Text::_("COM_CONTRACTOR_SMTP_TEST_TIMEOUT"); ?>';
                    } else {
                        console.error("SMTP Test Error:", err);
                        resultSpan.textContent = 'Network or Server Error (Check Console)';
                    }
                    
                    resultSpan.className = 'fw-bold small text-danger';
                });
            });
        }
        // --- 2. Reset Warning Delays Logic ---
        const btnReset = document.getElementById('btn-reset-delays');
        if (btnReset) {
            btnReset.addEventListener('click', function() {
                if (confirm('<?php echo Text::_("COM_CONTRACTOR_RESET_DELAYS_CONFIRM"); ?>')) {
                    const iconReset = document.getElementById('icon-reset-delays');
                    iconReset.classList.add('spin-anim');
                    btnReset.disabled = true;

                    const formData = new FormData();
                    formData.append('<?php echo $csrfToken; ?>', '1');
                    const baseUrl = '<?php echo Route::_('index.php?option=com_contractor'); ?>';

                    fetch(`${baseUrl}&task=config.resetDelays&format=json`, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(json => {
                        iconReset.classList.remove('spin-anim');
                        btnReset.disabled = false;
                        alert(json.message);
                    })
                    .catch(err => {
                        iconReset.classList.remove('spin-anim');
                        btnReset.disabled = false;
                        alert('Error: ' + err);
                    });
                }
            });
        }
    });
</script>

<style>
/* Helper animation for the spinning icon */
@keyframes joomla-spin {
    100% { transform: rotate(360deg); }
}
.spin-anim {
    animation: joomla-spin 1s linear infinite;
    display: inline-block;
}
</style>