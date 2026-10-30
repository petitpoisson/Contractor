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
$wa->addInlineStyle('.tox-tinymce { height: 350px !important; min-height: 200px; }');

// Load Joomla's form validation behaviors
HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

$csrfToken = Session::getFormToken();
?>
<form action="<?php echo Route::_('index.php?option=com_contractor&view=config'); ?>" method="post" name="adminForm" id="adminForm" class="form-validate">

    <?php echo HTMLHelper::_('uitab.startTabSet', 'configTabs', ['active' => 'general', 'recall' => false, 'breakpoint' => 768]); ?>

    <?php 
    // Tableau de correspondance pour vos onglets natifs (puisqu'ils n'ont pas de label dans le config.xml)
    $coreLabels = [
        'general'   => 'COM_CONTRACTOR_CONFIGURATION_GEN',
        'messaging' => 'COM_CONTRACTOR_CONFIGURATION_MSG',
        'frontend'  => 'COM_CONTRACTOR_CONFIGURATION_FRE'
    ];

    // Boucle dynamique sur TOUS les fieldsets (natifs + ceux injectés par les plugins)
    foreach ($this->form->getFieldsets() as $fieldset) : 
        $name = $fieldset->name;
        
        // On récupère le label depuis notre tableau, sinon depuis le XML (pour les plugins), sinon le nom brut
        $labelKey = $coreLabels[$name] ?? ($fieldset->label ?? $name);
        $tabLabel = Text::_($labelKey);
    ?>
        <?php echo HTMLHelper::_('uitab.addTab', 'configTabs', $name, $tabLabel); ?>
            <fieldset class="options-form mt-3">
                <legend class="visually-hidden"><?php echo $tabLabel; ?></legend>
                
                <?php echo $this->form->renderFieldset($name); ?>
                
                <?php /* On réinjecte le bouton de réinitialisation UNIQUEMENT à la fin de l'onglet "general" */ ?>
                <?php if ($name === 'general') : ?>
                    <div class="d-flex flex-row-reverse mt-3">
                        <button type="button" class="btn btn-warning" id="btn-reset-delays">
                            <span class="icon-loop" id="icon-reset-delays"></span> <?php echo Text::_('COM_CONTRACTOR_RESET_DELAYS_BTN'); ?>
                        </button>
                    </div>
                <?php endif; ?>
                
            </fieldset>
        <?php echo HTMLHelper::_('uitab.endTab'); ?>
    <?php endforeach; ?>

    <?php echo HTMLHelper::_('uitab.endTabSet'); ?>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Inject the Test Button dynamically into the SMTP Password control group
        const passwordField = document.getElementById('jform_smtp_password');
        
        if (passwordField) {
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
                iconTest.classList.add('spin-anim');
                btnTest.disabled = true;
                resultSpan.textContent = '<?php echo Text::_("COM_CONTRACTOR_SMTP_TESTING"); ?>';
                resultSpan.className = 'fw-bold small text-muted';

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
                const timeoutId = setTimeout(() => controller.abort(), 15000);

                fetch(`${baseUrl}&task=config.testSmtp&format=json`, {
                    method: 'POST',
                    body: formData,
                    signal: controller.signal
                })
                .then(async response => {
                    clearTimeout(timeoutId);
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
                    clearTimeout(timeoutId);
                    iconTest.classList.remove('spin-anim');
                    btnTest.disabled = false;
                    
                    if (err.name === 'AbortError') {
                        resultSpan.textContent = '<?php echo Text::_("COM_CONTRACTOR_SMTP_TEST_TIMEOUT"); ?>';
                    } else {
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
@keyframes joomla-spin {
    100% { transform: rotate(360deg); }
}
.spin-anim {
    animation: joomla-spin 1s linear infinite;
    display: inline-block;
}
</style>