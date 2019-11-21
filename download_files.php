<?php
// stream_context_set_default(['http'=>['proxy'=>'81.17.131.61:8080']]); // Proxy if u need
function DownloadFile($url) {
    file_put_contents("downloaded.csv", fopen($url, 'r'));
}

function ChangeEncodingAndClearSymbols($file_from, $file_to, $from_encoding, $to_encoding) {
    file_put_contents($file_to, mb_convert_encoding(file_get_contents($file_from), $to_encoding, $from_encoding));
    $file_contents = file_get_contents($file_to);
    $file_contents = str_replace("\r", "\n", $file_contents);
    file_put_contents($file_to, $file_contents);
}

function ChangeTextEncoding($text, $from_encoding, $to_encoding) {
    $text = mb_convert_encoding($text, $to_encoding, $from_encoding);
    return $text;
}

function GetTypeByName($types, $name) {
    foreach ($types as $type) {
        if ($type->short_name == $name)
            return $type->id;
    }
    return "error";
}

function GetSubjectsList($events, $types) {
    $subjects = [];
    $temp = [];
    foreach ($events as $event) {
        $subject = explode(' ', $event['Тема'])[0];
        $type = explode(' ', $event['Тема'])[1];
        if (!in_array($subject, $temp)) {
            $array = [];
            $array['id'] = count($subjects) + 1;
            $array['brief'] = $subject;
            $array['title'] = $subject;
            $hours = [];
            $hours['type'] = GetTypeByName($types, $type);
            $hours['val'] = 1;
            $hours['teachers'] = [];
            $array['hours'] = [];
            array_push($array['hours'], $hours);
            array_push($temp, $subject);
            array_push($subjects, $array);
        }
        else {
            $found = false;
            foreach ($subjects[array_search($subject, $temp)]['hours'] as $hour) {
                if ($hour['type'] == GetTypeByName($types, $type)) {
                    $hour['val']++;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $hours = [];
                $hours['type'] = GetTypeByName($types, $type);
                $hours['val'] = 1;
                $hours['teachers'] = [];
                array_push($subjects[array_search($subject, $temp)]['hours'], $hours);
            }
        }
    }
    print("\nGot subjects list");
    return $subjects;
}

function LoadCsvToArray($file) {
    $datas = [];
    if (($handle = fopen($file, "r")) !== FALSE) {
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
    }
    array_pop($datas); // Последний элемент всегда NULL
    return $datas;
}

function DeleteUselessRows($array, $rows) {
    $i = 0;
    foreach ($array as $row) {
        foreach ($row as $key => $val) {
            if (!in_array($key, $rows)) {
                unset($array[$i][$key]);
            }
        }
        $i++;
    }
    return $array;
}

function GetTeachersList() {
    $teachers = [];
    $data = file_get_contents('http://cist.nure.ua/ias/app/tt/P_API_PODR_JSON');
    $data = ChangeTextEncoding($data, "cp1251", "utf-8");
    $json = json_decode($data);
    foreach ($json->university->faculties as $faculty) {
        foreach ($faculty->departments as $department) {
            foreach ($department->teachers as $teacher) {
                $array = [];
                $array['id'] = $teacher->id;
                $array['full_name'] = $teacher->full_name;
                $array['short_name'] = $teacher->short_name;

                array_push($teachers, $array);
            }
        }
    }
    print("\nGot teachers list");
    return $teachers;
}

function GetPairsList() {
    $pairs = [];
    $data = file_get_contents('timePairs.json');
    $json = json_decode($data);
    foreach ($json->pairs as $pair) {
        $pairs[$pair->time] = $pair->pair;
    }
    print("\nGot pairs list");
    return $pairs;
}

function GetTypesList() {
    $data = file_get_contents("types.json");
    $json = json_decode($data);
    print("\nGot types list");
    return $json->types;
}
/*
function GetAuditoriesList() {
    $auditories = [];
    $data = file_get_contents('http://cist.nure.ua/ias/app/tt/P_API_AUDITORIES_JSON');
    $data = ChangeTextEncoding($data, "cp1251", "utf-8");
    $json = json_decode($data);
    foreach ($json->university->buildings as $building) {
        foreach ($building->auditories as $auditory) {
            $auditories[$auditory->short_name] = $auditory->id;
        }
    }
    print("\nGot auditories list");
    return $auditories;
}
*/
function unique_multidim_array($array, $key) {
    $temp_array = array();
    $i = 0;
    $key_array = array();

    foreach($array as $val) {
        if (!in_array($val[$key], $key_array)) {
            $key_array[$i] = $val[$key];
            $temp_array[$i] = $val;
        }
        $i++;
    }
    return $temp_array;
}

function GetGroupsList() {
    $groups = [];
    $data = file_get_contents('http://cist.nure.ua/ias/app/tt/P_API_GROUP_JSON');
    $data = ChangeTextEncoding($data, "cp1251", "utf-8");
    $json = json_decode($data);
    foreach ($json->university->faculties as $faculty) {
        foreach ($faculty->directions as $direction) {
            foreach ($direction->specialities as $speciality) {
                if (isset($speciality->groups)) {
                    foreach ($speciality->groups as $group) {
                        $temp = [];
                        $temp['id'] = $group->id;
                        $temp['name'] = $group->name;

                        array_push($groups, $temp);
                    }
                }
            }
            if (isset($direction->groups)) {
                foreach ($direction->groups as $group) {
                    $temp = [];
                    $temp['id'] = $group->id;
                    $temp['name'] = $group->name;

                    array_push($groups, $temp);
                }
            }
        }
    }
    $groups = unique_multidim_array($groups, 'id'); // Удаляем дубликаты
    $groups = array_values($groups); // Перенумировать массив
    return $groups;
}

function GetGroupIdByName($name, $groups) {
    foreach ($groups as $group) {
        if ($group['name'] == $name)
            return $group['id'];
    }
    return -1;
}

function GetGroupNameById($id, $groups) {
    foreach ($groups as $group) {
        if ($group['id'] == $id)
            return $group['name'];
    }
    return "error";
}

function GetNormalEvents($events, $subjects, $types, $pairs, $group_id) {
    $new_events = [];
    for ($i=0; $i<count($events); $i++) {
        foreach ($subjects as $subject) {
            if ($subject['brief'] == explode(' ', $events[$i]['Тема'])[0]) {
                $new_events[$i]['subject_id'] = $subject['id'];
            }
        }
        $new_events[$i]['start_time'] = strtotime($events[$i]['Дата начала'] . ' ' . $events[$i]['Время начала']);
        $new_events[$i]['end_time'] = strtotime($events[$i]['Дата начала'] . ' ' . $events[$i]['Время завершения']);
        $to_find = explode(' ', $events[$i]['Тема'])[1];
        foreach ($types as $t) {
            if ($t->short_name == $to_find) {
                $new_events[$i]['type'] = $t->id;
            }
        }
        $new_events[$i]['number_pair'] = $pairs[$events[$i]['Время начала']];
        /*
        foreach ($auditories as $a) {
            $to_find = explode(' ', $events[$i]['Тема'])[2];
            if ($auditories[$to_find]) {
                $new_events[$i]['auditory'] = $auditories[$to_find];
            }
        }*/
        $new_events[$i]['auditory'] = explode(' ', $events[$i]['Тема'])[2];
        $new_events[$i]['teachers'] = [];
        $new_events[$i]['groups'][0] = $group_id;
    }
    return $new_events;
}

function FormCistJson($group_id, $date_start, $date_end) {
    try {
        $group = GetGroupNameById($group_id, GetGroupsList());
        $url = "http://cist.nure.ua/ias/app/tt/WEB_IAS_TT_GNR_RASP.GEN_GROUP_POTOK_RASP?ATypeDoc=3&Aid_group=$group_id&Aid_potok=0&ADateStart=$date_start&ADateEnd=$date_end&AMultiWorkSheet=0";
        DownloadFile($url);
        ChangeEncodingAndClearSymbols("downloaded.csv", "clean.csv", "cp1251", "utf-8");
        $events = LoadCsvToArray("clean.csv");
        $useful_rows = ["Тема", "Дата начала", "Время начала", "Время завершения"];
        $events = DeleteUselessRows($events, $useful_rows);
        $last_array = [];
        // Обновляем все данные (можно скачать и хранить локально)
        //$auditories = GetAuditoriesList();
        $teachers = GetTeachersList();
        $types = GetTypesList();
        $pairs = GetPairsList();
        $subjects = GetSubjectsList($events, $types);
        $events = GetNormalEvents($events, $subjects/*, $auditories*/, $types, $pairs, $group_id);
        $groups[0]['id'] = $group_id;
        $groups[0]['name'] = $group;
        $last_array['time-zone'] = "Europe/Kiev";
        $last_array['events'] = $events;
        $last_array['groups'] = $groups;
        $last_array['teachers'] = $teachers;
        $last_array['subjects'] = $subjects;
        $last_array['types'] = $types;
        $last_json = json_encode($last_array, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        file_put_contents("ready.json", $last_json);
        return true;
    }
    catch(Exception $excp) {
        return false;
    }
}
//http://192.168.64.2/csvjson/download_files.php?P_API_EVENT_JSON&timetable_id=6283405&type_id=1&time_from=21.11.2019&time_to=22.11.2019
print_r($_GET);
if (isset($_GET['P_API_EVENT_JSON'])) {
    if (FormCistJson($_GET['timetable_id'], $_GET['time_from'], $_GET['time_to'])) {
        echo "\nSuccess!";
        header("Location: ready.json");
    }
    else
        echo "\nError parsing!";
}
else {
    echo "Error in link";
}