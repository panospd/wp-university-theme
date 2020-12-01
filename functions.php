<?php
    require get_theme_file_path('/inc/search-route.php');
    require get_theme_file_path('/inc/like-route.php');

    function pageBanner($args = null) {
        // php logic will live here 
        $args['title'] = $args['title'] ?? get_the_title();
        $args['subtitle'] = $args['subtitle'] ?? get_field('page_banner_subtitle');

        if (!$args['photo']) {
            if (get_field('page_banner_background_image') AND !is_archive() AND !is_home() ) {
              $args['photo'] = get_field('page_banner_background_image')['sizes']['pageBanner'];
            } else {
              $args['photo'] = get_theme_file_uri('/images/ocean.jpg');
            }
        }
        ?>
        <div class="page-banner">
            <div class="page-banner__bg-image" style="background-image: url(<?php
                echo $args['photo']; ?>)"></div>
            <div class="page-banner__content container container--narrow">
                <h1 class="page-banner__title"><?php echo $args['title']; ?></h1>
                <div class="page-banner__intro">
                    <p><?php echo $args['subtitle']; ?></p>
                </div>
            </div>  
        </div>
   <?php }

    function university_files() {
        wp_enqueue_style('custom-google-fonts', 'https://fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i|Roboto:100,300,400,400i,700,700i');
        wp_enqueue_style('font-awesome', 'https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css');
        
        wp_enqueue_script('google-map', '//maps.googleapis.com/maps/api/js?key=AIzaSyBbKBS6CUm6NoaBwd2cErrOp0QszKGPmPM', NULL, '1.0', true);

        if(strstr($_SERVER['SERVER_NAME'], "localhost")) {
            wp_enqueue_script('main-university-js', 'http://localhost:3000/bundled.js', NULL, '1.0', true);
        }else {
            wp_enqueue_script('our-vendors-js', get_theme_file_uri('/bundled-assets/vendors~scripts.9f081058b0665e3bdd11.js'), NULL, '1.0', true);
            wp_enqueue_script('main-university-js', get_theme_file_uri('/bundled-assets/scripts.14918edb62d8d205fd94.js'), NULL, '1.0', true);
            wp_enqueue_style('our-main-styles', get_theme_file_uri('/bundled-assets/styles.14918edb62d8d205fd94.css'));
        }

        wp_localize_script('main-university-js', 'universityData', array(
            'root_url' => get_site_url(),
            'nonce' => wp_create_nonce("wp_rest")
        ));
    }

    add_action('wp_enqueue_scripts', 'university_files');

    function university_features() {
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_image_size('professor-landscape', 400, 260, true);
        add_image_size('professor-portrait', 480, 650, true);
        add_image_size('pageBanner', 1500, 350, true);
    }

    function university_adjust_queries($query) {
        if(!is_admin() AND is_post_type_archive('program') AND $query->is_main_query()) {
            $query->set('posts_per_page', -1);
            $query->set('orderby', 'title');
            $query->set('order', 'ASC');
        }

        if(!is_admin() AND is_post_type_archive('campus') AND $query->is_main_query()) {
            $query->set('posts_per_page', -1);
        }

        if (!is_admin() AND is_post_type_archive('event') AND $query->is_main_query()) {
            $today = date('Ymd');

            $query->set('meta_key', 'event_date');
            $query->set('orderby', 'meta_value_num');
            $query->set('order', 'ASC');
            $query->set('meta_query', array(
                array(
                  'key' => 'event_date',
                  'compare' => '>=',
                  'value' => $today,
                  'type' => 'numeric'
                )
            ));
        }
    }

    function universityMapKey($api) {
        $api['key'] = 'AIzaSyBbKBS6CUm6NoaBwd2cErrOp0QszKGPmPM';
        return $api;
    }

    function university_custom_rest() {
        register_rest_field('post', 'authorName', array(
            'get_callback' => function() {
                return get_the_author();
            }
        ));

        register_rest_field('note', 'userNoteCount', array(
            'get_callback' => function() {
                return count_user_posts(get_current_user_id(), "note");
            }
        ));
    }

    add_action('after_setup_theme', 'university_features');
    add_action('pre_get_posts', 'university_adjust_queries');
    add_action('rest_api_init', 'university_custom_rest');

    add_filter('acf/fields/google_map/api', 'universityMapKey');

    // Redirect subscriber accounts out of admin and onto homepage

    function redirectSubsToFrontend() {
        $ourCurrentUser = wp_get_current_user();
        if(count($ourCurrentUser->roles) == 1 AND $ourCurrentUser->roles[0] == 'subscriber') {
            wp_redirect(site_url('/'));
            exit;
        } 
    }

    function noSubsAdminBar() {
        $ourCurrentUser = wp_get_current_user();
        if(count($ourCurrentUser->roles) == 1 AND $ourCurrentUser->roles[0] == 'subscriber') {
            show_admin_bar(false);
        } 
    }

    add_action('admin_init', 'redirectSubsToFrontend');

    add_action('wp_loaded', 'noSubsAdminBar');

    // Customize login screen
    function ourHeaderUrl() {
        return esc_url(site_url('/'));
    }

    add_filter('login_headerurl', 'ourHeaderUrl');

    // load css in login screen
    function ourLoginCss() {
        wp_enqueue_style('our-main-styles', get_theme_file_uri('/bundled-assets/styles.14918edb62d8d205fd94.css'));
        wp_enqueue_style('custom-google-fonts', 'https://fonts.googleapis.com/css?family=Roboto+Condensed:300,300i,400,400i,700,700i|Roboto:100,300,400,400i,700,700i');
    }

    add_action('login_enqueue_scripts', 'ourLoginCss');

    // Change header text of login page
    function my_login_logo_url_title() {
        return get_bloginfo('name');
    }
    add_filter( 'login_headertitle', 'my_login_logo_url_title' );

    // Force note posts to be private
    function makeNotePrivate($note, $postarr) {
        if ($note['post_type'] == 'note') {
            if(count_user_posts(get_current_user_id(), 'note') > 4 AND !$postarr["ID"]) {
                die("You have reached your note limit.");
            }

            $note['post_content'] = sanitize_textarea_field($note['post_content']);
            $note['post_title'] = sanitize_text_field($note['post_title']);
        }

        if ($note['post_type'] == 'note' AND $note['post_status'] != 'trash') {
            $note['post_status'] = 'private';
        }

        return $note;
    }

    add_filter('wp_insert_post_data', 'makeNotePrivate', 10, 2);


    function ignoreRecentFiles($exclude_filters) {
        $exclude_filters[] = "themes/fictional-university-theme/node_modules";
        return $exclude_filters;
    }
     
    add_filter("ai1wm_exclude_content_from_export", "ignoreRecentFiles");
?>