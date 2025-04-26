<?php get_header(); ?>
<main id="main" class="site-main">
    <h1>Son Eklenen Tarifler</h1>
    <div id="tarifler-listesi" class="tarifler-listesi">
        <?php
        $args = [
            'post_type' => 'tarif',
            'posts_per_page' => 12,
            'paged' => 1
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
        ?>
    </div>
    <?php if ($tarif_query->max_num_pages > 1): ?>
        <button id="daha-fazla-tarif" data-sayfa="1">Daha fazlası için tıklayınız</button>
    <?php endif; ?>
</main>
<?php get_footer(); ?>