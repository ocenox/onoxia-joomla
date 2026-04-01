<?php
/**
 * @copyright 2026 OCENOX LTD
 * @license   GPL-2.0-or-later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/** @var \Ocenox\Component\Onoxia\Administrator\View\Dashboard\HtmlView $this */

$statusClass = $this->apiConnected ? 'success' : ($this->tokenSet ? 'danger' : 'warning');
$statusIcon = $this->apiConnected ? 'check-circle' : ($this->tokenSet ? 'times-circle' : 'exclamation-triangle');
$statusText = $this->apiConnected
    ? Text::_('COM_ONOXIA_STATUS_CONNECTED')
    : ($this->tokenSet ? Text::_('COM_ONOXIA_STATUS_ERROR') : Text::_('COM_ONOXIA_STATUS_NOT_CONFIGURED'));
$csrfToken = Session::getFormToken();
?>

<div class="row">
    <!-- Connection Status -->
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <span class="icon-plug me-2"></span>
                    <?php echo Text::_('COM_ONOXIA_CONNECTION'); ?>
                </h3>
            </div>
            <div class="card-body">
                <div class="alert alert-<?php echo $statusClass; ?> d-flex align-items-center mb-4">
                    <span class="fas fa-<?php echo $statusIcon; ?> fa-2x me-3"></span>
                    <div>
                        <strong><?php echo $statusText; ?></strong>
                        <?php if ($this->apiError): ?>
                            <br><small><?php echo htmlspecialchars($this->apiError); ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <th scope="row"><?php echo Text::_('COM_ONOXIA_PLUGIN_STATUS'); ?></th>
                            <td>
                                <?php if ($this->pluginEnabled): ?>
                                    <span class="badge bg-success"><?php echo Text::_('COM_ONOXIA_ENABLED'); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><?php echo Text::_('COM_ONOXIA_DISABLED'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo Text::_('COM_ONOXIA_API_TOKEN'); ?></th>
                            <td>
                                <?php if ($this->tokenSet): ?>
                                    <span class="badge bg-success"><?php echo Text::_('COM_ONOXIA_TOKEN_SET'); ?></span>
                                    <code class="ms-2"><?php echo htmlspecialchars($this->tokenPreview); ?></code>
                                <?php else: ?>
                                    <span class="badge bg-danger"><?php echo Text::_('COM_ONOXIA_TOKEN_MISSING'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if ($this->siteInfo): ?>
                        <tr>
                            <th scope="row"><?php echo Text::_('COM_ONOXIA_SITE_NAME'); ?></th>
                            <td><?php echo htmlspecialchars($this->siteInfo['name'] ?? '—'); ?></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo Text::_('COM_ONOXIA_SITE_UUID'); ?></th>
                            <td><code><?php echo htmlspecialchars($this->siteInfo['id'] ?? '—'); ?></code></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo Text::_('COM_ONOXIA_RAG_COUNT'); ?></th>
                            <td><?php echo (int) ($this->siteInfo['rag_count'] ?? 0); ?></td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Configuration + Actions -->
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <span class="icon-cog me-2"></span>
                    <?php echo Text::_('COM_ONOXIA_CONFIGURATION'); ?>
                </h3>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <tbody>
                        <tr>
                            <th scope="row"><?php echo Text::_('COM_ONOXIA_SYNC_ARTICLES'); ?></th>
                            <td>
                                <span class="badge bg-<?php echo $this->syncArticles ? 'success' : 'secondary'; ?>">
                                    <?php echo $this->syncArticles ? Text::_('JYES') : Text::_('JNO'); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo Text::_('COM_ONOXIA_SYNC_LLMS'); ?></th>
                            <td>
                                <span class="badge bg-<?php echo $this->syncLlms ? 'success' : 'secondary'; ?>">
                                    <?php echo $this->syncLlms ? Text::_('JYES') : Text::_('JNO'); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo Text::_('COM_ONOXIA_SYNC_SITEMAP'); ?></th>
                            <td>
                                <span class="badge bg-<?php echo $this->syncSitemap ? 'success' : 'secondary'; ?>">
                                    <?php echo $this->syncSitemap ? Text::_('JYES') : Text::_('JNO'); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php echo Text::_('COM_ONOXIA_PAGE_RESTRICTIONS'); ?></th>
                            <td>
                                <?php
                                $menuCount = count(array_filter($this->enabledMenuitems));
                                echo $menuCount > 0
                                    ? Text::sprintf('COM_ONOXIA_N_MENUITEMS', $menuCount)
                                    : Text::_('COM_ONOXIA_ALL_PAGES');
                                ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <span class="icon-lightning me-2"></span>
                    <?php echo Text::_('COM_ONOXIA_ACTIONS'); ?>
                </h3>
            </div>
            <div class="card-body d-grid gap-2">
                <?php if ($this->pluginId): ?>
                <a href="<?php echo Route::_('index.php?option=com_plugins&task=plugin.edit&extension_id=' . $this->pluginId); ?>"
                   class="btn btn-primary">
                    <span class="icon-cog me-2"></span>
                    <?php echo Text::_('COM_ONOXIA_EDIT_SETTINGS'); ?>
                </a>
                <?php endif; ?>
                <a href="https://onoxia.nz/app" target="_blank" rel="noopener" class="btn btn-outline-primary">
                    <span class="icon-out-2 me-2"></span>
                    <?php echo Text::_('COM_ONOXIA_OPEN_DASHBOARD'); ?>
                </a>
                <a href="https://onoxia.nz/docs/api" target="_blank" rel="noopener" class="btn btn-outline-secondary">
                    <span class="icon-file-alt me-2"></span>
                    <?php echo Text::_('COM_ONOXIA_API_DOCS'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Sync Panel -->
<div class="row">
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">
                    <span class="icon-refresh me-2"></span>
                    <?php echo Text::_('COM_ONOXIA_SYNC'); ?>
                </h3>
                <button type="button" class="btn btn-success btn-sm" id="btn-sync"
                        <?php echo !$this->apiConnected ? 'disabled' : ''; ?>>
                    <span class="icon-play me-1"></span>
                    <?php echo Text::_('COM_ONOXIA_SYNC_START'); ?>
                </button>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-3">
                    <span class="fas fa-info-circle me-1"></span>
                    <?php echo Text::_('COM_ONOXIA_SYNC_KNOWLEDGE_INFO'); ?>
                </div>

                <!-- Progress Bar -->
                <div id="sync-progress-wrap" style="display:none;">
                    <div class="progress mb-3" style="height: 24px;">
                        <div id="sync-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated"
                             role="progressbar" style="width: 0%">0%</div>
                    </div>
                </div>

                <!-- Log -->
                <div id="sync-log"></div>

                <?php if (!$this->apiConnected): ?>
                <p class="text-muted mb-0"><?php echo Text::_('COM_ONOXIA_SYNC_CONNECT_FIRST'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Cron Info -->
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title mb-0">
                    <span class="icon-clock me-2"></span>
                    <?php echo Text::_('COM_ONOXIA_CRON'); ?>
                </h3>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-3">
                    <span class="fas fa-info-circle me-1"></span>
                    <?php echo Text::_('COM_ONOXIA_CRON_NOTE'); ?>
                </div>
                <p><?php echo Text::_('COM_ONOXIA_CRON_DESC'); ?></p>
                <div class="input-group mb-3">
                    <input type="text" class="form-control form-control-sm font-monospace" readonly
                           id="cron-command"
                           value="0 3 * * * curl -s &quot;<?php echo htmlspecialchars($this->cronUrl); ?>&quot;">
                    <button class="btn btn-outline-secondary btn-sm" type="button" id="btn-copy-cron"
                            title="<?php echo Text::_('COM_ONOXIA_CRON_COPY'); ?>">
                        <span class="icon-copy"></span>
                    </button>
                </div>
                <small class="text-muted"><?php echo Text::_('COM_ONOXIA_CRON_HINT'); ?></small>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var btnSync = document.getElementById('btn-sync');
    var progressWrap = document.getElementById('sync-progress-wrap');
    var progressBar = document.getElementById('sync-progress-bar');
    var syncLog = document.getElementById('sync-log');
    var token = '<?php echo $csrfToken; ?>';

    var steps = [
        {task: 'sync.articles', label: '<?php echo Text::_('COM_ONOXIA_SYNC_ARTICLES', true); ?>'},
        {task: 'sync.llms', label: '<?php echo Text::_('COM_ONOXIA_SYNC_LLMS', true); ?>'},
        {task: 'sync.sitemap', label: '<?php echo Text::_('COM_ONOXIA_SYNC_SITEMAP', true); ?>'}
    ];

    if (btnSync) {
        btnSync.addEventListener('click', function() {
            btnSync.disabled = true;
            syncLog.innerHTML = '';
            progressWrap.style.display = 'block';
            setProgress(0);
            runSteps(0);
        });
    }

    function runSteps(i) {
        if (i >= steps.length) {
            setProgress(100);
            progressBar.classList.remove('progress-bar-animated');
            progressBar.classList.add('bg-success');
            btnSync.disabled = false;
            return;
        }

        var step = steps[i];
        var pct = Math.round(((i) / steps.length) * 100);
        setProgress(pct);

        fetch('index.php?option=com_onoxia&task=' + step.task + '&' + token + '=1&format=json', {
            method: 'POST',
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var icon = data.success ? 'check' : 'times';
            var cls = data.success ? 'success' : 'danger';
            if (data.message === 'skipped') {
                icon = 'forward'; cls = 'secondary';
            }
            addLog(icon, cls, step.label + ': ' + data.message);
            runSteps(i + 1);
        })
        .catch(function(err) {
            addLog('times', 'danger', step.label + ': ' + err.message);
            runSteps(i + 1);
        });
    }

    function setProgress(pct) {
        progressBar.style.width = pct + '%';
        progressBar.textContent = pct + '%';
    }

    function addLog(icon, cls, text) {
        var div = document.createElement('div');
        div.className = 'mb-1';
        div.innerHTML = '<span class="fas fa-' + icon + ' text-' + cls + ' me-2"></span>' + text;
        syncLog.appendChild(div);
    }

    // Copy cron command
    var btnCopy = document.getElementById('btn-copy-cron');
    if (btnCopy) {
        btnCopy.addEventListener('click', function() {
            var input = document.getElementById('cron-command');
            navigator.clipboard.writeText(input.value).then(function() {
                btnCopy.innerHTML = '<span class="icon-checkmark"></span>';
                setTimeout(function() { btnCopy.innerHTML = '<span class="icon-copy"></span>'; }, 2000);
            });
        });
    }
});
</script>
