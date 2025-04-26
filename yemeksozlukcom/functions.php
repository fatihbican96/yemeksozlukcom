<?php
require_once(ABSPATH . 'wp-admin/includes/image.php');
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once(ABSPATH . 'wp-admin/includes/media.php');

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
        'supports' => array('title', 'editor', 'thumbnail', 'author', 'custom-fields', 'comments'),
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
        $zorluk = sanitize_text_field($_POST['zorluk']);

        if (!$tarif_adi || !$giris || !$kapak_aciklama || !$kac_kisilik || !$hazir_sure || !$pisirme_sure || empty($malzemeler) || empty($adimlar) || !$zorluk) {
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
                    'zorluk' => $zorluk,
                ]
            ]);

            if ($post_id && !is_wp_error($post_id)) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                if (!empty($_FILES['tarif_gorsel']['name'])) {
                    $uploaded = media_handle_upload('tarif_gorsel', $post_id);
                    if (is_wp_error($uploaded)) {
                        $msg = '<div class="form-error">Görsel yüklenemedi: ' . $uploaded->get_error_message() . '</div>';
                    } else {
                        set_post_thumbnail($post_id, $uploaded);
                    }
                }
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
        <label>Zorluk Derecesi: <span style="color:#c72828">*</span>
            <select name="zorluk" required>
                <option value="">Seçiniz</option>
                <option value="Kolay" <?php selected($_POST['zorluk'] ?? '', 'Kolay'); ?>>Kolay</option>
                <option value="Orta" <?php selected($_POST['zorluk'] ?? '', 'Orta'); ?>>Orta</option>
                <option value="Zor" <?php selected($_POST['zorluk'] ?? '', 'Zor'); ?>>Zor</option>
            </select>
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

// Meta kutusu ekle
function ys_meta_box_ekle() {
    add_meta_box('ys_meta', 'SEO Ayarları', 'ys_meta_box_icerik', ['post', 'page', 'tarif'], 'normal', 'high');
}
add_action('add_meta_boxes', 'ys_meta_box_ekle');

// Meta kutusu içeriği
function ys_meta_box_icerik($post) {
    $meta_title = get_post_meta($post->ID, '_ys_meta_title', true);
    $meta_desc = get_post_meta($post->ID, '_ys_meta_desc', true);
    $meta_keys = get_post_meta($post->ID, '_ys_meta_keys', true);
    ?>
    <label>Meta Başlığı:</label><br>
    <input type="text" name="ys_meta_title" value="<?php echo esc_attr($meta_title); ?>" style="width:100%;"><br><br>
    <label>Meta Açıklaması:</label><br>
    <textarea name="ys_meta_desc" rows="2" style="width:100%;"><?php echo esc_textarea($meta_desc); ?></textarea><br><br>
    <label>Anahtar Kelimeler (virgülle ayırın):</label><br>
    <input type="text" name="ys_meta_keys" value="<?php echo esc_attr($meta_keys); ?>" style="width:100%;">
    <?php
}

// Meta veriyi kaydet
function ys_meta_box_kaydet($post_id) {
    if (array_key_exists('ys_meta_title', $_POST)) {
        update_post_meta($post_id, '_ys_meta_title', sanitize_text_field($_POST['ys_meta_title']));
    }
    if (array_key_exists('ys_meta_desc', $_POST)) {
        update_post_meta($post_id, '_ys_meta_desc', sanitize_textarea_field($_POST['ys_meta_desc']));
    }
    if (array_key_exists('ys_meta_keys', $_POST)) {
        update_post_meta($post_id, '_ys_meta_keys', sanitize_text_field($_POST['ys_meta_keys']));
    }
}
add_action('save_post', 'ys_meta_box_kaydet');

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

