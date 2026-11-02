<?php
/**
 * Programs offered, with the short code used on masterlists (e.g. "BSIT 2B MASTERLIST") and their college.
 * The college is worked out from the course, so nobody has to pick it twice.
 * Keep the names in sync with assets/js/course-picker.js.
 */
function course_catalog(): array
{
    return [
        ['code' => 'BSIT',    'name' => 'Bachelor of Science in Information Technology (BSIT)', 'college' => 'CCS'],
        ['code' => 'BEED',    'name' => 'Bachelor in Elementary Education (BEED)',               'college' => 'CTE'],
        ['code' => 'BECED',   'name' => 'Bachelor of Early Childhood Education (BECED)',         'college' => 'CTE'],
        ['code' => 'BSE-ENG', 'name' => 'Bachelor in Secondary Education (BSE) – English',       'college' => 'CTE'],
        ['code' => 'BSE-MATH','name' => 'Bachelor in Secondary Education (BSE) – Mathematics',   'college' => 'CTE'],
        ['code' => 'BSE-SS',  'name' => 'Bachelor in Secondary Education (BSE) – Social Studies','college' => 'CTE'],
        ['code' => 'BSA',     'name' => 'Bachelor of Science in Accountancy (BSA)',              'college' => 'COA'],
        ['code' => 'BSMA',    'name' => 'Bachelor of Science in Management Accounting (BSMA)',   'college' => 'COA'],
        ['code' => 'BSBA-FM', 'name' => 'BS Business Administration – Financial Management',     'college' => 'CBA'],
        ['code' => 'BSBA-HRM','name' => 'BS Business Administration – Human Resource Management','college' => 'CBA'],
        ['code' => 'BSBA-MM', 'name' => 'BS Business Administration – Marketing Management',     'college' => 'CBA'],
        ['code' => 'BSCRIM',  'name' => 'Bachelor of Science in Criminology',                    'college' => 'CCJE'],
        ['code' => 'ABJOURN', 'name' => 'Bachelor of Arts in Journalism',                        'college' => 'CAS'],
        ['code' => 'ABPOLSCI','name' => 'Bachelor of Arts in Political Science',                 'college' => 'CAS'],
        ['code' => 'BPA',     'name' => 'Bachelor of Public Administration',                     'college' => 'CAS'],
        ['code' => 'BSSW',    'name' => 'Bachelor of Science in Social Work',                    'college' => 'CAS'],
    ];
}

/** Match a typed course (full name, code, or "BSIT") to the catalog. */
function course_lookup(string $text): ?array
{
    $norm = function ($s) { return preg_replace('/[^A-Z0-9]/', '', strtoupper($s)); };
    $t = $norm($text);
    if ($t === '') return null;
    foreach (course_catalog() as $c) {
        if ($norm($c['code']) === $t || $norm($c['name']) === $t) return $c;
    }
    // plain names people type: "BS Information Technology", "BSBA Marketing", "BSE English" ...
    $aliases = [
        [['INFORMATIONTECH'], 'BSIT'], [['ELEMENTARYEDUC'], 'BEED'], [['EARLYCHILDHOOD'], 'BECED'],
        [['SECONDARY', 'ENGLISH'], 'BSE-ENG'], [['SECONDARY', 'MATH'], 'BSE-MATH'], [['SECONDARY', 'SOCIAL'], 'BSE-SS'],
        [['MANAGEMENTACCOUNTING'], 'BSMA'], [['ACCOUNTANCY'], 'BSA'],
        [['BUSINESSADMIN', 'FINANCIAL'], 'BSBA-FM'], [['BUSINESSADMIN', 'HUMANRESOURCE'], 'BSBA-HRM'], [['BUSINESSADMIN', 'MARKETING'], 'BSBA-MM'],
        [['BSBA', 'FINANCIAL'], 'BSBA-FM'], [['BSBA', 'HUMANRESOURCE'], 'BSBA-HRM'], [['BSBA', 'MARKETING'], 'BSBA-MM'],
        [['CRIMINOLOGY'], 'BSCRIM'], [['JOURNALISM'], 'ABJOURN'], [['POLITICALSCIENCE'], 'ABPOLSCI'], [['PUBLICADMINISTRATION'], 'BPA'], [['SOCIALWORK'], 'BSSW'],
    ];
    foreach ($aliases as [$needles, $code]) {
        $all = true; foreach ($needles as $n) if (strpos($t, $n) === false) { $all = false; break; }
        if ($all) foreach (course_catalog() as $c) if ($c['code'] === $code) return $c;
    }
    foreach (course_catalog() as $c) {                         // "... (BSIT)" typed in full or partially
        if (preg_match('/\(([A-Z]+)\)/', $c['name'], $m) && strpos($t, $m[1]) !== false && strlen($m[1]) >= 3) return $c;
    }
    return null;
}

/** department_id of the college that owns this course (or null). */
function course_department(string $course): ?int
{
    $c = course_lookup($course);
    if (!$c) return null;
    $id = q('SELECT department_id FROM departments WHERE department_type = "college" AND department_name LIKE ? LIMIT 1', ['%(' . $c['college'] . ')%'])->fetchColumn();
    return $id ? (int)$id : null;
}

/** Course / year / section hints from a masterlist title or file name, e.g. "BSIT 2B MASTERLIST" or "BSIT-3B-MASTERLIST-first-sem". */
function detect_batch(string ...$sources): array
{
    foreach ($sources as $src) {
        $src = strtoupper(str_replace(['_', '.docx', '.xlsx', '.csv'], [' ', '', '', ''], $src));
        $src = preg_replace('/[\[\](){}]/', ' ', $src);
        // a code followed by year+section ("BSIT 2B", "BSIT-3B", "BSBA-FM 4A")
        if (preg_match('/\b([A-Z]{2,8}(?:-[A-Z]{2,5})?)[\s-]+([1-6])\s*-?\s*([A-Z]{1,2}\d?)\b/', $src, $m)) {
            $course = course_lookup($m[1]);
            return ['course' => $course ? $course['name'] : '', 'code' => $m[1], 'year_level' => (int)$m[2], 'section' => $m[2] . $m[3], 'from' => trim($src)];
        }
    }
    return ['course' => '', 'code' => '', 'year_level' => null, 'section' => '', 'from' => ''];
}
