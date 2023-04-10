<?php
    function uniqueID(string $prefix): string {
        return substr(uniqid($prefix , true), 0, 15) . "_" . substr(rand() , 0, 5);
    }

    function notFound(): void {
        http_response_code(404);
        print_r(json_encode(["message" => "Not Found"]));
        exit;
    }

    function notAllodMethods(string $allowed_method) {
        http_response_code("405");
        header("Allow: {$allowed_method}");
        exit;
    }
    
    function unprocessableContent(array $errors) {
        http_response_code(422);
        echo json_encode(["message"=> "The given data was invalid." , "errors" => $errors]);
    }

    function checkAuth() {
        $auth = new UserGateway;
        if ($auth->authAccessToken() === false) exit;
        return $auth->authAccessToken();
    }
?>