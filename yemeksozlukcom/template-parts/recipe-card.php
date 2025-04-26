<div class="tarif-karti">
    <a href="<?php the_permalink(); ?>">
        <div class="tarif-gorsel">
            <?php if (has_post_thumbnail()) the_post_thumbnail('medium'); ?>
        </div>
        <div class="tarif-bilgi">
            <h3 class="tarif-baslik"><?php the_title(); ?></h3>
            <div class="tarif-detay">
                <?php
                $kisi = get_post_meta(get_the_ID(), 'kisi', true);
                $hazirlik = get_post_meta(get_the_ID(), 'hazirlik', true);
                $pisirme = get_post_meta(get_the_ID(), 'pisirme', true);
                ?>
                <span><?php echo esc_html($kisi); ?> kişilik</span>,
                <span><?php echo esc_html($hazirlik); ?> dk Hazırlık</span>,
                <span><?php echo esc_html($pisirme); ?> dk Pişirme</span>
            </div>
        </div>
    </a>
    <div class="tarif-footer">
        <span class="tarif-yazar">
            <?php
            $author_id = get_post_field('post_author', get_the_ID());
            echo get_the_author_meta('display_name', $author_id);
            ?>
        </span>
        <span class="tarif-favori">
            <i class="fa fa-heart"></i>
            <?php
            $favori = get_post_meta(get_the_ID(), 'favori', true);
            echo $favori ? intval($favori) : 0;
            ?>
        </span>
        <span class="tarif-yorum">
            <i class="fa fa-comment"></i>
            <?php echo get_comments_number(); ?>
        </span>
    </div>
</div>