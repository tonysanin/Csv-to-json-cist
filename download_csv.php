<?php
require "get_groups_list.php";


function DownloadFile($url) {
    file_put_contents("./Files/downloaded.csv", fopen($url, 'r'));
}


function GetJsonFromCSV($GROUP)
{
    $id = GetIdByName($GROUP);
    DownloadFile("http://mnuxg5a.nz2xezjoovqq.cmle.ru/ias/app/tt/WEB_IAS_TT_GNR_RASP.GEN_GROUP_POTOK_RASP?ATypeDoc=3&Aid_group=" . $id . "&Aid_potok=0&ADateStart=01.09.2019&ADateEnd=31.01.2020&AMultiWorkSheet=0");
    $file = "./Files/downloaded.csv";
    $clean = './Files/clean.csv';

// Танцуем с бубном, чтобы хоть что-то работало
    file_put_contents($clean, mb_convert_encoding(file_get_contents($file), 'utf-8', "cp1251"));
    $file_contents = file_get_contents($clean);
    $file_contents = str_replace("\r", "\n", $file_contents);
    file_put_contents($clean, $file_contents);


    if (($handle = fopen($clean, "r")) !== FALSE) {
        $csvs = [];
        while (!feof($handle)) {
            $csvs[] = fgetcsv($handle);
        }

        $datas = [];
        $column_names = [];
        foreach ($csvs[0] as $single_csv) {
            $column_names[] = $single_csv;
        }

        foreach ($csvs as $key => $csv) {
            if ($key === 0) {
                continue;
            }
            foreach ($column_names as $column_key => $column_name) {
                $datas[$key - 1][$column_name] = $csv[$column_key];
            }
        }
        fclose($handle);


        $useful_rows = ["Тема", "Дата начала", "Время начала", "Время завершения"];
        $i = 0;
        foreach ($datas as $row) {
            foreach ($row as $key => $val) {
                if (!in_array($key, $useful_rows)) {
                    unset($datas[$i][$key]);
                }
            }
            $i++;
        }


        $i = 0;
        $types = ["Лк", "У.Лк (1)", "У.Лк", "Пз", "У.Пз", "Лб", "У.Лб",
            "Конс", "Зал", "дзал", "Екз", "ЕкзП", "ЕкзУ", "ІспКомб", "ІспТест", "мод", "КП/КР"];

        foreach ($datas as $row) {
            $already_written = false;
            $temp = $datas[$i]["Тема"];

            $newthemes = explode(';', $temp);
            foreach ($newthemes as $line) {

                $line = ltrim($line);
                $result = explode(' ', $line);

                normal:
                if (count($result) == 4) {
                    if ($already_written == false) {
                        $datas[$i]["Тема"] = $result[0];
                        $datas[$i]["Тип"] = $result[1];
                        $datas[$i]["Аудитория"] = $result[2];
                        $datas[$i]["Группа"] = $id;
                        $already_written = true;
                    } else {
                        $datas[$i]["Тема"] .= '/' . $result[0];
                        $datas[$i]["Тип"] .= '/' . $result[1];
                        $datas[$i]["Аудитория"] .= '/' . $result[2];
                    }
                } // Фиксим такую херню *С (NoSQL) Лк 337 *С (NoSQL)(ІТКН-17-)-1;*С (NoSQL)(ІТКНу-18-)-2"
                elseif (count($result) > 4) {
                    for ($a = 0; $a < count($result); $a++) {
                        if (in_array($result[$a], $types)) {
                            for ($k = 1; $k < $a; $k++) {
                                $result[0] .= $result[$k];
                                unset($result[$k]);
                            }
                            unset($result[5]);
                            $result = array_filter($result);
                            $result = array_values($result);
                            goto normal; // Господи, прости меня
                            break;
                        }
                    }
                }
            }
            $i++;
        }
        array_pop($datas); // Последний элемент всегда NULL
        $datas = array_filter($datas); // Ещё одна чистка от NULL
        $datas = array_values($datas); // Переиндексация


        $json = json_encode($datas, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); // Без него крокозябры

        $fp = fopen('results.json', 'w');
        fwrite($fp, $json);
        fclose($fp);
    }
}