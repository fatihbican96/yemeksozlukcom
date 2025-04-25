<?php
add_theme_support('title-tag');
add_theme_support('post-thumbnails');
add_theme_support('automatic-feed-links');

register_nav_menus(array(
  'header-menu' => __('Header Menü', 'yemeksozlukcom')
));

// CSS yükle
function yemeksozluk_enqueue() {
    wp_enqueue_style('main', get_stylesheet_uri());
}
add_action('wp_enqueue_scripts', 'yemeksozluk_enqueue');

// Tarifi CPT ve tax (örnek)
add_action('init', function() {
    register_post_type('tarif', array(
        'label' => 'Tarifler',
        'public' => true,
        'has_archive' => true,
        'rewrite' => array('slug' => 'tarif'),
        'supports' => array('title', 'editor', 'thumbnail', 'author', 'custom-fields'),
        'show_in_rest' => true,
    ));
    register_taxonomy('yemek_turu', ['tarif'], array(
        'label' => 'Yemek Türü',
        'public' => true,
        'hierarchical' => true,
        'rewrite' => array('slug' => 'yemek-turu'),
        'show_in_rest' => true,
    ));
});

// FAVORİ SİSTEMİ
function ys_is_favorite($post_id=0, $user_id=0) {
    if (!$user_id) $user_id = get_current_user_id();
    if (!$post_id) $post_id = get_the_ID();
    $favs = get_user_meta($user_id, 'ys_favs', true);
    if (!is_array($favs)) $favs = [];
    return in_array($post_id, $favs);
}
function ys_fav_button($post_id=0) {
    if (!$post_id) $post_id = get_the_ID();
    if (!is_user_logged_in()) {
        return '<span class="fav-btn fav-login" title="Favorilere eklemek için giriş yapın"><i class="fa fa-heart"></i></span>';
    }
    $is_fav = ys_is_favorite($post_id);
    $class = $is_fav ? 'fav-btn fav-active' : 'fav-btn';
    return '<span class="'.$class.'" data-post="'.$post_id.'" title="Favorilere ekle/kaldır"><i class="fa fa-heart"></i></span>';
}
add_action('wp_ajax_ys_fav_toggle', function() {
    if (!is_user_logged_in()) wp_send_json_error('Giriş yapmalısınız!');
    $post_id = intval($_POST['post_id']);
    $user_id = get_current_user_id();
    $favs = get_user_meta($user_id, 'ys_favs', true);
    if (!is_array($favs)) $favs = [];
    if (in_array($post_id, $favs)) {
        $favs = array_diff($favs, [$post_id]);
        update_user_meta($user_id, 'ys_favs', $favs);
        wp_send_json_success(['fav'=>0]);
    } else {
        $favs[] = $post_id;
        update_user_meta($user_id, 'ys_favs', $favs);
        wp_send_json_success(['fav'=>1]);
    }
    wp_die();
});
add_action('wp_footer', function() {
    if (is_user_logged_in()) : ?>
    <script>
    document.addEventListener('click',function(e){
        var target = e.target.closest('.fav-btn');
        if(target && target.dataset.post) {
            var btn = target, pid = btn.dataset.post;
            var xhr = new XMLHttpRequest();
            var fd = new FormData();
            fd.append('action','ys_fav_toggle');
            fd.append('post_id',pid);
            xhr.open('POST','<?php echo admin_url('admin-ajax.php'); ?>',true);
            xhr.onload = function(){
                try{ var res=JSON.parse(xhr.responseText);
                if(res.success) btn.classList.toggle('fav-active',res.data.fav==1);
                }catch(e){}
            };
            xhr.send(fd);
        }
    });
    </script>
    <?php endif;
});

// FAVORİLERİM KISA KODU
add_shortcode('favorilerim', function() {
    if (!is_user_logged_in()) return '<p>Giriş yapmalısınız.</p>';
    $user_id = get_current_user_id();
    $favs = get_user_meta($user_id, 'ys_favs', true);
    if (!is_array($favs) || !$favs) return '<p>Favoriniz yok.</p>';
    $q = new WP_Query([
        'post_type' => ['tarif','post'],
        'post__in' => $favs,
        'orderby' => 'post__in',
        'posts_per_page' => 20
    ]);
    ob_start();
    echo '<div class="block-recipes-list">';
    while($q->have_posts()) { $q->the_post();
        get_template_part('template-parts/recipe-card');
    }
    wp_reset_postdata();
    echo '</div>';
    return ob_get_clean();
});

