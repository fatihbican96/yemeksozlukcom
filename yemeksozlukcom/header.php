<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php
    if (is_singular()) {
        $meta_title = get_post_meta(get_the_ID(), '_ys_meta_title', true);
        $meta_desc  = get_post_meta(get_the_ID(), '_ys_meta_desc', true);
        $meta_keys  = get_post_meta(get_the_ID(), '_ys_meta_keys', true);
    ?>
        <title><?php echo $meta_title ? esc_html($meta_title) : get_the_title() . ' - Yemek Sözlük'; ?></title>
        <?php if ($meta_desc): ?>
            <meta name="description" content="<?php echo esc_attr($meta_desc); ?>">
        <?php endif; ?>
        <?php if ($meta_keys): ?>
            <meta name="keywords" content="<?php echo esc_attr($meta_keys); ?>">
        <?php endif; ?>
    <?php } else { ?>
        <title><?php bloginfo('name'); ?><?php wp_title('|'); ?></title>
    <?php } ?>
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
    <!-- Üst ince bar: Sosyal Medya -->
    <div class="header-top">
        <div class="container" style="display:flex;justify-content:flex-end;align-items:center;gap:14px;">
            <a href="https://facebook.com/" target="_blank" rel="noopener" title="Facebook"><i class="fab fa-facebook-f"></i></a>
            <a href="https://twitter.com/" target="_blank" rel="noopener" title="Twitter"><i class="fab fa-twitter"></i></a>
            <a href="https://instagram.com/" target="_blank" rel="noopener" title="Instagram"><i class="fab fa-instagram"></i></a>
            <a href="mailto:info@yemeksozluk.com" title="E-posta"><i class="fas fa-envelope"></i></a>
            <button id="theme-toggle" title="Açık/Kapalı Mod" style="background:none;border:none;cursor:pointer;font-size:1.2em;color:#c72828;">
                <i id="theme-icon" class="fas fa-moon"></i>
            </button>
        </div>
    </div>
    <!-- Alt kalın bar: Logo ve Menü -->
    <div class="header-main">
        <div class="container" style="display:flex;justify-content:space-between;align-items:center;">
            <div style="display:flex;align-items:center;gap:10px;">
                <a href="<?php echo home_url('/'); ?>">
                    <img src="https://via.placeholder.com/40x40?text=🍳" alt="Logo" style="vertical-align:middle;border-radius:8px;" />
                </a>
                <span style="font-size:2rem;font-weight:bold;vertical-align:middle;">Yemek Sözlük</span>
            </div>
            <nav class="main-nav">
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
    <form id="canli-arama-form" action="<?php echo home_url('/'); ?>" method="get" autocomplete="off" style="position:relative;">
        <input type="text" id="canli-arama" name="s" placeholder="Tarif veya içerik ara..." />
        <div id="arama-oneriler" style="position:absolute;top:100%;left:0;width:100%;background:#fff;z-index:99;box-shadow:0 2px 8px #0001;display:none;"></div>
    </form>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const btn = document.getElementById('theme-toggle');
  const icon = document.getElementById('theme-icon');
  // Tema durumunu localStorage'dan al
  if(localStorage.getItem('theme') === 'dark') {
    document.documentElement.classList.add('dark-mode');
    icon.classList.remove('fa-moon');
    icon.classList.add('fa-sun');
  }
  btn.addEventListener('click', function() {
    document.documentElement.classList.toggle('dark-mode');
    const isDark = document.documentElement.classList.contains('dark-mode');
    icon.classList.toggle('fa-moon', !isDark);
    icon.classList.toggle('fa-sun', isDark);
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
  });

  var input = document.getElementById('canli-arama');
  var box = document.getElementById('arama-oneriler');
  if(input && box) {
    input.addEventListener('keyup', function() {
      var val = this.value;
      if(val.length < 2) { box.style.display='none'; box.innerHTML=''; return; }
      var xhr = new XMLHttpRequest();
      xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
      xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
      xhr.onload = function() {
        box.innerHTML = this.responseText;
        box.style.display = 'block';
      };
      xhr.send('action=canli_arama&q=' + encodeURIComponent(val));
    });
    document.addEventListener('click', function(e){
      if(!box.contains(e.target) && e.target!==input) box.style.display='none';
    });
  }
});
</script>
<?php wp_footer(); ?>
</body>
</html>