<?php

namespace NarrysTech\Api_Rest\classes;

class Helpers{

    public static function response_json (array $datas = [], int $status = 200, bool $success = true):string
    {
        return json_encode([
            "success" => $success,
            "status" => $status,
            "datas" => $datas
        ]);
    }
}