// GELİŞMİŞ ARAMA KISA KODU
add_shortcode('ys_arama', function() {
    $q = isset($_GET['sq']) ? sanitize_text_field($_GET['sq']) : '';
    $kategori = isset($_GET['kategori']) ? intval($_GET['kategori']) : 0;
    $malzeme = isset($_GET['malzeme']) ? sanitize_text_field($_GET['malzeme']) : '';
    $sure = isset($_GET['sure']) ? intval($_GET['sure']) : 0;
    ob_start(); ?>
    <form class="ys-advanced-search" method="get" style="margin-bottom:22px;">
        <input type="text" name="sq" placeholder="Tarif veya yazı ara..." value="<?php echo esc_attr($q); ?>">
        <select name="kategori">
            <option value="0">Kategori</option>
            <?php
            $terms = get_terms(['taxonomy'=>'yemek_turu','hide_empty'=>false]);
            if(!is_wp_error($terms)) foreach($terms as $term){
                echo '<option value="'.$term->term_id.'" '.selected($kategori,$term->term_id,false).'>'.$term->name.'</option>';
            }
            ?>
        </select>
        <input type="text" name="malzeme" placeholder="Malzeme (örn: tavuk)" value="<?php echo esc_attr($malzeme); ?>">
        <select name="sure">
            <option value="0">Süre</option>
            <option value="15" <?php selected($sure,15); ?>>15 dk altı</option>
            <option value="30" <?php selected($sure,30); ?>>30 dk altı</option>
            <option value="60" <?php selected($sure,60); ?>>1 saat altı</option>
            <option value="61" <?php selected($sure,61); ?>>1 saat üstü</option>
        </select>
        <button type="submit">Ara</button>
    </form>
    <?php
    $args = [
        'post_type'=>['tarif','post'],
        'posts_per_page'=>12,
        's'=>$q,
    ];
    if($kategori){
        $args['tax_query'] = [[
            'taxonomy'=>'yemek_turu',
            'terms'=>$kategori
        ]];
    }
    if($malzeme){
        $args['meta_query'][] = [
            'key'=>'malzemeler',
            'value'=>$malzeme,
            'compare'=>'LIKE'
        ];
    }
    if($sure) {
        if($sure==15) {
            $args['meta_query'][] = [
                'key'=>'hazir_sure',
                'value'=>15,
                'type'=>'NUMERIC',
                'compare'=>'<='
            ];
        } elseif($sure==30) {
            $args['meta_query'][] = [
                'key'=>'hazir_sure',
                'value'=>30,
                'type'=>'NUMERIC',
                'compare'=>'<='
            ];
        } elseif($sure==60) {
            $args['meta_query'][] = [
                'key'=>'hazir_sure',
                'value'=>60,
                'type'=>'NUMERIC',
                'compare'=>'<='
            ];
        } elseif($sure==61) {
            $args['meta_query'][] = [
                'key'=>'hazir_sure',
                'value'=>60,
                'type'=>'NUMERIC',
                'compare'=>'>'
            ];
        }
    }
    $qres = new WP_Query($args);
    if($q || $kategori || $malzeme || $sure) {
        echo '<div class="block-recipes-list">';
        if($qres->have_posts()) {
            while($qres->have_posts()) { $qres->the_post();
                get_template_part('template-parts/recipe-card');
            }
        } else {
            echo '<p>Sonuç bulunamadı.</p>';
        }
        wp_reset_postdata();
        echo '</div>';
    }
    return ob_get_clean();
});

