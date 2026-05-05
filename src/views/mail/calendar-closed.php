<?php
// Variables esperadas:
// - $property_name (string)
// - $year (int)               -> año que quieres renderizar
// - $bookings (array)         -> cada booking con: start_date (Y-m-d), end_date (Y-m-d), owner_position (1..N)
// - $color_selected (string)  -> color fijo para PDF individual (opcional)
// - $colors_only (array)      -> colores por posición, indexados 0..N-1 (opcional)
// - $calendar_scope (string)  -> 'just_me' o 'rent' (opcional)
// - $legend_owners (array)    -> [['name' => string, 'color' => string], ...] solo PDF just_me (opcional)

$legend_owners = $legend_owners ?? [];

// Construye mapa fecha -> color
$booking_map = [];

foreach ($bookings as $booking) {
    $start = new DateTime($booking['start_date']);
    $end   = new DateTime($booking['end_date']);

    // owner_position viene como número 1..N
    $position = isset($booking['owner_position']) ? (int)$booking['owner_position'] : 0;

    // En scope "rent" el PDF va siempre en gris.
    if (($calendar_scope ?? 'rent') === 'rent') {
        $bg = '#D1D1D1';
    } elseif (!empty($colors_only) && is_array($colors_only) && $position > 0) {
        // En scope "just_me", usa color por owner_position.
        $idx = $position - 1;
        $bg  = $colors_only[$idx] ?? ($color_selected ?? '#D0D0D0');
    } else {
        $bg  = $color_selected ?? '#D0D0D0';
    }

    // Rellenar todos los días (extremos INCLUSIVOS)
    while ($start <= $end) {
        $date_str = $start->format('Y-m-d');
        $booking_map[$date_str] = $bg;
        $start->modify('+1 day');
    }
}

if (!function_exists('render_calendar_grid')) {
    function render_calendar_grid($year, $booking_map)
    {
        ob_start();
        echo '<table style="width: 100%; border-collapse: collapse;"><tr>';

        for ($month = 1; $month <= 12; $month++) {
            if ($month % 3 === 1 && $month !== 1) {
                echo '</tr><tr>';
            }

            $first_day  = new DateTime("$year-$month-01");
            $month_name = strtoupper($first_day->format('F'));

            echo '<td style="width: 33.33%; padding: 5px 4%;">';
            echo "<table border='0' cellspacing='0' cellpadding='0' style='width: 100%; border-collapse: collapse; font-size: 14px; border: none;'>";
            echo "<tr><th colspan='7' style='background: white; min-height: 36px; height: 36px;'>$month_name - $year</th></tr>";
            echo "<tr>
                <th style='color: #283d48; min-height: 36px; height: 36px;'>Sun</th>
                <th style='color: #283d48; min-height: 36px; height: 36px;'>Mon</th>
                <th style='color: #283d48; min-height: 36px; height: 36px;'>Tue</th>
                <th style='color: #283d48; min-height: 36px; height: 36px;'>Wed</th>
                <th style='color: #283d48; min-height: 36px; height: 36px;'>Thur</th>
                <th style='color: #283d48; min-height: 36px; height: 36px;'>Fri</th>
                <th style='color: #283d48; min-height: 36px; height: 36px;'>Sat</th>
            </tr>";

            $day            = clone $first_day;
            $first_weekday  = (int)$day->format('w');
            $days_in_month  = (int)$day->format('t');

            echo '<tr>';
            for ($i = 0; $i < 42; $i++) {
                $current_day = $i - $first_weekday + 1;

                if ($i > 0 && $i % 7 == 0) {
                    echo '</tr><tr>';
                }

                if ($i < $first_weekday || $current_day > $days_in_month) {
                    echo '<td style="min-height: 36px; height: 36px;"> </td>';
                } else {
                    $current_date = $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($current_day, 2, '0', STR_PAD_LEFT);
                    $bg = $booking_map[$current_date] ?? '';
                    if (empty($bg)) {
                        echo "<td style='background:white; min-height: 36px; height: 36px; text-align: center; vertical-align: middle; opacity: 0.4;'><b>$current_day</b></td>";
                    } else {
                        echo "<td style='background:$bg; color: #5a5c68; min-height: 36px; height: 36px; text-align: center; vertical-align: middle;'><b>$current_day</b></td>";
                    }
                }
            }
            echo '</tr>';
            echo '</table>';
            echo '</td>';
        }

        echo '</tr></table>';
        return ob_get_clean();
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        /* Registrar Poppins local (TTF) */
        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 400;
            /* Regular */
            src: url('assets/fonts/Poppins-Regular.ttf') format('truetype');
        }

        @font-face {
            font-family: 'Poppins';
            font-style: normal;
            font-weight: 700;
            /* Bold (no 600) */
            src: url('assets/fonts/Poppins-Bold.ttf') format('truetype');
        }

        body,
        * {
            font-family: 'Poppins', Helvetica, Arial, sans-serif;
        }

        body {
            padding: 16px;
        }

        h1 {
            color: #60C0A8;
            text-align: center;
            margin-bottom: 30px;
            margin-top: 30px;
            font-family: 'Poppins', Helvetica, Arial, sans-serif;
        }

        p {
            font-size: 14px;
        }

        img {
            display: block;
            width: 150px;
            object-fit: contain;
            object-position: center;
            text-align: center;
            margin-left: auto;
            margin-right: auto;
        }

        .legend {
            margin: 0 auto 30px auto;
            max-width: 100%;
        }

        .legend ul {
            list-style: none;
            padding: 0;
            margin: 0;
            text-align: center;
        }

        .legend li {
            display: inline-block;
            font-size: 13px;
            color: #283d48;
            margin: 0 10px 6px 0;
            white-space: nowrap;
            vertical-align: middle;
        }

        .legend-swatch {
            display: inline-block;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            vertical-align: middle;
            margin-right: 6px;
        }
    </style>
</head>

<body>
    <?php $logo_src = $MEDIA . '/logo.png'; ?>
    <?php $calendar_label = (($calendar_scope ?? 'rent') === 'just_me') ? 'Booked Dates' : 'Rent Dates'; ?>
    <div style="width:100%; text-align:center; margin:0 auto; margin-top:10px;">
        <img src="<?php echo $logo_src; ?>" width="160" style="display:inline-block;">
    </div>
    <h1>Mojo Sharing - <?php echo $property_name; ?> - <?php echo $calendar_label; ?> - <?php echo (int)$year; ?></h1>
    <?php if (($calendar_scope ?? 'rent') === 'just_me' && !empty($legend_owners)) : ?>
        <div class="legend">
            <ul>
                <?php foreach ($legend_owners as $legend_row) :
                    $swatch = htmlspecialchars((string) ($legend_row['color'] ?? '#D0D0D0'), ENT_QUOTES, 'UTF-8');
                    $owner_name = htmlspecialchars((string) ($legend_row['name'] ?? ''), ENT_QUOTES, 'UTF-8');
                ?>
                    <li>
                        <span class="legend-swatch" style="background-color: <?php echo $swatch; ?>;"></span>
                        <span><?php echo $owner_name; ?></span>
                    </li>
                <?php endforeach; ?>
                <li>
                    <span class="legend-swatch" style="background-color: #CCCCCC;"></span>
                    <span>For Rental</span>
                </li>
            </ul>
        </div>
    <?php endif; ?>
    <?= render_calendar_grid((int)$year, $booking_map); ?>
</body>

</html>