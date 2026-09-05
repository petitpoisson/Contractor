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

use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;

$wa = $this->document->getWebAssetManager();
$wa->useStyle('com_contractor.main');

$version = $this->config['version'] ?? '1.0.0 (Unknown)';
?>
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="display-1" style="line-height: 40px;"><span class="fas fa-file-contract"></span>&nbsp;<?php echo Text::_('COM_CONTRACTOR_DASHBOARD_TITLE'); ?><br />
                <span class="display-6"><?php echo Text::_('COM_CONTRACTOR_DASHBOARD_SUBTITLE'); ?></span></h1>
            <p class="text-muted"><?php echo Text::_('COM_CONTRACTOR_VERSION'); ?> : <?php echo $this->escape($version); ?></p>
        </div>
    </div>

    <div class="row">
        
        <div class="col-md-6 col-lg-4 mb-4" style="cursor: pointer;" onclick="window.location.href='<?php echo Route::_('index.php?option=com_contractor&view=contracts'); ?>';">
            <div class="card bg-primary text-white h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0"><?php echo Text::_('COM_CONTRACTOR_DASHBOARD_TOTAL_CONT'); ?></h5>
                        <span class="icon-folder-open fs-2 opacity-50" aria-hidden="true"></span>
                    </div>
                    <p class="card-text display-4 fw-bold mb-0">
                        <?php echo (int) $this->stats->contracts; ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4" style="cursor: pointer;" onclick="window.location.href='<?php echo Route::_('index.php?option=com_contractor&view=contracts'); ?>';">
            <div class="card bg-warning text-dark h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0"><?php echo Text::_('COM_CONTRACTOR_DASHBOARD_WARNING_CONT'); ?></h5>
                        <span class="icon-exclamation-triangle fs-2 opacity-50" aria-hidden="true"></span>
                    </div>
                    <p class="card-text display-4 fw-bold mb-0">
                        <?php echo (int) $this->stats->warningContracts; ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4" style="cursor: pointer;" onclick="window.location.href='<?php echo Route::_('index.php?option=com_contractor&view=contracts'); ?>';">
            <div class="card bg-danger text-white h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0"><?php echo Text::_('COM_CONTRACTOR_DASHBOARD_EXPIRED_CONT'); ?></h5>
                        <span class="icon-exclamation-circle fs-2 opacity-50" aria-hidden="true"></span>
                    </div>
                    <p class="card-text display-4 fw-bold mb-0">
                        <?php echo (int) $this->stats->expiredContracts; ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4" style="cursor: pointer;" onclick="window.location.href='<?php echo Route::_('index.php?option=com_contractor&view=clients'); ?>';">
            <div class="card bg-success text-white h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0"><?php echo Text::_('COM_CONTRACTOR_DASHBOARD_TOTAL_CLIENTS'); ?></h5>
                        <span class="icon-users fs-2 opacity-50" aria-hidden="true"></span>
                    </div>
                    <p class="card-text display-4 fw-bold mb-0">
                        <?php echo (int) $this->stats->clients; ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4" style="cursor: pointer;" onclick="window.location.href='<?php echo Route::_('index.php?option=com_contractor&view=tags'); ?>';">
            <div class="card bg-info text-white h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0"><?php echo Text::_('COM_CONTRACTOR_DASHBOARD_TOTAL_TAGS'); ?></h5>
                        <span class="icon-tag fs-2 opacity-50" aria-hidden="true"></span>
                    </div>
                    <p class="card-text display-4 fw-bold mb-0">
                        <?php echo (int) $this->stats->tags; ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 mb-4">
            <div id="versionTile" class="card bg-secondary text-white h-100 shadow-sm border-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0"><?php echo Text::_('COM_CONTRACTOR_DASHBOARD_SYSTEM'); ?></h5>
                            <span class="fas fa-gear fs-2 opacity-50" aria-hidden="true"></span>
                    </div>
                    
                    <div class="d-flex align-items-center gap-2 flex-wrap mt-auto">
                        <p class="card-text fs-6 mb-0">
                            <?php echo Text::_('COM_CONTRACTOR_DASHBOARD_VERSION')." ".$this->escape($version); ?>
                        </p>
                        <div id="versionStatus" class="mb-1"></div>
                        <a href="javascript:void(0);" onclick="checkComponentVersion();" class="text-white text-decoration-none" title="<?php echo Text::_('COM_CONTRACTOR_DASHBOARD_CLICK_TO_CHECK'); ?>">
                            <span id="versionIcon" class="icon-loop fs-3 opacity-50" aria-hidden="true"></span>
                        </a>
                    </div>
                    
                    <div id="versionDownload" class="mt-0 mb-1"></div>

                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <p class="fs-6 mb-0"><?php 
                            echo Text::_('COM_CONTRACTOR_DASHBOARD_LOGLINES').": ".$this->stats->loglines." "; 
                        if(strstr($this->stats->loglines, ">")):
                        ?></p>
                        <a href="index.php?option=com_contractor&view=logs" class="text-decoration-none text-dark"><div class="badge bg-warning px-1 py-1 mb-1"><span class="icon-warning mx-0"></span> <?php echo Text::_('COM_CONTRACTOR_DASHBOARD_LOGPLPRUNE'); ?></div></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

    <div class="row mb-4 mt-2">
        <div class="col-12">
            <div class="card-header text-white bg-primary"><?php echo Text::_('COM_CONTRACTOR_DASHBOARD_LATEST_LOGS'); ?></div>
            <div class="table-responsive bg-white shadow-sm p-0 rounded" style="max-height: 600px; overflow-y: auto;">
                <table class="table table-striped table-hover mb-0">
                    <thead class="sticky-top">
                        <tr>
                            <th width="200px"><?php echo Text::_('COM_CONTRACTOR_LOG_DATE'); ?></th>
                            <th width="1%"><?php echo Text::_('COM_CONTRACTOR_LOG_LEVEL'); ?></th>
                            <th><?php echo Text::_('COM_CONTRACTOR_LOG_CLIENT'); ?></th>
                            <th><?php echo Text::_('COM_CONTRACTOR_LOG_DESC'); ?></th>
                        </tr>
                    </thead>
                    <tbody class="border" style="max-height: 500px; overflow-y: auto;">
                        <?php if (!empty($this->logs)): ?>
                            <?php foreach ($this->logs as $log): ?>
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
                                    <td><?php echo ($log->timedate ?? 0); ?></td>
                                    <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $levelLabel; ?></span></td>
                                    <td>
                                        <?php 
                                        if($log->pname) {
                                            echo ("<a href='index.php?option=com_contractor&task=client.edit&id=$log->client_id'>$log->pname</a>");
                                        } else {
                                            echo ('<span class="text-muted">System</span>'); 
                                        } ?>
                                    </td>
                                    <td><?php echo $this->escape($log->description ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-muted"><?php echo Text::_('COM_CONTRACTOR_NO_LOGS'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <p class="mb-2"><?php echo Text::sprintf('COM_CONTRACTOR_DASHBOARD_COPYRIGHT', '2026'); ?></p>
            <p><a href="https://www.petitpoisson.be/"><img src="<?php echo Uri::root(true) . '/media/com_contractor/images/petitpoisson-200.png'; ?>" alt="<?php echo Text::_('COM_CONTRACTOR_DASHBOARD_LOGO_ALT'); ?>" /></a></p>
        </div>
    </div>
</div>

<style>
/* Helper animation for the spinning icon */
@keyframes joomla-spin {
    100% { transform: rotate(360deg); }
}
.spin-anim {
    animation: joomla-spin 2s linear infinite;
    display: inline-block;
}
</style>

<script>
    const currentVersion = '<?php echo $this->escape($version); ?>';
    const xmlUrl = 'https://f001.backblazeb2.com/file/extupdates/contractor_update.xml';

    function compareVersions(v1, v2) {
        const v1parts = v1.split('.').map(Number);
        const v2parts = v2.split('.').map(Number);
        for (let i = 0; i < Math.max(v1parts.length, v2parts.length); ++i) {
            const p1 = v1parts[i] || 0;
            const p2 = v2parts[i] || 0;
            if (p1 > p2) return 1;
            if (p1 < p2) return -1;
        }
        return 0;
    }

    function checkComponentVersion() {
        const statusEl   = document.getElementById('versionStatus');
        const downloadEl = document.getElementById('versionDownload');
        const iconEl     = document.getElementById('versionIcon');
        
        // Reset state to "Checking"
        statusEl.innerHTML = '<span class="badge bg-light text-dark bg-opacity-75 fs-7 px-1 py-1"><span class="icon-loop spin-anim me-1"></span><?php echo Text::_("COM_CONTRACTOR_CHECKING_VERSION"); ?></span>';
        downloadEl.innerHTML = '';
        iconEl.classList.add('spin-anim');
        
        // Add cache-busting parameter
        const fetchUrl = xmlUrl + '?t=' + new Date().getTime();

        fetch(fetchUrl)
            .then(response => {
                if (!response.ok) throw new Error("HTTP " + response.status);
                return response.text();
            })
            .then(str => (new window.DOMParser()).parseFromString(str, "text/xml"))
            .then(data => {
                const versionNode = data.querySelector("update > version");
                const downloadNode = data.querySelector("update > downloads > downloadurl"); 
                
                if (!versionNode) throw new Error("Invalid XML structure");
                
                const latestVersion = versionNode.textContent.trim();
                const downloadUrl = downloadNode ? downloadNode.textContent.trim() : '#';
                
                iconEl.classList.remove('spin-anim');
                
                if (compareVersions(latestVersion, currentVersion) > 0) {
                    // Update available: show badge next to version, and download link below
                    statusEl.innerHTML = '<span class="badge bg-danger"><?php echo Text::_("COM_CONTRACTOR_UPDATE_AVAILABLE"); ?> : ' + latestVersion + '</span>';
                    downloadEl.innerHTML = '<a href="' + downloadUrl + '" target="_blank" class="text-white fw-bold small text-decoration-none"><span class="icon-download me-1"></span><?php echo Text::_("COM_CONTRACTOR_DOWNLOAD_UPDATE"); ?></a>';
                } else {
                    // Up to date: green badge
                    statusEl.innerHTML = '<span class="badge bg-success bg-opacity-75 fs-7 px-1 py-1"><span class="icon-check mx-0"></span> <?php echo Text::_("COM_CONTRACTOR_UP_TO_DATE"); ?></span>';
                    downloadEl.innerHTML = '';
                }
            })
            .catch(err => {
                console.error("Version check failed:", err);
                iconEl.classList.remove('spin-anim');
                statusEl.innerHTML = '<span class="badge bg-warning text-dark" title="<?php echo Text::_("COM_CONTRACTOR_DOWNLOAD_CHERR2"); ?>"><span class="icon-warning bg-opacity-75 fs-7 px-1 py-1" ></span><?php echo Text::_("COM_CONTRACTOR_DOWNLOAD_CHERR"); ?></span>';
                downloadEl.innerHTML = '';
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Automatically check version once the DOM is fully loaded
        checkComponentVersion();
    });
</script>