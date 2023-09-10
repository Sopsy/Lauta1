<?php

if (empty($_POST['board']) OR !empty($_POST['thread'])) {
    $postBoard = false;
} else {
    $postBoard = $_POST['board'];
}

// Initialize the board engine
$loadClasses = [
    'cache' => '',
    'db' => '',
    'html' => true,
    'board' => [$postBoard, false, false],
    'user' => false,
    'posts' => '',
    'fileupload' => '',
];
include '../../inc/engine.class.php';
new Engine($loadClasses);

// Check some basic things
if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
    http_response_code(401);
    $engine->dieWithError(_('Your session has expired. Please refresh the page and try again.'));
}

if (empty($_POST)) {
    $engine->dieWithError(_('No message received (Re-sending by refreshing the page does not work).'));
}

if ($user->isBanned) {
    $engine->dieWithError(_('You are banned.'));
}
if (!isset($_POST['thread']) OR !is_numeric($_POST['thread'])) {
    $engine->dieWithError(_('Invalid thread.'));
}

// Cookieless users
if ($user->isLimited) {
    $engine->dieWithError(
        _(
            'This function requires you to have cookies enabled. Please allow cookies from your browser settings to continue.'
        )
    );
}

$userIp = $user->getIp();

// Get the thread if it's a reply
if ($_POST['thread'] != '0') {
    $isReply = true;
    $thread = $posts->getThread($_POST['thread']);
    if (!$thread) {
        $engine->dieWithError(_('Thread does not exist.'));
    }
    if (!$board->isLoaded OR $thread['board_id'] != $board->info['boardid']) {
        $board->getBoardInfo($thread['board_id'], false);
    }
    if ($thread['is_locked'] && !$user->isMod) {
        $engine->dieWithError(_('This thread is locked, and cannot be replied to.'));
    }
} else {
    $isReply = false;
    $thread['id'] = 0;
}

// Prevent the flooding of posts
if (!$posts->checkFloodPrevention($isReply)) {
    $engine->dieWithError(_('You are sending messages too fast. Please wait a while between your posts.'));
}
$posts->activateTempFloodPrevention($isReply); // Temp limit, gets replaced with the correct value when message is sent

if (!$board->isLoaded) {
    $engine->dieWithError(_('Invalid board'));
}
if ($board->isLocked AND !$user->isMod) {
    $engine->dieWithError(_('Your message was not saved because this discussion board is locked.'));
}
if ($board->info['url'] == 'bilderberg' AND !$user->hasGoldAccount) {
    $engine->dieWithError(_('Invalid board'));
} // Gold account special board!
if ($board->info['url'] == 'platina' AND !$user->hasPlatinumAccount) {
    $engine->dieWithError(_('Invalid board'));
} // Gold account special board!

// CSRF-check
if (empty($_SERVER['HTTP_X_CSRF_TOKEN']) || !hash_equals($user->csrf_token, $_SERVER['HTTP_X_CSRF_TOKEN'])) {
    $engine->dieWithError(_('Bad request'));
}

// Antispam by honeypot-field
if (!empty($_POST['author'])) {
    if ($_POST['author'] == 'grill' && !$user->hasTag('grill')) {
        $user->unlockTag('grill');
    }
    $engine->dieWithError(_('Your post was not accepted because you fell into the honeypot meant for robots.'));
}

// reCAPTCHA
if ($user->useCaptcha()) {
    if (empty($_POST['captcha'])) {
        $engine->dieWithError(
            _(
                'Your browser did not send us a Google reCAPTCHA response. Check that you are not blocking it. Please refresh this page and try again.'
            )
        );
    }

    $captchaOk = $engine->verifyReCaptchaV3($_POST['captcha']);
    if (!$captchaOk) {
        $engine->dieWithError(_('Google reCAPTCHA thinks you are a robot. Please refresh this page and try again.'));
    }
}

// Country detection
$ip2location = new \IP2Location\Database($engine->cfg->phpIp2LocationDBPath);
$ipCountryCode = strtoupper($ip2location->lookup($userIp, \IP2Location\Database::COUNTRY_CODE));

// Blacklists and whitelists
if (!$user->isWhitelisted()) {
    if ($engine->ipIsBanned($_SERVER['REMOTE_ADDR'])) {
        $engine->dieWithError(
            _(
                'The internet connection you are using was recently used for abuse, so posting from it is temporarily not allowed.'
            )
        );
    }

    if (!$engine->ipIsAllowed($_SERVER['REMOTE_ADDR'])) {
        $engine->dieWithError(_('The internet connection you are using is commonly used for abuse, so posting from it is not allowed.'));
    }

    // Temporary spam prevention
    if ($board->info['url'] !== 'aihevapaa' && $engine->antispam() && $user->account_created > (time() - $engine->antispam())) {
        $hours = ceil($engine->antispam() / 3600);
        $engine->dieWithError(sprintf(_('Temporary spam protection enabled - you will need an user account older than %d hour(s) to post.'), $hours));
    }

    // Semi-permanent for /pub/
    if ($board->info['url'] === 'pub' && $user->account_created > (time() - 86400)) {
        $engine->dieWithError(sprintf(_('Temporary spam protection enabled - you will need an user account older than %d hour(s) to post.'), 24));
    }

}

