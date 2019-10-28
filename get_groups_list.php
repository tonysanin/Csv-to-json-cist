<?php
// Пока что через API. Есть обход, но пока не знаю как реализовать
function GetIdByName($name)
{
    system('python3 make_groups.py');
    $file_contents = file_get_contents("./Files/groups_with_id.txt");
    $array = explode("\n", $file_contents);

    for ($i = 0; $i < count($array); $i++) {
        if (explode(":", $array[$i])[0] == $name) {
            return explode(':', $array[$i])[1];
        }
    }
    return null;
}