(function () {
    document.querySelectorAll('.mkt-add-form').forEach(function (form) {
        const textInput = form.querySelector('input[name="text"]');
        const linkInput = form.querySelector('input[name="link"]');
        if (!textInput) return;

        form.addEventListener('submit', function (e) {
            const text = (textInput.value || '').trim();
            const link = linkInput ? (linkInput.value || '').trim() : '';
            if (!text && !link) {
                e.preventDefault();
                textInput.focus();
            }
        });

        if (linkInput) {
            linkInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    form.requestSubmit();
                }
            });
        }
    });
})();
