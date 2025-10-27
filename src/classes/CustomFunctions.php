<?php
namespace EndrockTheme\Classes;

use Timber\Timber;

class CustomFunctions
{

    public function __construct()
    {
    }

    public function json_decode(String $json)
    {
        return json_decode($json, true);
    }

    public function getField(String $name, Int $id)
    {
        return \get_field($name, $id, true);
    }

    public function setPost(Int $postId){
        return Timber::get_post($postId, 'Timber\Post');
    }

    public function getPostMeta(String $key, Int $postId, Bool $single = true)
    {
        $value = \get_post_meta($postId, $key, $single);
        
        // Si viene serializado, deserializar
        if (is_string($value) && $single) {
            $unserialized = maybe_unserialize($value);
            if ($unserialized !== false) {
                return $unserialized;
            }
        }
        
        return $value;
    }

}
