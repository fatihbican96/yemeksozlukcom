<article class="recipe-detail" itemscope itemtype="https://schema.org/Recipe">
    <h1 itemprop="name"><?php the_title(); ?></h1>
    <meta itemprop="mainEntityOfPage" content="<?php the_permalink(); ?>" />

    <?php
    // Kapak fotoğrafı
    if(has_post_thumbnail()) {
        $kapak_url = get_the_post_thumbnail_url(get_the_ID(), 'large');
        $kapak_aciklama = get_post_meta(get_the_ID(), 'kapak_aciklama', true) ?: get_the_title();
        echo '<figure><img src="'.esc_url($kapak_url).'" alt="'.esc_attr($kapak_aciklama).'" itemprop="image" style="max-width:400px;width:100%;border-radius:10px"><figcaption>'.esc_html($kapak_aciklama).'</figcaption></figure>';
    }
    ?>

    <div class="recipe-meta" style="margin:15px 0;">
        <span><b>Kaç Kişilik:</b> <span itemprop="recipeYield"><?php echo esc_html(get_post_meta(get_the_ID(),'kac_kisilik',true)); ?></span></span> &nbsp;|&nbsp;
        <span><b>Hazırlık:</b> <span itemprop="prepTime" content="PT<?php echo intval(get_post_meta(get_the_ID(),'hazir_sure',true)); ?>M"><?php echo esc_html(get_post_meta(get_the_ID(),'hazir_sure',true)); ?> dk</span></span> &nbsp;|&nbsp;
        <span><b>Pişirme:</b> <span itemprop="cookTime" content="PT<?php echo intval(get_post_meta(get_the_ID(),'pisirme_sure',true)); ?>M"><?php echo esc_html(get_post_meta(get_the_ID(),'pisirme_sure',true)); ?> dk</span></span>
    </div>

    <!-- Kategori(ler) -->
    <div class="recipe-taxonomies" style="margin:10px 0;">
        <?php
        $terms = get_the_terms(get_the_ID(), 'yemek_turu');
        if ($terms && !is_wp_error($terms)) {
            echo '<b>Kategori:</b> ';
            $tmp = [];
            foreach ($terms as $term) {
                $tmp[] = '<a href="'.esc_url(get_term_link($term)).'">'.esc_html($term->name).'</a>';
            }
            echo implode(', ', $tmp);
        }
        ?>
    </div>

    <!-- Yazar Bilgisi -->
    <div class="recipe-author-info" style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
        <?php echo get_avatar(get_the_author_meta('ID'), 48); ?>
        <div>
            <span style="font-weight:bold;">Tarif Sahibi:</span>
            <a href="<?php echo get_author_posts_url(get_the_author_meta('ID')); ?>" itemprop="author">
                <?php the_author(); ?>
            </a>
        </div>
    </div>

    <!-- PUANLAMA -->
    <?php
    list($avg, $count) = ys_get_recipe_rating(get_the_ID());
    $user_vote = is_user_logged_in() ? ys_user_rated_recipe(get_the_ID()) : 0;
    ?>
    <div class="ys-recipe-rating" data-post="<?php the_ID(); ?>" style="margin:16px 0 12px 0;">
        <div class="ys-stars" style="font-size:2em; color:#ffc107; cursor:pointer;">
            <?php for($i=1;$i<=5;$i++): ?>
                <span class="ys-star<?php if($user_vote>0 && $i<=$user_vote) echo ' rated'; ?>" data-rate="<?php echo $i; ?>" style="margin-right:2px;">
                    &#9733;
                </span>
            <?php endfor; ?>
        </div>
        <div class="ys-rating-info" style="font-size:0.95em; color:#666;">
            <?php if($avg>0): ?>
                Ortalama: <b><?php echo $avg; ?></b> / 5, Oy: <b><?php echo $count; ?></b>
            <?php else: ?>
                Henüz oy yok.
            <?php endif; ?>
            <?php if($user_vote): ?> <span style="color:green;">(Oy verdiniz)</span> <?php endif; ?>
        </div>
    </div>

    <?php if($giris = get_post_meta(get_the_ID(),'giris',true)): ?>
        <div class="recipe-intro" style="background:#fffbe7;padding:12px 18px;border-radius:8px;margin-bottom:16px;">
            <?php echo esc_html($giris); ?>
        </div>
    <?php endif; ?>

    <h2>Malzemeler</h2>
    <?php
    $malzeme_meta = get_post_meta(get_the_ID(), 'malzemeler', true);
    if(is_array($malzeme_meta)) {
        $malzemeler = $malzeme_meta;
    } else {
        $malzemeler = array_filter(array_map('trim', explode("\n", (string)$malzeme_meta)));
    }
    ?>
    <ul>
        <?php foreach($malzemeler as $malzeme): ?>
            <li itemprop="recipeIngredient"><?php echo esc_html($malzeme); ?></li>
        <?php endforeach; ?>
    </ul>

    <h2>Nasıl Yapılır?</h2>
    <?php
    $adimlar_meta = get_post_meta(get_the_ID(), 'adimlar', true);
    if(is_array($adimlar_meta) && count($adimlar_meta)) {
        $adimlar = $adimlar_meta;
    } else {
        $content = get_the_content(null, false, get_the_ID());
        $adimlar = array_filter(array_map('trim', explode("\n", $content)));
    }
    ?>
    <ol itemprop="recipeInstructions">
        <?php foreach($adimlar as $adim): ?>
            <li itemprop="HowToStep"><?php echo esc_html($adim); ?></li>
        <?php endforeach; ?>
    </ol>

    <?php if($puf = get_post_meta(get_the_ID(),'puf',true)): ?>
        <h3>Püf Noktaları / Pişirme & Servis Önerileri</h3>
        <div class="recipe-tip" style="background:#e7fbe7;padding:10px 15px;border-radius:8px;margin-bottom:12px;">
            <?php echo nl2br(esc_html($puf)); ?>
        </div>
    <?php endif; ?>

    <?php if($video = get_post_meta(get_the_ID(),'video',true)): ?>
        <h3>Video</h3>
        <div class="recipe-video">
            <a href="<?php echo esc_url($video); ?>" target="_blank" rel="noopener">Tarif Videosunu İzle</a>
        </div>
    <?php endif; ?>

    <!-- Etiketler -->
    <div class="recipe-tags" style="margin:20px 0;">
        <?php
        $tags = get_the_tags(get_the_ID());
        if($tags) {
            echo '<b>Etiketler:</b> ';
            foreach($tags as $tag) {
                echo '<a href="'.esc_url(get_tag_link($tag->term_id)).'" style="background:#f2dfdf;color:#c72828;padding:2px 10px;border-radius:8px;font-size:0.97em;text-decoration:none;margin-right:5px;">'.esc_html($tag->name).'</a>';
            }
        }
        ?>
    </div>

    <meta itemprop="author" content="<?php echo esc_attr(get_the_author()); ?>">
    <meta itemprop="datePublished" content="<?php echo get_the_date('c'); ?>">
    <meta itemprop="dateModified" content="<?php echo get_the_modified_date('c'); ?>">

    <!-- Benzer Tarifler -->
    <?php
    $related_args = array(
        'post_type' => 'tarif',
        'posts_per_page' => 3,
        'post__not_in' => [get_the_ID()],
        'orderby' => 'rand',
        'tax_query' => array()
    );
    if($terms && !is_wp_error($terms)) {
        $related_args['tax_query'][] = array(
            'taxonomy' => 'yemek_turu',
            'field' => 'term_id',
            'terms' => wp_list_pluck($terms, 'term_id'),
        );
    }
    $related = new WP_Query($related_args);
    if($related->have_posts()):
    ?>
        <div class="related-recipes" style="margin-top:30px;">
            <h3>Benzer Tarifler</h3>
            <div class="block-recipes-list">
                <?php while($related->have_posts()): $related->the_post();
                    get_template_part('template-parts/recipe-card');
                endwhile; wp_reset_postdata(); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Soru & Cevap Alanı -->
    <div class="recipe-questions" style="margin-top:44px;">
        <h3>Sor & Cevapla</h3>
        <?php echo do_shortcode('[ys_soru_cevap]'); ?>
    </div>

    <!-- WordPress Yorumları -->
    <div class="recipe-comments" style="margin-top:38px;">
        <h3>Yorumlar</h3>
        <?php
        comments_template();
        ?>
    </div>

    <script type="application/ld+json">
    {
      "@context": "https://schema.org/",
      "@type": "Recipe",
      "name": "<?php the_title(); ?>",
      "image": "<?php echo esc_url(get_the_post_thumbnail_url(get_the_ID(), 'large')); ?>",
      "author": {
        "@type": "Person",
        "name": "<?php the_author(); ?>"
      },
      "datePublished": "<?php echo get_the_date('c'); ?>",
      "description": "<?php echo esc_attr(get_post_meta(get_the_ID(), 'tarif_aciklama', true)); ?>",
      "prepTime": "PT<?php echo intval(get_post_meta(get_the_ID(),'hazir_sure',true)); ?>M",
      "cookTime": "PT<?php echo intval(get_post_meta(get_the_ID(),'pisirme_sure',true)); ?>M",
      "recipeYield": "<?php echo esc_attr(get_post_meta(get_the_ID(),'kac_kisilik',true)); ?>",
      "recipeIngredient": <?php echo json_encode(get_post_meta(get_the_ID(), 'malzemeler', true)); ?>,
      "recipeInstructions": <?php echo json_encode(get_post_meta(get_the_ID(), 'adimlar', true)); ?>
    }
    </script>
</article>