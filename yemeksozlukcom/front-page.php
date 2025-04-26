<?php get_header(); ?>

<div class="homepage-hero">
    <h1>Lezzetli Tarifler, Pratik Fikirler!</h1>
    <form id="canli-arama-form" action="<?php echo home_url('/'); ?>" method="get" autocomplete="off">
        <input type="text" id="canli-arama" name="s" placeholder="Tarif veya içerik ara..." />
        <button type="submit"><i class="fas fa-search"></i></button>
        <div id="arama-oneriler"></div>
    </form>
    <p>Binlerce yemek tarifi, püf noktası ve mutfak önerisi burada!</p>
</div>

<!-- Evdeki Malzemelerle Tarif Bul -->
<div class="malzeme-ile-tarif">
    <h2>Evdeki malzemelerinizi seçin, Size tarif önerelim</h2>
    <form id="malzeme-tarif-form" method="get" action="<?php echo home_url('/'); ?>">
        <?php
        // Tüm malzemeleri topla (basitçe son 100 tariften)
        $malzeme_list = [];
        $tarifler = get_posts(['post_type'=>'tarif','posts_per_page'=>100,'post_status'=>'publish']);
        foreach($tarifler as $tarif) {
            $malzemeler = get_post_meta($tarif->ID, 'malzemeler', true);
            if(is_array($malzemeler)) {
                foreach($malzemeler as $m) $malzeme_list[] = trim($m);
            }
        }
        $malzeme_list = array_unique($malzeme_list);
        sort($malzeme_list);
        foreach($malzeme_list as $malzeme): ?>
            <label style="margin-right:12px;">
                <input type="checkbox" name="ev_malzeme[]" value="<?php echo esc_attr($malzeme); ?>">
                <?php echo esc_html($malzeme); ?>
            </label>
        <?php endforeach; ?>
        <br>
        <button type="submit" style="margin-top:10px;">Tarifleri Listele</button>
    </form>
    <?php
    if (!empty($_GET['ev_malzeme']) && is_array($_GET['ev_malzeme'])) {
        $secili = array_map('sanitize_text_field', $_GET['ev_malzeme']);
        $args = [
            'post_type' => 'tarif',
            'posts_per_page' => 50,
            'post_status' => 'publish',
        ];
        $q = new WP_Query($args);
        $tarifler = [];
        while($q->have_posts()) { $q->the_post();
            $malzemeler = get_post_meta(get_the_ID(), 'malzemeler', true);
            if(is_array($malzemeler)) {
                $malzemeler = array_map('trim', $malzemeler);
                $kesisim = array_intersect($malzemeler, $secili);
                $uyum_orani = count($kesisim) / count($malzemeler);
                $eksik = array_diff($malzemeler, $secili);
                $tam_eslesme = (count($eksik) === 0 && count($malzemeler) === count($secili));
                if(count($kesisim) > 0) {
                    $tarifler[] = [
                        'id' => get_the_ID(),
                        'uyum' => $uyum_orani,
                        'eksik' => $eksik,
                        'tam' => $tam_eslesme,
                        'isim' => get_the_title()
                    ];
                }
            }
        }
        wp_reset_postdata();

        // Önce tam eşleşmeleri, sonra uyum oranına göre sırala
        usort($tarifler, function($a, $b) {
            if ($a['tam'] && !$b['tam']) return -1;
            if (!$a['tam'] && $b['tam']) return 1;
            return $b['uyum'] <=> $a['uyum'];
        });

        echo '<h3>Elinizdeki malzemelerle yapabileceğiniz tarifler:</h3>';
        if($tarifler) {
            echo '<div class="block-recipes-list">';
            foreach($tarifler as $tarif) {
                setup_postdata(get_post($tarif['id']));
                get_template_part('template-parts/recipe-card');
            }
            echo '</div>';
        } else {
            echo '<p>Seçtiğiniz malzemelerle uygun tarif bulunamadı.</p>';
        }
    }
    ?>
</div>

<section class="homepage-section">
    <h2>Popüler Tarifler</h2>
    <div class="recipe-slider">
        <?php
        $populer = new WP_Query([
            'post_type' => 'tarif',
            'posts_per_page' => 6,
            'meta_key' => 'fav_count',
            'orderby' => 'meta_value_num',
            'order' => 'DESC'
        ]);
        while($populer->have_posts()): $populer->the_post();
            get_template_part('template-parts/recipe-card');
        endwhile; wp_reset_postdata();
        ?>
    </div>
</section>

<section class="homepage-section">
    <h2>Son Eklenen Tarifler</h2>
    <div class="block-recipes-list">
        <?php
        $son = new WP_Query([
            'post_type' => 'tarif',
            'posts_per_page' => 6,
            'orderby' => 'date',
            'order' => 'DESC'
        ]);
        while($son->have_posts()): $son->the_post();
            get_template_part('template-parts/recipe-card');
        endwhile; wp_reset_postdata();
        ?>
    </div>
</section>

<section class="homepage-section">
    <h2>Kategoriler</h2>
    <div class="category-tags">
        <?php
        $cats = get_terms(['taxonomy'=>'yemek_turu','hide_empty'=>false]);
        foreach($cats as $cat) {
            echo '<a href="'.get_term_link($cat).'" class="cat-tag">'.$cat->name.'</a>';
        }
        ?>
    </div>
</section>

<?php get_footer(); ?>