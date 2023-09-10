<?php

if (!empty($_POST['post_id']) AND is_numeric($_POST['post_id'])) {
    // Initialize the board engine
    $loadClasses = [
        'cache' => '',
        'db' => '',
        'html' => true,
        'posts' => '',
        'user' => false,
    ];
    include("../../inc/engine.class.php");
    new Engine($loadClasses);

    if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
        http_response_code(401);
        die(_('Your session has expired. Please refresh the page and try again.'));
    }

    if ($user->activity_points < 50) {
        http_response_code(403);
        die(_('You don\'t have have enough activity to upvote'));
    }

    // Shadow blocked users
    if (in_array($user->id, [36337062, 12546748])) {
        $user->incrementStats('total_upboats_given');
        die();
    }

    $post = $posts->getPost($_POST['post_id']);
    if (!$post) {
        http_response_code(404);
        die(_('Message does not exist'));
    }
    if ($post['user_id'] == $user->id) {
        http_response_code(403);
        die(_('You cannot upvote your own posts'));
    }

    if ($posts->addThis($_POST['post_id'])) {
        $user->incrementStats('total_upboats_received', 1, $post['user_id']);
        $user->incrementStats('total_upboats_given');

        $user->addNotification('post_upboated', $post['user_id'], $post['id']);
    } else {
        die(_('You have already upvoted this post'));
    }
}
