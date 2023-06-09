<?php

    class UserGateway extends DataBase {
        public function getUserDetails($userId) {
            $sql =  "SELECT _id,
            name,
            email,
            phone
                FROM user WHERE _id = '$userId'" ;
            $user = $this->executeQuery($sql);
            $user = $this->fetch($user);
                
            echo(json_encode($user));
        }

        public function UpdateUser($userId) {
            $data = json_decode(file_get_contents("php://input") , true);

            if (isset($data["name"]) && isset($data["phone"])) {
                $name = $data["name"];
                $phone = $data["phone"];
                
                // check name
                if (strlen($name) < 3) {
                    $response = array(
                        'status' => 'error',
                        'message' => 'name must contain at least 4 characters'
                    );
                    echo json_encode($response);
                    http_response_code(401);
                    exit;
                } else if (strlen($name) > 15) {
                    $response = array(
                        'status' => 'error',
                        'message' => 'name must less than 15 characters'
                    );
                    echo json_encode($response);
                    http_response_code(401);
                    exit;
                }

                // check phone
                if (!preg_match('/^((06)|(07))[0-9]{8}$/' , $phone) && $phone !== "") {
                    http_response_code(401);
                    echo json_encode(["message" => "invalid phone"]);
                    exit;
                }

                // update user
                $sql = "UPDATE user
                SET name = '$name' , phone = '$data[phone]'
                WHERE _id = '$userId';";

                $this->executeQuery($sql);
                mysqli_affected_rows($this->connection) > 0 && print_r(json_encode(["message" => "updated successfully"]));

            } else if (isset($data["oldPassword"]) && isset($data["newPassword"])) {
                $newPassword = $data["newPassword"];
                $oldPassword = $data["oldPassword"];

                // check old password
                $sql = "SELECT password FROM user WHERE _id = '$userId'";
                $user = $this->executeQuery($sql);
                $user = $this->fetch($user);
                
                $password_hash = $user["password"];
                if (!password_verify($oldPassword , $password_hash)){
                    $response = array(
                        'status' => 'error',
                        'message' => "current password incorrect."
                    );
                    http_response_code(401);
                    echo json_encode($response);
                    exit;
                }

                // check new password
                if (strlen($newPassword) < 8) {
                    $response = array(
                        'status' => 'error',
                        'message' => 'Password must contain at least 8 characters'
                    );
                    echo json_encode($response);
                    http_response_code(401);
                    exit;
                } else if (strlen($newPassword) > 20) {
                    $response = array(
                        'status' => 'error',
                        'message' => 'Password must less than 15'
                    );
                    echo json_encode($response);
                    http_response_code(401);
                    exit;
                }

                $regPasswordHash = password_hash($newPassword , PASSWORD_DEFAULT);
                
                $sql = "UPDATE user
                SET password = '$regPasswordHash'
                WHERE _id = '$userId';";

                $this->executeQuery($sql);
                mysqli_affected_rows($this->connection) > 0 && print_r(json_encode(["message" => "updated successfully"]));

            }
            else {
                print_r(json_encode(["message" => "all fields required"]));
                http_response_code(422);
                exit;
            }
        }

        public function login() {
            $data = json_decode(file_get_contents("php://input") , true);

            if (!isset($data['email']) || !isset($data['password'])){
                $response = array(
                    'status' => 'error',
                    'message' => 'Email and password are required'
                );
                echo json_encode($response);
                http_response_code(400);
                exit;
            }

            $email = htmlspecialchars($data["email"]);
            $password = htmlspecialchars($data["password"]);
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response = array(
                    'status' => 'error',
                    'message' => 'Invalid email format'
                );
                echo json_encode($response);
                http_response_code(401);
                exit;
            }
            
            if (strlen($password) < 8) {
                $response = array(
                    'status' => 'error',
                    'message' => 'Password must contain at least 8 characters'
                );
                echo json_encode($response);
                http_response_code(401);
                exit;
            }

            $sql = "SELECT _id , name , password FROM user WHERE email = '$email'";
            $user = $this->executeQuery($sql);

            if (mysqli_num_rows($user) === 0) {
                $response = array(
                    'status' => 'error',
                    'message' => "Your account name or password is incorrect."
                );
                echo json_encode($response);
                http_response_code(401);
                exit;
            }

            $user = $this->fetch($user);
            $password_hash = $user["password"];
            if (!password_verify($password , $password_hash)){
                $response = array(
                    'status' => 'error',
                    'message' => "Your account name or password is incorrect."
                );
                http_response_code(401);
                echo json_encode($response);
                exit;
            }

            $this->SendJwtByEmail($email);
        }

        public function forgotPassword() {
            $data = json_decode(file_get_contents("php://input") , true);

            if (!isset($data['email'])){
                $response = array(
                    'status' => 'error',
                    'message' => 'Email required'
                );
                echo json_encode($response);
                http_response_code(400);
                exit;
            }

            $email = htmlspecialchars($data["email"]);
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response = array(
                    'status' => 'error',
                    'message' => 'Invalid email format'
                );
                echo json_encode($response);
                http_response_code(401);
                exit;
            }

            $sql = "SELECT _id FROM user WHERE email = '$email'";
            $user = $this->executeQuery($sql);

            if (mysqli_num_rows($user) === 0) {
                $response = array(
                    'status' => 'error',
                    'message' => "Your account is not exist."
                );
                echo json_encode($response);
                http_response_code(401);
                exit;
            }

            $resetToken = bin2hex(random_bytes(16));

  // Send an email to the user with the password reset link
  $resetLink = 'https://example.com/reset_password.php?token=' . $resetToken;

            print_r(json_encode(["status" => "success"]));
        }


        public function register() {
            
            $data = json_decode(file_get_contents("php://input") , true);

            if (!isset($data['email']) || !isset($data['password']) || !isset($data['name'])){
                $response = array(
                    'status' => 'error',
                    'message' => 'Email, password and name are required'
                );
                echo json_encode($response);
                http_response_code(400);
                exit;
            }

            $email = htmlspecialchars(trim($data["email"]));
            $name = htmlspecialchars(trim($data["name"]));
            $password = htmlspecialchars(trim($data["password"]));
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response = array(
                    'status' => 'error',
                    'message' => 'Invalid email format'
                );
                echo json_encode($response);
                http_response_code(401);
                exit;
            }
            
            if (strlen($name) < 3) {
                $response = array(
                    'status' => 'error',
                    'message' => 'name must contain at least 4 characters'
                );
                echo json_encode($response);
                http_response_code(401);
                exit;
            } else if (strlen($name) > 15) {
                $response = array(
                    'status' => 'error',
                    'message' => 'name must less than 15 characters'
                );
                echo json_encode($response);
                http_response_code(401);
                exit;
            }

            if (strlen($password) < 8) {
                $response = array(
                    'status' => 'error',
                    'message' => 'Password must contain at least 8 characters'
                );
                echo json_encode($response);
                http_response_code(401);
                exit;
            } else if (strlen($password) > 20) {
                $response = array(
                    'status' => 'error',
                    'message' => 'Password must less than 15'
                );
                echo json_encode($response);
                http_response_code(401);
                exit;
            }

            $sql = "SELECT _id FROM user WHERE email = '$email'";
            $user = $this->executeQuery($sql);

            if (mysqli_num_rows($user) !== 0) {
                $response = array(
                    'status' => 'error',
                    'message' => "this email address already exists."
                );
                echo json_encode($response);
                http_response_code(401);
                exit;
            }

            $regPasswordHash = password_hash($password , PASSWORD_DEFAULT);

            $sql = "INSERT INTO user(name ,email, password) VALUES ('$name', '$email', '$regPasswordHash');";
            $this->executeQuery($sql);

            if (mysqli_affected_rows($this->connection) < 1 ) {
                $response = array(
                    'status' => 'error',
                    'message' => "user not added."
                );
                echo json_encode($response);
                http_response_code(401);
                exit;
            } 

            // response
            $this->SendJwtByEmail($email);
        }

        private function SendJwtByEmail(string $email)
        {
            $sql = "SELECT u._id, u.name, u.password, ur.nameRole
            FROM user u
            INNER JOIN user_role ur
                ON u._idRole = ur._idRole WHERE email = '$email';";
            $user = $this->executeQuery($sql);
            $user = $this->fetch($user);
            
            $payload = [
                "sub" => $user["_id"],
                "name" =>  $user["name"],
                "email" => $email,
                "role" => $user["nameRole"],
                "exp" => time() + 1800
            ];

            $exp_ref = time() + 604800;
            $payload_ref = [
                "sub" => $user["_id"],
                "exp" => $exp_ref
            ];

            $JWTcode = new JWTCodec;
            $accessToken = $JWTcode->encode($payload);
            $refToken = $JWTcode->encode($payload_ref);

            // store refresh token in db
            $this->storeRefreshToken($refToken , $exp_ref);

            print_r(json_encode(["jwt" => $accessToken , "refresh_token" => $refToken]));
        }

        public function logout() {
            $data = json_decode(file_get_contents("php://input") , true);

            if (!isset($data['token'])){
                $response = array(
                    'message' => 'refresh token required'
                );
                echo json_encode($response);
                http_response_code(400);
                exit;
            }

            $refresh_token = $data['token'];

            // validate if token stored in db
            if ($this->getStoreToken($refresh_token) === false)  {
                http_response_code(400);
                echo json_encode(["message" => "invalid token (not in whitelist)"]);
                exit;
            }

            $JWTcode = new JWTCodec;
            $jwt = $JWTcode->decode_ref($refresh_token);

            // validate if token have a user
            $user = $this->getUserByid($jwt["_id"]);
            if (mysqli_num_rows($user) === 0) {
                notFound();
            }

            $this->deleteRefreshToken($refresh_token);
        }

        public function getUserByid(int $id) {
            $sql = "SELECT u._id, u.name, u.email, u.password, ur.nameRole
            FROM user u
            INNER JOIN user_role ur
                ON u._idRole = ur._idRole WHERE _id = '$id';";
            $user = $this->executeQuery($sql);
            return $user;
        }

        public function authAccessToken(): mixed {
            if (!isset(apache_request_headers()["Authorization"])) {
                http_response_code(400);
                echo json_encode(["message" => "Unauthorized"]);
                return false;
            }
            
            if (!preg_match("/^Bearer\s+(.*)$/" , apache_request_headers()["Authorization"] , $matches)) {
                http_response_code(400);
                echo json_encode(["message" => "Unauthorized"]);
                return false;
            }
            
            $JWTcode = new JWTCodec;
            $decoded = $JWTcode->decode($matches[1]);
            return $decoded["_id"];
        }

        public function refreshAccessToken() {
            $data = json_decode(file_get_contents("php://input") , true);

            if (!isset($data['token'])){
                $response = array(
                    'message' => 'refresh token required'
                );
                echo json_encode($response);
                http_response_code(400);
                exit;
            }

            $refresh_token = $data['token'];

            // validate if token stored in db
            if ($this->getStoreToken($refresh_token) === false)  {
                http_response_code(400);
                echo json_encode(["message" => "invalid token (not in whitelist)"]);
                exit;
            }

            $JWTcode = new JWTCodec;
            $jwt = $JWTcode->decode_ref($refresh_token);

            // validate if token have a user
            $user = $this->getUserByid($jwt["_id"]);
            if (mysqli_num_rows($user) === 0) {
                notFound();
            }

            $user = $this->fetch($user);

            $payload = [
                "sub" => $user["_id"],
                "name" =>  $user["name"],
                "email" => $user["email"],
                "role" => $user["nameRole"],
                "exp" => time() + 1800
            ];
            $exp_ref = time() + 604800;
            $payload_ref = [
                "sub" => $user["_id"],
                "exp" => $exp_ref 
            ];

            $JWTcode = new JWTCodec;
            $accessToken = $JWTcode->encode($payload);
            $refToken = $JWTcode->encode($payload_ref);

            // store refresh token from db
            $this->storeRefreshToken($refToken , $exp_ref);
            // delete store refresh token from db
            $this->deleteRefreshToken($refresh_token); //

            print_r(json_encode(["jwt" => $accessToken , "refresh_token" => $refToken]));
        }

        public function CheckAccessToken() {
            $data = json_decode(file_get_contents("php://input") , true);

            if (!isset($data['token'])){
                $response = array(
                    'message' => 'token required'
                );
                echo json_encode($response);
                http_response_code(400);
                exit;
            }

            $token = $data['token'];

            $JWTcode = new JWTCodec;
            $jwt = $JWTcode->decode($token);

            // validate if token have a user
            $user = $this->getUserByid($jwt["_id"]);
            if (mysqli_num_rows($user) === 0) {
                notFound();
            }
            

            print_r(json_encode(["message" => "success"]));
        }

        private function storeRefreshToken(string $refreshToken , string $exp) {
            $token_hash = hash_hmac("sha256" , $refreshToken , $_ENV["SECRET_KEY"]);
            $sql = "INSERT INTO refresh_token(token_hash , exp) VALUES ('$token_hash' , '$exp')";
            $res = $this->executeQuery($sql);
            return $res;
        }

        private function deleteRefreshToken(string $refreshToken) {
            $token_hash = hash_hmac("sha256" , $refreshToken , $_ENV["SECRET_KEY"]);
            $sql = "DELETE FROM refresh_token WHERE token_hash = '$token_hash'";
            $this->executeQuery($sql);
        }

        private function getStoreToken(string $token) {
            $token_hash = hash_hmac("sha256" , $token , $_ENV["SECRET_KEY"]);
            $sql = "SELECT token_hash FROM refresh_token WHERE token_hash = '$token_hash'";
            $res = $this->executeQuery($sql);
            return mysqli_num_rows($res) !== 0 ? true : false;
        }
    }
?>
