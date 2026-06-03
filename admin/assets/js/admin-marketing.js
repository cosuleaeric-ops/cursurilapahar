(function () {
    document.querySelectorAll('.mkt-add-form').forEach(function (form) {
        const textInput = form.querySelector('input[name="text"]');
        const linkInput = form.querySelector('input[name="link"]');
        if (!textInput) return;

        function trySubmit(e) {
            const text = (textInput.value || '').trim();
            const link = linkInput ? (linkInput.value || '').trim() : '';
            if (!text && !link) {
                if (e) e.preventDefault();
                textInput.focus();
                return;
            }
            if (e) e.preventDefault();
            form.submit();
        }

        form.addEventListener('submit', trySubmit);

        textInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                trySubmit(e);
            }
        });

        if (linkInput) {
            linkInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    trySubmit(e);
                }
            });
        }
    });
})();
