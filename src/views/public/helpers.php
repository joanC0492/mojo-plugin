<?php

function transform_date($fecha)
{
    $date = DateTime::createFromFormat('Y-m-d', $fecha);
    if ($date) {
        return $date->format('d/m/y');
    } else {
        return '';
    }
}