// TARİF EKLEME KISA KODU (Schema.org uyumlu)
add_shortcode('tarif_ekle', function() {
    if (!is_user_logged_in()) return '<p>Lütfen tarif eklemek için giriş yapın.</p>';
    $msg = '';
    if ($_POST && isset($_POST['ys_tarif_ekle'])) {
        $tarif_adi = sanitize_text_field($_POST['tarif_adi']);
        $giris = sanitize_textarea_field($_POST['giris']);
        $kapak_aciklama = sanitize_text_field($_POST['kapak_aciklama']);
        $kac_kisilik = intval($_POST['kac_kisilik']);
        $hazir_sure = intval($_POST['hazir_sure']);
        $pisirme_sure = intval($_POST['pisirme_sure']);
        $malzemeler = array_filter(array_map('sanitize_text_field', $_POST['malzemeler'] ?? []));
        $adimlar = array_filter(array_map('sanitize_text_field', $_POST['adimlar'] ?? []));
        $puf = sanitize_textarea_field($_POST['puf']);
        $video = sanitize_text_field($_POST['video']);

        if (!$tarif_adi || !$giris || !$kapak_aciklama || !$kac_kisilik || !$hazir_sure || !$pisirme_sure || empty($malzemeler) || empty($adimlar)) {
            $msg = '<div class="form-error">Lütfen tüm zorunlu alanları doldurun.</div>';
        } else {
            $post_id = wp_insert_post([
                'post_type' => 'tarif',
                'post_title' => $tarif_adi,
                'post_content' => implode("\n", $adimlar),
                'post_status' => 'pending',
                'post_author' => get_current_user_id(),
                'meta_input' => [
                    'giris' => $giris,
                    'kapak_aciklama' => $kapak_aciklama,
                    'kac_kisilik' => $kac_kisilik,
                    'hazir_sure' => $hazir_sure,
                    'pisirme_sure' => $pisirme_sure,
                    'malzemeler' => $malzemeler,
                    'puf' => $puf,
                    'video' => $video,
                ]
            ]);
            if ($post_id && !is_wp_error($post_id)) {
                $msg = '<div class="form-success">Tarifiniz gönderildi! Editör onayından sonra yayınlanacaktır.</div>';
                $_POST = [];
            } else {
                $msg = '<div class="form-error">Bir hata oluştu. Lütfen tekrar deneyin.</div>';
            }
        }
    }
    ob_start();
    ?>
    <form method="post" class="ys-tarif-ekle-form" enctype="multipart/form-data" itemprop="recipeInstructions" itemscope itemtype="https://schema.org/Recipe">
        <?php echo $msg; ?>
        <label>Tarif Adı: <span style="color:#c72828">*</span><br>
            <input type="text" name="tarif_adi" maxlength="120" required value="<?php echo esc_attr($_POST['tarif_adi'] ?? ''); ?>">
        </label><br><br>
        <label>Giriş Metni (maks. 1000 karakter): <span style="color:#c72828">*</span><br>
            <textarea name="giris" maxlength="1000" required style="width:100%;min-height:60px;"><?php echo esc_textarea($_POST['giris'] ?? ''); ?></textarea>
        </label><br><br>
        <label>Kapak Fotoğrafı Açıklaması: <span style="color:#c72828">*</span><br>
            <input type="text" name="kapak_aciklama" maxlength="150" required value="<?php echo esc_attr($_POST['kapak_aciklama'] ?? ''); ?>">
        </label><br><br>
        <label>Kaç Kişilik?: <span style="color:#c72828">*</span><br>
            <input type="number" name="kac_kisilik" min="1" max="50" pattern="[0-9]*" inputmode="numeric" required value="<?php echo esc_attr($_POST['kac_kisilik'] ?? ''); ?>">
        </label><br><br>
        <label>Hazırlama Süresi (dakika): <span style="color:#c72828">*</span><br>
            <input type="number" name="hazir_sure" min="1" max="1000" pattern="[0-9]*" inputmode="numeric" required value="<?php echo esc_attr($_POST['hazir_sure'] ?? ''); ?>">
        </label><br><br>
        <label>Pişirme Süresi (dakika): <span style="color:#c72828">*</span><br>
            <input type="number" name="pisirme_sure" min="1" max="1000" pattern="[0-9]*" inputmode="numeric" required value="<?php echo esc_attr($_POST['pisirme_sure'] ?? ''); ?>">
        </label><br><br>
        <label>Malzemeler (ölçülerle birlikte, madde madde): <span style="color:#c72828">*</span><br>
            <div id="malzeme-listesi">
                <?php
                $malzemeler = $_POST['malzemeler'] ?? [''];
                foreach ($malzemeler as $i => $malzeme) {
                    echo '<input name="malzemeler[]" type="text" maxlength="100" required placeholder="Malzeme – miktar" value="'.esc_attr($malzeme).'"><br>';
                }
                ?>
            </div>
            <button type="button" onclick="ekleMalzeme()" style="margin-top:5px;">+ Malzeme Ekle</button>
        </label><br>
        <label>Nasıl Yapılır? (adım adım): <span style="color:#c72828">*</span><br>
            <div id="adim-listesi">
                <?php
                $adimlar = $_POST['adimlar'] ?? [''];
                foreach ($adimlar as $i => $adim) {
                    echo '<input name="adimlar[]" type="text" maxlength="250" required placeholder="Adım – fiil ile başla" value="'.esc_attr($adim).'"><br>';
                }
                ?>
            </div>
            <button type="button" onclick="ekleAdim()" style="margin-top:5px;">+ Adım Ekle</button>
        </label><br>
        <label>Püf Noktaları / Pişirme & Servis Önerileri:<br>
            <textarea name="puf" maxlength="500" style="width:100%;min-height:50px;"><?php echo esc_textarea($_POST['puf'] ?? ''); ?></textarea>
        </label><br><br>
        <label>Video Linki (varsa):<br>
            <input type="url" name="video" placeholder="YouTube/Instagram URL" value="<?php echo esc_attr($_POST['video'] ?? ''); ?>">
        </label><br><br>
        <button type="submit" name="ys_tarif_ekle" style="font-size:1.1em;padding:10px 26px;">Tarifi Gönder</button>
    </form>
    <script>
    function ekleMalzeme() {
        var d = document.getElementById('malzeme-listesi');
        var inp = document.createElement('input');
        inp.type='text'; inp.name='malzemeler[]'; inp.maxLength=100; inp.required=true; inp.placeholder="Malzeme – miktar";
        d.appendChild(inp); d.appendChild(document.createElement('br'));
    }
    function ekleAdim() {
        var d = document.getElementById('adim-listesi');
        var inp = document.createElement('input');
        inp.type='text'; inp.name='adimlar[]'; inp.maxLength=250; inp.required=true; inp.placeholder="Adım – fiil ile başla";
        d.appendChild(inp); d.appendChild(document.createElement('br'));
    }
    </script>
    <style>
    .ys-tarif-ekle-form input[type=text], .ys-tarif-ekle-form input[type=number],
    .ys-tarif-ekle-form input[type=url], .ys-tarif-ekle-form textarea {
        width: 100%; max-width: 480px; padding: 7px; margin-bottom: 8px; border: 1px solid #bbb; border-radius: 6px;
    }
    .ys-tarif-ekle-form label { font-weight: 500; display: block; margin-bottom: 12px;}
    .form-error {color:#b31c1c;background:#ffeaea;padding:8px 13px;border-radius:8px;margin: 6px 0 10px 0;}
    .form-success {color:#15811a;background:#e9ffea;padding:8px 13px;border-radius:8px;margin: 6px 0 10px 0;}
    </style>
    <?php
    return ob_get_clean();
});

// KENDİ TARİFLERİM KISA KODU
add_shortcode('kendi_tariflerim', function() {
    if (!is_user_logged_in()) return '<p>Giriş yapmalısınız.</p>';
    $q = new WP_Query([
        'post_type'=>'tarif',
        'posts_per_page'=>20,
        'author'=>get_current_user_id()
    ]);
    ob_start();
    echo '<div class="block-recipes-list">';
    while($q->have_posts()) { $q->the_post();
        get_template_part('template-parts/recipe-card');
    }
    wp_reset_postdata();
    echo '</div>';
    return ob_get_clean();
});

// TARİF PUANLAMA SİSTEMİ
function ys_get_recipe_rating($post_id) {
    $ratings = get_post_meta($post_id, 'ys_recipe_ratings', true);
    if (!is_array($ratings)) $ratings = [];
    $count = count($ratings);
    $avg = $count ? round(array_sum($ratings)/$count,2) : 0;
    return [$avg, $count];
}
function ys_user_rated_recipe($post_id, $user_id=0) {
    if(!$user_id) $user_id = get_current_user_id();
    $user_ratings = get_post_meta($post_id, 'ys_recipe_ratings_users', true);
    if (!is_array($user_ratings)) $user_ratings = [];
    return isset($user_ratings[$user_id]) ? intval($user_ratings[$user_id]) : 0;
}
add_action('wp_ajax_ys_rate_recipe', function() {
    if (!is_user_logged_in()) wp_send_json_error('Lütfen giriş yapın.');
    $post_id = intval($_POST['post_id']);
    $rate = intval($_POST['rate']);
    if ($rate < 1 || $rate > 5) wp_send_json_error('Geçersiz puan.');
    $user_id = get_current_user_id();

    $user_ratings = get_post_meta($post_id, 'ys_recipe_ratings_users', true);
    if (!is_array($user_ratings)) $user_ratings = [];
    if(isset($user_ratings[$user_id])) {
        wp_send_json_error('Bu tarife zaten oy verdiniz.');
    }

    $ratings = get_post_meta($post_id, 'ys_recipe_ratings', true);
    if (!is_array($ratings)) $ratings = [];
    $ratings[] = $rate;
    update_post_meta($post_id, 'ys_recipe_ratings', $ratings);

    $user_ratings[$user_id] = $rate;
    update_post_meta($post_id, 'ys_recipe_ratings_users', $user_ratings);

    $count = count($ratings);
    $avg = round(array_sum($ratings)/$count,2);
    wp_send_json_success(['avg'=>$avg, 'count'=>$count, 'voted'=>$rate]);
});
add_action('wp_footer', function(){
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function(){
        document.querySelectorAll('.ys-recipe-rating .ys-star').forEach(function(star){
            star.addEventListener('mouseenter', function(){
                var val = parseInt(this.dataset.rate);
                var stars = this.parentNode.querySelectorAll('.ys-star');
                stars.forEach(function(s,k){s.style.color=(k<val)?'#ffc107':'#ccc';});
            });
            star.addEventListener('mouseleave', function(){
                var stars = this.parentNode.querySelectorAll('.ys-star');
                stars.forEach(function(s){
                    s.style.color = s.classList.contains('rated') ? '#ffc107' : '#ccc';
                });
            });
            star.addEventListener('click', function(){
                var val = parseInt(this.dataset.rate);
                var box = this.closest('.ys-recipe-rating');
                if(box.classList.contains('voted')) return;
                var post_id = box.dataset.post;
                var xhr = new XMLHttpRequest();
                var fd = new FormData();
                fd.append('action','ys_rate_recipe');
                fd.append('rate',val);
                fd.append('post_id',post_id);
                xhr.open('POST','<?php echo admin_url('admin-ajax.php'); ?>');
                xhr.onload = function(){
                    try {
                        var res = JSON.parse(xhr.responseText);
                        if(res.success) {
                            box.classList.add('voted');
                            var info = box.querySelector('.ys-rating-info');
                            info.innerHTML = 'Ortalama: <b>'+res.data.avg+'</b> / 5, Oy: <b>'+res.data.count+'</b> <span style="color:green;">(Oy verdiniz)</span>';
                            var stars = box.querySelectorAll('.ys-star');
                            stars.forEach(function(s,k){s.classList.toggle('rated',k<val); s.style.color=(k<val)?'#ffc107':'#ccc';});
                        } else {
                            alert(res.data || 'Tekrar oy veremezsiniz.');
                        }
                    } catch(e){}
                };
                xhr.send(fd);
            });
        });
        document.querySelectorAll('.ys-recipe-rating .ys-stars').forEach(function(box){
            var rated = box.querySelectorAll('.ys-star.rated').length;
            box.querySelectorAll('.ys-star').forEach(function(s,k){
                s.style.color = (k<rated)?'#ffc107':'#ccc';
            });
        });
    });
    </script>
    <style>
    .ys-stars .ys-star {
        transition: color 0.1s;
        user-select:none;
        text-shadow:0 1px 1px #fff;
    }
    .ys-stars .ys-star.rated { color:#ffc107 !important; }
    .ys-recipe-rating.voted .ys-star { pointer-events:none; opacity:0.65; }
    </style>
    <?php
});

// ... önceki kodlar burada ...

// Soru-Cevap Sistemi
add_shortcode('ys_soru_cevap', function() {
    if (!is_singular('tarif')) return '';
    global $post;
    $post_id = $post->ID;
    $user_id = get_current_user_id();
    $msg = '';

    // Soru gönderimi
    if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['ys_soru_gonder']) && is_user_logged_in()) {
        $soru = trim(strip_tags($_POST['ys_soru']));
        if ($soru && mb_strlen($soru)<=300) {
            $sorular = get_post_meta($post_id, 'ys_sorular', true);
            if (!is_array($sorular)) $sorular = [];
            $sorular[] = [
                'uid' => $user_id,
                'user' => get_userdata($user_id)->display_name,
                'soru' => $soru,
                'cevaplar' => [],
                'ts' => time()
            ];
            update_post_meta($post_id, 'ys_sorular', $sorular);
            $msg = '<div class="form-success">Sorunuz iletildi!</div>';
        } else {
            $msg = '<div class="form-error">Soru boş olamaz ve 300 karakteri geçemez.</div>';
        }
    }

    // Cevap gönderimi
    if ($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['ys_cevap_gonder']) && is_user_logged_in()) {
        $sid = intval($_POST['soru_id']);
        $cevap = trim(strip_tags($_POST['ys_cevap']));
        $sorular = get_post_meta($post_id, 'ys_sorular', true);
        if (isset($sorular[$sid]) && $cevap && mb_strlen($cevap)<=400) {
            $sorular[$sid]['cevaplar'][] = [
                'uid' => $user_id,
                'user' => get_userdata($user_id)->display_name,
                'cevap' => $cevap,
                'ts' => time()
            ];
            update_post_meta($post_id, 'ys_sorular', $sorular);
            $msg = '<div class="form-success">Cevabınız eklendi!</div>';
        } else {
            $msg = '<div class="form-error">Cevap boş olamaz ve 400 karakteri geçemez.</div>';
        }
    }

    $sorular = get_post_meta($post_id, 'ys_sorular', true);
    if (!is_array($sorular)) $sorular = [];
    ob_start();

    // Soru Formu
    if (is_user_logged_in()):
    ?>
    <form method="post" style="margin-bottom:22px;">
        <?php echo $msg; ?>
        <textarea name="ys_soru" required maxlength="300" placeholder="Bu tarif hakkında bir soru sor..." style="width:100%;min-height:48px;margin-bottom:7px;"></textarea>
        <button type="submit" name="ys_soru_gonder">Soruyu Gönder</button>
    </form>
    <?php else: ?>
    <div class="form-info">Soru sormak için <a href="<?php echo wp_login_url(get_permalink()); ?>">giriş yapın</a>.</div>
    <?php endif; ?>

    <?php if ($sorular): ?>
    <div class="qa-list">
        <?php foreach($sorular as $sid => $soru): ?>
            <div class="qa-q" style="margin-bottom:13px;padding:10px 12px 8px 12px;background:#f7f7f7;border-radius:7px;">
                <div style="font-weight:bold;color:#c72828;">
                    <span style="font-size:1.1em;">S:</span>
                    <?php echo esc_html($soru['soru']); ?>
                </div>
                <div style="font-size:0.93em;color:#777;">
                    <?php echo esc_html($soru['user']); ?> &bull; <?php echo date('d.m.Y H:i', $soru['ts']); ?>
                </div>
                <?php if (is_user_logged_in()): ?>
                <form method="post" style="margin-top:7px;">
                    <input type="hidden" name="soru_id" value="<?php echo $sid; ?>">
                    <input type="text" name="ys_cevap" required maxlength="400" placeholder="Bu soruya cevap ver..." style="width:70%;max-width:260px;">
                    <button type="submit" name="ys_cevap_gonder" style="font-size:0.95em;">Cevapla</button>
                </form>
                <?php endif; ?>

                <?php if (!empty($soru['cevaplar'])): ?>
                    <div class="qa-answers" style="margin-top:7px;padding-left:10px;">
                        <?php foreach($soru['cevaplar'] as $cevap): ?>
                            <div class="qa-a" style="background:#e7fbe7;color:#222;padding:8px 10px;margin-bottom:4px;border-radius:5px;">
                                <span style="font-size:1.08em;">C:</span>
                                <?php echo esc_html($cevap['cevap']); ?>
                                <div style="font-size:0.91em;color:#296230;">
                                    <?php echo esc_html($cevap['user']); ?> &bull; <?php echo date('d.m.Y H:i', $cevap['ts']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <p style="color:#888;">Henüz hiç soru sorulmamış.</p>
    <?php endif;

    return ob_get_clean();
});

