document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('daha-fazla-tarif');
    if (!btn) return;
    btn.addEventListener('click', function() {
        let sayfa = parseInt(btn.getAttribute('data-sayfa')) + 1;
        btn.disabled = true;
        btn.textContent = 'Yükleniyor...';
        fetch(ajaxurl + '?action=tarifleri_getir&sayfa=' + sayfa)
            .then(response => response.text())
            .then(html => {
                if (html.trim() !== '') {
                    document.getElementById('tarifler-listesi').insertAdjacentHTML('beforeend', html);
                    btn.setAttribute('data-sayfa', sayfa);
                    btn.disabled = false;
                    btn.textContent = 'Daha fazlası için tıklayınız';
                } else {
                    btn.style.display = 'none';
                }
            });
    });
});