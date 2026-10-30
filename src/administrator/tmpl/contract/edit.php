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
use XavierSpirlet\Component\Contractor\Administrator\Helper\ContractorHelper;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');

$isNew = ($this->item->id == 0);
$csrfToken = Session::getFormToken(); 

// Get config
$config   = ContractorHelper::getConfig();
?>

<form action="<?php echo Route::_('index.php?option=com_contractor&layout=edit&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="item-form" class="form-validate">
    
    <div class="card mb-4 shadow-sm">
        <div class="card-header text-white bg-primary">
            <?php if (!$isNew) : ?>
            <strong><?php echo Text::_("COM_CONTRACTOR_CONTRACT_FH"); ?></strong>
            <?php else : ?>
            <strong><?php echo Text::_("COM_CONTRACTOR_CONTRACT_NEW"); ?></strong>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div class="row">
                <?php echo $this->form->renderField('name'); ?>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <?php if (!$isNew) : ?>
                        <input type="hidden" name="jform[id]" value="<?php echo (int) $this->item->id; ?>">
                    <?php else : ?>
                        <?php echo $this->form->renderField('id'); ?>
                    <?php endif; ?>

                    <?php echo $this->form->renderField('client_id'); ?>
                    <?php echo $this->form->renderField('published'); ?>
                    <?php echo $this->form->renderField('tag_id'); ?>
                </div>

                <div class="col-md-6">
                    <?php echo $this->form->renderField('date_start'); ?>
                    
                    <?php 
                        // We render the valid_thru field, the JS below will append the button
                        echo $this->form->renderField('valid_thru'); 
                    ?>
                    
                    <?php echo $this->form->renderField('lastmsg'); ?>
                </div>
            </div>
        </div>
        <div class="card-header bg-primary text-white">
            <strong><?php echo Text::_("COM_CONTRACTOR_CONTRACT_REMTITLE"); ?></strong>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <strong><?php echo Text::_("COM_CONTRACTOR_CONTRACT_REM"); ?></strong>
                        <?php echo $this->form->renderField('rem'); ?>
                    <div id="editor-rem" style="height: 150px; background: #fff;"></div>
                    <small class="text-muted"><?php echo Text::_("COM_CONTRACTOR_CONTRACT_REMDESC"); ?></small>
                </div>
                <div class="col-md-6">
                    <strong><?php echo Text::_("COM_CONTRACTOR_CONTRACT_XREM"); ?></strong>
                        <?php echo $this->form->renderField('xrem'); ?>
                    <div id="editor-xrem" style="height: 150px; background: #fff;"></div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-stretch border-bottom">
            <div class="bg-primary text-white p-3 flex-grow-1 d-flex align-items-center">
                <strong class="m-0"><?php echo Text::_("COM_CONTRACTOR_CONTRACT_SERVICESLIST"); ?></strong>
            </div>
            <div class="px-4 py-2 fs-3 fw-bold text-dark d-flex align-items-center" id="total-cost-display" style="background-color: #e9ecef; min-width: 250px; justify-content: flex-end;">
                <?php echo $config['currency']; ?> total 0.00
            </div>
        </div>
        
        <div class="card-body">
            <?php if ($isNew) : ?>
                <div class="alert alert-warning mb-0">
                    <span class="icon-warning"></span> <?php echo Text::_("COM_CONTRACTOR_CONTRACT_NCWARN"); ?>
                </div>
            <?php else : ?>
                
                <table class="table table-sm table-striped border mb-3">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-end" width="150"><?php echo $config['currency']; ?></th>
                            <th class="text-end" width="120">&nbsp;</th>
                        </tr>
                    </thead>
                    <tbody id="lines-table-body">
                        <tr><td colspan="3" class="text-center text-muted"><?php echo Text::_("COM_CONTRACTOR_CONTRACT_NCWARN"); ?></td></tr>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>
                                <input type="hidden" id="edit-line-id" value="0">
                                <input type="text" id="edit-line-desc" class="form-control p-1 w-100" placeholder="<?php echo Text::_("COM_CONTRACTOR_CONTRACT_LINEPH"); ?>">
                            </td>
                            <td>
                                <div class="custom-number-wrapper">
                                    <input type="number" id="edit-line-price" class="form-control p-1 no-spinners text-end" step="0.01" value="0.00">
                                    <div class="custom-spinners">
                                        <button type="button" id="btn-price-plus" tabindex="-1"><span class="icon-caret-up"></span></button>
                                        <button type="button" id="btn-price-minus" tabindex="-1"><span class="icon-caret-down"></span></button>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-success p-1 px-3" onclick="saveContractLine()" title="<?php echo Text::_("COM_CONTRACTOR_CONTRACT_LINEADD2"); ?>">
                                    <span class="icon-save"></span> <span id="btn-save-line-text"><?php echo Text::_("COM_CONTRACTOR_CONTRACT_LINEADD"); ?></span>
                                </button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
                
            <?php 
            endif; 

            // --- HOOK : Zone de réglages additionnels des plugins ---
            \Joomla\CMS\Plugin\PluginHelper::importPlugin('contractor');
            $eventSettings = new \Joomla\CMS\Event\GenericEvent('onContractorRenderServiceSettings', [
                'contractId' => (int) $this->item->id,
                'html'       => []
            ]);
            $eventSettings = \Joomla\CMS\Factory::getApplication()->getDispatcher()->dispatch('onContractorRenderServiceSettings', $eventSettings);
            echo implode("\n", $eventSettings->getArgument('html'));
            ?>
        </div>        
    </div>

    <input type="hidden" name="task" value="">
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<?php
// Récupération des couleurs des tags pour le JavaScript
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
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
<style>
    .ql-toolbar.ql-snow { border-top-left-radius: 0.375rem; border-top-right-radius: 0.375rem; border-color: #dee2e6; background-color: #f8f9fa; }
    .ql-container.ql-snow { border-bottom-left-radius: 0.375rem; border-bottom-right-radius: 0.375rem; border-color: #dee2e6; font-family: inherit; font-size: inherit; }

    /* Added to style the native textarea when source mode is toggled on */
    textarea.source-mode-active { display: block !important; width: 100%; height: 150px; font-family: Consolas, monospace; font-size: 14px; border: 1px solid #dee2e6; border-bottom-left-radius: 0.375rem; border-bottom-right-radius: 0.375rem; padding: 10px; resize: vertical; }

    /* Hide native spinners for the price field */
    input[type=number].no-spinners::-webkit-inner-spin-button, 
    input[type=number].no-spinners::-webkit-outer-spin-button { 
        -webkit-appearance: none; 
        margin: 0; 
    }
    input[type=number].no-spinners {
        -moz-appearance: textfield; /* For Firefox */
    }

    /* Custom stacked spinners styling */
    .custom-number-wrapper {
        position: relative;
        display: block;
    }
    .custom-number-wrapper input {
        padding-right: 1.5rem !important;
    }
    .custom-spinners {
        position: absolute;
        top: 1px;
        right: 1px;
        height: calc(100% - 2px);
        width: 1.25rem;
        display: flex;
        flex-direction: column;
        border-left: 1px solid #dee2e6;
        background: #f8f9fa;
        border-top-right-radius: 0.25rem;
        border-bottom-right-radius: 0.25rem;
        overflow: hidden;
    }
    .custom-spinners button {
        flex: 1;
        border: none;
        background: transparent;
        padding: 0;
        line-height: 1;
        font-size: 0.6rem;
        color: #495057;
        cursor: pointer;
    }
    .custom-spinners button:hover {
        background: #e9ecef;
    }
    .custom-spinners button:first-child {
        border-bottom: 1px solid #dee2e6;
    }
    
    /* Utility flex class for the date picker wrapper */
    .date-wrapper-flex {
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        width: 100%;
    }
    /* Force the native web component to stretch and take remaining space */
    .date-wrapper-flex > joomla-field-calendar {
        flex: 1 1 auto;
        min-width: 0;
    }
</style>

<script>
// Global Data
const tNoLines = "<?php echo Text::_('COM_CONTRACTOR_TNOLINES'); ?>";
const tNoDesc  = "<?php echo Text::_('COM_CONTRACTOR_TNODESC'); ?>";
<?php if (!$isNew) : ?>
const contractId = <?php echo (int) $this->item->id; ?>;
const csrfToken  = "<?php echo $csrfToken; ?>";
const baseUrl    = "<?php echo Route::_('index.php?option=com_contractor'); ?>";
<?php endif; ?>

// --- GLOBAL DOMContentLoaded BLOCK ---
document.addEventListener('DOMContentLoaded', function() {
    
    // ---------------------------------------------------------
    // 1. QUILL INITIALIZATION
    // ---------------------------------------------------------
    const icons = Quill.import('ui/icons');
    icons['source'] = '<svg viewBox="0 0 18 18"><polyline class="ql-even ql-stroke" points="5 7 3 9 5 11"></polyline><polyline class="ql-even ql-stroke" points="13 7 15 9 13 11"></polyline><line class="ql-even ql-stroke" x1="10" x2="8" y1="5" y2="13"></line></svg>';

    const toolbarOptions = [
        ['bold', 'italic', 'underline'],
        [{ 'color': [] }], 
        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
        ['link', 'clean'],
        ['source'] 
    ];

    function initQuill(editorId, joomlaInputId) {
        const joomlaTextarea = document.getElementById(joomlaInputId);
        if (!joomlaTextarea) return;

        joomlaTextarea.style.display = 'none';
        const editorDiv = document.getElementById(editorId);
        editorDiv.innerHTML = joomlaTextarea.value;
        let isSourceMode = false;

        const quill = new Quill(editorDiv, {
            theme: 'snow',
            modules: { 
                toolbar: {
                    container: toolbarOptions,
                    handlers: {
                        'source': function() {
                            const toolbarButton = this.container.querySelector('.ql-source');
                            isSourceMode = !isSourceMode;

                            if (isSourceMode) {
                                let html = quill.getSemanticHTML();
                                html = html.replace(/&nbsp;/g, ' ').replace(/\u00a0/g, ' ');
                                if (html === '<p><br></p>' || html === '<p></p>') html = '';
                                joomlaTextarea.value = html;
                                
                                editorDiv.style.display = 'none';
                                joomlaTextarea.classList.add('source-mode-active');
                                toolbarButton.classList.add('ql-active');
                            } else {
                                quill.clipboard.dangerouslyPasteHTML(joomlaTextarea.value);
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

        quill.on('text-change', function() {
            if (!isSourceMode) {
                let html = quill.getSemanticHTML();
                html = html.replace(/&nbsp;/g, ' ').replace(/\u00a0/g, ' ');
                if (html === '<p><br></p>' || html === '<p></p>') html = '';
                joomlaTextarea.value = html;
            }
        });
    }

    initQuill('editor-rem', 'jform_rem');
    initQuill('editor-xrem', 'jform_xrem');
    
    // ---------------------------------------------------------
    // 2. TAG COLORS INITIALIZATION
    // ---------------------------------------------------------
    const tagColors = <?php echo json_encode($tagColorsMap); ?>;
    
    function getContrastColor(hexColor) {
        hexColor = hexColor.replace('#', '');
        if (hexColor.length === 3) hexColor = hexColor[0] + hexColor[0] + hexColor[1] + hexColor[1] + hexColor[2] + hexColor[2];
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

    const fancyWrap = document.querySelector('joomla-field-fancy-select');
    if (fancyWrap) {
        const observer = new MutationObserver(() => applyTagColors());
        observer.observe(fancyWrap, { childList: true, subtree: true });
        setTimeout(applyTagColors, 100);
    }
    
    // ---------------------------------------------------------
    // 3. +1 YEAR BUTTON FOR VALID_THRU DATE
    // ---------------------------------------------------------
    const validThruInput = document.getElementById('jform_valid_thru');
    if (validThruInput) {
        const joomlaCalendar = validThruInput.closest('joomla-field-calendar');
        const wrapper = joomlaCalendar ? joomlaCalendar.parentNode : validThruInput.parentNode;
        
        wrapper.classList.add('date-wrapper-flex');
        
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'btn btn-primary text-nowrap rounded-4';
        btn.innerHTML = '<span class="icon-calendar" aria-hidden="true"></span> <?php echo Text::_("COM_CONTRACTOR_CONTRACT_PLUSONE"); ?>';
        
        btn.addEventListener('click', function() {
            if (validThruInput.value) {
                const dateObj = new Date(validThruInput.value);
                if (!isNaN(dateObj.getTime())) {
                    dateObj.setFullYear(dateObj.getFullYear() + 1);
                    validThruInput.value = dateObj.toISOString().split('T')[0];
                    validThruInput.dispatchEvent(new Event('input', { bubbles: true }));
                    validThruInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
        });
        
        wrapper.appendChild(btn);
    }
    
    // ---------------------------------------------------------
    // 4. CONTRACT LINES SPINNERS INITIALIZATION
    // ---------------------------------------------------------
    <?php if (!$isNew) : ?>
    const priceInput = document.getElementById('edit-line-price');
    const descInput  = document.getElementById('edit-line-desc');
    const btnPlus    = document.getElementById('btn-price-plus');
    const btnMinus   = document.getElementById('btn-price-minus');

    const handleEnterKey = function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            saveContractLine();
        }
    };

    if (descInput) descInput.addEventListener('keydown', handleEnterKey);

    if (priceInput) {
        priceInput.addEventListener('keydown', handleEnterKey);
        priceInput.addEventListener('blur', function() {
            let val = parseFloat(this.value) || 0;
            this.value = val.toFixed(2);
        });

        if (btnPlus) {
            btnPlus.addEventListener('click', function() {
                let val = parseFloat(priceInput.value) || 0;
                priceInput.value = (val + 1).toFixed(2);
            });
        }

        if (btnMinus) {
            btnMinus.addEventListener('click', function() {
                let val = parseFloat(priceInput.value) || 0;
                priceInput.value = (val - 1).toFixed(2);
            });
        }
    }
    
    loadLines(); // Load lines when DOM is ready
    <?php endif; ?>

}); // END OF GLOBAL DOMContentLoaded

// ---------------------------------------------------------
// --- CONTRACT LINES LOGIC (AJAX FUNCTIONS) ---
// ---------------------------------------------------------
<?php if (!$isNew) : ?>
function loadLines(focusAfterLoad = false) {
    fetch(`${baseUrl}&task=contract.getLines&contract_id=${contractId}&format=json`)
        .then(response => response.json())
        .then(json => {
            if(json.data) {
                renderLines(json.data, focusAfterLoad);
            }
        });
}

function renderLines(lines, focusAfterLoad = false) {
    const tbody = document.getElementById('lines-table-body');
    let html = '';
    let totalCents = 0;

    if (lines.length === 0) {
        html = '<tr><td colspan="3" class="text-center text-muted">'+tNoLines+'</td></tr>';
    } else {
        lines.forEach(line => {
            const priceCents = parseInt(line.price, 10) || 0;
            totalCents += priceCents;
            const priceEuros = (priceCents / 100).toFixed(2);

            html += `
                <tr>
                    <td class="align-middle">${line.description}</td>
                    <td class="align-middle text-end">${priceEuros}</td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-primary" onclick="editLine(${line.id}, '${line.description.replace(/'/g, "\\'")}', ${priceEuros})" title="<?php echo Text::_("COM_CONTRACTOR_CONTRACT_EDITLINE"); ?>"><span class="icon-edit"></span></button>
                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteLine(${line.id})" title="<?php echo Text::_("COM_CONTRACTOR_CONTRACT_DELLINE"); ?>"><span class="icon-trash"></span></button>
                    </td>
                </tr>
            `;
        });
    }

    tbody.innerHTML = html;
    document.getElementById('total-cost-display').innerHTML = `<?php echo $config['currency']; ?> total ${(totalCents / 100).toFixed(2)}`;
    resetLineForm(focusAfterLoad);
}

function editLine(id, description, priceEuros) {
    document.getElementById('edit-line-id').value = id;
    document.getElementById('edit-line-desc').value = description;
    document.getElementById('edit-line-price').value = priceEuros;
    document.getElementById('btn-save-line-text').innerText = '<?php echo Text::_("COM_CONTRACTOR_CONTRACT_LINEUPD"); ?>';
}

function resetLineForm(setFocus = false) {
    document.getElementById('edit-line-id').value = '0';
    document.getElementById('edit-line-desc').value = '';
    document.getElementById('edit-line-price').value = '0.00';
    document.getElementById('btn-save-line-text').innerText = '<?php echo Text::_("COM_CONTRACTOR_CONTRACT_LINEADD"); ?>';
    if (setFocus) {
        document.getElementById('edit-line-desc').focus();
    }
}

function saveContractLine() {
    const id = document.getElementById('edit-line-id').value;
    const desc = document.getElementById('edit-line-desc').value.trim();
    const priceEuros = parseFloat(document.getElementById('edit-line-price').value) || 0;

    if (desc === '') {
        alert(tNoDesc);
        return;
    }

    const priceCents = Math.round(priceEuros * 100);

    const formData = new FormData();
    formData.append('line_id', id);
    formData.append('contract_id', contractId);
    formData.append('description', desc);
    formData.append('price', priceCents);
    formData.append(csrfToken, '1');

    fetch(`${baseUrl}&task=contract.saveLine&format=json`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(json => {
        if(json.success) {
            loadLines(true);
        } else {
            alert(json.message);
        }
    });
}

function deleteLine(id) {
    if (!confirm('<?php echo Text::_("COM_CONTRACTOR_CONTRACT_DELCONF"); ?>')) return;

    const formData = new FormData();
    formData.append('line_id', id);
    formData.append(csrfToken, '1');

    fetch(`${baseUrl}&task=contract.deleteLine&format=json`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(json => {
        if(json.success) {
            loadLines();
        } else {
            alert(json.message);
        }
    });
}
<?php endif; ?>
</script>