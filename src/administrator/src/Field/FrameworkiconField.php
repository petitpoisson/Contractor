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

namespace XavierSpirlet\Component\Contractor\Administrator\Field;

\defined('_JEXEC') or die;

use Joomla\CMS\Form\FormField;

class FrameworkiconField extends FormField
{
    protected $type = 'Frameworkicon';

    protected function getInput()
    {
        // Build custom radio buttons wrapped in a flex container for spacing
        $html = [];
        // Removed 'btn-group' and used Flexbox utility classes to separate the buttons
        $html[] = '<div class="d-flex gap-2">';
        
        // Bootstrap option
        $checked0 = ((string) $this->value === '0') ? 'checked' : '';
        $html[] = '<input type="radio" class="btn-check" name="' . $this->name . '" id="' . $this->id . '_0" value="0" ' . $checked0 . '>';
        $html[] = '<label class="btn btn-outline-primary" for="' . $this->id . '_0"><span class="fab fa-bootstrap"></span> Bootstrap</label>';

        // UIkit option
        $checked1 = ((string) $this->value === '1') ? 'checked' : '';
        $html[] = '<input type="radio" class="btn-check" name="' . $this->name . '" id="' . $this->id . '_1" value="1" ' . $checked1 . '>';
        $html[] = '<label class="btn btn-outline-primary" for="' . $this->id . '_1"><span class="fab fa-uikit"></span> UIkit</label>';
        
        $html[] = '</div>';

        return implode('', $html);
    }
}