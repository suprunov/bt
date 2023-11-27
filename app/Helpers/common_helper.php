<?php

namespace App\Helpers;

class StringHelper {

    public static function transliterate(string $string): string
    {
        $transliterateArr = array(
            "А" => "A", "Б" => "B", "В" => "V", "Г" => "G",
            "Д" => "D", "Е" => "E", "Ж" => "J", "З" => "Z", "И" => "I",
            "Й" => "Y", "К" => "K", "Л" => "L", "М" => "M", "Н" => "N",
            "О" => "O", "П" => "P", "Р" => "R", "С" => "S", "Т" => "T",
            "У" => "U", "Ф" => "F", "Х" => "H", "Ц" => "TS", "Ч" => "CH",
            "Ш" => "SH", "Щ" => "SCH", "Ъ" => "", "Ы" => "YI", "Ь" => "",
            "Э" => "YE", "Ю" => "YU", "Я" => "YA", "а" => "a", "б" => "b",
            "в" => "v", "г" => "g", "д" => "d", "е" => "e", "ё" => "yo",
            "ж" => "j", "з" => "z", "и" => "i", "й" => "y", "к" => "k",
            "л" => "l", "м" => "m", "н" => "n", "о" => "o", "п" => "p",
            "р" => "r", "с" => "s", "т" => "t", "у" => "u", "ф" => "f",
            "х" => "h", "ц" => "ts", "ч" => "ch", "ш" => "sh", "щ" => "sch",
            "ъ" => "y", "ы" => "yi", "ь" => "", "э" => "ye", "ю" => "yu",
            "я" => "ya"
        );
        return strtr($string, $transliterateArr);
    }

    public static function makeCode(string $string, $lowercase = true): string
    {
        if ($string === null)
            return '';

        $code = preg_replace('~[^a-zA-Z0-9]+~', '-', self::transliterate($string));
        $code = trim($code, '-');

        return $lowercase ? strtolower($code): $code;
    }

    public static function mb_ucfirst($string)
    {
        $capitalLetter = mb_strtoupper(mb_substr($string, 0, 1));
        return $capitalLetter . mb_substr($string, 1);
    }

}

class ArrayHelper {

    public static function array_make_keys(array $array, string $key): array
    {
        return array_combine(array_column($array, $key), $array);
    }

    public static function array_column_multi($array, $column)
    {
        $types = array_unique(array_column($array, $column));
        $return = [];
        foreach ($types as $type) {
            foreach ($array as $key => $value) {
                if ($type === $value[$column]) {
                    unset($value[$column]);
                    $return[$type][] = $value;
                    unset($array[$key]);
                }
            }
        }
        return $return;
    }

}

class HTMLHelper {

    public static function tag($tagName, $attributes = array(), $content = '')
    {
        if (empty($tagName))
            RETURN $content;

        $attrStr = '';
        if (is_array($attributes))
            foreach ($attributes as $k => $v)
                $attrStr .= " {$k}=\"{$v}\"";
        unset($k, $v);

        RETURN "<{$tagName}{$attrStr}>" . $content . "</{$tagName}>";
    }

    public static function script($content)
    {
        RETURN self::tag(
            'script',
            array('type' => 'text/javascript'),
            $content
        );
    }

    public static function array2jsvar($array, $varname)
    {
        RETURN self::script("var {$varname} = " . json_encode($array));
    }

    public static function hint(string $text, bool $stripTags = true)
    {
        $textFormatted = mb_ereg_replace('"', "'", $text);
        return $stripTags ? strip_tags($textFormatted) : $textFormatted;
    }

    public static function esc($text)
    {
        return htmlentities($text, ENT_COMPAT, "UTF-8");
    }

}

class NameHelper {