// Message options
$sage = false;
$goldHide = false;

if ((!empty($_POST['sage']) AND $_POST['sage'] == 'on')) {
    $sage = true;
    if (!$user->hasTag('assburger')) {
        $user->unlockTag('assburger');
    }
}
if (!empty($_POST['goldhide']) AND $_POST['goldhide'] == 'on' AND $user->hasGoldAccount) {
    $goldHide = true;
}

// File
class uploadedFile
{
    public $pngToJpg = false;

    public function __construct()
    {
        $this->tmpName = sys_get_temp_dir() . '/uploadedfile-' . time() . mt_rand(000000, 999999);
    }

    public function __destruct()
    {
        if (is_file($this->tmpName)) {
            unlink($this->tmpName);
        }
    }

    public function error()
    {
        if (!empty($this->thumbDest) && is_file($this->thumbDest)) {
            unlink($this->thumbDest);
        }
        if (!empty($this->destination) && is_file($this->destination)) {
            unlink($this->destination);
        }
    }
}

$hasFile = false;
$file = new uploadedFile();
if (!empty($_FILES['file']['tmp_name']) AND is_uploaded_file($_FILES['file']['tmp_name'])) {
    $hasFile = true;
    if (!move_uploaded_file($_FILES['file']['tmp_name'], $file->tmpName)) {
        $engine->dieWithError(_('Saving the file failed.'));
    }
    $file->name = $_FILES['file']['name'];
}

// Poster name
if (!empty($_POST['show_username'])) {
    $posterName = $user->info->username;
} else {
    $posterName = '';
}

// The message itself
$message = '';
if (isset($_POST['msg'])) {
    if (substr_count($_POST['msg'], "\n") > 200) {
        $engine->dieWithError(_('Your message has more newlines than anyone would need.'));
    }
    $message = $posts->formatMessage($_POST['msg']);
}

$strippedMessage = trim($posts->strip_bbcode($message));

if (mb_strlen($strippedMessage) == 0 && !$isReply) {
    $engine->dieWithError(_('A message is required to create a thread.'));
}

preg_match_all('/>>([0-9]+)/i', $message, $postReplies);
$postReplies = array_unique($postReplies[1]);

if (count($postReplies) > $engine->cfg->maxRepliesPerPost && !$user->hasGoldAccount) {
    $engine->dieWithError(
        sprintf(
            _(
                'To prevent spam, messages with more than %d replies are not allowed. You may remove this limit by purchasing a Gold account.'
            ),
            $engine->cfg->maxRepliesPerPost
        )
    );
}

// Post subject
$postSubject = '';
if (!$isReply) {
    if (!empty($_POST['subject'])) {
        $postSubject = trim($posts->removeForbiddenUnicode($_POST['subject']));
    } else {
        $postSubject = preg_replace('/\s\s+/', ' ', str_replace(["\n", "\r"], ' ', $message));
        $postSubject = $posts->strip_bbcode($postSubject);
        $postSubject = $posts->removeForbiddenUnicode($postSubject);
        $postSubject = $posts->truncate(trim($postSubject), 57);
    }
    $postSubject = trim(mb_substr($postSubject, 0, $engine->cfg->subjectMaxLength));
}

if (!$cache->exists('autobanWords')) {
    $autobanWords = $db->q("SELECT * FROM word_blacklist");
    $autobanWords = $db->fetchAll($autobanWords, 'word');
    $cache->set('autobanWords', json_encode($autobanWords), 60);
} else {
    $autobanWords = json_decode($cache->get('autobanWords'), true);
}

foreach ($autobanWords AS $autobanWord) {
    if (stripos($strippedMessage, $autobanWord) !== false || stripos($postSubject, $autobanWord) !== false) {
        if (!$user->hasGoldAccount) {
            $user->addBan(
                '',
                $user->info->id,
                $engine->cfg->wordAutobanLength,
                8,
                null,
                true
            );
            $engine->dieWithError(
                sprintf(
                    _('Your message contained a blacklisted word. Posting blocked for %d minute(s).'),
                    ceil($engine->cfg->wordAutobanLength / 60)
                )
            );
        } else {
            $engine->dieWithError(_('Your message contained a blacklisted word'));
        }
    }
}

