<div class="mt-1">
    <button onclick="window.parent.postMessage('open_url:<?= $module_url ?>|new_tab', '*'); setTimeout(function() { window.parent.postMessage('reset', '*'); }, 1000);" class="success">View Your Module</button>
</div>
<div class="mt-1">
    <button onclick="window.parent.postMessage('reset', '*')">Okay</button>
</div>