    public static array $productNameTemplates = [
        'tyres' => [
            'default'             => '{{width}}/{{profile}} {{construction}}{{diameter}}{{ power_c.alias}} {{power}}{{speed}}{{ power_xl.alias}}{{ runflat.alias}}{{ hmg}}{{ info}}',
            'default_type'        => 'Шины{{ offroad.alias}}',
            'default_alt'         => '{{season.alias}} шина {{brand}} {{model}}',
            'feed_full'           => '{{spikes.alias}} {{season.alias}} шина {{brand}} {{model}} {{width}}/{{profile}} {{construction}}{{diameter}}{{ power_c.alias}} {{power}}{{speed}}{{ power_xl.alias}}{{ runflat.alias}}{{ hmg}}{{ info}}',
            /*'yml_simple' => 'Автомобильная шина {{brand}} {{model}} {{width}}/{{profile}} R{{diameter}} {{power}}{{speed}}{{ runflat.alias}}{{ power_xl.alias}}{{ power_c.alias}} {{season.alias}}',
            'yml_direct' => '{{season.alias}} шина {{brand}} {{model}} {{width}}/{{profile}} R{{diameter}} {{power}}{{speed}}{{ runflat.alias}}{{ power_xl.alias}}{{ power_c.alias}}',*/
        ],
        'disks' => [
            'default'         => '{{width}}x{{diameter}}/{{bolt}}x{{pcd}} D{{dia}} ET{{et}} {{color}}{{ info}}',
            'default_type'    => 'Диски',
            'default_alt'     => '{{type}} диск {{brand}} {{model}}',
            /*'yml_simple' => 'Колесный диск {{brand}} {{model}} {{width}}x{{diameter}}/{{bolt}}x{{pcd}} D{{dia}} ET{{et}}, {{color_group}}',
            'yml_direct' => '{{type}} диск {{brand}} {{model}} {{width}}x{{diameter}}/{{bolt}}x{{pcd}} D{{dia}} ET{{et}}, {{color_group}}',*/
        ],
        'mototyres' => [
            'default'         => '{{width}}/{{profile}} {{construction}}{{diameter}} {{power}}{{speed}} {{typet}}{{ info}}',
            'default_type'    => 'Мотошины',
            'default_alt'     => '{{season.alias}} шина {{brand}} {{model}}',
            /*'short'      => '{{width}}/{{profile}} {{construction}}{{diameter}}',
            'yml_simple' => 'Шина для мотоцикла {{brand}} {{model}}, {{axis.alias}} {{profile}} {{width}} {{diameter}} {{speed}} ({{speed.alias}}) {{power}} {{typet}}',*/
        ]
    ];

    public static function product(string $productTypeCode, array $replacements, ?string $templateName = 'default'): ?string
    {
        $template = self::$productNameTemplates[$productTypeCode][$templateName] ?? null;
        if ($template) {
            $productName = self::fillTemplate($template, $replacements);
            return StringHelper::mb_ucfirst(trim($productName));
        }
        return null;
    }

    /**
     * @param string $template
     * @param array $replacements [code, value, value_description]
     * @return string
     */
    private static function fillTemplate(string $template, array $replacements): string
    {
        foreach ($replacements as $replacement)
        {
            $replacement = (array)$replacement;
            $code        = $replacement['code'];
            $value       = $replacement['value'];
            $valueAlias  = $replacement['value_alias'];

            if ($value && $valueAlias !== null) {
                $template = preg_replace(
                    "~{{([\s,]*){$code}\.alias([\s,]*)}}~", '${1}' . $valueAlias . '${2}',
                    $template
                );
            }
            if ($value !== null) {
                $template = preg_replace(
                    "~{{([\s,]*){$code}(?!\.)([\s,]*)}}~", '${1}' . $value . '${2}',
                    $template
                );
            }
        }
        return preg_replace('~{{[^{}]+}}~', '', $template);
    }

}

class UriHelper
{
    public static function product(?string $code, int $id): string
    {
        return '/product/' . urlencode($code) . '-' . $id . '/';
    }

    public static function brand(string $code, int $id): string
    {
        return '/brand/' . urlencode($code) . '-' . $id . '/';
    }

    public static function model(string $code, int $id): string
    {
        return '/model/' . urlencode($code) . '-' . $id . '/';
    }
}

