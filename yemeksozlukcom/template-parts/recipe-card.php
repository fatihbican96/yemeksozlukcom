<div class="recipe-card" itemscope itemtype="https://schema.org/Recipe">
    <a href="<?php the_permalink(); ?>" itemprop="url">
        <?php if(has_post_thumbnail()) { the_post_thumbnail('medium', ['itemprop'=>'image']); } else { ?>
            <img src="https://via.placeholder.com/300x200?text=Tarif" alt="<?php the_title_attribute(); ?>" itemprop="image" />
        <?php } ?>
    </a>
    <?php
    // Kategori(ler)
    $terms = get_the_terms(get_the_ID(), 'yemek_turu');
    if($terms && !is_wp_error($terms)){
        echo '<span class="tarif-cat">';
        $cats = [];
        foreach($terms as $term) {
            $cats[] = '<a href="'.esc_url(get_term_link($term)).'">'.esc_html($term->name).'</a>';
        }
        echo implode(', ', $cats);
        echo '</span>';
    }
    ?>
    <a class="rcard-title" href="<?php the_permalink(); ?>" itemprop="name"><?php the_title(); ?></a>
    <div class="rcard-meta">
        <span class="rcard-author">
            <a href="<?php echo get_author_posts_url(get_the_author_meta('ID')); ?>" itemprop="author"><?php the_author(); ?></a>
        </span>
        <?php echo function_exists('ys_fav_button') ? ys_fav_button(get_the_ID()) : ''; ?>
    </div>
</div>