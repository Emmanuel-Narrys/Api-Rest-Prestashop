<?php

namespace ApiRest\Classes;

class Helpers{

    public static function response_json (bool $success = true, int $status = 200, array $datas = []):string
    {
        return json_encode([
            "success" => $success,
            "status" => $status,
            "datas" => $datas
        ]);
    }
}