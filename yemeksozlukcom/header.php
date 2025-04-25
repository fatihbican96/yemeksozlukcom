<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php wp_title('|', true, 'right'); bloginfo('name'); ?></title>
    <meta name="description" content="<?php echo trim(wp_strip_all_tags(get_the_excerpt())) ?: get_bloginfo('description'); ?>">
    <link rel="canonical" href="<?php echo esc_url(get_permalink()); ?>" />
    <!-- OpenGraph & Twitter Card -->
    <meta property="og:title" content="<?php the_title(); ?>" />
    <meta property="og:description" content="<?php echo trim(wp_strip_all_tags(get_the_excerpt())); ?>" />
    <meta property="og:type" content="article" />
    <meta property="og:url" content="<?php echo esc_url(get_permalink()); ?>" />
    <?php if(has_post_thumbnail()) {
        $image = wp_get_attachment_image_src(get_post_thumbnail_id(), 'large');
        if ($image && !is_wp_error($image)) {
            echo '<meta property="og:image" content="' . esc_url($image[0]) . '" />';
        }
    } ?>
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?php the_title(); ?>" />
    <meta name="twitter:description" content="<?php echo trim(wp_strip_all_tags(get_the_excerpt())); ?>" />
    <?php wp_head(); ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body <?php body_class(); ?>>
<div class="header">
    <div class="container">
        <div style="display:flex;justify-content:space-between;align-items:center;">
            <div>
                <a href="<?php echo home_url('/'); ?>"><img src="https://via.placeholder.com/40x40?text=🍳" alt="Logo" style="vertical-align:middle;border-radius:8px;margin-right:8px;" /></a>
                <span style="font-size:2rem;font-weight:bold;vertical-align:middle;">Yemek Sözlük</span>
            </div>
            <nav>
                <a href="<?php echo home_url('/'); ?>">Ana Sayfa</a>
                <a href="<?php echo home_url('/tarif/'); ?>">Tarifler</a>
                <a href="<?php echo home_url('/kategoriler/'); ?>">Kategoriler</a>
                <a href="<?php echo home_url('/blog/'); ?>">Blog</a>
                <a href="<?php echo home_url('/sozluk/'); ?>">Sözlük</a>
                <a href="<?php echo home_url('/tarif-ekle/'); ?>">Tarif Ekle</a>
                <?php if(is_user_logged_in()): ?>
                    <a href="<?php echo home_url('/kendi-tariflerim/'); ?>">Tariflerim</a>
                    <a href="<?php echo home_url('/favorilerim/'); ?>">Favorilerim</a>
                    <a href="<?php echo wp_logout_url(home_url()); ?>">Çıkış</a>
                <?php else: ?>
                    <a href="<?php echo wp_login_url(); ?>">Giriş/Kayıt</a>
                <?php endif; ?>
                <a href="<?php echo home_url('/iletisim/'); ?>">İletişim</a>
            </nav>
        </div>
    </div>
</div>
<div class="container">