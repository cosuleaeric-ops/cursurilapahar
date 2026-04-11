<?php
// Include in view.php — has access to $csrf and $id from parent context
?>
<form method="post" enctype="multipart/form-data" class="raport-form">
  <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
  <input type="hidden" name="action" value="upload_raport">
  <input type="hidden" name="total_bilete" value="0">
  <input type="hidden" name="total_incasari" value="0">
  <input type="hidden" name="types_json" value="[]">
  <div class="raport-drop">
    <input type="file" name="raport_file" accept=".xlsx,.xls">
    <p>📊 Trage sau apasa pentru a incarca raportul XLSX</p>
  </div>
  <div class="raport-preview"></div>
  <div class="raport-submit">
    <button type="submit" class="btn btn-green" style="width:100%;justify-content:center;padding:9px">Salveaza raportul</button>
  </div>
</form>