// Gelişmiş Arama ve Filtreleme Formu
add_shortcode('ys_arama_formu', function() {
    $q = isset($_GET['sq']) ? sanitize_text_field($_GET['sq']) : '';
    $kategori = isset($_GET['kategori']) ? intval($_GET['kategori']) : 0;
    $malzeme = isset($_GET['malzeme']) ? sanitize_text_field($_GET['malzeme']) : '';
    $sure = isset($_GET['sure']) ? intval($_GET['sure']) : 0;

    ob_start(); ?>
    <form class="ys-advanced-search" method="get" style="margin-bottom:22px;">
        <input type="text" name="sq" placeholder="Tarif veya yazı ara..." value="<?php echo esc_attr($q); ?>" style="width:100%;max-width:400px;margin-bottom:10px;">
        
        <select name="kategori" style="margin-right:10px;">
            <option value="0">Kategori Seçin</option>
            <?php
            $terms = get_terms(['taxonomy'=>'yemek_turu','hide_empty'=>false]);
            if(!is_wp_error($terms)) foreach($terms as $term){
                echo '<option value="'.$term->term_id.'" '.selected($kategori,$term->term_id,false).'>'.$term->name.'</option>';
            }
            ?>
        </select>

        <input type="text" name="malzeme" placeholder="Malzeme (örn: tavuk)" value="<?php echo esc_attr($malzeme); ?>" style="margin-right:10px;">
        
        <select name="sure">
            <option value="0">Hazırlama Süresi</option>
            <option value="15" <?php selected($sure,15); ?>>15 dk altı</option>
            <option value="30" <?php selected($sure,30); ?>>30 dk altı</option>
            <option value="60" <?php selected($sure,60); ?>>1 saat altı</option>
            <option value="61" <?php selected($sure,61); ?>>1 saat üstü</option>
        </select>
        
        <button type="submit" style="margin-top:10px;">Ara</button>
    </form>
    <?php

    // WP_Query ile sonuçları listeleme
    $args = [
        'post_type'=>['tarif','post'], // Hem "tarif" hem "post" sorgular
        'posts_per_page'=>12,
        's'=>$q,
    ];

    if($kategori){
        $args['tax_query'] = [[
            'taxonomy'=>'yemek_turu',
            'terms'=>$kategori
        ]];
    }

    if($malzeme){
        $args['meta_query'][] = [
            'key'=>'malzemeler',
            'value'=>$malzeme,
            'compare'=>'LIKE'
        ];
    }

    if($sure) {
        if($sure==15) {
            $args['meta_query'][] = [
                'key'=>'hazir_sure',
                'value'=>15,
                'type'=>'NUMERIC',
                'compare'=>'<='
            ];
        } elseif($sure==30) {
            $args['meta_query'][] = [
                'key'=>'hazir_sure',
                'value'=>30,
                'type'=>'NUMERIC',
                'compare'=>'<='
            ];
        } elseif($sure==60) {
            $args['meta_query'][] = [
                'key'=>'hazir_sure',
                'value'=>60,
                'type'=>'NUMERIC',
                'compare'=>'<='
            ];
        } elseif($sure==61) {
            $args['meta_query'][] = [
                'key'=>'hazir_sure',
                'value'=>60,
                'type'=>'NUMERIC',
                'compare'=>'>'
            ];
        }
    }

    // Sorgu çalıştır
    $qres = new WP_Query($args);
    if($q || $kategori || $malzeme || $sure) {
        echo '<div class="block-recipes-list">';
        if($qres->have_posts()) {
            while($qres->have_posts()) { $qres->the_post();
                get_template_part('template-parts/recipe-card'); // Tarif kartı şablonu
            }
        } else {
            echo '<p>Sonuç bulunamadı.</p>';
        }
        wp_reset_postdata();
        echo '</div>';
    }

    return ob_get_clean();
});