class PhoneHelper
{
    /**
     * Форматирование кода телефона
     *
     * @var string $code
     * @var ?array $options [first_8, clear_int] - first_8 - всегда 8 в начале, clear_int - без пробелов, скобочек и тире для линков
     * @return string
     */
    public static function formatCode(string $code, ?array $options=[]):string
    {
        if (isset($options['first_8']))
            $return = '8 (' . $code . ')';
        else
            if ($code == '800')
                $return = '8 (' . $code . ')';
            else
                $return = '+7 (' . $code . ')';

        if (isset($options['clear_int']))
            return str_replace([' ', '(', ')', '-'], '', $return);
        else
            return $return;
    }

    /**
     * Форматирование телефона (без кода)
     *
     * @var string $number
     * @var ?array $options  [clear_int]
     * @return string
     */
    public static function formatNumber(string $number, ?array $options=[]): string
    {
        $return = substr($number, 0, strlen($number) - 4) . '-' . substr($number, -4, 2) . '-' . substr($number, -2, 2);
        if (isset($options['clear_int']))
            return str_replace([' ', '(', ')', '-'], '', $return);
        else
            return $return;
    }

    /**
     * Форматирование телефона
     *
     * @var string $code - допускается пустым для clear и clear_int
     * @var string $number
     * @var ?array $options  [type => default|clear|clear_7|clear_int|format_7|format_7_wop]
     * @return ?string
     */
    public static function format(string $code, string $number, ?array $options = ['type' => 'default']): ?string
    {
        $phone = null;

        switch ($options['type']) {
            case 'clear': // 9999999999 (10 digits)
                $number_full = preg_replace('~[^0-9]~', '', $code . $number);
                if (in_array(strlen($number_full), [10, 11]))
                    $phone = substr($number_full, -10);
                break;
            case 'clear_7': // 79999999999 (11 digits)
                $number_full = preg_replace('~[^0-9]~', '', $code . $number);
                if (in_array(strlen($number_full), [10, 11]))
                    $phone = '7' . substr($number_full, -10);
                break;
            case 'clear_int': // +79999999999
                $number_full = preg_replace('~[^0-9]~', '', $code . $number);
                if (in_array(strlen($number_full), [10, 11]))
                    $phone = '+7' . substr($number_full, -10);
                break;
            case 'format_7': // +7 (999) 999-99-99
                if (!$code) {
                    $number = self::format('',$number, ['type' => 'clear_int']);
                    $code = substr($number, 2, 3);
                    $number = substr($number, -7);
                }
                $phone = '+7 (' . $code . ') ' . preg_replace('~^([0-9]{' . (10 - strlen($code) - 4) . '})([0-9]{2})([0-9]{2})$~', '$1-$2-$3', $number);
                break;
            case 'format_7_wop': // 7 (999) 999-99-99
                if (!$code) {
                    $number = self::format('',$number, ['type' => 'clear_int']);
                    $code = substr($number, 2, 3);
                    $number = substr($number, -7);
                }
                $phone = '7 (' . $code . ') ' . preg_replace('~^([0-9]{' . (10 - strlen($code) - 4) . '})([0-9]{2})([0-9]{2})$~', '$1-$2-$3', $number);
                break;
            case 'default': // 8 (999) 999-99-99
            default:
            {
                if (!$code) {
                    $number = self::format('',$number, ['type' => 'clear_int']);
                    $code = substr($number, 2, 3);
                    $number = substr($number, -7);
                }
                $phone = '8 (' . $code . ') ' . preg_replace('~^([0-9]{' . (10 - strlen($code) - 4) . '})([0-9]{2})([0-9]{2})$~', '$1-$2-$3', $number);
            }
        }

        return $phone;
    }
}

class ImgHelper {

    public const IMG_NOLOGO = '/img/icons/nofoto_big.gif';
    public const IMG_ALLOWED_EXT = 'jpg,jpeg,jpe,gif,png,webp,svg';

