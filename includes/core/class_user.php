<?php

class User {

    // GENERAL

    public static function user_info($d) {
        // vars
        $user_id = isset($d['user_id']) && is_numeric($d['user_id']) ? $d['user_id'] : 0;
        $phone = isset($d['phone']) ? preg_replace('~\D+~', '', $d['phone']) : 0;
        // where
        if ($user_id) $where = "user_id='".$user_id."'";
        else if ($phone) $where = "phone='".$phone."'";
        else return [];
        // info
        $q = DB::query("SELECT user_id, phone, access FROM users WHERE ".$where." LIMIT 1;") or die (DB::error());
        if ($row = DB::fetch_row($q)) {
            return [
                'id' => (int) $row['user_id'],
                'access' => (int) $row['access']
            ];
        } else {
            return [
                'id' => 0,
                'access' => 0
            ];
        }
    }

    public static function users_list_plots($number) {
        // vars
        $items = [];
        // info
        $q = DB::query("SELECT user_id, plot_id, first_name, email, phone
            FROM users WHERE plot_id LIKE '%".$number."%' ORDER BY user_id;") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $plot_ids = explode(',', $row['plot_id']);
            $val = false;
            foreach($plot_ids as $plot_id) if ($plot_id == $number) $val = true;
            if ($val) $items[] = [
                'id' => (int) $row['user_id'],
                'first_name' => $row['first_name'],
                'email' => $row['email'],
                'phone_str' => phone_formatting($row['phone'])
            ];
        }
        // output
        return $items;
    }

    public static function users_list($params = []): array
    {
        // vars
        $search = isset($params['search']) && trim($params['search']) ? $params['search'] : '';
        $offset = isset($params['offset']) && is_numeric($params['offset']) ? $params['offset'] : 0;
        $limit = 20;
        $items = [];
        // where
        $where = [];
        if ($search) {
            $where[] = "
                (phone LIKE '%".$search."%' 
                OR first_name LIKE '%".$search."%' 
                OR last_name LIKE '%".$search."%' 
                OR email LIKE '%".$search."%')
            ";
        }
        $where = $where ? "WHERE ".implode(" AND ", $where) : "";
        // info
        $q = DB::query("SELECT user_id, plot_id, first_name, last_name, email, phone, last_login
            FROM users ".$where." ORDER BY user_id+0 LIMIT ".$offset.", ".$limit.";") or die (DB::error());
        while ($row = DB::fetch_row($q)) {
            $items[] = [
                'id' => (int) $row['user_id'],
                'plot_id' => $row['plot_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'email' => $row['email'],
                'phone' => (int) $row['phone'],
                'last_login' => !empty($row['last_login']) ? date('Y-m-d H:i:s', $row['last_login']) : '',
            ];
        }
        // paginator
        $q = DB::query("SELECT count(*) FROM users ".$where.";");
        $count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
        $url = 'users?';
        if ($search) $url .= '&search='.$search;
        paginator($count, $offset, $limit, $url, $paginator);
        // output
        return ['items' => $items, 'paginator' => $paginator];
    }

    public static function getUsers($params = []): array
    {
        $info = User::users_list($params);
        HTML::assign('users', $info['items']);
        return ['html' => HTML::fetch('./partials/users_table.html'), 'paginator' => $info['paginator']];
    }

    public static function getUserById(int $userId): array {
        $q = DB::query("SELECT user_id, first_name, last_name, phone, email, plot_id
            FROM users WHERE user_id = " . $userId) or die (DB::error());
        if ($row = DB::fetch_row($q)) {
            return [
                'id' => (int) $row['user_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'phone' => $row['phone'],
                'email' => $row['email'],
                'plot_id' => $row['plot_id'],
            ];
        }

        return ['error' => 'user not found'];
    }

    public static function user_add_window() {
        HTML::assign('user');
        return ['html' => HTML::fetch('./partials/user_edit.html')];
    }
    public static function user_edit_window($params = [])
    {
        if (empty($params['user_id'])) {
            return null;
        }

        $user = self::getUserById((int)$params['user_id']);

        if (!$user) {
            return null;
        }

        HTML::assign('user', $user);
        return ['html' => HTML::fetch('./partials/user_edit.html')];

    }

    public static function delete($params = []): ?array
    {
        $userId = (int)$params['user_id'];

        if (DB::query('DELETE FROM users WHERE user_id = ' . $userId)) {
            $result = self::getUsers();
        }

        if (!$result) {
            return null;
        }

        return $result;
    }

    public static function update($params = []): ?array
    {
        $fields = ['email', 'phone', 'first_name', 'last_name', 'plot_id'];
        $set = [];
        $userId = $params['user_id'];

        foreach ($fields as $field) {
            if (isset($params[$field])) {
                $set[] = "$field = '" . $params[$field] . "'";
            }
        }

        if (!empty($set) && !empty($userId)) {
            DB::query("UPDATE users SET " . implode(", ", $set) . " WHERE user_id = " . $userId);
        }

        return self::getUsers();
    }

    public static function create($params): ?array
    {
        if (!empty($params)) {
            DB::query("INSERT INTO users (
                first_name,
                last_name,
                phone,
                email,
                plot_id
            ) VALUES (
                '".$params['first_name']."',
                '".$params['last_name']."',
                '".$params['phone']."',
                '".$params['email']."',
                '".$params['plot_id']."'
            );") or die (DB::error());
        }

        return self::getUsers();
    }

}