// Tarif Ekleme Shortcode
function tarif_ekle_schema_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Tarif gönderebilmek için giriş yapmalısınız.</p>';
    }

    $msg = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ys_tarif_gonder'])) {
        $baslik = sanitize_text_field($_POST['tarif_baslik']);
        $aciklama = sanitize_textarea_field($_POST['tarif_aciklama']);
        $kategori = intval($_POST['yemek_turu']);
        $porsiyon = intval($_POST['porsiyon']);
        $hazir_sure = intval($_POST['hazir_sure']);
        $pisirme_sure = intval($_POST['pisirme_sure']);
        $malzemeler = array_filter(array_map('sanitize_text_field', $_POST['malzemeler'] ?? []));
        $adimlar = array_filter(array_map('sanitize_text_field', $_POST['adimlar'] ?? []));
        $puf = sanitize_textarea_field($_POST['puf']);
        $video = sanitize_text_field($_POST['video']);
        $zorluk = sanitize_text_field($_POST['zorluk']);

        if (!$baslik || !$aciklama || !$kategori || !$porsiyon || !$hazir_sure || !$pisirme_sure || empty($malzemeler) || empty($adimlar) || !$zorluk) {
            $msg = '<div class="form-error">Lütfen tüm zorunlu alanları doldurun.</div>';
        } else {
            $post_id = wp_insert_post([
                'post_type' => 'tarif',
                'post_title' => $baslik,
                'post_content' => implode("\n", $adimlar),
                'post_status' => 'pending',
                'post_author' => get_current_user_id(),
                'meta_input' => [
                    'tarif_aciklama' => $aciklama,
                    'porsiyon' => $porsiyon,
                    'hazir_sure' => $hazir_sure,
                    'pisirme_sure' => $pisirme_sure,
                    'malzemeler' => $malzemeler,
                    'puf' => $puf,
                    'video' => $video,
                    'zorluk' => $zorluk,
                ]
            ]);
            if ($post_id && !is_wp_error($post_id)) {
                wp_set_object_terms($post_id, $kategori, 'yemek_turu');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                if (!empty($_FILES['tarif_gorsel']['name'])) {
                    $uploaded = media_handle_upload('tarif_gorsel', $post_id);
                    if (!is_wp_error($uploaded)) {
                        set_post_thumbnail($post_id, $uploaded);
                    }
                }
                $msg = '<div class="form-success">Tarifiniz gönderildi! Editör onayından sonra yayınlanacaktır.</div>';
                $_POST = [];
            } else {
                $msg = '<div class="form-error">Bir hata oluştu. Lütfen tekrar deneyin.</div>';
            }
        }
    }

    $terms = get_terms(['taxonomy'=>'yemek_turu','hide_empty'=>false]);
    ob_start();
    ?>
    <form method="post" class="tarif-form" enctype="multipart/form-data" itemscope itemtype="https://schema.org/Recipe">
        <?php echo $msg; ?>
        <label>Tarif Başlığı: <span style="color:#c72828">*</span>
            <input type="text" name="tarif_baslik" maxlength="120" required value="<?php echo esc_attr($_POST['tarif_baslik'] ?? ''); ?>" itemprop="name">
        </label>
        <label>Tarif Açıklaması: <span style="color:#c72828">*</span>
            <textarea name="tarif_aciklama" maxlength="1000" required itemprop="description"><?php echo esc_textarea($_POST['tarif_aciklama'] ?? ''); ?></textarea>
        </label>
        <label>Yemek Türü: <span style="color:#c72828">*</span>
            <select name="yemek_turu" required>
                <option value="">Seçiniz</option>
                <?php foreach ($terms as $term): ?>
                    <option value="<?php echo esc_attr($term->term_id); ?>" <?php selected($_POST['yemek_turu'] ?? '', $term->term_id); ?>><?php echo esc_html($term->name); ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Porsiyon (Kişi Sayısı): <span style="color:#c72828">*</span>
            <input type="number" name="porsiyon" min="1" max="50" required value="<?php echo esc_attr($_POST['porsiyon'] ?? ''); ?>" itemprop="recipeYield">
        </label>
        <label>Hazırlama Süresi (dakika): <span style="color:#c72828">*</span>
            <input type="number" name="hazir_sure" min="1" max="1000" required value="<?php echo esc_attr($_POST['hazir_sure'] ?? ''); ?>" itemprop="prepTime">
        </label>
        <label>Pişirme Süresi (dakika): <span style="color:#c72828">*</span>
            <input type="number" name="pisirme_sure" min="1" max="1000" required value="<?php echo esc_attr($_POST['pisirme_sure'] ?? ''); ?>" itemprop="cookTime">
        </label>
        <label>Malzemeler: <span style="color:#c72828">*</span>
            <div id="malzeme-listesi">
                <?php
                $malzemeler = $_POST['malzemeler'] ?? [''];
                foreach ($malzemeler as $malzeme) {
                    echo '<input name="malzemeler[]" type="text" maxlength="100" required placeholder="Malzeme – miktar" value="'.esc_attr($malzeme).'" itemprop="recipeIngredient"><br>';
                }
                ?>
            </div>
            <button type="button" onclick="ekleMalzeme()" style="margin-top:5px;">+ Malzeme Ekle</button>
        </label>
        <label>Nasıl Yapılır? (adım adım): <span style="color:#c72828">*</span>
            <div id="adim-listesi">
                <?php
                $adimlar = $_POST['adimlar'] ?? [''];
                foreach ($adimlar as $adim) {
                    echo '<input name="adimlar[]" type="text" maxlength="250" required placeholder="Adım – fiil ile başla" value="'.esc_attr($adim).'" itemprop="recipeInstructions"><br>';
                }
                ?>
            </div>
            <button type="button" onclick="ekleAdim()" style="margin-top:5px;">+ Adım Ekle</button>
        </label>
        <label>Püf Noktası / Servis Önerisi:
            <textarea name="puf" maxlength="500"><?php echo esc_textarea($_POST['puf'] ?? ''); ?></textarea>
        </label>
        <label>Video Linki (varsa):
            <input type="url" name="video" placeholder="YouTube/Instagram URL" value="<?php echo esc_attr($_POST['video'] ?? ''); ?>">
        </label>
        <label>Tarif Görseli:
            <input type="file" name="tarif_gorsel" accept="image/*">
        </label>
        <label>Zorluk Derecesi: <span style="color:#c72828">*</span>
            <select name="zorluk" required>
                <option value="">Seçiniz</option>
                <option value="Kolay" <?php selected($_POST['zorluk'] ?? '', 'Kolay'); ?>>Kolay</option>
                <option value="Orta" <?php selected($_POST['zorluk'] ?? '', 'Orta'); ?>>Orta</option>
                <option value="Zor" <?php selected($_POST['zorluk'] ?? '', 'Zor'); ?>>Zor</option>
            </select>
        </label>
        <button type="submit" name="ys_tarif_gonder">Tarifi Gönder</button>
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
    .tarif-form input[type=text], .tarif-form input[type=number],
    .tarif-form input[type=url], .tarif-form textarea, .tarif-form select {
        width: 100%; max-width: 480px; padding: 7px; margin-bottom: 8px; border: 1px solid #bbb; border-radius: 6px;
    }
    .tarif-form label { font-weight: 500; display: block; margin-bottom: 12px;}
    .form-error {color:#b31c1c;background:#ffeaea;padding:8px 13px;border-radius:8px;margin: 6px 0 10px 0;}
    .form-success {color:#15811a;background:#e9ffea;padding:8px 13px;border-radius:8px;margin: 6px 0 10px 0;}
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('tarif_ekle', 'tarif_ekle_schema_shortcode');

// Tarif detayları meta kutusu
function ys_tarif_detay_meta_box() {
    add_meta_box('ys_tarif_detay', 'Tarif Detayları', 'ys_tarif_detay_icerik', 'tarif', 'normal', 'high');
}
add_action('add_meta_boxes', 'ys_tarif_detay_meta_box');

function ys_tarif_detay_icerik($post) {
    $aciklama = get_post_meta($post->ID, 'tarif_aciklama', true);
    $porsiyon = get_post_meta($post->ID, 'porsiyon', true);
    $hazir_sure = get_post_meta($post->ID, 'hazir_sure', true);
    $pisirme_sure = get_post_meta($post->ID, 'pisirme_sure', true);
    $malzemeler = get_post_meta($post->ID, 'malzemeler', true);
    $adimlar = get_post_meta($post->ID, 'adimlar', true);
    $puf = get_post_meta($post->ID, 'puf', true);
    $video = get_post_meta($post->ID, 'video', true);
    $zorluk = get_post_meta($post->ID, 'zorluk', true);

    // Malzemeler ve adımlar dizi olarak kaydedildiyse
    if (is_array($malzemeler)) $malzemeler = implode("\n", $malzemeler);
    if (is_array($adimlar)) $adimlar = implode("\n", $adimlar);

    ?>
    <label><strong>Tarif Açıklaması:</strong></label>
    <textarea name="ys_tarif_aciklama" rows="2" style="width:100%;"><?php echo esc_textarea($aciklama); ?></textarea><br><br>
    <label><strong>Porsiyon:</strong></label>
    <input type="number" name="ys_porsiyon" value="<?php echo esc_attr($porsiyon); ?>" min="1" max="50" style="width:100px;"><br><br>
    <label><strong>Hazırlama Süresi (dakika):</strong></label>
    <input type="number" name="ys_hazir_sure" value="<?php echo esc_attr($hazir_sure); ?>" min="1" max="1000" style="width:100px;"><br><br>
    <label><strong>Pişirme Süresi (dakika):</strong></label>
    <input type="number" name="ys_pisirme_sure" value="<?php echo esc_attr($pisirme_sure); ?>" min="1" max="1000" style="width:100px;"><br><br>
    <label><strong>Malzemeler (her satıra bir malzeme):</strong></label>
    <textarea name="ys_malzemeler" rows="4" style="width:100%;"><?php echo esc_textarea($malzemeler); ?></textarea><br><br>
    <label><strong>Adımlar (her satıra bir adım):</strong></label>
    <textarea name="ys_adimlar" rows="5" style="width:100%;"><?php echo esc_textarea($adimlar); ?></textarea><br><br>
    <label><strong>Püf Noktası / Servis Önerisi:</strong></label>
    <textarea name="ys_puf" rows="2" style="width:100%;"><?php echo esc_textarea($puf); ?></textarea><br><br>
    <label><strong>Video Linki:</strong></label>
    <input type="url" name="ys_video" value="<?php echo esc_attr($video); ?>" style="width:100%;"><br><br>
    <label><strong>Zorluk:</strong></label>
    <select name="ys_zorluk">
      <option value="Kolay" <?php selected($zorluk, 'Kolay'); ?>>Kolay</option>
      <option value="Orta" <?php selected($zorluk, 'Orta'); ?>>Orta</option>
      <option value="Zor" <?php selected($zorluk, 'Zor'); ?>>Zor</option>
    </select><br><br>
    <?php
}

// Kaydetme işlemi
function ys_tarif_detay_kaydet($post_id) {
    if (isset($_POST['ys_tarif_aciklama'])) {
        update_post_meta($post_id, 'tarif_aciklama', sanitize_textarea_field($_POST['ys_tarif_aciklama']));
    }
    if (isset($_POST['ys_porsiyon'])) {
        update_post_meta($post_id, 'porsiyon', intval($_POST['ys_porsiyon']));
    }
    if (isset($_POST['ys_hazir_sure'])) {
        update_post_meta($post_id, 'hazir_sure', intval($_POST['ys_hazir_sure']));
    }
    if (isset($_POST['ys_pisirme_sure'])) {
        update_post_meta($post_id, 'pisirme_sure', intval($_POST['ys_pisirme_sure']));
    }
    if (isset($_POST['ys_malzemeler'])) {
        // Satırlara ayırıp dizi olarak kaydediyoruz
        $malzemeler = array_filter(array_map('trim', explode("\n", $_POST['ys_malzemeler'])));
        update_post_meta($post_id, 'malzemeler', $malzemeler);
    }
    if (isset($_POST['ys_adimlar'])) {
        $adimlar = array_filter(array_map('trim', explode("\n", $_POST['ys_adimlar'])));
        update_post_meta($post_id, 'adimlar', $adimlar);
    }
    if (isset($_POST['ys_puf'])) {
        update_post_meta($post_id, 'puf', sanitize_textarea_field($_POST['ys_puf']));
    }
    if (isset($_POST['ys_video'])) {
        update_post_meta($post_id, 'video', esc_url_raw($_POST['ys_video']));
    }
    if (isset($_POST['ys_zorluk'])) {
        update_post_meta($post_id, 'zorluk', sanitize_text_field($_POST['ys_zorluk']));
    }
}
add_action('save_post_tarif', 'ys_tarif_detay_kaydet');

// Profil Düzenleme Shortcode
function ys_profil_duzenle_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Profilinizi düzenlemek için giriş yapmalısınız.</p>';
    }
    $user_id = get_current_user_id();
    $user = get_userdata($user_id);
    $msg = '';

    // Profil güncelleme işlemi
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ys_profil_guncelle'])) {
        $display_name = sanitize_text_field($_POST['display_name']);
        $bio = sanitize_textarea_field($_POST['description']);

        wp_update_user([
            'ID' => $user_id,
            'display_name' => $display_name,
            'description' => $bio
        ]);

        // Profil fotoğrafı yükleme
        if (!empty($_FILES['profil_foto']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            $attachment_id = media_handle_upload('profil_foto', 0);
            if (!is_wp_error($attachment_id)) {
                update_user_meta($user_id, 'profil_foto', $attachment_id);
            }
        }
        $msg = '<div class="form-success">Profiliniz güncellendi.</div>';
        $user = get_userdata($user_id);
    }

    // Şifre değiştirme işlemi
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ys_sifre_degistir'])) {
        $pass1 = $_POST['pass1'];
        $pass2 = $_POST['pass2'];
        if ($pass1 && $pass1 === $pass2 && strlen($pass1) >= 6) {
            wp_set_password($pass1, $user_id);
            $msg = '<div class="form-success">Şifreniz güncellendi.</div>';
        } else {
            $msg = '<div class="form-error">Şifreler eşleşmeli ve en az 6 karakter olmalı.</div>';
        }
    }

    // E-posta değiştirme işlemi
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ys_email_degistir'])) {
        $email = sanitize_email($_POST['email']);
        if (is_email($email)) {
            wp_update_user(['ID' => $user_id, 'user_email' => $email]);
            $msg = '<div class="form-success">E-posta adresiniz güncellendi.</div>';
        } else {
            $msg = '<div class="form-error">Geçerli bir e-posta giriniz.</div>';
        }
    }

    // Kullanıcı adı değiştirme işlemi (sadece bir kez ve admin değilse)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ys_kadi_degistir'])) {
        $kadi = sanitize_user($_POST['user_login']);
        if ($kadi && !username_exists($kadi) && $user->user_login !== $kadi) {
                <input type="file" name="profil_foto" accept="image/*">
            </div>
            <label>Görünen Adınız:
                <input type="text" name="display_name" value="<?php echo esc_attr($user->display_name); ?>" required>
            </label>
            <label>Hakkınızda (bio):
                <textarea name="description" rows="3"><?php echo esc_textarea($user->description); ?></textarea>
            </label>
            <button type="submit" name="ys_profil_guncelle">Profili Güncelle</button>
        </form>

        <hr class="profil-hr">

        <form method="post" class="profil-form">
            <h3>Şifre Değiştir</h3>
            <input type="password" name="pass1" placeholder="Yeni Şifre" required>
            <input type="password" name="pass2" placeholder="Yeni Şifre (Tekrar)" required>
            <button type="submit" name="ys_sifre_degistir">Şifreyi Güncelle</button>
        </form>

        <hr class="profil-hr">

        <form method="post" class="profil-form">
            <h3>E-posta Değiştir</h3>
            <input type="email" name="email" value="<?php echo esc_attr($user->user_email); ?>" required>
            <button type="submit" name="ys_email_degistir">E-posta Güncelle</button>
        </form>

        <hr class="profil-hr">

        <form method="post" class="profil-form">
            <h3>Kullanıcı Adı Değiştir</h3>
            <input type="text" name="user_login" value="<?php echo esc_attr($user->user_login); ?>" required>
            <button type="submit" name="ys_kadi_degistir">Kullanıcı Adı Güncelle</button>
        </form>

        <hr class="profil-hr">

        <div class="profil-form">
            <h3>Hesap Ayarları</h3>
            <p>Buraya ek ayarlar ekleyebilirsiniz.</p>
        </div>

        <div class="profil-form">
            <h3>Sosyal Hesaplar</h3>
            <p>Buraya sosyal medya bağlantı alanları ekleyebilirsiniz.</p>
        </div>

        <div class="profil-form">
            <h3>İletişim Bilgileri</h3>
            <p>Buraya telefon, adres gibi iletişim alanları ekleyebilirsiniz.</p>
        </div>

        <div class="profil-form">
            <h3>Bildirim Ayarları</h3>
            <p>Buraya bildirim tercihleri ekleyebilirsiniz.</p>
        </div>

        <div class="profil-form">
            <h3>Masaüstü Bildirim Ayarları</h3>
            <p>Buraya masaüstü bildirim seçenekleri ekleyebilirsiniz.</p>
        </div>

        <div class="profil-form">
            <h3>E-posta Bildirim Ayarları</h3>
            <p>Buraya e-posta bildirim tercihleri ekleyebilirsiniz.</p>
        </div>
    </div>
    <style>
    .profil-duzenle-panel { max-width: 420px; margin: 0 auto; }
    .profil-duzenle-panel h2 { text-align:center; color:#c72828; margin-bottom:18px; }
    .profil-duzenle-panel h3 { margin:18px 0 10px 0; color:#a41c1c; font-size:1.08em; }
    .profil-form { margin-bottom: 10px; }
    .profil-form label { display:block; margin-bottom:8px; font-weight:500; }
    .profil-form input[type="text"], .profil-form input[type="email"], .profil-form input[type="password"], .profil-form textarea {
        width:100%; max-width:350px; padding:7px; margin-bottom:8px; border:1px solid #bbb; border-radius:6px;
    }
    .profil-form button { background:#c72828; color:#fff; border:none; padding:8px 22px; border-radius:6px; font-size:1em; cursor:pointer; }
    .profil-form button:hover { background:#a41c1c; }
    .profil-foto-blok { text-align:center; margin-bottom:14px; }
    .profil-foto-blok input[type="file"] { margin-top:8px; }
    .profil-hr { border:0; border-top:1px solid #eee; margin:18px 0; }
    .form-success {color:#15811a;background:#e9ffea;padding:8px 13px;border-radius:8px;margin: 6px 0 10px 0;}
    .form-error {color:#b31c1c;background:#ffeaea;padding:8px 13px;border-radius:8px;margin: 6px 0 10px 0;}
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('profil_duzenle', 'ys_profil_duzenle_shortcode');

// Kullanıcı avatar URL'sini alma
function ys_get_user_avatar_url($user_id, $size = 96) {
    $profil_foto_id = get_user_meta($user_id, 'profil_foto', true);
    if ($profil_foto_id) {
        $url = wp_get_attachment_image_url($profil_foto_id, [$size, $size]);
        if ($url) return $url;
    }
    $user = get_userdata($user_id);
    if ($user && !empty($user->user_email)) {
        return 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($user->user_email))) . '?s=' . $size . '&d=mp';
    }
    return 'https://www.gravatar.com/avatar/?s=' . $size . '&d=mp';
}

// Avatar URL'sini filtreleme
add_filter('get_avatar_url', function($url, $id_or_email, $args) {
    $user_id = false;

    if (is_numeric($id_or_email)) {
        $user_id = intval($id_or_email);
    } elseif (is_object($id_or_email) && isset($id_or_email->user_id)) {
        $user_id = intval($id_or_email->user_id);
    } elseif (is_string($id_or_email)) {
        $user = get_user_by('email', $id_or_email);
        if ($user && isset($user->ID)) {
            $user_id = $user->ID;
        }
    }

    if ($user_id) {
        $custom = ys_get_user_avatar_url($user_id, $args['size']);
        if ($custom) return $custom;
    }
    return $url;
}, 10, 3);

// Tarif Yayınlama Bildirimi
add_action('transition_post_status', function($new_status, $old_status, $post) {
    if ($post->post_type === 'tarif' && $old_status === 'pending' && $new_status === 'publish') {
        $author = get_userdata($post->post_author);
        if ($author && !empty($author->user_email)) {
            $subject = 'Tarifiniz Yayınlandı!';
            $message = 'Merhaba ' . esc_html($author->display_name) . ",\n\n"
                . '"' . esc_html($post->post_title) . '" başlıklı tarifiniz editör onayıyla yayına alındı. Teşekkürler!';
            wp_mail($author->user_email, $subject, $message);
        }
    }
}, 10, 3);

// Yorum Bildirimi
add_action('comment_post', function($comment_ID, $comment_approved) {
    if ($comment_approved !== 1) return;
    $comment = get_comment($comment_ID);
    if ($comment->comment_parent) {
        $parent = get_comment($comment->comment_parent);
        if ($parent && $parent->user_id) {
            $user = get_userdata($parent->user_id);
            if ($user && !empty($user->user_email)) {
                $subject = 'Tarifine Yorum Geldi!';
                $message = 'Merhaba ' . esc_html($user->display_name) . ",\n\n"
                    . 'Tarifine yeni bir yanıt geldi: "' . esc_html($comment->comment_content) . '"';
                wp_mail($user->user_email, $subject, $message);
            }
        }
    }
}, 10, 2);

// Yorum formuna yıldız puan ekle
add_filter('comment_form_fields', function($fields) {
    $puan_html = '<p class="comment-form-rating"><label for="yorum_puani">Puanınız: </label>
    <span class="yorum-starlar">';
    for($i=1;$i<=5;$i++) {
        $puan_html .= '<input type="radio" name="yorum_puani" id="yorum_puani'.$i.'" value="'.$i.'" style="display:none;">
        <label for="yorum_puani'.$i.'" style="font-size:1.5em;cursor:pointer;color:#ffc107;">&#9733;</label>';
    }
    $puan_html .= '</span></p>';
    // Yorum alanından önce ekle
    $fields = array_merge(['yorum_puani'=>$puan_html], $fields);
    return $fields;
});

// Yorum puanını kaydet
add_action('comment_post', function($comment_id) {
    if(isset($_POST['yorum_puani'])) {
        $puan = intval($_POST['yorum_puani']);
        if($puan >= 1 && $puan <= 5) {
            add_comment_meta($comment_id, 'yorum_puani', $puan);
        }
    }
});

// Yorum puanını göster
add_filter('comment_text', function($comment_text, $comment) {
    $puan = get_comment_meta($comment->comment_ID, 'yorum_puani', true);
    if($puan) {
        $puan_html = '<span class="yorum-puan">';
        for($i=1;$i<=5;$i++) {
            $puan_html .= '<span style="color:'.($i<=$puan?'#ffc107':'#ccc').';font-size:1.2em;">&#9733;</span>';
        }
        $comment_text .= $puan_html;
    }
    return $comment_text;
}, 10, 2);

// Canlı Arama Sistemi
add_action('wp_ajax_canli_arama', 'canli_arama_ajax');
add_action('wp_ajax_nopriv_canli_arama', 'canli_arama_ajax');
function canli_arama_ajax() {
    $aranan = isset($_POST['q']) ? sanitize_text_field($_POST['q']) : '';
    if(strlen($aranan) < 2) exit;
    $args = array(
        's' => $aranan,
        'post_type' => array('post', 'tarif'),
        'posts_per_page' => 6,
        'post_status' => 'publish'
    );
    $query = new WP_Query($args);
    if($query->have_posts()) {
        echo '<ul style="margin:0;padding:0;list-style:none;">';
        while($query->have_posts()) {
            $query->the_post();
            echo '<li style="border-bottom:1px solid #eee;"><a href="'.get_permalink().'">'.get_the_title().'</a></li>';
        }
        echo '</ul>';
    } else {
        echo '<div style="padding:8px 12px;color:#888;">Sonuç bulunamadı.</div>';
    }
    wp_die();
}

// PROFİLİM SEKME SİSTEMİ [profilim] kısa kodu
function profilim_sayfasi_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Profilinizi görüntülemek için giriş yapmalısınız.</p>';
    }
    ob_start();
    ?>
    <div class="profilim-tabs">
        <ul class="profilim-tab-menu">
            <li class="active" data-tab="profil"><span class="tab-ikon">👤</span> Profil</li>
            <li data-tab="tarifler"><span class="tab-ikon">📋</span> Gönderdiğim Tarifler</li>
            <li data-tab="taslaklar"><span class="tab-ikon">📝</span> Taslak Tariflerim</li>
            <li data-tab="bendedim"><span class="tab-ikon">🍽️</span> Ben de Yaptım</li>
            <li data-tab="defter"><span class="tab-ikon">📖</span> Tarif Defteri</li>
            <li data-tab="yorumlar"><span class="tab-ikon">💬</span> Yorumlarım</li>
        </ul>
        <div class="profilim-tab-content active" id="profil">
            <?php echo do_shortcode('[profil_duzenle]'); ?>
        </div>
        <div class="profilim-tab-content" id="tarifler">
            <?php echo do_shortcode('[kendi_tariflerim]'); ?>
        </div>
        <div class="profilim-tab-content" id="taslaklar">
            <div class="profilim-bos">Taslak tarifleriniz burada listelenecek.</div>
        </div>
        <div class="profilim-tab-content" id="bendedim">
            <div class="profilim-bos">Ben de Yaptım bölümü (isteğe bağlı kısa kod eklenebilir).</div>
        </div>
        <div class="profilim-tab-content" id="defter">
            <div class="profilim-bos">Tarif Defteri bölümü (isteğe bağlı kısa kod eklenebilir).</div>
        </div>
        <div class="profilim-tab-content" id="yorumlar">
            <div class="profilim-bos">Yorumlarım bölümü (isteğe bağlı kısa kod eklenebilir).</div>
        </div>
    </div>
    <style>
    .profilim-tabs {
        max-width: 700px;
        margin: 40px auto;
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 4px 24px #0002;
        overflow: hidden;
    }
    .profilim-tab-menu {
        display: flex;
        border-bottom: 2px solid #f3dede;
        margin: 0;
        padding: 0;
        list-style: none;
        background: #f7e7e7;
    }
    .profilim-tab-menu li {
        flex: 1;
        text-align: center;
        padding: 18px 0 14px 0;
        cursor: pointer;
        font-weight: 600;
        color: #c72828;
        border-bottom: 3px solid transparent;
        transition: all .2s;
        font-size: 1.08em;
        letter-spacing: 0.01em;
        position: relative;
        background: none;
    }
    .profilim-tab-menu li .tab-ikon {
        font-size: 1.2em;
        margin-right: 6px;
        vertical-align: middle;
    }
    .profilim-tab-menu li.active {
        border-bottom: 3px solid #c72828;
        background: #fff;
        color: #a41c1c;
        z-index: 2;
    }
    .profilim-tab-menu li:hover:not(.active) {
        background: #fbeaea;
        color: #a41c1c;
    }
    .profilim-tab-content {
        display: none;
        padding: 32px 28px 28px 28px;
        min-height: 220px;
        animation: fadeInTab .3s;
    }
    .profilim-tab-content.active {
        display: block;
    }
    .profilim-bos {
        color: #888;
        background: #f7f7f7;
        border-radius: 8px;
        padding: 32px 0;
        text-align: center;
        font-size: 1.08em;
    }
    @keyframes fadeInTab {
        from { opacity: 0; transform: translateY(20px);}
        to { opacity: 1; transform: none;}
    }
    @media (max-width: 800px) {
        .profilim-tabs { max-width: 98vw; }
        .profilim-tab-content { padding: 18px 8px 18px 8px; }
        .profilim-tab-menu li { font-size: 0.98em; padding: 13px 0 10px 0; }
    }
    @media (max-width: 500px) {
        .profilim-tab-menu { flex-direction: column; }
        .profilim-tab-menu li { border-bottom: 1.5px solid #f3dede; border-right: none; }
        .profilim-tab-menu li.active { border-bottom: 3px solid #c72828; }
    }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded',function(){
        document.querySelectorAll('.profilim-tab-menu li').forEach(function(tab){
            tab.addEventListener('click',function(){
                document.querySelectorAll('.profilim-tab-menu li').forEach(function(t){t.classList.remove('active');});
                document.querySelectorAll('.profilim-tab-content').forEach(function(c){c.classList.remove('active');});
                tab.classList.add('active');
                document.getElementById(tab.getAttribute('data-tab')).classList.add('active');
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('profilim', 'profilim_sayfasi_shortcode');

// === KAPSAMLI İSTATİSTİKLER: Admin Panelinde Tarif, Kullanıcı, Favori, Görüntülenme ===
add_action('admin_menu', function() {
    add_menu_page(
        'Yemek Sözlük İstatistikler',
        'YS İstatistikler',
        'manage_options',
        'ys_istatistikler',
        'ys_istatistikler_admin_sayfa',
        'dashicons-chart-bar',
        3
    );
});

function ys_istatistikler_admin_sayfa() {
    global $wpdb;

    // Toplam tarif sayısı
    $tarif_sayisi = wp_count_posts('tarif')->publish;

    // Toplam kullanıcı sayısı
    $kullanici_sayisi = count_users()['total_users'];

    // Toplam favori (tüm kullanıcıların favori tarif toplamı)
    $favori_toplam = 0;
    $usermeta = $wpdb->get_results("SELECT meta_value FROM $wpdb->usermeta WHERE meta_key = 'ys_favs'");
    foreach ($usermeta as $row) {
        $favs = maybe_unserialize($row->meta_value);
        if (is_array($favs)) $favori_toplam += count($favs);
    }

    // Tarif görüntülenme (her tarifte 'views' meta varsa)
    $goruntulenme = $wpdb->get_var("SELECT SUM(meta_value+0) FROM $wpdb->postmeta WHERE meta_key = 'views'");
    if (!$goruntulenme) $goruntulenme = 0;

    // Son 7 gün eklenen tarifler
    $son7gun = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type='tarif' AND post_status='publish' AND post_date > %s",
        date('Y-m-d H:i:s', strtotime('-7 days'))
    ));

    // En çok favorilenen 5 tarif
    $favori_sayilari = [];
    foreach ($usermeta as $row) {
        $favs = maybe_unserialize($row->meta_value);
        if (is_array($favs)) {
            foreach ($favs as $pid) {
                if (!isset($favori_sayilari[$pid])) $favori_sayilari[$pid] = 0;
                $favori_sayilari[$pid]++;
            }
        }
    }
    arsort($favori_sayilari);
    $en_cok_favori = array_slice($favori_sayilari, 0, 5, true);

    ?>
    <div class="wrap">
        <h1>Yemek Sözlük İstatistikler</h1>
        <table class="widefat" style="max-width:500px;">
            <tr><th>Toplam Tarif</th><td><?php echo intval($tarif_sayisi); ?></td></tr>
            <tr><th>Toplam Kullanıcı</th><td><?php echo intval($kullanici_sayisi); ?></td></tr>
            <tr><th>Toplam Favori</th><td><?php echo intval($favori_toplam); ?></td></tr>
            <tr><th>Toplam Görüntülenme</th><td><?php echo intval($goruntulenme); ?></td></tr>
            <tr><th>Son 7 Gün Eklenen Tarif</th><td><?php echo intval($son7gun); ?></td></tr>
        </table>
        <h2 style="margin-top:30px;">En Çok Favorilenen 5 Tarif</h2>
        <table class="widefat" style="max-width:600px;">
            <tr><th>Tarif</th><th>Favori Sayısı</th></tr>
            <?php
            if ($en_cok_favori) {
                foreach ($en_cok_favori as $pid => $adet) {
                    $title = get_the_title($pid);
                    $link = get_edit_post_link($pid);
                    echo '<tr><td><a href="'.esc_url($link).'">'.esc_html($title).'</a></td><td>'.intval($adet).'</td></tr>';
                }
            } else {
                echo '<tr><td colspan="2">Favorilenen tarif yok.</td></tr>';
            }
            ?>
        </table>
        <p style="margin-top:18px;color:#888;">Daha fazla detay için geliştirme yapılabilir.</p>
    </div>
    <?php
}

// Tarifler için AJAX yükleme
add_action('wp_enqueue_scripts', function() {
    if (is_post_type_archive('tarif')) {
        wp_enqueue_script('tarifler-ajax', get_template_directory_uri() . '/js/tarifler-ajax.js', [], false, true);
        wp_localize_script('tarifler-ajax', 'ajaxurl', admin_url('admin-ajax.php'));
    }
});

add_action('wp_ajax_tarifleri_getir', 'tarifleri_getir_callback');
add_action('wp_ajax_nopriv_tarifleri_getir', 'tarifleri_getir_callback');
function tarifleri_getir_callback() {
    $sayfa = isset($_GET['sayfa']) ? intval($_GET['sayfa']) : 1;
    $args = [
        'post_type' => 'tarif',
        'posts_per_page' => 12,
        'paged' => $sayfa
    ];
    $tarif_query = new WP_Query($args);
    if ($tarif_query->have_posts()) :
        while ($tarif_query->have_posts()) : $tarif_query->the_post(); ?>
            <div class="tarif-karti">
                <a href="<?php the_permalink(); ?>">
                    <div class="tarif-gorsel">
                        <?php if (has_post_thumbnail()) the_post_thumbnail('medium'); ?>
                    </div>
                    <div class="tarif-bilgi">
                        <h3><?php the_title(); ?></h3>
                        <p><?php echo wp_trim_words(get_the_excerpt(), 15); ?></p>
                        <span class="tarif-sure"><?php echo get_post_meta(get_the_ID(), 'sure', true); ?> dk</span>
                    </div>
                </a>
            </div>
        <?php endwhile;
    endif;
    wp_reset_postdata();
    wp_die();
}

// Kategoriler menü öğesi ekleme
add_action('admin_menu', function() {
    // Sadece bir kez ekle, ismi "Kategoriler" ve category taksonomisine yönlendir
    add_submenu_page(
        'edit.php?post_type=tarif', // Parent slug
        'Kategoriler',              // Sayfa başlığı
        'Kategoriler',              // Menüde görünen ad
        'manage_categories',        // Yetki
        'edit-tags.php?taxonomy=category&post_type=tarif' // Hedef link
    );
});

// Zorluk Seviyesi Meta Kutusu
add_action('add_meta_boxes', function() {
    add_meta_box('tarif_zorluk', 'Zorluk Seviyesi', function($post) {
        $zorluk = get_post_meta($post->ID, 'zorluk', true);
        ?>
        <select name="tarif_zorluk" style="width:100%;">
            <option value="">Seçiniz</option>
            <option value="Kolay" <?php selected($zorluk, 'Kolay'); ?>>Kolay</option>
            <option value="Orta" <?php selected($zorluk, 'Orta'); ?>>Orta</option>
            <option value="Zor" <?php selected($zorluk, 'Zor'); ?>>Zor</option>
        </select>
        <?php
    }, 'tarif', 'side');
});
add_action('save_post_tarif', function($post_id) {
    if (isset($_POST['tarif_zorluk'])) {
        update_post_meta($post_id, 'zorluk', sanitize_text_field($_POST['tarif_zorluk']));
    }
});

// Tarif detayları için zorluk seviyesi ekleme
add_action('the_content', function($content) {
    if (is_singular('tarif')) {
        $zorluk = get_post_meta(get_the_ID(), 'zorluk', true);
        $zorluk_html = '<div class="tarif-detay">
            <!-- Diğer detaylar -->
            <span class="tarif-zorluk">';
        if ($zorluk) $zorluk_html .= 'Zorluk: ' . esc_html($zorluk);
        $zorluk_html .= '</span></div>';
        $content .= $zorluk_html;
    }
    return $content;
});

// Tarif Oylama Sistemi
add_action('wp_ajax_tarif_oyla', 'tarif_oyla_callback');
add_action('wp_ajax_nopriv_tarif_oyla', 'tarif_oyla_callback');
function tarif_oyla_callback() {
    $post_id = intval($_POST['post_id']);
    $puan = intval($_POST['puan']);
    if ($post_id && $puan >= 1 && $puan <= 5) {
        $oylar = get_post_meta($post_id, 'oylar', true);
        if (!is_array($oylar)) $oylar = [];
        $oylar[] = $puan;
        update_post_meta($post_id, 'oylar', $oylar);
        $ortalama = array_sum($oylar) / count($oylar);
        wp_send_json_success(['ortalama'=>round($ortalama,1), 'adet'=>count($oylar)]);
    }
    wp_send_json_error();
}


