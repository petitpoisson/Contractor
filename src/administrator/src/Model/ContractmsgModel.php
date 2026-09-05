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

namespace XavierSpirlet\Component\Contractor\Administrator\Model;

\defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use XavierSpirlet\Component\Contractor\Administrator\Helper\ContractorHelper;

/**
 * Model for compiling the contract message
 */
class ContractmsgModel extends BaseDatabaseModel
{
    public function getMessageData(int $contractId)
    {
        $db = $this->getDatabase();

        // 1. Get Contract and Client Data (Added a.rem)
        $query = $db->getQuery(true)
            ->select([
                'a.id', 'a.name AS contract_name', 'a.valid_thru', 'a.rem', 
                'c.client_name', 'c.email', 'c.mailtext'
            ])
            ->from($db->quoteName('#__ctr_contract', 'a'))
            ->join('LEFT', $db->quoteName('#__ctr_client', 'c') . ' ON ' . $db->quoteName('c.id') . ' = ' . $db->quoteName('a.client_id'))
            ->where($db->quoteName('a.id') . ' = ' . $contractId);
            
        $db->setQuery($query);
        $data = $db->loadObject();

        if (!$data) {
            return false;
        }

        // 2. Get Contract Lines
        $linesQuery = $db->getQuery(true)
            ->select(['description', 'price'])
            ->from($db->quoteName('#__ctr_contract_line'))
            ->where($db->quoteName('contract_id') . ' = ' . $contractId);
        $db->setQuery($linesQuery);
        $lines = $db->loadObjectList();

        // 3. Get Configuration
        $config = ContractorHelper::getConfig();

        // 4. Calculate Total and Build Services List HTML
        $totalCents = 0;
        $currency   = $config['currency'] ?? '€';
        
        $linesHtml  = "<table style=\"width: 100%; border-collapse: collapse; margin-top: 20px; margin-bottom: 20px; table-layout: fixed;\">\n";
        $linesHtml .= "  <thead>\n    <tr>\n";
        $linesHtml .= "      <th style=\"border-bottom: 1px solid #ddd; text-align: left; padding: 8px;\">Description</th>\n";
        $linesHtml .= "      <th style=\"border-bottom: 1px solid #ddd; text-align: right; padding: 8px; width: 150px;\">Price</th>\n";
        $linesHtml .= "    </tr>\n  </thead>\n  <tbody>\n";

        if (!empty($lines)) {
            foreach ($lines as $line) {
                $totalCents += (int) $line->price;
                $priceEuros = number_format($line->price / 100, 2, '.', '');
                
                $linesHtml .= "    <tr>\n";
                $linesHtml .= "      <td style=\"padding: 8px; border-bottom: 1px solid #eee; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;\">" . htmlspecialchars($line->description) . "</td>\n";
                $linesHtml .= "      <td style=\"padding: 8px; border-bottom: 1px solid #eee; text-align: right;\">" . $priceEuros . " " . $currency . "</td>\n";
                $linesHtml .= "    </tr>\n";
            }
        }
        $linesHtml .= "  </tbody>\n</table>\n";
        
        $totalEuros = number_format($totalCents / 100, 2, '.', '');
        
        // Final structure for the {serviceslist} tag
        $servicesListHtml  = $linesHtml;
        $servicesListHtml .= "<p style=\"text-align: right; font-weight: bold;\">Total: " . $totalEuros . " " . $currency . "</p>\n";

        // 5. Build Replacement Dictionary for the Live Preview and Final Sending
        $replacements = [
            '{client_name}'        => $data->client_name ?? '',
            '{contract_name}'      => $data->contract_name ?? '',
            '{contract_validthru}' => Factory::getDate($data->valid_thru)->format('d/m/Y'),
            '{currency}'           => $currency,
            '{total_price}'        => $totalEuros,
            '{serviceslist}'       => $servicesListHtml,
            '{rem}'                => $data->rem ?? '', // Added Remarks tag mapping
            '{signature}'          => "<p>" . nl2br($config['signature'] ?? '') . "</p>\n"
        ];

        // 6. Assemble initial raw source (Template text + default pseudo-tags if missing)
        $rawSource = $data->mailtext ?? '';
        
        // Append {serviceslist} if not present in the client's default text
        if (strpos($rawSource, '{serviceslist}') === false) {
            $rawSource .= "\n\n{serviceslist}";
        }
        
        // Append {signature} if not present
        if (strpos($rawSource, '{signature}') === false) {
            $rawSource .= "\n\n{signature}";
        }

        $data->raw_source   = $rawSource;
        $data->replacements = $replacements; 
        $data->config       = $config;

        return $data;
    }
}