$emptyMessage = mb_strlen($strippedMessage) == 0;
if ($isReply && $emptyMessage && !$hasFile) {
    $engine->dieWithError(_('A text or a file is required to send a message.'));
}

if ($hasFile) {
    $file->size = filesize($file->tmpName);

    if ($file->size > $engine->cfg->maxFileSize) {
        $engine->dieWithError(_('Your file is larger than the biggest allowed file size.'));
    }
    if ($file->size == 0) {
        $engine->dieWithError(
            _('The received file is empty, probably the file upload was interrupted. Please try again.')
        );
    }

    if (!is_dir($engine->cfg->filesDir) && !mkdir($engine->cfg->filesDir, 0775, true) && !is_dir(
            $engine->cfg->filesDir
        )) {
        $engine->dieWithError(_('Creating a file directory failed.'));
    }

    if (disk_free_space($engine->cfg->filesDir) < $engine->cfg->minFreeSpace || disk_free_space(
            $engine->cfg->filesDir
        ) < $file->size) {
        $engine->dieWithError(_('File uploads are temporarily disabled. Please try again in a while.'));
    }

    // Check for a blacklisted filename
    foreach ($autobanWords AS $autobanWord) {
        if (stripos($file->name, $autobanWord) !== false AND !$user->isMod) {
            if (!$user->hasGoldAccount) {
                $user->addBan(
                    $userIp,
                    $user->info->id,
                    $engine->cfg->wordAutobanLength,
                    8,
                    null,
                    true
                );
                $engine->redirectExit($engine->cfg->siteUrl . '/banned');
            } else {
                $engine->dieWithError(_('Your filename contained a blacklisted word'));
            }
        }
    }

    $file->extension = strtolower(pathinfo($file->name, PATHINFO_EXTENSION));
    if (!empty($_POST['show_filename'])) {
        $file->name = pathinfo($file->name, PATHINFO_FILENAME);
        $file->name = str_replace('.', '_', $file->name);

        // Check if name is empty
        if (empty($file->name)) {
            $file->name = null;
        }
    } else {
        $file->name = null;
    }

    $file->md5 = [md5_file($file->tmpName)];
    $q = $db->q("SELECT `file_id` FROM `file_md5` WHERE `md5` = UNHEX('" . $db->escape($file->md5[0]) . "') LIMIT 1");
    if ($q->num_rows == 1) {
        $fileInfo = $q->fetch_assoc();
        $file->id = $fileInfo['file_id'];
    } else {
        $mime = $fileupload->getMimeType($file->tmpName);
        switch ($mime) {
            case 'image/png':
                $file->extension = 'png';
                break;
            case 'image/jpeg':
            case 'image/pjpeg':
                $file->extension = 'jpg';
                break;
            case 'image/gif':
                $file->extension = 'gif';
                break;
            case 'image/webp':
                $file->extension = 'webp';
                break;
            case 'audio/mpeg':
                $file->extension = 'mp3';
                break;
            case 'audio/aac':
            case 'audio/mp4':
                $file->extension = 'm4a';
                break;
            case 'video/mp4':
            case 'video/x-m4v':
                $file->extension = 'mp4';
                break;
            case 'video/quicktime4':
                $file->extension = 'mov';
                break;
            case 'video/webm':
            case 'audio/webm':
                $file->extension = 'webm';
                break;
        }

        $fileIsAllowed = $fileupload->typeIsAllowed($file->tmpName, $file->extension);
        if ($fileIsAllowed !== true) {
            if ($fileIsAllowed === false) {
                $engine->dieWithError(_('The type of the file you sent is not allowed.'));
            } elseif (is_array($fileIsAllowed)) {
                $engine->dieWithError(
                    sprintf(
                        _(
                            'Invalid file extension or corrupted file. The file extension is %s, but the detected file type was %s.'
                        ),
                        htmlspecialchars($file->extension, ENT_QUOTES | ENT_HTML5),
                        $fileIsAllowed[0]
                    )
                );
            } else {
                $engine->dieWithError(_('The type of the file you sent is not allowed.'));
            }
        }

        // File type conversions
        if ($file->extension === 'jpeg') {
            // Just for support
            $file->extension = 'jpg';
        } elseif ($file->extension === 'webp') {
            // WebP to JPEG
            $file->extension = 'jpg';
            shell_exec(
                'nice --adjustment=18 convert ' . escapeshellarg($file->tmpName) . ' jpg:' . escapeshellarg(
                    $file->tmpName . '.' . $file->extension
                )
            );
            if (!is_file($file->tmpName . '.' . $file->extension)) {
                $engine->dieWithError(_('An error occurred while saving the file. The file may be corrupted.'));
            }
            unlink($file->tmpName);
            rename($file->tmpName . '.' . $file->extension, $file->tmpName);
            $file->md5[] = md5_file($file->tmpName);
            $file->size = filesize($file->tmpName);
        } elseif ($file->extension === 'gif') {
            // GIFs into MP4 or JPG
            $frames = shell_exec(
                'nice --adjustment=10 gifsicle --info ' . escapeshellarg(
                    $file->tmpName
                ) . ' | head -n 1 | awk \'{print $3}\''
            );

            $imageSize = getimagesize($file->tmpName);
            if (!$imageSize) {
                $engine->dieWithError(_('An error occurred while saving the file. The file may be corrupted.'));
            }

            // Limit image pixels
            if ($imageSize[0] * $imageSize[1] > $engine->cfg->maxImageSize) {
                $engine->dieWithError(
                    _(
                        'The dimensions of the image you uploaded are too big. Scale it down with an image editing software.'
                    )
                );
            }

            if ($frames > 1) {
                if ($frames > $engine->cfg->gifMaxFrames) {
                    $engine->dieWithError(_('The GIF you uploaded is too long. Please upload a video file instead.'));
                }

                // Convert animated gif to video
                shell_exec(
                    'nice --adjustment=18 ffmpeg -f gif -i ' . escapeshellarg(
                        $file->tmpName
                    ) . ' -threads 0 -c:v libx264 -crf 23 -preset:v veryfast -filter_complex scale="trunc(in_w/2)*2:trunc(in_h/2)*2" -an -sn ' . escapeshellarg(
                        $file->tmpName . '.mp4'
                    )
                );

                if (!is_file($file->tmpName . '.mp4') || filesize($file->tmpName . '.mp4') === 0) {
                    unlink($file->tmpName . '.mp4');
                    $engine->dieWithError(_('An error occurred while saving the file. The file may be corrupted.'));
                }
                unlink($file->tmpName);
                rename($file->tmpName . '.mp4', $file->tmpName);
                $file->extension = 'mp4';
            } else {
                // Non-animated into jpg
                $file->extension = 'jpg';
                shell_exec(
                    'nice --adjustment=18 convert ' . escapeshellarg($file->tmpName) . ' ' . escapeshellarg(
                        $file->tmpName . '.' . $file->extension
                    )
                );
                if (!is_file($file->tmpName . '.' . $file->extension)) {
                    $engine->dieWithError(_('An error occurred while saving the file. The file may be corrupted.'));
                }
                unlink($file->tmpName);
                rename($file->tmpName . '.' . $file->extension, $file->tmpName);
            }
            $file->md5[] = md5_file($file->tmpName);
            $file->size = filesize($file->tmpName);
        } elseif ($file->extension === 'png') {
            // Limit image pixels
            $imageSize = getimagesize($file->tmpName);
            if ($imageSize && ($imageSize[0] * $imageSize[1]) > $engine->cfg->maxImageSize) {
                $engine->dieWithError(
                    _(
                        'The dimensions of the image you uploaded are too big. Scale it down with an image editing software.'
                    )
                );
            }

            // png to jpg
            $fileupload->createImage($file->tmpName, $file->tmpName . '.tmp', 3840, 3840);

            if (!is_file($file->tmpName . '.tmp') || filesize($file->tmpName . '.tmp') === 0) {
                $file->error();
                if (is_file($file->tmpName . '.tmp')) {
                    unlink($file->tmpName . '.tmp');
                }
                $engine->dieWithError(_('An error occurred while saving the file.'));
            }

            unlink($file->tmpName);
            rename($file->tmpName . '.tmp', $file->tmpName);
            $file->md5[] = md5_file($file->tmpName);
            $file->size = filesize($file->tmpName);
            $file->extension = 'jpg';
            $file->pngToJpg = true;
        } elseif (in_array($file->extension, ['webm', 'mkv', 'mov'])) {
            // Conversion is done after posting
            $file->extension = 'mp4';
        } elseif (in_array($file->extension, ['mp3', 'aac', 'wav'])) {
            // Conversion is done after posting
            $file->extension = 'm4a';
        }

        // Saving the file
        if ($file->extension === 'jpg' || $file->extension === 'png') {
            // Limit image pixels
            if (!$file->pngToJpg) {
                $imageSize = getimagesize($file->tmpName);
                if ($imageSize && ($imageSize[0] * $imageSize[1]) > $engine->cfg->maxImageSize) {
                    $engine->dieWithError(
                        _(
                            'The dimensions of the image you uploaded are too big. Scale it down with an image editing software.'
                        )
                    );
                }
            }

            // Rotate and optimize
            if ($file->extension === 'jpg') {
                if (!$file->pngToJpg) {
                    $fileupload->jheadAutorot($file->tmpName);
                    $image = $fileupload->createImage($file->tmpName, $file->tmpName . '.tmp', 3840, 3840);
                    unlink($file->tmpName);
                    rename($file->tmpName . '.tmp', $file->tmpName);
                }
                $imageSize = getimagesize($file->tmpName);
                $img = $fileupload->jpegtran($file->tmpName, true);
            } elseif ($file->extension === 'png') {
                $img = $fileupload->pngcrush($file->tmpName);
            }

            if (!is_file($file->tmpName) || filesize($file->tmpName) == 0) {
                $file->error();
                $engine->dieWithError(_('An error occurred while saving the file.'));
            }

            $file->md5[] = md5_file($file->tmpName);
            $file->size = filesize($file->tmpName);

            if (!$img) {
                $file->error();
                $engine->dieWithError(_('An error occurred while saving the file. The file could be corrupted.'));
            }
        } elseif ($file->extension == 'm4a') {
            // Get duration and bitrate with ffprobe
            $streams = shell_exec(
                'nice --adjustment=19 ffprobe -of json -show_streams ' . escapeshellarg($file->tmpName)
            );
            $streams = json_decode($streams, true)['streams'];
            if (empty($streams[0]['duration']) || empty($streams[0]['bit_rate'])) {
                $engine->dieWithError(
                    _(
                        'Cannot determine the duration or bitrate of the audio file you uploaded. The file may be corrupted.'
                    )
                );
            }

            $file->duration = (int)round($streams[0]['duration']);
            $file->bitrate = (int)$streams[0]['bit_rate'];

            if ($file->bitrate < 8000) {
                $engine->dieWithError(_('Audio bitrate too low. This file cannot be saved.'));
            }

            if ($file->duration > $engine->cfg->audioMaxLength) {
                $engine->dieWithError(_('The audio file you tried to upload is too long.'));
            }

            $file->size = filesize($file->tmpName);

            if (!is_file($file->tmpName)) {
                $engine->dieWithError(_('An error occurred while saving the file.'));
            }

            $fileNeedsProcessing = true;
        } elseif (in_array($file->extension, ['mp4', 'webm', 'flv', 'mkv', 'mov'])) {
            $probe = shell_exec(
                'nice --adjustment=19 ffprobe -show_streams -of json ' . escapeshellarg($file->tmpName) . ' -v quiet'
            );
            $videoInfo = json_decode($probe, true);

            if (empty($videoInfo['streams'])) {
                $engine->dieWithError(_('No streams found in the uploaded file. The file may be corrupted.'));
            }

            $videoInfo = $videoInfo['streams'];

            // Figure out which stream to use info from and if the file has sound
            $file->hasSound = 0;
            foreach ($videoInfo AS $key => $stream) {
                if ($stream['codec_type'] == 'video' && empty($streamNum)) {
                    $streamNum = $key;
                } elseif ($stream['codec_type'] == 'audio') {
                    $file->hasSound = 1;
                }
            }

            $streams = count($videoInfo);
            if (!isset($streamNum) || $streams == 0) {
                $engine->dieWithError(_('No video streams found in the uploaded file. The file may be corrupted.'));
            }

            if (!isset($videoInfo[$streamNum]['duration']) || $videoInfo[$streamNum]['duration'] == 'N/A') {
                $videoInfo[$streamNum]['duration'] = (int)shell_exec(
                    'nice --adjustment=19 ffmpeg -i ' . escapeshellarg(
                        $file->tmpName
                    ) . ' 2>&1 | grep "Duration"| cut -d " " -f 4 | sed s/,// | sed "s@\..*@@g" | awk \'{ split($1, A, ":"); split(A[3], B, "."); print 3600*A[1] + 60*A[2] + B[1] }\''
                );
            }

            if (!isset($videoInfo[$streamNum]['duration'])) {
                $engine->dieWithError(
                    _('Cannot determine the duration of the video you uploaded. The file may be corrupted.')
                );
            }

            $file->duration = (int)$videoInfo[$streamNum]['duration'];

            if ($file->duration > $engine->cfg->videoMaxLength) {
                $engine->dieWithError(sprintf(_('The video you tried to upload is too long. Max length is %d minutes.'),
                    round($engine->cfg->videoMaxLength / 60, 0)));
            }

            shell_exec(
                'nice --adjustment=19 ffmpeg -i ' . escapeshellarg(
                    $file->tmpName
                ) . ' -vframes 1 -f image2 ' . escapeshellarg($file->tmpName . '.thumb')
            );
            if (!is_file($file->tmpName . '.thumb')) {
                $engine->dieWithError(_('Generating the thumbnail failed. Please try again.'));
            }

            $thumbnail = $fileupload->createImage($file->tmpName . '.thumb', $file->tmpName . '.thumb.tmp', 960, 960);
            unlink($file->tmpName . '.thumb');
            if (!$thumbnail) {
                $engine->dieWithError(_('Generating the thumbnail failed. Please try again.'));
            }

            rename($file->tmpName . '.thumb.tmp', $file->tmpName . '.thumb');
            $hasThumb = true;

            $file->size = filesize($file->tmpName);
            $fileNeedsProcessing = true;
        }

        if (isset($file->duration)) {
            $file->duration = (int)round(str_replace(',', '.', $file->duration), 0);
        }

        // Save file to database
        $add = $db->q(
            "INSERT INTO `file` (
                `extension`,
                `duration`,
                `has_sound`
            ) VALUES (
                '" . $db->escape($file->extension) . "',
                " . (!isset($file->duration) ? 'NULL' : (int)$file->duration) . ",
                " . (!isset($file->hasSound) ? 'NULL' : (int)$file->hasSound) . "
            )"
        );
        if ($add) {
            $file->id = $db->mysql0->insert_id;

            $filename = base_convert($file->id, 10, 36);
            $folder = "{$filename[0]}/{$filename[1]}/{$filename[2]}";
            $destination = $engine->cfg->filesDir . '/' . $folder . '/' . $filename;

            // Create destination folder
            if (!is_dir($engine->cfg->filesDir . '/' . $folder)
                && !mkdir($engine->cfg->filesDir . '/' . $folder, 0775, true)
                && !is_dir($engine->cfg->filesDir . '/' . $folder)) {
                die("directory creation failed: {$folder}\n");
            }

            $moveOrigFile = rename($file->tmpName, "{$destination}.{$file->extension}");
            if (!$moveOrigFile OR !is_file("{$destination}.{$file->extension}")) {
                $db->q('DELETE FROM file WHERE id = ' . (int)$file->id . ' LIMIT 1');
                $engine->dieWithError(_('An error occurred while saving the file'));
            }

            if (!empty($hasThumb)) {
                $moveThumb = rename("{$file->tmpName}.thumb", "{$destination}.jpg");
                if (!$moveThumb OR !is_file("{$destination}.jpg")) {
                    $db->q('DELETE FROM file WHERE id = ' . (int)$file->id . ' LIMIT 1');
                    $engine->dieWithError(_('An error occurred while saving the file'));
                }
            }

            if (isset($fileNeedsProcessing)) {
                $db->q("INSERT INTO file_processing (file_id) VALUES (" . (int)$file->id . ")");
            }

            $md5 = implode("'), '" . (int)$file->id . "'), (UNHEX('", array_unique($file->md5));
            $db->q("INSERT INTO `file_md5` (`md5`, `file_id`) VALUES (UNHEX('" . $md5 . "'), " . (int)$file->id . ")");

            if ($file->extension == 'mp4') {
                $cmd = 'php ' . escapeshellarg(
                        $engine->cfg->siteDir . '/scripts/convertvideo.php'
                    ) . ' ' . escapeshellarg("{$destination}.{$file->extension}") . ' ' . escapeshellarg($file->id);
                shell_exec(sprintf('%s > /dev/null 2>&1 &', $cmd));
            } elseif ($file->extension == 'm4a') {
                $cmd = 'php ' . escapeshellarg(
                        $engine->cfg->siteDir . '/scripts/convertaudio.php'
                    ) . ' ' . escapeshellarg("{$destination}.{$file->extension}") . ' ' . escapeshellarg($file->id);
                shell_exec(sprintf('%s > /dev/null 2>&1 &', $cmd));
            }
        } else {
            if (is_file("{$file->tmpName}.thumb")) {
                unlink("{$file->tmpName}.thumb");
            }
            if (is_file($file->tmpName)) {
                unlink($file->tmpName);
            }
            $engine->dieWithError(_('An error occurred while saving the file.'));
        }
    }
}

