document.addEventListener('DOMContentLoaded', function () {
    function formatRupiahDisplay(value) {
        if (value === null || value === undefined) return '';
        let numberString = value.toString().replace(/[^0-9]/g, '');
        if (!numberString) return '';
        return numberString.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    const priceInputs = document.querySelectorAll('.price-input');
    priceInputs.forEach(input => {
        if (input.value) {
            input.value = formatRupiahDisplay(input.value);
        }

        input.addEventListener('input', function () {
            const cursorPosition = this.selectionStart;
            const originalLength = this.value.length;
            this.value = formatRupiahDisplay(this.value);
            const newLength = this.value.length;
            const newCursor = Math.max(0, cursorPosition + (newLength - originalLength));
            this.setSelectionRange(newCursor, newCursor);
        });
    });
});
