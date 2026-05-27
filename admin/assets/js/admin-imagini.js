function deleteImage(filename) {
    if (!confirm('Ștergi imaginea?')) return;
    const fd = new FormData();
    fd.append('action', 'delete_image');
    fd.append('filename', filename);
    fetch('/admin/?tab=imagini', { method: 'POST', body: fd })
        .then(() => location.reload());
}
