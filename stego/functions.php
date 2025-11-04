<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../../templates/functions.php';

if (!isset($_SESSION['id'])){
    header("Location:../../index.php?msg1");
    exit();
}

function embedMessage($imagePath, $message, $outputPath) {
    $img = imagecreatefrompng($imagePath);
    if (!$img) {
        die('Unable to open image.');
    }

    $message .= chr(0);
    $messageBin = '';
    for ($i = 0; $i < strlen($message); $i++) {
        $messageBin .= str_pad(decbin(ord($message[$i])), 8, '0', STR_PAD_LEFT);
    }

    $messageLength = strlen($messageBin);
    $width = imagesx($img);
    $height = imagesy($img);
    $pixelCount = $width * $height;

    if ($messageLength > $pixelCount) {
        die('Message is too long to fit in the image.');
    }

    $messageIndex = 0;
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            if ($messageIndex < $messageLength) {
                $rgb = imagecolorat($img, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                $newB = ($b & 0xFE) | $messageBin[$messageIndex];
                $newColor = imagecolorallocate($img, $r, $g, $newB);
                imagesetpixel($img, $x, $y, $newColor);

                $messageIndex++;
            }
        }
    }

    imagepng($img, $outputPath);
    imagedestroy($img);
}

function extractMessage($imagePath) {
    $img = imagecreatefrompng($imagePath);
    if (!$img) {
        die('Unable to open image.');
    }

    $width = imagesx($img);
    $height = imagesy($img);
    $messageBin = '';

    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $rgb = imagecolorat($img, $x, $y);
            $b = $rgb & 0xFF;
            $messageBin .= $b & 1;
        }
    }

    $message = '';
    for ($i = 0; $i < strlen($messageBin); $i += 8) {
        $byte = substr($messageBin, $i, 8);
        if ($byte === '00000000') {
            break;
        }
        $message .= chr(bindec($byte));
    }

    imagedestroy($img);
    return $message;
}