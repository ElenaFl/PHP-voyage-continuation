<?php
function renderTemplate(string $template, array $params): string
{
    /* Запускаем буферизацию вывода
    Все последующие echo/print будут сохраняться в буфер, а не отправляться в браузер*/
    ob_start();

    /* Распаковываем массив $params в переменные текущей области видимости
    Например, ['title' => 'Привет'] создаст переменную $title = 'Привет'*/
    extract($params);

    try {
        /* Подключаем файл шаблона
        Путь строится от директории текущего файла (__DIR__)*/
        include __DIR__ . "/../templates/$template.phtml";
    } catch (Throwable $e) {
        /* Останавливаем буферизацию и выбрасываем исключение
        throwable - объединяет все типы «бросаемых» объектов*/
        ob_end_clean();
        throw new Exception('Ошибка при подключении шаблона: ' . $e->getMessage());
    }

    /* Получаем содержимое буфера и очищаем его
    Возвращаем отрендеренный HTML*/
    return ob_get_clean();
}

// частичный шаблон - отрисовка части страницы (menu, header, footer)
function renderPartial(string $page): string
{
    ob_start();
    include __DIR__ . "/../templates/$page.phtml";
    return ob_get_clean();
}