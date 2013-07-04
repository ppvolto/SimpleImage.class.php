<?php

/*
 * File: SimpleImage.php
 * Author: Simon Jarvis
 * Copyright: 2006 Simon Jarvis
 * Date: 08/11/06
 * Link: http://www.white-hat-web-design.co.uk/articles/php-image-resizing.php
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details:
 * http://www.gnu.org/licenses/gpl.html
 *
 */

class SimpleImage {

    var $image;
    var $image_type;

    function load($filename) {

        $image_info = getimagesize($filename);
        $this->image_type = $image_info[2];
        if ($this->image_type == IMAGETYPE_JPEG) {
            $this->image = imagecreatefromjpeg($filename);
        } elseif ($this->image_type == IMAGETYPE_GIF) {
            $this->image = imagecreatefromgif($filename);
        } elseif ($this->image_type == IMAGETYPE_PNG) {
            $this->image = imagecreatefrompng($filename);
        }
    }

    function save($filename, $image_type = IMAGETYPE_JPEG, $compression = 75, $permissions = null) {
        if ($image_type == IMAGETYPE_JPEG) {
            imagejpeg($this->image, $filename, $compression);
        } elseif ($image_type == IMAGETYPE_GIF) {
            imagegif($this->image, $filename);
        } elseif ($image_type == IMAGETYPE_PNG) {
            imagepng($this->image, $filename);
        }
        if ($permissions != null) {
            chmod($filename, $permissions);
        }
    }

    function output($image_type = IMAGETYPE_JPEG) {
        if ($image_type == IMAGETYPE_JPEG) {
            imagejpeg($this->image);
        } elseif ($image_type == IMAGETYPE_GIF) {
            imagegif($this->image);
        } elseif ($image_type == IMAGETYPE_PNG) {
            imagepng($this->image);
        }
    }

    function getWidth() {
        return imagesx($this->image);
    }

    function getHeight() {
        return imagesy($this->image);
    }

    function resizeToHeight($height) {
        $ratio = $height / $this->getHeight();
        $width = $this->getWidth() * $ratio;
        $this->resize($width, $height);
    }

    function resizeToWidth($width) {
        $ratio = $width / $this->getWidth();
        $height = $this->getheight() * $ratio;
        $this->resize($width, $height);
    }

    function scale($scale) {
        $width = $this->getWidth() * $scale / 100;
        $height = $this->getheight() * $scale / 100;
        $this->resize($width, $height);
    }

    function resize($width, $height) {
        $new_image = $this->createImage($width, $height);
        imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
        $this->image = $new_image;
    }

    public function crop($x, $y, $width, $height) {
        $new_image = $this->createImage($width, $height);
        imagecopyresampled($new_image, $this->image, 0, 0, $x, $y, $width, $height, $width, $height);
        $this->image = $new_image;
    }

    private function createImage($width, $height) {
        $new_image = imagecreatetruecolor($width, $height);
        if ($this->image_type == IMAGETYPE_GIF || $this->image_type == IMAGETYPE_PNG) {
            $current_transparent = imagecolortransparent($this->image);
            if ($current_transparent != -1) {
                $transparent_color = imagecolorsforindex($this->image, $current_transparent);
                $current_transparent = imagecolorallocate($new_image, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']);
                imagefill($new_image, 0, 0, $current_transparent);
                imagecolortransparent($new_image, $current_transparent);
            } elseif ($this->image_type == IMAGETYPE_PNG) {
                imagealphablending($new_image, false);
                $color = imagecolorallocatealpha($new_image, 0, 0, 0, 127);
                imagefill($new_image, 0, 0, $color);
                imagesavealpha($new_image, true);
            }
        }
        return $new_image;
    }
    
    public function createGradient($x, $y, $xBlend = false ,$yBlend = false)
    {
        $w = $this->getWidth();
        $h = $this->getHeight();
        $new_image = imagecreatetruecolor($w, $h);
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
        imagecopy($new_image, $this->image, 0, 0, 0, 0, $w, $h);
        $dx = $w - $x;
        $dy = $h - $y;
        if($x < 0){ $dx = $x * -1; $x = $w - $dx; }
        if($y < 0){ $dy = $y * -1; $y = $h - $dy; }
        $xp = $yp = 0;
        $max = ((($yBlend ? $dy : 0) + ($xBlend ? $dx : 0))/2);
        $p = 1 / $max;
        imagesavealpha($new_image,true);
        for($i = 0; $i < $dx; $i++) {
            if($xBlend) $xp = ($i - $dx) * -1;
            for ($j = 0; $j < $dy; $j++) {
                if($yBlend) $yp = ($j - $dy) * -1;
                $result = sqrt(($xp + $yp) * ($xp + $yp));
                $pointX = $x + $i;
                $pointY = $y + $j;
                $rgba = imagecolorat( $this->image, $x + $i , $y + $j );
                $a = ($rgba >> 24) & 0x7F; $r = ($rgba >> 16) & 0xFF; $g = ($rgba >> 8) & 0xFF; $b = $rgba & 0xFF;
                $color = imagecolorallocatealpha( $new_image , $r, $g, $b, 127 - (127 * ( $result < $max ? ($result / $max) : 1 )));
                imagesetpixel( $new_image , $x + $i , $y + $j , $color );
            }
        }
        imagealphablending($new_image, false);
        imagesavealpha($new_image,true);
        $this->image = $new_image;
    }
}

?>
