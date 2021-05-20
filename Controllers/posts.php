<?php
require_once 'Classes/Post.php';


function route($method, $controllerData, $requestData)
{
    global $connect;
    $headers = getallheaders();

    if (isset($headers["Authorization"])) $generalAccessLevel = GetGeneralAccessLevel($headers["Authorization"]);
    else $generalAccessLevel = UNAUTHORIZED_ACCESS_LEVEL;



    if ($method == 'GET') {

        if (Count($controllerData) === 0) {
            $posts = $connect->query("SELECT * FROM `post`");
            printJSON($posts);
            exit();
        } //   /posts:  Get all posts

        else if (Count($controllerData) === 1) {
            if (is_numeric($controllerData[0])) {
                $postId = (int)$controllerData[0];
                $post = $connect->query("SELECT * FROM `post` WHERE `id` = $postId");

                if ($post->num_rows == 0) {
                    Entity_Not_Found_404();
                }

                printJSON($post);
                exit();
            } //  /posts/{id}:  Get a single post by Id
        }
    }


    else if ($method == 'POST') {
        if (Count($controllerData) === 0) {
            if (is_string($requestData->Text) && !empty($requestData->Text)) {
                if ($generalAccessLevel > UNAUTHORIZED_ACCESS_LEVEL) {

                    $userId = getUserId($headers["Authorization"]);

                    $model = new Post(
                        Post::validateText($requestData->Text),
                        date('Y-m-d h:i:s', time())
                    );


                    $text = $model->getText();
                    $date = $model->getDate();

                    $connect->query("INSERT INTO `post` (`Id`, `Text`, `Date`, `User_Id`) VALUES (NULL, '$text', '$date', '$userId')");
                    exit();
                }
            }
            else BadRequest_400();
        } // /posts: Create a new post (Authorized)
    }


    else if ($method == 'PATCH') {
        if (Count($controllerData) === 1) {
            if (is_numeric($controllerData[0])) {

                if ($generalAccessLevel > UNAUTHORIZED_ACCESS_LEVEL) $ownerAccess = IdentifyOwner($headers["Authorization"], (int)$controllerData[0], 'post');
                else $ownerAccess = ACCESS_DENIED;

                $postId = (int)$controllerData[0];
                $post = $connect->query("SELECT * FROM `post` WHERE `post`.`Id` = '$postId'");

                if ($post->num_rows == 0) {
                    Entity_Not_Found_404();
                }

                if ($generalAccessLevel >= MODERATOR_ACCESS_LEVEL || $ownerAccess == ACCESS_ALLOWED) {

                    $model = new Post(
                        Post::validateText($requestData->Text),
                        date('Y-m-d h:i:s', time())
                    );

                    $text = $model->getText();
                    $date = $model->getDate();

                    $connect->query("UPDATE `post` SET `Text` = '$text', `Date` = '$date' WHERE `post`.`Id` = '$postId'");
                    exit();
                }
                Forbidden_403();
            } // /posts/{id}: Update an existing post (Owner and =< Moderator)
            else BadRequest_400();
        }
    }


    else if ($method == 'DELETE') {
        if (Count($controllerData) === 1) {
            if (is_numeric($controllerData[0])) {

                if ($generalAccessLevel > UNAUTHORIZED_ACCESS_LEVEL) $ownerAccess = IdentifyOwner($headers["Authorization"], (int)$controllerData[0], 'post');
                else $ownerAccess = ACCESS_DENIED;

                $postId = (int)$controllerData[0];
                $post = $connect->query("SELECT * FROM `post` WHERE `post`.`Id` = '$postId'");

                if ($post->num_rows == 0) {
                    Entity_Not_Found_404();
                }

                if ($generalAccessLevel >= MODERATOR_ACCESS_LEVEL || $ownerAccess == ACCESS_ALLOWED) {
                    $connect->query("DELETE FROM `post` WHERE `post`.`Id` = '$postId'");
                    exit();
                }
                Forbidden_403();
            } // /posts/{id}: Delete an existing post (Owner and =< Moderator)
            else BadRequest_400();
        }
    }

}