<?php
function menuAdminController(): string
{
    $data = getData('menuAdmin');

    return renderTemplate('menuAdmin', $data);
}