// Check tags
$tags = [];
if (!empty($_POST['posttag'])) {
    foreach ($_POST['posttag'] AS $postTag => $postTagOn) {
        if ($postTagOn != 'on') {
            continue;
        }
        if ($user->hasTag($postTag)) {
            $tags[] = $postTag;
        }
    }
}

// Thread specific public IDs
if (!$isReply || $thread['user_id'] == $user->id) {
    $publicUserId = 0;
} else {
    $getPublicId = $db->q("SELECT public_user_id FROM post WHERE thread_id = " . (int)$thread['id'] . " AND user_id = " . (int)$user->id . " LIMIT 1");
    if ($getPublicId->num_rows === 0) {
        $getPublicId = $db->q("SELECT COALESCE(MAX(public_user_id)+1, 1) AS public_user_id FROM post WHERE thread_id = " . (int)$thread['id'] . " LIMIT 1");
    }

    $publicUserId = $getPublicId->fetch_assoc()['public_user_id'];
}

if (!empty($_POST['admin_tag']) && $user->isMod) {
    $adminPost = true;
} else {
    $adminPost = false;
}

// Modpost
$locked = 0;
$sticky = 0;
if ($user->isMod) {
    // Check checkboxes
    if (!empty($_POST['lockthread']) AND $_POST['lockthread'] == "on" AND $user->hasPermissions("updatethread")) {
        $locked = 1;
    }
    if (!empty($_POST['stickthread']) AND $_POST['stickthread'] == "on" AND $user->hasPermissions("updatethread")) {
        $sticky = 1;
    }
}

