<?php
function menuController(): string
{
    $data = getData('menu');

    return renderTemplate('menu', $data);
}