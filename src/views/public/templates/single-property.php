<?php
/*
    Template name: single-property
*/

global $cpd;

if (!is_admin() && !current_user_can('administrator') && (!isset($_SESSION['mojo_owner_id']))) {
    wp_redirect(home_url());
    exit;
}

?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>

    <?php if ($cpd): ?>
        <title><?php echo $cpd->getName(); ?> - Mojo Sharing</title>
    <?php else: ?>
        <title>Mojo Sharing</title>
    <?php endif; ?>
</head>

<body>

    <main class="mojo_plugin mojo_sproperty single_property">
        <?php get_mojo_header(); ?>
        <div class="mojo_sproperty-banner">
            <?php if ($cpd && !empty($cpd->getThumbnail())): ?>
                <img src="<?php echo $cpd->getThumbnail(); ?>">
            <?php else: ?>
                <img src="<?php echo MEDIA; ?>/banner.webp">
            <?php endif; ?>
        </div>
        <div class="mojo_sproperty-body">
            <div class="mojo_container">
                <?php if ($cpd): ?>
                    <div class="mojo_sproperty-actions">
                        <?php get_booking_calendar($cpd->getId()); ?>
                        <?php get_back_dashboard(); ?>
                    </div>
                    <div class="mojo_sproperty-grid">
                        <div>
                            <?php
                            $title = $cpd->getTitle();
                            if (!empty($title)) {
                                echo '<div class="mojo_sproperty-title"><h1>' . $title . '</h1></div>';
                            } else {
                                echo '<div class="mojo_sproperty-title"><h1>' . $cpd->name . '</h1></div>';
                            }

                            $content = $cpd->getDescription();
                            if (!empty($content)) {
                                echo '<div class="mojo_sproperty-content">';
                                echo wpautop(wp_kses_post($content));
                                echo '</div>';
                            }
                            ?>
                        </div>
                        <div>
                            <?php
                            $gallery = $cpd->getGallery();
                            $gallery = $gallery ? json_decode($gallery, true) : [];

                            if (!empty($gallery)) {
                                echo '<div class="mojo_sproperty-thumbs">
                                        <div class="splide" role="group" id="thumbs">
                                            <div class="splide__track">
                                                <ul class="splide__list">';
                                foreach ($gallery as $image) {
                                    echo '<li class="splide__slide">
                                                        <img src="' . $image . '">
                                                    </li>';
                                }
                                echo '</ul>
                                            </div>
                                        </div>
                                    </div>';
                            }
                            ?>
                            <?php
                            $key_features = $cpd->getKeyFeatures();
                            if (!empty($key_features)) {
                                $key_features = array_map('trim', explode(',', $key_features));
                                $key_features = array_slice($key_features, 0, 6);
                                $chunks = array_chunk($key_features, 3);

                                echo '<div class="mojo_sproperty-keyfeatures">
                                    <h2>KEY FEATURES</h2>
                                    <div class="mojo_sproperty-features">';
                                foreach ($chunks as $group) {
                                    echo '<div class="feature-group">';
                                    foreach ($group as $feature) {
                                        echo '<div class="feature-item">' . esc_html($feature) . '</div>';
                                    }
                                    echo '</div>';
                                }
                                echo '</div>
                                </div>';
                            }
                            ?>
                            <div class="mojo_sproperty-checking mojo_sproperty-quote">
                                <h2>Fill out the form to receive more information on the booking!</h2>

                                <form action="POST" id="send_quote" class="form send-quote for-quote">
                                    <?php
                                    echo '<input type="text" name="property" value="' . $cpd->getCode() . ' - ' . $cpd->getName() . '" style="display:none">';
                                    ?>
                                    <?php if (isset($_SESSION['mojo_owner_name'])): ?>
                                        <input type="text" name="owner" value="<?php echo $_SESSION['mojo_owner_name']; ?>" style="display:none">
                                    <?php endif; ?>

                                    <div class="form-control">
                                        <label for="daterange">Check-in and Check-out dates</label>
                                        <div class="form-input">
                                            <input autocomplete="false" type="text" name="daterange" id="daterange" value="">
                                            <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <g clip-path="url(#clip0_233_2650)">
                                                    <path d="M4.36657 3.13935C4.69993 3.13935 4.97042 2.86914 4.97042 2.53575V0.603562C4.97042 0.270211 4.69993 0 4.36657 0C4.03322 0 3.7627 0.270211 3.7627 0.603598V2.53578C3.7627 2.86914 4.03322 3.13935 4.36657 3.13935Z" fill="#33475B" />
                                                    <path d="M9.19637 3.13935C9.52972 3.13935 9.80021 2.86914 9.80021 2.53575V0.603562C9.80018 0.270211 9.52969 0 9.19637 0C8.86298 0 8.59277 0.270211 8.59277 0.603598V2.53578C8.59277 2.86914 8.86298 3.13935 9.19637 3.13935Z" fill="#33475B" />
                                                    <path d="M15.2088 1.57849H15.1134V2.53576C15.1134 3.13475 14.6261 3.6223 14.0266 3.6223C13.4276 3.6223 12.9398 3.13472 12.9398 2.53576V1.57849H10.2836V2.53576C10.2836 3.13475 9.79602 3.6223 9.19681 3.6223C8.59782 3.6223 8.11024 3.13472 8.11024 2.53576V1.57849H5.45383V2.53576C5.45383 3.13475 4.96625 3.6223 4.36701 3.6223C3.76802 3.6223 3.28019 3.13472 3.28019 2.53576V1.57849H3.18534C1.88196 1.57849 0.825195 2.63522 0.825195 3.93864V15.6399C0.825195 16.9432 1.88192 18 3.18534 18H15.2088C16.5119 18 17.569 16.9433 17.569 15.6399V3.93864C17.569 2.63525 16.5119 1.57849 15.2088 1.57849ZM16.6025 15.4607C16.6025 16.3296 15.8983 17.0341 15.0294 17.0341H3.36474C2.49582 17.0341 1.79115 16.3296 1.79115 15.4607V6.69664H16.6025V15.4607Z" fill="#33475B" />
                                                    <path d="M14.0267 3.13935C14.3601 3.13935 14.6306 2.86914 14.6306 2.53575V0.603562C14.6306 0.270211 14.3601 0 14.0267 0C13.6933 0 13.4229 0.270211 13.4229 0.603598V2.53578C13.4229 2.86914 13.6933 3.13935 14.0267 3.13935Z" fill="#33475B" />
                                                </g>
                                                <defs>
                                                    <clipPath id="clip0_233_2650">
                                                        <rect width="18" height="18" fill="white" />
                                                    </clipPath>
                                                </defs>
                                            </svg>
                                        </div>
                                    </div>
                                    <button type="submit">REQUEST FOR QUOTE</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="mojo_sproperty-actions">
                        <p>Property not found.</p>
                        <?php get_back_dashboard(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php wp_footer(); ?>

    <?php if ($cpd && !empty($cpd->getGallery())): ?>
        <script>
            if (document.querySelector('#thumbs')) {
                new Splide('#thumbs', {
                    type: 'loop',
                    autoplay: true,
                    interval: 5000,
                    arrows: true,
                    pagination: true,
                    perPage: 1,
                    pauseOnHover: false,
                    pauseOnFocus: false,
                    perMove: 1
                }).mount();
            }
        </script>
    <?php endif; ?>

</body>

</html>