if (!$isReply) {
    $q = $db->q('
        INSERT INTO thread (
            user_id,
            board_id,
            subject,
            is_locked,
            is_sticky
        ) VALUES (
            ' . (int)$user->id . ',
            ' . (int)$board->info['boardid'] . ',
            "' . $db->escape($postSubject) . '",
            ' . (int)$locked . ',
            ' . (int)$sticky . '
        )
    ');

    if (!$q) {
        if (!empty($file->destination) AND is_file($file->destination)) {
            unlink($file->destination);
        }
        if (!empty($file->thumbDest) AND is_file($file->thumbDest)) {
            unlink($file->thumbDest);
        }
        $engine->dieWithError(_('Creating the thread failed.'));
    }

    $thread['id'] = $db->mysql0->insert_id;
}

$add = $db->q(
    "
    INSERT INTO `post`(
        `user_id`,
        `thread_id`,
        `ip`,
        `remote_port`,
        `country_code`,
        `public_user_id`,
        `name`,
        `message`,
        `op_post`,
        `admin_post`,
        `gold_hide`
    )
    VALUES (
        " . (int)$user->id . ",
        " . (int)$thread['id'] . ",
        INET6_ATON('" . $db->escape($userIp) . "'),
        " . (empty($_SERVER['REMOTE_PORT']) ? 0 : (int)$_SERVER['REMOTE_PORT']) . ",
        " . (empty($ipCountryCode) ? 'NULL' : "'" . $db->escape($ipCountryCode) . "'") . ",
        " . (int)$publicUserId . ",
        " . (empty($posterName) ? 'NULL' : "'" . $db->escape($posterName) . "'") . ",
        '" . $db->escape($message) . "',
        " . ($isReply ? 0 : 1) . ",
        " . ($adminPost ? 1 : 0) . ",
        " . ($goldHide ? 1 : 0) . "
    )
"
);
if ($add) {
    $postid = $db->mysql0->insert_id;

    if ($adminPost) {
        $engine->writeModlog(3, '', $postid, $thread['id'], (int)$board->info['boardid']);
    }

    // Add tags
    if (!empty($tags)) {
        $tagValues = '';
        foreach ($tags as $tag) {
            $tagValues .= '(' . (int)$postid . ', "' . $db->escape($tag) . '"),';
        }
        $tagValues = substr($tagValues, 0, -1);
        $db->q('INSERT INTO post_tag (post_id, tag_id) VALUES ' . $tagValues);
    }

    // Lock thread if too many replies
    if ($isReply) {
        $threadq = $db->q("SELECT COUNT(*) AS count FROM post WHERE thread_id = " . (int)$thread['id'] . " AND op_post = 0 LIMIT 1");
        $thread_info = $threadq->fetch_assoc();
        $reply_count = (int)$thread_info['count'];
        if ($reply_count >= $engine->cfg->maxThreadReplies) {
            $db->q("UPDATE thread SET is_locked = 1 WHERE id = " . (int)$thread['id'] . " LIMIT 1");
        }
    }

    // Update thread post count
    if ($isReply) {
        $q = $db->q(
            "SELECT COUNT(*) AS count, COUNT(DISTINCT user_id) AS distinct_count FROM post WHERE thread_id = " . (int)$thread['id'] . " AND op_post = 0 LIMIT 1"
        );
        $counts = $q->fetch_assoc();
        $reply_count = $counts['count'];
        $distinct_count = $counts['distinct_count'];
        $db->q(
            "UPDATE thread SET reply_count = " . (int)$reply_count . ", distinct_reply_count = " . (int)$distinct_count . " WHERE id = " . (int)$thread['id'] . " LIMIT 1"
        );

        $q = $db->q(
            "SELECT id FROM post WHERE thread_id = " . (int)$thread['id'] . " AND user_id = " . (int)$user->id . " LIMIT 1"
        );
        if ($q->num_rows == 0) {
            $db->q(
                "UPDATE thread SET distinct_reply_count = distinct_reply_count+1 WHERE id = " . (int)$thread['id'] . " LIMIT 1"
            );
        }

        // Bump the thread
        if (!$sage) {
            $db->q("UPDATE thread SET bump_time = NOW() WHERE id = " . (int)$thread['id'] . " LIMIT 1");
        }
    }

    // File
    if ($hasFile) {
        $addFile = $db->q(
            "INSERT INTO `post_file` (`post_id`, `file_id`, `orig_name`) VALUES
            (" . (int)$postid . ", " . (int)$file->id . ", " . ($file->name === null ? 'NULL' : "'" . $db->escape(
                    $file->name
                ) . "'") . ")"
        );
        if (!$addFile) {
            if (!empty($file->destination) AND is_file($file->destination)) {
                unlink($file->destination);
            }
            if (!empty($file->thumbDest) AND is_file($file->thumbDest)) {
                unlink($file->thumbDest);
            }
        }
    }

    // Midsummer beer -tag
    if (!$user->hasTag('midsummer_beer') && date('j') >= 20 && date('j') <= 26 && date('N') == 6 && date('n') == 6) {
        $user->unlockTag('midsummer_beer');
    }

    // Profile counters
    if (!$isReply) {
        $user->incrementStats('total_threads');
    }
    $user->incrementStats('total_posts');
    $user->incrementStats('total_post_characters', mb_strlen($message));

    if ($hasFile) {
        $user->incrementStats('total_uploaded_files');
        $user->incrementStats('total_uploaded_filesize', $file->size);
    }

    // Notifications
    $notified_users = [$user->id];

    // Post reply
    if (!empty($postReplies)) {
        $repliedPosts = implode(',', array_map('intval', $postReplies));
        $q = $db->q("SELECT id, user_id FROM post WHERE id IN (" . $repliedPosts . ")");
        while ($row = $q->fetch_assoc()) {
            if (!empty($thread['user_id']) && $thread['user_id'] == $row['user_id']) {
                continue;
            }
            $user->addNotification('post_reply', $row['user_id'], $postid, 'NULL', 'NULL', $row['id']);
            $notified_users[] = $row['user_id'];
        }

        // Save replies to message
        $posts->saveReplies($postid, $postReplies);
    }

    // Thread reply
    if ($isReply && !in_array($thread['user_id'], $notified_users)) {
        $user->addNotification('thread_reply', $thread['user_id'], $postid, $thread['subject'], $thread['id']);
        $notified_users[] = $thread['user_id'];
    }

    // Followed reply
    $qf = $db->q("SELECT user_id FROM user_thread_follow WHERE thread_id = " . (int)$thread['id'] . " AND `user_id` != " . (int)$user->id);
    $users_following = $qf->fetch_all(MYSQLI_NUM);
    $users_following = array_map('current', $users_following);

    foreach ($users_following AS $user_following) {
        if (in_array($user_following, $notified_users)) {
            continue;
        }
        $user->addNotification('followed_reply', $user_following, $postid, $thread['subject'], $thread['id']);
        $notified_users[] = $user_following;
    }

    // Remove possibly read notifications
    if ($isReply) {
        $user->markNotificationsAsReadByThreadId($thread['id']);

        if ($user->id == $thread['user_id']) {
            $user->markNotificationsAsReadByPostId($thread['id']);
        }
    }

    // Increase threadFollow unreadCount by one
    $db->q(
        "UPDATE `user_thread_follow` SET `unread_count` = `unread_count` + 1 WHERE `thread_id` = " . (int)$thread['id'] . " AND `user_id` != " . (int)$user->id
    );

    if (!in_array($thread['id'], $user->info->followedThreads)) {
        // Autofollow
        if (!$isReply AND $user->getPreferences('auto_follow') == 1) {
            $user->followThread($thread['id']);
        } elseif ($isReply AND $user->getPreferences('auto_follow_reply') == 1) {
            $user->followThread($thread['id']);
        }
    }
    // Reset the unreadCount for the thread we just posted into
    $db->q(
        "UPDATE `user_thread_follow` SET `unread_count` = 0 WHERE `thread_id` = " . (int)$thread['id'] . " AND `user_id` = " . (int)$user->id
    );

    // Prevent the flooding of posts
    $posts->activateFloodPrevention($isReply);

    if (!$isReply) {
        echo 'OK:' . $engine->cfg->siteUrl . '/' . $board->info['url'] . '/' . $thread['id'];
    }
} else {
    if (!empty($file->destination) AND is_file($file->destination)) {
        unlink($file->destination);
    }
    if (!empty($file->thumbDest) AND is_file($file->thumbDest)) {
        unlink($file->thumbDest);
    }
    $engine->dieWithError(_('Saving the message failed.'));
}
