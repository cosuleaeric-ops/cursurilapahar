<?php

/** @return array<string, mixed> Variables for admin/partials/dashboard-tab.php */
function clp_dashboard_data(string $admin_dir): array {
    $_dash_courses = clp_load_courses_for_admin();
    $_dash_total_courses = 0;

    $_dash_today = date('Y-m-d');
    $_dash_scheduled = count(array_filter(
        clp_filter_public_courses($_dash_courses),
        fn($c) => ($c['date_raw'] ?? '') >= $_dash_today
    ));

    $_dash_pnl_profit = 0;
    $_dash_pnl_venituri = 0;
    $_dash_pnl_cheltuieli = 0;
    $_dash_pnl_year  = date('Y');
    $_dash_pnl_month = str_pad(date('n'), 2, '0', STR_PAD_LEFT);
    $_dash_pnl_db_path = $admin_dir . '/statistici/data/pnl.sqlite';
    if (file_exists($_dash_pnl_db_path)) {
        try {
            $_pdb = new SQLite3($_dash_pnl_db_path);
            $_pdb->exec('PRAGMA journal_mode=WAL');
            $_dash_pnl_venituri = (float)$_pdb->querySingle("SELECT COALESCE(SUM(suma),0) FROM venituri WHERE strftime('%Y',data)='{$_dash_pnl_year}' AND strftime('%m',data)='{$_dash_pnl_month}'");
            $_dash_pnl_cheltuieli = (float)$_pdb->querySingle("SELECT COALESCE(SUM(suma),0) FROM cheltuieli WHERE strftime('%Y',data)='{$_dash_pnl_year}' AND strftime('%m',data)='{$_dash_pnl_month}'");
            $_dash_pnl_profit = $_dash_pnl_venituri - $_dash_pnl_cheltuieli;
            $_pdb->close();
        } catch (Exception $e) {}
    }

    $_dash_participants = 0;
    $_dash_total_tickets = 0;
    $_dash_clp_db_path = $admin_dir . '/statistici/data/clp.sqlite';
    if (file_exists($_dash_clp_db_path)) {
        try {
            $_cdb = new SQLite3($_dash_clp_db_path);
            $_cdb->exec('PRAGMA journal_mode=WAL');
            $_dash_total_courses = (int) $_cdb->querySingle('SELECT COUNT(*) FROM courses');
            $_dash_participants = (int)$_cdb->querySingle("SELECT COUNT(DISTINCT LOWER(TRIM(participant_name))) FROM tickets");
            $_dash_total_tickets = (int)$_cdb->querySingle("SELECT COUNT(*) FROM tickets");
            $_cdb->close();
        } catch (Exception $e) {}
    }

    $_dash_pnl_monthly = [];
    if (file_exists($_dash_pnl_db_path)) {
        try {
            $_pdb2 = new SQLite3($_dash_pnl_db_path);
            $_pdb2->exec('PRAGMA journal_mode=WAL');
            $_mv = [];
            $_mc = [];
            $r = $_pdb2->query("SELECT strftime('%m',data) as m, COALESCE(SUM(suma),0) as s FROM venituri WHERE strftime('%Y',data)='{$_dash_pnl_year}' GROUP BY m ORDER BY m");
            while ($row = $r->fetchArray(SQLITE3_ASSOC)) $_mv[$row['m']] = (float)$row['s'];
            $r = $_pdb2->query("SELECT strftime('%m',data) as m, COALESCE(SUM(suma),0) as s FROM cheltuieli WHERE strftime('%Y',data)='{$_dash_pnl_year}' GROUP BY m ORDER BY m");
            while ($row = $r->fetchArray(SQLITE3_ASSOC)) $_mc[$row['m']] = (float)$row['s'];
            for ($i = 1; $i <= (int)date('n'); $i++) {
                $k = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
                $_dash_pnl_monthly[] = ['v' => $_mv[$k] ?? 0, 'c' => $_mc[$k] ?? 0];
            }
            $_pdb2->close();
        } catch (Exception $e) {}
    }

    $_dash_participant_months = [];
    if (file_exists($_dash_clp_db_path)) {
        try {
            $_cdb2 = new SQLite3($_dash_clp_db_path);
            $_cdb2->exec('PRAGMA journal_mode=WAL');
            $r = $_cdb2->query("SELECT strftime('%Y-%m', c.date) as m, COUNT(DISTINCT LOWER(TRIM(t.participant_name))) as unici, COUNT(*) as bilete
                FROM tickets t JOIN courses c ON c.id = t.course_id
                GROUP BY m ORDER BY m DESC LIMIT 6");
            while ($row = $r->fetchArray(SQLITE3_ASSOC)) $_dash_participant_months[] = $row;
            $_cdb2->close();
        } catch (Exception $e) {}
    }

    $_dash_ditl_year = 0;
    if (file_exists($_dash_clp_db_path)) {
        try {
            $_cdb4 = new SQLite3($_dash_clp_db_path);
            $_cdb4->exec('PRAGMA journal_mode=WAL');
            $_dash_ditl_year = (float)$_cdb4->querySingle("SELECT COALESCE(SUM(total_bilete),0) FROM course_reports r JOIN courses c ON c.id=r.course_id WHERE strftime('%Y',c.date)='{$_dash_pnl_year}'") * 0.02;
            $_cdb4->close();
        } catch (Exception $e) {}
    }

    $_ro_months_dash = ['', 'ian', 'feb', 'mar', 'apr', 'mai', 'iun', 'iul', 'aug', 'sep', 'oct', 'nov', 'dec'];
    $_ro_months_full = ['', 'ianuarie', 'februarie', 'martie', 'aprilie', 'mai', 'iunie', 'iulie', 'august', 'septembrie', 'octombrie', 'noiembrie', 'decembrie'];
    $_dash_month_label = $_ro_months_full[(int)date('n')] . ' ' . date('Y');

    $_mc_by_day = [];
    foreach ($_dash_courses as $_c) {
        $d = $_c['date_raw'] ?? '';
        if ($d) $_mc_by_day[$d][] = $_c;
    }

    return compact(
        '_dash_courses', '_dash_scheduled', '_dash_total_courses',
        '_dash_pnl_profit', '_dash_pnl_venituri', '_dash_pnl_cheltuieli',
        '_dash_participants', '_dash_total_tickets',
        '_dash_pnl_monthly', '_dash_participant_months',
        '_dash_ditl_year', '_ro_months_dash', '_ro_months_full', '_dash_month_label',
        '_mc_by_day'
    );
}
