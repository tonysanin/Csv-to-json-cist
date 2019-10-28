import requests
import json

struct_faculties = []

def UpdateStructs():
    try:
        json_groups = "http://mnuxg5a.nz2xezjoovqq.cmle.ru/ias/app/tt/P_API_GROUP_JSON"
        response = requests.get(json_groups)
        json_data = json.loads(response.text)
        university = json_data['university']
        _faculties = university['faculties']
        global struct_faculties
        struct_faculties = university['faculties']
    except:
        pass


UpdateStructs()
groups = []
for _f in struct_faculties:
    for _s in _f['directions']:
        try:
            for _a in _s['groups']:
                groups.append(_a['name'] + ':' + str(_a['id']))
        except:
            pass
for _f in struct_faculties:
    for _s in _f['directions']:
        try:
            for _g in _s['specialities']:
                for _gg in _g['groups']:
                    groups.append(_gg['name'] + ':' + str(_gg['id']))
        except:
            pass


with open("./Files/groups_with_id.txt", "w") as txt_file:
    for line in groups:
        txt_file.write(line + "\n")