    /**
     * Путь к картинке
     * IMG_NOLOGO - если пустая
     * и всегда клеим версию релиза
     *
     * @param string|null $path - путь может быть относительный и полный урл
     * @return string
     * */
    public static function path(string|null $path): string
    {
        $path = str_replace('/media/media/','/media/',$path);
        $pathExtension = strtolower(pathinfo(parse_url($path, PHP_URL_PATH), PATHINFO_EXTENSION));
        if (! $path || ! in_array($pathExtension, ['jpg', 'jpeg', 'svg', 'png', 'gif', 'webp']))
            return self::IMG_NOLOGO . '?v=' . RELEASE_VERSION ;
        return $path .  '?v=' . RELEASE_VERSION ;
    }

    /**
     * Проверяем картинку на диске
     * При $path = url если картинка находится у нас
     * @param string|null $path - путь может быть относительный, полный серверный и полный урл
     * @return bool
     */
    public static function check(null|string $path): bool
    {
        if (! $path)
            return false;

        $path = element('path', parse_url(trim($path)));//на случай uri выбираем только path
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $full_path = trim($path);

        if (mb_strpos($full_path, $_SERVER['DOCUMENT_ROOT']) !== 0)
            $full_path = $_SERVER['DOCUMENT_ROOT'] . $full_path;

        if ($extension == 'svg')
            return is_file($full_path);
        else
            return is_array(@getimagesize($full_path));
    }

    /**
     * Создание картинки
     *
     * @param ?string $src
     * @param ?array $attributes
     * @param ?array $options [lazy_load, lazy_slider, lazy] lazy(load|slider) - replacement for [lazy_load, lazy_slider]
     * @return string
     * */
    public static function tag(?string $src, ?array $attributes = [], ?array $options = []): string
    {
        $src = self::path($src);

        if (isset($options['lazy_load']) || (isset($options['lazy']) && $options['lazy'] == 'load')) {
            $attributes['class'] .= ' lazyload';
            $attributes['data-src'] = $src;
        } elseif (isset($options['lazy_slider']) || (isset($options['lazy']) && $options['lazy'] == 'slider')) {
            $attributes['class'] .= ' lazyslider';
            $attributes['data-src'] = $src;
        } else {
            $attributes['src'] = $src;
        }

        $attributesStr = '';
        foreach ($attributes as $attribute => $value)
            $attributesStr .= preg_replace('/[^a-zA-Z0-9-]/i', '', $attribute) . '="' . htmlspecialchars($value) . '" ';

        return '<img  ' . $attributesStr . '/>';
    }

    // File check on upload
    public static function checkImg($path, $extension = false)
    {
        if(!$path){
            return false;
        }
        if(!$extension){
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        }
        if(!$extension){
            return false;
        }

        if(($extension == 'webp' && version_compare(PHP_VERSION, '7.1.0') < 0) || $extension == 'svg'){
            $check = is_file($path);
        }else{
            $check = @getimagesize($path);
        }

        return (bool)$check;
    }

    /**
     * Find images in HTML content and replace them with a stub image if it doesn't exist
     */
    public static function checkImgsInContent($content)
    {
        $images = preg_match_all("~<img\s[^>]*?src\s*=\s*['\"]([^'\"]*?)['\"][^>]*?>~", $content, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            if ( ! self::checkImg($_SERVER['DOCUMENT_ROOT'] . $match[1])) {
                $content = str_replace( "{$match[1]}", self::IMG_NOLOGO, $content);
            }
        }
        return $content;
    }

    public static function checkExt($name = false)
    {
        if(!$name || !is_string($name)){
            return false;
        }
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if(!$extension){
            return false;
        }
        $validExtensions = explode(',', self::IMG_ALLOWED_EXT);
        return in_array($extension, $validExtensions);
    }

    public static function allowedFilesExtJS()
    {
        return json_encode(explode(',', self::IMG_ALLOWED_EXT));
    }

    public static function imgSize($path, $format = 'default')
    {
        $img = self::check($path, true);
        $img_size = $img ? getimagesize($_SERVER['DOCUMENT_ROOT'] . $img) : false;
        $img_size_formatted = null;
        if ($img_size)
            switch ($format) {
                case 'default': $img_size_formatted = $img_size[0] . ' x ' . $img_size[1];
            }
        return $img_size_formatted;
    }

}
