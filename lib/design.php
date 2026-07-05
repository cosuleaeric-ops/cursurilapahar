<?php

function clp_design_color_fields(): array {
    return [
        'color_bg'         => ['label' => 'Fundal principal',       'default' => '#0D0D0D'],
        'color_accent'     => ['label' => 'Culoare accent',          'default' => '#C9A84C'],
        'color_text'       => ['label' => 'Culoare text',            'default' => '#E8E4DC'],
        'color_text_muted' => ['label' => 'Text secundar',           'default' => '#9CA3AF'],
        'color_surface'    => ['label' => 'Fundal carduri/secțiuni', 'default' => '#161616'],
        'color_btn_hover'  => ['label' => 'Hover butoane',           'default' => '#b8922e'],
        'color_banner'     => ['label' => 'Fundal banner anunț',     'default' => '#FFB000'],
    ];
}

