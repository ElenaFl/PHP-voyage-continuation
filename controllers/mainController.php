<?php
function mainController(): string
{
    $mainTitle = getStr(
        page:'mainTitle',
        field: 'mainTitle',
        default: 'Блог'
    );

    $content = renderTemplate('mainTitle', [
        'mainTitle' => $mainTitle
    ]);

    $menu = menuController();

    return renderTemplate('main', [
        'menu' => $menu,
        'content' => $content
    ]);
}