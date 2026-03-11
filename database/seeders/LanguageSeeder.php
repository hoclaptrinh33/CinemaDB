<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            // Ngôn ngữ nhiều người nói nhất
            ['iso_code' => 'en', 'language_name' => 'English'],
            ['iso_code' => 'zh', 'language_name' => 'Chinese (Mandarin)'],
            ['iso_code' => 'hi', 'language_name' => 'Hindi'],
            ['iso_code' => 'es', 'language_name' => 'Spanish'],
            ['iso_code' => 'fr', 'language_name' => 'French'],
            ['iso_code' => 'ar', 'language_name' => 'Arabic'],
            ['iso_code' => 'bn', 'language_name' => 'Bengali'],
            ['iso_code' => 'ru', 'language_name' => 'Russian'],
            ['iso_code' => 'pt', 'language_name' => 'Portuguese'],
            ['iso_code' => 'ur', 'language_name' => 'Urdu'],
            ['iso_code' => 'id', 'language_name' => 'Indonesian'],
            ['iso_code' => 'de', 'language_name' => 'German'],
            ['iso_code' => 'ja', 'language_name' => 'Japanese'],
            ['iso_code' => 'sw', 'language_name' => 'Swahili'],
            ['iso_code' => 'mr', 'language_name' => 'Marathi'],
            ['iso_code' => 'te', 'language_name' => 'Telugu'],
            ['iso_code' => 'ta', 'language_name' => 'Tamil'],
            ['iso_code' => 'tr', 'language_name' => 'Turkish'],
            ['iso_code' => 'vi', 'language_name' => 'Vietnamese'],
            ['iso_code' => 'ko', 'language_name' => 'Korean'],
            // Châu Âu
            ['iso_code' => 'it', 'language_name' => 'Italian'],
            ['iso_code' => 'pl', 'language_name' => 'Polish'],
            ['iso_code' => 'uk', 'language_name' => 'Ukrainian'],
            ['iso_code' => 'nl', 'language_name' => 'Dutch'],
            ['iso_code' => 'ro', 'language_name' => 'Romanian'],
            ['iso_code' => 'el', 'language_name' => 'Greek'],
            ['iso_code' => 'hu', 'language_name' => 'Hungarian'],
            ['iso_code' => 'cs', 'language_name' => 'Czech'],
            ['iso_code' => 'sv', 'language_name' => 'Swedish'],
            ['iso_code' => 'da', 'language_name' => 'Danish'],
            ['iso_code' => 'fi', 'language_name' => 'Finnish'],
            ['iso_code' => 'no', 'language_name' => 'Norwegian'],
            ['iso_code' => 'sk', 'language_name' => 'Slovak'],
            ['iso_code' => 'hr', 'language_name' => 'Croatian'],
            ['iso_code' => 'bg', 'language_name' => 'Bulgarian'],
            ['iso_code' => 'sr', 'language_name' => 'Serbian'],
            ['iso_code' => 'lt', 'language_name' => 'Lithuanian'],
            ['iso_code' => 'lv', 'language_name' => 'Latvian'],
            ['iso_code' => 'et', 'language_name' => 'Estonian'],
            ['iso_code' => 'sl', 'language_name' => 'Slovenian'],
            ['iso_code' => 'mk', 'language_name' => 'Macedonian'],
            ['iso_code' => 'bs', 'language_name' => 'Bosnian'],
            ['iso_code' => 'be', 'language_name' => 'Belarusian'],
            ['iso_code' => 'sq', 'language_name' => 'Albanian'],
            ['iso_code' => 'ca', 'language_name' => 'Catalan'],
            ['iso_code' => 'gl', 'language_name' => 'Galician'],
            ['iso_code' => 'eu', 'language_name' => 'Basque'],
            ['iso_code' => 'cy', 'language_name' => 'Welsh'],
            ['iso_code' => 'ga', 'language_name' => 'Irish'],
            ['iso_code' => 'mt', 'language_name' => 'Maltese'],
            ['iso_code' => 'is', 'language_name' => 'Icelandic'],
            ['iso_code' => 'lb', 'language_name' => 'Luxembourgish'],
            ['iso_code' => 'fy', 'language_name' => 'Frisian'],
            // Trung Đông & Trung Á
            ['iso_code' => 'fa', 'language_name' => 'Persian (Farsi)'],
            ['iso_code' => 'he', 'language_name' => 'Hebrew'],
            ['iso_code' => 'az', 'language_name' => 'Azerbaijani'],
            ['iso_code' => 'kk', 'language_name' => 'Kazakh'],
            ['iso_code' => 'hy', 'language_name' => 'Armenian'],
            ['iso_code' => 'ka', 'language_name' => 'Georgian'],
            ['iso_code' => 'uz', 'language_name' => 'Uzbek'],
            ['iso_code' => 'tk', 'language_name' => 'Turkmen'],
            ['iso_code' => 'ps', 'language_name' => 'Pashto'],
            ['iso_code' => 'ku', 'language_name' => 'Kurdish'],
            // Đông Nam Á & Nam Á
            ['iso_code' => 'th', 'language_name' => 'Thai'],
            ['iso_code' => 'ms', 'language_name' => 'Malay'],
            ['iso_code' => 'tl', 'language_name' => 'Filipino'],
            ['iso_code' => 'km', 'language_name' => 'Khmer'],
            ['iso_code' => 'lo', 'language_name' => 'Lao'],
            ['iso_code' => 'my', 'language_name' => 'Burmese'],
            ['iso_code' => 'si', 'language_name' => 'Sinhala'],
            ['iso_code' => 'ne', 'language_name' => 'Nepali'],
            ['iso_code' => 'jv', 'language_name' => 'Javanese'],
            ['iso_code' => 'su', 'language_name' => 'Sundanese'],
            // Nam Á (ngôn ngữ Ấn Độ)
            ['iso_code' => 'pa', 'language_name' => 'Punjabi'],
            ['iso_code' => 'gu', 'language_name' => 'Gujarati'],
            ['iso_code' => 'ml', 'language_name' => 'Malayalam'],
            ['iso_code' => 'kn', 'language_name' => 'Kannada'],
            ['iso_code' => 'or', 'language_name' => 'Odia'],
            // Đông Á
            ['iso_code' => 'mn', 'language_name' => 'Mongolian'],
            // Châu Phi
            ['iso_code' => 'am', 'language_name' => 'Amharic'],
            ['iso_code' => 'yo', 'language_name' => 'Yoruba'],
            ['iso_code' => 'ig', 'language_name' => 'Igbo'],
            ['iso_code' => 'ha', 'language_name' => 'Hausa'],
            ['iso_code' => 'af', 'language_name' => 'Afrikaans'],
            ['iso_code' => 'zu', 'language_name' => 'Zulu'],
            ['iso_code' => 'xh', 'language_name' => 'Xhosa'],
            ['iso_code' => 'so', 'language_name' => 'Somali'],
            ['iso_code' => 'rw', 'language_name' => 'Kinyarwanda'],
            ['iso_code' => 'mg', 'language_name' => 'Malagasy'],
            ['iso_code' => 'sn', 'language_name' => 'Shona'],
            ['iso_code' => 'ny', 'language_name' => 'Chichewa'],
            ['iso_code' => 'st', 'language_name' => 'Sesotho'],
            // Châu Đại Dương
            ['iso_code' => 'mi', 'language_name' => 'Māori'],
            ['iso_code' => 'sm', 'language_name' => 'Samoan'],
            ['iso_code' => 'to', 'language_name' => 'Tongan'],
            // Khác
            ['iso_code' => 'la', 'language_name' => 'Latin'],
            ['iso_code' => 'eo', 'language_name' => 'Esperanto'],
            ['iso_code' => 'xx', 'language_name' => 'No Language / Silent'],
        ];

        DB::table('languages')->insertOrIgnore($languages);
    }
}
