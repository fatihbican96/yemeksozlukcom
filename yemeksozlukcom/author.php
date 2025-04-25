<?php get_header(); ?>
<div class="profile-main">
    <?php
    $user = get_queried_object();
    $user_id = $user->ID;
    $user_info = get_userdata($user_id);
    ?>
    <div class="profile-header">
        <div class="profile-avatar">
            <?php echo get_avatar($user_id, 100); ?>
        </div>
        <div class="profile-info">
            <h2><?php echo esc_html($user_info->display_name); ?></h2>
            <p><b>Kullanıcı adı:</b> <?php echo esc_html($user_info->user_login); ?></p>
            <p><b>Kayıt tarihi:</b> <?php echo date_i18n('j F Y', strtotime($user_info->user_registered)); ?></p>
            <?php
            $tarif_count = count_user_posts($user_id, 'tarif');
            echo '<p><b>Eklediği Tarif Sayısı:</b> ' . $tarif_count . '</p>';
            ?>
        </div>
    </div>
    <div class="profile-tarifler">
        <h3><?php echo esc_html($user_info->display_name); ?> tarafından eklenen tarifler</h3>
        <div class="block-recipes-list">
        <?php
        $tarifler = new WP_Query([
            'post_type' => 'tarif',
            'posts_per_page' => 12,
            'author' => $user_id,
        ]);
        if($tarifler->have_posts()):
            while($tarifler->have_posts()): $tarifler->the_post();
                get_template_part('template-parts/recipe-card');
            endwhile; wp_reset_postdata();
        else:
            echo '<p>Henüz tarif eklememiş.</p>';
        endif;
        ?>
        </div>
    </div>
</div>
<?php get_footer(); ?>