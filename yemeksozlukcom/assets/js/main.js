document.addEventListener('DOMContentLoaded', function(){
    // Favori işlemi
    document.querySelectorAll('.favori-btn').forEach(function(btn){
        btn.addEventListener('click', function(){
            var tarif_id = btn.getAttribute('data-id');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '/wp-admin/admin-ajax.php');
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status == 200) {
                    btn.textContent = (btn.textContent === 'Favorilere Ekle') ? 'Favoriden Çıkar' : 'Favorilere Ekle';
                }
            };
            xhr.send('action=favori_toggle&tarif_id=' + tarif_id);
        });
    });

    // Puan işlemi
    document.querySelectorAll('.puan-star').forEach(function(star){
        star.addEventListener('click', function(){
            var puan = star.getAttribute('data-puan');
            var tarif_id = star.getAttribute('data-id');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '/wp-admin/admin-ajax.php');
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status == 200) {
                    star.parentElement.querySelector('.puan-ortalama').textContent = '(' + xhr.responseText + ')';
                }
            };
            xhr.send('action=puan_ver&tarif_id=' + tarif_id + '&puan=' + puan);
        });
    });
});