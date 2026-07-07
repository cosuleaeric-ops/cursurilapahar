<script src="/admin/assets/js/admin-common.js?v=7"></script>
<?php if ($tab === 'cursuri'): ?>
<script src="/admin/assets/js/admin-course-form.js?v=2"></script>
<?php elseif ($tab === 'imagini'): ?>
<script src="/admin/assets/js/admin-imagini.js?v=5"></script>
<?php elseif ($tab === 'mesaje'): ?>
<script>window.CLP_IS_OWNER = <?= is_owner() ? 'true' : 'false' ?>;</script>
<script src="/admin/assets/js/admin-mesaje.js?v=3"></script>
<?php elseif ($tab === 'speakeri'): ?>
<script src="/admin/assets/js/admin-speakeri.js?v=4"></script>
<?php elseif ($tab === 'aspect'): ?>
<script src="/assets/js/coloris.min.js"></script>
<script src="/admin/assets/js/admin-aspect.js?v=1"></script>
<?php endif; ?>
